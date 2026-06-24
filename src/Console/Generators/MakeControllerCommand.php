<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Console\Generators;

use Dem1Off\LaravelModular\Operations\ClassLayer;

final class MakeControllerCommand extends ModuleGeneratorCommand
{
    protected $signature = 'module:make-controller {module} {name} {--force}';

    protected $description = 'Create a controller inside a module';

    protected function layer(): ClassLayer
    {
        return ClassLayer::controller();
    }
}
