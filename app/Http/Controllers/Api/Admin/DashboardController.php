<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
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
