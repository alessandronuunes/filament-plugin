<?php

declare(strict_types=1);

namespace AlessandroNuunes\FilamentPlugin\Support;

use Illuminate\Support\Facades\File;

class StubProcessor
{
    /**
     * @param  array<string, string>  $replacements
     * @param  array<string, bool>  $conditionalTags  e.g. ['CONFIG' => true, 'VIEWS' => false]
     */
    public function process(
        string $stubPath,
        string $destinationPath,
        array $replacements,
        array $conditionalTags = []
    ): bool {
        if (! File::exists($stubPath)) {
            return false;
        }

        $content = File::get($stubPath);
        $content = str_replace(array_keys($replacements), array_values($replacements), $content);

        foreach ($conditionalTags as $tag => $keep) {
            $content = $this->processConditionalBlock($content, $tag, $keep);
        }

        File::put($destinationPath, $content);

        return true;
    }

    /**
     * Process conditional block {{#TAG}}...{{/TAG}}.
     * If keep=true, unwrap. If keep=false, remove block.
     */
    public function processConditionalBlock(string $content, string $tag, bool $keep): string
    {
        $pattern = '/\{\{#'.$tag.'\}\}(.*?)\{\{\/'.$tag.'\}\}/s';

        return (string) preg_replace($pattern, $keep ? '$1' : '', $content);
    }
}
