---
phase: 03-frontend-modernization-authorship
plan: 03-Build-05
type: build
wave: 1
depends_on: ["03-frontend-modernization-authorship/03-Build-04"]
files_modified:
  - ".planning/phases/03-frontend-modernization-authorship/03-01-PLAN.md"
  - ".planning/phases/03-frontend-modernization-authorship/03-Build-05-PLAN.md"
  - "src/plugin.tsx"
  - "tests/js/components/authors-select-init.test.tsx"
  - "package.json"
  - "package-lock.json"
  - "docs/audit/roadmap-global.md"
  - "docs/audit/roadmap-01.md"
autonomous: true
user_setup: []
must_haves:
  truths:
    - "`PluginPostStatusInfo` import uses `@wordpress/editor` for forward compatibility."
    - "Guest-author creation success and error flows are covered by JS tests."
  artifacts:
    - path: "tests/js/components/authors-select-init.test.tsx"
      provides: "Guest-author create and error behavior checks"
  key_links: []
---

<objective>
Complete the next frontend modernization slice by migrating the plugin post-status import and expanding JS coverage for guest-author create/error behavior.
</objective>

<tasks>

<task type="auto">
  <name>03-05-01 Add guest-author creation/error test coverage (TDD baseline)</name>
  <files>tests/js/components/authors-select-init.test.tsx</files>
  <action>
    - Add test coverage for successful guest-author creation through `onCreateOption`.
    - Add test coverage for failed guest-author creation and error-notice callback behavior.
  </action>
  <verify>`npm run test:js -- --ci` passes with new guest-author flow tests.</verify>
  <done>Guest-author behavior tests are in place.</done>
</task>

<task type="auto">
  <name>03-05-02 Migrate PluginPostStatusInfo import path</name>
  <files>src/plugin.tsx, package.json, package-lock.json</files>
  <action>
    - Move `PluginPostStatusInfo` import from `@wordpress/edit-post` to `@wordpress/editor`.
    - Remove now-unused `@wordpress/edit-post` package dependency.
  </action>
  <verify>JS lint/tests/build pass after import and dependency updates.</verify>
  <done>Import-path migration is complete.</done>
</task>

<task type="auto">
  <name>03-05-03 Re-verify quality gates and update roadmap status</name>
  <files>.planning/phases/03-frontend-modernization-authorship/03-01-PLAN.md, docs/audit/roadmap-global.md, docs/audit/roadmap-01.md</files>
  <action>
    - Run frontend and plugin quality gates.
    - Record Build-05 execution status and next planned phase step.
  </action>
  <verify>`npm run lint:js`, `npm run test:js -- --ci`, `npm run build`, and `composer test` pass.</verify>
  <done>Build-05 is documented as executed with verification evidence.</done>
</task>

</tasks>

<status>
Started on 2026-03-07 on branch `codex/phase-03-build-05-editor-import-guest-tests`.

Execution state:
- 03-05-01 executed: guest-author create success/failure flows are covered in JS tests.
- 03-05-02 executed: `PluginPostStatusInfo` import moved to `@wordpress/editor`; `@wordpress/edit-post` removed.
- 03-05-03 executed: `npm run lint:js`, `npm run test:js -- --ci`, `npm run build`, and `composer test` passed after implementation.
</status>
