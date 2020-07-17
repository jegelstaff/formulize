<?php
/**
 * Extended User Profile
 *
 * @copyright       The ImpressCMS Project http://www.impresscms.org/
 * @license         LICENSE.txt
 * @license			GNU General Public License (GPL) http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @package         modules
 * @since           1.2
 * @author          Jan Pedersen
 * @author          Marcello Brandao <marcello.brandao@gmail.com>
 * @author	   		Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @version         $Id: notification.inc.php 20562 2010-12-19 18:26:36Z phoenyx $
 */

/** Protection against inclusion outside the site */
defined("ICMS_ROOT_PATH") or die("ICMS root path not defined");

function profile_iteminfo($category, $item_id) {
	$item = array('name' => '', 'url' => '');

	switch ($category) {
		case 'pictures':
		case 'videos':
		case 'audio':
			$thisUser = icms::handler('icms_member')->getUser($item_id);
			if ($thisUser === false) break;
			$item['name'] = $thisUser->getVar('uname');
			$item['url'] = ICMS_URL.'/modules/'.basename(dirname(dirname(__FILE__))).'/'.$category.'.php?uid='.$item_id;
			break;
		case 'tribetopic':
			$profile_tribes_handler = icms_getModuleHandler('tribes', basename(dirname(dirname(__FILE__))), 'profile');
			$tribesObj = $profile_tribes_handler->get($item_id);
			if ($tribesObj->isNew()) break;
			$item['name'] = $tribesObj->getVar('title');
			$item['url'] = $tribesObj->getItemLink(true);
			break;
		case 'tribepost':
			$profile_tribetopic_handler = icms_getModuleHandler('tribetopic', basename(dirname(dirname(__FILE__))), 'profile');
			$tribetopicObj = $profile_tribetopic_handler->get($item_id);
			if ($tribetopicObj->isNew()) break;
			$tribetopic = $tribetopicObj->toArray();
			$item['name'] = $tribetopic['title'];
			$item['url'] = $tribetopic['itemUrl'];
			break;
	}

	return $item;
}
?>