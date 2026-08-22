# 36Blocks API & Snippet Reference

Full technical reference. Read this whenever you need exact snippet code, API request/response shapes, or field-level details. SKILL.md has the workflow and decision logic — this file has the copy-pasteable specifics.

## Table of contents
1. Core concepts & architecture
2. Frontend snippets (all four)
3. Backend APIs
4. Multi-block guidance

---

## 1. Core concepts & architecture

- **Client**: A 36Blocks account holder — the platform owner integrating 36Blocks (i.e., the person Claude is helping).
- **Block**: A container owned by a client. Each block has its own configuration (themes, user management settings, roles/permissions, redirection URL). A client can own **multiple blocks** — see section 4.
- **reference_id**: The unique ID of a block, generated when the client creates it in the 36Blocks dashboard. Required in the authorization snippet.
- **c_user**: An end user of the *client's* platform (i.e., the client's customer). Each c_user belongs to exactly one block.
- **c_company**: A tenant/organization inside a block. A c_user can belong to multiple c_companies, with a different role and permission set in each. A c_company can have multiple c_users.
- **proxy_auth_token**: An encrypted session token 36Blocks issues after a c_user authenticates. Used as a header to call the backend APIs. It expires when `/c/logout` is called.
- **Redirection URL**: Configured per-block. After a c_user authenticates via the authorization snippet, 36Blocks redirects them to this URL with `proxy_auth_token` as a query parameter.
- 36Blocks manages all c_user data server-side. Client applications do **not** need their own users table or DB-level auth schema — they only need to persist the `proxy_auth_token` (or a session tied to it) and call the APIs to fetch user data as needed.

---

## 2. Frontend snippets

All snippets load `https://proxy.msg91.com/assets/proxy-auth/proxy-auth.js` and call `initVerification(configuration)` on load. `type` in the configuration object selects which UI renders.

### 2.1 `authorization` — login / signup

Renders login/signup CTAs (Google and others). Place on the login page. Requires the block's `reference_id`.

```html
<div id="1258584j170929103665e1b61c32c86"></div>

<script type="text/javascript">
    var configuration = {
        referenceId: '1258584j170929103665e1b61c32c86', // block's reference_id
        type: 'authorization',
        success: (data) => {
            console.log('success response', data);
        },
        failure: (error) => {
            console.log('failure reason', error);
        },
    };
</script>
<script
    type="text/javascript"
    onload="initVerification(configuration)"
    src="https://proxy.msg91.com/assets/proxy-auth/proxy-auth.js"
></script>
```

After successful auth, 36Blocks redirects the browser to the block's configured redirection URL with `?proxy_auth_token=...` in the query string.

### 2.2 `user-management` — manage teammates within the current c_company

Lets the current c_user view other c_users in the current c_company, change roles, remove users, and invite new ones (subject to their permissions). Use when the client wants "team/member management" UI.

```html
<div id="userProxyContainer"></div>

<script type="text/javascript">
    var configuration = {
        type: 'user-management',
        authToken: 'ENCRYPTED_AUTH_TOKEN', // the c_user's proxy_auth_token
        success: (data) => {
            console.log('success response', data);
        },
        failure: (error) => {
            console.log('failure reason', error);
        },
    };
</script>
<script
    type="text/javascript"
    onload="initVerification(configuration)"
    src="https://proxy.msg91.com/assets/proxy-auth/proxy-auth.js"
></script>
```

### 2.3 `user-profile` — self-service profile

Lets the c_user view/edit their own data, see all c_companies they belong to, and leave a c_company. Use for "account settings" / "my profile" pages.

```html
<div id="userProxyContainer"></div>

<script type="text/javascript">
    var configuration = {
        type: 'user-profile',
        authToken: 'ENCRYPTED_AUTH_TOKEN',
        success: (data) => {
            console.log('success response', data);
        },
        failure: (error) => {
            console.log('failure reason', error);
        },
    };
</script>
<script
    type="text/javascript"
    onload="initVerification(configuration)"
    src="https://proxy.msg91.com/assets/proxy-auth/proxy-auth.js"
></script>
```

### 2.4 `organization-details` — current c_company details

Shows details of the c_company the c_user is currently in, with an option to modify it. Use for "organization/workspace settings" pages.

```html
<div id="userProxyContainer"></div>

<script type="text/javascript">
    var configuration = {
        type: 'organization-details',
        authToken: 'ENCRYPTED_AUTH_TOKEN',
        success: (data) => {
            console.log('success response', data);
        },
        failure: (error) => {
            console.log('failure reason', error);
        },
    };
</script>
<script
    type="text/javascript"
    onload="initVerification(configuration)"
    src="https://proxy.msg91.com/assets/proxy-auth/proxy-auth.js"
></script>
```

---

## 3. Backend APIs

All require the `proxy_auth_token` header (obtained from the redirect query param after login).

### 3.1 `GET /c/getDetails`

Fetch the current c_user's details, including all c_companies they belong to and the currently active company (with role + permissions).

```bash
curl --request GET \
  --url https://routes.msg91.com/api/c/getDetails \
  --header 'Content-Type: application/json' \
  --header 'proxy_auth_token: {{proxy_auth_token}}'
```

Response shape (trimmed):
```json
{
  "data": [
    {
      "id": 99152,
      "name": "...",
      "email": "...",
      "client_id": 8,
      "c_companies": [
        { "id": 61434, "name": "...", "role_id": 1, "role_name": "Owner", "...": "..." }
      ],
      "currentCompany": {
        "id": 63363,
        "role": { "id": 1, "name": "Owner", "is_default": true },
        "permissions": ["create_c_company", "update_c_company", "..."]
      }
    }
  ],
  "status": "success",
  "hasError": false,
  "errors": []
}
```

Use this to render user-specific views, gate UI on `permissions`, and know which company is active.

### 3.2 `POST /c/switchCompany`

Switch the c_user's active c_company (for c_users who belong to more than one).

```bash
curl --request POST \
  --url https://routes.msg91.com/api/c/switchCompany \
  --header 'Content-Type: application/json' \
  --header 'proxy_auth_token: {{proxy_auth_token}}' \
  --data '{
    "company_ref_id": 61434
  }'
```

Response includes a `jwt` reflecting the new active company/permissions context — treat it like a refreshed session token for that company.

### 3.3 `DELETE /c/logout`

Logs the c_user out and expires the `proxy_auth_token`.

```bash
curl --request DELETE \
  --url https://routes.msg91.com/api/c/logout \
  --header 'proxy_auth_token: {{proxy_auth_token}}'
```

Call this from the client's own logout action, then clear whatever server-side session/cookie was storing the token (see SKILL.md § Token storage).

---

## 4. Multi-block guidance

A single 36Blocks client account can own multiple blocks. Common reasons to create a **new** block vs. reusing one:

- **Environment separation** — separate blocks for staging and production so test c_users/data never mix with real customers.
- **Separate products** — if the client runs more than one distinct product/app with its own user base, each typically gets its own block (each c_user belongs to exactly one block).
- **Different auth/branding config** — a block's theme, redirection URL, and settings are block-wide; if two surfaces need materially different auth configuration, that's a signal for separate blocks.

Reuse the same block when it's the same user base and same app, just deployed to different pages/subdomains of the same product — configure a single redirection URL that all those surfaces redirect to, and branch app-side after login.
