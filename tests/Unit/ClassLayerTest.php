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

it('describes the app-folder layers added in 1.5', function () {
    expect(ClassLayer::request()->path)->toBe('Infrastructure/Http/Requests')
        ->and(ClassLayer::request()->suffix)->toBe('Request')
        ->and(ClassLayer::event()->namespace)->toBe('Domain\\Events')
        ->and(ClassLayer::listener()->namespace)->toBe('Application\\Listeners')
        ->and(ClassLayer::job()->namespace)->toBe('Application\\Jobs')
        ->and(ClassLayer::command()->path)->toBe('Infrastructure/Console')
        ->and(ClassLayer::command()->suffix)->toBe('Command');
});

it('roots factories, seeders and tests at the module, not the app folder', function () {
    expect(ClassLayer::factory()->inAppFolder)->toBeFalse()
        ->and(ClassLayer::factory()->namespace)->toBe('Database\\Factories')
        ->and(ClassLayer::seeder()->path)->toBe('database/seeders')
        ->and(ClassLayer::test()->path)->toBe('tests/Feature')
        ->and(ClassLayer::action()->inAppFolder)->toBeTrue();
});

it('strips the suffix for the base name', function () {
    $layer = ClassLayer::command();

    expect($layer->baseName('PublishPostsCommand'))->toBe('PublishPosts')
        ->and($layer->baseName('Command'))->toBe('Command')
        ->and(ClassLayer::model()->baseName('Post'))->toBe('Post');
});
