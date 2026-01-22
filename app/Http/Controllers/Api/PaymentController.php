<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Services\CinetPayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

class PaymentController extends Controller
{
    public function __construct(
        private CinetPayService $cinetpay
    ) {}

    #[OA\Post(
        path: '/payments/cinetpay/init',
        summary: 'Initialiser un paiement CinetPay',
        description: 'Initialiser un paiement via CinetPay pour une commande',
        tags: ['Payments'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['order_id'],
                properties: [
                    new OA\Property(property: 'order_id', type: 'string', format: 'uuid', description: 'ID de la commande à payer'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Paiement initialisé',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Paiement initialisé'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'payment_url', type: 'string', format: 'url', description: 'URL de paiement CinetPay'),
                                new OA\Property(property: 'transaction_id', type: 'string'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Commande non payable'),
            new OA\Response(response: 401, description: 'Non authentifié'),
            new OA\Response(response: 404, description: 'Commande non trouvée'),
            new OA\Response(response: 500, description: 'Erreur lors de l\'initialisation'),
        ]
    )]
    public function initCinetpay(Request $request): JsonResponse
    {
        $request->validate([
            'order_id' => 'required|uuid|exists:orders,id',
        ]);

        $order = Order::findOrFail($request->order_id);

        if ($order->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Commande non trouvée'
            ], 404);
        }

        if ($order->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Cette commande ne peut pas être payée'
            ], 400);
        }

        try {
            $paymentData = $this->cinetpay->initializePayment($order);

            return response()->json([
                'success' => true,
                'message' => 'Paiement initialisé',
                'data' => $paymentData
            ]);

        } catch (\Exception $e) {
            Log::error('CinetPay init error', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'initialisation du paiement'
            ], 500);
        }
    }

    #[OA\Post(
        path: '/payments/cinetpay/notify',
        summary: 'Webhook de notification CinetPay',
        description: 'Endpoint pour recevoir les notifications de paiement de CinetPay (appelé par CinetPay)',
        tags: ['Payments'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'cpm_trans_id', type: 'string'),
                    new OA\Property(property: 'cpm_site_id', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Notification traitée',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Notification traitée'),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Erreur de traitement'),
        ]
    )]
    public function cinetpayNotify(Request $request): JsonResponse
    {
        Log::info('CinetPay Notify', $request->all());

        try {
            $result = $this->cinetpay->handleNotification($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Notification traitée'
            ]);

        } catch (\Exception $e) {
            Log::error('CinetPay notify error', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    #[OA\Get(
        path: '/payments/cinetpay/return',
        summary: 'Page de retour CinetPay',
        description: 'Page de retour après paiement CinetPay',
        tags: ['Payments'],
        parameters: [
            new OA\Parameter(name: 'transaction_id', in: 'query', required: true, description: 'ID de la transaction', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Statut du paiement',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'payment_status', type: 'string', enum: ['pending', 'completed', 'failed', 'cancelled']),
                                new OA\Property(property: 'order_id', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'order_status', type: 'string'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Transaction ID manquant'),
            new OA\Response(response: 404, description: 'Paiement non trouvé'),
        ]
    )]
    public function cinetpayReturn(Request $request): JsonResponse
    {
        $transactionId = $request->input('transaction_id');

        if (!$transactionId) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction ID manquant'
            ], 400);
        }

        $payment = Payment::where('transaction_id', $transactionId)->first();

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Paiement non trouvé'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'payment_status' => $payment->status,
                'order_id' => $payment->order_id,
                'order_status' => $payment->order->status,
            ]
        ]);
    }

    #[OA\Get(
        path: '/payments/{payment}/status',
        summary: 'Statut d\'un paiement',
        description: 'Vérifier le statut d\'un paiement',
        tags: ['Payments'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'payment', in: 'path', required: true, description: 'ID du paiement', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Statut du paiement',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'status', type: 'string', enum: ['pending', 'completed', 'failed', 'cancelled']),
                                new OA\Property(property: 'amount', type: 'integer'),
                                new OA\Property(property: 'payment_method', type: 'string'),
                                new OA\Property(property: 'completed_at', type: 'string', format: 'date-time', nullable: true),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Non authentifié'),
            new OA\Response(response: 404, description: 'Paiement non trouvé'),
        ]
    )]
    public function status(Payment $payment): JsonResponse
    {
        if ($payment->order->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Paiement non trouvé'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $payment->id,
                'status' => $payment->status,
                'amount' => $payment->amount,
                'payment_method' => $payment->payment_method,
                'completed_at' => $payment->completed_at,
            ]
        ]);
    }
}
