<?php

namespace DreamTechnologies\TelebirrLaravelPlus\Facades;

use DreamTechnologies\TelebirrLaravelPlus\Contracts\TelebirrClient;
use DreamTechnologies\TelebirrLaravelPlus\DTO\CreateOrderData;
use DreamTechnologies\TelebirrLaravelPlus\DTO\TelebirrOrder;
use Illuminate\Support\Facades\Facade;

/**
 * @method static array applyFabricToken()
 * @method static TelebirrOrder createOrder(CreateOrderData $order)
 * @method static array queryOrder(string $merchantOrderId)
 */
class Telebirr extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return TelebirrClient::class;
    }
}
