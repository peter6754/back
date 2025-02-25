<?php

namespace App\Http\Controllers\Auth;

use Laravel\Socialite\Two\InvalidStateException;
use Symfony\Component\HttpFoundation\Response;
use GuzzleHttp\Exception\GuzzleException;
use Laravel\Socialite\Facades\Socialite;
use App\Http\Traits\ApiResponseTrait;
use Illuminate\Http\RedirectResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use App\Services\AuthService;
use App\Services\JwtService;
use Illuminate\Http\Request;
use Detection\MobileDetect;
use Exception;

class AuthController extends Controller
{
    use ApiResponseTrait;

    /**
     * @var array|array[]
     */
    private array $socialProviders = [
//        [
//            'icon' => 'fab fa-telegram',
//            'provider' => 'telegram',
//            'title' => 'Telegram'
//        ],
        [
            'icon' => 'fab fa-google',
            'provider' => 'google',
            'title' => 'Google'
        ],
        [
            'icon' => 'fab fa-apple',
            'provider' => 'apple',
            'title' => 'Apple'
        ]
    ];

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
     *      tags={"Customer Authorization"},
     *      path="/auth/login",
     *      summary="Request user authorization",
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"phone"},
     *              @OA\Property(
     *                  example="20bdb8fb3bdadc1bef037eefcaeb56ad6e57f3241c99e734062b6ee829271b71",
     *                  description="Device Token",
     *                  property="device_token",
     *                  type="string"
     *              ),
     *              @OA\Property(
     *                  description="Phone Number",
     *                  example="+37491563504",
     *                  property="phone",
     *                  type="string"
     *              )
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
    public function login(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'device_token' => 'string|nullable',
                'phone' => 'string|required'
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
     *  @param Request $request
     *  @OA\Post(
     *      tags={"Customer Authorization"},
     *      path="/auth/verify-login",
     *      summary="Verify user by code (Don't forget to add the token from the login)",
     *      @OA\Parameter(
     *          name="Login-Token",
     *          in="header",
     *          description="Login token",
     *          required=true,
     *          @OA\Schema(
     *              example="LOGIN_JWT_TOKEN",
     *              type="string",
     *          )
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"code"},
     *              @OA\Property(
     *                  property="code",
     *                  type="string",
     *                  example="7878"
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
     *      )
     *  )
     *  @return JsonResponse
     */
    public function verify(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'code' => 'required|string|digits:4'
        ]);

        try {
            // Checking auth token
            if (!$tokenPayload = app(JwtService::class)->decode(
                request()->header("Login-Token") ??
                request()->bearerToken() ?? ""
            )) {
                return $this->errorUnauthorized();
            }

            return $this->successResponse(
                $this->authService->verifyLogin($validatedData, $tokenPayload),
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
     * @return JsonResponse
     * @throws \Throwable
     */
    public function telegram(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'initData' => 'required|string',
                'appId' => 'string',
                'step' => 'string'
            ]);

            return $this->successResponse(
                $this->authService->telegram($validatedData),
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
     * @return JsonResponse
     */
    public function socialLinks(): JsonResponse
    {
        // Add dynamic links
        foreach ($this->socialProviders as &$val) {
            $val['link'] = url('/auth/social/' . $val['provider']);
        }

        // Response
        return $this->successResponse(
            $this->socialProviders
        );
    }

    /**
     * @param Request $request
     * @return RedirectResponse|void
     */
    public function socialCallback(Request $request)
    {
        $provider = $request->route('provider');
        $browser = new MobileDetect();

        try {
            try {
                $profile = Socialite::driver($provider)->user();
            } catch (InvalidStateException $e) {
                $profile = Socialite::driver($provider)->stateless()->user();
            }

            $data = $this->authService->loginBySocial(
                $provider,
                $profile
            );
            Log::debug("Social authentication debug:", $data);

            // Set redirect url
            $redirectUrl = 'https://web.tinderone.app/';
            if ($browser->isMobile()) {
                $redirectUrl = 'tinderone://';
            }

            return redirect()->away($redirectUrl . "oauth/" . implode("/", [
                    $data['type'],
                    $data['token']
                ])
            );
        } catch (Exception $e) {
            Log::error("Social authentication failed: " . $e->getMessage(), [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            abort(401, 'Invalid account data');
        }
    }
}
