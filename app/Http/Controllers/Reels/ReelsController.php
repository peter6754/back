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
     * Add new reel - upload video
     *
     * @OA\Post(
     *     path="/reels",
     *     tags={"Reels"},
     *     summary="Upload new reel video",
     *     description="Upload video file to SeaweedFS and create temporary reel record",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="video",
     *                     type="string",
     *                     format="binary",
     *                     description="Video file (mp4, mov, avi, wmv, flv, mkv)"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Video uploaded successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="string", example="uuid"),
     *             @OA\Property(property="path", type="string", example="fid123"),
     *             @OA\Property(property="message", type="string", example="Video uploaded successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Upload failed"
     *     )
     * )
     */
    public function addReel(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'video' => [
                    'required',
                    'file',
                    'mimes:mp4,mov,avi,wmv,flv,mkv',
                    'max:102400',
                ],
            ], [
                'video.required' => 'Video file is required',
                'video.file' => 'The uploaded file must be a valid file',
                'video.mimes' => 'Video must be one of the following formats: mp4, mov, avi, wmv, flv, mkv',
                'video.max' => 'Video file size must not exceed 100MB',
            ]);

            $userId = $request->user()->id;
            $video = $request->file('video');

            $result = $this->reelsService->addReel($video, $userId);

            return $this->successResponse($result);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
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
