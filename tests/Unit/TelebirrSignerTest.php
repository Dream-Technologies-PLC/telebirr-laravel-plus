<?php

namespace DreamTechnologies\TelebirrLaravelPlus\Tests\Unit;

use DreamTechnologies\TelebirrLaravelPlus\Support\TelebirrSigner;
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
}
