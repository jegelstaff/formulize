<?php

/**
* Classes responsible for managing profile videos objects
*
* @copyright	GNU General Public License (GPL)
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @since		1.3
* @author		Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
* @package		profile
* @version		$Id$
*/

if (!defined("ICMS_ROOT_PATH")) die("ICMS root path not defined");

// including the IcmsPersistabelSeoObject
include_once ICMS_ROOT_PATH . '/kernel/icmspersistableseoobject.php';
include_once(ICMS_ROOT_PATH . '/modules/profile/include/functions.php');

class ProfileVideos extends IcmsPersistableSeoObject {

	/**
	 * Constructor
	 *
	 * @param object $handler ProfilePostHandler object
	 */
	public function __construct(& $handler) {
		global $icmsConfig;

		$this->IcmsPersistableObject($handler);

		$this->quickInitVar('videos_id', XOBJ_DTYPE_INT, true);
		$this->quickInitVar('uid_owner', XOBJ_DTYPE_INT, true);
		$this->quickInitVar('video_title', XOBJ_DTYPE_TXTBOX, true);
		$this->quickInitVar('youtube_code', XOBJ_DTYPE_TXTBOX, true);
		$this->quickInitVar('video_desc', XOBJ_DTYPE_TXTAREA, true);
		$this->quickInitVar('creation_time', XOBJ_DTYPE_LTIME, false);
		$this->initCommonVar('counter', false);
		$this->initCommonVar('dohtml', false, true);
		$this->initCommonVar('dobr', false, true);
		$this->initCommonVar('doimage', false, true);
		$this->initCommonVar('dosmiley', false, true);
		$this->initCommonVar('doxcode', false, true);

		$this->setControl('uid_owner', 'user');
		$this->setControl('video_desc', 'dhtmltextarea');

		$this->IcmsPersistableSeoObject();
	}

	/**
	 * Overriding the IcmsPersistableObject::getVar method to assign a custom method on some
	 * specific fields to handle the value before returning it
	 *
	 * @param str $key key of the field
	 * @param str $format format that is requested
	 * @return mixed value of the field that is requested
	 */
	function getVar($key, $format = 's') {
		if ($format == 's' && in_array($key, array ())) {
			return call_user_func(array ($this,	$key));
		}
		return parent :: getVar($key, $format);
	}
	function getVideoToDisplay() {
		$ret = '<object width="320" height="265"><param name="movie" value="http://www.youtube.com/v/' . $this->getVar ( 'youtube_code' ) . '&hl='._LANGCODE.'&fs=1&color1=0x3a3a3a&color2=0x999999"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://www.youtube.com/v/' . $this->getVar ( 'youtube_code' ) . '&hl='._LANGCODE.'&fs=1&color1=0x3a3a3a&color2=0x999999" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" wmode="opaque" width="320" height="265"></embed></object>';
		return $ret;
	}
	
	function getVideoSender() {
		return icms_getLinkedUnameFromId($this->getVar('uid_owner', 'e'));
	}

	function getVideoDescription() {
		$ret = '<a href="' . ICMS_URL . '/modules/profile/videos.php?uid=' . $this->getVar('uid_owner', 'e') . '">'.$this->getVar('video_desc', 'e').'</a>';
		return $ret;
	}

	/**
	 * return video title
	 *
	 * @return string video title
	 */
	function getVideoTitle() {
		return $this->getVar('video_title');
	}

	/**
	 * Check to see wether the current user can edit or delete this video
	 *
	 * @return bool true if he can, false if not
	 */
	function userCanEditAndDelete() {
		global $icmsUser, $profile_isAdmin;
		if (!is_object($icmsUser)) {
			return false;
		}
		if ($profile_isAdmin) {
			return true;
		}
		return $this->getVar('uid_owner', 'e') == $icmsUser->uid();
	}

	/**
	 * Overridding IcmsPersistable::toArray() method to add a few info
	 *
	 * @return array of video info
	 */
	function toArray() {
		$ret = parent :: toArray();
		$ret['creation_time'] = formatTimestamp($this->getVar('creation_time', 'e'), 'm');
		$ret['video_content'] = $this->getVideoToDisplay();
		$ret['video_desc'] = $this->getVar('video_desc','show');
		$ret['editItemLink'] = $this->getEditItemLink(false, true, true);
		$ret['deleteItemLink'] = $this->getDeleteItemLink(false, true, true);
		$ret['userCanEditAndDelete'] = $this->userCanEditAndDelete();
		$ret['video_senderid'] = $this->getVar('uid_owner','e');
		$ret['video_sender_link'] = $this->getVideoSender();
		return $ret;
	}
}
class ProfileVideosHandler extends IcmsPersistableObjectHandler {

	/**
	 * Constructor
	 */
	public function __construct(& $db) {
		$this->IcmsPersistableObjectHandler($db, 'videos', 'videos_id', 'video_title', '', 'profile');
	}

	/**
	 * Create the criteria that will be used by getVideos and getVideosCount
	 *
	 * @param int $start to which record to start
	 * @param int $limit limit of videos to return
	 * @param int $uid_owner if specifid, only the videos of this user will be returned
	 * @param int $video_id ID of a single video to retrieve
	 * @return CriteriaCompo $criteria
	 */
	function getVideosCriteria($start = 0, $limit = 0, $uid_owner = false, $video_id = false) {
		global $icmsUser;

		$criteria = new CriteriaCompo();
		if ($start) {
			$criteria->setStart($start);
		}
		if ($limit) {
			$criteria->setLimit(intval($limit));
		}
		$criteria->setSort('creation_time');
		$criteria->setOrder('DESC');
		
		if ($uid_owner) {
			$criteria->add(new Criteria('uid_owner', $uid_owner));
		}
		if ($video_id) {
			$criteria->add(new Criteria('videos_id', $video_id));
		}
		return $criteria;
	}

	/**
	 * Get single video object
	 *
	 * @param int $videos_id
	 * @return object ProfileVideo object
	 */
	function getVideo($videos_id) {
		$ret = $this->getVideos(0, 0, false, $videos_id);
		return isset($ret[$videos_id]) ? $ret[$videos_id] : false;
	}

	/**
	 * Get videos as array, ordered by creation_time DESC
	 *
	 * @param int $start to which record to start
	 * @param int $limit max videos to display
	 * @param int $uid_owner if specifid, only the video of this user will be returned
	 * @param int $videos_id ID of a single video to retrieve
	 * @return array of videos
	 */
	function getVideos($start = 0, $limit = 0, $uid_owner = false, $videos_id = false) {
		$criteria = $this->getVideosCriteria($start, $limit, $uid_owner, $videos_id);
		$ret = $this->getObjects($criteria, true, false);
		return $ret;
	}

	
	/**
	 * Check wether the current user can submit a new video or not
	 *
	 * @return bool true if he can false if not
	 */
	function userCanSubmit() {
		global $icmsUser;
		if (!is_object($icmsUser)) {
			return false;
		}
		return true;
	}


	/**
	 * Update the counter field of the post object
	 *
	 * @todo add this in directly in the IPF
	 * @param int $post_id
	 *
	 * @return VOID
	 */
	function updateCounter($id) {
		$sql = 'UPDATE ' . $this->table . ' SET counter = counter + 1 WHERE ' . $this->keyName . ' = ' . $id;
		$this->query($sql, null, true);
	}
}
?>