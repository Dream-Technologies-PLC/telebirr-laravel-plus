<?php

namespace DreamTechnologies\TelebirrLaravelPlus\Events;

use DreamTechnologies\TelebirrLaravelPlus\DTO\TelebirrNotification;

class TelebirrNotificationReceived
{
    public function __construct(
        public readonly array $payload,
        public readonly ?TelebirrNotification $notification = null,
    ) {
    }
}
