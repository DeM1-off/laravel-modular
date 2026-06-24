<?php

declare(strict_types=1);

use Dem1Off\LaravelModular\Operations\ClassLayer;
use Dem1Off\LaravelModular\Operations\GenerateModuleClass;
use Illuminate\Filesystem\Filesystem;

// No Orchestra TestCase, no artisan — the Operations layer is plain PHP.

beforeEach(function () {
    $this->root = sys_get_temp_dir().'/generate-class-'.uniqid();
    $this->generate = new GenerateModuleClass(
        new Filesystem,
        packageStubs: __DIR__.'/../../stubs',
        publishedStubs: $this->root.'/published',
    );
});

afterEach(function () {
    (new Filesystem)->deleteDirectory($this->root);
});

it('writes a class into the module app folder from the stub', function () {
    $file = $this->generate->execute(
        moduleRoot: $this->root.'/Modules/Blog',
        appFolder: 'src/',
        baseNamespace: 'Modules',
        module: 'Blog',
        layer: ClassLayer::controller(),
        class: 'PostController',
    );

    expect($file)->toBe($this->root.'/Modules/Blog/src/Infrastructure/Http/Controllers/PostController.php')
        ->and(file_exists($file))->toBeTrue();

    $contents = file_get_contents($file);

    expect($contents)->toContain('namespace Modules\\Blog\\Infrastructure\\Http\\Controllers;')
        ->and($contents)->toContain('PostController');
});

it('reports whether the target file already exists', function () {
    $layer = ClassLayer::model();

    expect($this->generate->exists($this->root.'/Modules/Blog', 'src/', $layer, 'Post'))->toBeFalse();

    $this->generate->execute($this->root.'/Modules/Blog', 'src/', 'Modules', 'Blog', $layer, 'Post');

    expect($this->generate->exists($this->root.'/Modules/Blog', 'src/', $layer, 'Post'))->toBeTrue();
});

it('prefers a published stub over the package stub', function () {
    $published = $this->root.'/published';
    (new Filesystem)->ensureDirectoryExists($published);
    file_put_contents($published.'/action.stub', 'PUBLISHED {{ class }}');

    $file = $this->generate->execute(
        moduleRoot: $this->root.'/Modules/Blog',
        appFolder: 'src/',
        baseNamespace: 'Modules',
        module: 'Blog',
        layer: ClassLayer::action(),
        class: 'PublishPost',
    );

    expect(file_get_contents($file))->toBe('PUBLISHED PublishPost');
});
