<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * @OA\Tag(
 *     name="Feedbacks",
 *     description="User feedback endpoints"
 * )
 */
class FeedbackController extends Controller
{
    /**
     * @OA\Get(
     *     path="/users/feedbacks",
     *     summary="Get user feedbacks",
     *     tags={"Feedbacks"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="user_id",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="items", type="array", @OA\Items(
     *                 @OA\Property(property="feedback", type="string"),
     *                 @OA\Property(property="sender_id", type="string")
     *             ))
     *         )
     *     )
     * )
     */
    public function getFeedbacks(Request $request)
    {
        $request->validate([
            'user_id' => 'required|string'
        ]);

        $feedbacks = DB::select('
            SELECT feedback, sender_id
            FROM user_feedbacks
            WHERE user_id = ?
            ORDER BY date DESC
        ', [$request->user_id]);

        return response()->json([
            'items' => $feedbacks
        ]);
    }

    /**
     * @OA\Post(
     *     path="/users/feedbacks",
     *     summary="Leave feedback",
     *     tags={"Feedbacks"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="feedback", type="string"),
     *             @OA\Property(property="user_id", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function leaveFeedback(Request $request)
    {
        $request->validate([
            'feedback' => 'required|string',
            'user_id' => 'required|string'
        ]);

        $sender_id = $request->user()->id;
        $user_id = $request->user_id;

        try {
            // Check if users have chatted (validation query from original)
            $message = DB::select('
                SELECT ch.id FROM chat_messages c
                LEFT JOIN chat_messages ch ON ch.receiver_id = c.sender_id AND ch.sender_id = c.receiver_id
                WHERE c.sender_id = ? AND c.receiver_id = ?
            ', [$sender_id, $user_id]);

            if (empty($message)) {
                return response()->json([
                    'message' => "You didn't chat with this user",
                    'code' => 4031
                ], 403);
            }

            // Get device tokens for push notifications
            $tokens = DB::select('
                SELECT token FROM user_device_tokens
                WHERE user_id = ?
            ', [$user_id]);

            // Insert feedback
            DB::insert('
                INSERT INTO user_feedbacks (user_id, feedback, date, sender_id)
                VALUES (?, ?, NOW(), ?)
            ', [$user_id, $request->feedback, $sender_id]);

            // Note: Push notification would be sent here in production
            // Original code: this.expoService.sendPushNotification(...)

            return response()->json([
                'message' => 'Feedback sent successfully'
            ]);

        } catch (\Exception $err) {
            return response()->json([
                'message' => "You have already left a review for this user",
                'code' => 4065
            ], 406);
        }
    }

    /**
     * @OA\Put(
     *     path="/users/feedbacks/{user_id}",
     *     summary="Change feedback",
     *     tags={"Feedbacks"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="user_id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="feedback", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function changeFeedback(Request $request, $user_id)
    {
        $request->validate([
            'feedback' => 'string'
        ]);

        $sender_id = Auth::id();
        $receiver_id = $user_id;

        try {
            // Get device tokens for push notifications
            $tokens = DB::select('
                SELECT token FROM user_device_tokens
                WHERE user_id = ?
            ', [$receiver_id]);

            // Update feedback
            $affected = DB::update('
                UPDATE user_feedbacks
                SET feedback = ?
                WHERE user_id = ? AND sender_id = ?
            ', [$request->feedback, $receiver_id, $sender_id]);

            if ($affected === 0) {
                return response()->json([
                    'message' => 'Feedback not found'
                ], 404);
            }

            // Note: Push notification would be sent here in production
            // Original code: this.expoService.sendPushNotification(...)

            return response()->json([
                'message' => 'Feedback changed successfully'
            ]);

        } catch (\Exception $err) {
            return response()->json([
                'message' => 'Item not found'
            ], 404);
        }
    }

    /**
     * @OA\Delete(
     *     path="/users/feedbacks/{sender_id}",
     *     summary="Delete feedback",
     *     tags={"Feedbacks"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="sender_id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function deleteFeedback($sender_id)
    {
        $receiver_id = Auth::id();

        try {
            // Check for premium subscription (using original complex query structure)
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
            ', [$receiver_id, $receiver_id]);

            if (empty($subscription)) {
                return response()->json([
                    'message' => "You don't have Tinderone Premium subscription or item was not found"
                ], 403);
            }

            // Delete feedback
            $affected = DB::delete('
                DELETE FROM user_feedbacks
                WHERE user_id = ? AND sender_id = ?
            ', [$receiver_id, $sender_id]);

            if ($affected === 0) {
                return response()->json([
                    'message' => "Feedback not found"
                ], 404);
            }

            return response()->json([
                'message' => 'Feedback deleted successfully'
            ]);

        } catch (\Exception $err) {
            return response()->json([
                'message' => "You don't have Tinderone Premium subscription or item was not found"
            ], 403);
        }
    }
}