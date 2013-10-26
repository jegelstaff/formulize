<?php
/**
 * content version infomation
 *
 * This file holds the configuration information of this module
 *
 * @copyright	The ImpressCMS Project
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @since		1.0
 * @author		Rodrigo P Lima aka TheRplima <therplima@impresscms.org>
 * @package		content
 * @version		$Id: search.inc.php 20051 2010-08-28 16:30:42Z phoenyx $
 */

defined("ICMS_ROOT_PATH") or die("ICMS root path not defined");

include_once ICMS_ROOT_PATH . '/modules/' . basename(dirname(dirname(__FILE__))) . '/include/common.php';
function content_search($queryarray, $andor, $limit, $offset, $userid){
	$imcontent_content_handler = icms_getModuleHandler('content', basename(dirname(dirname(__FILE__))), 'content');
	$contentsArray = $imcontent_content_handler->getContentsForSearch($queryarray, $andor, $limit, $offset, $userid);

	$ret = array();

	foreach ($contentsArray as $contentArray) {
		$item['image'] = "images/content.png";
		$item['link'] = $contentArray['itemUrl'];
		$item['title'] = $contentArray['content_title'];
		$item['time'] = strtotime($contentArray['content_published_date']);
		$item['uid'] = $contentArray['content_posterid'];
		$ret[] = $item;
		unset($item);
	}
	return $ret;
}