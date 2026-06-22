# Changelog

All notable changes are tracked in
[`CHANGELOG.md`](https://github.com/dem1-off/laravel-modular/blob/main/CHANGELOG.md),
following [Keep a Changelog](https://keepachangelog.com/) and
[Semantic Versioning](https://semver.org/).

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