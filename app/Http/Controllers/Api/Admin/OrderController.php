<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
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
        path: '/admin/orders',
        operationId: 'adminGetOrders',
        summary: 'Liste des commandes (Admin)',
        description: 'Récupérer toutes les commandes avec filtrage et pagination',
        tags: ['Admin - Orders'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'status', in: 'query', description: 'Filtrer par statut', schema: new OA\Schema(type: 'string', enum: ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'])),
            new OA\Parameter(name: 'search', in: 'query', description: 'Rechercher par numéro, email ou nom client', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'per_page', in: 'query', description: 'Nombre de commandes par page (max 100)', schema: new OA\Schema(type: 'integer', default: 20)),
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
            new OA\Response(response: 403, description: 'Accès refusé'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = Order::query()->with(['items', 'payment']);

        if ($request->has('status')) {
            $query->byStatus($request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'ILIKE', "%{$search}%")
                    ->orWhere('customer_email', 'ILIKE', "%{$search}%")
                    ->orWhere('customer_first_name', 'ILIKE', "%{$search}%")
                    ->orWhere('customer_last_name', 'ILIKE', "%{$search}%");
            });
        }

        $perPage = min($request->get('per_page', 20), 100);
        $orders = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'items' => OrderResource::collection($orders),
                'meta' => [
                    'current_page' => $orders->currentPage(),
                    'last_page' => $orders->lastPage(),
                    'per_page' => $orders->perPage(),
                    'total' => $orders->total(),
                ],
            ],
        ]);
    }

    #[OA\Get(
        path: '/admin/orders/{order}',
        operationId: 'adminGetOrder',
        summary: 'Détail d\'une commande (Admin)',
        description: 'Récupérer les détails complets d\'une commande',
        tags: ['Admin - Orders'],
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
            new OA\Response(response: 403, description: 'Accès refusé'),
            new OA\Response(response: 404, description: 'Commande non trouvée'),
        ]
    )]
    public function show(Order $order): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new OrderResource($order->load(['items', 'payment', 'user'])),
        ]);
    }

    #[OA\Put(
        path: '/admin/orders/{order}/status',
        operationId: 'adminUpdateOrderStatus',
        summary: 'Mettre à jour le statut d\'une commande',
        description: 'Modifier le statut d\'une commande',
        tags: ['Admin - Orders'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'order', in: 'path', required: true, description: 'ID de la commande', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['status'],
                properties: [
                    new OA\Property(property: 'status', type: 'string', enum: ['confirmed', 'processing', 'shipped', 'delivered', 'cancelled']),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Statut mis à jour',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Statut mis à jour'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Order'),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Transition de statut invalide'),
            new OA\Response(response: 401, description: 'Non authentifié'),
            new OA\Response(response: 403, description: 'Accès refusé'),
            new OA\Response(response: 404, description: 'Commande non trouvée'),
            new OA\Response(response: 422, description: 'Erreur de validation'),
        ]
    )]
    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:confirmed,processing,shipped,delivered,cancelled',
        ]);

        try {
            $this->orderService->updateStatus($order, $request->status);

            return response()->json([
                'success' => true,
                'message' => 'Statut mis à jour',
                'data' => new OrderResource($order->fresh()->load(['items', 'payment'])),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
