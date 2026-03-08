---
phase: 03-frontend-modernization-authorship
plan: 03-Build-06
type: build
wave: 1
depends_on: ["03-frontend-modernization-authorship/03-Build-05"]
files_modified:
  - ".planning/phases/03-frontend-modernization-authorship/03-01-PLAN.md"
  - ".planning/phases/03-frontend-modernization-authorship/03-Build-06-PLAN.md"
  - "docs/manual-testing-checklist.md"
  - "docs/audit/roadmap-global.md"
  - "docs/audit/roadmap-01.md"
autonomous: true
user_setup: []
must_haves:
  truths:
    - "A manual checklist exists for UI, REST API, WP-CLI, and XML-RPC verification paths."
    - "XML-RPC compatibility is documented as partial support with explicit expected behavior."
  artifacts:
    - path: "docs/manual-testing-checklist.md"
      provides: "Step-by-step manual verification workflow for release and regression checks"
  key_links: []
---

<objective>
Create a deterministic manual testing checklist covering editor UI, REST API, WP-CLI, and XML-RPC interactions for Authorship.
</objective>

<tasks>

<task type="auto">
  <name>03-06-01 Define checklist scope and scenario inventory</name>
  <files>.planning/phases/03-frontend-modernization-authorship/03-Build-06-PLAN.md</files>
  <action>
    - Enumerate required manual paths: UI, REST API, WP-CLI, XML-RPC.
    - Define per-scenario IDs with clear expected results.
  </action>
  <verify>Plan contains explicit sections and acceptance criteria for each protocol.</verify>
  <done>Checklist scope and scenario structure are defined.</done>
</task>

<task type="auto">
  <name>03-06-02 Author manual testing document with executable steps</name>
  <files>docs/manual-testing-checklist.md</files>
  <action>
    - Document step-by-step UI scenarios for assign/reorder/guest-author and permission behavior.
    - Add REST API curl examples for read/write/validation/error behavior.
    - Add WP-CLI scenarios for create/update/migrate flows.
    - Add XML-RPC reachability/create/edit/limitations checks and explicit compatibility note.
  </action>
  <verify>Checklist includes runnable commands/scripts and expected outcomes for each scenario ID.</verify>
  <done>Manual checklist is available for release and regression validation.</done>
</task>

<task type="auto">
  <name>03-06-03 Record Build-06 execution in phase and roadmap artifacts</name>
  <files>.planning/phases/03-frontend-modernization-authorship/03-01-PLAN.md, docs/audit/roadmap-global.md, docs/audit/roadmap-01.md</files>
  <action>
    - Mark Build-06 as executed and set the next planned Build-07 focus.
    - Keep phase narrative aligned with fork-first execution.
  </action>
  <verify>Phase plan and roadmap docs show Build-06 executed with the checklist path referenced.</verify>
  <done>Build-06 execution status is reflected consistently across planning docs.</done>
</task>

</tasks>

<status>
Started on 2026-03-07 on branch `codex/phase-03-build-06-manual-testing-checklist`.

Execution state:
- 03-06-01 executed: scope includes UI, REST API, WP-CLI, and XML-RPC tracks.
- 03-06-02 executed: `docs/manual-testing-checklist.md` authored with runnable manual scenarios.
- 03-06-03 executed: Phase and roadmap artifacts updated to mark Build-06 complete and queue Build-07 accessibility work.
</status>
