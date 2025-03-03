<?php

namespace App\Services;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class SeaweedFsService
{

    private string $seaweedFsUrl;
    private string $devVolumeUrl;
    private bool $isDevMode;

    public function __construct()
    {
        $this->seaweedFsUrl = config('services.seaweedfs.master_url');
        $this->devVolumeUrl = config('services.seaweedfs.dev_volume_url');
        $this->isDevMode = config('app.env') === 'local' || config('app.env') === 'development';
    }

    /**
     * Загружает файл в SeaweedFS и возвращает FID
     */
    public function uploadToStorage(string $fileContent, string $filename = null): string
    {
        // FID от мастера
        $masterUrl = "{$this->seaweedFsUrl}/dir/assign";
        $fidResponse = Http::get($masterUrl);

        if (!$fidResponse->successful()) {
            throw new Exception('SeaweedFS master server error: ' . $fidResponse->body());
        }

        $fidData = $fidResponse->json();

        if (isset($fidData['error'])) {
            throw new Exception("SeaweedFS getting fid error: {$fidData['error']}");
        }

        $fid = $fidData['fid'];

        // URL для загрузки
        if ($this->isDevMode) {
            $volumeUrl = "{$this->devVolumeUrl}/{$fid}";
        } else {
            $volumeUrl = "http://{$fidData['url']}/{$fid}";
        }

        // Загружаем файл
        $uploadResponse = Http::attach('file', $fileContent, $filename)
            ->post($volumeUrl);

        if (!$uploadResponse->successful()) {
            throw new Exception('SeaweedFS upload error: ' . $uploadResponse->body());
        }

        $uploadData = $uploadResponse->json();

        if (isset($uploadData['error'])) {
            throw new Exception("SeaweedFS uploading file error: {$uploadData['error']}");
        }

        return $fid;
    }

    /**
     * Создает URL для доступа к файлу по FID
     */
    public function createVolumeUrl(string $fid): string
    {
        $volumeId = explode(',', $fid)[0];
        $masterUrl = "{$this->seaweedFsUrl}/dir/lookup?volumeId={$volumeId}";

        $lookupResponse = Http::get($masterUrl);

        if (!$lookupResponse->successful()) {
            throw new Exception('SeaweedFS lookup error: ' . $lookupResponse->body());
        }

        $lookupData = $lookupResponse->json();

        if (isset($lookupData['error'])) {
            throw new Exception("SeaweedFS lookup volumeId error: " . json_encode($lookupData));
        }

        if ($this->isDevMode) {
            return "{$this->devVolumeUrl}/{$fid}";
        }

        $volumeUrl = $lookupData['locations'][0]['url'];
        return "http://{$volumeUrl}/{$fid}";
    }

    /**
     * Скачивает файл из SeaweedFS
     */
    public function downloadFromStorage(string $fid): string
    {
        $fileUrl = $this->createVolumeUrl($fid);
        $response = Http::get($fileUrl);

        if (!$response->successful()) {
            throw new Exception("SeaweedFS download error for fid {$fid}: " . $response->body());
        }

        return $response->body();
    }

    /**
     * Удаляет файл из SeaweedFS
     */
    public function deleteFromStorage(string $fid): void
    {
        $fileUrl = $this->createVolumeUrl($fid);
        $deleteResponse = Http::delete($fileUrl);

        if (!$deleteResponse->successful()) {
            throw new Exception("SeaweedFS delete error for fid {$fid}: " . $deleteResponse->body());
        }

        $deleteData = $deleteResponse->json();

        if (isset($deleteData['error'])) {
            throw new Exception("SeaweedFS delete file error: " . json_encode($deleteData));
        }
    }

    /**
     * Обновляет файл в SeaweedFS
     */
    public function updateInStorage(string $fid, string $newFileContent): void
    {
        $fileUrl = $this->createVolumeUrl($fid);

        $uploadResponse = Http::attach('file', $newFileContent, 'updated_file')
            ->post($fileUrl);

        if (!$uploadResponse->successful()) {
            throw new Exception("SeaweedFS update error for fid {$fid}: " . $uploadResponse->body());
        }

        $uploadData = $uploadResponse->json();

        if (isset($uploadData['error'])) {
            throw new Exception("SeaweedFS update file {$fid} error: {$uploadData['error']}");
        }
    }

}
