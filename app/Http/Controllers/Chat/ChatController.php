<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use App\Services\ChatService;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function __construct(private ChatService $chatService) {}

    public function sendMessage(Request $request)
    {
        $validated = $request->validate([
            'conversation_id' => 'required|integer|exists:conversations,id',
            'message' => 'required|string',
            'type' => 'required|string|in:text,contact,media,gift',
            'gift' => 'nullable|string|required_if:type,gift',
            'contact_type' => 'nullable|string|required_if:type,contact'
        ]);

        return $this->chatService->emitMessage(
            $request->user()->id,
            $validated['conversation_id'],
            $validated['message'],
            $validated['type'],
            $validated['gift'] ?? null,
            $validated['contact_type'] ?? null
        );
    }

    public function sendMedia(Request $request)
    {
        $request->validate([
            'conversation_id' => 'required|integer',
            'media' => 'required|array'
        ]);

        return $this->chatService->sendMedia([
            'sender_id' => $request->user()->id,
            'conversation_id' => $request->conversation_id,
            'media' => $request->media
        ]);
    }

    public function sendGift(Request $request)
    {
        $request->validate([
            'user_id' => 'required|string',
            'gift_id' => 'required|integer'
        ]);

        return $this->chatService->sendGift([
            'sender_id' => $request->user()->id,
            'user_id' => $request->user_id,
            'gift_id' => $request->gift_id
        ], $request->is_first ?? false);
    }
}
