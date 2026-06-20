<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Console;

use Dem1Off\LaravelModular\Manager\ModuleManager;
use Illuminate\Console\Command;

final class ModuleListCommand extends Command
{
    protected $signature = 'module:list';

    protected $description = 'List all discovered modules and their status';

    public function handle(ModuleManager $manager): int
    {
        $modules = $manager->all();

        if ($modules === []) {
            $this->components->warn('No modules found.');

            return self::SUCCESS;
        }

        $rows = [];

        foreach ($modules as $module) {
            $rows[] = [
                $module->name,
                $module->enabled ? '<fg=green>enabled</>' : '<fg=red>disabled</>',
                count($module->providers),
            ];
        }

        $this->table(['Module', 'Status', 'Providers'], $rows);

        return self::SUCCESS;
    }
}
