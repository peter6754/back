<?php

namespace App\Http\Controllers\Payments;

use Symfony\Component\HttpFoundation\Response;
use App\Http\Traits\ApiResponseTrait;
use App\Http\Controllers\Controller;
use App\Services\RobokassaService;
use App\Models\Transactions;

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
     * @return \Illuminate\Http\JsonResponse
     */
    public function status($id)
    {
        try {
            if (!$transaction = Transactions::select('status')->find($id)) {
                throw new \Exception('Transaction not found', 4042);
            }

            return $this->successResponse([
                'status' => $transaction->status
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                $e->getCode(),
                Response::HTTP_NOT_FOUND
            );
        }
    }
}
