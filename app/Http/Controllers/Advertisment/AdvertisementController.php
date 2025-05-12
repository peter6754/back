<?php

namespace App\Http\Controllers\Advertisment;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponseTrait;
use App\Services\AdvertisementService;
use Illuminate\Http\JsonResponse;

class AdvertisementController extends Controller
{
    /**
     * Include API response trait
     */
    use ApiResponseTrait;

    public function __construct(
        private AdvertisementService $advertisementService
    ) {}

    /**
     * @OA\Get(
     *     path="/advertisements/active",
     *     tags={"Advertisements"},
     *     summary="Get active advertisement",
     *     description="Returns active advertisement with images",
     *
     *     @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *
     *          @OA\JsonContent(
     *              example={
     *                  "meta": {
     *                      "error": null,
     *                      "status": 200
     *                  },
     *                  "data": {
     *                      "id": 1,
     *                      "title": "Акция 50% на всё!",
     *                      "link": "https://example.com/promo",
     *                      "images": {
     *                          {
     *                              "id": 1,
     *                              "fid": "7,068dd1dbb4",
     *                              "url": "http://seaweedfs.local/7,068dd1dbb4",
     *                              "original_name": "promo.jpg",
     *                              "is_primary": true,
     *                              "order": 1
     *                          }
     *                      },
     *                      "primary_image": {
     *                          "id": 1,
     *                          "fid": "7,068dd1dbb4",
     *                          "url": "http://seaweedfs.local/7,068dd1dbb4",
     *                          "original_name": "promo.jpg",
     *                          "is_primary": true,
     *                          "order": 1
     *                      },
     *                      "impressions": {
     *                          "current": 1523,
     *                          "limit": 10000,
     *                          "remaining": 8477
     *                      },
     *                      "period": {
     *                          "start": "2024-01-01T00:00:00+00:00",
     *                          "end": "2024-12-31T23:59:59+00:00"
     *                      }
     *                  }
     *              }
     *          )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="No active advertisement",
     *
     *         @OA\JsonContent(
     *              example={
     *                  "meta": {
     *                      "error": "Нет активной рекламы",
     *                      "status": 404
     *                  },
     *                  "data": null
     *              }
     *          )
     *     )
     * )
     */
    public function getActive(): JsonResponse
    {

        try {
            return $this->successResponse(
                $this->advertisementService->getActiveAdvertisementForApi()
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                (int) $e->getCode()
            );
        }
    }
}
