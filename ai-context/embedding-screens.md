# Embedding screens in other websites

Embedding lets a Formulize screen render inside an iframe on another website. For how to set it up, see [docs/embedding_screens.md](../docs/embedding_screens.md).

## Who can frame a screen

**No website can frame anything until an administrator allows it.** Every page says that only this site may frame it (`formulize_sendFrameAncestorsHeader()`, which sends a `Content-Security-Policy: frame-ancestors` header). `formulizeEmbeddingEnabled` is the single switch over everything to do with embedding, and it is off by default. Once it is on, websites are allowed site-wide or on a screen (`embedOrigins`), and `formulize_siteFrameAncestors()` and `formulize_sendScreenFrameAncestorsHeader()` add them to the header. A lone `*` is not accepted.

**An embedded screen can't send anyone to the login page.** That page may only be framed by this site, so the browser would blank the frame. `formulize_embeddedNoPermissionHtml()` shows a message in the frame instead, and offers to open the screen in a new tab.

## Whether the screen is signed in

**Where the screen is embedded decides this, not SameSite alone.** The session cookie is `SameSite=Lax` by default (`icms_core_Session::cookieSameSite()`, configurable through the `cookie_samesite` preference). Lax blocks the cookie on **cross-site** framing only. A screen framed by a page on the **same registrable domain and same scheme** (`forms.example.com` inside `www.example.com`) IS same-site, so the cookie flows and the embedded screen is fully signed in. That's the configuration the docs tell people to build, so "embedded screens are always anonymous" is false for the recommended deployment. People can skip it if every visitor will be anonymous.

`formulize_sortOriginsBySessionSharing()` and `formulize_hostSessionRelationship()` classify each listed website as same-site or cross-site against this site's own host, so the notice can be accurate per entry. A different scheme or an IP address is cross-site. No Public Suffix List is carried, so same-site is only claimed for the same host, parent/child, or a shared parent that is not in `formulize_multiLabelPublicSuffixes()`. A short two-label tail under a country code that isn't in that list can't be told from a registered name, so it is left out of both buckets rather than guessed. A suffix missing from that list anywhere else still gets reported as same-site, so those are worth adding.

Set `cookie_samesite` to `None` and the session travels cross-site too. The origin allowlist is then the only control. Patch 000 defaults it to `None` on LTI/Brightspace sites, detected by the `lti2_consumer` table. `None` needs HTTPS: `cookieSameSite()` downgrades it to `Lax` otherwise, because browsers reject it without a Secure cookie.

## The formulize_embed URL parameter

It looks redundant, and it isn't, so don't propose removing it. The reasoning is in the docblock on `formulize_isEmbeddedRequest()` in `modules/formulize/include/functions.php`. The header `Sec-Fetch-Dest: iframe` answers "is this request loading a document into a frame right now?", which is narrower than "should this render as an embed?". The parameter is needed for browsers that don't send Fetch Metadata, and for the "open in a new window" fallback, a top-level load that should still render without site chrome, because the visitor is working on someone else's site.

**The parameter may only ever decide presentation.** `formulize_isAuthoritativelyEmbeddedRequest()`, which looks at the header only, is what anything with a consequence must ask. It's what gates writing the cross-site cookie.

## CSRF tokens

This is how tokens differ inside a frame. For how they work, see [security-tokens](security-tokens.md).

**Anonymous tokens are deliberately bound less strictly than logged-in ones.** Tokens are filed under `session_id()`, but in a cross-site frame the session cookie never arrives, so nothing could ever validate. Anonymous visitors bind to a `__Host-formulize_anonbind` cookie (`SameSite=None; Partitioned`) instead. Logged-in requests always use `session_id()` and never consult the cookie.

Don't tidy up this asymmetry, and don't weaken it to a bare unbound token. Anonymous sessions hold no privilege to steal, but binding still matters: without it, an attacker mints a token and gets thousands of unwitting visitors' browsers to fire chosen submissions, which scales for free, launders the source IPs and defeats IP-based abuse controls. That's a narrower threat than the logged-in one, which is why anonymous visitors get a lower bar rather than the same bar.

**Anonymous tokens are filed under one key but validated under either.** This covers a same-site embed on a first visit. No session cookie exists yet, and the server can't tell "not issued yet" from "blocked cross-site", so it issues a bind cookie and files the token under it. The same response sets the anonymous session cookie, which same-site keeps, so the submit carries both. `icms_core_Security::tokenBindKeysToCheck()` therefore looks under `session_id()` and `formulize_anonBindCookieValue()`. `Sec-Fetch-Site` can't replace this, because in-frame navigations report `same-origin` even inside a cross-site frame. The bind cookie slides, re-issued with the same value on each authoritative frame load. CSRF tokens themselves don't need to, because they're per page render.

See [anonymous-entry-access](anonymous-entry-access.md) for how an embedded visitor's entry cookie works.

## The embed theme

**Never set `$icmsConfig['theme_set']` to the embed theme.** `$xoopsConfig` is the same array by reference, and per-screen templates resolve under `modules/formulize/templates/screens/<theme_set>/<sid>/`, so switching it sends embedded screens to the default templates instead of the site's customised ones. The embed theme is selected by passing `folderName`, from `formulize_embedRenderingTheme()`, to `createInstance()` in `header.php` instead. See [bootstrap-include-order](bootstrap-include-order.md).

Embed themes are marked by a `formulize-embed-theme.marker` file in the theme folder (`FORMULIZE_EMBED_THEME_MARKER`). An embed theme needs a `theme.html` like any other, so the marker is what keeps it out of the theme pickers. `icms_view_theme_Factory::getThemesList()` leaves marked themes out, `formulize_selectableThemesList()` applies the same rule to other lists such as the admin themes, and `formulize_embedThemesList()` lists them for the `formulizeEmbedTheme` preference. An embed theme borrows the site theme's styling (`formulize_embedBaseTheme()`).

The embed theme keeps its own `session-timeout-warning.html` on purpose, because not every theme has one, and its `theme.html` depends on `#session-timeout-warning`.

## The embed script

Host pages load `formulize-embed.js` from the Formulize server at runtime, so a change to it reaches a live site only when that server is updated. Say so when you report a fix, and check what the live URL actually serves.

## Behaviour that's by design

Don't raise these as bugs:

- Sites that were already framed, such as LTI or portals, go blank after an upgrade until an admin turns embedding on and lists the origins. The message from patch 011 tells the admin.
- Navigating a frame to a page that isn't a screen, such as `redirect_header`, print views or other screens, carries only the site-wide list. A screen that fails under embedding is a matter for testing before launch.
- The generated embed code has a placeholder, `{formulize-address}` (`FORMULIZE_ADDRESS_PLACEHOLDER`), where the admin puts the embedding address.
- The session-sharing notice sorts websites against the hostname the admin is browsing from, because that's all the request knows. It can't see the address the iframe will use.
