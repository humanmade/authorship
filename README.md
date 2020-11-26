# Authorship

Authorship is a modern approach to author attribution in WordPress. It supports multiple authors and guest authors, provides a great UI, and treats API access to author data as a first-class citizen.

Authorship is currently geared toward developers who are implementing custom solutions on WordPress. For example, it doesn't provide an option to automatically display author profiles at the bottom of a post. In the future it will include wider support for existing themes and useful features for implementors and site builders.

---

 * [Features](#features)
 * [Design decisions](#design-decisions)
 * [Team](#team)
 * [License](#license)
 * [Alternatives](#alternatives)

---

## Features

* Multiple authors per post
* Guest authors (that can be created in place on the post editing screen)
* A convenient and user-friendly UI that feels like a part of WordPress
* Works great with the block editor and the classic editor
* Full CRUD support in the REST API and WP-CLI
* Full support in RSS and Atom feeds
* Fine-grained user permission controls
* Plenty of filters and actions

## Design decisions

Why another multi-author plugin? What about Co-Authors Plus or Bylines or PublishPress Authors?

Firstly, those plugins are all great and have served us well over the years, however the existing solutions mostly suffer from similar problems:

* API: Lack of support for exposing author data beyond the theme, for example via the REST API and in feeds
* UI: Limited or custom UI that doesn't feel like a natural part of WordPress
* Users: An unnecessary distinction between guest authors and actual WordPress users

Let's look at these points in detail and explain how Authorship addresses them:

### API design decisions

There's a lot more to a WordPress website than just its theme. Services can both consume and create data via its feeds and APIs, so these need to be treated as first-class citizens when exposing information about the multiple authors and guest authors of posts.

Authorship provides:

* Full CRUD support via WP-CLI
* Full CRUD support for guest authors via a custom REST API endpoint
* Full CRUD support for author association via a property on the default post endpoints
* Full support for RSS and Atom feed output

On the roadmap:

* Support for XML-RPC
* Support for WPGraphQL

### UI design decisions

We'd love it if you activated Authorship and then forgot that its features are provided by a plugin. The UI feels just like native WordPress but provides convenient functionality without looking out of place, both in the block editor and the classic editor.

### User design decisions

Existing plugins that provide guest author functionality make a distinction between a guest author and a real WordPress user. A guest author exists only as a taxonomy term, which complicates the UX and creates inconsistencies and duplication in the data.

Authorship creates a real WordPress user account for each guest author, and this provides several advantages:

* Familiar UI and UX - no custom administration screens for managing the details of a guest author differently from a regular user
* Third party plugins that customise user profile functionality just work
* Consistent data structure - you only ever deal with `WP_User` objects
* No need to keep data in sync between a user and their "author" profile
* Promoting a guest author to a functional user is done just by changing their user role

## Team

Authorship is developed and maintained by [Human Made](https://humanmade.com) and [Altis](https://www.altis-dxp.com). Its initial development was funded by [Siemens](https://www.siemens.com).

[<img src="assets/images/hm-logo.png" width="207" height="86" alt="">](https://humanmade.com)
[<img src="assets/images/altis-logo.png" width="207" height="86" alt="">](https://www.altis-dxp.com)
[<img src="assets/images/siemens-logo.png" width="215" height="86" alt="">](https://www.siemens.com)

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
