<?php

declare(strict_types=1);

use Dem1Off\LaravelModular\Manager\ModuleManager;
use Illuminate\Filesystem\Filesystem;

function makeManager(string $base, Filesystem $files): ModuleManager
{
    return new ModuleManager($files, [
        'paths' => ['modules' => $base],
        'statuses_file' => $base.'/modules_statuses.json',
        'manifest_file' => 'module.json',
    ]);
}

beforeEach(function () {
    $this->base = sys_get_temp_dir().'/lm-unit-'.uniqid();
    $this->files = new Filesystem;

    // Blog: declared via module.json
    $this->files->ensureDirectoryExists($this->base.'/Blog');
    $this->files->put($this->base.'/Blog/module.json', json_encode([
        'name' => 'Blog',
        'providers' => ['App\\Blog\\BlogServiceProvider'],
    ]));

    // Shop: declared via composer.json only
    $this->files->ensureDirectoryExists($this->base.'/Shop');
    $this->files->put($this->base.'/Shop/composer.json', json_encode([
        'extra' => ['laravel' => ['providers' => ['App\\Shop\\ShopServiceProvider']]],
    ]));
});

afterEach(function () {
    $this->files->deleteDirectory($this->base);
});

it('discovers modules from both module.json and composer.json', function () {
    $manager = makeManager($this->base, $this->files);

    expect($manager->all())->toHaveCount(2)
        ->and($manager->has('Blog'))->toBeTrue()
        ->and($manager->has('Shop'))->toBeTrue()
        ->and($manager->find('Shop')->providers)->toBe(['App\\Shop\\ShopServiceProvider']);
});

it('defaults a module with no status entry to enabled', function () {
    $manager = makeManager($this->base, $this->files);

    expect($manager->isEnabled('Blog'))->toBeTrue();
});

it('respects the statuses file', function () {
    $this->files->put($this->base.'/modules_statuses.json', json_encode(['Shop' => false]));

    $manager = makeManager($this->base, $this->files);

    expect($manager->isEnabled('Shop'))->toBeFalse()
        ->and(array_keys($manager->enabled()))->toBe(['Blog']);
});

it('writes and re-reads status via setStatus', function () {
    $manager = makeManager($this->base, $this->files);

    $manager->setStatus('Blog', false);

    expect($manager->isEnabled('Blog'))->toBeFalse();
    expect($this->files->exists($this->base.'/modules_statuses.json'))->toBeTrue();
});

it('resolves a module path and throws for unknown modules', function () {
    $manager = makeManager($this->base, $this->files);

    expect($manager->path('Blog'))->toEndWith('Blog');

    $manager->path('Ghost');
})->throws(RuntimeException::class);

it('reads requires and reports dependents', function () {
    $this->files->ensureDirectoryExists($this->base.'/Store');
    $this->files->put($this->base.'/Store/module.json', json_encode([
        'name' => 'Store',
        'requires' => ['Shop'],
        'providers' => [],
    ]));

    $manager = makeManager($this->base, $this->files);

    expect($manager->find('Store')->requires)->toBe(['Shop'])
        ->and($manager->dependents('Shop'))->toBe(['Store'])
        ->and($manager->dependents('Blog'))->toBe([]);
});

it('loads required modules before their dependents, over priority', function () {
    $this->files->ensureDirectoryExists($this->base.'/Aaa');
    $this->files->put($this->base.'/Aaa/module.json', json_encode([
        'name' => 'Aaa',
        'priority' => 10,
        'requires' => ['Shop'],
        'providers' => [],
    ]));

    $manager = makeManager($this->base, $this->files);

    $order = array_keys($manager->all());

    expect(array_search('Shop', $order, true))->toBeLessThan(array_search('Aaa', $order, true));
});

it('survives a dependency cycle and unknown requirements', function () {
    $this->files->put($this->base.'/Blog/module.json', json_encode([
        'name' => 'Blog',
        'requires' => ['Shop', 'Ghost'],
        'providers' => [],
    ]));
    $this->files->put($this->base.'/Shop/module.json', json_encode([
        'name' => 'Shop',
        'requires' => ['Blog'],
        'providers' => [],
    ]));

    $manager = makeManager($this->base, $this->files);

    expect($manager->all())->toHaveCount(2);
});

it('orders modules by priority then name', function () {
    $this->files->ensureDirectoryExists($this->base.'/Zeta');
    $this->files->put($this->base.'/Zeta/module.json', json_encode([
        'name' => 'Zeta',
        'priority' => 10,
        'providers' => [],
    ]));

    $manager = makeManager($this->base, $this->files);

    // Zeta (priority 10) first; Blog and Shop (priority 0) alphabetical.
    expect(array_keys($manager->all()))->toBe(['Zeta', 'Blog', 'Shop']);
});
