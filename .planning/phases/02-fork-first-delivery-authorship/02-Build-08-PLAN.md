---
phase: 02-fork-first-delivery-authorship
plan: 02-Build-08
type: build
wave: 1
depends_on: ["02-Build-07"]
files_modified:
  - "inc/cli/class-migrate-command.php"
  - "tests/phpunit/test-cli.php"
autonomous: true
user_setup: []
must_haves:
  truths:
    - "wp-authors migration preserves explicit `post-type=any` behavior while keeping registration-aware filtering for concrete types."
  artifacts:
    - path: "inc/cli/class-migrate-command.php"
      provides: "Post-type resolver supports `any` sentinel alongside validated post-type lists"
    - path: "tests/phpunit/test-cli.php"
      provides: "Regression coverage for `post-type=any` migration behavior"
  key_links: []
---

<objective>
Preserve wp-authors CLI compatibility for `post-type=any` after registration-aware input hardening.
</objective>

<status>
Executed on 2026-03-07 in fork integration branch context (`codex/restack-audit-queue`).

Delivered:
- Added explicit handling for `post-type=any` in migration post-type resolution.
- Kept registered post-type filtering for concrete post-type lists.
- Added regression test coverage proving `post-type=any` still migrates supported types.

Verification:
- `vendor/bin/phpunit --filter "TestCLI::testMigratePostTypeAnyProcessesSupportedTypes|TestCLI::testMigratePostTypeFallsBackToPostWhenUnknownTypeProvided|TestCLI::testMigratePostTypeIncludesRegisteredCustomType"` passes.
- `composer test` passes (`157 tests, 391 assertions`).
</status>
