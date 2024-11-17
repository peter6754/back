<?php

namespace App\Http\Controllers\Payments;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\Payments\PaymentsService;
use App\Http\Traits\ApiResponseTrait;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use App\Models\Secondaryuser;
use App\Models\Transactions;
use Exception;

class PaymentsController extends Controller
{
    use ApiResponseTrait;

    /**
     * @var Transactions
     */
    protected Transactions $transactions;

    /**
     * @throws Exception
     */
    public function __construct(private PaymentsService $payments)
    {
        // Default models
        $this->transactions = new Transactions();
    }

    /**
     * @param Request $request
     * @param string $provider
     * @return JsonResponse
     * @throws Exception
     * @OA\Post(
     *     path="/payment/service-package",
     *     tags={"Payment"},
     *     summary="Create service package payment",
     *     operationId="createServicePackagePayment",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"service_package_id"},
     *             @OA\Property(
     *                 property="service_package_id",
     *                 type="integer",
     *                 example=4,
     *                 description="ID of the package"
     *             ),
     *         )
     *     ),
     *
     *     @OA\Response(
     *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse"),
     *         description="Successful operation",
     *         response=201,
     *     ),
     *
     *     @OA\Response(
     *         @OA\JsonContent(ref="#/components/schemas/Unauthorized"),
     *         description="Unauthorized",
     *         response=401
     *     )
     * )
     */
    public function servicePackage(Request $request, string $provider = "robokassa")
    {
        // Checking auth user
        $customer = $this->checkingAuth();

        // Logic
        try {
            $package_id = $request->input("service_package_id") ?? 99;

            $getTransaction = $this->payments->buyServicePackage($provider, [
                "package_id" => $package_id,
                "customer" => $customer
            ]);

            return $this->successResponse($getTransaction, Response::HTTP_CREATED);
        } catch (Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                $e->getCode()
            );
        }
    }

    /**
     * @param Request $request
     * @param string $provider
     * @return JsonResponse
     * @throws Exception
     * @OA\Post(
     *     path="/payment/subscription",
     *     tags={"Payment"},
     *     summary="Create subscription payment",
     *     operationId="createSubscriptionPayment",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"package_id"},
     *             @OA\Property(
     *                 property="package_id",
     *                 type="integer",
     *                 example=4,
     *                 description="ID of the subscription package"
     *             ),
     *         )
     *     ),
     *
     *     @OA\Response(
     *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse"),
     *         description="Successful operation",
     *         response=201,
     *     ),
     *
     *     @OA\Response(
     *         @OA\JsonContent(ref="#/components/schemas/Unauthorized"),
     *         description="Unauthorized",
     *         response=401
     *     )
     * )
     */
    public function subscription(Request $request, string $provider = "robokassa")
    {
        // Checking auth user
        $customer = $this->checkingAuth();

        // Logic
        try {
            $from_banner = !empty($request->input("from_banner"));
            $package_id = $request->input("package_id") ?? 99;

            $getTransaction = $this->payments->buySubscription($provider, [
                "from_banner" => $from_banner,
                "package_id" => $package_id,
                "customer" => $customer
            ]);

            return $this->successResponse($getTransaction, Response::HTTP_CREATED);
        } catch (Exception $e) {
            return $this->errorResponse(
                $e->getMessage()
            );
        }
    }

    /**
     * @param Request $request
     * @param string $provider
     * @return JsonResponse
     * @throws Exception
     * @OA\Post(
     *      path="/payment/gift",
     *      tags={"Payment"},
     *      summary="Create gift payment",
     *      operationId="createGiftPayment",
     *      security={{"bearerAuth":{}}},
     *
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"user_id","gift_id"},
     *               @OA\Property(
     *                   property="user_id",
     *                   type="string",
     *                   example="0b18a55c-e44e-4f72-9829-6d143878ce35",
     *                   description="User id"
     *               ),
     *               @OA\Property(
     *                   property="gift_id",
     *                   type="integer",
     *                   example=4,
     *                   description="Gift id"
     *               )
     *          )
     *      ),
     *
     *      @OA\Response(
     *          @OA\JsonContent(ref="#/components/schemas/SuccessResponse"),
     *          description="Successful operation",
     *          response=201,
     *      ),
     *
     *      @OA\Response(
     *          @OA\JsonContent(ref="#/components/schemas/Unauthorized"),
     *          description="Unauthorized",
     *          response=401
     *      )
     *  )
     * /
     */
    public function gift(Request $request, string $provider = "robokassa")
    {
        // Checking auth user
        $customer = $this->checkingAuth();

        // Logic
        try {
            $user_jd = $request->input("user_id") ?? 99;
            $gift_id = $request->input("gift_id") ?? 99;

            $getTransaction = $this->payments->buyGift($provider, [
                "customer" => $customer,
                "user_id" => $user_jd,
                "gift_id" => $gift_id
            ]);

            return $this->successResponse($getTransaction, Response::HTTP_CREATED);
        } catch (Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                $e->getCode()
            );
        }
    }

    /**
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function status($id): JsonResponse
    {
        // Checking auth user
        $this->checkingAuth();

//        $status = $this->payments->driver("robokassa")->checkOrderStatus($id);
//        print_r($status);
//        exit();

        // Get transaction
        if (!$transaction = Transactions::select('status')->find($id)) {
            return $this->errorResponse(
                Response::$statusTexts[Response::HTTP_UNAUTHORIZED],
                4042,
                Response::HTTP_NOT_FOUND
            );
        }

        // Return status
        return $this->successResponse([
            'status' => $transaction->status
        ]);
    }

    /**
     * @return array|JsonResponse
     * @throws Exception

     * @OA\Schema(
     *     schema="Unauthorized",
     *     title="Error Unauthorized Structure",
     *     description="Standard Unauthorized response format",
     *     @OA\Property(
     *         property="meta",
     *         type="object",
     *         @OA\Property(
     *             property="error",
     *             type="object",
     *             @OA\Property(
     *                 property="code",
     *                 type="integer",
     *                 example=4010,
     *                 description="Application-specific error code"
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Unauthorized",
     *                 description="Human-readable error message"
     *             )
     *         ),
     *         @OA\Property(
     *             property="status",
     *             type="integer",
     *             example=401,
     *             description="HTTP status code"
     *         )
     *     ),
     *     @OA\Property(
     *         property="data",
     *         type="null",
     *         example=null,
     *         description="Empty data payload for error responses"
     *     )
     * )
     */
    private function checkingAuth(): JsonResponse|array
    {
        $getUser = Secondaryuser::getUser();
        if (empty($getUser)) {
            $this->errorResponse(
                Response::$statusTexts[Response::HTTP_UNAUTHORIZED],
                4010,
                Response::HTTP_UNAUTHORIZED
            )->send();
            die();
        }
        return $getUser;
    }
}
