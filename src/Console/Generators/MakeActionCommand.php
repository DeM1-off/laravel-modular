<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Console\Generators;

use Dem1Off\LaravelModular\Operations\ClassLayer;

final class MakeActionCommand extends ModuleGeneratorCommand
{
    protected $signature = 'module:make-action {module} {name} {--force}';

    protected $description = 'Create an application use-case (action) inside a module';

    protected function layer(): ClassLayer
    {
        return ClassLayer::action();
    }
}
