<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Console\Generators;

final class MakeControllerCommand extends ModuleGeneratorCommand
{
    protected $signature = 'module:make-controller {module} {name} {--force}';

    protected $description = 'Create a controller inside a module';

    protected function stub(): string
    {
        return 'controller.stub';
    }

    protected function layerPath(): string
    {
        return 'Infrastructure/Http/Controllers';
    }

    protected function layerNamespace(): string
    {
        return 'Infrastructure\\Http\\Controllers';
    }

    protected function classSuffix(): string
    {
        return 'Controller';
    }
}