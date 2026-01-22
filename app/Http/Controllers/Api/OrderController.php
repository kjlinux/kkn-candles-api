<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\CreateOrderRequest;
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
        $perPage = min($request->get('per_page', 10), 50);

        $orders = Order::query()
            ->forUser(auth()->id())
            ->with('items')
            ->latest()
            ->paginate($perPage);

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

    public function store(CreateOrderRequest $request): JsonResponse
    {
        try {
            $order = $this->orderService->createOrder(
                $request->validated(),
                auth()->id()
            );

            if (auth()->user()->cart) {
                auth()->user()->cart->items()->delete();
            }

            return response()->json([
                'success' => true,
                'message' => 'Commande créée avec succès',
                'data' => new OrderResource($order->load('items'))
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function show(Order $order): JsonResponse
    {
        if ($order->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Commande non trouvée'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new OrderResource($order->load(['items', 'payment']))
        ]);
    }
}
