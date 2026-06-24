<?php

declare(strict_types=1);

namespace Dem1Off\LaravelModular\Operations;

use Closure;

/**
 * Use-case: toggle git's skip-worktree bit for the files that local-dev linking
 * mutates (composer.json / composer.lock).
 *
 * With the bit set, git stops surfacing those files in `git status` / `git diff`
 * while modules are linked, so a project's diff shows only real code changes;
 * unlinking clears the bit and the files reappear.
 *
 * The git invocation is injected as a runner, so the bit-flipping logic stays
 * console-free and unit-testable.
 */
final readonly class HideFromGit
{
    /** @var Closure(list<string>): bool */
    private Closure $git;

    /**
     * @param  Closure(list<string>): bool  $git  Runs `git <args...>`, returns success.
     */
    public function __construct(Closure $git)
    {
        $this->git = $git;
    }

    /**
     * @param  list<string>  $paths
     */
    public function hide(array $paths): bool
    {
        return $this->run('--skip-worktree', $paths);
    }

    /**
     * @param  list<string>  $paths
     */
    public function reveal(array $paths): bool
    {
        return $this->run('--no-skip-worktree', $paths);
    }

    /**
     * @param  list<string>  $paths
     */
    private function run(string $flag, array $paths): bool
    {
        if ($paths === []) {
            return true;
        }

        return ($this->git)(['update-index', $flag, ...$paths]);
    }
}
