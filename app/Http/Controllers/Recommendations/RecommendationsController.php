<?php

namespace App\Http\Controllers\Recommendations;

use Exception;
use App\DTO\RecommendationsDto;
use Illuminate\Support\Facades\Log;
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
     * @param  Request  $request
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
            return $this->successResponse(
                $this->recommendations->getTopProfiles($request->user())
            );
        } catch (Exception $e) {
            return $this->errorResponse(
                $e->getMessage()
            );
        }
    }

    /**
     * @param  Request  $request
     * @OA\Delete(
     *      path="/recommendations/match/{id}",
     *      tags={"Recommendations"},
     *      security={{"bearerAuth":{}}},
     *
     *       @OA\Parameter(
     *           name="id",
     *           in="path",
     *           required=true,
     *           description="User ID (UUID)",
     *           @OA\Schema(
     *               example="000558ed-d557-4fc0-99da-b23dec6be0bf",
     *               format="uuid",
     *               type="string"
     *           )
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
     */
    public function match(Request $request): JsonResponse
    {
        try {
            // Get queries
            $query = RecommendationsDto::forActions($request);

            // Return data
            return $this->successResponse(
                $this->recommendations->deleteMatchedUser($request->user()->id, $query['user_id'])
            );
        } catch (Exception $e) {
            Log::channel('recommendations')->error(basename(__FILE__, ".php").' > '.__FUNCTION__.' error:', [
                'user_id' => $request->user()->id ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            return $this->errorResponse(
                "Internal error"
            );
        }
    }

    /**
     * @param  Request  $request
     * @OA\Post(
     *      path="/recommendations/dislike",
     *      tags={"Recommendations"},
     *      security={{"bearerAuth":{}}},
     *
     *       @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  example="13c8c521-c797-4de4-abba-6f2ce0e24c40",
     *                  description="User ID",
     *                  property="user_id",
     *                  format="uuid",
     *                  type="string"
     *              )
     *           )
     *       ),
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
    public function dislike(Request $request): JsonResponse
    {
        try {
            // Get queries
            $query = RecommendationsDto::forActions($request);

            // Return data
            return $this->successResponse(
                $this->recommendations->dislike($request->user()->id, $query)
            );
        } catch (Exception $e) {
            Log::channel('recommendations')->error(basename(__FILE__, ".php").' > '.__FUNCTION__.' error:', [
                'user_id' => $request->user()->id ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            return $this->errorResponse(
                "Internal error"
            );
        }
    }

    /**
     * @param  Request  $request
     * @OA\Post(
     *      path="/recommendations/like",
     *      tags={"Recommendations"},
     *      summary="Get recommendations profiles",
     *      security={{"bearerAuth":{}}},
     *
     *       @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              @OA\Property(
     *                   example="13c8c521-c797-4de4-abba-6f2ce0e24c40",
     *                   description="User ID (UUID)",
     *                   property="user_id",
     *                   format="uuid",
     *                   type="string"
     *               ),
     *               @OA\Property(
     *                   description="From top",
     *                   property="from_top",
     *                   type="boolean",
     *                   example="false"
     *               )
     *           )
     *       ),
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
    public function like(Request $request): JsonResponse
    {
        try {
            // Get queries
            $query = RecommendationsDto::forActions($request, [
                'from_top' => 'required|boolean'
            ]);

            // Return data
            return $this->successResponse(
                $this->recommendations->like($request->user()->id, $query)
            );
        } catch (Exception $e) {
            Log::channel('recommendations')->error(basename(__FILE__, ".php").' > '.__FUNCTION__.' error:', [
                'user_id' => $request->user()->id ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            return $this->errorResponse(
                "Internal error"
            );
        }
    }

    /**
     * @param  Request  $request
     * @OA\Get(
     *       path="/recommendations",
     *       tags={"Recommendations"},
     *       summary="Get recommendations profiles",
     *       security={{"bearerAuth":{}}},
     *
     *       @OA\Parameter(
     *          name="interest_id",
     *          in="query",
     *          description="Filter by interest ID",
     *          required=false,
     *          @OA\Schema(type="string", example="123")
     *       ),
     *
     *       @OA\Parameter(
     *          name="min_photo_count",
     *          in="query",
     *          description="Minimum number of photos required",
     *          required=false,
     *          @OA\Schema(type="string", example="3")
     *       ),
     *
     *       @OA\Parameter(
     *          name="is_verified",
     *          in="query",
     *          description="Filter verified profiles (true/false)",
     *          required=false,
     *          @OA\Schema(type="string", example="true")
     *       ),
     *
     *       @OA\Parameter(
     *          name="has_info",
     *          in="query",
     *          description="Filter profiles with complete info",
     *          required=false,
     *          @OA\Schema(type="string", example="false")
     *       ),
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
        try {
            // Get queries
            $query = RecommendationsDto::forRecommendations($request);

            $getResponse = $this->recommendations->getRecommendations($request->user()->id, $query);

            // Return string error
            if (!empty($getResponse['message'])) {
                if ($request->get('debug') == '1') {

                    $errorCode = !empty($getResponse['code']) ? (9000 + $getResponse['code']) : 9404;
                    $httpCode = $getResponse['code'] ?? 404;

                    return $this->errorResponse(
                        $getResponse['message'],
                        $errorCode,
                        $httpCode
                    );
                }
                return $this->successResponse([
                    'items' => []
                ]);
            }

            // Return data
            return $this->successResponse(
                $getResponse
            );
        } catch (Exception $e) {
            Log::channel('recommendations')->error(basename(__FILE__, ".php").' > '.__FUNCTION__.' error:', [
                'user_id' => $request->user()->id ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            return $this->errorResponse(
                "Internal error"
            );
        }
    }

    /**
     * @param  Request  $request
     * @OA\Post(
     *      path="/recommendations/superlike",
     *      tags={"Recommendations"},
     *      security={{"bearerAuth":{}}},
     *
     *       @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *               @OA\Property(
     *                   example="13c8c521-c797-4de4-abba-6f2ce0e24c40",
     *                   description="User ID (UUID)",
     *                   property="user_id",
     *                   format="uuid",
     *                   type="string"
     *                ),
     *                @OA\Property(
     *                    description="From top",
     *                    property="from_top",
     *                    type="boolean",
     *                    example="false"
     *                ),
     *                @OA\Property(
     *                    description="Comment",
     *                    property="comment",
     *                    type="string",
     *                    example=""
     *                )
     *            )
     *       ),
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
    public function superlike(Request $request): JsonResponse
    {
        try {
            // Get queries
            $query = RecommendationsDto::forActions($request, [
                'from_top' => 'boolean',
                'comment' => 'string',
            ]);

            // Return data
            return $this->successResponse(
                $this->recommendations->superlike($request->user()->id, $query)
            );
        } catch (Exception $e) {
            Log::channel('recommendations')->error(basename(__FILE__, ".php").' > '.__FUNCTION__.' error:', [
                'user_id' => $request->user()->id ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            return $this->errorResponse(
                "Internal error"
            );
        }
    }

    /**
     * @param  Request  $request
     * @OA\Post(
     *      path="/recommendations/rollback",
     *      tags={"Recommendations"},
     *      security={{"bearerAuth":{}}},
     *
     *       @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  example="13c8c521-c797-4de4-abba-6f2ce0e24c40",
     *                  description="User ID (UUID)",
     *                  property="user_id",
     *                  format="uuid",
     *                  type="string"
     *              )
     *           )
     *       ),
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
    public function rollback(Request $request): JsonResponse
    {
        try {
            // Get queries
            $query = RecommendationsDto::forActions($request);

            // Return data
            return $this->successResponse(
                $this->recommendations->rollback($request->user()->id, $query)
            );
        } catch (Exception $e) {
            Log::channel('recommendations')->error(basename(__FILE__, ".php").' > '.__FUNCTION__.' error:', [
                'user_id' => $request->user()->id ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            return $this->errorResponse(
                "Internal error"
            );
        }
    }
}
