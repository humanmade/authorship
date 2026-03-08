---
phase: 02-fork-first-delivery-authorship
plan: 02-Build-04
type: build
wave: 1
depends_on: ["02-Build-03"]
files_modified:
  - "tests/phpunit/test-cli.php"
  - "README.md"
autonomous: true
user_setup: []
must_haves:
  truths:
    - "Migration pause hooks have deterministic multi-batch contract coverage and user-facing documentation."
  artifacts:
    - path: "tests/phpunit/test-cli.php"
      provides: "Deterministic assertion that pause-resolution events fire once per processed batch"
    - path: "README.md"
      provides: "Documented migration pacing controls and pause hook contracts"
  key_links: []
---

<objective>
Define and verify the migration pacing hook contract across multi-batch runs and document extension behavior for fork maintainers.
</objective>

<status>
Executed on 2026-03-06 in fork integration branch context (`codex/restack-audit-queue`).

Delivered:
- Added deterministic CLI test coverage asserting pause-resolution events fire per processed batch and expose cumulative processed counts.
- Documented migration pacing controls in `README.md`, including `--batch-pause`, filter, and action payloads.

Verification:
- `vendor/bin/phpunit --filter "TestCLI::testMigratePauseResolutionActionFiresPerProcessedBatch|TestCLI::testMigratePauseResolutionActionFiresForWpAuthors|TestCLI::testMigratePauseResolutionActionFiresForPpa"` passes.
- `composer test` passes (`151 tests, 372 assertions`).
</status>
