<?php

namespace DreamTechnologies\TelebirrLaravelPlus\Tests;

use DreamTechnologies\TelebirrLaravelPlus\TelebirrLaravelPlusServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [TelebirrLaravelPlusServiceProvider::class];
    }
}
