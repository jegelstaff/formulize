<?php
/**
 * Extended User Profile
 *
 *
 *
 * @copyright       The ImpressCMS Project http://www.impresscms.org/
 * @license         LICENSE.txt
 * @license			GNU General Public License (GPL) http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @package         modules
 * @since           1.2
 * @author          Jan Pedersen
 * @author          Marcello Brandao <marcello.brandao@gmail.com>
 * @author	   		Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @version         $Id$
 */

/**
* Protection against inclusion outside the site 
*/
if (!defined("ICMS_ROOT_PATH")) {
die("XOOPS root path not defined");
}

function profile_iteminfo($category, $item_id)
{
	global $icmsModule;
	if ($category=='global') {
		$item['name'] = '';
		$item['url'] = '';
		return $item;
	}

	global $xoopsDB;


	if ($category=='picture') {

		$sql = 'SELECT title,uid_owner,url FROM ' . $xoopsDB->prefix('profile_images') . ' WHERE uid_owner = ' . $item_id . ' LIMIT 1';
		$result = $xoopsDB->query($sql);
		$result_array = $xoopsDB->fetchArray($result);
		/**
		 * Let's get the user name of the owner of the album
		 */ 
		$owner = new XoopsUser();
		$identifier = $owner->getUnameFromId($result_array['uid_owner']);
		$item['name'] = $identifier."'s Album";
		$item['url'] = ICMS_URL . '/modules/' . $icmsModule->getVar('dirname') . '/album.php?uid=' . $result_array['uid_owner'];
		return $item;
	}
	
	if ($category=='video') {

		$sql = 'SELECT video_id,uid_owner,video_desc,youtube_code, mainvideo FROM ' . $xoopsDB->prefix('profile_images') . ' WHERE uid_owner = ' . $item_id . ' LIMIT 1';
		$result = $xoopsDB->query($sql);
		$result_array = $xoopsDB->fetchArray($result);
		/**
		 * Let's get the user name of the owner of the album
		 */ 
		$owner = new XoopsUser();
		$identifier = $owner->getUnameFromId($result_array['uid_owner']);
		$item['name'] = $identifier."'s Videos";
		$item['url'] = ICMS_URL . '/modules/' . $icmsModule->getVar('dirname') . '/video.php?uid=' . $result_array['uid_owner'];
		return $item;
	}
}
?>