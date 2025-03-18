<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponseTrait;
use App\Models\ChatMessage;
use App\Models\ConnectedAccount;
use App\Models\Conversation;
use App\Models\Secondaryuser as User;
use App\Models\UserImage;
use App\Models\UserReaction;
use App\Services\ChatService;
use App\Services\SeaweedFsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private ChatService $chatService, private SeaweedFsService $seaweedFsService) {}

    /**
     * Send message to conversation
     *
     * @OA\Post(
     *     path="/api/conversations/send-messages",
     *     tags={"Chat"},
     *     summary="Send a message to a conversation",
     *     description="Send text, media, gift, or contact message to a conversation",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *
     *             @OA\Schema(
     *
     *                 @OA\Property(
     *                     property="conversation_id",
     *                     type="integer",
     *                     description="Conversation ID",
     *                     example=1
     *                 ),
     *                 @OA\Property(
     *                     property="type",
     *                     type="string",
     *                     enum={"text", "contact", "media", "gift"},
     *                     description="Message type",
     *                     example="text"
     *                 ),
     *                 @OA\Property(
     *                     property="message",
     *                     type="string",
     *                     description="Message text (required for text messages, optional for media)",
     *                     example="Hello there!"
     *                 ),
     *                 @OA\Property(
     *                     property="file",
     *                     type="string",
     *                     format="binary",
     *                     description="Media file (required for media type)"
     *                 ),
     *                 @OA\Property(
     *                     property="gift",
     *                     type="string",
     *                     description="Gift identifier (required for gift type)",
     *                     nullable=true
     *                 ),
     *                 @OA\Property(
     *                     property="contact_type",
     *                     type="string",
     *                     description="Contact type (required for contact type)",
     *                     nullable=true
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Message sent successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="status", type="integer", example=200)
     *             ),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="message", type="string", example="Message sent successfully via WebSocket"),
     *                 @OA\Property(property="file_url", type="string", nullable=true, example="http://example.com/storage/chat_media/file.jpg"),
     *                 @OA\Property(property="file_type", type="string", nullable=true, example="image/jpeg")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Conversation not found or access denied"),
     *     @OA\Response(response=422, description="Validation failed"),
     *     @OA\Response(response=500, description="Failed to send message")
     * )
     */
    public function sendMessage(Request $request)
    {
        try {
            $userId = $request->user()->id;
            \Log::info('SendMessage - User ID: '.$userId);
            \Log::info('SendMessage - Request data: '.json_encode($request->all()));

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

            if ($validated['type'] === 'media' && $request->hasFile('file')) {
                // Handle file upload to SeaweedFS
                $file = $request->file('file');

                // Upload to SeaweedFS and get FID
                $fid = $this->seaweedFsService->uploadToStorage(
                    file_get_contents($file->getRealPath()),
                    $file->getClientOriginalName()
                );

                // Store only FID as file_url
                $fileUrl = $fid;
                $fileType = $file->getMimeType();

                // Use FID as message content for media messages
                $messageContent = $fid;
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
            return $this->error('Validation failed: '.implode(', ', $e->validator->errors()->all()), 422);
        } catch (\Exception $e) {
            \Log::error('Failed to send message: '.$e->getMessage());

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
     * Get all conversations for the current user with mutual matches
     *
     * @OA\Get(
     *     path="/api/conversations",
     *     tags={"Chat"},
     *     summary="Get all conversations for the current user with mutual matches",
     *     description="Returns list of conversations and users with mutual likes (matches)",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of conversations and matched users",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="status", type="integer", example=200)
     *             ),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="conversations", type="array", description="List of existing conversations",
     *
     *                     @OA\Items(
     *
     *                         @OA\Property(property="chat_id", type="integer", example=1, description="Conversation ID"),
     *                         @OA\Property(property="user", type="object", description="Other participant in conversation",
     *                             @OA\Property(property="id", type="string", example="user-uuid", description="User ID"),
     *                             @OA\Property(property="name", type="string", example="John Doe", description="User name"),
     *                             @OA\Property(property="avatar_url", type="string", nullable=true, example="http://example.com/avatar.jpg", description="User avatar URL"),
     *                             @OA\Property(property="online", type="boolean", example=true, description="User online status")
     *                         ),
     *                         @OA\Property(property="last_message", type="object", nullable=true, description="Last message in conversation",
     *                             @OA\Property(property="user_id", type="string", example="sender-uuid", description="Message sender ID"),
     *                             @OA\Property(property="username", type="string", example="John Doe", description="Message sender name"),
     *                             @OA\Property(property="text", type="string", example="Hello!", description="Message text"),
     *                             @OA\Property(property="type", type="string", example="text", enum={"text", "media"}, description="Message type"),
     *                             @OA\Property(property="timestamp", type="string", format="date-time", description="Message timestamp")
     *                         ),
     *                         @OA\Property(property="is_pinned", type="boolean", example=false, description="Whether conversation is pinned by current user"),
     *                         @OA\Property(property="unread_count", type="integer", example=2, description="Number of unread messages")
     *                     )
     *                 ),
     *                 @OA\Property(property="match", type="array", description="List of users with mutual likes (matches)",
     *
     *                     @OA\Items(
     *
     *                         @OA\Property(property="id", type="string", example="match-user-uuid", description="Matched user ID"),
     *                         @OA\Property(property="name", type="string", example="Jane Smith", description="Matched user name"),
     *                         @OA\Property(property="avatar_url", type="string", nullable=true, example="http://example.com/match-avatar.jpg", description="Matched user avatar URL")
     *                     )
     *                 ),
     *                 @OA\Property(property="match_count", type="integer", example=5, description="Total number of mutual matches")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
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

                    // находим аватар
                    $mainImage = UserImage::where('user_id', $otherUser->id)
                        ->where('is_main', true)
                        ->first();

                    // Если нет главного изображения, берем первое доступное
                    if (! $mainImage) {
                        $mainImage = UserImage::where('user_id', $otherUser->id)
                            ->first();
                    }

                    $avatarUrl = null;
                    if ($mainImage && $mainImage->image) {
                        $avatarUrl = $mainImage->image;
                    }

                    // Get last message
                    $lastMessage = ChatMessage::where('conversation_id', $conversation->id)
                        ->with('sender:id,name')
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
                            'avatar_url' => $avatarUrl,
                            'online' => $otherUser->is_online,
                        ],
                        'last_message' => $lastMessage ? [
                            'user_id' => $lastMessage->sender_id,
                            'username' => $lastMessage->sender->name ?? null,
                            'text' => $lastMessage->message,
                            'type' => $lastMessage->type === 'media' ? 'media' : 'text',
                            'timestamp' => $lastMessage->date ? $lastMessage->date->utc()->toISOString() : now()->utc()->toISOString(),
                        ] : null,
                        'is_pinned' => $isPinned,
                        'unread_count' => $unreadCount,
                    ];
                })
                ->sortByDesc(function ($conversation) {
                    // получаем timestamps для сортировки
                    $timestamp = 0;
                    if ($conversation['last_message'] && $conversation['last_message']['timestamp']) {
                        try {
                            $timestamp = \Carbon\Carbon::parse($conversation['last_message']['timestamp'])->timestamp;
                        } catch (\Exception $e) {
                            $timestamp = 0;
                        }
                    }

                    // если закреп, добавим число
                    if ($conversation['is_pinned']) {
                        return $timestamp + 9999999999;
                    }

                    return $timestamp;
                })
                ->values()
                ->toArray();

            // Get users with mutual likes(matches)
            $mutualMatchIds = \DB::table('user_reactions as ur1')
                ->join('user_reactions as ur2', function ($join) {
                    $join->on('ur1.reactor_id', '=', 'ur2.user_id')
                        ->on('ur1.user_id', '=', 'ur2.reactor_id');
                })
                ->where('ur1.reactor_id', $userId)
                ->whereIn('ur1.type', ['like', 'superlike'])
                ->whereIn('ur2.type', ['like', 'superlike'])
                ->pluck('ur1.user_id')
                ->unique();

            // Get matched users with their avatars
            $matches = User::whereIn('id', $mutualMatchIds)
                ->select('id', 'name')
                ->get()
                ->map(function ($user) {
                    // Get avatar for match user
                    $mainImage = UserImage::where('user_id', $user->id)
                        ->where('is_main', true)
                        ->first();

                    if (! $mainImage) {
                        $mainImage = UserImage::where('user_id', $user->id)
                            ->first();
                    }

                    $avatarUrl = null;
                    if ($mainImage && $mainImage->image) {
                        $avatarUrl = $mainImage->image;
                    }

                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'avatar_url' => $avatarUrl,
                    ];
                })->toArray();

            $response = [
                'conversations' => $conversations,
                'match' => $matches,
                'match_count' => count($matches),
            ];

            return $this->success($response);

        } catch (\Exception $e) {
            return $this->error('Failed to fetch conversations', 500);
        }
    }

    /**
     * Create or get existing conversation with a user
     *
     * @OA\Post(
     *     path="/api/conversations",
     *     tags={"Chat"},
     *     summary="Create or get existing conversation with a user",
     *     description="Creates a new conversation or returns existing one. Requires mutual likes between users.",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="user_id",
     *                 type="string",
     *                 description="Target user ID",
     *                 example="user-uuid"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Conversation created or retrieved",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="status", type="integer", example=200)
     *             ),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="chat_id", type="integer", example=1),
     *                 @OA\Property(property="created", type="boolean", example=true)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Conversation can only be created after mutual likes"),
     *     @OA\Response(response=404, description="User not found")
     * )
     */
    public function createConversation(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|string|exists:users,id',
            ]);

            $currentUserId = $request->user()->id;
            $userId = $validated['user_id'];

            // Check for mutual likes - conversation can only be created if both users liked each other
            if (! UserReaction::haveMutualLikes($currentUserId, $userId)) {
                return $this->error('Conversation can only be created after mutual likes', 403);
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
     * Delete conversation
     *
     * @OA\Delete(
     *     path="/api/conversations/{chat_id}",
     *     tags={"Chat"},
     *     summary="Delete conversation and all its messages",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="chat_id",
     *         in="path",
     *         required=true,
     *         description="Chat ID",
     *
     *         @OA\Schema(type="string", example="1")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Conversation deleted",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="status", type="integer", example=200)
     *             ),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="success", type="boolean", example=true)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Conversation not found")
     * )
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
     *
     * @OA\Get(
     *     path="/api/conversations/{chat_id}/messages",
     *     tags={"Chat"},
     *     summary="Get messages from a specific conversation with pagination",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="chat_id",
     *         in="path",
     *         required=true,
     *         description="Chat ID",
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *
     *         @OA\Schema(type="integer", example=1, default=1)
     *     ),
     *
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Messages per page (max 100)",
     *
     *         @OA\Schema(type="integer", example=30, default=30)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of messages",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="status", type="integer", example=200)
     *             ),
     *             @OA\Property(property="data", type="array",
     *
     *                 @OA\Items(
     *
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="sender_id", type="string", example="user-uuid"),
     *                     @OA\Property(property="message", type="string", example="Hello!"),
     *                     @OA\Property(property="type", type="string", enum={"text", "media", "gift", "contact"}, example="text"),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="is_read", type="boolean", example=true),
     *                     @OA\Property(property="gift", type="string", nullable=true),
     *                     @OA\Property(property="contact_type", type="string", nullable=true),
     *                     @OA\Property(property="file_url", type="string", nullable=true),
     *                     @OA\Property(property="file_extension", type="string", nullable=true),
     *                     @OA\Property(property="is_image", type="boolean", nullable=true),
     *                     @OA\Property(property="is_video", type="boolean", nullable=true),
     *                     @OA\Property(property="is_document", type="boolean", nullable=true)
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Conversation not found or access denied")
     * )
     */
    public function getMessages(Request $request, string $chat_id): JsonResponse
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

            if (! $conversation) {
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
                        'created_at' => $message->date ? $message->date->utc()->toISOString() : now()->utc()->toISOString(),
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
            \Log::error('Failed to fetch messages: '.$e->getMessage());

            return $this->error('Failed to fetch messages: '.$e->getMessage(), 500);
        }
    }

    /**
     * Upload media file to a conversation
     *
     * @OA\Post(
     *     path="/api/conversations/{chat_id}/media",
     *     tags={"Chat"},
     *     summary="Upload media file to a conversation",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="chat_id",
     *         in="path",
     *         required=true,
     *         description="Chat ID",
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *
     *             @OA\Schema(
     *
     *                 @OA\Property(
     *                     property="file",
     *                     type="string",
     *                     format="binary",
     *                     description="Media file (max 10MB, types: jpg,jpeg,png,gif,mp4,mov,avi,pdf,doc,docx,txt)"
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Media uploaded successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="status", type="integer", example=200)
     *             ),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="message_id", type="integer", example=123),
     *                 @OA\Property(property="file_url", type="string", example="http://example.com/storage/chat_media/file.jpg"),
     *                 @OA\Property(property="file_type", type="string", example="image/jpeg")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Conversation not found or access denied"),
     *     @OA\Response(response=422, description="Validation failed")
     * )
     */
    public function uploadMedia(Request $request, string $chat_id): JsonResponse
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

            if (! $conversation) {
                return $this->error('Conversation not found or access denied', 404);
            }

            // Validate file upload
            $request->validate([
                'file' => 'required|file|max:10240|mimes:jpg,jpeg,png,gif,mp4,mov,avi,pdf,doc,docx,txt',
            ]);

            $file = $request->file('file');

            // Upload to SeaweedFS and get FID
            $fid = $this->seaweedFsService->uploadToStorage(
                file_get_contents($file->getRealPath()),
                $file->getClientOriginalName()
            );

            // Store only FID as file_url
            $fileUrl = $fid;

            // Determine receiver (the other user in conversation)
            $receiverId = $conversation->user1_id === $userId
                ? $conversation->user2_id
                : $conversation->user1_id;

            // Create message record using ORM
            $message = ChatMessage::create([
                'conversation_id' => $chat_id,
                'sender_id' => $userId,
                'receiver_id' => $receiverId,
                'message' => $fid, // Store FID as message content
                'type' => 'media',
                'is_seen' => false,
                'date' => now()->utc(),
            ]);

            return $this->success([
                'message_id' => $message->id,
                'file_url' => $fileUrl,
                'file_type' => $file->getMimeType(),
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->error('Validation failed: '.implode(', ', $e->validator->errors()->all()), 422);
        } catch (\Exception $e) {
            // Log failed
            \Log::error('Failed to upload media: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return $this->error('Failed to upload media', 500);
        }
    }

    /**
     * Mark all messages in a conversation as read
     *
     * @OA\Post(
     *     path="/api/conversations/read-messages/{chat_id}",
     *     tags={"Chat"},
     *     summary="Mark all messages in a conversation as read",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="chat_id",
     *         in="path",
     *         required=true,
     *         description="Chat ID",
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Messages marked as read",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="status", type="integer", example=200)
     *             ),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="success", type="boolean", example=true)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Conversation not found or access denied")
     * )
     */
    public function markMessagesAsRead(Request $request, string $chat_id): JsonResponse
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

            if (! $conversation) {
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
            \Log::error('Failed to mark messages as read: '.$e->getMessage());

            return $this->error('Failed to mark messages as read', 500);
        }
    }

    /**
     * Pin or unpin conversation
     *
     * @OA\Post(
     *     path="/api/conversations/pin/{chat_id}",
     *     tags={"Chat"},
     *     summary="Pin or unpin a conversation",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="chat_id",
     *         in="path",
     *         required=true,
     *         description="Chat ID",
     *
     *         @OA\Schema(type="string", example="1")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Pin status toggled",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="status", type="integer", example=200)
     *             ),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="pinned", type="boolean", example=true)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Conversation not found")
     * )
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
     *
     * @OA\Post(
     *     path="/api/conversations/typing-indicator",
     *     tags={"Chat"},
     *     summary="Send typing indicator to conversation",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="conversation_id", type="integer", example=1, description="Conversation ID"),
     *             @OA\Property(property="is_typing", type="boolean", example=true, description="Whether user is typing")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Typing indicator sent",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="status", type="integer", example=200)
     *             ),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="success", type="boolean", example=true)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Conversation not found or access denied"),
     *     @OA\Response(response=422, description="Validation failed")
     * )
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

            if (! $conversation) {
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
     *
     * @OA\Post(
     *     path="/api/conversations/online-status",
     *     tags={"Chat"},
     *     summary="Update user online status",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="is_online", type="boolean", example=true, description="Online status")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Online status updated",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="status", type="integer", example=200)
     *             ),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="is_online", type="boolean", example=true),
     *                 @OA\Property(property="last_seen", type="string", format="date-time", nullable=true)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=422, description="Validation failed")
     * )
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
     *
     * @OA\Get(
     *     path="/api/conversations/{chat_id}/online-status",
     *     tags={"Chat"},
     *     summary="Get conversation participants online status",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="chat_id",
     *         in="path",
     *         required=true,
     *         description="Chat ID",
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Other participant's online status",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="status", type="integer", example=200)
     *             ),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="id", type="string", example="user-uuid"),
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="is_online", type="boolean", example=true),
     *                     @OA\Property(property="last_seen", type="string", format="date-time", nullable=true)
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Conversation not found or access denied")
     * )
     */
    public function getConversationOnlineStatus(Request $request, string $chat_id): JsonResponse
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

            if (! $conversation) {
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
                ],
            ]);

        } catch (\Exception $e) {
            return $this->error('Failed to get online status', 500);
        }
    }

    /**
     * Get current user's connected social accounts
     *
     * @OA\Get(
     *     path="/api/conversations/social-accounts",
     *     tags={"Chat"},
     *     summary="Get current user's connected social accounts",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of connected social accounts",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="status", type="integer", example=200)
     *             ),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user_id", type="string", example="user-uuid"),
     *                 @OA\Property(property="social_accounts", type="array",
     *
     *                     @OA\Items(
     *
     *                         @OA\Property(property="provider", type="string", example="vkontakte"),
     *                         @OA\Property(property="name", type="string", example="John Doe"),
     *                         @OA\Property(property="email", type="string", example="john@example.com")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=500, description="Failed to get social accounts")
     * )
     */
    public function getUserSocialAccounts(Request $request): JsonResponse
    {
        try {
            $currentUserId = $request->user()->id;

            // Get current user's connected social accounts
            $socialAccounts = ConnectedAccount::where('user_id', $currentUserId)
                ->get()
                ->map(function ($account) {
                    return [
                        'provider' => $account->provider,
                        'name' => $account->name,
                        'email' => $account->email,
                    ];
                });

            return $this->success([
                'user_id' => $currentUserId,
                'social_accounts' => $socialAccounts,
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to get user social accounts: '.$e->getMessage());

            return $this->error('Failed to get social accounts', 500);
        }
    }

    /**
     * Test method
     */
    public function testMethod(Request $request): JsonResponse
    {
        \Log::info('testMethod - Called successfully');

        return $this->success(['test' => 'OK']);
    }

    /**
     * Send user's social contacts to chat
     *
     * @OA\Get(
     *     path="/api/conversations/send-social-contacts",
     *     tags={"Chat"},
     *     summary="Send user's social contacts to chat",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="conversation_id",
     *         in="query",
     *         required=true,
     *         description="Conversation ID",
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Parameter(
     *         name="provider",
     *         in="query",
     *         required=true,
     *         description="Social provider",
     *
     *         @OA\Schema(type="string", enum={"vkontakte", "telegram", "google", "apple"}, example="vkontakte")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Social contact sent successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="status", type="integer", example=200)
     *             ),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="message", type="string", example="Social contact sent successfully"),
     *                 @OA\Property(property="provider", type="string", example="vkontakte"),
     *                 @OA\Property(property="contact", type="string", example="John Doe")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=400, description="Invalid provider or missing parameters"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Conversation not found or no connected account"),
     *     @OA\Response(response=500, description="Failed to send social contact")
     * )
     */
    public function sendSocialContacts(Request $request): JsonResponse
    {
        \Log::info('ChatController - sendSocialContacts called');

        try {
            // Получаем параметры из query string для GET запроса
            $conversationId = $request->get('conversation_id');
            $provider = $request->get('provider');

            if (! $conversationId || ! $provider) {
                return $this->error('Required parameters: conversation_id and provider', 400);
            }

            if (! in_array($provider, ConnectedAccount::getSupportedProviders())) {
                return $this->error('Invalid provider', 400);
            }

            $userId = $request->user()->id;

            // Verify user has access to conversation
            $conversation = Conversation::where('id', $conversationId)
                ->where(function ($query) use ($userId) {
                    $query->where('user1_id', $userId)
                        ->orWhere('user2_id', $userId);
                })
                ->first();

            if (! $conversation) {
                return $this->error('Conversation not found or access denied', 404);
            }

            // Check if user has this social account connected
            $connectedAccount = ConnectedAccount::where('user_id', $userId)
                ->where('provider', $provider)
                ->first();

            if (! $connectedAccount) {
                return $this->error('You do not have a connected account for this provider', 404);
            }

            // Send contact via existing chat system
            $result = $this->chatService->emitMessage(
                $userId,
                $conversationId,
                $connectedAccount->name ?? $connectedAccount->email,
                'contact',
                null,
                $provider,
                false,
                null,
                null
            );

            if ($result['error']) {
                return $this->error($result['error']['message'], $result['error']['status']);
            }

            return $this->success([
                'message' => 'Social contact sent successfully',
                'provider' => $provider,
                'contact' => $connectedAccount->name ?? $connectedAccount->email,
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to send social contact: '.$e->getMessage());

            return $this->error('Failed to send social contact', 500);
        }
    }
}
