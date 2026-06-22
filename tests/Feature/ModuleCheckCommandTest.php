<?php

declare(strict_types=1);

use Dem1Off\LaravelModular\Tests\Fixtures\BlogServiceProvider;
use Illuminate\Filesystem\Filesystem;

it('passes when every provider is autoloadable', function () {
    $files = new Filesystem;
    $files->ensureDirectoryExists($this->modulesPath.'/Blog');
    $files->put($this->modulesPath.'/Blog/module.json', json_encode([
        'name' => 'Blog',
        'providers' => [BlogServiceProvider::class],
    ]));

    $this->artisan('module:check')->assertSuccessful();
});

it('fails when a provider is not autoloadable', function () {
    $files = new Filesystem;
    $files->ensureDirectoryExists($this->modulesPath.'/Ghost');
    $files->put($this->modulesPath.'/Ghost/module.json', json_encode([
        'name' => 'Ghost',
        'providers' => ['Modules\\Ghost\\GhostServiceProvider'],
    ]));

    $this->artisan('module:check')->assertFailed();
});
