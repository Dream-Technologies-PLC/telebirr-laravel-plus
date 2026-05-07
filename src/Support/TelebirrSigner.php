<?php

namespace DreamTechnologies\TelebirrLaravelPlus\Support;

use DreamTechnologies\TelebirrLaravelPlus\Exceptions\TelebirrConfigurationException;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Crypt\RSA;

final class TelebirrSigner
{
    /**
     * Telebirr signs the alphabetically sorted root fields plus flattened
     * biz_content fields. Signature metadata fields are excluded.
     */
    public function rawRequest(array $request): string
    {
        $pairs = [];
        $excluded = ['sign', 'sign_type', 'header', 'refund_info', 'openType', 'raw_request'];

        foreach ($request as $key => $value) {
            if (in_array($key, $excluded, true) || $value === null || $value === '') {
                continue;
            }

            if ($key === 'biz_content' && is_array($value)) {
                foreach ($value as $bizKey => $bizValue) {
                    if ($bizValue !== null && $bizValue !== '') {
                        $pairs[$bizKey] = $bizValue;
                    }
                }
                continue;
            }

            $pairs[$key] = $value;
        }

        ksort($pairs, SORT_STRING);

        return collect($pairs)
            ->map(fn ($value, $key) => $key.'='.$value)
            ->implode('&');
    }

    public function sign(array $request, string $privateKeyPath): string
    {
        if (! is_file($privateKeyPath) || ! is_readable($privateKeyPath)) {
            throw new TelebirrConfigurationException('Telebirr private key file was not found or is not readable.');
        }

        $keyContents = file_get_contents($privateKeyPath);
        if ($keyContents === false || trim($keyContents) === '') {
            throw new TelebirrConfigurationException('Telebirr private key file is empty.');
        }

        $privateKey = PublicKeyLoader::loadPrivateKey($keyContents)
            ->withHash('sha256')
            ->withMGFHash('sha256')
            ->withPadding(RSA::SIGNATURE_PSS);

        return base64_encode($privateKey->sign($this->rawRequest($request)));
    }
}
