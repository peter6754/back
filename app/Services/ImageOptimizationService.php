<?php

namespace App\Services;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Http\UploadedFile;
use Exception;


class ImageOptimizationService
{

    private SeaweedFsService $seaweedFsService;

    public function __construct(SeaweedFsService $seaweedFsService)
    {
        $this->seaweedFsService = $seaweedFsService;
    }

    /**
     * Оптимизирует и загружает фото в SeaweedFS.
     *
     * @param UploadedFile $photo
     * @return string
     * @throws Exception
     */
    public function optimizeAndUploadPhoto(UploadedFile $photo): string
    {
        $isGif = $photo->getClientOriginalExtension() === 'gif';

        if ($isGif) {
            $photoContent = file_get_contents($photo->getRealPath());
        } else {
            $photoContent = $this->optimizeImage($photo);
        }

        return $this->seaweedFsService->uploadToStorage(
            $photoContent,
            $photo->getClientOriginalName()
        );
    }

    /**
     * Оптимизирует изображение из UploadedFile.
     *
     * @param UploadedFile $photo
     * @param int $width
     * @param int $quality
     * @return string
     * @throws \Intervention\Image\Exceptions\DriverException
     */
    public function optimizeImage(UploadedFile $photo, int $width = 1440, int $quality = 60): string
    {
        $manager = new ImageManager(new Driver());
        $image = $manager->read($photo->getRealPath());

        if ($image->width() > $width) {
            $image->scaleDown($width);
        }
        return (string) $image->toJpeg($quality);
    }

    /**
     * Оптимизирует изображение из файла по пути.
     *
     * @param string $imagePath
     * @param int $width
     * @param int $quality
     * @return string
     * @throws \Intervention\Image\Exceptions\DriverException
     */
    public function optimizeImageFromPath(string $imagePath, int $width = 1440, int $quality = 60): string
    {
        $manager = new ImageManager(new Driver());
        $image = $manager->read($imagePath);

        if ($image->width() > $width) {
            $image->scaleDown($width);
        }

        return (string) $image->toJpeg($quality);
    }

    /**
     * Оптимизирует изображение на месте (перезаписывает файл).
     *
     * @param string $imagePath
     * @param int $width
     * @param int $quality
     * @return void
     */
    public function optimizeImageInPlace(string $imagePath, int $width = 1440, int $quality = 60): void
    {
        $optimizedContent = $this->optimizeImageFromPath($imagePath, $width, $quality);
        file_put_contents($imagePath, $optimizedContent);
    }

    /**
     * Оптимизирует изображение в новый файл.
     *
     * @param string $srcPath
     * @param string $dstPath
     * @param int $width
     * @param int $quality
     * @return void
     */
    public function optimizeImageToFile(string $srcPath, string $dstPath, int $width = 1440, int $quality = 60): void
    {
        $optimizedContent = $this->optimizeImageFromPath($srcPath, $width, $quality);
        file_put_contents($dstPath, $optimizedContent);
    }

    /**
     * Обновляет существующее изображение в SeaweedFS с оптимизацией.
     *
     * @param string $fid
     * @return void
     * @throws Exception
     */
    public function updateInStorage(string $fid): void
    {
        try {

            $fileContent = $this->seaweedFsService->downloadFromStorage($fid);

            $tempPath = tempnam(sys_get_temp_dir(), 'seaweedfs_update_');
            file_put_contents($tempPath, $fileContent);

            $optimizedContent = $this->optimizeImageFromPath($tempPath);

            $this->seaweedFsService->updateInStorage($fid, $optimizedContent);

            unlink($tempPath);

        } catch (Exception $e) {
            if (isset($tempPath) && file_exists($tempPath)) {
                unlink($tempPath);
            }
            throw $e;
        }
    }
}
