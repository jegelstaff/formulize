<?php
/**
 * Class responsible for managing profile videos objects
 *
 * @copyright	GNU General Public License (GPL)
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @since		1.4
 * @author		phoenyx
 * @package		profile
 * @version		$Id: VideosHandler.php 20562 2010-12-19 18:26:36Z phoenyx $
 */

defined("ICMS_ROOT_PATH") or die("ICMS root path not defined");

class mod_profile_VideosHandler extends icms_ipf_Handler {
	/**
	 * Constructor
	 *
	 * @param icms_db_legacy_Database $db database object
	 */
	public function __construct(&$db) {
		parent::__construct($db, 'videos', 'videos_id', 'video_title', '', basename(dirname(dirname(__FILE__))));
	}

	/**
	 * Create the criteria that will be used by getVideos and getVideosCount
	 *
	 * @param int $start to which record to start
	 * @param int $limit limit of videos to return
	 * @param int $uid_owner if specifid, only the videos of this user will be returned
	 * @param int $video_id ID of a single video to retrieve
	 * @return icms_db_criteria_Compo $criteria
	 */
	private function getVideosCriteria($start = 0, $limit = 0, $uid_owner = false, $video_id = false) {
		$criteria = new icms_db_criteria_Compo();
		if ($start) $criteria->setStart((int)$start);
		if ($limit) $criteria->setLimit((int)$limit);
		$criteria->setSort('creation_time');
		$criteria->setOrder('DESC');
		if ($uid_owner) $criteria->add(new icms_db_criteria_Item('uid_owner', (int)$uid_owner));
		if ($video_id) $criteria->add(new icms_db_criteria_Item('videos_id', (int)$video_id));
		return $criteria;
	}

	/**
	 * Get single video object
	 *
	 * @param int $videos_id
	 * @return ProfileVideo
	 */
	public function getVideo($videos_id) {
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
	public function getVideos($start = 0, $limit = 0, $uid_owner = false, $videos_id = false) {
		$criteria = $this->getVideosCriteria($start, $limit, $uid_owner, $videos_id);
		$ret = $this->getObjects($criteria, true, false);
		return $ret;
	}


	/**
	 * Check wether the current user can submit a new video or not
	 *
	 * @return bool true if he can false if not
	 */
	public function userCanSubmit() {
		return is_object(icms::$user);
	}

	/**
	 * AfterInsert event
	 *
	 * Event automatically triggered by IcmsPersistable Framework after the object is inserted
	 *
	 * @param mod_profile_Videos $obj object
	 * @return true
	 */
	protected function afterInsert(&$obj) {
		$thisUser = icms::handler("icms_member")->getUser($obj->getVar('uid_owner'));
		$module = icms::handler("icms_module")->getByDirname(basename(dirname(dirname(__FILE__))), TRUE);
		$tags['VIDEO_TITLE'] = $obj->getVar('video_title');
		$tags['VIDEO_OWNER'] = $thisUser->getVar('uname');
		$tags['VIDEO_URL'] = ICMS_URL.'/modules/'.basename(dirname(dirname(__FILE__))).'/videos.php?uid='.$obj->getVar('uid_owner');
		icms::handler('icms_data_notification')->triggerEvent('videos', $obj->getVar('uid_owner'), 'new_video', $tags, array(), $module->getVar('mid'));

		return true;
	}
}
?>