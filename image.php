<?php
/**
 * functions for image.
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package		core
 * @since		XOOPS
 * @version		$Id: image.php 20607 2010-12-21 19:05:12Z phoenyx $
 */

$image_id = isset($_GET["id"]) ? (int) $_GET["id"] : 0;
if (empty($image_id)) {
	header("Content-type: image/gif");
	readfile(ICMS_UPLOAD_PATH . "/blank.gif");
	exit();
}

include "mainfile.php";
icms::$logger->disableLogger();

$criteria = icms_buildCriteria(array("i.image_display" => 1, "i.image_id" => $image_id));
$images = icms::handler("icms_image")->getObjects($criteria, FALSE, TRUE);

if (count($images) == 1 && $images[0]->getVar("image_body") !== NULL) {
	header("Content-type: ".$images[0]->getVar("image_mimetype"));
	header("Cache-control: max-age=31536000");
	header("Expires: " . gmdate("D, d M Y H:i:s", time() + 31536000) . "GMT");
	header("Content-disposition: filename=" . $images[0]->getVar("image_name"));
	header("Content-Length: " . strlen($images[0]->getVar("image_body")));
	header("Last-Modified: " . gmdate("D, d M Y H:i:s", $images[0]->getVar("image_created"))  . "GMT");
	echo $images[0]->getVar("image_body");
} else {
	header("Content-type: image/gif");
	readfile(ICMS_UPLOAD_PATH . "/blank.gif");
	exit();
}