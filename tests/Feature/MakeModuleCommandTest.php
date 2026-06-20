<?php

declare(strict_types=1);

it('scaffolds a ddd module', function () {
    $this->artisan('make:module', ['name' => 'Shop'])->assertSuccessful();

    $path = $this->modulesPath.'/Shop';

    expect(is_file($path.'/composer.json'))->toBeTrue()
        ->and(is_dir($path.'/src/Domain'))->toBeTrue()
        ->and(is_dir($path.'/src/Application'))->toBeTrue()
        ->and(is_file($path.'/src/Infrastructure/Providers/ShopServiceProvider.php'))->toBeTrue();

    $composer = json_decode(file_get_contents($path.'/composer.json'), true);

    expect($composer['autoload']['psr-4']['Modules\\Shop\\'])->toBe('src/');
});

it('scaffolds a simple module', function () {
    $this->artisan('make:module', ['name' => 'Shop', '--layout' => 'simple'])->assertSuccessful();

    $path = $this->modulesPath.'/Shop';

    expect(is_dir($path.'/app/Http/Controllers'))->toBeTrue()
        ->and(is_file($path.'/app/Providers/ShopServiceProvider.php'))->toBeTrue();

    $composer = json_decode(file_get_contents($path.'/composer.json'), true);

    expect($composer['autoload']['psr-4']['Modules\\Shop\\'])->toBe('app/');
});

it('scaffolds a contracts module', function () {
    $this->artisan('make:module', ['name' => 'Shared', '--layout' => 'contracts'])->assertSuccessful();

    $path = $this->modulesPath.'/Shared';

    expect(is_dir($path.'/src/Contracts'))->toBeTrue()
        ->and(is_dir($path.'/src/Data'))->toBeTrue()
        ->and(is_dir($path.'/src/Events'))->toBeTrue()
        ->and(is_file($path.'/src/Providers/SharedServiceProvider.php'))->toBeTrue()
        ->and(is_dir($path.'/config'))->toBeFalse();

    expect(file_get_contents($path.'/src/Providers/SharedServiceProvider.php'))->toContain('extends ModuleServiceProvider');
});

it('enables the new module in the statuses file', function () {
    $this->artisan('make:module', ['name' => 'Shop'])->assertSuccessful();

    $statuses = json_decode(file_get_contents($this->statusesFile), true);

    expect($statuses['Shop'])->toBeTrue();
});

it('refuses to overwrite without --force', function () {
    $this->artisan('make:module', ['name' => 'Shop'])->assertSuccessful();
    $this->artisan('make:module', ['name' => 'Shop'])->assertFailed();
    $this->artisan('make:module', ['name' => 'Shop', '--force' => true])->assertSuccessful();
});
