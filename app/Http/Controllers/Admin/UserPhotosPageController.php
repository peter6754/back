<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Secondaryuser;
use Illuminate\Http\Request;

class UserPhotosPageController extends Controller
{

    protected $userPhotoService;

    public function __construct(\App\Services\UserPhotoService $userPhotoService)
    {
        $this->userPhotoService = $userPhotoService;
    }

    public function index($userId)
    {

        $user = Secondaryuser::findOrFail($userId);
        $photos = $this->userPhotoService->getUserPhotosWithUrls($user);

        // Заменяем URL на проксированные для админки
        foreach ($photos as &$photo) {
            $photo['url'] = url("admin/image-proxy/{$photo['fid']}");
        }

        return view('admin.user-photos', compact('user', 'photos'));

    }

    public function store(Request $request, $userId)
    {
        $user = Secondaryuser::findOrFail($userId);

        $request->validate([
            'photo' => 'required',
            'photo.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        try {
            $photos = $request->file('photo');
            if (!is_array($photos)) {
                $photos = [$photos];
            }

            $this->userPhotoService->addPhotos($photos, $user);

            return redirect()->back()->with('success', 'Фотографии успешно добавлены');

        } catch (\App\Exceptions\PhotoLimitExceededException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            \Log::error('Error adding photos', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Ошибка при загрузке фотографий');
        }
    }

    public function setMain(Request $request, $userId)
    {
        $user = Secondaryuser::findOrFail($userId);

        $request->validate([
            'fid' => 'required|string'
        ]);

        try {
            $success = $this->userPhotoService->setMainPhoto($user, $request->input('fid'));

            if ($success) {
                return redirect()->back()->with('success', 'Главное фото установлено');
            } else {
                return redirect()->back()->with('error', 'Фото не найдено');
            }

        } catch (\Exception $e) {
            \Log::error('Error setting main photo', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Ошибка при установке главного фото');
        }
    }

    public function destroy(Request $request, $userId)
    {
        $user = Secondaryuser::findOrFail($userId);

        $request->validate([
            'fid' => 'required|string'
        ]);

        try {
            $deleted = $this->userPhotoService->deleteUserPhoto($user, $request->input('fid'));

            if ($deleted) {
                return redirect()->back()->with('success', 'Фото удалено');
            } else {
                return redirect()->back()->with('error', 'Фото не найдено');
            }

        } catch (\Exception $e) {
            \Log::error('Error deleting photo', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Ошибка при удалении фото');
        }
    }
}
