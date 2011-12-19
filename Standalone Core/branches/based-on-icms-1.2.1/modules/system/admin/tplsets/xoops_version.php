<?php
/**
* Administration of template sets, versionfile
*
* @copyright	http://www.xoops.org/ The XOOPS Project
* @copyright	XOOPS_copyrights.txt
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license	LICENSE.txt
* @package	Administration
* @since	XOOPS
* @author	http://www.xoops.org The XOOPS Project
* @author	modified by UnderDog <underdog@impresscms.org>
* @version	$Id: xoops_version.php 8626 2009-04-21 03:56:17Z skenow $
*/

$modversion['name'] = _MD_AM_TPLSETS;
$modversion['version'] = "";
$modversion['description'] = "ImpressCMS Template Set Manager";
$modversion['author'] = "";
$modversion['credits'] = "The ImpressCMS Project";
$modversion['help'] = "tplsets.html";
$modversion['license'] = "GPL see LICENSE";
$modversion['official'] = 1;
$modversion['image'] = "tplsets.gif";

$modversion['hasAdmin'] = 1;
$modversion['adminpath'] = "admin.php?fct=tplsets";
$modversion['category'] = XOOPS_SYSTEM_TPLSET;
?>