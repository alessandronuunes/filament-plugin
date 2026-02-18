<?php

declare(strict_types=1);

namespace AlessandroNuunes\FilamentPlugin;

use Illuminate\Support\ServiceProvider;

class FilamentPluginServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/filament-plugin.php', 'filament-plugin');

        if ($this->app->runningInConsole()) {
            $this->commands([
                Commands\MakeFilamentPluginCommand::class,
                Commands\FilamentPluginRegisterCommand::class,
                Commands\FilamentPluginPageCommand::class,
            ]);

            $this->publishes([
                __DIR__.'/../config/filament-plugin.php' => config_path('filament-plugin.php'),
            ], 'filament-plugin-config');
        }
    }
}
