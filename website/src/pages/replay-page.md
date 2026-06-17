---
layout: ../layouts/Layout.astro
title: Replay page
description: Config-gated page to replay projectors one at a time.
---

# Replay page

Enable `replayPage()` on the plugin to add a page that replays projectors one at a time. It is off by default and gated three ways: the plugin option, the `replay.enabled` config flag and an optional authorization ability. All three are checked again on the server before a replay runs.

The page lists the registered projectors and offers a confirmation-guarded Replay action for each. When a replay finishes, a notification reports how many events were replayed.

<p class="callout"><strong>Warning.</strong> A replay runs synchronously inside the web request and can take a long time on large event stores. For production, prefer the CLI: <code>php artisan event-sourcing:replay</code>.</p>

See the [Configuration](../configuration) reference for the `replay.enabled` and `replay.authorize` options.
