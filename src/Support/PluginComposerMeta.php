<?php

declare(strict_types=1);

namespace AlessandroNuunes\FilamentPlugin\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class PluginComposerMeta
{
    public function __construct(
        public readonly string $namespace,
        public readonly string $composerName,
        public readonly string $viewNamespace,
    ) {}

    /**
     * Create from plugin path (reads composer.json).
     */
    public static function fromPath(string $pluginPath): ?self
    {
        $path = rtrim($pluginPath, '/').'/composer.json';
        if (! File::exists($path)) {
            return null;
        }

        $content = File::get($path);
        $data = json_decode($content, true);

        if (! is_array($data)) {
            return null;
        }

        $psr4 = $data['autoload']['psr-4'] ?? [];
        if (empty($psr4) || ! is_array($psr4)) {
            return null;
        }

        $namespace = (string) array_key_first($psr4);
        $namespace = rtrim($namespace, '\\');
        $composerName = $data['name'] ?? 'unknown/unknown';
        $viewNamespace = Str::after($composerName, '/');

        return new self($namespace, $composerName, $viewNamespace);
    }
}
