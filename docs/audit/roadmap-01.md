# Phase 01 Audit Roadmap for Authorship

## Current state
- `01-01` exists as the initial planning stub at `.planning/phases/01-audit-roadmap-authorship/01-01-PLAN.md`.
- `01-02` now has a repo-grounded audit deliverable in `docs/audit/HM_WPCS_audit.md`.
- No queued Git commits were present in this checkout when the audit was refreshed.

## What Phase 01 established
- Root standards configuration already exists and is not missing.
- The current HM/WPCS baseline is clean under the repo ruleset.
- Build-mode effort should focus on targeted maintenance and hardening rather than generic standards cleanup.

## Proposed Build queue
- `01-Build-01`: standards-tooling compatibility refresh
  - Target: `composer.json`, `composer.lock`, `.github/workflows/php-standards.yml`, `CONTRIBUTING.md`
  - Goal: keep PHPCS/PHPStan runnable on contemporary local PHP versions while preserving CI stability
- `01-Build-02`: guest author creation hardening
  - Target: `inc/class-users-controller.php`, `tests/phpunit/test-rest-api-user-endpoint.php`
  - Goal: harden username normalization, duplicate handling, and temporary validation-filter scope
- `01-Build-03`: editor and migration performance cleanup
  - Target: `src/components/AuthorsSelect.tsx`, `inc/cli/class-migrate-command.php`
  - Goal: remove render-time side effects and make CLI throttling configurable

## Next step
- Author the first concrete Build-mode patch from one of the three patch outlines in `docs/audit/patch_scaffolds/`.
