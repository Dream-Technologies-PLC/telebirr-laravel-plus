<?php

namespace DreamTechnologies\TelebirrLaravelPlus\Tests\Unit;

use DreamTechnologies\TelebirrLaravelPlus\Support\TelebirrSigner;
use phpseclib3\Crypt\RSA;
use PHPUnit\Framework\TestCase;

class TelebirrSignerTest extends TestCase
{
    public function test_raw_request_sorts_root_and_biz_content_fields(): void
    {
        $signer = new TelebirrSigner();

        $raw = $signer->rawRequest([
            'sign' => 'excluded',
            'sign_type' => 'SHA256WithRSA',
            'timestamp' => '1700000000',
            'biz_content' => [
                'title' => 'Ride',
                'total_amount' => '12.00',
                'empty' => '',
            ],
            'nonce_str' => 'abc',
            'method' => 'payment.preorder',
        ]);

        $this->assertSame(
            'method=payment.preorder&nonce_str=abc&timestamp=1700000000&title=Ride&total_amount=12.00',
            $raw,
        );
    }

    public function test_signs_with_inline_base64_key_and_verifies_pss_signature(): void
    {
        $signer = new TelebirrSigner();
        $privateKey = RSA::createKey(2048);
        $privatePem = $privateKey->toString('PKCS8');
        $privateBase64 = preg_replace('/-----[^-]+-----|\s+/', '', $privatePem);
        $publicPem = $privateKey->getPublicKey()->toString('PKCS8');
        $payload = [
            'method' => 'payment.preorder',
            'nonce_str' => 'abc123',
            'timestamp' => '1700000000',
            'sign_type' => 'SHA256WithRSA',
            'biz_content' => [
                'merch_order_id' => 'ORDER123',
                'total_amount' => '12.00',
            ],
        ];

        $payload['sign'] = $signer->sign($payload, (string) $privateBase64);

        $this->assertTrue($signer->verify($payload, $publicPem));

        $payload['biz_content']['total_amount'] = '13.00';
        $this->assertFalse($signer->verify($payload, $publicPem));
    }
}
