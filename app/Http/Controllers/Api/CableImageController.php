<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CableImage;
use App\Models\CableSegment;
use App\Support\MediaStorage;
use App\Support\WorkspaceSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\File;

class CableImageController extends Controller
{
    public function store(Request $request, CableSegment $cableSegment): JsonResponse
    {
        $this->authorizeCable($cableSegment);

        $validated = $request->validate([
            'file' => [
                'required',
                File::types(['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'])->max(10240),
            ],
            'caption' => ['nullable', 'string', 'max:255'],
        ]);

        $file = $validated['file'];
        $filename = Str::uuid().'.'.$file->getClientOriginalExtension();
        $disk = MediaStorage::diskName();
        $path = $file->storeAs('network-desk/cables', $filename, $disk);

        $maxSort = $cableSegment->images()->max('sort_order') ?? -1;

        $image = CableImage::query()->create([
            'cable_segment_id' => $cableSegment->id,
            'path' => $path,
            'url' => MediaStorage::url($path),
            'caption' => $validated['caption'] ?? null,
            'mime_type' => $file->getMimeType(),
            'sort_order' => $maxSort + 1,
        ]);

        return response()->json(['data' => $image], 201);
    }

    public function destroy(Request $request, CableSegment $cableSegment, CableImage $cableImage): JsonResponse
    {
        $this->authorizeCable($cableSegment);
        abort_unless($cableImage->cable_segment_id === $cableSegment->id, 404);

        MediaStorage::deleteReference($cableImage->url);
        $cableImage->delete();

        return response()->json(['message' => 'Attachment removed.']);
    }

    protected function authorizeCable(CableSegment $cableSegment): void
    {
        abort_unless(
            $cableSegment->workspace_id === WorkspaceSession::id(),
            404,
        );
    }
}
