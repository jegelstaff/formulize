<?php
/**
* Administration of users, form file
*
* @copyright	http://www.xoops.org/ The XOOPS Project
* @copyright	XOOPS_copyrights.txt
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license	LICENSE.txt
* @package	Administration
* @since	XOOPS
* @author	http://www.xoops.org The XOOPS Project
* @author	modified by UnderDog <underdog@impresscms.org>
* @version	$Id: userform.php 9340 2009-09-06 11:40:35Z pesianstranger $
*/

global $xoopsConfigUser;

$uid_label = new XoopsFormLabel(_AM_USERID, $uid_value);
$uname_text = new XoopsFormText(_AM_NICKNAME, "username", 25, 25, $uname_value);
$login_name_text = new XoopsFormText(_AM_LOGINNAME, "login_name", 25, 25, $login_name_value);
$name_text = new XoopsFormText(_AM_NAME, "name", 30, 60, $name_value);
$email_tray = new XoopsFormElementTray(_AM_EMAIL, "<br />");
$email_text = new XoopsFormText("", "email", 30, 60, $email_value);
$email_tray->addElement($email_text, true);
$email_cbox = new XoopsFormCheckBox("", "user_viewemail", $email_cbox_value);
$email_cbox->addOption(1, _AM_AOUTVTEAD);
$email_tray->addElement($email_cbox);
$config_handler =& xoops_gethandler('config');
$icmsauthConfig =& $config_handler->getConfigsByCat(XOOPS_CONF_AUTH);
if ($icmsauthConfig['auth_openid'] == 1) {
	$openid_tray = new XoopsFormElementTray(_AM_OPENID, "<br />");
	$openid_text = new XoopsFormText("", "openid", 30, 255, $openid_value);
	$openid_tray->addElement($openid_text);
	$openid_cbox = new XoopsFormCheckBox("", "user_viewoid", $openid_cbox_value);
	$openid_cbox->addOption(1, _AM_AOUTVTOIAD);
	$openid_tray->addElement($openid_cbox);
}
$url_text = new XoopsFormText(_AM_URL, "url", 30, 100, $url_value);
//  $avatar_select = new XoopsFormSelect("", "user_avatar", $avatar_value);
//  $avatar_array = XoopsLists::getImgListAsArray(XOOPS_ROOT_PATH."/images/avatar/");
//  $avatar_select->addOptionArray($avatar_array);
//  $a_dirlist = XoopsLists::getDirListAsArray(XOOPS_ROOT_PATH."/images/avatar/");
//  $a_dir_labels = array();
//  $a_count = 0;
//  $a_dir_link = "<a href=\"javascript:openWithSelfMain('".XOOPS_URL."/misc.php?action=showpopups&amp;type=avatars&amp;start=".$a_count."','avatars',600,400);\">XOOPS</a>";
//  $a_count = $a_count + count($avatar_array);
//  $a_dir_labels[] = new XoopsFormLabel("", $a_dir_link);
//  foreach ($a_dirlist as $a_dir) {
//	  if ( $a_dir == "users" ) {
//		  continue;
//	  }
//	  $avatars_array = XoopsLists::getImgListAsArray(XOOPS_ROOT_PATH."/images/avatar/".$a_dir."/", $a_dir."/");
//	  $avatar_select->addOptionArray($avatars_array);
//	  $a_dir_link = "<a href=\"javascript:openWithSelfMain('".XOOPS_URL."/misc.php?action=showpopups&amp;type=avatars&amp;subdir=".$a_dir."&amp;start=".$a_count."','avatars',600,400);\">".$a_dir."</a>";
//	  $a_dir_labels[] = new XoopsFormLabel("", $a_dir_link);
//	  $a_count = $a_count + count($avatars_array);
//  }
//  if (!empty($uid_value)) {
//	  $myavatar = avatarExists($uid_value);
//	  if ( $myavatar != false ) {
//		  $avatar_select->addOption($myavatar, _US_MYAVATAR);
//	  }
//  }
//  $avatar_select->setExtra("onchange='showImgSelected(\"avatar\", \"user_avatar\", \"images/avatar\", \"\", \"".XOOPS_URL."\")'");
//  $avatar_label = new XoopsFormLabel("", "<img src='".XOOPS_URL."/images/avatar/".$avatar_value."' name='avatar' id='avatar' alt='' />");
//  $avatar_tray = new XoopsFormElementTray(_AM_AVATAR, "&nbsp;");
//  $avatar_tray->addElement($avatar_select);
//  $avatar_tray->addElement($avatar_label);
//  foreach ($a_dir_labels as $a_dir_label) {
//	  $avatar_tray->addElement($a_dir_label);
//  }
//  $theme_select = new XoopsFormSelectTheme(_AM_THEME, "theme", $theme_value);
$timezone_select = new XoopsFormSelectTimezone(_US_TIMEZONE, "timezone_offset", $timezone_value);
$icq_text = new XoopsFormText(_AM_ICQ, "user_icq", 15, 15, $icq_value);
$aim_text = new XoopsFormText(_AM_AIM, "user_aim", 18, 18, $aim_value);
$yim_text = new XoopsFormText(_AM_YIM, "user_yim", 25, 25, $yim_value);
$msnm_text = new XoopsFormText(_AM_MSNM, "user_msnm", 30, 100, $msnm_value);
$location_text = new XoopsFormText(_AM_LOCATION, "user_from", 30, 100, $location_value);
$occupation_text = new XoopsFormText(_AM_OCCUPATION, "user_occ", 30, 100, $occ_value);
$interest_text = new XoopsFormText(_AM_INTEREST, "user_intrest", 30, 150, $interest_value);
$sig_tray = new XoopsFormElementTray(_AM_SIGNATURE, "<br />");
if($xoopsConfigUser['allow_htsig'] == 0) {$sig_tarea = new XoopsFormTextArea("", "user_sig", $sig_value);}
else {$sig_tarea = new XoopsFormDhtmlTextArea("", "user_sig", $sig_value);}
$sig_tray->addElement($sig_tarea);
$sig_cbox = new XoopsFormCheckBox("", "attachsig", $sig_cbox_value);
$sig_cbox->addOption(1, _US_SHOWSIG);
$sig_tray->addElement($sig_cbox);
$umode_select = new XoopsFormSelect(_US_CDISPLAYMODE, "umode", $umode_value);
$umode_select->addOptionArray(array("nest"=>_NESTED, "flat"=>_FLAT, "thread"=>_THREADED));
$uorder_select = new XoopsFormSelect(_US_CSORTORDER, "uorder", $uorder_value);
$uorder_select->addOptionArray(array("0"=>_OLDESTFIRST, "1"=>_NEWESTFIRST));

// RMV-NOTIFY
icms_loadLanguageFile('core', 'notification');
include_once XOOPS_ROOT_PATH . '/include/notification_constants.php';
$notify_method_select = new XoopsFormSelect(_NOT_NOTIFYMETHOD, 'notify_method', $notify_method_value);
$notify_method_select->addOptionArray(array(XOOPS_NOTIFICATION_METHOD_DISABLE=>_NOT_METHOD_DISABLE, XOOPS_NOTIFICATION_METHOD_PM=>_NOT_METHOD_PM, XOOPS_NOTIFICATION_METHOD_EMAIL=>_NOT_METHOD_EMAIL));
$notify_mode_select = new XoopsFormSelect(_NOT_NOTIFYMODE, 'notify_mode', $notify_mode_value);
$notify_mode_select->addOptionArray(array(XOOPS_NOTIFICATION_MODE_SENDALWAYS=>_NOT_MODE_SENDALWAYS, XOOPS_NOTIFICATION_MODE_SENDONCETHENDELETE=>_NOT_MODE_SENDONCE, XOOPS_NOTIFICATION_MODE_SENDONCETHENWAIT=>_NOT_MODE_SENDONCEPERLOGIN));
$bio_tarea = new XoopsFormTextArea(_US_EXTRAINFO, "bio", $bio_value);
$rank_select = new XoopsFormSelect(_AM_RANK, "rank", $rank_value);
$ranklist = XoopsLists::getUserRankList();
if ( count($ranklist) > 0 ) {
	$rank_select->addOption(0, "--------------");
	$rank_select->addOptionArray($ranklist);
} else {
	$rank_select->addOption(0, _AM_NSRID);
}
global $icmsConfigUser;
$pwd_text = new XoopsFormPassword(_AM_PASSWORD, "password", 10, 255, '', false, ($icmsConfigUser['pass_level']?'password_adv':''));
$pwd_text2 = new XoopsFormPassword(_AM_RETYPEPD, "pass2", 10, 255);
$mailok_radio = new XoopsFormRadioYN(_US_MAILOK, 'user_mailok', intval($mailok_value));

$language = new XoopsFormSelectLang(_US_SELECT_LANG,'language', $language_value);

// Groups administration addition XOOPS 2.0.9: Mith
global $icmsUser;
$gperm_handler =& xoops_gethandler('groupperm');
//If user has admin rights on groups
if ($gperm_handler->checkRight("system_admin", XOOPS_SYSTEM_GROUP, $icmsUser->getGroups(), 1)) {
	//add group selection
	if ( in_array(XOOPS_GROUP_ADMIN, $icmsUser->getGroups())){
		$group_select = array(new XoopsFormSelectGroup(_US_GROUPS, 'groups', false, $groups, 5, true));
	} else {
		$group_manager_value = array_intersect_key(xoops_gethandler('member')->getGroupList(), array_flip($gperm_handler->getItemIds('group_manager', $icmsUser->getGroups()))) ;
		$group_array = new XoopsFormSelect(_US_GROUPS, 'groups',$groups, 5, true);
		$group_array->addOptionArray($group_manager_value);
		$group_select = array ($group_array);
		//$group_hidden = array_diff(xoops_gethandler('member')->getGroupList(),$group_manager_value);
		$group_hidden = array_diff($groups,array_flip($group_manager_value));
		foreach ($group_hidden as $key => $group) {
			$group_hidden_select[] = new XoopsFormHidden('groups_hidden[' . $key . ']', $group);
		}
	}
}
else {
	//add each user groups
	foreach ($groups as $key => $group) {
		$group_select[] = new XoopsFormHidden('groups[' . $key . ']', $group);
	}
}
include_once ICMS_ROOT_PATH.'/class/icms_Password.php';
$icmspass = new icms_Password();

$salt_hidden = new XoopsFormHidden('salt', $icmspass->icms_createSalt());

$enc_type_hidden = new XoopsFormHidden('enc_type', $xoopsConfigUser['enc_type']);
$pass_expired_hidden = new XoopsFormHidden('pass_expired', 0);
$fct_hidden = new XoopsFormHidden("fct", "users");
$op_hidden = new XoopsFormHidden("op", $op_value);
$submit_button = new XoopsFormButton("", "submit", _SUBMIT, "submit");

$form = new XoopsThemeForm($form_title, "userinfo", "admin.php", "post", true);
$form->addElement($uname_text, true);
$form->addElement($login_name_text, true);
$form->addElement($name_text);
$form->addElement($email_tray, true);
$form->addElement($openid_tray, true);
$form->addElement($url_text);
//  $form->addElement($avatar_tray);
//  $form->addElement($theme_select);
$form->addElement($timezone_select);
$form->addElement($icq_text);
$form->addElement($aim_text);
$form->addElement($yim_text);
$form->addElement($msnm_text);
$form->addElement($location_text);
$form->addElement($occupation_text);
$form->addElement($interest_text);
$form->addElement($sig_tray);
$form->addElement($umode_select);
$form->addElement($uorder_select);
// RMV-NOTIFY
$form->addElement($notify_method_select);
$form->addElement($notify_mode_select);
$form->addElement($bio_tarea);
$form->addElement($rank_select);

// adding a new user requires password fields
if (!$form_isedit) {
	$form->addElement($pwd_text, true);
	$form->addElement($pwd_text2, true);
	$form->addElement($salt_hidden, true);
	$form->addElement($enc_type_hidden, true);
	$form->addElement($pass_expired_hidden, true);
} else {
	$form->addElement($pwd_text);
	$form->addElement($pwd_text2);
	$form->addElement($salt_hidden);
	$form->addElement($enc_type_hidden);
	$form->addElement($pass_expired_hidden);
}

$form->addElement($mailok_radio);
$form->addElement($language);

foreach ($group_select as $group) {
	$form->addElement($group);
	unset($group);
}

if (@is_array($group_hidden_select)){
	foreach ($group_hidden_select as $group) {
		$form->addElement($group);
		unset($group);
	}
}

$form->addElement($fct_hidden);
$form->addElement($op_hidden);
$form->addElement($submit_button);

if ( !empty($uid_value) ) {
	$uid_hidden = new XoopsFormHidden("uid", $uid_value);
	$form->addElement($uid_hidden);
}

//$form->setRequired($uname_text);
//$form->setRequired($email_text);
$form->display();

?>