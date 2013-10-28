<?php
/**
 * Comment include file
 *
 * File holding functions used by the module to hook with the comment system of ImpressCMS
 *
 * @copyright	The ImpressCMS Project
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @since		1.0
 * @author		Rodrigo P Lima aka TheRplima <therplima@impresscms.org>
 * @package		content
 * @version		$Id: comment.inc.php 20051 2010-08-28 16:30:42Z phoenyx $
 */

function content_com_update($item_id, $total_num) {
	$content_content_handler = icms_getModuleHandler("content", basename(dirname(dirname(__FILE__))), "content");
	$content_content_handler->updateComments($item_id, $total_num);
}

function content_com_approve(&$comment) {
	// notification mail here
}