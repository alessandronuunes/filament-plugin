<?php

declare(strict_types=1);

namespace AlessandroNuunes\FilamentPlugin\Commands;

use AlessandroNuunes\FilamentPlugin\Concerns\DiscoversFilamentPlugins;
use AlessandroNuunes\FilamentPlugin\Concerns\ResolvesAuthorDefaults;
use AlessandroNuunes\FilamentPlugin\Support\PluginSubmitDefaultsResolver;
use Illuminate\Console\Command;

use function Laravel\Prompts\select;

class SubmitPluginWizardCommand extends Command
{
    use DiscoversFilamentPlugins;
    use ResolvesAuthorDefaults;

    protected $signature = 'filament-plugin:submit
        {--repo= : Path to your filamentphp.com clone (default: current directory)}';

    protected $description = 'Interactive wizard to submit a plugin to filamentphp.com';

    private const FORK_URL = 'https://github.com/filamentphp/filamentphp.com';

    private const VALID_CATEGORIES = [
        'action', 'analytics', 'developer-tool', 'form-builder', 'form-editor',
        'form-field', 'form-layout', 'icon-set', 'infolist-entry', 'kit',
        'panel-authentication', 'panel-authorization', 'panel-builder', 'spatie',
        'table-builder', 'table-column', 'theme', 'widget',
    ];

    private string $repoPath = '';

    /** @var array<string, mixed> */
    private array $state = [];

    public function handle(): int
    {
        if ($this->option('no-interaction')) {
            $this->error('This command is interactive only. Run without --no-interaction.');

            return self::FAILURE;
        }

        $this->repoPath = $this->resolveRepoPath();

        if (! is_dir($this->repoPath)) {
            $this->error("Repository path does not exist or is not a directory: {$this->repoPath}");

            return self::FAILURE;
        }

        $this->info('Plugin Submit Wizard — filamentphp.com');
        $this->line('Repository path: '.$this->repoPath);
        $this->newLine();

        $this->stepFork();
        $this->stepClone();
        $this->stepSelectPlugin();
        $this->stepBranch();
        $this->stepAuthor();
        $this->stepPluginData();
        $this->stepCommitAndPush();
        $this->stepOpenPr();

        $this->newLine();
        $this->info('Wizard finished.');

        return self::SUCCESS;
    }

    private function resolveRepoPath(): string
    {
        $repo = $this->option('repo');

        if (filled($repo)) {
            return realpath($repo) ?: (string) $repo;
        }

        return (string) getcwd();
    }

    private function path(string $relative): string
    {
        return rtrim($this->repoPath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.ltrim($relative, DIRECTORY_SEPARATOR);
    }

    private function stepFork(): void
    {
        if (! $this->confirm('Have you already forked the filamentphp.com repository to your GitHub account?', true)) {
            $this->line('You need a fork to submit changes via Pull Request.');
            $this->line('URL: '.self::FORK_URL);
            $this->line('Click Fork and create the fork in your account.');
            $this->ask('When done, press ENTER to continue');
        }
    }

    private function stepClone(): void
    {
        if (! $this->confirm('Have you already cloned the repository (in this directory or elsewhere)?', true)) {
            $this->line('You need your fork\'s code locally to edit files.');
            $this->line('Run (replace YOUR_USERNAME with your GitHub username):');
            $this->line('  git clone https://github.com/YOUR_USERNAME/filamentphp.com.git');
            $this->line('  cd filamentphp.com');
            $this->ask('When done, press ENTER to continue');
        }
    }

    private function stepBranch(): void
    {
        $defaultBranch = $this->getDefaultBranchName();
        $branch = $this->ask(
            'Branch name (a new branch will be created with: git checkout -b '.$defaultBranch.')',
            $defaultBranch
        );
        $branch = filled($branch) ? $branch : $defaultBranch;
        $this->state['branch'] = $branch;

        $this->line('In your filamentphp.com clone, run:');
        $this->line('  git checkout -b '.$branch);
        $this->line('(A separate branch keeps main clean and makes opening the Pull Request easier.)');
        $this->ask('When done, press ENTER to continue');
    }

    private function getDefaultBranchName(): string
    {
        $slug = $this->state['selected_plugin_slug'] ?? null;

        if (filled($slug)) {
            return 'add-'.$slug;
        }

        return 'add-my-plugin';
    }

    private function stepAuthor(): void
    {
        $authorsPath = $this->path('content/authors');
        $hasAuthors = is_dir($authorsPath);
        $defaults = $this->getAuthorDefaults();

        if ($hasAuthors && $this->confirm('Do you already have an author file in content/authors/?', false)) {
            $this->state['author_slug'] = $this->ask('What is your author slug? (e.g. alessandro-nuunes)', $defaults['slug']);
            $this->state['is_new_author'] = false;
            $this->line('Ensure your avatar is at content/authors/avatars/'.$this->state['author_slug'].'.jpg (square, min 1000×1000 px, JPEG).');

            return;
        }

        $this->state['is_new_author'] = true;

        $this->line('Create your author profile. Values prefilled from config/filament-plugin.php — fill in or change as needed.');
        $this->newLine();
        $name = $this->ask('Full name', $defaults['full_name']);
        $slug = $this->ask('Author slug (e.g. alessandro-nuunes)', $defaults['slug']);
        $githubUrl = $this->ask('GitHub URL (e.g. https://github.com/username)', $defaults['github_url']);
        $twitter = $this->ask('Twitter (optional, leave blank to skip)', config('filament-plugin.author_twitter') ?? '');
        $mastodon = $this->ask('Mastodon (optional)', config('filament-plugin.author_mastodon') ?? '');
        $sponsor = $this->ask('Sponsor URL (optional)', config('filament-plugin.author_sponsor_url') ?? '');
        $bio = $this->ask('Short bio (one or two sentences)', config('filament-plugin.author_bio') ?? '');

        $lines = ["---\n", 'name: '.$name."\n", 'slug: '.$slug."\n", 'github_url: '.$githubUrl."\n"];
        if ($twitter !== '') {
            $lines[] = 'twitter: '.$twitter."\n";
        }
        if ($mastodon !== '') {
            $lines[] = 'mastodon: '.$mastodon."\n";
        }
        if ($sponsor !== '') {
            $lines[] = 'sponsor: '.$sponsor."\n";
        }
        $lines[] = "---\n\n";
        $lines[] = $bio !== '' ? $bio."\n" : "Your bio here. Check grammar (e.g. Grammarly).\n";

        $content = implode('', $lines);
        $filePath = $this->path('content/authors/'.$slug.'.md');

        $this->line('Create this file in your clone: '.$filePath);
        $this->line($content);
        $this->line('This file is required by Contributing to link the plugin to an author.');
        $this->line('Place your avatar at content/authors/avatars/'.$slug.'.jpg (square, min 1000×1000 px, JPEG).');
        $this->state['author_slug'] = $slug;
        $this->ask('When ready, press ENTER to continue');
    }

    private function stepSelectPlugin(): void
    {
        $plugins = $this->discoverPlugins();

        if (empty($plugins)) {
            $this->line('No Filament plugins found in packages path. You will enter plugin data manually.');

            return;
        }

        $options = ['__none__' => "(None — I'll enter everything manually)"];

        foreach ($plugins as $plugin) {
            $options[$plugin['path']] = $plugin['name'].' ('.$plugin['slug'].')';
        }

        $choice = select(
            label: 'Which plugin do you want to submit?',
            options: $options,
        );

        if ($choice === '__none__') {
            return;
        }

        $selected = collect($plugins)->first(fn (array $p) => $p['path'] === $choice);

        if ($selected !== null) {
            $this->state['selected_plugin_path'] = $selected['path'];
            $this->state['selected_plugin_composer'] = $selected['composer'];
            $this->state['selected_plugin_slug'] = $selected['slug'];
            $this->info('Selected: '.$selected['name'].' → branch will default to add-'.$selected['slug']);
        }
    }

    private function stepPluginData(): void
    {
        $authorSlug = $this->state['author_slug'] ?? $this->ask('What is your author slug?', '');
        $this->state['author_slug'] = $authorSlug;

        $composer = $this->state['selected_plugin_composer'] ?? [];
        $defaults = is_array($composer)
            ? PluginSubmitDefaultsResolver::fromComposer($composer)
            : ['name' => '', 'slug_part' => '', 'description' => '', 'docs_url' => '', 'github_repository' => ''];

        $pluginName = $this->ask('Plugin name (without "Filament", e.g. Member Management)', $defaults['name']);
        $slugPart = $this->ask('Plugin slug part (e.g. member → full slug: '.$authorSlug.'-member)', $defaults['slug_part']);
        $this->line('Valid categories: '.implode(', ', self::VALID_CATEGORIES));
        $categoriesInput = $this->ask('Categories (comma-separated, e.g. panel-builder, table-builder)', '');
        $categories = array_map('trim', array_filter(explode(',', $categoriesInput)));
        $categories = array_values(array_intersect($categories, self::VALID_CATEGORIES));

        $description = $this->ask('Description (one clear sentence)', $defaults['description']);
        $docsUrl = $this->ask('docs_url (raw README URL, e.g. https://raw.githubusercontent.com/user/repo/main/README.md)', $defaults['docs_url']);
        $githubRepo = $this->ask('github_repository (username/repo)', $defaults['github_repository']);
        $hasDarkTheme = $this->confirm('has_dark_theme?', false);
        $hasTranslations = $this->confirm('has_translations?', false);
        $versionsInput = $this->ask('Filament versions (comma-separated, e.g. 4, 5)', '4, 5');
        $versions = array_map('trim', explode(',', $versionsInput));
        $publishDate = $this->ask('publish_date (YYYY-MM-DD)', date('Y-m-d'));

        $slugPartNormalized = preg_replace('/^filament-/i', '', $slugPart);
        $pluginSlug = $authorSlug.'-'.$slugPartNormalized;
        $this->state['plugin_slug'] = $pluginSlug;
        $this->state['plugin_name'] = $pluginName;
        $this->state['plugin_filename'] = $pluginSlug.'.md';

        $categoriesInline = '['.implode(', ', $categories).']';
        $versionsInline = '['.implode(', ', array_map('intval', $versions)).']';

        $yaml = "---\n";
        $yaml .= 'name: "'.str_replace('"', '\\"', $pluginName)."\"\n";
        $yaml .= 'slug: '.$pluginSlug."\n";
        $yaml .= 'categories: '.$categoriesInline."\n";
        $yaml .= 'description: "'.str_replace('"', '\\"', $description)."\"\n";
        $yaml .= 'docs_url: "'.$docsUrl."\"\n";
        $yaml .= 'github_repository: '.$githubRepo."\n";
        $yaml .= 'has_dark_theme: '.($hasDarkTheme ? 'true' : 'false')."\n";
        $yaml .= 'has_translations: '.($hasTranslations ? 'true' : 'false')."\n";
        $yaml .= 'versions: '.$versionsInline."\n";
        $yaml .= 'publish_date: "'.$publishDate."\"\n";
        $yaml .= "---\n";

        $filePath = $this->path('content/plugins/'.$pluginSlug.'.md');

        $this->line('Create this file in your clone: '.$filePath);
        $this->line($yaml);
        $this->line('This file is what the site uses to list your plugin.');
        $this->line('Add the plugin image at content/plugins/images/'.$pluginSlug.'.jpg (16:9, min 2560×1440 px, JPEG, light theme).');
        $this->ask('When the image is in place (or to skip for now), press ENTER to continue');
    }

    private function stepCommitAndPush(): void
    {
        $authorSlug = $this->state['author_slug'];
        $pluginSlug = $this->state['plugin_slug'];
        $pluginFilename = $this->state['plugin_filename'];
        $pluginName = $this->state['plugin_name'];
        $branch = $this->state['branch'];
        $isNewAuthor = $this->state['is_new_author'] ?? true;

        $relativePaths = $isNewAuthor
            ? [
                'content/authors/'.$authorSlug.'.md',
                'content/authors/avatars/'.$authorSlug.'.jpg',
                'content/plugins/'.$pluginFilename,
                'content/plugins/images/'.$pluginSlug.'.jpg',
            ]
            : [
                'content/plugins/'.$pluginFilename,
                'content/plugins/images/'.$pluginSlug.'.jpg',
            ];

        $fileInfo = $isNewAuthor
            ? '4 files (author, avatar, plugin, image)'
            : '2 files (plugin + image — author already exists)';

        $this->line('You will commit '.$fileInfo.'.');
        $this->newLine();
        $this->line('In your filamentphp.com clone, run these commands in order:');
        $this->newLine();
        $this->line('  cd '.$this->repoPath);
        $this->line('  git add '.implode(' ', $relativePaths));
        $this->line('  git status   # optional: check what is staged');
        $this->line('  git commit -m "Add plugin: '.$pluginName.'"');
        $this->line('  git push -u origin '.$branch);
        $this->newLine();
        $this->line('(Push sends your branch to your fork and sets upstream for the PR.)');
    }

    private function stepOpenPr(): void
    {
        $this->newLine();
        $this->info('Open the Pull Request');
        $this->line('1. Open: https://github.com/filamentphp/filamentphp.com/compare (or "Compare & pull request" on your fork).');
        $this->line('2. Base: filamentphp/filamentphp.com branch main; compare: your fork, your branch.');
        $this->line('3. Important: Enable "Allow edits and access to secrets by maintainers" (otherwise the team cannot complete the review).');
        $this->line('4. Fill in PR title and description clearly.');
        $this->newLine();
        $this->line('After opening the PR, wait for review. See PLUGIN_REVIEW_GUIDELINES.md for common scenarios and team responses.');
        $this->newLine();
        $this->line('Quality checklist:');
        $this->line('  [ ] Author: file in content/authors/ and avatar in content/authors/avatars/ (1000×1000, square, JPEG).');
        $this->line('  [ ] Plugin: file in content/plugins/ and image in content/plugins/images/ (16:9, ≥2560×1440, JPEG, light theme).');
        $this->line('  [ ] Plugin name does not include the word "Filament".');
        $this->line('  [ ] Categories are from the official list only.');
        $this->line('  [ ] docs_url is a raw URL; README images use absolute URLs.');
        $this->line('  [ ] Plugin is on GitHub and installable via Packagist or Anystack; public documentation.');
        $this->line('  [ ] PR has "Allow edits and access to secrets by maintainers" enabled.');
    }
}
