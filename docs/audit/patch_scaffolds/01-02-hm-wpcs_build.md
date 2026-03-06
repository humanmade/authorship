PATCH SCAFFOLD: Standards tooling compatibility refresh

Goal:
- Preserve the repo's current HM/WPCS cleanliness while making the standards toolchain runnable on modern local PHP versions.

Primary targets:
- `composer.json`
- `composer.lock`
- `.github/workflows/php-standards.yml`
- `CONTRIBUTING.md`

Problem statement:
- The repo already has a root PHPCS ruleset and currently passes PHPCS under that ruleset.
- Local runs on PHP 8.5 produce deprecation noise from legacy PHPCS/HM packages.
- Local `composer test:phpstan` fails before reporting code issues because the pinned PHPStan stack is too old for the local runtime.

Planned changes:
- Refresh PHPCS/HM/PHPStan dependencies to versions that support current PHP releases, if compatible with the plugin's PHP floor and CI matrix.
- If a full dependency refresh is too risky, pin and document a supported PHP version for standards runs in local development.
- Update contributor guidance so standards commands are reproducible locally.

Validation:
- `composer test:phpcs`
- `composer test:phpstan`
- CI `PHP Standards` workflow still passes

Notes:
- This is maintenance work, not a response to current repo-local PHPCS violations.
