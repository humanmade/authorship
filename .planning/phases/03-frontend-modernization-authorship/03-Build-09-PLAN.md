---
phase: 03-frontend-modernization-authorship
plan: 03-Build-09
type: build
wave: 1
depends_on: ["03-frontend-modernization-authorship/03-Build-08"]
files_modified:
  - ".planning/phases/03-frontend-modernization-authorship/03-01-PLAN.md"
  - ".planning/phases/03-frontend-modernization-authorship/03-Build-09-PLAN.md"
  - "src/components/SortableSelectContainer.tsx"
  - "tests/js/components/sortable-select-container.test.tsx"
  - "docs/manual-testing-checklist.md"
  - "docs/audit/accessibility-author-selector.md"
  - "README.md"
  - "docs/audit/roadmap-global.md"
  - "docs/audit/roadmap-01.md"
autonomous: true
user_setup: []
must_haves:
  truths:
    - "Runtime editor accessibility behavior is validated with browser automation evidence."
    - "README accessibility language matches verified runtime behavior and known remaining gaps."
  artifacts:
    - path: "docs/audit/accessibility-author-selector.md"
      provides: "Runtime findings, resolved regressions, and remaining validation gaps"
  key_links: []
---

<objective>
Validate Build-08 accessibility changes in a live editor session, fix runtime regressions uncovered by that validation, and align README claims to the observed behavior.
</objective>

<tasks>

<task type="auto">
  <name>03-09-01 Run runtime accessibility validation in local editor session</name>
  <files>docs/audit/accessibility-author-selector.md, docs/manual-testing-checklist.md</files>
  <action>
    - Validate label/instructions presence, live status messaging, and keyboard reorder behavior on `single-site-local.local`.
    - Capture any runtime regressions from console/snapshot evidence.
  </action>
  <verify>Runtime evidence is documented for each target behavior and blockers are explicitly listed.</verify>
  <done>Runtime validation evidence captured with concrete outcomes.</done>
</task>

<task type="auto">
  <name>03-09-02 Fix runtime regression discovered during validation</name>
  <files>src/components/SortableSelectContainer.tsx, tests/js/components/sortable-select-container.test.tsx</files>
  <action>
    - Add missing DnD accessibility announcement handlers required at runtime.
    - Add/extend tests to cover the regression path.
  </action>
  <verify>`npm run test:js -- --ci` passes and runtime console no longer reports the missing `onDragOver` handler error.</verify>
  <done>Runtime drag-announcement regression is fixed and test-covered.</done>
</task>

<task type="auto">
  <name>03-09-03 Align README and roadmap status with validated accessibility behavior</name>
  <files>README.md, docs/audit/accessibility-author-selector.md, docs/audit/roadmap-global.md, docs/audit/roadmap-01.md, .planning/phases/03-frontend-modernization-authorship/03-01-PLAN.md</files>
  <action>
    - Update README accessibility claims to match validated runtime behavior.
    - Mark Build-09 executed and route next step to remaining AT matrix validation and role-path hardening.
  </action>
  <verify>`npm run lint:js`, `npm run test:js -- --ci`, `npm run build`, and `composer test` pass after updates.</verify>
  <done>Build-09 completion and next-step routing are fully documented.</done>
</task>

</tasks>

<status>
Started on 2026-03-07 on branch `codex/phase-03-build-09-runtime-a11y-validation`.

Execution state:
- 03-09-01 executed: runtime editor checks completed for label/instructions, live status messages, and keyboard reorder.
- 03-09-02 executed: fixed missing `onDragOver` accessibility announcement callback discovered during runtime validation.
- 03-09-03 executed: README and planning/roadmap docs aligned to validated behavior and remaining gaps.
</status>
