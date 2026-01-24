<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ProductController extends Controller
{
    #[OA\Get(
        path: '/products',
        operationId: 'getProducts',
        summary: 'Liste des produits',
        description: 'Récupérer la liste paginée des produits actifs',
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'category_id', in: 'query', description: 'Filtrer par catégorie', schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'in_stock', in: 'query', description: 'Filtrer les produits en stock', schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'per_page', in: 'query', description: 'Nombre de produits par page (max 50)', schema: new OA\Schema(type: 'integer', default: 12)),
            new OA\Parameter(name: 'page', in: 'query', description: 'Numéro de page', schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste des produits',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: '#/components/schemas/Product')),
                                new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
                            ]
                        ),
                    ]
                )
            ),
        ]
    )]
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
                ],
            ],
        ]);
    }

    #[OA\Get(
        path: '/products/featured',
        operationId: 'getFeaturedProducts',
        summary: 'Produits vedettes',
        description: 'Récupérer les produits mis en avant',
        tags: ['Products'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Produits vedettes',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Product')),
                    ]
                )
            ),
        ]
    )]
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
            'data' => ProductResource::collection($products),
        ]);
    }

    #[OA\Get(
        path: '/products/{id}',
        operationId: 'getProduct',
        summary: 'Détail d\'un produit',
        description: 'Récupérer les détails d\'un produit par ID ou slug',
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID ou slug du produit', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Détail du produit',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Product'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Produit non trouvé'),
        ]
    )]
    public function show(string $id): JsonResponse
    {
        $product = Product::with('category')
            ->where('id', $id)
            ->orWhere('slug', $id)
            ->active()
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => new ProductResource($product),
        ]);
    }

    #[OA\Get(
        path: '/products/search',
        operationId: 'searchProducts',
        summary: 'Rechercher des produits',
        description: 'Rechercher des produits par nom ou description',
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'q', in: 'query', required: true, description: 'Terme de recherche (min 2 caractères)', schema: new OA\Schema(type: 'string', minLength: 2)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Résultats de recherche',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Product')),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Erreur de validation'),
        ]
    )]
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
            'data' => ProductResource::collection($products),
        ]);
    }
}
