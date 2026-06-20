# How it works

The whole design rests on one idea: **a module is a real Composer package from
day one.** Everything else — discovery, enable/disable, zero-churn promotion —
follows from that.

## A module is a Composer package

Each module folder ships its own `composer.json` with PSR-4 autoload and Laravel
provider discovery:

```jsonc
// Modules/Blog/composer.json
{
  "name": "acme/blog-module",
  "type": "laravel-module",
  "autoload": { "psr-4": { "Modules\\Blog\\": "src/" } },
  "extra": { "laravel": { "providers": [
    "Modules\\Blog\\Infrastructure\\Providers\\BlogServiceProvider"
  ]}}
}
```

The app declares the modules directory as a **path repository**:

```jsonc
// app composer.json
"repositories": [{ "type": "path", "url": "Modules/*" }],
"require": { "acme/blog-module": "*" }
```

Composer symlinks `Modules/Blog` into `vendor/`, so it behaves exactly like any
installed package — no custom autoloader.

## The boot chain

```
composer.json (path repo)
        │  composer install
        ▼
vendor/acme/blog-module  ──►  symlink to Modules/Blog
        │  PSR-4 from the module's own composer.json
        ▼
Modules\Blog\…  classes autoload
        │  Laravel package auto-discovery reads extra.laravel.providers
        ▼
BlogServiceProvider registers itself
        │  modules_statuses.json + ModuleManager
        ▼
the module is gated on/off
```

## Two layers, clear jobs

| Layer | Job |
| --- | --- |
| **Composer + Laravel** | Autoload classes and register module providers (standard mechanisms). |
| **This package** | Discover modules (`ModuleManager`), gate them via `modules_statuses.json`, expose the `Modules` facade + `module_path()`, provide a convention-loading base provider with attribute wiring, and compile it all for production (`module:cache`). |

## Why promotion is zero-churn

The only "local" detail is the **path repository** entry in the app's
`composer.json`. The module's namespace, structure, provider and autoload all
live in the module's own `composer.json` and never change.

```
develop:  repositories: [{ type: path,  url: "Modules/*" }]
promote:  repositories: [{ type: vcs,   url: "git@…/blog-module.git" }]
```

The class `Modules\Blog\…` is identical whether symlinked from `Modules/` or
installed from a registry. The module was always a package — promotion only
changes where Composer fetches it from.

See [Promote to a package](/promotion/promote-to-package) and
[Private distribution](/promotion/private-distribution).