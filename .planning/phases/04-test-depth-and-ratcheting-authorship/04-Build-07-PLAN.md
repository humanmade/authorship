---
phase: 04-test-depth-and-ratcheting-authorship
plan: 04-Build-07
type: build
wave: 1
depends_on: ["04-test-depth-and-ratcheting-authorship/04-Build-06"]
files_modified:
  - "inc/namespace.php"
  - "tests/phpunit/test-user-deletion.php"
  - "tests/phpunit/test-multisite.php"
  - "docs/audit/roadmap-global.md"
  - "docs/audit/roadmap-01.md"
autonomous: true
user_setup: []
must_haves:
  truths:
    - "Deleting a WordPress user must synchronize authorship taxonomy data: remove the deleted author or replace them with the reassigned user."
    - "Multisite user deletion (`wpmu_delete_user`) must walk all network sites and sync authorship on each."
    - "Orphaned taxonomy terms for deleted users must be cleaned up when their post count reaches zero."
    - "Duplicate author prevention: if the reassign target is already a co-author on a post, the deleted author is simply removed without creating a duplicate entry."
  artifacts:
    - path: "inc/namespace.php"
      provides: "Verified and hardened `action_deleted_user` and `sync_deleted_user_authorship_for_current_site` implementations"
    - path: "tests/phpunit/test-user-deletion.php"
      provides: "Expanded user-deletion lifecycle regression coverage"
    - path: "tests/phpunit/test-multisite.php"
      provides: "Multisite network-user deletion authorship sync coverage"
  key_links: []
---

<objective>
Verify, harden, and expand test coverage for the user-deletion authorship synchronization handler that was introduced in the Phase 04 groundwork commit. Ensure deletion behavior is correct for single-site and multisite, with and without reassignment, including edge cases around guest authors, sole-author posts, and large post sets.
</objective>

<context>
The `action_deleted_user` handler and `sync_deleted_user_authorship_for_current_site` helper were committed as part of the Phase 04 groundwork (`380ba2c`). Initial test coverage exists in `test-user-deletion.php` (3 tests) and `test-multisite.php` (1 deletion test). This build formalizes verification, closes coverage gaps, and ensures the implementation passes all static analysis and integration gates.
</context>

<tasks>

<task type="auto">
  <name>04-07-01 Verify existing user-deletion tests pass all gates</name>
  <files>tests/phpunit/test-user-deletion.php, tests/phpunit/test-multisite.php</files>
  <action>
    - Run `composer test:integration` and `WP_MULTISITE=1 composer test:integration` to confirm the 4 existing user-deletion tests pass.
    - Run `composer analyse:phpstan` and `composer analyse:psalm` to confirm the new handler has no static analysis regressions.
    - Record pass/fail baseline before expanding coverage.
  </action>
  <verify>All 4 existing deletion tests pass. PHPStan and Psalm report no new issues against `action_deleted_user` or `sync_deleted_user_authorship_for_current_site`.</verify>
  <done>Existing user-deletion implementation confirmed green against all gates.</done>
</task>

<task type="auto">
  <name>04-07-02 Add sole-author deletion coverage</name>
  <files>tests/phpunit/test-user-deletion.php</files>
  <action>
    - Add a test where the deleted user is the sole author on a post with no reassignment target.
    - Assert the post's authorship term list is empty after deletion and the orphaned term is cleaned up.
    - Add a test where the deleted user is the sole author but reassignment is provided.
    - Assert the reassigned user becomes the sole author.
  </action>
  <verify>Sole-author edge cases pass deterministically.</verify>
  <done>Sole-author deletion paths are covered.</done>
</task>

<task type="auto">
  <name>04-07-03 Add guest-author deletion coverage</name>
  <files>tests/phpunit/test-user-deletion.php</files>
  <action>
    - Add a test where the deleted user has the `guest-author` role.
    - Assert the guest author's authorship terms are cleaned up identically to regular users.
    - Confirm guest-author deletion does not leave orphaned user rows or taxonomy terms.
  </action>
  <verify>Guest-author deletion behaves identically to regular-user deletion for authorship purposes.</verify>
  <done>Guest-author deletion lifecycle is covered.</done>
</task>

<task type="auto">
  <name>04-07-04 Add invalid-reassignment-target coverage</name>
  <files>tests/phpunit/test-user-deletion.php</files>
  <action>
    - Add a test where the reassignment target is a nonexistent user ID.
    - Assert the handler treats this as a no-reassignment case (removal, not replacement).
    - Add a test where the reassignment target equals the deleted user ID.
    - Assert the handler treats this as a no-reassignment case.
  </action>
  <verify>Invalid and self-referential reassignment targets fall back to removal behavior.</verify>
  <done>Invalid-reassignment edge cases are covered.</done>
</task>

<task type="auto">
  <name>04-07-05 Add multisite cross-site deletion depth coverage</name>
  <files>tests/phpunit/test-multisite.php</files>
  <action>
    - Add a test where a network user is attributed on multiple subsites and is deleted with reassignment.
    - Assert authorship is updated on all subsites, not just the current site.
    - Add a test where the authorship taxonomy is not registered on a particular subsite.
    - Assert the handler skips that site gracefully without errors.
  </action>
  <verify>Multisite deletion correctly walks all sites and handles missing-taxonomy subsites.</verify>
  <done>Multisite deletion depth coverage is in place.</done>
</task>

<task type="auto">
  <name>04-07-06 Verify gates and update roadmap</name>
  <files>docs/audit/roadmap-global.md, docs/audit/roadmap-01.md</files>
  <action>
    - Re-run full gate suite: `composer test:integration`, `WP_MULTISITE=1 composer test:integration`, `composer analyse:phpstan`, `composer analyse:psalm`, `composer lint`.
    - Record Build-07 results in roadmap docs.
    - Return execution priority to Build-01 and Build-02 if all blocker-lane builds (03-07) are complete.
  </action>
  <verify>All gates pass with expanded user-deletion coverage included.</verify>
  <done>Build-07 execution documented. Blocker-remediation lane closed if Build-03 through Build-07 are all green.</done>
</task>

</tasks>

<status>
Planned on 2026-03-08.

Execution state:
- Not started (planning artifact only).
- Implementation exists in groundwork commit 380ba2c; this build adds verification depth and coverage hardening.
</status>
