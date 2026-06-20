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

## Project-specific concerns

Keep anything proprietary (navigation, mailing, metrics, …) in your application,
invoked from the module's `boot()` — never inside this package. That keeps the
package generic and your module portable.
