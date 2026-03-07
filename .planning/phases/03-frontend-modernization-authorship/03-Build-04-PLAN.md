---
phase: 03-frontend-modernization-authorship
plan: 03-Build-04
type: build
wave: 1
depends_on: ["03-frontend-modernization-authorship/03-Build-03"]
files_modified:
  - ".planning/phases/03-frontend-modernization-authorship/03-01-PLAN.md"
  - ".planning/phases/03-frontend-modernization-authorship/03-Build-04-PLAN.md"
  - "src/components/AuthorsSelect.tsx"
  - "tests/js/components/authors-select-init.test.tsx"
  - "tests/js/components/authors-select-connected.test.tsx"
  - "docs/audit/roadmap-global.md"
  - "docs/audit/roadmap-01.md"
autonomous: true
user_setup: []
must_haves:
  truths:
    - "`AuthorsSelect` data wiring migrates from `withSelect`/`withDispatch` to `useSelect`/`useDispatch` without behavioral regression."
    - "Lodash helper usage is removed from `AuthorsSelect` while preserving authorization-link and selection-initialization behavior."
  artifacts:
    - path: "tests/js/components/authors-select-connected.test.tsx"
      provides: "Hook wiring behavior tests for connected `AuthorsSelect` export"
  key_links: []
---

<objective>
Refactor `AuthorsSelect` to modern WordPress data hooks and remove local lodash usage while preserving editor behavior.
</objective>

<tasks>

<task type="auto">
  <name>03-04-01 Add hook-wiring characterization tests</name>
  <files>tests/js/components/authors-select-connected.test.tsx</files>
  <action>
    - Add tests that validate connected `AuthorsSelect` uses `useSelect`/`useDispatch` data and dispatch wiring.
    - Assert selector disabled state behavior when assign-action link is missing.
  </action>
  <verify>`npm run test:js -- --ci` passes with connected-component hook tests.</verify>
  <done>Connected-component hook wiring tests are in place.</done>
</task>

<task type="auto">
  <name>03-04-02 Replace HOCs/lodash in `AuthorsSelect`</name>
  <files>src/components/AuthorsSelect.tsx</files>
  <action>
    - Remove `compose`, `withSelect`, `withDispatch`, and lodash helpers from `AuthorsSelect`.
    - Use `useSelect` and `useDispatch` hooks to provide existing props into `AuthorsSelectBase`.
    - Keep existing async loading, create-option, and sort/update behavior contracts unchanged.
  </action>
  <verify>All JS tests and build remain green after refactor.</verify>
  <done>Hook migration and lodash removal are complete.</done>
</task>

<task type="auto">
  <name>03-04-03 Re-verify quality gates and update roadmap status</name>
  <files>.planning/phases/03-frontend-modernization-authorship/03-01-PLAN.md, docs/audit/roadmap-global.md, docs/audit/roadmap-01.md</files>
  <action>
    - Run frontend and plugin quality gates.
    - Record Build-04 execution status and next planned step.
  </action>
  <verify>`npm run lint:js`, `npm run test:js -- --ci`, `npm run build`, and `composer test` pass.</verify>
  <done>Build-04 is documented as executed with verification evidence.</done>
</task>

</tasks>

<status>
Started on 2026-03-07 on branch `codex/phase-03-build-04-hooks-lodash`.

Execution state:
- 03-04-01 completed.
- 03-04-02 completed.
- 03-04-03 completed.

Verification:
- `npm run lint:js` passed.
- `npm run test:js -- --ci` passed.
- `npm run build` passed.
- `composer test` passed.
</status>
