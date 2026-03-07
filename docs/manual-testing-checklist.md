# Authorship Manual Testing Checklist

Last updated: 2026-03-07 (America/Edmonton)

## Purpose

Use this checklist to manually verify Authorship across:

- Block editor UI flows
- REST API read and write flows
- WP-CLI post and migration workflows
- XML-RPC compatibility behavior

This is intended for release validation, regression checks, and deployment readiness reviews.

## Environment and test data

Before running tests:

- [ ] Authorship plugin is active.
- [ ] Permalinks are enabled.
- [ ] You have users for `administrator`, `editor`, and `author`.
- [ ] You have API credentials (Application Password recommended).
- [ ] You can run WP-CLI in the target WordPress root.

Recommended shell variables:

```bash
export WP_URL="https://single-site-local.local"
export WP_USER="admin"
export WP_APP_PASS="<application-password>"
```

## UI tests (Block Editor)

### [ ] UI-01 Assign multiple authors

Steps:
1. Log in as Administrator.
2. Open `Posts -> Add New`.
3. In the Authorship panel, select two existing users.
4. Publish the post.
5. Refresh the editor.

Expected:
- Both selected authors remain assigned in the same order.
- No editor notices or save errors.

### [ ] UI-02 Reorder authors using drag and drop

Steps:
1. Open the post from UI-01.
2. Drag author #2 above author #1.
3. Update the post.
4. Reload the post editor.

Expected:
- Saved author order matches the drag result.
- No duplicate authors are introduced.

### [ ] UI-03 Create a guest author in editor

Steps:
1. Open a post as Administrator or Editor.
2. In the Authorship selector, type a name that does not match an existing user.
3. Choose the create option and save the post.

Expected:
- A new guest author is created and selected.
- The post saves successfully.

### [ ] UI-04 Permission and read-only behavior

Steps:
1. Log in as a user without author-assignment capability.
2. Open a post they should not be able to reassign.
3. Inspect the Authorship selector state.

Expected:
- User cannot change attributed authors.
- Selector is effectively read-only/disabled.

## REST API tests

Use Basic auth with Application Password credentials.

### [ ] API-01 Read `authorship` field

Steps:
1. Create a post in wp-admin with known attributed authors.
2. Run:

```bash
curl -sS -u "$WP_USER:$WP_APP_PASS" \
  "$WP_URL/wp-json/wp/v2/posts/<post-id>?_embed=1"
```

Expected:
- Response includes an `authorship` array.
- `_embedded["wp:authorship"]` is present when `_embed=1` and permissions allow user embedding.

### [ ] API-02 Update `authorship` with array payload

Steps:
1. Run:

```bash
curl -sS -u "$WP_USER:$WP_APP_PASS" \
  -X POST \
  -H "Content-Type: application/json" \
  -d '{"authorship":[<user-id-1>,<user-id-2>]}' \
  "$WP_URL/wp-json/wp/v2/posts/<post-id>"
```

Expected:
- `200` response.
- Returned `authorship` value matches submitted IDs and order.

### [ ] API-03 Reject invalid author IDs

Steps:
1. Run:

```bash
curl -sS -u "$WP_USER:$WP_APP_PASS" \
  -X POST \
  -H "Content-Type: application/json" \
  -d '{"authorship":[99999999]}' \
  "$WP_URL/wp-json/wp/v2/posts/<post-id>"
```

Expected:
- Error response (`4xx`).
- Response message indicates invalid or unsupported author IDs.

### [ ] API-04 Search assignable users endpoint

Steps:
1. Run:

```bash
curl -sS -u "$WP_USER:$WP_APP_PASS" \
  "$WP_URL/wp-json/authorship/v1/users?post_type=post&search=adm"
```

Expected:
- `200` response for authorized users.
- Response returns minimal user profile objects used by selector UI.

### [ ] API-05 Create guest author endpoint

Steps:
1. Run:

```bash
curl -sS -u "$WP_USER:$WP_APP_PASS" \
  -X POST \
  -H "Content-Type: application/json" \
  -d '{"name":"Manual API Guest"}' \
  "$WP_URL/wp-json/authorship/v1/users"
```

Expected:
- `201` response for authorized users.
- A new guest author account is created and can be assigned to posts.

## WP-CLI tests

Use `--path=<wp-path>` if WP-CLI is not run from the WordPress root.

### [ ] CLI-01 Create post with `--authorship`

Steps:
1. Run:

```bash
wp post create \
  --post_title="Authorship CLI Create" \
  --post_status=draft \
  --authorship=<user-id-1>,<user-id-2>
```

2. Verify assigned IDs:

```bash
wp eval "echo wp_json_encode( \\Authorship\\get_author_ids( get_post( <post-id> ) ) );"
```

Expected:
- Post is created successfully.
- Authorship stores provided IDs in the same order.

### [ ] CLI-02 Update post with `--authorship`

Steps:
1. Run:

```bash
wp post update <post-id> --authorship=<user-id-2>
```

2. Verify via REST:

```bash
curl -sS -u "$WP_USER:$WP_APP_PASS" \
  "$WP_URL/wp-json/wp/v2/posts/<post-id>"
```

Expected:
- Authorship reflects the updated ID list.

### [ ] CLI-03 Migration dry run

Steps:
1. Run:

```bash
wp authorship migrate wp-authors --dry-run=true --batch-pause=0
```

Expected:
- Command reports planned work.
- No persistent data changes occur.

### [ ] CLI-04 Migration live run

Steps:
1. Run:

```bash
wp authorship migrate wp-authors --dry-run=false --batch-pause=0
```

2. Verify migrated posts now contain expected authorship assignments.

Expected:
- Command succeeds.
- Target posts have authorship values populated.

## XML-RPC compatibility

### Does Authorship work with XML-RPC?

Short answer: partially.

- Works: default author attribution on XML-RPC-created posts via post-insert hooks.
- Does not provide first-class multi-author writes through standard XML-RPC fields.

Use the checks below to confirm behavior in your environment.

### [ ] XMLRPC-01 Endpoint reachability

Steps:
1. Open:

```text
https://single-site-local.local/xmlrpc.php
```

Expected:
- Response is `XML-RPC server accepts POST requests only.`

### [ ] XMLRPC-02 Create post via XML-RPC

Steps:
1. Run a script like:

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

2. Fetch the post through REST and inspect `authorship`.

Expected:
- Post is created.
- `authorship` defaults to the XML-RPC author when no explicit multi-author assignment is provided.

### [ ] XMLRPC-03 Edit existing XML-RPC post

Steps:
1. Update the same post via `metaWeblog.editPost`.
2. Re-check `authorship` through REST.

Expected:
- Post updates successfully.
- Existing authorship values are preserved unless changed by another integration path.

### [ ] XMLRPC-04 Multi-author assignment capability check

Steps:
1. Attempt multi-author assignment using standard XML-RPC post fields only.
2. Verify resulting `authorship` via REST.

Expected:
- Standard XML-RPC fields do not support explicit multi-author assignment.
- Use REST API or WP-CLI for deterministic multi-author writes.

## Sign-off

- [ ] All critical UI checks passed.
- [ ] REST API checks passed.
- [ ] WP-CLI checks passed.
- [ ] XML-RPC behavior confirmed and documented for this deployment.
- [ ] Any failures captured with exact reproduction steps and environment details.
