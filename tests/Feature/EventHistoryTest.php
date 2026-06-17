<?php

declare(strict_types=1);

use Albertoarena\FilamentEventSourcing\Actions\EventHistoryAction;
use Albertoarena\FilamentEventSourcing\Exceptions\MissingAggregateUuidException;
use Albertoarena\FilamentEventSourcing\RelationManagers\StoredEventsRelationManager;
use Albertoarena\FilamentEventSourcing\Tests\Fixtures\Article;
use Albertoarena\FilamentEventSourcing\Tests\Fixtures\Events\PostTitleChanged;
use Albertoarena\FilamentEventSourcing\Tests\Fixtures\Filament\Pages\ViewPost;
use Albertoarena\FilamentEventSourcing\Tests\Fixtures\Post;
use Albertoarena\FilamentEventSourcing\Tests\Fixtures\PostAggregate;
use Albertoarena\FilamentEventSourcing\Tests\Fixtures\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAsUser();
    Filament::setCurrentPanel('admin');
});

function makePost(string $title = 'Hello', string $body = 'Body'): Post
{
    $uuid = Str::uuid()->toString();
    PostAggregate::retrieve($uuid)->createPost($title, $body)->persist();

    return Post::find($uuid);
}

it('lists only the record own events in the relation manager', function () {
    $post = makePost('First');
    PostAggregate::retrieve($post->uuid)->changeTitle('Renamed')->persist();

    $other = makePost('Second');

    livewire(StoredEventsRelationManager::class, [
        'ownerRecord' => $post,
        'pageClass' => ViewPost::class,
    ])
        ->assertCanSeeTableRecords($post->storedEvents()->get())
        ->assertCanNotSeeTableRecords($other->storedEvents()->get());
});

it('mounts the history action on the page', function () {
    $post = makePost('Original');

    livewire(ViewPost::class, ['record' => $post->uuid])
        ->mountAction('eventHistory')
        ->assertActionMounted('eventHistory');
});

it('renders the events chronologically in the history modal', function () {
    $post = makePost('Original');
    PostAggregate::retrieve($post->uuid)->changeTitle('Updated')->persist();

    $html = view('filament-event-sourcing::event-history', EventHistoryAction::historyViewData($post))->render();

    expect($html)->toContain('PostCreated')
        ->and($html)->toContain('PostTitleChanged')
        ->and(strpos($html, 'PostCreated'))->toBeLessThan(strpos($html, 'PostTitleChanged'));
});

it('throws when the model does not use the trait', function () {
    EventHistoryAction::aggregateStoredEvents(new User);
})->throws(MissingAggregateUuidException::class);

it('throws when the aggregate uuid is missing', function () {
    EventHistoryAction::aggregateStoredEvents(new Article);
})->throws(MissingAggregateUuidException::class);

it('shows a notice when more than the cap of events exist', function () {
    $post = makePost('Capped');

    $rows = [];
    for ($version = 2; $version <= 101; $version++) {
        $rows[] = [
            'aggregate_uuid' => $post->uuid,
            'aggregate_version' => $version,
            'event_version' => 1,
            'event_class' => PostTitleChanged::class,
            'event_properties' => json_encode(['title' => "Title {$version}"]),
            'meta_data' => json_encode([]),
            'created_at' => now(),
        ];
    }
    DB::table('stored_events')->insert($rows);

    $data = EventHistoryAction::historyViewData($post);
    $html = view('filament-event-sourcing::event-history', $data)->render();

    expect($data['capped'])->toBeTrue()
        ->and($data['events'])->toHaveCount(EventHistoryAction::EVENT_CAP)
        ->and($html)->toContain('latest '.EventHistoryAction::EVENT_CAP);
});
