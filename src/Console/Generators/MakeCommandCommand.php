<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Console\Generators;

use Dem1Off\LaravelModular\Operations\ClassLayer;

final class MakeCommandCommand extends ModuleGeneratorCommand
{
    protected $signature = 'module:make-command {module} {name} {--force}';

    protected $description = 'Create an artisan command inside a module (auto-discovered from the Console directory)';

    protected function layer(): ClassLayer
    {
        return ClassLayer::command();
    }
}
