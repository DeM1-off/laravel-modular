<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Console;

use Dem1Off\LaravelModular\Manager\ModuleManager;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

final class ModuleDisableCommand extends Command
{
    protected $signature = 'module:disable {module : The module name}';

    protected $description = 'Disable a module';

    public function handle(ModuleManager $manager): int
    {
        /** @var string $argument */
        $argument = $this->argument('module');
        $name = Str::studly($argument);

        if (! $manager->has($name)) {
            $this->components->error("Module [{$name}] not found.");

            return self::FAILURE;
        }

        $manager->setStatus($name, false);
        $this->components->info("Module [{$name}] disabled.");

        return self::SUCCESS;
    }
}
