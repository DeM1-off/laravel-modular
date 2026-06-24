<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Operations;

/**
 * The concrete, copy-pasteable steps to move a module into its own package.
 * A value object: the console renders it, it never renders itself.
 */
final readonly class PromotionPlan
{
    public function __construct(
        public string $module,
        public string $package,
    ) {}

    /**
     * @return list<string>
     */
    public function steps(): array
    {
        return [
            "1. Move the module to its own repo (e.g. git subtree split --prefix=Modules/{$this->module} -b {$this->module}-module).",
            '2. In the app composer.json, replace the path repository with a vcs/registry entry:',
            "       \"require\": { \"{$this->package}\": \"^1.0\" }",
            "3. composer update {$this->package}",
        ];
    }
}
