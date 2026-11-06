# 2FA when editing your own account

When a user edits their OWN account (EAU forms / users.php) and 2FA is enabled site-wide, changing email/phone/method/password triggers a 2FA confirm dialog. The decision **two-phase** (verify OLD contact, then NEW contact) vs **single-phase** is computed by the SAME boolean logic in three places that MUST stay in sync:

1. Server: `validateOwnAccount2faTransition()` `$contactChanging` in `class/userAccountElement.php`
2. JS on save: `tfa_needsTwoPhase` in `class/userAccount2FAElement.php::generateValidationCode()`
3. JS auto-reopen after failed attempt: `tfa_needsTwoPhase_ro` in `userAccount2FAElement.php::render()`

Key rules: **TFA_OFF is treated as email-ish** (email is the default 2FA contact when no method set), so changing email while 2FA is off double-validates old+new email. Use the normalized `$effectiveNewMethod` (`$rawSubmitted2faMethod !== null ? raw : $old`) NOT the raw submitted method — raw is `null` when the 2FA radio isn't in the POST (e.g. inline list edits on users.php), and `null != $old` falsely reads as a method change.

Two-phase cases: (1) staying email-ish + email changing, (2) staying SMS + phone changing, (3) switching between two different ACTIVE (non-off) methods. Endpoints `include/2fa/confirm.php` and `validate_step1.php` already resolve OFF→email for old AND new contact, so phase logic changes need no endpoint changes.

## One method at a time

An account uses one 2FA method at a time. After a correct email or SMS code, `validateCode()` in `include/2fa/manage.php` deletes all of that user's rows in `tfa_codes`, whatever their method. That's intended. An account using email or SMS has no TOTP entry, so a TOTP row on that account is a leftover that shouldn't be there.
