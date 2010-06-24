<?php
/**
* Import script of profile module from xoops 2.2.* until 2.3.*
*
* @copyright	The XOOPS project http://www.xoops.org/
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license	http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @package	modules
* @since	1.2
* @author	Sina Asghari <pesian_stranger@users.sourceforge.net>
* @author	Taiwen Jiang <phppp@users.sourceforge.net>
* @version	$Id$
*/

define('PROFILE_DB_VERSION', 1);

function addField($name, $title, $description, $category, $type, $valuetype, $weight, $canedit, $options, $step_id, $length) {
	global $xoopsDB, $myts;
	if(!$myts){
		$myts =& MyTextSanitizer::getInstance();
	}
	$xoopsDB->query("INSERT INTO ".$xoopsDB->prefix("profile_field")." VALUES (0, ".$category.", '".$type."', ".$valuetype.", '".$name."', '".$myts->displayTarea($title, true)."', '".$myts->displayTarea($description, true)."', 0, $length, ".$weight.", '', 1, ".$canedit.", 1, 0, '".serialize($options)."', 1, ".$step_id.")");
}

function addCategory($name, $weight) {
	global $xoopsDB;
	$xoopsDB->query("INSERT INTO ".$xoopsDB->prefix("profile_category")." VALUES (0, '".$name."', '', ".$weight.")");
}

function addStep($name, $desc, $order, $save) {
	global $xoopsDB;
	$xoopsDB->query("INSERT INTO ".$xoopsDB->prefix("profile_regstep")." VALUES (0, '".$name."', '".$desc."', ".$order.", ".$save.")");
}
function addVisibility($fieldid, $user_group = 1, $profile_group = 0) {
	global $xoopsDB;
	$xoopsDB->query("INSERT INTO ".$xoopsDB->prefix("profile_visibility")." VALUES (".$fieldid.", ".$user_group.", ".$profile_group.")");
}

function profile_db_upgrade_1() {
	global $icmsConfig, $icmsConfigAuth;
	icms_loadLanguageFile('core', 'user');
	icms_loadLanguageFile('core', 'notification');
	
	addStep(_PROFILE_MI_CAT_BASEINFO, '', 1, 1);
	addStep(_PROFILE_MI_CAT_EXTINFO, '', 2, 0);
	addCategory(_PROFILE_MI_CAT_PERSONAL, 1);
	addCategory(_PROFILE_MI_CAT_MESSAGING, 2);
	addCategory(_PROFILE_MI_CAT_SETTINGS1, 3);
	addCategory(_PROFILE_MI_CAT_COMMUNITY, 4);
	include_once ICMS_ROOT_PATH . "/language/" . $icmsConfig['language'] . '/notification.php';
	include_once ICMS_ROOT_PATH . '/include/notification_constants.php';
	$umode_options = array('nest'=>_NESTED, 'flat'=>_FLAT, 'thread'=>_THREADED);
	$uorder_options = array(0 => _OLDESTFIRST,
							1 => _NEWESTFIRST);
	$notify_mode_options = array(XOOPS_NOTIFICATION_MODE_SENDALWAYS=>_NOT_MODE_SENDALWAYS,
								 XOOPS_NOTIFICATION_MODE_SENDONCETHENDELETE=>_NOT_MODE_SENDONCE,
								 XOOPS_NOTIFICATION_MODE_SENDONCETHENWAIT=>_NOT_MODE_SENDONCEPERLOGIN);
	$notify_method_options = array( XOOPS_NOTIFICATION_METHOD_DISABLE=>_NOT_METHOD_DISABLE,
									XOOPS_NOTIFICATION_METHOD_PM=>_NOT_METHOD_PM,
									XOOPS_NOTIFICATION_METHOD_EMAIL=>_NOT_METHOD_EMAIL);
	addField('user_aim', _PROFILE_MI_AIM_TITLE, _PROFILE_MI_AIM_DESCRIPTION, 1, 'textbox', 1, 1, 1, array(), 2, 255);
	addField('user_icq', _PROFILE_MI_ICQ_TITLE, _PROFILE_MI_ICQ_DESCRIPTION, 1, 'textbox', 1, 2, 1, array(), 2, 255);
	addField('user_msnm', _PROFILE_MI_MSN_TITLE, _PROFILE_MI_MSN_DESCRIPTION, 1, 'textbox', 1, 3, 1, array(), 2, 255);
	addField('user_yim', _PROFILE_MI_YIM_TITLE, _PROFILE_MI_YIM_DESCRIPTION, 1, 'textbox', 1, 4, 1, array(), 2, 255);
	addField('name', _US_REALNAME, '', 2, 'textbox', 1, 1, 1, array(), 1, 255);
	addField('user_from', _PROFILE_MI_FROM_TITLE, _PROFILE_MI_FROM_DESCRIPTION, 2, 'textbox', 1, 2, 1, array(), 2, 255);
	addField('timezone_offset', _US_TIMEZONE, '', 2, 'timezone', 1, 3, 1, array(), 2, 0);
	addField('user_occ', _PROFILE_MI_OCCUPATION_TITLE, _PROFILE_MI_OCCUPATION_DESCRIPTION, 2, 'textbox', 1, 4, 1, array(), 2, 255);
	addField('user_intrest', _PROFILE_MI_INTEREST_TITLE, _PROFILE_MI_INTEREST_DESCRIPTION, 2, 'textbox', 1, 5, 1, array(), 2, 255);
	addField('bio', _PROFILE_MI_BIO_TITLE, _PROFILE_MI_BIO_DESCRIPTION, 2, 'textarea', 2, 6, 1, array(), 2, 0);
	addField('user_regdate', _US_MEMBERSINCE, '', 2, 'datetime', 3, 7, 0, array(), 0, 10);
	addField('user_viewemail', _PROFILE_MI_VIEWEMAIL_TITLE, '', 3, 'yesno', 3, 1, 1, array(), 1, 1);
	addField('attachsig', _US_SHOWSIG, '', 3, 'yesno', 3, 2, 1, array(), 0, 1);
	addField('user_mailok', _US_MAILOK, '', 3, 'yesno', 3, 3, 1, array(), 1, 1);
	addField('theme', _US_SELECT_THEME, '', 3, 'theme', 1, 4, 1, array(), 0, 0);
	addField('language', _US_SELECT_LANG, $icmsConfig['language'], 3, 'language', 1, 5, 1, array(), 0, 0);
	addField('umode', _US_CDISPLAYMODE, '', 3, 'select', 3, 6, 1, $umode_options, 0, 0);
	addField('uorder', _US_CSORTORDER, '', 3, 'select', 3, 7, 1, $uorder_options, 0, 0);
	addField('notify_mode', _NOT_NOTIFYMODE, '', 3, 'select', 3, 8, 1, $notify_mode_options, 0, 0);
	addField('notify_method', _NOT_NOTIFYMETHOD, '', 3, 'select', 3, 9, 1, $notify_method_options, 0, 0);
	addField('url', _PROFILE_MI_URL_TITLE, _PROFILE_MI_URL_DESCRIPTION, 4, 'textbox', 1, 1, 1, array(), 1, 255);
	addField('posts', _US_POSTS, '', 4, 'textbox', 3, 2, 1, array(), 0, 255);
	addField('rank', _US_RANK, '', 4, 'rank', 3, 3, 1, array(), 0, 0);
	addField('last_login', _US_LASTLOGIN, '', 4, 'datetime', 3, 4, 0, array(), 0, 10);
	addField('user_sig', _PROFILE_MI_SIG_TITLE, _PROFILE_MI_SIG_DESCRIPTION, 4, 'dhtml', 1, 5, 1, array(), 0, 0);
	if($icmsConfigAuth['auth_openid'] == 1) {
		addField('openid', _US_OPENID_FORM_CAPTION, _US_OPENID_URL, 4, 'textbox', 1, 1, 1, array(), 1, 255);
		addField('user_viewoid', _PROFILE_MI_VIEWEOID_TITLE, '', 3, 'yesno', 3, 1, 1, array(), 1, 1);
	}
	// Add visbility permissions
	addVisibility(1, 1, 0);
	addVisibility(1, 2, 0);
	addVisibility(2, 1, 0);
	addVisibility(2, 2, 0);
	addVisibility(3, 1, 0);
	addVisibility(3, 2, 0);
	addVisibility(4, 1, 0);
	addVisibility(4, 2, 0);
	addVisibility(5, 1, 0);
	addVisibility(5, 2, 0);
	addVisibility(6, 1, 0);
	addVisibility(6, 2, 0);
	addVisibility(7, 1, 0);
	addVisibility(8, 1, 0);
	addVisibility(8, 2, 0);
	addVisibility(9, 1, 0);
	addVisibility(9, 2, 0);
	addVisibility(10, 1, 0);
	addVisibility(10, 2, 0);
	addVisibility(11, 1, 0);
	addVisibility(11, 2, 0);
	addVisibility(12, 1, 0);
	addVisibility(13, 1, 0);
	addVisibility(14, 1, 0);
	addVisibility(15, 1, 0);
	addVisibility(16, 1, 0);
	addVisibility(17, 1, 0);
	addVisibility(18, 1, 0);
	addVisibility(19, 1, 0);
	addVisibility(20, 1, 0);
	addVisibility(20, 2, 0);
	addVisibility(21, 1, 0);
	addVisibility(21, 2, 0);
	addVisibility(22, 1, 0);
	addVisibility(22, 2, 0);
	addVisibility(23, 1, 0);
	addVisibility(23, 2, 0);
	addVisibility(24, 1, 0);
	addVisibility(24, 2, 0);
	addVisibility(25, 1, 0);
	addVisibility(26, 1, 0);
	return true;
}

function icms_module_update_profile(&$module, $oldversion = null, $dbversion = null) 
{
	return true;
}

function icms_module_install_profile($module) {
	return true;
}
?>