<?php
/**
* functions for image.
*
* @copyright	http://www.xoops.org/ The XOOPS Project
* @copyright	XOOPS_copyrights.txt
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @package		core
* @since		XOOPS
* @author		http://www.xoops.org The XOOPS Project
* @author	   Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
* @version		$Id: image.php 8534 2009-04-11 10:11:43Z icmsunderdog $
*/

@set_magic_quotes_runtime(0);
if (function_exists('mb_http_output')) {
	mb_http_output('pass');
}
$image_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (empty($image_id)) {
	header('Content-type: image/gif');
	readfile(ICMS_UPLOAD_PATH.'/blank.gif');
	exit();
}
$xoopsOption['nocommon'] = 1;
/** Include mainfile.php - required */
include './mainfile.php';
/** Include the core functions */
include ICMS_ROOT_PATH.'/include/functions.php';
/** Include the logging functions */
include_once ICMS_ROOT_PATH.'/class/logger.php';
/** Include the textsanitizer class */
include_once ICMS_ROOT_PATH."/class/module.textsanitizer.php";
$xoopsLogger =& XoopsLogger::instance();
$xoopsLogger->startTime();
/** Include the database class */
include_once ICMS_ROOT_PATH.'/class/database/databasefactory.php';
/** Define XOOPS_DB_PROXY */
define('XOOPS_DB_PROXY', 1);
$xoopsDB =& XoopsDatabaseFactory::getDatabaseConnection();
// ################# Include class manager file ##############
/** Require the object class */
require_once ICMS_ROOT_PATH.'/kernel/object.php';
/** Require the criteria class, for building queries */
require_once ICMS_ROOT_PATH.'/class/criteria.php';
$imagehandler =& xoops_gethandler('image');
$criteria = new CriteriaCompo(new Criteria('i.image_display', 1));
$criteria->add(new Criteria('i.image_id', $image_id));
$image =& $imagehandler->getObjects($criteria, false, true);
if (count($image) > 0) {
	header('Content-type: '.$image[0]->getVar('image_mimetype'));
	header('Cache-control: max-age=31536000');
	header('Expires: '.gmdate("D, d M Y H:i:s",time()+31536000).'GMT');
	header('Content-disposition: filename='.$image[0]->getVar('image_name'));
	header('Content-Length: '.strlen($image[0]->getVar('image_body')));
	header('Last-Modified: '.gmdate("D, d M Y H:i:s",$image[0]->getVar('image_created')).'GMT');
	echo $image[0]->getVar('image_body');
} else {
	header('Content-type: image/gif');
	readfile(ICMS_UPLOAD_PATH.'/blank.gif');
	exit();
}
?>