<?php

namespace App\Http\Controllers\Users;

use App\Http\Traits\ApiResponseTrait;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use App\Models\UserDeviceToken;
use App\DTO\UsersSettingsDto;
use Illuminate\Http\Request;
use Exception;

class SettingsController extends Controller
{
    use ApiResponseTrait;

    /**
     * @var UserDeviceToken
     */
    private UserDeviceToken $deviceTokens;

    /**
     * @param UserDeviceToken $deviceTokens
     */
    public function __construct(UserDeviceToken $deviceTokens)
    {
        $this->deviceTokens = $deviceTokens;
    }

    /**
     * @param Request $request
     * @OA\Delete(
     *      tags={"User settings"},
     *      path="/users/settings/token",
     *      summary="Remove device token",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *                required={"token"},
     *                @OA\Property(
     *                   example="20bdb8fb3bdadc1bef037eefcaeb56ad6e57f3241c99e734062b6ee829271b71",
     *                   description="Device Token",
     *                   property="token",
     *                   type="string"
     *                )
     *          )
     *      ),
     *      @OA\Response(
     *          @OA\JsonContent(ref="#/components/schemas/SuccessResponse"),
     *          description="Successful operation",
     *          response=201,
     *      )
     * )
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
     *      tags={"User settings"},
     *      path="/users/settings/token",
     *      summary="Add / Register device token",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *                required={"token"},
     *                @OA\Property(
     *                   example="20bdb8fb3bdadc1bef037eefcaeb56ad6e57f3241c99e734062b6ee829271b71",
     *                   description="Device Token",
     *                   property="token",
     *                   type="string"
     *                ),
     *                @OA\Property(
     *                   description="Application",
     *                   example="AppStore 1.2.3",
     *                   property="application",
     *                   type="string"
     *               ),
     *               @OA\Property(
     *                  description="Device Name",
     *                  example="iPhone 15",
     *                  property="device",
     *                  type="string"
     *               ),
     *          )
     *      ),
     *      @OA\Response(
     *          @OA\JsonContent(ref="#/components/schemas/SuccessResponse"),
     *          description="Successful operation",
     *          response=201,
     *      )
     * )
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
}
