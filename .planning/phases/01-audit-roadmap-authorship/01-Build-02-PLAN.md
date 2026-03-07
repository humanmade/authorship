---
phase: 01-audit-roadmap-authorship
plan: 01-Build-02
type: build
wave: 1
depends_on: ["01-02"]
files_modified:
  - "inc/class-users-controller.php"
  - "tests/phpunit/test-rest-api-user-endpoint.php"
autonomous: true
user_setup: []
must_haves:
  truths:
    - "Guest author creation remains API-compatible while handling edge-case names safely."
  artifacts:
    - path: "docs/audit/patch_scaffolds/01-02-security_build.md"
      provides: "Guest-author hardening outline"
  key_links: []
---

<objective>
Harden guest author creation by improving username normalization, collision handling, and request-local filter scoping.
</objective>

<status>
Executed on 2026-03-06 in local fork context (`dknauss/authorship`).

Verification:
- `composer test:phpcs` passes after guest-author hardening changes.
- `composer test:phpstan` passes with updated baseline alignment.
- Added REST endpoint tests for duplicate-name and non-ASCII guest-author creation behavior.
- `vendor/bin/phpunit --filter TestRESTAPIUserEndpoint` passes after aligning `tests/.env` DB settings with Local runtime.
</status>
