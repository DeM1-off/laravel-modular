<?php

declare(strict_types=1);

use Dem1Off\LaravelModular\Manager\ModuleDescriptor;
use Dem1Off\LaravelModular\Operations\ComposerManifest;
use Dem1Off\LaravelModular\Operations\SyncModules;
use Illuminate\Filesystem\Filesystem;

// No Orchestra TestCase, no artisan — the Operations layer is plain PHP.

beforeEach(function () {
    $this->files = new Filesystem;
    $this->base = sys_get_temp_dir().'/sync-modules-'.uniqid();
    $this->files->ensureDirectoryExists($this->base.'/Modules/Blog');
    $this->files->ensureDirectoryExists($this->base.'/Modules/Draft');

    $this->files->put($this->base.'/Modules/Blog/composer.json', json_encode([
        'name' => 'acme/blog-module',
        'type' => 'laravel-module',
    ]));
    // Draft has no package name — not syncable.
    $this->files->put($this->base.'/Modules/Draft/composer.json', json_encode([
        'type' => 'laravel-module',
    ]));

    $this->files->put($this->base.'/composer.json', json_encode([
        'require' => ['php' => '^8.3', 'acme/blog-module' => '^2.0'],
    ]));

    $this->files->put($this->base.'/composer.lock', json_encode([
        'packages' => [
            ['name' => 'acme/blog-module', 'version' => 'v2.1.3'],
        ],
    ]));

    $this->module = fn (string $name): ModuleDescriptor => new ModuleDescriptor(
        name: $name,
        path: $this->base.'/Modules/'.$name,
        enabled: true,
        providers: [],
    );

    $this->plan = fn (array $names) => (new SyncModules(
        $this->files,
        ComposerManifest::load($this->files, $this->base.'/composer.json'),
        $this->base.'/composer.lock',
    ))->plan(array_map($this->module, $names));
});

afterEach(function () {
    $this->files->deleteDirectory($this->base);
});

it('reports the constraint and the installed version for a managed module', function () {
    $entries = ($this->plan)(['Blog']);

    expect($entries)->toHaveCount(1)
        ->and($entries[0]->package)->toBe('acme/blog-module')
        ->and($entries[0]->constraint)->toBe('^2.0')
        ->and($entries[0]->installed)->toBe('v2.1.3')
        ->and($entries[0]->isManaged())->toBeTrue();
});

it('marks a required-but-not-yet-installed module as managed with no version', function () {
    // Drop the lock entry: required, but nothing installed yet.
    $this->files->put($this->base.'/composer.lock', json_encode(['packages' => []]));

    $entries = ($this->plan)(['Blog']);

    expect($entries[0]->installed)->toBeNull()
        ->and($entries[0]->isManaged())->toBeTrue();
});

it('reports a module the app does not require as unmanaged', function () {
    // Blog is in Modules/ but remove it from the app require.
    $this->files->put($this->base.'/composer.json', json_encode(['require' => ['php' => '^8.3']]));

    $entries = ($this->plan)(['Blog']);

    expect($entries[0]->constraint)->toBeNull()
        ->and($entries[0]->isManaged())->toBeFalse();
});

it('skips modules without a package name', function () {
    $entries = ($this->plan)(['Draft']);

    expect($entries)->toBe([]);
});
