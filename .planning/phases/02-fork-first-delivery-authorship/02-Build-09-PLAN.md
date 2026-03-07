---
phase: 02-fork-first-delivery-authorship
plan: 02-Build-09
type: build
wave: 1
depends_on: ["02-Build-08"]
files_modified:
  - "composer.json"
  - "phpunit.xml.dist"
  - "tests/phpunit/includes/check-coverage-threshold.php"
  - "CONTRIBUTING.md"
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
Executed on 2026-03-07 in fork integration branch context (`codex/restack-audit-queue`).

Delivered:
- Added `composer test:coverage` using `phpdbg` as the coverage driver.
- Added a deterministic statement-coverage threshold gate via `tests/phpunit/includes/check-coverage-threshold.php`.
- Expanded coverage scope to include `plugin.php` plus `inc/` via `phpunit.xml.dist`.
- Documented the new coverage workflow in `CONTRIBUTING.md`.

Verification:
- `composer test:coverage` passes with statement coverage `64.03%` (`566/884`) against a `60.00%` threshold.
- `composer test` passes (`157 tests, 391 assertions`).
</status>
