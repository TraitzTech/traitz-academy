<?php

namespace App\Support\Payments;

use App\Support\Payments\Contracts\PaymentGateway;
use App\Support\Payments\DTOs\PaymentGatewayResult;
use Error;
use Illuminate\Support\Facades\Log;
use MeSomb\Exception\InvalidClientRequestException;
use MeSomb\Exception\PermissionDeniedException;
use MeSomb\Exception\ServerException;
use MeSomb\Exception\ServiceNotFoundException;
use MeSomb\Exception\UnexpectedValueException;
use MeSomb\Operation\PaymentOperation;
use RuntimeException;
use Throwable;

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

        try {
            $client = new PaymentOperation($applicationKey, $accessKey, $secretKey);
            $response = $client->makeCollect($payload);
        } catch (Throwable $e) {
            Log::warning('MeSomb makeCollect failed', [
                'message' => $e->getMessage(),
                'exception' => $e::class,
            ]);

            return new PaymentGatewayResult(
                operationSuccessful: false,
                transactionSuccessful: false,
                transactionId: null,
                message: $this->userMessageForCollectThrowable($e),
                rawResponse: [
                    'error' => $e::class,
                    'message' => $e->getMessage(),
                ],
            );
        }

        $operationSuccessful = method_exists($response, 'isOperationSuccess')
            ? (bool) $response->isOperationSuccess()
            : false;

        $transactionSuccessful = method_exists($response, 'isTransactionSuccess')
            ? (bool) $response->isTransactionSuccess()
            : false;

        $transactionId = $this->extractTransactionId($response);

        $message = $this->extractResponseMessage($response, $transactionSuccessful);

        $rawResponse = $this->encodeRawResponse($response);

        return new PaymentGatewayResult(
            operationSuccessful: $operationSuccessful,
            transactionSuccessful: $transactionSuccessful,
            transactionId: $transactionId,
            message: $message,
            rawResponse: $rawResponse,
        );
    }

    private function userMessageForCollectThrowable(Throwable $e): string
    {
        if ($e instanceof InvalidClientRequestException
            || $e instanceof PermissionDeniedException
            || $e instanceof ServerException
            || $e instanceof ServiceNotFoundException
            || $e instanceof UnexpectedValueException) {
            return $this->trimProviderMessage($e->getMessage());
        }

        if ($e instanceof \AssertionError) {
            return 'The payment amount is invalid. Please refresh the page and try again.';
        }

        if ($e instanceof Error) {
            return 'The payment provider response could not be processed. Please try again or contact support if this continues.';
        }

        return 'The payment provider returned an unexpected response. Please try again or contact support if this continues.';
    }

    private function extractResponseMessage(object $response, bool $transactionSuccessful): string
    {
        if (method_exists($response, 'getMessage')) {
            return (string) $response->getMessage();
        }

        if (property_exists($response, 'message') && $response->message !== null && $response->message !== '') {
            return (string) $response->message;
        }

        return $transactionSuccessful ? 'Payment completed.' : 'Payment failed at provider.';
    }

    /**
     * Keep the full provider message (MeSomb often puts a title on line 1 and details below).
     */
    private function trimProviderMessage(string $message): string
    {
        $message = trim($message);
        $max = 1200;

        if (function_exists('mb_strlen') && mb_strlen($message) > $max) {
            return mb_substr($message, 0, $max - 3).'...';
        }

        if (strlen($message) > $max) {
            return substr($message, 0, $max - 3).'...';
        }

        return $message;
    }

    /**
     * @param  object  $response
     */
    private function extractTransactionId($response): ?string
    {
        $transaction = null;
        if (method_exists($response, 'getTransaction')) {
            $transaction = $response->getTransaction();
        } elseif (property_exists($response, 'transaction')) {
            $transaction = $response->transaction;
        }

        if (is_array($transaction)) {
            $id = $transaction['id'] ?? $transaction['pk'] ?? null;

            return $id !== null ? (string) $id : null;
        }

        if (is_object($transaction)) {
            $id = $transaction->id ?? $transaction->pk ?? null;

            return $id !== null ? (string) $id : null;
        }

        return null;
    }

    /**
     * @param  object  $response
     * @return array<string, mixed>
     */
    private function encodeRawResponse($response): array
    {
        try {
            $flags = JSON_THROW_ON_ERROR;
            if (defined('JSON_PARTIAL_OUTPUT_ON_ERROR')) {
                $flags |= JSON_PARTIAL_OUTPUT_ON_ERROR;
            }
            $encoded = json_encode($response, $flags);
            $decoded = json_decode($encoded, true, 512, JSON_THROW_ON_ERROR);

            return is_array($decoded) ? $decoded : ['raw' => $encoded];
        } catch (Throwable $e) {
            Log::warning('MeSomb response could not be encoded for storage', [
                'message' => $e->getMessage(),
            ]);

            return ['encode_error' => $e->getMessage()];
        }
    }
}
