<?php
/**
 * Administration of users, main functions file
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		LICENSE.txt
 * @package		System
 * @subpackage	Users
 * @version		SVN: $Id: users.php 21133 2011-03-20 19:43:48Z m0nty_ $
 */

if (!is_object(icms::$user) 
	|| !is_object($icmsModule) 
	|| !icms::$user->isAdmin($icmsModule->getVar('mid'))
	) {
		exit('Access Denied');
	}

/**
 * Displays user information form
 * 
 */
function displayUsers() {
	global $icmsConfig, $icmsModule, $icmsConfigUser;
	$userstart = isset($_GET['userstart']) ? (int) $_GET['userstart'] : 0;

	icms_cp_header();
	echo '<div class="CPbigTitle" style="background-image: url(' . ICMS_MODULES_URL . '/system/admin/users/images/users_big.png)">' . _MD_AM_USER . '</div><br />';
	$member_handler = icms::handler('icms_member');
	$usercount = $member_handler->getUserCount(new icms_db_criteria_Item('level', '-1', '!='));
	$nav = new icms_view_PageNav($usercount, 200, $userstart, 'userstart', 'fct=users');
	$editform = new icms_form_Theme(_AM_EDEUSER, 'edituser', 'admin.php');
	$user_select = new icms_form_elements_Select('', 'uid');
	$criteria = new icms_db_criteria_Compo();
	$criteria->add(new icms_db_criteria_Item('level', '-1', '!='));
	$criteria->setSort('uname');
	$criteria->setOrder('ASC');
	$criteria->setLimit(200);
	$criteria->setStart($userstart);
	$user_select->addOptionArray($member_handler->getUserList($criteria));
	$user_select_tray = new icms_form_elements_Tray(_AM_NICKNAME, '<br />');
	$user_select_tray->addElement($user_select);
	$user_select_nav = new icms_form_elements_Label('', $nav->renderNav(4));
	$user_select_tray->addElement($user_select_nav);
	$op_select = new icms_form_elements_Select('', 'op');
	$op_select->addOptionArray(array('modifyUser'=>_AM_MODIFYUSER, 'delUser'=>_AM_DELUSER));
	$submit_button = new icms_form_elements_Button('', 'submit', _AM_GO, 'submit');
	$fct_hidden = new icms_form_elements_Hidden('fct', 'users');
	$editform->addElement($user_select_tray);
	$editform->addElement($op_select);
	$editform->addElement($submit_button);
	$editform->addElement($fct_hidden);
	$editform->display();

	echo "<br />\n";
	$usercount = $member_handler->getUserCount(new icms_db_criteria_Item('level', '-1'));
	$nav = new icms_view_PageNav($usercount, 200, $userstart, 'userstart', 'fct=users');
	$editform = new icms_form_Theme(_AM_REMOVED_USERS, 'edituser', 'admin.php');
	$user_select = new icms_form_elements_Select('', 'uid');
	$criteria = new icms_db_criteria_Compo();
	$criteria->add(new icms_db_criteria_Item('level', '-1'));
	$criteria->setSort('uname');
	$criteria->setOrder('ASC');
	$criteria->setLimit(200);
	$criteria->setStart($userstart);
	$user_select->addOptionArray($member_handler->getUserList($criteria));
	$user_select_tray = new icms_form_elements_Tray(_AM_NICKNAME, '<br />');
	$user_select_tray->addElement($user_select);
	$user_select_nav = new icms_form_elements_Label('', $nav->renderNav(4));
	$user_select_tray->addElement($user_select_nav);
	$op_select = new icms_form_elements_Select('', 'op');
	$op_select->addOptionArray(array('modifyUser'=>_AM_MODIFYUSER));
	$submit_button = new icms_form_elements_Button('', 'submit', _AM_GO, 'submit');
	$fct_hidden = new icms_form_elements_Hidden('fct', 'users');
	$editform->addElement($user_select_tray);
	$editform->addElement($op_select);
	$editform->addElement($submit_button);
	$editform->addElement($fct_hidden);
	$editform->display();

	echo "<br />\n";
	$uid_value = '';
	$uname_value = '';
	$login_name_value = '';
	$name_value = '';
	$email_value = '';
	$email_cbox_value = 0;
	$openid_value = '';
	$openid_cbox_value = 0;
	$url_value = '';
	//  $avatar_value = 'blank.gif';
	//  $theme_value = $icmsConfig['default_theme'];
	$timezone_value = $icmsConfig['default_TZ'];
	$icq_value = '';
	$aim_value = '';
	$yim_value = '';
	$msnm_value = '';
	$location_value = '';
	$occ_value = '';
	$interest_value = '';
	$sig_value = '';
	$sig_cbox_value = 0;
	$umode_value = $icmsConfig['com_mode'];
	$uorder_value = $icmsConfig['com_order'];

	include_once ICMS_INCLUDE_PATH .'/notification_constants.php';
	$notify_method_value = XOOPS_NOTIFICATION_METHOD_PM;
	$notify_mode_value = XOOPS_NOTIFICATION_MODE_SENDALWAYS;
	$bio_value = '';
	$rank_value = 0;
	$mailok_value = 0;
	$pass_expired_value = 0;
	$enc_type_value = $icmsConfigUser['enc_type'];
	$op_value = 'addUser';
	$form_title = _AM_ADDUSER;
	$form_isedit = FALSE;
	$language_value = $icmsConfig['language'];
	$groups = array(XOOPS_GROUP_USERS);
	include ICMS_MODULES_PATH . '/system/admin/users/userform.php';
	icms_cp_footer();
}

/**
 * Logic and rendering for modifying a member profile
 *
 * @param object $user
 */
function modifyUser($user) {
	global $icmsConfig, $icmsModule;
	icms_cp_header();
	echo '<div class="CPbigTitle" style="background-image: url(' . ICMS_MODULES_URL . '/system/admin/users/images/users_big.png)">' . _MD_AM_USER . '</div><br />';
	$member_handler = icms::handler('icms_member');
	$user =& $member_handler->getUser($user);
	if (is_object($user)) {
		if (!$user->isActive()) {
			icms_core_Message::confirm(array('fct' => 'users', 'op' => 'reactivate', 'uid' => $user->getVar('uid')), 'admin.php', _AM_NOTACTIVE);
			icms_cp_footer();
			exit();
		}

		$uid_value = $user->getVar('uid');
		$uname_value = $user->getVar('uname', 'E');
		$login_name_value = $user->getVar('login_name', 'E');
		$name_value = $user->getVar('name', 'E');
		$email_value = $user->getVar('email', 'E');
		$email_cbox_value = $user->getVar('user_viewemail') ? 1 : 0;
		$openid_value = $user->getVar('openid', 'E');
		$openid_cbox_value = $user->getVar('user_viewoid') ? 1 : 0;
		$url_value = $user->getVar('url', 'E');
		//	  $avatar_value = $user->getVar('user_avatar');
		$temp = $user->getVar('theme');
		//$theme_value = empty($temp) ? $icmsConfig['default_theme'] : $temp;
		$timezone_value = $user->getVar('timezone_offset');
		$icq_value = $user->getVar('user_icq', 'E');
		$aim_value = $user->getVar('user_aim', "E");
		$yim_value = $user->getVar('user_yim', "E");
		$msnm_value = $user->getVar('user_msnm', 'E');
		$location_value = $user->getVar('user_from', 'E');
		$occ_value = $user->getVar('user_occ', 'E');
		$interest_value = $user->getVar('user_intrest', 'E');
		$sig_value = $user->getVar('user_sig', 'E');
		$sig_cbox_value = ($user->getVar('attachsig') == 1) ? 1 : 0;
		$umode_value = $user->getVar('umode');
		$uorder_value = $user->getVar('uorder');
		$notify_method_value = $user->getVar('notify_method');
		$notify_mode_value = $user->getVar('notify_mode');
		$bio_value = $user->getVar('bio', 'E');
		$rank_value = $user->rank(FALSE);
		$mailok_value = $user->getVar('user_mailok', 'E');
		$pass_expired_value = $user->getVar('pass_expired') ? 1 : 0;
		$enc_type_value = $user->getVar('enc_type', 'E');
		$op_value = 'updateUser';
		$form_title = _AM_UPDATEUSER . ': ' . $user->getVar('uname');
		$language_value = $user->getVar('language');
		$form_isedit = TRUE;
		$groups = array_values($user->getGroups());
		include ICMS_MODULES_PATH . '/system/admin/users/userform.php';
		echo "<br /><strong>" . _AM_USERPOST . "</strong><br /><br />\n"
			. "<table>\n"
			. "<tr><td>" . _AM_COMMENTS . "</td><td>" . icms_conv_nr2local($user->getVar('posts')) . "</td></tr>\n"
			. "</table>\n"
			. "<br />" . _AM_PTBBTSDIYT . "<br />\n"
			. "<form action=\"admin.php\" method=\"post\">\n"
			. "<input type=\"hidden\" name=\"id\" value=\"" . $user->getVar('uid') . "\">"
			. "<input type=\"hidden\" name=\"type\" value=\"user\">\n"
			. "<input type=\"hidden\" name=\"fct\" value=\"users\">\n"
			. "<input type=\"hidden\" name=\"op\" value=\"synchronize\">\n"
			. icms::$security->getTokenHTML() . "\n"
			. "<input type=\"submit\" value=\"" . _AM_SYNCHRONIZE . "\">\n"
			. "</form>\n";
	} else {
		echo "<h4 style='text-align:" . _GLOBAL_LEFT . ";'>" . _AM_USERDONEXIT . "</h4>";
	}
	icms_cp_footer();
}

/**
 * Updates the member profile, saving the changes to the database
 *
 * @param $uid
 * @param $uname
 * @param $login_name
 * @param $name
 * @param $url
 * @param $email
 * @param $user_icq
 * @param $user_aim
 * @param $user_yim
 * @param $user_msnm
 * @param $user_from
 * @param $user_occ
 * @param $user_intrest
 * @param $user_viewemail
 * @param $user_avatar
 * @param $user_sig
 * @param $attachsig
 * @param $theme
 * @param $pass
 * @param $pass2
 * @param $rank
 * @param $bio
 * @param $uorder
 * @param $umode
 * @param $notify_method
 * @param $notify_mode
 * @param $timezone_offset
 * @param $user_mailok
 * @param $language
 * @param $openid
 * @param $salt
 * @param $user_viewoid
 * @param $pass_expired
 * @param $enc_type
 * @param $groups
 */
function updateUser($uid, $uname, $login_name, $name, $url, $email, $user_icq, $user_aim, $user_yim,
					$user_msnm, $user_from, $user_occ, $user_intrest, $user_viewemail, $user_avatar,
					$user_sig, $attachsig, $theme, $pass, $pass2, $rank, $bio, $uorder, $umode, $notify_method,
					$notify_mode, $timezone_offset, $user_mailok, $language, $openid, $salt, $user_viewoid,
					$pass_expired, $enc_type, $groups = array()
					) {
	global $icmsConfig, $icmsModule, $icmsConfigUser;
	$member_handler = icms::handler('icms_member');
	$edituser =& $member_handler->getUser($uid);
	if ($edituser->getVar('uname') != $uname && $member_handler->getUserCount(new icms_db_criteria_Item('uname', $uname)) > 0 || $edituser->getVar('login_name') != $login_name && $member_handler->getUserCount(new icms_db_criteria_Item('login_name', $login_name)) > 0) {
		icms_cp_header();
		echo '<div class="CPbigTitle" style="background-image: url(' . ICMS_MODULES_URL . '/system/admin/users/images/users_big.png)">' . _MD_AM_USER . '</div><br />';
		echo _AM_UNAME . ' ' . $uname . ' ' . _AM_ALREADY_EXISTS;
		icms_cp_footer();
	} else {
		$edituser->setVar('name', $name);
		$edituser->setVar('uname', $uname);
		$edituser->setVar('login_name', $login_name);
		$edituser->setVar('email', $email);
		$edituser->setVar('openid', $openid);
		$user_viewoid = (isset($user_viewoid) && $user_viewoid == 1) ? 1 : 0;
		$edituser->setVar('user_viewoid', $user_viewoid);
		$url = isset($url) ? formatURL($url) : '';
		$edituser->setVar('url', $url);
		//$edituser->setVar('user_avatar', $user_avatar);
		$edituser->setVar('user_icq', $user_icq);
		$edituser->setVar('user_from', $user_from);
		if ($icmsConfigUser['allow_htsig'] == 0) {
			$signature = strip_tags(icms_core_DataFilter::codeDecode($user_sig, 1));
			$edituser->setVar('user_sig', icms_core_DataFilter::icms_substr($signature, 0, (int) $icmsConfigUser['sig_max_length']));
		} else {
			$signature = icms_core_DataFilter::checkVar($user_sig, 'html', 'input');
			$edituser->setVar('user_sig', icms_core_DataFilter::icms_substr($signature, 0, (int) $icmsConfigUser['sig_max_length']));
		}
		$user_viewemail = (isset($user_viewemail) && $user_viewemail == 1) ? 1 : 0;
		$edituser->setVar('user_viewemail', $user_viewemail);
		$edituser->setVar('user_aim', $user_aim);
		$edituser->setVar('user_yim', $user_yim);
		$edituser->setVar('user_msnm', $user_msnm);
		$attachsig = (isset($attachsig) && $attachsig == 1) ? 1 : 0;
		$edituser->setVar('attachsig', $attachsig);
		$edituser->setVar('timezone_offset', $timezone_offset);
		//$edituser->setVar('theme', $theme);
		$edituser->setVar('uorder', $uorder);
		$edituser->setVar('umode', $umode);
		// RMV-NOTIFY
		$edituser->setVar('notify_method', $notify_method);
		$edituser->setVar('notify_mode', $notify_mode);
		$edituser->setVar('bio', $bio);
		$edituser->setVar('rank', $rank);
		$edituser->setVar('user_occ', $user_occ);
		$edituser->setVar('user_intrest', $user_intrest);
		$edituser->setVar('user_mailok', $user_mailok);
		$edituser->setVar('language', $language);
		if ($pass2 != '') {
			if ($pass != $pass2) {
				icms_cp_header();
				echo "<strong>" . _AM_STNPDNM . "</strong>";
				icms_cp_footer();
				exit();
			}

			$icmspass = new icms_core_Password();
			$edituser->setVar('salt', $salt);
			$edituser->setVar('enc_type', $enc_type);
			$edituser->setVar('pass_expired', $pass_expired);
			$pass = $icmspass->encryptPass($pass, $salt, $enc_type);
			$edituser->setVar('pass', $pass);
		}
		if (!$member_handler->insertUser($edituser)) {
			icms_cp_header();
			echo $edituser->getHtmlErrors();
			icms_cp_footer();
		} else {
			if ($groups != array()) {
				$oldgroups = $edituser->getGroups();
				//If the edited user is the current user and the current user WAS in the webmaster's group and is NOT in the new groups array
				if ($edituser->getVar('uid') == icms::$user->getVar('uid') && (in_array(XOOPS_GROUP_ADMIN, $oldgroups)) && !(in_array(XOOPS_GROUP_ADMIN, $groups))) {
					//Add the webmaster's group to the groups array to prevent accidentally removing oneself from the webmaster's group
					$groups[] = XOOPS_GROUP_ADMIN;
				}
				$member_handler = icms::handler('icms_member');
				foreach ($oldgroups as $groupid) {
					$member_handler->removeUsersFromGroup($groupid, array($edituser->getVar('uid')));
				}
				foreach (
					$groups as $groupid) {$member_handler->addUserToGroup($groupid, $edituser->getVar('uid'));
				}
			}
			redirect_header('admin.php?fct=users', 1, _AM_DBUPDATED);
		}
	}
	exit();
}

/**
 * Update count of posts in comments and bb_posts (old forums)
 *
 * @param int $id	Unique ID of the member to synchronize
 * @param str $type	'user' or 'all users'
 */
function synchronize($id, $type) {
	switch($type) {
		case 'user':
			// Array of tables from which to count 'posts'
			$tables = array();
			// Count comments (approved only: com_status == XOOPS_COMMENT_ACTIVE)
			include_once ICMS_INCLUDE_PATH . '/comment_constants.php';
			$tables[] = array ('table_name' => 'xoopscomments', 'uid_column' => 'com_uid', 'criteria' => new icms_db_criteria_Item('com_status', XOOPS_COMMENT_ACTIVE));
			// Count forum posts
			$tables[] = array ('table_name' => 'bb_posts', 'uid_column' => 'uid');
			$total_posts = 0;
			foreach ($tables as $table) {
				$criteria = new icms_db_criteria_Compo();
				$criteria->add (new icms_db_criteria_Item($table['uid_column'], $id));
				if (!empty($table['criteria'])) {$criteria->add ($table['criteria']);}
				$sql = "SELECT COUNT(*) AS total FROM " . icms::$xoopsDB->prefix($table['table_name']) . ' ' . $criteria->renderWhere();
				if ($result = icms::$xoopsDB->query($sql)) {
					if ($row = icms::$xoopsDB->fetchArray($result)) {$total_posts = $total_posts + $row['total'];}
				}
			}
			$sql = "UPDATE " . icms::$xoopsDB->prefix("users") . " SET posts = '". (int) $total_posts . "' WHERE uid = '". (int) $id . "'";
			if (!$result = icms::$xoopsDB->query($sql)) {exit(sprintf(_AM_CNUUSER %s , $id));}
			break;

		case 'all users':
			$sql = "SELECT uid FROM " . icms::$xoopsDB->prefix('users') . "";
			if (!$result = icms::$xoopsDB->query($sql)) {exit(_AM_CNGUSERID);}
			while ($row = icms::$xoopsDB->fetchArray($result)) {
				$id = $row['uid'];
				synchronize($id, "user");
			}
			break;

		default:
			break;
	}
	redirect_header('admin.php?fct=users&amp;op=modifyUser&amp;uid=' . $id, 1, _AM_DBUPDATED);
	exit();
}

