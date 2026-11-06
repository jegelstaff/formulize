# Anonymous access to entries

Every anonymous visitor is uid 0, so an entry being *owned* by uid 0 says nothing about **which**
visitor it belongs to. Anonymous access to an entry is therefore granted on possession of an
unforgeable token, never on ownership. There are two kinds of token: a signed cookie, and a passcode
held in the session.

All the helpers below live in `modules/formulize/include/functions.php`.

Anonymous visitors only reach forms through screens. `initialize.php` turns an anonymous `?fid=` request into the form's default screen, with `determineScreenForUserFromFid()`. If there's no default screen, they see a message saying the form isn't available to them.

## The signed cookie

**Secret** — `formulize_anonEntryToken_secret()` derives a binary HMAC key from
`XOOPS_DB_SALT` (falling back to `XOOPS_DB_PASS`, then a fixed string). It is domain-separated by
HMACing a constant tag, so this key is not interchangeable with any other use of the salt.

**Format** — the cookie is named `entryid_<fid>` and its value is `<entry_id>.<expires>.<signature>`,
where the signature is `hash_hmac('sha256', "$fid:$entry_id:$expires", secret)`.

Two things are inside the signed payload deliberately:

- **the form id**, so a token issued for one form cannot be replayed against another;
- **the expiry**, so the lifetime is enforced server side. Re-setting the cookie in the browser with
  a longer expiry does not extend it, because that does not change the signed timestamp the server
  checks.

`formulize_anonEntryTokenSignature()` is the only place the signed message is assembled, so
the signer and the verifier cannot drift apart in how it is encoded. **If you add a field to the
payload, add it there and pass it from both callers.**

**Issuing** — `formulize_setAnonEntryCookie($fid, $entry_id)` writes the cookie for 7 days through
`formulize_setCookie()`, the one place Formulize's own cookies are written. That gives it `Path=/`,
`HttpOnly`, and `Secure` when the site is HTTPS. It follows the site's SameSite preference, except on
an embedded request, where it is `SameSite=None`, `Secure` and `Partitioned`, so it's filed under the
website doing the embedding. `formulize_setCookie()` also updates `$_COOKIE` so the value is usable in
the current request. The window slides: it is re-issued on each anonymous save, from
`readelements.php` in the `if($creation_user == 0)` branch.

**Verifying** — `formulize_verifyAnonEntryCookie($fid)` returns an int entry id only when the
cookie has three parts, the first two are numeric, the expiry has not passed, and the signature
matches under `hash_equals()`. Anything missing, malformed, expired or tampered returns 0. Never read
the raw cookie yourself; a raw entry id out of `$_COOKIE` is attacker-controlled.

## The authority: `formulize_anonHoldsEntry()`

`formulize_anonHoldsEntry($form_id, $entry_id)` is the single place that answers "is this
anonymous visitor entitled to this entry?", and **both the read and the write path ask it**. It
builds a candidate list and tests membership:

- It scans the session for `formulize_passCode_<sid>` variables whose screen belongs to `$form_id`.
  If it finds one, it sets `$sawPasscodeForForm` and collects every entry whose
  `anon_passcode_<fid>` field matches, via `findAllEntriesWithValue()`.
- **A passcode for this form takes precedence over the cookie** — the cookie is only consulted when
  no passcode for this form was seen. The check is scoped to the form, so a passcode held for some
  *other* form does not suppress this form's cookie.

## Where it is enforced

| Path | Site | What it gates |
|---|---|---|
| Read | `security_check()`, functions.php | In the `owner == $user_id == 0` branch, after `view_globalscope`/`view_groupscope` have both failed. Without a held token, returns false. |
| Write | `formulizePermHandler::user_can_modify_entry()`, `class/usersGroupsPerms.php` | The own-entry branch requires `($user_id != 0 OR formulize_anonHoldsEntry(...))` before `{$action}_own_entry` is even consulted. |

`formdisplay.php` calls `formulize_verifyAnonEntryCookie()` directly rather than the
authority, because there it is *choosing which entry to display* on a single-entry form, not granting
access to it — `security_check()` still runs on whatever it picks.

## Rules when changing this

- Ownership by uid 0 is never sufficient on its own. Any new anonymous path must ask
  `formulize_anonHoldsEntry()`, not `getEntryOwner() == 0`.
- Read and write must keep asking the same authority. A rule enforced on only one of the two paths
  is not enforced at all.
- The cookie is not a session. It survives logout, carries no identity, and grants exactly one entry
  on one form.
