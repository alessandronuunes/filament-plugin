# Filament Plugin Scaffolder

Scaffold Filament v3/v4/v5 plugins with Artisan commands. Generates the full structure: ServiceProvider, Plugin class, config, views, translations, install command, and more in `packages/` or a custom path.

## Features

| Command | Description |
|---------|-------------|
| `make:filament-plugin` | Create a new Filament plugin from scratch |
| `filament-plugin:register` | Register an existing plugin in the project `composer.json` |
| `filament-plugin:page` | Create a Filament page inside an existing plugin |
| `filament-plugin:submit` | Interactive wizard to submit a plugin to filamentphp.com |

## Requirements

- PHP 8.2+
- Laravel 11.x or 12.x

## Installation

```bash
composer require alessandronuunes/filament-plugin
```

For local package (monorepo), add to the application `composer.json`:

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

## Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag=filament-plugin-config
```

Edit `config/filament-plugin.php`:

| Option | Description | Default |
|--------|-------------|---------|
| `packages_path` | Base directory for local plugins | `packages` |
| `filamentphp_fork_path` | Path to your filamentphp.com fork (env: `FILAMENTPHP_FORK_PATH`) | — |
| `default_vendor` | Vendor namespace for new plugins | `AlessandroNuunes` |
| `default_author_name` | Author name for `composer.json` | `AlessandroNuunes` |
| `default_author_email` | Author email for `composer.json` | — |

The `packages_path` is used by `filament-plugin:register` and `filament-plugin:page` to locate plugins (e.g. `FilamentTest` → `packages/filament-test`).

The `filamentphp_fork_path` is used by `filament-plugin:submit` as the default repo path and enables automatic creation of author and plugin files in your fork. Set in `.env`:

```env
FILAMENTPHP_FORK_PATH=/Users/you/Workspace/fork/filamentphp.com
```

---

## 1. Create a New Plugin

```bash
php artisan make:filament-plugin FilamentTest
```

The command asks interactively:

- **Vendor namespace** (PascalCase)
- **Package slug** (kebab-case)
- **Description** and **author** (name/email)
- **Plugin type:** `panel` (pages, resources, widgets, tenancy) or `standalone` (reusable components)
- **Filament version:** 3, 4, 5, or 4|5 (compatible with both)
- **Include:** config, views, translations, migrations, install command

The plugin is created in `packages/{slug}/` (or `--path=custom/path`).

### Options

| Option | Description |
|--------|-------------|
| `--path=packages` | Base directory for the plugin (default: `packages`) |
| `--force` | Overwrite existing directory |
| `--register` | Add plugin to `composer.json` and run `composer update` |
| `--no-register` | Skip adding to `composer.json` |

### Non-interactive Example

```bash
php artisan make:filament-plugin FilamentTest --path=packages --force --register
```

With `--no-interaction`, the Filament version defaults to 4 or 5.

---

## 2. Register Existing Plugin

If the plugin already exists in `packages/` but is not in the project `composer.json`:

```bash
php artisan filament-plugin:register FilamentTest
```

The command:

1. Finds the plugin in `packages/filament-test` (or configured `packages_path`)
2. Adds the path repository and `require` to `composer.json`
3. Runs `composer update {package}`

**Argument:** Plugin name in PascalCase (e.g. `FilamentTest`, `FilamentMember`).

If the plugin is not found, check `packages_path` in `config/filament-plugin.php`.

---

## 3. Create Page Inside a Plugin

After creating or cloning a plugin, add Filament pages with:

```bash
php artisan filament-plugin:page MyPage --plugin=FilamentTest
```

Creates the class at `src/Pages/MyPage.php` and the view at `resources/views/filament/pages/my-page.blade.php`.

### Arguments and Options

| Argument/Option | Required | Description |
|-----------------|----------|-------------|
| `name` | Yes | Page name in PascalCase (e.g. `ManageSettings`) |
| `--plugin=` | Yes | Plugin name in PascalCase (e.g. `FilamentTest`) |
| `--filament=` | When `--no-interaction` | Version: `3`, `4`, `5`, or `4\|5` |
| `--force` | No | Overwrite existing class and view |
| `--register` | No | Add page to `->pages([...])` in the Plugin class |
| `--panel=` | No | Panel name (comment only in the class) |

### Examples

```bash
# Interactive (prompts for Filament version)
php artisan filament-plugin:page ManageSettings --plugin=FilamentTest

# Non-interactive
php artisan filament-plugin:page ManageSettings --plugin=FilamentTest --filament=5 --no-interaction

# Register in Plugin and overwrite
php artisan filament-plugin:page Settings --plugin=FilamentTest --register --force

# With specific panel
php artisan filament-plugin:page Billing --plugin=FilamentTest --panel=admin
```

### Auto-register Page

With `--register`, the command adds the page to `->pages([...])` in the plugin `*Plugin.php`. If the plugin uses `->discoverPages()`, the page is discovered automatically and no manual change is needed.

---

## 4. Submit Plugin to filamentphp.com

Interactive wizard to prepare and submit a plugin to [filamentphp.com](https://filamentphp.com/plugins):

```bash
php artisan filament-plugin:submit
```

The wizard guides you through:

1. Fork and clone the filamentphp.com repository
2. Selecting a plugin from `packages/` (or entering data manually)
3. Creating a branch (e.g. `add-filament-tabbed-dashboard`)
4. Author profile (new or existing)
5. Plugin data (name, slug, categories, description, etc.)
6. **Create files** — optionally writes `content/authors/{slug}.md` and `content/plugins/{slug}.md` directly into your fork
7. Commit and push instructions
8. Pull Request checklist

### Options

| Option | Description |
|--------|-------------|
| `--repo=` | Path to your filamentphp.com clone (default: `FILAMENTPHP_FORK_PATH` or current directory) |

### Automatic File Creation

If `FILAMENTPHP_FORK_PATH` is set in `.env`, the wizard uses it as the default repo path. At the end, it asks:

> Create the author and plugin files in your fork now? [y/N]

If you confirm, it writes the author and plugin markdown files directly. Add avatar and plugin image manually (JPEG, sizes as per filamentphp.com guidelines).

### Author Defaults (Submit Wizard)

Configure in `config/filament-plugin.php` to pre-fill the submit wizard:

| Option | Description |
|--------|-------------|
| `author_full_name` | Full name for author profile |
| `author_slug` | Author slug (e.g. `alessandro-nuunes`) |
| `author_github_url` | GitHub profile URL |
| `author_twitter` | Twitter/X URL (optional) |
| `author_mastodon` | Mastodon URL (optional) |
| `author_sponsor_url` | Sponsor URL (optional) |
| `author_bio` | Short bio (optional) |

---

## Recommended Workflow

1. **Create the plugin:**
   ```bash
   php artisan make:filament-plugin FilamentTest
   ```

2. **Register in the project** (if not already):
   ```bash
   php artisan filament-plugin:register FilamentTest
   ```

3. **Add pages:**
   ```bash
   php artisan filament-plugin:page MyPage --plugin=FilamentTest
   ```

4. **Register the plugin in the Filament panel:**
   ```php
   // App\Providers\Filament\AdminPanelProvider (or similar)
   ->plugins([
       \YourVendor\FilamentTest\FilamentTestPlugin::make(),
   ])
   ```

5. **Submit to filamentphp.com** (optional):
   ```bash
   php artisan filament-plugin:submit
   ```

---

## Structure Generated by `make:filament-plugin`

```
packages/{slug}/
├── src/
│   ├── {Name}Plugin.php          # Plugin class (panel type only)
│   ├── {Name}ServiceProvider.php
│   ├── Pages/                    # Pages created with filament-plugin:page
│   ├── Support/
│   │   └── ConfigHelper.php
│   └── Console/Commands/         # If install command included
│       └── InstallCommand.php
├── config/
│   └── {slug}.php
├── resources/
│   ├── views/filament/pages/
│   ├── views/livewire/
│   └── lang/en/ and pt_BR/       # If translations included
├── database/migrations/          # If migrations included
├── composer.json
├── README.md
├── pint.json
└── ...
```

---

## Troubleshooting

- **Plugin not found:** Ensure `packages_path` in `config/filament-plugin.php` points to the directory containing your plugins.
- **Invalid namespace:** Vendor and namespace must be valid PHP identifiers (no leading numbers). Use PascalCase (e.g. `AlessandroNuunes`).
- **Interactive prompts:** Do not pipe input into the command; respond to prompts directly to avoid invalid data.

---

## Publishing

1. Publish the package to GitHub (or GitLab).
2. Others can install via Composer:
   - **Packagist:** `composer require alessandronuunes/filament-plugin`
   - **Git repo:** add to `repositories`: `"type": "vcs", "url": "https://github.com/your-username/filament-plugin"` then `composer require alessandronuunes/filament-plugin:dev-main`

---

## Contributing

This package is in its **early stages** and we would love your help to improve it. Ideas, bug reports, pull requests, and feedback are all welcome.

Ways you can contribute:

- Open issues for bugs or feature requests
- Submit pull requests for improvements
- Share your experience or suggestions
- Help improve the documentation

Together we can make this scaffolder more useful for the Filament community. Thank you!

---

## License

MIT. See [LICENSE.md](LICENSE.md).
