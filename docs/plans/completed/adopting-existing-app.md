# Plan: docs follow-ups (adoption, replay safety, demo)

Validated against the demo on 2026-06-17. Ready to write once the decisions below are made.

## 1. "Adopting in an existing app" docs section

Where: README (new section after "Replay page", before "Configuration"); a mirrored Starlight
page in the Guide group; a short non-goal note in `architecture.md` §6d.

Content:
- Records created through aggregates after adoption are fully event sourced: events are stored,
  they show in the audit tooling, and a projector replay rebuilds them.
- Pre-existing rows (ordinary Eloquent writes) have no events behind them. They keep working for
  reads, but editing one through the write bridge produces an incomplete event stream (a change
  event with no preceding creation event), and they are not replayable (a replay rebuilds from the
  event store, so rows with no events are not recreated, and a reset-then-replay drops them).
- To include existing rows, backfill one initial event per row (a one-off command the user writes)
  capturing current state. The package does not perform CRUD-to-event-sourcing migration; it is a
  domain decision.

## 2. "Replay safety" note

Where: the "Replay page" section of the README and the Starlight site.

Content:
- Spatie's `Projectionist::replay()` does not wipe the read model; it calls `resetState()` on each
  projector only if that method exists.
- Projectors driving Filament-managed resources must be replay-safe: implement `resetState()` to
  clear the projection, or make handlers idempotent (e.g. `updateOrCreate`). The package
  intentionally does not reset projections for you.

## 3. Screenshots on the docs website (decision needed)

Recommendation: yes, valuable. Copy the demo's screenshots into the package website as our own
assets (e.g. `website/src/assets/`) and embed them on the relevant pages (event history on the
Audit tooling page, the replay flow on the Replay page). The images are ours, so including them is
not "referencing another project".

## 4. Reference the demo (decision needed: Hard Rule 6)

Recommendation: yes, link the demo from the README ("Related packages" or a short "Demo" line) and
the website. BUT linking `albertoarena/filament-event-sourcing-demo` is a reference to another
repository, which Hard Rule 6 currently forbids. To do it, add the demo to Rule 6's allowed
exceptions in `CLAUDE.md` (alongside the dependencies, the §10 related packages, and the traffic
badge).

## Style

- README: no em dashes, no emoji in headings, `Post`-fixture examples.
- Website: Starlight `:::caution` asides where useful.

## Out of scope here (separate follow-ups, no docs change)

- Filament plugin directory submission: repo topics + a banner image (the event-history slide-over
  screenshot is a good candidate).
- Demo repo GitHub details: description and topics.
