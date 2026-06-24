<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Console;

use Dem1Off\LaravelModular\Operations\ClearModuleCache;
use Illuminate\Console\Command;

final class ModuleClearCommand extends Command
{
    protected $signature = 'module:clear';

    protected $description = 'Remove the compiled modules cache';

    public function handle(ClearModuleCache $clear): int
    {
        $clear->execute();

        $this->components->info('Module cache cleared.');

        return self::SUCCESS;
    }
}
