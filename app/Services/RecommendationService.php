<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Config\Repository;

class RecommendationService
{
    /**
     * @var int|Repository|Application|mixed|object|null
     */
    private int $cacheSize;

    /**
     * @var int|Repository|Application|mixed|object|null
     */
    private int $cacheTtl;

    /**
     * @var int|Repository|Application|mixed|object|null
     */
    private int $pageSize;

    /**
     *
     */
    public function __construct()
    {
        $this->cacheSize = config('recommendations.cache_size', 1000);
        $this->cacheTtl = config('recommendations.cache_ttl', 14400);
        $this->pageSize = config('recommendations.page_size', 10);
    }

    public function getRecommendationsV2(string $userId, array $query): array
    {
        $user = $this->getUserSettings($userId);

        if (empty($user->preferences) || count($user->preferences) === 0) {
            return ['items' => []];
        }

        $cacheKey = $this->generateCacheKey($userId, $user, $query). time();

        try {
            // Получаем кешированные рекомендации
            $cached = Cache::get($cacheKey, []);
            $forPage = array_splice($cached, 0, $this->pageSize);
            $forPage = [];

            if (empty($forPage)) {
                $fromDb = $this->getRecommendationsForCache($userId, $user, $query);
print_r($fromDb);

            echo "Count: " . count($fromDb) . PHP_EOL;
            exit;
                $forPage = array_splice($fromDb, 0, $this->pageSize);

                if (empty($forPage)) {
                    return ['items' => []];
                }

                if (!empty($fromDb)) {
                    Cache::put($cacheKey, $fromDb, $this->cacheTtl);
                }
            } else {
                $cached = array_splice($cached, $this->pageSize);
                Cache::put($cacheKey, $cached, $this->cacheTtl);
            }

            return $this->getRecommendationsPage($userId, $forPage);
        } catch (\Exception $e) {
            throw ValidationException::withMessages([
                'error' => 'Failed to get recommendations'
            ]);
        }
    }

    private function getUserSettings(string $userId): object
    {
        // В Laravel 11 предпочтительно использовать Eloquent, но если таблицы не Eloquent-модели, оставим DB-стиль.
        $user = DB::table('users')
            ->where('id', $userId)
            ->select(['id', 'phone', 'lat', 'long'])
            ->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'error' => 'User not found'
            ]);
        }

        // settings
        $user->settings = DB::table('user_settings')
            ->where('user_id', $userId)
            ->select(['user_id', 'age_range', 'search_radius', 'is_global_search'])
            ->first();

        // preferences
        $user->preferences = DB::table('user_preferences')
            ->where('user_id', $userId)
            ->select(['user_id', 'gender'])
            ->get();

        // interests
        $user->user_interest = DB::table('user_interests')
            ->where('user_id', $userId)
            ->select(['user_id', 'interest_id'])
            ->get();

        return $user;
    }

    private function generateCacheKey(string $userId, object $user, array $query): string
    {
        $parts = [
            'recommendations',
            $userId,
            $user->settings->age_range ?? '',
            ($user->settings->is_global_search ?? false) ? 'global' : ($user->settings->search_radius ?? ''),
            implode(',', collect($user->preferences)->pluck('gender')->toArray()),
            $query['interest_id'] ?? ''
        ];

        return implode(':', array_filter($parts));
    }

    private function getRecommendationsForCache(string $userId, object $user, array $query): array
    {
        $this->checkInterestAccess($userId, $query);

        // Increment variables
        [$latitude, $longitude] = explode('-', $user->settings->age_range);
        $gender = collect($user->preferences)->pluck('gender')->toArray();
        [$minAge, $maxAge] = explode('-', $user->settings->age_range);
        $radius = $user->settings->search_radius;
        $blockPhone = $user->phone;

        // Calculate
        $longDelta = $radius / (111.12 * cos(deg2rad($latitude)));
        $latDelta = $radius / 111.12;

        // Query
        $query = DB::table('users as u')
            ->leftJoin('user_information as ui', 'ui.user_id', '=', 'u.id')
            ->leftJoin('user_activity as ua', 'ua.user_id', '=', 'u.id')
            ->leftJoin('user_settings as us', 'us.user_id', '=', 'u.id')
            ->leftJoin('user_relationship_preferences as urp', 'urp.user_id', '=', 'u.id')
            ->select([
                'u.id',
                DB::raw('MAX(ui.superboom_due_date) as superboom_due_date'),
                DB::raw('MAX(ua.last_activity) as last_activity'),
                'u.is_online'
            ])
            ->where('u.id', '!=', $userId)
            ->whereNotNull('u.lat')
            ->whereNotNull('u.long')
            // bounding box
            ->whereBetween('u.lat', [$latitude - $latDelta, $latitude + $latDelta])
            ->whereBetween('u.long', [$longitude - $longDelta, $longitude + $longDelta])
            ->whereRaw('ST_Distance_Sphere(point(?, ?), point(u.long, u.lat)) / 1000 <= ?', [
                $longitude, $latitude, $radius
            ])
            ->whereBetween('u.age', [$minAge, $maxAge])
            ->whereIn('u.gender', $gender)
            ->whereNotNull('u.registration_date')
            ->where('u.mode', 'authenticated')
            // NOT EXISTS (user_reactions match)
            ->whereNotExists(function ($q) use ($userId) {
                $q->select(DB::raw(1))
                    ->from('user_reactions as ure')
                    ->whereRaw('ure.user_id = u.id')
                    ->where('ure.user_id', '!=', $userId)
                    ->where('ure.type', '!=', 'dislike')
                    ->whereExists(function ($q2) use ($userId) {
                        $q2->select(DB::raw(1))
                            ->from('user_reactions as ur')
                            ->whereRaw('ur.reactor_id = ure.user_id')
                            ->where('ur.user_id', '=', $userId)
                            ->where('ur.type', '!=', 'dislike');
                    });
            })
            // NOT EXISTS (reactions in last 24h)
            ->whereNotExists(function ($q) use ($userId) {
                $q->select(DB::raw(1))
                    ->from('user_reactions as ur')
                    ->whereRaw('ur.user_id = u.id')
                    ->where('ur.date', '>=', now()->subDays()->toDateTimeString())
                    ->where('ur.reactor_id', '=', $userId);
            })
            // NOT EXISTS (blocked_contacts by me)
            ->whereNotExists(function ($q) use ($userId) {
                $q->select(DB::raw(1))
                    ->from('blocked_contacts as bc')
                    ->whereRaw('bc.phone = u.phone')
                    ->where('bc.user_id', '=', $userId);
            })
            // NOT EXISTS (blocked_contacts by them)
            ->whereNotExists(function ($q) use ($blockPhone) {
                $q->select(DB::raw(1))
                    ->from('blocked_contacts as bc')
                    ->whereRaw('bc.user_id = u.id')
                    ->where('bc.phone', '=', $blockPhone);
            })
            ->groupBy('u.id', 'u.is_online')
            ->orderByDesc('u.is_online')
            ->orderByDesc(DB::raw('MAX(ua.last_activity)'))
            ->orderBy('u.id')
            ->limit($this->cacheSize);
        echo $query->toRawSql();

        return $query->pluck('u.id')->toArray();
    }

    private function checkInterestAccess(string $userId, array $query): void
    {
        if (!isset($query['interest_id'])) {
            return;
        }

        $hasAccess = DB::table('user_interests')
            ->where('user_id', $userId)
            ->where('interest_id', $query['interest_id'])
            ->exists();

        if ($hasAccess) {
            return;
        }

        // Проверка премиум-подписки
        $hasPremium = DB::table('transactions')
            ->where('user_id', $userId)
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('subscriptions')
                    ->whereColumn('transactions.subscription_id', 'subscriptions.id')
                    ->where('subscriptions.due_date', '>', now());
            })
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('packages')
                    ->whereColumn('transactions.package_id', 'packages.id')
                    ->where('packages.subscription_id', '>=', 2);
            })
            ->exists();

        if (!$hasPremium) {
            throw ValidationException::withMessages([
                'error' => 'Premium subscription required for this interest'
            ]);
        }
    }

    private function getRecommendationsPage(string $userId, array $userIds): array
    {
        if (empty($userIds)) {
            return ['items' => []];
        }

        $user = DB::table('users')
            ->where('id', $userId)
            ->select(['lat', 'long'])
            ->first();

        // Основная информация о пользователях
        $recommendations = DB::table('users as u')
            ->leftJoin('user_information as ui', 'u.id', '=', 'ui.user_id')
            ->leftJoin('user_settings as us', 'u.id', '=', 'us.user_id')
            ->whereIn('u.id', $userIds)
            ->select([
                'u.id',
                'u.name',
                'ui.bio',
                'u.is_online',
                DB::raw('IF(us.show_my_age, u.age, null) as age'),
                DB::raw('IF(us.show_distance_from_me,
                    ROUND(ST_Distance_Sphere(
                        point(?, ?),
                        point(u.long, u.lat)
                    ) / 1000, 0), null) as distance'),
                DB::raw('EXISTS(
                    SELECT 1 FROM verification_requests
                    WHERE user_id = u.id AND status = "approved"
                ) as is_verified')
            ])
            ->addBinding([$user->long, $user->lat], 'select')
            ->get();

        // Фотографии пользователей (группировка по user_id)
        $photos = DB::table('user_images')
            ->whereIn('user_id', $userIds)
            ->select(['user_id', 'image'])
            ->get()
            ->groupBy('user_id')
            ->map(fn($items) => $items->pluck('image')->toArray());

        return [
            'items' => $recommendations->map(function ($item) use ($photos) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'bio' => $item->bio,
                    'is_online' => (bool)$item->is_online,
                    'age' => $item->age ? (int)$item->age : null,
                    'distance' => $item->distance ? (int)$item->distance : null,
                    'is_verified' => (bool)$item->is_verified,
                    'photos' => $photos[$item->id] ?? []
                ];
            })->toArray()
        ];
    }

    /**
     * @param array $customer
     * @return Collection
     */
    public function getTopProfiles(array $customer): Collection
    {
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

        return $query->get();
    }
}
