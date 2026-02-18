<?php

declare(strict_types=1);

namespace AlessandroNuunes\FilamentPlugin\Concerns;

use AlessandroNuunes\FilamentPlugin\Support\StubProcessor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

trait ProcessesStubFiles
{
    /**
     * Write stub to destination with replacements and conditional blocks.
     *
     * @param  array<string, string>  $replacements
     * @param  array<string, bool>  $conditionalTags
     */
    protected function writeStub(
        string $stubName,
        string $destinationPath,
        array $replacements,
        array $conditionalTags = []
    ): bool {
        $stubPath = $this->getStubsBasePath().'/'.$stubName;

        if (! File::exists($stubPath)) {
            if ($this instanceof Command) {
                $this->warn("Stub [{$stubName}] not found at [{$stubPath}].");
            }

            return false;
        }

        return app(StubProcessor::class)->process($stubPath, $destinationPath, $replacements, $conditionalTags);
    }

    protected function getStubsBasePath(): string
    {
        return __DIR__.'/../../resources/stubs/filament-plugin';
    }
}
