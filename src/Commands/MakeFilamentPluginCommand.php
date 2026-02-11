<?php

declare(strict_types=1);

namespace AlessandroNuunes\FilamentPlugin\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeFilamentPluginCommand extends Command
{
    protected $signature = 'make:filament-plugin
        {name : The plugin name in PascalCase (e.g. FilamentMember)}
        {--path=packages : Base path for the plugin directory}
        {--force : Overwrite if the directory already exists}';

    protected $description = 'Scaffold a new Filament v4 plugin in packages/';

    /**
     * @var array<string, string>
     */
    private array $replacements = [];

    private function getStubsPath(): string
    {
        return __DIR__.'/../../resources/stubs/filament-plugin';
    }

    public function handle(): int
    {
        $name = (string) $this->argument('name');

        if (! preg_match('/^[A-Z][a-zA-Z0-9]+$/', $name)) {
            $this->error('Plugin name must be PascalCase (e.g. FilamentMember).');

            return self::FAILURE;
        }

        $data = $this->collectData($name);
        $pluginPath = base_path($this->option('path').'/'.$data['package_slug']);

        if (File::isDirectory($pluginPath) && ! $this->option('force')) {
            $this->error("Directory [{$pluginPath}] already exists. Use --force to overwrite.");

            return self::FAILURE;
        }

        $this->buildReplacements($data);
        $this->scaffoldPlugin($pluginPath, $data);

        $this->newLine();
        $this->components->info("Plugin [{$data['composer_name']}] created at [{$pluginPath}]");
        $this->printNextSteps($data);

        return self::SUCCESS;
    }

    /**
     * @return array<string, mixed>
     */
    private function collectData(string $name): array
    {
        $vendor = $this->ask('Vendor namespace (PascalCase)', 'AlessandroNuunes');
        $packageSlug = $this->ask('Package slug (kebab-case)', Str::kebab($name));
        $description = $this->ask('Short description', "A Filament plugin for {$name}.");
        $authorName = $this->ask('Author name', $vendor);
        $authorEmail = $this->ask('Author email', '');

        $this->newLine();
        $this->line('  <fg=gray>panel: adds pages, resources, widgets to a Filament panel (generates Plugin class).</>');
        $this->line('  <fg=gray>standalone: reusable components (form fields, columns) for any context (no Plugin class).</>');
        $this->newLine();
        $type = $this->choice('Plugin type', [
            'panel' => 'panel — for Panel (pages, resources, widgets, tenancy)',
            'standalone' => 'standalone — for reusable components (form fields, table columns)',
        ], 'panel');

        $withConfig = $this->confirm('Include config file?', true);
        $withViews = $this->confirm('Include views?', true);
        $withTranslations = $this->confirm('Include translations (en + pt_BR)?', true);
        $withMigrations = $this->confirm('Include migrations directory?', false);
        $withInstallCommand = $this->confirm('Include install command?', true);

        return [
            'name' => $name,
            'vendor' => $vendor,
            'vendor_slug' => Str::kebab($vendor),
            'package_slug' => $packageSlug,
            'namespace' => $vendor.'\\'.$name,
            'composer_name' => Str::kebab($vendor).'/'.$packageSlug,
            'description' => $description,
            'author_name' => $authorName,
            'author_email' => $authorEmail,
            'type' => $type,
            'with_config' => $withConfig,
            'with_views' => $withViews,
            'with_translations' => $withTranslations,
            'with_migrations' => $withMigrations,
            'with_install_command' => $withInstallCommand,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function buildReplacements(array $data): void
    {
        $this->replacements = [
            '{{NAMESPACE}}' => $data['namespace'],
            '{{NAMESPACE_ESCAPED}}' => str_replace('\\', '\\\\', $data['namespace']),
            '{{CLASS_NAME}}' => $data['name'],
            '{{PLUGIN_ID}}' => $data['package_slug'],
            '{{VENDOR_SLUG}}' => $data['vendor_slug'],
            '{{PACKAGE_SLUG}}' => $data['package_slug'],
            '{{COMPOSER_NAME}}' => $data['composer_name'],
            '{{DESCRIPTION}}' => $data['description'],
            '{{AUTHOR_NAME}}' => $data['author_name'],
            '{{AUTHOR_EMAIL}}' => $data['author_email'] !== '' ? $data['author_email'] : 'the maintainer (see composer.json or README)',
            '{{CONFIG_KEY}}' => $data['package_slug'],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function scaffoldPlugin(string $path, array $data): void
    {
        $this->components->task('Creating directory structure', function () use ($path, $data): void {
            File::ensureDirectoryExists($path.'/src');

            if ($data['with_config']) {
                File::ensureDirectoryExists($path.'/config');
            }
            if ($data['with_views']) {
                File::ensureDirectoryExists($path.'/resources/views/filament/pages');
                File::ensureDirectoryExists($path.'/resources/views/livewire');
            }
            if ($data['with_translations']) {
                File::ensureDirectoryExists($path.'/resources/lang/en');
                File::ensureDirectoryExists($path.'/resources/lang/pt_BR');
            }
            if ($data['with_migrations']) {
                File::ensureDirectoryExists($path.'/database/migrations');
            }
            if ($data['with_install_command']) {
                File::ensureDirectoryExists($path.'/src/Console/Commands');
            }

            File::ensureDirectoryExists($path.'/src/Support');
            File::ensureDirectoryExists($path.'/.github');
        });

        $this->components->task('Generating composer.json', function () use ($path, $data): void {
            $this->writeStub('composer.stub', $path.'/composer.json', $data);
        });

        $this->components->task('Generating ServiceProvider', function () use ($path, $data): void {
            $this->writeStub('service-provider.stub', $path.'/src/'.$data['name'].'ServiceProvider.php', $data);
        });

        if ($data['type'] === 'panel') {
            $this->components->task('Generating Plugin class', function () use ($path, $data): void {
                $this->writeStub('plugin.stub', $path.'/src/'.$data['name'].'Plugin.php', $data);
            });
        }

        if ($data['with_config']) {
            $this->components->task('Generating config file', function () use ($path, $data): void {
                $this->writeStub('config.stub', $path.'/config/'.$data['package_slug'].'.php', $data);
            });
        }

        if ($data['with_translations']) {
            $this->components->task('Generating translation files', function () use ($path, $data): void {
                $this->writeStub('lang-en.stub', $path.'/resources/lang/en/default.php', $data);
                $this->writeStub('lang-pt-br.stub', $path.'/resources/lang/pt_BR/default.php', $data);
            });
        }

        if ($data['with_install_command']) {
            $this->components->task('Generating install command', function () use ($path, $data): void {
                $this->writeStub('install-command.stub', $path.'/src/Console/Commands/InstallCommand.php', $data);
            });
        }

        $this->components->task('Generating Support/ConfigHelper', function () use ($path, $data): void {
            $this->writeStub('config-helper.stub', $path.'/src/Support/ConfigHelper.php', $data);
        });

        $this->components->task('Generating README.md', function () use ($path, $data): void {
            $this->writeStub('readme.stub', $path.'/README.md', $data);
        });

        $this->components->task('Generating metadata files', function () use ($path): void {
            $this->writeStub('gitignore.stub', $path.'/.gitignore');
            $this->writeStub('pint.stub', $path.'/pint.json');
            $this->writeStub('changelog.stub', $path.'/CHANGELOG.md');
            $this->writeStub('license.stub', $path.'/LICENSE.md');
            $this->writeStub('github-contributing.stub', $path.'/.github/CONTRIBUTING.md');
            $this->writeStub('github-funding.stub', $path.'/.github/FUNDING.yml');
            $this->writeStub('github-security.stub', $path.'/.github/SECURITY.md');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function writeStub(string $stub, string $destination, array $data = []): void
    {
        $stubPath = $this->getStubsPath().'/'.$stub;

        if (! File::exists($stubPath)) {
            $this->warn("Stub [{$stub}] not found at [{$stubPath}].");

            return;
        }

        $content = File::get($stubPath);
        $content = str_replace(array_keys($this->replacements), array_values($this->replacements), $content);

        $hasConfig = $data['with_config'] ?? false;
        $hasViews = $data['with_views'] ?? false;
        $hasTranslations = $data['with_translations'] ?? false;
        $hasMigrations = $data['with_migrations'] ?? false;
        $hasInstallCommand = $data['with_install_command'] ?? false;

        $content = $this->processConditionalBlocks($content, 'CONFIG', $hasConfig);
        $content = $this->processConditionalBlocks($content, 'VIEWS', $hasViews);
        $content = $this->processConditionalBlocks($content, 'TRANSLATIONS', $hasTranslations);
        $content = $this->processConditionalBlocks($content, 'MIGRATIONS', $hasMigrations);
        $content = $this->processConditionalBlocks($content, 'INSTALL_COMMAND', $hasInstallCommand);

        File::put($destination, $content);
    }

    private function processConditionalBlocks(string $content, string $tag, bool $keep): string
    {
        $pattern = '/\{\{#'.$tag.'\}\}(.*?)\{\{\/'.$tag.'\}\}/s';

        if ($keep) {
            return (string) preg_replace($pattern, '$1', $content);
        }

        return (string) preg_replace($pattern, '', $content);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function printNextSteps(array $data): void
    {
        $this->newLine();
        $this->components->twoColumnDetail('<fg=yellow>Next steps</>');
        $this->newLine();

        $step = 1;
        $this->line("  <fg=cyan>{$step}.</> Add path repository + require to <fg=white>composer.json</>:");
        $this->line('     <fg=gray>"repositories": [{ "type": "path", "url": "'.$this->option('path').'/'.$data['package_slug'].'", "options": { "symlink": true } }]</>');
        $this->line('     <fg=gray>"require": { "'.$data['composer_name'].'": "@dev" }</>');

        $step++;
        $this->line("  <fg=cyan>{$step}.</> composer update ".$data['composer_name']);

        $step++;
        $this->line("  <fg=cyan>{$step}.</> Register in PanelProvider: ->plugins([".$data['namespace'].'\\'.$data['name'].'Plugin::make()])');

        if ($data['with_config']) {
            $step++;
            $this->line("  <fg=cyan>{$step}.</> php artisan vendor:publish --tag=\"".$data['package_slug'].'-config"');
        }

        if ($data['with_migrations']) {
            $step++;
            $this->line("  <fg=cyan>{$step}.</> php artisan vendor:publish --tag=\"".$data['package_slug'].'-migrations" && php artisan migrate');
        }

        $step++;
        $this->line("  <fg=cyan>{$step}.</> Add @source to panel theme CSS and run: npm run build");

        $step++;
        $this->line("  <fg=cyan>{$step}.</> php artisan config:clear && php artisan view:clear");

        $this->newLine();
        $this->components->twoColumnDetail('<fg=green>Tip: plugin image</>');
        $this->line('  See README for advice on choosing a screenshot that highlights your plugin.');
        $this->newLine();
    }
}
