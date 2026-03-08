---
phase: 04-test-depth-and-ratcheting-authorship
plan: 04-Build-06
type: build
wave: 1
depends_on: ["04-test-depth-and-ratcheting-authorship/04-Build-05"]
files_modified:
  - "inc/namespace.php"
  - "tests/phpunit/test-wp-query.php"
  - "docs/audit/roadmap-global.md"
  - "docs/audit/roadmap-01.md"
autonomous: true
user_setup: []
must_haves:
  truths:
    - "Author-query rewrite callbacks must clean up after themselves within the request."
    - "Callback lifecycle cleanup must preserve restored query vars and subsequent-query isolation."
  artifacts:
    - path: "tests/phpunit/test-wp-query.php"
      provides: "Regression coverage for author-query callback cleanup and repeated-query stability"
    - path: "inc/namespace.php"
      provides: "Self-cleaning or named one-shot query-reset callback implementation"
  key_links: []
---

<objective>
Remove the leaked `posts_pre_query` callback accumulation from author-query rewriting while preserving current query-var restoration behavior.
</objective>

<tasks>

<task type="auto">
  <name>04-06-01 Add callback-lifecycle regression coverage</name>
  <files>tests/phpunit/test-wp-query.php</files>
  <action>
    - Add regression coverage proving repeated author-filtered queries do not grow the active callback list for `posts_pre_query`.
    - Keep the test outcome focused on stable public behavior plus filter-count hygiene rather than private implementation details alone.
  </action>
  <verify>The new regression test fails before remediation and passes after callback cleanup is implemented.</verify>
  <done>Callback-lifecycle regression coverage is present.</done>
</task>

<task type="auto">
  <name>04-06-02 Implement self-cleaning query-reset callback behavior</name>
  <files>inc/namespace.php</files>
  <action>
    - Replace the accumulating anonymous callback with a self-removing or named one-shot callback approach.
    - Preserve stored query-var restoration and leave subsequent unrelated queries unaffected.
  </action>
  <verify>Repeated author queries no longer accumulate callbacks, and query vars are still restored for callers after execution.</verify>
  <done>Author-query callback lifecycle cleanup is implemented.</done>
</task>

<task type="auto">
  <name>04-06-03 Re-verify query gates and update phase status</name>
  <files>docs/audit/roadmap-global.md, docs/audit/roadmap-01.md</files>
  <action>
    - Re-run the relevant verification suite for query behavior and static analysis.
    - Record Build-06 results and, if complete, return Phase 04 execution priority to Build-01 and Build-02.
  </action>
  <verify>`composer test:integration`, `WP_MULTISITE=1 composer test:integration`, `composer analyse:phpstan`, `composer analyse:psalm`, and `composer lint` pass with the callback cleanup in place.</verify>
  <done>Build-06 execution is documented and the blocker-remediation lane is closed.</done>
</task>

</tasks>

<status>
Planned on 2026-03-08.

Execution state:
- Not started (planning artifact only).
</status>
