<?php

namespace App\Services;

use App\Models\UserReaction;
use App\Jobs\ProcessReaction;
use App\Models\UserInformation;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Config\Repository;
use App\Models\Secondaryuser;
use App\Services\Notifications\NotificationService;

/**
 *
 */
class RecommendationService
{
    /**
     * @var mixed|Repository|Application|object|null
     */
    private mixed $recommendationsCacheSize;

    /**
     * @var mixed|Repository|Application|object|null
     */
    private mixed $recommendationsCacheTTL;

    /**
     * @var mixed|Repository|Application|object|null
     */
    private mixed $recommendationsPageSize;

    /**
     * @var mixed
     */
    protected mixed $notificationService;

    /**
     * @var mixed
     */
    protected mixed $emailService;

    /**
     *
     */
    public function __construct()
    {
        $this->recommendationsCacheSize = config('recommendations.cache_size', 1000);
        $this->recommendationsCacheTTL = config('recommendations.cache_ttl', 14400);
        $this->recommendationsPageSize = config('recommendations.page_size', 10);
    }

    /**
     * Временная затычка на несуществующие методы
     * @param $name
     * @param $arguments
     * @return mixed
     * @throws \Exception
     */
    public function __call($name, $arguments)
    {
        throw new \Exception("Метод {$name} еще в разработке.");
    }

    /**
     * @param  array  $customer
     * @return array
     */
    public function getTopProfiles(mixed $customer): array
    {
        $key = "top-profiles:".$customer['id'];
        $topProfiles = Redis::get($key);

        if (! empty($topProfiles)) {
            try {
                $topProfiles = json_decode($topProfiles, true);
            } catch (\Exception $e) {
                unset($topProfiles);
                Redis::del($key);
            }
        }

        if (empty($topProfiles)) {
            $twoDaysAgo = now()->subDays(2)->toDateTimeString();
            $myPhone = $customer['phone'];
            $myUserId = $customer['id'];
            $myLat = $customer['lat'];
            $myLng = $customer['long'];

            // Чистый SQL запрос с CTE
            $sql = "
                WITH filtered_users AS (
                    SELECT
                        u.id,
                        u.name,
                        u.age,
                        u.lat,
                        u.long,
                        u.phone,
                        (SELECT 1 FROM blocked_contacts bc
                         WHERE bc.user_id = u.id AND bc.phone = ? LIMIT 1) AS blocked_me,
                        us.show_my_age,
                        us.show_distance_from_me,
                        (SELECT image FROM user_images ui
                         WHERE ui.user_id = u.id ORDER BY ui.id LIMIT 1) AS image,
                        (SELECT COUNT(*) FROM user_reactions ur
                         WHERE ur.user_id = u.id AND ur.type != 'dislike') AS like_count,
                        (SELECT 1 FROM bought_subscriptions bs
                         JOIN transactions t ON t.id = bs.transaction_id
                         WHERE t.user_id = u.id AND bs.due_date >= NOW() LIMIT 1) AS has_subscription,
                        ui.superboom_due_date
                    FROM users AS u
                    LEFT JOIN user_settings AS us ON us.user_id = u.id
                    LEFT JOIN user_information AS ui ON ui.user_id = u.id
                    INNER JOIN user_preferences AS up ON up.gender = u.gender AND up.user_id = ?
                    WHERE u.id != ?
                        AND u.lat IS NOT NULL
                        AND u.long IS NOT NULL
                        AND u.mode = 'authenticated'
                        AND u.registration_date IS NOT NULL
                        AND NOT EXISTS (
                            SELECT 1 FROM blocked_contacts AS bc
                            WHERE bc.user_id = u.id AND bc.phone = ?
                        )
                        AND NOT EXISTS (
                            SELECT 1 FROM blocked_contacts AS my_bc
                            WHERE my_bc.phone = u.phone AND my_bc.user_id = ?
                        )
                        AND NOT EXISTS (
                            SELECT 1 FROM user_reactions AS ure
                            WHERE ure.reactor_id = ?
                                AND ure.user_id = u.id
                                AND ure.type != 'dislike'
                                AND ure.date >= ?
                        )
                )
                SELECT
                    id,
                    name,
                    (blocked_me IS NOT NULL) AS blocked_me,
                    CASE
                        WHEN has_subscription IS NOT NULL AND NOT show_my_age THEN NULL
                        ELSE age
                    END AS age,
                    image,
                    CASE
                        WHEN has_subscription IS NOT NULL AND NOT show_distance_from_me THEN NULL
                        ELSE ROUND(ST_Distance_Sphere(POINT(?, ?), POINT(`long`, `lat`)) / 1000, 0)
                    END AS distance,
                    COALESCE(like_count, 0) AS like_count,
                    (superboom_due_date IS NOT NULL AND superboom_due_date >= UTC_TIMESTAMP()) AS is_boosted
                FROM filtered_users
                ORDER BY
                    (superboom_due_date IS NOT NULL AND superboom_due_date >= UTC_TIMESTAMP()) DESC,
                    like_count DESC
                LIMIT 15
            ";

            $bindings = [
                $myPhone,        // blocked_me subquery
                $myUserId,       // user_preferences join
                $myUserId,       // u.id != ?
                $myPhone,        // blocked_contacts NOT EXISTS (who blocked me)
                $myUserId,       // blocked_contacts NOT EXISTS (I blocked)
                $myUserId,       // user_reactions NOT EXISTS
                $twoDaysAgo,     // user_reactions date filter
                $myLng,          // ST_Distance_Sphere longitude
                $myLat,          // ST_Distance_Sphere latitude
            ];

            $results = DB::select($sql, $bindings);

            // Преобразование stdClass в массивы и приведение типов
            $topProfiles = array_map(function ($row) {
                return [
                    'id' => $row->id,
                    'name' => $row->name,
                    'blocked_me' => (bool) $row->blocked_me,
                    'age' => $row->age !== null ? (int) $row->age : null,
                    'image' => $row->image,
                    'distance' => $row->distance !== null ? (int) $row->distance : null,
                    'like_count' => (int) $row->like_count,
                    'is_boosted' => (bool) $row->is_boosted,
                ];
            }, $results);

            #Redis::setex($key, 900, json_encode($topProfiles));
        }

        return [
            'items' => $topProfiles
        ];
    }

    /**
     * @param  Secondaryuser  $user
     * @param  array  $query
     * @return mixed
     */
    public function getRecommendations(Secondaryuser $user, array $query): mixed
    {
        if ($user->userPreferences->isEmpty()) {
            return [
                "message" => "Пожалуйста, укажите ваши предпочтения в настройках профиля.",
                "code" => 400
            ];
        }

        // Configure cache params
        $keyPart1 = implode('-', $user->userSettings->age_range);
        $keyPart2 = $user->userSettings->is_global_search ? 'global' : $user->userSettings->search_radius;
        $keyPart3 = $user->userSettings->filter_cities ? 'cities' : 'all';
        $keyPart4 = implode(',', $user->userPreferences->pluck('gender')->toArray());
        $keyPart5 = isset($query['interest_id']) ? ':'.$query['interest_id'] : '';

        // Configure cache
        $key = "recommendations:{$user->id}:{$keyPart1}:{$keyPart2}:{$keyPart3}{$keyPart4}{$keyPart5}";
        $recommendationsCacheSize = $this->recommendationsPageSize;

        try {
            $forPage = Redis::transaction(function ($redis) use ($key, $recommendationsCacheSize) {
                $redis->lRange($key, 0, $recommendationsCacheSize - 1);
                $redis->lTrim($key, $recommendationsCacheSize, -1);
            })[0];

            if (empty($forPage)) {
                $fromDb = $this->_getRecommendationsForCache($user, $query);
                $forPage = array_splice($fromDb, 0, $this->recommendationsPageSize);

                if (empty($forPage)) {
                    return [
                        "message" => "К сожалению, мы не смогли найти для вас подходящих рекомендаций. Попробуйте изменить ваши настройки поиска.",
                        "code" => 404
                    ];
                }

                if (! empty($fromDb)) {
                    Redis::rpush($key, ...$fromDb);
                    Redis::expire($key, $this->recommendationsCacheTTL);
                }
            }

            return [
                "items" => $this->_getRecommendationsPage($user, $forPage)
            ];
        } catch (\Exception $e) {
            Log::channel('recommendations')->error(__FUNCTION__.' error: '.$e->getMessage(), [
                'user_id' => $user->id,
                'error' => $e
            ]);
            return [
                "message" => "Произошла ошибка при получении рекомендаций, попробуйте позже.",
                "code" => 408
            ];
        }
    }

    /**
     * @param  string  $userId
     * @param  array  $params
     * @return array
     */
    public function like(string $userId, array $params)
    {
        // Get user with necessary relations
        $user = Secondaryuser::with(['userInformation', 'activeSubscription'])
            ->findOrFail($userId);

        // Check if user is male without active subscription
        $isMale = $user->gender === 'male';
        $hasActiveSubscription = $user->activeSubscription()->exists();

        if ($isMale && ! $hasActiveSubscription) {
            // Get or create user information
            $userInfo = $user->userInformation;
            if (! $userInfo) {
                $userInfo = UserInformation::create([
                    'user_id' => $userId,
                    'daily_likes' => 30,
                    'daily_likes_last_reset' => now()->toDateString(),
                ]);
            }

            // Check if user has remaining likes
            if ($userInfo->getRemainingLikes() <= 0) {
                Log::channel('recommendations')->warning('RecommendationService > like > no likes remaining:', [
                    'reactor_id' => $userId,
                    'remaining' => $userInfo->getRemainingLikes()
                ]);

                throw new \Exception('No likes available', 9999);
            }

            // Use one like
            if (! $userInfo->useLike()) {
                throw new \Exception('Failed to deduct like');
            }
        }

        $reactionExists = $this->checkExistingReaction($userId, $params);
        $superboom = $this->getSuperboomStatus($params);

        $this->updateOrCreateReaction([
            'user_id' => $params['user_id'],
            'reactor_id' => $userId,
        ], [
            'from_top' => $params['from_top'] ?? false,
            'superboom' => $superboom,
            'type' => 'like',
            'date' => now(),
        ]);

        // Send notifications
        (new \App\Services\RecommendationService())->handleLikeNotification(
            $params['user_id'],
            $reactionExists
        );

        return ['is_match' => $reactionExists];
    }

    /**
     * @param  string  $userId
     * @param  array  $params
     * @return array|null
     */
    public function dislike(string $userId, array $params)
    {
        $this->updateOrCreateReaction([
            'user_id' => $params['user_id'],
            'reactor_id' => $userId,
        ], [
            'type' => 'dislike',
            'date' => now()
        ]);

        return ['message' => 'Reaction sent successfully'];
    }

    /**
     * @param  string  $userId
     * @param  array  $params
     * @return bool[]
     */
    public function superlike(string $userId, array $params)
    {
        Log::channel('recommendations')->info('RecommendationService > superlike called with:', [
            'reactor_id' => $userId,
            'data' => $params
        ]);

        try {
            // Get user information and check superlike allocation
            $userInfo = UserInformation::where('user_id', $userId)->first();

            if (! $userInfo) {
                $userInfo = UserInformation::create(['user_id' => $userId]);
            }

            if ($userInfo->getRemainingSuperlikes() <= 0) {
                Log::channel('recommendations')->warning('RecommendationService > superlike > no superlikes remaining:', [
                    'reactor_id' => $userId,
                    'remaining' => $userInfo->getRemainingSuperlikes()
                ]);

                throw new \Exception('No superlikes available');
            }
            $user = Secondaryuser::with([
                'userDeviceTokens',
                'userInformation',
                'userSettings'
            ])
                ->select(['id', 'email'])
                ->findOrFail($params['user_id']);

            $reaction = UserReaction::where([
                'reactor_id' => $params['user_id'],
                'user_id' => $userId
            ])
                ->whereIn('type', ['like', 'superlike'])
                ->first();

            Log::channel('recommendations')->info('RecommendationService > superlike > user:', [
                'reactor_id' => $userId,
                'user' => $user
            ]);

            Log::channel('recommendations')->info('RecommendationService > superlike > reaction:', [
                'reactor_id' => $userId,
                'reaction' => $reaction
            ]);

            UserReaction::updateOrCreate(
                [
                    'reactor_id' => $userId,
                    'user_id' => $params['user_id']
                ],
                [
                    'date' => now(),
                    'type' => 'superlike',
                    'superboom' => $user->userInformation && $user->userInformation->superboom_due_date >= now(),
                    'from_top' => $params['from_top'] ?? false
                ]
            );

            // Use superlike from user information
            if (! $userInfo->useSuperlike()) {
                throw new \Exception('Failed to deduct superlike');
            }

            if (! empty($params['comment'])) {
                $this->leaveComment($params['comment'], $userId, $params['user_id']);
            }

            $userTokens = $user->userDeviceTokens->pluck('token')->filter()->toArray();

            if (! empty($userTokens)) {
                if ($reaction && $user->userSettings->new_couples_push) {
                    (new NotificationService())->sendPushNotification(
                        $userTokens,
                        "У вас совпала новая пара! Зайдите, чтобы посмотреть и начать общение.",
                        "Новая пара!"
                    );
                } elseif (! $reaction && $user->userSettings->new_super_likes_push) {
                    (new NotificationService())->sendPushNotification(
                        $userTokens,
                        "Вам поставили суперлайк! Заходите в TinderOne, чтобы найти свою пару!",
                        "Вы кому-то нравитесь!"
                    );
                }
            }

            Log::channel('recommendations')->info('RecommendationService > superlike finished with:', [
                'reactor_id' => $userId,
                'is_match' => ! ! $reaction
            ]);

            return [
                'is_match' => $reaction ? true : false
            ];

        } catch (\Exception $err) {
            Log::channel('recommendations')->error('RecommendationService > superlike > error:', [
                'user_id' => $params['user_id'],
                'err' => $err->getMessage()
            ]);
            throw $err;
        }
    }

    /**
     * @param  string  $userId
     * @param  array  $params
     * @return string[]
     * @throws \Exception
     */
    public function rollback(string $userId, array $params)
    {
        $lastReacted = UserReaction::where('reactor_id', $userId)
            ->latest('date')
            ->first(['user_id']);

        if (! $lastReacted || $lastReacted->user_id != $params['user_id']) {
            throw new \Exception('Your last reaction doesn\'t match to the given user_id');
        }

        DB::table('user_reactions')
            ->where('reactor_id', $userId)
            ->where('user_id', $params['user_id'])
            ->orderBy('date', 'desc')
            ->limit(1)
            ->delete();

        return ['message' => 'Rollbacked successfully'];
    }

    /**
     * @param  string  $matchedId
     * @param  string  $userId
     * @return array
     */
    public function deleteMatchedUser(string $matchedId, string $userId)
    {
        UserReaction::where(function ($query) use ($userId, $matchedId) {
            $query->where('user_id', $userId)
                ->where('reactor_id', $matchedId);
        })->orWhere(function ($query) use ($userId, $matchedId) {
            $query->where('user_id', $matchedId)
                ->where('reactor_id', $userId);
        })->delete();
        return [];
    }

    /**
     * @param  Secondaryuser  $user
     * @param  array  $filters
     * @return array
     */
    private function _getRecommendationsForCache(Secondaryuser $user, array $filters): array
    {
        $preferences = $user->userPreferences->pluck('gender')->toArray();
        $searchRadius = $user->userSettings->search_radius;
        $ageRange = $user->userSettings->age_range;

        // Базовый запрос
        $query = DB::table('users as u')
            ->select('u.id')
            ->distinct()
            ->leftJoin('user_settings as us', 'us.user_id', '=', 'u.id')
            ->where('u.id', '!=', $user->id)
            ->whereNotNull('u.lat')
            ->whereNotNull('u.long')
            ->whereBetween('u.age', [$ageRange[0], $ageRange[1]])
            ->whereIn('u.gender', empty($preferences) ? [null] : $preferences)
            ->whereNotNull('u.registration_date')
            ->where('u.mode', 'authenticated')
            ->groupBy('u.id')
            ->orderBy('u.is_online', 'desc')
            ->orderByDesc(DB::raw("(SELECT MAX(last_activity) FROM user_activity WHERE user_id = u.id)"))
            ->orderBy('u.id', 'asc')
            ->limit($this->recommendationsCacheSize);

        // Условие радиуса поиска
        if (! $user->userSettings->is_global_search) {
            $query->whereIn('u.id', function ($subquery) use ($user, $searchRadius) {
                $subquery->select('u2.id')
                    ->from('users as u2')
                    ->whereRaw('ST_Distance_Sphere(point(?, ?), point(u2.long, u2.lat)) / 1000 <= ?', [
                        $user->long,
                        $user->lat,
                        $searchRadius
                    ])
                    ->where('u2.id', '!=', $user->id);
            });
        }

        // Исключаем мэтчи (UNION ALL запрос)
        $query->whereNotIn('u.id', function ($subquery) use ($user) {
            $subquery->select('user_id')
                ->from('user_reactions')
                ->where('reactor_id', $user->id)
                ->unionAll(
                    DB::table('user_reactions')
                        ->select('reactor_id as user_id')
                        ->where('user_id', $user->id)
                        ->where('type', 'dislike')
                );
        });

        // Исключаем пользователей, которых я заблокировал
        $query->whereNotIn('u.phone', function ($subquery) use ($user) {
            $subquery->select('phone')
                ->from('blocked_contacts')
                ->where('user_id', $user->id);
        });

        // Исключаем пользователей, которые заблокировали меня
        $query->whereNotIn('u.id', function ($subquery) use ($user) {
            $subquery->select('user_id')
                ->from('blocked_contacts')
                ->where('phone', $user->phone);
        });

        $results = $query->get();

        return $results->pluck('id')->toArray();
    }

    /**
     * @param  Secondaryuser  $user
     * @param $usersIds
     * @return array
     */
    private function _getRecommendationsPage(Secondaryuser $user, $usersIds): array
    {
        $recommendations = DB::table('users as u')
            ->select([
                'u.id',
                'u.gender',
                'u.name',
                'ui.bio',
                'u.is_online',
                'u.registration_date',
                DB::raw("CAST(
                IF(has_user_subscription(u.id) AND NOT us.show_my_age, NULL, u.age)
                AS UNSIGNED
            ) AS age"),
                DB::raw("CAST(
                IF(has_user_subscription(u.id) AND NOT us.show_distance_from_me, NULL,
                    ROUND((SELECT count_distance(u.id, {$user->lat}, {$user->long})), 0)
                )
                AS UNSIGNED
            ) AS distance"),
                DB::raw("CAST(
                EXISTS(
                    SELECT 1
                    FROM verification_requests
                    WHERE user_id = u.id AND status = 'approved'
                    LIMIT 1
                ) AS CHAR
            ) AS is_verified"),
                DB::raw("(
                SELECT GROUP_CONCAT(image SEPARATOR ';')
                FROM user_images
                WHERE user_id = u.id
                LIMIT 4
            ) AS photos")
            ])
            ->leftJoin('user_information as ui', 'ui.user_id', '=', 'u.id')
            ->leftJoin('user_settings as us', 'us.user_id', '=', 'u.id')
            ->whereIn('u.id', $usersIds)
            ->where('u.mode', 'authenticated')
            ->orderBy('u.is_online', 'desc')
            ->get();

        return $recommendations->map(function ($r) {
            $photos = $r->photos ? explode(";", $r->photos) : [];
            return [
                'id' => $r->id,
                'name' => $r->name,
                'bio' => $r->bio,
                'is_verified' => (bool) $r->is_verified,
                'is_online' => (bool) $r->is_online,
                'is_new' => strtotime($r->registration_date) > strtotime("-1 day"),
                'photos' => $photos,
                'age' => $r->age ? (int) $r->age : null,
                'distance' => $r->distance !== null ? (int) $r->distance : null,
            ];
        })->toArray();
    }

    /**
     * @param $userId
     * @param $params
     * @return bool
     */
    private function checkExistingReaction($userId, $params): bool
    {
        return UserReaction::where('reactor_id', $params['user_id'])
            ->where('user_id', $userId)
            ->whereIn('type', ['like', 'superlike'])
            ->exists();
    }

    /**
     * @param $params
     * @return bool
     */
    private function getSuperboomStatus($params): bool
    {
        $user = Secondaryuser::with(['userInformation'])
            ->select(['id'])
            ->findOrFail($params['user_id']);

        return $user->userInformation && $user->userInformation->superboom_due_date >= now();
    }


    /**
     * @param  string  $userId
     * @param  bool  $isMatch
     * @param  bool  $superLike
     * @return bool
     */
    public function handleLikeNotification(string $userId = "", bool $isMatch = false, bool $superLike = false): bool
    {
        try {
            $getUserData = Secondaryuser::with(['userDeviceTokens', 'userSettings'])
                ->where('id', $userId)
                ->first();

            $userTokens = collect($getUserData->userDeviceTokens)->map(function ($userTokens) {
                return $userTokens->token;
            });

            $userApplications = collect($getUserData->userDeviceTokens)->map(function ($userApplication) {
                return $userApplication->application;
            });

            $isRuStoreUser = $userApplications->contains(function ($application) {
                return stripos($application, 'ru-store') !== false;
            });

            if ($isMatch === true) {
                if ($getUserData->userSettings->new_couples_push) {
                    (new NotificationService())->sendPushNotification($userTokens,
                        "У вас совпала новая пара! Зайдите, чтобы посмотреть и начать общение.",
                        "Новая пара!"
                    );
                }
            } else {
                if ($superLike === true && $getUserData->userSettings->new_super_likes_push) {
                    (new NotificationService())->sendPushNotification($userTokens,
                        "Вам поставили суперлайк! Заходите в TinderOne, чтобы найти свою пару!",
                        "Вы кому-то нравитесь!"
                    );
                } else {
                    if ($superLike === false && $getUserData->userSettings->new_likes_push) {
                        (new NotificationService())->sendPushNotification($userTokens,
                            "Вам поставили лайк! Заходите в TinderOne, чтобы найти свою пару!",
                            "Вы кому-то нравитесь!"
                        );

                        // Send like email only for RuStore users
                        if ($isRuStoreUser) {
                            $this->sendLikeEmail($getUserData);
                        }
                    }
                }
            }
            return true;
        } catch (\Exception $e) {
            Log::channel('recommendations')->error('LikeNotification error: '.$e->getMessage(), [
                'user_id' => $userId,
                'error' => $e
            ]);
            return false;
        }
    }

    /**
     * @param  string  $comment
     * @param  string  $authorId
     * @param  string  $recipientId
     * @return void
     */
    private function leaveComment(string $comment, string $authorId, string $recipientId)
    {
        // Реализация добавления комментария тут
    }

    /**
     * @param  array  $attributes
     * @param  array  $values
     * @return UserReaction
     */
    private function updateOrCreateReaction(array $attributes, array $values): UserReaction
    {
        // Сначала пытаемся обновить
        if (UserReaction::where($attributes)->exists()) {
            UserReaction::where($attributes)->update($values);
            return UserReaction::where($attributes)->first();
        }

        // Если нет - создаем
        return UserReaction::create(array_merge($attributes, [
            'superboom' => false,
            'from_top' => false,
            'is_notified' => false,
            'from_reels' => false,
        ], $values));
    }

    /**
     * Activate superboom for user - extends superboom period by 30 minutes
     *
     * @param string $userId
     * @return array
     * @throws \Exception
     */
    public function superboom(string $userId): array
    {
        try {
            $userInformation = UserInformation::where('user_id', $userId)->first();

            if (! $userInformation) {
                throw new \Exception('User information not found');
            }

            // Check combined balance (allocated + purchased)
            if ($userInformation->getRemainingSuperbooms() <= 0) {
                throw new \Exception('No superbooms available');
            }

            $currentSuperboomDate = $userInformation->superboom_due_date ?
                \Carbon\Carbon::parse($userInformation->superboom_due_date) : null;
            $now = now();

            $baseDate = ($currentSuperboomDate && $currentSuperboomDate > $now) ? $currentSuperboomDate : $now;
            $newSuperboomDate = $baseDate->copy()->addMinutes(30);

            // Use the useSuperboom method to properly deduct from allocated first, then purchased
            if (! $userInformation->useSuperboom()) {
                throw new \Exception('Failed to use superboom');
            }

            $userInformation->update([
                'superboom_due_date' => $newSuperboomDate,
            ]);

            $this->clearTopProfilesCache();

            return [
                'message' => 'Action was executed successfully'
            ];

        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Clear top-profiles cache for all users
     *
     * @return void
     */
    private function clearTopProfilesCache(): void
    {
        try {
            $pattern = 'top-profiles:*';
            $keys = Redis::keys($pattern);

            if (! empty($keys)) {
                Redis::del($keys);
            }
        } catch (\Exception $e) {
            Log::channel('recommendations')->warning('Failed to clear top-profiles cache: '.$e->getMessage());
        }
    }

    public function sendLikeEmail($user)
    {
        try {
            $testEmail = 'sofiebridge@gmail.com';

            if (empty($user->email)) {
                Log::channel('recommendations')->info('User has no email', [
                    'user_id' => $user->id
                ]);
                return;
            }

            // Test
            if ($user->email !== $testEmail) {
                Log::channel('recommendations')->info('Email skipped - not test email', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'test_email' => $testEmail
                ]);
                return;
            }

            $mailService = new \App\Services\MailService();

            $mailService->queueFromTemplate(
                'user_like',
                $user->email,
                [
                    'user_name' => $user->name ?? 'пользователь',
                    'user_id' => $user->id
                ],
                $user->name
            );

            Log::channel('recommendations')->info('Like email queued for RuStore user', [
                'user_id' => $user->id,
                'email' => $user->email,
                'is_test_mode' => true
            ]);
        } catch (\Exception $e) {
            Log::channel('recommendations')->error('Failed to queue like email', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
