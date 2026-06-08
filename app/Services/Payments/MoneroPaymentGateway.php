<?php

namespace App\Services\Payments;

use App\Models\Order;
use Illuminate\Support\Facades\Log;
use MoneroIntegrations\MoneroPhp\walletRPC;

class MoneroPaymentGateway implements PaymentGateway
{
    public function __construct(private ?walletRPC $walletRPC = null)
    {
    }

    public function createPaymentAddress(Order $order): PaymentRequest
    {
        if ($order->payment_address) {
            return new PaymentRequest(
                $order->payment_address,
                $order->payment_address_index,
                $order->expires_at
            );
        }

        $result = $this->wallet()->create_address(0, 'Order Payment ' . $order->id);
        $expiresAt = now()->addMinutes((int) config('monero.address_expiration_time', 1440));

        $order->forceFill([
            'payment_address' => $result['address'],
            'payment_address_index' => $result['address_index'],
            'expires_at' => $expiresAt,
        ])->save();

        return new PaymentRequest($result['address'], $result['address_index'], $expiresAt);
    }

    public function checkPaymentStatus(Order $order): PaymentStatus
    {
        if ($order->is_paid || $order->status !== Order::STATUS_WAITING_PAYMENT) {
            return new PaymentStatus((bool) $order->is_paid, (float) $order->total_received_xmr, $order->paid_at);
        }

        $transfers = $this->wallet()->get_transfers([
            'in' => true,
            'pool' => true,
            'subaddr_indices' => [$order->payment_address_index],
        ]);

        $minAcceptedAmount = (float) $order->required_xmr_amount * 0.10;
        $totalReceived = 0.0;

        foreach (['in', 'pool'] as $type) {
            foreach (($transfers[$type] ?? []) as $transfer) {
                $amount = ((float) $transfer['amount']) / 1e12;
                if ($amount >= $minAcceptedAmount) {
                    $totalReceived += $amount;
                }
            }
        }

        return new PaymentStatus(
            $totalReceived >= (float) $order->required_xmr_amount,
            $totalReceived,
            $totalReceived >= (float) $order->required_xmr_amount ? now() : null
        );
    }

    public function refund(Order $order): RefundResult
    {
        if (!$order->total_received_xmr || $order->total_received_xmr <= 0) {
            return new RefundResult(false, message: 'No received funds to refund.');
        }

        $returnAddress = $order->user?->returnAddresses()->inRandomOrder()->first();
        if (!$returnAddress) {
            return new RefundResult(false, message: 'Buyer has no return address.');
        }

        $commissionPercentage = (float) config('monero.cancelled_order_commission_percentage', 1.0);
        $refundAmount = (float) $order->total_received_xmr * (1 - ($commissionPercentage / 100));

        try {
            $this->wallet()->transfer([
                'address' => $returnAddress->monero_address,
                'amount' => $refundAmount,
                'priority' => 1,
            ]);
        } catch (\Throwable $e) {
            Log::error("Refund failed for order {$order->id}: " . $e->getMessage());

            return new RefundResult(false, message: $e->getMessage());
        }

        return new RefundResult(true, $refundAmount, $returnAddress->monero_address);
    }

    private function wallet(): walletRPC
    {
        if ($this->walletRPC) {
            return $this->walletRPC;
        }

        $config = config('monero');

        return $this->walletRPC = new walletRPC(
            $config['host'],
            $config['port'],
            $config['ssl'],
            $config['username'] ?? null,
            $config['password'] ?? null
        );
    }
}
