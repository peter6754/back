<?php

namespace App\Http\Controllers\Application;

use Exception;
use Illuminate\Http\Request;
use App\Models\ServicePackages;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponseTrait;

class PricesController extends Controller
{
    use ApiResponseTrait;

    /**
     * @param Request $request
     * @OA\Get(
     *     path="/application/prices/service-package",
     *     tags={"App Settings"},
     *     summary="Price for service packages",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *           name="type",
     *           in="query",
     *           description="Type",
     *           required=true,
     *          @OA\Schema(
     *              type="string",
     *              enum={"superboom","superlike"},
     *              example="superboom"
     *          )
     *     ),
     *
     *     @OA\Response(
     *          response=201,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              ref="#/components/schemas/SuccessResponse",
     *              example={
     *                  "meta": {
     *                      "error": null,
     *                      "status": 200
     *                  },
     *                  "data": {
     *                      "items": {
     *                          {
     *                              "id": 1,
     *                              "count": 3,
     *                              "stock": 0,
     *                              "is_bestseller": false,
     *                              "price_per_one": "280"
     *                          }
     *                      }
     *                  }
     *              }
     *          )
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
    public function getPackages(Request $request): JsonResponse
    {
        try {
            $gender = $request->customer['gender'];
            $type = $request->input('type');

            $packages = ServicePackages::where('type', $type)
                ->with(['price'])
                ->select(['id', 'count', 'stock', 'is_bestseller'])
                ->get()
                ->map(function ($package) use ($gender) {
                    return [
                        'id' => $package->id,
                        'count' => $package->count,
                        'stock' => $package->stock,
                        'is_bestseller' => $package->is_bestseller,
                        'price_per_one' => (string)intval($package->price->{$gender} ?? 0)
                    ];
                });

            return $this->successResponse([
                'items' => $packages
            ]);
        } catch (Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                $e->getCode()
            );
        }
    }
}
