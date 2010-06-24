<?php
/**
* Administration of user ranks, versionfile
*
* @copyright	http://www.xoops.org/ The XOOPS Project
* @copyright	XOOPS_copyrights.txt
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license	LICENSE.txt
* @package	Administration
* @since	XOOPS
* @author	http://www.xoops.org The XOOPS Project
* @author	modified by UnderDog <underdog@impresscms.org>
* @version	$Id: xoops_version.php 8627 2009-04-21 04:01:07Z skenow $
*/

$modversion['name'] = _MD_AM_RANK;
$modversion['version'] = "";
$modversion['description'] = "User Posts Ranks Configuration";
$modversion['author'] = "phpBB Group ( http://www.phpbb.com/ )";
$modversion['credits'] = "";
$modversion['help'] = "userrank.html";
$modversion['license'] = "GPL see LICENSE";
$modversion['official'] = 1;
$modversion['image'] = "userrank.gif";

$modversion['hasAdmin'] = 1;
$modversion['adminpath'] = "admin.php?fct=userrank";
$modversion['category'] = XOOPS_SYSTEM_URANK;
?>