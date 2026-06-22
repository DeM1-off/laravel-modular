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

## In-module generators

Generate classes inside an existing module (DDD layout):

| Command | Creates |
| --- | --- |
| `module:make-controller {module} {name}` | `src/Infrastructure/Http/Controllers/{Name}Controller.php` |
| `module:make-model {module} {name}` | `src/Infrastructure/Persistence/Models/{Name}.php` |
| `module:make-action {module} {name}` | `src/Application/UseCases/{Name}.php` |
| `module:make-migration {module} {name} [--table=]` | timestamped migration in `database/migrations` |

```bash
php artisan module:make-controller Blog ShowPost
php artisan module:make-action Blog PublishPost
php artisan module:make-migration Blog create_posts_table --table=posts
```