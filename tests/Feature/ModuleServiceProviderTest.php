<?php

declare(strict_types=1);

use Dem1Off\LaravelModular\Tests\Fixtures\BlogServiceProvider;
use Dem1Off\LaravelModular\Tests\Fixtures\ConsoleSampleServiceProvider;
use Dem1Off\LaravelModular\Tests\Fixtures\Counter;
use Dem1Off\LaravelModular\Tests\Fixtures\EnglishGreeter;
use Dem1Off\LaravelModular\Tests\Fixtures\Greeter;
use Illuminate\Filesystem\Filesystem;

beforeEach(function () {
    $files = new Filesystem;
    $blog = $this->modulesPath.'/Blog';

    $files->ensureDirectoryExists($blog.'/config');
    $files->put($blog.'/module.json', json_encode([
        'name' => 'Blog',
        'providers' => [BlogServiceProvider::class],
    ]));
    $files->put($blog.'/config/blog.php', "<?php\n\nreturn ['feature' => true];\n");
});

it('applies #[Bind] attributes', function () {
    $this->app->register(BlogServiceProvider::class);

    expect($this->app->make(Greeter::class))->toBeInstanceOf(EnglishGreeter::class);
});

it('binds singletons via #[Bind(singleton: true)]', function () {
    $this->app->register(BlogServiceProvider::class);

    expect($this->app->make(Counter::class))->toBe($this->app->make(Counter::class));
});

it('loads the module config by convention', function () {
    $this->app->register(BlogServiceProvider::class);

    expect(config('blog.feature'))->toBeTrue();
});

it('loads translations by convention', function () {
    $files = new Filesystem;
    $files->ensureDirectoryExists($this->modulesPath.'/Blog/lang/en');
    $files->put($this->modulesPath.'/Blog/lang/en/messages.php', "<?php\n\nreturn ['welcome' => 'Welcome'];\n");

    $this->app->register(BlogServiceProvider::class);

    expect(trans('blog::messages.welcome'))->toBe('Welcome');
});

it('registers commands found in Console directories by convention', function () {
    config(['modules.namespace' => 'Dem1Off\\LaravelModular\\Tests\\Fixtures']);

    $files = new Filesystem;
    $sample = $this->modulesPath.'/ConsoleSample';
    $files->ensureDirectoryExists($sample.'/src/Console');
    $files->put($sample.'/module.json', json_encode([
        'name' => 'ConsoleSample',
        'providers' => [ConsoleSampleServiceProvider::class],
    ]));
    // The class autoloads from tests/Fixtures; the module copy just has to exist.
    $files->put($sample.'/src/Console/GreetCommand.php', '<?php');

    $this->app->register(ConsoleSampleServiceProvider::class);

    $this->artisan('console-sample:greet')->assertSuccessful();
});

it('no-ops when the module is disabled', function () {
    (new Filesystem)->put($this->statusesFile, json_encode(['Blog' => false]));

    $this->app->register(BlogServiceProvider::class);

    expect($this->app->bound(Greeter::class))->toBeFalse();
});
