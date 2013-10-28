<?php
/**
 * The beginning of the admin interface for ImpressCMS
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		LICENSE.txt
 * @package		Administration
 * @subpackage	System
 * @version		SVN: $Id: admin.php 22256 2011-08-18 14:47:09Z phoenyx $
 */

define('ICMS_IN_ADMIN', 1);

include_once '../../include/functions.php';
if (!empty($_POST)) foreach ($_POST as $k => $v) ${$k} = StopXSS($v);
if (!empty($_GET)) foreach ($_GET as $k => $v) ${$k} = StopXSS($v);
$fct = (isset($_GET['fct']))
	? trim(filter_input(INPUT_GET, 'fct'))
	: ((isset($_POST['fct']))
		? trim(filter_input(INPUT_POST, 'fct'))
		: '');

if (isset($fct) && $fct == 'users') {$xoopsOption['pagetype'] = 'user';}
include '../../mainfile.php';
include ICMS_ROOT_PATH . '/include/cp_functions.php';
icms_loadLanguageFile('system', 'admin');
icms_loadLanguageFile('core', 'moduleabout');

// hook for profile module
if (isset($fct) && $fct == 'users' && icms_get_module_status('profile')) {
	$op = isset($_GET['op']) ? filter_input(INPUT_GET, 'op') : '';
	$uid = isset($_GET['uid']) ? filter_input(INPUT_GET, 'uid') : 0;
	if ($op == 'modifyUser' && $uid != 0) {
		header("Location:" . ICMS_MODULES_URL . "/profile/admin/user.php?op=edit&id=" . $uid);
	} else {
		header("Location:" . ICMS_MODULES_URL . "/profile/admin/user.php");
	}
}

// Check if function call does exist (security)
$admin_dir = ICMS_ROOT_PATH . '/modules/system/admin';
$dirlist = icms_core_Filesystem::getDirList($admin_dir);
if ($fct && !in_array($fct, $dirlist)) {redirect_header(ICMS_URL . '/', 3, _INVALID_ADMIN_FUNCTION);}
$admintest = 0;

if (is_object(icms::$user)) {
	$icmsModule = icms::handler('icms_module')->getByDirname('system');
	if (!icms::$user->isAdmin($icmsModule->getVar('mid'))) {
		redirect_header(ICMS_URL . '/', 3, _NOPERM);
	}
	$admintest = 1;
} else {redirect_header(ICMS_URL . '/', 3, _NOPERM);}

// include system category definitions
include_once ICMS_ROOT_PATH . '/modules/system/constants.php';
$error = FALSE;
if ($admintest != 0) {
	if (isset($fct) && $fct != '') {
		if (file_exists(ICMS_ROOT_PATH . '/modules/system/admin/' . $fct . '/icms_version.php')) {
			$icms_version = 'icms_version';
		} elseif (file_exists(ICMS_ROOT_PATH . '/modules/system/admin/' . $fct . '/xoops_version.php')) {
			$icms_version = 'xoops_version';
		}
		if (isset($icms_version) && $icms_version !== '') {
			icms_loadLanguageFile('system', $fct, TRUE);
			include ICMS_ROOT_PATH . '/modules/system/admin/' . $fct . '/' . $icms_version . '.php';
			$sysperm_handler = icms::handler('icms_member_groupperm');
			$category = !empty($modversion['category']) ? (int) $modversion['category'] : 0;
			unset($modversion);
			if ($category > 0) {
				$groups =& icms::$user->getGroups();
				if (in_array(XOOPS_GROUP_ADMIN, $groups) 
					|| FALSE !== $sysperm_handler->checkRight('system_admin', $category, $groups, $icmsModule->getVar('mid'))) 
					{
					if (file_exists(ICMS_ROOT_PATH . '/modules/system/admin/' . $fct . '/main.php')) {
						include_once ICMS_ROOT_PATH . '/modules/system/admin/' . $fct . '/main.php';
					} else {$error = TRUE;}
				} else {$error = TRUE;}
			} elseif ($fct == 'version') {
				if (file_exists(ICMS_ROOT_PATH . '/modules/system/admin/version/main.php')) {
					include_once ICMS_ROOT_PATH . '/modules/system/admin/version/main.php';
				} else {$error = TRUE;}
			} else {$error = TRUE;}
		} else {$error = TRUE;}
	} else {$error = TRUE;}
}
if ($error) {
	header("Location:" . ICMS_URL . "/admin.php");
}
