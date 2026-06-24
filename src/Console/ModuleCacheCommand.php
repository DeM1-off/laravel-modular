<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Console;

use Dem1Off\LaravelModular\Operations\CompileModuleCache;
use Illuminate\Console\Command;

/**
 * Thin adapter: delegate compilation to the CompileModuleCache use-case.
 */
final class ModuleCacheCommand extends Command
{
    protected $signature = 'module:cache';

    protected $description = 'Compile module discovery and attributes into a fast cache';

    public function handle(CompileModuleCache $compile): int
    {
        $count = $compile->execute(
            (string) config('modules.namespace'),
            (string) config('modules.paths.app_folder', 'src/'),
        );

        $this->components->info("Modules cached ({$count} enabled).");

        return self::SUCCESS;
    }
}
