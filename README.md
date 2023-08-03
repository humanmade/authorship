# Authorship

Stable tag: 0.2.17  
Requires at least: 5.4  
Tested up to: 6.2  
Requires PHP: 7.2  
License: GPL v3 or later  
Contributors: johnbillion, humanmade  

A modern approach to author attribution in WordPress.

## Description

Authorship is a modern approach to author attribution in WordPress. It supports attributing posts to multiple authors and to guest authors, provides a great UI, and treats API access to author data as a first-class citizen.

Authorship is currently geared toward developers who are implementing custom solutions on WordPress. For example, it doesn't provide an option to automatically display author profiles at the bottom of a post. In the future it will include wider support for existing themes and useful features for implementors and site builders.

---

- [Current Status](#current-status)
- [Features](#features)
- [Installation](#installation)
- [Design Decisions](#design-decisions)
- [Template Functions](#template-functions)
- [REST API](#rest-api)
- [WP-CLI](#wp-cli)
- [Email Notifications](#email-notifications)
- [Accessibility](#accessibility)
- [Security, Privileges, and Privacy](#security-privileges-and-privacy)
- [Contributing](#contributing)
- [Team](#team)
- [License](#license)
- [Alternatives](#alternatives)

---

## Current Status

**Alpha**. Generally very functional but several features are still in development.

## Features

* [X] Multiple authors per post
* [X] Guest authors (that can be created in place on the post editing screen)
* [X] A convenient and user-friendly UI that feels like a part of WordPress
* [X] Works with the block editor
* [ ] Works with the classic editor
* [X] Full CRUD support in the REST API and WP-CLI
* [X] Full support in RSS feeds
* [ ] Full support in Atom feeds
* [X] Fine-grained user permission controls

_Features without a checkmark are still work in progress._

## Installation

### For normal use

    composer require humanmade/authorship

### For development use

* Clone this repo into your plugins directory
* Install the dependencies:  
  `composer install && npm install`
* Start the dev server:  
  `npm run start`

## Design Decisions

Why another multi-author plugin? What about Co-Authors Plus or Bylines or PublishPress Authors?

Firstly, those plugins are great and have served us well over the years, however they all suffer from similar problems:

* API: Lack of support for writing and reading author data via the REST API and WP-CLI
* UI: Limited or custom UI that doesn't feel like a part of WordPress
* Users: An unnecessary distinction between guest authors and actual WordPress users

Let's look at these points in detail and explain how Authorship addresses them:

### API design decisions

There's a lot more to a modern WordPress site than just its theme. Data gets written to and read from its APIs, so these need to be treated as first-class citizens when working with the attributed authors of posts.

Authorship provides:

* Ability to read and write attributed authors via an `authorship` field on the `wp/v2/posts` REST API endpoints
* Ability to create guest authors via the `authorship/v1/users` REST API endpoint
* Read-only access to users who can be attributed to a post via the `authorship/v1/users` REST API endpoint
* Ability to specify attributed authors when creating or updating posts via WP-CLI with the `--authorship` flag

### UI design decisions

We'd love it if you activated Authorship and then forgot that its features are provided by a plugin. The UI provides convenient functionality without looking out of place, both in the block editor and the classic editor.

### User design decisions

Existing plugins that provide guest author functionality make a distinction between a guest author and a real WordPress user. A guest author exists only as a taxonomy term, which complicates the UX and creates inconsistencies and duplication in the data.

Authorship creates a real WordPress user account for each guest author, which provides several advantages:

* No custom administration screens for managing guest authors separately from regular users
* Plugins that customise user profiles work for guest authors too
* Consistent data structure - you only ever deal with `WP_User` objects
* No need to keep data in sync between a user and their "author" profile
* Promoting a guest author to a functional user is done just by changing their user role

## Template Functions

The following template functions are available for use in your theme to fetch the attributed author(s) of a post:

* `\Authorship\get_author_names( $post )`
  - Returns a comma-separated list of the names of the attributed author(s)
* `\Authorship\get_author_names_sentence( $post )`
  - Returns a sentence stating the names of the attributed author(s), localised to the current language
* `\Authorship\get_author_names_list( $post )`
  - Returns an unordered HTML list of the names of the attributed author(s)
* `\Authorship\get_authors( $post )`
  - Returns a list of user objects of the attributed authors
* `\Authorship\get_author_ids( $post )`
  - Returns a list of user ids of the attributed authors

## REST API

The following REST API endpoints and fields are available:

### `authorship/v1/users` endpoint

This endpoint allows:

* Searching all users who can be attributed to content
* Creating guest authors

### `authorship` field

This field is added to the endpoint for all suported post types (by default, ones which that have post type support for `author`), for example `wp/v2/posts`. This field is readable and writable and accepts and provides an array of IDs of users attributed to the post.

In addition, user objects are embedded in the `_embedded['wp:authorship']` field in the response if `_embed` is set and the authenticated user can list users.

## WP-CLI

Authorship implements a custom flag for use with posts, and migration commands.
The following WP-CLI flags are available:

 - `--authorship`

### `--authorship` flag

When creating or updating posts the `--authorship` flag can be used to specify the IDs of users attributed to the post. The flag accepts a comma-separated list of user IDs. Examples:

* `wp post create --post_title="My New Post" --authorship=4,11`
* `wp post update 220 --authorship=13`

If this flag is *not* set:

* When creating a new post, if the `--post_author` flag is set then it will be used for attributed authors
* When updating an existing post, no change will be made to attributed authors

### Migration of WordPress authors on existing posts.

If you activate Authorship on an existing site, all content already created will not have authorship data set for old content. This breaks things such as author archive pages.

This command will set the WordPress author as the authorship user for any posts with no authorship user. (Optionally you can override any existing authorship data, updating it with the WordPress post author).

```sh
wp authorship migrate wp-authors --dry-run=true
```

The command will perform a dry run by default, setting `--dry-run=false` will make changes to the database.

This command will not overwrite or update Authorship data unless the `--overwrite-authors=true` flag is set.

### PublishPress Authors Migration

Authorship provides a command for creating Authorship data using data from PublishPress Authors. This allows a non-destructive migration path from PublishPress Authors.

With both plugins active, this command will copy PPA data into Authorship:

```sh
wp authorship migrate ppa --dry-run=true
```

The command will perform a dry run by default, setting `--dry-run=false` will make changes to the database. Guest authors that do not exist as users will be created with blank emails and random passwords.

This command will not overwrite or update Authorship data unless the `--overwrite-authors=true` flag is set.

## Email Notifications

Authorship does not send any email notifications itself, but it does instruct WordPress core to additionally send its emails to attributed authors when appropriate.

* When a comment on a post is held for moderation, the comment moderation email also gets sent to all attributed authors who have the ability to moderate the comment and have a valid email address
* When a comment on a post is published, the comment notification email also gets sent to all attributed authors who have a valid email address

This plugin only adjusts the list of email addresses to which these emails get sent. If you want to disable these emails entirely, see the "Email me whenever" section of the Settings -> Discussion screen in WordPress.

## Accessibility

Authorship aims to conform to Web Content Accessibility Guidelines (WCAG) 2.1 at level AA but it does not yet fully achieve this. If full support for assistive technology is a requirement of your organisation then Authorship may not be a good fit in its current state.

With regard to the author selection control on the post editing screen:

* ✅ The visual styles are inherited from WordPress core and are WCAG 2.1 AA compliant
* ✅ The control is fully accessible using only the keyboard
* 🚫 The keyboard controls are not very intuitive
* 🚫 The control is not fully accessible when using a screen reader

The team are actively investigating either replacing the component used to render the control with a fully accessible one, or fixing the accessibility issues of the current one.

## Security, Privileges, and Privacy

Great care has been taken to ensure Authorship makes no changes to the user capabilities required to edit content or view sensitive user data on your site. What it *does* do is:

* Grant users who are attributed to a post the ability to edit that post if their capabilities allow it
* Grant users the ability to create and assign guest authors to a post
* Allow this behaviour to be changed at a granular level with custom capabilities

### Assigning Attribution

The capability required to change the attribution of a post matches that which is required by WordPress core to change the post author. This means a user needs the `edit_others_post` capability for the post type. The result is no change in behaviour from WordPress core with regard to being able to attribute a post to another user.

* Administrators and Editors can change the attributed authors of a post
* Authors and Contributors cannot change the attributed authors and see a read-only list when editing a post

Authorship allows the attribution to be changed for any post type that has post type support for `author`, which by default is Posts and Pages.

### Editing Posts

When a user is attributed to a post, that user becomes able to manage that post according to their capabilities as if they were the post author. This means:

* A post that is attributed to a user with a role of Author can be edited, published, and deleted by that user
* A post that is attributed to a user with a role of Contributor can be edited by that user while in draft, but cannot be not published, and cannot be edited once published

From a practical point of view this feature only affects users with a role of Author or Contributor. Administrators and Editors can edit other users' posts by default and therefore edit, publish, and delete posts regardless of whether they are attributed to it.

### Searching Users

The `authorship/v1/users` REST API endpoint provides a means of searching users on the site in order to attribute them to a post. Access to this endpoint is granted to all users who have the capability to change the attributed authors of the given post type, which means Editors and Administrators by default. The result is no change in behaviour from WordPress core with regard to being able to search users.

In addition, this endpoint has been designed to expose minimal information about users, for example it does not expose email addresses or capabilities. This allows lower level users such as users with a role of Author to be granted the ability to attribute users to a post without unnecessarily exposing sensitive information about other users.

### Creating Guest Authors

The `authorship/v1/users` REST API endpoint provides a means of creating guest authors that can subsequently be attributed to a post. Access to this endpoint is granted to all users who have the ability to edit others' posts, which means Editors and Administrators by default.

More work is still to be done around the ability to subsequently edit guest authors, but it's worth noting that this is the one area where Authorship diverges from the default capabilities of WordPress core. It allows an Editor role user to create a new user account, which they usually cannot do. However it is tightly controlled:

* An email address cannot be provided unless the user has the `create_users` capability, which only Administrators do
* A user role cannot be provided, it is always set to Guest Author

### Capability Customisation

The following custom user capabilities are used by Authorship. These can be granted to or denied from users or roles in order to adjust user access:

* `attribute_post_type`
   - Used when attributing users to a given post type
   - Maps to the `edit_others_posts` capability of the post type by default
* `create_guest_authors`
   - Used when creating a guest author
   - Maps to `edit_others_posts` by default

## Contributing

Code contributions, feedback, and feature suggestions are very welcome. See [CONTRIBUTING.md](https://github.com/humanmade/authorship/blob/master/CONTRIBUTING.md) for more details.

## Team

Authorship is developed and maintained by [Human Made](https://humanmade.com) and [Altis](https://www.altis-dxp.com). Its initial development was funded by [Siemens](https://www.siemens.com).

<p align="center">
	<a href="https://humanmade.com"><img src="assets/images/hm-logo.png" width="207" height="86" alt="Human Made"></a>
	<a href="https://www.altis-dxp.com"><img src="assets/images/altis-logo.png" width="207" height="86" alt="Altis DXP"></a>
	<a href="https://www.siemens.com"><img src="assets/images/siemens-logo.png" width="215" height="86" alt="Siemens"></a>
</p>

## License

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

## Alternatives

If the Authorship plugin doesn't suit your needs, try these alternatives:

* [PublishPress Authors](https://wordpress.org/plugins/publishpress-authors/)
* [Co-Authors Plus](https://wordpress.org/plugins/co-authors-plus/)
* [Guest Author](https://wordpress.org/plugins/guest-author/)
