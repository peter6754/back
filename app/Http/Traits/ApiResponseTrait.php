<?php

namespace App\Http\Traits;

use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\JsonResponse;
use App\Models\Secondaryuser;
use Exception;

trait ApiResponseTrait
{
    /**
     * Успешный ответ
     * @param mixed|null $data
     * @param int $httpCode
     * @OA\Schema(
     *     schema="SuccessResponse",
     *     title="[Success] Success Response (all ok)",
     *     description="Standard success response format",
     *     @OA\Property(
     *         property="meta",
     *         type="object",
     *         @OA\Property(
     *             property="error",
     *             type="null",
     *             example=null,
     *             description="Error information (null if no error)"
     *         ),
     *         @OA\Property(
     *             property="status",
     *             type="integer",
     *             example=201,
     *             description="HTTP status code"
     *         )
     *     ),
     *     @OA\Property(
     *         property="data",
     *         type="object",
     *         description="Response data payload",
     *         @OA\Property(
     *             property="confirmation_url",
     *             type="string",
     *             format="url",
     *             example="https://auth.robokassa.ru/RecurringSubscriptionPage/Subscription/Subscribe?subscriberId=e5e44d67-0691-4432-8f03-6bab9424f552&subscriptionId=545a01b4-7de7-4063-93f4-4572d0036d29",
     *             description="URL for payment confirmation"
     *         ),
     *         @OA\Property(
     *             property="payment_id",
     *             type="string",
     *             format="uuid",
     *             example="4d448cac-8222-4ca2-85f7-f0fccf5d5bf1",
     *             description="Unique payment identifier"
     *         )
     *     )
     * )
     * @return JsonResponse
     */
    protected function successResponse(
        mixed $data = null,
        int   $httpCode = Response::HTTP_OK
    ): JsonResponse
    {
        return response()->json([
            'meta' => [
                'error' => null,
                'status' => $httpCode
            ],
            'data' => $data
        ], $httpCode);
    }

    /**
     * Ошибка
     * @param string|null $message
     * @param int $errorCode
     * @param int $httpCode
     * @return JsonResponse
     */
    protected function errorResponse(
        ?string $message = null,
        int     $errorCode = 0,
        int     $httpCode = Response::HTTP_BAD_REQUEST
    ): JsonResponse
    {
        return response()->json([
            'meta' => [
                'error' => [
                    'code' => $errorCode,
                    'message' => $message
                ],
                'status' => $httpCode,
            ],
            'data' => null
        ], $httpCode);
    }

    /**
     * @OA\Schema(
     *     schema="Unauthorized",
     *     title="[Error] Unauthorized",
     *     description="Standard Unauthorized response format",
     *     @OA\Property(
     *         property="meta",
     *         type="object",
     *         @OA\Property(
     *             property="error",
     *             type="object",
     *             @OA\Property(
     *                 property="code",
     *                 type="integer",
     *                 example=4010,
     *                 description="Application-specific error code"
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Unauthorized",
     *                 description="Human-readable error message"
     *             )
     *         ),
     *         @OA\Property(
     *             property="status",
     *             type="integer",
     *             example=401,
     *             description="HTTP status code"
     *         )
     *     ),
     *     @OA\Property(
     *         property="data",
     *         type="null",
     *         example=null,
     *         description="Empty data payload for error responses"
     *     )
     * )
     * @return array|JsonResponse
     * @throws Exception
     */
    protected function checkingAuth(): JsonResponse|array
    {
        $getUser = Secondaryuser::getUser();
        if (empty($getUser)) {
            $this->errorResponse(
                Response::$statusTexts[Response::HTTP_UNAUTHORIZED],
                4010,
                Response::HTTP_UNAUTHORIZED
            )->send();
            die();
        }
        return $getUser;
    }
}
