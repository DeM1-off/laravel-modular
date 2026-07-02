# Configuration

After publishing, the package reads `config/modules.php`.

| Key | What it controls |
| --- | --- |
| `namespace` | Root PSR-4 namespace for modules (default `Modules`). A module keeps this namespace whether it lives in the app or as a standalone package — that is what makes promotion zero-churn. |
| `layout` | Default scaffold shape for `make:module`: `ddd` (`src/{Domain,Application,Infrastructure}`) or `simple` (`app/{Http,Models,Providers}`). |
| `paths.modules` | Directory holding in-app modules (the path-repository root). |
| `paths.app_folder` | Folder inside a module mapped to its root namespace (default `src/`). |
| `paths.generator.*` | Sub-paths used by `make:*` generators (DDD layout). |
| `statuses_file` | JSON map of `module => bool` gating which modules boot. |
| `manifest_file` | Per-module metadata file (`module.json`). |
| `vendor` | Composer vendor used when scaffolding a module's `composer.json`. |
| `auto_discover` | When `true`, the package registers each enabled module's providers itself. Set `false` if modules are wired through Composer path-repositories + Laravel package auto-discovery. |
| `scan_commands` | When `true`, artisan commands inside a module's `Console` directories register automatically (dev scan; `module:cache` compiles it for production). |
| `boundaries.allowed` | Sub-namespaces other modules may reference — a module's public surface (default `Contracts`, `Data`, `Events`, `Enums`). Used by `module:check --boundaries`. |

## Enabling and disabling modules

`modules_statuses.json` in the project root controls which modules boot:

```json
{
    "Blog": true,
    "Shop": false
}
```

A module with no entry defaults to **enabled**, so a freshly generated module is
live immediately. A **disabled** module is fully inert — its providers aren't
registered and (in runtime-autoload mode) its classes aren't even autoloaded.

Toggle from the CLI instead of editing the file by hand:

```bash
php artisan module:enable Shop
php artisan module:disable Shop
```

## Load order and dependencies

Set `"priority"` in a module's `module.json` to control registration order —
higher loads first, ties break alphabetically:

```json
{ "name": "Core", "priority": 100 }
```

When one module genuinely depends on another, declare it in `"requires"`
instead — required modules always load before their dependents (winning over
`priority`), and the dependency becomes checkable:

```json
{ "name": "Blog", "requires": ["Shop"] }
```

`module:enable`/`module:disable` warn when a requirement is missing, disabled,
or still depended upon, and `module:check` fails on broken requirements. Prefer
[contract modules](/basic-usage/contract-modules) over ordering where possible —
lazy bindings don't care about order.

## Diagnosing

`php artisan module:check` reports the autoload mode, cache status, any providers
that aren't autoloadable, broken `requires` declarations, and binding conflicts
between modules.

`php artisan module:check --boundaries` additionally scans every enabled module
for references into another module's internals — anything outside the
`boundaries.allowed` sub-namespaces — and, for modules that declare `requires`,
for undeclared dependencies. Wire it into CI to keep module boundaries honest.