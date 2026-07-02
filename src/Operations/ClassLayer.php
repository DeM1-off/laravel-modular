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
    /**
     * @param  bool  $inAppFolder  false for layers rooted at the module (tests, database)
     */
    public function __construct(
        public string $stub,
        public string $path,
        public string $namespace,
        public string $suffix = '',
        public bool $inAppFolder = true,
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

    /** The HTTP form-request layer. */
    public static function request(): self
    {
        return new self('request.stub', 'Infrastructure/Http/Requests', 'Infrastructure\\Http\\Requests', 'Request');
    }

    /** The domain event layer. */
    public static function event(): self
    {
        return new self('event.stub', 'Domain/Events', 'Domain\\Events');
    }

    /** The application event-listener layer. */
    public static function listener(): self
    {
        return new self('listener.stub', 'Application/Listeners', 'Application\\Listeners');
    }

    /** The application queued-job layer. */
    public static function job(): self
    {
        return new self('job.stub', 'Application/Jobs', 'Application\\Jobs');
    }

    /** The artisan command layer — discovered by convention (Console directory). */
    public static function command(): self
    {
        return new self('console-command.stub', 'Infrastructure/Console', 'Infrastructure\\Console', 'Command');
    }

    /** The model factory layer, at the module root next to migrations. */
    public static function factory(): self
    {
        return new self('factory.stub', 'database/factories', 'Database\\Factories', 'Factory', inAppFolder: false);
    }

    /** The database seeder layer, at the module root next to migrations. */
    public static function seeder(): self
    {
        return new self('seeder.stub', 'database/seeders', 'Database\\Seeders', 'Seeder', inAppFolder: false);
    }

    /** The feature-test layer, at the module root. */
    public static function test(): self
    {
        return new self('test.stub', 'tests/Feature', 'Tests\\Feature', 'Test', inAppFolder: false);
    }

    /** Studly-case the name and append the suffix when it is missing. */
    public function className(string $name): string
    {
        $class = Str::studly($name);

        return $this->suffix !== '' && ! str_ends_with($class, $this->suffix) ? $class.$this->suffix : $class;
    }

    /** The class name without the layer suffix — feeds name-derived stub values. */
    public function baseName(string $class): string
    {
        return $this->suffix !== '' && str_ends_with($class, $this->suffix) && $class !== $this->suffix
            ? substr($class, 0, -strlen($this->suffix))
            : $class;
    }
}
