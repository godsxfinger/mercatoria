<?php

namespace App\Services\Payments;

class RefundResult
{
    public function __construct(
        public readonly bool $successful,
        public readonly ?float $amount = null,
        public readonly ?string $address = null,
        public readonly ?string $message = null,
    ) {
    }
}
