# Changelog

All notable changes to `dem1-off/laravel-modular` are documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- `#[Singleton]` and `#[Scoped]` lifetime shorthands for auto-binding.
- `#[Provides(tag:)]` to tag implementations, resolvable via `app()->tagged()`.
- `#[Middleware]` to register a route middleware alias from a provider.
- Module load order via `module.json` `priority` (higher loads first).
- `module:check` doctor command (autoload mode, cache status, missing providers,
  binding conflicts).
- Development scan cache for `#[Provides]` (keyed by file count + mtime), cleared
  by `module:clear`.

### Changed
- A disabled module is now fully inert: its PSR-4 is not registered at runtime,
  so its classes don't autoload either.

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

[Unreleased]: https://github.com/dem1-off/laravel-modular/compare/v1.1.0...HEAD
[1.1.0]: https://github.com/dem1-off/laravel-modular/releases/tag/v1.1.0