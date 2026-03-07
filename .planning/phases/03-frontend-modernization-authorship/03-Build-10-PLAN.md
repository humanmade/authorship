---
phase: 03-frontend-modernization-authorship
plan: 03-Build-10
type: build
wave: 1
depends_on: ["03-frontend-modernization-authorship/03-Build-09"]
files_modified:
  - ".planning/phases/03-frontend-modernization-authorship/03-01-PLAN.md"
  - ".planning/phases/03-frontend-modernization-authorship/03-Build-10-PLAN.md"
  - "inc/namespace.php"
  - "tests/phpunit/test-rest-api-post-property.php"
  - "docs/audit/accessibility-author-selector.md"
  - "docs/manual-testing-checklist.md"
  - "docs/audit/roadmap-global.md"
  - "docs/audit/roadmap-01.md"
autonomous: true
user_setup:
  - "Run NVDA/VoiceOver manual checks on host OS and record outcomes in checklist."
must_haves:
  truths:
    - "Non-admin edit-context post responses must not emit `wp:authorship` links that trigger inaccessible user-embed request paths."
    - "AT matrix capture requirements must be explicit, executable, and tied to concrete checklist steps."
  artifacts:
    - path: "tests/phpunit/test-rest-api-post-property.php"
      provides: "Regression protection for non-admin edit-context link suppression"
    - path: "docs/manual-testing-checklist.md"
      provides: "Stepped NVDA/VoiceOver matrix checklist and role-path validation steps"
  key_links: []
---

<objective>
Harden non-admin editor request paths by suppressing inaccessible `wp:authorship` user embeds in edit context, and formalize the NVDA/VoiceOver validation matrix required to close the remaining accessibility risk.
</objective>

<tasks>

<task type="auto">
  <name>03-10-01 Add failing regression coverage for non-admin edit-context embed behavior</name>
  <files>tests/phpunit/test-rest-api-post-property.php</files>
  <action>
    - Add a role-aware REST test for post `context=edit` responses when the user lacks `list_users`.
    - Assert no `wp:authorship` link embedding path is exposed for that request class.
  </action>
  <verify>New PHPUnit test fails before implementation and passes after hardening.</verify>
  <done>Role-path regression test added and passing.</done>
</task>

<task type="auto">
  <name>03-10-02 Harden REST callback link emission for non-admin edit-context</name>
  <files>inc/namespace.php</files>
  <action>
    - Gate `wp:authorship` link emission in `filter_rest_request_after_callbacks()` when request context is `edit` and caller cannot `list_users`.
    - Keep existing link behavior unchanged for admin/public-compatible contexts.
  </action>
  <verify>Focused REST test confirms `wp:authorship` links are not emitted for non-admin edit context while existing admin tests remain green.</verify>
  <done>Capability-aware link suppression implemented.</done>
</task>

<task type="auto">
  <name>03-10-03 Capture runtime evidence and document AT matrix closure path</name>
  <files>docs/audit/accessibility-author-selector.md, docs/manual-testing-checklist.md, docs/audit/roadmap-global.md, docs/audit/roadmap-01.md, .planning/phases/03-frontend-modernization-authorship/03-01-PLAN.md</files>
  <action>
    - Validate non-admin editor network behavior on `single-site-local.local` and record observed results.
    - Add stepped NVDA/VoiceOver matrix procedures with explicit pass/fail capture fields.
    - Update phase/roadmap state and route any remaining manual AT blockers.
  </action>
  <verify>Runtime evidence is documented, phase status is current, and next-step manual AT capture is explicit.</verify>
  <done>Build-10 hardening and documentation execution completed with manual AT capture explicitly queued.</done>
</task>

</tasks>

<status>
Started on 2026-03-07 on branch `codex/phase-03-build-10-at-matrix-hardening`.

Execution state:
- 03-10-01 executed: added non-admin edit-context regression coverage for link emission behavior.
- 03-10-02 executed: implemented capability-aware suppression of `wp:authorship` links for edit-context users lacking `list_users`.
- 03-10-03 executed: captured local runtime network evidence and documented NVDA/VoiceOver matrix steps; host-native screen-reader run remains a manual checklist item.
</status>
