<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Product::query()
            ->active()
            ->with('category')
            ->ordered();

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('in_stock')) {
            $query->inStock();
        }

        $perPage = min($request->get('per_page', 12), 50);
        $products = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'items' => ProductResource::collection($products),
                'meta' => [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                ]
            ]
        ]);
    }

    public function featured(): JsonResponse
    {
        $products = Product::query()
            ->active()
            ->featured()
            ->inStock()
            ->with('category')
            ->ordered()
            ->limit(8)
            ->get();

        return response()->json([
            'success' => true,
            'data' => ProductResource::collection($products)
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $product = Product::with('category')
            ->where('id', $id)
            ->orWhere('slug', $id)
            ->active()
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => new ProductResource($product)
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2',
        ]);

        $query = $request->get('q');

        $products = Product::query()
            ->active()
            ->where(function ($q) use ($query) {
                $q->where('name', 'ILIKE', "%{$query}%")
                    ->orWhere('description', 'ILIKE', "%{$query}%");
            })
            ->with('category')
            ->ordered()
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'data' => ProductResource::collection($products)
        ]);
    }
}
