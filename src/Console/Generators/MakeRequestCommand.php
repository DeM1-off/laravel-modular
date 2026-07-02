<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Console\Generators;

use Dem1Off\LaravelModular\Operations\ClassLayer;

final class MakeRequestCommand extends ModuleGeneratorCommand
{
    protected $signature = 'module:make-request {module} {name} {--force}';

    protected $description = 'Create a form request inside a module';

    protected function layer(): ClassLayer
    {
        return ClassLayer::request();
    }
}
