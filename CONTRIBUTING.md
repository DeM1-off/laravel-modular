# Contributing

Thanks for your interest in improving `dem1-off/laravel-modular`.

## Workflow

1. Fork and branch from `main` (`feat/...`, `fix/...`, `docs/...`).
2. Make a focused change — one concern per pull request.
3. Add or update tests and run the suite.
4. Update the docs and `CHANGELOG.md` (under **Unreleased**) when behavior changes.
5. Open a PR using the template.

## Running checks

```bash
composer install
vendor/bin/pest          # tests
```

## Docs site

```bash
npm install
npm run docs:dev         # http://localhost:5173
```

## Code style

- `declare(strict_types=1)` everywhere; explicit types over docblocks.
- Guard clauses and early returns; keep the core free of project-specific coupling.
- Anything app-specific belongs in a consumer's Macroable extension, not in this package.

## Reporting bugs & requesting features

Use the issue templates. For usage questions, prefer
[Discussions](https://github.com/dem1-off/laravel-modular/discussions).