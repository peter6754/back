<?php

namespace App\Http\Controllers\Recommendations;

use Exception;
use App\Services\RecommendationService;
use Illuminate\Support\Facades\Cache;
use App\Http\Traits\ApiResponseTrait;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecommendationsController extends Controller
{
    use ApiResponseTrait;

    /**
     * @var RecommendationService
     */
    private RecommendationService $recommendations;

    /**
     *
     */
    public function __construct()
    {
        $this->recommendations = new RecommendationService();
    }

    /**
     * @param Request $request
     * @OA\Get(
     *      path="/recommendations/top-profiles",
     *      tags={"Recommendations"},
     *      summary="Get top profiles",
     *      security={{"bearerAuth":{}}},
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
     */
    public function getTopProfiles(Request $request): JsonResponse
    {
        try {
            // Checking  cache
            $cache = 'top-profiles:' . $request->customer['id'];
            $getData = Cache::get($cache);

            // Cache not exist? run request!!!
            if (is_null($getData)) {
                $getData = $this->recommendations->getTopProfiles($request->customer)->toArray();
                foreach ($getData as &$row) {
                    $row->blocked_me = (bool)$row->blocked_me;
                }

                Cache::set($cache, $getData, 15 * 60);
            }

            return $this->successResponse(["items" => $getData]);
        } catch (Exception $e) {
            return $this->errorResponse(
                $e->getMessage()
            );
        }
    }


    /**
     * @param Request $request
     * @OA\Get(
     *       path="/recommendations",
     *       tags={"Recommendations"},
     *       summary="Get profiles",
     *       security={{"bearerAuth":{}}},
     *
     *       @OA\Response(
     *           @OA\JsonContent(ref="#/components/schemas/SuccessResponse"),
     *           description="Successful operation",
     *           response=201,
     *       ),
     *
     *       @OA\Response(
     *           @OA\JsonContent(ref="#/components/schemas/Unauthorized"),
     *           description="Unauthorized",
     *           response=401
     *       )
     *   )
     * @return JsonResponse
     */
    public function getRecommendations(Request $request): JsonResponse
    {
//        try {
            return $this->successResponse(
                $this->recommendations->getRecommendations($request->customer['id'], [])
            );
//        } catch (Exception $e) {
//            return $this->errorResponse(
//                $e->getMessage()
//            );
//        }
    }
}
