# Filament Event Sourcing

[![Latest Version on Packagist](https://img.shields.io/packagist/v/albertoarena/filament-event-sourcing.svg)](https://packagist.org/packages/albertoarena/filament-event-sourcing)
[![Tests](https://github.com/albertoarena/filament-event-sourcing/actions/workflows/tests.yml/badge.svg)](https://github.com/albertoarena/filament-event-sourcing/actions/workflows/tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/albertoarena/filament-event-sourcing.svg)](https://packagist.org/packages/albertoarena/filament-event-sourcing)

Integrate [spatie/laravel-event-sourcing](https://spatie.be/docs/laravel-event-sourcing/v7/introduction) with [Filament](https://filamentphp.com) admin panels.

Filament assumes Eloquent CRUD. Event sourcing routes writes through aggregates and reads through projections. This package bridges the two without hiding either side. It lets Filament resource pages persist through your aggregates, gives you a read-only audit trail of stored events, and adds a config-gated page to replay projectors. You always write the aggregate call; the package provides the plumbing.

This package does not generate aggregates, events or projectors, and it does not map form state to domain commands. Those remain your decisions.

## Event history at a glance

Add the `HasStoredEvents` trait to a projection model and you can drop a full, read-only event history onto any record, either as a relation manager or as a slide-over action:

```php
use Albertoarena\FilamentEventSourcing\Actions\EventHistoryAction;

EventHistoryAction::make(); // a "History" action for a row or page header
```

The action lists the aggregate's events in order, with the version, timestamp and an expandable JSON payload for each event.

## Requirements

- PHP `^8.2`
- Laravel `^11.0 | ^12.0`
- Filament `^4.0`
- spatie/laravel-event-sourcing `^7.0`

## Installation

```bash
composer require albertoarena/filament-event-sourcing
```

Register the plugin on your panel and opt in to the features you want:

```php
use Albertoarena\FilamentEventSourcing\FilamentEventSourcingPlugin;

$panel->plugin(
    FilamentEventSourcingPlugin::make()
        ->storedEventsResource() // the read-only Stored Events browser, off by default
        ->replayPage()           // the projector replay page, off by default and also config gated
);
```

Publish the config if you need to change the defaults:

```bash
php artisan vendor:publish --tag="filament-event-sourcing-config"
```

## Write bridge

The write bridge keeps Filament's full page lifecycle (form validation, mutate hooks, notifications and redirects) and replaces only the persistence step. You implement one method per operation; the package generates the uuid, resolves the resulting projection and reports errors.

The examples below use a `Post` aggregate with `createPost`, `changeTitle` and `deletePost` methods, recording `PostCreated`, `PostTitleChanged` and `PostDeleted` events, plus a synchronous `PostProjector` that maintains a `Post` projection model.

> [!WARNING]
> Projectors must run synchronously for resources managed by Filament. The write bridge reads the projection back immediately after the aggregate persists, so a queued projector will not have created it yet and a `ProjectionNotFoundException` is thrown. Keep the projectors behind managed resources synchronous, or run the queue worker before the read.

### Creating

```php
use Albertoarena\FilamentEventSourcing\Concerns\CreatesEventSourcedRecord;
use Filament\Resources\Pages\CreateRecord;

class CreatePost extends CreateRecord
{
    use CreatesEventSourcedRecord;

    protected static string $resource = PostResource::class;

    protected function handleAggregateCreation(string $uuid, array $data): void
    {
        PostAggregate::retrieve($uuid)
            ->createPost($data['title'], $data['body'])
            ->persist();
    }
}
```

The trait generates the uuid with `Str::uuid()`. Override `newAggregateUuid()` if you derive uuids differently.

### Editing

```php
use Albertoarena\FilamentEventSourcing\Concerns\EditsEventSourcedRecord;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditPost extends EditRecord
{
    use EditsEventSourcedRecord;

    protected static string $resource = PostResource::class;

    protected function handleAggregateUpdate(Model $record, array $data): void
    {
        PostAggregate::retrieve($record->uuid)
            ->changeTitle($data['title'])
            ->persist();
    }
}
```

### Deleting

`EventSourcedDeleteAction` keeps the confirmation modal, notification and table refresh of Filament's `DeleteAction`, but never calls `$record->delete()` itself. Your projector owns the projection's lifecycle. Provide a `->using()` closure that calls your aggregate:

```php
use Albertoarena\FilamentEventSourcing\Actions\EventSourcedDeleteAction;

EventSourcedDeleteAction::make()
    ->using(fn (Post $record) => PostAggregate::retrieve($record->uuid)->deletePost()->persist());
```

If you forget the `->using()` closure, the action throws a `LogicException` showing the snippet above.

## Audit tooling

### Stored Events browser

Enable `storedEventsResource()` on the plugin to add a read-only resource over Spatie's stored event model. It lists every stored event with its aggregate uuid, version, event class and timestamp, filters by event class, aggregate uuid and date range, and shows the full event payload as pretty-printed JSON. It never creates, edits or deletes events.

### Per-record event history

Add the trait to your projection model:

```php
use Albertoarena\FilamentEventSourcing\Concerns\HasStoredEvents;
use Spatie\EventSourcing\Projections\Projection;

class Post extends Projection
{
    use HasStoredEvents;
}
```

The trait is primary-key agnostic. It links the aggregate uuid column (the `aggregate_uuid_column` config value, or a `getAggregateUuidColumn()` method on the model) to the stored events, so it works whether your projection uses a `uuid` primary key or an `id` primary key with a separate uuid column.

Then expose the history in either of two ways:

```php
use Albertoarena\FilamentEventSourcing\RelationManagers\StoredEventsRelationManager;

// On a resource:
public static function getRelations(): array
{
    return [StoredEventsRelationManager::class];
}
```

```php
use Albertoarena\FilamentEventSourcing\Actions\EventHistoryAction;

// As a table row action or page header action:
EventHistoryAction::make();
```

The history modal shows the latest 100 events with a notice when more exist.

## Replay page

Enable `replayPage()` on the plugin to add a page that replays projectors one at a time. It is off by default and gated three ways: the plugin option, the `replay.enabled` config flag and an optional authorization ability. All three are checked again on the server before a replay runs.

> [!WARNING]
> A replay runs synchronously inside the web request and can take a long time on large event stores. For production, prefer the CLI: `php artisan event-sourcing:replay`.

## Configuration

```php
return [
    // Column on projection models holding the aggregate uuid. Independent of the primary key.
    'aggregate_uuid_column' => 'uuid',

    'stored_events_resource' => [
        'navigation_group' => null,
        'navigation_sort' => null,
        'per_page' => 25,
    ],

    'replay' => [
        // Master switch. The plugin option alone is not enough; this must also be true.
        'enabled' => false,
        // Gate ability checked before showing or running a replay. Null means panel access only.
        'authorize' => null,
    ],
];
```

## Testing

```bash
composer test
```

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for recent changes.

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md).

## Security

See [SECURITY.md](SECURITY.md) for reporting vulnerabilities.

## Related packages

- [albertoarena/laravel-event-sourcing-generator](https://github.com/albertoarena/laravel-event-sourcing-generator) generates aggregates, events, projectors and reactors for bounded contexts.
- The `laravel-spatie-event-sourcing` Claude Code skill helps design event-sourced domains and scaffold code.

## License

The MIT License (MIT). See [LICENSE.md](LICENSE.md).
