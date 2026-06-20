<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Tests\Fixtures\ProvidesSample;

interface Mailer
{
    public function send(string $to): void;
}
