# Fork-First Policy

This repository (`dknauss/authorship`) uses a strict fork-first delivery model.

Effective date: 2026-03-08

## Source of truth

- The fork `develop` branch is the delivery source of truth.
- Phase completion is based on fork-local outcomes and green gates, not upstream merge timing.

## Normal development flow

1. Create a short-lived branch from fork `develop` (for example `codex/<scope>`).
2. Implement and verify changes in the fork.
3. Open a PR to fork `develop`.
4. Merge into fork `develop` only through PR workflow.

## Upstream contribution rules

- Upstream HM PRs are optional adoption paths, not delivery gates.
- Do not open one upstream PR per incremental build.
- Open upstream PRs only at explicit packaging checkpoints documented in roadmap plans.
- Keep the open upstream PR set intentionally bounded to the current approved package.

## Hygiene rules

- Close superseded upstream PRs promptly to prevent review-surface sprawl.
- When fork execution state materially changes, post concise status updates on the remaining upstream PR set.

## Current approved upstream set

- `#162` Tooling/CI modernization
- `#163` Guest author + observability hardening
- `#164` CLI migration reliability
- `#165` Editor asset fix
