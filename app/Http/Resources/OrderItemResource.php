<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_name' => $this->product_name,
            'product_image' => $this->product_image,
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'formatted_unit_price' => number_format($this->unit_price, 0, ',', ' ') . ' FCFA',
            'subtotal' => $this->total,
            'formatted_subtotal' => number_format($this->total, 0, ',', ' ') . ' FCFA',
        ];
    }
}
