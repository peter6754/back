<?php

namespace App\Services;

use App\Models\Advertisement;
use App\Models\AdvertisementImage;
use DB;
use Illuminate\Http\UploadedFile;
use Log;

class AdvertisementService
{

    public function __construct(
        private ImageOptimizationService $imageOptimizationService,
        private SeaweedFsService $seaweedFsService
    ) {}

    /**
     * Создать рекламу с изображениями
     */
    public function createAdvertisement(array $data, array $photos = []): Advertisement
    {
        DB::beginTransaction();

        try {
            $advertisement = Advertisement::create([
                'title' => $data['title'],
                'link' => $data['link'] ?? null,
                'impressions_limit' => $data['impressions_limit'] ?? 0,
                'start_date' => $data['start_date'] ?? null,
                'end_date' => $data['end_date'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'order' => $data['order'] ?? 0,
            ]);

            if (!empty($photos)) {
                $this->attachPhotos($advertisement, $photos);
            }

            DB::commit();

            return $advertisement->load('images');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating advertisement', [
                'data' => $data,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Обновить рекламу
     */
    public function updateAdvertisement(Advertisement $advertisement, array $data, array $photos = []): Advertisement
    {
        DB::beginTransaction();

        try {
            $advertisement->update([
                'title' => $data['title'],
                'link' => $data['link'] ?? null,
                'impressions_limit' => $data['impressions_limit'] ?? 0,
                'start_date' => $data['start_date'] ?? null,
                'end_date' => $data['end_date'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'order' => $data['order'] ?? 0,
            ]);

            if (!empty($photos)) {
                $this->attachPhotos($advertisement, $photos);
            }

            DB::commit();

            return $advertisement->load('images');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating advertisement', [
                'advertisement_id' => $advertisement->id,
                'data' => $data,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Прикрепить фотографии к рекламе
     */
    public function attachPhotos(Advertisement $advertisement, array $photos): void
    {
        $currentMaxOrder = $advertisement->images()->max('order') ?? 0;
        $isFirstImage = $advertisement->images()->count() === 0;

        $photosFids = [];

        foreach ($photos as $index => $photo) {
            if ($photo instanceof UploadedFile) {
                try {
                    $fid = $this->imageOptimizationService->optimizeAndUploadPhoto($photo);

                    $photosFids[] = [
                        'advertisement_id' => $advertisement->id,
                        'fid' => $fid,
                        'original_name' => $photo->getClientOriginalName(),
                        'order' => $currentMaxOrder + $index + 1,
                        'is_primary' => $isFirstImage && $index === 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                } catch (\Exception $e) {
                    Log::error('Failed to upload advertisement image', [
                        'advertisement_id' => $advertisement->id,
                        'file_name' => $photo->getClientOriginalName(),
                        'error' => $e->getMessage(),
                    ]);
                    throw $e;
                }
            }
        }

        if (!empty($photosFids)) {
            AdvertisementImage::insert($photosFids);
        }
    }

    /**
     * Получить изображения рекламы с URL
     */
    public function getAdvertisementImagesWithUrls(Advertisement $advertisement): array
    {
        $images = [];

        $advertisementImages = $advertisement->images()
            ->orderBy('is_primary', 'desc')
            ->orderBy('order')
            ->get(['id', 'fid', 'original_name', 'is_primary', 'order']);

        foreach ($advertisementImages as $image) {
            try {
                $url = $this->seaweedFsService->createVolumeUrl($image->fid);
                $images[] = [
                    'id' => $image->id,
                    'fid' => $image->fid,
//                    'url' => $url,
                    'original_name' => $image->original_name,
                    'is_primary' => $image->is_primary,
                    'order' => $image->order,
                ];
            } catch (\Exception $e) {
                Log::warning('Failed to create URL for advertisement image', [
                    'advertisement_id' => $advertisement->id,
                    'image_id' => $image->id,
                    'fid' => $image->fid,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $images;
    }

    /**
     * Установить изображение как основное
     */
    public function setPrimaryImage(Advertisement $advertisement, string $fid): bool
    {
        $image = AdvertisementImage::where('advertisement_id', $advertisement->id)
            ->where('fid', $fid)
            ->first();

        if (!$image) {
            return false;
        }

        DB::beginTransaction();

        try {
            $image->setAsPrimary();
            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error setting primary image', [
                'advertisement_id' => $advertisement->id,
                'fid' => $fid,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Удалить изображение рекламы
     */
    public function deleteImage(AdvertisementImage $image): bool
    {
        DB::beginTransaction();

        try {
            $advertisementId = $image->advertisement_id;
            $wasPrimary = $image->is_primary;
            $fid = $image->fid;

            $image->delete();

            if ($wasPrimary) {
                $newPrimary = AdvertisementImage::where('advertisement_id', $advertisementId)
                    ->orderBy('order')
                    ->first();

                if ($newPrimary) {
                    $newPrimary->update(['is_primary' => true]);
                }
            }

            $this->seaweedFsService->deleteFromStorage($fid);

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting advertisement image', [
                'image_id' => $image->id,
                'advertisement_id' => $image->advertisement_id,
                'fid' => $image->fid,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Изменить порядок изображений
     */
    public function reorderImages(array $imageIds): void
    {
        DB::beginTransaction();

        try {
            foreach ($imageIds as $order => $imageId) {
                AdvertisementImage::where('id', $imageId)
                    ->update(['order' => $order + 1]);
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error reordering images', [
                'image_ids' => $imageIds,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Получить ВСЮ активную рекламу для показа
     */
    public function getAllActiveAdvertisements(): array
    {
        $advertisements = Advertisement::with('images')
            ->where('is_active', true)
            ->where(function($query) {
                $query->whereNull('start_date')
                    ->orWhere('start_date', '<=', now());
            })
            ->where(function($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            })
            ->where(function($query) {
                $query->where('impressions_limit', 0)
                    ->orWhereRaw('impressions_count < impressions_limit');
            })
            ->orderBy('order')
            ->get();

        return $advertisements->toArray();
    }

    /**
     * Получить активную рекламу для показа (для API) - ВСЯ реклама
     */
    public function getActiveAdvertisementForApi(): array
    {
        try {
            $advertisements = Advertisement::with('images')
                ->where('is_active', true)
                ->where(function($query) {
                    $query->whereNull('start_date')
                        ->orWhere('start_date', '<=', now());
                })
                ->where(function($query) {
                    $query->whereNull('end_date')
                        ->orWhere('end_date', '>=', now());
                })
                ->where(function($query) {
                    $query->where('impressions_limit', 0)
                        ->orWhereRaw('impressions_count < impressions_limit');
                })
                ->orderBy('order')
                ->get();

            if ($advertisements->isEmpty()) {
                return [
                    'success' => false,
                    'message' => 'Нет активной рекламы',
                    'status' => 404,
                    'data' => [],
                ];
            }

            $result = [];
            foreach ($advertisements as $advertisement) {
                $images = $this->getAdvertisementImagesWithUrls($advertisement);

                $result[] = [
                    'id' => $advertisement->id,
                    'title' => $advertisement->title,
                    'link' => $advertisement->link,
                    'images' => $images,
                    'primary_image' => !empty($images) ?
                        (array_filter($images, function($img) { return $img['is_primary'] ?? false; })[0] ?? $images[0]) : null,
                    'impressions' => [
                        'current' => $advertisement->impressions_count,
                        'limit' => $advertisement->impressions_limit,
                        'remaining' => $advertisement->impressions_limit > 0
                            ? max(0, $advertisement->impressions_limit - $advertisement->impressions_count)
                            : null,
                    ],
                    'period' => [
                        'start' => $advertisement->start_date?->toIso8601String(),
                        'end' => $advertisement->end_date?->toIso8601String(),
                    ],
                    'order' => $advertisement->order,
                    'is_active' => $advertisement->is_active,
                ];
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('Error getting active advertisement for API', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Ошибка при получении рекламы',
                'status' => 500,
                'data' => [],
            ];
        }
    }

}
