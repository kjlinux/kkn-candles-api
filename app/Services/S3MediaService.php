<?php

namespace App\Services;

use App\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class S3MediaService
{
    private string $disk = 's3';

    public function uploadImage(UploadedFile $file, ?string $userId = null): Media
    {
        $filename = $this->generateFilename($file, 'img');
        $path = "images/{$filename}";

        Storage::disk($this->disk)->put($path, file_get_contents($file), 'public');

        $imageInfo = getimagesize($file->getRealPath());

        return Media::create([
            'filename' => $filename,
            'original_filename' => $file->getClientOriginalName(),
            'path' => $path,
            'url' => Storage::disk($this->disk)->url($path),
            'disk' => $this->disk,
            'type' => 'image',
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'width' => $imageInfo[0] ?? null,
            'height' => $imageInfo[1] ?? null,
            'uploaded_by' => $userId,
        ]);
    }

    public function uploadVideo(UploadedFile $file, ?string $userId = null): Media
    {
        $filename = $this->generateFilename($file, 'vid');
        $path = "videos/{$filename}";

        Storage::disk($this->disk)->put($path, file_get_contents($file), 'public');

        return Media::create([
            'filename' => $filename,
            'original_filename' => $file->getClientOriginalName(),
            'path' => $path,
            'url' => Storage::disk($this->disk)->url($path),
            'disk' => $this->disk,
            'type' => 'video',
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'uploaded_by' => $userId,
        ]);
    }

    public function delete(Media $media): bool
    {
        if (Storage::disk($this->disk)->exists($media->path)) {
            Storage::disk($this->disk)->delete($media->path);
        }

        if ($media->thumbnail_url) {
            $thumbnailPath = str_replace(Storage::disk($this->disk)->url(''), '', $media->thumbnail_url);
            if (Storage::disk($this->disk)->exists($thumbnailPath)) {
                Storage::disk($this->disk)->delete($thumbnailPath);
            }
        }

        return $media->delete();
    }

    private function generateFilename(UploadedFile $file, string $prefix): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $timestamp = now()->format('Ymd_His');
        $random = Str::random(8);

        return "{$prefix}_{$timestamp}_{$random}.{$extension}";
    }
}
