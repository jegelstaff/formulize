<?php
/**
* Versionchecker, versionfile
*
* @copyright	http://www.xoops.org/ The XOOPS Project
* @copyright	XOOPS_copyrights.txt
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license	LICENSE.txt
* @package	Administration
* @since	XOOPS
* @author	http://www.xoops.org The XOOPS Project
* @author	modified by UnderDog <underdog@impresscms.org>
* @version	$Id: xoops_version.php 8656 2009-05-01 01:01:39Z skenow $
*/

$modversion['name'] = _MD_AM_VRSN;
$modversion['version'] = "1.0";
$modversion['description'] = "ImpressCMS Version";
$modversion['author'] = "marcan (marcan@impresscms.org)";
$modversion['credits'] = "The ImpressCMS Project";
$modversion['help'] = "";
$modversion['license'] = "GPL see LICENSE";
$modversion['official'] = 1;
$modversion['image'] = "version.gif";

$modversion['hasAdmin'] = 1;
$modversion['adminpath'] = "admin.php?fct=version";

$modversion['category'] = XOOPS_SYSTEM_VERSION;
?>