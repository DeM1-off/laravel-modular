# Promote to a package

Because a module is already a Composer package and its namespace
(`Modules\Blog\`) never changes, promoting it to a standalone, independently
versioned package is mechanical — no refactor.

## 1. Move the directory to its own repo

```bash
git subtree split --prefix=Modules/Blog -b blog-module
# push that branch to a new repo, or copy Modules/Blog out
```

## 2. Point the app at it via Composer

Swap the path entry for a VCS/version constraint in the root `composer.json`:

```jsonc
// before — local development
"repositories": [{ "type": "path", "url": "Modules/*" }],
"require": { "acme/blog-module": "*" }

// after — promoted package
"repositories": [{ "type": "vcs", "url": "git@github.com:acme/blog-module.git" }],
"require": { "acme/blog-module": "^1.0" }
```

## 3. Update

```bash
composer update acme/blog-module
```

Done. No namespace changes, no provider rewrites: Laravel package auto-discovery
reads `extra.laravel.providers` from the module's own `composer.json`, exactly as
it did in-app. Tests, static-analysis config, and the `module_path()` helper all
keep working because the module's internal layout is unchanged.

::: tip
Develop with `"type": "path"` + `"url": "Modules/*"` and `"symlink": true` so
in-app modules and promoted packages behave identically during development.
:::

## Keeping it private

The public Packagist is optional. To reuse a module across your own apps without
publishing it, see [Private package distribution](/promotion/private-distribution)
— same mechanism, just a private repo or registry.