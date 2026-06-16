# Implementation Phases — filament-event-sourcing

Execute strictly in order. Each phase ends with: green suite, Larastan clean, Pint clean,
checkbox ticked, commit. Do not start a phase until the previous one is fully done.
Every phase starts by writing the tests listed under "Tests first".

## Phase 0 — Skeleton & toolchain

- [x] `composer.json`: name `albertoarena/filament-event-sourcing`, MIT, PSR-4 autoload
      (`Albertoarena\\FilamentEventSourcing\\` → `src/`, tests namespace → `tests/`),
      requires per CLAUDE.md, scripts (`test`, `test-coverage`, `analyse`, `format`)
- [x] Service provider via `spatie/laravel-package-tools`: name, config file
- [x] `config/filament-event-sourcing.php` per spec §2
- [x] `tests/TestCase.php`: testbench, register Spatie ES provider + package provider,
      sqlite :memory:, run Spatie's stored_events migration
- [x] Pest installed and bootstrapped (`tests/Pest.php` binding TestCase)
- [x] `phpstan.neon` (larastan, level 6, paths: src), `pint.json` (laravel preset)
- [x] GitHub Actions: `tests.yml` matrix PHP 8.2/8.3/8.4 × Laravel 11/12 × Filament 4
      (exclude impossible combos), `static.yml` for phpstan + pint
- [x] `.gitignore`, `LICENSE.md`, `CHANGELOG.md` (Keep a Changelog format), stub `README.md`

**Tests first:** `it('loads the config file')`, `it('boots the service provider')`.

## Phase 1 — Fixture domain

- [x] `Post` projection model (uuid pk), migration
- [x] `PostCreated` / `PostTitleChanged` / `PostDeleted` events
- [x] `PostAggregate` with `createPost`, `changeTitle`, `deletePost`
- [x] `PostProjector` (synchronous), registered in TestCase via Projectionist
- [x] Fixture panel provider + login-less auth setup suitable for Livewire tests
      (follow Filament's own plugin-testing approach; read vendor tests if unsure)

**Tests first:** `it('projects a created post')`, `it('projects a title change')`,
`it('removes the projection when the post is deleted')`. These prove the fixture domain
works before any package code depends on it.

## Phase 2 — HasStoredEvents trait

- [ ] Trait per spec §3, stored-event model resolved from Spatie config
- [ ] `getAggregateUuidColumn()` model override support

**Tests first:** relation returns the aggregate's events in order; respects a custom uuid
column via config; respects the per-model override; uses a custom stored-event model when
Spatie config points to one.

## Phase 3 — Stored Events browser resource

- [ ] Resource + List page + View (spec §4), registered conditionally by the plugin
- [ ] Plugin class with `storedEventsResource()` fluent option
- [ ] Config-driven navigation group/sort/per-page

**Tests first (pest-plugin-livewire):** list renders and shows seeded events; filters by
event class and aggregate uuid; resource absent from the panel when the plugin option is off;
create/edit/delete are not available; view shows pretty-printed payload.

## Phase 4 — Event history

- [ ] `StoredEventsRelationManager` (spec §5a)
- [ ] `EventHistoryAction` (spec §5b) incl. 100-event cap notice
- [ ] `MissingAggregateUuidException`

**Tests first:** relation manager lists only the record's events; history action modal
renders events chronologically; action on a model without the trait/uuid throws the
exception; cap notice appears at 101 events.

## Phase 5 — Write bridge

- [ ] `CreatesEventSourcedRecord` (spec §6a) + `ProjectionNotFoundException`
- [ ] `EditsEventSourcedRecord` (spec §6b)
- [ ] `EventSourcedDeleteAction` (spec §6c)
- [ ] Fixture `PostResource` pages wired with all three

**Tests first (through Livewire page tests on the fixture resource):**
- creating via the form stores a `PostCreated` event AND the projection exists AND
  Filament redirects normally
- the projection returned is the one the projector built (assert a projector-set attribute)
- a deliberately broken fixture (projector unregistered for one test) makes creation throw
  `ProjectionNotFoundException` with the queued-projector hint in the message
- editing stores `PostTitleChanged` and the form shows refreshed data
- `newAggregateUuid()` override is honoured
- delete action stores `PostDeleted`, the row disappears, and `$record->delete()` was NOT
  called directly (assert deletion happened via the projector, e.g. spy on the model event
  or use a projector flag)
- delete action without `using()` throws the LogicException with the snippet

## Phase 6 — Replay page

- [ ] `ReplayProjectors` page per spec §7, triple-gated visibility
- [ ] Replay execution through Projectionist with event-count notification

**Tests first:** page hidden when config flag false even if plugin option true; hidden when
ability fails; visible when all gates pass; replaying rebuilds a truncated projection table;
server-side execution refuses when config flag is off (direct Livewire call attempt).

## Phase 7 — Polish & release prep

- [ ] README per spec §10 (natural prose, no em dashes, runnable examples matching fixtures)
- [ ] Class-level docblocks on all public classes
- [ ] Coverage ≥ 90% overall; fill genuine gaps, do not write assertion-free tests
- [ ] `docs/ideas.md` listing deferred items (bulk actions, queued-projector support,
      snapshot browser, aggregate state inspector, replay-all)
- [ ] CHANGELOG 0.1.0 entry, `.github/ISSUE_TEMPLATE`, `CONTRIBUTING.md`, `SECURITY.md`
- [ ] Final pass: `composer test-coverage && composer analyse && composer format`

## Phase 8 — Documentation website (Astro)

Build only after Phase 7, so the docs describe real shipped behaviour.

- [ ] Scaffold an Astro project under `website/` (own `package.json`; `node_modules` and build
      output gitignored)
- [ ] Pages: home (what/why), installation, write bridge, audit tooling, replay page, config
      reference - mirroring the README structure (spec §10) without lifting from any other project
- [ ] Code samples consistent with the `Post` fixtures; natural prose, no em dashes, no emoji in
      headings
- [ ] Static build verified (`npm run build`) and a `.github/workflows/deploy-website.yml`
      that builds `website/` and deploys to GitHub Pages (push to default branch, path-filtered
      to `website/**`, plus `workflow_dispatch`)
- [ ] Link the website from the README and `composer.json` homepage

**Note:** no Pest tests here; "done" means the site builds cleanly and content matches the
shipped package. Honour Hard Rule 6 (do not reference other projects).

## Release checklist (manual, for the maintainer)

- [ ] Push, verify CI matrix green
- [ ] Tag `v0.1.0`
- [ ] Submit to Packagist
- [ ] Submit to filamentphp.com plugin directory (requires repo topics + banner image)
