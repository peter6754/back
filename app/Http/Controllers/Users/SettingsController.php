<?php

namespace App\Http\Controllers\Users;

use App\DTO\UsersSettingsDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\UserFilterSettingsRequest;
use App\Http\Traits\ApiResponseTrait;
use App\Models\UserCities;
use App\Models\UserDeviceToken;
use App\Services\UserService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Class SettingsController
 */
class SettingsController extends Controller
{
    /**
     * Include API response trait
     */
    use ApiResponseTrait;

    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * @OA\Delete(
     *      tags={"User Settings"},
     *      path="/users/settings/token",
     *      summary="Remove device token",
     *      security={{"bearerAuth":{}}},
     *
     *      @OA\RequestBody(
     *          required=true,
     *
     *          @OA\JsonContent(
     *              required={"token"},
     *
     *              @OA\Property(
     *                  example="20bdb8fb3bdadc1bef037eefcaeb56ad6e57f3241c99e734062b6ee829271b71",
     *                  description="Device Token",
     *                  property="token",
     *                  type="string"
     *              )
     *          )
     *      ),
     *
     *      @OA\Response(
     *
     *          @OA\JsonContent(ref="#/components/schemas/SuccessResponse"),
     *          description="Successful operation",
     *          response=201,
     *      )
     *  )
     *
     * @return JsonResponse
     */
    public function deleteToken(Request $request)
    {
        try {
            $query = UsersSettingsDto::tokenRequest($request);

            UserDeviceToken::removeToken($request->customer['id'], $query);

            return $this->successResponse([
                'message' => 'Request has ended successfully',
            ]);
        } catch (Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                (int) $e->getCode()
            );
        }
    }

    /**
     * @OA\Post(
     *      tags={"User Settings"},
     *      path="/users/settings/token",
     *      summary="Add / Register device token",
     *      security={{"bearerAuth":{}}},
     *
     *      @OA\RequestBody(
     *          required=true,
     *
     *          @OA\JsonContent(
     *              required={"token"},
     *
     *              @OA\Property(
     *                  example="20bdb8fb3bdadc1bef037eefcaeb56ad6e57f3241c99e734062b6ee829271b71",
     *                  description="Device Token",
     *                  property="token",
     *                  type="string"
     *              ),
     *              @OA\Property(
     *                  description="Application",
     *                  example="AppStore 1.2.3",
     *                  property="application",
     *                  type="string"
     *              ),
     *              @OA\Property(
     *                  description="Device Name",
     *                  example="iPhone 15",
     *                  property="device",
     *                  type="string"
     *              ),
     *          )
     *      ),
     *
     *      @OA\Response(
     *
     *          @OA\JsonContent(ref="#/components/schemas/SuccessResponse"),
     *          description="Successful operation",
     *          response=201,
     *      )
     *  )
     *
     * @return JsonResponse
     */
    public function addToken(Request $request)
    {
        try {
            $query = UsersSettingsDto::tokenRequest($request, [
                'application' => 'string|nullable',
                'device' => 'string|nullable',
            ]);

            UserDeviceToken::addToken($request->user()->id, $query);

            return $this->successResponse([
                'message' => 'Request has ended successfully',
            ]);
        } catch (Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                (int) $e->getCode()
            );
        }
    }

    /**
     * @OA\Get(
     *      tags={"User Settings"},
     *      path="/users/settings/filter",
     *      summary="Get recommendations filters",
     *      security={{"bearerAuth":{}}},
     *
     *      @OA\Response(
     *
     *          @OA\JsonContent(ref="#/components/schemas/SuccessResponse"),
     *          description="Successful operation",
     *          response=201,
     *      )
     *  )
     */
    public function getFilter(Request $request): JsonResponse
    {
        $user = $request->user()->toArray();
        try {
            return $this->successResponse(
                $this->userService->getFilterSettings($user['id'])
            );
        } catch (Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                (int) $e->getCode()
            );
        }
    }

    /**
     * @OA\Put(
     *      tags={"User Settings"},
     *      path="/users/settings/filter",
     *      summary="Update recommendations filters",
     *      security={{"bearerAuth":{}}},
     *
     *      @OA\RequestBody(
     *          required=true,
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(
     *                  description="Search Radius in km (1-1000)",
     *                  property="search_radius",
     *                  type="integer",
     *                  maximum=1000,
     *                  example=50,
     *                  minimum=1
     *              ),
     *              @OA\Property(
     *                  property="is_global_search",
     *                  description="Global Search",
     *                  type="boolean",
     *                  example=false
     *              ),
     *              @OA\Property(
     *                  pattern="^(?:1[89]|[2-9][0-9]|100)-(?:1[89]|[2-9][0-9]|100)$",
     *                  description="Age Range (e.g. 18-35, min 18, max 100)",
     *                  property="age_range",
     *                  example="18-35",
     *                  type="string"
     *              ),
     *              @OA\Property(
     *                  description="Gender preferences array",
     *                  property="show_me",
     *                  type="array",
     *
     *                  @OA\Items(
     *                      enum={"male", "female", "m_f", "m_m", "f_f"},
     *                      example="male",
     *                      type="string"
     *                  )
     *              ),
     *
     *              @OA\Property(
     *                  description="City filter",
     *                  property="cities",
     *                  type="array",
     *
     *                  @OA\Items(
     *                      example="Moscow",
     *                      type="string"
     *                  )
     *              )
     *          )
     *      ),
     *
     *      @OA\Response(
     *
     *          @OA\JsonContent(ref="#/components/schemas/SuccessResponse"),
     *          description="Successful operation",
     *          response=201,
     *      )
     *  )
     *
     * @throws \Throwable
     */
    public function setFilter(UserFilterSettingsRequest $request): JsonResponse
    {
        $user = $request->user()->toArray();

        try {
            return $this->successResponse(
                $this->userService->updateFilterSettings($user['id'], $request->validated())
            );
        } catch (Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                (int) $e->getCode()
            );
        }
    }

    /**
     * @OA\Get(
     *     path="/users/settings/cities",
     *     tags={"User Settings"},
     *     summary="Get cities for filter settings",
     *     description="Returns available cities for user filter settings with optional search functionality",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="q",
     *         in="query",
     *         description="Search query for filtering cities by name (searches cities that start with the query)",
     *         required=false,
     *
     *         @OA\Schema(type="string", example="Белго")
     *     ),
     *
     *     @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *
     *          @OA\JsonContent(
     *              ref="#/components/schemas/SuccessResponse",
     *              example={
     *                  "meta": {
     *                      "error": null,
     *                      "status": 200
     *                  },
     *                  "data": {
     *                      {
     *                          "formatted_address": "Белгород, Белгородская область, Россия"
     *                      },
     *                      {
     *                          "formatted_address": "Белая Церковь, Киевская область, Украина"
     *                      }
     *                  }
     *              }
     *          )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *
     *         @OA\JsonContent(ref="#/components/schemas/Unauthorized")
     *     ),
     *
     *     @OA\Response(
     *          response=500,
     *          description="Server error",
     *
     *          @OA\JsonContent(
     *              type="object",
     *
     *              @OA\Property(
     *                  property="message",
     *                  type="string",
     *                  example="Произошла ошибка при получении городов"
     *              ),
     *              @OA\Property(
     *                  property="errors",
     *                  type="string",
     *                  example="Database connection error"
     *              )
     *          )
     *      )
     * )
     */
    public function allCities(Request $request): JsonResponse
    {
        try {
            $query = UserCities::select('formatted_address')->distinct();

            $searchQuery = $request->query('q');
            if ($searchQuery) {
                $query->where('formatted_address', 'LIKE', $searchQuery.'%');
            }

            return $this->successResponse($query->get());
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Произошла ошибка при получении городов',
                (int) $e->getCode() ?: 500
            );
        }
    }
}
