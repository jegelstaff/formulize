<?php
/**
 * System Admin Blocks File
 *
 * @copyright	The ImpressCMS Project <http://www.impresscms.org/>
 * @license	LICENSE.txt
 * @package	SystemBlocks
 * @since	ImpressCMS 1.2
 * @version	$Id: system_admin_blocks.php 9843 2010-02-19 15:58:30Z skenow $
 * @author	Gustavo Pilla (aka nekro) <nekro@impresscms.org> <gpilla@nubee.com.ar>
 */

/**
 * Admin Warnings Block
 *
 * @copyright The ImpressCMS Project <http://www.impresscms.org>
 * @license GNU GPL v2
 *
 * @since ImpressCMS 1.2
 *
 * @author Gustavo Pilla (aka nekro) <nekro@impresscms.org> <gpilla@nubee.com.ar>
 *
 * @return array
 *
 * @todo This code is the copy of the one wich was in the admin.php, it should be improved.
 */
function b_system_admin_warnings_show(){

	global $xoopsDB;
	$block = array();
	$block['msg'] = array();
	// ###### Output warn messages for security  ######
	if(is_dir(ICMS_ROOT_PATH.'/install/')){
		array_push($block['msg'], icms_error_msg(sprintf(_WARNINSTALL2,ICMS_ROOT_PATH.'/install/'), '', false));
	}
	/** @todo make this dynamic, so the value is updated automatically */
	if(getDbValue($xoopsDB, 'modules', 'version', 'version="120" AND mid="1"') !== FALSE ){
		array_push($block['msg'], icms_error_msg('<a href="'.ICMS_URL.'/modules/system/admin.php?fct=modulesadmin&amp;op=update&amp;module=system">'._WARNINGUPDATESYSTEM.'</a>'));
	}
	if(is_writable(ICMS_ROOT_PATH.'/mainfile.php')){
		array_push($block['msg'], icms_error_msg(sprintf(_WARNINWRITEABLE,ICMS_ROOT_PATH.'/mainfile.php'), '', false));
	}
	if(is_dir(ICMS_ROOT_PATH.'/upgrade/')){
		array_push($block['msg'], icms_error_msg(sprintf(_WARNINSTALL2,ICMS_ROOT_PATH.'/upgrade/'), '', false));
	}
	if(!is_dir(XOOPS_TRUST_PATH)){
		array_push($block['msg'], icms_error_msg(_TRUST_PATH_HELP));
	}
	$sql1 = "SELECT conf_modid FROM `".$xoopsDB->prefix('config')."` WHERE conf_name = 'dos_skipmodules'";
	if($result1 = $xoopsDB->query($sql1)){
		list($modid) = $xoopsDB->FetchRow($result1);
		$protector_is_active = '0';
		if (!is_null($modid)){
		$sql2 = "SELECT isactive FROM `".$xoopsDB->prefix('modules')."` WHERE mid =".$modid;
		$result2 = $xoopsDB->query($sql2);
		list($protector_is_active) = $xoopsDB->FetchRow($result2);
		}
	}
	if($protector_is_active == 0){
		array_push($block['msg'], icms_error_msg(_PROTECTOR_NOT_FOUND, '', false));
		echo '<br />';
	}

	// ###### Output warn messages for correct functionality  ######
	if(!is_writable(ICMS_CACHE_PATH))
		array_push($block['msg'], icms_warning_msg(sprintf(_WARNINNOTWRITEABLE,ICMS_CACHE_PATH)), '', false);
	if(!is_writable(ICMS_UPLOAD_PATH))
		array_push($block['msg'], icms_warning_msg(sprintf(_WARNINNOTWRITEABLE,ICMS_UPLOAD_PATH)), '', false);
	if(!is_writable(ICMS_COMPILE_PATH))
		array_push($block['msg'], icms_warning_msg(sprintf(_WARNINNOTWRITEABLE,ICMS_COMPILE_PATH)), '', false);

	if(count($block['msg'] ) > 0){
		return $block;
	}

}

/**
 * Admin Control Panel Block
 *
 * @since ImpressCMS 1.2
 *
 * @author Gustavo Pilla (aka nekro) <nekro@impresscms.org> <gpilla@nubee.com.ar>
 *
 * @return array
 *
 * @todo This code is the copy of the one wich was in the admin.php, it should be improved.
 */
function b_system_admin_cp_show(){
	global $icmsTpl, $xoopsConfig, $icmsUser;

	$block['lang_cp']= _CPHOME;
	$block['lang_insmodules'] = _AD_INSTALLEDMODULES;

	// Loading System Configuration Links
	if( is_object( $icmsUser ) )
		$groups = $icmsUser->getGroups();
	else
		$groups = array();
	$all_ok = false;
	if(!in_array(XOOPS_GROUP_ADMIN, $groups))
	{
		$sysperm_handler =& xoops_gethandler('groupperm');
		$ok_syscats =& $sysperm_handler->getItemIds('system_admin', $groups);
	}
	else {$all_ok = true;}

	require_once ICMS_ROOT_PATH.'/class/xoopslists.php';
	require_once ICMS_ROOT_PATH.'/modules/system/constants.php';

	$admin_dir = ICMS_ROOT_PATH.'/modules/system/admin';
	$dirlist = XoopsLists::getDirListAsArray($admin_dir);

	icms_loadLanguageFile('system', 'admin');
	asort($dirlist);
	$block['sysmod'] = array();
	foreach($dirlist as $file){
		$mod_version_file = 'xoops_version.php';
		if(file_exists($admin_dir.'/'.$file.'/icms_version.php')){
		$mod_version_file = 'icms_version.php';
		}
		include $admin_dir.'/'.$file.'/'.$mod_version_file;
		if($modversion['hasAdmin']){
			$category = isset($modversion['category']) ? intval($modversion['category']) : 0;
			if(false != $all_ok || in_array($modversion['category'], $ok_syscats)){
				$sysmod = array('title' => $modversion['name'], 'link' => ICMS_URL.'/modules/system/admin.php?fct='.$file, 'image' => ICMS_URL.'/modules/system/admin/'.$file.'/images/'.$file.'_big.png');
				array_push($block['sysmod'], $sysmod);
			}
		}
		unset($modversion);
	}
	if(count($block['sysmod']) > 0)
		return $block;
}

/**
 * System Admin Modules Block Show Fuction
 *
 * @author Gustavo Pilla (aka nekro) <nekro@impresscms.org> <gpilla@nubee.com.ar>
 *
 * @since ImpressCMS 1.2
 *
 * @return array
 *
 * @todo Maybe it can be improved a little, is just a copy of the generate menu function.
 */
function b_system_admin_modules_show(){
	global $icmsUser;
	$block['mods'] = array();
	$module_handler = & xoops_gethandler ( 'module' );
	$moduleperm_handler = & xoops_gethandler ( 'groupperm' );
	$criteria = new CriteriaCompo ( );
	$criteria->add ( new Criteria ( 'hasadmin', 1 ) );
	$criteria->add ( new Criteria ( 'isactive', 1 ) );
	$criteria->setSort ( 'mid' );
	$modules = $module_handler->getObjects ( $criteria );
	foreach ( $modules as $module ) {
		$rtn = array ( );
		$inf = & $module->getInfo ();
		$rtn ['link'] = XOOPS_URL . '/modules/' . $module->dirname () . '/' . (isset ( $inf ['adminindex'] ) ? $inf ['adminindex'] : '');
		$rtn ['title'] = $module->name ();
		$rtn ['dir'] = $module->dirname ();
		if (isset ( $inf ['iconsmall'] ) && $inf ['iconsmall'] != '') {
			$rtn ['small'] = XOOPS_URL . '/modules/' . $module->dirname () . '/' . $inf ['iconsmall'];
		}
		if (isset ( $inf ['iconbig'] ) && $inf ['iconbig'] != '') {
			$rtn ['iconbig'] = XOOPS_URL . '/modules/' . $module->dirname () . '/' . $inf ['iconbig'];
		}
		$rtn ['absolute'] = 1;
		$module->loadAdminMenu ();
		if (is_array ( $module->adminmenu ) && count ( $module->adminmenu ) > 0) {
			$rtn ['hassubs'] = 1;
			$rtn ['subs'] = array ( );
			foreach ( $module->adminmenu as $item ) {
				$item ['link'] = XOOPS_URL . '/modules/' . $module->dirname () . '/' . $item ['link'];
				$rtn ['subs'] [] = $item;
			}
		} else {
			$rtn ['hassubs'] = 0;
			unset ( $rtn ['subs'] );
		}
		$hasconfig = $module->getVar ( 'hasconfig' );
		$hascomments = $module->getVar ( 'hascomments' );
		if ((isset ( $hasconfig ) && $hasconfig == 1) || (isset ( $hascomments ) && $hascomments == 1)) {
			$rtn ['hassubs'] = 1;
			if (! isset ( $rtn ['subs'] )) {
				$rtn ['subs'] = array ( );
			}
			$subs = array ('title' => _PREFERENCES, 'link' => XOOPS_URL . '/modules/system/admin.php?fct=preferences&amp;op=showmod&amp;mod=' . $module->mid () );
			$rtn ['subs'] [] = $subs;
		} else {
			$rtn ['hassubs'] = 0;
			unset ( $rtn ['subs'] );
		}
		if ($module->dirname () == 'system') {
			$systemadm = true;
		}
		if( is_object( $icmsUser ) )
			$admin_perm = $moduleperm_handler->checkRight ( 'module_admin', $module->mid (), $icmsUser->getGroups () );
		if ($admin_perm) {
			if ($rtn ['dir'] != 'system') {
				$block['mods'][] = $rtn;
			}
		}

	}
	// If there is any module listed, then show the block.
	if(count($block['mods'] > 0))
		return $block;
}
?>
