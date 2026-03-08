---
phase: 02-fork-first-delivery-authorship
plan: 02-Build-10
type: build
wave: 1
depends_on: ["02-Build-09"]
files_modified:
  - ".github/workflows/test.yml"
  - ".planning/phases/02-fork-first-delivery-authorship/02-01-PLAN.md"
  - "docs/audit/roadmap-01.md"
  - "docs/audit/roadmap-global.md"
autonomous: true
user_setup: []
must_haves:
  truths:
    - "Coverage enforcement runs consistently in CI and local development with matching command behavior."
  artifacts:
    - path: ".github/workflows/test.yml"
      provides: "Coverage gate execution in CI unit-test workflow"
  key_links: []
---

<objective>
Extend coverage-gate adoption into CI and document threshold-ratcheting policy for ongoing fork-first quality control.
</objective>

<status>
Executed on 2026-03-07 in fork integration branch context (`codex/restack-audit-queue`).

Delivered:
- Added a dedicated `coverage` job to `.github/workflows/test.yml` on WP 6.6 / PHP 8.3.
- The CI coverage job runs `composer test:coverage` with MySQL startup and caches coverage artifacts.
- Preserved local/CI command parity by using the same composer script contract.

Verification:
- `composer test:coverage` passes locally (statement coverage `64.03%`, threshold `60.00%`).
- `composer test` passes locally (`157 tests, 391 assertions`).
</status>
