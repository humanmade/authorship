---
phase: 03-frontend-modernization-authorship
plan: 03-Build-01
type: build
wave: 1
depends_on: ["02-fork-first-delivery-authorship/02-Build-13"]
files_modified:
  - ".planning/phases/03-frontend-modernization-authorship/03-01-PLAN.md"
  - "package.json"
  - "package-lock.json"
  - "inc/namespace.php"
  - "src/components/AuthorsSelect.tsx"
  - "tests/js/**/*.test.*"
  - "docs/audit/roadmap-global.md"
files_deleted:
  - ".babelrc.js"
  - ".config/webpack.config.js"
  - ".config/webpack.config.prod.js"
autonomous: true
user_setup: []
must_haves:
  truths:
    - "Build tooling migrates to @wordpress/scripts with Node 20 baseline and no intended runtime behavior changes."
    - "Editor asset enqueue supports both legacy build artifacts and @wordpress/scripts asset metadata."
  artifacts:
    - path: "package.json"
      provides: "Updated Node/tooling scripts and JS test commands"
  key_links: []
---

<objective>
Migrate the frontend toolchain baseline to Node 20 + @wordpress/scripts and establish first JS tests around AuthorsSelect initialization behavior.
</objective>

<tasks>

<task type="auto">
  <name>03-01-01 Migrate frontend toolchain baseline</name>
  <files>package.json, package-lock.json</files>
  <action>
    - Replace custom webpack helper scripts with @wordpress/scripts entry points.
    - Update Node engine baseline to 20.
    - Keep lint scripts functional.
    - **Destructive changes:** Delete legacy build config files that are replaced by @wordpress/scripts defaults:
      - `.babelrc.js` (Babel config — replaced by @wordpress/scripts built-in Babel preset)
      - `.config/webpack.config.js` (dev webpack config — replaced by @wordpress/scripts or custom `webpack.config.js` at root)
      - `.config/webpack.config.prod.js` (prod webpack config — replaced by @wordpress/scripts `build` command)
      - The `.config/` directory itself if empty after removal.
  </action>
  <verify>`npm run lint:js` and `npm run build` pass with the new toolchain.</verify>
  <done>Toolchain migration baseline is implemented.</done>
</task>

<task type="auto">
  <name>03-01-02 Preserve editor asset runtime compatibility</name>
  <files>inc/namespace.php</files>
  <action>
    - Add enqueue support for `build/index.asset.php` and associated output files.
    - Retain compatibility with existing legacy manifest/direct-file fallback paths.
  </action>
  <verify>Asset registration logic resolves dependencies/version metadata from @wordpress/scripts outputs when present.</verify>
  <done>Runtime enqueue compatibility supports both build styles.</done>
</task>

<task type="auto">
  <name>03-01-03 Add first JS tests for AuthorsSelect init behavior</name>
  <files>src/components/AuthorsSelect.tsx, tests/js/*</files>
  <action>
    - Add JS test harness and initial tests for initialization paths.
    - Cover preloaded-author initialization and remote-load initialization behavior.
  </action>
  <verify>`npm run test:js` passes with deterministic assertions.</verify>
  <done>Initial JS test baseline for AuthorsSelect is in place.</done>
</task>

</tasks>

<status>
Started on 2026-03-07 on branch `codex/phase-03-build-01-toolchain`.

Execution state:
- In progress.
</status>
