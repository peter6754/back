<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Http\Requests\GetUserLikesRequest;
use App\Http\Traits\ApiResponseTrait;
use App\Models\BoughtSubscriptions;
use App\Models\LikeSettings;
use App\Models\Users;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;

class UsersController extends Controller
{
    use ApiResponseTrait;

    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Get user likes with filtering options
     *
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
            // Получаем текущего пользователя через customer или auth user
            $secondaryUser = $request->customer ?? $request->user();

            if (! $secondaryUser) {
                return $this->errorUnauthorized();
            }

            // Получаем параметр фильтра
            $filter = $request->get('filter');

            // Константа для базовой подписки
            $PLUS_SUBSCRIPTION_ID = 1;

            $userSettings = null;

            // Если фильтр by_settings, проверяем подписку пользователя
            if ($filter === 'by_settings') {
                $userLatestSubscription = BoughtSubscriptions::where('due_date', '>', now())
                    ->whereHas('transaction', function ($query) use ($secondaryUser) {
                        $query->where('user_id', $secondaryUser->id);
                    })
                    ->with(['package.subscription'])
                    ->first();

                if ($userLatestSubscription && $userLatestSubscription->package &&
                    $userLatestSubscription->package->subscription_id > $PLUS_SUBSCRIPTION_ID) {
                    $userSettings = LikeSettings::where('user_id', $secondaryUser->id)->first();
                }
            }

            // Получаем через UserService (теперь возвращает готовую коллекцию)
            $formattedResults = $this->userService->getUserLikes($secondaryUser, $filter, $userSettings);

            return $this->successResponse([
                'items' => $formattedResults,
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse(
                'Something went wrong: '.$e->getMessage(),
                5000,
                500
            );
        }
    }
}
