<?php
/**
 * Extended User Profile
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		LICENSE.txt
 * @license		GNU General Public License (GPL) http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @package		modules
 * @since		1.3
 * @author		Marcello Brandao <marcello.brandao@gmail.com>
 * @author		Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @version		$Id: header.php 21139 2011-03-20 20:58:11Z m0nty_ $
 */

include_once "../../mainfile.php";
$xoopsOption['template_main'] = isset($profile_template) ? $profile_template : '';
include_once ICMS_ROOT_PATH."/header.php";
include_once ICMS_ROOT_PATH.'/modules/'.basename(dirname(__FILE__)).'/include/common.php';

// check if anonymous users can access profiles
if (!is_object(icms::$user) && !$icmsConfigUser['allow_annon_view_prof']) redirect_header(ICMS_URL, 3, _NOPERM);

$uid = isset($_GET['uid']) ? (int)$_GET['uid'] : 0;
if ($uid == 0) {
	if (is_object(icms::$user)){
		$uid = icms::$user->getVar('uid');
		// this is necessary to make comments work on index.php (comments require $_GET['uid'] here)
		if (isset($profile_current_page) && $profile_current_page == 'index.php') {
			header('location: '.PROFILE_URL.'index.php?uid='.$uid);
			exit();
		}
	} else {
		header('location: '.PROFILE_URL.'search.php');
		exit();
	}
}

$thisUser = icms::handler('icms_member')->getUser($uid);

if (!is_object($thisUser)) {
	if (is_object(icms::$user)) {
		redirect_header(PROFILE_URL.'index.php?uid='.icms::$user->getVar('uid'), 3, _MD_PROFILE_USER_NOT_FOUND);
	} else {
		redirect_header(PROFILE_URL.'search.php', 3, _MD_PROFILE_USER_NOT_FOUND);
	}
} else {
	// don't show profile for inactive users
	if (!is_object($thisUser) || (!$thisUser->isActive() && (!icms::$user || !$profile_isAdmin))) redirect_header(PROFILE_URL, 3, _MD_PROFILE_SELECTNG);
	if ($thisUser->getVar('level') == -1 && icms::$user && $profile_isAdmin) $icmsTpl->assign('deleted', _MD_PROFILE_DELETED);
}

$profile_configs_handler = icms_getModuleHandler('configs', basename(dirname(__FILE__)), 'profile');
if (icms::$module->config['profile_social']) {
	// all registrated users (administrators included) have to set their profile settings first
	if (!isset($profile_current_page)) $profile_current_page = basename(__FILE__);
	$configs = $profile_configs_handler->getObjects(new icms_db_criteria_Compo(new icms_db_criteria_Item('config_uid', (int)$uid)));
	if (is_object(icms::$user) && icms::$user->getVar('uid') == $uid && $profile_current_page != 'configs.php' && count($configs) <= 0)
		redirect_header(PROFILE_URL.'configs.php', 3, _MD_PROFILE_MAKE_CONFIG_FIRST);
	if (is_object(icms::$user) && $profile_isAdmin && count($configs) == 1 && $configs[0]->getVar('suspension') == 1 && $configs[0]->getVar('status') == 1)
		$icmsTpl->assign('suspended', sprintf(_MD_PROFILE_SUSPENDED, $configs[0]->getVar('end_suspension')));
	unset($configs);
}

$isOwner = false ;
$isOwner = (is_object(icms::$user) && icms::$user->getVar('uid') == $uid) ? true : false;
if (icms::$module->config['index_real_name'] == 'real' && trim($thisUser->getVar('name'))) {
	$owner_name = is_object($thisUser) ? trim($thisUser->getVar('name')) : _GUESTS;
} elseif (icms::$module->config['index_real_name'] == 'both' && trim($thisUser->getVar('name'))) {
	$owner_name = is_object($thisUser) ? trim($thisUser->getVar('name')).' ('.trim($thisUser->getVar('uname')).')' : _GUESTS;
} else {
	$owner_name = is_object($thisUser) ? trim($thisUser->getVar('uname')) : _GUESTS;
}

// check whether icms::$user is allowed to view profile of thisUser
if (!is_object(icms::$user)) {
	if (array_intersect($thisUser->getGroups(), icms::$module->config['view_group_'.ICMS_GROUP_ANONYMOUS]) != $thisUser->getGroups())
		redirect_header(icms_getPreviousPage('index.php'), 3, _NOPERM);
} elseif (!icms::$user->isAdmin(0)) {
	if (array_intersect($thisUser->getGroups(), icms::$module->config['view_group_'.ICMS_GROUP_USERS]) != $thisUser->getGroups())
		redirect_header(icms_getPreviousPage('index.php'), 3, _NOPERM);
}

icms_loadLanguageFile('core', 'user');
$module_name = icms::$module->getVar('name');
$xoTheme->addStylesheet(PROFILE_URL.'assets/css/profile'.(@_ADM_USE_RTL == 1 ? '_rtl':'').'.css');

icms_makeSmarty(array(
	'module_name'          => $module_name,
	'icms_pagetitle'       => sprintf(_MD_PROFILE_PAGETITLE, $owner_name),
	'profile_image'        => '<img src="'.PROFILE_URL.'images/profile-start.gif" alt="'.$module_name.'"/>',
	'profile_content'      => _MI_PROFILE_MODULEDESC,
	'module_is_socialmode' => icms::$module->config['profile_social'],
	'profile_module_home'  => '<a href="'.PROFILE_URL.'index.php?uid='.$uid.'">'.sprintf(_MD_PROFILE_PAGETITLE, $owner_name).'</a>'));

if (icms::$module->config['profile_social']) {
	$permissions = array();
	$items = array('audio', 'pictures', 'friendship', 'videos', 'tribes', 'profile_usercontributions');
	foreach ($items as $item) $permissions = array_merge($permissions, array($item => $profile_configs_handler->userCanAccessSection($item, $uid)));
	foreach ($permissions as $permission => $value) {
		if (in_array($permission, array('audio', 'pictures', 'friendship', 'videos', 'tribes'))) {
			$icmsTpl->assign('allow_'.$permission, icms::$module->config['enable_'.$permission] && $value);
		} else {
			$icmsTpl->assign('allow_'.$permission, $value);
		}
	}

	icms_makeSmarty(array(
		'lang_photos'  => _MD_PROFILE_PHOTOS,
		'lang_friends' => _MD_PROFILE_FRIENDS,
		'lang_audio'   => _MD_PROFILE_AUDIOS,
		'lang_videos'  => _MD_PROFILE_VIDEOS,
		'lang_profile' => _MD_PROFILE_PROFILE,
		'lang_tribes'  => _MD_PROFILE_TRIBES,
		'isOwner'      => $isOwner,
		'isAnonym'     => !is_object(icms::$user),
		'isAdmin'      => $profile_isAdmin,
		'uid'          => $uid));
}

if (!is_object(icms::$user) && $uid == 0) {
	include_once PROFILE_ROOT_PATH.'footer.php';
	exit();
}

$icmsTpl->assign('token', icms::$security->getTokenHTML());