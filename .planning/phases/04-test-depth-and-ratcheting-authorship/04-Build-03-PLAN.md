---
phase: 04-test-depth-and-ratcheting-authorship
plan: 04-Build-03
type: build
wave: 1
depends_on: ["04-test-depth-and-ratcheting-authorship/04-01", "03-frontend-modernization-authorship/03-Build-12"]
files_modified:
  - "inc/cli/class-migrate-command.php"
  - "tests/phpunit/test-cli.php"
  - "docs/audit/roadmap-global.md"
  - "docs/audit/roadmap-01.md"
autonomous: true
user_setup: []
must_haves:
  truths:
    - "Write-mode `wp-authors` migration must not skip records when earlier batches mutate the result set."
    - "Dry-run accounting and write-mode traversal may diverge internally if the external behavior stays explicit and deterministic."
  artifacts:
    - path: "tests/phpunit/test-cli.php"
      provides: "Regression coverage for multi-batch write-mode migration"
    - path: "inc/cli/class-migrate-command.php"
      provides: "Deterministic traversal strategy for wp-authors migration"
  key_links: []
---

<objective>
Eliminate the write-mode `wp-authors` batching bug so migrations with more than one batch do not skip posts after earlier batches gain authorship terms.
</objective>

<tasks>

<task type="auto">
  <name>04-03-01 Add failing multi-batch write-mode regression coverage</name>
  <files>tests/phpunit/test-cli.php</files>
  <action>
    - Add a regression test that creates more than 100 unmigrated posts, runs `wp_authors` with `dry-run=false`, and asserts that every candidate post receives authorship data.
    - Keep the fixture deterministic enough to distinguish skipped posts from duplicate processing.
  </action>
  <verify>The new CLI test fails against the current paged write-mode implementation and passes after remediation.</verify>
  <done>Multi-batch write-mode regression coverage is present.</done>
</task>

<task type="auto">
  <name>04-03-02 Replace shrinking-result pagination with deterministic traversal</name>
  <files>inc/cli/class-migrate-command.php</files>
  <action>
    - Replace the current `paged` traversal for write mode with a strategy that is stable while authorship terms are being added.
    - Keep dry-run output semantics and batch pause hooks intact.
  </action>
  <verify>Write-mode migration processes all eligible posts exactly once without regressing pause hooks or dry-run behavior.</verify>
  <done>wp-authors traversal is deterministic under mutation.</done>
</task>

<task type="auto">
  <name>04-03-03 Re-verify CLI migration gates and publish status</name>
  <files>docs/audit/roadmap-global.md, docs/audit/roadmap-01.md</files>
  <action>
    - Re-run the CLI-focused integration coverage needed for this slice.
    - Record Build-03 results and queue Build-04 as the next remediation slice.
  </action>
  <verify>`composer test:integration`, `WP_MULTISITE=1 composer test:integration`, `composer analyse:phpstan`, `composer analyse:psalm`, and `composer lint` pass with the new migration regression test included.</verify>
  <done>Build-03 execution is documented and Build-04 remains next.</done>
</task>

</tasks>

<status>
Planned on 2026-03-08.

Execution state:
- Not started (planning artifact only).
</status>
