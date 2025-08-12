<?php

namespace App\Http\Middleware;

use Symfony\Component\HttpFoundation\Response;
use App\Http\Traits\ApiResponseTrait;
use App\Models\Secondaryuser;
use App\Services\JwtService;
use Illuminate\Http\Request;
use Closure;

class AuthMiddleware
{
    use ApiResponseTrait;

    /**
     * @param JwtService $jwtService
     */
    public function __construct(private readonly JwtService $jwtService)
    {
    }

    /**
     * @param Request $request
     * @param Closure $next
     * @return Response
     * @throws \Exception
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $token = $request->bearerToken() ?? env('JWT_DEBUG', '');
            \Log::info('Auth middleware - Token: ' . substr($token, 0, 50) . '...');

            // Проверяем и декодируем токен
            if (!$payload = $this->jwtService->decode($token)) {
                \Log::error('Auth middleware - Failed to decode token');
                throw new \Exception('Unauthorized');
            }

            \Log::info('Auth middleware - Decoded payload: ' . json_encode($payload));

            // Получаем пользователя
            \Log::info('Auth middleware - Looking for user ID: ' . $payload['id']);
            $user = Secondaryuser::find($payload['id']);
            \Log::info('Auth middleware - User found: ' . ($user ? 'YES' : 'NO'));
            if (!$user) {
                throw new \Exception('User not found');
            }

            // Добавляем пользователя в запрос
            $request->setUserResolver(function () use ($user) {
                return $user;
            });

            \Log::info('Auth middleware - Calling next middleware/controller');
            $response = $next($request);
            \Log::info('Auth middleware - Response received: ' . $response->getStatusCode());
            return $response;
        } catch (\Exception $e) {
            return $this->errorUnauthorized();
        }
    }
}
