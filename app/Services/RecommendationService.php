<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class RecommendationService
{
    public static function getTopProfiles(array $customer): \Illuminate\Support\Collection
    {
        $twoDaysAgo = now()->subDays(2)->toDateTimeString();
        $myUserId = $customer['id'];
        $myPhone = $customer['phone'];
        $myLat = $customer['long'];
        $myLng = $customer['lat'];

        $candidateIds = DB::table('users as u')
            ->join('user_preferences as up', function ($join) use ($myUserId) {
                $join->on('up.gender', '=', 'u.gender')
                    ->where('up.user_id', '=', $myUserId);
            })
            ->where('u.id', '!=', $myUserId)
            ->whereNotNull('u.lat')
            ->whereNotNull('u.long')
            ->where('u.mode', 'authenticated')
            ->whereNotNull('u.registration_date')
            // NOT EXISTS 1
            ->whereNotExists(function ($query) use ($myUserId, $twoDaysAgo) {
                $query->select(DB::raw(1))
                    ->from('user_reactions as ure')
                    ->whereColumn('ure.user_id', 'u.id')
                    ->where('ure.reactor_id', $myUserId)
                    ->where('ure.type', '!=', 'dislike')
                    ->where('ure.date', '>=', $twoDaysAgo);
            })
            // NOT EXISTS 2
            ->whereNotExists(function ($query) use ($myUserId) {
                $query->select(DB::raw(1))
                    ->from('user_reactions as ure')
                    ->join('user_reactions as ur', function ($join) use ($myUserId) {
                        $join->on('ur.reactor_id', '=', 'ure.user_id')
                            ->where('ur.user_id', $myUserId)
                            ->where('ur.type', '!=', 'dislike');
                    })
                    ->whereColumn('ure.user_id', 'u.id')
                    ->where('ure.reactor_id', $myUserId)
                    ->where('ure.type', '!=', 'dislike');
            })
            ->limit(300)
            ->pluck('u.id');


        // 2. Основной запрос
        $users = DB::table('users as u')
            ->selectRaw('
                u.id,
                u.name,
                (bc.user_id IS NOT NULL) as blocked_me,
                CASE
                    WHEN subs.user_id IS NOT NULL AND NOT us.show_my_age THEN NULL
                    ELSE u.age
                END as age,
                ui.image,
                CASE
                    WHEN subs.user_id IS NOT NULL AND NOT us.show_distance_from_me THEN NULL
                    ELSE ROUND(
                        ST_Distance_Sphere(
                            POINT(u.long, u.lat),
                            POINT(?, ?)
                        ) / 1000, 0
                    )
                END as distance,
                COALESCE(rc.like_count, 0) as like_count
            ', [
                $myLng, $myLat
            ])
            ->leftJoin('user_settings as us', 'us.user_id', '=', 'u.id')
            ->leftJoin(DB::raw('(SELECT user_id, MIN(id) as min_image_id FROM user_images GROUP BY user_id) as mi'), 'mi.user_id', '=', 'u.id')
            ->leftJoin('user_images as ui', 'ui.id', '=', 'mi.min_image_id')
            ->leftJoin('blocked_contacts as bc', function ($join) use ($myPhone) {
                $join->on('bc.user_id', '=', 'u.id')
                    ->where('bc.phone', '=', $myPhone);
            })
            ->leftJoin('blocked_contacts as my_bc', function ($join) use ($myUserId) {
                $join->on('my_bc.phone', '=', 'u.phone')
                    ->where('my_bc.user_id', '=', $myUserId);
            })
            ->leftJoin(DB::raw('
                (SELECT user_id, COUNT(*) as like_count
                 FROM user_reactions
                 WHERE type != "dislike"
                 GROUP BY user_id) as rc
            '), 'rc.user_id', '=', 'u.id')
            ->leftJoin(DB::raw('
                (SELECT t.user_id
                 FROM bought_subscriptions bs
                 JOIN transactions t ON t.id = bs.transaction_id
                 WHERE NOW() <= bs.due_date
                 GROUP BY t.user_id) as subs
            '), 'subs.user_id', '=', 'u.id')
            // Подгрузи информацию о подписке текущего пользователя если нужно через отдельный запрос
            ->whereIn('u.id', $candidateIds)
            ->where(function ($query) use ($myUserId) {
                $query->whereNull('bc.user_id')
                    ->orWhereRaw('EXISTS (
                  SELECT 1 FROM bought_subscriptions bs
                  JOIN transactions t ON t.id = bs.transaction_id
                  WHERE t.user_id = ? AND NOW() <= bs.due_date LIMIT 1
              )', [$myUserId]);
            })
            ->whereNull('my_bc.user_id')
            ->orderByDesc('like_count')
            ->limit(15)
            ->get();

        return $users;
    }

    public static function getTopProfiles1(array $customer): \Illuminate\Support\Collection
    {
        $twoDaysAgo = now()->subDays(2)->toDateTimeString();

        $myUserId = $customer['id'];
        $myPhone = $customer['phone'];
        $myLat = $customer['long'];
        $myLng = $customer['lat'];

        $query = DB::table('users as u')
            ->selectRaw('
                u.id,
                u.name,
                (bc.user_id IS NOT NULL) as blocked_me,
                CASE
                    WHEN function_has_subscription(u.id) AND NOT us.show_my_age THEN NULL
                    ELSE u.age
                END as age,
                ui.image,
                CASE
                    WHEN function_has_subscription(u.id) AND NOT us.show_distance_from_me THEN NULL
                    ELSE ROUND(ST_Distance_Sphere(
                        POINT(u.long, u.lat),
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
            ->leftJoin(DB::raw('
                (SELECT user_id, MIN(id) as min_image_id
                 FROM user_images
                 GROUP BY user_id) as min_ui
            '), 'min_ui.user_id', '=', 'u.id')
            ->leftJoin('user_images as ui', 'ui.id', '=', 'min_ui.min_image_id')
            ->leftJoin('blocked_contacts as bc', function ($join) use ($myPhone) {
                $join->on('bc.user_id', '=', 'u.id')
                    ->where('bc.phone', '=', $myPhone);
            })
            ->leftJoin('blocked_contacts as my_bc', function ($join) use ($myUserId) {
                $join->on('my_bc.phone', '=', 'u.phone')
                    ->where('my_bc.user_id', '=', $myUserId);
            })
            ->leftJoin(DB::raw('
                (SELECT user_id, COUNT(*) as like_count
                 FROM user_reactions
                 WHERE type != "dislike"
                 GROUP BY user_id) as rc
            '), 'rc.user_id', '=', 'u.id')
            ->where('u.id', '!=', $myUserId)
            ->whereNotNull('u.lat')
            ->whereNotNull('u.long')
            ->where('u.mode', '=', 'authenticated')
            ->whereNotNull('u.registration_date')
            // NOT EXISTS 1
            ->whereNotExists(function ($query) use ($myUserId, $twoDaysAgo) {
                $query->select(DB::raw(1))
                    ->from('user_reactions as ure')
                    ->whereColumn('ure.user_id', 'u.id')
                    ->where('ure.reactor_id', '=', $myUserId)
                    ->where('ure.type', '!=', 'dislike')
                    ->where('ure.date', '>=', $twoDaysAgo);
            })
            // NOT EXISTS 2
            ->whereNotExists(function ($query) use ($myUserId) {
                $query->select(DB::raw(1))
                    ->from('user_reactions as ure')
                    ->join('user_reactions as ur', function ($join) use ($myUserId) {
                        $join->on('ur.reactor_id', '=', 'ure.user_id')
                            ->where('ur.user_id', '=', $myUserId)
                            ->where('ur.type', '!=', 'dislike');
                    })
                    ->whereColumn('ure.user_id', 'u.id')
                    ->where('ure.reactor_id', '=', $myUserId)
                    ->where('ure.type', '!=', 'dislike');
            })
            ->where(function ($query) use ($myUserId) {
                $query->whereNull('bc.user_id')
                    ->orWhereRaw('function_has_subscription(?) = 1', [$myUserId]);
            })
            ->whereNull('my_bc.user_id')
            ->orderByDesc('like_count')
            ->limit(15);

        return $query->get();
    }

    public static function getTopProfiles2(array $customer): \Illuminate\Support\Collection
    {
        $twoDaysAgo = now()->subDays(2)->toDateTimeString();

        $currentUserId = $customer['id'];
        $phone = $customer['phone'];
        $lng = $customer['long'];
        $lat = $customer['lat'];

        $query = DB::table('users as u')
            ->select([
                'u.id',
                'u.name',
                DB::raw("IF(bc.user_id IS NOT NULL, '1', '0') AS blocked_me"),
                DB::raw("CASE WHEN has_user_subscription(u.id) AND NOT us.show_my_age THEN NULL ELSE u.age END AS age"),
                'ui.image',
                DB::raw("CASE WHEN has_user_subscription(u.id) AND NOT us.show_distance_from_me THEN NULL ELSE ROUND(count_distance(u.id, ?, ?), 0) END AS distance"),
//                DB::raw("COALESCE(rc.like_count, 0) AS like_count")
            ])
            ->leftJoin('user_settings as us', 'us.user_id', '=', 'u.id')
            ->leftJoin('user_images as ui', function ($join) {
                $join->on('ui.user_id', '=', 'u.id')
                    ->whereRaw('ui.id = (SELECT MIN(id) FROM user_images WHERE user_id = u.id)');
            })
            ->leftJoin('blocked_contacts as bc', function ($join) use ($phone) {
                $join->on('bc.user_id', '=', 'u.id')
                    ->where('bc.phone', '=', $phone);
            })
            ->leftJoin('blocked_contacts as my_bc', function ($join) use ($currentUserId) {
                $join->on('my_bc.phone', '=', 'u.phone')
                    ->where('my_bc.user_id', '=', $currentUserId);
            })
            ->leftJoin(DB::raw('(SELECT user_id, COUNT(*) as like_count FROM user_reactions WHERE type != "dislike" GROUP BY user_id) rc'), 'rc.user_id', '=', 'u.id')
            ->join('user_preferences as up', function ($join) use ($currentUserId) {
                $join->on('up.gender', '=', 'u.gender')
                    ->where('up.user_id', '=', $currentUserId);
            })
            ->where('u.id', '!=', $currentUserId)
            ->whereNotNull('u.lat')
            ->whereNotNull('u.long')
            ->where('u.mode', 'authenticated')
            ->whereNotNull('u.registration_date')
            // Исключаем взаимные лайки
            ->whereNotExists(function ($query) use ($currentUserId) {
                $query->select(DB::raw(1))
                    ->from('user_reactions as ure')
                    ->whereColumn('ure.user_id', 'u.id')
                    ->where('ure.reactor_id', $currentUserId)
                    ->where('ure.type', '!=', 'dislike')
                    ->join('user_reactions as ur', function ($join) use ($currentUserId) {
                        $join->on('ur.reactor_id', '=', 'ure.user_id')
                            ->where('ur.user_id', $currentUserId)
                            ->where('ur.type', '!=', 'dislike');
                    });
            })
            // Исключаем пользователей, которым я ставил лайк в последние 48 часов
            ->whereNotExists(function ($query) use ($currentUserId, $twoDaysAgo) {
                $query->select(DB::raw(1))
                    ->from('user_reactions as ur')
                    ->whereColumn('ur.user_id', 'u.id')
                    ->where('ur.reactor_id', $currentUserId)
                    ->where('ur.date', '>=', $twoDaysAgo);
            })
            // Проверка блокировок
            ->where(function ($query) use ($currentUserId) {
                $query->whereNull('bc.user_id')
                    ->orWhere(DB::raw("has_user_subscription('{$currentUserId}')"), '=', 1);
            })
            // Исключаем мои блокировки
            ->whereNull('my_bc.user_id')
            ->orderByDesc('like_count')
            ->limit(15);

        // Добавляем bindings для параметров расстояния
        $query->addBinding($lat, 'select');
        $query->addBinding($lng, 'select');

        return $query->get();
    }
}
