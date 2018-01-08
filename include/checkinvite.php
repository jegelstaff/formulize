<?php
/**
 * The check invite include file
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license	LICENSE.txt
 * @package	core
 * @since	1.1
 * @author	modified by UnderDog <underdog@impresscms.org>
 * @version	$Id: checkinvite.php 20426 2010-11-20 22:28:44Z phoenyx $
 */

if (!defined('ICMS_ROOT_PATH')) {
	exit();
}

/**
 * Loads the invite code
 *
 * @param	string	$code	 Invitation code
 **/
function load_invite_code($code) {
	// validate if code is of valid length.
	if (empty($code) || strlen($code) != 8) {
		header('Location: invite.php');
		// redirect_header('invite.php', 0, _US_INVITENONE);
		exit();
	}
	$sql = sprintf(
		'SELECT invite_to, invite_date, register_id, extra_info FROM %s WHERE invite_code = %s AND register_id = 0',
		icms::$xoopsDB->prefix('invites'), icms::$xoopsDB->quoteString(addslashes($code))
	);
	$result = icms::$xoopsDB->query($sql);
	list($invite_to, $invite_date, $register_id, $extra_info) = icms::$xoopsDB->fetchRow($result);
	if (empty($invite_to)) {
		redirect_header('invite.php', 3, _US_INVITEINVALID);
		exit();
	}
	// discard if already registered or invite is more than 3 days old
	if (! empty($register_id) || (int) ($invite_date) < time() - 3 * 86400) {
		redirect_header('invite.php', 3, _US_INVITEEXPIRED);
		exit();
	}
	// load default email and actkey
	global $email, $actkey;
	$email = $invite_to;
	$actkey = $code;
	// load extra_info
	$extra_array = unserialize($extra_info);
	foreach ($extra_array as $ex_key => $ex_value) {
		$GLOBALS[$ex_key] = $ex_value;
	}
	// update view time
	$sql = sprintf(
		'UPDATE ' . icms::$xoopsDB->prefix('invites') . ' SET view_date = %d WHERE invite_code = %s AND register_id = 0',
		time(), icms::$xoopsDB->quoteString(addslashes($code))
	);
	$result = icms::$xoopsDB->queryF($sql);
}

/**
 * Checks if invite code is correct
 *
 * @param	string	$code	 Invitation code
 * @return   bool
 **/
function check_invite_code($code) {
	// validate if code is of valid length.
	if (empty($code) || strlen($code) != 8) {
		return false;
	}
	$sql = sprintf(
		'SELECT invite_to, invite_date FROM %s WHERE invite_code = %s AND register_id = 0',
		icms::$xoopsDB->prefix('invites'), icms::$xoopsDB->quoteString(addslashes($code))
	);
	$result = icms::$xoopsDB->query($sql);
	list($invite_to, $invite_date) = icms::$xoopsDB->fetchRow($result);
	if (empty($invite_to) || !empty($register_id) || (int) ($invite_date) < time() - 3 * 86400) {
		return false;
	}
	return true;
}

/**
 * Updates the invite code into the database
 *
 * @param	string	$code	 Invitation code
 * @param	int	   $new_id   New registration id
 * @return   true
 **/
function update_invite_code($code, $new_id) {
	// update register_id
	$sql = sprintf(
		'UPDATE ' . icms::$xoopsDB->prefix('invites') . ' SET register_id = %d WHERE invite_code = %s AND register_id = 0',
		$new_id, icms::$xoopsDB->quoteString(addslashes($code))
	);
	$result = icms::$xoopsDB->query($sql);
	return true;
}
