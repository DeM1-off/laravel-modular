<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Console\Generators;

use Dem1Off\LaravelModular\Operations\ClassLayer;

final class MakeEventCommand extends ModuleGeneratorCommand
{
    protected $signature = 'module:make-event {module} {name} {--force}';

    protected $description = 'Create a domain event inside a module';

    protected function layer(): ClassLayer
    {
        return ClassLayer::event();
    }
}
