# Recipes

Practical, copy-pasteable solutions for common tasks. Every example assumes a
module generated with `php artisan make:module <Name>`.

[[toc]]

## Bind an interface to an implementation

Declare it on the provider — repeatable, with `singleton: true` for a shared
instance.

```php
use Dem1Off\LaravelModular\Module\Attributes\Bind;
use Dem1Off\LaravelModular\Module\ModuleServiceProvider;

#[Bind(Clock::class, SystemClock::class)]
#[Bind(InvoiceNumberGenerator::class, SequentialGenerator::class, singleton: true)]
final class BillingServiceProvider extends ModuleServiceProvider {}
```

Anything type-hinting `Clock` now receives `SystemClock` from the container.

## Auto-bind from the implementation (`#[Provides]`)

Skip the provider entry — let the class declare what it provides. The module
scans for it and binds automatically.

```php
use Dem1Off\LaravelModular\Module\Attributes\Provides;

#[Provides(PaymentGateway::class, singleton: true)]
final class StripeGateway implements PaymentGateway {}
```

Omit the abstract to infer it from a single implemented interface. Compiled by
`module:cache`, so it's free in production.

## Add a config file

Create `config/<module>.php` — it is merged automatically under the kebab-cased
module name. No declaration needed.

```php
// Modules/Billing/config/billing.php
return [
    'currency' => 'EUR',
    'retries' => 3,
];
```

```php
config('billing.currency'); // 'EUR'
```

Don't want auto-merge? `#[Module(config: false)]`.

## Add migrations, factories and seeders

`database/migrations` loads automatically. Generate a migration:

```bash
php artisan module:make-migration Billing create_invoices_table --table=invoices
```

Factories and seeders are PSR-4 (`Modules\Billing\Database\Factories\`,
`Modules\Billing\Database\Seeders\`):

```php
// Modules/Billing/database/factories/InvoiceFactory.php
namespace Modules\Billing\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Billing\Infrastructure\Persistence\Models\Invoice;

final class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        return ['total' => $this->faker->numberBetween(100, 9999)];
    }
}
```

## Add routes and a controller

`routes/web.php` and `routes/api.php` load automatically (the API file is given
the `api` prefix). Generate a controller:

```bash
php artisan module:make-controller Billing ShowInvoice
```

```php
// Modules/Billing/routes/web.php
use Illuminate\Support\Facades\Route;
use Modules\Billing\Infrastructure\Http\ShowInvoiceController;

Route::get('/invoices/{invoice}', ShowInvoiceController::class)->name('billing.invoices.show');
```

Don't want a routes file loaded? `#[Module(routes: false)]`.

## Add views (and Livewire)

`resources/views` loads under the lowercase module namespace:

```php
return view('billing::invoice', ['invoice' => $invoice]); // resources/views/invoice.blade.php
```

For Livewire, register components in `boot()` (call the parent first):

```php
public function boot(): void
{
    parent::boot();

    Livewire::component('billing.invoice-list', InvoiceList::class);
}
```

## Register a console command

```php
#[Module(commands: [GenerateMonthlyInvoices::class])]
final class BillingServiceProvider extends ModuleServiceProvider {}
```

To schedule it, override `boot()`:

```php
public function boot(): void
{
    parent::boot();

    $this->app->booted(function () {
        $this->app->make(Schedule::class)->command('billing:generate')->monthly();
    });
}
```

## Listen to events (including cross-module)

```php
#[Listen(InvoicePaid::class, SendReceipt::class)]
#[Listen(InvoicePaid::class, UpdateLedger::class)]
final class BillingServiceProvider extends ModuleServiceProvider {}
```

## Let two modules talk without coupling

Use a contract module: the consumer depends on an interface, the provider binds
its implementation. Full walkthrough in
[Contract modules](/basic-usage/contract-modules).

```php
// Reporting consumes the port
final class RevenueReport
{
    public function __construct(private InvoiceQuery $invoices) {}
}

// Billing provides it
#[Bind(InvoiceQuery::class, EloquentInvoiceQuery::class)]
final class BillingServiceProvider extends ModuleServiceProvider {}
```

## Disable a module

```bash
php artisan module:disable Billing
```

Or edit `modules_statuses.json`. A disabled module's provider no-ops — its
bindings, routes and migrations are not registered.

## Swap an implementation in a test

Because everything is a container binding, override it in a test:

```php
it('charges with a fake gateway', function () {
    $this->app->bind(PaymentGateway::class, FakeGateway::class);

    // ... assert against the fake
});
```

## Override what convention loads

```php
#[Module(views: false, routes: false, migrations: false)]
final class ApiOnlyServiceProvider extends ModuleServiceProvider {}
```

## Add custom boot logic

A module provider is a normal Laravel provider — call the parent, then add
anything:

```php
public function boot(): void
{
    parent::boot();

    Gate::policy(Invoice::class, InvoicePolicy::class);
    View::composer('billing::invoice', InvoiceComposer::class);
}
```

## Extract a module into a service later

Swap the binding from a local implementation to a remote adapter — callers that
depend on the interface don't change:

```php
// before — in-process
#[Bind(InvoiceQuery::class, EloquentInvoiceQuery::class)]

// after — the module now lives behind an HTTP API
#[Bind(InvoiceQuery::class, HttpInvoiceServiceClient::class)]
```

See [How it works](/advanced/how-it-works) and
[Promote to a package](/promotion/promote-to-package).

## Make it fast in production

```bash
php artisan module:cache    # or: php artisan optimize
```

Compiles discovery, attributes and each module's resolved convention folders
into one file — zero reflection, zero filesystem scanning per request. Rebuild
after adding a folder like `routes/` to a module, the same as `config:cache`.
See [Performance](/advanced/performance).
