# Filament Event Sourcing

Integrate [spatie/laravel-event-sourcing](https://spatie.be/docs/laravel-event-sourcing/v7/introduction)
with [Filament](https://filamentphp.com) admin panels.

Filament assumes Eloquent CRUD. Event sourcing routes writes through aggregates and reads through
projections. This package bridges the two without hiding either side:

- **Write bridge** - traits and an action that let Filament Create, Edit and Delete pages persist
  through your aggregates instead of writing to Eloquent directly.
- **Audit tooling** - a read-only Stored Events browser resource, a per-record event history
  (relation manager and modal action), backed by a `HasStoredEvents` model trait.
- **Projector replay** - a config-gated admin page to replay projectors. Disabled by default.

This package does not generate aggregates, events or projectors, and it does not map form state to
domain commands. You always write the aggregate call; the package provides the plumbing.

## Requirements

- PHP `^8.2`
- Laravel `^11.0 | ^12.0`
- Filament `^4.0`
- spatie/laravel-event-sourcing `^7.0`

## Installation

```bash
composer require albertoarena/filament-event-sourcing
```

Register the plugin on your panel:

```php
use Albertoarena\FilamentEventSourcing\FilamentEventSourcingPlugin;

$panel->plugin(
    FilamentEventSourcingPlugin::make()
        ->storedEventsResource()   // opt-in, default off
        ->replayPage()             // opt-in, default off, also requires a config flag
);
```

Publish the config if you need to change defaults:

```bash
php artisan vendor:publish --tag="filament-event-sourcing-config"
```

## Status

Early development. The full documentation (write bridge, audit tooling, replay page and config
reference) will land with the first release. See `docs/architecture.md` for the functional spec.

## Testing

```bash
composer test
```

## License

The MIT License (MIT). See [LICENSE.md](LICENSE.md).
