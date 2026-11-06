# Self-registration: signup.php

How self-service account signup works. It's built on the System Users form and the token system.

## The flow

`signup.php`, in the web root, renders the **System Users form**, trimmed to first and last name, username, password, email and phone. It saves through the same `readelements.php` pipeline as `edituser.php` and `users.php`. `register.php` and `modules/profile/register.php` redirect to it, passing `?token=`.

Confirmation reuses the 2FA code machinery (`sendCode()` and `validateCode()` in `include/2fa/manage.php`), preferring email and falling back to SMS. A correct code activates the account and logs the user in, with session setup that mirrors `checklogin.php`.

## The gate

`formulize_selfRegistrationActive()` in `functions.php` is true only when `$GLOBALS['formulize_selfRegistration']` is set, which only `signup.php` does, there's no `$xoopsUser`, and `allow_register` is on.

The System Users form is locked, so this one gate is what lets an anonymous visitor render and save a new entry on it. These places check it:

- `formulize_systemManagedFormAccessCheck()` in `functions.php`
- `canProcessUserAccountSubmission` and `loadOrCreateUserContext` in `userAccountElement.php`, which give a new self-registered user level 0
- `GroupMembershipService::processUserGroupMemberships()`, which does nothing for them
- `user_can_edit_entry()` in `usersGroupsPerms.php`, without which the fields render disabled

## Groups and tokens

`signup.php` assigns groups itself, in `formulize_signupAssignGroups()`, not through the permission-gated element path. New users always get Registered Users plus the token's groups, and Anonymous and Webmaster are stripped for safety.

Tokens are in `class/token.php` and `admin/managetokens.php`. The `groups` column is a space-separated list, and `insert($groups, $expiry, $len, $maxuses, $customKey)` accepts a custom token value. The manage tokens page leaves out the Webmaster, Registered Users and Anonymous groups, and template groups. It offers admin-created groups and entry groups. `formulize_signupAssignGroups()` enforces the same exclusions, with `formulize_signupTemplateGroupIds()`, so a hand-crafted token can't grant them either.

A token comes from `?token=` or the invitation code box, is kept in `$_SESSION['formulize_signup_token']`, and is used up (`incrementUses`) when the account is created.

## Account status

Level 0 is "Pending confirmation", distinct from -1, Disabled. Self-registered accounts start at 0 and change to 1 on confirmation. `include/checklogin.php` rejects level 0 or below, so pending users can't log in. When rendering status, null means a new account, which defaults to Active, and an explicit 0 means Pending.

## Settings

- `allow_register` and `requireTokenForSignup` are both in the New Users section of the Users settings page, in `configsettings_registry.php`. The second only shows when the first is on.
- When `requireTokenForSignup` is on and there's no valid token, `signup.php` skips `readelements.php` and the account form, and shows the token box instead.

## Password help text

The `userAccountPassword` element's help text is in `ele_desc`, the standard help text mechanism, with the value `_formulize_USERACCOUNT_PWREPEATDESC`. New elements get it from `setupAndValidateElementProperties`, and the System Users copy gets it from the password `extraElement` in `ensureUsersTableForm()`.

## Throttling

`formulize_signupThrottleExceeded()` limits account creations per source IP (`FORMULIZE_SIGNUP_THROTTLE_MAX`, 60 by default, per `FORMULIZE_SIGNUP_THROTTLE_WINDOW`, an hour by default), using one small file per hashed IP in `cache/`. While throttled, `signup.php` skips `readelements.php`, so no account is created and no confirmation code is sent.

## Tests

`tests/e2e/formulize-core/02-validate/032-self-registration-signup.spec.js` covers signup turned off, the fields rendering when it's on, full signup through pending and confirmation to active and auto-login, pending users not being able to log in, the manage tokens page, a token in the URL granting its group and being used up, and requiring tokens. It reads the confirmation code from `tfa_codes`, because the Docker setup can't send mail.
