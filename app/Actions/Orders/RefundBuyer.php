<?php

namespace App\Actions\Orders;

use App\Models\Order;
use App\Services\Payments\PaymentGateway;

class RefundBuyer
{
    public function __construct(private PaymentGateway $payments)
    {
    }

    public function handle(Order $order): bool
    {
        $refund = $this->payments->refund($order);

        if (!$refund->successful) {
            return false;
        }

        $order->forceFill([
            'status' => Order::STATUS_REFUNDED,
            'is_disputed' => false,
            'buyer_refund_amount' => $refund->amount,
            'buyer_refund_address' => $refund->address,
            'buyer_refund_at' => now(),
        ])->save();

        return true;
    }
}
