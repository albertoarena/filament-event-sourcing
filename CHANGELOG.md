# Changelog

All notable changes to `filament-event-sourcing` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Package skeleton: service provider, configuration file and test harness.
- `HasStoredEvents` model concern exposing a `storedEvents()` relation.
- `FilamentEventSourcingPlugin` with an opt-in `storedEventsResource()` option.
- Read-only Stored Events browser resource (list and view) over the configured stored-event model.
