<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = Category::query()
            ->active()
            ->withCount('products')
            ->ordered()
            ->get();

        return response()->json([
            'success' => true,
            'data' => CategoryResource::collection($categories)
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $category = Category::query()
            ->where('id', $id)
            ->orWhere('slug', $id)
            ->active()
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => new CategoryResource($category)
        ]);
    }

    public function products(Request $request, string $id): JsonResponse
    {
        $category = Category::query()
            ->where('id', $id)
            ->orWhere('slug', $id)
            ->active()
            ->firstOrFail();

        $perPage = min($request->get('per_page', 12), 50);

        $products = $category->activeProducts()
            ->with('category')
            ->ordered()
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'category' => new CategoryResource($category),
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
}
