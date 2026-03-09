# Project State

## Current Position

Phase: 04 (Test Depth and Ratcheting)
Build: 05 of 08 complete — Build-06 queued
Status: In progress
Last activity: 2026-03-09

## Project Reference

See: `README.md`, `.planning/README.md`

**Core value:** Reliable multi-author and guest-author attribution for WordPress — every post can have multiple attributed authors, including guest authors backed by real user accounts.
**Current focus:** Phase 04 — close test coverage gaps, ratchet quality thresholds, harden edge cases discovered in prior phases.

## Accumulated Context

### Completed Phases

- **Phase 01 (Audit & Standards):** Foundation quality baseline, HM WPCS audit, tooling patch scaffolds. Complete.
- **Phase 02 (Fork Delivery):** 13 builds covering fork governance, CI pipeline, WPCS alignment, PHPStan integration, Psalm baseline, security hardening, WP-CLI reliability. Complete.
- **Phase 03 (Frontend Modernization):** 12 builds — React/TypeScript component hardening, dnd-kit drag-and-drop, VoiceOver accessibility audit passed. Complete.
- **Phase 04 Builds 01–05:** Multisite behavior coverage, coverage ratcheting, write-mode batching fix, stale PPA linked-user validation, implicit author-query post-type resolution. Complete.

### Phase 04 Remaining Builds

- **Build-06:** Author-query callback lifecycle cleanup (queued)
- **Build-07:** User-deletion authorship sync verification (planned)
- **Build-08:** Remaining quality gates and threshold ratcheting (planned)

### Key Decisions

- Fork-first workflow: `dknauss/authorship` `develop` is canonical. Upstream PRs minimized per `docs/fork-first-policy.md`.
- PHP 8.3 is the stable CI gate; PHP 8.4 is advisory/nightly.
- PHPStan level max with committed baseline.
- Psalm advisory baseline (not blocking).
- Coverage thresholds: PHP 63%, JS 80% lines/statements, 70% functions, 55% branches.
- `tests/wordpress/` contains a full WordPress install for integration testing — exclude from line counts.
- React frontend uses `@dnd-kit` for drag-and-drop and `react-select` for user selection.

### Blockers/Concerns

None.

## Session Continuity

Last session: 2026-03-09
Stopped at: Build-05 complete, Build-06 queued
Current metrics: See `docs/current-metrics.md`
