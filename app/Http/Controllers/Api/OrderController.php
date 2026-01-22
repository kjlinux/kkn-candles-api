<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\CreateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class OrderController extends Controller
{
    public function __construct(
        private OrderService $orderService
    ) {}

    #[OA\Get(
        path: '/orders',
        summary: 'Liste des commandes',
        description: 'Récupérer les commandes de l\'utilisateur connecté',
        tags: ['Orders'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', description: 'Nombre de commandes par page (max 50)', schema: new OA\Schema(type: 'integer', default: 10)),
            new OA\Parameter(name: 'page', in: 'query', description: 'Numéro de page', schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste des commandes',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: '#/components/schemas/Order')),
                                new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Non authentifié'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $perPage = min($request->get('per_page', 10), 50);

        $orders = Order::query()
            ->forUser(auth()->id())
            ->with('items')
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'items' => OrderResource::collection($orders),
                'meta' => [
                    'current_page' => $orders->currentPage(),
                    'last_page' => $orders->lastPage(),
                    'per_page' => $orders->perPage(),
                    'total' => $orders->total(),
                ]
            ]
        ]);
    }

    #[OA\Post(
        path: '/orders',
        summary: 'Créer une commande',
        description: 'Créer une nouvelle commande',
        tags: ['Orders'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['first_name', 'last_name', 'email', 'phone', 'address', 'city', 'items'],
                properties: [
                    new OA\Property(property: 'first_name', type: 'string', maxLength: 100, example: 'Jean'),
                    new OA\Property(property: 'last_name', type: 'string', maxLength: 100, example: 'Dupont'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'jean@example.com'),
                    new OA\Property(property: 'phone', type: 'string', maxLength: 20, example: '+225 07 00 00 00 00'),
                    new OA\Property(property: 'address', type: 'string', maxLength: 500, example: 'Cocody, Rue des Jardins'),
                    new OA\Property(property: 'city', type: 'string', maxLength: 100, example: 'Abidjan'),
                    new OA\Property(property: 'notes', type: 'string', maxLength: 1000, nullable: true, example: 'Livrer le matin'),
                    new OA\Property(
                        property: 'items',
                        type: 'array',
                        items: new OA\Items(
                            type: 'object',
                            required: ['product_id', 'quantity'],
                            properties: [
                                new OA\Property(property: 'product_id', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'quantity', type: 'integer', minimum: 1, maximum: 100),
                            ]
                        )
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Commande créée avec succès',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Commande créée avec succès'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Order'),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Erreur lors de la création de la commande'),
            new OA\Response(response: 401, description: 'Non authentifié'),
            new OA\Response(response: 422, description: 'Erreur de validation'),
        ]
    )]
    public function store(CreateOrderRequest $request): JsonResponse
    {
        try {
            $order = $this->orderService->createOrder(
                $request->validated(),
                auth()->id()
            );

            if (auth()->user()->cart) {
                auth()->user()->cart->items()->delete();
            }

            return response()->json([
                'success' => true,
                'message' => 'Commande créée avec succès',
                'data' => new OrderResource($order->load('items'))
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    #[OA\Get(
        path: '/orders/{order}',
        summary: 'Détail d\'une commande',
        description: 'Récupérer les détails d\'une commande',
        tags: ['Orders'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'order', in: 'path', required: true, description: 'ID de la commande', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Détail de la commande',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Order'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Non authentifié'),
            new OA\Response(response: 404, description: 'Commande non trouvée'),
        ]
    )]
    public function show(Order $order): JsonResponse
    {
        if ($order->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Commande non trouvée'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new OrderResource($order->load(['items', 'payment']))
        ]);
    }
}
