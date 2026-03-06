PATCH SCAFFOLD: Guest author creation hardening

Goal:
- Make guest author creation safer and more predictable for arbitrary names without changing the public API shape.

Primary targets:
- `inc/class-users-controller.php`
- `tests/phpunit/test-rest-api-user-endpoint.php`

Problem statement:
- Guest author `username` is derived from `name` and reduced to lowercase ASCII alphanumerics.
- That normalization can produce empty or ambiguous usernames for non-ASCII names and near-duplicate display names.
- `create_item()` also installs a temporary `wpmu_validate_user_signup` filter and never removes it within the request.

Planned changes:
- Reject or repair empty normalized usernames.
- Guarantee a unique fallback login when normalized names collide.
- Replace the anonymous temporary signup-validation filter with a removable callback and remove it immediately after user creation.
- Extend REST tests to cover duplicate-name and non-ASCII-name creation paths.

Validation:
- PHPUnit coverage for guest-author creation remains green.
- REST creation still works for simple names already covered by the current tests.
- New tests prove correct behavior for ambiguous or non-ASCII input.

Notes:
- The current implementation is functional for common names; this patch is about edge-case hardening.
