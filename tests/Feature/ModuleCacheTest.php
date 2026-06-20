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

it('clears the cache', function () {
    $this->artisan('module:cache')->assertSuccessful();
    $this->artisan('module:clear')->assertSuccessful();

    expect(app(ModuleCache::class)->exists())->toBeFalse();
});
