# Artisan commands

## Module lifecycle

| Command | Description |
| --- | --- |
| `make:module {name} [--layout=ddd\|simple\|contracts\|clean] [--force]` | Scaffold a new module. |
| `module:list` | List all modules with their status. |
| `module:enable {module}` | Enable a module (writes `modules_statuses.json`). |
| `module:disable {module}` | Disable a module. |
| `module:cache` | Compile discovery + attributes into a fast cache (see [Performance](/advanced/performance)). |
| `module:clear` | Remove the compiled cache (and the dev scan cache). |
| `module:check [--boundaries]` | Diagnose the setup: autoload mode, cache status, missing providers, broken `requires`, binding conflicts. `--boundaries` also flags cross-module references into another module's internals and undeclared dependencies. |
| `module:promote {module} [--export=PATH]` | Print the promotion plan; `--export` copies the module out (non-destructive). |

## Local-path linking

Switch modules between local path development and a versioned Composer package
(the reverse of promotion). Both edit the root `composer.json`.

| Command | Description |
| --- | --- |
| `module:link {modules?*} [--all] [--hide-git] [--dry-run]` | Point the root `composer.json` at module(s) as local `path` repositories for development. |
| `module:unlink {modules?*} [--all] [--constraint=] [--hide-git] [--dry-run]` | Restore module(s) to a versioned package; `--constraint` pins a version instead of the recorded one. |

```bash
php artisan module:link Blog
php artisan module:link --all
php artisan module:unlink Blog --constraint=^1.2
```

`--hide-git` sets git's `skip-worktree` bit on `composer.json` and
`composer.lock` so the linking churn never shows up in `git status` / `git diff`
while modules are linked. `module:unlink --hide-git` clears the bit again. Your
real diff then lives only in each module's own repository — see
[Customising behaviour](/advanced/extending-the-core#the-operations-layer) and
the linking recipe.

```bash
php artisan module:link Billing Auth --hide-git   # develop, composer.json stays "clean"
# ...edit + commit inside each module's repo...
php artisan module:unlink --all --hide-git        # restore git tracking
```

## Syncing module packages

When the same modules are shared across several projects as versioned packages,
`module:sync` brings a project's modules up to the version Composer resolves —
addressed by **module name**, not package name — and reports what is pinned vs.
installed before running `composer update`.

| Command | Description |
| --- | --- |
| `module:sync {modules?*} [--all] [--check] [--dry-run]` | Report pinned vs installed version for the module package(s), then `composer update` them. `--check` only reports; `--dry-run` passes through to Composer. |

```bash
php artisan module:sync --check          # just show what would change
php artisan module:sync Billing Auth     # composer update acme/billing-module acme/auth-module
php artisan module:sync --all            # sync every required module
```

Only modules the app actually requires are syncable; a module that has not been
promoted/required yet is reported as unmanaged and skipped.

## In-module generators

Generate classes inside an existing module (DDD layout):

| Command | Creates |
| --- | --- |
| `module:make-controller {module} {name} [--force]` | `src/Infrastructure/Http/Controllers/{Name}Controller.php` |
| `module:make-model {module} {name} [--force]` | `src/Infrastructure/Persistence/Models/{Name}.php` |
| `module:make-action {module} {name} [--force]` | `src/Application/UseCases/{Name}.php` |
| `module:make-request {module} {name} [--force]` | `src/Infrastructure/Http/Requests/{Name}Request.php` |
| `module:make-event {module} {name} [--force]` | `src/Domain/Events/{Name}.php` |
| `module:make-listener {module} {name} [--force]` | `src/Application/Listeners/{Name}.php` |
| `module:make-job {module} {name} [--force]` | `src/Application/Jobs/{Name}.php` |
| `module:make-command {module} {name} [--force]` | `src/Infrastructure/Console/{Name}Command.php` (auto-discovered — no registration needed) |
| `module:make-factory {module} {name} [--force]` | `database/factories/{Name}Factory.php` |
| `module:make-seeder {module} {name} [--force]` | `database/seeders/{Name}Seeder.php` |
| `module:make-test {module} {name} [--force]` | `tests/Feature/{Name}Test.php` |
| `module:make-migration {module} {name} [--table=]` | timestamped migration in `database/migrations` |

```bash
php artisan module:make-controller Blog ShowPost
php artisan module:make-action Blog PublishPost
php artisan module:make-command Blog PublishPosts    # signature blog:publish-posts
php artisan module:make-migration Blog create_posts_table --table=posts
```