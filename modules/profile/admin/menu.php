<?php
/**
 * Extended User Profile
 *
 * @copyright       The ImpressCMS Project <http://www.impresscms.org/>
 * @license         LICENSE.txt
 * @license			GNU General Public License (GPL) http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @package         profile
 * @since           1.2
 * @author          Jan Pedersen
 * @author          The SmartFactory <www.smartfactory.ca>
 * @author	   		Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @author			Gustavo Pilla (aka nekro) <nekro@impresscms.org>
 * @version         $Id: menu.php 20562 2010-12-19 18:26:36Z phoenyx $
 */

$adminmenu[] = array(
	'title'	=> _MI_PROFILE_USERS,
	'link'	=> 'admin/user.php',
	'icon'  => 'images/admin/users_big.png',
	'small' => 'images/admin/users_small.png');
$adminmenu[] = array(
	'title'	=> _AM_PROFILE_CATEGORYS,
	'link'	=> 'admin/category.php',
	'icon'  => 'images/admin/categories_big.png',
	'small' => 'images/admin/categories_small.png');
$adminmenu[] = array(
	'title'	=> _AM_PROFILE_FIELDS,
	'link'	=> 'admin/field.php',
	'icon'  => 'images/admin/fields_big.png',
	'small' => 'images/admin/fields_small.png');
$adminmenu[] = array(
	'title'	=> _AM_PROFILE_REGSTEPS,
	'link'	=> 'admin/regstep.php',
	'icon'  => 'images/admin/regstep_big.png',
	'small' => 'images/admin/regstep_small.png');
$adminmenu[] = array(
	'title'	=> _MI_PROFILE_VISIBILITY,
	'link'	=> 'admin/visibility.php',
	'icon'  => 'images/admin/visibility_big.png',
	'small' => 'images/admin/visibility_small.png');
$adminmenu[] = array(
	'title'	=> _MI_PROFILE_PERMISSIONS,
	'link'	=> 'admin/permissions.php',
	'icon'  => 'images/admin/permissions_big.png',
	'small' => 'images/admin/permissions_small.png');

$module = icms::handler("icms_module")->getByDirname(basename(dirname(dirname(__FILE__))), TRUE);
if ($module->config['profile_social']) {
	$adminmenu[] = array(
		'title'	=> _MI_PROFILE_PICTURES,
		'link'	=> 'admin/pictures.php',
		'icon'  => 'images/admin/pictures_big.png',
		'small' => 'images/admin/pictures_small.png');
	$adminmenu[] = array(
		'title'	=> _MI_PROFILE_TRIBES,
		'link'	=> 'admin/tribes.php',
		'icon'  => 'images/admin/tribes_big.png',
		'small' => 'images/admin/tribes_small.png');
	$adminmenu[] = array(
		'title'	=> _MI_PROFILE_TRIBEUSERS,
		'link'	=> 'admin/tribeuser.php',
		'icon'  => 'images/admin/tribeusers_big.png',
		'small' => 'images/admin/tribeusers_small.png');
	$adminmenu[] = array(
		'title'	=> _MI_PROFILE_AUDIOS,
		'link'	=> 'admin/audio.php',
		'icon'  => 'images/admin/audio_big.png',
		'small' => 'images/admin/audio_small.png');
	$adminmenu[] = array(
		'title'	=> _MI_PROFILE_VIDEOS,
		'link'	=> 'admin/videos.php',
		'icon'  => 'images/admin/videos_big.png',
		'small' => 'images/admin/videos_small.png');
}

require_once ICMS_ROOT_PATH . '/modules/system/constants.php';

$ugroups = is_object(icms::$user) ? icms::$user->getGroups() : array(ICMS_GROUP_ANONYMOUS);
$agroups = icms::handler('icms_member_groupperm')->getGroupIds('system_admin', XOOPS_SYSTEM_FINDU);
if (array_intersect($ugroups, $agroups)) {
	$adminmenu[] = array(
		'title'	=> _MI_PROFILE_FINDUSER,
		'link'	=> '../../modules/system/admin.php?fct=findusers',
		'icon'  => 'images/admin/findusers_big.png',
		'small' => 'images/admin/findusers_small.png');
}

if (isset($module)) {
	$headermenu[] = array(
		'title'	=> _PREFERENCES,
		'link'	=> ICMS_URL.'/modules/system/admin.php?fct=preferences&amp;op=showmod&amp;mod='.$module->getVar('mid'));
	$headermenu[] = array(
		'title'	=> _CO_ICMS_GOTOMODULE,
		'link'	=> ICMS_URL.'/modules/'.$module->getVar('dirname'));
	$headermenu[] = array(
		'title'	=> _CO_ICMS_UPDATE_MODULE,
		'link'	=> ICMS_URL.'/modules/system/admin.php?fct=modulesadmin&amp;op=update&amp;module='.$module->getVar('dirname'));
	$headermenu[] = array(
		'title' => _MODABOUT_ABOUT,
		'link'	=> ICMS_URL.'/modules/'.$module->getVar('dirname').'/admin/about.php');
}

unset($module);