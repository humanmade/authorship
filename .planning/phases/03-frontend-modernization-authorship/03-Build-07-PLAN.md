---
phase: 03-frontend-modernization-authorship
plan: 03-Build-07
type: build
wave: 1
depends_on: ["03-frontend-modernization-authorship/03-Build-06"]
files_modified:
  - ".planning/phases/03-frontend-modernization-authorship/03-01-PLAN.md"
  - ".planning/phases/03-frontend-modernization-authorship/03-Build-07-PLAN.md"
  - "docs/audit/accessibility-author-selector.md"
  - "docs/audit/roadmap-global.md"
  - "docs/audit/roadmap-01.md"
autonomous: true
user_setup: []
must_haves:
  truths:
    - "Author selector accessibility findings are mapped to WCAG 2.1 AA criteria with code evidence."
    - "A prioritized remediation backlog exists for follow-up implementation slices."
  artifacts:
    - path: "docs/audit/accessibility-author-selector.md"
      provides: "WCAG audit evidence, risk ranking, and remediation queue for the selector UX"
  key_links: []
---

<objective>
Complete the Phase 03 accessibility audit slice by documenting WCAG 2.1 AA findings and a concrete remediation plan for the author selector.
</objective>

<tasks>

<task type="auto">
  <name>03-07-01 Define WCAG audit scope and capture code-grounded evidence</name>
  <files>docs/audit/accessibility-author-selector.md</files>
  <action>
    - Audit selector behavior against WCAG 2.1 AA criteria relevant to keyboard, screen reader, and form semantics.
    - Record direct evidence from current component implementation.
  </action>
  <verify>Audit document contains explicit criterion mappings and file-level evidence pointers.</verify>
  <done>WCAG scope and evidence are documented.</done>
</task>

<task type="auto">
  <name>03-07-02 Produce remediation backlog with phased implementation targets</name>
  <files>docs/audit/accessibility-author-selector.md</files>
  <action>
    - Convert findings into prioritized remediation items with acceptance criteria.
    - Separate immediate fixes from candidate larger refactors.
  </action>
  <verify>Audit includes prioritized backlog entries with implementation intent and test expectations.</verify>
  <done>Remediation backlog and sequencing are defined.</done>
</task>

<task type="auto">
  <name>03-07-03 Update Phase 03 status and next-step routing</name>
  <files>.planning/phases/03-frontend-modernization-authorship/03-01-PLAN.md, docs/audit/roadmap-global.md, docs/audit/roadmap-01.md</files>
  <action>
    - Mark Build-07 as executed.
    - Set Build-08 as the next accessibility remediation implementation slice.
  </action>
  <verify>Phase and roadmap docs consistently reflect Build-07 completion and Build-08 next step.</verify>
  <done>Phase routing is updated for post-audit remediation work.</done>
</task>

</tasks>

<status>
Started on 2026-03-07 on branch `codex/phase-03-build-07-accessibility-audit`.

Execution state:
- 03-07-01 executed: WCAG 2.1 AA audit scope and code evidence captured.
- 03-07-02 executed: remediation backlog prioritized and mapped to implementation slices.
- 03-07-03 executed: planning and roadmap artifacts updated with Build-07 completion and Build-08 next step.
</status>
