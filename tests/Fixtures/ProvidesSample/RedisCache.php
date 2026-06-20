<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Tests\Fixtures\ProvidesSample;

use Dem1Off\LaravelModular\Module\Attributes\Provides;

#[Provides(CacheContract::class, singleton: true)]
final class RedisCache implements CacheContract
{
    public function get(string $key): mixed
    {
        return null;
    }
}
