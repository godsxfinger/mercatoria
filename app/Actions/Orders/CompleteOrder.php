<?php

namespace App\Actions\Orders;

use App\Models\Order;

class CompleteOrder
{
    public function handle(Order $order): bool
    {
        return $order->markAsCompleted();
    }
}
