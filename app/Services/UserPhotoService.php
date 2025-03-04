<?php

namespace App\Services;

use App\Models\Secondaryuser;
use App\Models\UserImage;
use App\Exceptions\PhotoLimitExceededException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class UserPhotoService
{

    private ImageOptimizationService $imageOptimizationService;
    public function __construct(
        ImageOptimizationService $imageOptimizationService
    ) {
        $this->imageOptimizationService = $imageOptimizationService;
    }

    /**
     * Добавляет фотографии пользователя
     *
     * @param array $photos массив UploadedFile объектов
     * @param Secondaryuser $user
     * @throws PhotoLimitExceededException
     * @throws Exception
     */
    public function addPhotos(array $photos, Secondaryuser $user): void
    {
        DB::beginTransaction();

        try {

            $existingPhotosCount = $user->getImagesCount();

            if ($existingPhotosCount + count($photos) > 9) {
                throw new PhotoLimitExceededException('Out of count');
            }

            $photosFids = [];
            $isFirstPhoto = $existingPhotosCount === 0; // Первое фото автоматически главное

            // Проверяем, нужно ли устанавливать дату регистрации
            $shouldSetRegistrationDate = empty($user->registration_date);

            foreach ($photos as $index => $photo) {
                $fid = $this->imageOptimizationService->optimizeAndUploadPhoto($photo);
                $photosFids[] = [
                    'image' => $fid,
                    'user_id' => $user->id,
                    'is_main' => $isFirstPhoto && $index === 0, // Первое фото первой загрузки = главное
                ];
            }

            UserImage::insert($photosFids);

            // Устанавливаем дату регистрации, если она еще не установлена
            if ($shouldSetRegistrationDate) {
                $user->registration_date = now();
                $user->save();
            }

            DB::commit();

        } catch (PhotoLimitExceededException $e) {
            DB::rollBack();
            throw $e;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error in UserPhotoService.addPhotos()', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Получает все фотографии пользователя с URL
     * @param Secondaryuser $user
     * @return array
     */
    public function getUserPhotosWithUrls(Secondaryuser $user): array
    {
        $seaweedFsService = app(SeaweedFsService::class);
        $photos = [];

        $userImages = UserImage::where('user_id', $user->id)
            ->orderBy('is_main', 'desc')
            ->get(['id', 'image', 'is_main']);

        foreach ($userImages as $userImage) {
            try {
                $url = $seaweedFsService->createVolumeUrl($userImage->image);
                $photos[] = [
                    'id' => $userImage->id,
                    'fid' => $userImage->image,
                    'url' => $url,
                    'is_main' => $userImage->is_main,
                ];
            } catch (Exception $e) {
                Log::warning('Failed to create URL for image', [
                    'user_id' => $user->id,
                    'image_id' => $userImage->id,
                    'fid' => $userImage->image,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $photos;
    }

    /**
     * Получает все фотографии пользователя
     * @param Secondaryuser $user
     * @return array
     */
    public function getUserPhotos(Secondaryuser $user): array
    {
        return UserImage::where('user_id', $user->id)
            ->orderBy('id')
            ->pluck('image')
            ->toArray();
    }

    /**
     * Устанавливает фотографию как главную
     * @param Secondaryuser $user
     * @param string $fid
     * @return bool
     * @throws \Throwable
     */
    public function setMainPhoto(Secondaryuser $user, string $fid): bool
    {
        $userImage = UserImage::where('user_id', $user->id)
            ->where('image', $fid)
            ->first();

        if (!$userImage) {
            return false;
        }

        DB::beginTransaction();

        try {
            $userImage->setAsMain();
            DB::commit();
            return true;

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error setting main photo', [
                'user_id' => $user->id,
                'fid' => $fid,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Получает главное фото пользователя
     * @param Secondaryuser $user
     * @return array|null
     */
    public function getMainPhoto(Secondaryuser $user): ?array
    {
        $mainImage = $user->mainImage();

        if (!$mainImage) {
            return null;
        }

        try {
            $seaweedFsService = app(SeaweedFsService::class);
            $url = $seaweedFsService->createVolumeUrl($mainImage->image);

            return [
                'id' => $mainImage->id,
                'fid' => $mainImage->image,
                'url' => $url,
                'is_main' => true,
            ];
        } catch (Exception $e) {
            Log::warning('Failed to create URL for main image', [
                'user_id' => $user->id,
                'image_id' => $mainImage->id,
                'fid' => $mainImage->image,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Удаляет фотографию пользователя
     * @param Secondaryuser $user
     * @param string $fid
     * @return bool
     * @throws \Throwable
     */
    public function deleteUserPhoto(Secondaryuser $user, string $fid): bool
    {
        $userImage = UserImage::where('user_id', $user->id)
            ->where('image', $fid)
            ->first();

        if (!$userImage) {
            return false;
        }

        DB::beginTransaction();

        try {
            $wasMain = $userImage->is_main;

            $userImage->delete();

            // Если удаляли главное фото, делаем главным самое старое
            if ($wasMain) {
                $nextMainImage = UserImage::where('user_id', $user->id)
                    ->orderBy('id')
                    ->first();

                if ($nextMainImage) {
                    $nextMainImage->update(['is_main' => true]);
                }
            }

            // Удаляем из SeaweedFS через сервис
            $seaweedFsService = app(SeaweedFsService::class);
            $seaweedFsService->deleteFromStorage($fid);

            DB::commit();
            return true;

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error deleting user photo', [
                'user_id' => $user->id,
                'fid' => $fid,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

}
