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

    public function servicePackage()
    {
        // Checking auth user
        $this->checkingAuth();

    }


    public function unsubscription(Request $request, string $provider = "robokassa")
    {
        // Checking auth user
        $customer = $this->checkingAuth();

        // Logic
        try {
            $from_banner = !empty($request->input("from_banner"));
            $package_id = $request->input("package_id") ?? 2;

            $getTransaction = $this->payments->buySubscription($provider, [
                "from_banner" => $from_banner,
                "package_id" => $package_id,
                "customer" => $customer
            ]);

            return $this->successResponse($getTransaction, Response::HTTP_CREATED);
        } catch (Exception $exception) {

        }
    }

    public function subscription(Request $request, string $provider = "robokassa")
    {
        // Checking auth user
        $customer = $this->checkingAuth();

        // Logic
        try {
            $from_banner = !empty($request->input("from_banner"));
            $package_id = $request->input("package_id") ?? 2;

            $getTransaction = $this->payments->buySubscription($provider, [
                "from_banner" => $from_banner,
                "package_id" => $package_id,
                "customer" => $customer
            ]);

            return $this->successResponse($getTransaction, Response::HTTP_CREATED);
        } catch (Exception $exception) {

        }
    }

    public function gift()
    {
        // Checking auth user
        $this->checkingAuth();

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
