# Plan: add Filament 5 support

## Status

Approved by the maintainer (2026-10-12): option A for PHP 8.2, and CLAUDE.md plus the docs website
are updated in the same PR.

## Problem

Dependabot PR #20 changes `filament/filament` from `^4.0` to `^5.9`. That replaces the constraint
instead of widening it, so merging it would cut off every Filament 4 user. The goal is a constraint of
`^4.0|^5.0`, with both majors tested on every supported PHP and Laravel combination.

Today `tests.yml` forces `filament/filament:^4.0` in the install step, so every matrix leg runs
Filament 4. Only the unmatrixed Coverage and "PHPStan and Pint" jobs float to whatever
`composer.json` allows. On #20's branch they installed Filament 5.10.0, Livewire 4.4.7 and Laravel
13.35 on PHP 8.3, and both passed (runs 37574121499 and 37574121433). That is one green leg of v5
evidence, by accident.

## Resolved facts (verified 2026-10-12)

| Package | Requires | Source |
|---|---|---|
| `filament/support` 5.10.0 | php `^8.2`, illuminate/contracts `^11.28\|^12.0\|^13.0`, livewire `^4.4.7` | Packagist |
| `filament/support` 5.0.0 | php `^8.2`, illuminate/contracts `^11.28\|^12.0`, livewire `^4.0` | Packagist |
| `filament/support` 4.15.0 | php `^8.2`, illuminate/contracts `^11.28\|^12.0\|^13.0`, livewire `^3.8.7` | Packagist |
| `livewire/livewire` 4.4.7 | php `^8.1`, illuminate `^10.0\|^11.0\|^12.0\|^13.0` | Packagist |
| `pestphp/pest-plugin-livewire` 3.0.0 | php `^8.1`, livewire `^3.5.6`, pest `^3.0` | Packagist |
| `pestphp/pest-plugin-livewire` 4.1.0 | php `^8.3`, livewire `^3.7.4\|^4.0.1`, pest `^4.3.1` | Packagist |

Filament 5 supports the same PHP and Laravel range as Filament 4.15. It adds no new runtime exclusion.

The official v5 upgrade guide lists no API changes, only new minimums: PHP 8.2, Laravel 11.28,
Livewire 4, Tailwind 4. I checked the Livewire 4 upgrade guide against the package: no `wire:`
directives, no `<livewire:>` tags, no Livewire config, routes or JS hooks. The three Blade views
render inside Filament components and use nothing Livewire-specific.

Local run on a scratch worktree with `^4.0|^5.0` (Filament 5.10.1, Livewire 4.4.7, Laravel 13.35,
Testbench 11.3, PHP 8.4): 50 tests passed, PHPStan level 6 clean. No source change is needed.

## The catch: PHP 8.2 cannot run the test suite on Filament 5

PHP 8.2 legs resolve Pest 3, because Pest 4 needs PHP 8.3. On Pest 3 the only
`pest-plugin-livewire` release is 3.0.0, which requires Livewire 3. Filament 5 needs Livewire 4.
So PHP 8.2 plus Filament 5 does not install with today's dev dependencies, even though the package
itself supports that combination at runtime.

The plugin is used for its `livewire()` helper, 17 calls across 4 test files. Two options:

- **A. Exclude PHP 8.2 x Filament 5 in CI.** No test changes. PHP 8.2 users on Filament 5 run an
  untested combination.
- **B. Replace `livewire()` with `Livewire::test()` and drop `pest-plugin-livewire`.**
  `Livewire::test()` is core Livewire in both v3 and v4, so it needs no plugin and resolves on every
  leg. This is a mechanical test-only change that removes one dev dependency. Every supported
  combination is then tested.

**Decision: option A.** The package keeps allowing PHP 8.2 with Filament 5, and the docs say CI
covers Filament 5 on PHP 8.3 and newer. Option B stays available in `docs/ideas.md`.

## Changes

### 1. `.github/workflows/tests.yml` matrix

Add `filament: ['4', '5']` as a dimension. The install step uses
`"filament/filament:^${{ matrix.filament }}.0"`, and the job name reads
`PHP x - Laravel y - Filament ${{ matrix.filament }}`, so names stop saying "Filament 4" for
everything. The existing excludes stay, plus PHP 8.2 on Filament 5 (option A): 18 legs, 10 on
Filament 4 and 8 on Filament 5.

### 2. `composer.json`

`filament/filament: ^4.0` becomes `^4.0|^5.0`. Nothing else changes, and Livewire comes in
transitively.

### 3. Coverage and PHPStan jobs

Neither job should be the only evidence for a major again, and neither should float silently.
`Coverage` and `PHPStan and Pint` are required status checks on `main`, so their job names stay
exactly as they are (renaming them would block every merge).

- **PHPStan and Pint**: pinned to Filament 5 (PHP 8.3). A new job, `PHPStan (Filament 4)`, runs
  PHPStan against Filament 4. Vendor types differ between majors, so analyse against both. Pint
  runs once.
- **Coverage**: pinned to Filament 5 on PHP 8.3, with a comment saying so. Coverage measures our own
  code, and the matrix already runs the full suite on both majors, so a second coverage run adds
  cost without adding evidence.
- The maintainer may want to add `PHPStan (Filament 4)` to the required checks after merge.

### 4. Docs

- `README.md:38` and `website/src/content/docs/index.mdx:64`: Filament `^4.0 | ^5.0`.
- `docs/architecture.md:81`: "A Filament v4 resource" becomes "A Filament resource".
- `CLAUDE.md`: line 58, Laravel `^11.0|^12.0` becomes `^11.0|^12.0|^13.0` (composer.json already
  allows 13). Line 59: the constraint and the "pinned to v4" note. Line 161: the matrix description.
  Approved by the maintainer.
- README and website requirements: note that CI covers Filament 5 on PHP 8.3 and newer.
- `docs/ideas.md`: record option B for v2.
- The filamentphp.com directory listing requirements block, after release (manual).

### 5. Verification (this is the gate)

Before opening the PR:

1. Local, PHP 8.4, Filament 5 + Laravel 13: `composer test`, `composer test-coverage`,
   `composer analyse`, `composer format`.
2. Local, PHP 8.4, Filament 4 + Laravel 12: same commands, to confirm the widened constraint still
   resolves and passes on v4.

On the PR: all 18 matrix legs green, both PHPStan legs clean, Pint clean, coverage
threshold met.

## Commit sequence (one PR, TDD posture)

No behaviour differs between v4 and v5 (the suite passes unchanged on v5), so there is no
production-code test to write first. The existing suite, run on both majors, is the test.

1. `ci: add Filament as a test matrix dimension`. The install step's `composer require` rewrites
   the constraint per leg, so the Filament 5 legs pass even before the widen; they prove the code
   works on v5, and the next commit makes `composer.json` allow it.
2. `feat: support Filament 5` (widen the constraint; all legs green).
3. `ci: run static analysis on both Filament majors` (Filament 4 PHPStan job plus pins).
4. `docs: document Filament 5 support` (README, website, architecture, CLAUDE.md, ideas).

Then close #20 with a link to the PR. No merge or release tag without the maintainer's go-ahead.

## Out of scope

- A `--prefer-lowest` leg (the floor of `^5.0` is 5.0.0, which lacks Laravel 13; the resolver
  handles that). Note it in `docs/ideas.md` if wanted.
- Bumping the demo app to Filament 5 (separate repo).

## Outcome (merged in PR #22, 2026-10-12)

Delivered as planned with option A. No source changes were needed. All 18 test legs passed on the
PR (10 on Filament 4, 8 on Filament 5), along with `Coverage` and `PHPStan and Pint` (both pinned to
Filament 5) and the new `PHPStan (Filament 4)` job. Locally on PHP 8.4, both Filament 4.15.1 and
5.10.1 passed 50 tests at 97.0% coverage with PHPStan clean. Dependabot PR #20 was closed in favour
of #22.

A correction to the first draft of this plan: the commit sequence assumed the Filament 5 legs
would fail until the constraint was widened. They would not have, because each leg's
`composer require` rewrites the constraint before installing. The legs prove the code works on
v5, and `composer.json` is what users actually get.

Still open after merge: tag the release, consider making `PHPStan (Filament 4)` a required check,
and update the filamentphp.com directory listing requirements.
