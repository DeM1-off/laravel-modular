<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Console;

use Dem1Off\LaravelModular\Manager\ModuleManager;
use Dem1Off\LaravelModular\Operations\SetModuleStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

final class ModuleEnableCommand extends Command
{
    protected $signature = 'module:enable {module : The module name}';

    protected $description = 'Enable a module';

    public function handle(SetModuleStatus $status, ModuleManager $manager): int
    {
        /** @var string $argument */
        $argument = $this->argument('module');
        $name = Str::studly($argument);

        if (! $status->execute($name, true)) {
            $this->components->error("Module [{$name}] not found.");

            return self::FAILURE;
        }

        $this->components->info("Module [{$name}] enabled.");

        $module = $manager->find($name);

        if ($module !== null) {
            foreach ($module->requires as $requirement) {
                if (! $manager->has($requirement)) {
                    $this->components->warn("[{$name}] requires [{$requirement}], which is not installed.");
                } elseif (! $manager->isEnabled($requirement)) {
                    $this->components->warn("[{$name}] requires [{$requirement}], which is disabled.");
                }
            }
        }

        return self::SUCCESS;
    }
}
