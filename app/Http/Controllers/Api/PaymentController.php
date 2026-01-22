<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Services\CinetPayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(
        private CinetPayService $cinetpay
    ) {}

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
