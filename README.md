# dem1-off/laravel-modular

[![Latest Version on Packagist](https://img.shields.io/packagist/v/dem1-off/laravel-modular.svg)](https://packagist.org/packages/dem1-off/laravel-modular)
[![Tests](https://github.com/DeM1-off/laravel-modular/actions/workflows/tests.yml/badge.svg)](https://github.com/DeM1-off/laravel-modular/actions/workflows/tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/dem1-off/laravel-modular.svg)](https://packagist.org/packages/dem1-off/laravel-modular)
[![PHP Version](https://img.shields.io/packagist/php-v/dem1-off/laravel-modular.svg)](https://packagist.org/packages/dem1-off/laravel-modular)
[![License](https://img.shields.io/packagist/l/dem1-off/laravel-modular.svg)](LICENSE)

Modular architecture tooling for Laravel. DDD modules
(Domain / Application / Infrastructure) that are **real Composer packages from
day one**, so any module can be promoted to a standalone package with zero code
churn.

## Highlights

- **Attribute-driven wiring** — declare bindings and listeners with `#[Bind]`
  and `#[Listen]`, or let an implementation auto-bind itself with `#[Provides]`
  (Symfony-style autoconfigure for Laravel modules). Config, migrations, views,
  routes, translations and artisan commands load by convention, so the common
  provider is empty.
- **Checkable boundaries** — declare inter-module dependencies with `requires`
  in `module.json` (dependency-aware load order) and let
  `module:check --boundaries` fail CI when a module reaches into another
  module's internals instead of its contracts.
- **Fast by design** — `module:cache` compiles discovery, attributes and each
  module's resolved folders into one PHP file (zero reflection, zero filesystem
  scanning, zero stat calls per module at runtime), wired into
  `php artisan optimize`.
- **A designed-in promotion path** — module namespaces never change, so moving a
  module into its own repo is a Composer change, not a refactor.
- **Familiar layout** — works with the `Modules/` directory, `module.json`,
  `modules_statuses.json`, and `module_path()` conventions, so existing projects
  interoperate.

## A module at a glance

```php
#[Bind(PostRepositoryInterface::class, EloquentPostRepository::class)]
#[Listen(ChapterPublished::class, SendDigest::class)]
final class BlogServiceProvider extends ModuleServiceProvider {}
```

## Installation

```bash
composer require dem1-off/laravel-modular
php artisan vendor:publish --tag=modules-config
```

`config/modules.php` uses conventional module config keys (`namespace`,
`paths`, `statuses_file`), so an existing modular project migrates without
editing modules.

## Creating a module

```bash
php artisan make:module Blog
```

Produces a promotion-ready package:

```
Modules/Blog/
├── composer.json          # type: laravel-module, PSR-4 Modules\Blog\
├── module.json            # module manifest
├── config/blog.php
├── database/{migrations,factories,seeders}/
├── lang/
├── resources/views/
├── src/{Domain,Application,Infrastructure}/
│   └── Infrastructure/Providers/BlogServiceProvider.php
└── tests/
```

Prefer to start with nothing? `--layout=clean` scaffolds a namespace and a
provider, and nothing else — every convention folder is opt-in, and one you
never add costs nothing at boot:

```bash
php artisan make:module Ping --layout=clean
```

```
Modules/Ping/
├── composer.json
├── module.json
└── src/Providers/PingServiceProvider.php
```

Presets: `ddd` (default), `simple`, `contracts`, `clean`.

## Configuring a module

**Convention loads, attributes wire.** Config, migrations, views, routes,
translations and artisan commands (from `Console/` directories) load
automatically when their folders exist. Declare container bindings and
listeners with attributes:

```php
use Dem1Off\LaravelModular\Module\Attributes\Bind;
use Dem1Off\LaravelModular\Module\Attributes\Listen;
use Dem1Off\LaravelModular\Module\ModuleServiceProvider;

#[Bind(PostRepositoryInterface::class, EloquentPostRepository::class)]
#[Bind(FeedCache::class, RedisFeedCache::class, singleton: true)]
#[Listen(ChapterPublished::class, SendDigest::class)]
final class BlogServiceProvider extends ModuleServiceProvider {}
```

Need more than attributes? Override `register()`/`boot()` and call the parent —
it's a normal Laravel provider. See the
[docs](https://dem1-off.github.io/laravel-modular/basic-usage/attributes).

## Performance

Attributes reflect in development. In production, `php artisan module:cache`
compiles discovery, attributes **and** each module's resolved convention folders
into one PHP file — a request does zero reflection, zero filesystem scanning and
no stat calls per module. It's wired into `php artisan optimize`, and the usual
cached-artifact rule applies: add a `routes/` or `lang/` folder to a module and
rebuild the cache for it to take effect.

## Runtime API

```php
use Dem1Off\LaravelModular\Facades\Modules;

Modules::all();           // every module, keyed by name
Modules::enabled();       // only enabled ones
Modules::find('Blog');    // ModuleDescriptor|null
Modules::isEnabled('Blog');
Modules::path('Blog');    // absolute path

module_path('Blog', 'resources/views'); // path helper
```

## Customising behaviour

A module provider is a normal Laravel `ServiceProvider`. For anything beyond
attributes, override `register()`/`boot()` and call the parent:

```php
public function boot(): void
{
    parent::boot();

    Livewire::component('blog.feed', Feed::class);
}
```

Keep anything proprietary (navigation, mailing, metrics, …) in your application,
invoked from the module's `boot()` — never inside this package.

## Promoting a module to a standalone package

Because a module is already a Composer package and its namespace
(`Modules\Blog\`) never changes, promotion is mechanical:

1. **Move the directory to its own git repo**

   ```bash
   git subtree split --prefix=Modules/Blog -b blog-module
   # push that branch to a new repo, or copy Modules/Blog out
   ```

2. **Point the app at it via Composer** — swap the path entry for a VCS/version
   constraint in the root `composer.json`:

   ```jsonc
   // before — local development
   "repositories": [{ "type": "path", "url": "Modules/*" }],
   "require": { "acme/blog-module": "*" }

   // after — promoted package
   "repositories": [{ "type": "vcs", "url": "git@github.com:acme/blog-module.git" }],
   "require": { "acme/blog-module": "^1.0" }
   ```

3. **`composer update acme/blog-module`** — done.

No namespace changes, no provider rewrites: Laravel package auto-discovery reads
`extra.laravel.providers` from the module's own `composer.json`, exactly as it
did in-app. Tests, static-analysis config, and the `module_path()` helper all
keep working because the module's internal layout is unchanged.

> Tip: develop with `"type": "path"` + `"url": "Modules/*"` and `"symlink": true`
> so in-app modules and promoted packages behave identically during development.

## License

MIT.