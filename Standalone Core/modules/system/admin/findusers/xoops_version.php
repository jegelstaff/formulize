<?php
// $Id: xoops_version.php 8475 2009-04-05 11:34:50Z icmsunderdog $
/**
* Administration of finding users, versionfile
*
* @copyright	http://www.xoops.org/ The XOOPS Project
* @copyright	XOOPS_copyrights.txt
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license	LICENSE.txt
* @package	Administration
* @since	XOOPS
* @author	http://www.xoops.org The XOOPS Project
* @author	modified by UnderDog <underdog@impresscms.org>
* @version	$Id: xoops_version.php 8475 2009-04-05 11:34:50Z icmsunderdog $
*/

$modversion['name'] = _MD_AM_FINDUSER;
$modversion['version'] = "";
$modversion['description'] = "Find Users";
$modversion['author'] = "Kazumi Ono<br />( http://www.myweb.ne.jp/ )";
$modversion['credits'] = "The XOOPS Project";
$modversion['help'] = "findusers.html";
$modversion['license'] = "GPL see LICENSE";
$modversion['official'] = 1;
$modversion['image'] = "users.gif";

$modversion['hasAdmin'] = 1;
$modversion['adminpath'] = "admin.php?fct=findusers";
$modversion['category'] = XOOPS_SYSTEM_FINDU;
?>