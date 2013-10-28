<?php
/**
 * Classes responsible for managing profile audio objects
 *
 * @copyright	GNU General Public License (GPL)
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @since		1.4
 * @author		phoenyx
 * @package		profile
 * @version		$Id: AudioHandler.php 20562 2010-12-19 18:26:36Z phoenyx $
 */

defined("ICMS_ROOT_PATH") or die("ICMS root path not defined");

class mod_profile_AudioHandler extends icms_ipf_Handler {
	/**
	 * Constructor
	 *
	 * @param icms_db_legacy_Database $db database connection object
	*/
	public function __construct(&$db) {
		parent::__construct($db, 'audio', 'audio_id', 'title', '', basename(dirname(dirname(__FILE__))));
		$module = icms::handler("icms_module")->getByDirname(basename(dirname(dirname(__FILE__))), TRUE);
		$this->enableUpload(array("audio/mp3" , "audio/x-mp3", "audio/mpeg"), $module->config['maxfilesize_audio']);
	}

	/**
	 * Create the criteria that will be used by getAudios and getAudioCount
	 *
	 * @param int $start to which record to start
	 * @param int $limit limit of audio to return
	 * @param int $uid_owner if specifid, only the audio of this user will be returned
	 * @param int $audio_id ID of a single audio to retrieve
	 * @return icms_db_criteria_Compo $criteria
	 */
	private function getAudioCriteria($start = 0, $limit = 0, $uid_owner = false, $audio_id = false) {
		$criteria = new icms_db_criteria_Compo();
		if ($start) $criteria->setStart((int)$start);
		if ($limit) $criteria->setLimit((int)$limit);
		if ($uid_owner) $criteria->add(new icms_db_criteria_Item('uid_owner', (int)$uid_owner));
		if ($audio_id) $criteria->add(new icms_db_criteria_Item('audio_id', (int)$audio_id));
		$criteria->setSort('creation_time');
		$criteria->setOrder('DESC');

		return $criteria;
	}

	/**
	 * Get audio as array, ordered by creation_time DESC
	 *
	 * @param int $start to which record to start
	 * @param int $limit max audio to display
	 * @param int $uid_owner if specifid, only the audio of this user will be returned
	 * @param int $audio_id ID of a single audio to retrieve
	 * @return array of audio objects
	 */
	public function getAudios($start = 0, $limit = 0, $uid_owner = false, $audio_id = false) {
		$criteria = $this->getAudioCriteria($start, $limit, $uid_owner, $audio_id);
		$ret = $this->getObjects($criteria, true, false);
		return $ret;
	}

	/**
	 * Check wether the current user can submit a new audio or not
	 *
	 * @return bool true if he can false if not
	 */
	public function userCanSubmit() {
		return is_object(icms::$user);
	}

	/**
	 * Check whether the user has already reached the upload limit
	 *
	 * @return int number of audios for the current user
	 */
	public function checkUploadLimit() {
		if (!is_object(icms::$user)) return false;
		$module = icms::handler("icms_module")->getByDirname(basename(dirname(dirname(__FILE__))), TRUE);
		if ($module->config['nb_audio'] == 0) return true;
		$count = $this->getCount(new icms_db_criteria_Compo(new icms_db_criteria_Item('uid_owner', icms::$user->getVar('uid'))));
		return ($count < $module->config['nb_audio']);
	}

	/**
	 * BeforeUpdate event
	 *
	 * Event automatically triggered by IcmsPersistable Framework before the object is updated
	 *
	 * @param mod_profile_Audio $obj object
	 * @return bool true
	 */
	protected function beforeUpdate(&$obj) {
		$obj->setVar('creation_time', date(_DATESTRING));
		return true;
	}

	/**
	 * AfterInsert event
	 *
	 * Event automatically triggered by IcmsPersistable Framework after the object is inserted
	 *
	 * @param mod_profile_Audio $obj object
	 * @return bool true
	 */
	protected function afterInsert(&$obj) {
		$thisUser = icms::handler("icms_member")->getUser($obj->getVar('uid_owner'));
		$module = icms::handler("icms_module")->getByDirname(basename(dirname(dirname(__FILE__))), TRUE);
		$tags['AUDIO_TITLE'] = $obj->getVar('title');
		$tags['AUDIO_OWNER'] = $thisUser->getVar('uname');
		$tags['AUDIO_URL'] = ICMS_URL.'/modules/'.basename(dirname(dirname(__FILE__))).'/audio.php?uid='.$obj->getVar('uid_owner');
		icms::handler('icms_data_notification')->triggerEvent('audio', $obj->getVar('uid_owner'), 'new_audio', $tags, array(), $module->getVar('mid'));

		return true;
	}

	/*
	 * afterDelete event
	 *
	 * Event automatically triggered by IcmsPersistable Framework after the object is deleted
	 *
	 * @param mod_profile_Audio $obj object
	 * @return bool true
	 */
	protected function afterDelete(&$obj) {
		$url = $obj->getVar('url');
		if (!empty($url)) @unlink($this->getImagePath().$url);

		return true;
	}
}
?>