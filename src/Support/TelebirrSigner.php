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
                    if (! in_array($bizKey, $excluded, true) && $bizValue !== null && $bizValue !== '') {
                        $pairs[] = $bizKey.'='.$this->scalarString($bizValue);
                    }
                }
                continue;
            }

            $pairs[] = $key.'='.$this->scalarString($value);
        }

        sort($pairs, SORT_STRING);

        return implode('&', $pairs);
    }

    /**
     * Signs a Telebirr request with an inline PEM/base64 key or a key file.
     */
    public function sign(array $request, string $privateKeyOrPath): string
    {
        try {
            $privateKey = PublicKeyLoader::loadPrivateKey(
                $this->keyContents($privateKeyOrPath, private: true)
            )
                ->withHash('sha256')
                ->withMGFHash('sha256')
                ->withSaltLength(32)
                ->withPadding(RSA::SIGNATURE_PSS);
        } catch (TelebirrConfigurationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw new TelebirrConfigurationException(
                'Telebirr private key could not be loaded.',
                previous: $exception,
            );
        }

        return base64_encode($privateKey->sign($this->rawRequest($request)));
    }

    /**
     * Verifies a callback signed with SHA-256 using PKCS#1 or RSA-PSS.
     */
    public function verify(array $payload, string $publicKeyOrPath, ?string $signature = null): bool
    {
        $signature ??= is_string($payload['sign'] ?? null) ? $payload['sign'] : null;
        if ($signature === null || trim($signature) === '') {
            return false;
        }

        $decodedSignature = base64_decode(trim($signature), true);
        if ($decodedSignature === false) {
            return false;
        }

        try {
            $publicKey = PublicKeyLoader::loadPublicKey(
                $this->keyContents($publicKeyOrPath, private: false)
            )->withHash('sha256');

            $verifiers = [
                $publicKey->withPadding(RSA::SIGNATURE_PKCS1),
                $publicKey
                    ->withMGFHash('sha256')
                    ->withSaltLength(32)
                    ->withPadding(RSA::SIGNATURE_PSS),
            ];
        } catch (TelebirrConfigurationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw new TelebirrConfigurationException(
                'Telebirr callback public key could not be loaded.',
                previous: $exception,
            );
        }

        foreach ($verifiers as $verifier) {
            if ($verifier->verify($this->rawRequest($payload), $decodedSignature)) {
                return true;
            }
        }

        return false;
    }

    private function keyContents(string $keyOrPath, bool $private): string
    {
        $value = trim(str_replace('\\n', "\n", $keyOrPath));
        if ($value === '') {
            throw new TelebirrConfigurationException(
                $private
                    ? 'Telebirr private key is not configured.'
                    : 'Telebirr callback public key is not configured.'
            );
        }

        if (is_file($value)) {
            if (! is_readable($value)) {
                throw new TelebirrConfigurationException('Telebirr key file is not readable.');
            }

            $contents = file_get_contents($value);
            if ($contents === false || trim($contents) === '') {
                throw new TelebirrConfigurationException('Telebirr key file is empty.');
            }

            return $contents;
        }

        if (str_contains($value, '-----BEGIN')) {
            return $value;
        }

        $compact = preg_replace('/\s+/', '', $value) ?: '';
        if ($compact === '' || base64_decode($compact, true) === false) {
            throw new TelebirrConfigurationException('Telebirr key is not valid PEM or base64 data.');
        }

        $label = $private ? 'PRIVATE KEY' : 'PUBLIC KEY';

        return "-----BEGIN {$label}-----\n"
            .chunk_split($compact, 64, "\n")
            ."-----END {$label}-----\n";
    }

    private function scalarString(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_scalar($value) || $value instanceof \Stringable) {
            return (string) $value;
        }

        throw new TelebirrConfigurationException('Telebirr signing values must be scalar.');
    }
}
