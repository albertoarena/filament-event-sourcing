# Plan: rebuild the docs website with Astro Starlight

## Problem

The current `website/` is a hand-rolled minimal Astro site. It renders dark-only with a plain
two-column layout and no search, theme toggle, on-page table of contents or landing hero. The
maintainer wants it to match the look and feel of their other docs sites, which are built with
[Astro Starlight](https://starlight.astro.build): light/dark/auto theme toggle, top bar with
command-palette search, grouped collapsible sidebar, right-hand "On this page" ToC, and a splash
hero with action buttons and card grids.

## Approach

Replace the custom Astro setup with Starlight, keeping the same content, base path and deploy
workflow. Starlight provides the theme toggle, search (Pagefind), sidebar, ToC and hero out of the
box, which removes the need for the hand-written layout and global CSS.

## Changes

### Toolchain

- `website/package.json`: dependencies become `@astrojs/starlight` `^0.32`, `astro` `^5.1`,
  `sharp` `^0.33`. Regenerate `website/package-lock.json` with `npm install`.
- `website/astro.config.mjs`: keep `site: 'https://albertoarena.github.io'` and
  `base: '/filament-event-sourcing'`; drop `trailingSlash` (use Starlight's default). Add the
  Starlight integration:
  - `title`, `description`
  - `social` GitHub link to this repository
  - `editLink.baseUrl` pointing at this repository's `website/`
  - `customCss: ['./src/styles/custom.css']`
  - `sidebar` groups (see below)
- `website/src/content.config.ts`: Starlight `docsSchema()` collection.

### Content

Move `website/src/pages/*.md` into `website/src/content/docs/*.mdx`:

- `index.mdx` becomes a `template: splash` hero page:
  - tagline: the one-line what/why
  - actions: Get Started (Installation), Write bridge, View on GitHub
  - a `<CardGrid>` of features: write bridge, audit tooling, replay page, event history,
    primary-key agnostic, KISS scope
- `installation.mdx`, `write-bridge.mdx`, `audit-tooling.mdx`, `replay-page.mdx`,
  `configuration.mdx`: same prose as today, with the two warning callouts converted from
  `<p class="callout">` to Starlight asides (`:::caution`).
- Internal links use Starlight's base-prefixed, trailing-slash form
  (e.g. `/filament-event-sourcing/configuration/`).

Sidebar groups:

- Introduction: Overview (`/`)
- Getting Started: Installation
- Guide: Write bridge, Audit tooling, Replay page
- Reference: Configuration

### Remove

- `website/src/layouts/Layout.astro`
- `website/src/pages/` (replaced by `src/content/docs/`)

### Theme

- `website/src/styles/custom.css`: override Starlight accent colors with an amber palette to fit
  Filament's branding (Filament's own default primary, Tailwind `amber`), set content width and
  code-block styling. Agreed palette:

  Light mode:
  - `--sl-color-accent-low: #fef3c7` (amber-100)
  - `--sl-color-accent: #d97706` (amber-600, darker for link contrast on white)
  - `--sl-color-accent-high: #78350f` (amber-900)

  Dark mode:
  - `--sl-color-accent-low: #451a03` (amber-950)
  - `--sl-color-accent: #f59e0b` (amber-500, Filament's signature amber)
  - `--sl-color-accent-high: #fde68a` (amber-200)

## Unaffected

- `.github/workflows/deploy-website.yml` (still `npm ci` + `astro build` to `dist`; Starlight
  bundles Pagefind search during the build).
- The `/filament-event-sourcing` base path and the README and `composer.json` links.
- Hard Rule 6: no other project is referenced in any file; only this repository is linked.

## Verification

- `npm run build` completes cleanly and emits the six pages plus the Pagefind search index.
- No em dashes and no emoji in headings.
- Spot-check the built output: theme toggle present, sidebar groups correct, hero renders.

## Decisions

- Accent color: amber, using Filament's default primary palette (values above).
