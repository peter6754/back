<?php

namespace App\Http\Middleware;

use App\Models\Secondaryuser;
use Symfony\Component\HttpFoundation\Response;
use App\Services\JwtService;
use Illuminate\Http\Request;
use Closure;

class AuthMiddleware
{
    /**
     * @param JwtService $jwtService
     */
    public function __construct(private readonly JwtService $jwtService) {}

    /**
     * @param Request $request
     * @param Closure $next
     * @return Response
     * @throws \Exception
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken() ?? config('app.jwt_debug', '');

        // Проверяем и декодируем токен
        if (!$payload = $this->jwtService->decode($token)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Получаем пользователя
        $user = Secondaryuser::find($payload['id']);
        if (!$user) {
            return response()->json(['error' => 'User not found'], 401);
        }

        // Добавляем пользователя в запрос
        $request->merge(['user' => $user->toArray()]);

        return $next($request);
    }
}
