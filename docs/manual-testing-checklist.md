# Authorship Manual Testing Checklist

Last updated: 2026-03-07

## Purpose

This checklist defines manual verification for Authorship across:

- Block editor UI flows
- REST API read/write flows
- WP-CLI post and migration flows
- XML-RPC compatibility behavior

Use this when validating a release candidate, verifying a bug fix, or confirming integration on a new site.

## Environment And Test Data

Before running tests:

- [ ] Authorship plugin is active.
- [ ] Permalinks are enabled.
- [ ] You have three users: `administrator`, `editor`, `author`.
- [ ] You have API credentials (Application Password recommended).
- [ ] You know your WordPress path for WP-CLI (`--path=<wp-path>` if needed).

Recommended variables:

```bash
export WP_URL="https://single-site-local.local"
export WP_USER="admin"
export WP_APP_PASS="<application-password>"
```

## UI Tests (Block Editor)

### [ ] UI-01 Assign Multiple Authors

Steps:
1. Log in as Administrator.
2. Open `Posts -> Add New`.
3. In the Authorship panel, select two existing users.
4. Publish the post.
5. Refresh the editor.

Expected:
- Authorship shows both selected users in saved order.
- No editor errors/notices.

### [ ] UI-02 Reorder Authors By Drag And Drop

Steps:
1. Open the post from UI-01.
2. Drag author #2 above author #1 in the selector.
3. Update the post.
4. Reload the post editor.

Expected:
- Order remains as saved after reload.
- No duplicate authors are introduced.

### [ ] UI-03 Create Guest Author In Editor

Steps:
1. Open any post as Administrator or Editor.
2. In Authorship selector, type a new name not matching an existing user.
3. Choose the create option and save the post.

Expected:
- New guest author is created and selected.
- Post saves successfully.

### [ ] UI-04 Permissions And Read-Only Behavior

Steps:
1. Log in as Author role user.
2. Open a post authored by another user.
3. Inspect the Authorship selector.

Expected:
- Author cannot change attributed authors.
- Selector is effectively read-only for unauthorized user.

## REST API Tests

Use Basic auth with Application Password for these examples.

### [ ] API-01 Read `authorship` Field

Steps:
1. Create a post in wp-admin with known attributed authors.
2. Call:

```bash
curl -sS -u "$WP_USER:$WP_APP_PASS" \
  "$WP_URL/wp-json/wp/v2/posts/<post-id>?_embed=1"
```

Expected:
- Response includes `authorship` as an array of user IDs.
- Response contains `_embedded["wp:authorship"]` when `_embed=1` and permission allows user listing.

### [ ] API-02 Update `authorship` With Array Payload

Steps:
1. Call:

```bash
curl -sS -u "$WP_USER:$WP_APP_PASS" \
  -X POST \
  -H "Content-Type: application/json" \
  -d '{"authorship":[<user-id-1>,<user-id-2>]}' \
  "$WP_URL/wp-json/wp/v2/posts/<post-id>"
```

Expected:
- `200` response.
- `authorship` in response matches submitted IDs/order.

### [ ] API-03 Reject Invalid Author IDs

Steps:
1. Call:

```bash
curl -sS -u "$WP_USER:$WP_APP_PASS" \
  -X POST \
  -H "Content-Type: application/json" \
  -d '{"authorship":[99999999]}' \
  "$WP_URL/wp-json/wp/v2/posts/<post-id>"
```

Expected:
- Error response (`4xx`).
- Message indicates invalid IDs.

### [ ] API-04 Search Assignable Users Endpoint

Steps:
1. Call:

```bash
curl -sS -u "$WP_USER:$WP_APP_PASS" \
  "$WP_URL/wp-json/authorship/v1/users?post_type=post&search=adm"
```

Expected:
- `200` response for authorized users.
- Returned users are minimal profile data for selector use.

### [ ] API-05 Create Guest Author Endpoint

Steps:
1. Call:

```bash
curl -sS -u "$WP_USER:$WP_APP_PASS" \
  -X POST \
  -H "Content-Type: application/json" \
  -d '{"name":"Manual API Guest"}' \
  "$WP_URL/wp-json/authorship/v1/users"
```

Expected:
- `201` response for authorized users.
- New user is created with guest-author role behavior.

## WP-CLI Tests

Use `--path=<wp-path>` if WP-CLI is not run from WordPress root.

### [ ] CLI-01 Create Post With `--authorship`

Steps:
1. Run:

```bash
wp post create \
  --post_title="Authorship CLI Create" \
  --post_status=draft \
  --authorship=<user-id-1>,<user-id-2>
```

2. Capture the returned post ID and verify with plugin helper:

```bash
wp eval "echo wp_json_encode( \\Authorship\\get_author_ids( get_post( <post-id> ) ) );"
```

Expected:
- Post is created.
- Authorship stores the provided IDs in the same order.

### [ ] CLI-02 Update Post With `--authorship`

Steps:
1. Run:

```bash
wp post update <post-id> --authorship=<user-id-2>
```

2. Verify through REST:

```bash
curl -sS -u "$WP_USER:$WP_APP_PASS" \
  "$WP_URL/wp-json/wp/v2/posts/<post-id>"
```

Expected:
- Authorship updates to the new ID list.

### [ ] CLI-03 Migration Dry Run

Steps:
1. Run:

```bash
wp authorship migrate wp-authors --dry-run=true --batch-pause=0
```

Expected:
- Command reports what would change.
- No data modifications are made.

### [ ] CLI-04 Migration Live Run

Steps:
1. Run:

```bash
wp authorship migrate wp-authors --dry-run=false --batch-pause=0
```

2. Verify a previously un-attributed post now has authorship set to post author.

Expected:
- Command succeeds.
- Target posts receive authorship attribution.

## XML-RPC Compatibility

### Does Authorship work with XML-RPC?

Short answer: partially.

- Expected to work: default author assignment on XML-RPC-created posts, because Authorship runs on post insert hooks.
- Not first-class: XML-RPC does not provide a dedicated Authorship field for multi-author assignment.

Use the tests below to confirm behavior in your environment.

### [ ] XMLRPC-01 Endpoint Reachability

Steps:
1. Open:

```text
https://single-site-local.local/xmlrpc.php
```

Expected:
- Returns `XML-RPC server accepts POST requests only.`

### [ ] XMLRPC-02 Create Post Via XML-RPC

Steps:
1. Run a script similar to:

```python
import xmlrpc.client

url = "https://single-site-local.local/xmlrpc.php"
username = "editor_user"
password = "editor_password"

client = xmlrpc.client.ServerProxy(url)
post = {
    "post_type": "post",
    "post_status": "draft",
    "post_title": "XML-RPC Authorship Test"
}
post_id = client.metaWeblog.newPost("1", username, password, post, True)
print(post_id)
```

2. Fetch post via REST and inspect `authorship`.

Expected:
- Post is created.
- `authorship` defaults to XML-RPC user/post author when explicit authorship was not supplied elsewhere.

### [ ] XMLRPC-03 Edit Existing XML-RPC Post

Steps:
1. Update the post using `metaWeblog.editPost`.
2. Re-check `authorship` using REST.

Expected:
- Post updates successfully.
- Existing authorship is preserved unless another integration path explicitly changes it.

### [ ] XMLRPC-04 Multi-Author Assignment Capability Check

Steps:
1. Attempt to assign multiple authors using standard XML-RPC post fields only.
2. Verify resulting `authorship` via REST.

Expected:
- Multi-author assignment is not directly supported by standard XML-RPC fields.
- Use REST API or WP-CLI for explicit multi-author writes.

## Sign-Off

- [ ] All critical UI checks passed.
- [ ] REST read/write checks passed.
- [ ] WP-CLI checks passed.
- [ ] XML-RPC behavior confirmed and documented for this deployment.
- [ ] Any failures captured with reproduction steps and environment details.
