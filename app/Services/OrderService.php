<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class OrderService
{
    private const SHIPPING_COST = 2000;

    public function createOrder(array $data, ?string $userId = null): Order
    {
        return DB::transaction(function () use ($data, $userId) {
            $subtotal = 0;
            $itemsData = [];

            foreach ($data['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);

                if (!$product->in_stock || $product->stock_quantity < $item['quantity']) {
                    throw new \Exception("Le produit '{$product->name}' n'est plus disponible en quantité suffisante");
                }

                $itemTotal = $product->price * $item['quantity'];
                $subtotal += $itemTotal;

                $itemsData[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_image' => $product->main_image,
                    'quantity' => $item['quantity'],
                    'unit_price' => $product->price,
                    'total' => $itemTotal,
                ];

                $product->decrementStock($item['quantity']);
            }

            $total = $subtotal + self::SHIPPING_COST;

            $order = Order::create([
                'user_id' => $userId,
                'customer_first_name' => $data['first_name'],
                'customer_last_name' => $data['last_name'],
                'customer_email' => $data['email'],
                'customer_phone' => $data['phone'],
                'shipping_address' => $data['address'],
                'shipping_city' => $data['city'],
                'notes' => $data['notes'] ?? null,
                'subtotal' => $subtotal,
                'shipping_cost' => self::SHIPPING_COST,
                'total' => $total,
                'status' => 'pending',
            ]);

            foreach ($itemsData as $itemData) {
                $order->items()->create($itemData);
            }

            return $order->load('items');
        });
    }

    public function cancelOrder(Order $order): void
    {
        if (in_array($order->status, ['shipped', 'delivered'])) {
            throw new \Exception('Cette commande ne peut pas être annulée');
        }

        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                if ($item->product) {
                    $item->product->incrementStock($item->quantity);
                }
            }

            $order->cancel();
        });
    }

    public function updateStatus(Order $order, string $status): void
    {
        $validTransitions = [
            'pending' => ['confirmed', 'cancelled'],
            'confirmed' => ['processing', 'cancelled'],
            'processing' => ['shipped', 'cancelled'],
            'shipped' => ['delivered'],
            'delivered' => [],
            'cancelled' => [],
        ];

        if (!in_array($status, $validTransitions[$order->status] ?? [])) {
            throw new \Exception("Transition de statut invalide: {$order->status} vers {$status}");
        }

        match ($status) {
            'confirmed' => $order->update(['status' => 'confirmed']),
            'processing' => $order->update(['status' => 'processing']),
            'shipped' => $order->markAsShipped(),
            'delivered' => $order->markAsDelivered(),
            'cancelled' => $this->cancelOrder($order),
            default => $order->update(['status' => $status]),
        };
    }
}
