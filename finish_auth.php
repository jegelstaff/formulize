<?php
/**
 * Complete the OpenID authentication
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package		Auth
 * @subpackage  Openid
 * @since		1.1
 * @author		malanciault <marcan@impresscms.org>
 * @version		SVN: $Id: finish_auth.php 21731 2011-06-10 21:36:28Z skenow $
 */
/**
 * Set this to TRUE to troubleshoot OpenID login
 */
$openid_debug = FALSE;

define('ICMS_INCLUDE_OPENID', TRUE);
$xoopsOption['pagetype'] = 'user';
/** Including mainfile.php is required */
include_once 'mainfile.php';

$redirect_url = $_SESSION['frompage'];
$member_handler = icms::handler('icms_member');

/** Including the language files for the authentication pages */
icms_loadLanguageFile('core', 'auth');

$xoopsAuth = & icms_auth_Factory::getAuthConnection(NULL);
$user = $xoopsAuth->authenticate($openid_debug);

if ($xoopsAuth->errorOccured()) {
	redirect_header($redirect_url, 3, $xoopsAuth->getHtmlErrors());
}

switch ($xoopsAuth->step) {
	case OPENID_STEP_NO_USER_FOUND :
		$xoopsOption['template_main'] = 'system_openid.html';
		/** Including header.php to start page rendering */
		include_once ICMS_ROOT_PATH . "/header.php" ;

		$sreg = $_SESSION['openid_sreg'];

		$xoopsTpl->assign('displayId', $xoopsAuth->displayid);
		$xoopsTpl->assign('cid', $xoopsAuth->openid);
		$xoopsTpl->assign('uname', isset ($sreg['nickname']) ? $sreg['nickname'] : '');
		$xoopsTpl->assign('email', isset ($sreg['email']) ? $sreg['email'] : '');
		/** Including footer.php to complete page rendering */
		include_once ICMS_ROOT_PATH . '/footer.php';
		break;

	case OPENID_STEP_REGISTER :

		/**
		 * setting the step to the previous one for if there is an error, user will be redirected
		 * a step behind
		 */
		$_SESSION['openid_step'] = OPENID_STEP_NO_USER_FOUND;

		$sreg = $_SESSION['openid_sreg'];
		/** Including header.php to start page rendering */
		include_once ICMS_ROOT_PATH . '/header.php' ;

		/**
		 * @todo this is only temporary and it needs to be included in the template as a javascript check
		 */
		if (empty ($_POST['email']) || empty ($_POST['uname'])) {
			redirect_header(ICMS_URL . '/finish_auth.php', 3, 'email and username are mandatory');
		}

		$email = addslashes($_POST['email']);

		$uname = addslashes($_POST['uname']);
		/**
		 * @todo use the related UserConfigOption
		 */
		if (strlen($uname) < 3) { // Username too short.
			redirect_header(ICMS_URL . '/finish_auth.php', 3, _US_OPENID_NEW_USER_UNAME_TOO_SHORT);
		}

		// checking if this uname is available
		$criteria = new icms_db_criteria_Compo(new icms_db_criteria_Item('uname', $uname));
		$user_handler = icms::handler('icms_member_user');
		$users = & $user_handler->getObjects($criteria, FALSE);

		if (is_array($users) && count($users) > 0) {
			redirect_header(ICMS_URL . '/finish_auth.php', 3, _US_OPENID_NEW_USER_UNAME_EXISTS);
		}

		$name = addslashes(icms_core_DataFilter::stripSlashesGPC(utf8_decode($sreg['fullname'])));
		//$tz = quote_smart($tzoffset[$sreg['timezone']]);
		$country = addslashes(icms_core_DataFilter::stripSlashesGPC(utf8_decode($sreg['country'])));

		/**
		 * @todo use proper core class, manage activation_type and send notifications
		 */

		$newUser = $member_handler->createUser();
		$newUser->setVar('uname', $uname);
		$newUser->setVar('email', $email);
		$newUser->setVar('name', $name);
		$newUser->setVar('pass', '*');
		$newUser->setVar('user_regdate', time());
		$newUser->setVar('level', 1);
		$newUser->setVar('country', $country);
		$newUser->setVar('timesone_offset', $icmsConfig['default_TZ']);
		$newUser->setVar('openid', $xoopsAuth->openid);
		if (!$member_handler->insertUser($newUser)) {
			redirect_header(ICMS_URL . '/finish_auth.php', 3, _US_OPENID_NEW_USER_CANNOT_INSERT . ' ' . $newUser->getHtmlErrors());
		}

		// Now, add the user to the group.
		$newid = $newUser->getVar('uid');
		$mship_handler = icms::handler('icms_member_group_membership');
		$mship = & $mship_handler->create();
		$mship->setVar('groupid', XOOPS_GROUP_USERS);
		$mship->setVar('uid', $newid);
		if (!$mship_handler->insert($mship)) {
			redirect_header($redirect_url, 3, _US_OPENID_NEW_USER_CANNOT_INSERT_INGROUP);
		}

		// Login with this user.

		/**
		 * @todo use proper login process (include/checklogin.php)
		 */
		if ($newUser->getVar('level') == 0) {
			redirect_header($redirect_url, 3, _US_OPENID_NEW_USER_AUTH_NOT_ACTIVATED);
		}

		$_SESSION['xoopsUserId'] = $newUser->getVar('uid');
		$_SESSION['xoopsUserGroups'] = $newUser->getGroups();
		$user_theme = $newUser->getVar('theme');

		if (in_array($user_theme, $icmsConfig['theme_set_allowed'])) {
			$_SESSION['xoopsUserTheme'] = $user_theme;
		}

		unset ($_SESSION['openid_response']);
		unset ($_SESSION['openid_sreg']);
		unset ($_SESSION['frompage']);

		redirect_header($redirect_url, 3, sprintf(_US_OPENID_NEW_USER_CREATED, $newUser->getVar('uname')));

		break;

	case OPENID_STEP_USER_FOUND :
		/** Including the login authentication page */
		include_once 'include/checklogin.php';
		exit;
		break;

	case OPENID_STEP_LINK :
		// Linking an existing user with this openid
		/** Including header.php to start page rendering */
		include_once ICMS_ROOT_PATH . '/header.php' ;

		$uname4sql = addslashes(icms_core_DataFilter::stripSlashesGPC($_POST['uname']));
		$pass4sql = addslashes(icms_core_DataFilter::stripSlashesGPC($_POST['pass']));

		$thisUser = $member_handler->loginUser($uname4sql, $pass4sql);

		if (!$thisUser) {
			redirect_header($redirect_url, 3, _US_OPENID_LINKED_AUTH_FAILED);
		}

		if ($thisUser->getVar('level') == 0) {
			redirect_header($redirect_url, 3, _US_OPENID_LINKED_AUTH_NOT_ACTIVATED);
		}

		// This means the authentication succeeded.
		$displayId = $xoopsAuth->response->getDisplayIdentifier();

		$thisUser->setVar('last_login', time());
		$thisUser->setVar('openid', $xoopsAuth->openid);

		if (!$member_handler->insertUser($thisUser)) {
			redirect_header($redirect_url, 3, _US_OPENID_LINKED_AUTH_CANNOT_SAVE);
		}

		$_SESSION['xoopsUserId'] = $thisUser->getVar('uid');
		$_SESSION['xoopsUserGroups'] = $thisUser->getGroups();
		$user_theme = $thisUser->getVar('theme');

		if (in_array($user_theme, $icmsConfig['theme_set_allowed'])) {
			$_SESSION['xoopsUserTheme'] = $user_theme;
		}

		unset ($_SESSION['openid_response']);
		unset ($_SESSION['openid_sreg']);
		unset ($_SESSION['frompage']);

		redirect_header($redirect_url, 3, sprintf(_US_OPENID_LINKED_DONE, $thisUser->getVar('uname')));
		break;

}
