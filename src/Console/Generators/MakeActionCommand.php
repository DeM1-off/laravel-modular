<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Console\Generators;

final class MakeActionCommand extends ModuleGeneratorCommand
{
    protected $signature = 'module:make-action {module} {name} {--force}';

    protected $description = 'Create an application use-case (action) inside a module';

    protected function stub(): string
    {
        return 'action.stub';
    }

    protected function layerPath(): string
    {
        return 'Application/UseCases';
    }

    protected function layerNamespace(): string
    {
        return 'Application\\UseCases';
    }
}