<?php
// $Id: xoops_version.php 8481 2009-04-05 12:06:11Z icmsunderdog $
/**
* Administration of images, versionfile
*
* @copyright	http://www.xoops.org/ The XOOPS Project
* @copyright	XOOPS_copyrights.txt
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license	LICENSE.txt
* @package	Administration
* @since	XOOPS
* @author	http://www.xoops.org The XOOPS Project
* @author	modified by UnderDog <underdog@impresscms.org>
* @version	$Id: xoops_version.php 8481 2009-04-05 12:06:11Z icmsunderdog $
*/

$modversion['name'] = _MD_AM_IMAGES;
$modversion['version'] = "";
$modversion['description'] = "ImpressCMS Image Manager";
$modversion['author'] = "";
$modversion['credits'] = "The ImpressCMS Project";
$modversion['help'] = "images.html";
$modversion['license'] = "GPL see LICENSE";
$modversion['official'] = 1;
$modversion['image'] = "images.gif";

$modversion['hasAdmin'] = 1;
$modversion['adminpath'] = "admin.php?fct=images";
$modversion['category'] = XOOPS_SYSTEM_IMAGE;
?>