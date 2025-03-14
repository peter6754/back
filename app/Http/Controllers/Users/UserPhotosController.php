<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserPhotosRequest;
use App\Http\Requests\SetMainPhotoRequest;
use App\Http\Traits\ApiResponseTrait;
use App\Models\UserImage;
use App\Services\UserPhotoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class UserPhotosController extends Controller
{
    use ApiResponseTrait;
    private UserPhotoService $userPhotoService;

    public function __construct(UserPhotoService $userPhotoService)
    {
        $this->userPhotoService = $userPhotoService;
    }

    /**
     * @OA\Post(
     *     path="/users/photos",
     *     tags={"User Photos"},
     *     summary="Add user photos",
     *     description="Upload one or multiple photos for the authenticated user",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"photo"},
     *                 @OA\Property(
     *                     property="photo",
     *                     description="Photo file(s) to upload",
     *                     type="array",
     *                     @OA\Items(
     *                         type="string",
     *                         format="binary"
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Photos uploaded successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="message", type="string", example="Фотографии успешно добавлены")
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(property="error", type="null"),
     *                 @OA\Property(property="status", type="integer", example=200)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Photo limit exceeded",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Превышено максимальное количество фотографий"),
     *             @OA\Property(property="code", type="integer", example=4030)
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Внутренняя ошибка сервера"),
     *             @OA\Property(property="code", type="integer", example=500)
     *         )
     *     )
     * )
     */
    public function addPhotos(UserPhotosRequest $request): JsonResponse
    {
        try {
            /** @var \App\Models\Secondaryuser $user */
            $user = $request->user();
            $photos = $request->file('photo');

            \Log::info('Полученные файлы', [
                'type' => gettype($photos),
                'count' => is_array($photos) ? count($photos) : 1,
                'is_instance_of_uploaded_file' => $photos instanceof \Illuminate\Http\UploadedFile,
            ]);

            if ($photos instanceof \Illuminate\Http\UploadedFile) {
                $photos = [$photos];
            }

            $this->userPhotoService->addPhotos($photos, $user);

            return $this->successResponse(['message' => 'Фотографии успешно добавлены']);

        } catch (Exception $e) {
            Log::error('Error in UserPhotosController.addPhotos()', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if ($e instanceof \App\Exceptions\PhotoLimitExceededException) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'code' => 4030
                ], 403);
            }

            return $this->errorResponse(
                $e->getMessage(),
                (int)$e->getCode()
            );
        }
    }

    /**
     * @OA\Get(
     *     path="/users/photos",
     *     tags={"User Photos"},
     *     summary="Get user photos",
     *     description="Get all photos of the authenticated user",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="photos",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=63351),
     *                         @OA\Property(property="image", type="string", example="11,f3dc14b810ca"),
     *                         @OA\Property(property="url", type="string", example="https://storage.example.com/11,f3dc14b810ca"),
     *                         @OA\Property(property="is_main", type="boolean", example=true)
     *                     )
     *                 ),
     *                 @OA\Property(property="total", type="integer", example=5)
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(property="error", type="null"),
     *                 @OA\Property(property="status", type="integer", example=200)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Внутренняя ошибка сервера"),
     *             @OA\Property(property="code", type="integer", example=500)
     *         )
     *     )
     * )
     */
    public function getPhotos(Request $request): JsonResponse
    {
        try {
            /** @var \App\Models\Secondaryuser $user */

            $user = $request->user();

            $photos = $this->userPhotoService->getUserPhotosWithUrls($user);

            return $this->successResponse([
                'photos' => $photos,
                'total' => count($photos)
            ], 200);

        } catch (Exception $e) {
            Log::error('Error in UserPhotosController.getPhotos()', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return $this->errorResponse(
                $e->getMessage(),
                (int)$e->getCode()
            );
        }
    }

    /**
     * @OA\Get(
     *     path="/users/photos/main",
     *     tags={"User Photos"},
     *     summary="Get main photo",
     *     description="Get the main photo of the authenticated user",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=63351),
     *                 @OA\Property(property="image", type="string", example="11,f3dc14b810ca"),
     *                 @OA\Property(property="url", type="string", example="https://storage.example.com/11,f3dc14b810ca"),
     *                 @OA\Property(property="is_main", type="boolean", example=true)
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(property="error", type="null"),
     *                 @OA\Property(property="status", type="integer", example=200)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Main photo not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Главное фото не найдено"),
     *             @OA\Property(property="code", type="integer", example=404)
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Внутренняя ошибка сервера"),
     *             @OA\Property(property="code", type="integer", example=500)
     *         )
     *     )
     * )
     */
    public function getMainPhoto(Request $request): JsonResponse
    {
        try {
            /** @var \App\Models\Secondaryuser $user */
            $user = $request->user();

            $mainPhoto = $this->userPhotoService->getMainPhoto($user);

            if (!$mainPhoto) {
                return $this->errorResponse('Главное фото не найдено', 404);
            }

            return $this->successResponse($mainPhoto);

        } catch (Exception $e) {
            Log::error('Error in UserPhotosController.getMainPhoto()', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return $this->errorResponse(
                $e->getMessage(),
                (int)$e->getCode()
            );
        }
    }

    /**
     * @OA\Patch(
     *     path="/users/photos/main",
     *     tags={"User Photos"},
     *     summary="Set main photo",
     *     description="Set a photo as the main photo for the authenticated user",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"fid"},
     *             @OA\Property(property="fid", type="string", example="11,f3dc14b810ca")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Main photo updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="message", type="string", example="Главное фото успешно обновлено")
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(property="error", type="null"),
     *                 @OA\Property(property="status", type="integer", example=200)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Photo not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Фото не найдено"),
     *             @OA\Property(property="code", type="integer", example=404)
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Внутренняя ошибка сервера"),
     *             @OA\Property(property="code", type="integer", example=500)
     *         )
     *     )
     * )
     */
    public function setMainPhoto(SetMainPhotoRequest $request): JsonResponse
    {
        try {
            /** @var \App\Models\Secondaryuser $user */
            $user = $request->user();

            $fid = $request->validated()['fid'];

            $userImage = UserImage::where('user_id', $user->id)
                ->where('image', $fid)
                ->first();

            if (!$userImage) {
                return $this->errorResponse('Фото не найдено', 404);
            }

            $success = $this->userPhotoService->setMainPhoto($user, $fid);

            if (!$success) {
                return $this->errorResponse('Фото не найдено', 404);
            }

            return $this->successResponse(['message' => 'Главное фото успешно обновлено']);

        } catch (Exception $e) {
            Log::error('Error in UserPhotosController.setMainPhoto()', [
                'user_id' => auth()->id(),
                'fid' => $request->input('fid'),
                'error' => $e->getMessage()
            ]);

            return $this->errorResponse(
                $e->getMessage(),
                (int)$e->getCode()
            );
        }
    }

    /**
     * @OA\Delete(
     *     path="/users/photos",
     *     tags={"User Photos"},
     *     summary="Delete photo",
     *     description="Delete a photo of the authenticated user",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"fid"},
     *             @OA\Property(property="fid", type="string", example="11,f3dc14b810ca")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Photo deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="message", type="string", example="Фото успешно удалено")
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(property="error", type="null"),
     *                 @OA\Property(property="status", type="integer", example=200)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Photo not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Фото не найдено"),
     *             @OA\Property(property="code", type="integer", example=404)
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Внутренняя ошибка сервера"),
     *             @OA\Property(property="code", type="integer", example=500)
     *         )
     *     )
     * )
     */
    public function deletePhoto(Request $request): JsonResponse
    {
        $request->validate([
            'fid' => 'required|string'
        ]);

        try {
            /** @var \App\Models\Secondaryuser $user */
            $user = $request->user();
            $fid = $request->input('fid');

            $deleted = $this->userPhotoService->deleteUserPhoto($user, $fid);

            if (!$deleted) {
                return $this->errorResponse('Фото не найдено', 404);
            }

            return $this->successResponse(['message' => 'Фото успешно удалено']);

        } catch (Exception $e) {
            Log::error('Error in UserPhotosController.deletePhoto()', [
                'user_id' => auth()->id(),
                'fid' => $request->input('fid'),
                'error' => $e->getMessage()
            ]);

            return $this->errorResponse(
                $e->getMessage(),
                (int)$e->getCode()
            );
        }
    }
}
