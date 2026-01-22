<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        private OrderService $orderService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Order::query()->with(['items', 'payment']);

        if ($request->has('status')) {
            $query->byStatus($request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'ILIKE', "%{$search}%")
                    ->orWhere('customer_email', 'ILIKE', "%{$search}%")
                    ->orWhere('customer_first_name', 'ILIKE', "%{$search}%")
                    ->orWhere('customer_last_name', 'ILIKE', "%{$search}%");
            });
        }

        $perPage = min($request->get('per_page', 20), 100);
        $orders = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'items' => OrderResource::collection($orders),
                'meta' => [
                    'current_page' => $orders->currentPage(),
                    'last_page' => $orders->lastPage(),
                    'per_page' => $orders->perPage(),
                    'total' => $orders->total(),
                ]
            ]
        ]);
    }

    public function show(Order $order): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new OrderResource($order->load(['items', 'payment', 'user']))
        ]);
    }

    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:confirmed,processing,shipped,delivered,cancelled',
        ]);

        try {
            $this->orderService->updateStatus($order, $request->status);

            return response()->json([
                'success' => true,
                'message' => 'Statut mis à jour',
                'data' => new OrderResource($order->fresh()->load(['items', 'payment']))
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
