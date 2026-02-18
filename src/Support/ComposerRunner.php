<?php

declare(strict_types=1);

namespace AlessandroNuunes\FilamentPlugin\Support;

use Illuminate\Support\Facades\Process;

class ComposerRunner
{
    /**
     * Run composer update for a package.
     */
    public function update(string $package, int $timeout = 120): bool
    {
        $result = Process::path(base_path())
            ->timeout($timeout)
            ->run(['composer', 'update', $package, '--no-interaction']);

        return $result->successful();
    }
}
