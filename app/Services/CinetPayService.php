<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CinetPayService
{
    private string $apiKey;
    private string $siteId;
    private string $secretKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('cinetpay.api_key');
        $this->siteId = config('cinetpay.site_id');
        $this->secretKey = config('cinetpay.secret_key');
        $this->baseUrl = config('cinetpay.base_url');
    }

    public function initializePayment(Order $order): array
    {
        $transactionId = $this->generateTransactionId();

        $payment = Payment::create([
            'order_id' => $order->id,
            'transaction_id' => $transactionId,
            'amount' => $order->total,
            'currency' => 'XOF',
            'status' => 'pending',
        ]);

        $data = [
            'apikey' => $this->apiKey,
            'site_id' => $this->siteId,
            'transaction_id' => $transactionId,
            'amount' => $order->total,
            'currency' => 'XOF',
            'description' => "Commande {$order->order_number}",
            'notify_url' => config('cinetpay.notify_url'),
            'return_url' => config('cinetpay.return_url') . "?transaction_id={$transactionId}",
            'cancel_url' => config('cinetpay.cancel_url'),
            'channels' => 'ALL',
            'lang' => 'fr',
            'customer_id' => $order->user_id,
            'customer_name' => $order->customer_first_name,
            'customer_surname' => $order->customer_last_name,
            'customer_email' => $order->customer_email,
            'customer_phone_number' => $order->customer_phone,
            'customer_address' => $order->shipping_address,
            'customer_city' => $order->shipping_city,
            'customer_country' => 'BF',
            'metadata' => json_encode([
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ]),
        ];

        $response = Http::post("{$this->baseUrl}/payment", $data);

        if (!$response->successful()) {
            Log::error('CinetPay API Error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception('Erreur lors de la communication avec CinetPay');
        }

        $result = $response->json();

        if ($result['code'] !== '201') {
            throw new \Exception($result['message'] ?? 'Erreur CinetPay');
        }

        $payment->update([
            'payment_token' => $result['data']['payment_token'] ?? null,
        ]);

        return [
            'payment_id' => $payment->id,
            'transaction_id' => $transactionId,
            'payment_url' => $result['data']['payment_url'],
            'payment_token' => $result['data']['payment_token'] ?? null,
        ];
    }

    public function handleNotification(array $data): bool
    {
        $transactionId = $data['cpm_trans_id'] ?? null;

        if (!$transactionId) {
            throw new \Exception('Transaction ID manquant');
        }

        $checkData = [
            'apikey' => $this->apiKey,
            'site_id' => $this->siteId,
            'transaction_id' => $transactionId,
        ];

        $response = Http::post(config('cinetpay.check_url'), $checkData);

        if (!$response->successful()) {
            throw new \Exception('Erreur lors de la vérification du paiement');
        }

        $result = $response->json();

        Log::info('CinetPay Check Result', $result);

        $payment = Payment::where('transaction_id', $transactionId)->first();

        if (!$payment) {
            throw new \Exception('Paiement non trouvé');
        }

        $status = $result['data']['status'] ?? 'UNKNOWN';
        $paymentMethod = $result['data']['payment_method'] ?? null;
        $operator = $result['data']['operator_id'] ?? null;

        $payment->update([
            'metadata' => $result['data'],
            'payment_method' => $paymentMethod,
            'operator' => $operator,
        ]);

        if ($status === 'ACCEPTED') {
            $payment->markAsCompleted();
            $payment->order->markAsPaid();
            return true;
        } elseif (in_array($status, ['REFUSED', 'CANCELLED'])) {
            $payment->markAsFailed($result['data']['description'] ?? 'Paiement refusé');
            return false;
        }

        return false;
    }

    public function checkPaymentStatus(string $transactionId): array
    {
        $data = [
            'apikey' => $this->apiKey,
            'site_id' => $this->siteId,
            'transaction_id' => $transactionId,
        ];

        $response = Http::post(config('cinetpay.check_url'), $data);

        if (!$response->successful()) {
            throw new \Exception('Erreur lors de la vérification');
        }

        return $response->json();
    }

    private function generateTransactionId(): string
    {
        return 'KKN_' . Str::upper(Str::random(16)) . '_' . time();
    }
}
