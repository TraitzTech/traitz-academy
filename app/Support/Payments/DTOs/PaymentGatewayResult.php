<?php

namespace App\Support\Payments\DTOs;

class PaymentGatewayResult
{
    /**
     * @param  array<string, mixed>  $rawResponse
     */
    public function __construct(
        public bool $operationSuccessful,
        public bool $transactionSuccessful,
        public ?string $transactionId,
        public ?string $message,
        public array $rawResponse = [],
    ) {}

    public function isSuccessful(): bool
    {
        return $this->operationSuccessful && $this->transactionSuccessful;
    }
}
