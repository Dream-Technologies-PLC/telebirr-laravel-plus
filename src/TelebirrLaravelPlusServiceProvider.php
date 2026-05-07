<?php

namespace DreamTechnologies\TelebirrLaravelPlus;

use DreamTechnologies\TelebirrLaravelPlus\Contracts\TelebirrClient;
use DreamTechnologies\TelebirrLaravelPlus\Support\TelebirrSigner;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class TelebirrLaravelPlusServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/telebirr.php', 'telebirr');

        $this->app->singleton(TelebirrSigner::class);

        $this->app->singleton(TelebirrClient::class, function (Application $app): TelebirrLaravelPlus {
            return new TelebirrLaravelPlus(
                http: $app->make(HttpFactory::class),
                signer: $app->make(TelebirrSigner::class),
                config: $app['config']->get('telebirr', []),
            );
        });

        $this->app->alias(TelebirrClient::class, 'telebirr');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/telebirr.php' => config_path('telebirr.php'),
        ], 'telebirr-config');

        if ((bool) config('telebirr.routes_enabled', true)) {
            Route::middleware(config('telebirr.route_middleware', ['api']))
                ->prefix(config('telebirr.route_prefix', 'api/telebirr'))
                ->group(__DIR__.'/../routes/telebirr.php');
        }
    }
}
