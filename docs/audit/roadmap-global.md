# Authorship Global Roadmap

Last updated: 2026-03-07 (America/Edmonton)

## Why this project exists
Authorship is a WordPress plugin for modern author attribution. Its core purpose is to let teams assign multiple authors (including guest authors) while treating REST API and WP-CLI workflows as first-class paths.

At this stage, the work is focused on making the plugin safer to operate and easier to maintain in a fork-first model:
- deterministic behavior in CLI migrations
- explicit quality gates and reproducible testing
- clear planning artifacts that do not depend on upstream merge acceptance

## Strategic direction (current)
1. Keep fork-local delivery unblocked.
2. Prefer TDD increments with narrow, verifiable build items.
3. Raise operational confidence before expanding feature surface.
4. Keep upstream PRs as optional value, not a dependency.

## History to date

### Phase 00: Foundation baseline (completed)
- Defined support matrix and quality-gate contract in [foundation-quality-baseline.md](/Users/danknauss/Documents/GitHub/authorship/docs/audit/foundation-quality-baseline.md).
- Established execution order: tooling and hardening before performance work.

### Phase 01: Audit + first hardening wave (completed)
- Built evidence-backed audit in [HM_WPCS_audit.md](/Users/danknauss/Documents/GitHub/authorship/docs/audit/HM_WPCS_audit.md).
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

### Phase 02: Fork-first delivery stabilization (active)
- Executed `02-Build-01` through `02-Build-08` on `codex/restack-audit-queue`.
- Main theme: deterministic and test-covered CLI migration behavior.
- Highlights:
  - pause filter/clamping coverage
  - pause-resolution hook contract and multi-batch behavior
  - multisite stabilization for migration tests
  - post-type input normalization, validation, and `post-type=any` compatibility
- Recent commits:
  - `eededb0` `Build-06: harden wp-authors post-type input handling`
  - `2a9d813` `Build-07: validate migration post types against registered types`
  - `bdbc20d` `Build-08: preserve wp-authors post-type any compatibility`

## Current status snapshot
- Branch: `codex/restack-audit-queue`
- Open upstream-facing PRs:
  - `#160` current branch integration PR
  - `#161` focused Build-04 PR
- Quality state: local `composer test`, `composer test:phpstan`, and `composer test:phpcs` are green in current branch context.
- Known process constraint: `wordpress-plugin-engineer` gate command names (`test:integration`, `analyse:*`, `lint`) are not defined in this repo; local equivalent gates are used.

## Backlog (human-readable)

### Ready now (next execution item)
1. `02-Build-09` Coverage baseline and gate (queued, not started)
   - Add a dedicated coverage command (`test:coverage`)
   - Enable a coverage driver in local test runtime
   - Produce a baseline report and set an initial enforceable threshold

### Next after Build-09 (recommended)
1. CI parity follow-through for coverage reporting
   - Ensure coverage command can run in CI environment, not only local
   - Keep threshold realistic and ratchet upward only with passing builds
2. Migration reliability deepening
   - Add additional edge-case tests for long-running migration scenarios and mixed post-type inputs
3. Documentation consolidation
   - Keep phase plans detailed, but maintain this global roadmap as the plain-language project source of truth

### Product backlog (already acknowledged in README)
1. Classic editor support completion
2. Full Atom feed support
3. Broader implementation guidance for site builders

## What happens next
Immediate next step is to execute `02-Build-09` and make coverage measurable and enforceable.

After that, continue iterative fork-first hardening with small, test-first increments and periodic roadmap refreshes in this file.
