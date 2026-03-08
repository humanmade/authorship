# Changelog

All notable changes to the `dknauss/authorship` fork are documented in this file.

Scope notes:
- Baseline upstream branch: `humanmade/authorship` `develop`
- This file tracks fork-local delivery history and coordination milestones.

## 2026-03-08

### Added
- Frontend modernization across Phase 03 Builds 01-12:
  - migrated JS toolchain to `@wordpress/scripts` and Node 20 baseline
  - replaced `react-sortable-hoc` with `@dnd-kit/*`
  - upgraded `react-select` to v5
  - migrated `AuthorsSelect` data wiring from HOCs to `useSelect`/`useDispatch`
  - expanded JS test coverage for init/reorder/selection/accessibility behavior
- Accessibility verification artifacts:
  - `docs/audit/accessibility-author-selector.md`
  - `docs/manual-testing-checklist.md`
  - Build-12 AT evidence artifacts and transcript ledger structure
- Coverage and analysis hardening:
  - JS coverage threshold command (`npm run test:js:coverage`)
  - fork-local Psalm advisory tooling (`composer analyse:psalm`)

### Changed
- Updated fork documentation to reflect current execution state and quality gates:
  - `README.md`
  - `docs/audit/roadmap-global.md`
  - `docs/audit/roadmap-01.md`
  - `.planning/phases/03-frontend-modernization-authorship/*`
- Scoped style linting target to SCSS files in CI workflow docs/config context.

### Fixed
- Selector remove-control accessibility label now includes author name context (`Remove author <name>`).
- Added `.playwright-cli/` to `.gitignore` to avoid local tool artifact noise.

### Coordination
- Merged fork PRs:
  - #9 `Phase 03 Build-12: AT evidence and remove-label a11y fix`
  - #10 `Docs: align README with fork workflow and quality gates`
- Pruned stale local/remote phase-chain branches no longer needed after merge.

## 2026-03-07

### Added
- Completed Phase 02 Build sequence and closeout planning/docs:
  - deterministic CLI migration controls and coverage expansion
  - PHPStan baseline reduction to zero ignored errors
  - coverage gate ratcheting policy and CI parity
- Added global roadmap and phase execution docs:
  - `docs/audit/roadmap-global.md`
  - `docs/audit/roadmap-01.md`
  - `.planning/phases/02-fork-first-delivery-authorship/*`
  - `.planning/phases/03-frontend-modernization-authorship/*`

### Changed
- Shifted to fork-first completion criteria:
  - phase completion decoupled from upstream merge acceptance
  - upstream submission treated as coordination, not delivery gating

### Coordination
- Opened upstream HM packaging wave:
  - #162 Tooling/CI modernization
  - #163 Guest author + observability hardening
  - #164 CLI migration reliability
  - #165 Editor asset fix
- Opened upstream Phase 03 PR set:
  - #167 Build-01, #168 Build-02, #169 Build-03, #170 Build-04, #171 docs, #172 Build-11
- Tagged submission checkpoint: `phase-02-submission-2026-03-07`.

## 2026-03-06

### Added
- Initial fork audit/planning baseline and build queue scaffolding.
- Early audit evidence expansion and roadmap initialization for Phase 01 work.

## Follow-up status

- Assistive-tech matrix: manual VoiceOver pass is recorded; NVDA transcript capture remains an open follow-up item in `docs/manual-testing-checklist.md` (`UI-06`).

## See also

- `docs/audit/roadmap-global.md`
- `docs/audit/roadmap-01.md`
- `.planning/phases/`
