---
phase: 03-frontend-modernization-authorship
plan: 03-Build-12
type: build
wave: 1
depends_on: ["03-frontend-modernization-authorship/03-Build-11"]
files_modified:
  - ".planning/phases/03-frontend-modernization-authorship/03-01-PLAN.md"
  - ".planning/phases/03-frontend-modernization-authorship/03-Build-12-PLAN.md"
  - "docs/manual-testing-checklist.md"
  - "docs/audit/accessibility-author-selector.md"
  - "docs/audit/roadmap-global.md"
  - "docs/audit/roadmap-01.md"
  - "output/playwright/build12-authors-field.png"
autonomous: true
user_setup:
  - "Run host-native NVDA on Windows and record the remaining spoken-output transcript row in the checklist (VoiceOver row already manually recorded)."
must_haves:
  truths:
    - "AT matrix closure requires real screen-reader spoken-output capture, not only DOM/live-region checks."
    - "Fork-local automation evidence should be captured even when host assistive-tech execution is manual."
  artifacts:
    - path: "docs/manual-testing-checklist.md"
      provides: "Executable AT matrix steps plus transcript capture table"
    - path: "docs/audit/accessibility-author-selector.md"
      provides: "Build-12 evidence and residual-risk state"
  key_links: []
---

<objective>
Execute the Build-12 accessibility matrix slice by capturing machine-verifiable runtime evidence and closing remaining host-native AT transcript requirements for Phase 03.
</objective>

<tasks>

<task type="auto">
  <name>03-12-01 Capture runtime accessibility evidence in local editor</name>
  <files>docs/audit/accessibility-author-selector.md, output/playwright/build12-authors-field.png</files>
  <action>
    - Run browser automation on `single-site-local.local` to verify current selector semantics and live-region outputs.
    - Capture instruction text (`DndDescribedBy`) and live announcer strings (`DndLiveRegion`) plus a screenshot artifact.
  </action>
  <verify>Evidence includes selector label/instructions and announcement output observed in the running editor.</verify>
  <done>Automated runtime evidence captured and documented.</done>
</task>

<task type="auto">
  <name>03-12-02 Validate host-native execution feasibility for VoiceOver automation</name>
  <files>docs/audit/accessibility-author-selector.md</files>
  <action>
    - Verify VoiceOver process availability and AppleScript control boundaries.
    - Record blockers for scripted spoken-output capture (keystroke automation/TCC limits and missing speech API surface).
  </action>
  <verify>Blockers are explicit and reproducible with command evidence.</verify>
  <done>VoiceOver automation constraints documented with concrete command outputs.</done>
</task>

<task type="auto">
  <name>03-12-03 Add AT transcript ledger for manual NVDA/VoiceOver runs</name>
  <files>docs/manual-testing-checklist.md</files>
  <action>
    - Add a structured transcript table for NVDA + VoiceOver runs (OS/browser/version, spoken strings, pass/fail).
    - Pre-fill machine-verifiable preflight notes from automated Build-12 checks.
  </action>
  <verify>Checklist contains one place to record final spoken-output evidence and closure status.</verify>
  <done>Transcript ledger added; VoiceOver row recorded and NVDA row remains queued.</done>
</task>

<task type="auto">
  <name>03-12-04 Update roadmap/phase status with manual closure gate</name>
  <files>.planning/phases/03-frontend-modernization-authorship/03-01-PLAN.md, docs/audit/roadmap-global.md, docs/audit/roadmap-01.md</files>
  <action>
    - Mark Build-12 as in progress with completed automation evidence, VoiceOver confirmation, and pending NVDA transcript.
    - Keep Phase 03 closeout tied to final NVDA transcript capture and residual-risk tracking.
  </action>
  <verify>Roadmap and phase docs agree on current state and exact remaining closure action.</verify>
  <done>Status aligned: VoiceOver evidence recorded; NVDA transcript is the remaining Build-12 closure gate.</done>
</task>

</tasks>

<status>
Started on 2026-03-07 on branch `codex/phase-03-build-12-at-matrix-evidence`.

Execution state:
- 03-12-01 executed: captured runtime selector/evidence signals and screenshot artifact.
- 03-12-02 executed: documented VoiceOver automation constraints and reproducible blockers.
- 03-12-03 executed: added AT transcript ledger and recorded manual VoiceOver pass evidence.
- 03-12-04 executed: roadmap/phase docs updated to reflect Build-12 in progress with NVDA transcript as the remaining closure gate.
</status>
