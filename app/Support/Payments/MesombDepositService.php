<?php

namespace App\Support\Payments;

use DateTime;
use MeSomb\MeSomb;
use MeSomb\Operation\PaymentOperation;
use MeSomb\Signature;
use MeSomb\Util\RandomGenerator;

class MesombDepositService
{
    /**
     * Get MeSomb credentials or throw if not configured.
     *
     * @return array{application_key: string, access_key: string, secret_key: string}
     *
     * @throws \RuntimeException
     */
    private function credentials(): array
    {
        $applicationKey = (string) config('services.mesomb.application_key');
        $accessKey = (string) config('services.mesomb.access_key');
        $secretKey = (string) config('services.mesomb.secret_key');

        if ($applicationKey === '' || $accessKey === '' || $secretKey === '') {
            throw new \RuntimeException('MeSomb credentials are not configured.');
        }

        return [
            'application_key' => $applicationKey,
            'access_key' => $accessKey,
            'secret_key' => $secretKey,
        ];
    }

    /**
     * Make a deposit to a mobile money account via MeSomb.
     *
     * @param  array{
     *     amount: int,
     *     service: string,
     *     receiver: string,
     *     country?: string,
     *     currency?: string,
     *     trxID?: string,
     * }  $params
     *
     * @throws \RuntimeException
     */
    public function deposit(array $params): \MeSomb\Model\TransactionResponse
    {
        $creds = $this->credentials();

        $client = new PaymentOperation($creds['application_key'], $creds['access_key'], $creds['secret_key']);

        return $client->makeDeposit($params);
    }

    /**
     * Fetch the current MeSomb application balance.
     *
     * @return array{total: float, balances: array<int, array{provider: string, country: string, value: float}>}
     *
     * @throws \RuntimeException
     */
    public function getBalance(): array
    {
        $creds = $this->credentials();

        $client = new PaymentOperation($creds['application_key'], $creds['access_key'], $creds['secret_key']);

        $application = $client->getStatus();

        $balances = array_map(fn ($b) => [
            'provider' => $b->provider ?? '',
            'country' => $b->country ?? '',
            'value' => (float) ($b->value ?? 0),
        ], $application->balances ?? []);

        return [
            'total' => (float) $application->getBalance(),
            'balances' => array_values($balances),
        ];
    }

    /**
     * Look up the account holder name for a mobile money number via MeSomb Contact Info API.
     *
     * @param  array{provider: string, service_key: string, country?: string}  $params
     * @return array{first_name: string, last_name: string}
     *
     * @throws \RuntimeException
     */
    public function getContactInfo(array $params): array
    {
        $creds = $this->credentials();

        $endpoint = 'payment/contact/info/';
        $apiBase = MeSomb::$apiBase;
        $apiVersion = MeSomb::$apiVersion;
        $url = "$apiBase/api/$apiVersion/$endpoint";

        $body = [
            'provider' => $params['provider'],
            'service_key' => $params['service_key'],
            'country' => $params['country'] ?? 'CM',
        ];

        $date = new DateTime;
        $nonce = RandomGenerator::nonce();

        $authorization = Signature::signRequest(
            'payment',
            'POST',
            $url,
            $date,
            $nonce,
            ['accessKey' => $creds['access_key'], 'secretKey' => $creds['secret_key']],
            ['content-type' => 'application/json'],
            $body,
        );

        $response = \Illuminate\Support\Facades\Http::asJson()
            ->withHeaders([
                'x-mesomb-date' => (string) $date->getTimestamp(),
                'x-mesomb-nonce' => $nonce,
                'X-MeSomb-Application' => $creds['application_key'],
                'X-MeSomb-Source' => 'MeSombPHP/v'.MeSomb::$version,
                'Accept-Language' => 'en',
                'Authorization' => $authorization,
            ])->post($url, $body);

        if ($response->failed()) {
            $message = $response->json('detail') ?? $response->json('message') ?? $response->body();
            throw new \RuntimeException('Account lookup failed: '.$message);
        }

        /** @var array{first_name: string, last_name: string} $data */
        $data = $response->json();

        return [
            'first_name' => $data['first_name'] ?? '',
            'last_name' => $data['last_name'] ?? '',
        ];
    }
}
