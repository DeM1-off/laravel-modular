<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Console\Generators;

use Dem1Off\LaravelModular\Operations\ClassLayer;

final class MakeListenerCommand extends ModuleGeneratorCommand
{
    protected $signature = 'module:make-listener {module} {name} {--force}';

    protected $description = 'Create an event listener inside a module';

    protected function layer(): ClassLayer
    {
        return ClassLayer::listener();
    }
}
