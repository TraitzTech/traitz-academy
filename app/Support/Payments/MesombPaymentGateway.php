<?php

namespace App\Support\Payments;

use App\Support\Payments\Contracts\PaymentGateway;
use App\Support\Payments\DTOs\PaymentGatewayResult;
use MeSomb\Operation\PaymentOperation;
use RuntimeException;

class MesombPaymentGateway implements PaymentGateway
{
    public function collect(array $payload): PaymentGatewayResult
    {
        $applicationKey = (string) config('services.mesomb.application_key');
        $accessKey = (string) config('services.mesomb.access_key');
        $secretKey = (string) config('services.mesomb.secret_key');

        if ($applicationKey === '' || $accessKey === '' || $secretKey === '') {
            throw new RuntimeException('MeSomb credentials are not configured.');
        }

        $client = new PaymentOperation($applicationKey, $accessKey, $secretKey);
        $response = $client->makeCollect($payload);

        $operationSuccessful = method_exists($response, 'isOperationSuccess')
            ? (bool) $response->isOperationSuccess()
            : false;

        $transactionSuccessful = method_exists($response, 'isTransactionSuccess')
            ? (bool) $response->isTransactionSuccess()
            : false;

        $transactionId = null;
        if (method_exists($response, 'getTransaction')) {
            $transaction = $response->getTransaction();
            if (is_array($transaction)) {
                $transactionId = $transaction['id'] ?? $transaction['pk'] ?? null;
            }
            if (is_object($transaction)) {
                $transactionId = $transaction->id ?? $transaction->pk ?? null;
            }
        }

        $message = method_exists($response, 'getMessage')
            ? (string) $response->getMessage()
            : ($transactionSuccessful ? 'Payment completed.' : 'Payment failed at provider.');

        $encoded = json_encode($response);
        $rawResponse = is_string($encoded)
            ? (json_decode($encoded, true) ?: ['raw' => $encoded])
            : ['raw' => null];

        return new PaymentGatewayResult(
            operationSuccessful: $operationSuccessful,
            transactionSuccessful: $transactionSuccessful,
            transactionId: $transactionId,
            message: $message,
            rawResponse: $rawResponse,
        );
    }
}
