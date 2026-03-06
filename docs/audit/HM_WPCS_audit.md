# HM vs WPCS Audit - Authorship plugin

This document replaces the earlier placeholder audit with repo-grounded notes taken from the current `develop` checkout.

## Scope
- PHP plugin bootstrap and runtime files under `plugin.php` and `inc/`
- Editor UI code under `src/`
- Repository standards and CI configuration
- Existing PHPUnit coverage and standards tooling

## Verification performed
- Confirmed that a root PHPCS ruleset exists at `phpcs.xml.dist`.
- Ran `php -d error_reporting='E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED' vendor/bin/phpcs --standard=phpcs.xml.dist --report=summary plugin.php inc tests/phpunit`.
- Attempted `composer test:phpstan` on local PHP 8.5.1. The toolchain failed with PHPStan/parser internal errors before reporting code issues.

## Baseline findings

### Standards configuration is already present
- The repository already defines a root PHPCS ruleset in `phpcs.xml.dist`.
- That ruleset uses the HM standard, configures the `authorship` text domain, and sets a WordPress deprecated-function floor of 5.4.
- The prior claim that the repo had no local PHPCS config was incorrect.

Evidence:
- `phpcs.xml.dist`
- `composer.json`
- `.github/workflows/php-standards.yml`

### Current HM/WPCS baseline is strong
- Runtime PHP files consistently use namespaces and `declare( strict_types=1 )`.
- Output escaping is present in the admin column renderer.
- REST permission checks are present on the custom users controller and post attribution field.
- PHPUnit coverage exists for archives, capabilities, CLI, feeds, REST API, multisite, post saving, and template helpers.
- PHPCS completed cleanly under the current repo ruleset when PHP 8.5 deprecations from legacy tooling were suppressed.

Evidence:
- `plugin.php`
- `inc/admin.php`
- `inc/class-users-controller.php`
- `inc/namespace.php`
- `tests/phpunit/`

## Concrete follow-up items

### 1. Tooling compatibility is lagging current PHP releases
This repo pins older standards and analysis tooling:
- `php_codesniffer` `3.5.8`
- `phpstan/phpstan` `0.12.57`
- `humanmade/coding-standards` `1.1.1`

Those versions are old enough that local runs on PHP 8.5 generate deprecation noise and, for PHPStan, hard failures before code analysis completes. CI avoids this today by running the standards job on PHP 7.4.

Impact:
- Standards checks are not reliably runnable on a modern local PHP installation.
- The repo currently depends on CI/runtime pinning rather than tool compatibility.

Evidence:
- `composer.json`
- `.github/workflows/php-standards.yml`

Recommendation:
- Treat standards-tooling refresh as Build-mode work.
- If a full dependency refresh is too disruptive, explicitly document that standards jobs must run on the supported PHP version from CI.

### 2. Guest author username generation should be hardened
Guest author creation currently derives `username` from `name`, then strips everything except lowercase ASCII letters and digits.

Impact:
- Non-Latin or punctuation-heavy names can collapse to an empty or low-information username.
- Distinct display names can normalize to the same login base.
- The behavior is workable for simple English names but brittle for arbitrary guest-author data.

Evidence:
- `inc/class-users-controller.php`

Recommendation:
- Add explicit handling for empty normalized usernames.
- Guarantee uniqueness with a deterministic fallback rather than relying on the current bare normalization path.
- Add tests for duplicate names and non-ASCII names.

### 3. Temporary signup validation filter is added but never removed
`Users_Controller::create_item()` adds an anonymous `wpmu_validate_user_signup` filter and leaves it in place for the rest of the request.

Impact:
- The effect is request-scoped, not persistent, so it is not catastrophic.
- It is still broader than needed and makes the method harder to reason about and test.

Evidence:
- `inc/class-users-controller.php`

Recommendation:
- Replace the anonymous callback with a removable callback and remove it immediately after `parent::create_item()` returns.

### 4. Post insert errors are swallowed silently
`InsertPostHandler::action_wp_insert_post()` catches `Exception` from `set_authors()` and discards it.

Impact:
- Invalid or failed author assignment can fail with no log, no notice, and no test signal.
- This weakens observability more than standards conformance.

Evidence:
- `inc/class-insert-post-handler.php`

Recommendation:
- Surface the failure through logging, an admin notice, or a test-visible error path.
- If silent failure is intentional, document that design choice explicitly.

### 5. React editor code performs side effects during render
`AuthorsSelect` initializes state and can trigger REST fetches directly from render-time conditionals rather than from effects.

Impact:
- This is not an HM/WPCS issue, but it is a real maintenance/performance concern.
- It increases the chance of repeated fetches or render churn as the editor evolves.

Evidence:
- `src/components/AuthorsSelect.tsx`

Recommendation:
- Move preload and fetch logic into `useEffect`.
- Keep render pure and make request lifecycles explicit.

## Items not supported by the current evidence
- I did not find repo evidence for missing root PHPCS configuration.
- I did not find a custom admin form or AJAX workflow here that would justify generic “missing nonce” claims.
- I did not find an immediate escaping gap in the reviewed PHP output paths.

## Build-mode patch candidates
- Standards/tooling compatibility refresh for PHPCS/PHPStan and contributor guidance.
- Guest author creation hardening for username normalization and temporary filter scope.
- Editor/CLI performance cleanup for render-time fetches and migration throttling.

## Status
- HM/WPCS baseline: healthy under the current repo ruleset.
- Highest-value next work: tooling compatibility and targeted hardening, not broad standards cleanup.
