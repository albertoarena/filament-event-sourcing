# Plan: add Laravel 13 support

## Status

Research complete (2026-08-05). Every prerequisite the earlier draft of this plan flagged as an
open question has been resolved against live Packagist metadata. The ecosystem is ready; the work
is no longer blocked on upstream. The one thing the earlier draft missed is now the core of the
task: **Laravel 13 forces an upgrade of the Pest testing stack to v4**, because
`pestphp/pest-plugin-laravel` only gained Laravel 13 support in v4.1.0.

## Problem

The package requires `illuminate/contracts: ^11.0|^12.0` and, in dev, `orchestra/testbench: ^9.0|^10.0`
and the Pest 3 stack. The CI matrix (`.github/workflows/tests.yml`) runs PHP 8.2/8.3/8.4 against
Laravel 11 and 12 with Filament 4. filamentphp.com Package health flags "Current Laravel version
supported" as a warning because the package does not co-install with Laravel 13 (an Ecosystem score
item, not Security). We want Laravel 13 in the constraint and in CI, verified across every supported
PHP and Laravel combination, with Larastan staying clean and Filament held at `^4.0`.

## Resolved facts (verified 2026-08-05)

| Dependency | Laravel 13 support | Notes |
| --- | --- | --- |
| `laravel/framework` 13.x | shipped, stable (v13.24.0) | requires PHP `^8.3` (drops 8.2) |
| `filament/filament` 4.11.7 | yes | `filament/support` requires `illuminate/contracts: ^11.28\|^12.0\|^13.0`. No bump needed; stays `^4.0`. |
| `spatie/laravel-event-sourcing` v7 | yes | `illuminate/*: ^10.0\|^11.0\|^12.0\|^13.0`. Stays `^7.0`. |
| `spatie/laravel-package-tools` | yes | `illuminate/contracts: ...\|^13.0`. |
| `orchestra/testbench` 11.x | yes (v11.1.0) | pairs with Laravel `^13.1.1`, requires PHP `^8.3`. |
| `larastan/larastan` 3.x | yes (v3.10.0) | `illuminate/contracts: ...\|^13`. Existing `^3.0` resolves it; no change. |
| `pestphp/pest-plugin-laravel` | **v3 does NOT support L13** | v3.2.0 caps at `^11.39.1\|^12.9.2`. v4.1.0 adds `^13.0` but pulls **Pest 4** and PHP `^8.3`. v5 needs PHP `^8.4` (too aggressive). |
| `pestphp/pest` | v4 needed for L13 | Pest 4 core requires PHP `^8.3`. |
| `pestphp/pest-plugin-livewire` | v4.1.0 tracks Pest `^4.3.1` | must move to v4 in lockstep. |

Conclusion: the target testing stack is **Pest 4** (not Pest 5, which would raise the floor to
PHP 8.4 and drop 8.2/8.3). Filament, both Spatie packages, and Larastan need no constraint changes.

## The key insight the earlier draft missed

The earlier draft said "No source changes are expected" and treated this as a mechanical constraint
widen. That is true for `src/` (the Illuminate touchpoints are narrow and stable across L11/L12/L13),
but the dev-tooling chain is not mechanical:

```
Laravel 13
  -> pest-plugin-laravel >= v4.1.0   (v3 has no L13)
       -> pestphp/pest ^4            (plugin major tracks Pest major)
            -> PHP ^8.3              (Pest 4 core floor)
  -> pest-plugin-livewire ^4         (must match the Pest major)
```

So the real work is a **Pest 3 to Pest 4 migration**, and the main risk is that jump, not the
constraint edit. Pest 4 is largely additive (it introduces browser testing), so the existing
`it()`-style feature and unit tests are expected to pass unchanged, but this must be proven by
running the suite, not assumed.

## Constraint strategy: one composer.json for the whole matrix

Widen with unions rather than bumping floors, so a single `composer.json` serves every matrix cell.
Composer picks the highest installable set per cell:

- PHP 8.2 cells (Laravel 11/12 only): resolve **Pest 3** (Pest 4 needs PHP 8.3).
- PHP 8.3+ cells: resolve **Pest 4**; Laravel 13 cells additionally get Testbench 11.

This keeps Laravel 11 + PHP 8.2 support intact while adding Laravel 13 on PHP 8.3+.

## Changes

### 1. `composer.json`

```
require:
  php: ^8.2                                  # unchanged; L11/L12 still run on 8.2
  illuminate/contracts: ^11.0|^12.0|^13.0    # add ^13.0
require-dev:
  orchestra/testbench: ^9.0|^10.0|^11.0      # add ^11.0
  pestphp/pest: ^3.0|^4.0                     # add ^4.0
  pestphp/pest-plugin-laravel: ^3.0|^4.0      # add ^4.0
  pestphp/pest-plugin-livewire: ^3.0|^4.0     # add ^4.0
```

Unchanged: `filament/filament: ^4.0`, `spatie/laravel-event-sourcing: ^7.0`,
`spatie/laravel-package-tools: ^1.16`, `larastan/larastan: ^3.0`, `laravel/pint: ^1.17`.

### 2. `.github/workflows/tests.yml` matrix

Add the Laravel 13 leg, pair it with Testbench 11, and exclude PHP 8.2 (Laravel 13 needs 8.3):

```yaml
matrix:
  php: ['8.2', '8.3', '8.4']
  laravel: ['11.*', '12.*', '13.*']
  include:
    - { laravel: '11.*', testbench: '9.*' }
    - { laravel: '12.*', testbench: '10.*' }
    - { laravel: '13.*', testbench: '11.*' }
  exclude:
    - { php: '8.2', laravel: '13.*' }
```

Resulting cells (8 total): 8.2x{11,12}, 8.3x{11,12,13}, 8.4x{11,12,13}. The job-name template and
the `composer require` install step already parameterise on `matrix.laravel` and `matrix.testbench`,
so no other edits are needed there.

The `coverage` and `static` jobs run a plain `composer update` on PHP 8.3, which will now resolve to
Laravel 13 + Pest 4 + Larastan 3.10. That is desirable: coverage and static analysis then run against
the newest supported Laravel. No change required to those jobs.

Optional PHP 8.5: the maintainer can run 8.5 locally via Herd. If the full stack (Filament 4, Pest 4,
Spatie ES v7) passes on 8.5, add `'8.5'` to `matrix.php` in a follow-up. Keep it out of the first pass
until verified, to avoid a red cell blocking the L13 landing.

### 3. Composer audit / tools pin

Leave `tools: composer:2.9` in place on every leg. It exists because active Laravel 11 advisories
would otherwise be rejected, and `config.audit.block-insecure: false` is honoured by the 2.9 line.
This is orthogonal to Laravel 13. Do not remove it in this change.

### 4. Source code

No `src/` changes expected. The package's Illuminate surface is narrow and stable across L11/L12/L13:

- `Illuminate\Database\Eloquent\{Model, Builder, Relations\HasMany}`
- `Illuminate\Support\{Collection, HtmlString, Str}`
- `Illuminate\Contracts\Auth\Authenticatable` (tests only)

If Laravel 13 changed any of these signatures, fix it in `src/` alongside the widen, failing test
first, no compatibility shim. Do not pre-emptively refactor.

### 5. Verification (this is the gate)

The maintainer has Herd with PHP 8.2/8.3/8.4/8.5, so full local verification is possible. Exercise
both ends of the constraint union, because a green run on one Pest major says nothing about the other:

1. High end, Laravel 13 + Pest 4 (PHP 8.3, then 8.4):
   ```
   composer require --dev --no-update "orchestra/testbench:^11.0" \
     "pestphp/pest:^4.0" "pestphp/pest-plugin-laravel:^4.0" "pestphp/pest-plugin-livewire:^4.0"
   composer require --no-update "illuminate/contracts:^13.0"
   composer update -W --prefer-dist
   composer test && composer analyse && vendor/bin/pint --test
   ```
   This is where the Pest 3 to 4 migration surfaces. Fix any test-API breaks here.
2. Low end, Laravel 12 + Pest 3 (PHP 8.2): confirm the widened unions still resolve the floor and the
   suite stays green (`composer update -W --prefer-lowest` on a fresh checkout, or force the L12 leg).
3. Sanity-check the exact cells CI will run before pushing, so the matrix does not surprise us.

The package does not commit `composer.lock` (a library; Plumb verifies this), so local resolver
experiments do not dirty a tracked lockfile. Restore `composer.json` between experiments.

### 6. Docs

- README requirements/stack section: add Laravel 13.
- Docs website (`website/`): mirror the same change in the installation/requirements content.
- filamentphp.com directory listing: add Laravel 13 to the Requirements block after release.
- No `composer.json` keyword change (there is no `laravel-13` keyword convention).

### 7. Demo app (separate repo, out of scope here)

`albertoarena/filament-event-sourcing-demo` requires `laravel/framework: ^12.0`. Bumping it is a
follow-up in that repo, not a blocker for this package release. Track it separately.

## Commit sequence (TDD posture, one logical change per commit)

1. `test: add Laravel 13 leg to the CI matrix` (matrix edit; expected red until the constraints land,
   or land together with step 2 if a red main run is undesirable).
2. `chore: widen constraints for Laravel 13 and Pest 4` (composer.json unions; the substantive change).
3. Any `fix:` commits the Pest 4 migration requires, each with its failing test first.
4. `docs: note Laravel 13 support` (README + website).

Keep Filament pinned to `^4.0`. Do not bundle Filament 5. Commit subjects imperative, under 50 chars,
no Claude attribution (per CLAUDE.md).

## Risks and call-outs

- **Pest 3 to 4 migration.** The one real risk. Pest 4 is mostly additive, but the suite must pass on
  Pest 4 before this ships. Verify locally on PHP 8.3 and 8.4.
- **PHP floor per Laravel major.** Laravel 13 requires PHP 8.3; the matrix exclude handles this. The
  package `require.php` stays `^8.2` so Laravel 11/12 installs on 8.2 keep working. This is not a
  breaking change for existing users.
- **Range resolution correctness.** The union constraints must resolve Pest 3 on 8.2 and Pest 4 on
  8.3+. Confirmed possible from metadata (Pest 4 floor is PHP 8.3), but prove it with an actual
  `--prefer-lowest` run.
- **CI runtime.** Two new cells (8.3x13, 8.4x13). Acceptable.
- **Composer 2.9 pin.** Keep it. Removing it is a separate decision tied to the L11 advisory, not L13.

## Definition of done

- `composer.json` constraints widened; `composer update` resolves cleanly on both the L13/Pest 4 and
  L12/Pest 3 ends.
- `tests.yml` includes the Laravel 13 leg (Testbench 11, PHP 8.2 excluded); CI green on all 8 cells.
- `composer test`, `composer analyse`, and `vendor/bin/pint --test` green locally on the L13 install.
- README and docs website mention Laravel 13 in requirements.
- One commit per logical change, imperative subjects, no Claude attribution.
- filamentphp.com "Current Laravel version supported" warning clears after release.
