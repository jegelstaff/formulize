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
* @version		$Id$
*/

include_once 'header.php';
$com_itemid = isset($_GET['com_itemid']) ? intval($_GET['com_itemid']) : 0;
if ($com_itemid > 0) {
	$content_content_handler = xoops_getModuleHandler('content');
	$contentObj = $content_content_handler->get($com_itemid);
	if ($contentObj && !$contentObj->isNew()) {
		//$com_replytext = _POSTEDBY.'&nbsp;<b>'.smartsection_getLinkedUnameFromId($itemObj->uid()) . '</b>&nbsp;'._DATE.'&nbsp;<b>'.$itemObj->dateSub().'</b><br /><br />'.$itemObj->summary();
		$com_replytext = 'test...';
		$bodytext = $contentObj->getContentLead();
		if ($bodytext != '') {
			$com_replytext .= '<br /><br />'.$bodytext.'';
		}
		$com_replytitle = $contentObj->getVar('content_title');
		include_once ICMS_ROOT_PATH .'/include/comment_new.php';
	}
}

?>