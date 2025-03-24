<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class FeedbackService
{
    public function getFeedbacksByUserId(string $userId): array
    {
        return DB::select('
            SELECT feedback, sender_id
            FROM user_feedbacks
            WHERE user_id = ?
            ORDER BY date DESC
        ', [$userId]);
    }

    public function hasUsersChatted(string $senderId, string $userId): bool
    {
        $message = DB::select('
            SELECT ch.id FROM chat_messages c
            LEFT JOIN chat_messages ch ON ch.receiver_id = c.sender_id AND ch.sender_id = c.receiver_id
            WHERE c.sender_id = ? AND c.receiver_id = ?
        ', [$senderId, $userId]);

        return !empty($message);
    }

    public function getUserDeviceTokens(string $userId): array
    {
        return DB::select('
            SELECT token FROM user_device_tokens
            WHERE user_id = ?
        ', [$userId]);
    }

    public function createFeedback(string $userId, string $feedback, string $senderId): void
    {
        DB::insert('
            INSERT INTO user_feedbacks (user_id, feedback, date, sender_id)
            VALUES (?, ?, NOW(), ?)
        ', [$userId, $feedback, $senderId]);
    }

    public function updateFeedback(string $receiverId, string $senderId, string $feedback): int
    {
        return DB::update('
            UPDATE user_feedbacks
            SET feedback = ?, date = NOW()
            WHERE user_id = ? AND sender_id = ?
        ', [$feedback, $receiverId, $senderId]);
    }

    public function hasPremiumSubscription(string $userId): bool
    {
        $subscription = DB::select('
            SELECT t.id
            FROM transactions t
            JOIN bought_subscriptions bs ON bs.id = (
                SELECT subscription_id FROM transactions
                WHERE user_id = ? AND subscription_id IS NOT NULL
                ORDER BY created_at DESC LIMIT 1
            )
            JOIN subscription_packages sp ON sp.id = bs.package_id
            JOIN subscriptions s ON s.id = sp.subscription_id
            WHERE s.id = 3 AND bs.due_date > NOW() AND t.user_id = ?
        ', [$userId, $userId]);

        return !empty($subscription);
    }

    public function deleteFeedback(string $receiverId, string $senderId): int
    {
        return DB::delete('
            DELETE FROM user_feedbacks
            WHERE user_id = ? AND sender_id = ?
        ', [$receiverId, $senderId]);
    }
}