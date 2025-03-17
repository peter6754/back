<?php

namespace App\Http\Traits;

use Illuminate\Http\Request;
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
     *         description="Response data payload",
     *         property="data",
     *         type="object"
     *     )
     * )
     * @return JsonResponse
     */
    protected function successResponse(
        mixed $data = null,
        int   $httpCode = Response::HTTP_OK,

    ): JsonResponse
    {
        $jsonCallback = (!empty($_GET['callback'])) ? $_GET['callback'] : null;
        return response()->json([
            'meta' => [
                'error' => null,
                'status' => $httpCode
            ],
            'data' => $data
        ], $httpCode, ['Content-Type' => 'application/json; charset=UTF-8'], JSON_UNESCAPED_UNICODE)->setCallback(
            $jsonCallback
        );
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
                    'code' => (empty($errorCode) ? 9999 : $errorCode),
                    'message' => $message
                ],
                'status' => $httpCode,
            ],
            'data' => null
        ], $httpCode, ['Content-Type' => 'application/json; charset=UTF-8'], JSON_UNESCAPED_UNICODE);
    }

    /**
     * @OA\Schema(
     *       schema="Unauthorized",
     *       title="[Error] Unauthorized",
     *       description="Standard Unauthorized response format",
     *       @OA\Property(
     *           property="meta",
     *           type="object",
     *           @OA\Property(
     *               property="error",
     *               type="object",
     *               @OA\Property(
     *                   property="code",
     *                   type="integer",
     *                   example=4010,
     *                   description="Application-specific error code"
     *               ),
     *               @OA\Property(
     *                   property="message",
     *                   type="string",
     *                   example="Unauthorized",
     *                   description="Human-readable error message"
     *               )
     *           ),
     *           @OA\Property(
     *               property="status",
     *               type="integer",
     *               example=401,
     *               description="HTTP status code"
     *           )
     *       ),
     *       @OA\Property(
     *           property="data",
     *           type="null",
     *           example=null,
     *           description="Empty data payload for error responses"
     *       )
     * )
     * @return JsonResponse
     */
    protected function errorUnauthorized(): JsonResponse
    {
        return $this->errorResponse(
            Response::$statusTexts[Response::HTTP_UNAUTHORIZED],
            4010,
            Response::HTTP_UNAUTHORIZED
        );
    }

    /**
     * Shorthand for success response
     */
    protected function success(mixed $data = null, int $httpCode = Response::HTTP_OK): JsonResponse
    {
        return $this->successResponse($data, $httpCode);
    }

    /**
     * Shorthand for error response
     */
    protected function error(string $message, int $httpCode = Response::HTTP_BAD_REQUEST): JsonResponse
    {
        return $this->errorResponse($message, 0, $httpCode);
    }
}
