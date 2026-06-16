<?php

declare(strict_types=1);

use Albertoarena\FilamentEventSourcing\Tests\Fixtures\Article;
use Albertoarena\FilamentEventSourcing\Tests\Fixtures\Comment;
use Albertoarena\FilamentEventSourcing\Tests\Fixtures\CustomStoredEvent;
use Albertoarena\FilamentEventSourcing\Tests\Fixtures\Events\PostCreated;
use Albertoarena\FilamentEventSourcing\Tests\Fixtures\Events\PostTitleChanged;
use Albertoarena\FilamentEventSourcing\Tests\Fixtures\Post;
use Albertoarena\FilamentEventSourcing\Tests\Fixtures\PostAggregate;
use Illuminate\Support\Str;

it('returns the aggregate stored events in version order', function () {
    $uuid = Str::uuid()->toString();

    PostAggregate::retrieve($uuid)->createPost('Original', 'Body')->persist();
    PostAggregate::retrieve($uuid)->changeTitle('Updated')->persist();

    $events = Post::find($uuid)->storedEvents()->get();

    expect($events)->toHaveCount(2)
        ->and($events[0]->event_class)->toBe(PostCreated::class)
        ->and($events[1]->event_class)->toBe(PostTitleChanged::class);
});

it('uses the aggregate uuid column from config', function () {
    config()->set('filament-event-sourcing.aggregate_uuid_column', 'ref');

    $uuid = Str::uuid()->toString();
    PostAggregate::retrieve($uuid)->createPost('Original', 'Body')->persist();

    $article = Article::create(['ref' => $uuid]);

    expect($article->storedEvents()->count())->toBe(1);
});

it('prefers the per-model aggregate uuid column override', function () {
    config()->set('filament-event-sourcing.aggregate_uuid_column', 'uuid');

    $uuid = Str::uuid()->toString();
    PostAggregate::retrieve($uuid)->createPost('Original', 'Body')->persist();

    $comment = Comment::create(['comment_uuid' => $uuid]);

    expect($comment->storedEvents()->count())->toBe(1);
});

it('uses the stored event model configured by Spatie', function () {
    config()->set('event-sourcing.stored_event_model', CustomStoredEvent::class);

    $post = new Post(['uuid' => Str::uuid()->toString()]);

    expect($post->storedEvents()->getRelated())->toBeInstanceOf(CustomStoredEvent::class);
});
