# Authorship — Architecture Reference

## Overview

Authorship is a WordPress plugin for multi-author attribution, created by Human Made (John Billion) and funded by Siemens for the Altis DXP platform. This document describes the internal architecture as verified by source-level audit of the `develop` branch (v0.2.17).

## Design philosophy

Authorship makes three architectural bets that distinguish it from every other multi-author plugin in the WordPress ecosystem:

1. **Authors are users, not taxonomy terms.** Every author — including guest authors — is a real `WP_User` row. There is no parallel entity type.
2. **API is a first-class citizen.** REST API read/write, WP-CLI support, and embedded author objects in `_embed` responses are all core features, not afterthoughts.
3. **Invisible integration.** The plugin intercepts WordPress query vars, capability checks, and REST link relations so that existing theme code, archive pages, and editorial workflows continue to work without modification.

## Data model

### Attribution storage: the hidden taxonomy bridge

Attribution (which users are credited on which posts) is stored using a **hidden custom taxonomy** named `authorship`. This taxonomy is registered with `public => false` and `show_in_rest => false`, making it invisible to end users, themes, and the REST API.

The key design trick: **taxonomy term slugs are user IDs cast to strings.** When user 42 is attributed to a post, a term with slug `"42"` is created in the `authorship` taxonomy and assigned to that post via `wp_term_relationships`.

Source: `inc/taxonomy.php:14-65`

```
const TAXONOMY = 'authorship';

register_taxonomy( TAXONOMY, $post_types, [
    'hierarchical' => false,
    'sort'         => true,
    'public'       => false,
    'show_in_rest' => false,
    ...
]);
```

This gives Authorship the query performance benefits of taxonomy joins (`WP_Tax_Query`) while keeping the data model purely user-centric at the API surface.

### Writing attribution

`set_authors()` in `inc/template.php:150-178` converts user IDs to string slugs and writes them via `wp_set_post_terms()`:

```
$terms = wp_set_post_terms( $post->ID, array_map( 'strval', $authors ), TAXONOMY );
```

### Reading attribution

`get_author_ids()` in `inc/template.php:23-40` reads taxonomy terms and reverses the slug-to-ID mapping:

```
$authors = wp_get_post_terms( $post->ID, TAXONOMY );
return array_map( function ( WP_Term $term ) : int {
    return intval( $term->slug );
}, $authors );
```

`get_authors()` then resolves those IDs into `WP_User` objects via `get_users()` with `'orderby' => 'include'` to preserve attribution order.

### Guest authors

Guest authors are `WP_User` rows with the custom role `guest-author`, registered with an **empty capabilities array**:

```
const GUEST_ROLE = 'guest-author';

function register_roles_and_caps() : void {
    add_role( GUEST_ROLE, __( 'Guest Author', 'authorship' ), [] );
}
```

Source: `inc/namespace.php:27,297-299`

When a guest author is created via the REST API (`authorship/v1/users`), the `prepare_item_for_database()` method in `inc/class-users-controller.php:228-238` enforces:

- **Password:** random 24-character string via `wp_generate_password(24)`, never returned to the caller.
- **Email:** set to empty string unless the requesting user has `create_users` capability (Administrators only).
- **Role:** always forced to `['guest-author']` regardless of request input.
- **Username:** derived from the display name, sanitized to lowercase ASCII alphanumerics.

**Security note:** Authorship does **not** actively block login for the Guest Author role. There is no `authenticate` filter. The defense relies on the password being unknowable and the role having zero capabilities. If an Administrator creates a guest author with an email address, the password-reset flow could theoretically be used to obtain credentials. The resulting session would have no capabilities, but the session would exist. This is documented as a known gap in the audit.

### Query rewriting

`action_pre_get_posts()` in `inc/namespace.php:577-688` intercepts WordPress author query variables and transparently rewrites them to taxonomy queries:

1. Records the original values of `author`, `author_name`, `author__in`, `author__not_in`.
2. Clears those query vars from `WP_Query`.
3. Adds a `tax_query` clause against the `authorship` taxonomy using user IDs as term slugs.
4. Registers a `posts_pre_query` filter that restores the original query vars after the query executes.

This makes author archives, `WP_Query` author parameters, and theme functions like `get_author_posts_url()` work transparently without any theme modifications.

### Capability mapping

Authorship defines two custom capabilities:

- `attribute_post_type` — maps to `edit_others_posts` for the relevant post type.
- `create_guest_authors` — maps to `edit_others_posts`.

The `filter_map_meta_cap_for_editing()` function in `inc/namespace.php:118-222` grants attributed authors the ability to edit, delete, and read posts they're attributed to, as if they were the `post_author`. This means an Author-role user attributed to a post can edit it, while a Contributor attributed to a post can edit it only while in draft.

## File structure

```
plugin.php                          # Bootstrap, requires, conditional admin/CLI loading
inc/
  namespace.php                     # Core hooks, REST fields, capability filters, query rewriting, feed output
  taxonomy.php                      # Hidden taxonomy registration, "Mine" view filter
  template.php                      # get_authors(), set_authors(), get_author_names*(), user_is_author()
  class-users-controller.php        # REST API: authorship/v1/users (search + guest author creation)
  class-insert-post-handler.php     # wp_insert_post hook handling for attribution during post save
  admin.php                         # Admin-only: classic editor metabox, asset enqueuing
  cli/
    namespace.php                   # WP-CLI bootstrap
    class-migrate-command.php       # Migration commands (wp-authors, ppa)
src/
  index.tsx                         # Block editor entry point
  plugin.tsx                        # SlotFill plugin registration
  style.scss                        # Editor styles
  components/
    AuthorsSelect.tsx               # Author selection UI component
  utils/                            # TypeScript utilities
tests/
  phpunit/                          # PHPUnit test suite
    test-archives.php
    test-capabilities.php
    test-cli.php
    test-feeds.php
    test-multisite.php
    test-post-saving.php
    test-rest-api-post-endpoint.php
    test-rest-api-user-endpoint.php
```

## Feed output

Current feed support is minimal: a single `the_author` filter that returns `get_author_names()` for RSS2 feeds only. Atom feeds are not handled. There is no structured author metadata in feeds beyond the plain-text name list.

Source: `inc/namespace.php:57,95-107`

## REST API

### `authorship` field on post endpoints

Added to all post types with `author` support. Readable (returns array of user IDs) and writable (calls `set_authors()`). User objects are embedded in `_embedded['wp:authorship']` when `_embed` is set.

The plugin removes the default `wp:action-assign-author` link relation and replaces it with a custom `authorship:action-assign-authorship` relation, which suppresses the default author dropdown in the block editor.

### `authorship/v1/users` endpoint

- **GET:** Search attributable users. Requires `attribute_post_type` capability. Exposes minimal user information (no email, no capabilities). Removes the `has_published_posts` constraint so unpublished authors are findable.
- **POST:** Create guest authors. Requires `create_guest_authors` capability. Rejects requests that attempt to set `roles` or `password` parameters. Email requires `create_users` capability.

## Editor UI

The block editor integration is built in TypeScript with React, using the `PluginDocumentSettingPanel` SlotFill. It replaces the default Author panel with a search-as-you-type multi-select component that queries `authorship/v1/users`. Author data is preloaded via `wp_localize_script` to avoid an initial API call.

The classic editor uses a standard metabox registered in `inc/admin.php`.
