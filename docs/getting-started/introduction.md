# Introduction

**Laravel Modular** is modular architecture tooling for Laravel. It lets you
build DDD modules — split into Domain, Application and Infrastructure layers —
that are **real Composer packages from day one**. Because every module is
already a package, any module can be promoted to a standalone, independently
versioned package with zero code churn.

## Highlights

- **Attribute-first configuration** — a trivial module's provider has an empty body.
- **A generic core with no project coupling** — navigation, mailing, metrics, etc.
  plug in as extensions; the package never learns about them.
- **A designed-in promotion path** — module namespaces never change, so moving a
  module into its own repo is a Composer change, not a refactor.
- **Familiar layout** — works with the `Modules/` directory, `module.json`,
  `modules_statuses.json`, and `module_path()` conventions, so existing projects
  interoperate.

## A module at a glance

```
Modules/Blog/
├── composer.json          # type: laravel-module, PSR-4 Modules\Blog\
├── module.json            # manifest
├── config/blog.php
├── database/{migrations,factories,seeders}/
├── resources/views/
├── src/{Domain,Application,Infrastructure}/
│   └── Infrastructure/Providers/BlogServiceProvider.php
└── tests/
```

Continue to [Installation](/getting-started/installation).