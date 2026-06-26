# Changelog

All notable changes to `filament-event-sourcing` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.2.0] - 2026-06-26

### Changed

- Restyle the event-history slide-over as a timeline with per-event badges, version pills,
  timestamps and server-side syntax-highlighted JSON. Self-contained and theme-aware
  (light/dark) with no build step required in the host panel.
- Modernise the Stored Events resource: payloads and metadata render as syntax-highlighted JSON,
  and the event class shows as a colour-coded badge in both the list and view pages.
- Share a single `filament-event-sourcing::partials.json-payload` view between the event-history
  slide-over and the Stored Events resource for consistent, highlighted JSON.

## [0.1.0] - 2026-06-17

### Added

- Package skeleton: service provider, configuration file and test harness.
- `HasStoredEvents` model concern exposing a `storedEvents()` relation.
- `FilamentEventSourcingPlugin` with an opt-in `storedEventsResource()` option.
- Read-only Stored Events browser resource (list and view) over the configured stored-event model.
- `StoredEventsRelationManager` for per-record event history on a resource.
- `EventHistoryAction` slide-over listing the latest events chronologically, capped at 100.
- `MissingAggregateUuidException` thrown when a record has no aggregate uuid or trait.
- Write bridge: `CreatesEventSourcedRecord` and `EditsEventSourcedRecord` page traits and
  `EventSourcedDeleteAction`, routing Filament create, edit and delete through aggregates.
- `ProjectionNotFoundException` thrown when a projection is missing after an aggregate persist.
- Config-gated `ReplayProjectors` page (opt-in via the plugin's `replayPage()` option) that
  replays a projector through the Projectionist and reports the number of events replayed.
