# Contract modules

A **contract module** (shared kernel) holds only the abstractions that connect
other modules — interfaces, DTOs, integration events, enums. Modules depend on
*it*, never on each other, so they stay decoupled and independently extractable.

## Generate one

```bash
php artisan make:module Shared --layout=contracts
```

This scaffolds a thin, implementation-free module:

```
Modules/Shared/
├── composer.json
├── module.json
└── src/
    ├── Contracts/   # interfaces (ports)
    ├── Data/        # DTOs (readonly)
    ├── Events/      # integration events
    ├── Enums/
    └── Providers/SharedServiceProvider.php   # #[Module] — registers nothing
```

The provider is intentionally empty (`#[Module]` with no config/migrations/views):
a contracts module has no runtime behaviour.

## How modules use it

Define the abstraction in the contract module:

```php
// Modules/Shared/src/Contracts/SentenceProvider.php
namespace Modules\Shared\Contracts;

interface SentenceProvider
{
    public function findById(string $id): ?\Modules\Shared\Data\SentenceData;
}
```

The **provider module** binds its implementation:

```php
#[Bind(\Modules\Shared\Contracts\SentenceProvider::class, SentenceRepository::class)]
final class BibleServiceProvider extends ModuleServiceProvider {}
```

The **consumer module** depends only on the interface:

```php
final class GrammarSyncService
{
    public function __construct(private SentenceProvider $sentences) {}
}
```

## The dependency rule

```
        Shared (contracts)
        ▲              ▲
     Bible          Grammar
   (implements)    (consumes)
```

- `Shared` depends on nothing.
- `Bible` and `Grammar` depend only on `Shared`, never on each other.

This is dependency inversion: both sides depend on the abstraction. A module can
later be extracted into a service by swapping the binding for a remote adapter —
the interface, and every caller, stay unchanged.

::: tip Ordering doesn't matter
Interfaces are just autoloaded classes and bindings resolve lazily. Bind in
`register()`, resolve at runtime — never resolve a contract during `register()`.
:::

## Integration events

Put event DTOs in the contract module; the publishing module dispatches them and
consumers subscribe with `#[Listen]`:

```php
#[Listen(\Modules\Shared\Events\ChapterPublished::class, SendDigest::class)]
final class GrammarServiceProvider extends ModuleServiceProvider {}
```

## Customising the scaffold

The generated structure is yours to shape. Publish the stubs and edit them —
published stubs (`stubs/modular/`) take priority over the package defaults:

```bash
php artisan vendor:publish --tag=modules-stubs
```

Edit `stubs/modular/provider-contracts.stub` (or any other) and every future
`make:module`/`module:make-*` uses your version.
