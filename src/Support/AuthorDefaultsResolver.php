<?php

declare(strict_types=1);

namespace AlessandroNuunes\FilamentPlugin\Support;

use Illuminate\Support\Str;

class AuthorDefaultsResolver
{
    /**
     * Resolve author defaults from filament-plugin config.
     *
     * @return array{full_name: string, slug: string, github_url: string}
     */
    public static function resolve(): array
    {
        $vendor = config('filament-plugin.default_vendor', '');
        $authorName = config('filament-plugin.default_author_name', $vendor);

        $fullName = config('filament-plugin.author_full_name');
        if (! filled($fullName)) {
            $fullName = (string) preg_replace('/([a-z])([A-Z])/', '$1 $2', $authorName);
        }

        $slug = config('filament-plugin.author_slug');
        if (! filled($slug)) {
            $slug = Str::slug((string) preg_replace('/([a-z])([A-Z])/', '$1 $2', $authorName));
        }

        $githubUrl = config('filament-plugin.author_github_url');
        if (! filled($githubUrl)) {
            $githubUrl = 'https://github.com/'.Str::slug($vendor ?: $authorName, '');
        }

        return [
            'full_name' => (string) $fullName,
            'slug' => (string) $slug,
            'github_url' => (string) $githubUrl,
        ];
    }
}
