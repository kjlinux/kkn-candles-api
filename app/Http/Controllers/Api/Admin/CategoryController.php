<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class CategoryController extends Controller
{
    #[OA\Get(
        path: '/admin/categories',
        summary: 'Liste des catégories (Admin)',
        description: 'Récupérer toutes les catégories avec filtrage optionnel',
        tags: ['Admin - Categories'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'is_active', in: 'query', description: 'Filtrer par statut actif', schema: new OA\Schema(type: 'boolean')),
        ],
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
            new OA\Response(response: 401, description: 'Non authentifié'),
            new OA\Response(response: 403, description: 'Accès refusé'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = Category::query()->withCount('products');

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $categories = $query->ordered()->get();

        return response()->json([
            'success' => true,
            'data' => CategoryResource::collection($categories)
        ]);
    }

    #[OA\Post(
        path: '/admin/categories',
        summary: 'Créer une catégorie',
        description: 'Créer une nouvelle catégorie',
        tags: ['Admin - Categories'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'slug'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Bougies Parfumées'),
                    new OA\Property(property: 'slug', type: 'string', maxLength: 255, example: 'bougies-parfumees'),
                    new OA\Property(property: 'description', type: 'string', maxLength: 1000, nullable: true),
                    new OA\Property(property: 'image_url', type: 'string', format: 'url', nullable: true),
                    new OA\Property(property: 'is_active', type: 'boolean', default: true),
                    new OA\Property(property: 'sort_order', type: 'integer', default: 0),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Catégorie créée',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Catégorie créée avec succès'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Category'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Non authentifié'),
            new OA\Response(response: 403, description: 'Accès refusé'),
            new OA\Response(response: 422, description: 'Erreur de validation'),
        ]
    )]
    public function store(CategoryRequest $request): JsonResponse
    {
        $category = Category::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Catégorie créée avec succès',
            'data' => new CategoryResource($category)
        ], 201);
    }

    #[OA\Get(
        path: '/admin/categories/{category}',
        summary: 'Détail d\'une catégorie (Admin)',
        description: 'Récupérer les détails d\'une catégorie',
        tags: ['Admin - Categories'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'category', in: 'path', required: true, description: 'ID de la catégorie', schema: new OA\Schema(type: 'string', format: 'uuid')),
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
            new OA\Response(response: 401, description: 'Non authentifié'),
            new OA\Response(response: 403, description: 'Accès refusé'),
            new OA\Response(response: 404, description: 'Catégorie non trouvée'),
        ]
    )]
    public function show(Category $category): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new CategoryResource($category->loadCount('products'))
        ]);
    }

    #[OA\Put(
        path: '/admin/categories/{category}',
        summary: 'Modifier une catégorie',
        description: 'Mettre à jour une catégorie existante',
        tags: ['Admin - Categories'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'category', in: 'path', required: true, description: 'ID de la catégorie', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'slug'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 255),
                    new OA\Property(property: 'slug', type: 'string', maxLength: 255),
                    new OA\Property(property: 'description', type: 'string', maxLength: 1000, nullable: true),
                    new OA\Property(property: 'image_url', type: 'string', format: 'url', nullable: true),
                    new OA\Property(property: 'is_active', type: 'boolean'),
                    new OA\Property(property: 'sort_order', type: 'integer'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Catégorie mise à jour',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Catégorie mise à jour'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Category'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Non authentifié'),
            new OA\Response(response: 403, description: 'Accès refusé'),
            new OA\Response(response: 404, description: 'Catégorie non trouvée'),
            new OA\Response(response: 422, description: 'Erreur de validation'),
        ]
    )]
    public function update(CategoryRequest $request, Category $category): JsonResponse
    {
        $category->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Catégorie mise à jour',
            'data' => new CategoryResource($category->fresh())
        ]);
    }

    #[OA\Delete(
        path: '/admin/categories/{category}',
        summary: 'Supprimer une catégorie',
        description: 'Supprimer une catégorie (impossible si elle contient des produits)',
        tags: ['Admin - Categories'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'category', in: 'path', required: true, description: 'ID de la catégorie', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Catégorie supprimée',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Catégorie supprimée'),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Catégorie contient des produits'),
            new OA\Response(response: 401, description: 'Non authentifié'),
            new OA\Response(response: 403, description: 'Accès refusé'),
            new OA\Response(response: 404, description: 'Catégorie non trouvée'),
        ]
    )]
    public function destroy(Category $category): JsonResponse
    {
        if ($category->products()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer une catégorie contenant des produits'
            ], 400);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Catégorie supprimée'
        ]);
    }
}
