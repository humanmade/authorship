---
phase: 02-fork-first-delivery-authorship
plan: 02-Build-11
type: build
wave: 1
depends_on: ["02-Build-10"]
files_modified:
  - "CONTRIBUTING.md"
  - "composer.json"
  - ".planning/phases/02-fork-first-delivery-authorship/02-01-PLAN.md"
  - "docs/audit/roadmap-01.md"
  - "docs/audit/roadmap-global.md"
autonomous: true
user_setup: []
must_haves:
  truths:
    - "Coverage thresholds are raised in controlled increments with explicit policy and no hidden regressions."
  artifacts:
    - path: "CONTRIBUTING.md"
      provides: "Threshold-ratcheting policy and update criteria"
    - path: "composer.json"
      provides: "Updated threshold value after policy-based ratchet"
  key_links: []
---

<objective>
Define and apply a threshold-ratcheting policy for coverage so gate strictness increases predictably over time.
</objective>

<status>
Executed on 2026-03-07 in fork integration branch context (`codex/restack-audit-queue`).

Delivered:
- Documented explicit threshold-ratcheting rules and rollback criteria in `CONTRIBUTING.md`.
- Raised coverage threshold from `60%` to `63%` in `composer.json`.
- Updated phase planning and roadmap docs to mark Build-11 complete and queue Build-12 next.

Verification:
- `composer test:coverage` passes with statement coverage `64.03%` (`566/884`) against the new `63.00%` threshold.
- `composer test` passes locally (`157 tests, 391 assertions`).
</status>
