<?php

declare(strict_types=1);

namespace AlessandroNuunes\FilamentPlugin\Concerns;

use AlessandroNuunes\FilamentPlugin\Support\PluginDiscoverer;

trait DiscoversFilamentPlugins
{
    /**
     * Discover Filament plugins in the project packages path.
     *
     * @return array<int, array{path: string, composer: array<string, mixed>, name: string, slug: string}>
     */
    protected function discoverPlugins(): array
    {
        return PluginDiscoverer::discover();
    }
}
