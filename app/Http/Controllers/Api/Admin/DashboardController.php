<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class DashboardController extends Controller
{
    #[OA\Get(
        path: '/admin/dashboard',
        summary: 'Tableau de bord administrateur',
        description: 'Récupérer les statistiques et les commandes récentes pour le tableau de bord admin',
        tags: ['Admin - Dashboard'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Statistiques du tableau de bord',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(
                                    property: 'stats',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(
                                            property: 'orders',
                                            type: 'object',
                                            properties: [
                                                new OA\Property(property: 'total', type: 'integer'),
                                                new OA\Property(property: 'today', type: 'integer'),
                                                new OA\Property(property: 'this_month', type: 'integer'),
                                                new OA\Property(property: 'pending', type: 'integer'),
                                                new OA\Property(property: 'confirmed', type: 'integer'),
                                                new OA\Property(property: 'processing', type: 'integer'),
                                                new OA\Property(property: 'shipped', type: 'integer'),
                                                new OA\Property(property: 'delivered', type: 'integer'),
                                            ]
                                        ),
                                        new OA\Property(
                                            property: 'revenue',
                                            type: 'object',
                                            properties: [
                                                new OA\Property(property: 'total', type: 'integer'),
                                                new OA\Property(property: 'today', type: 'integer'),
                                                new OA\Property(property: 'this_month', type: 'integer'),
                                            ]
                                        ),
                                        new OA\Property(
                                            property: 'products',
                                            type: 'object',
                                            properties: [
                                                new OA\Property(property: 'total', type: 'integer'),
                                                new OA\Property(property: 'active', type: 'integer'),
                                                new OA\Property(property: 'out_of_stock', type: 'integer'),
                                                new OA\Property(property: 'low_stock', type: 'integer'),
                                            ]
                                        ),
                                        new OA\Property(
                                            property: 'customers',
                                            type: 'object',
                                            properties: [
                                                new OA\Property(property: 'total', type: 'integer'),
                                                new OA\Property(property: 'this_month', type: 'integer'),
                                            ]
                                        ),
                                    ]
                                ),
                                new OA\Property(
                                    property: 'recent_orders',
                                    type: 'array',
                                    items: new OA\Items(
                                        type: 'object',
                                        properties: [
                                            new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                            new OA\Property(property: 'order_number', type: 'string'),
                                            new OA\Property(property: 'customer_name', type: 'string'),
                                            new OA\Property(property: 'total', type: 'integer'),
                                            new OA\Property(property: 'status', type: 'string'),
                                            new OA\Property(property: 'status_label', type: 'string'),
                                            new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                                        ]
                                    )
                                ),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Non authentifié'),
            new OA\Response(response: 403, description: 'Accès refusé (non admin)'),
        ]
    )]
    public function index(): JsonResponse
    {
        $today = now()->startOfDay();
        $thisMonth = now()->startOfMonth();

        $stats = [
            'orders' => [
                'total' => Order::count(),
                'today' => Order::where('created_at', '>=', $today)->count(),
                'this_month' => Order::where('created_at', '>=', $thisMonth)->count(),
                'pending' => Order::byStatus('pending')->count(),
                'confirmed' => Order::byStatus('confirmed')->count(),
                'processing' => Order::byStatus('processing')->count(),
                'shipped' => Order::byStatus('shipped')->count(),
                'delivered' => Order::byStatus('delivered')->count(),
            ],
            'revenue' => [
                'total' => Order::whereNotIn('status', ['cancelled', 'pending'])->sum('total'),
                'today' => Order::whereNotIn('status', ['cancelled', 'pending'])
                    ->where('created_at', '>=', $today)
                    ->sum('total'),
                'this_month' => Order::whereNotIn('status', ['cancelled', 'pending'])
                    ->where('created_at', '>=', $thisMonth)
                    ->sum('total'),
            ],
            'products' => [
                'total' => Product::count(),
                'active' => Product::active()->count(),
                'out_of_stock' => Product::where('in_stock', false)->count(),
                'low_stock' => Product::where('stock_quantity', '<=', 5)->where('stock_quantity', '>', 0)->count(),
            ],
            'customers' => [
                'total' => User::where('role', 'customer')->count(),
                'this_month' => User::where('role', 'customer')
                    ->where('created_at', '>=', $thisMonth)
                    ->count(),
            ],
        ];

        $recentOrders = Order::with('items')
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn($order) => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'customer_name' => $order->customer_full_name,
                'total' => $order->total,
                'status' => $order->status,
                'status_label' => $order->status_label,
                'created_at' => $order->created_at->toISOString(),
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'recent_orders' => $recentOrders,
            ]
        ]);
    }
}
