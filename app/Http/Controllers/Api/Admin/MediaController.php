<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Services\S3MediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class MediaController extends Controller
{
    public function __construct(
        private S3MediaService $mediaService
    ) {}

    #[OA\Get(
        path: '/admin/media',
        operationId: 'adminGetMedia',
        summary: 'Liste des médias',
        description: 'Récupérer la liste des fichiers médias uploadés',
        tags: ['Admin - Media'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'type', in: 'query', description: 'Filtrer par type', schema: new OA\Schema(type: 'string', enum: ['image', 'video'])),
            new OA\Parameter(name: 'per_page', in: 'query', description: 'Nombre de médias par page (max 100)', schema: new OA\Schema(type: 'integer', default: 20)),
            new OA\Parameter(name: 'page', in: 'query', description: 'Numéro de page', schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste des médias',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: '#/components/schemas/Media')),
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
        $query = Media::query()->with('uploader');

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $perPage = min($request->get('per_page', 20), 100);
        $media = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $media->map(fn ($m) => [
                    'id' => $m->id,
                    'url' => $m->url,
                    'type' => $m->type,
                    'filename' => $m->filename,
                    'original_filename' => $m->original_filename,
                    'size' => $m->size,
                    'formatted_size' => $m->formatted_size,
                    'width' => $m->width,
                    'height' => $m->height,
                    'created_at' => $m->created_at->toISOString(),
                ]),
                'meta' => [
                    'current_page' => $media->currentPage(),
                    'last_page' => $media->lastPage(),
                    'per_page' => $media->perPage(),
                    'total' => $media->total(),
                ],
            ],
        ]);
    }

    #[OA\Post(
        path: '/admin/media/upload',
        operationId: 'adminUploadMedia',
        summary: 'Uploader un fichier',
        description: 'Uploader une image ou une vidéo',
        tags: ['Admin - Media'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['file', 'type'],
                    properties: [
                        new OA\Property(property: 'file', type: 'string', format: 'binary', description: 'Fichier à uploader'),
                        new OA\Property(property: 'type', type: 'string', enum: ['image', 'video'], description: 'Type de fichier'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Fichier uploadé',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Fichier uploadé avec succès'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'url', type: 'string', format: 'url'),
                                new OA\Property(property: 'type', type: 'string', enum: ['image', 'video']),
                                new OA\Property(property: 'size', type: 'string', example: '1.5 MB'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Non authentifié'),
            new OA\Response(response: 403, description: 'Accès refusé'),
            new OA\Response(response: 422, description: 'Erreur de validation'),
        ]
    )]
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:51200',
            'type' => 'required|in:image,video',
        ]);

        $file = $request->file('file');
        $type = $request->input('type');

        if ($type === 'image') {
            $request->validate([
                'file' => 'mimes:jpg,jpeg,png,gif,webp|max:10240',
            ]);
            $media = $this->mediaService->uploadImage($file, auth()->id());
        } else {
            $request->validate([
                'file' => 'mimes:mp4,mov,avi,webm|max:51200',
            ]);
            $media = $this->mediaService->uploadVideo($file, auth()->id());
        }

        return response()->json([
            'success' => true,
            'message' => 'Fichier uploadé avec succès',
            'data' => [
                'id' => $media->id,
                'url' => $media->url,
                'type' => $media->type,
                'size' => $media->formatted_size,
            ],
        ], 201);
    }

    #[OA\Delete(
        path: '/admin/media/{media}',
        operationId: 'adminDeleteMedia',
        summary: 'Supprimer un fichier',
        description: 'Supprimer un fichier média',
        tags: ['Admin - Media'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'media', in: 'path', required: true, description: 'ID du média', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Fichier supprimé',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Fichier supprimé'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Non authentifié'),
            new OA\Response(response: 403, description: 'Accès refusé'),
            new OA\Response(response: 404, description: 'Média non trouvé'),
        ]
    )]
    public function destroy(Media $media): JsonResponse
    {
        $this->mediaService->delete($media);

        return response()->json([
            'success' => true,
            'message' => 'Fichier supprimé',
        ]);
    }
}
