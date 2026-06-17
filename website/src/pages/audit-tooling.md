---
layout: ../layouts/Layout.astro
title: Audit tooling
description: Browse stored events and show per-record event history.
---

# Audit tooling

## Stored Events browser

Enable `storedEventsResource()` on the plugin to add a read-only resource over Spatie's stored event model. It lists every stored event with its aggregate uuid, version, event class and timestamp, filters by event class, aggregate uuid and date range, and shows the full event payload as pretty-printed JSON. It never creates, edits or deletes events.

## Per-record event history

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

Then expose the history in either of two ways.

As a relation manager on a resource:

```php
use Albertoarena\FilamentEventSourcing\RelationManagers\StoredEventsRelationManager;

public static function getRelations(): array
{
    return [StoredEventsRelationManager::class];
}
```

As a table row action or page header action:

```php
use Albertoarena\FilamentEventSourcing\Actions\EventHistoryAction;

EventHistoryAction::make();
```

The history modal shows the latest 100 events with a notice when more exist.
