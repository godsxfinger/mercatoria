<?php

namespace App\Services\Payments;

use App\Models\Order;

interface PaymentGateway
{
    public function createPaymentAddress(Order $order): PaymentRequest;

    public function checkPaymentStatus(Order $order): PaymentStatus;

    public function refund(Order $order): RefundResult;
}
