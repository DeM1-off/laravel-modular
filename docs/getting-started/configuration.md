# Configuration

After publishing, the package reads `config/modules.php`.

| Key | What it controls |
| --- | --- |
| `namespace` | Root PSR-4 namespace for modules (default `Modules`). A module keeps this namespace whether it lives in the app or as a standalone package — that is what makes promotion zero-churn. |
| `layout` | Default scaffold shape for `make:module`: `ddd` (`src/{Domain,Application,Infrastructure}`) or `simple` (`app/{Http,Models,Providers}`). |
| `paths.modules` | Directory holding in-app modules (the path-repository root). |
| `paths.app_folder` | Folder inside a module mapped to its root namespace (default `src/`). |
| `paths.generator.*` | Sub-paths used by `make:*` generators (DDD layout). |
| `statuses_file` | JSON map of `module => bool` gating which modules boot. |
| `manifest_file` | Per-module metadata file (`module.json`). |
| `vendor` | Composer vendor used when scaffolding a module's `composer.json`. |
| `auto_discover` | When `true`, the package registers each enabled module's providers itself. Set `false` if modules are wired through Composer path-repositories + Laravel package auto-discovery. |

## Enabling and disabling modules

`modules_statuses.json` in the project root controls which modules boot:

```json
{
    "Blog": true,
    "Shop": false
}
```

A module with no entry defaults to **enabled**, so a freshly generated module is
live immediately. A disabled module is skipped at registration and also
self-guards inside its service provider.