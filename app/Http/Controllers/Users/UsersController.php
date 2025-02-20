<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Http\Requests\GetUserLikesRequest;
use App\Models\BoughtSubscriptions;
use App\Models\LikeSettings;
use App\Models\Users;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UsersController extends Controller
{
    /**
     * Get user likes with filtering options
     * @OA\Get(
     *     path="/users/likes",
     *     summary="Get user likes",
     *     description="Retrieve users who liked the authenticated user with various filtering options",
     *     operationId="getUserLikes",
     *     tags={"User"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="filter",
     *         in="query",
     *         required=false,
     *         description="Filter type for results",
     *
     *         @OA\Schema(
     *             type="string",
     *             enum={"by_distance", "by_information", "by_verification_status", "by_settings"}
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="items",
     *                 type="array",
     *
     *                 @OA\Items(
     *
     *                     @OA\Property(property="id", type="string"),
     *                     @OA\Property(property="name", type="string"),
     *                     @OA\Property(property="image", type="string"),
     *                     @OA\Property(property="age", type="integer", nullable=true),
     *                     @OA\Property(property="distance", type="integer", nullable=true),
     *                     @OA\Property(property="superliked_me", type="boolean"),
     *                     @OA\Property(property="is_online", type="boolean")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error"
     *     )
     * )
     */
    public function getUserLikes(GetUserLikesRequest $request): JsonResponse
    {
        try {
            // Получаем текущего пользователя
            $user = $request->customer;

            // Получаем параметр фильтра
            $filter = $request->get('filter');

            // Константа для базовой подписки
            $PLUS_SUBSCRIPTION_ID = 1;

            $userSettings = null;

            // Если фильтр by_settings, проверяем подписку пользователя
            if ($filter === 'by_settings') {
                $userLatestSubscription = BoughtSubscriptions::where('due_date', '>', now())
                    ->whereHas('transaction', function ($query) use ($user) {
                        $query->where('user_id', $user->id);
                    })
                    ->with(['package.subscription'])
                    ->first();

                if ($userLatestSubscription && $userLatestSubscription->package &&
                    $userLatestSubscription->package->subscription_id > $PLUS_SUBSCRIPTION_ID) {
                    $userSettings = LikeSettings::where('user_id', $user->id)->first();
                }
            }

            // Запрос аналогично Node.js версии
            $queryData = $this->buildGetLikesQuery($user, $filter, $userSettings);
            $query = $queryData['query'];

            $results = $query->get();

            // Форматируем результаты
            $formattedResults = collect($results)->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'image' => $user->image,
                    'age' => $user->age ? (int)$user->age : null,
                    'distance' => $user->distance ? (int)$user->distance : null,
                    'superliked_me' => (bool)$user->superliked_me,
                    'is_online' => (bool)$user->is_online,
                ];
            });

            return response()->json([
                'items' => $formattedResults,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 5000,
                'message' => 'Something went wrong: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Build Eloquent query for getting user likes
     * @param Users $user
     * @param string|null $filter
     * @param LikeSettings|null $userSettings
     */
    private function buildGetLikesQuery($user, $filter, $userSettings): array
    {
        // Построим запрос используя Query Builder
        $query = DB::table('users as u')
            ->select([
                'u.id',
                'u.name',
                DB::raw('(SELECT image FROM user_images WHERE user_id = u.id LIMIT 1) as image'),
                DB::raw('CASE
                    WHEN EXISTS(
                        SELECT 1 FROM bought_subscriptions bs
                        JOIN transactions t ON t.id = bs.transaction_id
                        WHERE t.user_id = u.id AND bs.due_date > NOW()
                    ) AND us.show_my_age = 0 THEN NULL
                    ELSE u.age
                END as age'),
                DB::raw('CASE
                    WHEN EXISTS(
                        SELECT 1 FROM bought_subscriptions bs
                        JOIN transactions t ON t.id = bs.transaction_id
                        WHERE t.user_id = u.id AND bs.due_date > NOW()
                    ) AND us.show_distance_from_me = 0 THEN NULL
                    ELSE ROUND(
                        (6371 * acos(
                            cos(radians(' . $user->lat . ')) * cos(radians(u.lat)) *
                            cos(radians(u.long) - radians(' . $user->long . ')) +
                            sin(radians(' . $user->lat . ')) * sin(radians(u.lat))
                        )), 0
                    )
                END as distance'),
                DB::raw('EXISTS(
                    SELECT 1 FROM user_reactions ur2
                    WHERE ur2.user_id = "' . $user->id . '" AND ur2.reactor_id = u.id AND ur2.type = "superlike"
                ) as superliked_me'),
                DB::raw('CASE
                    WHEN us.status_online = 1 THEN u.is_online
                    ELSE 0
                END as is_online'),
            ])
            ->leftJoin('user_settings as us', 'us.user_id', '=', 'u.id')
            ->leftJoin('user_information as ui', 'ui.user_id', '=', 'u.id')
            ->whereExists(function ($query) use ($user) {
                $query->select(DB::raw(1))
                    ->from('user_reactions')
                    ->where('user_id', $user->id)
                    ->whereColumn('reactor_id', 'u.id')
                    ->whereIn('type', ['like', 'superlike']);
            })
            ->whereNotExists(function ($query) use ($user) {
                // Исключаем матчи
                $query->select(DB::raw(1))
                    ->from('user_reactions as ur1')
                    ->whereColumn('ur1.user_id', 'u.id')
                    ->where('ur1.reactor_id', $user->id)
                    ->where('ur1.type', '!=', 'dislike')
                    ->whereExists(function ($subquery) use ($user) {
                        $subquery->select(DB::raw(1))
                            ->from('user_reactions as ur2')
                            ->where('ur2.user_id', $user->id)
                            ->whereColumn('ur2.reactor_id', 'u.id')
                            ->where('ur2.type', '!=', 'dislike');
                    });
            })
            ->whereNotExists(function ($query) use ($user) {
                // Исключаем дизлайки
                $query->select(DB::raw(1))
                    ->from('user_reactions')
                    ->where('reactor_id', $user->id)
                    ->whereColumn('user_id', 'u.id')
                    ->where('type', 'dislike');
            });

        // Bindings для distance calculation и superliked_me check

        // Добавляем фильтрацию
        if ($userSettings) {
            // Фильтр по настройкам пользователя
            if ($userSettings->radius) {
                $query->whereRaw('(6371 * acos(cos(radians(' . $user->lat . ')) * cos(radians(u.lat)) * cos(radians(u.long) - radians(' . $user->long . ')) + sin(radians(' . $user->lat . ')) * sin(radians(u.lat)))) <= ' . $userSettings->radius);
            }

            if ($userSettings->age_range) {
                $ageRange = explode('-', $userSettings->age_range);
                $query->whereBetween('u.age', [(int)$ageRange[0], (int)$ageRange[1]]);
            }

            if ($userSettings->verified) {
                $query->whereExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('verification_requests')
                        ->whereColumn('user_id', 'u.id')
                        ->where('status', 'approved');
                });
            }

            if ($userSettings->has_info) {
                $query->whereNotNull('ui.bio');
            }

            if ($userSettings->min_photo_count) {
                $query->whereRaw('(SELECT COUNT(*) FROM user_images WHERE user_id = u.id) >= ?', [$userSettings->min_photo_count]);
            }

            // Гендерные предпочтения из like_preferences
            $query->whereExists(function ($query) use ($user) {
                $query->select(DB::raw(1))
                    ->from('like_preferences')
                    ->where('user_id', $user->id)
                    ->whereColumn('gender', 'u.gender');
            });

        } else {
            // Простые фильтры
            switch ($filter) {
                case 'by_distance':
                    $query->whereRaw('(6371 * acos(cos(radians(' . $user->lat . ')) * cos(radians(u.lat)) * cos(radians(u.long) - radians(' . $user->long . ')) + sin(radians(' . $user->lat . ')) * sin(radians(u.lat)))) <= 30');
                    break;
                case 'by_verification_status':
                    $query->whereExists(function ($query) {
                        $query->select(DB::raw(1))
                            ->from('verification_requests')
                            ->whereColumn('user_id', 'u.id')
                            ->where('status', 'approved');
                    });
                    break;
                case 'by_information':
                    $query->whereNotNull('ui.bio');
                    break;
            }
        }

        return [
            'query' => $query,
        ];
    }
}
