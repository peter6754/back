<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class RecommendationService
{
    /**
     * @param array $customer
     * @return Collection
     */
    public static function getRecommendations(array $customer): Collection
    {

    }

    /**
     * @param array $customer
     * @return Collection
     */
    public static function getTopProfiles(array $customer): Collection
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
