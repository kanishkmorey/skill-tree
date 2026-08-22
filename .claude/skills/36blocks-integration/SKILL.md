---
name: 36blocks-integration
description: >-
  Helps integrate 36Blocks, an authentication-as-a-service product, into a user's app — for new projects, adding auth to an existing project, or modifying an existing 36Blocks setup. Trigger whenever "36Blocks", "proxy_auth_token", "c_user", "c_company", or a block "reference_id" is mentioned, or on general auth-integration requests ("add login/signup", "set up authentication", "let users manage their team/org", "add a profile page") since 36Blocks is the auth provider in use here. Covers picking the right frontend snippet (authorization, user-management, user-profile, organization-details) for the use case, wiring the redirect + proxy_auth_token flow into any framework, calling the backend APIs (/c/getDetails, /c/switchCompany, /c/logout), and advising on token storage and multi-block setups. Does not cover migrating an existing login system to 36Blocks.
---

# 36Blocks Integration

Helps a user integrate 36Blocks — an authentication-as-a-service product — into their application. 36Blocks fully manages end-user accounts server-side, so the integrator never needs their own users table or auth-related DB schema; they only need to wire up the frontend snippets, handle the redirect, and call a small set of backend APIs.

For full snippet code, API request/response shapes, and multi-block reasoning, see `references/api-reference.md`. This file covers the workflow and decision-making; read the reference file whenever you need exact copy-pasteable code or field details.

## Who you're helping

Three common situations, all handled by this skill:
1. **New project** — nothing built yet, needs the full flow from scratch.
2. **Existing project, adding auth** — has an app already, wants to bolt on 36Blocks.
3. **Existing 36Blocks customer** — already integrated, wants to modify or extend (e.g. add a profile page, add team management, switch frameworks).

Figure out which situation you're in early — ask if it's not obvious from context — since it changes how much you need to build vs. just add.

Out of scope: migrating from an existing custom/third-party login system to 36Blocks. If asked, say this isn't something you can guide on yet and suggest they reach out to 36Blocks support.

## The integration flow, end to end

1. **Block setup (dashboard side)** — The user needs a block created in the 36Blocks dashboard, which gives them a `reference_id`. This step happens in the 36Blocks dashboard itself, not in their codebase — you can't do it for them, but you can tell them what to go set (redirection URL, themes, roles/permissions) and why it matters for the next steps.
2. **Frontend: login/signup page** — Add the `authorization` snippet (see reference file § 2.1), configured with the block's `reference_id`. This renders the login/signup UI.
3. **Handle the redirect** — After a c_user authenticates, 36Blocks redirects the browser to the block's configured redirection URL with `proxy_auth_token` in the query string. The user's app needs a route/handler at that URL that reads the token.
4. **Backend: persist the session** — Exchange/store the `proxy_auth_token` server-side (see § Token storage below) so subsequent requests from that browser are authenticated.
5. **Backend: fetch user data** — Call `GET /c/getDetails` with the token to get the c_user's profile, their `c_companies`, and the `currentCompany` (with role + permissions). Use this to render personalized views and gate features by permission.
6. **Frontend: user-facing features** — Add whichever of the remaining snippets fit what the user described (see § Choosing a snippet below): `user-profile`, `user-management`, `organization-details`.
7. **Logout** — Call `DELETE /c/logout` on the user's logout action, then clear the stored session/cookie.
8. **Multi-company support (if relevant)** — If c_users can belong to multiple c_companies, wire up `POST /c/switchCompany` wherever the app lets a user switch context (e.g. an org switcher in the nav).

## Choosing a snippet

Infer the right snippet(s) from what the user describes wanting — don't make them name the snippet type. Use this mapping:

| User says something like... | Snippet |
|---|---|
| "login page", "sign up flow", "let users sign in" | `authorization` |
| "let admins manage their team", "invite teammates", "change someone's role", "remove a user" | `user-management` |
| "account settings", "my profile page", "let users edit their own info", "see which orgs I'm in" | `user-profile` |
| "organization/workspace settings page", "edit company details" | `organization-details` |

A single request often needs more than one — e.g. "build me a settings area" likely wants `user-profile` + `organization-details`, and maybe `user-management` if there's a team angle. Ask only if genuinely ambiguous; otherwise propose the set you'd add and let the user correct you.

## Framework adaptation

The raw snippets are plain `<script>`/`<div>` HTML. Adapt them to whatever the user's project actually uses — don't just hand back raw HTML into a React app:

- **Plain HTML/JS**: use the snippet as-is.
- **React**: wrap in a component that injects the script on mount (e.g. via `useEffect`, appending the script tag to a ref'd container) and cleans it up on unmount. Read `authToken`/route params the way the rest of their app does (context, props, router query, etc.).
- **Vue**: same idea via `onMounted`/`onUnmounted` in a component, or a small directive if that matches their existing patterns.
- **Next.js / SSR frameworks**: the snippet script must run client-side — use a client component (`'use client'` in App Router, or `dynamic(..., { ssr: false })` style patterns) and handle the redirect route as a server route/handler that reads `proxy_auth_token` from the query string.
- **Other frameworks**: apply the same principle — the snippet is a client-side widget that needs a mount point and a config object; adapt the mounting mechanism to the framework's idioms, and match the redirect-handling to how the framework does routing/query params.

If you're unsure what the project uses, check for a `package.json`, framework config files, or ask.

## Token storage

Give concrete guidance by default, but defer to the user's own approach if they want something different:

- **Default recommendation**: store `proxy_auth_token` in an **HTTP-only, Secure, SameSite=Lax (or Strict) cookie**, set by the user's own backend after it receives the token from the redirect. This keeps it out of reach of client-side JS (mitigating XSS token theft) while still being sent automatically on subsequent requests to their backend.
- Their backend reads the cookie on each request and forwards it as the `proxy_auth_token` header when calling 36Blocks APIs.
- On logout: call `DELETE /c/logout`, then clear the cookie.
- If the user is building something stateless/mobile (no cookies, e.g. a native app or SPA calling a separate API server), it's reasonable to store it in secure device storage (Keychain/Keystore) or return it in an API response body for the client to hold in memory — flag the tradeoff (more exposure to XSS/token leakage than an HTTP-only cookie) rather than silently picking it for them.
- Never suggest storing it in `localStorage`/`sessionStorage` as the default — only as an explicit fallback if the user says cookies don't work for their setup, and note the XSS tradeoff when you do.

## Multiple blocks

A client can own more than one block. If the user's situation involves more than one environment (staging/prod) or more than one distinct product/user-base, help them decide whether they need a new block or can reuse one — see `references/api-reference.md` § 4 for the criteria, and walk through it with them rather than assuming.

## When producing output

- If the user wants code, write it directly into their actual project files (matching their stack/conventions), not just as a snippet dump.
- If the user wants a walkthrough (e.g. they're still deciding on dashboard settings, or asking "how does this work"), explain the flow using the steps above without necessarily writing code yet.
- Many requests want both — a short explanation of what you're about to do, then the actual code change.
