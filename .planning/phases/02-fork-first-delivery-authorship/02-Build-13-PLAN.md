---
phase: 02-fork-first-delivery-authorship
plan: 02-Build-13
type: build
wave: 1
depends_on: ["02-Build-12"]
files_modified:
  - ".planning/phases/02-fork-first-delivery-authorship/02-01-PLAN.md"
  - "docs/audit/roadmap-01.md"
  - "docs/audit/roadmap-global.md"
autonomous: false
user_setup:
  - "User must have push access to the fork remote and ability to open PRs against humanmade/authorship."
must_haves:
  truths:
    - "Each PR is independently mergeable against upstream develop and passes upstream CI."
    - "PRs are submitted but not depended on — fork proceeds regardless of upstream response."
  artifacts:
    - path: "docs/audit/roadmap-global.md"
      provides: "PR submission record with numbers and links"
  key_links: []
---

<objective>
Prepare and submit upstream PRs that package the Phase 01–02 hardening work into focused, reviewable units for Human Made.
</objective>

<tasks>

<task type="auto">
  <name>02-13-01 Prepare PR A branch: Tooling and CI modernization</name>
  <files>composer.json, composer.lock, phpstan.neon.dist, phpstan-baseline.neon, phpunit.xml.dist, .github/workflows/php-standards.yml, .github/workflows/test.yml, CONTRIBUTING.md, tests/phpunit/includes/check-coverage-threshold.php, tests/wp-tests-config.php</files>
  <action>
    - Create a clean branch from upstream develop.
    - Cherry-pick or reconstruct tooling/CI changes only.
    - Verify upstream CI compatibility (no fork-only dependencies).
    - Write PR description explaining the tooling improvements.
  </action>
  <verify>Branch builds and tests pass against upstream develop. No runtime code changes included.</verify>
  <done>PR A branch ready.</done>
</task>

<task type="auto">
  <name>02-13-02 Prepare PR B branch: Guest author + post-insert hardening</name>
  <files>inc/class-users-controller.php, inc/class-insert-post-handler.php, tests/phpunit/test-rest-api-user-endpoint.php, tests/phpunit/test-post-saving.php</files>
  <action>
    - Create a clean branch from upstream develop.
    - Cherry-pick or reconstruct security/observability changes only.
    - Verify all new tests pass against upstream develop without other fork changes.
    - Write PR description covering the security rationale.
  </action>
  <verify>Branch passes upstream CI. Only security/observability changes included.</verify>
  <done>PR B branch ready.</done>
</task>

<task type="auto">
  <name>02-13-03 Prepare PR C branch: CLI migration improvements</name>
  <files>inc/cli/class-migrate-command.php, tests/phpunit/test-cli.php, tests/phpunit/test-multisite.php, tests/phpunit/includes/testcase.php, README.md</files>
  <action>
    - Create a clean branch from upstream develop.
    - Cherry-pick or reconstruct CLI migration changes only (pacing hooks, post-type validation, multisite stabilization).
    - Verify all new CLI tests pass against upstream develop.
    - Write PR description covering migration reliability improvements.
  </action>
  <verify>Branch passes upstream CI. Only CLI/migration changes included.</verify>
  <done>PR C branch ready.</done>
</task>

<task type="auto">
  <name>02-13-04 Prepare PR D branch: Editor asset fix</name>
  <files>src/components/AuthorsSelect.tsx</files>
  <action>
    - Create a clean branch from upstream develop.
    - Cherry-pick or reconstruct the useEffect fix and lodash.get removal.
    - Verify JS build succeeds.
    - Write PR description explaining the render side-effect fix.
  </action>
  <verify>Branch builds JS assets. Only editor component changes included.</verify>
  <done>PR D branch ready.</done>
</task>

<task type="user">
  <name>02-13-05 Submit PRs and create umbrella issue</name>
  <files>docs/audit/roadmap-global.md</files>
  <action>
    - Open all 4 PRs against humanmade/authorship develop branch.
    - Create an umbrella issue linking all PRs with a summary of the hardening effort.
    - Record PR numbers and links in roadmap-global.md.
    - Optionally close or note supersession of existing #160/#161 if no active review signal.
  </action>
  <verify>All 4 PRs are open and linked from umbrella issue. Roadmap updated with PR references.</verify>
  <done>PRs submitted.</done>
</task>

</tasks>

<status>
Queued on 2026-03-07 in fork integration branch context (`codex/restack-audit-queue`).

Execution state:
- Not started.
</status>
