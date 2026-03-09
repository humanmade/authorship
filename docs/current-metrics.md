# Current Metrics (Canonical)

This file is the single source of truth for current repository counts.

Last verified: 2026-03-09
Verification environment: local repo checkout at `/Users/danknauss/Documents/GitHub/authorship`

## Test Metrics

| Metric | Value | Verification |
|---|---:|---|
| PHPUnit tests | 179 tests | `composer test:ut` |
| PHPUnit assertions | 1,461 assertions | `composer test:ut` |
| Jest test suites | 3 suites | `npm run test:js -- --ci` |
| Jest tests | 16 tests | `npm run test:js -- --ci` |
| PHP coverage threshold | 63% | `tests/phpunit/includes/check-coverage-threshold.php` |
| JS coverage thresholds | 80% lines, 80% statements, 70% functions, 55% branches | `package.json test:js:coverage` |

## Size Metrics

| Metric | Value | Verification |
|---|---:|---|
| Production PHP lines (`inc/` + `plugin.php`) | 2,705 | `find ./inc -type f -name "*.php" -print0 \| xargs -0 wc -l \| tail -1` + `wc -l plugin.php` |
| Test PHP lines (`tests/phpunit/`) | 4,080 | `find ./tests/phpunit -type f -name "*.php" -print0 \| xargs -0 wc -l \| tail -1` |
| JS/TS source lines (`src/`) | 766 | `find ./src -type f \( -name "*.ts" -o -name "*.tsx" -o -name "*.js" -o -name "*.jsx" -o -name "*.scss" \) -print0 \| xargs -0 wc -l \| tail -1` |
| JS test lines (`tests/js/`) | 761 | `find ./tests/js -type f \( -name "*.ts" -o -name "*.tsx" -o -name "*.js" \) -print0 \| xargs -0 wc -l \| tail -1` |
| Test-to-production ratio (PHP) | 1.51:1 | `4080 / 2705` |

## Architectural Facts

Volatile counts that change when features ship. Every doc referencing these
numbers MUST point to or be verified against this table.

| Fact | Value | Verification | Last changed |
|---|---:|---|---|
| Supported post types | configurable | `apply_filters( 'authorship_supported_post_types', ... )` | v0.1.0 |
| Author taxonomy | `wp-authors` | `grep "TAXONOMY" inc/taxonomy.php` | v0.1.0 |
| REST controllers | 1 | `Users_Controller` in `inc/class-users-controller.php` | v0.1.0 |
| WP-CLI commands | 1 | `Migrate_Command` in `inc/cli/` | v0.2.0 |
| React components | ~8 | `find src/components -name "*.tsx" \| wc -l` | Phase 03 |
| PHPStan level | max | `grep "level:" phpstan.neon.dist` | Phase 02 |
| Psalm baseline | committed | `psalm-baseline.xml` | Phase 02 |

### Files that reference these counts

- `README.md` — plugin description
- `.planning/README.md` — technical architecture summary
- `docs/manual-testing-checklist.md` — testing prompts

## CI Matrix Snapshot

Source: `.github/workflows/`

- PHP standards (stable): PHP 8.3, PHPCS + PHPStan — merge-blocking
- PHP standards (latest): PHP 8.4, nightly — advisory
- PHPUnit: WordPress 6.6 test suite, single-site + multisite
- Jest: Node 20, `@wordpress/scripts` test runner
- Build: `wp-scripts build` verification

## Verification Notes

- `composer test:ut` passed on 2026-03-09 (179 tests, 1461 assertions).
- `npm run test:js -- --ci` passed on 2026-03-09 (3 suites, 16 tests).
- `composer analyse:phpstan` passed on 2026-03-09.

## Update Procedure

1. Re-run all verification commands listed above.
2. Update this file first.
3. Keep other docs referencing this file instead of duplicating current counts.
