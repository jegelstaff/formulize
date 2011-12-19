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
 * @version         $Id$
 */


$i = 0;

$i++;
$adminmenu[$i]['title'] = _PROFILE_MI_USERS;
$adminmenu[$i]['link'] = 'admin/user.php';
$i++;
$adminmenu[$i]['title'] = _AM_PROFILE_CATEGORYS;
$adminmenu[$i]['link'] = 'admin/category.php';
$i++;
$adminmenu[$i]['title'] = _AM_PROFILE_FIELDS;
$adminmenu[$i]['link'] = 'admin/field.php';
$i++;
$adminmenu[$i]['title'] = _AM_PROFILE_REGSTEPS;
$adminmenu[$i]['link'] = 'admin/regstep.php';
$i++;
$adminmenu[$i]['title'] = _MI_PROFILE_VISIBILITY;
$adminmenu[$i]['link'] = 'admin/visibility.php';
$i++;
$adminmenu[$i]['title'] = _PROFILE_MI_PERMISSIONS;
$adminmenu[$i]['link'] = 'admin/permissions.php';
$i++;
$adminmenu[$i]['title'] = _MI_PROFILE_PICTURES;
$adminmenu[$i]['link'] = 'admin/pictures.php';
$i++;
$adminmenu[$i]['title'] = _MI_PROFILE_TRIBES;
$adminmenu[$i]['link'] = 'admin/tribes.php';
$i++;
$adminmenu[$i]['title'] = _MI_PROFILE_TRIBEUSERS;
$adminmenu[$i]['link'] = 'admin/tribeuser.php';
$i++;
$adminmenu[$i]['title'] = _MI_PROFILE_AUDIOS;
$adminmenu[$i]['link'] = 'admin/audio.php';
$i++;
$adminmenu[$i]['title'] = _MI_PROFILE_VIDEOS;
$adminmenu[$i]['link'] = 'admin/videos.php';

$gperm =& xoops_gethandler ( 'groupperm' );
$icmsUser = $GLOBALS['xoopsUser'];
$ugroups = is_object($icmsUser) ? $icmsUser->getGroups() : array(ICMS_GROUP_ANONYMOUS);
$agroups = $gperm->getGroupIds('system_admin',7); //ICMS_SYSTEM_BLOCK constant not available?
if (array_intersect($ugroups, $agroups)) {
$i++;
$adminmenu[$i]['title'] = _PROFILE_MI_FINDUSER;
$adminmenu[$i]['link'] = '../system/admin.php?fct=findusers';
}
global $icmsModule;
if (isset($icmsModule)) {

	$i = -1;

	$i++;
	$headermenu[$i]['title'] = _PREFERENCES;
	$headermenu[$i]['link'] = '../../system/admin.php?fct=preferences&amp;op=showmod&amp;mod=' . $icmsModule->getVar('mid');

	$i++;
	$headermenu[$i]['title'] = _CO_ICMS_GOTOMODULE;
	$headermenu[$i]['link'] = ICMS_URL.'/modules/'.$icmsModule->getVar('dirname') . '/';

	$i++;
	$headermenu[$i]['title'] = _CO_ICMS_UPDATE_MODULE;
	$headermenu[$i]['link'] = ICMS_URL . '/modules/system/admin.php?fct=modulesadmin&amp;op=update&amp;module=' . $icmsModule->getVar('dirname');

	$i++;
	$headermenu[$i]['title'] = _MODABOUT_ABOUT;
	$headermenu[$i]['link'] = ICMS_URL . '/modules/'.$icmsModule->getVar('dirname').'/admin/about.php';
}
?>