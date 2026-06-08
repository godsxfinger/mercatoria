<?php

namespace App\Actions\Orders;

use App\Models\Order;

class CancelExpiredOrder
{
    public function handle(Order $order): bool
    {
        if (!$order->isExpired() || $order->status !== Order::STATUS_WAITING_PAYMENT) {
            return false;
        }

        return $order->markAsCancelled();
    }
}
