# Configuring a module

Two principles: **convention loads, attributes wire.**

- **Convention** — config, migrations, views and routes load automatically when
  their folders exist. Nothing to declare.
- **Attributes** — declare container bindings and event listeners right on the
  provider. This is the package's signature: a module's wiring reads top-to-bottom
  as a list of attributes.

A typical provider:

```php
use Dem1Off\LaravelModular\Module\Attributes\Bind;
use Dem1Off\LaravelModular\Module\Attributes\Listen;
use Dem1Off\LaravelModular\Module\ModuleServiceProvider;

#[Bind(PostRepositoryInterface::class, EloquentPostRepository::class)]
#[Bind(FeedCache::class, RedisFeedCache::class, singleton: true)]
#[Listen(ChapterPublished::class, SendDigest::class)]
final class BlogServiceProvider extends ModuleServiceProvider {}
```

No method body — config/migrations/views/routes are picked up by convention.

## Convention paths

| Loaded when present | Path |
| --- | --- |
| Config | `config/<module>.php` (or `config/config.php`), merged under the kebab-cased module name |
| Migrations | `database/migrations/` |
| Views | `resources/views/` (namespace = lowercase module name) |
| Routes | `routes/web.php` (web), `routes/api.php` (api, `api` prefix) |

## `#[Bind]`

Maps an abstract to a concrete. Repeatable. Pass `singleton: true` for a shared
instance.

```php
#[Bind(PostRepositoryInterface::class, EloquentPostRepository::class)]
#[Bind(SearchIndex::class, MeilisearchIndex::class, singleton: true)]
```

Swapping the concrete is all it takes to extract the module into a service later
— callers depend only on the abstract. See
[Contract modules](/basic-usage/contract-modules).

## `#[Provides]` — auto-binding (the unique bit)

Instead of listing bindings on the provider, let the **implementation declare
what it provides**. The module scans for `#[Provides]` and binds it
automatically — no provider entry at all.

```php
use Dem1Off\LaravelModular\Module\Attributes\Provides;

#[Provides(PostRepositoryInterface::class)]
final class EloquentPostRepository implements PostRepositoryInterface {}
```

Omit the abstract to infer it when the class implements exactly one interface:

```php
#[Provides]
final class EloquentPostRepository implements PostRepositoryInterface {}
```

Pass `singleton: true` for a shared instance; the attribute is repeatable for a
class that provides several abstracts.

This is Symfony-style autoconfiguration for Laravel modules: bindings live next
to the code they bind. It's compiled, so it costs nothing in production (see
[Performance](/advanced/performance)) — and you can turn it off with
`modules.scan_bindings => false`.

### `#[Bind]` vs `#[Provides]`

| | Lives on | Use when |
| --- | --- | --- |
| `#[Bind]` | the provider | you want the wiring listed in one place |
| `#[Provides]` | the implementation | you want bindings co-located with the class |

## Lifetime shorthands

On an implementation, `#[Singleton]` and `#[Scoped]` are shorter forms of
`#[Provides]` with a lifetime:

```php
#[Singleton]                       // shared instance, abstract inferred
final class RedisFeedCache implements FeedCache {}

#[Scoped(RequestContext::class)]   // one per request/job
final class HttpRequestContext implements RequestContext {}
```

## Tagged collections

Tag implementations with `#[Provides(tag:)]` and resolve them as a group:

```php
#[Provides(Report::class, tag: 'reports')]
final class SalesReport implements Report {}

#[Provides(Report::class, tag: 'reports')]
final class RefundsReport implements Report {}
```

```php
$reports = app()->tagged('reports'); // iterable of both
```

## Route middleware

Register a middleware alias from the provider with `#[Middleware]`:

```php
#[Middleware('blog.subscriber', EnsureSubscriber::class)]
final class BlogServiceProvider extends ModuleServiceProvider {}
```

```php
Route::middleware('blog.subscriber')->get(...);
```

## `#[Listen]`

Registers an event listener. Repeatable.

```php
#[Listen(ChapterPublished::class, SendDigest::class)]
#[Listen(ChapterPublished::class, WarmCache::class)]
```

## `#[Module]` (overrides only)

Add it only to override a convention default — rename the module, turn a loader
off, or register commands.

```php
#[Module(views: false, commands: [PublishScheduledPosts::class])]
final class BlogServiceProvider extends ModuleServiceProvider {}
```

| Argument | Default | Effect |
| --- | --- | --- |
| `name` | class basename | Override the module name. |
| `config` / `migrations` / `views` / `routes` | `true` | Turn a convention loader off. |
| `commands` | `[]` | Artisan commands to register. |

## Need more than attributes?

Override `register()` or `boot()` and call the parent — standard Laravel:

```php
public function boot(): void
{
    parent::boot();

    Livewire::component('blog.feed', Feed::class);
}
```

## Performance

Attributes are read by reflection in development. In production,
`php artisan module:cache` compiles every module's settings (and discovery) into
a single PHP file, so a request does **zero reflection and zero filesystem
scanning**. See [Performance](/advanced/performance).
