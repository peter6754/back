<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Http;

class ImageProxyController extends Controller
{
    public function show($imageId)
    {
        try {

            $seaweedFsService = app(\App\Services\SeaweedFsService::class);
            $imageUrl = $seaweedFsService->createVolumeUrl($imageId);

            $response = Http::timeout(30)->get($imageUrl);

            if (!$response->successful()) {
                abort(404);
            }

            return response($response->body())
                ->header('Content-Type', 'image/jpeg')
                ->header('Access-Control-Allow-Origin', '*');

        } catch (\Exception $e) {
            abort(404);
        }
    }
}
