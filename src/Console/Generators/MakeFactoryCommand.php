<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Console\Generators;

use Dem1Off\LaravelModular\Operations\ClassLayer;

final class MakeFactoryCommand extends ModuleGeneratorCommand
{
    protected $signature = 'module:make-factory {module} {name} {--force}';

    protected $description = 'Create a model factory inside a module';

    protected function layer(): ClassLayer
    {
        return ClassLayer::factory();
    }
}
