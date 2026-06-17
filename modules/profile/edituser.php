<?php
/**
 * Extended User Profile — handoff to the unified self-service account editor.
 *
 * Account editing is now unified in the Formulize user-account form rendered by /edituser.php,
 * so every entry point shares one form and one security model. This page used to build its own
 * profile form; it now simply redirects to /edituser.php, so any old bookmark or link lands on
 * the same self-service form for the current user.
 *
 * @package modules
 * @subpackage profile
 */

include '../../mainfile.php';

if (!is_object(icms::$user)) {
	redirect_header(ICMS_URL, 3, _NOPERM);
	exit();
}

redirect_header(ICMS_URL . '/edituser.php', 0, '');
exit();
