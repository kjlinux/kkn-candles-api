<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Services\S3MediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MediaController extends Controller
{
    public function __construct(
        private S3MediaService $mediaService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Media::query()->with('uploader');

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $perPage = min($request->get('per_page', 20), 100);
        $media = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $media->map(fn($m) => [
                    'id' => $m->id,
                    'url' => $m->url,
                    'type' => $m->type,
                    'filename' => $m->filename,
                    'original_filename' => $m->original_filename,
                    'size' => $m->size,
                    'formatted_size' => $m->formatted_size,
                    'width' => $m->width,
                    'height' => $m->height,
                    'created_at' => $m->created_at->toISOString(),
                ]),
                'meta' => [
                    'current_page' => $media->currentPage(),
                    'last_page' => $media->lastPage(),
                    'per_page' => $media->perPage(),
                    'total' => $media->total(),
                ]
            ]
        ]);
    }

    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:51200',
            'type' => 'required|in:image,video',
        ]);

        $file = $request->file('file');
        $type = $request->input('type');

        if ($type === 'image') {
            $request->validate([
                'file' => 'mimes:jpg,jpeg,png,gif,webp|max:10240',
            ]);
            $media = $this->mediaService->uploadImage($file, auth()->id());
        } else {
            $request->validate([
                'file' => 'mimes:mp4,mov,avi,webm|max:51200',
            ]);
            $media = $this->mediaService->uploadVideo($file, auth()->id());
        }

        return response()->json([
            'success' => true,
            'message' => 'Fichier uploadé avec succès',
            'data' => [
                'id' => $media->id,
                'url' => $media->url,
                'type' => $media->type,
                'size' => $media->formatted_size,
            ]
        ], 201);
    }

    public function destroy(Media $media): JsonResponse
    {
        $this->mediaService->delete($media);

        return response()->json([
            'success' => true,
            'message' => 'Fichier supprimé'
        ]);
    }
}
