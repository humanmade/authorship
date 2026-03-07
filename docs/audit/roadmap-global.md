# Authorship Global Roadmap

Last updated: 2026-03-07 (America/Edmonton)

## Why this project exists

Authorship is a WordPress plugin for modern author attribution. Its core purpose is to let teams assign multiple authors (including guest authors) while treating REST API and WP-CLI workflows as first-class paths.

At this stage, the work is focused on making the plugin safer to operate and easier to maintain in a fork-first model:
- deterministic behavior in CLI migrations
- explicit quality gates and reproducible testing
- clear planning artifacts that do not depend on upstream merge acceptance

## Strategic direction

1. Keep fork-local delivery unblocked.
2. Prefer TDD increments with narrow, verifiable build items.
3. Raise operational confidence before expanding feature surface.
4. Keep upstream PRs as optional value, not a dependency.
5. Offer upstream contributions at natural phase boundaries in minimal, well-scoped PRs.

---

## History to date

### Phase 00: Foundation baseline (completed)
- Defined support matrix and quality-gate contract in [foundation-quality-baseline.md](foundation-quality-baseline.md).
- Established execution order: tooling and hardening before performance work.

### Phase 01: Audit + first hardening wave (completed)
- Built evidence-backed audit in [HM_WPCS_audit.md](HM_WPCS_audit.md).
- Replaced placeholder assumptions with repo-grounded findings and rule references.
- Executed `01-Build-01` through `01-Build-04`:
  - tooling compatibility and standards reproducibility
  - guest-author creation hardening
  - post-insert failure observability
  - editor render-side-effect removal and CLI pacing controls
- Key commits in this wave:
  - `d00d15c` `Execute 01-Build-01 standards tooling compatibility refresh`
  - `a83e128` `Execute 01-Build-02 guest author creation hardening`
  - `5a3e0c7` `Execute 01-Build-03 post insert observability hardening`
  - `1d02511` `Execute 01-Build-04 performance hardening and stabilize tests`

### Phase 02: Fork-first delivery stabilization (completed — fork-local)
- Executed `02-Build-01` through `02-Build-12` on `codex/restack-audit-queue`.
- Main theme: deterministic and test-covered CLI migration behavior.
- Highlights:
  - pause filter/clamping coverage
  - pause-resolution hook contract and multi-batch behavior
  - multisite stabilization for migration tests
  - post-type input normalization, validation, and `post-type=any` compatibility
  - baseline statement-coverage gate via `composer test:coverage` (`64.03%` vs `60%` threshold)
  - CI parity for coverage gate in the WP unit-test workflow
  - ratcheting policy adoption with threshold increase from `60%` to `63%`
  - PHPStan baseline reduced to zero ignored errors via annotation/type-guard fixes
- Recent commits:
  - `eededb0` `Build-06: harden wp-authors post-type input handling`
  - `2a9d813` `Build-07: validate migration post types against registered types`
  - `bdbc20d` `Build-08: preserve wp-authors post-type any compatibility`
  - `0089222` `Build-09: add coverage command and baseline threshold gate`
  - `fb56f21` `Build-10: add CI coverage gate parity in unit test workflow`

---

## Phase 02 completion criteria

Phase 02 is done when all of the following fork-local outcomes are true:

1. `02-Build-11` is executed: coverage threshold ratcheted to `63%` (actual minus 1), ratcheting policy documented.
2. `02-Build-12` is executed: PHPStan baseline reduced to justified entries only, remaining entries annotated.
3. All quality gates pass: `composer test`, `composer test:phpstan`, `composer test:phpcs`, `composer test:coverage`.
4. `02-Build-13` is executed: upstream PRs are prepared and submitted (see [Upstream PR strategy](#upstream-pr-strategy) below).
5. This roadmap and `roadmap-01.md` are updated to mark Phase 02 complete.

Phase 02 closes on PR *submission*, not on upstream response. Existing PRs (#160, #161) may be closed or superseded as housekeeping if there is no active review signal, but this is not a completion gate.

After these five items, Phase 02 is closed and Phase 03 begins.

### Remaining Phase 02 work

| Item | Description | Status |
|------|-------------|--------|
| `02-Build-11` | Coverage threshold ratcheting policy + raise to 63% | completed |
| `02-Build-12` | PHPStan baseline reduction (fix what's fixable without behavior changes) | completed |
| `02-Build-13` | Upstream PR preparation and submission | completed |

---

## Upstream PR strategy

### Goals
- Make it easy for HM to review and adopt the hardening work.
- Minimize the number of PRs (fewer is better for a likely low-bandwidth reviewer).
- Do not depend on acceptance. Fork proceeds regardless.
- Each PR must be independently mergeable and pass upstream CI.
- Fork-local artifacts (`.planning/`, `docs/audit/`) are excluded from all upstream PR branches. These are process infrastructure for the fork only.

### PR plan (4 PRs)

**PR A: Tooling and CI modernization**
Covers: `01-Build-01`, `02-Build-09`, `02-Build-10`, `02-Build-11`, `02-Build-12`
- `composer.json` / `composer.lock` changes (PHPStan upgrade, PHPCS deprecation suppression, coverage command)
- `phpstan.neon.dist` and `phpstan-baseline.neon` updates
- `phpunit.xml.dist` coverage whitelist
- `.github/workflows/php-standards.yml` (PHP 8.4 matrix row)
- `.github/workflows/test.yml` (coverage gate job)
- `CONTRIBUTING.md` (standards environment docs, coverage notes)
- `tests/phpunit/includes/check-coverage-threshold.php`
- `tests/wp-tests-config.php` one-line change

Why one PR: All infrastructure/tooling changes with no runtime behavior change. Easy to review as a unit, low risk.

**PR B: Guest author + post-insert hardening**
Covers: `01-Build-02`, `01-Build-03`
- `inc/class-users-controller.php` (username normalization, unique collision, filter cleanup)
- `inc/class-insert-post-handler.php` (failure action hook, defensive catch)
- Corresponding test additions in `tests/phpunit/test-rest-api-user-endpoint.php`, `tests/phpunit/test-post-saving.php`

Why separate from CLI: Different risk profile (security/observability vs. CLI workflow). Small surface area, easy to assess.

**PR C: CLI migration improvements**
Covers: `01-Build-04` (PHP parts), `02-Build-01` through `02-Build-08`
- `inc/cli/class-migrate-command.php` (batch pause hooks, post-type validation, cache reset, pacing)
- `tests/phpunit/test-cli.php` (all new CLI tests)
- `tests/phpunit/test-multisite.php` (multisite stabilization)
- `tests/phpunit/includes/testcase.php` (fixture additions)
- `README.md` (migration pacing docs)

Why separate from security: Larger change set, isolated to CLI path. All changes are test-covered and independently verifiable.

**PR D: Editor asset fix**
Covers: `01-Build-04` (JS part)
- `src/components/AuthorsSelect.tsx` (move side-effect out of render into useEffect, remove lodash.get)

Why separate: JS change requiring a different reviewer skillset and a build step. Small and self-contained.

### Timing
Submit all four PRs at the same time, after `02-Build-13` is complete. Reference them from a single umbrella issue that explains the overall hardening effort. This gives HM one place to understand the full scope while letting them merge PRs independently.

### Submission record (2026-03-07)
- Umbrella issue: https://github.com/humanmade/authorship/issues/166
- PR A (tooling/CI): https://github.com/humanmade/authorship/pull/162
- PR B (guest author + observability): https://github.com/humanmade/authorship/pull/163
- PR C (CLI migration improvements): https://github.com/humanmade/authorship/pull/164
- PR D (editor asset fix): https://github.com/humanmade/authorship/pull/165

### After submission
- Tag `codex/restack-audit-queue` at the submission point.
- Continue fork-local work on a new branch for Phase 03.
- If any PR is merged, rebase fork against upstream `develop`.
- If PRs are ignored for 30+ days, proceed without further follow-up.

### Residual risk note
- PR C (CLI migration improvements) is still the largest Build-13 package. If upstream review stalls or CI failures cluster in PR C, split it into C1 (pause hook contract + pacing) and C2 (post-type validation + multisite stabilization) without blocking fork-local phase closure.
- Build-12's zero-baseline runtime type hardening remains validated on the fork integration branch (`codex/restack-audit-queue`) with no intentional behavior changes; upstream packaging keeps this decoupled from phase completion.
- Coverage ratchet remains conservative at `63%`; continue incremental raises in Phase 04 only after stable CI windows.

---

## Phase 03: Frontend modernization (active)

### Goal
Replace the deprecated and accessibility-impaired frontend stack with current WordPress-ecosystem tooling. This is the highest-impact remaining work.

### Execution status (2026-03-07)
- `03-Build-01` executed on `codex/phase-03-build-01-toolchain`:
  - migrated to `@wordpress/scripts` with Node 20 baseline
  - added editor enqueue compatibility for `*.asset.php` metadata and CSS filename variants
  - introduced first JS unit tests for `AuthorsSelect` initialization behavior
- `03-Build-02` executed on `codex/phase-03-build-02-dnd-migration`:
  - replaced `react-sortable-hoc` with `@dnd-kit/core` + `@dnd-kit/sortable` + `@dnd-kit/utilities`
  - migrated multi-value sorting components while preserving legacy `onSortEnd({ oldIndex, newIndex })` callback shape
  - added JS tests for DND drag-end index mapping and removed legacy sortable-hoc prop assumptions
  - verified with `npm run lint:js`, `npm run test:js -- --ci`, `npm run build`, and `composer test`

### Scope

| Item | Current | Target | Why |
|------|---------|--------|-----|
| Node version | 16 (EOL) | 20 LTS | Security, ecosystem support |
| Build tooling | Webpack 4 + `@humanmade/webpack-helpers` | `@wordpress/scripts` (Webpack 5) | WP ecosystem standard, simpler config |
| React | 17 | 18 (WP 6.2+ ships it) | Concurrent features, ecosystem compat |
| `react-select` | v3 | v5 or replacement | Accessibility improvements |
| `react-sortable-hoc` | v2 (deprecated, unmaintained) | `@dnd-kit/core` + `@dnd-kit/sortable` | Maintained, accessible, lighter |
| State binding | `withSelect`/`withDispatch` HOCs | `useSelect`/`useDispatch` hooks | Modern WP data pattern, simpler code |
| `lodash` | Used for `get`, `isEqual` | Native JS / `@wordpress/compose` | WP core removing lodash dependency |
| `PluginPostStatusInfo` | From `@wordpress/edit-post` | From `@wordpress/editor` | Forward-compat with site editor |
| JS tests | None (lint+build only) | Jest + `@testing-library/react` | Verify async logic, prevent regressions |

### Build sequence (tentative, to be planned in detail at phase start)
1. Migrate build tooling to `@wordpress/scripts`, update Node to 20, verify build output parity.
2. Replace `react-sortable-hoc` with `@dnd-kit`. Verify drag-and-drop behavior.
3. Upgrade `react-select` to v5 (or evaluate `@wordpress/components` `FormTokenField` as replacement). Audit accessibility.
4. Convert HOCs to hooks (`useSelect`/`useDispatch`). Remove lodash.
5. Add JS unit tests for `AuthorsSelect` component (API fetch mocking, state init, sort, create guest author).
6. Move `PluginPostStatusInfo` import to `@wordpress/editor`.
7. Accessibility audit against WCAG 2.1 AA for the author selector.

### Upstream PR opportunity
PR packaging for Phase 03 will be decided at phase planning time. The frontend changes are self-contained (no PHP changes), which makes them clean to offer upstream. Whether this is one PR or several depends on the size and coherence of individual build items — to be determined when the build sequence is finalized.

---

## Phase 04: Test depth + PHPStan zero (future)

### Goal
Raise PHP test coverage and eliminate the PHPStan baseline entirely.

### Scope
- **Multisite test expansion**: Cross-site author queries, super admin capabilities on subsites, author archives on subsites. Currently only 1 multisite test.
- **Coverage ratcheting continuation**: Incremental threshold raises toward 80%+.
- **PHPStan baseline elimination**: Add `@var` annotations and type guards at WordPress API boundaries to resolve remaining mixed-type errors.
- **Custom post type coverage**: Deeper testing of CPT-specific capability mapping, especially with `map_meta_cap`.
- **Cache behavior tests**: Verify object cache invalidation on authorship changes.
- **Hook/filter coverage**: Test `authorship_default_author`, `authorship_supported_post_types`, and other public filters.

---

## Priority-triaged backlog

Items are ordered by impact and urgency. Phase assignments indicate when each item is expected to be addressed.

### P0 — Current phase (Phase 02 close-out)

| # | Item | Phase | Notes |
|---|------|-------|-------|
| 1 | Upstream PR preparation + submission | 02-Build-13 | 4 PRs, see strategy above |

### P1 — High impact, planned (Phase 03)

| # | Item | Notes |
|---|------|-------|
| 4 | Migrate build to `@wordpress/scripts` + Node 20 | Unblocks everything else in Phase 03 |
| 5 | Replace `react-sortable-hoc` with `@dnd-kit` | Deprecated dep, accessibility |
| 6 | Upgrade or replace `react-select` v3 | Accessibility, maintenance |
| 7 | Convert `withSelect`/`withDispatch` to hooks | Modern WP pattern |
| 8 | Remove `lodash` dependency | WP core direction |
| 9 | Add JS unit tests | Zero JS test coverage today |
| 10 | Move `PluginPostStatusInfo` to `@wordpress/editor` | Site editor forward-compat |
| 11 | Accessibility audit (WCAG 2.1 AA) | Documented gap in README |

### P2 — Important, next after P1 (Phase 04)

| # | Item | Notes |
|---|------|-------|
| 12 | Multisite test expansion | 1 test today, need 5-10 |
| 13 | Coverage ratcheting toward 80% | Incremental, ongoing |
| 14 | PHPStan baseline to zero | Type annotations at WP API boundaries |
| 15 | CPT capability test depth | `map_meta_cap` edge cases |
| 16 | Cache invalidation tests | Object cache interactions |
| 17 | Hook/filter contract tests | `authorship_default_author`, etc. |
| 18 | `post_author` field synchronization | Sync `post_author` with first attributed author on `set_authors()` to close theme/SEO/caching compatibility gap. See `.planning/known-gaps.md` §`post_author` field divergence. |
| 19 | Schema.org / JSON-LD author markup in HTML | Structured author data in page output for SEO. Competitors (PPA Pro, Molongui) already provide this. Independent of feed/Byline work. |

### P3 — Product features (future, no phase assigned)

| # | Item | Notes |
|---|------|-------|
| 20 | Classic editor support | README marks incomplete |
| 21 | Atom feed support | README marks incomplete |
| 22 | `init_taxonomy` "Mine" count performance | `get_term_by` on every `init`; cache or lazy-load |
| 23 | Quick edit author hide cleanup | `include => [0]` hack is fragile |
| 24 | Site builder implementation guidance | README aspirational item |
| 25 | REST API embedding depth tests | Embedded author data structure |

---

## Current status snapshot

- Active execution branch: `codex/phase-03-build-02-dnd-migration`
- Integration baseline branch: `codex/restack-audit-queue`
- Open upstream-facing PRs:
  - `#162` tooling/CI modernization
  - `#163` guest author + post-insert hardening
  - `#164` CLI migration improvements
  - `#165` editor asset fix
  - Umbrella issue: `#166`
- Legacy upstream-facing PRs:
  - `#160` previous integration PR (superseded in packaging, pending housekeeping decision)
  - `#161` previous focused Build-04 PR (superseded in packaging, pending housekeeping decision)
- Quality state: `composer test`, `composer test:phpstan`, `composer test:phpcs`, and `composer test:coverage` all green.
- 82 PHPUnit test methods, ~64% statement coverage (`64.03%`) with threshold ratcheted to `63%`.
- PHPStan state: baseline contains zero ignored errors.
- Phase 02 status: completion criteria met on 2026-03-07 (fork-local).
- Phase 03 status: active; Build-01 and Build-02 executed.

## What happens next

1. Open and monitor the Build-02 Phase-03 PR from `codex/phase-03-build-02-dnd-migration` after commit/push.
2. Plan and execute `03-Build-03`: evaluate `react-select` v5 upgrade versus replacement with `@wordpress/components` `FormTokenField`, with accessibility acceptance criteria and tests.
