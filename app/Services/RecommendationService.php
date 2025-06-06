<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class RecommendationService
{


    public static function getPotentialMatches(array $customer)
    {
        $twoDaysAgo = now()->subDays(2)->toDateTimeString();

        $currentUserId = $customer['id'];
        $phone = $customer['phone'];
        $lng = $customer['long'];
        $lat = $customer['lat'];

        return DB::table('users as u')
            ->select([
                'u.id',
                'u.name',
                DB::raw("CAST(EXISTS(SELECT 1 FROM blocked_contacts WHERE phone = ? AND user_id = u.id) AS CHAR) AS blocked_me", [$phone]),
                DB::raw("CAST(IF(has_user_subscription(u.id) AND NOT us.show_my_age, NULL, u.age) AS CHAR) AS age"),
                DB::raw("(SELECT image FROM user_images WHERE user_id = u.id LIMIT 1) AS image"),
                DB::raw("CAST(IF(has_user_subscription(u.id) AND NOT us.show_distance_from_me, NULL, ROUND(count_distance(u.id, ?, ?), 0)) AS CHAR) AS distance", [$lat, $lng])
            ])
            ->leftJoin('user_settings as us', 'us.user_id', '=', 'u.id')
            ->where('u.id', '!=', $currentUserId)
            ->whereNotNull('u.lat')
            ->whereNotNull('u.long')
            ->where('u.mode', 'authenticated')
            ->whereNotNull('u.registration_date')
            // Исключаем взаимные лайки
            ->whereNotExists(function ($query) use ($currentUserId) {
                $query->select(DB::raw(1))
                    ->from('user_reactions as ure')
                    ->where('ure.user_id', DB::raw('u.id'))
                    ->where('ure.reactor_id', $currentUserId)
                    ->where('ure.type', '!=', 'dislike')
                    ->whereExists(function ($subQuery) use ($currentUserId) {
                        $subQuery->select(DB::raw(1))
                            ->from('user_reactions as ur')
                            ->where('ur.user_id', $currentUserId)
                            ->where('ur.reactor_id', DB::raw('ure.user_id'))
                            ->where('ur.type', '!=', 'dislike');
                    });
            })
            // Исключаем пользователей, которым я ставил лайк в последние 48 часов
            ->whereNotExists(function ($query) use ($currentUserId, $twoDaysAgo) {
                $query->select(DB::raw(1))
                    ->from('user_reactions as ur')
                    ->where('ur.user_id', DB::raw('u.id'))
                    ->where('ur.reactor_id', $currentUserId)
                    ->where('ur.date', '>=', $twoDaysAgo);
            })
            // Проверка блокировок
            ->where(function ($query) use ($phone, $currentUserId) {
                $query->whereNotExists(function ($subQuery) use ($phone) {
                    $subQuery->select(DB::raw(1))
                        ->from('blocked_contacts')
                        ->where('phone', $phone)
                        ->where('user_id', DB::raw('u.id'));
                })
                    ->orWhere(DB::raw("has_user_subscription(?)"), [$currentUserId]);
            })
            // Исключаем пользователей, которых я заблокировал
            ->whereNotExists(function ($query) use ($currentUserId) {
                $query->select(DB::raw(1))
                    ->from('blocked_contacts')
                    ->where('user_id', $currentUserId)
                    ->where('phone', DB::raw('u.phone'));
            })
            // Фильтр по полу
            ->whereExists(function ($query) use ($currentUserId) {
                $query->select(DB::raw(1))
                    ->from('user_preferences')
                    ->where('user_id', $currentUserId)
                    ->where('gender', DB::raw('u.gender'));
            })
            // Сортировка по количеству лайков
            ->orderByDesc(
                DB::raw("(SELECT COUNT(*) FROM user_reactions WHERE user_id = u.id AND type != 'dislike')")
            )
            ->limit(15)
            ->get();
    }


    public static function getPotentialMatchesOptimized(array $customer)
    {
        $twoDaysAgo = now()->subDays(2)->toDateTimeString();

        $currentUserId = $customer['id'];
        $phone = $customer['phone'];
        $lng = $customer['long'];
        $lat = $customer['lat'];

        return DB::table('users as u')
            ->select([
                'u.id',
                'u.name',
                DB::raw("COALESCE(bc.blocked, '0') AS blocked_me"),
                DB::raw("CASE WHEN has_user_subscription(u.id) AND NOT us.show_my_age THEN NULL ELSE u.age END AS age"),
                'ui.image',
                DB::raw("CASE WHEN has_user_subscription(u.id) AND NOT us.show_distance_from_me THEN NULL ELSE ROUND(count_distance(u.id, ?, ?), 0) END AS distance", [$lat, $lng]),
                DB::raw("COALESCE(rc.like_count, 0) AS like_count")
            ])
            ->leftJoin('user_settings as us', 'us.user_id', '=', 'u.id')
            ->leftJoin('user_images as ui', function($join) {
                $join->on('ui.user_id', '=', 'u.id')
                    ->whereRaw('ui.id = (SELECT MIN(id) FROM user_images WHERE user_id = u.id)');
            })
            ->leftJoin('blocked_contacts as bc', function($join) use ($phone) {
                $join->on('bc.user_id', '=', 'u.id')
                    ->where('bc.phone', '=', $phone);
            })
            ->leftJoin('blocked_contacts as my_bc', function($join) use ($currentUserId) {
                $join->on('my_bc.phone', '=', 'u.phone')
                    ->where('my_bc.user_id', '=', $currentUserId);
            })
            ->leftJoin(DB::raw('(SELECT user_id, COUNT(*) as like_count FROM user_reactions WHERE type != "dislike" GROUP BY user_id) rc'), 'rc.user_id', '=', 'u.id')
            ->join('user_preferences as up', function($join) use ($currentUserId) {
                $join->on('up.gender', '=', 'u.gender')
                    ->where('up.user_id', '=', $currentUserId);
            })
            ->where('u.id', '!=', $currentUserId)
            ->whereNotNull('u.lat')
            ->whereNotNull('u.long')
            ->where('u.mode', 'authenticated')
            ->whereNotNull('u.registration_date')
            // Исключаем взаимные лайки
            ->whereNotExists(function($query) use ($currentUserId) {
                $query->select(DB::raw(1))
                    ->from('user_reactions as ure')
                    ->whereColumn('ure.user_id', 'u.id')
                    ->where('ure.reactor_id', $currentUserId)
                    ->where('ure.type', '!=', 'dislike')
                    ->join('user_reactions as ur', function($join) use ($currentUserId) {
                        $join->on('ur.reactor_id', '=', 'ure.user_id')
                            ->where('ur.user_id', $currentUserId)
                            ->where('ur.type', '!=', 'dislike');
                    });
            })
            // Исключаем пользователей, которым я ставил лайк в последние 48 часов
            ->whereNotExists(function($query) use ($currentUserId, $twoDaysAgo) {
                $query->select(DB::raw(1))
                    ->from('user_reactions as ur')
                    ->whereColumn('ur.user_id', 'u.id')
                    ->where('ur.reactor_id', $currentUserId)
                    ->where('ur.date', '>=', $twoDaysAgo);
            })
            // Проверка блокировок
            ->where(function($query) use ($currentUserId) {
                $query->whereNull('bc.phone')
                    ->orWhere(DB::raw("has_user_subscription(?)"), [$currentUserId]);
            })
            // Исключаем мои блокировки
            ->whereNull('my_bc.phone')
            ->orderByDesc('like_count')
            ->limit(15)
            ->get();
    }
}
