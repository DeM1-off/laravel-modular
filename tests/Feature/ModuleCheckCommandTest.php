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

it('fails when an enabled module requires a module that is not installed', function () {
    $files = new Filesystem;
    $files->ensureDirectoryExists($this->modulesPath.'/Blog');
    $files->put($this->modulesPath.'/Blog/module.json', json_encode([
        'name' => 'Blog',
        'requires' => ['Ghost'],
        'providers' => [BlogServiceProvider::class],
    ]));

    $this->artisan('module:check')->assertFailed();
});

it('flags cross-module boundary violations with --boundaries', function () {
    $files = new Filesystem;
    $files->ensureDirectoryExists($this->modulesPath.'/Blog/src');
    $files->put($this->modulesPath.'/Blog/module.json', json_encode([
        'name' => 'Blog',
        'providers' => [BlogServiceProvider::class],
    ]));
    $files->put(
        $this->modulesPath.'/Blog/src/ImportOrders.php',
        "<?php\n\nuse Modules\\Shop\\Infrastructure\\Persistence\\Models\\Order;\n",
    );
    $files->ensureDirectoryExists($this->modulesPath.'/Shop');
    $files->put($this->modulesPath.'/Shop/module.json', json_encode([
        'name' => 'Shop',
        'providers' => [],
    ]));

    $this->artisan('module:check')->assertSuccessful();
    $this->artisan('module:check', ['--boundaries' => true])->assertFailed();
});
