# Authorship Author Selector Accessibility Audit (WCAG 2.1 AA)

Last updated: 2026-03-07 (America/Edmonton)

## Scope

Audit target:
- Editor-side author selector implemented in:
  - `src/components/AuthorsSelect.tsx`
  - `src/components/SortableSelectContainer.tsx`
  - `src/components/SortableMultiValueElement.tsx`
  - `src/style.scss`

Audit method:
- Code-based WCAG 2.1 AA conformance review focused on:
  - keyboard operability
  - form semantics and labeling
  - assistive technology status messaging
  - drag-and-drop interaction accessibility

Out of scope for this slice:
- live screen-reader session transcript capture
- cross-browser AT matrix execution
- remediation implementation code changes

## Baseline statement check

Current README accessibility statement says:
- keyboard-only use is fully accessible
- screen reader support is not fully accessible

Reference: `README.md:214-223`.

Post-Build-02 (`@dnd-kit`) and Build-03 (`react-select` v5) behavior needs re-validation against this statement.

## Findings

| ID | Severity | WCAG 2.1 AA criteria | Finding | Evidence |
|---|---|---|---|---|
| A11Y-01 | High | `2.1.1 Keyboard` | Reorder interaction appears pointer-first with no explicit keyboard drag sensor configured. | `src/components/SortableSelectContainer.tsx:55-61` configures only `PointerSensor`. |
| A11Y-02 | High | `4.1.3 Status Messages` | Reorder/create/remove operations have no explicit ARIA live announcements for screen readers. | `src/components/SortableSelectContainer.tsx:88-105` has no `accessibility` announcements on `DndContext`; `src/components/AuthorsSelect.tsx:191-216` mutates selection state without assistive status output. |
| A11Y-03 | Medium | `3.3.2 Labels or Instructions`, `4.1.2 Name, Role, Value` | The selector relies on placeholder text and does not set an explicit programmatic label in plugin render path. | Placeholder only at `src/components/SortableSelectContainer.tsx:19,100`; plugin wrapper at `src/plugin.tsx:13-15` adds no explicit label props. |
| A11Y-04 | Medium | `3.3.2 Labels or Instructions` | Drag affordance/instructions are not explicitly conveyed in component code for non-visual users. | Draggable listeners attached in `src/components/SortableMultiValueElement.tsx:37`; no matching instruction text in selector props. |

## Positive evidence

| ID | WCAG signal | Observation | Evidence |
|---|---|---|---|
| POS-01 | Focus visibility support | Focus border/box-shadow styles are explicitly set for the text input. | `src/style.scss:81-88` |
| POS-02 | Decorative image handling | Author avatars are treated as decorative (`alt=""`) and text label is present. | `src/components/SortableSelectContainer.tsx:34-42` |

## Assumption updates

1. Assumption to revise: "keyboard accessibility is fully covered."
- Build-02 changed drag behavior to `@dnd-kit`; keyboard reorder conformance should be treated as unverified until runtime testing confirms parity.

2. Assumption to revise: "screen-reader issue is generic only."
- Findings suggest specific missing status/instruction semantics that can be scoped and fixed incrementally.

3. Assumption to revise: "component swap is the only path."
- A phased fix path exists without immediate full replacement: explicit labels, keyboard sensor coverage, and announcements can be delivered first.

## Remediation backlog (proposed Build-08+)

### R1 (High): keyboard-operable reorder path
- Add `KeyboardSensor` and sortable keyboard coordinate mapping in selector DnD setup.
- Acceptance:
  - author order can be changed without pointer input
  - behavior is deterministic with at least one integration-style interaction test

### R2 (High): screen reader status announcements
- Add live announcements for create/remove/reorder outcomes.
- Acceptance:
  - status message emitted for each reorder and create operation
  - no visual regression to existing notice UX

### R3 (Medium): explicit labeling and instructions
- Provide explicit `aria-label`/`aria-labelledby` and concise usage instructions for the selector and reorder flow.
- Acceptance:
  - selector has a stable programmatic name independent of placeholder text
  - instruction copy exists for reorder interaction expectations

### R4 (Medium, optional spike): component replacement decision
- Evaluate migration from custom `react-select` + DnD composition to WordPress-native alternatives where feasible.
- Acceptance:
  - decision record with tradeoffs, effort estimate, and risk

## Verification plan for remediation

When implementation starts, verify with:
- keyboard-only stepped checks in the manual checklist
- screen-reader smoke pass (NVDA/VoiceOver)
- Playwright editor flow checks where feasible
- regression checks: `npm run lint:js`, `npm run test:js -- --ci`, `npm run build`, `composer test`

## Residual risk

- Accessibility statement drift risk: README claims may not fully match post-modernization behavior until runtime re-validation is completed.
- Upstream adoption risk: if upstream PR cadence is slow, fork should treat accessibility remediation as fork-local delivery scope and proceed.
