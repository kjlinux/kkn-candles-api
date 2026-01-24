<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ProductController extends Controller
{
    #[OA\Get(
        path: '/admin/products',
        operationId: 'adminGetProducts',
        summary: 'Liste des produits (Admin)',
        description: 'Récupérer tous les produits avec filtrage et pagination',
        tags: ['Admin - Products'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'category_id', in: 'query', description: 'Filtrer par catégorie', schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'is_active', in: 'query', description: 'Filtrer par statut actif', schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'search', in: 'query', description: 'Rechercher par nom ou slug', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'per_page', in: 'query', description: 'Nombre de produits par page (max 100)', schema: new OA\Schema(type: 'integer', default: 20)),
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
            new OA\Response(response: 401, description: 'Non authentifié'),
            new OA\Response(response: 403, description: 'Accès refusé'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = Product::query()->with('category');

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                    ->orWhere('slug', 'ILIKE', "%{$search}%");
            });
        }

        $perPage = min($request->get('per_page', 20), 100);
        $products = $query->ordered()->paginate($perPage);

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

    #[OA\Post(
        path: '/admin/products',
        operationId: 'adminCreateProduct',
        summary: 'Créer un produit',
        description: 'Créer un nouveau produit',
        tags: ['Admin - Products'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['category_id', 'name', 'slug', 'price', 'stock_quantity'],
                properties: [
                    new OA\Property(property: 'category_id', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Bougie Parfumée Vanille'),
                    new OA\Property(property: 'slug', type: 'string', maxLength: 255, example: 'bougie-parfumee-vanille'),
                    new OA\Property(property: 'description', type: 'string', maxLength: 1000, nullable: true),
                    new OA\Property(property: 'long_description', type: 'string', nullable: true),
                    new OA\Property(property: 'price', type: 'integer', minimum: 0, example: 5000),
                    new OA\Property(property: 'stock_quantity', type: 'integer', minimum: 0, example: 100),
                    new OA\Property(property: 'in_stock', type: 'boolean', default: true),
                    new OA\Property(property: 'is_featured', type: 'boolean', default: false),
                    new OA\Property(property: 'is_active', type: 'boolean', default: true),
                    new OA\Property(property: 'images', type: 'array', items: new OA\Items(type: 'string', format: 'url'), nullable: true),
                    new OA\Property(property: 'specifications', type: 'object', nullable: true),
                    new OA\Property(property: 'sort_order', type: 'integer', default: 0),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Produit créé',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Produit créé avec succès'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Product'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Non authentifié'),
            new OA\Response(response: 403, description: 'Accès refusé'),
            new OA\Response(response: 422, description: 'Erreur de validation'),
        ]
    )]
    public function store(ProductRequest $request): JsonResponse
    {
        $product = Product::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Produit créé avec succès',
            'data' => new ProductResource($product->load('category')),
        ], 201);
    }

    #[OA\Get(
        path: '/admin/products/{product}',
        operationId: 'adminGetProduct',
        summary: 'Détail d\'un produit (Admin)',
        description: 'Récupérer les détails d\'un produit',
        tags: ['Admin - Products'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'product', in: 'path', required: true, description: 'ID du produit', schema: new OA\Schema(type: 'string', format: 'uuid')),
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
            new OA\Response(response: 401, description: 'Non authentifié'),
            new OA\Response(response: 403, description: 'Accès refusé'),
            new OA\Response(response: 404, description: 'Produit non trouvé'),
        ]
    )]
    public function show(Product $product): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new ProductResource($product->load('category')),
        ]);
    }

    #[OA\Put(
        path: '/admin/products/{product}',
        operationId: 'adminUpdateProduct',
        summary: 'Modifier un produit',
        description: 'Mettre à jour un produit existant',
        tags: ['Admin - Products'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'product', in: 'path', required: true, description: 'ID du produit', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['category_id', 'name', 'slug', 'price', 'stock_quantity'],
                properties: [
                    new OA\Property(property: 'category_id', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'name', type: 'string', maxLength: 255),
                    new OA\Property(property: 'slug', type: 'string', maxLength: 255),
                    new OA\Property(property: 'description', type: 'string', maxLength: 1000, nullable: true),
                    new OA\Property(property: 'long_description', type: 'string', nullable: true),
                    new OA\Property(property: 'price', type: 'integer', minimum: 0),
                    new OA\Property(property: 'stock_quantity', type: 'integer', minimum: 0),
                    new OA\Property(property: 'in_stock', type: 'boolean'),
                    new OA\Property(property: 'is_featured', type: 'boolean'),
                    new OA\Property(property: 'is_active', type: 'boolean'),
                    new OA\Property(property: 'images', type: 'array', items: new OA\Items(type: 'string', format: 'url'), nullable: true),
                    new OA\Property(property: 'specifications', type: 'object', nullable: true),
                    new OA\Property(property: 'sort_order', type: 'integer'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Produit mis à jour',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Produit mis à jour'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Product'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Non authentifié'),
            new OA\Response(response: 403, description: 'Accès refusé'),
            new OA\Response(response: 404, description: 'Produit non trouvé'),
            new OA\Response(response: 422, description: 'Erreur de validation'),
        ]
    )]
    public function update(ProductRequest $request, Product $product): JsonResponse
    {
        $product->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Produit mis à jour',
            'data' => new ProductResource($product->fresh()->load('category')),
        ]);
    }

    #[OA\Delete(
        path: '/admin/products/{product}',
        operationId: 'adminDeleteProduct',
        summary: 'Supprimer un produit',
        description: 'Supprimer un produit',
        tags: ['Admin - Products'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'product', in: 'path', required: true, description: 'ID du produit', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Produit supprimé',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Produit supprimé'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Non authentifié'),
            new OA\Response(response: 403, description: 'Accès refusé'),
            new OA\Response(response: 404, description: 'Produit non trouvé'),
        ]
    )]
    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Produit supprimé',
        ]);
    }
}
