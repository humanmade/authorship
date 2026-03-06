# Foundation Quality Baseline (Phase 00)

## Date
- Baseline established: 2026-03-06 (MST)

## Purpose
- Align quality gates between local development and CI before Build-mode implementation.

## Support matrix (explicit)
| Surface | Version or Constraint | Source |
| --- | --- | --- |
| Plugin runtime floor | `PHP >=7.2` | `composer.json` `require.php` |
| Tooling platform pin | `PHP 7.4` | `composer.json` `config.platform.php` |
| Standards CI runtime | `PHP 7.4` | `.github/workflows/php-standards.yml` |
| Observed local modern runtime | `PHP 8.5.1` | Local audit runs on 2026-03-06 |

## Green gate definition
1. `composer test:phpcs` passes on the declared tooling runtime.
2. `composer test:phpstan` passes on the declared tooling runtime.
3. `composer test:ut` passes for single-site and multisite.
4. Any narrower diagnostic command used during auditing is documented as diagnostic-only and not treated as a gate replacement.

## Known gap at phase start
- On local `PHP 8.5.1`, default `composer test:phpcs` and `composer test:phpstan` are not green because of legacy toolchain/runtime incompatibilities.
- The targeted PHPCS command below is useful for diagnostic signal but is not a substitute for the default gate:
  - `php -d error_reporting='E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED' vendor/bin/phpcs --standard=phpcs.xml.dist --report=summary plugin.php inc tests/phpunit`

## CI and local parity rule
- Until tooling is refreshed, contributor documentation must clearly state the supported runtime for standards gates and provide a reproducible way to run those gates under that runtime.

## Build execution order
1. `01-Build-01` tooling compatibility.
2. `01-Build-02` guest author hardening.
3. `01-Build-03` post insert observability hardening.
4. `01-Build-04` editor and CLI performance cleanup.

## Change control
- Any change to support matrix or gate commands must update this file and any linked phase plan metadata.
