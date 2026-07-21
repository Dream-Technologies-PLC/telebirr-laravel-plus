<?php

namespace DreamTechnologies\TelebirrLaravelPlus\Contracts;

use DreamTechnologies\TelebirrLaravelPlus\DTO\CreateOrderData;
use DreamTechnologies\TelebirrLaravelPlus\DTO\TelebirrOrder;
use DreamTechnologies\TelebirrLaravelPlus\DTO\TelebirrNotification;

interface TelebirrClient
{
    public function applyFabricToken(): array;

    public function createOrder(CreateOrderData $order): TelebirrOrder;

    public function queryOrder(string $merchantOrderId): array;

    public function verifyNotification(array $payload): TelebirrNotification;
}
