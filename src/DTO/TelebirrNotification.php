<?php

namespace DreamTechnologies\TelebirrLaravelPlus\DTO;

final class TelebirrNotification
{
    public function __construct(
        public readonly bool $accepted,
        public readonly string $signatureStatus,
        public readonly ?string $merchantOrderId,
        public readonly ?string $prepayId,
        public readonly ?string $paymentOrderId,
        public readonly ?string $transactionId,
        public readonly ?string $amount,
        public readonly ?string $currency,
        public readonly string $status,
        public readonly array $raw = [],
    ) {
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Checks callback identity and value fields against a server-owned order.
     */
    public function matches(
        string $merchantOrderId,
        string|float|int $amount,
        string $currency = 'ETB',
    ): bool {
        if ($this->amount === null || ! is_numeric($this->amount) || ! is_numeric($amount)) {
            return false;
        }

        return hash_equals($merchantOrderId, (string) $this->merchantOrderId)
            && number_format((float) $amount, 2, '.', '') === number_format((float) $this->amount, 2, '.', '')
            && $this->currency !== null
            && strtoupper($currency) === strtoupper((string) $this->currency);
    }

    public function toArray(): array
    {
        return [
            'accepted' => $this->accepted,
            'signatureStatus' => $this->signatureStatus,
            'merchantOrderId' => $this->merchantOrderId,
            'prepayId' => $this->prepayId,
            'paymentOrderId' => $this->paymentOrderId,
            'transactionId' => $this->transactionId,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'status' => $this->status,
        ];
    }
}
