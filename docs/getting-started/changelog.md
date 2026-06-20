# Changelog

All notable changes are tracked in
[`CHANGELOG.md`](https://github.com/dem1-off/laravel-modular/blob/main/CHANGELOG.md),
following [Keep a Changelog](https://keepachangelog.com/) and
[Semantic Versioning](https://semver.org/).

## 1.1.0 — 2026-06-21

### Added
- Attribute-driven wiring: `#[Bind]`, `#[Listen]`, optional `#[Module]`.
- Convention loading of config, migrations, views and routes.
- Compiled cache (`module:cache` / `module:clear`), wired into `optimize`.
- `Modules` facade and `module_path()` helper.
- `make:module` with `ddd`, `simple`, `contracts` presets + in-module generators.
- Lifecycle commands and publishable stubs.
- Compatibility with `module.json` and `modules_statuses.json`.