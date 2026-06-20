<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Console;

use Dem1Off\LaravelModular\Manager\ModuleManager;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

final class ModuleEnableCommand extends Command
{
    protected $signature = 'module:enable {module : The module name}';

    protected $description = 'Enable a module';

    public function handle(ModuleManager $manager): int
    {
        $name = Str::studly($this->argument('module'));

        if (! $manager->has($name)) {
            $this->components->error("Module [{$name}] not found.");

            return self::FAILURE;
        }

        $manager->setStatus($name, true);
        $this->components->info("Module [{$name}] enabled.");

        return self::SUCCESS;
    }
}