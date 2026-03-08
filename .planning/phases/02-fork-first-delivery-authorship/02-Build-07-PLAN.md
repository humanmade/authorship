---
phase: 02-fork-first-delivery-authorship
plan: 02-Build-07
type: build
wave: 1
depends_on: ["02-Build-06"]
files_modified:
  - "inc/cli/class-migrate-command.php"
  - "tests/phpunit/test-cli.php"
autonomous: true
user_setup: []
must_haves:
  truths:
    - "wp-authors migration only targets registered post types and falls back deterministically when CLI input is invalid."
  artifacts:
    - path: "inc/cli/class-migrate-command.php"
      provides: "Post-type normalization that filters unknown types and preserves a safe default"
    - path: "tests/phpunit/test-cli.php"
      provides: "Regression coverage for unknown post-type fallback and supported custom post-type migration"
  key_links: []
---

<objective>
Harden wp-authors CLI post-type resolution by filtering to registered post types while preserving deterministic fallback behavior.
</objective>

<status>
Executed on 2026-03-07 in fork integration branch context (`codex/restack-audit-queue`).

Delivered:
- Filtered normalized `post-type` input to registered post types only.
- Preserved `post` fallback when all provided post types are invalid.
- Added CLI regression tests for unknown post-type fallback and supported custom post-type handling.

Verification:
- `vendor/bin/phpunit --filter "TestCLI::testMigratePostTypeFallsBackToPostWhenUnknownTypeProvided|TestCLI::testMigratePostTypeIncludesRegisteredCustomType|TestCLI::testMigratePostTypeDefaultsToPostWhenNotProvided|TestCLI::testMigratePostTypeListIsTrimmedAndEmptyValuesIgnored"` passes.
- `composer test` passes (`156 tests, 388 assertions`).
</status>
