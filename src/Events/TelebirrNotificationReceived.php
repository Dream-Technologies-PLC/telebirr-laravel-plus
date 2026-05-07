<?php

namespace DreamTechnologies\TelebirrLaravelPlus\Events;

class TelebirrNotificationReceived
{
    public function __construct(public readonly array $payload)
    {
    }
}
