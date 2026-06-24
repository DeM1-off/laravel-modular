<?php

declare(strict_types=1);

use Dem1Off\LaravelModular\Operations\ComposerManifest;
use Illuminate\Filesystem\Filesystem;

// No Orchestra TestCase, no artisan — the Operations layer is plain PHP.

beforeEach(function () {
    $this->file = sys_get_temp_dir().'/manifest-'.uniqid().'.json';
});

afterEach(function () {
    @unlink($this->file);
});

it('reads the package name', function () {
    file_put_contents($this->file, json_encode(['name' => 'acme/app']));

    expect(ComposerManifest::load(new Filesystem, $this->file)->name())->toBe('acme/app');
});

it('requires and removes a package', function () {
    file_put_contents($this->file, json_encode(['require' => ['php' => '^8.3']]));

    $manifest = ComposerManifest::load(new Filesystem, $this->file);
    $manifest->requirePackage('acme/blog-module', '*');

    expect($manifest->constraintFor('acme/blog-module'))->toBe('*');

    $manifest->removePackage('acme/blog-module');

    expect($manifest->constraintFor('acme/blog-module'))->toBeNull();
});

it('adds the path repository idempotently', function () {
    file_put_contents($this->file, json_encode([]));

    $manifest = ComposerManifest::load(new Filesystem, $this->file)
        ->ensurePathRepository('Modules/*')
        ->ensurePathRepository('Modules/*');

    expect($manifest->toArray()['repositories'])->toBe([
        ['type' => 'path', 'url' => 'Modules/*', 'options' => ['symlink' => true]],
    ]);
});

it('removes the path repository', function () {
    file_put_contents($this->file, json_encode([]));

    $manifest = ComposerManifest::load(new Filesystem, $this->file)
        ->ensurePathRepository('Modules/*')
        ->removePathRepository('Modules/*');

    expect($manifest->toArray())->not->toHaveKey('repositories');
});

it('persists changes on save', function () {
    file_put_contents($this->file, json_encode([]));

    ComposerManifest::load(new Filesystem, $this->file)
        ->requirePackage('acme/blog-module', '^1.0')
        ->save();

    $written = json_decode(file_get_contents($this->file), true);

    expect($written['require']['acme/blog-module'])->toBe('^1.0');
});
