<?php

declare(strict_types=1);

namespace AlessandroNuunes\FilamentPlugin\Support;

use Illuminate\Support\Facades\File;

class PluginDiscoverer
{
    /**
     * Discover Filament plugins in the project packages path
     * (directories with composer.json that require filament/filament).
     *
     * @return array<int, array{path: string, composer: array<string, mixed>, name: string, slug: string}>
     */
    public static function discover(?string $packagesPath = null): array
    {
        $packagesPath = $packagesPath ?? base_path(config('filament-plugin.packages_path', 'packages'));

        if (! is_dir($packagesPath)) {
            return [];
        }

        $plugins = [];

        foreach (File::directories($packagesPath) as $dir) {
            $composerPath = $dir.DIRECTORY_SEPARATOR.'composer.json';

            if (! File::exists($composerPath)) {
                continue;
            }

            $json = File::get($composerPath);
            $data = json_decode($json, true);

            if (! is_array($data)) {
                continue;
            }

            $require = $data['require'] ?? [];
            $requireDev = $data['require-dev'] ?? [];
            $hasFilament = isset($require['filament/filament']) || isset($requireDev['filament/filament']);

            if (! $hasFilament) {
                continue;
            }

            $name = $data['name'] ?? basename($dir);
            $plugins[] = [
                'path' => $dir,
                'composer' => $data,
                'name' => $name,
                'slug' => basename($dir),
            ];
        }

        return $plugins;
    }
}
