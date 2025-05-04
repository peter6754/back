<?php

namespace App\Http\Controllers\Payments;

use Symfony\Component\HttpFoundation\Response;
use App\Http\Traits\ApiResponseTrait;
use App\Http\Controllers\Controller;
use App\Services\RobokassaService;
use Illuminate\Http\JsonResponse;
use App\Models\Secondaryuser;
use App\Models\Transactions;
use Exception;

class PaymentsController extends Controller
{
    use ApiResponseTrait;

    /**
     * @var RobokassaService
     */
    protected RobokassaService $robokassa;

    /**
     * @var Transactions
     */
    protected Transactions $transactions;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        // Payments methods
        $this->robokassa = new RobokassaService();

        // Default models
        $this->transactions = new Transactions();
    }

    public function servicePackage()
    {
        // Checking auth user
        $this->checkingAuth();

    }

    public function subscription()
    {
        // Checking auth user
        $this->checkingAuth();

    }

    public function recurring()
    {
        // Checking auth user
        $this->checkingAuth();

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
                "Transaction not found",
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
