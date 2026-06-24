<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Console\Generators;

use Dem1Off\LaravelModular\Manager\ModuleManager;
use Dem1Off\LaravelModular\Operations\GenerateModuleMigration;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

/**
 * Thin adapter: resolve the module, delegate to the GenerateModuleMigration
 * use-case. The migration writing itself lives in the Operations layer.
 */
final class MakeMigrationCommand extends Command
{
    protected $signature = 'module:make-migration {module} {name} {--table=}';

    protected $description = 'Create a migration inside a module';

    public function __construct(private readonly Filesystem $files)
    {
        parent::__construct();
    }

    public function handle(ModuleManager $manager): int
    {
        /** @var string $moduleArg */
        $moduleArg = $this->argument('module');
        $module = Str::studly($moduleArg);

        if (! $manager->has($module)) {
            $this->components->error("Module [{$module}] not found.");

            return self::FAILURE;
        }

        /** @var string $nameArg */
        $nameArg = $this->argument('name');
        /** @var string|null $tableOption */
        $tableOption = $this->option('table');

        $generate = new GenerateModuleMigration(
            $this->files,
            packageStubs: __DIR__.'/../../../stubs',
            publishedStubs: base_path('stubs/modular'),
        );

        $file = $generate->execute($manager->path($module), $nameArg, $tableOption);

        $this->components->info('Migration ['.basename($file).'] created in module '.$module.'.');

        return self::SUCCESS;
    }
}
