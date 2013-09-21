<?php
/**
 * All functions for Password Expiry & Reset Password generator are going through here.
 * Form and process for resetting password and sending to user
 * 
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package		Member
 * @subpackage	Users
 * @since		ImpressCMS 1.1
 * @author		Vaughan Montgomery <vaughan@impresscms.org>
 * @author		The ImpressCMS Project
 * @version		SVN: $Id: resetpass.php 11946 2012-08-23 12:45:17Z m0nty $
 */

$xoopsOption['pagetype'] = 'user';
include 'mainfile.php';

/* the following are passed through $_POST/$_GET
 *	'c_password' => 'str',
 *	'password' => 'str',
 *	'password2' => 'str',
 */
$filter_get = array(
	'email' => array('email', 'options' => array(0, 1)),
);
$filter_post = array(
	'email' => array('email', 'options' => array(0, 1)),
);
if (!empty($_GET)) {
    $clean_GET = icms_core_DataFilter::checkVarArray($_GET, $filter_get, FALSE);
    extract($clean_GET);
}
if (!empty($_POST)) {
    $clean_POST = icms_core_DataFilter::checkVarArray($_POST, $filter_post, FALSE);
    extract($clean_POST);
}

global $icmsConfigUser;
if ($password == '' || $password2 == '') {
	redirect_header('user.php?op=resetpass', 3, sprintf(_US_SORRYMUSTENTERPASS, icms::$user->getVar('uname')), FALSE);
}
if ((isset($password)) && ($password !== $password2)) {
	redirect_header('user.php?op=resetpass', 3, sprintf(_US_PASSNOTSAME, ''), FALSE);
} elseif (($password !== '') && (strlen($password) < $icmsConfigUser['minpass'])) {
	redirect_header('user.php?op=resetpass', 2, sprintf(_US_PWDTOOSHORT, $icmsConfigUser['minpass']), FALSE);
}

if (!icms::$user) {
	redirect_header('user.php', 2, sprintf(_US_SORRYNOTFOUND, 3, ''), FALSE);
} else {
	$icmspass = new icms_core_Password();

	if (!$icmspass->verifyPass($c_password, icms::$user->getVar('login_name'))) {
		redirect_header('user.php?op=resetpass', 2, _US_SORRYINCORRECTPASS);
	}

	$pass = $icmspass->encryptPass($password);
	$xoopsMailer = new icms_messaging_Handler();
	$xoopsMailer->useMail();
	$xoopsMailer->setTemplate('resetpass2.tpl');
	$xoopsMailer->assign('SITENAME', $icmsConfig['sitename']);
	$xoopsMailer->assign('ADMINMAIL', $icmsConfig['adminmail']);
	$xoopsMailer->assign('SITEURL', ICMS_URL.'/');
	$xoopsMailer->assign('IP', $_SERVER['REMOTE_ADDR']);
	$xoopsMailer->setToUsers(icms::$user->getVar('uid'));
	$xoopsMailer->setFromEmail($icmsConfig['adminmail']);
	$xoopsMailer->setFromName($icmsConfig['sitename']);
	$xoopsMailer->setSubject(sprintf(_US_PWDRESET, ICMS_URL));
	if (!$xoopsMailer->send()) {
		echo $xoopsMailer->getErrors();
	}

	$sql = sprintf("UPDATE %s SET pass = '%s', pass_expired = '%u' WHERE uid = '%u'",
					icms::$xoopsDB->prefix('users'),
					$pass,
					0,
					(int) icms::$user->getVar('uid')
	);
	if (!icms::$xoopsDB->query($sql)) {
		include 'header.php';
		echo _US_RESETPWDNG;
		include 'footer.php';
		exit();
	}
	unset($pass);
	redirect_header('user.php', 3, sprintf(_US_PWDRESET, icms::$user->getVar('uname')), FALSE);
}
