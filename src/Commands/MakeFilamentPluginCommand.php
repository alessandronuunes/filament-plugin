<?php

declare(strict_types=1);

namespace AlessandroNuunes\FilamentPlugin\Commands;

use AlessandroNuunes\FilamentPlugin\Concerns\ProcessesStubFiles;
use AlessandroNuunes\FilamentPlugin\Concerns\RegistersPluginInComposer;
use AlessandroNuunes\FilamentPlugin\Concerns\ResolvesFilamentVersion;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;

class MakeFilamentPluginCommand extends Command
{
    use ProcessesStubFiles;
    use RegistersPluginInComposer;
    use ResolvesFilamentVersion;

    protected $signature = 'make:filament-plugin
        {name : The plugin name in PascalCase (e.g. FilamentMember)}
        {--path=packages : Base path for the plugin directory}
        {--force : Overwrite if the directory already exists}
        {--register : Add plugin to project composer.json and run composer update (skips prompt)}
        {--no-register : Skip adding plugin to composer.json}';

    protected $description = 'Scaffold a new Filament v4 plugin in packages/';

    public function handle(): int
    {
        $name = (string) $this->argument('name');

        if (! preg_match('/^[A-Z][a-zA-Z0-9]+$/', $name)) {
            $this->error('Plugin name must be PascalCase (e.g. FilamentMember).');

            return self::FAILURE;
        }

        $data = $this->collectPluginData($name);
        $pluginPath = base_path($this->option('path').'/'.$data['package_slug']);

        if (File::isDirectory($pluginPath) && ! $this->option('force')) {
            $this->error("Directory [{$pluginPath}] already exists. Use --force to overwrite.");

            return self::FAILURE;
        }

        $this->scaffoldPlugin($pluginPath, $data);

        $this->newLine();
        $this->components->info("Plugin [{$data['composer_name']}] created at [{$pluginPath}]");

        $registered = $this->handleRegistration($data);
        $this->printNextSteps($data, $registered);

        return self::SUCCESS;
    }

    /**
     * @return array<string, mixed>
     */
    private function collectPluginData(string $name): array
    {
        $vendor = $this->ask('Vendor namespace (PascalCase)', config('filament-plugin.default_vendor', 'AlessandroNuunes'));
        $packageSlug = $this->ask('Package slug (kebab-case)', Str::kebab($name));
        $description = $this->ask('Short description', "A Filament plugin for {$name}.");
        $authorName = $this->ask('Author name', config('filament-plugin.default_author_name', $vendor));
        $authorEmail = $this->ask('Author email', config('filament-plugin.default_author_email', ''));

        $this->newLine();
        $this->line('  <fg=gray>panel: adds pages, resources, widgets to a Filament panel (generates Plugin class).</>');
        $this->line('  <fg=gray>standalone: reusable components (form fields, columns) for any context (no Plugin class).</>');
        $this->newLine();

        $type = select(
            label: 'Plugin type',
            options: [
                'panel' => 'panel — for Panel (pages, resources, widgets, tenancy)',
                'standalone' => 'standalone — for reusable components (form fields, table columns)',
            ],
            default: 'panel',
        );

        $filamentVersion = $this->resolveFilamentVersion();
        $filamentConstraint = $this->filamentVersionToConstraint($filamentVersion ?? '4|5');

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
            'filament_version' => $filamentVersion,
            'filament_constraint' => $filamentConstraint,
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
    private function handleRegistration(array $data): bool
    {
        if ($this->option('no-register')) {
            return false;
        }

        $shouldRegister = $this->option('register')
            || (! $this->option('no-interaction') && confirm('Add this plugin to the project\'s composer.json?', true));

        if (! $shouldRegister) {
            return false;
        }

        $repoUrl = $this->option('path').'/'.$data['package_slug'];

        return $this->registerPluginInComposer($data['composer_name'], $repoUrl);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function scaffoldPlugin(string $path, array $data): void
    {
        $replacements = $this->buildReplacements($data);
        $conditionalTags = $this->buildConditionalTags($data);

        $this->components->task('Creating directory structure', fn () => $this->createDirectoryStructure($path, $data));

        $this->components->task('Generating composer.json', fn () => $this->writeStub('composer.stub', $path.'/composer.json', $replacements, $conditionalTags));

        $this->components->task('Generating ServiceProvider', fn () => $this->writeStub('service-provider.stub', $path.'/src/'.$data['name'].'ServiceProvider.php', $replacements, $conditionalTags));

        if ($data['type'] === 'panel') {
            $this->components->task('Generating Plugin class', fn () => $this->writeStub('plugin.stub', $path.'/src/'.$data['name'].'Plugin.php', $replacements, $conditionalTags));
        }

        if ($data['with_config']) {
            $this->components->task('Generating config file', fn () => $this->writeStub('config.stub', $path.'/config/'.$data['package_slug'].'.php', $replacements, $conditionalTags));
        }

        if ($data['with_translations']) {
            $this->components->task('Generating translation files', function () use ($path, $replacements, $conditionalTags): void {
                $this->writeStub('lang-en.stub', $path.'/resources/lang/en/default.php', $replacements, $conditionalTags);
                $this->writeStub('lang-pt-br.stub', $path.'/resources/lang/pt_BR/default.php', $replacements, $conditionalTags);
            });
        }

        if ($data['with_install_command']) {
            $this->components->task('Generating install command', fn () => $this->writeStub('install-command.stub', $path.'/src/Console/Commands/InstallCommand.php', $replacements, $conditionalTags));
        }

        $this->components->task('Generating Support/ConfigHelper', fn () => $this->writeStub('config-helper.stub', $path.'/src/Support/ConfigHelper.php', $replacements, $conditionalTags));

        $this->components->task('Generating README.md', fn () => $this->writeStub('readme.stub', $path.'/README.md', $replacements, $conditionalTags));

        $this->components->task('Generating metadata files', function () use ($path, $replacements): void {
            $this->writeStub('gitignore.stub', $path.'/.gitignore', $replacements);
            $this->writeStub('pint.stub', $path.'/pint.json', $replacements);
            $this->writeStub('changelog.stub', $path.'/CHANGELOG.md', $replacements);
            $this->writeStub('license.stub', $path.'/LICENSE.md', $replacements);
            $this->writeStub('github-contributing.stub', $path.'/.github/CONTRIBUTING.md', $replacements);
            $this->writeStub('github-funding.stub', $path.'/.github/FUNDING.yml', $replacements);
            $this->writeStub('github-security.stub', $path.'/.github/SECURITY.md', $replacements);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createDirectoryStructure(string $path, array $data): void
    {
        File::ensureDirectoryExists($path.'/src');
        File::ensureDirectoryExists($path.'/src/Support');
        File::ensureDirectoryExists($path.'/.github');

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
    }

    /**
     * @return array<string, string>
     */
    private function buildReplacements(array $data): array
    {
        $authorEmail = $data['author_email'] !== '' ? $data['author_email'] : 'the maintainer (see composer.json or README)';

        return [
            '{{NAMESPACE}}' => $data['namespace'],
            '{{NAMESPACE_ESCAPED}}' => str_replace('\\', '\\\\', $data['namespace']),
            '{{CLASS_NAME}}' => $data['name'],
            '{{PLUGIN_ID}}' => $data['package_slug'],
            '{{VENDOR_SLUG}}' => $data['vendor_slug'],
            '{{PACKAGE_SLUG}}' => $data['package_slug'],
            '{{COMPOSER_NAME}}' => $data['composer_name'],
            '{{DESCRIPTION}}' => $data['description'],
            '{{AUTHOR_NAME}}' => $data['author_name'],
            '{{AUTHOR_EMAIL}}' => $authorEmail,
            '{{CONFIG_KEY}}' => $data['package_slug'],
            '{{FILAMENT_CONSTRAINT}}' => $data['filament_constraint'] ?? '^4.0|^5.0',
        ];
    }

    /**
     * @return array<string, bool>
     */
    private function buildConditionalTags(array $data): array
    {
        return [
            'CONFIG' => $data['with_config'] ?? false,
            'VIEWS' => $data['with_views'] ?? false,
            'TRANSLATIONS' => $data['with_translations'] ?? false,
            'MIGRATIONS' => $data['with_migrations'] ?? false,
            'INSTALL_COMMAND' => $data['with_install_command'] ?? false,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function printNextSteps(array $data, bool $registered): void
    {
        $this->newLine();
        $this->components->twoColumnDetail('<fg=yellow>Next steps</>');
        $this->newLine();

        $step = 1;
        if (! $registered) {
            $this->line("  <fg=cyan>{$step}.</> Add path repository + require to <fg=white>composer.json</>:");
            $this->line('     <fg=gray>"repositories": [{ "type": "path", "url": "'.$this->option('path').'/'.$data['package_slug'].'", "options": { "symlink": true } }]</>');
            $this->line('     <fg=gray>"require": { "'.$data['composer_name'].'": "@dev" }</>');
            $step++;
            $this->line("  <fg=cyan>{$step}.</> composer update ".$data['composer_name']);
            $step++;
        }

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
