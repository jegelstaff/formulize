# HTTP Security Headers — Analysis & Implementation Plan

> **Status:** Planning / reference document. Captures analysis of the security headers
> added to `index.php` on branch `FEATURE/security-http-headers`, the risks they
> introduce, and a recommended path to making them safe to ship.
>
> **Created:** 2026-06-22

---

## 1. What was added

Commit `1250d74653` ("Adding http security headers to index.php") added the following
to the root [`index.php`](index.php) (around lines 60–79), just before page output begins:

```php
// HTTP Strict Transport Security (HSTS) - two years
header("Strict-Transport-Security: max-age=63072000");
// Content Security Policy (CSP) - starting with recommended locked down policy
header("Content-Security-Policy: default-src 'none';
	img-src 'self';
	script-src 'self' ajax.googleapis.com use.fontawesome.com;
	style-src 'self' fonts.googleapis.com ajax.googleapis.com use.fontawesome.com;
	font-src fonts.gstatic.com;
	connect-src 'self'"
);
// X-Frame-Options to prevent clickjacking
header("X-Frame-Options: DENY");
// Referrer Policy to prevent URL leakage
header("Referrer-Policy: same-origin");
// X-Content-Type-Options to prevent MIME sniffing
header("X-Content-Type-Options: nosniff");
```

---

## 2. Scope: where these headers actually apply

PHP `header()` only affects the current response. These calls live **only in the root
`index.php`** — confirmed by grep that they exist nowhere else in core (other matches are
bundled phpMyAdmin).

Implications:

- They do **not** apply to `modules/formulize/index.php`, the admin UI, save handlers,
  `public_api/`, or most of the actual application. Those pages are unaffected — and also
  unprotected.
- When `startpage == 'formulize'`, the root `index.php` usually **redirects to a module
  URL and `exit()`s** before rendering, so the CSP frequently never renders for the home page.
- **However**, the root `index.php` *is* reached by the Brightspace LTI flow (see §4), so
  the headers are not as inert as "home page only" suggests.

**Decision needed:** Is the intent home-page-only (low risk, low security value) or
site-wide (must be centralized in `header.php` or an `icms` preload event — which is exactly
where breakage risk explodes)? The plan below assumes the goal is real, site-wide protection
done safely.

---

## 3. Risks introduced by the policy as written

### 3.1 Biggest functional killer — inline scripts/styles

- `script-src 'self' …` has **no `'unsafe-inline'`** → every inline `<script>` block and
  every inline `onclick=`/event-handler attribute is blocked. Formulize templates `eval()`
  PHP that emits inline JS and `onclick` handlers throughout; the theme has inline scripts too.
  This breaks *interactivity*, not just an unlisted URL.
- `style-src 'self' …` has no `'unsafe-inline'` → every `<style>` block and every
  `style="…"` attribute is blocked. ImpressCMS themes and Formulize templates use inline
  styles pervasively. This breaks *layout*.

Moving to nonces/hashes is unrealistic given the legacy `eval`-the-template design, so
`'unsafe-inline'` is effectively required (at least for `style-src`, almost certainly
`script-src` too) before enforcing.

### 3.2 The "can't enumerate all the URLs" problem (real, bounded)

`default-src 'none'` is the catch-all, so anything not explicitly listed is blocked:

- `connect-src 'self'` blocks XHR/fetch/WebSocket to any external origin.
- `frame-src` / `media-src` / `object-src` / `worker-src` / `manifest-src` all fall back to
  `'none'` → no embedded iframes, media, web workers, etc.
- Any CDN beyond the three whitelisted (`ajax.googleapis.com`, `use.fontawesome.com`,
  `fonts.googleapis.com`/`fonts.gstatic.com`) — maps, analytics, customer-specific assets,
  derived-value code emitting external resources — is blocked.

Hand-enumerating every URL a given install uses is impractical. Let the browsers enumerate
them via Report-Only mode (see §5).

### 3.3 Framing / embedding — the LTI killer

- `X-Frame-Options: DENY` forbids the page from being framed by **anyone, including
  same-origin**. This directly breaks the Brightspace LTI iframe (see §4).
- The CSP has **no `frame-ancestors`** directive, so framing is governed *solely* by
  `X-Frame-Options` here. The modern, correct tool is `frame-ancestors`.

### 3.4 SAML / cross-site — largely NOT broken (clarification)

- CSP does **not** block cross-site SSO. `form-action` and `frame-ancestors` are *navigation*
  directives that do **not** inherit `default-src`, so SAML redirect and HTTP-POST-binding
  flows are not blocked.
- `SAMLResponse` is POSTed as a top-level navigation to the site and processed in
  `Session.php::sessionStart()`; inbound navigations aren't restricted by `connect-src`.
- The only cross-site caveat is `Referrer-Policy: same-origin` (some SSO/return flows expect a
  referrer; `strict-origin-when-cross-origin` is the safer common default), plus the framing
  and cookie issues. SAML breaks only if it runs *inside an iframe*, via `X-Frame-Options`.

### 3.5 HSTS — sticky footgun

- `Strict-Transport-Security: max-age=63072000` is sent unconditionally, even on plain-HTTP
  responses. Once a browser sees it over HTTPS it **forces HTTPS for 2 years and refuses
  HTTP** — painful for dev/staging/internal installs on HTTP, and hard to reverse (serve
  `max-age=0` over HTTPS and wait for every client to revisit). Should be HTTPS-gated, opt-in,
  and ideally start with a smaller max-age.

---

## 4. The Brightspace / LTI conflict (concrete, not hypothetical)

[`libraries/brightspace/controller.php`](libraries/brightspace/controller.php) is:

```php
require_once "../../mainfile.php";
include "../../index.php";
```

The LTI handshake in [`libraries/brightspace/index.php`](libraries/brightspace/index.php)
ends with `header("Location: /libraries/brightspace/controller.php")`, and `controller.php`
renders the **root `index.php`** — the file now emitting `X-Frame-Options: DENY`. That
response is displayed **inside the Brightspace iframe**, so the browser refuses to render it.
Result: blank / "refused to connect" panel for the LTI user. Definite break.

### The session layer was deliberately built for that iframe

[`libraries/icms/core/Session.php`](libraries/icms/core/Session.php) shows intentional work
to keep cookies alive inside a third-party iframe — which `X-Frame-Options: DENY` defeats
entirely:

- `cookieSameSite()` (lines ~416–423) supports **`SameSite=None`**, gated to `Secure`,
  configurable via the `cookie_samesite` preference. `SameSite=None; Secure` is exactly what's
  required for a session cookie to be sent when Formulize is embedded in Brightspace.
- The anonymous-cookie branch states the intent (lines ~225–227):
  > `set anon session cookie - necessary for preserving state in LTI systems...some browsers
  > set one by default anyway, but it won't be secure and Samesite=None`
- The Brightspace user-matching block (lines ~124–135) and SAML handling (lines ~86–120) live
  in the session bootstrap.

**Conclusion:** `X-Frame-Options: DENY` directly contradicts an existing, deliberately-built
embedding capability. The two must be reconciled.

---

## 5. Recommended plan to make this safe

### 5.1 Roll out in Report-Only mode first

- Emit `Content-Security-Policy-Report-Only` (not enforcing) with a `report-to` / `report-uri`
  endpoint.
- Deploy to real sites, collect actual violations for a few weeks, build an accurate policy
  from real data, then promote to enforcing once the report stream is quiet.
- This is the direct answer to "we can't enumerate the URLs" — the browsers enumerate them.

### 5.2 Make every header configurable, default-safe on upgrade

- Surface enable/disable toggles, the CSP string, and the `frame-ancestors` list as
  site/module preferences. Different installs use different CDNs, IdPs, and LTI consumers, so a
  hardcoded list will always be wrong for someone.
- For existing sites being upgraded, default to **off or Report-Only** so nothing silently
  breaks.
- Model the implementation on the existing `cookieSameSite()` preference-with-safe-downgrade
  pattern in `Session.php`.

### 5.3 Fix framing vs. embedding

- **Drop `X-Frame-Options: DENY`** — it is the single line that breaks Brightspace.
- Replace framing control with CSP **`frame-ancestors`** listing allowed embedders, e.g.
  `frame-ancestors 'self' https://*.brightspace.com <customer LMS origins>`, defaulting to
  `'self'`.
- **Tie framing policy to the existing `cookie_samesite` signal.** When
  `cookie_samesite === 'None'` the install is declaring an embedded deployment, so the framing
  policy must allow embedding. Wire the two together (or add a parallel `frame_ancestors`
  preference) rather than hardcoding.

### 5.4 Fix the policy before enforcing

- Add `'unsafe-inline'` to `style-src` (effectively mandatory) and almost certainly
  `script-src`.
- Add `connect-src` entries for any AJAX endpoints in use.
- HTTPS-gate HSTS (`if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')`), make it
  opt-in, consider a smaller initial `max-age`.
- Reconsider `Referrer-Policy: same-origin` → `strict-origin-when-cross-origin`.

### 5.5 Decide and document scope

- If site-wide protection is intended, move the headers out of the root `index.php` into a
  central location (`header.php` or an `icms` preload event). Pair this with §5.1 + §5.2,
  because centralizing is where breakage risk is highest.
- If home-page-only is intended, keep them in `index.php` but acknowledge the limited security
  value.

### 5.6 Test matrix (devtools console + network tab open, watching for CSP violations)

1. Anonymous home page
2. Logged-in home page
3. A Formulize screen page
4. The admin UI
5. A form with AJAX elements
6. **The Brightspace LTI iframe launch** (`controller.php` → `index.php`)
7. A full SAML login round-trip

---

## 6. Summary of recommended changes

| Item | Current | Recommended |
|------|---------|-------------|
| Rollout mode | Enforcing | `Report-Only` first, then enforce |
| Configurability | Hardcoded | Per-install preferences, default-off on upgrade |
| `X-Frame-Options` | `DENY` | Remove; use CSP `frame-ancestors` |
| Framing allow-list | none | `frame-ancestors 'self' <LMS origins>`, tied to `cookie_samesite` |
| `script-src` | no `'unsafe-inline'` | add `'unsafe-inline'` (or nonces — impractical here) |
| `style-src` | no `'unsafe-inline'` | add `'unsafe-inline'` |
| HSTS | unconditional, 2 yr | HTTPS-gated, opt-in, smaller initial max-age |
| `Referrer-Policy` | `same-origin` | `strict-origin-when-cross-origin` |
| Scope | root `index.php` only | decide home-page-only vs. centralized site-wide |

---

## 7. Key files

- [`index.php`](index.php) — where the headers were added (lines ~60–79)
- [`libraries/brightspace/controller.php`](libraries/brightspace/controller.php) — includes root `index.php` for LTI
- [`libraries/brightspace/index.php`](libraries/brightspace/index.php) — LTI/OAuth handshake
- [`libraries/icms/core/Session.php`](libraries/icms/core/Session.php) — `cookieSameSite()`, SameSite=None for embedding, SAML/Brightspace session setup
