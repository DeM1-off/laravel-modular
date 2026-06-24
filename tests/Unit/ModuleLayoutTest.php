<?php

declare(strict_types=1);

use Dem1Off\LaravelModular\Operations\ModuleLayout;

it('builds the ddd layout by default', function () {
    $layout = ModuleLayout::for('ddd', 'Modules', 'Blog');

    expect($layout->name)->toBe('ddd')
        ->and($layout->srcPath)->toBe('src/')
        ->and($layout->providerNamespace)->toBe('Modules\\Blog\\Infrastructure\\Providers')
        ->and($layout->config)->toBeTrue()
        ->and($layout->dirs)->toContain('src/Domain', 'src/Application');
});

it('builds the contracts layout without config or database', function () {
    $layout = ModuleLayout::for('contracts', 'Modules', 'Shared');

    expect($layout->name)->toBe('contracts')
        ->and($layout->config)->toBeFalse()
        ->and($layout->dirs)->toContain('src/Contracts')
        ->and($layout->dirs)->not->toContain('database/migrations');
});

it('builds the simple layout under app/', function () {
    $layout = ModuleLayout::for('simple', 'Modules', 'Shop');

    expect($layout->srcPath)->toBe('app/')
        ->and($layout->providerRelpath)->toBe('app/Providers/ShopServiceProvider.php');
});

it('composes the provider fqcn', function () {
    expect(ModuleLayout::for('ddd', 'Modules', 'Blog')->providerFqcn('Blog'))
        ->toBe('Modules\\Blog\\Infrastructure\\Providers\\BlogServiceProvider');
});
