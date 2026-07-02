<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Console\Generators;

use Dem1Off\LaravelModular\Operations\ClassLayer;

final class MakeTestCommand extends ModuleGeneratorCommand
{
    protected $signature = 'module:make-test {module} {name} {--force}';

    protected $description = 'Create a feature test inside a module';

    protected function layer(): ClassLayer
    {
        return ClassLayer::test();
    }
}
