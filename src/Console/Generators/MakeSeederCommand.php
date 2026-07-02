<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Console\Generators;

use Dem1Off\LaravelModular\Operations\ClassLayer;

final class MakeSeederCommand extends ModuleGeneratorCommand
{
    protected $signature = 'module:make-seeder {module} {name} {--force}';

    protected $description = 'Create a database seeder inside a module';

    protected function layer(): ClassLayer
    {
        return ClassLayer::seeder();
    }
}
