<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MediaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'url' => $this->url,
            'thumbnail_url' => $this->thumbnail_url,
            'type' => $this->type,
            'filename' => $this->filename,
            'original_filename' => $this->original_filename,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'formatted_size' => $this->formatted_size,
            'width' => $this->width,
            'height' => $this->height,
            'sort_order' => $this->whenPivotLoaded('product_media', fn () => $this->pivot->sort_order),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
