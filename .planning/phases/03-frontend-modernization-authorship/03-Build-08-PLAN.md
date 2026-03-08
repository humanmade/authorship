---
phase: 03-frontend-modernization-authorship
plan: 03-Build-08
type: build
wave: 1
depends_on: ["03-frontend-modernization-authorship/03-Build-07"]
files_modified:
  - ".planning/phases/03-frontend-modernization-authorship/03-01-PLAN.md"
  - ".planning/phases/03-frontend-modernization-authorship/03-Build-08-PLAN.md"
  - "src/components/SortableSelectContainer.tsx"
  - "src/components/AuthorsSelect.tsx"
  - "tests/js/components/sortable-select-container.test.tsx"
  - "tests/js/components/authors-select-init.test.tsx"
  - "docs/audit/accessibility-author-selector.md"
  - "docs/audit/roadmap-global.md"
  - "docs/audit/roadmap-01.md"
autonomous: true
user_setup: []
must_haves:
  truths:
    - "Selector drag interactions support keyboard sensor configuration."
    - "Selector has explicit accessible labeling/instructions independent of placeholder text."
    - "Create and reorder actions emit status messages for assistive technologies."
  artifacts:
    - path: "tests/js/components/sortable-select-container.test.tsx"
      provides: "Coverage for keyboard sensor wiring, selector ARIA metadata, and drag announcements"
    - path: "tests/js/components/authors-select-init.test.tsx"
      provides: "Coverage for live status messages on reorder and guest-author creation"
  key_links: []
---

<objective>
Implement the first accessibility remediation slice by adding keyboard reorder support, explicit selector labeling/instructions, and assistive status announcements.
</objective>

<tasks>

<task type="auto">
  <name>03-08-01 Add failing tests for accessibility remediation targets (TDD)</name>
  <files>tests/js/components/sortable-select-container.test.tsx, tests/js/components/authors-select-init.test.tsx</files>
  <action>
    - Add tests for keyboard sensor configuration and ARIA labeling metadata.
    - Add tests for live status announcements on reorder/create actions.
  </action>
  <verify>New tests fail before implementation and pass after remediation.</verify>
  <done>Accessibility-focused test coverage added and passing.</done>
</task>

<task type="auto">
  <name>03-08-02 Implement selector accessibility remediations</name>
  <files>src/components/SortableSelectContainer.tsx, src/components/AuthorsSelect.tsx</files>
  <action>
    - Configure keyboard DnD sensor (`KeyboardSensor` + `sortableKeyboardCoordinates`) with existing pointer sensor.
    - Add explicit ARIA label/description and screen-reader instruction text for selector use.
    - Add announcements for drag outcomes and polite live-region messages for create/reorder/selection changes.
  </action>
  <verify>Behavioral tests pass and no existing selector contract regressions occur.</verify>
  <done>Accessibility remediations are implemented without changing public data contracts.</done>
</task>

<task type="auto">
  <name>03-08-03 Re-verify gates and update accessibility status tracking</name>
  <files>docs/audit/accessibility-author-selector.md, docs/audit/roadmap-global.md, docs/audit/roadmap-01.md, .planning/phases/03-frontend-modernization-authorship/03-01-PLAN.md</files>
  <action>
    - Run project gates for JS and plugin-level confidence.
    - Mark Build-08 executed and route to next accessibility-validation build.
  </action>
  <verify>`npm run lint:js`, `npm run test:js -- --ci`, `npm run build`, and `composer test` pass.</verify>
  <done>Build-08 completion is documented with verification evidence and next-step routing.</done>
</task>

</tasks>

<status>
Started on 2026-03-07 on branch `codex/phase-03-build-08-accessibility-remediation`.

Execution state:
- 03-08-01 executed: accessibility regression tests were added first and used as implementation targets.
- 03-08-02 executed: keyboard sensor, ARIA metadata, and live announcement behavior were implemented.
- 03-08-03 executed: quality gates passed and planning/roadmap status was updated for Build-09 follow-up.
</status>
