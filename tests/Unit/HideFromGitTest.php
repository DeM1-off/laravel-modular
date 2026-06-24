<?php

declare(strict_types=1);

use Dem1Off\LaravelModular\Operations\HideFromGit;

// No Orchestra TestCase, no artisan — the git call is injected as a closure.

it('sets the skip-worktree bit on hide', function () {
    $calls = [];
    $git = new HideFromGit(function (array $args) use (&$calls): bool {
        $calls[] = $args;

        return true;
    });

    expect($git->hide(['composer.json', 'composer.lock']))->toBeTrue()
        ->and($calls)->toBe([
            ['update-index', '--skip-worktree', 'composer.json', 'composer.lock'],
        ]);
});

it('clears the skip-worktree bit on reveal', function () {
    $calls = [];
    $git = new HideFromGit(function (array $args) use (&$calls): bool {
        $calls[] = $args;

        return true;
    });

    $git->reveal(['composer.json']);

    expect($calls)->toBe([['update-index', '--no-skip-worktree', 'composer.json']]);
});

it('does not invoke git when there are no paths', function () {
    $called = false;
    $git = new HideFromGit(function () use (&$called): bool {
        $called = true;

        return true;
    });

    expect($git->hide([]))->toBeTrue()
        ->and($called)->toBeFalse();
});

it('propagates a failed git invocation', function () {
    $git = new HideFromGit(fn (array $args): bool => false);

    expect($git->hide(['composer.json']))->toBeFalse();
});
