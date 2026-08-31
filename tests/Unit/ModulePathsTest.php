<?php

declare(strict_types=1);

use Dem1Off\LaravelModular\Module\ModulePaths;
use Illuminate\Filesystem\Filesystem;

$all = ['config' => true, 'migrations' => true, 'views' => true, 'routes' => true, 'lang' => true];

beforeEach(function () {
    $this->modulePath = sys_get_temp_dir().'/lm-paths-'.uniqid();
    (new Filesystem)->ensureDirectoryExists($this->modulePath);
});

afterEach(function () {
    (new Filesystem)->deleteDirectory($this->modulePath);
});

it('resolves nothing for a module with no convention folders', function () use ($all) {
    expect(ModulePaths::resolve($this->modulePath, 'Blog', $all))->toBe([
        'config' => null,
        'migrations' => null,
        'views' => null,
        'routes' => ['web' => null, 'api' => null],
        'lang' => null,
    ]);
});

it('finds the folders a module does ship', function () use ($all) {
    $files = new Filesystem;
    $files->ensureDirectoryExists($this->modulePath.'/database/migrations');
    $files->ensureDirectoryExists($this->modulePath.'/resources/views');
    $files->ensureDirectoryExists($this->modulePath.'/routes');
    $files->put($this->modulePath.'/routes/api.php', '<?php');

    $paths = ModulePaths::resolve($this->modulePath, 'Blog', $all);

    expect($paths['migrations'])->toBe($this->modulePath.'/database/migrations')
        ->and($paths['views'])->toBe($this->modulePath.'/resources/views')
        ->and($paths['routes'])->toBe(['web' => null, 'api' => $this->modulePath.'/routes/api.php']);
});

it('prefers a name-matched config file over config/config.php', function () use ($all) {
    $files = new Filesystem;
    $files->ensureDirectoryExists($this->modulePath.'/config');
    $files->put($this->modulePath.'/config/config.php', '<?php return [];');

    expect(ModulePaths::resolve($this->modulePath, 'Blog', $all)['config'])
        ->toBe($this->modulePath.'/config/config.php');

    $files->put($this->modulePath.'/config/blog.php', '<?php return [];');

    expect(ModulePaths::resolve($this->modulePath, 'Blog', $all)['config'])
        ->toBe($this->modulePath.'/config/blog.php');
});

it('falls back from lang/ to resources/lang/', function () use ($all) {
    $files = new Filesystem;
    $files->ensureDirectoryExists($this->modulePath.'/resources/lang');

    expect(ModulePaths::resolve($this->modulePath, 'Blog', $all)['lang'])
        ->toBe($this->modulePath.'/resources/lang');

    $files->ensureDirectoryExists($this->modulePath.'/lang');

    expect(ModulePaths::resolve($this->modulePath, 'Blog', $all)['lang'])
        ->toBe($this->modulePath.'/lang');
});

it('does not resolve a folder the #[Module] attribute switched off', function () {
    $files = new Filesystem;
    $files->ensureDirectoryExists($this->modulePath.'/resources/views');
    $files->ensureDirectoryExists($this->modulePath.'/lang');

    $paths = ModulePaths::resolve($this->modulePath, 'Blog', [
        'config' => true, 'migrations' => true, 'views' => false, 'routes' => true, 'lang' => false,
    ]);

    expect($paths['views'])->toBeNull()->and($paths['lang'])->toBeNull();
});
