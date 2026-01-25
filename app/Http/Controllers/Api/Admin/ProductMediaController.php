<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\MediaResource;
use App\Models\Media;
use App\Models\Product;
use App\Services\S3MediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ProductMediaController extends Controller
{
    public function __construct(
        private S3MediaService $mediaService
    ) {}

    #[OA\Get(
        path: '/admin/products/{product}/media',
        operationId: 'adminGetProductMedia',
        summary: 'Liste des médias d\'un produit',
        description: 'Récupérer tous les médias associés à un produit',
        tags: ['Admin - Product Media'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'product', in: 'path', required: true, description: 'ID du produit', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste des médias',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Media')),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Produit non trouvé'),
        ]
    )]
    public function index(Product $product): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => MediaResource::collection($product->media),
        ]);
    }

    #[OA\Post(
        path: '/admin/products/{product}/media/upload',
        operationId: 'adminUploadProductMedia',
        summary: 'Uploader une image directement sur un produit',
        description: 'Upload une image et l\'associe directement au produit',
        tags: ['Admin - Product Media'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'product', in: 'path', required: true, description: 'ID du produit', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['file'],
                    properties: [
                        new OA\Property(property: 'file', type: 'string', format: 'binary', description: 'Image à uploader'),
                        new OA\Property(property: 'sort_order', type: 'integer', description: 'Position de l\'image (optionnel)'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Image uploadée et associée',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Image uploadée et associée au produit'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Media'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Produit non trouvé'),
            new OA\Response(response: 422, description: 'Erreur de validation'),
        ]
    )]
    public function upload(Request $request, Product $product): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,gif,webp|max:10240',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $media = $this->mediaService->uploadImage($request->file('file'), auth()->id());
        $product->attachMedia($media->id, $request->input('sort_order'));

        $media->load('products');

        return response()->json([
            'success' => true,
            'message' => 'Image uploadée et associée au produit',
            'data' => new MediaResource($media),
        ], 201);
    }

    #[OA\Post(
        path: '/admin/products/{product}/media',
        operationId: 'adminAttachMediaToProduct',
        summary: 'Associer un média existant à un produit',
        description: 'Lie un média de la bibliothèque à un produit',
        tags: ['Admin - Product Media'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'product', in: 'path', required: true, description: 'ID du produit', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['media_id'],
                properties: [
                    new OA\Property(property: 'media_id', type: 'string', format: 'uuid', description: 'ID du média à associer'),
                    new OA\Property(property: 'sort_order', type: 'integer', description: 'Position de l\'image (optionnel)'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Média associé',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Média associé au produit'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Media'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Produit ou média non trouvé'),
            new OA\Response(response: 422, description: 'Erreur de validation'),
        ]
    )]
    public function attach(Request $request, Product $product): JsonResponse
    {
        $request->validate([
            'media_id' => 'required|uuid|exists:media,id',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $media = Media::findOrFail($request->input('media_id'));

        if ($media->type !== 'image') {
            return response()->json([
                'success' => false,
                'message' => 'Seules les images peuvent être associées aux produits',
            ], 422);
        }

        $product->attachMedia($media->id, $request->input('sort_order'));

        return response()->json([
            'success' => true,
            'message' => 'Média associé au produit',
            'data' => new MediaResource($media->fresh()),
        ]);
    }

    #[OA\Delete(
        path: '/admin/products/{product}/media/{media}',
        operationId: 'adminDetachMediaFromProduct',
        summary: 'Dissocier un média d\'un produit',
        description: 'Retire l\'association entre un média et un produit (ne supprime pas le média)',
        tags: ['Admin - Product Media'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'product', in: 'path', required: true, description: 'ID du produit', schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'media', in: 'path', required: true, description: 'ID du média', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Média dissocié',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Média dissocié du produit'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Produit ou média non trouvé'),
        ]
    )]
    public function detach(Product $product, Media $media): JsonResponse
    {
        $product->detachMedia($media->id);

        return response()->json([
            'success' => true,
            'message' => 'Média dissocié du produit',
        ]);
    }

    #[OA\Put(
        path: '/admin/products/{product}/media/reorder',
        operationId: 'adminReorderProductMedia',
        summary: 'Réordonner les médias d\'un produit',
        description: 'Met à jour l\'ordre des médias associés à un produit',
        tags: ['Admin - Product Media'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'product', in: 'path', required: true, description: 'ID du produit', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['media_ids'],
                properties: [
                    new OA\Property(
                        property: 'media_ids',
                        type: 'array',
                        items: new OA\Items(type: 'string', format: 'uuid'),
                        description: 'Liste ordonnée des IDs des médias'
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Ordre mis à jour',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Ordre des médias mis à jour'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Media')),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Produit non trouvé'),
            new OA\Response(response: 422, description: 'Erreur de validation'),
        ]
    )]
    public function reorder(Request $request, Product $product): JsonResponse
    {
        $request->validate([
            'media_ids' => 'required|array',
            'media_ids.*' => 'uuid|exists:media,id',
        ]);

        $product->syncMedia($request->input('media_ids'));

        return response()->json([
            'success' => true,
            'message' => 'Ordre des médias mis à jour',
            'data' => MediaResource::collection($product->fresh()->media),
        ]);
    }
}
