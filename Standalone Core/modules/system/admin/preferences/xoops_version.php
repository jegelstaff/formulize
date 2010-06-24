<?php
// $Id: xoops_version.php 8505 2009-04-11 05:38:40Z icmsunderdog $
/**
* Administration of preferences, versionfile
*
* @copyright	http://www.xoops.org/ The XOOPS Project
* @copyright	XOOPS_copyrights.txt
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license	LICENSE.txt
* @package	Administration
* @since	XOOPS
* @author	http://www.xoops.org The XOOPS Project
* @author	modified by UnderDog <underdog@impresscms.org>
* @version	$Id: xoops_version.php 8505 2009-04-11 05:38:40Z icmsunderdog $
*/

$modversion['name'] = _MD_AM_PREF;
$modversion['version'] = "";
$modversion['description'] = "ImpressCMS Site Preferences";
$modversion['author'] = "";
$modversion['credits'] = "The ImpressCMS Project";
$modversion['help'] = "preferences.html";
$modversion['license'] = "GPL see LICENSE";
$modversion['official'] = 1;
$modversion['image'] = "pref.gif";

$modversion['hasAdmin'] = 1;
$modversion['adminpath'] = "admin.php?fct=preferences";
$modversion['category'] = XOOPS_SYSTEM_PREF;
?>