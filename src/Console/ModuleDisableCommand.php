<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Console;

use Dem1Off\LaravelModular\Manager\ModuleManager;
use Dem1Off\LaravelModular\Operations\SetModuleStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

final class ModuleDisableCommand extends Command
{
    protected $signature = 'module:disable {module : The module name}';

    protected $description = 'Disable a module';

    public function handle(SetModuleStatus $status, ModuleManager $manager): int
    {
        /** @var string $argument */
        $argument = $this->argument('module');
        $name = Str::studly($argument);

        if (! $status->execute($name, false)) {
            $this->components->error("Module [{$name}] not found.");

            return self::FAILURE;
        }

        $this->components->info("Module [{$name}] disabled.");

        $dependents = array_filter(
            $manager->dependents($name),
            static fn (string $dependent): bool => $manager->isEnabled($dependent),
        );

        if ($dependents !== []) {
            $this->components->warn('Still-enabled modules require ['.$name.']: '.implode(', ', $dependents).'.');
        }

        return self::SUCCESS;
    }
}
