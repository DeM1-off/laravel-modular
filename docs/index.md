---
layout: home

hero:
  name: Laravel Modular
  text: DDD modules that promote to packages
  tagline: Modular architecture tooling for Laravel — modules are real Composer packages from day one, so any module promotes to a standalone package with zero code churn.
  image:
    src: /logo.svg
    alt: Laravel Modular
  actions:
    - theme: brand
      text: Get Started
      link: /getting-started/introduction
    - theme: alt
      text: View on GitHub
      link: https://github.com/dem1-off/laravel-modular

features:
  - title: Attribute-driven
    details: Declare bindings and listeners with #[Bind] and #[Listen] — or let an implementation auto-bind itself with #[Provides] (Symfony-style autoconfigure for Laravel modules). Config, migrations, views and routes load by convention.
  - title: Fast by design
    details: module:cache compiles discovery and attributes into one PHP file, so a production request does zero reflection and zero filesystem scanning. Wired into php artisan optimize.
  - title: Promotion built in
    details: Module namespaces never change, so moving a module into its own repo is a Composer change, not a refactor.
  - title: Familiar layout
    details: Works with the Modules/ directory, module.json, modules_statuses.json and module_path() conventions, so existing projects interoperate.
---