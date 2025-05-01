<?php

namespace App\Http\Middleware;

use Symfony\Component\HttpFoundation\Response;
use App\Http\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\App;
use App\Models\Secondaryuser;
use App\Services\JwtService;
use Illuminate\Http\Request;
use Closure;

class AuthMiddleware
{
    use ApiResponseTrait;

    /**
     * @param  JwtService  $jwtService
     */
    public function __construct(private readonly JwtService $jwtService)
    {
    }

    /**
     * @param  Request  $request
     * @param  Closure  $next
     * @return Response
     * @throws \Exception
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $token = $request->bearerToken() ?? env('JWT_DEBUG', '');

            // Проверяем и декодируем токен
            if (!$payload = $this->jwtService->decode($token)) {
                throw new \Exception('Unauthorized');
            }

            // Получаем пользователя
            $user = Secondaryuser::find($payload['id']);
            if (!$user) {
                throw new \Exception('User not found');
            }

            // Проверка на бан пользователя
            $banInfo = $user->getBanInfo();

            if ($banInfo && $banInfo['is_permanent']) {
                request()->attributes->set('ban_info', $banInfo);
                throw new \Exception('User is blocked', 401);
            }

            // Выбираем язык пользователя (если указан)
            App::setLocale($payload['language'] ?? config('locales.default_locale'));
            if (isset($payload['language'])) {
                $user->language = $payload['language'];
            }

            // Добавляем пользователя в запрос
            $request->setUserResolver(function () use ($user) {
                return $user;
            });

            // Устанавливаем customer для совместимости с контроллерами
            $request->customer = $user->toArray();
            return $next($request);
        } catch (\Exception $e) {
            return $this->errorUnauthorized();
        }
    }
}
