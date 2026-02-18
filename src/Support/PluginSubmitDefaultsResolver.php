<?php

declare(strict_types=1);

namespace AlessandroNuunes\FilamentPlugin\Support;

class PluginSubmitDefaultsResolver
{
    /**
     * Extract plugin submit form defaults from composer.json data.
     *
     * @param  array<string, mixed>  $composer
     * @return array{name: string, slug_part: string, description: string, docs_url: string, github_repository: string}
     */
    public static function fromComposer(array $composer): array
    {
        $defaults = [
            'name' => '',
            'slug_part' => '',
            'description' => '',
            'docs_url' => '',
            'github_repository' => '',
        ];

        $defaults['description'] = (string) ($composer['description'] ?? '');

        $packageName = (string) ($composer['name'] ?? '');
        if (str_contains($packageName, '/')) {
            $slugPartRaw = explode('/', $packageName)[1] ?? '';
            $defaults['slug_part'] = (string) preg_replace('/^filament-/i', '', $slugPartRaw);
        }

        $slugPart = $defaults['slug_part'];
        $defaults['name'] = ucwords(str_replace('-', ' ', $slugPart));

        $source = $composer['support']['source'] ?? $composer['homepage'] ?? '';
        if ($source === '' && isset($composer['homepage'])) {
            $source = (string) $composer['homepage'];
        }
        if (preg_match('#github\.com/([^/]+)/([^/]+?)(?:\.git)?/?$#', $source, $m)) {
            $user = $m[1];
            $repo = trim($m[2], '/');
            $defaults['github_repository'] = $user.'/'.$repo;
            $defaults['docs_url'] = 'https://raw.githubusercontent.com/'.$user.'/'.$repo.'/main/README.md';
        }

        return $defaults;
    }
}
