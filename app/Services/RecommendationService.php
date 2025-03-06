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

        if (!empty($topProfiles)) {
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

            // Создаем CTE
            $filteredUsers = DB::table('users as u')
                ->selectRaw("
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
                 WHERE t.user_id = u.id AND bs.due_date >= NOW() LIMIT 1) AS has_subscription
            ")
                ->leftJoin('user_settings as us', 'us.user_id', '=', 'u.id')
                ->join('user_preferences as up', function ($join) use ($myUserId) {
                    $join->on('up.gender', '=', 'u.gender')
                        ->where('up.user_id', '=', $myUserId);
                })
                ->where('u.id', '!=', $myUserId)
                ->whereNotNull('u.lat')
                ->whereNotNull('u.long')
                ->where('u.mode', 'authenticated')
                ->whereNotNull('u.registration_date')
                ->whereNotExists(function ($query) use ($myPhone) {
                    $query->select(DB::raw(1))
                        ->from('blocked_contacts as bc')
                        ->whereRaw('bc.user_id = u.id')
                        ->where('bc.phone', $myPhone);
                })
                ->whereNotExists(function ($query) use ($myUserId) {
                    $query->select(DB::raw(1))
                        ->from('blocked_contacts as my_bc')
                        ->whereRaw('my_bc.phone = u.phone')
                        ->where('my_bc.user_id', $myUserId);
                })
                ->whereNotExists(function ($query) use ($myUserId, $twoDaysAgo) {
                    $query->select(DB::raw(1))
                        ->from('user_reactions as ure')
                        ->where('ure.reactor_id', $myUserId)
                        ->where('ure.type', '!=', 'dislike')
                        ->where('ure.date', '>=', $twoDaysAgo)
                        ->whereRaw('ure.user_id = u.id');
                });

            // Основной запрос из CTE
            $query = DB::table(DB::raw("({$filteredUsers->toSql()}) as filtered_users"))
                ->mergeBindings($filteredUsers)
                ->selectRaw("
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
                    ELSE ROUND(ST_Distance_Sphere(POINT(?, ?),POINT(`long`, `lat`)) / 1000, 0)
                END AS distance,
                COALESCE(like_count, 0) AS like_count
            ", [
                    $myLng,
                    $myLat,
                    $myPhone
                ])
                ->orderByDesc('like_count')
                ->limit(15);

            $topProfiles = $query->get();
            foreach ($topProfiles as &$row) {
                $row->blocked_me = (bool) $row->blocked_me;
            }

            Redis::setex($key, 900, json_encode($topProfiles));
        }

        return [
            'items' => $topProfiles
        ];
    }

    /**
     * @param  string  $userId
     * @param  array  $query
     * @return string|array
     */
    public function getRecommendations(string $userId, array $query): string|array
    {
        $user = Secondaryuser::with(['userSettings', 'userPreferences'])
            ->select(['id', 'phone', 'lat', 'long'])
            ->findOrFail($userId);

        if ($user->userPreferences->isEmpty()) {
            return ['items' => []];
        }

        // Configure cache params
        $keyPart1 = implode('-', $user->userSettings->age_range);
        $keyPart2 = $user->userSettings->is_global_search ? 'global' : $user->userSettings->search_radius;
        $keyPart3 = implode(',', $user->userPreferences->pluck('gender')->toArray());
        $keyPart4 = isset($query['interest_id']) ? ':'.$query['interest_id'] : '';

        // Configure cache
        $key = "recommendations:{$userId}:{$keyPart1}:{$keyPart2}:{$keyPart3}{$keyPart4}";
        $recommendationsCacheSize = $this->recommendationsPageSize;

        try {
            $forPage = Redis::transaction(function ($redis) use ($key, $recommendationsCacheSize) {
                $redis->lRange($key, 0, $recommendationsCacheSize - 1);
                $redis->lTrim($key, $recommendationsCacheSize, -1);
            })[0];

            if (empty($forPage)) {
                $fromDb = $this->_getRecommendationsForCache($userId, $query, $this->recommendationsCacheSize);
                $forPage = array_splice($fromDb, 0, $this->recommendationsPageSize);

                if (empty($forPage)) {
                    return "Мы не нашли анкеты с данным критериями поиска";
                }

                if (!empty($fromDb)) {
                    Redis::rpush($key, ...$fromDb);
                    Redis::expire($key, $this->recommendationsCacheTTL);
                }
            }

            return $this->_getRecommendationsPage($userId, $forPage);
        } catch (\Exception $e) {
            Log::channel('recommendations')->error('getRecommendations_v2 error: '.$e->getMessage(), [
                'user_id' => $userId,
                'error' => $e
            ]);
        }
    }

    /**
     * @param  string  $userId
     * @param  array  $params
     * @return array
     */
    public function like(string $userId, array $params)
    {
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
        $reactionExists = $this->checkExistingReaction($userId, $params);
        $superboom = $this->getSuperboomStatus($params);

        $this->updateOrCreateReaction([
            'user_id' => $params['user_id'],
            'reactor_id' => $userId,
        ], [
            'from_top' => $params['from_top'] ?? false,
            'superboom' => $superboom,
            'type' => 'superlike',
            'date' => now()
        ]);

        UserInformation::where('user_id', $userId)->decrement('superlikes');

        if (!empty($params['comment'])) {
            $this->leaveComment($params['comment'], $userId, $params['user_id']);
        }

        // Send notifications
        (new \App\Services\RecommendationService())->handleLikeNotification(
            $params['user_id'],
            $reactionExists,
            true
        );

        return ['is_match' => $reactionExists];
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

        if (!$lastReacted || $lastReacted->user_id != $params['user_id']) {
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
     * @param  string  $userId
     * @param  array  $query
     * @param  int  $cacheSize
     * @return array
     */
    private function _getRecommendationsForCache(string $userId, array $query, int $cacheSize): array
    {
        $user = Secondaryuser::with(['userSettings', 'preferences'])
            ->select(['id', 'phone', 'lat', 'long'])
            ->findOrFail($userId);

        $preferences = $user->preferences->pluck('gender')->toArray();
        $ageRange = $user->userSettings->age_range;

        $query = DB::select("
            WITH
            users_in_my_radius AS (
                SELECT u.id
                FROM users u
                WHERE ST_Distance_Sphere(point(?, ?), point(u.long, u.lat)) / 1000 <= ?
                    AND u.id != ?
            ),
            my_matches AS (
                SELECT user_id FROM (
                    SELECT user_id
                    FROM user_reactions
                    WHERE reactor_id = ?
                    
                    UNION ALL
                    
                    SELECT reactor_id as user_id
                    FROM user_reactions
                    WHERE user_id = ?
                        AND type = 'dislike'
                ) AS combined_results
            ),
            users_blocked_by_me AS (
                SELECT phone
                FROM blocked_contacts
                WHERE user_id = ?
            ),
            users_who_blocked_me AS (
                SELECT user_id
                FROM blocked_contacts
                WHERE phone = ?
            )
            SELECT DISTINCT u.id AS id
            FROM users u
            LEFT JOIN user_settings us ON us.user_id = u.id
            WHERE u.id != ?
                AND u.lat IS NOT NULL
                AND u.long IS NOT NULL
                AND (
                    u.id IN (SELECT id FROM users_in_my_radius)
                    OR ?
                )
                AND u.age BETWEEN ? AND ?
                AND u.id NOT IN (SELECT user_id FROM my_matches)
                AND u.gender IN(".(empty($preferences) ? 'NULL' : " '".implode("', '", $preferences)."' ").")
                AND u.phone NOT IN (SELECT phone FROM users_blocked_by_me)
                AND u.id NOT IN (SELECT user_id FROM users_who_blocked_me)
                AND u.registration_date IS NOT NULL
                AND u.mode = 'authenticated'
            GROUP BY u.id
            ORDER BY u.is_online DESC,
                (
                    SELECT MAX(last_activity)
                    FROM user_activity
                    WHERE user_id = u.id
                ) DESC, u.id ASC
            LIMIT ?
        ", [
            $user->long,
            $user->lat,
            $user->userSettings->search_radius,
            $userId,
//            $userId,
            $userId,
            $userId,
            $userId,
            $user->phone,
            $userId,
            $user->userSettings->is_global_search,
            $ageRange[0],
            $ageRange[1],
            $cacheSize
        ]);

        return array_map(fn($item) => $item->id, $query);
    }

    /**
     * @param  string  $userId
     * @param $usersIds
     * @return array
     */
    private function _getRecommendationsPage(string $userId, $usersIds): array
    {
        $user = Secondaryuser::select(['lat', 'long'])->findOrFail($userId);

        $recommendations = DB::select("
            SELECT
                u.id,
                u.gender,
                u.name,
                ui.bio,
                u.is_online,
                CAST(
                    IF(has_user_subscription(u.id) AND NOT us.show_my_age, NULL, u.age)
                    AS UNSIGNED
                ) AS age,
                CAST(
                    IF(has_user_subscription(u.id) AND NOT us.show_distance_from_me, NULL,
                        ROUND((SELECT count_distance(u.id, ?, ?)), 0)
                    )
                    AS UNSIGNED
                ) AS distance,
                CAST(
                    EXISTS(SELECT 1 FROM verification_requests WHERE user_id = u.id AND status = 'approved' LIMIT 1)
                    AS CHAR
                ) AS is_verified
            FROM users u
            LEFT JOIN user_information ui ON ui.user_id = u.id
            LEFT JOIN user_settings us ON us.user_id = u.id
            WHERE u.id IN (".implode(',', array_fill(0, count($usersIds), '?')).")
                AND u.mode = 'authenticated'
            ORDER BY u.is_online DESC;
        ", array_merge([
            $user->lat,
            $user->long
        ], $usersIds));

        $response = array_map(function ($r) {
            $photos = DB::table('user_images')
                ->where('user_id', $r->id)
                ->take(4)
                ->pluck('image')
                ->toArray();

            return [
                'id' => $r->id,
                'name' => $r->name,
                'bio' => $r->bio,
                'is_verified' => (bool) $r->is_verified,
                'is_online' => (bool) $r->is_online,
                'photos' => $photos,
                'age' => $r->age ? (int) $r->age : null,
                'distance' => $r->distance !== null ? (int) $r->distance : null
            ];
        }, $recommendations);

        return [
            'items' => $response
        ];
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
     * @param  string  $user_id
     * @param  bool  $is_match
     * @param  bool  $superlike
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
        // Реализация добавления комментария
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
}
