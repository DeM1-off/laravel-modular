# Creating a module

```bash
php artisan make:module Blog
```

This scaffolds a promotion-ready package under your modules path:

```
Modules/Blog/
├── composer.json          # type: laravel-module, PSR-4 Modules\Blog\
├── module.json            # manifest
├── config/blog.php
├── database/{migrations,factories,seeders}/
├── resources/views/
├── src/{Domain,Application,Infrastructure}/
│   └── Infrastructure/Providers/BlogServiceProvider.php
└── tests/
```

The generated provider has an empty body — config, migrations, views and routes
load by convention. Add `#[Bind]`/`#[Listen]` attributes only when you need them:

```php
final class BlogServiceProvider extends ModuleServiceProvider {}
```

See [Configuring a module](/basic-usage/attributes).

The module is added to `modules_statuses.json` as enabled, so it boots
immediately.

## Layout presets

The scaffold shape follows `modules.layout` (default `ddd`). Override per command
with `--layout`:

```bash
php artisan make:module Blog --layout=ddd        # src/{Domain,Application,Infrastructure}
php artisan make:module Blog --layout=simple     # app/{Http,Models,Providers}
php artisan make:module Shared --layout=contracts # src/{Contracts,Data,Events,Enums}
```

| Preset | Structure | Provider | Best for |
| --- | --- | --- | --- |
| `ddd` | `src/Domain`, `src/Application`, `src/Infrastructure` | `src/Infrastructure/Providers` | modules with real domain logic; enforces layer boundaries |
| `simple` | `app/Http`, `app/Models`, `app/Providers` | `app/Providers` | straightforward CRUD modules |
| `contracts` | `src/Contracts`, `src/Data`, `src/Events`, `src/Enums` | `src/Providers` (empty `#[Module]`) | shared kernel linking modules via interfaces — see [Contract modules](/basic-usage/contract-modules) |

Both layouts keep the `Modules\Blog\` root namespace, so promotion stays
zero-churn either way.

## Overwriting

Use `--force` to overwrite an existing module:

```bash
php artisan make:module Blog --force
```

Next: [configure it with attributes](/basic-usage/attributes).