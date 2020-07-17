<?php
/**
 * New comment form
 *
 * This file holds the configuration information of this module
 *
 * @copyright	The ImpressCMS Project
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @since		1.0
 * @author		Rodrigo P Lima aka TheRplima <therplima@impresscms.org>
 * @package		content
 * @version		$Id: comment_new.php 20051 2010-08-28 16:30:42Z phoenyx $
 */

include_once "header.php";
$com_itemid = isset($_GET["com_itemid"]) ? (int)$_GET["com_itemid"] : 0;
if ($com_itemid > 0) {
	$content_content_handler = icms_getModuleHandler("content", basename(dirname(__FILE__)), "content");
	$contentObj = $content_content_handler->get($com_itemid);
	if ($contentObj && !$contentObj->isNew()) {
		$com_replytext = "test...";
		$bodytext = $contentObj->getContentLead();
		if ($bodytext != "") {
			$com_replytext .= "<br /><br />".$bodytext."";
		}
		$com_replytitle = $contentObj->getVar("content_title");
		include_once ICMS_ROOT_PATH . "/include/comment_new.php";
	}
}