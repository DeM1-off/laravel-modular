<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Tests\Fixtures\ProvidesSample;

use Dem1Off\LaravelModular\Module\Attributes\Singleton;

#[Singleton]
final class CsvExporter implements Exporter
{
    public function export(): string
    {
        return 'csv';
    }
}
