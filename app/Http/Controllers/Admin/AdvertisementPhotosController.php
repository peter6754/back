<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Advertisement;
use App\Services\AdvertisementService;
use Illuminate\Http\Request;

class AdvertisementPhotosController extends Controller
{

    protected $advertisementService;

    public function __construct(AdvertisementService $advertisementService)
    {
        $this->advertisementService = $advertisementService;
    }

    public function index($advertisementId)
    {
        $advertisement = Advertisement::findOrFail($advertisementId);
        $photos = $this->advertisementService->getAdvertisementImagesWithUrls($advertisement);

        foreach ($photos as &$photo) {
            $photo['url'] = url("admin/image-proxy/{$photo['fid']}");
        }

        return view('admin.advertisement-photos', compact('advertisement', 'photos'));
    }

    /**
     * Добавить фотографии к рекламе
     */
    public function store(Request $request, $advertisementId)
    {
        $advertisement = Advertisement::findOrFail($advertisementId);

        $request->validate([
            'photo' => 'required',
            'photo.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:10240'
        ]);

        try {
            $photos = $request->file('photo');
            if (!is_array($photos)) {
                $photos = [$photos];
            }

            $this->advertisementService->attachPhotos($advertisement, $photos);

            return redirect()->back()->with('success', 'Фотографии успешно добавлены');

        } catch (\Exception $e) {
            \Log::error('Error adding advertisement photos', [
                'advertisement_id' => $advertisementId,
                'error' => $e->getMessage()
            ]);
            return redirect()->back()->with('error', 'Ошибка при загрузке фотографий');
        }
    }

    /**
     * Установить фото как основное
     */
    public function setPrimary(Request $request, $advertisementId)
    {
        $advertisement = Advertisement::findOrFail($advertisementId);

        $request->validate([
            'fid' => 'required|string'
        ]);

        try {
            $success = $this->advertisementService->setPrimaryImage($advertisement, $request->input('fid'));

            if ($success) {
                return redirect()->back()->with('success', 'Основное фото установлено');
            } else {
                return redirect()->back()->with('error', 'Фото не найдено');
            }

        } catch (\Exception $e) {
            \Log::error('Error setting primary photo', [
                'advertisement_id' => $advertisementId,
                'error' => $e->getMessage()
            ]);
            return redirect()->back()->with('error', 'Ошибка при установке основного фото');
        }
    }

    /**
     * Удалить фото рекламы
     */
    public function destroy(Request $request, $advertisementId)
    {
        $advertisement = Advertisement::findOrFail($advertisementId);

        $request->validate([
            'fid' => 'required|string'
        ]);

        try {
            $image = $advertisement->images()->where('fid', $request->input('fid'))->first();

            if (!$image) {
                return redirect()->back()->with('error', 'Фото не найдено');
            }

            $deleted = $this->advertisementService->deleteImage($image);

            if ($deleted) {
                return redirect()->back()->with('success', 'Фото удалено');
            } else {
                return redirect()->back()->with('error', 'Ошибка при удалении фото');
            }

        } catch (\Exception $e) {
            \Log::error('Error deleting advertisement photo', [
                'advertisement_id' => $advertisementId,
                'error' => $e->getMessage()
            ]);
            return redirect()->back()->with('error', 'Ошибка при удалении фото');
        }
    }
}
