<?php
// $Id$
/**
* The beginning of the admin interface for ImpressCMS
*
* @copyright	http://www.xoops.org/ The XOOPS Project
* @copyright	XOOPS_copyrights.txt
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license		LICENSE.txt
* @package	Administration
* @subpackage System
* @since		XOOPS
* @author		http://www.xoops.org The XOOPS Project
* @version		$Id$
*/


include_once '../../include/functions.php';
if(!empty($_POST)) foreach($_POST as $k => $v) ${$k} = StopXSS($v);
if(!empty($_GET)) foreach($_GET as $k => $v) ${$k} = StopXSS($v);
$fct = (isset($_GET['fct']))?trim(StopXSS($_GET['fct'])):((isset($_POST['fct']))?trim(StopXSS($_POST['fct'])):'');

if(isset($fct) && $fct == 'users') {$xoopsOption['pagetype'] = 'user';}
include '../../mainfile.php';
$false = false;
include ICMS_ROOT_PATH.'/include/cp_functions.php';
icms_loadLanguageFile('system', 'admin');
icms_loadLanguageFile('core', 'moduleabout');

include_once ICMS_ROOT_PATH.'/class/xoopsmodule.php';
// Check if function call does exist (security)
require_once ICMS_ROOT_PATH.'/class/xoopslists.php';
$admin_dir = ICMS_ROOT_PATH.'/modules/system/admin';
$dirlist = XoopsLists::getDirListAsArray($admin_dir);
if($fct && !in_array($fct,$dirlist)) {redirect_header(ICMS_URL.'/',3,_INVALID_ADMIN_FUNCTION);}
$admintest = 0;

if(is_object($icmsUser))
{
	$icmsModule =& XoopsModule::getByDirname('system');
	if(!$icmsUser->isAdmin($icmsModule->mid())) {redirect_header(ICMS_URL.'/',3,_NOPERM);}
	$admintest=1;
}
else {redirect_header(ICMS_URL.'/',3,_NOPERM);}

// include system category definitions
include_once ICMS_ROOT_PATH.'/modules/system/constants.php';
$error = false;
if($admintest != 0)
{
	if(isset($fct) && $fct != '')
	{
		if(file_exists(ICMS_ROOT_PATH.'/modules/system/admin/'.$fct.'/xoops_version.php'))
		{
			icms_loadLanguageFile('system', $fct, true);
			include ICMS_ROOT_PATH.'/modules/system/admin/'.$fct.'/xoops_version.php';
			$sysperm_handler =& xoops_gethandler('groupperm');
			$category = !empty($modversion['category']) ? intval($modversion['category']) : 0;
			unset($modversion);
			if($category > 0)
			{
				$groups =& $icmsUser->getGroups();
				if(in_array(XOOPS_GROUP_ADMIN, $groups) || false != $sysperm_handler->checkRight('system_admin', $category, $groups, $icmsModule->getVar('mid')))
				{
					if(file_exists(ICMS_ROOT_PATH.'/modules/system/admin/'.$fct.'/main.php'))
					{
						include_once ICMS_ROOT_PATH.'/modules/system/admin/'.$fct.'/main.php';
					}
					else {$error = true;}
				}
				else {$error = true;}
			}
			elseif($fct == 'version')
			{
				if(file_exists(ICMS_ROOT_PATH.'/modules/system/admin/version/main.php'))
				{
					include_once ICMS_ROOT_PATH.'/modules/system/admin/version/main.php';
				}
				else {$error = true;}
			}
			else {$error = true;}
		}
		else {$error = true;}
	}
	else {$error = true;}
}
if(isset($fct) && $fct == 'users' && icms_get_module_status('profile')){
		header("Location:".ICMS_MODULES_URL."/profile/admin/user.php");

}
if($false != $error){
	header("Location:".ICMS_URL."/admin.php");
}
?>