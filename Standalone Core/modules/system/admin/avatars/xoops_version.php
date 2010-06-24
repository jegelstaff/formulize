<?php
// $Id: xoops_version.php 8451 2009-04-05 10:24:25Z icmsunderdog $
/**
* Administration of avatars, versionfile
*
* Longer description about this page
*
* @copyright	http://www.xoops.org/ The XOOPS Project
* @copyright	XOOPS_copyrights.txt
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license		LICENSE.txt
* @package		Administration
* @since			XOOPS
* @author			http://www.xoops.org The XOOPS Project
* @author			modified by UnderDog <underdog@impresscms.org>
* @version		$Id: xoops_version.php 8451 2009-04-05 10:24:25Z icmsunderdog $
*/

$modversion['name'] = _MD_AM_AVATARS;
$modversion['version'] = "";
$modversion['description'] = "ImpressCMS Site Avatar Manager";
$modversion['author'] = "";
$modversion['credits'] = "The ImpressCMS Project";
$modversion['help'] = "images.html";
$modversion['license'] = "GPL see LICENSE";
$modversion['official'] = 1;
$modversion['image'] = "avatars.gif";

$modversion['hasAdmin'] = 1;
$modversion['adminpath'] = "admin.php?fct=avatars";
$modversion['category'] = XOOPS_SYSTEM_AVATAR;
?>