<?php

namespace App\Http\Controllers\Users;

use App\DTO\UsersSettingsDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\UserFilterSettingsRequest;
use App\Http\Traits\ApiResponseTrait;
use App\Models\UserDeviceToken;
use App\Services\CitiesService;
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
    private CitiesService $citiesService;

    public function __construct(UserService $userService, CitiesService $citiesService)
    {
        $this->userService = $userService;
        $this->citiesService = $citiesService;
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
     *                  property="city",
     *                  example="Moscow",
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
            $searchQuery = $request->query('q');
            $cities = $this->citiesService->getCities($searchQuery);

            return $this->successResponse($cities);
        } catch (Exception $e) {
            return $this->errorResponse(
                'Произошла ошибка при получении городов',
                (int) $e->getCode() ?: 500
            );
        }
    }

    /**
     * @param Request $request
     * @OA\Get(
     *     tags={"User Settings"},
     *     path="/users/settings/connected-accounts",
     *     summary="Get connected accounts",
     *     description="Get user's connected social accounts with settings",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="google", type="boolean", nullable=true),
     *             @OA\Property(property="facebook", type="boolean", nullable=true),
     *             @OA\Property(property="apple", type="boolean", nullable=true),
     *             @OA\Property(property="vk", type="boolean", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     * @return JsonResponse
     * @throws Exception
     */
    public function getConnectedAccounts(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $connectedAccounts = $this->userService->getConnectedAccounts($user->id);

            return $this->successResponse($connectedAccounts);
        } catch (Exception $e) {
            return $this->errorResponse(
                'Error get connected accounts',
                (int) $e->getCode() ?: 500
            );
        }
    }

    /**
     * @param Request $request
     * @OA\Get(
     *     tags={"User Settings"},
     *     path="/users/settings/blocked-contacts",
     *     summary="Get blocked contacts",
     *     description="Get user's blocked contacts with optional phone number search",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="query",
     *         in="query",
     *         description="Search query for phone number",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="user_id", type="string", example="user-123"),
     *                 @OA\Property(property="phone", type="string", example="+1234567890"),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="date", type="string", format="date", example="2024-01-15")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Failed to retrieve blocked contacts"),
     *             @OA\Property(property="message", type="string", example="Error message details")
     *         )
     *     )
     * )
     * @return JsonResponse
     * @throws Exception
     */
    public function getBlockedContacts(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $query = $request->input('query');

            $blockedContacts = $this->userService->getBlockedContacts($user->id, $query);

            return $this->successResponse($blockedContacts);

        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error get blocked contacts',
                (int) $e->getCode() ?: 500
            );
        }
    }

    /**
     * @param Request $request
     * @OA\Post(
     *     tags={"User Settings"},
     *     path="/users/settings/blocked-contacts",
     *     summary="Create blocked contact",
     *     description="Add a new phone number to blocked contacts",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"phone", "name"},
     *             @OA\Property(property="phone", type="string", example="+1234567890"),
     *             @OA\Property(property="name", type="string", example="John Doe")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successfully created",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Data added successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=406,
     *         description="Already exists",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Blocked contact already exists"),
     *             @OA\Property(property="code", type="integer", example=4060)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error"
     *     )
     * )
     * @return JsonResponse
     * @throws \Throwable
     */
    public function createBlockedContact(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'phone' => 'required|string|max:20',
                'name' => 'required|string|max:255'
            ]);

            $user = $request->user();

            $result = $this->userService->createBlockedContact($user->id, $validated);

            return $this->successResponse($result);

        } catch (Exception $e) {

            return $this->errorResponse(
                $e->getMessage(),
                (int)$e->getCode() ?: 500
            );
        }
    }

    /**
     * @param Request $request
     * @param string $phone
     * @OA\Delete(
     *     tags={"User Settings"},
     *     path="/users/settings/blocked-contacts/{phone}",
     *     summary="Delete blocked contact",
     *     description="Delete a blocked contact by phone number",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="phone",
     *         in="path",
     *         description="Phone number to delete",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successfully deleted",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Data deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Data doesn't exist"),
     *             @OA\Property(property="code", type="integer", example=404)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error"
     *     )
     * )
     * @return JsonResponse
     * @throws Exception
     */
    public function deleteBlockedContact(Request $request, string $phone): JsonResponse
    {
        try {
            $user = $request->user();

            $phone = urldecode($phone);

            $result = $this->userService->deleteBlockedContact($phone, $user->id);

            return $this->successResponse($result);

        } catch (Exception $e) {

            return $this->errorResponse(
                $e->getMessage(),
                (int)$e->getCode() ?: 500
            );
        }
    }

}
