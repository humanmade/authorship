---
phase: 02-fork-first-delivery-authorship
plan: 02-Build-12
type: build
wave: 1
depends_on: ["02-Build-11"]
files_modified:
  - "phpstan-baseline.neon"
  - "inc/namespace.php"
  - "inc/class-insert-post-handler.php"
  - "inc/cli/class-migrate-command.php"
  - ".planning/phases/02-fork-first-delivery-authorship/02-01-PLAN.md"
  - "docs/audit/roadmap-01.md"
  - "docs/audit/roadmap-global.md"
autonomous: true
user_setup: []
must_haves:
  truths:
    - "Baseline entries are reduced to those that genuinely require upstream WordPress API changes to resolve."
    - "No runtime behavior changes are introduced — only type annotations and defensive type guards."
  artifacts:
    - path: "phpstan-baseline.neon"
      provides: "Reduced baseline with justification comments for remaining entries"
  key_links: []
---

<objective>
Reduce the PHPStan baseline by fixing annotation-level issues that do not require behavior changes, and annotate remaining entries with justification.
</objective>

<tasks>

<task type="auto">
  <name>02-12-01 Audit current baseline entries</name>
  <files>phpstan-baseline.neon</files>
  <action>
    - Categorize each baseline entry as fixable (missing @var, type guard, or cast) or genuinely unfixable (WordPress core returns mixed).
    - Document the categorization.
  </action>
  <verify>Every baseline entry has a disposition: fix or justify.</verify>
  <done>Categorization complete.</done>
</task>

<task type="auto">
  <name>02-12-02 Fix annotation-level baseline entries</name>
  <files>inc/namespace.php, inc/class-insert-post-handler.php, inc/cli/class-migrate-command.php</files>
  <action>
    - Add @var annotations, type guards, or safe casts to resolve fixable entries.
    - Do not change runtime behavior or control flow.
    - Regenerate baseline to confirm entry removal.
  </action>
  <verify>`composer test:phpstan` passes with a smaller baseline. `composer test` still green.</verify>
  <done>Fixable entries resolved.</done>
</task>

<task type="auto">
  <name>02-12-03 Annotate remaining baseline entries</name>
  <files>phpstan-baseline.neon</files>
  <action>
    - Add inline comments to remaining baseline entries explaining why they cannot be resolved without WordPress core changes.
  </action>
  <verify>Every remaining entry has a justification comment.</verify>
  <done>Baseline annotated.</done>
</task>

</tasks>

<status>
Executed on 2026-03-07 in fork integration branch context (`codex/restack-audit-queue`).

Delivered:
- Removed all fixable PHPStan baseline entries using annotation-safe guards and type narrowing only.
- Updated `phpstan-baseline.neon` to an empty `ignoreErrors` set.
- Kept runtime behavior unchanged while improving static type safety in `inc/namespace.php`, `inc/class-insert-post-handler.php`, and `inc/cli/class-migrate-command.php`.

Verification:
- `composer test:phpstan` passes with no baseline ignores.
- `composer test` remains green after the type-safety updates.
</status>
