<?php

namespace App\Services\Orders;

use App\Models\Order;

class OrderStateService
{
    public const ALLOWED_TRANSITIONS = [
        Order::STATUS_WAITING_PAYMENT => [
            Order::STATUS_PAYMENT_RECEIVED,
            Order::STATUS_CANCELLED,
        ],
        Order::STATUS_PAYMENT_RECEIVED => [
            Order::STATUS_PRODUCT_SENT,
            Order::STATUS_REFUNDED,
        ],
        Order::STATUS_PRODUCT_SENT => [
            Order::STATUS_COMPLETED,
            Order::STATUS_DISPUTED,
        ],
        Order::STATUS_DISPUTED => [
            Order::STATUS_COMPLETED,
            Order::STATUS_REFUNDED,
        ],
    ];

    public function transition(Order $order, string $toStatus): Order
    {
        $fromStatus = $order->status;

        if (!$this->canTransition($fromStatus, $toStatus)) {
            throw new InvalidOrderTransitionException(
                "Cannot transition order {$order->id} from {$fromStatus} to {$toStatus}."
            );
        }

        $order->status = $toStatus;
        $this->syncFlags($order, $toStatus);
        $order->save();

        return $order;
    }

    public function canTransition(string $fromStatus, string $toStatus): bool
    {
        return in_array($toStatus, self::ALLOWED_TRANSITIONS[$fromStatus] ?? [], true);
    }

    private function syncFlags(Order $order, string $status): void
    {
        match ($status) {
            Order::STATUS_PAYMENT_RECEIVED => $order->forceFill([
                'is_paid' => true,
                'paid_at' => $order->paid_at ?? now(),
                'payment_completed_at' => $order->payment_completed_at ?? now(),
            ]),
            Order::STATUS_PRODUCT_SENT => $order->forceFill([
                'is_sent' => true,
                'sent_at' => $order->sent_at ?? now(),
            ]),
            Order::STATUS_COMPLETED => $order->forceFill([
                'is_completed' => true,
                'is_disputed' => false,
                'completed_at' => $order->completed_at ?? now(),
            ]),
            Order::STATUS_DISPUTED => $order->forceFill([
                'is_disputed' => true,
                'disputed_at' => $order->disputed_at ?? now(),
            ]),
            Order::STATUS_REFUNDED, Order::STATUS_CANCELLED => $order->forceFill([
                'is_disputed' => false,
            ]),
            default => null,
        };
    }
}
