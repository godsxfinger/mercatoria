<?php

namespace App\Services\Cart;

use App\Models\Cart;
use App\Models\User;

class CartPricingService
{
    public function subtotal(User $user): float
    {
        return (float) Cart::getCartTotal($user);
    }

    public function commission(float $subtotal): float
    {
        return ($subtotal * (float) config('marketplace.commission_percentage')) / 100;
    }

    public function total(User $user): array
    {
        $subtotal = $this->subtotal($user);
        $commission = $this->commission($subtotal);

        return [
            'subtotal' => $subtotal,
            'commission' => $commission,
            'total' => $subtotal + $commission,
        ];
    }
}
