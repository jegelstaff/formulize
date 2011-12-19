<?php
/**
* Administration of users, versionfile
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

$modversion['name'] = _MD_AM_USER;
$modversion['version'] = "";
$modversion['description'] = "Users Administration";
$modversion['author'] = "Francisco Burzi<br />( http://phpnuke.org/ )";
$modversion['credits'] = "Modified by Kazumi Ono<br>( http://www.mywebaddons.com/ )";
$modversion['help'] = "users.html";
$modversion['license'] = "GPL see LICENSE";
$modversion['official'] = 1;
$modversion['image'] = "users.gif";

$modversion['hasAdmin'] = 1;
$modversion['adminpath'] = "admin.php?fct=users";
$modversion['category'] = XOOPS_SYSTEM_USER;
?>