# HM vs WPCS Audit - Authorship plugin

Repo-grounded audit with command evidence, rule references, and queued follow-up work.

## Scope
- PHP plugin bootstrap and runtime files under `plugin.php` and `inc/`.
- Editor UI code under `src/` where behavior affects author assignment.
- Standards configuration and CI/runtime assumptions.
- Existing PHPUnit coverage breadth relevant to authoring and attribution flows.

## Verification context
- Audit date: 2026-03-06.
- Local runtime observed during audit: PHP 8.5.1.
- Standards profile source: `phpcs.xml.dist`.

## Command evidence log
| Command | Intent | Result | Evidence summary |
| --- | --- | --- | --- |
| `composer test:phpcs` | Run default PHPCS gate used by contributors/CI scripts. | Failed (exit 1). | Internal exceptions from legacy PHPCS stack, including `Generic.WhiteSpace.ScopeIndentSniff` runtime errors before meaningful standards output. |
| `php -d error_reporting='E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED' vendor/bin/phpcs --standard=phpcs.xml.dist --report=summary plugin.php inc tests/phpunit` | Run targeted PHPCS verification against repo ruleset. | Passed (exit 0). | No standards violations reported for the selected surfaces; command required deprecation suppression for legacy tooling compatibility. |
| `composer test:phpstan` | Run default static-analysis gate. | Failed (exit 1). | Internal parser/runtime errors (for example `PhpParser\\Lexer::getNextToken()`), plus ignored-error mismatch due tool/runtime incompatibility. |

## Standards profile and rule references
| Type | Rule or property | Source | Audit implication |
| --- | --- | --- | --- |
| Standards baseline | `HM` | `phpcs.xml.dist:15` | HM coding standard is the primary PHPCS profile for the repo. |
| PHP compatibility target | `testVersion=7.2-` | `phpcs.xml.dist:3` | PHPCS compatibility checks are aligned to plugin floor and newer versions. |
| I18n constraint | `WordPress.WP.I18n` with `text_domain=authorship` | `phpcs.xml.dist:35-38` | Strings should be constrained to the plugin text domain. |
| Deprecated API floor | `WordPress.WP.DeprecatedFunctions` with `minimum_supported_version=5.4` | `phpcs.xml.dist:41-44` | Deprecated-function checks are tied to WP 5.4 support floor. |
| Intentional exclusions | `HM.Files`, `WordPress.Files`, `Generic.Commenting.DocComment.MissingShort`, `PSR2R.Namespaces.*` exclusions | `phpcs.xml.dist:17-27` | Some filename/docblock/namespace ordering checks are intentionally non-goals for this repo profile. |
| Test commenting scope | `Squiz.Commenting` with tests excluded | `phpcs.xml.dist:30-33` | Commenting sniffs are intentionally not enforced for test files. |

## Baseline findings with detailed evidence

### A. Standards configuration exists and is explicit
Evidence:
- Ruleset exists and is root-scoped: `phpcs.xml.dist:2-45`.
- Composer scripts expose standards commands: `composer.json:48-63`.
- Standards workflow executes both PHPCS and PHPStan on CI runtime pin: `.github/workflows/php-standards.yml:22,53-56`.

Assessment:
- Prior "missing root PHPCS config" assumptions are not supported.
- The repo has a clear standards profile; the current gap is runtime compatibility, not absence of configuration.

### B. Codebase shows strong HM/WPCS-aligned patterns in reviewed paths
Evidence:
- Strict typing declarations across runtime entry points: `plugin.php:30`, `inc/namespace.php:8`, `inc/class-users-controller.php:8`, `inc/class-insert-post-handler.php:8`, `inc/admin.php:8`.
- Output escaping in admin/template rendering: `inc/admin.php:94-96`, `inc/template.php:130`.
- REST permission callbacks and capability checks: `inc/class-users-controller.php:56-63,77,151,161`.
- REST update callback converts assignment exceptions into `WP_Error`: `inc/namespace.php:422-429`.
- PHPUnit suite breadth includes archive/capabilities/CLI/feeds/multisite/post saving/REST: `tests/phpunit/test-*.php`.

Assessment:
- Reviewed runtime surfaces show deliberate permission and escaping controls.
- Error surfacing is inconsistent between REST update path (`inc/namespace.php`) and insert hook path (`inc/class-insert-post-handler.php`).

## Detailed follow-up items (evidence + rule/context references)

### 1. Tooling compatibility lags modern PHP runtimes
Evidence:
- Dev tool versions are old and pinned: `composer.json:14,17,20`.
- Composer platform pin is `7.4`: `composer.json:34-36`.
- CI standards workflow runs PHP `7.4`: `.github/workflows/php-standards.yml:22`.
- Local command outcomes show default gates failing on PHP 8.5.1 (see command log).

Rule/context references:
- PHPCS profile is valid (`HM` plus WordPress sniffs), but execution reliability is runtime-constrained.

Impact:
- Default quality gates are not reproducible on modern local runtimes.

Recommendation:
- Execute Build `01-Build-01` first to restore reproducibility and documentation parity.

### 2. Guest-author username normalization is brittle for edge-case names
Evidence:
- Username derives from name and strips to lowercase ASCII alphanumerics: `inc/class-users-controller.php:194-197`.
- Existing tests cover simple creation and permissions but not non-ASCII/collision behavior: `tests/phpunit/test-rest-api-user-endpoint.php:30-265`.

Rule/context references:
- No direct HM/WPCS violation implied; this is correctness and data-integrity hardening.

Impact:
- Empty/low-information usernames and collisions are possible for real-world names.

Recommendation:
- Add deterministic fallback + uniqueness strategy, and add explicit non-ASCII/collision test cases.

### 3. Signup-validation filter scope is broader than needed
Evidence:
- Anonymous `wpmu_validate_user_signup` filter added in `create_item()` and not removed: `inc/class-users-controller.php:211-219`.
- Contrast: request-scoped query filter in `get_items()` is explicitly removed: `inc/class-users-controller.php:122-127`.

Rule/context references:
- This is lifecycle/scoping hygiene, not a standards-sniff failure.

Impact:
- Request-level side effects are harder to reason about and test.

Recommendation:
- Replace anonymous callback with removable callback and remove it after `parent::create_item()`.

### 4. Insert hook path swallows author-assignment exceptions
Evidence:
- Exceptions from `set_authors()` are caught and discarded in insert hook: `inc/class-insert-post-handler.php:85-89`.
- REST update path handles similar exceptions by returning `WP_Error`: `inc/namespace.php:422-429`.

Rule/context references:
- Observability/correctness gap rather than HM/WPCS style issue.

Impact:
- Author-assignment failures can be silent in post-insert flows.

Recommendation:
- Add deterministic failure signaling (hook/log/error object pathway) and test coverage.

### 5. `AuthorsSelect` performs side effects during render
Evidence:
- State initialization and API fetch can run from render conditionals: `src/components/AuthorsSelect.tsx:59-79`.

Rule/context references:
- React correctness/performance concern outside PHPCS/HM rule scope.

Impact:
- Increased risk of repeated requests and render churn.

Recommendation:
- Move preload/fetch lifecycle to `useEffect`; keep render path pure.

## Claims currently not supported by evidence
- Missing root PHPCS configuration.
- Broad nonce issues in custom admin/AJAX flows (not evidenced in reviewed paths).
- Immediate output-escaping gaps in reviewed rendering paths.

## Build queue mapping
| Candidate | Plan | Scaffold | Primary files |
| --- | --- | --- | --- |
| Standards/tooling compatibility | `01-Build-01` | `docs/audit/patch_scaffolds/01-02-hm-wpcs_build.md` | `composer.json`, `composer.lock`, `.github/workflows/php-standards.yml`, `CONTRIBUTING.md` |
| Guest-author hardening | `01-Build-02` | `docs/audit/patch_scaffolds/01-02-security_build.md` | `inc/class-users-controller.php`, `tests/phpunit/test-rest-api-user-endpoint.php` |
| Post-insert observability hardening | `01-Build-03` | `docs/audit/patch_scaffolds/01-02-observability_build.md` | `inc/class-insert-post-handler.php`, `tests/phpunit/test-post-saving.php` |
| Editor/CLI performance cleanup | `01-Build-04` | `docs/audit/patch_scaffolds/01-02-performance_build.md` | `src/components/AuthorsSelect.tsx`, `inc/cli/class-migrate-command.php` |

## Status
- Standards profile quality: explicit and repo-grounded.
- Gate reproducibility: currently split between targeted diagnostics (passes) and default gate commands on modern runtime (fails).
- Recommended sequence: tooling reproducibility first, then correctness/security hardening, then performance cleanup.
