<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Console\Generators;

use Dem1Off\LaravelModular\Operations\ClassLayer;

final class MakeJobCommand extends ModuleGeneratorCommand
{
    protected $signature = 'module:make-job {module} {name} {--force}';

    protected $description = 'Create a queued job inside a module';

    protected function layer(): ClassLayer
    {
        return ClassLayer::job();
    }
}
