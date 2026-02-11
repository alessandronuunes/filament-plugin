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
        if ($this->app->runningInConsole()) {
            $this->commands([
                Commands\MakeFilamentPluginCommand::class,
            ]);
        }
    }
}
