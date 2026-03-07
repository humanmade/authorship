---
phase: 01-audit-roadmap-authorship
plan: 01-Build-01
type: build
wave: 1
depends_on: ["01-02"]
files_modified:
  - "composer.json"
  - "composer.lock"
  - "phpstan.neon.dist"
  - "phpstan-baseline.neon"
  - ".github/workflows/php-standards.yml"
  - "CONTRIBUTING.md"
autonomous: true
user_setup: []
must_haves:
  truths:
    - "Standards tooling remains reproducible in CI and locally."
  artifacts:
    - path: "docs/audit/patch_scaffolds/01-02-hm-wpcs_build.md"
      provides: "Standards-tooling compatibility outline"
  key_links: []
---

<objective>
Refresh or document the standards toolchain so PHPCS and PHPStan remain runnable on supported development environments.
</objective>

<status>
Executed on 2026-03-06 in local fork context (`dknauss/authorship`).

Verification:
- `composer test:phpcs` passes on local PHP 8.5 via deprecation-suppressed PHPCS runtime invocation.
- `composer test:phpstan` passes with upgraded PHPStan stack, explicit memory limit, and baseline for pre-existing findings.
- Workflow now runs standards checks on both PHP `7.4` and `8.4` to retain floor compatibility while validating modern runtime behavior.
</status>
