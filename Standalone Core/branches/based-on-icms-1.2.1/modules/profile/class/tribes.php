<?php

/**
* Classes responsible for managing profile tribes objects
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

define('PROFILE_TRIBES_SECURITY_EVERYBODY', 1);
define('PROFILE_TRIBES_SECURITY_APPROVAL', 2);
define('PROFILE_TRIBES_SECURITY_INVITATION', 3);

class ProfileTribes extends IcmsPersistableSeoObject {

	/**
	 * Constructor
	 *
	 * @param object $handler ProfilePostHandler object
	 */
	public function __construct(& $handler) {
		global $icmsConfig;

		$this->IcmsPersistableObject($handler);

		$this->quickInitVar('tribes_id', XOBJ_DTYPE_INT, true);
		$this->quickInitVar('uid_owner', XOBJ_DTYPE_INT, true);
		$this->quickInitVar('title', XOBJ_DTYPE_TXTBOX, true);
		$this->quickInitVar('tribe_desc', XOBJ_DTYPE_TXTAREA, true);
		$this->quickInitVar('tribe_img', XOBJ_DTYPE_IMAGE, false);
		$this->quickInitVar('creation_time', XOBJ_DTYPE_LTIME, false);
		$this->quickInitVar('security', XOBJ_DTYPE_INT, false, false, false, PROFILE_TRIBES_SECURITY_EVERYBODY);
		$this->initCommonVar('counter', false);
		$this->initCommonVar('dohtml', false, true);
		$this->initCommonVar('dobr', false, true);
		$this->initCommonVar('doimage', false, true);
		$this->initCommonVar('dosmiley', false, true);
		$this->initCommonVar('doxcode', false, true);

		$this->setControl('uid_owner', 'user');
		$this->setControl('tribe_img', array('name' => 'image', 'nourl' => true));
		$this->setControl('tribe_desc', 'dhtmltextarea');
		$this->setControl('security', array (
			'itemHandler' => 'tribes',
			'method' => 'getTribes_securityArray',
			'module' => 'profile'
		));

		$this->hideFieldFromForm('creation_time');

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

	/**
	 * get the colorboxed tribe picture
	 *
	 * @return string colorboxed image of the tribe
	 */
	function getTribePicture() {
		if ($this->getVar('tribe_img')) {
			$ret = '<a href="' . ICMS_URL . '/uploads/profile/tribes/resized_' . $this->getVar ( 'tribe_img' ) . '" rel="lightbox" title="' . $this->getVar ( 'title' ) . '">
					<img class="thumb" src="' . ICMS_URL . '/uploads/profile/tribes/thumb_' . $this->getVar ( 'tribe_img' ) . '" rel="lightbox" title="' . $this->getVar ( 'title' ) . '" />
					</a>';
		} else {
			$ret = '';
		}
		return $ret;
	}

	/**
	 * get the linked tribe picture
	 *
	 * @param string $itemUrl link to the tribe
	 * @return string linked image or title of the tribe
	 */
	function getTribePictureLink($itemUrl) {
		if ($this->getVar('tribe_img')) {
			$ret = '<a href="'.$itemUrl.'">
					<img class="thumb" src="' . ICMS_URL . '/uploads/profile/tribes/thumb_' . $this->getVar ( 'tribe_img' ) . '" rel="lightbox" title="' . $this->getVar ( 'title' ) . '" />
					</a>';
		} else {
			$ret = '<a href="'.$itemUrl.'">'.$this->getVar('title').'</a>';
		}
		return $ret;
	}

	/**
	 * Get the avatar for the tribe owner
	 *
	 * @global array $icmsConfigUser user configuration
	 * @return string html image tag for the avatar of the user
	 */
	function getProfileTribeSenderAvatar() {
		global $icmsConfigUser;

		$member_handler =& xoops_gethandler('member');
		$thisUser =& $member_handler->getUser($this->getVar('uid_owner', 'e'));
		$avatar = $thisUser->gravatar();
		if (!$icmsConfigUser['avatar_allow_gravatar'] && strpos($avatar, 'http://www.gravatar.com/avatar/') !== false) return false;
		return '<img src="'.$thisUser->gravatar().'" />';
	}

	/**
	 * Generate the linked user name
	 *
	 * @return string linked username
	 */
	function getTribeSender() {
		return icms_getLinkedUnameFromId($this->getVar('uid_owner', 'e'));
	}

	/**
	 * return linked tribe title
	 *
	 * return string linked tribe title
	 */
	function getLinkedTribeTitle() {
		$link = $this->handler->_moduleUrl.$this->handler->_page.'?uid='.$this->getVar('uid_owner').'&tribes_id='.$this->getVar('tribes_id');
		return '<a href="'.$link.'">'.$this->getVar('title').'</a>';
	}

	/**
	 * Check to see wether the current user can edit or delete this tribe
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
	 * check if a user is a member of the tribe
	 *
	 * @param int $uid user ID
	 * @return bool true if $uid is a member of this tribe
	 */
	function isMember($uid) {
		global $icmsUser, $profile_isAdmin;

		if (!is_object($icmsUser)) return false;

		$profile_tribeuser_handler = icms_getmodulehandler('tribeuser');
		if ($this->getVar('security') == PROFILE_TRIBES_SECURITY_EVERYBODY) {
			$tribeusers = $profile_tribeuser_handler->getTribeusers(0, 1, $icmsUser->getVar('uid'), false, $this->getVar('tribes_id'));
		} elseif ($this->getVar('security') == PROFILE_TRIBES_SECURITY_APPROVAL) {
			$tribeusers = $profile_tribeuser_handler->getTribeusers(0, 1, $icmsUser->getVar('uid'), false, $this->getVar('tribes_id'), '=', 1);
		} elseif ($this->getVar('security') == PROFILE_TRIBES_SECURITY_INVITATION) {
			$tribeusers = $profile_tribeuser_handler->getTribeusers(0, 1, $icmsUser->getVar('uid'), false, $this->getVar('tribes_id'), '=', false, 1);
		}
		if (is_array($tribeusers) && (count($tribeusers) == 1 || $profile_isAdmin || $uid == $this->getVar('uid_owner'))) return true;

		return false;
	}

	/**
	 * Overridding IcmsPersistable::toArray() method to add a few info
	 *
	 * @return array of tribe info
	 */
	function toArray() {
		$ret = parent :: toArray();
		$ret['itemLink'] = str_replace($this->handler->_itemname.'.php?', $this->handler->_itemname.'.php?uid='.$this->getVar('uid_owner').'&', $ret['itemLink']);
		$ret['itemUrl'] = str_replace($this->handler->_itemname.'.php?', $this->handler->_itemname.'.php?uid='.$this->getVar('uid_owner').'&', $ret['itemUrl']);
		$ret['creation_time'] = formatTimestamp($this->getVar('creation_time', 'e'), 'm');
		$ret['creation_time_short'] = formatTimestamp($this->getVar('creation_time', 'e'), 's');
		$ret['tribe_title'] = $this->getVar('title','e');
		$ret['tribe_content'] = $this->getTribePicture();
		$ret['picture_link'] = $this->getTribePictureLink($ret['itemUrl']);
		$ret['editItemLink'] = $this->getEditItemLink(false, true, true);
		$ret['deleteItemLink'] = $this->getDeleteItemLink(false, true, true);
		$ret['userCanEditAndDelete'] = $this->userCanEditAndDelete();
		$ret['tribe_senderid'] = $this->getVar('uid_owner','e');
		$ret['tribe_sender_link'] = $this->getTribeSender();
		$ret['tribe_sender_avatar'] = $this->getProfileTribeSenderAvatar();
		return $ret;
	}
}

class ProfileTribesHandler extends IcmsPersistableObjectHandler {

	public $_allTribes;
	public $_tribes_security = array();
	private $_tribesImgBeforeUnlink = '';

	/**
	 * Constructor
	 */
	public function __construct(& $db) {
		global $icmsModuleConfig;
		$this->IcmsPersistableObjectHandler($db, 'tribes', 'tribes_id', 'title', '', 'profile');
		$this->enableUpload(array('image/gif', 'image/jpeg', 'image/pjpeg', 'image/png'), $icmsModuleConfig['maxfilesize_picture'], $icmsModuleConfig['max_original_width'], $icmsModuleConfig['max_original_height']);
	}

	/**
	 * Create the criteria that will be used by getTribes and getTribesCount
	 *
	 * @param int $start to which record to start
	 * @param int $limit limit of tribes to return
	 * @param int $uid_owner if specifid, only the tribes of this user will be returned
	 * @param int $tribes_id ID of a single tribe to retrieve, may also be an array of tribe IDs
	 * @param bool $sortByTitle true to sort by title ascending
	 * @return CriteriaCompo $criteria
	 */
	function getTribesCriteria($start = 0, $limit = 0, $uid_owner = false, $tribes_id = false, $sortByTitle = false) {
		global $icmsUser;

		$criteria = new CriteriaCompo();
		if ($start) {
			$criteria->setStart($start);
		}
		if ($limit) {
			$criteria->setLimit(intval($limit));
		}
		if ($sortByTitle) {
			$criteria->setSort('title');
		} else {
			$criteria->setSort('creation_time');
			$criteria->setOrder('DESC');
		}

		if ($uid_owner) {
			$criteria->add(new Criteria('uid_owner', $uid_owner));
		}
		if ($tribes_id) {
			if (!is_array($tribes_id)) $tribes_id = array($tribes_id);
			$tribes_id = '('.implode(',', $tribes_id).')';
			$criteria->add(new Criteria('tribes_id', $tribes_id, 'IN'));
		}
		return $criteria;
	}

	/**
	 * Get single tribe object
	 *
	 * @param int $tribes_id
	 * @return object ProfileTribe object
	 */
	function getTribe($tribes_id=false, $uid_owner=false) {
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
	function getTribes($start = 0, $limit = 0, $uid_owner = false, $tribes_id = false, $sortByTitle = false) {
		$criteria = $this->getTribesCriteria($start, $limit, $uid_owner, $tribes_id, $sortByTitle);
		$ret = $this->getObjects($criteria, true, false);
		return $ret;
	}

	/**
	 * Get all Tribes ordered by title
	 *
	 * @return array of all tribes orderd by title
	 */
	function getAllTribes() {
		if (is_array($this->_allTribes)) return $this->_allTribes;

		$this->_allTribes = array();
		$criteria = new CriteriaCompo();
		$criteria->setSort('title');
		$criteria->setOrder('ASC');
		$tribes = $this->getObjects($criteria, true, false);
		foreach($tribes as $tribe) {
			$this->_allTribes[$tribe['tribes_id']] = $tribe['title'];
		}
		return $this->_allTribes;
	}

	/**
	 * Get all tribes where the specified user is a member
	 *
	 * @param int $uid_owner
	 * @return array of all tribes where the specified user is a member
	 */
	function getMembershipTribes($uid) {
		$profile_tribeuser_handler = icms_getModuleHandler('tribeuser');
		$tribe_users = $profile_tribeuser_handler->getTribeusers(0, 0, $uid, false, false, '=', 1, 1);

		$tribe_ids = array();
		foreach ($tribe_users as $tribe_user) {
			$tribe_ids[] = $tribe_user['tribe_id'];
		}

		if (count($tribe_ids) > 0) {
			return $this->getTribes(0, 0, false, $tribe_ids);
		} else {
			return array();
		}
	}

	function searchTribes($title) {
		$criteria = new CriteriaCompo();
		$criteria->setSort('title');
		$criteria->add(new Criteria('title', '%'.$title.'%', 'LIKE'));
		$criteria->add(new Criteria('tribe_desc', '%'.$title.'%', 'LIKE'), 'OR');
		$ret = $this->getObjects($criteria, true, false);
		return $ret;
	}

	function getTribes_securityArray() {
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
	* @return nothing
	*/
	function imageResizer($img, $width=320, $height=240, $path_upload=ICMS_UPLOAD_PATH, $prefix='') {
		$prefix = (isset($prefix) && $prefix != '')?$prefix:time();
		$path = pathinfo($img);
		$img = WideImage::load($img);
		$img->resizeDown($width, $height)->saveToFile($path_upload.'/'.$prefix.'_'.$path['basename']);
	}

	/**
	* Resize a tribe and save it to $path_upload
	*
	* @param text $img the path to the file
	* @param text $path_upload The path to where the files should be saved after resizing
	* @param int $thumbwidth the width in pixels that the thumbnail will have
	* @param int $thumbheight the height in pixels that the thumbnail will have
	* @param int $pictwidth the width in pixels that the pic will have
	* @param int $pictheight the height in pixels that the pic will have
	* @return nothing
	*/
	function resizeImage($img, $thumbwidth, $thumbheight, $pictwidth, $pictheight,$path_upload) {
		$this->imageResizer($img, $thumbwidth, $thumbheight, $path_upload, 'thumb');
		$this->imageResizer($img, $pictwidth, $pictheight, $path_upload, 'resized');
	}

	/**
	 * Check wether the current user can submit a new tribe or not
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

	/**
	 * AfterSave event
	 *
	 * Event automatically triggered by IcmsPersistable Framework after the object is inserted or updated
	 *
	 * @param object $obj ProfileTribes object
	 * @return true
	 */
	function afterSave(& $obj) {
		global $icmsModuleConfig;

		// only resize images if image is provided
		$imgName = $obj->getVar('tribe_img');
		if (empty($imgName)) return true;

		// Resizing Images!
		$imgPath = ICMS_UPLOAD_PATH.'/profile/tribes/';
		$img = $imgPath.$imgName;
		$this->resizeImage($img, $icmsModuleConfig['thumb_width'], $icmsModuleConfig['thumb_height'], $icmsModuleConfig['resized_width'], $icmsModuleConfig['resized_height'],$imgPath);
		return true;
	}

	/*
	 * beforeDelete event
	 *
	 * Event automatically triggered by IcmsPersistable Framework before the object is deleted
	 *
	 * @param object $obj ProfileTribes object
	 * @return bool
	 */
	function beforeDelete(&$obj) {
		// delete all tribe users
		$profile_tribeuser_handler = icms_getModuleHandler('tribeuser');
		$rtn = $profile_tribeuser_handler->deleteAll(new CriteriaCompo(new Criteria('tribe_id', $obj->getVar('tribes_id'))));
		// delete all tribe topics (not triggering beforeDelete or afterDelete events in tribetopic object)
		$profile_tribetopic_handler = icms_getModuleHandler('tribetopic');
		$rtn = $rtn && $profile_tribetopic_handler->deleteAll(new CriteriaCompo(new Criteria('tribes_id', $obj->getVar('tribes_id'))));
		// delete all tribe posts (not triggering beforeDelete or afterDelete events in tribepost object)
		$profile_tribepost_handler = icms_getModuleHandler('tribepost');
		return $rtn && $profile_tribepost_handler->deleteAll(new CriteriaCompo(new Criteria('tribes_id', $obj->getVar('tribes_id'))));
	}

	/*
	 * afterDelete event
	 *
	 * Event automatically triggered by IcmsPersistable Framework after the object is deleted
	 *
	 * @param object $obj ProfileTribes object
	 * @return bool
	 */
	function afterDelete(&$obj) {
		$imgPath = ICMS_UPLOAD_PATH.'/profile/tribes/';
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
	 * @param object $obj ProfileTribes object
	 * @return bool
	 */
	function beforeFileUnlink(&$obj) {
		$this->_tribesImgBeforeUnlink = $obj->getVar('tribe_img');
		return true;
	}

	/*
	 * afterFileUnlink event
	 *
	 * Event automatically triggered by IcmsPersistable Framework after an Image (field type XOBJ_DTYPE_IMAGE) is unlinked
	 *
	 * @param object $obj ProfileTribes object
	 * @return bool
	 */
	function afterFileUnlink(&$obj) {
		if ($this->_tribesImgBeforeUnlink == $obj->getVar('tribe_img')) return true;

		$imgPath = ICMS_UPLOAD_PATH.'/profile/tribes/';
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