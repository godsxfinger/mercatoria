<?php

namespace App\Actions\Orders;

use App\Models\Cart;
use App\Models\Order;
use App\Models\User;
use App\Services\Cart\CartPricingService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CreateOrder
{
    public function __construct(private CartPricingService $pricing)
    {
    }

    /**
     * @param Collection<int, Cart> $cartItems
     */
    public function handle(User $user, Collection $cartItems): Order
    {
        if ($cartItems->isEmpty()) {
            throw new \InvalidArgumentException('Cannot create an order from an empty cart.');
        }

        $vendorId = $cartItems->first()->product->user_id;
        [$canCreate, $reason] = Order::canCreateNewOrder($user->id, $vendorId);

        if (!$canCreate) {
            throw new \RuntimeException($reason);
        }

        $totals = $this->pricing->total($user);

        return DB::transaction(function () use ($user, $cartItems, $totals) {
            $order = Order::createFromCart(
                $user,
                $cartItems,
                $totals['subtotal'],
                $totals['commission'],
                $totals['total']
            );

            Cart::where('user_id', $user->id)->delete();

            return $order;
        });
    }
}
