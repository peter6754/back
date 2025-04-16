<?php

namespace App\Http\Controllers\Users;

use App\Http\Traits\ApiResponseTrait;
use App\Http\Controllers\Controller;
use App\Models\TransactionProcess;
use Illuminate\Http\JsonResponse;
use App\Services\UserService;
use Illuminate\Http\Request;
use Exception;

/**
 * Class TransactionsController
 */
class TransactionsController extends Controller
{
    /**
     * Include API response trait
     */
    use ApiResponseTrait;

    /**
     * @var UserService
     */
    private UserService $userService;

    /**
     * @param  UserService  $userService
     */
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * @param  Request  $request
     * @param  TransactionProcess  $transactions
     * @OA\Get(
     *      path="/users/transactions",
     *      tags={"User Transactions"},
     *      summary="Show user transactions",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *          name="page",
     *          in="query",
     *          description="Get page",
     *          @OA\Schema(type="integer", example=1, default=1)
     *      ),
     *      @OA\Response(
     *          @OA\JsonContent(ref="#/components/schemas/Unauthorized"),
     *          description="Unauthorized",
     *          response=401
     *      )
     *  )
     * @return JsonResponse
     * @throws \Throwable
     */
    public function getTransactionsSubscriptions(Request $request, TransactionProcess $transactions): JsonResponse
    {
        $viewer = $request->user()->toArray();
        try {
            return $this->successResponse(
                $transactions->getUserTransactions($viewer, $request->get('page', 1))
            );
        } catch (Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                (int) $e->getCode()
            );
        }
    }
}
