---
phase: 02-fork-first-delivery-authorship
plan: 02-Build-09
type: build
wave: 1
depends_on: ["02-Build-08"]
files_modified:
  - "composer.json"
  - "phpunit.xml.dist"
  - ".planning/phases/02-fork-first-delivery-authorship/02-01-PLAN.md"
  - "docs/audit/roadmap-01.md"
autonomous: true
user_setup: []
must_haves:
  truths:
    - "Coverage reporting is measurable and enforceable in local CI scripts without regressing current PHP compatibility constraints."
  artifacts:
    - path: "composer.json"
      provides: "A dedicated coverage script and documented invocation path"
    - path: "phpunit.xml.dist"
      provides: "Coverage target scope aligned to plugin source"
  key_links: []
---

<objective>
Add deterministic test coverage tooling and a baseline coverage gate for fork-first quality control.
</objective>

<status>
Queued on 2026-03-07 in fork integration branch context (`codex/restack-audit-queue`).

Planned scope:
- Enable a coverage driver in the local test environment (pcov or xdebug-backed).
- Add `composer test:coverage` and generate a baseline report for tracked source paths.
- Define and document an initial minimum threshold that can be raised incrementally.

Execution state:
- Not started.
</status>
