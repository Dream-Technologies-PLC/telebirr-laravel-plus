<?php

namespace DreamTechnologies\TelebirrLaravelPlus\Tests\Feature;

use DreamTechnologies\TelebirrLaravelPlus\Contracts\TelebirrClient;
use DreamTechnologies\TelebirrLaravelPlus\DTO\CreateOrderData;
use DreamTechnologies\TelebirrLaravelPlus\Support\TelebirrSigner;
use DreamTechnologies\TelebirrLaravelPlus\Tests\TestCase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use phpseclib3\Crypt\RSA;

class TelebirrLaravelPlusTest extends TestCase
{
    private string $privateKey;

    private string $publicKey;

    protected function setUp(): void
    {
        parent::setUp();

        $key = RSA::createKey(2048);
        $this->privateKey = $key->toString('PKCS8');
        $this->publicKey = $key->getPublicKey()->toString('PKCS8');

        config()->set('telebirr.base_url', 'https://telebirr.example/gateway');
        config()->set('telebirr.fabric_app_id', 'fabric-app-id');
        config()->set('telebirr.app_secret', 'backend-secret');
        config()->set('telebirr.merchant_app_id', 'merchant-app-id');
        config()->set('telebirr.merchant_code', '772770');
        config()->set('telebirr.private_key', $this->privateKey);
        config()->set('telebirr.private_key_path', null);
        config()->set('telebirr.notify_url', 'https://merchant.example/api/telebirr/notify');
        config()->set('telebirr.redirect_url', 'merchantapp://payment-return');
        config()->set('telebirr.business_type', 'BuyGoods');
        config()->set('telebirr.trade_type', 'Cross-App');
        config()->set('telebirr.payee_identifier', '772770');
        config()->set('telebirr.payee_identifier_type', '04');
        config()->set('telebirr.payee_type', '5000');
        config()->set('telebirr.verify_ssl', true);
    }

    public function test_creates_order_with_inline_key_and_normalized_token(): void
    {
        Http::fake(function (Request $request) {
            if (str_ends_with($request->url(), '/payment/v1/token')) {
                return Http::response(['accessToken' => 'Bearer fabric-token'], 200);
            }

            if (str_ends_with($request->url(), '/payment/v1/inapp/createOrder')) {
                return Http::response([
                    'result' => 'SUCCESS',
                    'code' => '0',
                    'msg' => 'success',
                    'biz_content' => [
                        'merch_order_id' => $request['biz_content']['merch_order_id'],
                        'prepay_id' => 'PREPAY123',
                    ],
                ], 200);
            }

            return Http::response([], 404);
        });

        $order = app(TelebirrClient::class)->createOrder(new CreateOrderData(
            title: 'Driver wallet deposit',
            amount: '125.50',
            extra: [
                'appid' => 'untrusted-app-id',
                'merch_code' => '999999',
                'notify_url' => 'https://untrusted.example/notify',
                'total_amount' => '0.01',
                'custom_reference' => 'SAFE123',
            ],
        ));

        $this->assertTrue($order->success);
        $this->assertSame('PREPAY123', $order->prepayId);
        $this->assertSame(
            'TELEBIRR$BUYGOODS$772770$125.50$PREPAY123$120m',
            $order->receiveCode,
        );

        Http::assertSent(function (Request $request): bool {
            if (! str_ends_with($request->url(), '/payment/v1/inapp/createOrder')) {
                return false;
            }

            return $request->hasHeader('X-APP-Key', 'fabric-app-id')
                && $request->hasHeader('Authorization', 'Bearer fabric-token')
                && $request['method'] === 'payment.preorder'
                && $request['sign_type'] === 'SHA256WithRSA'
                && $request['biz_content']['notify_url'] === 'https://merchant.example/api/telebirr/notify'
                && $request['biz_content']['appid'] === 'merchant-app-id'
                && $request['biz_content']['merch_code'] === '772770'
                && $request['biz_content']['total_amount'] === '125.50'
                && $request['biz_content']['custom_reference'] === 'SAFE123';
        });
    }

    public function test_verifies_and_normalizes_signed_callback(): void
    {
        config()->set('telebirr.public_key', $this->publicKey);
        config()->set('telebirr.callback_signature_required', true);

        $payload = [
            'merch_order_id' => 'ORDER123',
            'payment_order_id' => 'PAYMENT123',
            'trans_id' => 'TRANS123',
            'total_amount' => '125.50',
            'trans_currency' => 'ETB',
            'trade_status' => 'Completed',
            'sign_type' => 'SHA256WithRSA',
        ];
        $payload['sign'] = app(TelebirrSigner::class)->sign($payload, $this->privateKey);

        $notification = app(TelebirrClient::class)->verifyNotification($payload);

        $this->assertTrue($notification->accepted);
        $this->assertSame('verified', $notification->signatureStatus);
        $this->assertTrue($notification->isCompleted());
        $this->assertTrue($notification->matches('ORDER123', '125.50', 'ETB'));
        $this->assertFalse($notification->matches('ORDER123', '125.51', 'ETB'));
    }

    public function test_rejects_missing_signature_when_required(): void
    {
        config()->set('telebirr.public_key', $this->publicKey);
        config()->set('telebirr.callback_signature_required', true);

        $notification = app(TelebirrClient::class)->verifyNotification([
            'merch_order_id' => 'ORDER123',
            'trade_status' => 'Completed',
        ]);

        $this->assertFalse($notification->accepted);
        $this->assertSame('missing', $notification->signatureStatus);
    }

    public function test_rejects_callback_when_required_public_key_is_not_configured(): void
    {
        config()->set('telebirr.public_key', null);
        config()->set('telebirr.public_key_path', null);
        config()->set('telebirr.callback_signature_required', true);

        $notification = app(TelebirrClient::class)->verifyNotification([
            'merch_order_id' => 'ORDER123',
            'trade_status' => 'Completed',
        ]);

        $this->assertFalse($notification->accepted);
        $this->assertSame('not_configured', $notification->signatureStatus);
    }
}
