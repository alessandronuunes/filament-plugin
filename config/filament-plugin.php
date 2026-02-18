<?php

return [
    /*
    |--------------------------------------------------------------------------
    | FilamentPHP.com Fork Path
    |--------------------------------------------------------------------------
    | Path to your filamentphp.com fork clone. Used as default for
    | filament-plugin:submit --repo. Enables automatic file creation.
    | Example: /Users/you/Workspace/fork/filamentphp.com
    */
    'filamentphp_fork_path' => env('FILAMENTPHP_FORK_PATH'),

    /*
    |--------------------------------------------------------------------------
    | Packages Path
    |--------------------------------------------------------------------------
    | Base path where local plugins live (e.g. packages/). Used by
    | filament-plugin:register and filament-plugin:page to locate plugins
    | by name (e.g. FilamentTabbedDashboard → packages/filament-tabbed-dashboard).
    */
    'packages_path' => 'packages',

    /*
    |--------------------------------------------------------------------------
    | Default Vendor Namespace
    |--------------------------------------------------------------------------
    | Default vendor/namespace used when creating new plugins with
    | php artisan make:filament-plugin. Customize this to your company or
    | personal namespace (e.g. MyCompany, Acme).
    */
    'default_vendor' => 'AlessandroNuunes',

    /*
    |--------------------------------------------------------------------------
    | Default Author
    |--------------------------------------------------------------------------
    | Default author name and email used in composer.json when creating
    | new plugins.
    */
    'default_author_name' => 'AlessandroNuunes',
    'default_author_email' => 'alessandronuunes@gmail.com',

    /*
    |--------------------------------------------------------------------------
    | Submit Wizard Author Defaults
    |--------------------------------------------------------------------------
    | Pre-fill for filament-plugin:submit (filamentphp.com). Leave null to
    | derive from default_author_name / default_vendor.
    */
    'author_full_name' => 'alessandro nunes de oliveira',
    'author_slug' => 'alessandronuunes',
    'author_github_url' => 'https://github.com/alessandronuunes',
    'author_twitter' => 'https://x.com/alessandronuunes',
    'author_mastodon' => null,
    'author_sponsor_url' => 'https://github.com/sponsors/alessandronuunes',

];
