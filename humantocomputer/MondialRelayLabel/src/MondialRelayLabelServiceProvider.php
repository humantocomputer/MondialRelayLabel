<?php

namespace Humantocomputer\MondialRelayLabel;

use Illuminate\Support\ServiceProvider;

class MondialRelayLabelServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {

        $this->publishes([
            __DIR__.'/../resources/config/config.php' => config_path('mondial-relay-label.php'),
        ], 'config');

    }

    /**
     * Register the application services.
     */
    public function register(): void
    {
//        $this->mergeConfigFrom(__DIR__.'/../resources/config/config.php', 'mondial-relay-label');
    }
}
