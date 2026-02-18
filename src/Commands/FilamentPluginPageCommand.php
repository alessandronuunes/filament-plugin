<?php

declare(strict_types=1);

namespace AlessandroNuunes\FilamentPlugin\Commands;

use AlessandroNuunes\FilamentPlugin\Concerns\ProcessesStubFiles;
use AlessandroNuunes\FilamentPlugin\Concerns\ResolvesFilamentVersion;
use AlessandroNuunes\FilamentPlugin\Support\PluginComposerMeta;
use AlessandroNuunes\FilamentPlugin\Support\PluginPageRegistrar;
use AlessandroNuunes\FilamentPlugin\Support\PluginPathResolver;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

use function Laravel\Prompts\confirm;

class FilamentPluginPageCommand extends Command
{
    use ProcessesStubFiles;
    use ResolvesFilamentVersion;

    protected $signature = 'filament-plugin:page
        {name : The page class name in PascalCase}
        {--plugin= : The plugin name in PascalCase (e.g. FilamentTabbedDashboard)}
        {--filament= : Filament version (3, 4, 5, or 4|5)}
        {--force : Overwrite existing files}
        {--register : Register the page in the Plugin class}
        {--panel= : Panel name (comment only)}';

    protected $description = 'Create a Filament page class and view inside an existing plugin';

    public function handle(): int
    {
        $pluginPath = $this->resolvePluginPath();
        if ($pluginPath === null) {
            return self::FAILURE;
        }

        $meta = PluginComposerMeta::fromPath($pluginPath);
        if ($meta === null) {
            $this->error('Invalid composer.json in plugin directory.');

            return self::FAILURE;
        }

        $pageName = $this->resolvePageName();
        if ($pageName === null) {
            return self::FAILURE;
        }

        $filamentVersion = $this->resolveFilamentVersion($this->option('filament'));
        if ($filamentVersion === null) {
            return self::FAILURE;
        }

        $pageSlug = Str::kebab(Str::camel($pageName));
        $pagesNamespace = $meta->namespace.'\\Pages';
        $classFqn = $pagesNamespace.'\\'.$pageName;
        $viewName = $meta->viewNamespace.'::filament.pages.'.$pageSlug;
        $pageFilePath = $pluginPath.'/src/Pages/'.$pageName.'.php';
        $viewFilePath = $pluginPath.'/resources/views/filament/pages/'.$pageSlug.'.blade.php';

        if (! $this->option('force') && $this->filesExist($pageFilePath, $viewFilePath)) {
            return self::FAILURE;
        }

        File::ensureDirectoryExists(dirname($pageFilePath));
        File::ensureDirectoryExists(dirname($viewFilePath));

        $this->writePageClass($pageFilePath, $pageName, $pagesNamespace, $viewName, $filamentVersion);
        $this->writePageView($viewFilePath, $filamentVersion);

        $this->components->info("Filament page [{$classFqn}] created successfully.");
        $this->line("  Class: {$pageFilePath}");
        $this->line("  View:  {$viewFilePath}");

        $this->maybeRegisterPage($pluginPath, $classFqn);

        return self::SUCCESS;
    }

    private function resolvePluginPath(): ?string
    {
        $pluginOption = $this->option('plugin');
        if (blank($pluginOption)) {
            $this->error('Option --plugin is required (e.g. --plugin=FilamentTabbedDashboard).');

            return null;
        }

        $pluginPath = PluginPathResolver::resolve((string) $pluginOption);
        if ($pluginPath === null) {
            $slug = PluginPathResolver::toSlug((string) $pluginOption);
            $basePath = base_path(config('filament-plugin.packages_path', 'packages'));
            $this->error("Plugin not found: [{$basePath}/{$slug}].");
            $this->line('  Use the plugin name in PascalCase (e.g. FilamentTabbedDashboard).');

            return null;
        }

        return $pluginPath;
    }

    private function resolvePageName(): ?string
    {
        $name = (string) str($this->argument('name'))->trim()->studly();

        if ($name === '') {
            $this->error('Page name cannot be empty.');

            return null;
        }

        return $name;
    }

    private function filesExist(string $pageFilePath, string $viewFilePath): bool
    {
        if (File::exists($pageFilePath)) {
            $this->error("Page class already exists: [{$pageFilePath}]. Use --force to overwrite.");

            return true;
        }
        if (File::exists($viewFilePath)) {
            $this->error("View already exists: [{$viewFilePath}]. Use --force to overwrite.");

            return true;
        }

        return false;
    }

    private function writePageClass(string $path, string $className, string $namespace, string $viewName, string $filamentVersion): void
    {
        $panel = $this->option('panel');
        $panelComment = filled($panel) ? "Panel: {$panel}" : '';

        $replacements = [
            '{{NAMESPACE}}' => $namespace,
            '{{CLASS_NAME}}' => $className,
            '{{VIEW_NAME}}' => $viewName,
            '{{PANEL_COMMENT}}' => $panelComment,
        ];

        $stubName = 'page/PageClass.Filament'.($filamentVersion === '4|5' ? '5' : $filamentVersion).'.stub';
        $stubPath = $this->getStubsBasePath().'/'.$stubName;

        if (! File::exists($stubPath)) {
            $stubPath = $this->getStubsBasePath().'/page/PageClass.Filament5.stub';
        }

        app(\AlessandroNuunes\FilamentPlugin\Support\StubProcessor::class)->process($stubPath, $path, $replacements, []);
    }

    private function writePageView(string $path, string $filamentVersion): void
    {
        $stubVersion = $filamentVersion === '4|5' ? '5' : $filamentVersion;
        $stubName = 'page/PageView.Filament'.$stubVersion.'.stub';
        $stubPath = $this->getStubsBasePath().'/'.$stubName;

        if (! File::exists($stubPath)) {
            $stubPath = $this->getStubsBasePath().'/page/PageView.Filament5.stub';
        }

        $content = File::get($stubPath);
        File::put($path, $content);
    }

    private function maybeRegisterPage(string $pluginPath, string $classFqn): void
    {
        $shouldRegister = $this->option('register')
            ?: (! $this->option('no-interaction') && confirm('Register this page in the Plugin class?', false));

        if (! $shouldRegister) {
            return;
        }

        $registrar = new PluginPageRegistrar($pluginPath);

        if ($registrar->usesDiscoverPages()) {
            $this->line('  Plugin uses discoverPages(); page will be discovered automatically.');

            return;
        }

        if (! $registrar->hasPagesArray()) {
            $this->warn('Plugin file has no ->pages([...]). Add the page manually to the Plugin register() method.');

            return;
        }

        if ($registrar->register($classFqn)) {
            $this->components->info('Page registered in Plugin.');
        } else {
            $this->warn('Could not register page in Plugin file.');
        }
    }
}
