<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class CategoryController extends Controller
{
    #[OA\Get(
        path: '/categories',
        summary: 'Liste des catégories',
        description: 'Récupérer toutes les catégories actives avec le nombre de produits',
        tags: ['Categories'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste des catégories',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Category')),
                    ]
                )
            ),
        ]
    )]
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

    #[OA\Get(
        path: '/categories/{id}',
        summary: 'Détail d\'une catégorie',
        description: 'Récupérer les détails d\'une catégorie par ID ou slug',
        tags: ['Categories'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID ou slug de la catégorie', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Détail de la catégorie',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Category'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Catégorie non trouvée'),
        ]
    )]
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

    #[OA\Get(
        path: '/categories/{id}/products',
        summary: 'Produits d\'une catégorie',
        description: 'Récupérer les produits d\'une catégorie spécifique',
        tags: ['Categories'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID ou slug de la catégorie', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'per_page', in: 'query', description: 'Nombre de produits par page (max 50)', schema: new OA\Schema(type: 'integer', default: 12)),
            new OA\Parameter(name: 'page', in: 'query', description: 'Numéro de page', schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Produits de la catégorie',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'category', ref: '#/components/schemas/Category'),
                                new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: '#/components/schemas/Product')),
                                new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Catégorie non trouvée'),
        ]
    )]
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
