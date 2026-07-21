<?php

namespace DreamTechnologies\TelebirrLaravelPlus\DTO;

final class TelebirrOrder
{
    public function __construct(
        public readonly bool $success,
        public readonly string $merchantOrderId,
        public readonly ?string $receiveCode,
        public readonly ?string $code,
        public readonly string $message,
        public readonly array $raw = [],
        public readonly ?string $prepayId = null,
    ) {
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'merchantOrderId' => $this->merchantOrderId,
            'receiveCode' => $this->receiveCode,
            'code' => $this->code,
            'message' => $this->message,
            'prepayId' => $this->prepayId,
            'raw' => $this->raw,
        ];
    }
}
