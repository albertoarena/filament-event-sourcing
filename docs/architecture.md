# Architecture & Functional Spec — filament-event-sourcing

This document is the single source of truth for WHAT the package does. `CLAUDE.md` defines HOW to
build it. If something is not specified here, it is out of scope for v1.

## 1. Plugin entry point

`FilamentEventSourcingPlugin` implements `Filament\Contracts\Plugin`.

```php
use Albertoarena\FilamentEventSourcing\FilamentEventSourcingPlugin;

$panel->plugin(
    FilamentEventSourcingPlugin::make()
        ->storedEventsResource()          // opt-in, default false
        ->replayPage()                    // opt-in, default false, ALSO requires config flag
);
```

Behaviour:
- `getId()` returns `filament-event-sourcing`.
- `register()` conditionally registers the Stored Events resource and the Replay page on the panel.
- Fluent setters accept `bool|Closure` (Filament convention: evaluate lazily).
- The plugin must be safe to register on multiple panels with different options.

## 2. Config (`config/filament-event-sourcing.php`)

```php
return [
    // Column on projection models holding the aggregate uuid. This is independent of the
    // model's primary key: the projection may use an `id` PK with a separate uuid column,
    // or a `uuid` PK that doubles as this column. The package only reads/writes this column.
    'aggregate_uuid_column' => 'uuid',

    'stored_events_resource' => [
        'navigation_group' => null,        // string|null
        'navigation_sort' => null,
        'per_page' => 25,
    ],

    'replay' => [
        // Master switch. The plugin option alone is NOT enough; this must also be true.
        // Defence in depth for a destructive-ish operation.
        'enabled' => false,
        // Gate/ability checked before showing or running replay. Null = panel access only.
        'authorize' => null,               // string ability name or null
    ],
];
```

## 3. `HasStoredEvents` trait (model concern)

Applied to projection models. Provides:

```php
public function storedEvents(): HasMany
// hasMany(EloquentStoredEvent resolved via Spatie's configured stored-event model,
//         foreignKey: 'aggregate_uuid',
//         localKey: config('filament-event-sourcing.aggregate_uuid_column'))
```

- Resolve the stored event model from Spatie's own config (`event-sourcing.stored_event_model`),
  never hardcode `EloquentStoredEvent`.
- A per-model override: if the model defines `public function getAggregateUuidColumn(): string`,
  use it over config.
- If the column does not exist on the model's table at query time, that is the user's problem;
  do NOT add schema checks.

This trait is the foundation: both the relation manager and the history action use it.

**Primary-key agnostic.** The relation keys on the aggregate-uuid column (`localKey`), never on
the model's primary key. The package works identically whether the projection uses an `id`
auto-increment PK with a separate uuid column, or a `uuid` PK that doubles as the aggregate-uuid
column. The write bridge (§6) likewise resolves projections by `where(aggregateUuidColumn, uuid)`
and reads the uuid via the configured column, never via the PK. Configuring the model's key type
(`$incrementing`, `$keyType`) and Filament's record routing (`getRouteKeyName()`) is the user's
responsibility; the package neither assumes nor sets them.

## 4. Stored Events browser (read-only resource)

A Filament v4 resource over Spatie's stored event model.

- **List page only + view modal/page.** No create, no edit, no delete. `canCreate(): false` etc.
- Table columns: `id`, `aggregate_uuid` (copyable), `aggregate_version`, event class
  (short class name via `class_basename`, full class in tooltip), `created_at`.
- Filters: event class (select, options = distinct event classes present), aggregate uuid
  (text filter, exact match), created_at date range.
- Default sort: `id` desc.
- View: infolist showing all columns plus `event_properties` and `meta_data` rendered as
  pretty-printed JSON (use a code/pre block; do not build a custom JSON tree viewer).
- Navigation label "Stored Events", icon a sensible heroicon, group/sort/per-page from config.
- Eloquent query MUST be read-only in spirit: no actions that mutate.

## 5. Per-record event history

Two delivery mechanisms, same data:

### 5a. `StoredEventsRelationManager`
- Standard Filament relation manager for the `storedEvents` relation from the trait.
- Read-only table, same columns/filters as §4 minus aggregate_uuid (it is constant here).
- User attaches it to their resource like any relation manager. Zero package magic.

### 5b. `EventHistoryAction`
- A `Filament\Actions\Action` subclass, name `eventHistory`, default label "History",
  default icon clock-ish heroicon.
- Usable as a table row action or page header action on records whose model uses
  `HasStoredEvents` (throw `MissingAggregateUuidException` with guidance if the relation
  is absent).
- Opens a slide-over/modal with a simple chronological list: event short name, version,
  created_at, expandable JSON payload. Implement with an infolist or a simple Blade view
  inside the modal; pick the simpler one that tests cleanly.
- No pagination inside the modal for v1; cap at the latest 100 events with a notice if
  more exist.

## 6. Write bridge

The core idea: Filament pages keep their full lifecycle (form validation, mutate hooks,
notifications, redirects) but the persistence step goes through the user's aggregate.
The user implements ONE method per operation. The package handles uuid generation,
projection resolution and error reporting.

### 6a. `CreatesEventSourcedRecord` (trait for `CreateRecord` pages)

```php
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

Trait behaviour (overrides `handleRecordCreation(array $data): Model`):
1. Generate uuid via `Str::uuid()->toString()`. Overridable hook
   `protected function newAggregateUuid(): string` for users who derive uuids differently.
2. Call abstract `handleAggregateCreation(string $uuid, array $data): void`.
3. Resolve the projection: `static::getModel()::query()->where($uuidColumn, $uuid)->first()`.
4. If null → throw `ProjectionNotFoundException` (message must mention queued projectors,
   see CLAUDE.md). No retry loops, no sleeps. Sync projectors are a documented requirement
   for managed resources.
5. Return the projection model so Filament's redirect/notification flow works untouched.

### 6b. `EditsEventSourcedRecord` (trait for `EditRecord` pages)

```php
protected function handleAggregateUpdate(Model $record, array $data): void
{
    PostAggregate::retrieve($record->uuid)
        ->changeTitle($data['title'])
        ->persist();
}
```

Trait overrides `handleRecordUpdate(Model $record, array $data): Model`:
1. Read the uuid from the record via the configured column (throw
   `MissingAggregateUuidException` if null/absent).
2. Call abstract `handleAggregateUpdate(Model $record, array $data): void`.
3. `return $record->refresh();`

### 6c. `EventSourcedDeleteAction`

A configured `DeleteAction` subclass. Required `->using()` closure receives the record;
the user calls their aggregate's delete method inside it:

```php
EventSourcedDeleteAction::make()
    ->using(fn (Post $record) => PostAggregate::retrieve($record->uuid)->deletePost()->persist());
```

- If `using()` was not set, throw a `LogicException` at call time with a message showing
  the exact snippet above.
- After the closure runs, do NOT call `$record->delete()`; the projector owns the projection's
  lifecycle. Verify in the test that the projector's deletion is what removes the row.
- Keep DeleteAction's confirmation modal, notification and table refresh behaviour intact.

### 6d. Explicit non-goals of the write bridge (document in README)

- No automatic form-to-command mapping. Ever.
- No support for queued projectors on managed resources in v1.
- No soft-delete handling beyond what the user's projector does.
- Bulk actions: out of scope for v1; users can compose `EventSourcedDeleteAction` semantics
  themselves. Note in docs/ideas.md.

## 7. Replay page (config-gated)

`ReplayProjectors` — a simple Filament panel page.

- Visible ONLY when `config('filament-event-sourcing.replay.enabled') === true`
  AND the plugin enabled it AND the configured ability (if any) passes for the user.
  All three checks in `canAccess()`; also re-checked server-side before executing replay
  (never trust the UI).
- Lists projectors registered with Spatie's `Projectionist` (class name, short name).
- Per-projector "Replay" action with a confirmation modal that states clearly:
  this runs synchronously in the request, may take long on large event stores,
  and the CLI (`php artisan event-sourcing:replay`) is preferred for production.
- Execution: call the Projectionist replay for the selected projector directly
  (read Spatie's replay command source and reuse the underlying API, not `Artisan::call`,
  so we can report the number of events replayed in the success notification).
- No "replay all" button in v1. One projector at a time.

## 8. Exceptions

- `ProjectionNotFoundException extends RuntimeException` — static constructor
  `::for(string $modelClass, string $uuid)` building the standard message.
- `MissingAggregateUuidException extends RuntimeException` — static constructor
  `::for(Model $record)`.

## 9. Test fixtures (the `Post` domain)

Minimal Spatie ES domain used by the whole suite, in `tests/Fixtures`:

- `PostAggregate` (createPost, changeTitle, deletePost)
- Events: `PostCreated`, `PostTitleChanged`, `PostDeleted` (ShouldBeStored)
- `PostProjector` (sync) maintaining the `Post` projection model (uuid pk, title, body)
- Migrations for `posts` + Spatie's `stored_events` (publishable from the vendor)
- A fixture panel (`TestPanelProvider`) registering the plugin with everything enabled
- A fixture `PostResource` with Create/Edit pages using the write-bridge traits,
  the relation manager, the history action and the delete action

This doubles as living documentation: README examples must match the fixtures.

## 10. README structure (write in Phase 7)

1. One-paragraph what/why, badges (tests, packagist version, downloads)
2. The screenshot-worthy feature first: event history on a record
3. Installation (composer require + plugin registration)
4. Write bridge: full Post example, the sync-projector requirement in a warning box
5. Audit tooling: stored events resource, relation manager, history action
6. Replay page with the safety caveats
7. Config reference
8. Testing, changelog, contributing, license (MIT)
9. A "Related packages" section linking laravel-event-sourcing-generator and the
   claude-laravel-event-sourcing skill
