<?php

declare(strict_types=1);

use Dem1Off\LaravelModular\Module\CommandScanner;
use Dem1Off\LaravelModular\Module\ScanCache;
use Dem1Off\LaravelModular\Tests\Fixtures\ConsoleSample\Console\AbstractSampleCommand;
use Dem1Off\LaravelModular\Tests\Fixtures\ConsoleSample\Console\ConsoleHelper;
use Dem1Off\LaravelModular\Tests\Fixtures\ConsoleSample\Console\GreetCommand;
use Dem1Off\LaravelModular\Tests\Fixtures\ConsoleSample\NotACommand;
use Illuminate\Filesystem\Filesystem;

function scanConsoleSample(string $appFolder = ''): array
{
    return (new CommandScanner(new Filesystem))->scan(
        __DIR__.'/../Fixtures/ConsoleSample',
        'Dem1Off\\LaravelModular\\Tests\\Fixtures\\ConsoleSample',
        $appFolder,
    );
}

it('discovers instantiable commands inside Console directories', function () {
    expect(scanConsoleSample())->toBe([GreetCommand::class]);
});

it('skips abstract classes, non-commands and commands outside Console directories', function () {
    expect(scanConsoleSample())->not->toContain(
        AbstractSampleCommand::class,
        ConsoleHelper::class,
        NotACommand::class,
    );
});

it('returns nothing when the app folder is missing', function () {
    expect(scanConsoleSample('src/'))->toBe([]);
});

it('memoises scan results to a file', function () {
    $cacheFile = sys_get_temp_dir().'/lm-commands-'.uniqid().'.php';
    $scanner = new CommandScanner(new Filesystem, new ScanCache(new Filesystem, $cacheFile));

    $first = $scanner->scan(
        __DIR__.'/../Fixtures/ConsoleSample',
        'Dem1Off\\LaravelModular\\Tests\\Fixtures\\ConsoleSample',
        '',
    );

    expect(file_exists($cacheFile))->toBeTrue()
        ->and($first)->toBe([GreetCommand::class]);

    $second = $scanner->scan(
        __DIR__.'/../Fixtures/ConsoleSample',
        'Dem1Off\\LaravelModular\\Tests\\Fixtures\\ConsoleSample',
        '',
    );

    expect($second)->toBe($first);

    @unlink($cacheFile);
});
