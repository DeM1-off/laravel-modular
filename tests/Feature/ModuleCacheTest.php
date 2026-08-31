<?php

declare(strict_types=1);

use Dem1Off\LaravelModular\Manager\ModuleCache;
use Dem1Off\LaravelModular\Tests\Fixtures\BlogServiceProvider;
use Illuminate\Filesystem\Filesystem;

beforeEach(function () {
    $files = new Filesystem;
    $blog = $this->modulesPath.'/Blog';

    $files->ensureDirectoryExists($blog);
    $files->put($blog.'/module.json', json_encode([
        'name' => 'Blog',
        'providers' => [BlogServiceProvider::class],
    ]));
});

afterEach(function () {
    app(ModuleCache::class)->clear();
});

it('compiles modules and attribute settings into the cache', function () {
    $this->artisan('module:cache')->assertSuccessful();

    $cache = app(ModuleCache::class);
    expect($cache->exists())->toBeTrue();

    $data = $cache->load();

    expect($data['modules'])->toHaveKey('Blog')
        ->and($data['settings'])->toHaveKey(BlogServiceProvider::class)
        ->and($data['settings'][BlogServiceProvider::class]['binds'])->toHaveCount(2);
});

it('bakes each module\'s resolved convention paths into the cache', function () {
    $files = new Filesystem;
    $blog = $this->modulesPath.'/Blog';

    $files->ensureDirectoryExists($blog.'/config');
    $files->put($blog.'/config/blog.php', "<?php\n\nreturn [];\n");
    $files->ensureDirectoryExists($blog.'/database/migrations');

    $this->artisan('module:cache')->assertSuccessful();

    $paths = app(ModuleCache::class)->load()['settings'][BlogServiceProvider::class]['paths'];

    expect($paths['config'])->toBe($blog.'/config/blog.php')
        ->and($paths['migrations'])->toBe($blog.'/database/migrations')
        // A clean module ships none of these, and the cache says so — nothing
        // stats for them on a request.
        ->and($paths['views'])->toBeNull()
        ->and($paths['lang'])->toBeNull()
        ->and($paths['routes'])->toBe(['web' => null, 'api' => null]);
});

it('boots a compiled module from the baked paths, not from a fresh look at disk', function () {
    $files = new Filesystem;
    $blog = $this->modulesPath.'/Blog';

    // Compiled while the module ships no config at all.
    $this->artisan('module:cache')->assertSuccessful();

    $files->ensureDirectoryExists($blog.'/config');
    $files->put($blog.'/config/blog.php', "<?php\n\nreturn ['feature' => true];\n");

    $this->refreshApplication();
    $this->app->register(BlogServiceProvider::class);

    // The baked answer wins: a folder added after compiling needs a rebuild,
    // the same contract as config:cache. That is what buys the zero stats.
    expect(config('blog.feature'))->toBeNull();

    $this->artisan('module:cache')->assertSuccessful();
    $this->refreshApplication();
    $this->app->register(BlogServiceProvider::class);

    expect(config('blog.feature'))->toBeTrue();
});

it('clears the cache', function () {
    $this->artisan('module:cache')->assertSuccessful();
    $this->artisan('module:clear')->assertSuccessful();

    expect(app(ModuleCache::class)->exists())->toBeFalse();
});
