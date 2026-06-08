<?php

namespace App\Services\Payments;

class PaymentRequest
{
    public function __construct(
        public readonly string $address,
        public readonly ?int $addressIndex = null,
        public readonly ?\DateTimeInterface $expiresAt = null,
    ) {
    }
}
