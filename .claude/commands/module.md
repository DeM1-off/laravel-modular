---
description: Drive the dem1-off/laravel-modular workflow — create, link, sync, promote and generate inside modules
argument-hint: create Blog | link Billing Auth --hide-git | sync --all | promote Blog | make controller Blog ShowPost
allowed-tools: Bash(php artisan module:*), Bash(php artisan make:module*), Bash(composer update*), Bash(git update-index*), Read, Edit, Grep, Glob
---

You are operating in a Laravel project that uses the **dem1-off/laravel-modular**
package, where **each module is a real Composer package** (its own `composer.json`,
PSR-4, provider auto-discovery). Carry out the user's module request below.

## Request

$ARGUMENTS

## How to act

Map the request to the package's artisan commands and run the matching one. Do
**not** hand-edit `composer.json`, `modules_statuses.json` or create module files
by hand — always go through the commands so the structure stays promotion-ready.

| Intent | Command |
| --- | --- |
| Create / scaffold a module | `php artisan make:module {Name} [--layout=ddd\|simple\|contracts]` |
| List modules + status | `php artisan module:list` |
| Enable / disable (boot gating only) | `php artisan module:enable {Name}` / `module:disable {Name}` |
| Diagnose setup | `php artisan module:check` |
| Compile / clear prod cache | `php artisan module:cache` / `module:clear` |
| Promote a module to its own repo | `php artisan module:promote {Name} [--export=DIR]` (non-destructive — prints the plan) |
| Link module(s) for local dev | `php artisan module:link {Names…}\|--all [--hide-git] [--dry-run]` |
| Restore to a versioned package | `php artisan module:unlink {Names…}\|--all [--constraint=^1.2] [--hide-git] [--dry-run]` |
| Sync module package(s) to latest | `php artisan module:sync {Names…}\|--all [--check] [--dry-run]` |
| Generate a class in a module | `php artisan module:make-controller\|make-model\|make-action {Name} {Class} [--force]` |
| Generate a migration | `php artisan module:make-migration {Name} {name} [--table=]` |

### Rules

- **Multi-project local dev:** when the user is editing shared module packages,
  prefer `module:link … --hide-git` so the project's `git diff` shows only real
  code changes; the actual changes are committed inside each module's own repo.
  Restore with `module:unlink … --hide-git`.
- **`enable`/`disable` gate booting only** — they are *not* how you move a module
  in/out of the app. Use `link`/`unlink` for that.
- **`module:sync`** addresses modules by **module name** (`Billing`), not package
  name; it reports pinned-vs-installed then runs `composer update`. Use `--check`
  first if the user wants a dry look.
- Prefer `--dry-run` first for anything that rewrites `composer.json` when the
  user is unsure.
- After scaffolding or generating, briefly tell the user what was created and the
  next step (e.g. the suggested `composer update`).

If the request is ambiguous, ask one short clarifying question before running
anything that writes to disk or `composer.json`.