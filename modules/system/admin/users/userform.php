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
 * @version	$Id: userform.php 20597 2010-12-21 13:54:35Z phoenyx $
 */

global $icmsConfigUser, $icmsConfigAuth;

$uid_label = new icms_form_elements_Label(_AM_USERID, $uid_value);
$uname_text = new icms_form_elements_Text(_AM_NICKNAME, "username", 25, 25, $uname_value);
$login_name_text = new icms_form_elements_Text(_AM_LOGINNAME, "login_name", 25, 25, $login_name_value);
$name_text = new icms_form_elements_Text(_AM_NAME, "name", 30, 60, $name_value);
$email_tray = new icms_form_elements_Tray(_AM_EMAIL, "<br />");
$email_text = new icms_form_elements_Text("", "email", 30, 60, $email_value);
$email_tray->addElement($email_text, true);
$email_cbox = new icms_form_elements_Checkbox("", "user_viewemail", $email_cbox_value);
$email_cbox->addOption(1, _AM_AOUTVTEAD);
$email_tray->addElement($email_cbox);
if ($icmsConfigAuth['auth_openid'] == 1) {
	$openid_tray = new icms_form_elements_Tray(_AM_OPENID, "<br />");
	$openid_text = new icms_form_elements_Text("", "openid", 30, 255, $openid_value);
	$openid_tray->addElement($openid_text);
	$openid_cbox = new icms_form_elements_Checkbox("", "user_viewoid", $openid_cbox_value);
	$openid_cbox->addOption(1, _AM_AOUTVTOIAD);
	$openid_tray->addElement($openid_cbox);
}
$url_text = new icms_form_elements_Text(_AM_URL, "url", 30, 100, $url_value);
//  $avatar_select = new icms_form_elements_Select("", "user_avatar", $avatar_value);
//  $avatar_array = icms_core_Filesystem::getImgList(XOOPS_ROOT_PATH."/images/avatar/");
//  $avatar_select->addOptionArray($avatar_array);
//  $a_dirlist = icms_core_Filesystem::getDirList(XOOPS_ROOT_PATH."/images/avatar/");
//  $a_dir_labels = array();
//  $a_count = 0;
//  $a_dir_link = "<a href=\"javascript:openWithSelfMain('".XOOPS_URL."/misc.php?action=showpopups&amp;type=avatars&amp;start=".$a_count."','avatars',600,400);\">XOOPS</a>";
//  $a_count = $a_count + count($avatar_array);
//  $a_dir_labels[] = new icms_form_elements_Label("", $a_dir_link);
//  foreach ($a_dirlist as $a_dir) {
//	  if ($a_dir == "users") {
//		  continue;
//	  }
//	  $avatars_array = icms_core_Filesystem::getImgList(XOOPS_ROOT_PATH."/images/avatar/".$a_dir."/", $a_dir."/");
//	  $avatar_select->addOptionArray($avatars_array);
//	  $a_dir_link = "<a href=\"javascript:openWithSelfMain('".XOOPS_URL."/misc.php?action=showpopups&amp;type=avatars&amp;subdir=".$a_dir."&amp;start=".$a_count."','avatars',600,400);\">".$a_dir."</a>";
//	  $a_dir_labels[] = new icms_form_elements_Label("", $a_dir_link);
//	  $a_count = $a_count + count($avatars_array);
//  }
//  if (!empty($uid_value)) {
//	  $myavatar = avatarExists($uid_value);
//	  if ($myavatar != false) {
//		  $avatar_select->addOption($myavatar, _US_MYAVATAR);
//	  }
//  }
//  $avatar_select->setExtra("onchange='showImgSelected(\"avatar\", \"user_avatar\", \"images/avatar\", \"\", \"".XOOPS_URL."\")'");
//  $avatar_label = new icms_form_elements_Label("", "<img src='".XOOPS_URL."/images/avatar/".$avatar_value."' name='avatar' id='avatar' alt='' />");
//  $avatar_tray = new icms_form_elements_Tray(_AM_AVATAR, "&nbsp;");
//  $avatar_tray->addElement($avatar_select);
//  $avatar_tray->addElement($avatar_label);
//  foreach ($a_dir_labels as $a_dir_label) {
//	  $avatar_tray->addElement($a_dir_label);
//  }
//  $theme_select = new icms_form_elements_select_Theme(_AM_THEME, "theme", $theme_value);
$timezone_select = new icms_form_elements_select_Timezone(_US_TIMEZONE, "timezone_offset", $timezone_value);
$icq_text = new icms_form_elements_Text(_AM_ICQ, "user_icq", 15, 15, $icq_value);
$aim_text = new icms_form_elements_Text(_AM_AIM, "user_aim", 18, 18, $aim_value);
$yim_text = new icms_form_elements_Text(_AM_YIM, "user_yim", 25, 25, $yim_value);
$msnm_text = new icms_form_elements_Text(_AM_MSNM, "user_msnm", 30, 100, $msnm_value);
$location_text = new icms_form_elements_Text(_AM_LOCATION, "user_from", 30, 100, $location_value);
$occupation_text = new icms_form_elements_Text(_AM_OCCUPATION, "user_occ", 30, 100, $occ_value);
$interest_text = new icms_form_elements_Text(_AM_INTEREST, "user_intrest", 30, 150, $interest_value);
$sig_tray = new icms_form_elements_Tray(_AM_SIGNATURE, "<br />");
if ($icmsConfigUser['allow_htsig'] == 0) {$sig_tarea = new icms_form_elements_Textarea("", "user_sig", $sig_value);}
else {$sig_tarea = new icms_form_elements_Dhtmltextarea("", "user_sig", $sig_value);}
$sig_tray->addElement($sig_tarea);
$sig_cbox = new icms_form_elements_Checkbox("", "attachsig", $sig_cbox_value);
$sig_cbox->addOption(1, _US_SHOWSIG);
$sig_tray->addElement($sig_cbox);
$umode_select = new icms_form_elements_Select(_US_CDISPLAYMODE, "umode", $umode_value);
$umode_select->addOptionArray(array("nest"=>_NESTED, "flat"=>_FLAT, "thread"=>_THREADED));
$uorder_select = new icms_form_elements_Select(_US_CSORTORDER, "uorder", $uorder_value);
$uorder_select->addOptionArray(array("0"=>_OLDESTFIRST, "1"=>_NEWESTFIRST));

// RMV-NOTIFY
icms_loadLanguageFile('core', 'notification');
include_once ICMS_ROOT_PATH . '/include/notification_constants.php';
$notify_method_select = new icms_form_elements_Select(_NOT_NOTIFYMETHOD, 'notify_method', $notify_method_value);
$notify_method_select->addOptionArray(array(XOOPS_NOTIFICATION_METHOD_DISABLE=>_NOT_METHOD_DISABLE, XOOPS_NOTIFICATION_METHOD_PM=>_NOT_METHOD_PM, XOOPS_NOTIFICATION_METHOD_EMAIL=>_NOT_METHOD_EMAIL));
$notify_mode_select = new icms_form_elements_Select(_NOT_NOTIFYMODE, 'notify_mode', $notify_mode_value);
$notify_mode_select->addOptionArray(array(XOOPS_NOTIFICATION_MODE_SENDALWAYS=>_NOT_MODE_SENDALWAYS, XOOPS_NOTIFICATION_MODE_SENDONCETHENDELETE=>_NOT_MODE_SENDONCE, XOOPS_NOTIFICATION_MODE_SENDONCETHENWAIT=>_NOT_MODE_SENDONCEPERLOGIN));
$bio_tarea = new icms_form_elements_Textarea(_US_EXTRAINFO, "bio", $bio_value);
$rank_select = new icms_form_elements_Select(_AM_RANK, "rank", $rank_value);
$ranklist = icms_getModuleHandler("userrank", "system")->getList(icms_buildCriteria(array('rank_special' => '1')));
if (count($ranklist) > 0) {
	$rank_select->addOption(0, "--------------");
	$rank_select->addOptionArray($ranklist);
} else {
	$rank_select->addOption(0, _AM_NSRID);
}
global $icmsConfigUser;
$pwd_text = new icms_form_elements_Password(_AM_PASSWORD, "password", 10, 255, '', false, ($icmsConfigUser['pass_level']?'password_adv':''));
$pwd_text2 = new icms_form_elements_Password(_AM_RETYPEPD, "pass2", 10, 255);
$mailok_radio = new icms_form_elements_Radioyn(_US_MAILOK, 'user_mailok', (int) ($mailok_value));

$language = new icms_form_elements_select_Lang(_US_SELECT_LANG,'language', $language_value);

// Groups administration addition XOOPS 2.0.9: Mith
$gperm_handler = icms::handler('icms_member_groupperm');
//If user has admin rights on groups
if ($gperm_handler->checkRight("system_admin", XOOPS_SYSTEM_GROUP, icms::$user->getGroups(), 1)) {
	//add group selection
	if (in_array(XOOPS_GROUP_ADMIN, icms::$user->getGroups())) {
		$group_select = array(new icms_form_elements_select_Group(_US_GROUPS, 'groups', false, $groups, 15, true));
	} else {
		$group_manager_value = array_intersect_key(icms::handler('icms_member')->getGroupList(), array_flip($gperm_handler->getItemIds('group_manager', icms::$user->getGroups()))) ;
		$group_array = new icms_form_elements_Select(_US_GROUPS, 'groups', $groups, 15, true);
		$group_array->addOptionArray($group_manager_value);
		$group_select = array ($group_array);
		//$group_hidden = array_diff(icms::handler('icms_member')->getGroupList(),$group_manager_value);
		$group_hidden = array_diff($groups,array_flip($group_manager_value));
		foreach ($group_hidden as $key => $group) {
			$group_hidden_select[] = new icms_form_elements_Hidden('groups_hidden[' . $key . ']', $group);
		}
	}
}
else {
	//add each user groups
	foreach ($groups as $key => $group) {
		$group_select[] = new icms_form_elements_Hidden('groups[' . $key . ']', $group);
	}
}

$salt_hidden = new icms_form_elements_Hidden('salt', icms_core_Password::createSalt());

$enc_type_hidden = new icms_form_elements_Hidden('enc_type', $icmsConfigUser['enc_type']);
$pass_expired_hidden = new icms_form_elements_Hidden('pass_expired', 0);
$fct_hidden = new icms_form_elements_Hidden("fct", "users");
$op_hidden = new icms_form_elements_Hidden("op", $op_value);
$submit_button = new icms_form_elements_Button("", "submit", _SUBMIT, "submit");

$form = new icms_form_Theme($form_title, "userinfo", "admin.php", "post", true);
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

if (@is_array($group_hidden_select)) {
	foreach ($group_hidden_select as $group) {
		$form->addElement($group);
		unset($group);
	}
}

$form->addElement($fct_hidden);
$form->addElement($op_hidden);
$form->addElement($submit_button);

if (!empty($uid_value)) {
	$uid_hidden = new icms_form_elements_Hidden("uid", $uid_value);
	$form->addElement($uid_hidden);
}

//$form->setRequired($uname_text);
//$form->setRequired($email_text);
$form->display();

?>