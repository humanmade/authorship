# Known Gaps, Security Notes, and Hardening Opportunities

This document supplements the Phase 01 audit (`docs/audit/HM_WPCS_audit.md`) with additional findings from the source-level architecture review.

## Security

### Guest author login is not actively blocked

**Severity:** Low (mitigated by design, but defense-in-depth gap)

Guest authors are `WP_User` rows with the `guest-author` role (zero capabilities). Authorship does not register an `authenticate` filter or `wp_login` action to prevent login. The defense relies on:

- Passwords are random 24-character strings generated at creation time, never returned to any caller.
- Email is set to empty string by default, preventing password reset.
- Even if authenticated, the session has zero capabilities.

**Risk scenario:** An Administrator creates a guest author with an email address. The guest author uses WordPress's password reset flow to obtain credentials. They log in with an empty-capability session. The session itself may have side effects with plugins that check `is_user_logged_in()` rather than specific capabilities.

**Recommendation:** Add an `authenticate` filter that returns `WP_Error` for users whose only role is `guest-author`. This is a one-line defense-in-depth addition:

```php
add_filter( 'authenticate', function( $user ) {
    if ( $user instanceof WP_User && in_array( GUEST_ROLE, $user->roles, true ) && count( $user->roles ) === 1 ) {
        return new WP_Error( 'guest_author_login_blocked', __( 'Guest authors cannot log in.', 'authorship' ) );
    }
    return $user;
}, 100, 1 );
```

### Guest author username normalization

**Severity:** Low (edge case)

`create_item()` in `class-users-controller.php:194-195` derives usernames from the display name:

```php
$username = sanitize_title( sanitize_user( $request->get_param( 'name' ), true ) );
$username = preg_replace( '/[^a-z0-9]/', '', $username );
```

This can produce empty strings for non-ASCII names (e.g., Japanese, Arabic, Chinese names) or collide for near-duplicate display names. See `docs/audit/patch_scaffolds/01-02-security_build.md` for the planned hardening.

### Signup validation filter scope

**Severity:** Low (code hygiene)

`create_item()` adds an anonymous `wpmu_validate_user_signup` filter and never removes it. This is a request-scoped side effect that is inconsistent with the pattern used in `get_items()` where the filter is explicitly removed after use. See `docs/audit/patch_scaffolds/01-02-security_build.md`.

## Data integrity

### Post-insert author assignment failures are silent

`InsertPostHandler::action_wp_insert_post()` catches exceptions from `set_authors()` and discards them. This means author attribution can silently fail during post save, migration, or programmatic post creation. The REST API path handles the same exceptions by returning `WP_Error`. See `docs/audit/patch_scaffolds/01-02-observability_build.md`.

### `post_author` field divergence

WordPress core's `post_author` field on `wp_posts` is not the source of truth for Authorship — the hidden taxonomy is. However, `post_author` continues to exist and may be set/read by other plugins and themes. Authorship does not currently synchronize `post_author` with the first attributed author.

This can cause divergence where `$post->post_author` says user A but Authorship says users B and C. Theme code that reads `post_author` directly (rather than using `the_author()` or Authorship's template functions) will show stale data.

### Object cache considerations

Taxonomy term lookups and `get_users()` calls are cached by WordPress's object cache. On persistent cache backends (Redis, Memcached), stale cache entries after attribution changes could show incorrect authors. Authorship relies on WordPress's built-in cache invalidation for `wp_set_post_terms()` and `get_users()`, which is generally correct but worth noting for debugging.

## Performance

### Author archive queries

Author archives use the `action_pre_get_posts()` taxonomy rewrite, which converts `author` and `author_name` query vars into `tax_query` clauses. This is more performant than a post meta query but involves an additional join compared to the native `post_author` column index.

On sites with Elasticsearch (e.g., WordPress VIP), this is likely irrelevant as the taxonomy query will be handled by ES. On MySQL-only sites with very large post tables, the join performance should be tested.

### Editor component render behavior

`AuthorsSelect.tsx` performs state initialization and can trigger `apiFetch()` from render-time conditionals. See `docs/audit/patch_scaffolds/01-02-performance_build.md`.

## Feed output limitations

- RSS2: outputs comma-separated name list only via `the_author` filter. No structured metadata.
- Atom: no Authorship-specific handling at all.
- JSON Feed: not addressed.
- No `dc:creator` output for individual co-authors.
- No Schema.org / JSON-LD author metadata in feeds.

See `docs/byline-spec-plan.md` for the proposed Byline spec implementation that would address structured feed output.

## Compatibility

### WordPress version

Plugin header declares `Requires at least: 5.4`, tested up to 6.2. The 6.2 cap is stale — the plugin likely works with current WordPress but testing has not been updated.

### PHP version

Plugin requires PHP 7.2+. Tooling (PHPCS, PHPStan) is pinned to PHP 7.4 and does not run on PHP 8.5 without deprecation suppression. See `docs/audit/foundation-quality-baseline.md`.

### Multisite

The plugin has multisite-specific tests and uses `'blog_id' => 0` in `get_users()` calls to search across all sites. Guest authors created on one site exist in the shared `wp_users` table.

### Theme compatibility

Authorship intercepts `the_author`, author query vars, and capability checks transparently. Themes that use standard WordPress template tags (`the_author()`, `get_the_author()`, author archive templates) will work. Themes that read `$post->post_author` directly may show stale data (see `post_author` divergence above).

### Plugin compatibility

Co-Authors Plus and PublishPress Authors both use the `author` taxonomy slug. Authorship uses `authorship`. These should not conflict if multiple plugins are active, though running multiple multi-author plugins simultaneously is not recommended. Authorship provides WP-CLI migration commands for both CAP and PPA data.
