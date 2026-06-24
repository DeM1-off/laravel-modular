# Customising behaviour

Attributes cover bindings and listeners. For anything else, a module provider is
a normal Laravel `ServiceProvider` — override `register()` or `boot()` and call
the parent.

```php
use Dem1Off\LaravelModular\Module\ModuleServiceProvider;

final class BlogServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        parent::register();          // applies #[Bind] attributes

        $this->app->singleton(FeedBuilder::class);
    }

    public function boot(): void
    {
        parent::boot();              // convention loading + #[Listen]

        Livewire::component('blog.feed', Feed::class);
        Gate::policy(Post::class, PostPolicy::class);
    }
}
```

Calling the parent keeps the convention loading and attribute wiring; your code
adds whatever the framework supports. Because it is just a service provider,
there is nothing module-specific to learn.

## Custom in-module generators

The built-in `module:make-controller`, `module:make-model` and
`module:make-action` commands all extend `ModuleGeneratorCommand` and only
describe **where** their class lives via a `ClassLayer`. Adding your own
generator is the same: subclass the base and return a `ClassLayer`.

```php
use Dem1Off\LaravelModular\Console\Generators\ModuleGeneratorCommand;
use Dem1Off\LaravelModular\Operations\ClassLayer;

final class MakeEventCommand extends ModuleGeneratorCommand
{
    protected $signature = 'module:make-event {module} {name} {--force}';

    protected $description = 'Create a domain event inside a module';

    protected function layer(): ClassLayer
    {
        return new ClassLayer(
            stub: 'event.stub',            // resolved from stubs/ (published wins)
            path: 'Domain/Events',         // under the module's app folder
            namespace: 'Domain\\Events',   // appended after Modules\{Module}
            suffix: 'Event',               // optional, appended when missing
        );
    }
}
```

Drop an `event.stub` next to the package stubs (or publish your own with
`--tag=modules-stubs`), register the command, and `module:make-event Blog Posted`
writes `Modules/Blog/src/Domain/Events/PostedEvent.php`.

## The Operations layer

Every artisan command in this package is a thin adapter over a console-free
**use-case** under `Dem1Off\LaravelModular\Operations`. The command resolves
input and prints output; the use-case does the work and returns plain data.

That split is mostly an internal concern, but it is also a public extension
point: if you build your own tooling (a deployment script, a custom command, a
test) you can call the use-cases directly, without booting artisan — for example
`ScaffoldModule` to generate a module, `LinkModules`/`UnlinkModules` to rewrite
the root `composer.json`, or `GenerateModuleClass` to emit a class from a stub.

## Project-specific concerns

Keep anything proprietary (navigation, mailing, metrics, …) in your application,
invoked from the module's `boot()` — never inside this package. That keeps the
package generic and your module portable.
