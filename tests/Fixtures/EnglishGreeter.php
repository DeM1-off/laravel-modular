<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Tests\Fixtures;

final class EnglishGreeter implements Greeter
{
    public function greet(): string
    {
        return 'hello';
    }
}