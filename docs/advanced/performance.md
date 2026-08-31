# Performance & caching

Attributes are convenient, but reflecting them on every request — scanning the
`Modules/` directory, and stat-ing each module's convention folders — would cost
time. So the package compiles all of it into a single PHP file, the same way
Laravel caches config and routes.

## What gets compiled

`php artisan module:cache` writes one file (`bootstrap/cache/modular.php`)
containing:

- **Discovery** — every module's name, path, status and providers, so the
  filesystem is never scanned at runtime.
- **Settings** — each provider's parsed attributes (`#[Bind]`, `#[Listen]`,
  `#[Module]`) **and** the `#[Provides]` implementations found by scanning, so
  nothing is reflected or scanned at runtime.
- **Paths** — which convention folders each module actually ships (`config/`,
  `database/migrations/`, `resources/views/`, `routes/web.php`, `routes/api.php`,
  `lang/`), already resolved, so booting a module asks the filesystem nothing.

With the cache present, a request reads one `require`d array. **Zero reflection,
zero filesystem scanning, zero stat calls per module.**

```bash
php artisan module:cache    # build (run on deploy)
php artisan module:clear    # remove (back to live resolution)
```

## What a module costs at boot

A module's boot is a walk over the compiled array: bind what it declares, load
the folders it has, register its listeners. Folders it does not ship are `null`
in the cache and skipped without touching disk — so a
[clean module](/basic-usage/creating-a-module#a-clean-module) costs close to
nothing, and adding twenty of them does not add sixty stat calls to every
request.

Publishable paths (`--tag=modules-views`, `--tag=modules-lang`) only matter to
`vendor:publish`, so they are declared on console boots only. An HTTP request
never builds them.

## The cached contract

Because folder resolution is baked, **adding** a convention folder to a module
needs a rebuild to take effect:

```bash
mkdir Modules/Blog/routes && touch Modules/Blog/routes/web.php
php artisan module:cache    # ← without this, the compiled cache still says "no routes"
```

This is the same contract as `config:cache` and `route:cache`: in development
there is no cache, so a new folder just works; in production you compile once at
deploy.

## Dev vs production

| | Discovery | Attributes & `#[Provides]` | Convention folders |
| --- | --- | --- | --- |
| **Dev** (no cache) | live filesystem scan | provider attributes reflected; module classes scanned for `#[Provides]` | resolved live, memoised per provider |
| **Prod** (`module:cache`) | from compiled file | from compiled file | from compiled file |

In development you change a module and it just works — no rebuild.

### What development actually pays

Without the compiled cache the scanners still run, so they are built not to be
wasteful:

- A module's file tree is **walked once per process** and shared by the
  `#[Provides]` scanner and the command scanner, rather than once each.
- Results are memoised to `bootstrap/cache/modular-scan.php`, keyed by a
  signature of the module (newest mtime + file count). An unchanged module is
  never re-reflected.
- On a cold scan, files are filtered on their source text before being loaded —
  only files that mention `Provides`, `Singleton` or `Scoped` are reflected, so
  scanning does not drag the whole module into memory.

`module:clear` removes this memo along with the compiled cache.

## Hooks into `optimize`

`module:cache` and `module:clear` are wired into Laravel's optimizer, so the
commands you already run on deploy cover modules too:

```bash
php artisan optimize         # also runs module:cache
php artisan optimize:clear   # also runs module:clear
```

Add `php artisan optimize` to your deployment pipeline and module caching is
automatic.

## Autoloading

With `modules.autoload` on (the default) each enabled module's PSR-4 prefix is
registered on the Composer loader at boot, so a module works by just existing.
If your modules are wired through Composer path repositories instead, set
`modules.autoload` to `false` and run `composer dump-autoload -o`: class
resolution then comes from the optimised classmap and the package registers
nothing at runtime.
