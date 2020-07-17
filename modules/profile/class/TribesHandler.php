<?php
/**
 * Class responsible for managing profile tribes objects
 *
 * @copyright	GNU General Public License (GPL)
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @since		1.4
 * @author		phoenyx
 * @package		profile
 * @version		$Id: TribesHandler.php 22417 2011-08-27 12:56:36Z phoenyx $
 */

defined("ICMS_ROOT_PATH") or die("ICMS root path not defined");

define('PROFILE_TRIBES_SECURITY_EVERYBODY', 1);
define('PROFILE_TRIBES_SECURITY_APPROVAL', 2);
define('PROFILE_TRIBES_SECURITY_INVITATION', 3);

class mod_profile_TribesHandler extends icms_ipf_Handler {
	private $_allTribes;
	private $_tribes_security = array();
	private $_tribesImgBeforeUnlink = '';
	private $_tribeOwners;

	/**
	 * Constructor
	 *
	 * @param icms_db_legacy_Database $db database connection object
	 */
	public function __construct(&$db) {
		parent::__construct($db, 'tribes', 'tribes_id', 'title', '', basename(dirname(dirname(__FILE__))));
		$module = icms::handler("icms_module")->getByDirname(basename(dirname(dirname(__FILE__))), TRUE);
		$this->enableUpload(array('image/gif', 'image/jpeg', 'image/pjpeg', 'image/png'), $module->config['maxfilesize_picture'], $module->config['max_original_width'], $module->config['max_original_height']);
	}

	/**
	 * Get tribe owner as array for acp filter
	 * 
	 * @return array
	 */
	public function getTribeOwnerArray() {
		if ($this->_tribeOwners) return $this->tribeOwners;

		$sql = "SELECT DISTINCT uid, uname FROM " . $this->table . " t, " . icms::$xoopsDB->prefix("users") . " u " .
			   "WHERE u.uid = t.uid_owner ORDER BY u.uname";
		$users = icms::$xoopsDB->query($sql);
		while ($user = icms::$xoopsDB->fetchArray($users))
			$this->_tribeOwners[$user['uid']] = $user['uname'];
		return $this->_tribeOwners;
	}

	/**
	 * Create the criteria that will be used by getTribes and getTribesCount
	 *
	 * @param int $start to which record to start
	 * @param int $limit limit of tribes to return
	 * @param int $uid_owner if specifid, only the tribes of this user will be returned
	 * @param int $tribes_id ID of a single tribe to retrieve, may also be an array of tribe IDs
	 * @param bool $sortByTitle true to sort by title ascending
	 * @return icms_db_criteria_Compo $criteria
	 */
	private function getTribesCriteria($start = 0, $limit = 0, $uid_owner = false, $tribes_id = false, $sortByTitle = false) {
		$criteria = new icms_db_criteria_Compo();
		if ($start) $criteria->setStart((int)$start);
		if ($limit) $criteria->setLimit((int)$limit);
		if ($sortByTitle) {
			$criteria->setSort('title');
		} else {
			$criteria->setSort('creation_time');
			$criteria->setOrder('DESC');
		}

		if ($uid_owner) $criteria->add(new icms_db_criteria_Item('uid_owner', (int)$uid_owner));
		if ($tribes_id) {
			if (!is_array($tribes_id)) $tribes_id = array((int)$tribes_id);
			$tribes_id = '('.implode(',', $tribes_id).')';
			$criteria->add(new icms_db_criteria_Item('tribes_id', $tribes_id, 'IN'));
		}
		return $criteria;
	}

	/**
	 * Get single tribe object
	 *
	 * @param int $tribes_id
	 * @param int $uid_owner
	 * @return object ProfileTribe object
	 */
	public function getTribe($tribes_id = false, $uid_owner = false) {
		$ret = $this->getTribes(0, 0, $uid_owner, $tribes_id);
		return isset($ret[$tribes_id]) ? $ret[$tribes_id] : false;
	}

	/**
	 * Get tribes as array, ordered by creation_time DESC
	 *
	 * @param int $start to which record to start
	 * @param int $limit max tribes to display
	 * @param int $uid_owner if specifid, only the tribe of this user will be returned
	 * @param int $tribes_id ID of a single tribe to retrieve, may also be an array of tribe IDs
	 * @param bool $sortByTitle true to sort by title ascending
	 * @return array of tribes
	 */
	public function getTribes($start = 0, $limit = 0, $uid_owner = false, $tribes_id = false, $sortByTitle = false) {
		$criteria = $this->getTribesCriteria($start, $limit, $uid_owner, $tribes_id, $sortByTitle);
		$ret = $this->getObjects($criteria, true, false);
		return $ret;
	}

	/**
	 * Get all Tribes ordered by title
	 *
	 * @return array of all tribes orderd by title
	 */
	public function getAllTribes() {
		if (is_array($this->_allTribes)) return $this->_allTribes;

		$this->_allTribes = array();
		$criteria = new icms_db_criteria_Compo();
		$criteria->setSort('title');
		$criteria->setOrder('ASC');
		$tribes = $this->getObjects($criteria, true, false);
		foreach ($tribes as $tribe) $this->_allTribes[$tribe['tribes_id']] = $tribe['title'];
		return $this->_allTribes;
	}

	/**
	 * Get all tribes where the specified user is a member
	 *
	 * @param int $uid
	 * @return array of all tribes where the specified user is a member
	 */
	public function getMembershipTribes($uid) {
		$profile_tribeuser_handler = icms_getModuleHandler('tribeuser', basename(dirname(dirname(__FILE__))), 'profile');
		$tribe_users = $profile_tribeuser_handler->getTribeusers(0, 0, $uid, false, false, '=', 1, 1);

		$tribe_ids = array();
		foreach ($tribe_users as $tribe_user) $tribe_ids[] = $tribe_user['tribe_id'];

		if (count($tribe_ids) > 0) {
			return $this->getTribes(0, 0, false, $tribe_ids);
		} else {
			return array();
		}
	}

	/**
	 * Search tribes by title and description
	 *
	 * @param str search string
	 * @return array of mod_profile_Tribes objects
	 */
	public function searchTribes($title) {
		$criteria = new icms_db_criteria_Compo();
		$criteria->setSort('title');
		$criteria->add(new icms_db_criteria_Item('title', '%'.$title.'%', 'LIKE'));
		$criteria->add(new icms_db_criteria_Item('tribe_desc', '%'.$title.'%', 'LIKE'), 'OR');
		$ret = $this->getObjects($criteria, true, false);
		return $ret;
	}

	/**
	 * Build tribe security array
	 *
	 * @return array
	 */
	public function getTribes_securityArray() {
		if (!$this->_tribes_security) {
			$this->_tribes_security[PROFILE_TRIBES_SECURITY_EVERYBODY] = _CO_PROFILE_TRIBES_SECURITY_EVERYBODY;
			$this->_tribes_security[PROFILE_TRIBES_SECURITY_APPROVAL] = _CO_PROFILE_TRIBES_SECURITY_APPROVAL;
			$this->_tribes_security[PROFILE_TRIBES_SECURITY_INVITATION] = _CO_PROFILE_TRIBES_SECURITY_INVITATION;
		}
		return $this->_tribes_security;
	}

	/**
	* Resize a tribe and save it to $path_upload
	*
	* @param text $img the path to the file
	* @param int $width the width in pixels that the pic will have
	* @param int $height the height in pixels that the pic will have
	* @param text $path_upload The path to where the files should be saved after resizing
	* @param text $prefix The prefix used to recognize files and avoid multiple files.
	* @return void
	*/
	private function imageResizer($img, $width = 320, $height = 240, $path_upload = ICMS_UPLOAD_PATH, $prefix = '') {
		$prefix = (isset($prefix) && $prefix != '') ? $prefix : time();
		$path = pathinfo($img);
		if (is_file($path_upload.'/'.$prefix.'_'.$path['basename'])) return;
		$img = WideImage::load($img);
		$img->resizeDown($width, $height)->saveToFile($path_upload.'/'.$prefix.'_'.$path['basename']);
	}

	/**
	* Resize a tribe and save it to $path_upload
	*
	* @param text $img the path to the file
	* @param int $thumbwidth the width in pixels that the thumbnail will have
	* @param int $thumbheight the height in pixels that the thumbnail will have
	* @param int $pictwidth the width in pixels that the pic will have
	* @param int $pictheight the height in pixels that the pic will have
	* @return void
	*/
	private function resizeImage($img, $thumbwidth, $thumbheight, $pictwidth, $pictheight,$path_upload) {
		$this->imageResizer($img, $thumbwidth, $thumbheight, $path_upload, 'thumb');
		$this->imageResizer($img, $pictwidth, $pictheight, $path_upload, 'resized');
	}

	/**
	 * Check wether the current user can submit a new tribe or not
	 *
	 * @return bool true if he can false if not
	 */
	public function userCanSubmit() {
		return is_object(icms::$user);
	}

	/**
	 * Update the counter field of the tribes object
	 *
	 * @param int $tribes_id
	 * @return bool
	 */
	public function updateCounter($tribes_id) {
		$tribesObj = $this->get($tribes_id);
		$tribesObj->setVar('counter', $tribesObj->getVar('counter') + 1);
		return $tribesObj->store(true);
	}

	public function mergeTribes($tribes_id, $merge_tribes_id) {
		// make sure not to join one tribe with itself
		if ($tribes_id == $merge_tribes_id) redirect_header(PROFILE_ADMIN_URL.'tribes.php?op=merge&tribes_id='.$tribes_id, 3, _AM_PROFILE_TRIBES_MERGE_ERR_SAME);

		// make sure both tribes exist
		$tribesObj = $this->get($tribes_id);
		$merge_tribesObj = $this->get($merge_tribes_id);
		if ($tribesObj->isNew() || $merge_tribesObj->isNew()) redirect_header(PROFILE_ADMIN_URL.'tribes.php', 3, _AM_PROFILE_TRIBES_MERGE_ERR_ID);

		$profile_tribetopic_handler = icms_getModuleHandler('tribetopic', basename(dirname(dirname(__FILE__))), 'profile');
		$profile_tribepost_handler = icms_getModuleHandler('tribepost', basename(dirname(dirname(__FILE__))), 'profile');
		$profile_tribeuser_handler = icms_getModuleHandler('tribeuser', basename(dirname(dirname(__FILE__))), 'profile');

		// move the discussions
		$profile_tribetopic_handler->updateAll('tribes_id', $merge_tribes_id, icms_buildCriteria(array('tribes_id' => $tribes_id)));
		$profile_tribepost_handler->updateAll('tribes_id', $merge_tribes_id, icms_buildCriteria(array('tribes_id' => $tribes_id)));

		// get all members of the old tribe and add the owner as a "member"
		$tribeusers = $profile_tribeuser_handler->getObjects(icms_buildCriteria(array('tribe_id' => $tribes_id)));
		$tribeuserObj = $profile_tribeuser_handler->get(0);
		$tribeuserObj->setVar('tribe_id', $tribes_id);
		$tribeuserObj->setVar('user_id', $tribesObj->getVar('uid_owner'));
		$tribeusers[] = $tribeuserObj;
		unset($tribeuserObj);

		// move all users
		foreach ($tribeusers as $tribeuserObj) {
			$merge_tribeusers = $profile_tribeuser_handler->getCount(icms_buildCriteria(array('user_id' => $tribeuserObj->getVar('user_id'), 'tribe_id' => $merge_tribes_id)));
			// we only have to add this user as a user of the new tribe if he isn't already a user and if he isn't the owner
			if ($merge_tribeusers == 0 && $tribeuserObj->getVar('user_id') != $merge_tribesObj->getVar('uid_owner')) {
				$merge_tribeuserObj = $profile_tribeuser_handler->get(0);

				if ($merge_tribesObj->getVar('security') == PROFILE_TRIBES_SECURITY_APPROVAL) {
					$merge_tribeuserObj->setVar('approved', 0);
				} elseif ($merge_tribesObj->getVar('security') == PROFILE_TRIBES_SECURITY_INVITATION) {
					$merge_tribeuserObj->setVar('accepted', 0);
				}

				$merge_tribeuserObj->setVar('tribe_id', $merge_tribesObj->getVar('tribes_id'));
				$merge_tribeuserObj->setVar('user_id', $tribeuserObj->getVar('user_id'));

				$merge_tribeuserObj->store();
			}
			if (!$tribeuserObj->getVar('tribeuser_id') == 0) $tribeuserObj->delete();
		}

		// deleting the old tribe
		$tribesObj->delete();
	}

	/**
	 * AfterSave event
	 *
	 * Event automatically triggered by IcmsPersistable Framework after the object is inserted or updated
	 *
	 * @param mod_profile_Tribes $obj object
	 * @return true
	 */
	protected function afterSave(&$obj) {
		// only resize images if image is provided
		$imgName = $obj->getVar('tribe_img');
		if (empty($imgName)) return true;

		// Resizing Images
		$module = icms::handler("icms_module")->getByDirname(basename(dirname(dirname(__FILE__))), TRUE);
		$this->resizeImage($this->getImagePath().$imgName, $module->config['thumb_width'], $module->config['thumb_height'], $module->config['resized_width'], $module->config['resized_height'], $this->getImagePath());
		return true;
	}

	/*
	 * beforeDelete event
	 *
	 * Event automatically triggered by IcmsPersistable Framework before the object is deleted
	 *
	 * @param mod_profile_Tribes $obj object
	 * @return bool
	 */
	protected function beforeDelete(&$obj) {
		$notification_handler = icms::handler('icms_data_notification');
		$profile_tribetopic_handler = icms_getModuleHandler('tribetopic', basename(dirname(dirname(__FILE__))), 'profile');
		$profile_tribeuser_handler = icms_getModuleHandler('tribeuser', basename(dirname(dirname(__FILE__))), 'profile');
		$profile_tribepost_handler = icms_getModuleHandler('tribepost', basename(dirname(dirname(__FILE__))), 'profile');
		$module = icms::handler("icms_module")->getByDirname(basename(dirname(dirname(__FILE__))), TRUE);

		$rtn = true;

		// delete all notification subscriptions for all topics in this tribe
		$topics = $profile_tribetopic_handler->getList(new icms_db_criteria_Compo(new icms_db_criteria_Item('tribes_id', $obj->getVar('tribes_id'))));
		foreach (array_keys($topics) as $topic_id) $rtn = $rtn && $notification_handler->unsubscribeByItem($module->getVar('mid'), 'tribepost', $topic_id);
		// delete all notification subscriptions for this tribe
		$rtn = $rtn && $notification_handler->unsubscribeByItem($module->getVar('mid'), 'tribetopic', $obj->getVar('tribes_id'));
		// delete all tribe users
		$rtn = $profile_tribeuser_handler->deleteAll(new icms_db_criteria_Compo(new icms_db_criteria_Item('tribe_id', $obj->getVar('tribes_id'))));
		// delete all tribe topics (not triggering beforeDelete or afterDelete events in tribetopic object)
		$rtn = $rtn && $profile_tribetopic_handler->deleteAll(new icms_db_criteria_Compo(new icms_db_criteria_Item('tribes_id', $obj->getVar('tribes_id'))));
		// delete all tribe posts (not triggering beforeDelete or afterDelete events in tribepost object)
		$rtn = $rtn && $profile_tribepost_handler->deleteAll(new icms_db_criteria_Compo(new icms_db_criteria_Item('tribes_id', $obj->getVar('tribes_id'))));

		return $rtn;
	}

	/*
	 * afterDelete event
	 *
	 * Event automatically triggered by IcmsPersistable Framework after the object is deleted
	 *
	 * @param mod_profile_Tribes $obj object
	 * @return bool
	 */
	protected function afterDelete(&$obj) {
		$imgPath = $this->getImagePath();
		$imgUrl = $obj->getVar('tribe_img');

		if (!empty($imgUrl)) {
			unlink($imgPath.$imgUrl);
			unlink($imgPath.'thumb_'.$imgUrl);
			unlink($imgPath.'resized_'.$imgUrl);
		}

		return true;
	}

	/*
	 * beforeFileUnlink event
	 *
	 * Event automatically triggered by IcmsPersistable Framework before an Image (field type XOBJ_DTYPE_IMAGE) is unlinked
	 *
	 * @param mod_profile_Tribes $obj object
	 * @return bool
	 */
	protected function beforeFileUnlink(&$obj) {
		$this->_tribesImgBeforeUnlink = $obj->getVar('tribe_img');
		return true;
	}

	/*
	 * afterFileUnlink event
	 *
	 * Event automatically triggered by IcmsPersistable Framework after an Image (field type XOBJ_DTYPE_IMAGE) is unlinked
	 *
	 * @param mod_profile_Tribes $obj object
	 * @return bool
	 */
	protected function afterFileUnlink(&$obj) {
		if ($this->_tribesImgBeforeUnlink == $obj->getVar('tribe_img')) return true;

		$imgPath = $this->getImagePath();
		$imgUrl = $this->_tribesImgBeforeUnlink;

		if (!empty($imgUrl)) {
			unlink($imgPath.'thumb_'.$imgUrl);
			unlink($imgPath.'resized_'.$imgUrl);
		}

		$this->_tribesImgBeforeUnlink = $obj->getVar('tribe_img');
		return true;
	}
}
?>