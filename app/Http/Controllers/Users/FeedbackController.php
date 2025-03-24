<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponseTrait;
use App\Services\FeedbackService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * @OA\Tag(
 *     name="Feedbacks",
 *     description="User feedback endpoints"
 * )
 */
class FeedbackController extends Controller
{
    use ApiResponseTrait;

    protected FeedbackService $feedbackService;

    public function __construct(FeedbackService $feedbackService)
    {
        $this->feedbackService = $feedbackService;
    }
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
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(property="error", type="null"),
     *                 @OA\Property(property="status", type="integer", example=200)
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="items", type="array", @OA\Items(
     *                     @OA\Property(property="feedback", type="string"),
     *                     @OA\Property(property="sender_id", type="string")
     *                 ))
     *             )
     *         )
     *     )
     * )
     */
    public function getFeedbacks(Request $request)
    {
        $request->validate([
            'user_id' => 'required|string'
        ]);

        $feedbacks = $this->feedbackService->getFeedbacksByUserId($request->user_id);

        return $this->success([
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
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(property="error", type="null"),
     *                 @OA\Property(property="status", type="integer", example=200)
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="message", type="string", example="Feedback sent successfully")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Users haven't chatted",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(property="error", type="object",
     *                     @OA\Property(property="code", type="integer", example=4031),
     *                     @OA\Property(property="message", type="string", example="You didn't chat with this user")
     *                 ),
     *                 @OA\Property(property="status", type="integer", example=403)
     *             ),
     *             @OA\Property(property="data", type="null")
     *         )
     *     ),
     *     @OA\Response(
     *         response=406,
     *         description="Already reviewed",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(property="error", type="object",
     *                     @OA\Property(property="code", type="integer", example=4065),
     *                     @OA\Property(property="message", type="string", example="You have already left a review for this user")
     *                 ),
     *                 @OA\Property(property="status", type="integer", example=406)
     *             ),
     *             @OA\Property(property="data", type="null")
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
            if (!$this->feedbackService->hasUsersChatted($sender_id, $user_id)) {
                return $this->errorResponse("You didn't chat with this user", 4031, 403);
            }

            $tokens = $this->feedbackService->getUserDeviceTokens($user_id);

            $this->feedbackService->createFeedback($user_id, $request->feedback, $sender_id);

            // Note: Push notification would be sent here in production
            // Original code: this.expoService.sendPushNotification(...)

            return $this->success([
                'message' => 'Feedback sent successfully'
            ]);

        } catch (\Exception $err) {
            return $this->errorResponse("You have already left a review for this user", 4065, 406);
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
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(property="error", type="null"),
     *                 @OA\Property(property="status", type="integer", example=200)
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="message", type="string", example="Feedback changed successfully")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Feedback not found",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(property="error", type="object",
     *                     @OA\Property(property="code", type="integer", example=9999),
     *                     @OA\Property(property="message", type="string", example="Feedback not found")
     *                 ),
     *                 @OA\Property(property="status", type="integer", example=404)
     *             ),
     *             @OA\Property(property="data", type="null")
     *         )
     *     )
     * )
     */
    public function changeFeedback(Request $request, $user_id)
    {
        $request->validate([
            'feedback' => 'string'
        ]);

        $sender_id = $request->user()->id;
        $receiver_id = $user_id;

        try {
            $tokens = $this->feedbackService->getUserDeviceTokens($receiver_id);

            $affected = $this->feedbackService->updateFeedback($receiver_id, $sender_id, $request->feedback);

            if ($affected === 0) {
                return $this->errorResponse('Feedback not found', 0, 404);
            }

            // Note: Push notification would be sent here in production
            // Original code: this.expoService.sendPushNotification(...)

            return $this->success([
                'message' => 'Feedback changed successfully'
            ]);

        } catch (\Exception $err) {
            return $this->errorResponse('Item not found', 0, 404);
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
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(property="error", type="null"),
     *                 @OA\Property(property="status", type="integer", example=200)
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="message", type="string", example="Feedback deleted successfully")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="No premium subscription",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(property="error", type="object",
     *                     @OA\Property(property="code", type="integer", example=9999),
     *                     @OA\Property(property="message", type="string", example="You don't have Tinderone Premium subscription or item was not found")
     *                 ),
     *                 @OA\Property(property="status", type="integer", example=403)
     *             ),
     *             @OA\Property(property="data", type="null")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Feedback not found",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(property="error", type="object",
     *                     @OA\Property(property="code", type="integer", example=9999),
     *                     @OA\Property(property="message", type="string", example="Feedback not found")
     *                 ),
     *                 @OA\Property(property="status", type="integer", example=404)
     *             ),
     *             @OA\Property(property="data", type="null")
     *         )
     *     )
     * )
     */
    public function deleteFeedback(Request $request, $sender_id)
    {
        $receiver_id = $request->user()->id;

        try {
            if (!$this->feedbackService->hasPremiumSubscription($receiver_id)) {
                return $this->errorResponse("You don't have Tinderone Premium subscription or item was not found", 0, 403);
            }

            $affected = $this->feedbackService->deleteFeedback($receiver_id, $sender_id);

            if ($affected === 0) {
                return $this->errorResponse("Feedback not found", 0, 404);
            }

            return $this->success([
                'message' => 'Feedback deleted successfully'
            ]);

        } catch (\Exception $err) {
            return $this->errorResponse("You don't have Tinderone Premium subscription or item was not found", 0, 403);
        }
    }
}