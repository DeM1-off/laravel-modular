# Performance & caching

Attributes are convenient, but reflecting them on every request — and scanning
the `Modules/` directory — would cost time. So the package compiles both into a
single PHP file, the same way Laravel caches config and routes.

## What gets compiled

`php artisan module:cache` writes one file (`bootstrap/cache/modular.php`)
containing:

- **Discovery** — every module's name, path, status and providers, so the
  filesystem is never scanned at runtime.
- **Settings** — each provider's parsed attributes (`#[Bind]`, `#[Listen]`,
  `#[Module]`) **and** the `#[Provides]` implementations found by scanning, so
  nothing is reflected or scanned at runtime.

With the cache present, a request reads one `require`d array. **Zero reflection,
zero filesystem scanning.**

```bash
php artisan module:cache    # build (run on deploy)
php artisan module:clear    # remove (back to live reflection)
```

## Dev vs production

| | Discovery | Attributes & `#[Provides]` |
| --- | --- | --- |
| **Dev** (no cache) | live filesystem scan | provider attributes reflected; module classes scanned for `#[Provides]`; memoised per request |
| **Prod** (`module:cache`) | from compiled file | from compiled file |

In development you change a module and it just works — no rebuild. In production
you compile once at deploy.

## Hooks into `optimize`

`module:cache` and `module:clear` are wired into Laravel's optimizer, so the
commands you already run on deploy cover modules too:

```bash
php artisan optimize         # also runs module:cache
php artisan optimize:clear   # also runs module:clear
```

Add `php artisan optimize` to your deployment pipeline and module caching is
automatic.
