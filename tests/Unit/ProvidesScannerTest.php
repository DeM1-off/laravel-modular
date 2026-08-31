<?php

declare(strict_types=1);

use Dem1Off\LaravelModular\Module\ProvidesScanner;
use Dem1Off\LaravelModular\Module\ScanCache;
use Dem1Off\LaravelModular\Tests\Fixtures\ProvidesSample\CacheContract;
use Dem1Off\LaravelModular\Tests\Fixtures\ProvidesSample\CsvExporter;
use Dem1Off\LaravelModular\Tests\Fixtures\ProvidesSample\Exporter;
use Dem1Off\LaravelModular\Tests\Fixtures\ProvidesSample\Mailer;
use Dem1Off\LaravelModular\Tests\Fixtures\ProvidesSample\RedisCache;
use Dem1Off\LaravelModular\Tests\Fixtures\ProvidesSample\Report;
use Dem1Off\LaravelModular\Tests\Fixtures\ProvidesSample\SalesReport;
use Dem1Off\LaravelModular\Tests\Fixtures\ProvidesSample\SmtpMailer;
use Illuminate\Filesystem\Filesystem;

function scanSample(): array
{
    return (new ProvidesScanner(new Filesystem))->scan(
        __DIR__.'/../Fixtures/ProvidesSample',
        'Dem1Off\\LaravelModular\\Tests\\Fixtures\\ProvidesSample',
        '',
    );
}

it('discovers explicit #[Provides] implementations', function () {
    expect(scanSample()['binds'])->toContain([
        'abstract' => CacheContract::class,
        'concrete' => RedisCache::class,
        'lifetime' => 'singleton',
    ]);
});

it('infers the abstract from a single implemented interface', function () {
    expect(scanSample()['binds'])->toContain([
        'abstract' => Mailer::class,
        'concrete' => SmtpMailer::class,
        'lifetime' => 'bind',
    ]);
});

it('treats #[Singleton] as a singleton binding', function () {
    expect(scanSample()['binds'])->toContain([
        'abstract' => Exporter::class,
        'concrete' => CsvExporter::class,
        'lifetime' => 'singleton',
    ]);
});

it('collects #[Provides(tag:)] into tags', function () {
    $result = scanSample();

    expect($result['tags'])->toContain([
        'tag' => 'reports',
        'concrete' => SalesReport::class,
    ])->and($result['binds'])->toContain([
        'abstract' => Report::class,
        'concrete' => SalesReport::class,
        'lifetime' => 'bind',
    ]);
});

it('caches scan results to a file', function () {
    $cacheFile = sys_get_temp_dir().'/lm-scan-'.uniqid().'.php';
    $scanner = new ProvidesScanner(new Filesystem, new ScanCache(new Filesystem, $cacheFile));

    $first = $scanner->scan(
        __DIR__.'/../Fixtures/ProvidesSample',
        'Dem1Off\\LaravelModular\\Tests\\Fixtures\\ProvidesSample',
        '',
    );

    expect(file_exists($cacheFile))->toBeTrue();

    $second = $scanner->scan(
        __DIR__.'/../Fixtures/ProvidesSample',
        'Dem1Off\\LaravelModular\\Tests\\Fixtures\\ProvidesSample',
        '',
    );

    expect($second)->toBe($first);

    @unlink($cacheFile);
});
