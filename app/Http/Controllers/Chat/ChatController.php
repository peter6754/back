<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponseTrait;
use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\Secondaryuser as User;
use App\Services\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private ChatService $chatService) {}

    public function sendMessage(Request $request)
    {
        $validated = $request->validate([
            'conversation_id' => 'required|integer|exists:conversations,id',
            'message' => 'required|string',
            'type' => 'required|string|in:text,contact,media,gift',
            'gift' => 'nullable|string|required_if:type,gift',
            'contact_type' => 'nullable|string|required_if:type,contact',
        ]);

        $result = $this->chatService->emitMessage(
            $request->user()->id,
            $validated['conversation_id'],
            $validated['message'],
            $validated['type'],
            $validated['gift'] ?? null,
            $validated['contact_type'] ?? null
        );

        if ($result['error']) {
            return $this->error($result['error']['message'], $result['error']['status']);
        }

        return $this->success(['message' => 'Message sent successfully via WebSocket']);
    }

    public function sendMedia(Request $request)
    {
        $validated = $request->validate([
            'conversation_id' => 'required|integer|exists:conversations,id',
            'media' => 'required|array',
        ]);

        $this->chatService->sendMedia([
            'sender_id' => $request->user()->id,
            'conversation_id' => $validated['conversation_id'],
            'media' => $validated['media'],
        ]);

        return $this->success(['message' => 'Media sent successfully via WebSocket']);
    }

    public function sendGift(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|string|exists:users,id',
            'gift_id' => 'required|integer|exists:gifts,id',
            'is_first' => 'nullable|boolean',
        ]);

        $this->chatService->sendGift([
            'sender_id' => $request->user()->id,
            'user_id' => $validated['user_id'],
            'gift_id' => $validated['gift_id'],
        ], $validated['is_first'] ?? false);

        return $this->success(['message' => 'Gift sent successfully via WebSocket']);
    }

    /**
     * Get all conversations for the current user
     */
    public function getConversations(Request $request): JsonResponse
    {
        try {
            $userId = $request->user()->id;

            $conversations = Conversation::where(function ($query) use ($userId) {
                $query->where('user1_id', $userId)
                    ->orWhere('user2_id', $userId);
            })
                ->with([
                    'user1:id,name,is_online',
                    'user2:id,name,is_online',
                ])
                ->get()
                ->map(function ($conversation) use ($userId) {
                    // Determine the other user
                    $otherUser = $conversation->user1_id === $userId
                        ? $conversation->user2
                        : $conversation->user1;

                    // Get last message
                    $lastMessage = ChatMessage::where('conversation_id', $conversation->id)
                        ->orderBy('id', 'desc')
                        ->first();

                    // Get unread count for current user
                    $unreadCount = ChatMessage::where('conversation_id', $conversation->id)
                        ->where('receiver_id', $userId)
                        ->where('is_seen', false)
                        ->count();

                    // Determine if pinned for current user
                    $isPinned = $conversation->user1_id === $userId
                        ? $conversation->is_pinned_by_user1
                        : $conversation->is_pinned_by_user2;

                    return [
                        'chat_id' => $conversation->id,
                        'user' => [
                            'id' => $otherUser->id,
                            'name' => $otherUser->name,
                            'avatar_url' => null, // Add avatar logic if needed
                            'online' => $otherUser->is_online,
                        ],
                        'last_message' => $lastMessage ? [
                            'text' => $lastMessage->message,
                            'timestamp' => $lastMessage->created_at ?? now(),
                        ] : null,
                        'is_pinned' => $isPinned,
                        'unread_count' => $unreadCount,
                    ];
                })
                ->sortByDesc(function ($conversation) {
                    return $conversation['is_pinned'] ? 1 : 0;
                })
                ->values()
                ->toArray();

            return $this->success($conversations);

        } catch (\Exception $e) {
            return $this->error('Failed to fetch conversations', 500);
        }
    }

    /**
     * Get unread messages status for specific chat
     */
    public function getUnreadMessagesStatus(Request $request, int $chat_id): JsonResponse
    {
        try {
            $userId = $request->user()->id;

            // Verify that the user has access to this conversation
            $conversation = Conversation::where('id', $chat_id)
                ->where(function ($query) use ($userId) {
                    $query->where('user1_id', $userId)
                        ->orWhere('user2_id', $userId);
                })
                ->first();

            if (!$conversation) {
                return $this->error('Conversation not found or access denied', 404);
            }

            $unreadCount = ChatMessage::where('conversation_id', $chat_id)
                ->where('receiver_id', $userId)
                ->where('is_seen', false)
                ->count();

            return $this->success(['unread_count' => $unreadCount]);

        } catch (\Exception $e) {
            return $this->error('Failed to fetch unread status', 500);
        }
    }

    /**
     * Create or get existing conversation with a user
     */
    public function createConversation(Request $request, string $userId): JsonResponse
    {
        try {
            $currentUserId = $request->user()->id;

            // Check if user exists
            if (! User::find($userId)) {
                return $this->error('User not found', 404);
            }

            // Check if conversation already exists
            $existingConversation = Conversation::betweenUsers($currentUserId, $userId)->first();

            if ($existingConversation) {
                return $this->success([
                    'chat_id' => $existingConversation->id,
                    'created' => false,
                ]);
            }

            // Create new conversation
            $conversation = Conversation::create([
                'user1_id' => $currentUserId,
                'user2_id' => $userId,
                'is_pinned_by_user1' => false,
                'is_pinned_by_user2' => false,
            ]);

            return $this->success([
                'chat_id' => $conversation->id,
                'created' => true,
            ]);

        } catch (\Exception $e) {
            return $this->error('Failed to create conversation', 500);
        }
    }

    /**
     * Delete conversation (soft delete)
     */
    public function deleteConversation(Request $request, string $chatId): JsonResponse
    {
        try {
            $userId = $request->user()->id;

            $conversation = Conversation::where('id', $chatId)
                ->where(function ($query) use ($userId) {
                    $query->where('user1_id', $userId)
                        ->orWhere('user2_id', $userId);
                })
                ->first();

            if (! $conversation) {
                return $this->error('Conversation not found', 404);
            }

            // For now, we'll actually delete the conversation and messages
            // In a production app, you might want to implement soft deletes
            ChatMessage::where('conversation_id', $chatId)->delete();
            $conversation->delete();

            return $this->success(['success' => true]);

        } catch (\Exception $e) {
            return $this->error('Failed to delete conversation', 500);
        }
    }

    /**
     * Pin or unpin conversation
     */
    public function togglePinConversation(Request $request, string $chatId): JsonResponse
    {
        try {
            $userId = $request->user()->id;

            $conversation = Conversation::where('id', $chatId)
                ->where(function ($query) use ($userId) {
                    $query->where('user1_id', $userId)
                        ->orWhere('user2_id', $userId);
                })
                ->first();

            if (! $conversation) {
                return $this->error('Conversation not found', 404);
            }

            // Determine which user is making the request and toggle their pin status
            if ($conversation->user1_id === $userId) {
                $isPinned = ! $conversation->is_pinned_by_user1;
                $conversation->is_pinned_by_user1 = $isPinned;
            } else {
                $isPinned = ! $conversation->is_pinned_by_user2;
                $conversation->is_pinned_by_user2 = $isPinned;
            }

            $conversation->save();

            return $this->success(['pinned' => $isPinned]);

        } catch (\Exception $e) {
            return $this->error('Failed to toggle pin status', 500);
        }
    }
}
