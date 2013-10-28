<?php
/**
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package		System
 * @subpackage	Modules
 * @author	    Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @version		SVN: $Id: main.php 21379 2011-03-30 13:53:00Z m0nty_ $
 */

if (!is_object(icms::$user) || !is_object($icmsModule) || !icms::$user->isAdmin($icmsModule->getVar('mid'))) {
	exit("Access Denied");
}

$icmsAdminTpl = new icms_view_Tpl();

include_once ICMS_MODULES_PATH . "/system/admin/modulesadmin/modulesadmin.php";
icms_loadLanguageFile('system', 'blocksadmin', TRUE);
if (!empty($_POST)) foreach ($_POST as $k => $v) ${$k} = StopXSS($v);
if (!empty($_GET)) foreach ($_GET as $k => $v) ${$k} = StopXSS($v);
$op = (isset($_GET['op'])) ? trim(filter_input(INPUT_GET, 'op')) : ((isset($_POST['op'])) ? trim(filter_input(INPUT_POST, 'op')) : 'list');

if (in_array($op, array('submit', 'install_ok', 'update_ok', 'uninstall_ok'))) {
	if (!icms::$security->check()) {
		$op = 'list';
	}
}

if ($op == "list") {
	icms_cp_header();
	echo xoops_module_list();
	icms_cp_footer();
	exit();
}

if ($op == "confirm") {
	icms_cp_header();
	$error = array();
	if (!is_writable(ICMS_CACHE_PATH . '/')) {
		// attempt to chmod 666
		if (!chmod(ICMS_CACHE_PATH . '/', 0777)) {
			$error[] = sprintf(_MUSTWABLE, "<strong>" . ICMS_CACHE_PATH . '/</strong>');
		}
	}

	if (count($error) > 0) {
		icms_core_Message::error($error);
		echo "<p><a href='admin.php?fct=modulesadmin'>" . _MD_AM_BTOMADMIN . "</a></p>";
		icms_cp_footer();
		exit();
	}

	echo "<h4 style='text-align:" . _GLOBAL_LEFT . ";'>" . _MD_AM_PCMFM . "</h4>"
	. "<form action='admin.php' method='post'>"
	. "<input type='hidden' name='fct' value='modulesadmin' />"
	. "<input type='hidden' name='op' value='submit' />"
	. "<table width='100%' border='0' cellspacing='1' class='outer'>"
	. "<tr align='center'><th>" . _MD_AM_MODULE . "</th><th>" . _MD_AM_ACTION . "</th><th>" . _MD_AM_ORDER . "</th></tr>";
	$mcount = 0;
	foreach ($module as $mid) {
		if ($mcount % 2 != 0) {
			$class = 'odd';
		} else {
			$class = 'even';
		}
		echo '<tr class="' . $class . '"><td align="center">' . icms_core_DataFilter::stripSlashesGPC($oldname[$mid]);
		$newname[$mid] = trim(icms_core_DataFilter::stripslashesGPC($newname[$mid]));
		if ($newname[$mid] != $oldname[$mid]) {
			echo '&nbsp;&raquo;&raquo;&nbsp;<span style="color:#ff0000;font-weight:bold;">' . $newname[$mid] . '</span>';
		}
		echo '</td><td align="center">';
		if (isset($newstatus[$mid]) && $newstatus[$mid] ==1) {
			if ($oldstatus[$mid] == 0) {
				echo "<span style='color:#ff0000;font-weight:bold;'>" . _MD_AM_ACTIVATE . "</span>";
			} else {
				echo _MD_AM_NOCHANGE;
			}
		} else {
			$newstatus[$mid] = 0;
			if ($oldstatus[$mid] == 1) {
				echo "<span style='color:#ff0000;font-weight:bold;'>" . _MD_AM_DEACTIVATE . "</span>";
			} else {
				echo _MD_AM_NOCHANGE;
			}
		}
		echo "</td><td align='center'>";
		if ($oldweight[$mid] != $weight[$mid]) {
			echo "<span style='color:#ff0000;font-weight:bold;'>" . $weight[$mid] . "</span>";
		} else {
			echo $weight[$mid];
		}
		echo "<input type='hidden' name='module[]' value='". (int) $mid
		."' /><input type='hidden' name='oldname[" . $mid . "]' value='" . htmlspecialchars($oldname[$mid], ENT_QUOTES)
		."' /><input type='hidden' name='newname[" . $mid . "]' value='" . htmlspecialchars($newname[$mid], ENT_QUOTES)
		."' /><input type='hidden' name='oldstatus[" . $mid . "]' value='" . (int) $oldstatus[$mid]
		."' /><input type='hidden' name='newstatus[" . $mid . "]' value='" . (int) $newstatus[$mid]
		."' /><input type='hidden' name='oldweight[" . $mid . "]' value='" . (int) $oldweight[$mid]
		."' /><input type='hidden' name='weight[" . $mid . "]' value='" . (int) $weight[$mid]
		."' /></td></tr>";
	}

	echo "<tr class='foot' align='center'><td colspan='3'><input type='submit' value='"
	. _MD_AM_SUBMIT . "' />&nbsp;<input type='button' value='" . _MD_AM_CANCEL
	. "' onclick='location=\"admin.php?fct=modulesadmin\"' />" . icms::$security->getTokenHTML()
	. "</td></tr></table></form>";
	icms_cp_footer();
	exit();
}

if ($op == "submit") {
	$ret = array();
	$write = FALSE;
	foreach ($module as $mid) {
		if (isset($newstatus[$mid]) && $newstatus[$mid] ==1) {
			if ($oldstatus[$mid] == 0) {
				$ret[] = xoops_module_activate($mid);
			}
		} else {
			if ($oldstatus[$mid] == 1) {
				$ret[] = xoops_module_deactivate($mid);
			}
		}
		$newname[$mid] = trim($newname[$mid]);
		if ($oldname[$mid] != $newname[$mid] || $oldweight[$mid] != $weight[$mid]) {
			$ret[] = xoops_module_change($mid, $weight[$mid], $newname[$mid]);
			$write = TRUE;
		}
		flush();
	}
	if ($write) {
		$contents = impresscms_get_adminmenu();
		if (!xoops_module_write_admin_menu($contents)) {
			$ret[] = "<p>" . _MD_AM_FAILWRITE . "</p>";
		}
	}
	icms_cp_header();
	if (count($ret) > 0) {
		foreach ($ret as $msg) {
			if ($msg != '') {
				echo $msg;
			}
		}
	}
	echo "<br /><a href='admin.php?fct=modulesadmin'>" . _MD_AM_BTOMADMIN . "</a>";
	icms_cp_footer();
	exit();
}

if ($op == 'install') {
	$module_handler = icms::handler('icms_module');
	$mod =& $module_handler->create();
	$mod->loadInfoAsVar($module);
	if ($mod->getInfo('image') != FALSE && trim($mod->getInfo('image')) != '') {
		$msgs ='<img src="' . ICMS_MODULES_URL . '/' . $mod->getVar('dirname') . '/' . trim($mod->getInfo('image')) . '" alt="" />';
	}
	$msgs .= '<br /><span style="font-size:smaller;">' . $mod->getVar('name') . '</span><br /><br />' . _MD_AM_RUSUREINS;
	if (empty($from_112)) {
		$from_112 = FALSE;
	}
	icms_cp_header();
	icms_core_Message::confirm(array('module' => $module, 'op' => 'install_ok', 'fct' => 'modulesadmin', 'from_112' => $from_112), 'admin.php', $msgs, _MD_AM_INSTALL);
	icms_cp_footer();
	exit();
}

if ($op == 'install_ok') {
	$ret = array();
	$ret[] = xoops_module_install($module);
	if ($from_112) {
		$ret[] = icms_module_update($module);
	}
	$contents = impresscms_get_adminmenu();
	if (!xoops_module_write_admin_menu($contents)) {
		$ret[] = "<p>" . _MD_AM_FAILWRITE . "</p>";
	}
	icms_cp_header();
	if (count($ret) > 0) {
		foreach ($ret as $msg) {
			if ($msg != '') {
				echo $msg;
			}
		}
	}
	echo "<br /><a href='admin.php?fct=modulesadmin'>" . _MD_AM_BTOMADMIN . "</a>";
	icms_cp_footer();
	exit();
}

if ($op == 'uninstall') {
	$module_handler = icms::handler('icms_module');
	$mod =& $module_handler->getByDirname($module);
	$mod->registerClassPath();

	if ($mod->getInfo('image') != FALSE && trim($mod->getInfo('image')) != '') {
		$msgs ='<img src="' . ICMS_MODULES_URL . '/' . $mod->getVar('dirname') . '/' . trim($mod->getInfo('image')) . '" alt="" />';
	}
	$msgs .= '<br /><span style="font-size:smaller;">' . $mod->getVar('name') . '</span><br /><br />' . _MD_AM_RUSUREUNINS;
	icms_cp_header();
	icms_core_Message::confirm(array('module' => $module, 'op' => 'uninstall_ok', 'fct' => 'modulesadmin'), 'admin.php', $msgs, _YES);
	icms_cp_footer();
	exit();
}

if ($op == 'uninstall_ok') {
	$ret = array();
	$ret[] = xoops_module_uninstall($module);
	$contents = impresscms_get_adminmenu();
	if (!xoops_module_write_admin_menu($contents)) {
		$ret[] = "<p>" . _MD_AM_FAILWRITE . "</p>";
	}
	icms_cp_header();
	if (count($ret) > 0) {
		foreach ($ret as $msg) {
			if ($msg != '') {
				echo $msg;
			}
		}
	}
	echo "<a href='admin.php?fct=modulesadmin'>" . _MD_AM_BTOMADMIN . "</a>";
	icms_cp_footer();
	exit();
}

if ($op == 'update') {
	$module_handler = icms::handler('icms_module');
	$mod =& $module_handler->getByDirname($module);
	if ($mod->getInfo('image') != FALSE && trim($mod->getInfo('image')) != '') {
		$msgs ='<img src="' . ICMS_MODULES_URL . '/' . $mod->getVar('dirname') . '/' . trim($mod->getInfo('image')) . '" alt="" />';
	}
	$msgs .= '<br /><span style="font-size:smaller;">' . $mod->getVar('name') . '</span><br /><br />' . _MD_AM_RUSUREUPD;
	icms_cp_header();

	if (icms_getModuleInfo('system')->getDBVersion() < 14 && (!is_writable(ICMS_PLUGINS_PATH) || !is_dir(ICMS_ROOT_PATH . '/plugins/preloads') || !is_writable(ICMS_ROOT_PATH . '/plugins/preloads'))) {
		icms_core_Message::error(sprintf(_MD_AM_PLUGINSFOLDER_UPDATE_TEXT, ICMS_PLUGINS_PATH,ICMS_ROOT_PATH . '/plugins/preloads'), _MD_AM_PLUGINSFOLDER_UPDATE_TITLE, TRUE);
	}
	if (icms_getModuleInfo('system')->getDBVersion() < 37 && !is_writable(ICMS_IMANAGER_FOLDER_PATH)) {
		icms_core_Message::error(sprintf(_MD_AM_IMAGESFOLDER_UPDATE_TEXT, str_ireplace(ICMS_ROOT_PATH, "", ICMS_IMANAGER_FOLDER_PATH)), _MD_AM_IMAGESFOLDER_UPDATE_TITLE, TRUE);
	}

	icms_core_Message::confirm(array('module' => $module, 'op' => 'update_ok', 'fct' => 'modulesadmin'), 'admin.php', $msgs, _MD_AM_UPDATE);
	icms_cp_footer();
	exit();
}

if ($op == 'update_ok') {
	$ret = array();
	$ret[] = icms_module_update($module);
	$contents = impresscms_get_adminmenu();
	if (!xoops_module_write_admin_menu($contents)) {
		$ret[] = "<p>" . _MD_AM_FAILWRITE . "</p>";
	}
	icms_cp_header();
	if (count($ret) > 0) {
		foreach ($ret as $msg) {
			if ($msg != '') {
				echo $msg;
			}
		}
	}
	echo "<br /><a href='admin.php?fct=modulesadmin'>" . _MD_AM_BTOMADMIN . "</a>";
	icms_cp_footer();
	exit();
}

