<?php

declare(strict_types=1);

namespace AlessandroNuunes\FilamentPlugin\Support;

use Illuminate\Support\Facades\File;

class PluginPageRegistrar
{
    public function __construct(
        private string $pluginPath
    ) {}

    /**
     * Register page class in Plugin ->pages([...]).
     */
    public function register(string $pageClassFqn): bool
    {
        $pluginFile = $this->findPluginFile();
        if ($pluginFile === null) {
            return false;
        }

        $content = File::get($pluginFile);

        if (str_contains($content, '->discoverPages(')) {
            return true;
        }

        if (! str_contains($content, '->pages([')) {
            return false;
        }

        if (str_contains($content, $pageClassFqn)) {
            return true;
        }

        $insertLine = '            \\'.$pageClassFqn.'::class,';
        $pos = strpos($content, '->pages([');
        if ($pos === false) {
            return false;
        }
        $afterPages = $pos + strlen('->pages([');
        $bracketClose = strpos($content, ']', $afterPages);
        if ($bracketClose === false) {
            return false;
        }

        $before = substr($content, 0, $bracketClose);
        $after = substr($content, $bracketClose);
        $newContent = $before."\n".$insertLine."\n        ".$after;

        File::put($pluginFile, $newContent);

        return true;
    }

    public function usesDiscoverPages(): bool
    {
        $pluginFile = $this->findPluginFile();
        if ($pluginFile === null) {
            return false;
        }

        return str_contains(File::get($pluginFile), '->discoverPages(');
    }

    public function hasPagesArray(): bool
    {
        $pluginFile = $this->findPluginFile();
        if ($pluginFile === null) {
            return false;
        }

        return str_contains(File::get($pluginFile), '->pages([');
    }

    private function findPluginFile(): ?string
    {
        $pluginFiles = File::glob($this->pluginPath.'/src/*Plugin.php');

        return $pluginFiles[0] ?? null;
    }
}
