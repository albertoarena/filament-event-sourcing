<?php

declare(strict_types=1);

use Albertoarena\FilamentEventSourcing\Tests\Fixtures\Post;
use Albertoarena\FilamentEventSourcing\Tests\Fixtures\PostAggregate;
use Illuminate\Support\Str;

it('projects a created post', function () {
    $uuid = Str::uuid()->toString();

    PostAggregate::retrieve($uuid)
        ->createPost('Hello world', 'The body')
        ->persist();

    $post = Post::find($uuid);

    expect($post)->not->toBeNull()
        ->and($post->title)->toBe('Hello world')
        ->and($post->body)->toBe('The body');
});

it('projects a title change', function () {
    $uuid = Str::uuid()->toString();

    PostAggregate::retrieve($uuid)
        ->createPost('Original', 'The body')
        ->persist();

    PostAggregate::retrieve($uuid)
        ->changeTitle('Updated')
        ->persist();

    expect(Post::find($uuid)->title)->toBe('Updated');
});

it('removes the projection when the post is deleted', function () {
    $uuid = Str::uuid()->toString();

    PostAggregate::retrieve($uuid)
        ->createPost('Doomed', 'The body')
        ->persist();

    expect(Post::find($uuid))->not->toBeNull();

    PostAggregate::retrieve($uuid)
        ->deletePost()
        ->persist();

    expect(Post::find($uuid))->toBeNull();
});
