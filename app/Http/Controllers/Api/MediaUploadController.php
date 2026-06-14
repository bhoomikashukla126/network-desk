<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\MediaStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MediaUploadController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'image', 'max:10240'],
        ]);

        $file = $validated['file'];
        $filename = Str::uuid().'.'.$file->getClientOriginalExtension();
        $disk = MediaStorage::diskName();
        $path = $file->storeAs('network-desk/images', $filename, $disk);

        return response()->json([
            'path' => $path,
            'url' => MediaStorage::url($path),
        ], 201);
    }

    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'url' => ['required', 'string', 'max:2000'],
        ]);

        $url = trim($validated['url']);

        if (! MediaStorage::isManagedReference($url)) {
            return response()->json([
                'message' => 'Only uploaded network-desk files can be deleted from storage.',
                'cleared' => true,
            ]);
        }

        MediaStorage::deleteReference($url);

        return response()->json([
            'message' => 'File deleted.',
            'cleared' => true,
        ]);
    }
}
