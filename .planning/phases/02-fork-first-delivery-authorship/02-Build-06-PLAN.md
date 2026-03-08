---
phase: 02-fork-first-delivery-authorship
plan: 02-Build-06
type: build
wave: 1
depends_on: ["02-Build-05"]
files_modified:
  - "inc/cli/class-migrate-command.php"
  - "tests/phpunit/test-cli.php"
autonomous: true
user_setup: []
must_haves:
  truths:
    - "wp-authors migration handles missing and noisy post-type input deterministically."
  artifacts:
    - path: "inc/cli/class-migrate-command.php"
      provides: "Normalized post-type resolution with safe default fallback"
    - path: "tests/phpunit/test-cli.php"
      provides: "Regression coverage for missing and whitespace-heavy post-type inputs"
  key_links: []
---

<objective>
Harden wp-authors CLI post-type input handling so command behavior matches documented defaults and remains deterministic under noisy input.
</objective>

<status>
Executed on 2026-03-07 in fork integration branch context (`codex/restack-audit-queue`).

Delivered:
- Added normalized post-type resolver with default fallback to `post`.
- Ensured empty and whitespace-only post-type segments are ignored.
- Added regression tests for missing `post-type` input and trimmed comma-separated post-type lists.

Verification:
- `vendor/bin/phpunit --filter "TestCLI::testMigratePostTypeDefaultsToPostWhenNotProvided|TestCLI::testMigratePostTypeListIsTrimmedAndEmptyValuesIgnored|TestCLI::testMigratePostTypePost|TestCLI::testMigratePostTypePage"` passes.
- `composer test` passes (`154 tests, 382 assertions`).
</status>
