# Phase 01–02 Execution Roadmap for Authorship

See also: [Global Roadmap](roadmap-global.md) for project-wide purpose, history, backlog, and next-step narrative.

## Current state
- `00-01` foundation baseline defines support matrix and gate assumptions in `docs/audit/foundation-quality-baseline.md`.
- `01-01` exists as the initial planning stub at `.planning/phases/01-audit-roadmap-authorship/01-01-PLAN.md`.
- `01-02` has a repo-grounded audit deliverable in `docs/audit/HM_WPCS_audit.md`.
- `01-Build-01` through `01-Build-04` executed on `codex/restack-audit-queue`.
- `02-Build-01` through `02-Build-10` executed with deterministic CLI migration pause coverage, multisite stabilization, pacing hook contract hardening, registration-aware post-type input hardening, a baseline coverage gate, and CI parity.
- Focused upstream PR for Build-04 is open: `https://github.com/humanmade/authorship/pull/161`.

## What Phase 01 established
- Root standards configuration already exists and is not missing.
- Build queue sequencing (tooling -> hardening -> observability -> performance) is implemented in code.
- Current fork-local test/lint gates are green on the integration branch.

## Phase 01 Build queue status
- `01-Build-01`: completed
- `01-Build-02`: completed
- `01-Build-03`: completed
- `01-Build-04`: completed

## Phase 02 Build queue status
- `02-Build-01`: completed
- `02-Build-02`: completed
- `02-Build-03`: completed
- `02-Build-04`: completed
- `02-Build-05`: completed
- `02-Build-06`: completed
- `02-Build-07`: completed
- `02-Build-08`: completed
- `02-Build-09`: completed
- `02-Build-10`: completed
- `02-Build-11`: queued — coverage threshold ratcheting policy, raise to 63%
- `02-Build-12`: queued — PHPStan baseline reduction (annotation-level fixes only)
- `02-Build-13`: queued — upstream PR preparation and submission (4 PRs)

## Phase 02 completion criteria

Phase 02 is done when these fork-local outcomes are all true:
1. `02-Build-11` executed: threshold ratcheted, policy documented.
2. `02-Build-12` executed: baseline reduced to justified entries only.
3. `02-Build-13` executed: 4 upstream PRs submitted.
4. All quality gates pass.
5. Global roadmap updated, Phase 02 marked complete.

Phase closes on PR *submission*, not on upstream response. Existing #160/#161 may be closed as housekeeping if no active review signal, but this is not a gate.

Residual risk note: PR C (CLI migration improvements) remains the broadest Build-13 PR. Split into C1/C2 if upstream review or CI signal indicates excessive scope.

## Next step
- Execute `02-Build-11`.
