# Changelog

All notable changes to `dem1-off/laravel-modular` are documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.6.0] - 2026-08-31

### Added
- `make:module --layout=clean` — a fourth preset that scaffolds a module with
  nothing but a namespace and a provider (`src/Providers`, `composer.json`,
  `module.json`). Every convention folder is opt-in: create `routes/`, `lang/`,
  `config/` and the rest only when the module needs them.

### Changed
- `module:cache` now also compiles each module's **resolved convention paths**,
  so a cached boot performs no `is_file`/`is_dir` checks per module. A module
  that ships none of the optional folders costs nothing at boot. Because folder
  resolution is baked, adding a convention folder to a module now needs a cache
  rebuild to take effect — the same contract as `config:cache`; development,
  which has no cache, is unaffected.
- Publishable paths (`modules-views`, `modules-lang`, `modules-config`,
  `modules-stubs`) and artisan command registration are declared on console
  boots only, so an HTTP request no longer builds them.
- Development scanning got cheaper: a module's file tree is walked once per
  process and shared by the `#[Provides]` scanner and the command scanner
  instead of once each; command discovery is now memoised against the module's
  change signature like `#[Provides]` already was (both share
  `bootstrap/cache/modular-scan.php`, cleared by `module:clear`); and files are
  filtered on their source text before being reflected, so a cold scan no
  longer autoloads a module's entire codebase.
- Smaller boot costs: the compiled cache is read with a single filesystem
  operation, the enabled-module list is memoised, and runtime PSR-4
  registration reuses Composer's already-registered loader instead of
  re-including `vendor/autoload.php` on every request.

## [1.5.0] - 2026-07-02

### Added
- Convention loading of translations: a module's `lang/` directory (or legacy
  `resources/lang/`) loads automatically — PHP files under the module's
  lowercase namespace (`trans('blog::messages.welcome')`) plus JSON files;
  publishable with `--tag=modules-lang`. Toggle per module with
  `#[Module(lang: false)]`.
- Convention discovery of artisan commands: `Illuminate\Console\Command`
  subclasses inside a module's `Console` directories (any depth under the app
  folder) register automatically — scanned on console boots in development,
  compiled by `module:cache` for production. Toggle with
  `modules.scan_commands`; `#[Module(commands: [...])]` remains for commands
  living elsewhere.
- Eight new in-module generators: `module:make-request`, `module:make-event`,
  `module:make-listener`, `module:make-job`, `module:make-command`,
  `module:make-factory`, `module:make-seeder` and `module:make-test`.
- Inter-module dependencies via a `requires` list in `module.json`: required
  modules now load before their dependents (winning over `priority`),
  `module:enable` warns when a requirement is missing or disabled,
  `module:disable` warns when still-enabled modules depend on the disabled one,
  and `module:check` fails when an enabled module's requirement is missing or
  disabled.
- `module:check --boundaries` — cross-module boundary analysis. Reports when a
  module references another module outside its public surface
  (`Contracts`/`Data`/`Events`/`Enums` by default, configurable via
  `modules.boundaries.allowed`) and, for modules that declare `requires`, any
  undeclared dependency.

### Changed
- `make:module` now scaffolds a `lang/` directory and a `requires` field in
  `module.json`.
- Re-run `php artisan module:cache` after upgrading: the compiled settings now
  carry the translation toggle and discovered commands.

## [1.4.0] - 2026-06-24

### Added
- `module:link --hide-git` / `module:unlink --hide-git`: toggle git's
  `skip-worktree` bit on `composer.json` / `composer.lock` so the linking churn
  stays out of `git status` and `git diff` while modules are linked locally.
- `module:sync` — `composer update` module package(s) addressed by module name,
  with a report of the pinned vs. installed version (`--check` to report only,
  `--dry-run` to pass through to Composer). Convenient for keeping several
  projects that share the same module packages up to date.

## [1.3.0] - 2026-06-24

### Added
- Console-free `Operations` layer: each artisan command now delegates to a
  plain-PHP use-case under `src/Operations/`, so the behaviour can be unit
  tested without booting the console — `LinkModules`, `UnlinkModules`,
  `ComposerManifest`, `LinkState`, `PromoteModule`, `PromotionPlan`,
  `CompileModuleCache`, `ClearModuleCache`, `DiagnoseModules`, `ScaffoldModule`,
  `ModuleLayout`, `GenerateModuleClass`, `GenerateModuleMigration`, `ClassLayer`.
- Unit coverage for the `Operations` layer and a feature test for `module:link`.

### Changed
- The artisan commands are now thin adapters over the `Operations` layer; their
  signatures and behaviour are unchanged.
- The abstract `ModuleGeneratorCommand` base now declares a single
  `layer(): ClassLayer` method in place of the previous `stub()`, `layerPath()`,
  `layerNamespace()` and `classSuffix()` hooks. This only affects code that
  subclasses `ModuleGeneratorCommand` directly to add a custom generator.

## [1.2.0] - 2026-06-22

### Added
- `#[Singleton]` and `#[Scoped]` lifetime shorthands for auto-binding.
- `#[Provides(tag:)]` to tag implementations, resolvable via `app()->tagged()`.
- `#[Middleware]` to register a route middleware alias from a provider.
- Module load order via the `module.json` `priority` field (higher loads first,
  ties broken alphabetically).
- `module:check` doctor command — reports autoload mode, cache status,
  non-autoloadable providers, and binding conflicts between modules.
- Development scan cache for `#[Provides]` (keyed by file count + newest mtime),
  cleared by `module:clear`.

### Changed
- A disabled module is now fully inert: its PSR-4 namespace is not registered at
  runtime, so its classes no longer autoload either.

### Fixed
- Ship the package service provider and the `module:clear` command, which were
  missing from version control — a fresh install could not autoload the provider.

## [1.1.0] - 2026-06-21

### Added
- Attribute-driven module wiring: `#[Bind]`, `#[Listen]`, and an optional
  `#[Module]` override.
- `#[Provides]` auto-binding — an implementation declares what it provides and is
  bound automatically (scanned in dev, compiled by `module:cache`). Toggle with
  `modules.scan_bindings`.
- Runtime autoloading (`modules.autoload`, on by default) so a module works by
  just existing — no Composer package or root PSR-4 required.
- Convention loading of config, migrations, views and routes (no declaration).
- Compiled cache (`module:cache` / `module:clear`) for zero-reflection,
  zero-scan production requests; wired into `php artisan optimize`.
- `Modules` facade and `module_path()` helper for runtime queries.
- `make:module` generator with `ddd`, `simple` and `contracts` layout presets.
- In-module generators: `module:make-controller`, `module:make-model`,
  `module:make-action`, `module:make-migration`.
- Lifecycle commands: `module:list`, `module:enable`, `module:disable`,
  `module:promote`.
- Publishable stubs (`--tag=modules-stubs`) for customising the scaffold.
- Compatibility with `module.json` and `modules_statuses.json`.
- Test suite (Pest + Testbench), PHPStan (Larastan) config, Pint config, and a
  GitHub Actions CI workflow.

[Unreleased]: https://github.com/dem1-off/laravel-modular/compare/1.6.0...HEAD
[1.6.0]: https://github.com/dem1-off/laravel-modular/compare/1.5.0...1.6.0
[1.5.0]: https://github.com/dem1-off/laravel-modular/compare/1.4.0...1.5.0
[1.4.0]: https://github.com/dem1-off/laravel-modular/compare/1.3.0...1.4.0
[1.3.0]: https://github.com/dem1-off/laravel-modular/compare/1.2.0...1.3.0
[1.2.0]: https://github.com/dem1-off/laravel-modular/compare/1.1.0...1.2.0
[1.1.0]: https://github.com/dem1-off/laravel-modular/releases/tag/1.1.0