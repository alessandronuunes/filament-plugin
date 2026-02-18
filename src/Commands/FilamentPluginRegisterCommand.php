<?php

declare(strict_types=1);

namespace AlessandroNuunes\FilamentPlugin\Commands;

use AlessandroNuunes\FilamentPlugin\Concerns\RegistersPluginInComposer;
use AlessandroNuunes\FilamentPlugin\Support\PluginComposerMeta;
use AlessandroNuunes\FilamentPlugin\Support\PluginPathResolver;
use Illuminate\Console\Command;

class FilamentPluginRegisterCommand extends Command
{
    use RegistersPluginInComposer;

    protected $signature = 'filament-plugin:register
        {plugin : The plugin name in PascalCase (e.g. FilamentTabbedDashboard)}';

    protected $description = 'Add an existing plugin to project composer.json and run composer update';

    public function handle(): int
    {
        $pluginName = (string) $this->argument('plugin');
        $pluginPath = PluginPathResolver::resolve($pluginName);

        if ($pluginPath === null) {
            $this->reportPluginNotFound($pluginName);

            return self::FAILURE;
        }

        $meta = PluginComposerMeta::fromPath($pluginPath);
        if ($meta === null) {
            $this->error('Invalid or missing composer.json in plugin directory.');

            return self::FAILURE;
        }

        $packagesPath = config('filament-plugin.packages_path', 'packages');
        $packageSlug = basename($pluginPath);
        $repoUrl = $packagesPath.'/'.$packageSlug;

        $registered = $this->registerPluginInComposer($meta->composerName, $repoUrl);

        if ($registered) {
            $this->components->info("Plugin [{$meta->composerName}] registered successfully.");
        }

        return $registered ? self::SUCCESS : self::FAILURE;
    }

    private function reportPluginNotFound(string $pluginName): void
    {
        $slug = PluginPathResolver::toSlug($pluginName);
        $basePath = base_path(config('filament-plugin.packages_path', 'packages'));
        $this->error("Plugin not found: [{$basePath}/{$slug}].");
        $this->line('  Use the plugin name in PascalCase (e.g. FilamentTabbedDashboard).');
    }
}
