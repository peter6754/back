<?php

namespace App\Http\Controllers\Payments;

use Symfony\Component\HttpFoundation\Response;
use App\Services\Payments\PaymentsService;
use App\Http\Traits\ApiResponseTrait;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
     * @return JsonResponse
     * @throws Exception
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
     * @return JsonResponse
     * @throws Exception
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
     * @return JsonResponse
     * @throws Exception
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
     * @OA\Get(
     *     path="/payment/status/{id}",
     *     tags={"Payment"},
     *     summary="Get payment status by ID",
     *     operationId="getPaymentStatus",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *          name="id",
     *          in="path",
     *          required=true,
     *          description="Payment ID (UUID)",
     *          @OA\Schema(
     *              type="string",
     *              format="uuid",
     *              example="000558ed-d557-4fc0-99da-b23dec6be0bf"
     *          )
     *     ),
     *     @OA\Response(
     *         @OA\JsonContent(
     *             @OA\Property(
     *                  property="meta",
     *                  type="object",
     *                  @OA\Property(
     *                      property="error",
     *                      type="null",
     *                      example=null,
     *                      description="Error information (null if no error)"
     *                  ),
     *                  @OA\Property(
     *                      property="status",
     *                      type="integer",
     *                      example=200,
     *                      description="HTTP status code"
     *                  )
     *              ),
     *              @OA\Property(
     *                  property="data",
     *                  type="object",
     *                  description="Payment status data",
     *                  @OA\Property(
     *                      property="status",
     *                      type="string",
     *                      enum={"completed", "pending", "failed", "canceled"},
     *                      example="canceled",
     *                      description="Current payment status"
     *                  )
     *              )
     *         ),
     *         description="Successful operation",
     *         response=200,
     *     ),
     *
     *     @OA\Response(
     *         @OA\JsonContent(ref="#/components/schemas/Unauthorized"),
     *         description="Unauthorized",
     *         response=401
     *     )
     * )
     * @return JsonResponse
     * @throws Exception
     */
    public function status($id): JsonResponse
    {
        // Checking auth user
        $this->checkingAuth();

        // Get transaction
        if (!$transaction = Transactions::select('status')->find($id)) {
            return $this->errorResponse(
                Response::$statusTexts[Response::HTTP_NOT_FOUND],
                4042,
                Response::HTTP_NOT_FOUND
            );
        }

        // Return status
        return $this->successResponse([
            'status' => $transaction->status
        ]);
    }
}
