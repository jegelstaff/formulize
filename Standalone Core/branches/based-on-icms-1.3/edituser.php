<?php
/**
 * Generates form and validation for editing users
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package		Member
 * @subpackage	Users
 * @version		SVN: $Id: edituser.php 21846 2011-06-23 16:37:07Z phoenyx $
 */

$xoopsOption['pagetype'] = 'user';
include 'mainfile.php';

if (icms_get_module_status('profile') && file_exists(ICMS_MODULES_PATH . '/profile/edituser.php')) {
	header('Location: ' . ICMS_MODULES_URL . '/profile/edituser.php');
	exit();
}

// If not a user, redirect
if (!is_object(icms::$user)) {
	redirect_header('index.php', 3, _US_NOEDITRIGHT);
}

$allowedHTML = array('htmlcode');
if (!empty($_POST)) { 
	foreach ($_POST as $k => $v) { 
		if (!in_array($k, $allowedHTML)) {
			${$k} = StopXSS($v);
		} else {
			${$k} = $v;
		}
	}
}
if (!empty($_GET)) { 
	foreach ($_GET as $k => $v) { 
		if (!in_array($k, $allowedHTML)) {
			${$k} = StopXSS($v);
		} else {
			${$k} = $v;
		}
	}
}
$op = (isset($_GET['op'])) 
	? trim(filter_input(INPUT_GET, 'op')) 
	: ((isset($_POST['op'])) 
		? trim(filter_input(INPUT_POST, 'op')) 
		: 'editprofile'
		);

switch ($op) {
	case 'saveuser':
		if (!icms::$security->check()) {
			redirect_header('index.php', 3, _US_NOEDITRIGHT . "<br />" . implode('<br />', icms::$security->getErrors()));
		}
	
		$uid = 0;
		if (!empty($_POST['uid'])) {
			$uid = (int) $_POST['uid'];
		}
	
		if (empty($uid) || icms::$user->getVar('uid') != $uid) {
			redirect_header('index.php', 3, _US_NOEDITRIGHT);
		}
	
		$errors = array();
	
		if ($icmsConfigUser['allow_chgmail'] == 1) {
			$email = '';
			if (!empty($_POST['email'])) {
				$email = icms_core_DataFilter::stripSlashesGPC(trim($_POST['email']));
			}
	
			if ($email == '' || !icms_core_DataFilter::checkVar($email, 'email', 0, 1))	{
				$errors[] = _US_INVALIDMAIL;
			}
	
			$count = 0;
			if ($email) {
				$sql = sprintf('SELECT COUNT(*) FROM %s WHERE email = %s',
						icms::$xoopsDB->prefix('users'), icms::$xoopsDB->quoteString(addslashes($email)));
				$result = icms::$xoopsDB->query($sql);
				list($count) = icms::$xoopsDB->fetchRow($result);
				if ($count > 1) {
					$errors[] .= _US_EMAILTAKEN . "<br />";
				}
			}
		}
	
		if ($icmsConfigUser['allow_chguname'] == 1) {
			$uname = '';
			if (!empty($_POST['uname'])) {
				$uname = icms_core_DataFilter::stripSlashesGPC(trim($_POST['uname']));
			}
	
			if ($uname == '') {
				$errors[] = _US_INVALIDNICKNAME;
			}
			if (strlen($uname) > $icmsConfigUser['maxuname']) {
				$errors[] .= sprintf(_US_NICKNAMETOOLONG, $icmsConfigUser['maxuname']) . "<br />";
			}
	
			if (strlen($uname) < $icmsConfigUser['minuname']) {
				$errors[] .= sprintf(_US_NICKNAMETOOSHORT, $icmsConfigUser['minuname']) . "<br />";
			}
	
			foreach ($icmsConfigUser['bad_unames'] as $bu) {
				if (!empty($bu) && preg_match("/" . $bu . "/i", $uname)) {
					$errors[] .= _US_NAMERESERVED . "<br />";
					break;
				}
			}
	
			$count = 0;
			if ($uname) {
				$sql = sprintf('SELECT COUNT(*) FROM %s WHERE uname = %s',
						icms::$xoopsDB->prefix('users'), icms::$xoopsDB->quoteString(addslashes($uname)));
				$result = icms::$xoopsDB->query($sql);
				list($count) = icms::$xoopsDB->fetchRow($result);
				if ($count > 1) {
					$errors[] .= _US_NICKNAMETAKEN . "<br />";
				}
			}
		}
	
		$username = xoops_getLinkedUnameFromId($uid);
		$password = $oldpass = '';
		if (!empty($_POST['password'])) {
			$password = icms_core_DataFilter::stripSlashesGPC(trim($_POST['password']));
			$oldpass = !empty($_POST['old_password']) 
				? icms_core_DataFilter::stripSlashesGPC(trim($_POST['old_password'])) 
				: '';
		}
	
		if ($password !== '' && $_POST['change_pass'] == 1) {
			$member_handler = icms::handler('icms_member');
			if (!$member_handler->loginUser(addslashes($uname), addslashes($oldpass))) {
				$errors[] = _US_BADPWD;
			}
	
			if (strlen($password) < $icmsConfigUser['minpass']) {
				$errors[] = sprintf(_US_PWDTOOSHORT, $icmsConfigUser['minpass']);
			}
	
			$vpass = '';
			if (!empty($_POST['vpass'])) {
				$vpass = icms_core_DataFilter::stripSlashesGPC(trim($_POST['vpass']));
			}
	
			if ($password != $vpass) {
				$errors[] = _US_PASSNOTSAME;
			}
	
			if ($password == $username 
				|| $password == icms_core_DataFilter::utf8_strrev($username, TRUE) 
				|| strripos($password, $username) === TRUE
				) {
				$errors[] = _US_BADPWD;
			}
		}
	
		if (count($errors) > 0) {
			/** Include the header that starts page rendering */
			include ICMS_ROOT_PATH . '/header.php';
			echo '<div>';
			foreach ($errors as $er) {
				echo '<span style="color: #ff0000; font-weight: bold;">' . $er . '</span><br />';
			}
			echo '</div><br />';
			$op = 'editprofile';
		} else {
			$member_handler = icms::handler('icms_member');
			$edituser =& $member_handler->getUser($uid);
			$edituser->setVar('name', $_POST['name']);
			if ($icmsConfigUser['allow_chgmail'] == 1) {
				$edituser->setVar('email', $email, TRUE);
			}
	
			if ($icmsConfigUser['allow_chguname'] == 1) {
				$edituser->setVar('uname', $uname, TRUE);
			}
	
			$edituser->setVar('url', formatURL($_POST['url']));
			$edituser->setVar('user_icq', $_POST['user_icq']);
			$edituser->setVar('user_from', $_POST['user_from']);
			$edituser->setVar('openid', isset($_POST['openid']) ? trim($_POST['openid']) : '');
			if ($icmsConfigUser['allwshow_sig'] == 1) {
				if ($icmsConfigUser['allow_htsig'] == 0) {
					$signature = strip_tags(icms_core_DataFilter::checkVar($_POST['user_sig'], 'text', 'input'));
					$edituser->setVar('user_sig', icms_core_DataFilter::icms_substr($signature, 0, (int) $icmsConfigUser['sig_max_length']));
				} else {
					$signature = icms_core_DataFilter::checkVar($_POST['user_sig'], 'html', 'input');
					$edituser->setVar('user_sig', icms_core_DataFilter::icms_substr($signature, 0, (int) $icmsConfigUser['sig_max_length']));
				}
			}
	
			$user_viewemail = (!empty($_POST['user_viewemail'])) ? 1 : 0;
			$edituser->setVar('user_viewemail', $user_viewemail);
			$user_viewoid = (!empty($_POST['user_viewoid'])) ? 1 : 0;
			$edituser->setVar('user_viewoid', $user_viewoid);
			$edituser->setVar('user_aim', $_POST['user_aim']);
			$edituser->setVar('user_yim', $_POST['user_yim']);
			$edituser->setVar('user_msnm', $_POST['user_msnm']);
			if ($password != '') {
				$icmspass = new icms_core_Password();
				$salt = $icmspass->createSalt();
				$edituser->setVar('salt', $salt, TRUE);
				$edituser->setVar('enc_type', $icmsConfigUser['enc_type'], TRUE);
				$pass = $icmspass->encryptPass($password, $salt, $icmsConfigUser['enc_type']);
				$edituser->setVar('pass', $pass, TRUE);
			}
	
			$attachsig = !empty($_POST['attachsig']) ? 1 : 0;
			$edituser->setVar('attachsig', $attachsig);
			$edituser->setVar('timezone_offset', $_POST['timezone_offset']);
			$edituser->setVar('uorder', $_POST['uorder']);
			$edituser->setVar('umode', $_POST['umode']);
			$edituser->setVar('notify_method', $_POST['notify_method']);
			$edituser->setVar('notify_mode', $_POST['notify_mode']);
			$edituser->setVar('bio', icms_core_DataFilter::icms_substr($_POST['bio'], 0, 255));
			$edituser->setVar('user_occ', $_POST['user_occ']);
			$edituser->setVar('user_intrest', $_POST['user_intrest']);
			$edituser->setVar('user_mailok', $_POST['user_mailok']);
			if (isset($_POST['theme_selected'])) {
				$edituser->setVar('theme', $_POST['theme_selected']);
				$_SESSION['xoopsUserTheme'] = $_POST['theme_selected'];
				$icmsConfig['theme_set'] = $_SESSION['xoopsUserTheme'];
			} else {
				$edituser->setVar('theme', $icmsConfig['theme_set']);
			}
	
			if (!empty($_POST['usecookie'])) {
				setcookie($icmsConfig['usercookie'], icms::$user->getVar('login_name'), time()+ 31536000);
			} else {
				setcookie($icmsConfig['usercookie']);
			}
	
			if (!$member_handler->insertUser($edituser)) {
				/** Include the header that starts page rendering */
				include ICMS_ROOT_PATH . '/header.php';
				echo $edituser->getHtmlErrors();
				/** Include the footer file to complete page rendering */
				include ICMS_ROOT_PATH . '/footer.php';
			} else {
				redirect_header('userinfo.php?uid=' . $uid, 1, _US_PROFUPDATED);
			}
			exit();
	}
	break;

	default:
	case 'editprofile':
		/** Include the header that starts page rendering */
		include_once ICMS_ROOT_PATH . '/header.php';
		include_once ICMS_ROOT_PATH . '/include/comment_constants.php';
		if ($icmsConfigUser['pass_level'] > 20) {
			icms_PasswordMeter();
		}
	
		echo '<a href="userinfo.php?uid='. (int) icms::$user->getVar('uid') . '">' . _US_PROFILE . '</a>&nbsp;
			<span style="font-weight:bold;">&raquo;&raquo;</span>&nbsp;' . _US_EDITPROFILE . '<br /><br />';
		$form = new icms_form_Theme(_US_EDITPROFILE, 'userinfo', 'edituser.php', 'post', TRUE);
		$login_name_label = new icms_form_elements_Label(_US_LOGINNAME, icms::$user->getVar('login_name'));
		$form->addElement($login_name_label);
		$email_tray = new icms_form_elements_Tray(_US_EMAIL, '<br />');
		if ($icmsConfigUser['allow_chgmail'] == 1) {
			$email_text = new icms_form_elements_Text('', 'email', 30, 60, icms::$user->getVar('email'));
		} else {
			$email_text = new icms_form_elements_Label('', icms::$user->getVar('email'));
		}
	
		$email_tray->addElement($email_text);
		$email_cbox_value = icms::$user->getVar('user_viewemail') ? 1 : 0;
		$email_cbox = new icms_form_elements_Checkbox('', 'user_viewemail', $email_cbox_value);
		$email_cbox->addOption(1, _US_ALLOWVIEWEMAIL);
		$email_tray->addElement($email_cbox);
		$form->addElement($email_tray);
	
		if ($icmsConfigAuth['auth_openid'] == 1) {
			$openid_tray = new icms_form_elements_Tray(_US_OPENID_FORM_CAPTION, '<br />');
			$openid_text = new icms_form_elements_Text('', 'openid', 30, 255, icms::$user->getVar('openid'));
			$openid_tray->setDescription(_US_OPENID_FORM_DSC);
			$openid_tray->addElement($openid_text);
			$openid_cbox_value = icms::$user->getVar('user_viewoid') ? 1 : 0;
			$openid_cbox = new icms_form_elements_Checkbox('', 'user_viewoid', $openid_cbox_value);
			$openid_cbox->addOption(1, _US_ALLOWVIEWEMAILOPENID);
			$openid_tray->addElement($openid_cbox);
			$form->addElement($openid_tray);
		}
	
		if ($icmsConfigUser['allow_chguname'] == 1) {
			$uname_label = new icms_form_elements_Text(_US_NICKNAME, 'uname', 30, 60, icms::$user->getVar('uname', 'E'));
		} else {
			$uname_label = new icms_form_elements_Label(_US_NICKNAME, icms::$user->getVar('uname'));
		}
	
		$form->addElement($uname_label);
		$name_text = new icms_form_elements_Text(_US_REALNAME, 'name', 30, 60, icms::$user->getVar('name', 'E'));
		$form->addElement($name_text);
		$url_text = new icms_form_elements_Text(_US_WEBSITE, 'url', 30, 100, icms::$user->getVar('url', 'E'));
		$form->addElement($url_text);
	
		$timezone_select = new icms_form_elements_select_Timezone(_US_TIMEZONE, 'timezone_offset', icms::$user->getVar('timezone_offset'));
		$icq_text = new icms_form_elements_Text(_US_ICQ, 'user_icq', 15, 15, icms::$user->getVar('user_icq', 'E'));
		$aim_text = new icms_form_elements_Text(_US_AIM, 'user_aim', 18, 18, icms::$user->getVar('user_aim', 'E'));
		$yim_text = new icms_form_elements_Text(_US_YIM, 'user_yim', 25, 25, icms::$user->getVar('user_yim', 'E'));
		$msnm_text = new icms_form_elements_Text(_US_MSNM, 'user_msnm', 30, 100, icms::$user->getVar('user_msnm', 'E'));
		$location_text = new icms_form_elements_Text(_US_LOCATION, 'user_from', 30, 100, icms::$user->getVar('user_from', 'E'));
		$occupation_text = new icms_form_elements_Text(_US_OCCUPATION, 'user_occ', 30, 100, icms::$user->getVar('user_occ', 'E'));
		$interest_text = new icms_form_elements_Text(_US_INTEREST, 'user_intrest', 30, 150, icms::$user->getVar('user_intrest', 'E'));
		/** include the file to display the xoopscodes and smilies */
		include_once 'include/xoopscodes.php';
		if ($icmsConfigUser['allwshow_sig'] == 1) {
			if ($icmsConfigUser['allow_htsig'] == 0) {
				$sig_tray = new icms_form_elements_Tray(_US_SIGNATURE, '<br />');
				$sig_tarea = new icms_form_elements_Textarea('', 'user_sig', icms::$user->getVar('user_sig', 'E'));
				$sig_tray->addElement($sig_tarea);
				$sig_cbox_value = icms::$user->getVar('attachsig') ? 1 : 0;
				$sig_cbox = new icms_form_elements_Checkbox('', 'attachsig', $sig_cbox_value);
				$sig_cbox->addOption(1, _US_SHOWSIG);
				$sig_tray->addElement($sig_cbox);
			} else {
				$sig_tray = new icms_form_elements_Tray(_US_SIGNATURE, '<br />');
				$sig_tarea = new icms_form_elements_Dhtmltextarea('', 'user_sig', icms::$user->getVar('user_sig', 'E'));
				$sig_tray->addElement($sig_tarea);
				$sig_cbox_value = icms::$user->getVar('attachsig') ? 1 : 0;
				$sig_cbox = new icms_form_elements_Checkbox('', 'attachsig', $sig_cbox_value);
				$sig_cbox->addOption(1, _US_SHOWSIG);
				$sig_tray->addElement($sig_cbox);
			}
		}
	
		$umode_select = new icms_form_elements_Select(_US_CDISPLAYMODE, 'umode', icms::$user->getVar('umode'));
		$umode_select->addOptionArray(array('nest'=>_NESTED, 'flat'=>_FLAT, 'thread'=>_THREADED));
		$uorder_select = new icms_form_elements_Select(_US_CSORTORDER, 'uorder', icms::$user->getVar('uorder'));
		$uorder_select->addOptionArray(array(XOOPS_COMMENT_OLD1ST => _OLDESTFIRST, XOOPS_COMMENT_NEW1ST => _NEWESTFIRST));
		$selected_theme = new icms_form_elements_Select(_US_SELECT_THEME, 'theme_selected' , icms::$user->getVar('theme'));
		foreach ($icmsConfig['theme_set_allowed'] as $theme) {
			$selected_theme->addOption($theme, $theme);
		}
	
		$selected_language = new icms_form_elements_Select(_US_SELECT_LANG, 'language_selected', icms::$user->getVar('language'));
		foreach (icms_core_Filesystem::getDirList(ICMS_ROOT_PATH . "/language/") as $language) {
			$selected_language->addOption($language, $language);
		}
	
		// TODO: add this to admin user-edit functions...
		icms_loadLanguageFile('core', 'notification');
		include_once ICMS_ROOT_PATH . '/include/notification_constants.php';
		$notify_method_select = new icms_form_elements_Select(_NOT_NOTIFYMETHOD, 'notify_method', icms::$user->getVar('notify_method'));
		$notify_method_select->addOptionArray(array(XOOPS_NOTIFICATION_METHOD_DISABLE=>_NOT_METHOD_DISABLE, XOOPS_NOTIFICATION_METHOD_PM=>_NOT_METHOD_PM, XOOPS_NOTIFICATION_METHOD_EMAIL=>_NOT_METHOD_EMAIL));
		$notify_mode_select = new icms_form_elements_Select(_NOT_NOTIFYMODE, 'notify_mode', icms::$user->getVar('notify_mode'));
		$notify_mode_select->addOptionArray(array(XOOPS_NOTIFICATION_MODE_SENDALWAYS=>_NOT_MODE_SENDALWAYS, XOOPS_NOTIFICATION_MODE_SENDONCETHENDELETE=>_NOT_MODE_SENDONCE, XOOPS_NOTIFICATION_MODE_SENDONCETHENWAIT=>_NOT_MODE_SENDONCEPERLOGIN));
		$bio_tarea = new icms_form_elements_Textarea(_US_EXTRAINFO, 'bio', icms::$user->getVar('bio', 'E'));
		$cookie_radio_value = empty($_COOKIE[$icmsConfig['usercookie']]) ? 0 : 1;
		$cookie_radio = new icms_form_elements_Radioyn(_US_USECOOKIE, 'usecookie', $cookie_radio_value, _YES, _NO);
		$pwd_text = new icms_form_elements_Password('', 'password', 10, 255, "", FALSE, ($icmsConfigUser['pass_level']?'password_adv':''));
		$pwd_text2 = new icms_form_elements_Password('', 'vpass', 10, 255);
		$pwd_tray = new icms_form_elements_Tray(_US_PASSWORD . '<br />' . _US_TYPEPASSTWICE);
		$pwd_tray->addElement($pwd_text);
		$pwd_tray->addElement($pwd_text2);
		$pwd_text_old = new icms_form_elements_Password(_US_OLD_PASSWORD, 'old_password', 10, 255);
		$mailok_radio = new icms_form_elements_Radioyn(_US_MAILOK, 'user_mailok', (int) icms::$user->getVar('user_mailok'));
		$salt_hidden = new icms_form_elements_Hidden('salt', icms_core_DataFilter::checkVar(icms::$user->getVar('salt'), 'str'));
		$uid_hidden = new icms_form_elements_Hidden('uid', (int) icms::$user->getVar('uid'));
		$op_hidden = new icms_form_elements_Hidden('op', 'saveuser');
		$submit_button = new icms_form_elements_Button('', 'submit', _US_SAVECHANGES, 'submit');
	
		$form->addElement($timezone_select);
		$form->addElement($icq_text);
		$form->addElement($aim_text);
		$form->addElement($yim_text);
		$form->addElement($msnm_text);
		$form->addElement($location_text);
		$form->addElement($occupation_text);
		$form->addElement($interest_text);
		$form->addElement($sig_tray);
		if (count($icmsConfig['theme_set_allowed']) > 1) {
			$form->addElement($selected_theme);
		}
	
		if ($icmsConfigMultilang['ml_enable']) {
			$form->addElement($selected_language);
		}
	
		$form->addElement($umode_select);
		$form->addElement($uorder_select);
		$form->addElement($notify_method_select);
		$form->addElement($notify_mode_select);
		$form->addElement($bio_tarea);
		$form->addElement($pwd_change_radio);
		$form->addElement($pwd_text_old);
		$form->addElement($pwd_tray);
		$form->addElement($pwd_tray_old);
		$form->addElement($cookie_radio);
		$form->addElement($mailok_radio);
		$form->addElement($salt_hidden);
		$form->addElement($uid_hidden);
		$form->addElement($op_hidden);
		$form->addElement($token_hidden);
		$form->addElement($submit_button);
		if ($icmsConfigUser['allow_chgmail'] == 1) {
			$form->setRequired($email_text);
		}
		$form->display();
		/** Include the footer file to complete page rendering */
		include ICMS_ROOT_PATH . '/footer.php';
		break;

	case 'avatarform':
		/** Include the header that starts page rendering */
		include ICMS_ROOT_PATH . '/header.php';
		echo '<a href="userinfo.php?uid='. (int) icms::$user->getVar('uid') . '">' . _US_PROFILE . '</a>&nbsp;
			<span style="font-weight:bold;">&raquo;&raquo;</span>&nbsp;' . _US_UPLOADMYAVATAR . '<br /><br />';
		$oldavatar = icms::$user->getVar('user_avatar');
		if (!empty($oldavatar) && $oldavatar != 'blank.gif') {
			echo '<div style="text-align:center;"><h4 style="color:#ff0000; font-weight:bold;">' . _US_OLDDELETED . '</h4>';
			echo '<img src="' . ICMS_UPLOAD_URL . '/' . $oldavatar . '" alt="" /></div>';
		}
	
		if ($icmsConfigUser['avatar_allow_upload'] == 1 && icms::$user->getVar('posts') >= $icmsConfigUser['avatar_minposts']) {
			$form = new icms_form_Theme(_US_UPLOADMYAVATAR, 'uploadavatar', 'edituser.php', 'post', TRUE);
			$form->setExtra('enctype="multipart/form-data"');
			$form->addElement(new icms_form_elements_Label(_US_MAXPIXEL, icms_conv_nr2local($icmsConfigUser['avatar_width']) . ' x ' . icms_conv_nr2local($icmsConfigUser['avatar_height'])));
			$form->addElement(new icms_form_elements_Label(_US_MAXIMGSZ, icms_conv_nr2local($icmsConfigUser['avatar_maxsize'])));
			$form->addElement(new icms_form_elements_File(_US_SELFILE, 'avatarfile', icms_conv_nr2local($icmsConfigUser['avatar_maxsize'])), TRUE);
			$form->addElement(new icms_form_elements_Hidden('op', 'avatarupload'));
			$form->addElement(new icms_form_elements_Hidden('uid', (int) icms::$user->getVar('uid')));
			$form->addElement(new icms_form_elements_Button('', 'submit', _SUBMIT, 'submit'));
			$form->display();
		}
		$avatar_handler = icms::handler('icms_data_avatar');
		$form2 = new icms_form_Theme(_US_CHOOSEAVT, 'uploadavatar', 'edituser.php', 'post', TRUE);
		$avatar_select = new icms_form_elements_Select('', 'user_avatar', icms::$user->getVar('user_avatar'));
		$avatar_select->addOptionArray($avatar_handler->getList('S'));
		$avatar_select->setExtra("onchange='showImgSelected(\"avatar\", \"user_avatar\", \"uploads\", \"\", \"" . ICMS_URL . "\")'");
		$avatar_tray = new icms_form_elements_Tray(_US_AVATAR, '&nbsp;');
		$avatar_tray->addElement($avatar_select);
		$avatar_tray->addElement(new icms_form_elements_Label('', "<img src='" . ICMS_UPLOAD_URL . "/" . icms::$user->getVar("user_avatar", "E") . "' name='avatar' id='avatar' alt='' /> <a href=\"javascript:openWithSelfMain('" . ICMS_URL . "/misc.php?action=showpopups&amp;type=avatars','avatars',600,400);\">" . _LIST . "</a>"));
		if ($icmsConfigUser['avatar_allow_upload'] == 1 && icms::$user->getVar('posts') < $icmsConfigUser['avatar_minposts']) {
			$form2->addElement(new icms_form_elements_Label(sprintf(_US_POSTSNOTENOUGH, icms_conv_nr2local($icmsConfigUser['avatar_minposts'])), _US_UNCHOOSEAVT));
		}
			$form2->addElement($avatar_tray);
			$form2->addElement(new icms_form_elements_Hidden('uid', (int) icms::$user->getVar('uid')));
			$form2->addElement(new icms_form_elements_Hidden('op', 'avatarchoose'));
			$form2->addElement(new icms_form_elements_Button('', 'submit2', _SUBMIT, 'submit'));
			$form2->display();
			/** Include the footer file to complete page rendering */
			include ICMS_ROOT_PATH . '/footer.php';
	break;

	case 'avatarupload':
		if (!icms::$security->check()) {
			redirect_header('index.php', 3, _US_NOEDITRIGHT . "<br />" . implode('<br />', icms::$security->getErrors()));
		}
		$xoops_upload_file = array();
		$uid = 0;
		if (!empty($_POST['xoops_upload_file']) && is_array($_POST['xoops_upload_file'])) {
			$xoops_upload_file = $_POST['xoops_upload_file'];
		}
	
		if (!empty($_POST['uid'])) {
			$uid = (int) $_POST['uid'];
		}
	
		if (empty($uid) || icms::$user->getVar('uid') != $uid) {
			redirect_header('index.php', 3, _US_NOEDITRIGHT);
		}
		if ($icmsConfigUser['avatar_allow_upload'] == 1 && icms::$user->getVar('posts') >= $icmsConfigUser['avatar_minposts']) {
			$uploader = new icms_file_MediaUploadHandler(ICMS_UPLOAD_PATH, array('image/gif', 'image/jpeg', 'image/pjpeg', 'image/x-png', 'image/png'), $icmsConfigUser['avatar_maxsize'], $icmsConfigUser['avatar_width'], $icmsConfigUser['avatar_height']);
			if ($uploader->fetchMedia($_POST['xoops_upload_file'][0])) {
				$uploader->setPrefix('cavt');
				if ($uploader->upload()) {
					$avt_handler = icms::handler('icms_data_avatar');
					$avatar =& $avt_handler->create();
					$avatar->setVar('avatar_file', $uploader->getSavedFileName());
					$avatar->setVar('avatar_name', icms::$user->getVar('uname'));
					$avatar->setVar('avatar_mimetype', $uploader->getMediaType());
					$avatar->setVar('avatar_display', 1);
					$avatar->setVar('avatar_type', 'C');
					if (!$avt_handler->insert($avatar)) {
						@unlink($uploader->getSavedDestination());
					} else {
						$oldavatar = icms::$user->getVar('user_avatar');
						if (!empty($oldavatar) && preg_match("/^cavt/", strtolower($oldavatar))) {
							$avatars =& $avt_handler->getObjects(new icms_db_criteria_Item('avatar_file', $oldavatar));
							if (!empty($avatars) && count($avatars) == 1 && is_object($avatars[0])) {
								$avt_handler->delete($avatars[0]);
								$oldavatar_path = str_replace("\\", "/", realpath(ICMS_UPLOAD_PATH . '/' . $oldavatar));
								if (0 === strpos($oldavatar_path, ICMS_UPLOAD_PATH) && is_file($oldavatar_path)) {
									unlink($oldavatar_path);
								}
							}
						}
						$sql = sprintf("UPDATE %s SET user_avatar = %s WHERE uid = '%u'",
							 icms::$xoopsDB->prefix('users'), 
							 icms::$xoopsDB->quoteString($uploader->getSavedFileName()), 
							 (int) icms::$user->getVar('uid')
						);
						icms::$xoopsDB->query($sql);
						$avt_handler->addUser($avatar->getVar('avatar_id'), (int) icms::$user->getVar('uid'));
						redirect_header('userinfo.php?t=' . time() . '&amp;uid='. (int) icms::$user->getVar('uid'), 0, _US_PROFUPDATED);
					}
				}
			}
			/** Include the header that starts page rendering */
			include ICMS_ROOT_PATH . '/header.php';
			echo $uploader->getErrors();
			/** Include the footer file to complete page rendering */
			include ICMS_ROOT_PATH . '/footer.php';
		}
	break;

	case 'avatarchoose':
		if (!icms::$security->check()) {
			redirect_header('index.php', 3, _US_NOEDITRIGHT . "<br />" . implode('<br />', icms::$security->getErrors()));
		}
	
		$uid = 0;
		if (!empty($_POST['uid'])) {
			$uid = (int) $_POST['uid'];
		}
	
		if (empty($uid) || icms::$user->getVar('uid') != $uid) {
			redirect_header('index.php', 3, _US_NOEDITRIGHT);
		}
	
		$user_avatar = '';
		$avt_handler = icms::handler('icms_data_avatar');
		if (!empty($_POST['user_avatar'])) {
			$user_avatar = icms_core_DataFilter::addSlashes(trim($_POST['user_avatar']));
			$criteria_avatar = new icms_db_criteria_Compo(new icms_db_criteria_Item('avatar_file', $user_avatar));
			$criteria_avatar->add(new icms_db_criteria_Item('avatar_type', "S"));
			$avatars =& $avt_handler->getObjects($criteria_avatar);
			if (!is_array($avatars) || !count($avatars)) {
				$user_avatar = 'blank.gif';
			}
			unset($avatars, $criteria_avatar);
		}
	
		$user_avatarpath = str_replace("\\", "/", realpath(ICMS_UPLOAD_PATH . '/' . $user_avatar));
		if (0 === strpos($user_avatarpath, ICMS_UPLOAD_PATH) && is_file($user_avatarpath)) {
			$oldavatar = icms::$user->getVar('user_avatar');
			icms::$user->setVar('user_avatar', $user_avatar);
			$member_handler = icms::handler('icms_member');
			if (!$member_handler->insertUser(icms::$user)) {
				/** Include the header that starts page rendering */
				include ICMS_ROOT_PATH . '/header.php';
				echo icms::$user->getHtmlErrors();
				/** Include the footer file to complete page rendering */
				include ICMS_ROOT_PATH . '/footer.php';
				exit();
			}
			if ($oldavatar && preg_match("/^cavt/", strtolower($oldavatar))) {
				$avatars =& $avt_handler->getObjects(new icms_db_criteria_Item('avatar_file', $oldavatar));
				if (!empty($avatars) && count($avatars) == 1 && is_object($avatars[0])) {
					$avt_handler->delete($avatars[0]);
					$oldavatar_path = str_replace("\\", "/", realpath(ICMS_UPLOAD_PATH . '/' . $oldavatar));
					if (0 === strpos($oldavatar_path, ICMS_UPLOAD_PATH) && is_file($oldavatar_path)) {
						unlink($oldavatar_path);
					}
				}
			}
			if ($user_avatar != 'blank.gif') {
				$avatars =& $avt_handler->getObjects(new icms_db_criteria_Item('avatar_file', $user_avatar));
				if (is_object($avatars[0])) {
					$avt_handler->addUser($avatars[0]->getVar('avatar_id'), icms::$user->getVar('uid'));
				}
			}
		}
		redirect_header('userinfo.php?uid=' . $uid, 0, _US_PROFUPDATED);
	break;
}
