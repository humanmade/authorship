PATCH SCAFFOLD: Editor and migration performance cleanup

Goal:
- Reduce unnecessary work in the editor UI and make long-running CLI migrations less artificially slow.

Primary targets:
- `src/components/AuthorsSelect.tsx`
- `inc/cli/class-migrate-command.php`

Problem statement:
- `AuthorsSelect` performs `setSelected()` and can trigger `apiFetch()` from render-time conditionals.
- The migration command sleeps for two seconds after every processed batch, even when operators may prefer throughput over throttling.

Planned changes:
- Move author preload and fetch behavior out of render and into `useEffect`.
- Ensure author lookup requests run once per relevant input change instead of during render retries.
- Add a CLI flag or filter for batch pause duration so large migrations can run without a fixed two-second delay.

Validation:
- Editor author chips still preload correctly for existing posts.
- No repeated fetches are triggered just by rerendering the component.
- CLI migrations preserve current behavior by default and allow faster execution when explicitly requested.

Notes:
- This scaffold is about efficiency and React correctness, not a user-facing feature change.
