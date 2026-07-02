# Upgrade guide

This package follows [Semantic Versioning](https://semver.org/). Patch and minor
releases are backward compatible; breaking changes only land in major versions
and are listed here.

## Migrating from another modules package

The layout is intentionally compatible, so most projects migrate without
touching their modules:

1. **Keep your structure.** The `Modules/` directory, `module.json`, and
   `modules_statuses.json` are read as-is.
2. **Publish the config.**
   ```bash
   php artisan vendor:publish --tag=modules-config
   ```
3. **Point providers at the new base class.** Change a module's provider to
   extend `Dem1Off\LaravelModular\Module\ModuleServiceProvider`; config,
   migrations, views and routes then load by convention. Declare bindings and
   listeners with [attributes](/basic-usage/attributes).
4. **Keep app-specific concerns in your app.** Anything proprietary (navigation,
   mailing, metrics, …) stays in your application, invoked from the module's
   `boot()` — see [Customising behaviour](/advanced/extending-the-core).

## Within v1.x

Minor and patch releases are backward compatible for application code — your
modules, config and the artisan command signatures are unaffected. Breaking
changes to the public runtime API only land in the next major (v2) and are
listed in the [Changelog](/getting-started/changelog).

### 1.5.0 — re-run `module:cache`

The compiled settings now carry the translation toggle and the commands
discovered from `Console` directories. After upgrading a deployment that uses
the compiled cache, rebuild it once:

```bash
php artisan module:cache   # or: php artisan optimize
```

Everything else is additive — translations, command discovery, the new
generators, `requires` and `module:check --boundaries` change nothing for
modules that don't use them.

### 1.3.0 — custom generators

1.3.0 moved each command's logic into a console-free
[Operations layer](/advanced/extending-the-core#the-operations-layer). This is
invisible to applications, but if you **subclass `ModuleGeneratorCommand`** to
add a custom generator, replace the old `stub()`, `layerPath()`,
`layerNamespace()` and `classSuffix()` methods with a single
`layer(): ClassLayer` — see
[Custom in-module generators](/advanced/extending-the-core#custom-in-module-generators).