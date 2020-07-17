<?php
/**
 * Class responsible for managing profile tribetopic objects
 *
 * @copyright	GNU General Public License (GPL)
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @since		1.4
 * @author		phoenyx
 * @package		profile
 * @version		$Id: TribetopicHandler.php 20562 2010-12-19 18:26:36Z phoenyx $
 */

defined("ICMS_ROOT_PATH") or die("ICMS root path not defined");

class mod_profile_TribetopicHandler extends icms_ipf_Handler {
	/**
	 * Constructor
	 *
	 * @param icms_db_legacy_Database $db database object
	 */
	public function __construct(&$db) {
		parent::__construct($db, 'tribetopic', 'topic_id', 'title', '', basename(dirname(dirname(__FILE__))));
	}

	/**
	 * Create the criteria that will be used by getTopics
	 *
	 * @param int $start to which record to start
	 * @param int $limit limit of topics to return
	 * @param int $topic_id id of the topic
	 * @param int $tribes_id id of the tribe the topic belongs to
	 * @return icms_db_criteria_Compo $criteria
	 */
	private function getTopicCriteria($start = 0, $limit = 0, $topic_id = false, $tribes_id = false) {
		$criteria = new icms_db_criteria_Compo();
		if ($start) $criteria->setStart((int)$start);
		if ($limit) $criteria->setLimit((int)$limit);
		$criteria->setSort('last_post_time');
		$criteria->setOrder('DESC');
		if ($topic_id) $criteria->add(new icms_db_criteria_Item('topic_id', (int)$topic_id));
		if ($tribes_id) $criteria->add(new icms_db_criteria_Item('tribes_id', (int)$tribes_id));

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
	public function getTopics($start = 0, $limit = 0, $topic_id = false, $tribes_id = false) {
		$criteria = $this->getTopicCriteria($start, $limit, $topic_id, $tribes_id);
		$ret = $this->getObjects($criteria, true, false);
		return $ret;
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
		$thisUser = icms::handler("icms_member")->getUser($obj->getVar('poster_uid'));
		$module = icms::handler("icms_module")->getByDirname(basename(dirname(dirname(__FILE__))), TRUE);
		$tags['TRIBETOPIC_TITLE'] = $obj->getVar('title');
		$tags['POSTER_UNAME'] = $thisUser->getVar('uname');
		$tags['TRIBETOPIC_URL'] = str_replace($obj->handler->_itemname.'.php?', 'tribes.php?tribes_id='.$obj->getVar('tribes_id').'&', $obj->getItemLink(true));
		$profile_tribes_handler = icms_getModuleHandler('tribes', basename(dirname(dirname(__FILE__))), 'profile');
		$tribesObj = $profile_tribes_handler->get($obj->getVar('tribes_id'));
		$tags['TRIBE_TITLE'] = $tribesObj->getVar('title');
		icms::handler('icms_data_notification')->triggerEvent('tribetopic', $obj->getVar('tribes_id'), 'new_tribetopic', $tags, array(), $module->getVar('mid'));

		return true;
	}

	/*
	 * beforeDelete event
	 *
	 * Event automatically triggered by IcmsPersistable Framework before the object is deleted
	 *
	 * @param ProfileTribestopic $obj ProfileTribestopic object
	 * @return bool
	 */
	protected function beforeDelete(&$obj) {
		$profile_tribepost_handler = icms_getModuleHandler('tribepost', basename(dirname(dirname(__FILE__))), 'profile');
		$module = icms::handler("icms_module")->getByDirname(basename(dirname(dirname(__FILE__))), TRUE);
		$rtn = $profile_tribepost_handler->deleteAll(new icms_db_criteria_Compo(new icms_db_criteria_Item('topic_id', $obj->getVar('topic_id'))));
		// delete all notification subscriptions for this tribetopic
		$rtn = $rtn && icms::handler('icms_data_notification')->unsubscribeByItem($module->getVar('mid'), 'tribepost', $obj->getVar('topic_id'));
		return $rtn;
	}
}
?>