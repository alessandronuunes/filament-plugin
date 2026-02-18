<?php

declare(strict_types=1);

namespace AlessandroNuunes\FilamentPlugin\Concerns;

use AlessandroNuunes\FilamentPlugin\Support\AuthorDefaultsResolver;

trait ResolvesAuthorDefaults
{
    /**
     * Get author defaults for the submit wizard.
     *
     * @return array{full_name: string, slug: string, github_url: string}
     */
    protected function getAuthorDefaults(): array
    {
        return AuthorDefaultsResolver::resolve();
    }
}
