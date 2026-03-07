# Byline Spec Assessment and Implementation Plan

## What is Byline?

Byline (bylinespec.org) is an open specification (v0.1.0, January 2026, CC0 licensed) that extends RSS 2.0, Atom, and JSON Feed with structured author identity, context, and content perspective metadata. It was created by Terry Godier to address "content collapse" — the loss of context when diverse content sources arrive in a unified feed reader stream.

The spec adds an XML namespace (`https://bylinespec.org/1.0`) with elements for:

- **`byline:person`**: structured author identity (name, bio, avatar, profile links, /now page, /uses page, theme colors).
- **`byline:org`**: organization metadata (name, URL, type).
- **`byline:author ref`**: per-item author attribution referencing channel-level person definitions.
- **`byline:role`**: author's relationship to the content (creator, editor, guest, staff, contributor, bot).
- **`byline:perspective`**: content intent type (personal, reporting, analysis, official, sponsored, satire, review, tutorial, etc.).
- **`byline:affiliation`**: conflict-of-interest disclosure (employer, investor, sponsor, etc.).
- **`byline:theme`**: author brand colors (optional, hint-only).

Current status: 4 GitHub stars, 3 commits, zero implementations, zero feed reader support. Very early stage.

## Why Authorship is a natural fit

Authorship's data model maps cleanly to Byline's elements because every author is a `WP_User`:

| Byline element | Authorship data source | Notes |
| --- | --- | --- |
| `byline:person.name` | `WP_User->display_name` | Direct |
| `byline:person.context` | `WP_User->description` | Cap at 280 chars per spec recommendation |
| `byline:person.url` | `WP_User->user_url` | Direct |
| `byline:person.avatar` | `get_avatar_url( $user->ID )` | Direct |
| `byline:person.id` | User slug or ID | Unique within feed |
| `byline:author ref` | `get_authors( $post )` | Ordered array already available |
| `byline:role` | WordPress role mapping | `guest-author` → `guest`, `administrator`/`editor` → `staff`, etc. |
| `byline:profile` | User meta (social URLs) | Requires convention for meta keys |
| `byline:perspective` | **Not available** | Requires new post meta or taxonomy |
| `byline:org` | Site-level settings | `get_bloginfo()` for single-site |
| `byline:affiliation` | **Not available** | Requires new per-post or per-author meta |
| `byline:person.now` | User meta | Requires convention for meta key |
| `byline:person.uses` | User meta | Requires convention for meta key |
| `byline:theme` | User meta | Optional |

Taxonomy-based plugins (PPA, CAP) would need to resolve term meta → profile data, handle the user/term duality, and work around the inconsistent object types. Authorship just calls `get_userdata()`.

## Current feed implementation gap

Authorship's entire feed integration is one function:

```php
function filter_the_author_for_rss( ?string $display_name ) : ?string {
    if ( ! is_feed( 'rss2' ) ) {
        return $display_name;
    }
    $post = get_post();
    if ( ! $post ) {
        return $display_name;
    }
    return get_author_names( $post );
}
```

This only handles RSS2, not Atom. It only outputs a plain-text name list. No structured metadata.

## Implementation plan

### Recommended approach: companion module

Implement as a separate file (`inc/byline-feed.php`) conditionally loaded in `plugin.php`, or as an independent companion plugin. This keeps the core lean and lets the Byline implementation evolve independently.

### Phase 1: structural elements (data already available)

Hook into WordPress feed actions to output Byline namespace and elements using data Authorship already has.

**WordPress feed hooks to use:**

- `rss2_ns` — add `xmlns:byline="https://bylinespec.org/1.0"` to the `<rss>` element.
- `rss2_head` — output `<byline:contributors>` with `<byline:person>` for each author who contributed to posts in the current feed query.
- `rss2_item` — output `<byline:author ref="..."/>` and `<byline:role>` per item.
- `atom_ns`, `atom_head`, `atom_entry` — parallel implementation for Atom feeds.

**Minimal RSS2 output example:**

```xml
<rss version="2.0" xmlns:byline="https://bylinespec.org/1.0">
  <channel>
    <byline:contributors>
      <byline:person id="jdoe">
        <byline:name>Jane Doe</byline:name>
        <byline:context>Staff writer covering technology.</byline:context>
        <byline:url>https://example.com/author/jdoe</byline:url>
        <byline:avatar>https://example.com/avatars/jdoe.jpg</byline:avatar>
      </byline:person>
    </byline:contributors>

    <item>
      <title>Example Post</title>
      <byline:author ref="jdoe"/>
      <byline:role>staff</byline:role>
    </item>
  </channel>
</rss>
```

**Role mapping logic:**

```php
function get_byline_role( WP_User $user ) : string {
    if ( in_array( GUEST_ROLE, $user->roles, true ) ) {
        return 'guest';
    }
    if ( user_can( $user, 'edit_others_posts' ) ) {
        return 'staff';
    }
    return 'contributor';
}
```

Filterable via `apply_filters( 'authorship_byline_role', $role, $user, $post )`.

### Phase 2: perspective (requires new data)

> **Adoption gate:** Phase 2 should not begin until at least one of these signals is present: (a) a feed reader ships Byline namespace support, (b) the spec reaches v0.5.0 or higher, or (c) a second CMS/plugin implements Phase 1-level support. Without external adoption signal, the investment in new editorial UI is premature.

The `byline:perspective` element is the spec's most important feature for addressing content collapse. It requires editorial metadata that WordPress doesn't natively provide.

**Adoption caution:** Adding a `perspective` field is technically simple (post meta + sidebar control), but getting editors to actually populate it is a UX and workflow adoption problem. Unlike structural elements in Phase 1 (which use data that already exists), `perspective` requires conscious editorial input on every post. Without clear value to the author — such as feed reader UIs that visibly differentiate perspectives — adoption will be low and the field will be empty on most posts. The filter hook (`authorship_byline_perspective`) mitigates this by allowing automated population from existing data (categories, post formats, etc.), but this trades accuracy for coverage.

Options for storage:

- **Post meta** (`_authorship_perspective`): simplest, least queryable. Sufficient for feed output.
- **Taxonomy** (`authorship_perspective`): more queryable, allows filtering by perspective type. Heavier.
- **Post format mapping**: creative but lossy — WordPress post formats don't cover the Byline perspective vocabulary.

Recommendation: start with post meta and a block editor sidebar control. Add a filter (`authorship_byline_perspective`) so themes can compute perspective from other data (categories, custom fields, etc.).

### Phase 3: extended identity (progressive enhancement)

> **Adoption gate:** Same gate as Phase 2. Do not proceed without external adoption signal.

Add support for `byline:profile`, `byline:now`, `byline:uses` using user meta fields with a defined key convention:

- `authorship_profile_mastodon`, `authorship_profile_github`, etc.
- `authorship_now_url`
- `authorship_uses_url`

Register these fields on the user profile edit screen. Output them in `byline:person` elements when present.

### Phase 4: affiliation and organization

> **Adoption gate:** Same gate as Phases 2-3, plus the spec should be at v1.0+ before investing in affiliation/org elements, as these are the most likely to change during spec development.

These require the most new UI work and are editorially complex. Defer until the spec stabilizes and feed reader adoption provides validation signal.

## Strategic considerations

Being the first CMS plugin to implement the Byline spec would give this fork influence over how the spec evolves. The spec is CC0 licensed and at v0.1.0 — early implementors always shape specifications.

The implementation cost is low for Phase 1 (a few dozen lines hooking into existing WordPress feed actions with data Authorship already has). The risk is also low since Byline elements are ignored by feed readers that don't support them — standard `<author>` elements should always be present alongside Byline data for backward compatibility.

The Byline spec explicitly states: "Always include standard elements for maximum compatibility. Byline is additive."

### Adoption gate definition

The spec currently has 4 GitHub stars, 3 commits, zero implementations, and zero feed reader support (as of March 2026). Phase 1 is a low-cost, low-risk bet that's worth making regardless of adoption trajectory. Phases 2-4 require new editorial data and UI work with diminishing returns if the spec stalls.

**Go/no-go criteria for Phases 2-4:**
- **Go signal (any one):** At least one feed reader ships Byline namespace support; the spec reaches v0.5.0+; a second CMS or plugin implements Phase 1-level support.
- **No-go signal:** Spec repo inactive for 12+ months with no adoption. In this case, Phase 1 output remains harmless (additive namespace elements ignored by non-supporting readers) and requires no maintenance.

Re-evaluate at each phase boundary rather than committing to the full 4-phase plan upfront.
