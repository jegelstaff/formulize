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
