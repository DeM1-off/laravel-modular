<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Console;

use Dem1Off\LaravelModular\Manager\ModuleCache;
use Dem1Off\LaravelModular\Module\ProvidesScanner;
use Illuminate\Console\Command;

final class ModuleClearCommand extends Command
{
    protected $signature = 'module:clear';

    protected $description = 'Remove the compiled modules cache';

    public function handle(ModuleCache $cache, ProvidesScanner $scanner): int
    {
        $cache->clear();
        $scanner->clearCache();

        $this->components->info('Module cache cleared.');

        return self::SUCCESS;
    }
}
