<?php

namespace DreamTechnologies\TelebirrLaravelPlus\DTO;

final class CreateOrderData
{
    public function __construct(
        public readonly string $title,
        public readonly string|float|int $amount,
        public readonly ?string $merchantOrderId = null,
        public readonly ?string $notifyUrl = null,
        public readonly ?string $redirectUrl = null,
        public readonly ?string $callbackInfo = null,
        public readonly array $extra = [],
    ) {
    }

    public function normalizedAmount(): string
    {
        return number_format((float) $this->amount, 2, '.', '');
    }
}
