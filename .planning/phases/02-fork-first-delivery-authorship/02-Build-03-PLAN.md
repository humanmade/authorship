---
phase: 02-fork-first-delivery-authorship
plan: 02-Build-03
type: build
wave: 1
depends_on: ["02-Build-02"]
files_modified:
  - "inc/cli/class-migrate-command.php"
  - "tests/phpunit/test-cli.php"
  - "tests/phpunit/test-multisite.php"
autonomous: true
user_setup: []
must_haves:
  truths:
    - "Multisite author archive behavior remains stable after CLI PPA migration tests."
  artifacts:
    - path: "tests/phpunit/test-multisite.php"
      provides: "Deterministic multisite author archive test setup after blog switch"
    - path: "inc/cli/class-migrate-command.php"
      provides: "PPA fallback taxonomy registration without rewrite/query-var side effects"
  key_links: []
---

<objective>
Stabilize multisite test behavior by removing rewrite/query-var side effects from temporary `author` taxonomy registration and ensuring subsite permalink state is explicit in multisite tests.
</objective>

<status>
Executed on 2026-03-06 in fork integration branch context (`codex/restack-audit-queue`).

Delivered:
- Updated CLI PPA fallback taxonomy registration to disable rewrite and query var side effects.
- Updated CLI PPA pause-resolution test fixture taxonomy registration to match no-rewrite behavior.
- Updated multisite author archive test to set permalink structure after switching blogs.

Verification:
- `WP_MULTISITE=1 vendor/bin/phpunit --filter "TestCLI::testMigratePauseResolutionActionFiresForPpa|TestMultisite::testSuperAdminWithNoRoleOnSite" --exclude-group=ms-excluded` passes.
- `composer test` passes (`150 tests, 366 assertions`).
</status>
