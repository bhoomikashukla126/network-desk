<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NetworkPoint;
use App\Models\PointImage;
use App\Support\MediaStorage;
use App\Support\WorkspaceSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PointImageController extends Controller
{
    public function store(Request $request, NetworkPoint $networkPoint): JsonResponse
    {
        $this->authorizePoint($networkPoint);

        $validated = $request->validate([
            'file' => ['required', 'file', 'image', 'max:10240'],
            'caption' => ['nullable', 'string', 'max:255'],
        ]);

        $file = $validated['file'];
        $filename = Str::uuid().'.'.$file->getClientOriginalExtension();
        $disk = MediaStorage::diskName();
        $path = $file->storeAs('network-desk/images', $filename, $disk);

        $maxSort = $networkPoint->images()->max('sort_order') ?? -1;

        $image = PointImage::query()->create([
            'network_point_id' => $networkPoint->id,
            'path' => $path,
            'url' => MediaStorage::url($path),
            'caption' => $validated['caption'] ?? null,
            'sort_order' => $maxSort + 1,
        ]);

        return response()->json(['data' => $image], 201);
    }

    public function destroy(Request $request, NetworkPoint $networkPoint, PointImage $pointImage): JsonResponse
    {
        $this->authorizePoint($networkPoint);
        abort_unless($pointImage->network_point_id === $networkPoint->id, 404);

        MediaStorage::deleteReference($pointImage->url);
        $pointImage->delete();

        return response()->json(['message' => 'Image removed.']);
    }

    protected function authorizePoint(NetworkPoint $networkPoint): void
    {
        abort_unless(
            $networkPoint->workspace_id === WorkspaceSession::id(),
            404,
        );
    }
}
