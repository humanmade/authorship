PATCH SCAFFOLD: Post insert observability hardening

Goal:
- Make post author-assignment failures observable and test-visible without changing successful request behavior.

Primary targets:
- `inc/class-insert-post-handler.php`
- `tests/phpunit/test-post-saving.php`

Problem statement:
- `InsertPostHandler::action_wp_insert_post()` catches `Exception` from `set_authors()` and silently discards it.
- This hides assignment failures from operators and tests, reducing confidence in migration and editor flows.

Planned changes:
- Replace the empty catch block with a deterministic failure path (for example logging hook, explicit `do_action`, or other testable signal).
- Keep the current non-fatal flow for post insert to avoid introducing user-facing fatal errors.
- Add PHPUnit coverage that verifies failure signaling behavior when author assignment throws.

Validation:
- Existing post-save behavior remains compatible for successful paths.
- New tests confirm failures are no longer silently swallowed.
- No regressions in existing post-saving test coverage.

Notes:
- This scaffold is about correctness and observability, not about changing successful attribution behavior.
