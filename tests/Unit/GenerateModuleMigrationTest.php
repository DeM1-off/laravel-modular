<?php

declare(strict_types=1);

use Dem1Off\LaravelModular\Operations\GenerateModuleMigration;
use Illuminate\Filesystem\Filesystem;

// No Orchestra TestCase, no artisan — the Operations layer is plain PHP.

beforeEach(function () {
    $this->root = sys_get_temp_dir().'/generate-migration-'.uniqid();
    $this->generate = new GenerateModuleMigration(
        new Filesystem,
        packageStubs: __DIR__.'/../../stubs',
        publishedStubs: $this->root.'/published',
    );
});

afterEach(function () {
    (new Filesystem)->deleteDirectory($this->root);
});

it('writes a timestamped migration into the module', function () {
    $file = $this->generate->execute($this->root.'/Modules/Blog', 'CreatePostsTable');

    expect(file_exists($file))->toBeTrue()
        ->and(basename($file))->toMatch('/^\d{4}_\d{2}_\d{2}_\d{6}_create_posts_table\.php$/')
        ->and(dirname($file))->toBe($this->root.'/Modules/Blog/database/migrations');
});

it('defaults the table name to the snake-cased migration name', function () {
    $file = $this->generate->execute($this->root.'/Modules/Blog', 'CreatePosts');

    expect(file_get_contents($file))->toContain('create_posts');
});

it('honours an explicit table name', function () {
    $file = $this->generate->execute($this->root.'/Modules/Blog', 'AddSlug', 'posts');

    expect(file_get_contents($file))->toContain('posts');
});
