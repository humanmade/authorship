---
phase: 03-frontend-modernization-authorship
plan: 03-Build-02
type: build
wave: 1
depends_on: ["03-frontend-modernization-authorship/03-Build-01"]
files_modified:
  - ".planning/phases/03-frontend-modernization-authorship/03-01-PLAN.md"
  - ".planning/phases/03-frontend-modernization-authorship/03-Build-02-PLAN.md"
  - "package.json"
  - "package-lock.json"
  - "src/components/SortableSelectContainer.tsx"
  - "src/components/SortableMultiValueElement.tsx"
  - "src/components/AuthorsSelect.tsx"
  - "tests/js/components/*.test.tsx"
  - "docs/audit/roadmap-global.md"
autonomous: true
user_setup: []
must_haves:
  truths:
    - "Drag-and-drop author reordering migrates from deprecated `react-sortable-hoc` to maintained `@dnd-kit` without user-visible regression."
    - "Reorder behavior is guarded by JS tests before and after implementation changes."
  artifacts:
    - path: "tests/js/components/authors-select-init.test.tsx"
      provides: "Characterization tests for reorder behavior used as migration guardrail"
  key_links: []
---

<objective>
Replace `react-sortable-hoc` with `@dnd-kit` in the author selector while preserving reorder behavior and keeping existing initialization semantics stable.
</objective>

<tasks>

<task type="auto">
  <name>03-02-01 Add reorder characterization tests (TDD red/green baseline)</name>
  <files>tests/js/components/authors-select-init.test.tsx</files>
  <action>
    - Add assertions for `onSortEnd` reorder behavior and emitted author ID ordering.
    - Ensure tests capture expected behavior independently of implementation library.
  </action>
  <verify>`npm run test:js -- --ci` passes with reorder assertions included.</verify>
  <done>Reorder behavior characterization tests are in place.</done>
</task>

<task type="auto">
  <name>03-02-02 Migrate selector drag-and-drop to @dnd-kit</name>
  <files>src/components/SortableSelectContainer.tsx, src/components/SortableMultiValueElement.tsx, package.json, package-lock.json</files>
  <action>
    - Remove `react-sortable-hoc` integration points and adopt `@dnd-kit` primitives.
    - Keep multi-value rendering and remove-control UX behavior intact.
    - Preserve keyboard/mouse reorder semantics and callback payload shape expected by `AuthorsSelect`.
  </action>
  <verify>Reorder tests remain green and manual selector interactions still work in editor context.</verify>
  <done>DND migration implementation is complete.</done>
</task>

<task type="auto">
  <name>03-02-03 Re-verify frontend and plugin quality gates</name>
  <files>package.json, package-lock.json, docs/audit/roadmap-global.md</files>
  <action>
    - Run JS gates and core plugin gate suite.
    - Update roadmap status for Build-02 execution outcome.
  </action>
  <verify>`npm run lint:js`, `npm run test:js -- --ci`, `npm run build`, and `composer test` pass.</verify>
  <done>Build-02 execution is documented and verified.</done>
</task>

</tasks>

<status>
Started on 2026-03-07 on branch `codex/phase-03-build-02-dnd-migration`.

Execution state:
- 03-02-01 in progress.
- 03-02-02 pending.
- 03-02-03 pending.
</status>
