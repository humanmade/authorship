# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

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
composer test:integration     # Alias for test:ut
composer test:coverage        # PHPUnit with coverage (63% threshold gate)
composer test:phpstan         # PHPStan level max (with committed baseline)
composer test:phpcs            # PHPCS (HM coding standards)
composer lint                 # Alias for test:phpcs
composer analyse:phpstan      # Alias for test:phpstan
composer analyse:psalm        # Psalm (with committed baseline, advisory)
```

### JavaScript

```bash
npm install                   # Install Node dependencies
npm run build                 # wp-scripts production build
npm run start                 # wp-scripts dev server (watch mode)
npm run test:js               # Jest unit tests
npm run test:js:coverage      # Jest with coverage thresholds (80/80/70/55)
npm run lint                  # ESLint + Stylelint (concurrent)
npm run lint:js               # ESLint (TS, TSX, JS, JSX)
npm run lint:css              # Stylelint (SCSS)
```

### Quality gates before any commit

```bash
composer test:ut && composer analyse:phpstan && composer lint && npm run lint:js && npm run test:js -- --ci && npm run build
```

## Documentation

- `.planning/README.md` — technical architecture index.
- `docs/fork-first-policy.md` — fork governance and upstream submission rules.
- `docs/manual-testing-checklist.md` — UI/UX testing prompts.
- `docs/audit/` — Phase 01 audit artifacts.

## Verification Requirements

### Internal architectural counts

- **MUST** check `docs/current-metrics.md` before writing any count that appears
  there (tests, LOC, coverage thresholds, component counts).
- When adding a feature that changes a count, update `current-metrics.md`
  FIRST, then update all files listed in its "Files that reference these
  counts" section.

## Architecture

**Entry point:** `plugin.php` — loads asset-loader, requires all `inc/` files, conditionally loads admin and CLI bootstraps, calls `bootstrap()`.

**Namespace:** `Authorship\`

### PHP Backend (`inc/`)

- **namespace.php** — Core bootstrap. Registers taxonomy, hooks for post save, author query modifications, RSS feed integration, `admin_init` permission controls.
- **taxonomy.php** — Registers the `wp-authors` taxonomy. Authors are terms in this taxonomy, linked to real WordPress user accounts.
- **Users_Controller** — REST API controller extending `WP_REST_Users_Controller`. Provides endpoints for author CRUD operations.
- **Insert_Post_Handler** — Handles author attribution during post save via `wp_insert_post_data` and `set_object_terms` hooks.
- **template.php** — Template tag functions for theme integration (`get_authors()`, `the_authors()`, etc.).
- **admin.php** — Admin UI hooks and column customization.
- **cli/** — WP-CLI `Migrate_Command` for bulk author migration from legacy meta.

### React/TypeScript Frontend (`src/`)

- **index.tsx** — Plugin registration via `@wordpress/plugins`.
- **plugin.tsx** — Main sidebar panel component for the block editor.
- **components/** — Reusable UI: `AuthorList` (sortable with `@dnd-kit`), `AuthorSelection` (with `react-select`), `AuthorItem`.
- **types.ts** — TypeScript type definitions.
- **style.scss** — Component styles.

### Key Behaviors

- **Author taxonomy:** `wp-authors` — each term links to a real WordPress user account via term meta.
- **Guest authors:** Real user accounts with limited capabilities, not taxonomy-only entries.
- **Post attribution:** Modified via `wp_insert_post_data` filter and `set_object_terms` action.
- **Author queries:** `pre_get_posts` modifies author archive queries to use the taxonomy instead of `post_author`.
- **Asset loading:** Uses `humanmade/asset-loader` for enqueuing built React bundles.

## Testing

### PHP

Integration tests in `tests/phpunit/` use WordPress test suite (`WP_UnitTestCase`). WordPress is installed to `tests/wordpress/` via Composer. Tests run in both single-site and multisite modes.

Copy `tests/.env.dist` to `tests/.env` and configure database credentials.

### JavaScript

Jest tests in `tests/js/` use `@testing-library/react`. Run via `wp-scripts test-unit-js`.

## Commit Practices

- Use conventional commit format.
- Fork-first workflow: `develop` is the canonical branch.
- `composer test:ut && composer analyse:phpstan && npm run test:js -- --ci` should pass before every commit.
