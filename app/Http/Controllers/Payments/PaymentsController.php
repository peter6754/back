<?php

namespace App\Http\Controllers\Payments;

use Exception;
use App\Models\User;
use App\Models\Secondaryuser;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Traits\ApiResponseTrait;
use App\Http\Controllers\Controller;
use App\Services\RobokassaService;
use App\Models\Transactions;
use App\Services\JwtService;

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
     *
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

    }

    public function subscription()
    {

    }

    public function recurring()
    {

    }

    public function gift()
    {

    }

    /**
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function status($id): JsonResponse
    {
        if (!Secondaryuser::getUser()) {
            return $this->errorResponse(
                Response::$statusTexts[Response::HTTP_UNAUTHORIZED],
                4010,
                Response::HTTP_UNAUTHORIZED
            );
        }

        if (!$transaction = Transactions::select('status')->find($id)) {
            return $this->errorResponse(
                "Transaction not found",
                4042,
                Response::HTTP_NOT_FOUND
            );
        }

        return $this->successResponse([
            'status' => $transaction->status
        ]);
    }
}
