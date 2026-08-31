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
php artisan make:module Ping --layout=clean       # src/Providers only — every folder opt-in
```

| Preset | Structure | Provider | Best for |
| --- | --- | --- | --- |
| `ddd` | `src/Domain`, `src/Application`, `src/Infrastructure` | `src/Infrastructure/Providers` | modules with real domain logic; enforces layer boundaries |
| `simple` | `app/Http`, `app/Models`, `app/Providers` | `app/Providers` | straightforward CRUD modules |
| `contracts` | `src/Contracts`, `src/Data`, `src/Events`, `src/Enums` | `src/Providers` (empty `#[Module]`) | shared kernel linking modules via interfaces — see [Contract modules](/basic-usage/contract-modules) |
| `clean` | `src/Providers` — nothing else | `src/Providers` | a module that should carry no folder it does not use; add each convention folder when you need it |

Every preset keeps the `Modules\Blog\` root namespace, so promotion stays
zero-churn whichever you pick.

### A clean module

`--layout=clean` writes the bare minimum — a `composer.json`, a `module.json`
and a provider:

```
Modules/Ping/
├── composer.json
├── module.json
└── src/Providers/PingServiceProvider.php
```

Every convention folder is opt-in from there. Create the one you need and it
loads itself, no registration:

| Add | And you get |
| --- | --- |
| `config/ping.php` | merged under the `ping` config key |
| `database/migrations/` | migrations loaded |
| `resources/views/` | views under the `ping::` namespace |
| `routes/web.php` | routes behind the `web` middleware |
| `routes/api.php` | routes behind `api`, prefixed `/api` |
| `lang/` | translations under `ping::`, plus JSON |
| `src/**/Console/` | artisan commands discovered by convention |

Because [`module:cache`](/advanced/performance) resolves folders once at compile
time, a module that stays clean costs nothing at boot — and a folder added
afterwards needs a cache rebuild to take effect, the same as `config:cache`.

## Overwriting

Use `--force` to overwrite an existing module:

```bash
php artisan make:module Blog --force
```

Next: [configure it with attributes](/basic-usage/attributes).