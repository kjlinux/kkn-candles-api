<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'customer' => [
                'first_name' => $this->customer_first_name,
                'last_name' => $this->customer_last_name,
                'full_name' => $this->customer_full_name,
                'email' => $this->customer_email,
                'phone' => $this->customer_phone,
            ],
            'shipping' => [
                'address' => $this->shipping_address,
                'city' => $this->shipping_city,
            ],
            'subtotal' => $this->subtotal,
            'shipping_cost' => $this->shipping_cost,
            'total' => $this->total,
            'formatted_total' => number_format($this->total, 0, ',', ' ') . ' FCFA',
            'notes' => $this->notes,
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'payment' => new PaymentResource($this->whenLoaded('payment')),
            'paid_at' => $this->paid_at?->toISOString(),
            'shipped_at' => $this->shipped_at?->toISOString(),
            'delivered_at' => $this->delivered_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
