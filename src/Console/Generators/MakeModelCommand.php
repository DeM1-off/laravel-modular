<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Console\Generators;

use Dem1Off\LaravelModular\Operations\ClassLayer;

final class MakeModelCommand extends ModuleGeneratorCommand
{
    protected $signature = 'module:make-model {module} {name} {--force}';

    protected $description = 'Create an Eloquent model inside a module';

    protected function layer(): ClassLayer
    {
        return ClassLayer::model();
    }
}
