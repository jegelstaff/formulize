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
 * @version		$Id:$
 */

include_once "../../mainfile.php";
$xoopsOption['template_main'] = isset($profile_template) ? $profile_template : '';
include_once ICMS_ROOT_PATH."/header.php";
global $icmsModuleConfig;
$dirname = basename( dirname( __FILE__ ) );

// check if anonymous users can access profiles
if (!is_object($icmsUser) && !$icmsConfigUser['allow_annon_view_prof']) {
	redirect_header(ICMS_URL, 3, _NOPERM);
	exit();
}

$uid = isset($_GET['uid']) ? intval($_GET['uid']) : 0;
if ($uid == 0) {
	if(is_object($icmsUser)){
		$uid = $icmsUser->getVar('uid');
		// this is necessary to make comments work on index.php (comments require $_GET['uid'] here)
		if (isset($profile_current_page) && $profile_current_page == 'index.php') {
			header('location: '.ICMS_URL.'/modules/'.$dirname.'/index.php?uid='.$uid);
			exit();
		}
	} else {
		header('location: '.ICMS_URL.'/modules/'.$dirname.'/search.php');
		exit();
	}
}

$member_handler =& xoops_gethandler('member');
$thisUser =& $member_handler->getUser($uid);

if (!is_object($thisUser)) {
	if (is_object($icmsUser)) {
		redirect_header(ICMS_URL.'/modules/'.$dirname.'/index.php?uid='.$icmsUser->getVar('uid'), 3, _PROFILE_MA_USER_NOT_FOUND);
	} else {
		redirect_header(ICMS_URL.'/modules/'.$dirname.'/search.php', 3, _PROFILE_MA_USER_NOT_FOUND);
	}
	exit();
} else {
	// don't show profile for inactive users
	if ($thisUser->getVar('level') == 0) redirect_header(icms_getPreviousPage('index.php'), 3, _PROFILE_MA_USER_NOT_FOUND);
}

if ($icmsModuleConfig['profile_social']) {
	// all registrated users (administrators included) have to set their profile settings first
	if (!isset($profile_current_page)) $profile_current_page = basename(__FILE__);
	if (is_object($icmsUser) && $icmsUser->getVar('uid') == $uid && $profile_current_page != 'configs.php') {
		$profile_configs_handler = icms_getModuleHandler('configs');
		$config_count = $profile_configs_handler->getCount(new CriteriaCompo(new Criteria('config_uid', intval($uid))));
		if ( $config_count <= 0 ) {
			redirect_header(ICMS_URL.'/modules/'.$dirname.'/configs.php', 3, _PROFILE_MA_MAKE_CONFIG_FIRST);
			exit();
		}
		unset($config_count, $profile_configs_handler);
	}
}

$isOwner = false ;
$isAnonym = is_object($icmsUser) ? false : true;
$isOwner = (is_object($icmsUser) && $icmsUser->getVar('uid') == $uid) ? true : false;
if ($icmsModuleConfig['index_real_name'] == 'real' && trim($thisUser->getVar('name'))) {
	$owner_name = is_object($thisUser) ? trim($thisUser->getVar('name')) : _GUESTS;
} elseif ($icmsModuleConfig['index_real_name'] == 'both' && trim($thisUser->getVar('name'))) {
	$owner_name = is_object($thisUser) ? trim($thisUser->getVar('name')).' ('.trim($thisUser->getVar('uname')).')' : _GUESTS;
} else {
	$owner_name = is_object($thisUser) ? trim($thisUser->getVar('uname')) : _GUESTS;
}

// check whether icmsUser is allowed to view profile of thisUser
if ($isAnonym) {
	if (array_intersect($thisUser->getGroups(), $icmsModuleConfig['view_group_anonymous']) != $thisUser->getGroups())
		redirect_header(icms_getPreviousPage('index.php'), 3, _NOPERM);
} elseif (!$icmsUser->isAdmin(0)) {
	if (array_intersect($thisUser->getGroups(), $icmsModuleConfig['view_group_registered']) != $thisUser->getGroups())
		redirect_header(icms_getPreviousPage('index.php'), 3, _NOPERM);
}

include_once ICMS_ROOT_PATH.'/modules/'.$dirname.'/include/common.php';
icms_loadLanguageFile('core', 'user');
$myts =& MyTextSanitizer::getInstance();
$module_name = $icmsModule->getVar('name');
$xoTheme->addStylesheet(ICMS_URL.'/modules/'.$dirname.'/assets/css/profile'.(@_ADM_USE_RTL == 1 ? '_rtl':'').'.css');
if(ereg('msie', strtolower($_SERVER['HTTP_USER_AGENT']))) {$xoTheme->addStylesheet(ICMS_URL.'/modules/'.$dirname.'/assets/css/tabs-ie.css');}

icms_makeSmarty(array(
	'module_name'          => $module_name,
	'icms_pagetitle'       => sprintf(_MD_PROFILE_PAGETITLE, $owner_name),
	'profile_image'        => '<img src="'.ICMS_URL.'/modules/'.$dirname.'/assets/images/profile-start.gif" alt="'.$module_name.'"/>',
	'profile_content'      => _MI_PROFILE_MODULEDESC,
	'module_is_socialmode' => $icmsModuleConfig['profile_social'],
	'profile_module_home'  => '<a href="'.ICMS_URL.'/modules/'.$dirname.'/index.php?uid='.$uid.'">'.sprintf(_MD_PROFILE_PAGETITLE, $owner_name).'</a>'));

if ($icmsModuleConfig['profile_social']) {
	$permissions = array();
	$items = array('audio', 'pictures', 'friendship', 'videos', 'tribes', 'profile_contact', 'profile_stats', 'profile_general', 'profile_usercontributions');
	foreach ($items as $item) $permissions = array_merge($permissions, array($item => getAllowedItems($item, $uid)));
	foreach ($permissions as $permission => $value) {
		if (in_array($permission, array('audio', 'pictures', 'friendship', 'videos', 'tribes'))) {
			$icmsTpl->assign('allow_'.$permission, $icmsModuleConfig['enable_'.$permission] && $value);
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
		'isAnonym'     => $isAnonym,
		'uid'          => $uid));
}

if ($isAnonym == true && $uid == 0) {
	include_once(ICMS_ROOT_PATH.'/modules/'.$dirname.'/footer.php');
	exit();
}

$icmsTpl->assign('token',$GLOBALS['xoopsSecurity']->getTokenHTML());
?>