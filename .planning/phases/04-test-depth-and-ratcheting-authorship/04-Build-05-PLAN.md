---
phase: 04-test-depth-and-ratcheting-authorship
plan: 04-Build-05
type: build
wave: 1
depends_on: ["04-test-depth-and-ratcheting-authorship/04-Build-04"]
files_modified:
  - "inc/namespace.php"
  - "tests/phpunit/test-wp-query.php"
  - "tests/phpunit/test-archive.php"
  - "README.md"
  - "docs/audit/roadmap-global.md"
  - "docs/audit/roadmap-01.md"
autonomous: true
user_setup: []
must_haves:
  truths:
    - "Author-filtered queries with omitted `post_type` must not silently collapse supported content to `post` only."
    - "Any change to implicit author-query semantics is public behavior and must be test-backed and documented."
  artifacts:
    - path: "tests/phpunit/test-wp-query.php"
      provides: "Regression coverage for omitted-`post_type` author queries"
    - path: "tests/phpunit/test-archive.php"
      provides: "Archive-level coverage for supported non-`post` content"
    - path: "inc/namespace.php"
      provides: "Updated implicit author-query post-type resolution"
  key_links: []
---

<objective>
Fix omitted-`post_type` author-query behavior so supported post types and archives are handled with Authorship semantics instead of defaulting to `post` only.
</objective>

<tasks>

<task type="auto">
  <name>04-05-01 Add failing coverage for implicit author-query post-type behavior</name>
  <files>tests/phpunit/test-wp-query.php, tests/phpunit/test-archive.php</files>
  <action>
    - Add direct `WP_Query` coverage where `author` or `author_name` is set and `post_type` is omitted.
    - Add archive coverage to confirm supported non-`post` content is not excluded by the implicit default.
  </action>
  <verify>The new tests fail against the current implementation and pass after semantics are updated.</verify>
  <done>Implicit post-type regression coverage is present.</done>
</task>

<task type="auto">
  <name>04-05-02 Implement explicit supported-type resolution for implicit author queries</name>
  <files>inc/namespace.php</files>
  <action>
    - Replace the current implicit `'post'` fallback for author-filtered queries with supported/queryable post-type resolution that matches plugin semantics.
    - Keep unsupported post types out of the rewritten author query path.
  </action>
  <verify>Supported CPTs and pages participate in implicit author queries while unsupported types still avoid incorrect taxonomy rewrites.</verify>
  <done>Implicit author-query post-type handling matches documented plugin semantics.</done>
</task>

<task type="auto">
  <name>04-05-03 Document behavior and re-verify query gates</name>
  <files>README.md, docs/audit/roadmap-global.md, docs/audit/roadmap-01.md</files>
  <action>
    - Update user-facing documentation if the final shipped behavior changes observable query semantics.
    - Re-run the relevant PHP integration and static-analysis gates and record Build-05 status.
  </action>
  <verify>`composer test:integration`, `WP_MULTISITE=1 composer test:integration`, `composer analyse:phpstan`, `composer analyse:psalm`, and `composer lint` pass, and any public-semantics changes are documented.</verify>
  <done>Build-05 execution is documented and Build-06 remains next.</done>
</task>

</tasks>

<status>
Planned on 2026-03-08.

Execution state:
- Explicit Build-05 execution not started.
- Groundwork commit `380ba2c` already changed `action_pre_get_posts()` for `post_type=any` and mixed supported/unsupported author queries; this build remains responsible for omitted-`post_type` semantics, regression coverage, and documentation.
</status>
