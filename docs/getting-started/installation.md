# Installation

## Requirements

- PHP 8.3+
- Laravel 11, 12 or 13

## Install

```bash
composer require dem1-off/laravel-modular
```

Publish the configuration:

```bash
php artisan vendor:publish --tag=modules-config
```

This writes `config/modules.php`. Its keys mirror the conventional module
config (`namespace`, `paths`, `statuses_file`), so an existing modular project
interoperates without editing any module.

## Autoloading modules

Module classes need a PSR-4 mapping. Pick whichever fits — you don't have to turn
modules into Composer packages.

### Option A — just modules (default, zero setup)

Leave `modules.autoload` at its default (`true`). Each discovered module's
namespace is registered at runtime, so a module works **by just existing** in the
`Modules/` directory:

```bash
php artisan make:module Blog   # done — it autoloads and boots, no Composer changes
```

No package, no `composer.json` edits. This is the path if you simply want
modules.

### Option B — Composer path repository (promotion-ready)

If you might extract modules into standalone packages later, treat the modules
directory as a path repository so each module is autoloaded from its own
`composer.json` and benefits from an optimised classmap. Set
`modules.autoload => false`, then:

```jsonc
// app composer.json
"repositories": [
  { "type": "path", "url": "Modules/*", "options": { "symlink": true } }
]
```

```bash
composer require your-vendor/blog-module:@dev
```

Promotion is then zero-churn — swap the path entry for a VCS/registry entry. See
[Promote to a package](/promotion/promote-to-package).

### Option C — root PSR-4

The classic approach: add each module's PSR-4 to the app's root `composer.json`
`autoload` and `composer dump-autoload`. Set `modules.autoload => false`.

> Set `modules.vendor` in `config/modules.php` to your vendor (e.g. `acme`) so
> generated modules are named `acme/blog-module`.

Continue to [Configuration](/getting-started/configuration).