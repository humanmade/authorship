---
phase: 03-frontend-modernization-authorship
plan: 03-Build-03
type: build
wave: 1
depends_on: ["03-frontend-modernization-authorship/03-Build-02"]
files_modified:
  - ".planning/phases/03-frontend-modernization-authorship/03-01-PLAN.md"
  - ".planning/phases/03-frontend-modernization-authorship/03-Build-03-PLAN.md"
  - "package.json"
  - "package-lock.json"
  - "src/components/SortableSelectContainer.tsx"
  - "src/components/AuthorsSelect.tsx"
  - "tests/js/components/authors-select-init.test.tsx"
  - "docs/audit/roadmap-global.md"
  - "docs/audit/roadmap-01.md"
autonomous: true
user_setup: []
must_haves:
  truths:
    - "Author selector migrates from `react-select` v3 to v5 with no regression in selected ID updates or reorder flow."
    - "Selector adapter uses explicit v5 typing interfaces to avoid deprecated alias dependence."
  artifacts:
    - path: "tests/js/components/authors-select-init.test.tsx"
      provides: "Selection and clearing behavior contract checks during dependency migration"
  key_links: []
---

<objective>
Upgrade the author selector from `react-select` v3 to v5 while preserving current editor behavior and callback contracts.
</objective>

<tasks>

<task type="auto">
  <name>03-03-01 Add selection-change behavior coverage (TDD baseline)</name>
  <files>tests/js/components/authors-select-init.test.tsx</files>
  <action>
    - Extend tests to verify selected author IDs are emitted when selection changes.
    - Verify clearing the selector emits an empty ID array.
  </action>
  <verify>`npm run test:js -- --ci` passes with the new selection-change assertions.</verify>
  <done>Selection-change behavior coverage is in place.</done>
</task>

<task type="auto">
  <name>03-03-02 Upgrade `react-select` to v5 and align adapter typings</name>
  <files>package.json, package-lock.json, src/components/SortableSelectContainer.tsx, src/components/AuthorsSelect.tsx</files>
  <action>
    - Upgrade `react-select` dependency and remove the legacy `@types/react-select` package.
    - Update selector component typings to use v5-native `AsyncCreatableProps` and `StylesConfig`/`MultiValue` interfaces.
    - Preserve existing option rendering, sort callbacks, and update callbacks.
  </action>
  <verify>JS lint/tests/build remain green after dependency and type updates.</verify>
  <done>Dependency and adapter migration is complete.</done>
</task>

<task type="auto">
  <name>03-03-03 Re-verify quality gates and update roadmap status</name>
  <files>.planning/phases/03-frontend-modernization-authorship/03-01-PLAN.md, docs/audit/roadmap-global.md, docs/audit/roadmap-01.md</files>
  <action>
    - Run frontend and plugin quality gates.
    - Record Build-03 execution status and next planned phase step.
  </action>
  <verify>`npm run lint:js`, `npm run test:js -- --ci`, `npm run build`, and `composer test` pass.</verify>
  <done>Build-03 is documented as executed with verification evidence.</done>
</task>

</tasks>

<status>
Started on 2026-03-07 on branch `codex/phase-03-build-03-react-select`.

Execution state:
- 03-03-01 completed.
- 03-03-02 completed.
- 03-03-03 completed.

Verification:
- `npm run lint:js` passed.
- `npm run test:js -- --ci` passed.
- `npm run build` passed.
- `composer test` passed.
</status>
