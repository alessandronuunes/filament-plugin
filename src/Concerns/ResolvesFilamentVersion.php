<?php

declare(strict_types=1);

namespace AlessandroNuunes\FilamentPlugin\Concerns;

use Illuminate\Console\Command;

use function Laravel\Prompts\select;

trait ResolvesFilamentVersion
{
    /**
     * Resolve Filament version from --filament option or prompt.
     * Returns: '3', '4', '5', or '4|5'.
     */
    protected function resolveFilamentVersion(?string $optionValue = null): ?string
    {
        if ($optionValue === null && $this instanceof Command && $this->getDefinition()->hasOption('filament')) {
            $optionValue = $this->option('filament');
        }

        if (filled($optionValue)) {
            $v = (string) preg_replace('/[\s]/', '', strtolower($optionValue));
            if (in_array($v, ['3', '4', '5'], true)) {
                return $v;
            }
            if (str_contains($v, '4') && str_contains($v, '5')) {
                return '4|5';
            }
            if ($this instanceof Command) {
                $this->error('Option --filament must be 3, 4, 5, or 4|5.');
            }

            return null;
        }

        if ($this instanceof Command && $this->option('no-interaction')) {
            $this->error('Option --filament is required when using --no-interaction.');

            return null;
        }

        return select(
            label: 'Which Filament version will this plugin target?',
            options: [
                '3' => '3',
                '4' => '4',
                '5' => '5',
                '4|5' => '4 | 5 (compatible with both)',
            ],
            default: '4|5',
        );
    }

    /**
     * Map Filament version to composer constraint.
     */
    protected function filamentVersionToConstraint(string $version): string
    {
        return match ($version) {
            '3' => '^3.0',
            '4' => '^4.0',
            '5' => '^5.0',
            '4|5' => '^4.0|^5.0',
            default => '^4.0|^5.0',
        };
    }
}
