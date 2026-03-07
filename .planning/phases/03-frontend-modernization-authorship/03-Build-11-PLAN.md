---
phase: 03-frontend-modernization-authorship
plan: 03-Build-11
type: build
wave: 1
depends_on: ["03-frontend-modernization-authorship/03-Build-10"]
files_modified:
  - ".planning/phases/03-frontend-modernization-authorship/03-01-PLAN.md"
  - ".planning/phases/03-frontend-modernization-authorship/03-Build-11-PLAN.md"
  - ".gitignore"
  - "composer.json"
  - "composer.lock"
  - "package.json"
  - "tests/phpunit/includes/bootstrap.php"
  - "tests/phpunit/test-admin.php"
  - "psalm.xml"
  - "psalm-baseline.xml"
  - "tools/psalm.sh"
  - "docs/audit/roadmap-global.md"
  - "docs/audit/roadmap-01.md"
autonomous: true
user_setup: []
must_haves:
  truths:
    - "Coverage gates must tighten without breaking deterministic local/CI execution."
    - "Psalm in this local stack is advisory-first with baseline and explicit runtime guardrails."
  artifacts:
    - path: "tests/phpunit/test-admin.php"
      provides: "Additional PHPUnit coverage for admin-area behavior"
    - path: "psalm.xml"
      provides: "Fork-local Psalm advisory config with baseline linkage"
    - path: "tools/psalm.sh"
      provides: "Stable Psalm runtime wrapper for host PHP compatibility"
  key_links: []
---

<objective>
Increase automated coverage depth and add a fork-local Psalm advisory gate that runs predictably in the local WordPress plugin environment.
</objective>

<tasks>

<task type="auto">
  <name>03-11-01 Expand PHPUnit coverage for admin flows</name>
  <files>tests/phpunit/test-admin.php, tests/phpunit/includes/bootstrap.php</files>
  <action>
    - Add new admin-focused PHPUnit tests for required-field error filtering, post-column replacement, column hook registration, and author-column rendering.
    - Load `inc/admin.php` in PHPUnit bootstrap to keep test files side-effect free under PHPCS.
  </action>
  <verify>`composer test:integration` and `WP_MULTISITE=1 composer test:integration` pass with new tests included.</verify>
  <done>Admin-area PHPUnit coverage expansion is implemented and passing.</done>
</task>

<task type="auto">
  <name>03-11-02 Add and verify JS coverage threshold gate</name>
  <files>package.json</files>
  <action>
    - Add `npm run test:js:coverage` with explicit global threshold constraints.
    - Verify current selector test suite satisfies thresholds.
  </action>
  <verify>`npm run test:js:coverage` passes with thresholds enforced.</verify>
  <done>JS coverage threshold command exists and is green.</done>
</task>

<task type="auto">
  <name>03-11-03 Add Psalm advisory gate with runtime wrapper</name>
  <files>composer.json, composer.lock, psalm.xml, psalm-baseline.xml, tools/psalm.sh</files>
  <action>
    - Add Psalm as a dev dependency and create fork-local config/baseline files.
    - Add `composer analyse:psalm` and baseline refresh script, routed through a wrapper that avoids unsupported PHP runtimes.
    - Force Psalm single-thread execution to avoid observed ParseTree timeout-noise in this environment.
  </action>
  <verify>`composer analyse:psalm` runs cleanly (baseline-backed advisory mode) and `composer analyse:phpstan` still passes.</verify>
  <done>Psalm advisory workflow is added and stable for local execution.</done>
</task>

<task type="auto">
  <name>03-11-04 Update Phase 03 and roadmap status</name>
  <files>.planning/phases/03-frontend-modernization-authorship/03-01-PLAN.md, docs/audit/roadmap-global.md, docs/audit/roadmap-01.md</files>
  <action>
    - Record Build-11 execution details and branch association.
    - Shift next queued work to Build-12 for host-native AT matrix capture.
  </action>
  <verify>Phase status and roadmap next-step language match executed Build-11 scope.</verify>
  <done>Planning and roadmap documents are aligned to executed state.</done>
</task>

</tasks>

<status>
Started on 2026-03-07 on branch `codex/phase-03-build-11-coverage-psalm-advisory`.

Execution state:
- 03-11-01 executed: added admin-area PHPUnit tests and bootstrap include for `inc/admin.php`.
- 03-11-02 executed: added `test:js:coverage` and validated threshold compliance.
- 03-11-03 executed: added Psalm advisory scripts/config/baseline with runtime wrapper.
- 03-11-04 executed: Phase 03 and roadmap docs updated to queue Build-12 as the next step.
</status>
