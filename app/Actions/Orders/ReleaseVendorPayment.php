<?php

namespace App\Actions\Orders;

use App\Models\Order;

class ReleaseVendorPayment
{
    public function handle(Order $order): bool
    {
        return $order->markAsCompleted();
    }
}
