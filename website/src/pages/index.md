---
layout: ../layouts/Layout.astro
title: Overview
description: Integrate spatie/laravel-event-sourcing with Filament admin panels.
---

# Filament Event Sourcing

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

## Where to next

- [Installation](installation) covers the composer require and plugin registration.
- [Write bridge](write-bridge) routes Filament create, edit and delete through aggregates.
- [Audit tooling](audit-tooling) covers the Stored Events browser and per-record history.
- [Replay page](replay-page) covers the config-gated projector replay.
- [Configuration](configuration) is the full config reference.

## Related packages

- [albertoarena/laravel-event-sourcing-generator](https://github.com/albertoarena/laravel-event-sourcing-generator) generates aggregates, events, projectors and reactors for bounded contexts.
- The `laravel-spatie-event-sourcing` Claude Code skill helps design event-sourced domains and scaffold code.
