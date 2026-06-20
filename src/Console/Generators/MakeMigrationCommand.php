<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Console\Generators;

use Dem1Off\LaravelModular\Manager\ModuleManager;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

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
        $module = Str::studly((string) $this->argument('module'));

        if (! $manager->has($module)) {
            $this->components->error("Module [{$module}] not found.");

            return self::FAILURE;
        }

        $name = Str::snake((string) $this->argument('name'));
        $table = (string) ($this->option('table') ?: $name);
        $dir = $manager->path($module).'/database/migrations';
        $file = $dir.'/'.date('Y_m_d_His').'_'.$name.'.php';

        $this->files->ensureDirectoryExists($dir);

        $published = base_path('stubs/modular/migration.stub');
        $stubPath = is_file($published) ? $published : __DIR__.'/../../../stubs/migration.stub';
        $this->files->put($file, strtr($this->files->get($stubPath), ['{{ table }}' => $table]));

        $this->components->info('Migration ['.basename($file).'] created in module '.$module.'.');

        return self::SUCCESS;
    }
}