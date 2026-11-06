# Password hashing

`icms_core_Password`, in `libraries/icms/core/Password.php`, handles passwords.

**New passwords use bcrypt.** `hashPassword()` hashes with bcrypt, after adding a pepper that's kept outside the database, so a stolen database alone isn't enough. Use it for every new password write: registration, reset, admin-set and account edit. The hash carries its own algorithm, cost and salt.

**Login goes through `verifyPassword()`,** which handles both kinds of stored hash. `loginUser()` in `libraries/icms/member/Handler.php` checks `passwordNeedsUpgrade()` after a successful login, and re-hashes old passwords with `hashPassword()`. So older accounts move to bcrypt as they log in. `01-setup/002-password-hashing-login.spec.js` and `tests/password_hashing_test.php` cover this.

**Old hashes depend on one site-wide `enc_type`.** `encryptPass()` is only for checking hashes that haven't been upgraded yet, so don't use it for new passwords. It ignores the `$enc_type` it's given and forces `$icmsConfigUser['enc_type']`, unless `$reset` is 1, as it is in the password-expired flow. So the per-user `users.enc_type` column is effectively vestigial for normal login.

**Store the three values together.** A password is `pass`, `salt` and `enc_type`, and writing one without the others can produce an account that exists and can't log in. In `userAccountElement.php`, `hashPasswordForStorage()` returns all three.
