<?php

declare(strict_types=1);

use Albertoarena\FilamentEventSourcing\Resources\StoredEvents\Pages\ListStoredEvents;
use Albertoarena\FilamentEventSourcing\Resources\StoredEvents\Pages\ViewStoredEvent;
use Albertoarena\FilamentEventSourcing\Resources\StoredEvents\StoredEventResource;
use Albertoarena\FilamentEventSourcing\Tests\Fixtures\Events\PostCreated;
use Albertoarena\FilamentEventSourcing\Tests\Fixtures\Events\PostTitleChanged;
use Albertoarena\FilamentEventSourcing\Tests\Fixtures\PostAggregate;
use Filament\Facades\Filament;
use Illuminate\Support\Str;

use function Pest\Livewire\livewire;

function storedEventModel(): string
{
    return config('event-sourcing.stored_event_model');
}

beforeEach(function () {
    $this->actingAsUser();

    Filament::setCurrentPanel('admin');
});

it('lists seeded stored events', function () {
    $uuid = Str::uuid()->toString();
    PostAggregate::retrieve($uuid)->createPost('Hello', 'Body')->persist();
    PostAggregate::retrieve($uuid)->changeTitle('Updated')->persist();

    livewire(ListStoredEvents::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords(storedEventModel()::all());
});

it('filters by event class', function () {
    $uuid = Str::uuid()->toString();
    PostAggregate::retrieve($uuid)->createPost('Hello', 'Body')->persist();
    PostAggregate::retrieve($uuid)->changeTitle('Updated')->persist();

    $created = storedEventModel()::where('event_class', PostCreated::class)->get();
    $changed = storedEventModel()::where('event_class', PostTitleChanged::class)->get();

    livewire(ListStoredEvents::class)
        ->filterTable('event_class', PostCreated::class)
        ->assertCanSeeTableRecords($created)
        ->assertCanNotSeeTableRecords($changed);
});

it('filters by aggregate uuid', function () {
    $first = Str::uuid()->toString();
    $second = Str::uuid()->toString();
    PostAggregate::retrieve($first)->createPost('First', 'Body')->persist();
    PostAggregate::retrieve($second)->createPost('Second', 'Body')->persist();

    livewire(ListStoredEvents::class)
        ->filterTable('aggregate_uuid', ['aggregate_uuid' => $first])
        ->assertCanSeeTableRecords(storedEventModel()::where('aggregate_uuid', $first)->get())
        ->assertCanNotSeeTableRecords(storedEventModel()::where('aggregate_uuid', $second)->get());
});

it('filters by a created_at date range', function () {
    $uuid = Str::uuid()->toString();
    PostAggregate::retrieve($uuid)->createPost('Hello', 'Body')->persist();

    livewire(ListStoredEvents::class)
        ->filterTable('created_at', [
            'created_from' => now()->subDay()->toDateString(),
            'created_until' => now()->addDay()->toDateString(),
        ])
        ->assertCanSeeTableRecords(storedEventModel()::all());
});

it('does not allow creating, editing or deleting', function () {
    $record = new (storedEventModel());

    expect(StoredEventResource::canCreate())->toBeFalse()
        ->and(StoredEventResource::canEdit($record))->toBeFalse()
        ->and(StoredEventResource::canDelete($record))->toBeFalse()
        ->and(StoredEventResource::canDeleteAny())->toBeFalse()
        ->and(StoredEventResource::getPages())->not->toHaveKey('create')
        ->and(StoredEventResource::getPages())->not->toHaveKey('edit');
});

it('shows the pretty printed payload on the view page', function () {
    $uuid = Str::uuid()->toString();
    PostAggregate::retrieve($uuid)->createPost('Hello', 'Body')->persist();

    $record = storedEventModel()::first();

    livewire(ViewStoredEvent::class, ['record' => $record->getKey()])
        ->assertSuccessful()
        ->assertSee('Hello');
});

it('renders the payload as highlighted JSON on the view page', function () {
    $uuid = Str::uuid()->toString();
    PostAggregate::retrieve($uuid)->createPost('Hello', 'Body')->persist();

    $record = storedEventModel()::first();

    livewire(ViewStoredEvent::class, ['record' => $record->getKey()])
        ->assertSuccessful()
        ->assertSee('fes-json-key', false)
        ->assertSee('fes-json-string', false)
        ->assertSee('PostCreated');
});

it('registers the resource only when the plugin option is enabled', function () {
    expect(Filament::getPanel('admin')->getResources())
        ->toContain(StoredEventResource::class)
        ->and(Filament::getPanel('minimal')->getResources())
        ->not->toContain(StoredEventResource::class);
});
