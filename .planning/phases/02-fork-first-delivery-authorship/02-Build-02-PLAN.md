---
phase: 02-fork-first-delivery-authorship
plan: 02-Build-02
type: build
wave: 1
depends_on: ["02-Build-01"]
files_modified:
  - "inc/cli/class-migrate-command.php"
  - "tests/phpunit/test-cli.php"
autonomous: true
user_setup: []
must_haves:
  truths:
    - "Resolved migration pause values are observable through deterministic hook payloads."
  artifacts:
    - path: "inc/cli/class-migrate-command.php"
      provides: "Pause-resolution action hook for migration observability"
    - path: "tests/phpunit/test-cli.php"
      provides: "Deterministic assertions for pause hook payloads in wp-authors and ppa flows"
  key_links: []
---

<objective>
Expose and verify resolved migration batch pause values via a stable action hook so pacing behavior can be asserted without timing-based flakiness.
</objective>

<status>
Executed on 2026-03-06 in fork integration branch context (`codex/restack-audit-queue`).

Delivered:
- Added `authorship_migrate_batch_pause_resolved` action dispatch in `Migrate_Command::pause_between_batches()`.
- Added `testMigratePauseResolutionActionFiresForWpAuthors()` and `testMigratePauseResolutionActionFiresForPpa()` in `tests/phpunit/test-cli.php`.
- Added fixture cleanup to restore temporary `author` taxonomy state after PPA pause-resolution tests.

Verification:
- `vendor/bin/phpunit --filter "TestCLI::testMigratePauseResolutionActionFiresForWpAuthors|TestCLI::testMigratePauseResolutionActionFiresForPpa|TestWPQuery::testQueriedObjectIsRetainedAfterQueryingForAuthor|TestWPQuery::testQueryForInvalidAuthorReturnsNoResults|TestWPQuery::testQueryOverridesDoNotAffectPostTypesThatDoNotSupportAuthor"` passes.
- `WP_MULTISITE=1 vendor/bin/phpunit --filter "TestCLI::testMigratePauseResolutionActionFiresForPpa" --exclude-group=ms-excluded` passes.
- `composer test` remains blocked by pre-existing multisite failure: `Authorship\Tests\TestMultisite::testSuperAdminWithNoRoleOnSite` (also reproducible on clean `HEAD` before this build change).
</status>
