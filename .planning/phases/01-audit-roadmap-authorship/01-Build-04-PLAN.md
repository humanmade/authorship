---
phase: 01-audit-roadmap-authorship
plan: 01-Build-04
type: build
wave: 1
depends_on: ["01-02"]
files_modified:
  - "src/components/AuthorsSelect.tsx"
  - "inc/cli/class-migrate-command.php"
autonomous: true
user_setup: []
must_haves:
  truths:
    - "Editor behavior stays correct while avoiding render-time side effects."
  artifacts:
    - path: "docs/audit/patch_scaffolds/01-02-performance_build.md"
      provides: "Editor and CLI performance outline"
  key_links: []
---

<objective>
Move editor fetch/state initialization out of render and make CLI migration pacing configurable.
</objective>

<status>
Executed on 2026-03-06 in local fork context (`dknauss/authorship`).

Delivered:
- Refactored `AuthorsSelect` to perform preload/fetch initialization in `useEffect` (no render-time `setState` or `apiFetch`).
- Added configurable migration pacing for `wp authorship migrate` via `--batch-pause` and `authorship_migrate_batch_pause_seconds`.
- Added CLI coverage for zero-pause behavior and removed fixed delay from existing CLI tests.

Verification:
- `composer test` passes (146 tests, 353 assertions).
- `npm run lint:js` passes.

Stabilization during verification:
- Added output-buffer guard in base PHPUnit `TestCase` and `WP_DEBUG_DISPLAY` suppression to prevent strict-output noise from deprecation chatter.
- Fixed multisite guest-author duplicate-name creation by switching unique username suffixing to alphanumeric-only (`name2` instead of `name-2`).
</status>
