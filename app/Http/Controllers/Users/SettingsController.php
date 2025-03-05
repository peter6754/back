<?php

namespace App\Http\Controllers\Users;

use App\Http\Traits\ApiResponseTrait;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use App\Models\UserDeviceToken;
use App\Services\UserService;
use App\DTO\UsersSettingsDto;
use Illuminate\Http\Request;
use Exception;
use App\Http\Requests\UserFilterSettingsRequest;

/**
 * Class SettingsController
 * @package App\Http\Controllers\Users
 */
class SettingsController extends Controller
{
    /**
     * Include API response trait
     */
    use ApiResponseTrait;

    /**
     * @var UserService
     */
    private UserService $userService;

    /**
     * @param UserService $userService
     */
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * @param Request $request
     * @OA\Delete(
     *      tags={"User Settings"},
     *      path="/users/settings/token",
     *      summary="Remove device token",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"token"},
     *              @OA\Property(
     *                  example="20bdb8fb3bdadc1bef037eefcaeb56ad6e57f3241c99e734062b6ee829271b71",
     *                  description="Device Token",
     *                  property="token",
     *                  type="string"
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          @OA\JsonContent(ref="#/components/schemas/SuccessResponse"),
     *          description="Successful operation",
     *          response=201,
     *      )
     *  )
     * @return JsonResponse
     */
    public function deleteToken(Request $request)
    {
        try {
            $query = UsersSettingsDto::tokenRequest($request);

            UserDeviceToken::removeToken($request->customer['id'], $query);

            return $this->successResponse([
                'message' => 'Request has ended successfully'
            ]);
        } catch (Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                (int)$e->getCode()
            );
        }
    }

    /**
     * @param Request $request
     * @OA\Post(
     *      tags={"User Settings"},
     *      path="/users/settings/token",
     *      summary="Add / Register device token",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"token"},
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
     *      @OA\Response(
     *          @OA\JsonContent(ref="#/components/schemas/SuccessResponse"),
     *          description="Successful operation",
     *          response=201,
     *      )
     *  )
     * @return JsonResponse
     */
    public function addToken(Request $request)
    {
        try {
            $query = UsersSettingsDto::tokenRequest($request, [
                'application' => 'string|nullable',
                'device' => 'string|nullable'
            ]);

            UserDeviceToken::addToken($request->user()->id, $query);

            return $this->successResponse([
                'message' => 'Request has ended successfully'
            ]);
        } catch (Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                (int)$e->getCode()
            );
        }
    }

    /**
     * @param Request $request
     * @OA\Get(
     *      tags={"User Settings"},
     *      path="/users/settings/filter",
     *      summary="Get recommendations filters",
     *      security={{"bearerAuth":{}}},
     *      @OA\Response(
     *          @OA\JsonContent(ref="#/components/schemas/SuccessResponse"),
     *          description="Successful operation",
     *          response=201,
     *      )
     *  )
     * @return JsonResponse
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
                (int)$e->getCode()
            );
        }
    }

    /**
     * @param UserFilterSettingsRequest $request
     * @OA\Put(
     *      tags={"User Settings"},
     *      path="/users/settings/filter",
     *      summary="Update recommendations filters",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
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
     *                  @OA\Items(
     *                      enum={"male", "female", "m_f", "m_m", "f_f"},
     *                      example="male",
     *                      type="string"
     *                  )
     *              ),
     *              @OA\Property(
     *                  description="Cities filter array",
     *                  property="cities",
     *                  type="array",
     *                  @OA\Items(
     *                      example="Moscow",
     *                      type="string"
     *                  )
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          @OA\JsonContent(ref="#/components/schemas/SuccessResponse"),
     *          description="Successful operation",
     *          response=201,
     *      )
     *  )
     * @return JsonResponse
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
                (int)$e->getCode()
            );
        }
    }
}
