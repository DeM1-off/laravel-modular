# AGENTS.md — working with `dem1-off/laravel-modular`

Guidance for AI coding assistants (Claude Code, Cursor, Copilot, Windsurf, …)
working in a project that uses this package. Read this before generating modules,
wiring services, or touching `composer.json`.

## Mental model (the one thing to get right)

A **module is a real Composer package from the first commit** — its own
`composer.json` (`type: laravel-module`), its own PSR-4 namespace, its own
provider registered via `extra.laravel.providers`.

The module's **namespace never changes** whether it lives in-app or as a
standalone package: `Modules\Blog\…` either way. That is the whole point —
promoting a module to its own repo is a `composer.json` change, **not** a
refactor. Never rename a module's namespace to "extract" it.

## Module layout (DDD preset, default)

```
Modules/Blog/
├── composer.json          # type: laravel-module, PSR-4 Modules\Blog\, extra.laravel.providers
├── module.json            # manifest: name, alias, priority, requires, providers (optional)
├── config/blog.php
├── database/{migrations,factories,seeders}/
├── lang/
├── resources/views/
├── src/{Domain,Application,Infrastructure}/
│   └── Infrastructure/Providers/BlogServiceProvider.php
└── tests/
```

`simple` and `contracts` presets also exist (`--layout=simple|contracts`).

## How wiring works: convention loads, attributes wire

- **Convention loads automatically** when the folder exists: `config/`,
  `database/migrations/`, `resources/views/`, `lang/`, routes, and artisan
  commands inside `Console/` directories. The provider is usually empty — do
  **not** add manual `loadMigrationsFrom`/`mergeConfigFrom`/`commands()` calls.
- **Declare container wiring with PHP attributes on the provider**, not in
  `register()`:

```php
use Dem1Off\LaravelModular\Module\Attributes\{Bind, Listen};
use Dem1Off\LaravelModular\Module\ModuleServiceProvider;

#[Bind(PostRepositoryInterface::class, EloquentPostRepository::class)]
#[Bind(FeedCache::class, RedisFeedCache::class, singleton: true)]
#[Listen(ChapterPublished::class, SendDigest::class)]
final class BlogServiceProvider extends ModuleServiceProvider {}
```

Available attributes (namespace `Dem1Off\LaravelModular\Module\Attributes`):
`#[Bind]`, `#[Singleton]`, `#[Scoped]`, `#[Listen]`, `#[Middleware]`,
`#[Provides]` (auto-bind an implementation to its interface), `#[Module]`.

Need more than attributes? Override `register()`/`boot()` and **call
`parent::` first** — it is a normal Laravel provider. Keep anything proprietary
(navigation, metrics, mailing) in the application, invoked from the module's
`boot()`, never inside this package.

## Commands

| Command | Purpose |
| --- | --- |
| `php artisan make:module Blog [--layout=ddd\|simple\|contracts]` | Scaffold a promotion-ready module. |
| `php artisan module:make-{controller,model,action,migration,request,event,listener,job,command,factory,seeder,test} Blog Name` | Generators scoped to a module (DDD layout paths). |
| `php artisan module:list` | List modules and their enabled state. |
| `php artisan module:enable Blog` / `module:disable Blog` | Toggle boot via `modules_statuses.json` (does **not** change source location). |
| `php artisan module:check [--boundaries]` | Diagnostics: providers, `requires`, conflicts; `--boundaries` flags cross-module references outside `Contracts`/`Data`/`Events`/`Enums` and undeclared dependencies. |
| `php artisan module:cache` / `module:clear` | Compile discovery + attributes into one PHP file for prod (wired into `optimize`). |
| `php artisan module:promote Blog [--export=DIR]` | Print the plan to move a module to its own repo (non-destructive). |
| `php artisan module:link Blog\|Blog Billing\|--all [--hide-git] [--dry-run]` | Switch module(s) to local path development (reverse of promotion). `--hide-git` skip-worktrees `composer.json`/`lock` so linking churn stays out of the diff. |
| `php artisan module:unlink Blog\|--all [--constraint=^1.2] [--hide-git] [--dry-run]` | Restore module(s) to a versioned package; `--hide-git` reverses the skip-worktree. |
| `php artisan module:sync Blog\|--all [--check] [--dry-run]` | `composer update` the module package(s) by module name; reports pinned vs installed version. `--check` reports only. |

## Runtime API

```php
use Dem1Off\LaravelModular\Facades\Modules;

Modules::all();            // every module, keyed by name
Modules::enabled();       // only enabled
Modules::find('Blog');    // ModuleDescriptor|null
Modules::isEnabled('Blog');
Modules::path('Blog');    // absolute path (throws if unknown)

module_path('Blog', 'resources/views'); // path helper
```

## Promotion ↔ local development workflow

- **Promote** (module → standalone repo): `module:promote` prints the plan;
  `git subtree split --prefix=Modules/Blog`, push to a new repo, swap the
  Composer `path` entry for a `vcs`/registry constraint. No code changes.
- **Link back for cross-module edits** (standalone → in-app, temporary):
  `module:link` adds the `Modules/*` path repository (`symlink: true`) and pins
  the target package(s) to `*`. Edit several modules together, push from each
  module's **own** git repo, then `module:unlink` restores the recorded version
  constraint and removes the path repository. The previous constraint is saved in
  `bootstrap/cache/module-links.json`, so the round-trip is exact.
- **Important:** `enable`/`disable` gate booting only — they are **not** how you
  switch a module between in-app and standalone. Use `link`/`unlink` for that.

## Internal architecture (when modifying this package)

Commands are **thin adapters**: they parse input, call a use-case, and render
the result. The actual work lives in `src/Operations/` (the application layer),
which is console-free and unit-testable without artisan:

- `ComposerManifest`, `LinkState` — value objects over JSON files.
- `LinkModules` / `UnlinkModules`, `SyncModules` (→ `SyncEntry`),
  `HideFromGit` (skip-worktree toggling via an injected git runner),
  `PromoteModule` (→ `PromotionPlan`), `ScaffoldModule` (+ `ModuleLayout`),
  `GenerateModuleClass` (+ `ClassLayer`) / `GenerateModuleMigration`,
  `CompileModuleCache` / `ClearModuleCache`, `DiagnoseModules` (→ `Diagnosis`),
  `CheckBoundaries`, `SetModuleStatus`.
- `Manager/` (`ModuleManager`, `ModuleDescriptor`) is the domain/query layer.
  `ModuleManager::all()` orders by priority, then topologically by `requires`.
- `Module/` holds the runtime: `ModuleServiceProvider` (convention loading),
  `AttributeParser`, `ProvidesScanner` (#[Provides] auto-binding) and
  `CommandScanner` (Console-directory command discovery) — the scanners run
  live in dev and feed `CompileModuleCache` for production.

When adding a command with real logic, **put the logic in an Operation and keep
the command thin** — match this pattern. Trivial queries that just delegate to
`ModuleManager` (e.g. `module:list`) need no operation; don't add passthroughs.
Unit-test operations directly (see `tests/Unit/`); reserve Feature tests for the
console wiring.

## Quality gates — run before declaring work done

```bash
vendor/bin/pint --test     # PSR-12 / Laravel preset, declare_strict_types enforced
vendor/bin/phpstan analyse # level 8 + Larastan, must be green
vendor/bin/pest            # Pest 3
```

House rules for generated code in this repo:
- `declare(strict_types=1);` at the top of **every** PHP file.
- Full native type hints (params + returns); PHPStan level 8 will reject missing ones.
- `final` classes by default; small, single-purpose command/service classes.
- Match the surrounding style — read a neighbouring file before adding a new one.

## Don'ts

- ❌ Don't rename a module's namespace to extract it — promotion is mechanical.
- ❌ Don't add manual config/migration/view loading the conventions already handle.
- ❌ Don't put proprietary app concerns inside a module's package code.
- ❌ Don't hand-edit `composer.json` to link modules — use `module:link`/`module:unlink`.
- ❌ Don't skip the quality gates; `pint`, `phpstan` (level 8) and `pest` must stay green.
