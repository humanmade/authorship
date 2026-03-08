---
phase: 04-test-depth-and-ratcheting-authorship
plan: 04-Build-01
type: build
wave: 1
depends_on: ["04-test-depth-and-ratcheting-authorship/04-01", "03-frontend-modernization-authorship/03-Build-12"]
files_modified:
  - "tests/phpunit/test-multisite.php"
  - "tests/phpunit/test-post-saving.php"
  - "tests/phpunit/test-rest-api-post-property.php"
  - "tests/phpunit/test-rest-api-user-endpoint.php"
  - "tests/phpunit/includes/testcase.php"
  - "inc/namespace.php"
  - "docs/audit/roadmap-global.md"
  - "docs/audit/roadmap-01.md"
autonomous: true
user_setup: []
must_haves:
  truths:
    - "Build-01 expands test depth and coverage signal without introducing intentional runtime behavior changes."
    - "Hook/filter tests validate contract behavior, not private implementation details."
  artifacts:
    - path: "tests/phpunit/test-multisite.php"
      provides: "Expanded multisite behavior regression coverage"
    - path: "tests/phpunit/test-post-saving.php"
      provides: "Public hook/filter contract assertions"
  key_links: []
---

<objective>
Expand PHPUnit coverage for multisite and public hooks/filters to raise confidence in cross-site behavior and extension contracts.
</objective>

<tasks>

<task type="auto">
  <name>04-01-01 Audit current multisite and hook/filter coverage gaps</name>
  <files>tests/phpunit/test-multisite.php, tests/phpunit/test-post-saving.php, tests/phpunit/test-rest-api-*.php</files>
  <action>
    - Catalog missing assertions for multisite author attribution and capability behavior.
    - Catalog missing tests for public filters such as `authorship_default_author` and `authorship_supported_post_types`.
  </action>
  <verify>Gap list is explicit and mapped to test files.</verify>
  <done>Coverage-gap map documented in implementation notes.</done>
</task>

<task type="auto">
  <name>04-01-02 Add multisite behavior regression tests</name>
  <files>tests/phpunit/test-multisite.php, tests/phpunit/includes/testcase.php</files>
  <action>
    - Add cross-site attribution read/write checks and role/capability-path assertions.
    - Add deterministic setup helpers needed for multisite fixtures.
  </action>
  <verify>`WP_MULTISITE=1 composer test:integration` passes with added multisite tests.</verify>
  <done>Multisite regression coverage expanded and green.</done>
</task>

<task type="auto">
  <name>04-01-03 Add public hook/filter contract coverage</name>
  <files>tests/phpunit/test-post-saving.php, tests/phpunit/test-rest-api-post-property.php, tests/phpunit/test-rest-api-user-endpoint.php</files>
  <action>
    - Add tests for default-author and supported-post-types filter behavior.
    - Verify filter contract behavior in REST and post-save flows where applicable.
  </action>
  <verify>`composer test:integration` passes and new tests assert contract-level outcomes.</verify>
  <done>Hook/filter contract tests added and passing.</done>
</task>

<task type="auto">
  <name>04-01-04 Re-verify gates and update roadmap status</name>
  <files>docs/audit/roadmap-global.md, docs/audit/roadmap-01.md</files>
  <action>
    - Re-run integration and static-analysis gates.
    - Record Build-01 results and queue Build-02.
  </action>
  <verify>`composer test:integration`, `WP_MULTISITE=1 composer test:integration`, `composer analyse:phpstan`, `composer analyse:psalm`, and `composer lint` pass.</verify>
  <done>Build-01 execution documented and Build-02 queued.</done>
</task>

</tasks>

<status>
Planned on 2026-03-08.

Execution state:
- Not started (planning artifact only).
</status>
