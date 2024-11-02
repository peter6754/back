<?php

namespace App\Http\Traits;

use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\JsonResponse;

trait ApiResponseTrait
{
    /**
     * Успешный ответ
     * @param mixed|null $data
     * @param int $httpCode
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
     * Ответ для 404 ошибки
     * @param string|null $message
     * @param int $errorCode
     * @return JsonResponse
     */
    protected function notFoundResponse(
        ?string $message = 'Resource not found',
        int     $errorCode = 0
    ): JsonResponse
    {
        return $this->errorResponse(
            $message,
            $errorCode,
            Response::HTTP_NOT_FOUND
        );
    }
}
