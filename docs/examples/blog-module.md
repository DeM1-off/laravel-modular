# Example: a Blog module

A realistic walkthrough — from generating a module to wiring it with attributes
and convention, then querying it at runtime.

## 1. Generate

```bash
php artisan make:module Blog
```

```
Modules/Blog/
├── composer.json
├── module.json
├── config/blog.php
├── database/{migrations,factories,seeders}/
├── resources/views/
├── src/{Domain,Application,Infrastructure}/
│   └── Infrastructure/Providers/BlogServiceProvider.php
└── tests/
```

## 2. Domain & Application

```php
// src/Domain/Post/Post.php
namespace Modules\Blog\Domain\Post;

final class Post
{
    public function __construct(
        public readonly string $slug,
        public readonly string $title,
        public readonly string $body,
    ) {}
}
```

```php
// src/Application/Contracts/PostRepositoryInterface.php
namespace Modules\Blog\Application\Contracts;

use Modules\Blog\Domain\Post\Post;

interface PostRepositoryInterface
{
    public function findBySlug(string $slug): ?Post;
}
```

## 3. Infrastructure: the provider

The static wiring is all attributes — no method body needed yet:

```php
// src/Infrastructure/Providers/BlogServiceProvider.php
namespace Modules\Blog\Infrastructure\Providers;

use Dem1Off\LaravelModular\Module\Attributes\Bind;
use Dem1Off\LaravelModular\Module\ModuleServiceProvider;
use Modules\Blog\Application\Contracts\PostRepositoryInterface;
use Modules\Blog\Infrastructure\Persistence\EloquentPostRepository;

#[Bind(PostRepositoryInterface::class, EloquentPostRepository::class)]
final class BlogServiceProvider extends ModuleServiceProvider {}
```

Config, migrations and views load by convention. Routes load from
`routes/web.php` / `routes/api.php` automatically:

```php
// Modules/Blog/routes/web.php
use Illuminate\Support\Facades\Route;
use Modules\Blog\Infrastructure\Http\ShowPostController;

Route::get('/blog/{slug}', ShowPostController::class)->name('blog.show');
```

## 4. Controller

```php
// src/Infrastructure/Http/ShowPostController.php
namespace Modules\Blog\Infrastructure\Http;

use Modules\Blog\Application\Contracts\PostRepositoryInterface;

final class ShowPostController
{
    public function __construct(private PostRepositoryInterface $posts) {}

    public function __invoke(string $slug)
    {
        $post = $this->posts->findBySlug($slug);

        abort_if($post === null, 404);

        return view('blog::show', ['post' => $post]);
    }
}
```

`blog::show` resolves to `resources/views/show.blade.php` because the `views: true`
attribute registered the module's view namespace.

## 5. Use it at runtime

```php
use Dem1Off\LaravelModular\Facades\Modules;

Modules::isEnabled('Blog');           // true
Modules::path('Blog');                // /app/Modules/Blog
module_path('Blog', 'config/blog.php');
```

## 6. Promote it later

When the blog grows up, move it to its own repo and swap the Composer
constraint — no code changes. See
[Promote to a package](/promotion/promote-to-package).