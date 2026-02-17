<?php

namespace App\Support\Payments\Contracts;

use App\Support\Payments\DTOs\PaymentGatewayResult;

interface PaymentGateway
{
    public function collect(array $payload): PaymentGatewayResult;
}
