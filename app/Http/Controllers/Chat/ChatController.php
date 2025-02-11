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

class ChatController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private ChatService $chatService) {}

    public function sendMessage(Request $request)
    {
        try {
            $userId = $request->user()->id;
            \Log::info('SendMessage - User ID: ' . $userId);
            \Log::info('SendMessage - Request data: ' . json_encode($request->all()));
            
            // Base validation rules
            $rules = [
                'conversation_id' => 'required|integer|exists:conversations,id',
                'type' => 'required|string|in:text,contact,media,gift',
                'gift' => 'nullable|string|required_if:type,gift',
                'contact_type' => 'nullable|string|required_if:type,contact',
            ];

            // Conditional validation based on message type
            if ($request->get('type') === 'media') {
                $rules['file'] = 'required|file|max:10240|mimes:jpg,jpeg,png,gif,mp4,mov,avi,pdf,doc,docx,txt';
                $rules['message'] = 'nullable|string';
            } else {
                $rules['message'] = 'required|string';
                $rules['file'] = 'nullable|file';
            }

            $validated = $request->validate($rules);

            // Handle file upload for media messages
            $messageContent = $validated['message'] ?? '';
            $fileUrl = null;
            $fileType = null;
            $filePath = null;

            if ($validated['type'] === 'media' && $request->hasFile('file')) {
                // Handle file upload
                $file = $request->file('file');
                $fileName = time() . '_' . $file->getClientOriginalName();
                
                // Store file
                $filePath = $file->storeAs('chat_media', $fileName, 'public');
                $fileUrl = asset('storage/' . $filePath);
                $fileType = $file->getMimeType();

                // Use file path as message content for media messages
                $messageContent = $filePath;
            }

            // Handle all messages (text, contact, gift, media) via WebSocket
            $result = $this->chatService->emitMessage(
                $userId,
                $validated['conversation_id'],
                $messageContent,
                $validated['type'],
                $validated['gift'] ?? null,
                $validated['contact_type'] ?? null,
                false,
                $fileUrl,
                $fileType
            );

            if ($result['error']) {
                return $this->error($result['error']['message'], $result['error']['status']);
            }

            $response = ['message' => 'Message sent successfully via WebSocket'];
            
            // Add file info for media messages
            if ($validated['type'] === 'media' && $fileUrl) {
                $response['file_url'] = $fileUrl;
                $response['file_type'] = $fileType;
            }
            
            return $this->success($response);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->error('Validation failed: ' . implode(', ', $e->validator->errors()->all()), 422);
        } catch (\Exception $e) {
            \Log::error('Failed to send message: ' . $e->getMessage());
            return $this->error('Failed to send message', 500);
        }
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
     * Get messages from a specific conversation
     */
    public function getMessages(Request $request, int $chat_id): JsonResponse
    {
        try {
            $userId = $request->user()->id;
            $page = (int) $request->get('page', 1);
            $limit = min((int) $request->get('limit', 30), 100); // Max 100 messages per page

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

            // Get messages with pagination
            $messages = ChatMessage::where('conversation_id', $chat_id)
                ->with(['sender:id,name'])
                ->orderBy('id', 'desc')
                ->offset(($page - 1) * $limit)
                ->limit($limit)
                ->get()
                ->reverse()
                ->values()
                ->map(function ($message) {
                    $messageData = [
                        'id' => $message->id,
                        'sender_id' => $message->sender_id,
                        'message' => $message->message,
                        'type' => $message->type,
                        'created_at' => $message->created_at,
                        'is_read' => $message->is_seen,
                        'gift' => $message->gift,
                        'contact_type' => $message->contact_type,
                    ];

                    // Add file information for media messages
                    if ($message->type === 'media' && $message->hasFile()) {
                        $messageData['file_url'] = $message->getFileUrl();
                        $messageData['file_extension'] = $message->getFileExtension();
                        $messageData['is_image'] = $message->isImage();
                        $messageData['is_video'] = $message->isVideo();
                        $messageData['is_document'] = $message->isDocument();
                    }

                    return $messageData;
                });

            // Mark messages as read for the current user
            ChatMessage::where('conversation_id', $chat_id)
                ->where('receiver_id', $userId)
                ->where('is_seen', false)
                ->update(['is_seen' => true]);

            return $this->success($messages);

        } catch (\Exception $e) {
            \Log::error('Failed to fetch messages: ' . $e->getMessage());
            return $this->error('Failed to fetch messages: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Upload media file to a conversation
     */
    public function uploadMedia(Request $request, int $chat_id): JsonResponse
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

            // Validate file upload
            $request->validate([
                'file' => 'required|file|max:10240|mimes:jpg,jpeg,png,gif,mp4,mov,avi,pdf,doc,docx,txt'
            ]);

            $file = $request->file('file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            
            // Store file in public/storage/chat_media directory
            $filePath = $file->storeAs('chat_media', $fileName, 'public');
            $fileUrl = asset('storage/' . $filePath);

            // Determine receiver (the other user in conversation)
            $receiverId = $conversation->user1_id === $userId 
                ? $conversation->user2_id 
                : $conversation->user1_id;

            // Create message record using ORM
            $message = ChatMessage::create([
                'conversation_id' => $chat_id,
                'sender_id' => $userId,
                'receiver_id' => $receiverId,
                'message' => $filePath, // Store file path as message content
                'type' => 'media',
                'is_seen' => false
            ]);

            return $this->success([
                'message_id' => $message->id,
                'file_url' => $fileUrl,
                'file_type' => $file->getMimeType()
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->error('Validation failed: ' . implode(', ', $e->validator->errors()->all()), 422);
        } catch (\Exception $e) {
            \Log::error('Failed to upload media: ' . $e->getMessage());
            return $this->error('Failed to upload media', 500);
        }
    }

    /**
     * Mark all messages in a conversation as read
     */
    public function markMessagesAsRead(Request $request, int $chat_id): JsonResponse
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

            // Mark all unread messages as read for the current user
            $updatedMessages = ChatMessage::where('conversation_id', $chat_id)
                ->where('receiver_id', $userId)
                ->where('is_seen', false)
                ->get();

            if ($updatedMessages->isNotEmpty()) {
                ChatMessage::where('conversation_id', $chat_id)
                    ->where('receiver_id', $userId)
                    ->where('is_seen', false)
                    ->update(['is_seen' => true]);

                // Messages marked as read - notification could be added here
            }

            return $this->success(['success' => true]);

        } catch (\Exception $e) {
            \Log::error('Failed to mark messages as read: ' . $e->getMessage());
            return $this->error('Failed to mark messages as read', 500);
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

    /**
     * Send typing indicator to conversation
     */
    public function sendTypingIndicator(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'conversation_id' => 'required|integer|exists:conversations,id',
                'is_typing' => 'required|boolean',
            ]);

            $userId = $request->user()->id;
            $conversationId = $validated['conversation_id'];

            // Verify user has access to conversation
            $conversation = Conversation::where('id', $conversationId)
                ->where(function ($query) use ($userId) {
                    $query->where('user1_id', $userId)
                        ->orWhere('user2_id', $userId);
                })
                ->first();

            if (!$conversation) {
                return $this->error('Conversation not found or access denied', 404);
            }

            // Determine receiver
            $receiverId = $conversation->user1_id === $userId 
                ? $conversation->user2_id 
                : $conversation->user1_id;

            // Typing indicator broadcast could be implemented here

            return $this->success(['success' => true]);

        } catch (\Exception $e) {
            return $this->error('Failed to send typing indicator', 500);
        }
    }

    /**
     * Update user online status
     */
    public function updateOnlineStatus(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'is_online' => 'required|boolean',
            ]);

            $userId = $request->user()->id;
            $isOnline = $validated['is_online'];

            // Update user online status
            User::where('id', $userId)->update([
                'is_online' => $isOnline,
                'last_seen_at' => $isOnline ? null : now(),
            ]);

            // Get users who should be notified (users in conversations)
            $notifyUsers = Conversation::where(function ($query) use ($userId) {
                $query->where('user1_id', $userId)
                    ->orWhere('user2_id', $userId);
            })
            ->get()
            ->map(function ($conversation) use ($userId) {
                return $conversation->user1_id === $userId 
                    ? $conversation->user2_id 
                    : $conversation->user1_id;
            })
            ->unique()
            ->values()
            ->toArray();

            // Online status change broadcast could be implemented here

            return $this->success([
                'is_online' => $isOnline,
                'last_seen' => $isOnline ? null : now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            return $this->error('Failed to update online status', 500);
        }
    }

    /**
     * Get conversation participants online status
     */
    public function getConversationOnlineStatus(Request $request, int $chat_id): JsonResponse
    {
        try {
            $userId = $request->user()->id;

            // Verify user has access to conversation
            $conversation = Conversation::where('id', $chat_id)
                ->where(function ($query) use ($userId) {
                    $query->where('user1_id', $userId)
                        ->orWhere('user2_id', $userId);
                })
                ->first();

            if (!$conversation) {
                return $this->error('Conversation not found or access denied', 404);
            }

            // Get the other participant
            $otherUserId = $conversation->user1_id === $userId 
                ? $conversation->user2_id 
                : $conversation->user1_id;

            $otherUser = User::find($otherUserId, ['id', 'name', 'is_online', 'last_seen_at']);

            return $this->success([
                'user' => [
                    'id' => $otherUser->id,
                    'name' => $otherUser->name,
                    'is_online' => $otherUser->is_online,
                    'last_seen' => $otherUser->last_seen_at?->toISOString(),
                ]
            ]);

        } catch (\Exception $e) {
            return $this->error('Failed to get online status', 500);
        }
    }
}
