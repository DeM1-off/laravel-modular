# Artisan commands

## Module lifecycle

| Command | Description |
| --- | --- |
| `make:module {name} [--layout=ddd\|simple\|contracts] [--force]` | Scaffold a new module. |
| `module:list` | List all modules with their status. |
| `module:enable {module}` | Enable a module (writes `modules_statuses.json`). |
| `module:disable {module}` | Disable a module. |
| `module:cache` | Compile discovery + attributes into a fast cache (see [Performance](/advanced/performance)). |
| `module:clear` | Remove the compiled cache (and the dev scan cache). |
| `module:check` | Diagnose the setup: autoload mode, cache status, missing providers, binding conflicts. |
| `module:promote {module} [--export=PATH]` | Print the promotion plan; `--export` copies the module out (non-destructive). |

## Local-path linking

Switch modules between local path development and a versioned Composer package
(the reverse of promotion). Both edit the root `composer.json`.

| Command | Description |
| --- | --- |
| `module:link {modules?*} [--all] [--dry-run]` | Point the root `composer.json` at module(s) as local `path` repositories for development. |
| `module:unlink {modules?*} [--all] [--constraint=] [--dry-run]` | Restore module(s) to a versioned package; `--constraint` pins a version instead of the recorded one. |

```bash
php artisan module:link Blog
php artisan module:link --all
php artisan module:unlink Blog --constraint=^1.2
```

## In-module generators

Generate classes inside an existing module (DDD layout):

| Command | Creates |
| --- | --- |
| `module:make-controller {module} {name} [--force]` | `src/Infrastructure/Http/Controllers/{Name}Controller.php` |
| `module:make-model {module} {name} [--force]` | `src/Infrastructure/Persistence/Models/{Name}.php` |
| `module:make-action {module} {name} [--force]` | `src/Application/UseCases/{Name}.php` |
| `module:make-migration {module} {name} [--table=]` | timestamped migration in `database/migrations` |

```bash
php artisan module:make-controller Blog ShowPost
php artisan module:make-action Blog PublishPost
php artisan module:make-migration Blog create_posts_table --table=posts
```