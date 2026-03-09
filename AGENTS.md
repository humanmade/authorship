# AGENTS.md

This file provides guidance to Codex and other AI agents when working with code in this repository.

## Project Overview

Authorship is a WordPress plugin for multi-author and guest-author attribution. It supports multiple authors per post, guest authors backed by real WordPress user accounts, block editor integration, REST API and WP-CLI support. This is an active fork (`dknauss/authorship`) from the original Human Made project.

**Version:** 0.2.17
**Requirements:** WordPress 5.4+, PHP 7.2+, Node 20+

## Commands

### PHP

```bash
composer install              # Install dependencies
composer test                 # Run full suite (phpcs + phpstan + phpunit)
composer test:ut              # Run PHPUnit (single-site + multisite)
composer test:coverage        # PHPUnit with coverage (63% threshold gate)
composer test:phpstan         # PHPStan level max (with committed baseline)
composer lint                 # PHPCS (HM coding standards)
composer analyse:phpstan      # Alias for test:phpstan
composer analyse:psalm        # Psalm (with committed baseline, advisory)
```

### JavaScript

```bash
npm run build                 # wp-scripts production build
npm run test:js               # Jest unit tests
npm run test:js:coverage      # Jest with coverage thresholds
npm run lint                  # ESLint + Stylelint
```

## Documentation

- `.planning/README.md` — technical architecture index.
- `docs/fork-first-policy.md` — fork governance and upstream submission rules.
- `docs/manual-testing-checklist.md` — UI/UX testing prompts.

## Verification Requirements

### Internal architectural counts

- **MUST** check `docs/current-metrics.md` before writing any count that appears
  there (tests, LOC, coverage thresholds, component counts).
- When adding a feature that changes a count, update `current-metrics.md`
  FIRST, then update all files listed in its "Files that reference these
  counts" section.

## Architecture

**Entry point:** `plugin.php` — loads asset-loader, requires `inc/`, conditionally loads admin and CLI.

**Namespace:** `Authorship\`

### PHP Backend (`inc/`)

- **namespace.php** — Core bootstrap, taxonomy registration, post save hooks, author query modifications.
- **taxonomy.php** — `wp-authors` taxonomy registration.
- **Users_Controller** — REST API controller for author CRUD.
- **Insert_Post_Handler** — Post save attribution logic.
- **template.php** — Template tags (`get_authors()`, `the_authors()`).
- **cli/** — WP-CLI `Migrate_Command` for bulk migration.

### React/TypeScript Frontend (`src/`)

- Block editor sidebar panel with `@dnd-kit` drag-and-drop and `react-select` user selection.

## Testing

- **PHP:** `tests/phpunit/` — `WP_UnitTestCase`, single-site + multisite. WordPress installed to `tests/wordpress/`.
- **JS:** `tests/js/` — Jest with `@testing-library/react`.

## Commit Practices

- Use conventional commit format.
- Fork-first workflow: `develop` is the canonical branch.
- Quality gates: `composer test:ut && composer analyse:phpstan && npm run test:js -- --ci`
