---
phase: 04-test-depth-and-ratcheting-authorship
plan: 04-Build-02
type: build
wave: 1
depends_on: ["04-test-depth-and-ratcheting-authorship/04-Build-01"]
files_modified:
  - "tests/phpunit/includes/check-coverage-threshold.php"
  - "package.json"
  - "psalm.xml"
  - "psalm-baseline.xml"
  - "composer.json"
  - "docs/audit/roadmap-global.md"
  - "docs/audit/roadmap-01.md"
autonomous: true
user_setup: []
must_haves:
  truths:
    - "Ratchets only advance when current baseline and full gate suite are stable."
    - "Psalm remains advisory in this phase while baseline debt is reduced incrementally."
  artifacts:
    - path: "tests/phpunit/includes/check-coverage-threshold.php"
      provides: "Updated PHP coverage threshold guard"
    - path: "psalm-baseline.xml"
      provides: "Reduced advisory baseline with documented delta"
  key_links: []
---

<objective>
Tighten quality signals by ratcheting coverage thresholds and reducing Psalm advisory baseline debt with rollback-safe criteria.
</objective>

<tasks>

<task type="auto">
  <name>04-02-01 Capture pre-ratchet quality metrics</name>
  <files>tests/phpunit/includes/check-coverage-threshold.php, package.json, psalm-baseline.xml</files>
  <action>
    - Record current PHPUnit statement coverage and JS coverage output.
    - Record Psalm advisory baseline count and category distribution.
  </action>
  <verify>Pre-ratchet metrics are documented and reproducible from commands.</verify>
  <done>Baseline metrics captured before threshold adjustments.</done>
</task>

<task type="auto">
  <name>04-02-02 Apply conservative coverage threshold ratchets</name>
  <files>tests/phpunit/includes/check-coverage-threshold.php, package.json</files>
  <action>
    - Raise PHP statement coverage threshold by a conservative increment aligned to actual coverage margin.
    - Raise JS thresholds conservatively only where sustained headroom exists.
  </action>
  <verify>`composer test:coverage` and `npm run test:js:coverage` pass under new thresholds.</verify>
  <done>Coverage thresholds ratcheted with green verification.</done>
</task>

<task type="auto">
  <name>04-02-03 Reduce Psalm advisory baseline and tighten reporting</name>
  <files>psalm.xml, psalm-baseline.xml, composer.json</files>
  <action>
    - Remove a targeted set of baseline entries through annotation/type-guard updates.
    - Keep Psalm in advisory mode while reducing baseline debt and preserving deterministic execution.
  </action>
  <verify>`composer analyse:psalm` and `composer analyse:phpstan` pass with reduced baseline entries.</verify>
  <done>Psalm advisory baseline reduced and reporting updated.</done>
</task>

<task type="auto">
  <name>04-02-04 Re-verify full gates and publish ratchet deltas</name>
  <files>docs/audit/roadmap-global.md, docs/audit/roadmap-01.md</files>
  <action>
    - Re-run full gate suite after ratchets.
    - Document before/after thresholds and baseline counts.
  </action>
  <verify>`composer test:integration`, `WP_MULTISITE=1 composer test:integration`, `composer analyse:phpstan`, `composer analyse:psalm`, `composer lint`, `composer test:coverage`, and `npm run test:js:coverage` pass.</verify>
  <done>Ratcheting outcomes documented with measurable deltas.</done>
</task>

</tasks>

<status>
Planned on 2026-03-08.

Execution state:
- Not started (planning artifact only).
</status>
