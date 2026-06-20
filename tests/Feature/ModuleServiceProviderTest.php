<?php

declare(strict_types=1);

use Dem1Off\LaravelModular\Tests\Fixtures\BlogServiceProvider;
use Dem1Off\LaravelModular\Tests\Fixtures\Counter;
use Dem1Off\LaravelModular\Tests\Fixtures\EnglishGreeter;
use Dem1Off\LaravelModular\Tests\Fixtures\Greeter;
use Illuminate\Filesystem\Filesystem;

beforeEach(function () {
    $files = new Filesystem;
    $blog = $this->modulesPath.'/Blog';

    $files->ensureDirectoryExists($blog.'/config');
    $files->put($blog.'/module.json', json_encode([
        'name' => 'Blog',
        'providers' => [BlogServiceProvider::class],
    ]));
    $files->put($blog.'/config/blog.php', "<?php\n\nreturn ['feature' => true];\n");
});

it('applies #[Bind] attributes', function () {
    $this->app->register(BlogServiceProvider::class);

    expect($this->app->make(Greeter::class))->toBeInstanceOf(EnglishGreeter::class);
});

it('binds singletons via #[Bind(singleton: true)]', function () {
    $this->app->register(BlogServiceProvider::class);

    expect($this->app->make(Counter::class))->toBe($this->app->make(Counter::class));
});

it('loads the module config by convention', function () {
    $this->app->register(BlogServiceProvider::class);

    expect(config('blog.feature'))->toBeTrue();
});

it('no-ops when the module is disabled', function () {
    (new Filesystem)->put($this->statusesFile, json_encode(['Blog' => false]));

    $this->app->register(BlogServiceProvider::class);

    expect($this->app->bound(Greeter::class))->toBeFalse();
});
