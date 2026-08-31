# Changelog

All notable changes are tracked in
[`CHANGELOG.md`](https://github.com/dem1-off/laravel-modular/blob/main/CHANGELOG.md),
following [Keep a Changelog](https://keepachangelog.com/) and
[Semantic Versioning](https://semver.org/).

## 1.6.0 — 2026-08-31

### Added
- `make:module --layout=clean` — scaffolds a module with nothing but a namespace
  and a provider; every convention folder is opt-in.

### Changed
- `module:cache` compiles each module's resolved convention paths, so a cached
  boot does no `is_file`/`is_dir` checks per module. Adding a convention folder
  now needs a cache rebuild to take effect (development is unaffected).
- Publishing and artisan command registration happen on console boots only.
- Cheaper development scanning: one shared tree walk per module, memoised
  command discovery, and source-text filtering before reflection.

## 1.5.0 — 2026-07-02

### Added
- Convention loading of translations (`lang/`, PHP + JSON, publishable with
  `--tag=modules-lang`); toggle with `#[Module(lang: false)]`.
- Convention discovery of artisan commands from a module's `Console`
  directories (dev scan + compiled by `module:cache`); toggle with
  `modules.scan_commands`.
- New generators: `module:make-request`, `module:make-event`,
  `module:make-listener`, `module:make-job`, `module:make-command`,
  `module:make-factory`, `module:make-seeder`, `module:make-test`.
- Inter-module dependencies via `module.json` `requires`: dependency-aware load
  order, enable/disable warnings, and `module:check` failures for broken
  requirements.
- `module:check --boundaries` — flags references into another module's
  internals (everything outside `Contracts`/`Data`/`Events`/`Enums` by default)
  and undeclared dependencies.

### Changed
- `make:module` scaffolds `lang/` and a `requires` field; re-run
  `php artisan module:cache` after upgrading.

## 1.4.0 — 2026-06-24

### Added
- `module:link --hide-git` / `module:unlink --hide-git` to keep the linking
  churn (`composer.json` / `composer.lock`) out of git while developing locally.
- `module:sync` — `composer update` module package(s) by module name, with a
  pinned-vs-installed report (`--check`, `--dry-run`). Handy for keeping several
  projects that share the same module packages in sync.

## 1.3.0 — 2026-06-24

### Added
- Console-free `Operations` layer: each command delegates to a plain-PHP
  use-case under `src/Operations/`, testable without the console.
- Unit coverage for the `Operations` layer and a `module:link` feature test.

### Changed
- Commands are now thin adapters over the `Operations` layer; their signatures
  and behaviour are unchanged.
- `ModuleGeneratorCommand` now declares a single `layer(): ClassLayer` hook
  (was `stub()`/`layerPath()`/`layerNamespace()`/`classSuffix()`) — only affects
  custom generators that subclass it directly.

## 1.2.0 — 2026-06-22

### Added
- `#[Singleton]` and `#[Scoped]` lifetime shorthands.
- `#[Provides(tag:)]` tagged collections, resolvable via `app()->tagged()`.
- `#[Middleware]` to register a route middleware alias.
- Module load order via `module.json` `priority`.
- `module:check` doctor command.
- Development scan cache for `#[Provides]`.

### Changed
- A disabled module is now fully inert (its classes no longer autoload).

### Fixed
- Ship the package service provider and `module:clear` command that were missing
  from version control.

## 1.1.0 — 2026-06-21

### Added
- Attribute-driven wiring: `#[Bind]`, `#[Listen]`, optional `#[Module]`.
- Convention loading of config, migrations, views and routes.
- Compiled cache (`module:cache` / `module:clear`), wired into `optimize`.
- `Modules` facade and `module_path()` helper.
- `make:module` with `ddd`, `simple`, `contracts` presets + in-module generators.
- Lifecycle commands and publishable stubs.
- Compatibility with `module.json` and `modules_statuses.json`.