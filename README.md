# Filament Plugin (Scaffolder)

Scaffold Filament v4 plugins with one Artisan command. Generates the full structure (ServiceProvider, Plugin class, config, views, translations, install command, etc.) in `packages/` or a path of your choice.

## Requirements

- PHP 8.2+
- Laravel 11.x or 12.x

## Installation

Install the package via Composer:

```bash
composer require alessandronuunes/filament-plugin
```

If the package is in a local path (e.g. monorepo), add to your application's `composer.json`:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "packages/filament-plugin",
            "options": { "symlink": true }
        }
    ],
    "require-dev": {
        "alessandronuunes/filament-plugin": "@dev"
    }
}
```

Then run:

```bash
composer update alessandronuunes/filament-plugin
```

## Usage

From your Laravel project root:

```bash
php artisan make:filament-plugin NomeDoPlugin
```

The command will prompt for:

- Vendor namespace (PascalCase)
- Package slug (kebab-case)
- Description, author name/email
- Plugin type (panel / standalone)
- Options: config, views, translations, migrations, install command

The plugin is generated in `packages/{slug}/` (or `--path=custom/path`). Next steps are printed in the terminal.

### Options

- `--path=packages` – Base directory for the new plugin (default: `packages`)
- `--force` – Overwrite existing directory
- `--no-interaction` – Use defaults for all prompts

## Sharing with others

1. Publish this package to GitHub (or GitLab).
2. Others can install via Composer:
   - **Packagist:** `composer require alessandronuunes/filament-plugin`
   - **Git repo:** add `repositories` with `"type": "vcs", "url": "https://github.com/your-user/filament-plugin"` and `composer require alessandronuunes/filament-plugin:dev-main`

## License

MIT. See [LICENSE.md](LICENSE.md).
