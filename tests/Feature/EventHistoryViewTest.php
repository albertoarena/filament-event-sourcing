<?php

declare(strict_types=1);

use Albertoarena\FilamentEventSourcing\Actions\EventHistoryAction;
use Albertoarena\FilamentEventSourcing\Tests\Fixtures\Post;
use Albertoarena\FilamentEventSourcing\Tests\Fixtures\PostAggregate;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/*
 * Presentation tests for the event-history Blade view.
 *
 * The view ships its own scoped styling and highlights the JSON payload
 * server-side, so these tests lock down the rendered markup: timeline
 * structure, per-event category classes, JSON token highlighting, HTML
 * escaping (the payload is printed with {!! !!}), and the empty states.
 */

beforeEach(function () {
    $this->actingAsUser();
});

function renderEventHistory(Post $post): string
{
    return view('filament-event-sourcing::event-history', EventHistoryAction::historyViewData($post))->render();
}

function newPost(string $title = 'Hello', string $body = 'Body'): Post
{
    $uuid = Str::uuid()->toString();
    PostAggregate::retrieve($uuid)->createPost($title, $body)->persist();

    return Post::find($uuid);
}

function appendStoredEvent(string $uuid, int $version, string $eventClass, array $properties): void
{
    DB::table('stored_events')->insert([
        'aggregate_uuid' => $uuid,
        'aggregate_version' => $version,
        'event_version' => 1,
        'event_class' => $eventClass,
        'event_properties' => json_encode($properties),
        'meta_data' => json_encode([]),
        'created_at' => now(),
    ]);
}

it('renders a timeline with one item per event', function () {
    $post = newPost('Original');
    PostAggregate::retrieve($post->uuid)->changeTitle('Renamed')->persist();

    $html = renderEventHistory($post);

    expect($html)->toContain('fes-timeline')
        ->and(substr_count($html, 'fi-fes-event-history-item'))->toBe(2);
});

it('marks created events with the created category', function () {
    $html = renderEventHistory(newPost());

    expect($html)->toContain('fes-event is-created');
});

it('marks changed events with the changed category', function () {
    $post = newPost();
    PostAggregate::retrieve($post->uuid)->changeTitle('Renamed')->persist();

    expect(renderEventHistory($post))->toContain('fes-event is-changed');
});

it('marks deleted events with the deleted category', function () {
    $post = newPost();
    appendStoredEvent($post->uuid, 2, \Albertoarena\FilamentEventSourcing\Tests\Fixtures\Events\PostDeleted::class, []);

    expect(renderEventHistory($post))->toContain('fes-event is-deleted');
});

it('marks failed events with the failed category', function () {
    $post = newPost();
    appendStoredEvent($post->uuid, 2, 'App\\Events\\PostUpdateFailed', ['reason' => 'nope']);

    expect(renderEventHistory($post))->toContain('fes-event is-failed');
});

it('falls back to the default category for unrecognised events', function () {
    $post = newPost();
    appendStoredEvent($post->uuid, 2, 'App\\Events\\SomethingHappened', ['x' => 1]);

    expect(renderEventHistory($post))->toContain('fes-event is-default');
});

it('highlights JSON keys, strings, numbers and booleans', function () {
    $post = newPost();
    appendStoredEvent($post->uuid, 2, 'App\\Events\\Mixed', [
        'name' => 'Ada',
        'age' => 36,
        'active' => true,
        'deleted_at' => null,
    ]);

    $html = renderEventHistory($post);

    expect($html)->toContain('fes-json-key')
        ->and($html)->toContain('fes-json-string')
        ->and($html)->toContain('fes-json-number')
        ->and($html)->toContain('fes-json-bool');
});

it('escapes HTML in the payload to prevent XSS', function () {
    $post = newPost();
    appendStoredEvent($post->uuid, 2, 'App\\Events\\Injected', [
        'title' => '<script>alert(1)</script>',
        'amp' => 'a & b',
    ]);

    $html = renderEventHistory($post);

    expect($html)->not->toContain('<script>alert(1)</script>')
        ->and($html)->toContain('&lt;script&gt;alert(1)&lt;/script&gt;')
        ->and($html)->toContain('a &amp; b');
});

it('renders an empty placeholder for an event with no properties', function () {
    $post = newPost();
    appendStoredEvent($post->uuid, 2, 'App\\Events\\Empty', []);

    expect(renderEventHistory($post))->toContain('fes-json-empty');
});

it('shows the version pill and timestamp for each event', function () {
    $html = renderEventHistory(newPost());

    expect($html)->toContain('fes-version')
        ->and($html)->toContain('v1')
        ->and($html)->toContain('fes-time');
});

it('renders the empty state when there are no events', function () {
    $html = view('filament-event-sourcing::event-history', [
        'events' => new Collection,
        'capped' => false,
        'cap' => EventHistoryAction::EVENT_CAP,
    ])->render();

    expect($html)->toContain('fes-empty')
        ->and($html)->toContain('No events recorded.')
        ->and($html)->not->toContain('class="fes-timeline"');
});

it('styles the capped notice with an icon', function () {
    $html = view('filament-event-sourcing::event-history', [
        'events' => new Collection,
        'capped' => true,
        'cap' => EventHistoryAction::EVENT_CAP,
    ])->render();

    expect($html)->toContain('fi-fes-event-history-notice')
        ->and($html)->toContain('<svg')
        ->and($html)->toContain('latest '.EventHistoryAction::EVENT_CAP);
});
