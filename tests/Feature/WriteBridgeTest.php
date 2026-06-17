<?php

declare(strict_types=1);

use Albertoarena\FilamentEventSourcing\Exceptions\ProjectionNotFoundException;
use Albertoarena\FilamentEventSourcing\Tests\Fixtures\Events\PostCreated;
use Albertoarena\FilamentEventSourcing\Tests\Fixtures\Events\PostDeleted;
use Albertoarena\FilamentEventSourcing\Tests\Fixtures\Events\PostTitleChanged;
use Albertoarena\FilamentEventSourcing\Tests\Fixtures\Filament\Pages\CreatePost;
use Albertoarena\FilamentEventSourcing\Tests\Fixtures\Filament\Pages\CreatePostWithFixedUuid;
use Albertoarena\FilamentEventSourcing\Tests\Fixtures\Filament\Pages\EditPost;
use Albertoarena\FilamentEventSourcing\Tests\Fixtures\Filament\Pages\ViewPost;
use Albertoarena\FilamentEventSourcing\Tests\Fixtures\Post;
use Albertoarena\FilamentEventSourcing\Tests\Fixtures\PostAggregate;
use Filament\Facades\Filament;
use Illuminate\Support\Str;
use Spatie\EventSourcing\Projectionist;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAsUser();
    Filament::setCurrentPanel('admin');
});

function storedEvents(string $eventClass): int
{
    return config('event-sourcing.stored_event_model')::where('event_class', $eventClass)->count();
}

it('creates through the aggregate and returns the projector built projection', function () {
    livewire(CreatePost::class)
        ->fillForm(['title' => 'Hello', 'body' => 'World'])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertRedirect();

    expect(storedEvents(PostCreated::class))->toBe(1);

    $post = Post::firstWhere('title', 'Hello');
    expect($post)->not->toBeNull()
        ->and($post->body)->toBe('World');
});

it('throws when the projection is missing after creation', function () {
    app()->forgetInstance(Projectionist::class);

    livewire(CreatePost::class)
        ->fillForm(['title' => 'Orphan', 'body' => 'No projector'])
        ->call('create');
})->throws(ProjectionNotFoundException::class, 'queued');

it('honours the newAggregateUuid override', function () {
    livewire(CreatePostWithFixedUuid::class)
        ->fillForm(['title' => 'Fixed', 'body' => 'Uuid'])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Post::find(CreatePostWithFixedUuid::FIXED_UUID))->not->toBeNull();
});

it('updates through the aggregate and refreshes the form', function () {
    $uuid = Str::uuid()->toString();
    PostAggregate::retrieve($uuid)->createPost('Original', 'Body')->persist();

    livewire(EditPost::class, ['record' => $uuid])
        ->fillForm(['title' => 'Updated', 'body' => 'Body'])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertFormSet(['title' => 'Updated']);

    expect(storedEvents(PostTitleChanged::class))->toBe(1)
        ->and(Post::find($uuid)->title)->toBe('Updated');
});

it('deletes through the aggregate so the projector removes the row', function () {
    $uuid = Str::uuid()->toString();
    PostAggregate::retrieve($uuid)->createPost('Doomed', 'Body')->persist();

    livewire(EditPost::class, ['record' => $uuid])
        ->callAction('delete');

    expect(storedEvents(PostDeleted::class))->toBe(1)
        ->and(Post::find($uuid))->toBeNull();
});

it('throws a LogicException when the delete action has no using closure', function () {
    $uuid = Str::uuid()->toString();
    PostAggregate::retrieve($uuid)->createPost('Doomed', 'Body')->persist();

    livewire(ViewPost::class, ['record' => $uuid])
        ->callAction('delete');
})->throws(LogicException::class, '->using()');
