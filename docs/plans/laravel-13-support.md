# Plan: add Laravel 13 to the supported constraint

## Problem

The package currently requires `illuminate/contracts: ^11.0|^12.0` and the dev dependency
`orchestra/testbench: ^9.0|^10.0`. The CI matrix (`.github/workflows/tests.yml`) runs
PHP 8.2/8.3/8.4 against Laravel 11 and 12 with Filament 4. The maintainer wants Laravel 13
in the constraint and in CI, but only once it is genuinely safe to ship: the two hard
dependencies (`filament/filament` and `spatie/laravel-event-sourcing`) must declare Laravel 13
support upstream, the full Pest suite must pass on every supported PHP × Laravel × Filament
combination, and Larastan must stay clean.

Today's stance, captured in `.docs/next-session.md` and in build memory, is "Laravel 13 /
Filament 5 widen only after CI matrix passes." This plan operationalises that.

## Prerequisites (do these first, do not skip)

These are discovery steps, not implementation steps. Stop and reassess if any answer is
"no": shipping a Laravel 13 constraint that pulls in unsupported transitive deps will break
real installs.

1. **Laravel 13 status.** Confirm Laravel 13 has shipped a stable tag on Packagist
   (`composer show laravel/framework --available | grep '^versions'` or check
   <https://laravel.com/docs/13.x>). Note the bundled minimum PHP version — Laravel 12
   required PHP 8.2; if 13 bumps to 8.3+ the matrix and `composer.json` `php` constraint
   change too.
2. **Filament 4 + Laravel 13.** Open `filament/filament`'s `composer.json` on Packagist (or
   GitHub `main`) and confirm `illuminate/contracts` (or whichever Illuminate packages it
   requires) include `^13.0`. The package pins Filament to `^4.0` on purpose (CLAUDE.md).
   If Filament 4 has not yet widened to Laravel 13, the work is blocked on upstream; do not
   proceed.
3. **spatie/laravel-event-sourcing v7 + Laravel 13.** Same check on
   `spatie/laravel-event-sourcing`'s `composer.json`. If v7 supports Laravel 13, no version
   bump needed. If only a newer major does, the work expands into a separate "bump Spatie
   ES" decision (out of scope here).
4. **Testbench mapping.** Orchestra Testbench follows Laravel major versions: L11 → tb9,
   L12 → tb10, so L13 → tb11. Confirm `orchestra/testbench: ^11.0` exists on Packagist.
5. **Composer audit advisory.** The current matrix pins `composer:2.9` because every L11
   release is flagged by an advisory and `config.audit.block-insecure` is honoured only by
   2.9 (see comments in `tests.yml` and `composer.json`). Verify whether Laravel 12 and 13
   are clear; if they are, the pin may no longer be strictly necessary for the L12/L13
   matrix legs, but keep it for the L11 leg.
6. **Filament 5.** The CLAUDE.md note says Filament 5 only adds Livewire v4 support. If
   Filament 4 supports L13 already, this plan is Filament 4 only. Filament 5 is a separate
   future task — do not bundle it.

Record the answers (versions, dates, sources) in the commit message that lands the
constraint change, so the rationale is preserved.

## Approach

Once the prerequisites pass, the work is mechanical: widen the constraints, add a CI matrix
leg, fix anything that breaks, then update README/docs/composer keywords. Keep Filament
pinned to `^4.0`. Do not loosen the spatie/laravel-event-sourcing constraint.

Adopt the same posture as the existing Laravel 11/12 dual support: TDD discipline
(failing test first), one logical change per commit, `composer test && composer analyse`
green before each commit.

## Changes

### 1. Composer constraints

`composer.json`:

- `require.illuminate/contracts`: `^11.0|^12.0|^13.0`
- `require-dev.orchestra/testbench`: `^9.0|^10.0|^11.0`
- `require.php`: bump to `^8.3` if and only if Laravel 13 drops PHP 8.2. Otherwise leave
  at `^8.2`.
- Do not change `filament/filament` (`^4.0` stays) or `spatie/laravel-event-sourcing`
  (`^7.0` stays). If the spatie check in prerequisite 3 fails, this plan stops here.

Run `composer update` locally against the L13 leg by temporarily forcing the constraint
(`composer require laravel/framework:^13.0 orchestra/testbench:^11.0 --no-update && composer update`)
and read the resolver output. Resolve any conflicts before pushing.

### 2. CI matrix

`.github/workflows/tests.yml`:

- Add `'13.*'` to `matrix.laravel`.
- Add the `include` mapping `laravel: '13.*'` → `testbench: '11.*'`.
- If Laravel 13 drops PHP 8.2, exclude `php: '8.2'` from the L13 leg via `matrix.exclude`
  (do not strip 8.2 from the whole matrix — L11 and L12 still need it).
- Job name template (`PHP ${{ matrix.php }} - Laravel ${{ matrix.laravel }} - Filament 4`)
  stays correct.
- Coverage job (`coverage`) keeps PHP 8.3 + the default constraints; no change unless 8.3
  becomes the L13 minimum and we want the coverage leg on the highest still-supported
  version (decide once L13's PHP floor is known).

### 3. Composer audit / tools pin

If the L11 advisory is still active, keep `tools: composer:2.9` on every leg. If it has
been cleared, drop the pin from the L12 and L13 legs only and remove the comment block
referencing it; leave the L11 leg pinned. Do not blanket-remove the pin without checking
both `composer.json` `block-insecure: false` and the upstream advisory feed.

### 4. Source code

No source changes are expected. The package's Illuminate touchpoints are narrow:

- `Illuminate\Database\Eloquent\{Model, Builder, Relations\HasMany}`
- `Illuminate\Support\{Collection, HtmlString, Str}`
- `Illuminate\Contracts\Auth\Authenticatable` (tests only)

These have been stable across L11 and L12. If L13 changes any signature (e.g. a method
gains a new required argument, an enum replaces a string return), fix it in `src/`
alongside the constraint widen; do not introduce a compatibility shim. Write the failing
Pest test first, then the fix.

### 5. Tests

- Run `composer test` locally against the L13 leg before pushing. The fixture domain
  (`tests/Fixtures/Post*`) is the integration surface and will surface any
  Eloquent/Filament/Spatie ES break.
- Watch for Testbench 11 changes to `defineEnvironment` / `getPackageProviders` semantics.
- The migration runner in `tests/TestCase.php` reads stub files from
  `vendor/spatie/laravel-event-sourcing/database/migrations`; confirm Spatie ES v7's
  layout has not changed on the L13 install.

### 6. Static analysis

Run `composer analyse` against the L13 install. Larastan level 6 must stay clean.
Larastan resolves baseline against the installed Laravel version, so a green local run on
L12 says nothing about L13. The CI `static.yml` job runs once against the default install
and that is enough; do not multiply static-analysis legs across the matrix.

### 7. Docs

- README "Requirements" / "Stack" section: add Laravel 13 to the supported list.
- Docs website (`website/src/content/docs/installation.*`): mirror the same change.
- Filament directory listing (filamentphp.com): edit the description's Requirements block
  to include Laravel 13. The submission text lives in `.claude/next-session.md`.
- `composer.json` keywords: no change needed (no `laravel-13` keyword convention).

### 8. Demo app (separate repo)

`albertoarena/filament-event-sourcing-demo` currently requires `laravel/framework: ^12.0`.
Updating it is out of scope for this plan but worth a follow-up:

- Bump the demo's `laravel/framework` constraint once L13 is shipped here.
- Re-run the demo locally to confirm screenshots still match (they should).
- Land a separate commit in the demo repo.

Do not let demo work block the package release; the demo can stay on L12 for a release
cycle.

## Risks and call-outs

- **Spatie ES v7 missing L13.** Most likely blocker. v7 was tagged in 2024; if Spatie has
  not widened it, the choices are wait, contribute upstream, or hold the constraint at
  L12. Holding is fine — there is no urgency.
- **Filament 4 missing L13.** Same shape. If Filament 4 is pinned to L11/12 upstream,
  Filament 5 may be required, which is a much larger change and explicitly out of scope.
- **PHP floor bump.** If L13 requires PHP 8.3, dropping 8.2 is a breaking change for
  installs on the lowest PHP. The package is `0.x` so this is acceptable, but it should
  be in the release notes (and trigger at least a `0.2.0` tag, not a `0.1.x` patch).
- **Composer audit pin.** Leaving the `composer:2.9` pin in place is safe; removing it
  prematurely is not. Default to leaving it.
- **CI runtime.** Adding a Laravel leg multiplies job count by `PHP_versions × 1`. At
  three PHP versions that is three more jobs. Acceptable.

## Open questions

1. Has Laravel 13 actually shipped a stable tag as of today? (Verify before starting.)
2. Does Laravel 13 keep PHP 8.2 or require 8.3+?
3. Has spatie/laravel-event-sourcing v7 widened to L13, and if not, is there an issue or
   PR tracking it?
4. Has Filament 4 widened to L13, and on which minor?

If any answer is "no" or "unknown after a real check", stop and report. Do not widen the
constraint speculatively.

## Definition of done

- All prerequisites verified and recorded in the commit message.
- `composer.json` widened (constraints) and `composer update` resolves cleanly.
- CI `tests.yml` includes a Laravel 13 leg and is green on PHP 8.3 and 8.4 (and 8.2 if
  still supported by L13).
- `composer test` and `composer analyse` green locally on the L13 install.
- README and docs website mention Laravel 13 in requirements.
- One commit per logical change, imperative subjects, no Claude attribution
  (per CLAUDE.md commit conventions).
- A note added to `.claude/next-session.md` recording the L13 bump and any follow-up
  (demo repo bump, Filament directory description edit).
