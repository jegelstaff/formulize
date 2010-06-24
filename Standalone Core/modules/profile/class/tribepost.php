<?php
/**
 * Classes responsible for managing profile tribepost objects
 *
 * @copyright	GNU General Public License (GPL)
 * @license	http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @since	1.3
 * @package	profile
 * @version	$Id$
 */

if (!defined("ICMS_ROOT_PATH")) die("ICMS root path not defined");

// including the IcmsPersistabelSeoObject
include_once ICMS_ROOT_PATH.'/kernel/icmspersistableseoobject.php';
include_once ICMS_ROOT_PATH.'/modules/profile/include/functions.php';

class ProfileTribepost extends IcmsPersistableSeoObject {

	/**
	 * Constructor
	 *
	 * @param object $handler ProfileTribepostHandler object
	 */
	public function __construct(&$handler) {
		$this->IcmsPersistableObject($handler);

		$this->quickInitVar('post_id', XOBJ_DTYPE_INT, true);
		$this->quickInitVar('topic_id', XOBJ_DTYPE_INT, true);
		$this->quickInitVar('tribes_id', XOBJ_DTYPE_INT, true);
		$this->quickInitVar('poster_uid', XOBJ_DTYPE_INT, true);
		$this->quickInitVar('title', XOBJ_DTYPE_TXTBOX, true);
		$this->quickInitVar('body', XOBJ_DTYPE_TXTAREA, true);
		$this->quickInitVar('attachsig', XOBJ_DTYPE_INT, true, false, false, 1);
		$this->quickInitVar('post_time', XOBJ_DTYPE_LTIME, false);
		$this->initCommonVar('dohtml', false, true);
		$this->initCommonVar('dobr', false, true);
		$this->initCommonVar('doimage', false, true);
		$this->initCommonVar('dosmiley', false, true);
		$this->initCommonVar('doxcode', false, true);

		$this->setControl('poster_uid', 'user');
		$this->setControl('body', 'dhtmltextarea');
		$this->setControl('attachsig', 'yesno');

		$this->hideFieldFromForm(array('topic_id', 'tribes_id', 'poster_uid', 'post_time'));

		$this->IcmsPersistableSeoObject();
	}

	/**
	 * Check to see wether the current user can edit or delete this tribe
	 *
	 * @return bool true if he can, false if not
	 */
	function userCanEditAndDelete() {
		global $icmsUser, $profile_isAdmin;

		if (!is_object($icmsUser)) return false;
		if ($profile_isAdmin) return true;

		return $this->getVar('poster_uid', 'e') == $icmsUser->getVar('uid');
	}

	/**
	 * Overridding IcmsPersistable::toArray() method to add a few info
	 *
	 * @global object $icmsUser current user object
	 * @global array $icmsConfigUser user configuration
	 * @return array of tribetopic info
	 */
	function toArray() {
		global $icmsUser, $icmsConfigUser;

		$ret = parent :: toArray();
		$ret['post_time'] = formatTimestamp($this->getVar('post_time', 'e'), 'm');
		$ret['poster_uname'] = icms_getLinkedUnameFromId($this->getVar('poster_uid'));
		$ret['userCanEditAndDelete'] = $this->userCanEditAndDelete();
		// get poster avatar
		$member_handler =& xoops_gethandler('member');
		$thisUser =& $member_handler->getUser($this->getVar('poster_uid'));
		$avatar = $thisUser->gravatar();
		if ($icmsConfigUser['avatar_allow_gravatar'] || strpos($avatar, 'http://www.gravatar.com/avatar/') === false) $ret['poster_avatar'] =  '<img src="'.$thisUser->gravatar().'" />';
		// get poster signature
		if (trim($thisUser->getVar('user_sig')) && $this->getVar('attachsig')) {
			$myts =& MyTextSanitizer::getInstance();
			$ret['poster_signature'] = $myts->displayTarea($thisUser->getVar('user_sig', 'N'), 1, 1, 1);
		}
		// rewrite edit and delete item links to work with tribes.php
		$ret['editItemLink'] = str_replace($this->handler->_itemname.'.php?op=mod', 'tribes.php?tribes_id='.$this->getVar('tribes_id').'&amp;topic_id='.$this->getVar('topic_id').'&amp;op=edittribepost', $this->getEditItemLink(false, true, true));
		$ret['deleteItemLink'] = str_replace($this->handler->_itemname.'.php?op=del', 'tribes.php?tribes_id='.$this->getVar('tribes_id').'&amp;topic_id='.$this->getVar('topic_id').'&amp;op=deltribepost', $this->getDeleteItemLink(false, true, true));
		return $ret;
	}
}

class ProfileTribepostHandler extends IcmsPersistableObjectHandler {

	/**
	 * Constructor
	 *
	 * @param object $db database object
	 */
	public function __construct(&$db) {
		$this->IcmsPersistableObjectHandler($db, 'tribepost', 'post_id', 'title', '', 'profile');
	}

	/**
	 * Create the criteria that will be used by getPosts
	 *
	 * @param int $start to which record to start
	 * @param int $limit limit of posts to return
	 * @param int $post_id id of the post
	 * @param int $topic_id id of the topic the post belongs to
	 * @param int $tribes_id id of the tribe the topic / post belongs to
	 * @param string $order sort order for the result list
	 * @return CriteriaCompo $criteria
	 */
	function getPostCriteria($start = 0, $limit = 0, $post_id = false, $topic_id = false, $tribes_id = false, $order = 'ASC') {
		$criteria = new CriteriaCompo();
		if ($start) $criteria->setStart(intval($start));
		if ($limit) $criteria->setLimit(intval($limit));

		$criteria->setSort('post_id');
		$criteria->setOrder($order);

		if ($post_id) $criteria->add(new Criteria('post_id', $post_id));
		if ($topic_id) $criteria->add(new Criteria('topic_id', $topic_id));
		if ($tribes_id) $criteria->add(new Criteria('tribes_id', $tribes_id));

		return $criteria;
	}

	/**
	 * Get posts
	 *
	 * @param int $start to which record to start
	 * @param int $limit limit of posts to return
	 * @param int $post_id id of the post
	 * @param int $topic_id id of the topic the post belongs to
	 * @param int $tribes_id id of the tribe the topic / post belongs to
	 * @param string $order sort order for the result list
	 * @return array of tribe posts
	 */
	function getPosts($start = 0, $limit = 0, $post_id = false, $topic_id = false, $tribes_id = false, $order = 'ASC') {
		$criteria = $this->getPostCriteria($start, $limit, $post_id, $topic_id, $tribes_id, $order);
		$ret = $this->getObjects($criteria, true, false);
		return $ret;
	}

	/*
	 * beforeInsert event
	 *
	 * Event automatically triggered by IcmsPersistable Framework before the object is inserted
	 *
	 * @param object $obj ProfileTribepost object
	 * @return bool
	 */
	function beforeInsert(&$obj) {
		$profile_tribetopic_handler = icms_getmodulehandler('tribetopic');
		$tribetopicObj = $profile_tribetopic_handler->get($obj->getVar('topic_id'));
		if ($tribetopicObj->isNew() || (!$tribetopicObj->isNew() && !$obj->isNew() && $tribetopicObj->getVar('post_id') == $obj->getVar('post_id'))) {
			$tribetopicObj->setVar('tribes_id', $obj->getVar('tribes_id'));
			$tribetopicObj->setVar('poster_uid', $obj->getVar('poster_uid'));
			$tribetopicObj->setVar('title', $obj->getVar('title'));
		}
		if (!$tribetopicObj->isNew() && $obj->isNew()) $tribetopicObj->setVar('replies', $tribetopicObj->getVar('replies') + 1);
		if (!$tribetopicObj->isNew()) $obj->setFieldAsRequired('title', false);
		$ret = $tribetopicObj->store();
		$obj->setVar('topic_id', $tribetopicObj->getVar('topic_id'));
		return $ret;
	}

	/*
	 * afterInsert event
	 *
	 * Event automatically triggered by IcmsPersistable Framework after the object is inserted
	 *
	 * @param object $obj ProfileTribepost object
	 * @return bool
	 */
	function afterInsert(&$obj) {
		$profile_tribetopic_handler = icms_getmodulehandler('tribetopic');
		$tribetopicObj = $profile_tribetopic_handler->get($obj->getVar('topic_id'));
		if ($tribetopicObj->getVar('replies') == 0) $tribetopicObj->setVar('post_id', $obj->getVar('post_id'));
		$tribetopicObj->setVar('last_post_id', $obj->getVar('post_id'));
		$tribetopicObj->setVar('last_post_time', $obj->getVar('post_time'));
		return $tribetopicObj->store();
	}

	/*
	 * beforeUpdate event
	 *
	 * Event automatically triggered by IcmsPersistable Framework before the object is modified
	 *
	 * @param object $obj ProfileTribepost object
	 * @return bool
	 */
	function beforeUpdate(&$obj) {
		$ret = true;
		$profile_tribetopic_handler = icms_getmodulehandler('tribetopic');
		$tribetopicObj = $profile_tribetopic_handler->get($obj->getVar('topic_id'));
		if ($tribetopicObj->getVar('post_id') == $obj->getVar('post_id')) {
			$tribetopicObj->setVar('tribes_id', $obj->getVar('tribes_id'));
			$tribetopicObj->setVar('poster_uid', $obj->getVar('poster_uid'));
			$tribetopicObj->setVar('title', $obj->getVar('title'));
			$ret = $tribetopicObj->store();
		} else {
			$obj->setFieldAsRequired('title', false);
		}
		return $ret;
	}

	/**
	 * afterDelete event
	 *
	 * Event automatically triggered by IcmsPersistable Framework after the object is deleted
	 *
	 * @param object $obj ProfileTribepost object
	 * @return bool
	 */
	function afterDelete(&$obj) {
		$profile_tribetopic_handler = icms_getModuleHandler('tribetopic');
		$tribetopicObj = $profile_tribetopic_handler->get($obj->getVar('topic_id'));
		
		if ($tribetopicObj->getVar('post_id') == $obj->getVar('post_id')) {
			// delete everything if the post is the topic main post
			$ret = $tribetopicObj->delete();
			// redirect the user as the redirect page no longer exists
			if ($ret) {
				redirect_header($obj->handler->_moduleUrl.'tribes.php?tribes_id='.$obj->getVar('tribes_id'), 2, _CO_ICMS_DELETE_SUCCESS);
				exit();
			}
			return $ret;
		} else {
			// decrement post counter
			$tribetopicObj->setVar('replies', $tribetopicObj->getVar('replies') - 1);
			// set new latest post infos if latest post was deleted
			if ($tribetopicObj->getVar('last_post_id') == $obj->getVar('post_id')) {
				$posts = $this->getPosts(0, 1, false, $tribetopicObj->getVar('topic_id'), $tribetopicObj->getVar('tribes_id'), 'DESC');
				$keys = array_keys($posts);
				$tribetopicObj->setVar('last_post_id', $posts[$keys[0]]['post_id']);
				$tribetopicObj->setVar('last_post_time', $posts[$keys[0]]['post_time']);
			}
			return $tribetopicObj->store();
		}
		return true;
	}
}
?>