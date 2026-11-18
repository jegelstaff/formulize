<?php

/* Copyright the Formulize Project - Julian Egelstaff 2021
 *
 * Shared 2FA method constants.
 *
 * These were originally defined inline in include/2fa/manage.php, which works
 * for the 2FA dialog scripts that include manage.php. But the constants are
 * also used on the Formulize form-save path (modules/formulize/class/
 * userAccountElement.php), which does not include manage.php. Defining them in
 * this standalone, side-effect-free file lets both code paths share a single
 * source of truth.
 */

if (!defined('TFA_OFF')) {
	define('TFA_OFF', 0);
}
if (!defined('TFA_EMAIL')) {
	define('TFA_EMAIL', 2);
}
if (!defined('TFA_SMS')) {
	define('TFA_SMS', 1);
}
if (!defined('TFA_APP')) {
	define('TFA_APP', 3);
}
if (!defined('TFA_MAX_ATTEMPTS')) {
	define('TFA_MAX_ATTEMPTS', 5); // failed 2FA code guesses allowed before the cooldown kicks in
}
if (!defined('TFA_LOCKOUT_SECONDS')) {
	define('TFA_LOCKOUT_SECONDS', 900); // cooldown once the attempt limit is hit, in seconds (15 min)
}
if (!defined('TFA_RESEND_INTERVAL')) {
	define('TFA_RESEND_INTERVAL', 90); // minimum gap before another email/SMS code is sent to a user's on-file contact, in seconds
}
// How long a "remember this device" trust token lasts. The actual window is the
// Formulize module preference `tfaRememberDeviceDays` (Users -> Settings -> Signing in),
// read and clamped by tfa_rememberDeviceDays() in manage.php; these are the default and
// the hard bounds it is clamped to.
if (!defined('TFA_REMEMBER_DEVICE_DEFAULT_DAYS')) {
	define('TFA_REMEMBER_DEVICE_DEFAULT_DAYS', 30); // used when the preference is unset/non-numeric
}
if (!defined('TFA_REMEMBER_DEVICE_MIN_DAYS')) {
	define('TFA_REMEMBER_DEVICE_MIN_DAYS', 1);
}
if (!defined('TFA_REMEMBER_DEVICE_MAX_DAYS')) {
	define('TFA_REMEMBER_DEVICE_MAX_DAYS', 365);
}
if (!defined('TFA_REMEMBER_DEVICE_COOKIE')) {
	define('TFA_REMEMBER_DEVICE_COOKIE', 'tfa_remember_device'); // cookie holding the random per-device trust token
}

// 2FA AJAX endpoint paths (concatenate with XOOPS_URL at the point of use). Centralised here so the
// login, profile-edit and userAccount-element flows all reference one definition if a path changes.
if (!defined('TFA_CONFIRM_URL_PATH')) {
	define('TFA_CONFIRM_URL_PATH', '/include/2fa/confirm.php');
}
if (!defined('TFA_VALIDATE_STEP1_URL_PATH')) {
	define('TFA_VALIDATE_STEP1_URL_PATH', '/include/2fa/validate_step1.php');
}
if (!defined('TFA_CHALLENGE_URL_PATH')) {
	define('TFA_CHALLENGE_URL_PATH', '/include/2fa/challenge.php');
}

// jQuery UI position for the 2FA dialogs (login, lost password, confirming account changes), as a JS
// object literal. They hold a text input, so they sit near the top of the window rather than in the middle:
// a phone's keyboard covers the lower half of the screen, and would cover the code box and the OK button.
// The dialog stays absolutely positioned, not fixed, so on a very short screen the page can still be scrolled to reach the buttons.
// jQuery UI only sets the top. Left and right are centred with CSS, because an auto width dialog shrinks to fit
// the space to the right of whatever left jQuery UI gives it, and ends up off centre.
if (!defined('TFA_DIALOG_POSITION_JS')) {
	define('TFA_DIALOG_POSITION_JS', "{ my: 'center top', at: 'center top+8%', of: window, collision: 'fit', using: function(pos) { jQuery(this).css({ top: pos.top, left: 0, right: 0, marginLeft: 'auto', marginRight: 'auto', width: 'fit-content' }); } }");
}
