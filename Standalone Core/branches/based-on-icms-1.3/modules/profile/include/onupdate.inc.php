<?php
/**
* Import script of profile module from xoops 2.2.* until 2.3.*
*
* @copyright	The XOOPS project http://www.xoops.org/
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @package		modules
* @since		1.2
* @author		Sina Asghari <pesian_stranger@users.sourceforge.net>
* @author		Taiwen Jiang <phppp@users.sourceforge.net>
* @version		$Id: onupdate.inc.php 22408 2011-08-26 18:45:39Z phoenyx $
*/

define('PROFILE_DB_VERSION', 2);

function addField($name, $title, $description, $category, $type, $valuetype, $weight, $canedit, $options, $step_id, $length, $required = 0) {
	$profile_field_handler = icms_getModuleHandler('field', basename(dirname(dirname(__FILE__))), 'profile');
	$fieldObj = $profile_field_handler->get(0);
	$fieldObj->setVar('catid', $category);
	$fieldObj->setVar('field_type', $type);
	$fieldObj->setVar('field_valuetype', $valuetype);
	$fieldObj->setVar('field_name', $name);
	$fieldObj->setVar('field_title', $title);
	$fieldObj->setVar('field_description', $description);
	$fieldObj->setVar('field_required', $required);
	$fieldObj->setVar('field_maxlength', $length);
	$fieldObj->setVar('field_weight', $weight);
	$fieldObj->setVar('field_default', '');
	$fieldObj->setVar('field_notnull', 1);
	$fieldObj->setVar('field_edit', $canedit);
	$fieldObj->setVar('field_show', 1);
	$fieldObj->setVar('field_options', serialize($options));
	$fieldObj->setVar('exportable', 1);
	$fieldObj->setVar('step_id', $step_id);
	$fieldObj->setVar('system', 1);
	$fieldObj->store();
	return $fieldObj->getVar('fieldid');
}

function addCategory($name, $weight) {
	$profile_category_handler = icms_getModuleHandler('category', basename(dirname(dirname(__FILE__))), 'profile');
	$categoryObj = $profile_category_handler->get(0);
	$categoryObj->setVar('cat_title', $name);
	$categoryObj->setVar('cat_weight', $weight);
	$categoryObj->store();
	return $categoryObj->getVar('catid');
}

function addStep($name, $desc, $order, $save) {
	$profile_regstep_handler = icms_getModuleHandler('regstep', basename(dirname(dirname(__FILE__))), 'profile');
	$regstepObj = $profile_regstep_handler->get(0);
	$regstepObj->setVar('step_name', $name);
	$regstepObj->setVar('step_intro', $desc);
	$regstepObj->setVar('step_order', $order);
	$regstepObj->setVar('step_save', $save);
	$regstepObj->store();
	return $regstepObj->getVar('step_id');
}
function addVisibility($fieldid, $user_groups = array(ICMS_GROUP_ADMIN), $profile_group = 0) {
	// uncomment this after IPFing the visibility class
	/*$profile_visibility_handler = icms_getModuleHandler('visibility', basename(dirname(dirname(__FILE__))), 'profile');
	foreach ($user_groups as $user_group) {
		$visibilityObj = $profile_visibility_handler->get(0);
		$visibilityObj->setVar('fieldid', $fieldid);
		$visibilityObj->setVar('user_group', $user_group);
		$visibilityObj->setVar('profile_group', $profile_group);
		$visibilityObj->store();
	}
	return true;*/
	foreach ($user_groups as $user_group)
		icms::$xoopsDB->query("INSERT INTO ".icms::$xoopsDB->prefix(basename(dirname(dirname(__FILE__)))."_visibility")." VALUES (".$fieldid.", ".$user_group.", ".$profile_group.")");
}

function profile_db_upgrade_1() {
	icms_loadLanguageFile('core', 'user');
	icms_loadLanguageFile('core', 'notification');
	
	addStep(_MI_PROFILE_CAT_BASEINFO, '', 1, 0);
	addStep(_MI_PROFILE_CAT_EXTINFO, '', 2, 1);

	addCategory(_MI_PROFILE_CAT_PERSONAL, 1);
	addCategory(_MI_PROFILE_CAT_MESSAGING, 3);
	addCategory(_MI_PROFILE_CAT_SETTINGS1, 4);
	addCategory(_MI_PROFILE_CAT_COMMUNITY, 2);

	include_once ICMS_ROOT_PATH.'/include/notification_constants.php';
	$umode_options = array('nest' => _NESTED, 'flat' => _FLAT, 'thread' => _THREADED);
	$uorder_options = array(0 => _OLDESTFIRST, 1 => _NEWESTFIRST);
	$notify_mode_options = array(XOOPS_NOTIFICATION_MODE_SENDALWAYS => _NOT_MODE_SENDALWAYS, XOOPS_NOTIFICATION_MODE_SENDONCETHENDELETE => _NOT_MODE_SENDONCE, XOOPS_NOTIFICATION_MODE_SENDONCETHENWAIT => _NOT_MODE_SENDONCEPERLOGIN);
	$notify_method_options = array(XOOPS_NOTIFICATION_METHOD_DISABLE=>_NOT_METHOD_DISABLE, XOOPS_NOTIFICATION_METHOD_PM => _NOT_METHOD_PM, XOOPS_NOTIFICATION_METHOD_EMAIL => _NOT_METHOD_EMAIL);

	$fieldid = addField('name',            _US_REALNAME,             '', 1, 'textbox',  1, 1,  1, array(),                1, 255);
	addVisibility($fieldid, array(ICMS_GROUP_ADMIN, ICMS_GROUP_USERS), 0);
	$fieldid = addField('user_from',       _US_LOCATION,             '', 1, 'location', 1, 2,  1, array(),                2, 255);
	addVisibility($fieldid, array(ICMS_GROUP_ADMIN, ICMS_GROUP_USERS), 0);
	$fieldid = addField('user_occ',        _US_OCCUPATION,           '', 1, 'textbox',  1, 3,  1, array(),                2, 255);
	addVisibility($fieldid, array(ICMS_GROUP_ADMIN, ICMS_GROUP_USERS), 0);
	$fieldid = addField('user_intrest',    _US_INTEREST,             '', 1, 'textbox',  1, 4,  1, array(),                2, 255);
	addVisibility($fieldid, array(ICMS_GROUP_ADMIN, ICMS_GROUP_USERS), 0);
	$fieldid = addField('bio',             _US_EXTRAINFO,            '', 1, 'textarea', 2, 5,  1, array(),                2, 0);
	addVisibility($fieldid, array(ICMS_GROUP_ADMIN, ICMS_GROUP_USERS), 0);
	$fieldid = addField('user_aim',        _US_AIM,                  '', 2, 'textbox',  1, 1,  1, array(),                2, 255);
	addVisibility($fieldid, array(ICMS_GROUP_ADMIN, ICMS_GROUP_USERS), 0);
	$fieldid = addField('user_icq',        _US_ICQ,                  '', 2, 'textbox',  1, 2,  1, array(),                2, 255);
	addVisibility($fieldid, array(ICMS_GROUP_ADMIN, ICMS_GROUP_USERS), 0);
	$fieldid = addField('user_msnm',       _US_MSNM,                 '', 2, 'textbox',  1, 3,  1, array(),                2, 255);
	addVisibility($fieldid, array(ICMS_GROUP_ADMIN, ICMS_GROUP_USERS), 0);
	$fieldid = addField('user_yim',        _US_YIM,                  '', 2, 'textbox',  1, 4,  1, array(),                2, 255);
	addVisibility($fieldid, array(ICMS_GROUP_ADMIN, ICMS_GROUP_USERS), 0);
	$fieldid = addField('user_viewemail',  _US_ALLOWVIEWEMAIL,       '', 3, 'yesno',    3, 1,  1, array(),                1, 1);
	$fieldid = addField('attachsig',       _US_SHOWSIG,              '', 3, 'yesno',    3, 2,  1, array(),                0, 1);
	$fieldid = addField('user_mailok',     _US_MAILOK,               '', 3, 'yesno',    3, 3,  1, array(),                1, 1);
	$fieldid = addField('theme',           _US_SELECT_THEME,         '', 3, 'theme',    1, 4,  1, array(),                0, 0);
	$fieldid = addField('language',        _US_SELECT_LANG,          '', 3, 'language', 1, 5,  1, array(),                0, 0);
	$fieldid = addField('umode',           _US_CDISPLAYMODE,         '', 3, 'select',   3, 6,  1, $umode_options,         0, 0);
	$fieldid = addField('uorder',          _US_CSORTORDER,           '', 3, 'select',   3, 7,  1, $uorder_options,        0, 0);
	$fieldid = addField('notify_mode',     _NOT_NOTIFYMODE,          '', 3, 'select',   3, 8,  1, $notify_mode_options,   0, 0);
	$fieldid = addField('notify_method',   _NOT_NOTIFYMETHOD,        '', 3, 'select',   3, 9,  1, $notify_method_options, 0, 0);
	$fieldid = addField('timezone_offset', _US_TIMEZONE,             '', 3, 'timezone', 1, 10, 1, array(),                2, 0);
	$fieldid = addField('user_viewoid',    _US_ALLOWVIEWEMAILOPENID, '', 3, 'yesno',    3, 11, 0, array(),                1, 1);
	$fieldid = addField('url',             _US_WEBSITE,              '', 4, 'url',      1, 1,  1, array(),                1, 255);
	addVisibility($fieldid, array(ICMS_GROUP_ADMIN, ICMS_GROUP_USERS), 0);
	$fieldid = addField('posts',           _US_POSTS,                '', 4, 'textbox',  3, 2,  0, array(),                0, 255);
	addVisibility($fieldid, array(ICMS_GROUP_ADMIN, ICMS_GROUP_USERS), 0);
	$fieldid = addField('rank',            _US_RANK,                 '', 4, 'rank',     3, 3,  1, array(),                0, 0);
	addVisibility($fieldid, array(ICMS_GROUP_ADMIN, ICMS_GROUP_USERS), 0);
	$fieldid = addField('user_regdate',    _US_MEMBERSINCE,          '', 4, 'datetime', 3, 4,  0, array(),                0, 10);
	addVisibility($fieldid, array(ICMS_GROUP_ADMIN, ICMS_GROUP_USERS), 0);
	$fieldid = addField('last_login',      _US_LASTLOGIN,            '', 4, 'datetime', 3, 5,  0, array(),                0, 10);
	addVisibility($fieldid, array(ICMS_GROUP_ADMIN, ICMS_GROUP_USERS), 0);
	$fieldid = addField('openid',          _US_OPENID_FORM_CAPTION,  '', 4, 'textbox',  1, 6,  0, array(),                1, 255);
	addVisibility($fieldid, array(ICMS_GROUP_ADMIN), 0);
	$fieldid = addField('user_sig',        _US_SIGNATURE,            '', 4, 'dhtml',    1, 7,  1, array(),                0, 0);
	addVisibility($fieldid, array(ICMS_GROUP_ADMIN, ICMS_GROUP_USERS), 0);

	return true;
}

function profile_db_upgrade_2() {
	// Initialization
	$profile_field_handler = icms_getModuleHandler('field', basename(dirname(dirname(__FILE__))), 'profile');

	// add new fields
	$fieldid = addField('email',           _US_EMAIL,                '', 2, 'email',    1,  5, 0, array(),                1, 255, 1);
	addVisibility($fieldid, array(ICMS_GROUP_ADMIN, ICMS_GROUP_USERS), 0);

	// Copy images
	icms_core_Filesystem::copyRecursive(ICMS_ROOT_PATH.'/modules/'.basename(dirname(dirname(__FILE__))).'/images/field', ICMS_UPLOAD_PATH.'/'.basename(dirname(dirname(__FILE__))).'/field');

	// Assign Images to fields
	$profile_field_handler->updateAll('url', 'aim.gif', new icms_db_criteria_Compo(new icms_db_criteria_Item('field_name', 'user_aim')));
	$profile_field_handler->updateAll('url', 'bio.gif', new icms_db_criteria_Compo(new icms_db_criteria_Item('field_name', 'bio')));
	$profile_field_handler->updateAll('url', 'birthday.gif', new icms_db_criteria_Compo(new icms_db_criteria_Item('field_name', 'user_regdate')));
	$profile_field_handler->updateAll('url', 'clock.gif', new icms_db_criteria_Compo(new icms_db_criteria_Item('field_name', 'last_login')));
	$profile_field_handler->updateAll('url', 'comments.gif', new icms_db_criteria_Compo(new icms_db_criteria_Item('field_name', 'posts')));
	$profile_field_handler->updateAll('url', 'email.gif', new icms_db_criteria_Compo(new icms_db_criteria_Item('field_name', 'email')));
	$profile_field_handler->updateAll('url', 'house.gif', new icms_db_criteria_Compo(new icms_db_criteria_Item('field_name', 'user_from')));
	$profile_field_handler->updateAll('url', 'icq.gif', new icms_db_criteria_Compo(new icms_db_criteria_Item('field_name', 'user_icq')));
	$profile_field_handler->updateAll('url', 'interests.gif', new icms_db_criteria_Compo(new icms_db_criteria_Item('field_name', 'user_intrest')));
	$profile_field_handler->updateAll('url', 'msnm.gif', new icms_db_criteria_Compo(new icms_db_criteria_Item('field_name', 'user_msnm')));
	$profile_field_handler->updateAll('url', 'occ.gif', new icms_db_criteria_Compo(new icms_db_criteria_Item('field_name', 'user_occ')));
	$profile_field_handler->updateAll('url', 'openid.gif', new icms_db_criteria_Compo(new icms_db_criteria_Item('field_name', 'openid')));
	$profile_field_handler->updateAll('url', 'rank.gif', new icms_db_criteria_Compo(new icms_db_criteria_Item('field_name', 'rank')));
	$profile_field_handler->updateAll('url', 'signature.gif', new icms_db_criteria_Compo(new icms_db_criteria_Item('field_name', 'user_sig')));
	$profile_field_handler->updateAll('url', 'url.gif', new icms_db_criteria_Compo(new icms_db_criteria_Item('field_name', 'url')));
	$profile_field_handler->updateAll('url', 'username.gif', new icms_db_criteria_Compo(new icms_db_criteria_Item('field_name', 'name')));
	$profile_field_handler->updateAll('url', 'ym.gif', new icms_db_criteria_Compo(new icms_db_criteria_Item('field_name', 'user_yim')));

	// update existing fields
	$profile_field_handler->updateAll('field_type', 'url', new icms_db_criteria_Compo(new icms_db_criteria_Item('field_name', 'url')));
	$profile_field_handler->updateAll('field_type', 'location', new icms_db_criteria_Compo(new icms_db_criteria_Item('field_name', 'user_from')));
	$profile_field_handler->updateAll('field_type', 'openid', new icms_db_criteria_Compo(new icms_db_criteria_Item('field_name', 'openid')));
	$profile_field_handler->updateAll('field_edit', '0', new icms_db_criteria_Compo(new icms_db_criteria_Item('field_name', '("posts", "openid", "user_viewoid")', 'IN')));
	$profile_field_handler->updateAll('system', '1', new icms_db_criteria_Compo(new icms_db_criteria_Item('field_name', '("' . implode('", "', $profile_field_handler->getUserVars()).'")', 'IN')));
}

function icms_module_update_profile(&$module, $oldversion = null, $dbversion = null) {
	return true;
}

function icms_module_install_profile($module) {
	return true;
}
?>