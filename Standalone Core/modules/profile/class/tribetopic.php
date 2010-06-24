<?php
/**
 * Classes responsible for managing profile tribetopic objects
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

class ProfileTribetopic extends IcmsPersistableSeoObject {

	/**
	 * Constructor
	 *
	 * @param object $handler ProfileTribetopicHandler object
	 */
	public function __construct(&$handler) {
		$this->IcmsPersistableObject($handler);

		$this->quickInitVar('topic_id', XOBJ_DTYPE_INT, true);
		$this->quickInitVar('tribes_id', XOBJ_DTYPE_INT, true);
		$this->quickInitVar('poster_uid', XOBJ_DTYPE_INT, true);
		$this->quickInitVar('post_id', XOBJ_DTYPE_INT, true);
		$this->quickInitVar('title', XOBJ_DTYPE_TXTBOX, true);
		$this->quickInitVar('closed', XOBJ_DTYPE_INT, true, false, false, 0);
		$this->quickInitVar('replies', XOBJ_DTYPE_INT, false);
		$this->quickInitVar('views', XOBJ_DTYPE_INT, false);
		$this->quickInitVar('last_post_id', XOBJ_DTYPE_INT, true);
		$this->quickInitVar('last_post_time', XOBJ_DTYPE_LTIME, false);
		
		$this->setControl('poster_uid', 'user');
		$this->setControl('closed', 'yesno');

		$this->hideFieldFromForm(array('tribes_id', 'post_uid', 'post_id', 'replies', 'views', 'last_post_id', 'last_post_time'));

		$this->IcmsPersistableSeoObject();
	}

	/**
	 * increment views counter
	 *
	 * @return bool true if incrementing was successfull
	 */
	function incrementViews() {
		$this->setVar('views', $this->getVar('views') + 1);
		return $this->store(true);
	}

	/**
	 * toggle closed status of the topic
	 *
	 * @return bool true if toggleing was successfull
	 */
	function toggleClose() {
		$this->setVar('closed', !$this->getVar('closed'));
		return $this->store(true);
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
	 * @return array of tribetopic info
	 */
	function toArray() {
		global $icmsModuleConfig;

		$ret = parent :: toArray();
		$ret['last_post_time'] = formatTimestamp($this->getVar('last_post_time', 'e'), 'm');
		$ret['poster_uname'] = icms_getLinkedUnameFromId($this->getVar('poster_uid'));
		$ret['userCanEditAndDelete'] = $this->userCanEditAndDelete();
		$ret['itemLink'] = str_replace($this->handler->_itemname.'.php?', 'tribes.php?tribes_id='.$this->getVar('tribes_id').'&', $ret['itemLink']);
		$ret['itemUrl'] = str_replace($this->handler->_itemname.'.php?', 'tribes.php?tribes_id='.$this->getVar('tribes_id').'&', $ret['itemUrl']);
		$ret['editItemLink'] = str_replace($this->handler->_itemname.'.php?op=mod', 'tribes.php?tribes_id='.$this->getVar('tribes_id').'&amp;op=modtribetopic', $this->getEditItemLink(false, true, true));
		$ret['deleteItemLink'] = str_replace($this->handler->_itemname.'.php?op=del', 'tribes.php?tribes_id='.$this->getVar('tribes_id').'&amp;op=deltribetopic', $this->getDeleteItemLink(false, true, true));
		// build last post link
		$start = '';
		if ($this->getVar('replies') + 1 > $icmsModuleConfig['tribepostsperpage']) {
			$start = '&start='.(($this->getVar('replies') + 1) - (($this->getVar('replies') + 1) % $icmsModuleConfig['tribepostsperpage']));
		}
		$ret['lastItemLink'] = '<a href="'.$ret['itemUrl'].$start.'#post'.$this->getVar('last_post_id').'"><img src="'.$this->handler->_moduleUrl.'/images/comments.gif" title="'._MD_PROFILE_TRIBETOPIC_SHOW_LAST_POST.'" style="vertical-align:middle;" /></a>';
		// build toggle close item link
		if ($this->getVar('closed')) {
			// link to reopen the topic
			$ret['closedIcon'] = '<img src="'.$this->handler->_moduleUrl.'/images/lock.gif" title="'._MD_PROFILE_TRIBETOPIC_CLOSE.'" style="vertical-align:middle;" />';
			$ret['toggleCloseLink'] = '<a href="'.$ret['itemUrl'].'&amp;op=toggleclose"><img src="'.$this->handler->_moduleUrl.'/images/unlock.gif" title="'._MD_PROFILE_TRIBETOPIC_REOPEN.'" style="vertical-align:middle;" /></a>';
		} else {
			// link to close the topic
			$ret['toggleCloseLink'] = '<a href="'.$ret['itemUrl'].'&amp;op=toggleclose"><img src="'.$this->handler->_moduleUrl.'/images/lock.gif" title="'._MD_PROFILE_TRIBETOPIC_CLOSE.'" style="vertical-align:middle;" /></a>';
		}
		
		return $ret;
	}
}

class ProfileTribetopicHandler extends IcmsPersistableObjectHandler {
	/**
	 * Constructor
	 *
	 * @param object $db database object
	 */
	public function __construct(&$db) {
		$this->IcmsPersistableObjectHandler($db, 'tribetopic', 'topic_id', 'title', '', 'profile');
	}

	/**
	 * Create the criteria that will be used by getTopics
	 *
	 * @param int $start to which record to start
	 * @param int $limit limit of topics to return
	 * @param int $topic_id id of the topic
	 * @param int $tribes_id id of the tribe the topic belongs to
	 * @return CriteriaCompo $criteria
	 */
	function getTopicCriteria($start = 0, $limit = 0, $topic_id = false, $tribes_id = false) {
		$criteria = new CriteriaCompo();
		if ($start) $criteria->setStart(intval($start));
		if ($limit) $criteria->setLimit(intval($limit));

		$criteria->setSort('last_post_time');
		$criteria->setOrder('DESC');

		if ($topic_id) $criteria->add(new Criteria('topic_id', $topic_id));
		if ($tribes_id) $criteria->add(new Criteria('tribes_id', $tribes_id));

		return $criteria;
	}

	/**
	 * Get topics
	 *
	 * @param int $start to which record to start
	 * @param int $limit limit of topics to return
	 * @param int $topic_id id of the topic
	 * @param int $tribes_id id of the tribe the topic belongs to
	 * @return array of tribe topics
	 */
	function getTopics($start = 0, $limit = 0, $topic_id = false, $tribes_id = false) {
		$criteria = $this->getTopicCriteria($start, $limit, $topic_id, $tribes_id);
		$ret = $this->getObjects($criteria, true, false);
		return $ret;
	}

	/*
	 * beforeDelete event
	 *
	 * Event automatically triggered by IcmsPersistable Framework before the object is deleted
	 *
	 * @param object $obj ProfileTribestopic object
	 * @return bool
	 */
	function beforeDelete(&$obj) {
		$profile_tribepost_handler = icms_getModuleHandler('tribepost');
		return $profile_tribepost_handler->deleteAll(new CriteriaCompo(new Criteria('topic_id', $obj->getVar('topic_id'))));
	}
}
?>