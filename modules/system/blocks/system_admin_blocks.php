<?php
/**
 * System Admin Blocks File
 *
 * @copyright	The ImpressCMS Project <http://www.impresscms.org/>
 * @license		LICENSE.txt
 * @package		System
 * @subpackage	Blocks
 * @since		ImpressCMS 1.2
 * @version		SVN: $Id: system_admin_blocks.php 21392 2011-03-30 16:45:08Z m0nty_ $
 */

/**
 * Admin Warnings Block
 *
 * @since ImpressCMS 1.2
 * @author Gustavo Pilla (aka nekro) <gpilla@nubee.com.ar>
 * @return array
 * @todo This code is the copy of the one wich was in the admin.php, it should be improved.
 */
function b_system_admin_warnings_show() {
	$block = array();
	$block['msg'] = array();
	// ###### Output warn messages for security  ######
	if (is_dir(ICMS_ROOT_PATH . '/install/')) {
		array_push($block['msg'], icms_core_Message::error(sprintf(_WARNINSTALL2, ICMS_ROOT_PATH . '/install/'), '', FALSE));
	}
	/** @todo make this dynamic, so the value is updated automatically */
	if (getDbValue(icms::$xoopsDB, 'modules', 'version', 'version="120" AND mid="1"') !== FALSE) {
		array_push($block['msg'], icms_core_Message::error('<a href="' . ICMS_MODULES_URL . '/system/admin.php?fct=modulesadmin&amp;op=update&amp;module=system">' . _WARNINGUPDATESYSTEM . '</a>'));
	}
	if (is_writable(ICMS_ROOT_PATH . '/mainfile.php')) {
		array_push($block['msg'], icms_core_Message::error(sprintf(_WARNINWRITEABLE, ICMS_ROOT_PATH . '/mainfile.php'), '', FALSE));
	}
	if (is_dir(ICMS_ROOT_PATH . '/upgrade/')) {
		array_push($block['msg'], icms_core_Message::error(sprintf(_WARNINSTALL2, ICMS_ROOT_PATH . '/upgrade/'), '', FALSE));
	}
	if (!is_dir(XOOPS_TRUST_PATH)) {
		array_push($block['msg'], icms_core_Message::error(_TRUST_PATH_HELP));
	}
	$sql1 = "SELECT conf_modid FROM `" . icms::$xoopsDB->prefix('config') . "` WHERE conf_name = 'dos_skipmodules'";
	if ($result1 = icms::$xoopsDB->query($sql1)) {
		list($modid) = icms::$xoopsDB->FetchRow($result1);
		$protector_is_active = '0';
		if (NULL !== $modid) {
			$sql2 = "SELECT isactive FROM `" . icms::$xoopsDB->prefix('modules') . "` WHERE mid =" . $modid;
			$result2 = icms::$xoopsDB->query($sql2);
			list($protector_is_active) = icms::$xoopsDB->FetchRow($result2);
		}
	}
	if (file_exists(ICMS_PLUGINS_PATH . '/csstidy/css_optimiser.php')) {
		array_push($block['msg'], icms_core_Message::error(sprintf(_CSSTIDY_VULN, ICMS_PLUGINS_PATH . '/csstidy/css_optimiser.php'), FALSE));
	}

	if ($protector_is_active == 0) {
		array_push($block['msg'], icms_core_Message::error(_PROTECTOR_NOT_FOUND, '', FALSE));
		echo '<br />';
	}

	// ###### Output warn messages for correct functionality  ######
	if (!is_writable(ICMS_CACHE_PATH))
		array_push($block['msg'], icms_core_Message::warning(sprintf(_WARNINNOTWRITEABLE, ICMS_CACHE_PATH)), '', FALSE);
	if (!is_writable(ICMS_UPLOAD_PATH))
		array_push($block['msg'], icms_core_Message::warning(sprintf(_WARNINNOTWRITEABLE, ICMS_UPLOAD_PATH)), '', FALSE);
	if (!is_writable(ICMS_COMPILE_PATH))
		array_push($block['msg'], icms_core_Message::warning(sprintf(_WARNINNOTWRITEABLE, ICMS_COMPILE_PATH)), '', FALSE);

	if (count($block['msg']) > 0) {
		return $block;
	}

}

/**
 * Admin Control Panel Block
 *
 * @return array
 * @todo This code is the copy of the one wich was in the admin.php, it should be improved.
 */
function b_system_admin_cp_show() {
	global $icmsTpl, $icmsConfig;

	$block['lang_cp']= _CPHOME;
	$block['lang_insmodules'] = _AD_INSTALLEDMODULES;

	// Loading System Configuration Links
	if (is_object(icms::$user)) {
		$groups = icms::$user->getGroups();
	} else {
		$groups = array();
	}
	$all_ok = FALSE;
	if (!in_array(XOOPS_GROUP_ADMIN, $groups)) {
		$sysperm_handler = icms::handler('icms_member_groupperm');
		$ok_syscats =& $sysperm_handler->getItemIds('system_admin', $groups);
	} else {$all_ok = TRUE;}

	require_once ICMS_MODULES_PATH . '/system/constants.php';

	$admin_dir = ICMS_MODULES_PATH . '/system/admin';
	$dirlist = icms_core_Filesystem::getDirList($admin_dir);

	icms_loadLanguageFile('system', 'admin');
	asort($dirlist);
	$block['sysmod'] = array();
	foreach ($dirlist as $file) {
		$mod_version_file = 'xoops_version.php';
		if (file_exists($admin_dir . '/' . $file . '/icms_version.php')) {
			$mod_version_file = 'icms_version.php';
		}
		include $admin_dir . '/' . $file . '/' . $mod_version_file;
		if ($modversion['hasAdmin']) {
			$category = isset($modversion['category']) ? (int) ($modversion['category']) : 0;
			if (FALSE != $all_ok || in_array($modversion['category'], $ok_syscats)) {
				$sysmod = array('title' => $modversion['name'], 'link' => ICMS_MODULES_URL . '/system/admin.php?fct=' . $file, 'image' => ICMS_MODULES_URL . '/system/admin/' . $file . '/images/' . $file . '_big.png');
				array_push($block['sysmod'], $sysmod);
			}
		}
		unset($modversion);
	}
	if (count($block['sysmod']) > 0)
	return $block;
}

/**
 * System Admin Modules Block Show Fuction
 *
 * @return array
 * @todo Maybe it can be improved a little, is just a copy of the generate menu function.
 */
function b_system_admin_modules_show() {
	$block['mods'] = array();
	$module_handler = icms::handler('icms_module');
	$moduleperm_handler = icms::handler('icms_member_groupperm');
	$criteria = new icms_db_criteria_Compo();
	$criteria->add(new icms_db_criteria_Item('hasadmin', 1));
	$criteria->add(new icms_db_criteria_Item('isactive', 1));
	$criteria->setSort('mid');
	$modules = $module_handler->getObjects($criteria);
	foreach ($modules as $module) {
		$rtn = array();
		$inf = & $module->getInfo();
		$rtn['link'] = ICMS_MODULES_URL . '/' . $module->getVar('dirname') . '/' . (isset($inf['adminindex']) ? $inf['adminindex'] : '');
		$rtn['title'] = $module->getVar('name');
		$rtn['dir'] = $module->getVar('dirname');
		if (isset($inf['iconsmall']) && $inf['iconsmall'] != '') {
			$rtn['small'] = ICMS_MODULES_URL . '/' . $module->getVar('dirname') . '/' . $inf['iconsmall'];
		}
		if (isset($inf['iconbig']) && $inf['iconbig'] != '') {
			$rtn['iconbig'] = ICMS_MODULES_URL . '/' . $module->getVar('dirname') . '/' . $inf['iconbig'];
		}
		$rtn['absolute'] = 1;
		$module->loadAdminMenu();
		if (is_array($module->adminmenu) && count($module->adminmenu) > 0) {
			$rtn['hassubs'] = 1;
			$rtn['subs'] = array();
			foreach ($module->adminmenu as $item) {
				$item['link'] = ICMS_MODULES_URL . '/' . $module->getVar('dirname') . '/' . $item['link'];
				$rtn['subs'][] = $item;
			}
		} else {
			$rtn['hassubs'] = 0;
			unset($rtn['subs']);
		}
		$hasconfig = $module->getVar('hasconfig');
		$hascomments = $module->getVar('hascomments');
		if ((isset($hasconfig) && $hasconfig == 1) || (isset($hascomments) && $hascomments == 1)) {
			$rtn['hassubs'] = 1;
			if (! isset($rtn['subs'])) {
				$rtn['subs'] = array();
			}
			$subs = array('title' => _PREFERENCES, 'link' => ICMS_MODULES_URL . '/system/admin.php?fct=preferences&amp;op=showmod&amp;mod=' . $module->getVar('mid'));
			$rtn['subs'][] = $subs;
		} else {
			$rtn['hassubs'] = 0;
			unset($rtn['subs']);
		}
		if ($module->getVar('dirname') == 'system') {
			$systemadm = TRUE;
		}
		if (is_object(icms::$user))
		$admin_perm = $moduleperm_handler->checkRight('module_admin', $module->getVar('mid'), icms::$user->getGroups());
		if ($admin_perm) {
			if ($rtn['dir'] != 'system') {
				$block['mods'][] = $rtn;
			}
		}

	}

	// If there is any module listed, then show the block.
	if (count($block['mods'] > 0))
	return $block;
}

/**
 * New Admin Control Panel Block, with grouping of items
 *
 * @since ImpressCMS 1.3
 * @return array
 */
function b_system_admin_cp_new_show() {
	global $icmsTpl, $icmsConfig;

	$block['lang_cp']= _CPHOME;

	// Loading System Configuration Links
	if (is_object(icms::$user)) {
		$groups = icms::$user->getGroups();
	} else {
		$groups = array();
	}
	$all_ok = FALSE;
	if (!in_array(ICMS_GROUP_ADMIN, $groups)) {
		$sysperm_handler = icms::handler('icms_member_groupperm');
		$ok_syscats =& $sysperm_handler->getItemIds('system_admin', $groups);
	} else {
		$all_ok = TRUE;
	}

	require_once ICMS_MODULES_PATH . '/system/constants.php';

	$admin_dir = ICMS_MODULES_PATH . '/system/admin';
	$dirlist = icms_core_Filesystem::getDirList($admin_dir);

	icms_loadLanguageFile('system', 'admin');
	asort($dirlist);
	$block = array();
	foreach ($dirlist as $file) {
		$mod_version_file = 'xoops_version.php';
		if (file_exists($admin_dir . '/' . $file . '/icms_version.php')) {
			$mod_version_file = 'icms_version.php';
		}
		include $admin_dir . '/' . $file . '/' . $mod_version_file;
		if ($modversion['hasAdmin']) {
			$category = isset($modversion['category']) ? (int) ($modversion['category']) : 0;
			if (FALSE != $all_ok || in_array($modversion['category'], $ok_syscats)) {
				$block[$modversion['group']][] = array(
					'title' => $modversion['name'],
					'link' => ICMS_MODULES_URL . '/system/admin.php?fct=' . $file,
					'image' => ICMS_MODULES_URL . '/system/admin/' . $file . '/images/' . $file . '_big.png',
				);
			}
		}
		unset($modversion);
	}
	if (count($block) > 0) {
		ksort($block);
		return $block;
	}
}
