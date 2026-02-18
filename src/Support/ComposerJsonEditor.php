<?php

declare(strict_types=1);

namespace AlessandroNuunes\FilamentPlugin\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;

class ComposerJsonEditor
{
    /**
     * @var array<string, mixed>
     */
    private array $data = [];

    public function __construct(
        private string $path
    ) {
        $this->load();
    }

    public static function forProject(): self
    {
        return new self(base_path('composer.json'));
    }

    public function load(): bool
    {
        if (! File::exists($this->path)) {
            return false;
        }

        $content = File::get($this->path);
        $decoded = json_decode($content, true);

        if (! is_array($decoded)) {
            return false;
        }

        $this->data = $decoded;

        return true;
    }

    public function isValid(): bool
    {
        return ! empty($this->data);
    }

    /**
     * Add path repository if not exists.
     */
    public function addPathRepository(string $url): bool
    {
        $repositories = Arr::get($this->data, 'repositories', []);
        if (! is_array($repositories)) {
            $repositories = [];
        }

        $exists = collect($repositories)->contains(
            fn (mixed $r): bool => is_array($r) && ($r['url'] ?? '') === $url
        );

        if (! $exists) {
            $repositories[] = [
                'type' => 'path',
                'url' => $url,
                'options' => ['symlink' => true],
            ];
            Arr::set($this->data, 'repositories', $repositories);
        }

        return true;
    }

    /**
     * Add require entry if not exists.
     */
    public function addRequire(string $package, string $constraint = '@dev'): bool
    {
        $require = Arr::get($this->data, 'require', []);
        if (! is_array($require)) {
            $require = [];
        }

        if (! isset($require[$package])) {
            $require[$package] = $constraint;
            ksort($require);
            Arr::set($this->data, 'require', $require);
        }

        return true;
    }

    public function save(): bool
    {
        $encoded = json_encode(
            $this->data,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );

        if ($encoded === false) {
            return false;
        }

        return File::put($this->path, $encoded."\n") !== false;
    }
}
