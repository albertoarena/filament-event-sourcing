# CLAUDE.md — filament-event-sourcing

AI agent instructions for building `albertoarena/filament-event-sourcing`, a public Composer package
that integrates [spatie/laravel-event-sourcing v7](https://spatie.be/docs/laravel-event-sourcing/v7/introduction)
with [Filament](https://filamentphp.com) admin panels.

Read this file fully before writing any code. The functional spec lives in `docs/architecture.md`.
The build order lives in `docs/implementation-phases.md`. Follow the phases in order. Do not skip ahead.

## What this package is

Filament assumes Eloquent CRUD. Event sourcing routes writes through aggregates and reads through
projections. This package bridges the two:

1. **Write bridge** — traits and an action that let Filament resource pages create, update and delete
   records through aggregates instead of direct Eloquent writes.
2. **Audit tooling** — a read-only Stored Events browser resource, a per-record event history
   (relation manager + modal action), powered by a `HasStoredEvents` model trait.
3. **Projector replay** — a config-gated admin page to replay projectors. Disabled by default.

What this package is NOT: it does not auto-generate aggregates, events or projectors
(that is `albertoarena/laravel-event-sourcing-generator`'s job), and it does not magically map
form state to domain commands. The user always writes the aggregate call; we provide the plumbing.

## Hard rules

1. **TDD, no exceptions.** Write the failing Pest test first, make it pass, refactor. Never write
   production code without a failing test that requires it. One logical change per commit, committed
   only when the suite is green.
2. **Verify Filament APIs against vendor source, not memory.** Filament v4 changed namespaces and
   signatures substantially (Schemas, unified `Filament\Actions`, enum-typed navigation properties).
   Before using any Filament class, open it in `vendor/filament/` and confirm the real signature.
   Same for `vendor/spatie/laravel-event-sourcing/`. If a test fails on a Filament API, read the
   source first; do not guess variations.
3. **KISS.** No abstraction until a second concrete use exists. No interfaces with one implementation
   unless the spec requires it. No options nobody asked for. When in doubt, leave it out and note it
   in `docs/ideas.md` for v2.
4. **Public API is sacred.** Everything in `src/` that is not `@internal` is a published contract.
   Name things carefully once; renaming after release is a breaking change.
5. **No fallbacks that hide errors.** If a projection is missing after an aggregate persist, throw
   the dedicated exception with an actionable message (see spec). Never silently create or skip.
6. **Do not reference other projects.** Never name, link to, or copy from other repositories or
   packages (the maintainer's own or third-party) in code, comments, docs, README, the docs website,
   or commit messages. The only allowed external references are the dependencies this package
   actually requires (Filament, spatie/laravel-event-sourcing), the two "Related packages" links
   the spec explicitly calls for in the README (§10), the GitHub traffic badge below, and the demo
   app `albertoarena/filament-event-sourcing-demo` (a public, working integration of this package,
   linked from the README and the docs website).

## README badges

Add a GitHub traffic badge to the README, generated with the maintainer's
`github-traffic-badge` package: https://github.com/albertoarena/github-traffic-badge. This is an
explicit exception to Hard Rule 6.

## Tech stack and constraints

- PHP `^8.2`, Laravel `^11.0|^12.0`
- `filament/filament: ^4.0` — pinned to v4. v5 adds only Livewire v4 support (no functional
  changes), so v5 can be added to the constraint later once it is GA. Write against v4 APIs.
- `spatie/laravel-event-sourcing: ^7.0` (require, not suggest — it is the point of the package)
- `spatie/laravel-package-tools` for the service provider
- Dev: `orchestra/testbench`, `pestphp/pest` + `pest-plugin-laravel` + `pest-plugin-livewire`,
  `larastan/larastan` (level 6 minimum), `laravel/pint` (Laravel preset)
- Database in tests: sqlite `:memory:`
- Package namespace: `Albertoarena\FilamentEventSourcing`
- Config file: `config/filament-event-sourcing.php`
- Primary-key agnostic on projections. The package never assumes the PK is the aggregate uuid;
  it depends only on an aggregate-uuid column (configurable name, default `uuid`). Projections
  may use an `id` auto-increment PK with a separate uuid column, or a `uuid` PK that doubles as
  the aggregate-uuid column. Configuring the PK (`$incrementing`, `$keyType`, `getRouteKeyName()`)
  is the user's model concern; the package only reads/writes the aggregate-uuid column.

## Project layout

```
src/
  FilamentEventSourcingPlugin.php        # Filament plugin entry point
  FilamentEventSourcingServiceProvider.php
  Concerns/
    HasStoredEvents.php                  # model trait: storedEvents() relation
    CreatesEventSourcedRecord.php        # CreateRecord page trait
    EditsEventSourcedRecord.php          # EditRecord page trait
  Actions/
    EventSourcedDeleteAction.php
    EventHistoryAction.php
  Resources/
    StoredEvents/                        # read-only browser resource (v4 structure)
  RelationManagers/
    StoredEventsRelationManager.php
  Pages/
    ReplayProjectors.php                 # config-gated
  Exceptions/
    ProjectionNotFoundException.php
    MissingAggregateUuidException.php
config/filament-event-sourcing.php
database/                                # none shipped; stored_events comes from Spatie
tests/
  TestCase.php                           # testbench + panel setup
  Fixtures/                              # Post domain: aggregate, events, projector, model, resource
  Feature/
  Unit/
docs/
website/                                 # Astro documentation website (built in Phase 8)
workbench/                               # only if testbench workbench is genuinely needed; prefer tests/Fixtures
```

## Conventions

- Strict types (`declare(strict_types=1);`) in every file.
- Final classes by default; open for extension only where the spec says users extend them.
- Constructor property promotion, readonly where possible.
- PHPDoc only where types cannot express it (generics, array shapes). Larastan must pass at level 6.
- Pest tests use `it()` syntax, one behaviour per test, descriptive names
  (`it('throws when the projection is missing after creation')`).
- Test fixtures live in `tests/Fixtures` under namespace
  `Albertoarena\FilamentEventSourcing\Tests\Fixtures`. Keep the fixture domain minimal:
  one `Post` aggregate with Created/TitleChanged/Deleted events and a sync projector.
- Commits: imperative mood, scoped, e.g. `Add HasStoredEvents trait`, `Test replay page authorization`.

## Commands

```bash
composer test            # vendor/bin/pest
composer test-coverage   # vendor/bin/pest --coverage --min=90
composer analyse         # vendor/bin/phpstan analyse
composer format          # vendor/bin/pint
```

Define these scripts in composer.json during Phase 0. Run `composer test && composer analyse`
before every commit. Run `composer format` before every commit.

## Pre-commit checklist

Run through this every time, before every commit. Do not commit until all applicable items pass.

- [ ] **Pint** — run `composer format` (the code is formatted, not just checked).
- [ ] **Tests** — `composer test` is green (and `composer analyse` is clean, per the rule above).
- [ ] **Docs website** — once the Astro site exists (Phase 8), update `website/` content for any
      behaviour the commit changes. (Not applicable before Phase 8.)
- [ ] **README** — update `README.md` if the commit changes anything it documents.

## Git Commit Conventions

### Format

- type: short subject line (max 50 chars)
- Detailed body paragraph explaining what and why (not how).

### Rules

- No Claude attribution - NEVER include "Generated with Claude Code" or "Co-Authored-By: Claude"
- Keep first line under 50 characters
- Use heredoc for multi-line commit messages

## Continuous integration (GitHub Actions)

Workflows live in `.github/workflows/`:

- `tests.yml` — runs the Pest suite on push and pull request across the matrix
  (PHP 8.2/8.3/8.4 × Laravel 11/12 × Filament 4; exclude impossible combos). Created in Phase 0.
- `static.yml` — runs Larastan (level 6) and Pint (`--test`) on push and pull request.
  Created in Phase 0.
- `deploy-website.yml` — builds the Astro docs site in `website/` (`npm ci && npm run build`)
  and deploys the static output to GitHub Pages. Triggered on push to the default branch
  (path-filtered to `website/**`) and manually via `workflow_dispatch`. Created in Phase 8.

Do not skip hooks or weaken these gates to make CI pass; fix the underlying issue.

## Definition of done (per phase)

- All tests green, coverage of new code ≥ 90%
- Larastan level 6 clean
- Pint clean
- Public classes documented with a one-paragraph class-level docblock
- `docs/implementation-phases.md` checkbox ticked in the same commit

## Writing style for public-facing text

README, docblocks and exception messages are part of the product. Write them in natural,
direct prose. Do not use em dashes in the README. No marketing fluff, no "blazingly fast",
no emoji in headings. Code examples must be copy-paste runnable and use the fixture-style
`Post` domain consistently. Exception messages must say what went wrong AND what to do
(e.g. "Projection App\Models\Post with uuid X not found after persisting the aggregate.
If your projector is queued, make it synchronous for resources managed by Filament, or run
the queue worker.").

## Documentation website (Astro)

A static documentation website lives in `website/`, built with [Astro](https://astro.build).
It is the public-facing docs home (installation, write bridge, audit tooling, replay page, config
reference) and complements the README rather than duplicating it wholesale. Build it in Phase 8,
after the package is functionally complete, so the docs describe real shipped behaviour.

Rules for the website:
- Self-contained under `website/` with its own `package.json` and `node_modules` (gitignored).
- Same writing style as the rest of the product: natural prose, no em dashes, no marketing fluff,
  no emoji in headings. Code examples copy-paste runnable and consistent with the `Post` fixtures.
- Honour Hard Rule 6: do not reference or copy from other projects. Design the structure and styling
  from scratch for this package; do not lift it from any other repository.
- Deployable as static output (e.g. GitHub Pages). Wire deployment in Phase 8, not before.

## When stuck

If a Filament internal behaves unexpectedly, write a minimal reproduction test against the
fixture panel before changing package code. If a design question is not answered by
`docs/architecture.md`, stop and ask the maintainer; do not invent scope.
