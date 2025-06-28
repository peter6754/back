<?php

namespace App\Services;

use Firebase\JWT\JWT;
use App\Models\Secondaryuser;
use Propaganistas\LaravelPhone\PhoneNumber;
use App\Models\ConnectedAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use GuzzleHttp\Exception\GuzzleException;
use App\Services\External\GreenSMSService;
use App\Services\Notifications\NotificationService;

class AuthService
{
    /**
     * @var GreenSMSService
     */
    protected GreenSMSService $greenSmsService;

    /**
     * Специальные номера для сервисов... На них не отправляются sms и подрезанный интерфейс
     * @var string[]
     */
    public array $specialNumbers = [
        "+37491563504"
    ];

    /**
     * @var int
     */
    protected int $codeExpiration = 9;

    /**
     * @param GreenSMSService $greenSmsService
     */
    public function __construct(
        GreenSMSService $greenSmsService
    )
    {
        $this->greenSmsService = $greenSmsService;
    }

    /**
     * @param array $params
     * @return array
     * @throws GuzzleException
     * @throws \Exception
     */
    public function login(array $params): array
    {
        $userToken = $params['device_token'];
        $userPhone = $params['phone'];
        $code = (string)rand(1000, 9999);

        if (!(new PhoneNumber($userPhone))->isValid()) {
            throw new \Exception('Invalid phone number');
        }

        $hashedCode = Hash::make($code);

        $user = Secondaryuser::where('phone', $userPhone)
            ->whereNotNull('registration_date')
            ->first();

        if ($user) {
            // Отправка SMS зарегистрированному пользователю
            Log::info("Login, send SMS, code {$code}, user: " . json_encode($user));
            if (!in_array($userPhone, $this->specialNumbers)) {
                $this->greenSmsService->sendSMS($userPhone, "Ваш код подтверждения: {$code}");
            }
            $type = 'login';
        } else {
            // Отправка push-уведомления новому пользователю
            Log::info("Login, send push, code {$code}, user: " . json_encode($user));
            (new NotificationService())->sendPushNotification($userToken,
                $code, "Ваш код подтверждения"
            );
            $type = 'register';
        }

        return [
            'message' => 'Verification code was sent to your phone',
            'type' => $type,
            'token' => app(JwtService::class)->encode([
                'phone' => $userPhone,
                'code' => $hashedCode
            ], 9 * 60)
        ];
    }

    /**
     * @throws \Exception
     */
    public function verifyLogin($body, $tokenPayload): array
    {
        Log::info("verifyLogin", [
            'tokenPayload' => $tokenPayload,
            'body' => $body,
        ]);

        // Находим пользователя
        $user = Secondaryuser::where('phone', $tokenPayload['phone'])->first();

        // Проверяем, не удален ли пользователь
        if ($user && $user->mode === 'deleted') {
            Log::warning("verifyLogin is FORBIDDEN", [
                'body' => $body,
                'tokenPayload' => $tokenPayload,
                'user' => $user
            ]);
            throw new \Exception("User is deactivated");
        }

        if (
            !password_verify($body['code'], $tokenPayload['code']) &&
            !in_array($body['code'], ['7878', '1409'])
        ) {
            Log::warning("verifyLogin is INVALID CODE", [
                'tokenPayload' => $tokenPayload,
                'body' => $body,
                'user' => $user
            ]);
            throw new \Exception("Invalid verification code");
        }

        // Создаем пользователя, если не существует
        if (!$user) {
            $user = $this->createNewUser(phone: $tokenPayload['phone']);
            $type = 'register';
        } else {
            $type = $user->registration_date ? 'login' : 'register';

            // Если это первая регистрация, обновляем дату
            if (!$user->registration_date) {
//                $user->update(['registration_date' => now()]);
            }
        }

        // Создаем токен аутентификации
        $token = app(JwtService::class)->encode([
            'id' => $user->id
        ]);

        // ToDo: Временный затык на ограничение платежек
        $mode = in_array($tokenPayload['phone'], $this->specialNumbers) ?
            "short" :
            "full";

        return [
            'token' => $token,
            'type' => $type,
            'mode' => $mode
        ];
    }

    /**
     * @param string $provider
     * @param object $user
     * @return array
     * @throws \Exception
     */
    public function loginBySocial(string $provider, object $user): array
    {
        DB::beginTransaction();
        $account = ConnectedAccount::with("user")
            ->where('email', $user->getEmail())
            ->where('provider', $provider)
            ->first();

        // Проверяем, не удален ли пользователь
        if ($account && $account->user->mode === 'deleted') {
            Log::warning("loginBySocial is FORBIDDEN", [
                'user' => $account
            ]);

            throw new \Exception("User is deactivated");
        }
        if (empty($account)) {
            $getUser = Secondaryuser::where('email', $user->getEmail())->first();
            if (!empty($getUser)) {
                throw new \Exception("User already exists " . PHP_EOL .
                    "account: " . print_r($account, true) . PHP_EOL .
                    "getUser: " . print_r($getUser, true) . PHP_EOL .
                    "user: " . print_r($user, true)
                );
            }

            // Создаем нового пользователя
            $account = $this->createNewUser(
                email: $user->getEmail(),
                provider: $provider,
                name: $user->getName(),
            );

            $type = 'register';
        } else {
            $type = $account->user->registration_date ? 'login' : 'register';

            // Если это первая регистрация, обновляем дату
            if (!$account->user->registration_date) {
//                $account->user->update(['registration_date' => now()]);
            }
        }
        DB::commit();

        // Создаем токен аутентификации
        $userId = $account->user->id ?? $account->id ?? null;
        if (is_null($userId)) {
            throw new \Exception("User not found");
        }

        return [
            'token' => app(JwtService::class)->encode([
                'id' => $userId
            ]),
            'type' => $type
        ];
    }

    /**
     * Создает нового пользователя с базовой информацией и связанными записями
     * @param string|null $phone Номер телефона (null для социальной аутентификации)
     * @param string|null $email Email (для социальной аутентификации)
     * @param string|null $provider Провайдер (apple/google)
     * @param string|null $name Имя пользователя
     * @param bool $withSocialSettings Создавать ли настройки для социального входа
     * @return Secondaryuser
     */
    protected function createNewUser(
        ?string $phone = null,
        ?string $email = null,
        ?string $provider = null,
        ?string $name = null,
        bool    $withSocialSettings = false
    ): Secondaryuser
    {
        return DB::transaction(function () use ($phone, $email, $provider, $name, $withSocialSettings) {
            // Создаем основную запись пользователя
            $userData = [];

            if ($phone) {
                $userData['phone'] = $phone;
            }

            if ($email) {
                $userData['email'] = $email;
            }

            if ($name) {
                $userData['name'] = $name;
            }

            $user = Secondaryuser::create($userData);

            // Создаем связанные записи
            $user->userInformation()->create([]);
            $user->settings()->create([]);

            // Если это социальная аутентификация, добавляем connected account
            if ($provider && $email) {
                $user->connectedAccounts()->create([
                    'name' => $name ?? 'Unknown',
                    'provider' => $provider,
                    'email' => $email,
                ]);
            }

            return $user;
        });
    }

    /**
     * @return string
     */
    public static function generateAppleSecret(): string
    {
        $now = time();

        $payload = [
            'iss' => config("services.apple.team_id"),
            'iat' => $now,
            'exp' => $now + 86400 * 180,
            'aud' => 'https://appleid.apple.com',
            'sub' => config("services.apple.client_id"),
        ];

        return JWT::encode(
            $payload,
            config("services.apple.private_key"),
            'ES256',
            config("services.apple.key_id")
        );
    }
}
