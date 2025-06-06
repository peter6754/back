<?php

namespace App\Http\Controllers\Recommendations;

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
     * @throws \Exception
     */
    public function getTopProfiles(): JsonResponse
    {
        // Checking auth user
        $customer = $this->checkingAuth();

        // Checking  cache
        $cache = 'top-profiles:' . $customer['id'];
        $getData = Cache::get($cache);

        // Cache not exist? run request!!!
        if (is_null($getData)) {
            $getData = RecommendationService::getPotentialMatchesOptimized($customer)->toArray();
            foreach ($getData as &$row) {
                $row->blocked_me = (bool)$row->blocked_me;
            }

            Cache::set($cache, $getData, 15 * 60);
        }

        return $this->successResponse(["items" => $getData]);
    }
}
