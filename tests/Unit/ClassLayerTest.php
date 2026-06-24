<?php

declare(strict_types=1);

use Dem1Off\LaravelModular\Operations\ClassLayer;

it('describes the action layer', function () {
    $layer = ClassLayer::action();

    expect($layer->stub)->toBe('action.stub')
        ->and($layer->path)->toBe('Application/UseCases')
        ->and($layer->namespace)->toBe('Application\\UseCases')
        ->and($layer->suffix)->toBe('');
});

it('describes the controller layer with a suffix', function () {
    $layer = ClassLayer::controller();

    expect($layer->path)->toBe('Infrastructure/Http/Controllers')
        ->and($layer->suffix)->toBe('Controller');
});

it('studly-cases the class name', function () {
    expect(ClassLayer::model()->className('blog_post'))->toBe('BlogPost');
});

it('appends the suffix only when missing', function () {
    $layer = ClassLayer::controller();

    expect($layer->className('post'))->toBe('PostController')
        ->and($layer->className('PostController'))->toBe('PostController');
});
