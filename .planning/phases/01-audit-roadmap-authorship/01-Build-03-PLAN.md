---
phase: 01-audit-roadmap-authorship
plan: 01-Build-03
type: build
wave: 1
depends_on: ["01-02"]
files_modified:
  - "inc/class-insert-post-handler.php"
  - "tests/phpunit/test-post-saving.php"
autonomous: true
user_setup: []
must_haves:
  truths:
    - "Author assignment failures are observable and test-visible instead of being silently swallowed."
  artifacts:
    - path: "docs/audit/patch_scaffolds/01-02-observability_build.md"
      provides: "Post-insert observability hardening outline"
  key_links: []
---

<objective>
Harden post insert author-assignment flow by surfacing failures through a controlled, testable path.
</objective>

<status>
Executed on 2026-03-06 in local fork context (`dknauss/authorship`).

Verification:
- `composer test:phpcs` passes after observability changes.
- `composer test:phpstan` passes after observability changes.
- `vendor/bin/phpunit --filter TestPostSaving` passes, including new failure-signaling coverage.
- Full `composer test` still reports legacy WordPress/PHPUnit deprecation-output failures on PHP 8.5 in unrelated REST property tests.
</status>
