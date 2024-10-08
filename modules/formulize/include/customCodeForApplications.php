<?php

/*get the aid and include custom_code if exists
 *
 *Added By Jinfu Jan 2015
 */
global $xoopsDB;
// must do DB query because this can be invoked before the session is fully instantiated. Using Application handler may reference the user object which won't exist yet when this is invoked early.
$sql = 'SELECT appid FROM '.$xoopsDB->prefix('formulize_applications');
if($res = $xoopsDB->query($sql)) {
	while($row = $xoopsDB->fetchRow($res)) {
		$aid=$row[0];
		if(file_exists(XOOPS_ROOT_PATH.'/modules/formulize/code/application_custom_code_'.$aid.'.php')) {
				ob_start();
				include_once(XOOPS_ROOT_PATH.'/modules/formulize/code/application_custom_code_'.$aid.'.php');
				$GLOBALS['formulize_customCodeForApplications'] .= ob_get_clean()."\n";
		}
	}
}
