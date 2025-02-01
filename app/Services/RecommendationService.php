<?php

namespace App\Services;

use App\Models\UserReaction;
use App\Models\UserInformation;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Config\Repository;
use App\Models\Secondaryuser;

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
     * @param array $customer
     * @return array
     */
    public function getTopProfiles(array $customer): array
    {
        // Checking cache
        $key = "top-profiles:" . $customer['id'];
        $topProfiles = Redis::get($key);

        if (empty($topProfiles)) {
            $twoDaysAgo = now()->subDays(2)->toDateTimeString();
            $myUserId = $customer['id'];
            $myPhone = $customer['phone'];
            $myLat = $customer['long'];
            $myLng = $customer['lat'];

            // 1. excluded_users подзапрос (UNION двух частей)
            $excludedUsers1 = DB::table('user_reactions as ure')
                ->select('ure.user_id')
                ->where('ure.reactor_id', $myUserId)
                ->where('ure.type', '!=', 'dislike')
                ->where('ure.date', '>=', $twoDaysAgo);

            $excludedUsers2 = DB::table('user_reactions as ure')
                ->select('ure.user_id')
                ->join('user_reactions as ur', function ($join) use ($myUserId) {
                    $join->on('ur.reactor_id', '=', 'ure.user_id')
                        ->where('ur.user_id', $myUserId)
                        ->where('ur.type', '!=', 'dislike');
                })
                ->where('ure.reactor_id', $myUserId)
                ->where('ure.type', '!=', 'dislike');

            $excludedUsers = $excludedUsers1->union($excludedUsers2);

            // 2. min_images подзапрос
            $minImages = DB::table('user_images')
                ->select('user_id', DB::raw('MIN(id) as min_image_id'))
                ->groupBy('user_id');

            // 3. like_counts подзапрос
            $likeCounts = DB::table('user_reactions')
                ->select('user_id', DB::raw('COUNT(*) as like_count'))
                ->where('type', '!=', 'dislike')
                ->groupBy('user_id');

            // 4. subs подзапрос
            $subs = DB::table('bought_subscriptions as bs')
                ->join('transactions as t', 't.id', '=', 'bs.transaction_id')
                ->select('t.user_id')
                ->whereRaw('NOW() <= bs.due_date')
                ->groupBy('t.user_id');

            // 5. Флаг подписки текущего пользователя
            $myHasSubscription = DB::table('bought_subscriptions as bs')
                ->join('transactions as t', 't.id', '=', 'bs.transaction_id')
                ->where('t.user_id', $myUserId)
                ->whereRaw('NOW() <= bs.due_date')
                ->exists();

            // 6. Основной запрос
            $query = DB::table('users as u')
                ->selectRaw('
                u.id,
                u.name,
                (bc.user_id IS NOT NULL) as blocked_me,
                CASE WHEN subs.user_id IS NOT NULL AND NOT us.show_my_age THEN NULL ELSE u.age END as age,
                ui.image,
                CASE
                    WHEN subs.user_id IS NOT NULL AND NOT us.show_distance_from_me THEN NULL
                    ELSE ROUND(ST_Distance_Sphere(
                        POINT(u.`long`, u.lat),
                        POINT(?, ?)
                    ) / 1000, 0)
                END as distance,
                COALESCE(rc.like_count, 0) as like_count
            ', [$myLng, $myLat])
                ->join('user_preferences as up', function ($join) use ($myUserId) {
                    $join->on('up.gender', '=', 'u.gender')
                        ->where('up.user_id', '=', $myUserId);
                })
                ->leftJoin('user_settings as us', 'us.user_id', '=', 'u.id')
                ->leftJoinSub($minImages, 'mi', 'mi.user_id', '=', 'u.id')
                ->leftJoin('user_images as ui', 'ui.id', '=', 'mi.min_image_id')
                ->leftJoin('blocked_contacts as bc', function ($join) use ($myPhone) {
                    $join->on('bc.user_id', '=', 'u.id')
                        ->where('bc.phone', '=', $myPhone);
                })
                ->leftJoin('blocked_contacts as my_bc', function ($join) use ($myUserId) {
                    $join->on('my_bc.phone', '=', 'u.phone')
                        ->where('my_bc.user_id', '=', $myUserId);
                })
                ->leftJoinSub($likeCounts, 'rc', 'rc.user_id', '=', 'u.id')
                ->leftJoinSub($subs, 'subs', 'subs.user_id', '=', 'u.id')
                ->leftJoinSub($excludedUsers, 'eu', 'eu.user_id', '=', 'u.id')
                ->where('u.id', '!=', $myUserId)
                ->whereNotNull('u.lat')
                ->whereNotNull('u.long')
                ->where('u.mode', 'authenticated')
                ->whereNotNull('u.registration_date')
                // блокировка: если нет подписки — фильтруем по bc.user_id
                ->when(!$myHasSubscription, function ($query) {
                    $query->whereNull('bc.user_id');
                })
                ->whereNull('my_bc.user_id')
                ->whereNull('eu.user_id')
                ->orderByDesc('like_count')
                ->limit(15);

            $topProfiles = $query->get();
            foreach ($topProfiles as &$row) {
                $row->blocked_me = (bool)$row->blocked_me;
            }

            Redis::set($key, $topProfiles->toArray());
        }

        return [
            'items' => $topProfiles
        ];
    }

    /**
     * @param string $userId
     * @param array $query
     * @return array|array[]|void
     */
    public function getRecommendations(string $userId, array $query)
    {
        $user = Secondaryuser::with(['settings', 'preferences'])
            ->select(['id', 'phone', 'lat', 'long'])
            ->findOrFail($userId);

        if ($user->preferences->isEmpty()) {
            return ['items' => []];
        }

        // Configure cache params
        $keyPart1 = implode('-', $user->settings->age_range);
        $keyPart2 = $user->settings->is_global_search ? 'global' : $user->settings->search_radius;
        $keyPart3 = implode(',', $user->preferences->pluck('gender')->toArray());
        $keyPart4 = isset($query['interest_id']) ? ':' . $query['interest_id'] : '';

        // Configure cache
        $key = "recommended:{$userId}:{$keyPart1}:{$keyPart2}:{$keyPart3}{$keyPart4}";
        $recommendationsCacheSize = $this->recommendationsPageSize;

        try {
            $forPage = Redis::transaction(function ($redis) use ($key, $recommendationsCacheSize) {
                $redis->lRange($key, 0, $recommendationsCacheSize - 1);
                $redis->lTrim($key, $recommendationsCacheSize, -1);
            })[0];

            if (empty($forPage)) {
                $fromDb = $this->getRecommendationsForCache($userId, $query, $this->recommendationsCacheSize);
                $forPage = array_splice($fromDb, 0, $this->recommendationsPageSize);

                if (empty($forPage)) {
                    return ['items' => []];
                }

                if (!empty($fromDb)) {
                    Redis::rpush($key, ...$fromDb);
                    Redis::expire($key, $this->recommendationsCacheTTL);
                }
            }

            return $this->getRecommendationsPage($userId, $forPage);
        } catch (\Exception $e) {
            Log::channel('recommendations')->error('getRecommendations_v2 error: ' . $e->getMessage(), [
                'user_id' => $userId,
                'error' => $e
            ]);
        }
    }


    /**
     * @param string $userId
     * @param array $params
     * @return array
     */
    public function like(string $userId, array $params)
    {
        // Оптимизация: загружаем только необходимые данные
        $user = Secondaryuser::with(['deviceTokens', 'userInformation'])
            ->select(['id', 'email'])
            ->findOrFail($params['user_id']);

        // Проверяем существование реакции за один запрос
        $reactionExists = UserReaction::where('reactor_id', $params['user_id'])
            ->where('user_id', $userId)
            ->whereIn('type', ['like', 'superlike'])
            ->exists();

        // Оптимизация: вычисляем superboom заранее
        $superboom = $user->userInformation && $user->userInformation->superboom_due_date >= now();

        // Оптимизация: используем insertOrIgnore или прямой insert/update
        UserReaction::updateOrCreate(
            [
                'user_id' => $params['user_id'],
                'reactor_id' => $userId,
            ],
            [
                'from_top' => $params['from_top'],
                'superboom' => $superboom,
                'type' => 'like',
                'date' => now(),
            ]
        );

        return [
            'is_match' => $reactionExists,
        ];
    }

    /**
     * @param string $userId
     * @param array $params
     * @return array|null
     */
    public function dislike(string $userId, array $params)
    {
        UserReaction::updateOrCreate(
            [
                'user_id' => $params['user_id'],
                'reactor_id' => $userId,
            ],
            [
                'type' => 'dislike',
                'date' => now()
            ]
        );

        return [
            'message' => 'Reaction sent successfully'
        ];
    }

    /**
     * @param string $userId
     * @param array $params
     * @return string[]|void
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

        return [
            'message' => 'Rollbacked successfully'
        ];
    }

    /**
     * @param string $userId
     * @param array $params
     * @return bool[]|void
     */
    public function superlike(string $userId, array $params)
    {
        $user = Secondaryuser::with(['deviceTokens', 'userInformation'])
            ->select(['id', 'email'])
            ->findOrFail($params['user_id']);

        $reaction = UserReaction::where('reactor_id', $params['user_id'])
            ->where('user_id', $userId)
            ->whereIn('type', ['like', 'superlike'])
            ->first();

        $superboom = $user->userInformation->superboom_due_date >= now();

        UserReaction::updateOrCreate(
            [
                'user_id' => $params['user_id'],
                'reactor_id' => $userId,
            ],
            [
                'from_top' => $params['from_top'],
                'superboom' => $superboom,
                'type' => 'superlike',
                'date' => now()
            ]
        );

        UserInformation::where('user_id', $userId)->decrement('superlikes');

        if (!empty($params['comment'])) {
            $this->leaveComment($params['comment'], $userId, $params['user_id']);
        }

        return [
            'is_match' => (bool)$reaction
        ];
    }

    /**
     * @param string $matchedId
     * @param string $userId
     * @return void
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
    }

    /**
     * @param string $userId
     * @param array $query
     * @param int $cacheSize
     * @return array
     */
    private function getRecommendationsForCache(string $userId, array $query, int $cacheSize): array
    {
        $user = Secondaryuser::with(['settings', 'preferences'])
            ->select(['id', 'phone', 'lat', 'long'])
            ->findOrFail($userId);

        $preferences = $user->preferences->pluck('gender')->toArray();
        $ageRange = $user->settings->age_range;

        $query = DB::select("
            WITH
            users_in_my_radius AS (
                SELECT u.id
                FROM users u
                WHERE ST_Distance_Sphere(point(?, ?), point(u.long, u.lat)) / 1000 <= ?
                    AND u.id != ?
            ),
            my_matches AS (
                SELECT ure.user_id
                FROM user_reactions ure
                LEFT JOIN user_reactions ur ON ur.reactor_id = ure.user_id
                    AND ur.user_id = ?
                    AND ure.reactor_id = ?
                WHERE ure.user_id != ?
                    AND ure.type != 'dislike'
                    AND ur.type != 'dislike'
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
                AND u.gender IN(" . (empty($preferences) ? 'NULL' : " '" . implode("', '", $preferences) . "' ") . ")
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
            $user->settings->search_radius,
            $userId,
            $userId,
            $userId,
            $userId,
            $userId,
            $user->phone,
            $userId,
            $user->settings->is_global_search ? 1 : 0,
            $ageRange[0],
            $ageRange[1],
            $cacheSize
        ]);

        return array_map(fn($item) => $item->id, $query);
    }

    /**
     * @param string $userId
     * @param $usersIds
     * @return array
     */
    private function getRecommendationsPage(string $userId, $usersIds): array
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
            WHERE u.id IN (" . implode(',', array_fill(0, count($usersIds), '?')) . ")
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
                'is_verified' => (bool)$r->is_verified,
                'is_online' => (bool)$r->is_online,
                'photos' => $photos,
                'age' => $r->age ? (int)$r->age : null,
                'distance' => $r->distance ? (int)$r->distance : null
            ];
        }, $recommendations);

        return [
            'items' => $response
        ];
    }
}
