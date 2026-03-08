---
phase: 04-test-depth-and-ratcheting-authorship
plan: 04-Build-04
type: build
wave: 1
depends_on: ["04-test-depth-and-ratcheting-authorship/04-Build-03"]
files_modified:
  - "inc/cli/class-migrate-command.php"
  - "tests/phpunit/test-cli.php"
  - "docs/audit/roadmap-global.md"
  - "docs/audit/roadmap-01.md"
autonomous: true
user_setup: []
must_haves:
  truths:
    - "Stale PublishPress `user_id` term-meta mappings must not abort the whole migration."
    - "Invalid linked-user metadata must fall through to existing login/slug resolution before guest-user creation is considered."
  artifacts:
    - path: "tests/phpunit/test-cli.php"
      provides: "Regression coverage for stale linked-user and fallback resolution paths"
    - path: "inc/cli/class-migrate-command.php"
      provides: "Validated linked-user lookup with safe fallback behavior"
  key_links: []
---

<objective>
Harden PublishPress migration so stale linked-user metadata is validated and safely falls back instead of aborting the entire run.
</objective>

<tasks>

<task type="auto">
  <name>04-04-01 Add stale-linked-user regression fixtures</name>
  <files>tests/phpunit/test-cli.php</files>
  <action>
    - Add a failing test where PublishPress `user_id` term meta points to a deleted or nonexistent user.
    - Assert that migration falls back to login/slug matching or guest-author creation rather than surfacing an unrecoverable invalid-user exception.
  </action>
  <verify>The new regression test fails before remediation and passes after linked-user validation is added.</verify>
  <done>Stale linked-user regression coverage is present.</done>
</task>

<task type="auto">
  <name>04-04-02 Validate linked-user metadata before reuse</name>
  <files>inc/cli/class-migrate-command.php</files>
  <action>
    - Guard `user_id` term-meta reuse with `get_userdata()` or equivalent existence checks.
    - Preserve the existing login-first, slug-second, create-guest-author-last resolution order once invalid metadata is rejected.
  </action>
  <verify>PPA migration no longer aborts on stale linked-user metadata and still reuses legitimate existing users.</verify>
  <done>Linked-user validation and fallback logic are implemented.</done>
</task>

<task type="auto">
  <name>04-04-03 Re-verify CLI migration gates and update roadmap state</name>
  <files>docs/audit/roadmap-global.md, docs/audit/roadmap-01.md</files>
  <action>
    - Re-run the CLI-focused verification suite after stale-user handling changes.
    - Record Build-04 execution results and queue Build-05.
  </action>
  <verify>`composer test:integration`, `WP_MULTISITE=1 composer test:integration`, `composer analyse:phpstan`, `composer analyse:psalm`, and `composer lint` pass.</verify>
  <done>Build-04 execution is documented and Build-05 remains next.</done>
</task>

</tasks>

<status>
Planned on 2026-03-08.

Execution state:
- Explicit Build-04 execution not started.
- Groundwork commit `380ba2c` already changed `get_ppa_user_id()` to prefer login-first, slug-second fallback; this build remains responsible for stale linked-user validation and gate re-verification.
</status>
