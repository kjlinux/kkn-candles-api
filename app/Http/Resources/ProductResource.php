<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'long_description' => $this->long_description,
            'price' => $this->price,
            'formatted_price' => $this->formatted_price,
            'stock_quantity' => $this->stock_quantity,
            'in_stock' => $this->in_stock,
            'is_featured' => $this->is_featured,
            'is_active' => $this->is_active,
            'images' => $this->images ?? [],
            'main_image' => $this->main_image,
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'specifications' => $this->specifications,
            'sort_order' => $this->sort_order,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
