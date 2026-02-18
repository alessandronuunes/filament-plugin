<?php

declare(strict_types=1);

namespace AlessandroNuunes\FilamentPlugin\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class PluginPathResolver
{
    /**
     * Resolve plugin directory path from PascalCase name (e.g. FilamentTabbedDashboard).
     */
    public static function resolve(string $pluginName, ?string $basePath = null): ?string
    {
        $basePath = $basePath ?? base_path(config('filament-plugin.packages_path', 'packages'));
        $slug = str_contains($pluginName, '-')
            ? $pluginName
            : Str::kebab($pluginName);
        $path = $basePath.'/'.$slug;

        if (! is_dir($path) || ! File::exists($path.'/composer.json')) {
            return null;
        }

        return $path;
    }

    /**
     * Get package slug from plugin name (e.g. FilamentTabbedDashboard → filament-tabbed-dashboard).
     */
    public static function toSlug(string $pluginName): string
    {
        return str_contains($pluginName, '-')
            ? $pluginName
            : Str::kebab($pluginName);
    }
}
