<?php

namespace App\Http\Controllers\Reels;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponseTrait;
use App\Services\ReelsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReelsController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private ReelsService $reelsService)
    {
    }

    /**
     * Get reels feed for user
     *
     * @OA\Get(
     *     path="/reels",
     *     tags={"Reels"},
     *     summary="Get reels feed",
     *     description="Get personalized reels feed based on user preferences, location, and filters",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="query",
     *         description="Shared reel ID (optional)",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="string"),
     *                 @OA\Property(property="path", type="string"),
     *                 @OA\Property(property="share_count", type="integer"),
     *                 @OA\Property(property="liked_by_me", type="boolean"),
     *                 @OA\Property(property="preview_screenshot", type="string"),
     *                 @OA\Property(property="comments_count", type="integer"),
     *                 @OA\Property(property="user", type="object"),
     *                 @OA\Property(property="likes", type="array", @OA\Items()),
     *                 @OA\Property(property="comments", type="array", @OA\Items())
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found"
     *     )
     * )
     */
    public function getReels(Request $request): JsonResponse
    {
        try {
            $userId = $request->user()->id;
            $sharedId = $request->query('id', '');

            $result = $this->reelsService->getReels($userId, $sharedId);

            return $this->successResponse($result);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 404);
        }
    }

    /**
     * Get user's own reels
     *
     * @OA\Get(
     *     path="/reels/own",
     *     tags={"Reels"},
     *     summary="Get user's own reels",
     *     description="Get all reels created by the authenticated user",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="query",
     *         description="Selected reel ID (optional) - will be shown first",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="string"),
     *                 @OA\Property(property="path", type="string"),
     *                 @OA\Property(property="share_count", type="integer"),
     *                 @OA\Property(property="liked_by_me", type="boolean"),
     *                 @OA\Property(property="preview_screenshot", type="string"),
     *                 @OA\Property(property="comments_count", type="integer"),
     *                 @OA\Property(property="likes", type="array", @OA\Items()),
     *                 @OA\Property(property="comments", type="array", @OA\Items())
     *             )
     *         )
     *     )
     * )
     */
    public function getUserReels(Request $request): JsonResponse
    {
        try {
            $userId = $request->user()->id;
            $selectedId = $request->query('id', '');

            $result = $this->reelsService->getUserReels($userId, $selectedId);

            return $this->successResponse($result);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get comments for a reel
     *
     * @OA\Get(
     *     path="/reels/{id}/comments",
     *     tags={"Reels"},
     *     summary="Get reel comments",
     *     description="Get up to 5 comments for a specific reel",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Reel ID",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="items",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(
     *                         property="user",
     *                         type="object",
     *                         @OA\Property(property="id", type="string"),
     *                         @OA\Property(property="image", type="string", nullable=true),
     *                         @OA\Property(property="name", type="string")
     *                     ),
     *                     @OA\Property(property="comment", type="string")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Reel not found"
     *     )
     * )
     */
    public function getComments(string $id): JsonResponse
    {
        try {
            $result = $this->reelsService->getComments($id);

            return $this->successResponse($result);
        } catch (\Exception $e) {
            return $this->errorResponse('Item not found', 404);
        }
    }
}
