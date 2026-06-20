<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Console\Generators;

final class MakeModelCommand extends ModuleGeneratorCommand
{
    protected $signature = 'module:make-model {module} {name} {--force}';

    protected $description = 'Create an Eloquent model inside a module';

    protected function stub(): string
    {
        return 'model.stub';
    }

    protected function layerPath(): string
    {
        return 'Infrastructure/Persistence/Models';
    }

    protected function layerNamespace(): string
    {
        return 'Infrastructure\\Persistence\\Models';
    }
}