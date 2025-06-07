<?php

namespace App\Services;

use InvalidArgumentException;
use Illuminate\Support\Str;
use Exception;

class JwtService
{
    private int $ttl = 365 * 24 * 60 * 60;
    private string $alg = 'HS256';
    private int $leeway = 0;
    private string $secret;

    public function __construct(string $secret = null)
    {
        $this->secret = $secret ?? config('jwt.jwt_secret');

        if (empty($this->secret)) {
            throw new InvalidArgumentException('Secret key cannot be empty');
        }
    }

    /**
     * Создание JWT токена
     * @param array $payload Полезная нагрузка
     * @param int|null $ttl Время жизни токена в секундах
     * @return string JWT токен
     * @throws Exception Если кодирование не удалось
     */
    public function encode(array $payload, int $ttl = null): string
    {
        try {
            $header = $this->base64UrlEncode(json_encode([
                'alg' => $this->alg,
                'typ' => 'JWT'
            ], JSON_THROW_ON_ERROR));

            $now = time();
            $payload = array_merge($payload, [
                'iat' => $now,
                'exp' => $now + ($ttl ?? $this->ttl),
                'jti' => Str::random(32),
                'iss' => request()->getHttpHost() ?? 'laravel.app'
            ]);

            $payload = $this->base64UrlEncode(json_encode($payload, JSON_THROW_ON_ERROR));
            $signature = $this->generateSignature("$header.$payload");

            return "$header.$payload.$signature";
        } catch (Exception $e) {
            throw new Exception('Failed to encode JWT: ' . $e->getMessage());
        }
    }

    /**
     * Декодирование JWT токена
     * @param string $token JWT токен
     * @return array Декодированная полезная нагрузка
     * @throws Exception Если токен невалиден
     */
    public function decode(string $token): array
    {
        try {
            if (empty($token)) {
                throw new Exception('Token cannot be empty', 401);
            }

            $parts = explode('.', $token);

            if (count($parts) !== 3) {
                throw new Exception('Invalid token format', 401);
            }

            [$header, $payload, $signature] = $parts;

            // Проверка подписи
            if (!$this->verifySignature("$header.$payload", $signature)) {
                throw new Exception('Invalid token signature', 401);
            }

            $decodedPayload = json_decode($this->base64UrlDecode($payload), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Malformed payload', 401);
            }

            // Проверка срока действия
            $now = time();
            if (isset($decodedPayload['exp']) && $decodedPayload['exp'] < ($now - $this->leeway)) {
                throw new Exception('Token expired', 401);
            }

            // Проверка времени выдачи
            if (isset($decodedPayload['nbf']) && $decodedPayload['nbf'] > ($now + $this->leeway)) {
                throw new Exception('Token not yet valid', 401);
            }

            return $decodedPayload;
        } catch (Exception $e) {
//            echo $e->getMessage();
            return [];
        }
    }

    /**
     * Проверка валидности токена
     */
    public function validate(string $token): bool
    {
        try {
            $this->decode($token);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Проверка подписи
     */
    private function verifySignature(string $data, string $signature): bool
    {
        $generatedSignature = $this->generateSignature($data);
        return hash_equals($generatedSignature, $signature);
    }

    /**
     * Генерация подписи
     */
    private function generateSignature(string $data): string
    {
        return $this->base64UrlEncode(
            hash_hmac('sha256', $data, $this->secret, true)
        );
    }

    /**
     * Кодирование в base64 URL-safe
     */
    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Декодирование из base64 URL-safe
     */
    private function base64UrlDecode(string $data): string
    {
        $decoded = base64_decode(strtr($data, '-_', '+/') . str_repeat('=', 3 - (3 + strlen($data)) % 4));

        if ($decoded === false) {
            throw new Exception('Invalid base64 encoding');
        }

        return $decoded;
    }
}
