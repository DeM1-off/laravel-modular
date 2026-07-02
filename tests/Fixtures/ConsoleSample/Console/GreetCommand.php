<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Tests\Fixtures\ConsoleSample\Console;

use Illuminate\Console\Command;

final class GreetCommand extends Command
{
    protected $signature = 'console-sample:greet';

    protected $description = 'Fixture command discovered by convention';

    public function handle(): int
    {
        return self::SUCCESS;
    }
}
