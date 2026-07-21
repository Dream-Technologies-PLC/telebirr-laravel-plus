<?php

namespace DreamTechnologies\TelebirrLaravelPlus;

use DreamTechnologies\TelebirrLaravelPlus\Contracts\TelebirrClient;
use DreamTechnologies\TelebirrLaravelPlus\DTO\CreateOrderData;
use DreamTechnologies\TelebirrLaravelPlus\DTO\TelebirrNotification;
use DreamTechnologies\TelebirrLaravelPlus\DTO\TelebirrOrder;
use DreamTechnologies\TelebirrLaravelPlus\Exceptions\TelebirrConfigurationException;
use DreamTechnologies\TelebirrLaravelPlus\Exceptions\TelebirrHttpException;
use DreamTechnologies\TelebirrLaravelPlus\Support\TelebirrSigner;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\ConnectionException;
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
        $numericAmount = (float) $order->amount;
        if (! is_numeric($order->amount) || ! is_finite($numericAmount) || $numericAmount <= 0) {
            throw new TelebirrConfigurationException('Telebirr order amount must be greater than zero.');
        }
        $normalizedAmount = $order->normalizedAmount();
        if (strlen($normalizedAmount) > 20) {
            throw new TelebirrConfigurationException('Telebirr order amount is too large.');
        }
        if (trim($order->title) === '' || strlen($order->title) > 512) {
            throw new TelebirrConfigurationException('Telebirr order title is required.');
        }

        $this->assertConfigured([
            'fabric_app_id',
            'app_secret',
            'merchant_app_id',
            'merchant_code',
        ]);
        $privateKey = $this->privateKey();

        $merchantOrderId = $this->resolveMerchantOrderId($order);
        $token = $this->extractFabricToken($this->applyFabricToken());

        if (! is_string($token) || $token === '') {
            throw new TelebirrHttpException('Telebirr did not return a Fabric Token.');
        }

        $notifyUrl = $this->clientOverride('notify_url', $order->notifyUrl)
            ?: $this->config('notify_url');
        if (! is_string($notifyUrl) || trim($notifyUrl) === '') {
            throw new TelebirrConfigurationException('Missing Telebirr config: notify_url');
        }

        $request = $this->signedRequest([
            'method' => $this->config('method', 'payment.preorder') ?: 'payment.preorder',
            'biz_content' => array_filter(array_merge($order->extra, [
                'notify_url' => $notifyUrl,
                'redirect_url' => $this->clientOverride('redirect_url', $order->redirectUrl)
                    ?: $this->config('redirect_url'),
                'callback_info' => $this->clientOverride('callback_info', $order->callbackInfo),
                'business_type' => $this->config('business_type'),
                'trade_type' => $this->config('trade_type'),
                'appid' => $this->config('merchant_app_id'),
                'merch_code' => $this->config('merchant_code'),
                'merch_order_id' => $merchantOrderId,
                'title' => $order->title,
                'total_amount' => $normalizedAmount,
                'trans_currency' => $this->config('currency'),
                'timeout_express' => $this->config('timeout_express'),
                'payee_identifier' => $this->config('payee_identifier') ?: $this->config('merchant_code'),
                'payee_identifier_type' => $this->config('payee_identifier_type'),
                'payee_type' => $this->config('payee_type'),
            ]), fn ($value) => $value !== null && $value !== ''),
        ], $privateKey);

        $response = $this->post($this->path('create_order'), $request, [
            'X-APP-Key' => $this->config('fabric_app_id'),
            'Authorization' => $token,
        ]);

        $prepayId = $this->stringValue($response, 'biz_content.prepay_id');
        $receiveCode = Arr::get($response, 'biz_content.receiveCode')
            ?: $this->buildReceiveCode($response, $normalizedAmount);
        $success = $this->isSuccessfulResult($response)
            && is_string($receiveCode)
            && trim($receiveCode) !== '';

        $message = $success
            ? 'success'
            : ($this->stringValue($response, 'msg')
                ?: $this->stringValue($response, 'errorMsg')
                ?: ($this->isSuccessfulResult($response)
                    ? 'Telebirr create-order response did not contain a receiveCode or prepay_id.'
                    : 'Telebirr create-order failed'));

        return new TelebirrOrder(
            success: $success,
            merchantOrderId: (string) Arr::get($response, 'biz_content.merch_order_id', $merchantOrderId),
            receiveCode: is_string($receiveCode) ? $receiveCode : null,
            code: $this->stringValue($response, 'code') ?: $this->stringValue($response, 'errorCode'),
            message: $message,
            raw: $response,
            prepayId: $prepayId,
        );
    }

    public function queryOrder(string $merchantOrderId): array
    {
        $this->assertConfigured(['fabric_app_id', 'app_secret', 'merchant_app_id', 'merchant_code']);
        $privateKey = $this->privateKey();

        $token = $this->extractFabricToken($this->applyFabricToken());
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
        ], $privateKey), [
            'X-APP-Key' => $this->config('fabric_app_id'),
            'Authorization' => $token,
        ]);
    }

    public function signedRequest(array $overrides, ?string $privateKey = null): array
    {
        $request = array_replace_recursive([
            'nonce_str' => bin2hex(random_bytes(16)),
            'method' => 'payment.preorder',
            'timestamp' => (string) time(),
            'version' => '1.0',
            'sign_type' => 'SHA256WithRSA',
            'biz_content' => [],
        ], $overrides);

        $request['sign'] = $this->signer->sign($request, $privateKey ?: $this->privateKey());

        return $request;
    }

    public function verifyNotification(array $payload): TelebirrNotification
    {
        $payload = $this->normalizeNotificationPayload($payload);
        $publicKey = $this->callbackPublicKey();
        $signature = $this->stringValue($payload, 'sign')
            ?: $this->stringValue($payload, 'biz_content.sign');
        $signType = $this->stringValue($payload, 'sign_type')
            ?: $this->stringValue($payload, 'biz_content.sign_type');

        if ($publicKey === null) {
            $signatureStatus = 'not_configured';
            $accepted = ! (bool) $this->config('callback_signature_required', true);
        } elseif ($signature === null) {
            $signatureStatus = 'missing';
            $accepted = false;
        } elseif ($signType !== null && strcasecmp($signType, 'SHA256WithRSA') !== 0) {
            $signatureStatus = 'unsupported';
            $accepted = false;
        } else {
            $accepted = $this->signer->verify($payload, $publicKey, $signature);
            $signatureStatus = $accepted ? 'verified' : 'invalid';
        }

        return new TelebirrNotification(
            accepted: $accepted,
            signatureStatus: $signatureStatus,
            merchantOrderId: $this->notificationValue($payload, 'merch_order_id'),
            prepayId: $this->notificationValue($payload, 'prepay_id'),
            paymentOrderId: $this->notificationValue($payload, 'payment_order_id'),
            transactionId: $this->notificationValue($payload, 'trans_id'),
            amount: $this->notificationValue($payload, 'total_amount'),
            currency: $this->notificationValue($payload, 'trans_currency'),
            status: $this->notificationStatus($payload),
            raw: $payload,
        );
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
                ->post($this->url($path), $payload);

            $response->throw();
            $json = $response->json();
        } catch (ConnectionException $exception) {
            throw new TelebirrHttpException(
                message: 'Unable to connect to Telebirr. Please try again.',
                previous: $exception,
            );
        } catch (RequestException $exception) {
            $body = $exception->response?->json() ?: [];
            throw new TelebirrHttpException(
                message: Arr::get($body, 'errorMsg') ?: Arr::get($body, 'msg') ?: $exception->getMessage(),
                telebirrCode: (string) (Arr::get($body, 'errorCode') ?: Arr::get($body, 'code') ?: ''),
                context: $this->redact($body),
                previous: $exception,
            );
        }

        if (! is_array($json)) {
            throw new TelebirrHttpException('Telebirr returned an invalid JSON response.');
        }

        return $json;
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

    private function extractFabricToken(array $payload): ?string
    {
        foreach (['token', 'access_token', 'accessToken', 'biz_content.token'] as $key) {
            $value = $this->stringValue($payload, $key);
            if ($value !== null && $value !== '') {
                return trim($value);
            }
        }

        return null;
    }

    private function privateKey(): string
    {
        $inline = $this->config('private_key');
        if (is_string($inline) && trim($inline) !== '') {
            return $inline;
        }

        $path = $this->config('private_key_path');
        if (is_string($path) && trim($path) !== '') {
            $path = trim($path);
            if (! is_file($path) || ! is_readable($path)) {
                throw new TelebirrConfigurationException(
                    'Telebirr private key file does not exist or is not readable.'
                );
            }

            return $path;
        }

        throw new TelebirrConfigurationException(
            'Missing Telebirr config: private_key or private_key_path'
        );
    }

    private function callbackPublicKey(): ?string
    {
        $inline = $this->config('public_key');
        if (is_string($inline) && trim($inline) !== '') {
            return $inline;
        }

        $path = $this->config('public_key_path');
        if (is_string($path) && trim($path) !== '') {
            $path = trim($path);
            if (! is_file($path) || ! is_readable($path)) {
                throw new TelebirrConfigurationException(
                    'Telebirr callback public key file does not exist or is not readable.'
                );
            }

            return $path;
        }

        return null;
    }

    private function clientOverride(string $field, ?string $value): ?string
    {
        if (! (bool) $this->config('allow_client_'.$field, false)) {
            return null;
        }

        return $value !== null && trim($value) !== '' ? trim($value) : null;
    }

    private function normalizeNotificationPayload(array $payload): array
    {
        $bizContent = $payload['biz_content'] ?? null;
        if (is_string($bizContent)) {
            $decoded = json_decode($bizContent, true);
            if (is_array($decoded)) {
                $payload['biz_content'] = $decoded;
            }
        }

        return $payload;
    }

    private function notificationValue(array $payload, string $key): ?string
    {
        return $this->stringValue($payload, $key)
            ?: $this->stringValue($payload, 'biz_content.'.$key);
    }

    private function notificationStatus(array $payload): string
    {
        $status = strtoupper(trim((string) (
            $this->notificationValue($payload, 'trade_status')
            ?: $this->notificationValue($payload, 'payment_status')
            ?: $this->notificationValue($payload, 'order_status')
            ?: $this->notificationValue($payload, 'pay_status')
            ?: ''
        )));

        if (in_array($status, ['SUCCESS', 'PAY_SUCCESS', 'PAID', 'COMPLETED', 'SETTLED', '2'], true)) {
            return 'completed';
        }
        if (in_array($status, ['CANCELLED', 'CANCELED', '-3'], true)) {
            return 'cancelled';
        }
        if (in_array($status, ['EXPIRED', 'CLOSED', 'ORDER_CLOSED'], true)) {
            return 'expired';
        }
        if (in_array($status, ['FAILURE', 'FAILED', 'PAY_FAILED'], true)) {
            return 'failed';
        }
        if (in_array($status, ['PAYING', 'PENDING', 'PROCESSING'], true)) {
            return 'pending';
        }

        return 'unknown';
    }

    private function stringValue(array $payload, string $key): ?string
    {
        $value = Arr::get($payload, $key);
        if ($value === null || $value === '' || (! is_scalar($value) && ! ($value instanceof \Stringable))) {
            return null;
        }

        return (string) $value;
    }

    private function isSuccessfulResult(array $payload): bool
    {
        return strtoupper((string) Arr::get($payload, 'result', '')) === 'SUCCESS'
            || (string) Arr::get($payload, 'code', '') === '0';
    }

    private function redact(array $payload): array
    {
        foreach ($payload as $key => $value) {
            if (in_array(strtolower((string) $key), ['sign', 'token', 'appsecret', 'app_secret'], true)) {
                $payload[$key] = '[redacted]';
            } elseif (is_array($value)) {
                $payload[$key] = $this->redact($value);
            }
        }

        return $payload;
    }

    private function url(string $path): string
    {
        $baseUrl = (string) ($this->config('base_url')
            ?: Arr::get($this->config, 'base_urls.'.$this->config('environment', 'test')));
        if (trim($baseUrl) === '') {
            throw new TelebirrConfigurationException('Telebirr base URL is not configured.');
        }

        return rtrim($baseUrl, '/')
            .'/'.ltrim($path, '/');
    }

    private function path(string $name): string
    {
        $path = (string) Arr::get($this->config, 'paths.'.$name);
        if (trim($path) === '') {
            throw new TelebirrConfigurationException("Missing Telebirr API path: {$name}");
        }

        return $path;
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
