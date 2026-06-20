# FAQ & Troubleshooting

[[toc]]

## FAQ

### Do module providers have to extend `ModuleServiceProvider`?

No. A module's providers can be plain `Illuminate\Support\ServiceProvider`s and
they will be discovered and registered. You only extend `ModuleServiceProvider`
to get convention loading (config/migrations/views/routes) and attribute wiring
(`#[Bind]`, `#[Listen]`).

### Do I have to use the DDD layout?

No. `make:module` has `ddd` (default), `simple` and `contracts` presets, and you
can publish the stubs (`--tag=modules-stubs`) to shape your own structure. See
[Creating a module](/basic-usage/creating-a-module).

### How can attribute-based config be fast?

In development attributes are reflected once per provider and memoised. In
production `php artisan module:cache` compiles discovery **and** attributes into
one PHP file, so a request does zero reflection and zero filesystem scanning.
See [Performance](/advanced/performance).

### Can a module declare its own Composer dependencies?

Yes — add them to the module's `composer.json` `require`. With the recommended
path-repository setup, `composer update` installs them like any package.

### Does it work with `config:cache` and `route:cache`?

Yes. Config is merged at boot, and route loading is skipped when routes are
cached, so Laravel's caches behave normally. Run `module:cache` too for the
fastest path.

### Can a module register more than one provider?

Yes. List them in the module's `module.json` `providers` array (or the module
`composer.json` `extra.laravel.providers`).

### Can I use this in a microservice architecture?

Yes — as a modular monolith that promotes modules to services, and for shared
contract packages between services. See
[How it works](/advanced/how-it-works) and
[Private distribution](/promotion/private-distribution).

## Troubleshooting

### `Class "Modules\X\...\XServiceProvider" not found`

The module's classes aren't autoloaded. Easiest fix: keep `modules.autoload`
enabled (the default) — modules are then autoloaded at runtime with no Composer
setup. If you turned it off to autoload via Composer, either:

```bash
composer require your-vendor/x-module:@dev   # path repository
composer dump-autoload
```

or add the module's PSR-4 to the app's root `composer.json` and
`composer dump-autoload`. See
[Installation → Autoloading modules](/getting-started/installation).

### A new module doesn't show up in `module:list`

- Run `composer require your-vendor/<name>-module:@dev` (path-repo) or
  `composer dump-autoload`.
- If you compiled the cache, it's stale — `php artisan module:clear`.

### Changes to a module aren't taking effect (production)

The compiled cache is stale. Rebuild or clear it:

```bash
php artisan module:clear      # or: php artisan optimize:clear
```

Don't run `module:cache` in development — it freezes discovery and attributes.

### A module's routes return 404

- Routes were cached before the module existed — `php artisan route:clear`.
- The module has no `routes/web.php` / `routes/api.php`, or `#[Module(routes: false)]`
  is set.
- Confirm the module is enabled: `php artisan module:list`.

### Config values are `null`

- The file must be `config/<module>.php` (lowercase) or `config/config.php`; it
  is merged under the **kebab-cased** module name (`config('my-module.key')`).
- Laravel's config cache is stale — `php artisan config:clear`.

### A disabled module still seems active

- The module name in `modules_statuses.json` must match the StudlyCase module
  name exactly.
- If the module is a Composer-discovered package, its provider self-guards and
  no-ops when disabled — clear caches (`php artisan optimize:clear`) if you
  changed the status while a compiled cache existed.

### Views aren't found (`View [x::y] not found`)

The view namespace is the **lowercase** module name, and `resources/views/` must
exist. Example: `view('billing::invoice')` →
`Modules/Billing/resources/views/invoice.blade.php`.

### In Docker, the package symlink is broken inside the container

A path-repository `symlink: true` pointing outside the mounted project directory
won't resolve in the container. Either set `"options": { "symlink": false }` so
Composer copies the package into `vendor/`, or mount the package directory into
the container too.

### Generated modules get the wrong vendor name

Set `modules.vendor` in `config/modules.php` (e.g. `'vendor' => 'acme'`) so
`make:module` names packages `acme/<name>-module`.
