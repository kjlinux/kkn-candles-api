<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'items' => CartItemResource::collection($this->whenLoaded('items')),
            'items_count' => $this->items_count,
            'total' => $this->total,
            'formatted_total' => number_format($this->total, 0, ',', ' ') . ' FCFA',
        ];
    }
}
