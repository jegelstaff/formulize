<?php
/**
 * Class responsible for managing profile tribepost objects
 *
 * @copyright	GNU General Public License (GPL)
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @since		1.4
 * @author		phoenyx
 * @package		profile
 * @version		$Id: TribepostHandler.php 20562 2010-12-19 18:26:36Z phoenyx $
 */

defined("ICMS_ROOT_PATH") or die("ICMS root path not defined");

class mod_profile_TribepostHandler extends icms_ipf_Handler {
	/**
	 * Constructor
	 *
	 * @param icms_db_legacy_Database $db database object
	 */
	public function __construct(&$db) {
		parent::__construct($db, 'tribepost', 'post_id', 'title', '', basename(dirname(dirname(__FILE__))));
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
	 * @return icms_db_criteria_Compo $criteria
	 */
	private function getPostCriteria($start = 0, $limit = 0, $post_id = false, $topic_id = false, $tribes_id = false, $order = 'ASC') {
		$criteria = new icms_db_criteria_Compo();
		if ($start) $criteria->setStart((int)$start);
		if ($limit) $criteria->setLimit((int)$limit);
		$criteria->setSort('post_id');
		$criteria->setOrder($order);
		if ($post_id) $criteria->add(new icms_db_criteria_Item('post_id', (int)$post_id));
		if ($topic_id) $criteria->add(new icms_db_criteria_Item('topic_id', (int)$topic_id));
		if ($tribes_id) $criteria->add(new icms_db_criteria_Item('tribes_id', (int)$tribes_id));

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
	public function getPosts($start = 0, $limit = 0, $post_id = false, $topic_id = false, $tribes_id = false, $order = 'ASC') {
		$criteria = $this->getPostCriteria($start, $limit, $post_id, $topic_id, $tribes_id, $order);
		$ret = $this->getObjects($criteria, true, false);
		return $ret;
	}

	/*
	 * beforeInsert event
	 *
	 * Event automatically triggered by IcmsPersistable Framework before the object is inserted
	 *
	 * @param mod_profile_Tribepost $obj object
	 * @return bool
	 */
	protected function beforeInsert(&$obj) {
		$profile_tribetopic_handler = icms_getModuleHandler('tribetopic', basename(dirname(dirname(__FILE__))), 'profile');
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
	 * @param mod_profile_Tribepost $obj object
	 * @return bool
	 */
	protected function afterInsert(&$obj) {
		$profile_tribetopic_handler = icms_getmodulehandler('tribetopic', basename(dirname(dirname(__FILE__))), 'profile');
		$tribetopicObj = $profile_tribetopic_handler->get($obj->getVar('topic_id'));

		// send notifications for new post if this is a reply
		if ($tribetopicObj->getVar('replies') > 0) {
			$thisUser = icms::handler("icms_member")->getUser($obj->getVar('poster_uid'));
			$module = icms::handler("icms_module")->getByDirname(basename(dirname(dirname(__FILE__))), TRUE);
			$tags['TRIBETOPIC_TITLE'] = $tribetopicObj->getVar('title');
			$tags['POSTER_UNAME'] = $thisUser->getVar('uname');
			$start = '';
			if ($tribetopicObj->getVar('replies') + 1 > $module->config['tribepostsperpage']) {
				$start = '&start='.(($tribetopicObj->getVar('replies') + 1) - (($tribetopicObj->getVar('replies') + 1) % $module->config['tribepostsperpage']));
			}
			$tags['TRIBEPOST_URL'] = str_replace($tribetopicObj->handler->_itemname.'.php?', 'tribes.php?tribes_id='.$obj->getVar('tribes_id').'&', $tribetopicObj->getItemLink(true));
			$tags['TRIBEPOST_URL'] = $tags['TRIBEPOST_URL'].$start.'#post'.$obj->getVar('post_id');
			$profile_tribes_handler = icms_getModuleHandler('tribes', basename(dirname(dirname(__FILE__))), 'profile');
			$tribesObj = $profile_tribes_handler->get($obj->getVar('tribes_id'));
			$tags['TRIBE_TITLE'] = $tribesObj->getVar('title');
			icms::handler('icms_data_notification')->triggerEvent('tribepost', $obj->getVar('topic_id'), 'new_tribepost', $tags, array(), $module->getVar('mid'));
		}

		// update tribetopic object
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
	 * @param mod_profile_Tribepost $obj object
	 * @return bool
	 */
	protected function beforeUpdate(&$obj) {
		$ret = true;
		$profile_tribetopic_handler = icms_getModuleHandler('tribetopic', basename(dirname(dirname(__FILE__))), 'profile');
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
	 * @param mod_profile_Tribepost $obj object
	 * @return bool
	 */
	protected function afterDelete(&$obj) {
		$profile_tribetopic_handler = icms_getModuleHandler('tribetopic', basename(dirname(dirname(__FILE__))), 'profile');
		$tribetopicObj = $profile_tribetopic_handler->get($obj->getVar('topic_id'));

		if ($tribetopicObj->getVar('post_id') == $obj->getVar('post_id')) {
			// delete everything if the post is the topic main post
			$ret = $tribetopicObj->delete();
			// redirect the user as the redirect page no longer exists
			if ($ret) redirect_header($this->_moduleUrl.'tribes.php?tribes_id='.$obj->getVar('tribes_id'), 2, _CO_ICMS_DELETE_SUCCESS);
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