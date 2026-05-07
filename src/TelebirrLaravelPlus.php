<?php

namespace DreamTechnologies\TelebirrLaravelPlus;

use DreamTechnologies\TelebirrLaravelPlus\Contracts\TelebirrClient;
use DreamTechnologies\TelebirrLaravelPlus\DTO\CreateOrderData;
use DreamTechnologies\TelebirrLaravelPlus\DTO\TelebirrOrder;
use DreamTechnologies\TelebirrLaravelPlus\Exceptions\TelebirrConfigurationException;
use DreamTechnologies\TelebirrLaravelPlus\Exceptions\TelebirrHttpException;
use DreamTechnologies\TelebirrLaravelPlus\Support\TelebirrSigner;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

final class TelebirrLaravelPlus implements TelebirrClient
{
    public function __construct(
        private readonly HttpFactory $http,
        private readonly TelebirrSigner $signer,
        private readonly array $config,
    ) {
    }

    public function applyFabricToken(): array
    {
        $this->assertConfigured(['fabric_app_id', 'app_secret']);

        return $this->post($this->path('token'), [
            'appSecret' => $this->config('app_secret'),
        ], [
            'X-APP-Key' => $this->config('fabric_app_id'),
        ]);
    }

    public function createOrder(CreateOrderData $order): TelebirrOrder
    {
        $this->assertConfigured([
            'fabric_app_id',
            'app_secret',
            'merchant_app_id',
            'merchant_code',
            'private_key_path',
        ]);

        $merchantOrderId = $this->resolveMerchantOrderId($order);
        $token = Arr::get($this->applyFabricToken(), 'token');

        if (! is_string($token) || $token === '') {
            throw new TelebirrHttpException('Telebirr did not return a Fabric Token.');
        }

        $request = $this->signedRequest([
            'biz_content' => array_filter(array_merge([
                'notify_url' => $order->notifyUrl ?: $this->config('notify_url'),
                'redirect_url' => $order->redirectUrl ?: $this->config('redirect_url'),
                'callback_info' => $order->callbackInfo,
                'business_type' => $this->config('business_type'),
                'trade_type' => $this->config('trade_type'),
                'appid' => $this->config('merchant_app_id'),
                'merch_code' => $this->config('merchant_code'),
                'merch_order_id' => $merchantOrderId,
                'title' => $order->title,
                'total_amount' => $order->normalizedAmount(),
                'trans_currency' => $this->config('currency'),
                'timeout_express' => $this->config('timeout_express'),
                'payee_identifier' => $this->config('payee_identifier') ?: $this->config('merchant_code'),
                'payee_identifier_type' => $this->config('payee_identifier_type'),
                'payee_type' => $this->config('payee_type'),
            ], $order->extra), fn ($value) => $value !== null && $value !== ''),
        ]);

        $response = $this->post($this->path('create_order'), $request, [
            'X-APP-Key' => $this->config('fabric_app_id'),
            'Authorization' => $token,
        ]);

        $success = Arr::get($response, 'result') === 'SUCCESS' || Arr::get($response, 'code') === '0';
        $receiveCode = Arr::get($response, 'biz_content.receiveCode')
            ?: $this->buildReceiveCode($response, $order->normalizedAmount());

        return new TelebirrOrder(
            success: $success,
            merchantOrderId: (string) Arr::get($response, 'biz_content.merch_order_id', $merchantOrderId),
            receiveCode: $receiveCode,
            code: Arr::get($response, 'code') ?: Arr::get($response, 'errorCode'),
            message: $success
                ? 'success'
                : (Arr::get($response, 'msg') ?: Arr::get($response, 'errorMsg') ?: 'Telebirr create-order failed'),
            raw: $response,
        );
    }

    public function queryOrder(string $merchantOrderId): array
    {
        $this->assertConfigured(['fabric_app_id', 'app_secret', 'merchant_app_id', 'merchant_code', 'private_key_path']);

        $token = Arr::get($this->applyFabricToken(), 'token');
        if (! is_string($token) || $token === '') {
            throw new TelebirrHttpException('Telebirr did not return a Fabric Token.');
        }

        return $this->post($this->path('query_order'), $this->signedRequest([
            'method' => 'payment.queryorder',
            'biz_content' => [
                'appid' => $this->config('merchant_app_id'),
                'merch_code' => $this->config('merchant_code'),
                'merch_order_id' => $merchantOrderId,
            ],
        ]), [
            'X-APP-Key' => $this->config('fabric_app_id'),
            'Authorization' => $token,
        ]);
    }

    public function signedRequest(array $overrides): array
    {
        $request = array_replace_recursive([
            'nonce_str' => bin2hex(random_bytes(16)),
            'method' => 'payment.preorder',
            'timestamp' => (string) time(),
            'version' => '1.0',
            'sign_type' => 'SHA256WithRSA',
            'biz_content' => [],
        ], $overrides);

        $request['sign'] = $this->signer->sign($request, (string) $this->config('private_key_path'));

        return $request;
    }

    private function post(string $path, array $payload, array $headers = []): array
    {
        try {
            $response = $this->http
                ->timeout((int) $this->config('http_timeout', 45))
                ->when(! $this->config('verify_ssl', true), fn ($pending) => $pending->withoutVerifying())
                ->acceptJson()
                ->asJson()
                ->withHeaders($headers)
                ->post($this->url($path), $payload)
                ->throw()
                ->json();
        } catch (RequestException $exception) {
            $body = $exception->response?->json() ?: [];
            throw new TelebirrHttpException(
                message: Arr::get($body, 'errorMsg') ?: Arr::get($body, 'msg') ?: $exception->getMessage(),
                telebirrCode: Arr::get($body, 'errorCode') ?: Arr::get($body, 'code'),
                context: $body,
                previous: $exception,
            );
        }

        if (! is_array($response)) {
            throw new TelebirrHttpException('Telebirr returned an invalid JSON response.');
        }

        return $response;
    }

    private function resolveMerchantOrderId(CreateOrderData $order): string
    {
        if ($this->config('allow_client_merchant_order_id') && $order->merchantOrderId) {
            return $order->merchantOrderId;
        }

        return (string) floor(microtime(true) * 1000).Str::random(4);
    }

    private function buildReceiveCode(array $response, string $amount): ?string
    {
        $prepayId = Arr::get($response, 'biz_content.prepay_id');
        if (! $prepayId) {
            return null;
        }

        return implode('$', [
            'TELEBIRR',
            strtoupper((string) $this->config('business_type', 'BuyGoods')),
            $this->config('merchant_code'),
            $amount,
            $prepayId,
            $this->config('timeout_express', '120m'),
        ]);
    }

    private function url(string $path): string
    {
        return rtrim((string) ($this->config('base_url') ?: Arr::get($this->config, 'base_urls.'.$this->config('environment', 'test'))), '/')
            .'/'.ltrim($path, '/');
    }

    private function path(string $name): string
    {
        return (string) Arr::get($this->config, 'paths.'.$name);
    }

    private function config(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->config, $key, $default);
    }

    private function assertConfigured(array $keys): void
    {
        foreach ($keys as $key) {
            if (! $this->config($key)) {
                throw new TelebirrConfigurationException("Missing Telebirr config: {$key}");
            }
        }
    }
}
