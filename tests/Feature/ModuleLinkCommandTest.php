<?php

declare(strict_types=1);

use Dem1Off\LaravelModular\Manager\ModuleManager;
use Illuminate\Filesystem\Filesystem;

/**
 * Point the app at a throwaway base path holding a real composer.json and a
 * Modules/ dir, so link/unlink can mutate composer.json for real.
 */
beforeEach(function () {
    $files = new Filesystem;

    $this->appBase = sys_get_temp_dir().'/laravel-modular-app-'.uniqid();
    $files->ensureDirectoryExists($this->appBase.'/Modules/Blog');
    $files->ensureDirectoryExists($this->appBase.'/Modules/Billing');

    $files->put($this->appBase.'/composer.json', json_encode([
        'name' => 'acme/app',
        'require' => ['php' => '^8.3'],
    ], JSON_PRETTY_PRINT));

    $files->put($this->appBase.'/Modules/Blog/composer.json', json_encode([
        'name' => 'acme/blog-module',
        'type' => 'laravel-module',
    ], JSON_PRETTY_PRINT));

    $files->put($this->appBase.'/Modules/Billing/composer.json', json_encode([
        'name' => 'acme/billing-module',
        'type' => 'laravel-module',
    ], JSON_PRETTY_PRINT));

    $this->app->setBasePath($this->appBase);
    config()->set('modules.paths.modules', $this->appBase.'/Modules');
    $this->app->forgetInstance(ModuleManager::class);

    // Start each test with no recorded link state.
    @unlink($this->app->bootstrapPath('cache/module-links.json'));

    $this->rootComposer = fn (): array => json_decode(
        file_get_contents($this->appBase.'/composer.json'),
        true,
    );
});

afterEach(function () {
    (new Filesystem)->deleteDirectory($this->appBase);
    @unlink($this->app->bootstrapPath('cache/module-links.json'));
});

it('links a single module', function () {
    $this->artisan('module:link', ['modules' => ['Blog']])->assertSuccessful();

    $composer = ($this->rootComposer)();

    expect($composer['require']['acme/blog-module'])->toBe('*')
        ->and($composer['repositories'])->toContain([
            'type' => 'path',
            'url' => 'Modules/*',
            'options' => ['symlink' => true],
        ]);
});

it('links several modules from a list', function () {
    $this->artisan('module:link', ['modules' => ['Blog', 'Billing']])->assertSuccessful();

    $composer = ($this->rootComposer)();

    expect($composer['require']['acme/blog-module'])->toBe('*')
        ->and($composer['require']['acme/billing-module'])->toBe('*');
});

it('links every module with --all', function () {
    $this->artisan('module:link', ['--all' => true])->assertSuccessful();

    $composer = ($this->rootComposer)();

    expect($composer['require'])->toHaveKeys(['acme/blog-module', 'acme/billing-module']);
});

it('fails when neither a module nor --all is given', function () {
    $this->artisan('module:link')->assertFailed();
});

it('unlink restores the previous constraint and removes the repository', function () {
    // Pretend Blog was a versioned dependency before linking.
    $files = new Filesystem;
    $composer = ($this->rootComposer)();
    $composer['require']['acme/blog-module'] = '^1.1';
    $files->put($this->appBase.'/composer.json', json_encode($composer, JSON_PRETTY_PRINT));

    $this->artisan('module:link', ['modules' => ['Blog']])->assertSuccessful();
    expect(($this->rootComposer)()['require']['acme/blog-module'])->toBe('*');

    $this->artisan('module:unlink', ['modules' => ['Blog']])->assertSuccessful();

    $composer = ($this->rootComposer)();

    expect($composer['require']['acme/blog-module'])->toBe('^1.1')
        ->and($composer)->not->toHaveKey('repositories');
});

it('unlink --constraint overrides the recorded constraint', function () {
    $this->artisan('module:link', ['modules' => ['Blog']])->assertSuccessful();
    $this->artisan('module:unlink', ['modules' => ['Blog'], '--constraint' => '^2.0'])->assertSuccessful();

    expect(($this->rootComposer)()['require']['acme/blog-module'])->toBe('^2.0');
});

it('dry run leaves composer.json untouched', function () {
    $before = file_get_contents($this->appBase.'/composer.json');

    $this->artisan('module:link', ['modules' => ['Blog'], '--dry-run' => true])->assertSuccessful();

    expect(file_get_contents($this->appBase.'/composer.json'))->toBe($before);
});
