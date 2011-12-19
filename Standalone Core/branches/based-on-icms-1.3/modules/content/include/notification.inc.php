<?php
/**
 * Common functions used by the module
 *
 * @copyright	The ImpressCMS Project
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @since		1.0
 * @author		Rodrigo P Lima aka TheRplima <therplima@impresscms.org>
 * @package		content
 * @version		$Id: notification.inc.php 20051 2010-08-28 16:30:42Z phoenyx $
 */

/**
 * Notification lookup function
 *
 * This function is called by the notification process to get an array contaning information
 * about the item for which there is a notification
 *
 * @param string $category category of the notification
 * @param int $item_id id f the item related to this notification
 *
 * @return array containing 'name' and 'url' of the related item
 */
function content_notify_iteminfo($category, $item_id){
	if ($category == 'global') {
		$item['name'] = '';
		$item['url'] = '';
		return $item;
	}
}