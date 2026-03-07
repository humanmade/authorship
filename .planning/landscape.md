# WordPress Multi-Author Plugin Landscape

## Active install counts (March 2026)

Data sourced from WordPress.org plugin pages and wordpress.com/plugins (which surfaces wp.org API data). Counts are rounded per wp.org convention.

| Plugin | Active installs | Last updated | Tested to | License | Maintainer |
| --- | --- | --- | --- | --- | --- |
| Simple Author Box | ~60,000 | Dec 2025 | 6.x | GPLv2+ (freemium) | WebFactory Ltd |
| Co-Authors Plus | ~20,000 | Oct 2025 | 6.9 | GPLv2+ | Automattic (volunteer) |
| PublishPress Authors | ~20,000 | Feb 2026 | 6.x | GPLv2+ (freemium) | PublishPress |
| Molongui Authorship | ~10,000 | Jan 2026 | 6.x | GPLv2+ (freemium) | Amitzy |
| WP Post Author | ~10,000 | Dec 2025 | 6.x | GPLv2+ (freemium) | AFThemes |
| HM Authorship | N/A (not on wp.org) | Jun 2024 | 6.2 | GPLv3 | Human Made |
| Byline (taxonomy) | <1,000 (abandoned) | ~2018 | — | GPLv2+ | Community |

Notes:

- Simple Author Box is primarily an author-box display plugin, not a multi-attribution system. Its high install count reflects demand for author bio boxes, not multi-author workflows.
- Co-Authors Plus has the deepest history (roots in Mark Jaquith's 2005 "Multiple Authors" plugin) and the widest theme/platform integration, but reviews and support forums indicate it is semi-abandoned with no active maintainer.
- PublishPress Authors is the most actively developed and feature-complete multi-author solution on wp.org.
- HM Authorship is not distributed via wp.org; it is installed via Composer and designed for developer/enterprise use within the Altis DXP ecosystem.
- The original "Byline" plugin (a simple taxonomy-based approach) is effectively abandoned but is importable by PublishPress Authors.

## Architectural comparison

### Data model approaches

There are three fundamental approaches to storing multi-author relationships in WordPress:

**1. Taxonomy-as-author (Co-Authors Plus, PublishPress Authors, Byline)**

Guest authors exist as taxonomy terms. Profile data (name, bio, avatar) is stored in term meta. The author-to-post relationship lives in `wp_term_relationships`. WordPress users may optionally be "linked" to their corresponding taxonomy term, but the term is the canonical entity for authorship.

Advantages: efficient querying via `WP_Tax_Query`, no `wp_users` table pollution, guest authors cannot log in by definition.

Disadvantages: parallel data structures that must be kept in sync, custom admin screens for managing guest author profiles, inconsistent object types (sometimes `WP_User`, sometimes term object), `post_author` field gets out of sync.

**2. Users-only (HM Authorship)**

Every author is a `WP_User`. Guest authors are users with a zero-capability role. Attribution is stored in a hidden taxonomy where term slugs are user IDs — the taxonomy is a relational bridge, not an entity store. The canonical entity is always `WP_User`.

Advantages: single data model, consistent API surface, guest-to-user promotion is a role change, standard WordPress profile management works for everyone, clean REST API.

Disadvantages: guest authors create real rows in `wp_users` (table growth on large sites), no active login prevention (relies on unknowable passwords), username normalization edge cases for non-ASCII names.

**3. Post meta with custom post type (Molongui Authorship)**

Guest authors are a custom post type. Attribution uses post meta to link content posts to author entities. This avoids both the taxonomy-term and wp_users approaches.

Advantages: full custom fields per guest author, clean separation from the users table.

Disadvantages: yet another entity type, complex querying, more custom admin UI.

### Feature comparison

| Feature | Co-Authors Plus | PublishPress Authors | HM Authorship | Molongui |
| --- | --- | --- | --- | --- |
| Multiple authors per post | Yes | Yes | Yes | Yes |
| Guest authors | Yes (taxonomy term) | Yes (taxonomy term or user role) | Yes (WP_User) | Yes (custom post type) |
| Block editor support | Yes (blocks) | Yes (sidebar) | Yes (sidebar) | Yes (sidebar) |
| Classic editor support | Yes | Yes | Yes | Yes |
| REST API read | Partial | Yes | Yes (first-class) | Partial |
| REST API write | No | Yes | Yes (first-class) | No |
| WP-CLI support | Yes | Yes | Yes (--authorship flag) | No |
| Author boxes / display | Blocks only | Yes (extensive) | No (developer-only) | Yes (extensive) |
| Schema.org output | No | Yes (Pro) | No | Yes |
| Migration tools | N/A (origin) | CAP, Byline import | CAP, PPA, wp-authors import | CAP, PPA, One User Avatar import |
| RSS/Atom feed support | Template tags | Automatic | RSS2 only (name list) | Yes |
| Multisite support | Yes | Yes | Yes | Yes |
| Author categories | No | Yes (Pro) | No | No |
| Custom author fields | No | Yes (Pro) | No (uses user meta) | Yes |

### PublishPress Authors data model in detail

PPA uses a custom taxonomy with slug `author`. Author profiles are taxonomy terms with profile data in `wp_termmeta`:

- `wp_terms` / `wp_term_taxonomy`: one row per author, taxonomy = `author`.
- `wp_term_relationships`: many-to-many link between posts and author terms.
- `wp_termmeta`: all profile fields — name, email, avatar path, custom fields, and `user_id` when mapped to a WordPress user.
- `wp_postmeta`: a denormalized `ppma_authors_name` field per post containing a comma-separated name string for display performance.

The REST API exposes authors on post endpoints via a `ppma_author` taxonomy field and provides a dedicated `publishpress-authors/v1/authors` endpoint for CRUD operations. Response objects include `term_id`, `user_id`, `is_guest` flag, and profile fields — exposing the internal taxonomy implementation to API consumers.

PPA can create guest authors either as pure taxonomy terms (no WordPress user account) or as users with a "Guest Author" role, giving it flexibility but also complexity.

### Co-Authors Plus data model

CAP uses a custom taxonomy (`author`) with a custom post type (`guest-author`) for guest author profiles. Regular WordPress users are represented by taxonomy terms whose slugs match the user's `user_nicename`. Guest authors are custom post type entries linked to taxonomy terms.

This creates a three-entity model: `wp_users` + `author` taxonomy terms + `guest-author` CPT posts. The `post_author` field is maintained as a "primary" author for backward compatibility but is not the source of truth.

CAP's architecture dates to 2009 and carries significant legacy. The GitHub repo is under the Automattic organization but maintenance is largely volunteer-driven, with many open issues and unanswered support threads.

### HM Authorship data model (this project)

See `docs/architecture.md` for the full source-level walkthrough. The key differentiator: a two-entity model (`wp_users` + hidden `authorship` taxonomy as relational bridge) with the taxonomy being an implementation detail that never surfaces to themes, API consumers, or admin users. The only objects anyone interacts with are `WP_User` and `WP_Post`.

## Historical lineage

The WordPress multi-author plugin space has a clear lineage:

1. **2005:** Mark Jaquith's "Multiple Authors" plugin — the first attempt.
2. **2007:** Weston Ruter's "Co-Authors" plugin — introduced the co-author concept.
3. **2009:** Daniel Bachhuber and Mohammad Jangda's "Co-Authors Plus" — complete rewrite, added guest authors via taxonomy + CPT. Became the de facto standard, adopted by WordPress VIP.
4. **2018:** "Byline" plugin — simplified taxonomy-only approach, now abandoned.
5. **2019:** PublishPress Authors — taxonomy-based, feature-rich, actively maintained. Includes CAP and Byline migration tools.
6. **2020:** Human Made Authorship — users-only approach, API-first, funded by Siemens for Altis DXP. Alpha status.

Each generation addressed limitations of the previous one. Authorship represents the most recent architectural thinking, trading feature breadth for data model purity and API correctness.
