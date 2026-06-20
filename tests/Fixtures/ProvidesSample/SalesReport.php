<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Tests\Fixtures\ProvidesSample;

use Dem1Off\LaravelModular\Module\Attributes\Provides;

#[Provides(Report::class, tag: 'reports')]
final class SalesReport implements Report
{
    public function title(): string
    {
        return 'sales';
    }
}
