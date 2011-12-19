<?php
/**
* @copyright	http://www.xoops.org/ The XOOPS Project
* @copyright	XOOPS_copyrights.txt
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @package		Administration
* @since		XOOPS
* @author		http://www.xoops.org The XOOPS Project
* @author	    Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
* @version		$Id: main.php 9607 2009-11-27 17:55:28Z realtherplima $
*/

if ( !is_object($icmsUser) || !is_object($icmsModule) || !$icmsUser->isAdmin($icmsModule->mid()) ) {
    exit("Access Denied");
}
include_once XOOPS_ROOT_PATH."/modules/system/admin/modulesadmin/modulesadmin.php";
require_once XOOPS_ROOT_PATH."/class/xoopslists.php";
icms_loadLanguageFile('system', 'blocksadmin', true);
if(!empty($_POST)) foreach($_POST as $k => $v) ${$k} = StopXSS($v);
if(!empty($_GET)) foreach($_GET as $k => $v) ${$k} = StopXSS($v);
$op = (isset($_GET['op']))?trim(StopXSS($_GET['op'])):((isset($_POST['op']))?trim(StopXSS($_POST['op'])):'list');


if (in_array($op, array('submit', 'install_ok', 'update_ok', 'uninstall_ok'))) {
	if (!$GLOBALS['xoopsSecurity']->check()) {
		$op = 'list';
	}
}

if ( $op == "list" ) {
	xoops_cp_header();
	echo xoops_module_list();
	xoops_cp_footer();
	exit();
}

if ( $op == "confirm" ) {
	xoops_cp_header();
	//OpenTable();
	$error = array();
	if ( !is_writable(XOOPS_CACHE_PATH.'/') ) {
		// attempt to chmod 666
		if ( !chmod(XOOPS_CACHE_PATH.'/', 0777) ) {
			$error[] = sprintf(_MUSTWABLE, "<b>".XOOPS_CACHE_PATH.'/</b>');
		}
	}

	if ( count($error) > 0 ) {
		xoops_error($error);
		echo "<p><a href='admin.php?fct=modulesadmin'>"._MD_AM_BTOMADMIN."</a></p>";
		xoops_cp_footer();
		exit();
	}

	echo "<h4 style='text-align:"._GLOBAL_LEFT.";'>"._MD_AM_PCMFM."</h4>
	<form action='admin.php' method='post'>
	<input type='hidden' name='fct' value='modulesadmin' />
	<input type='hidden' name='op' value='submit' />
	<table width='100%' border='0' cellspacing='1' class='outer'>
	<tr align='center'><th>"._MD_AM_MODULE."</th><th>"._MD_AM_ACTION."</th><th>"._MD_AM_ORDER."</th></tr>";
	$mcount = 0;
	$myts =& MyTextsanitizer::getInstance();
	foreach ($module as $mid) {
		if ($mcount % 2 != 0) {
			$class = 'odd';
		} else {
			$class = 'even';
		}
		echo '<tr class="'.$class.'"><td align="center">'.$myts->stripSlashesGPC($oldname[$mid]);
		$newname[$mid] = trim($myts->stripslashesGPC($newname[$mid]));
		if ($newname[$mid] != $oldname[$mid]) {
			echo '&nbsp;&raquo;&raquo;&nbsp;<span style="color:#ff0000;font-weight:bold;">'.$newname[$mid].'</span>';
		}
		echo '</td><td align="center">';
		if (isset($newstatus[$mid]) && $newstatus[$mid] ==1) {
			if ($oldstatus[$mid] == 0) {
				echo "<span style='color:#ff0000;font-weight:bold;'>"._MD_AM_ACTIVATE."</span>";
			} else {
				echo _MD_AM_NOCHANGE;
			}
		} else {
			$newstatus[$mid] = 0;
			if ($oldstatus[$mid] == 1) {
				echo "<span style='color:#ff0000;font-weight:bold;'>"._MD_AM_DEACTIVATE."</span>";
			} else {
				echo _MD_AM_NOCHANGE;
			}
		}
		echo "</td><td align='center'>";
		if ($oldweight[$mid] != $weight[$mid]) {
			echo "<span style='color:#ff0000;font-weight:bold;'>".$weight[$mid]."</span>";
		} else {
			echo $weight[$mid];
		}
		echo "
		<input type='hidden' name='module[]' value='".intval($mid)."' />
		<input type='hidden' name='oldname[".$mid."]' value='".htmlspecialchars($oldname[$mid], ENT_QUOTES)."' />
		<input type='hidden' name='newname[".$mid."]' value='".htmlspecialchars($newname[$mid], ENT_QUOTES)."' />
		<input type='hidden' name='oldstatus[".$mid."]' value='".intval($oldstatus[$mid])."' />
		<input type='hidden' name='newstatus[".$mid."]' value='".intval($newstatus[$mid])."' />
		<input type='hidden' name='oldweight[".$mid."]' value='".intval($oldweight[$mid])."' />
		<input type='hidden' name='weight[".$mid."]' value='".intval($weight[$mid])."' />
		</td></tr>";
	}

	echo "
	<tr class='foot' align='center'><td colspan='3'><input type='submit' value='"._MD_AM_SUBMIT."' />&nbsp;<input type='button' value='"._MD_AM_CANCEL."' onclick='location=\"admin.php?fct=modulesadmin\"' />".$GLOBALS['xoopsSecurity']->getTokenHTML()."</td></tr>
	</table>
	</form>";
	xoops_cp_footer();
	exit();
}

if ( $op == "submit" ) {
	$ret = array();
	$write = false;
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
			$write = true;
		}
		flush();
	}
	if ( $write ) {
		$contents = xoops_module_get_admin_menu();
		if (!xoops_module_write_admin_menu($contents)) {
			$ret[] = "<p>"._MD_AM_FAILWRITE."</p>";
		}
	}
	xoops_cp_header();
	if ( count($ret) > 0 ) {
		foreach ($ret as $msg) {
			if ($msg != '') {
				echo $msg;
			}
		}
	}
	echo "<br /><a href='admin.php?fct=modulesadmin'>"._MD_AM_BTOMADMIN."</a>";
	xoops_cp_footer();
	exit();
}

if ($op == 'install') {
	$module_handler =& xoops_gethandler('module');
	$mod =& $module_handler->create();
	$mod->loadInfoAsVar($module);
	if ($mod->getInfo('image') != false && trim($mod->getInfo('image')) != '') {
		$msgs ='<img src="'.XOOPS_URL.'/modules/'.$mod->getVar('dirname').'/'.trim($mod->getInfo('image')).'" alt="" />';
	}
	$msgs .= '<br /><span style="font-size:smaller;">'.$mod->getVar('name').'</span><br /><br />'._MD_AM_RUSUREINS;
	if (empty($from_112)){
		$from_112 = false;
	}
	xoops_cp_header();
	xoops_confirm(array('module' => $module, 'op' => 'install_ok', 'fct' => 'modulesadmin', 'from_112' => $from_112), 'admin.php', $msgs, _MD_AM_INSTALL);
	xoops_cp_footer();
	exit();
}

if ($op == 'install_ok') {
	$ret = array();
	$ret[] = xoops_module_install($module);
	if ($from_112){
		$ret[] = icms_module_update($module);
	}
	$contents = xoops_module_get_admin_menu();
	if (!xoops_module_write_admin_menu($contents)) {
		$ret[] = "<p>"._MD_AM_FAILWRITE."</p>";
	}
	xoops_cp_header();
	if (count($ret) > 0) {
		foreach ($ret as $msg) {
			if ($msg != '') {
				echo $msg;
			}
		}
	}
	echo "<br /><a href='admin.php?fct=modulesadmin'>"._MD_AM_BTOMADMIN."</a>";
	xoops_cp_footer();
	exit();
}

if ($op == 'uninstall') {
	$module_handler =& xoops_gethandler('module');
	$mod =& $module_handler->getByDirname($module);
	if ($mod->getInfo('image') != false && trim($mod->getInfo('image')) != '') {
		$msgs ='<img src="'.XOOPS_URL.'/modules/'.$mod->getVar('dirname').'/'.trim($mod->getInfo('image')).'" alt="" />';
	}
	$msgs .= '<br /><span style="font-size:smaller;">'.$mod->getVar('name').'</span><br /><br />'._MD_AM_RUSUREUNINS;
	xoops_cp_header();
	xoops_confirm(array('module' => $module, 'op' => 'uninstall_ok', 'fct' => 'modulesadmin'), 'admin.php', $msgs, _YES);
	xoops_cp_footer();
	exit();
}

if ($op == 'uninstall_ok') {
	$ret = array();
	$ret[] = xoops_module_uninstall($module);
	$contents = xoops_module_get_admin_menu();
	if (!xoops_module_write_admin_menu($contents)) {
		$ret[] = "<p>"._MD_AM_FAILWRITE."</p>";
	}
	xoops_cp_header();
	if (count($ret) > 0) {
		foreach ($ret as $msg) {
			if ($msg != '') {
				echo $msg;
			}
		}
	}
	echo "<a href='admin.php?fct=modulesadmin'>"._MD_AM_BTOMADMIN."</a>";
	xoops_cp_footer();
	exit();
}

if ($op == 'update') {
	$module_handler =& xoops_gethandler('module');
	$mod =& $module_handler->getByDirname($module);
	if ($mod->getInfo('image') != false && trim($mod->getInfo('image')) != '') {
		$msgs ='<img src="'.XOOPS_URL.'/modules/'.$mod->getVar('dirname').'/'.trim($mod->getInfo('image')).'" alt="" />';
	}
	$msgs .= '<br /><span style="font-size:smaller;">'.$mod->getVar('name').'</span><br /><br />'._MD_AM_RUSUREUPD;
	xoops_cp_header();


	if (icms_getModuleInfo('system')->getDBVersion() < 14 && (!is_writable ( ICMS_PLUGINS_PATH ) || !is_dir(ICMS_ROOT_PATH . '/plugins/preloads') || !is_writable ( ICMS_ROOT_PATH . '/plugins/preloads' ))) {
		  icms_error_msg(sprintf(_MD_AM_PLUGINSFOLDER_UPDATE_TEXT, ICMS_PLUGINS_PATH,ICMS_ROOT_PATH . '/plugins/preloads'), _MD_AM_PLUGINSFOLDER_UPDATE_TITLE, true);
	}
	if (icms_getModuleInfo('system')->getDBVersion() < 37 && !is_writable ( ICMS_IMANAGER_FOLDER_PATH )) {
		  icms_error_msg(sprintf(_MD_AM_IMAGESFOLDER_UPDATE_TEXT, ICMS_IMANAGER_FOLDER_PATH), _MD_AM_IMAGESFOLDER_UPDATE_TITLE, true);
	}

	xoops_confirm(array('module' => $module, 'op' => 'update_ok', 'fct' => 'modulesadmin'), 'admin.php', $msgs, _MD_AM_UPDATE);
	xoops_cp_footer();
	exit();
}

if ($op == 'update_ok') {
	$ret = array();
	$ret[] = icms_module_update($module);
	$contents = xoops_module_get_admin_menu();
	if (!xoops_module_write_admin_menu($contents)) {
		$ret[] = "<p>"._MD_AM_FAILWRITE."</p>";
	}
	xoops_cp_header();
	if (count($ret) > 0) {
		foreach ($ret as $msg) {
			if ($msg != '') {
				echo $msg;
			}
		}
	}
	echo "<br /><a href='admin.php?fct=modulesadmin'>"._MD_AM_BTOMADMIN."</a>";
	xoops_cp_footer();
	exit();
}

?>