<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Tests\Fixtures\ConsoleSample;

use Illuminate\Console\Command;

final class NotACommand extends Command
{
    protected $signature = 'console-sample:outside';

    protected $description = 'Lives outside a Console directory, so it is not discovered';
}
