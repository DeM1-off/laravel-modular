<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Operations;

use Illuminate\Support\Str;

/**
 * Where an in-module class lives and how it is named — stub, layer sub-path,
 * namespace segment and an optional class-name suffix. A value object so the
 * generator command and the GenerateModuleClass use-case share one source of
 * truth, the same way ModuleLayout backs the module scaffolder.
 */
final readonly class ClassLayer
{
    public function __construct(
        public string $stub,
        public string $path,
        public string $namespace,
        public string $suffix = '',
    ) {}

    /** The DDD application use-case (action) layer. */
    public static function action(): self
    {
        return new self('action.stub', 'Application/UseCases', 'Application\\UseCases');
    }

    /** The HTTP controller layer. */
    public static function controller(): self
    {
        return new self('controller.stub', 'Infrastructure/Http/Controllers', 'Infrastructure\\Http\\Controllers', 'Controller');
    }

    /** The Eloquent persistence model layer. */
    public static function model(): self
    {
        return new self('model.stub', 'Infrastructure/Persistence/Models', 'Infrastructure\\Persistence\\Models');
    }

    /** Studly-case the name and append the suffix when it is missing. */
    public function className(string $name): string
    {
        $class = Str::studly($name);

        return $this->suffix !== '' && ! str_ends_with($class, $this->suffix) ? $class.$this->suffix : $class;
    }
}
