<?php


// Auto-discovery entry point: called by xoops_module_update_formulize() via the patches loop.
// These operations should always run with an update... regardless of dbversion
function formulize_patch_000_always_run($prev_dbversion, $required_dbversion) {
	global $xoopsConfig;

	$adminMenuLangs = [ 'english', $xoopsConfig['language'] ];
	$adminMenuLangs = array_unique($adminMenuLangs);
	foreach($adminMenuLangs as $lang) {
		$adminMenuFile = XOOPS_ROOT_PATH.'/cache/adminmenu_'.$lang.'.php';
		if (file_exists($adminMenuFile)) {
			unlink($adminMenuFile);
		}
	}

  return true;
}


