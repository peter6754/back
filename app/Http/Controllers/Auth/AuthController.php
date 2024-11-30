<?php

namespace App\Http\Controllers\Auth;

use App\Services\JwtService;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\Response;
use GuzzleHttp\Exception\GuzzleException;
use App\Http\Traits\ApiResponseTrait;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Exception;
use Laravel\Socialite\Two\InvalidStateException;

class AuthController extends Controller
{
    use ApiResponseTrait;

    /**
     * @var AuthService
     */
    protected AuthService $authService;

    /**
     * @param AuthService $authService
     */
    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * @param Request $request
     * @OA\Post(
     *      path="/auth/login",
     *      tags={"Auth"},
     *      summary="Request user authorization",
     *      @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"phone"},
     *               @OA\Property(
     *                   property="device_token",
     *                   type="string",
     *                   example="20bdb8fb3bdadc1bef037eefcaeb56ad6e57f3241c99e734062b6ee829271b71",
     *                   description="Device Token"
     *               ),
     *              @OA\Property(
     *                  property="phone",
     *                  type="string",
     *                  example="+79851234567",
     *                  description="Phone Number"
     *              ),
     *          )
     *      ),
     *      @OA\Response(
     *          @OA\JsonContent(ref="#/components/schemas/SuccessResponse"),
     *          description="Successful operation",
     *          response=201,
     *      )
     * )
     * @return JsonResponse
     * @throws GuzzleException
     */
    public function login(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'device_token' => 'required',
                'phone' => 'required'
            ]);

            return $this->successResponse(
                $this->authService->login($validatedData),
                Response::HTTP_CREATED
            );
        } catch (Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                $e->getCode()
            );
        }
    }

    /**
     * @OA\Post(
     *     path="/auth/verify-login",
     *     tags={"Auth"},
     *     summary="Verify user by code (Don't forget to add the token from the login)",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"code"},
     *               @OA\Property(
     *                  property="code",
     *                  type="string",
     *                  example="7878"
     *              )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
     *     )
     * )
     */
    public function verify(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'code' => 'required|string|digits:4'
        ]);

        try {
            $tokenPayload = app(JwtService::class)->decode(request()->bearerToken() ?? "");
            $response = $this->authService->verifyLogin($validatedData, $tokenPayload);

            return $this->successResponse($response,
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                $e->getCode()
            );
        }
    }

    /**
     * @param Request $request
     * @return RedirectResponse|void
     */
    public function socialCallback(Request $request)
    {
        $provider = $request->route('provider');

//        try {
        try {
            $profile = Socialite::driver($provider)->user();
        } catch (InvalidStateException $e) {
            $profile = Socialite::driver($provider)->stateless()->user();
        }

        $data = $this->authService->loginBySocial(
            $provider,
            $profile
        );

        if (!empty($data)) {
            return redirect()->away('tinderone://oauth/' . implode("/", [
                    $data['type'],
                    $data['token']
                ])
            );
        }

        abort(403, 'Invalid account data');
//        } catch (Exception $e) {
//            Log::error("Social authentication failed: " . $e->getMessage(), [
//                'error' => $e->getMessage(),
//                'file' => $e->getFile(),
//                'line' => $e->getLine(),
//                'trace' => $e->getTraceAsString()
//            ]);
//            abort(401, 'Invalid account data');
//        }
    }
}
