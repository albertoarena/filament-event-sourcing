# Ideas for v2 and beyond

Deferred scope, kept out of v1 to honour KISS. Each item needs a concrete use case and tests
before it ships.

- **Bulk delete action.** A bulk equivalent of `EventSourcedDeleteAction` that calls the
  aggregate per selected record. Out of scope for v1; users can compose the single-record
  semantics themselves for now.
- **Queued projector support on managed resources.** The write bridge currently requires
  synchronous projectors because it reads the projection back immediately after persisting.
  A future version could wait for, or react to, queued projector completion.
- **Snapshot browser.** A read-only resource over Spatie's `snapshots` table, similar to the
  Stored Events browser.
- **Aggregate state inspector.** A page that retrieves an aggregate by uuid and shows its
  reconstituted state, useful for debugging.
- **Replay all.** A guarded "replay every projector" action. v1 deliberately replays one
  projector at a time to keep the operation predictable.
