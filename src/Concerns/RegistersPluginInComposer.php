<?php

declare(strict_types=1);

namespace AlessandroNuunes\FilamentPlugin\Concerns;

use AlessandroNuunes\FilamentPlugin\Support\ComposerJsonEditor;
use AlessandroNuunes\FilamentPlugin\Support\ComposerRunner;
use Illuminate\Console\Command;

trait RegistersPluginInComposer
{
    /**
     * Add plugin to project composer.json and run composer update.
     * Requires: $this must be a Command with option('path') or base path.
     */
    protected function registerPluginInComposer(string $composerName, string $repoUrl): bool
    {
        $editor = ComposerJsonEditor::forProject();

        if (! $editor->isValid()) {
            $this->warn('composer.json not found or invalid. Skipping registration.');

            return false;
        }

        $editor->addPathRepository($repoUrl);
        $editor->addRequire($composerName, '@dev');

        if (! $editor->save()) {
            $this->warn('Failed to save composer.json.');

            return false;
        }

        if (method_exists($this, 'newLine')) {
            $this->newLine();
        }
        $success = $this->runComposerUpdate($composerName);

        if (! $success) {
            $this->warn('Composer update failed. Run manually: composer update '.$composerName);
        }

        return true;
    }

    protected function runComposerUpdate(string $package): bool
    {
        if (method_exists($this, 'components') && $this->components !== null) {
            $success = false;
            $this->components->task('Running composer update', function () use ($package, &$success): void {
                $success = app(ComposerRunner::class)->update($package);
            });

            return $success;
        }

        return app(ComposerRunner::class)->update($package);
    }
}
