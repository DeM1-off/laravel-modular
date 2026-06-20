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

Minor and patch releases are backward compatible. Breaking changes only land in
the next major (v2) and are listed in the [Changelog](/getting-started/changelog).