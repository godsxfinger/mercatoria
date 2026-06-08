<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function view(User $user, Order $order): bool
    {
        return $user->isAdmin() || $user->id === $order->user_id || $user->id === $order->vendor_id;
    }

    public function update(User $user, Order $order): bool
    {
        return $user->isAdmin() || $user->id === $order->vendor_id;
    }

    public function complete(User $user, Order $order): bool
    {
        return $user->isAdmin() || $user->id === $order->user_id;
    }

    public function cancel(User $user, Order $order): bool
    {
        return $user->isAdmin() || $user->id === $order->user_id || $user->id === $order->vendor_id;
    }
}
