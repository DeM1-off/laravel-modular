<?php

declare(strict_types=1);

use Dem1Off\LaravelModular\Module\ProvidesScanner;
use Dem1Off\LaravelModular\Tests\Fixtures\ProvidesSample\CacheContract;
use Dem1Off\LaravelModular\Tests\Fixtures\ProvidesSample\Mailer;
use Dem1Off\LaravelModular\Tests\Fixtures\ProvidesSample\RedisCache;
use Dem1Off\LaravelModular\Tests\Fixtures\ProvidesSample\SmtpMailer;
use Illuminate\Filesystem\Filesystem;

it('discovers explicit #[Provides] implementations', function () {
    $binds = (new ProvidesScanner(new Filesystem))->scan(
        __DIR__.'/../Fixtures/ProvidesSample',
        'Dem1Off\\LaravelModular\\Tests\\Fixtures\\ProvidesSample',
        '',
    );

    expect($binds)->toContain([
        'abstract' => CacheContract::class,
        'concrete' => RedisCache::class,
        'singleton' => true,
    ]);
});

it('infers the abstract from a single implemented interface', function () {
    $binds = (new ProvidesScanner(new Filesystem))->scan(
        __DIR__.'/../Fixtures/ProvidesSample',
        'Dem1Off\\LaravelModular\\Tests\\Fixtures\\ProvidesSample',
        '',
    );

    expect($binds)->toContain([
        'abstract' => Mailer::class,
        'concrete' => SmtpMailer::class,
        'singleton' => false,
    ]);
});
