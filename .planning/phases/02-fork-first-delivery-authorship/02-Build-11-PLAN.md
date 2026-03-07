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
Queued on 2026-03-07 in fork integration branch context (`codex/restack-audit-queue`).

Planned scope:
- Document ratcheting rules (when to raise, by how much, and rollback criteria).
- Raise baseline threshold from 60% to the next policy-approved value only after CI parity is stable.
- Record the threshold change and rationale in roadmap docs.

Execution state:
- Not started.
</status>
