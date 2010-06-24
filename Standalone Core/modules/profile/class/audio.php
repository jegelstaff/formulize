<?php

/**
* Classes responsible for managing profile audio objects
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

class ProfileAudio extends IcmsPersistableSeoObject {

	/**
	 * Constructor
	 *
	 * @param object $handler ProfilePostHandler object
	 */
	public function __construct(& $handler) {
		global $icmsConfig;

		$this->IcmsPersistableObject($handler);

		$this->quickInitVar('audio_id', XOBJ_DTYPE_INT, true);
		$this->quickInitVar('author', XOBJ_DTYPE_TXTBOX, true);
		$this->quickInitVar('title', XOBJ_DTYPE_TXTBOX, true);
		$this->quickInitVar('url', XOBJ_DTYPE_TXTBOX, true);
		$this->quickInitVar('uid_owner', XOBJ_DTYPE_INT, true);
		$this->quickInitVar('creation_time', XOBJ_DTYPE_LTIME, false);
		$this->quickInitVar('data_update', XOBJ_DTYPE_TXTBOX, false);
		$this->initCommonVar('counter', false);
		$this->initCommonVar('dohtml', false, true);
		$this->initCommonVar('dobr', false, true);
		$this->initCommonVar('doimage', false, true);
		$this->initCommonVar('dosmiley', false, true);
		$this->initCommonVar('doxcode', false, true);
		$this->setControl('url', 'upload');

		$this->hideFieldFromForm('data_update');
		$this->setControl('uid_owner', 'user');




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
	function getAudioSender() {
		return icms_getLinkedUnameFromId($this->getVar('uid_owner', 'e'));
	}
	function getAudioToDisplay() {
		$ret = '<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=7,0,0,0" width="240" height="20" id="dewplayer" align="middle"><param name="wmode" value="transparent"><param name="allowScriptAccess" value="sameDomain" /><param name="movie" value="'.ICMS_URL.'/modules/profile/assets/audioplayers/dewplayer-multi.swf?mp3='.ICMS_URL.'/uploads/profile/audio/'.$this->getVar('url', 'e').'" /><param name="quality" value="high" /><param name="bgcolor" value="FFFFFF" /><embed src="'.ICMS_URL.'/modules/profile/assets/audioplayers/dewplayer-multi.swf?mp3='.ICMS_URL.'/uploads/profile/audio/'.$this->getVar('url', 'e').'" quality="high" bgcolor="FFFFFF" width="240" height="20" name="dewplayer" wmode="transparent" align="middle" allowScriptAccess="sameDomain" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer"></embed></object>';
		return $ret;
	}

	/**
	 * return audio title
	 *
	 * @return string audio title
	 */
	function getAudioTitle() {
		return $this->getVar('title');
	}

	/**
	 * Check to see wether the current user can edit or delete this audio
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
	 * @return array of audio info
	 */
	function toArray() {
		$ret = parent :: toArray();
		$ret['creation_time'] = formatTimestamp($this->getVar('creation_time', 'e'), 'm');
		$ret['audio_content'] = $this->getAudioToDisplay();
		$ret['audio_title'] = $this->getVar('title','e');
		$ret['editItemLink'] = $this->getEditItemLink(false, true, true);
		$ret['deleteItemLink'] = $this->getDeleteItemLink(false, true, true);
		$ret['userCanEditAndDelete'] = $this->userCanEditAndDelete();
		$ret['audio_senderid'] = $this->getVar('uid_owner','e');
		$ret['audio_sender_link'] = $this->getAudioSender();
		return $ret;
	}
}
class ProfileAudioHandler extends IcmsPersistableObjectHandler {

	/**
	 * Constructor
	 */
	public function __construct(& $db) {
		global $icmsModuleConfig;
		$this->IcmsPersistableObjectHandler($db, 'audio', 'audio_id', 'title', '', 'profile');
		$this->enableUpload(array("audio/mp3" , "audio/x-mp3", "audio/mpeg"), $icmsModuleConfig['maxfilesize_audio']);
	}


	/**
	 * Create the criteria that will be used by getAudio and getAudioCount
	 *
	 * @param int $start to which record to start
	 * @param int $limit limit of audio to return
	 * @param int $uid_owner if specifid, only the audio of this user will be returned
	 * @param int $audio_id ID of a single audio to retrieve
	 * @return CriteriaCompo $criteria
	 */
	function getAudioCriteria($start = 0, $limit = 0, $uid_owner = false, $audio_id = false) {
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
		if ($audio_id) {
			$criteria->add(new Criteria('audio_id', $audio_id));
		}
		return $criteria;
	}

	/**
	 * Get single audio object
	 *
	 * @param int $audio_id
	 * @return object ProfileAudio object
	 */
	function getAudio($audio_id=false, $uid_owner=false) {
		$ret = $this->getAudio(0, 0, $uid_owner, $audio_id);
		return isset($ret[$audio_id]) ? $ret[$audio_id] : false;
	}

	/**
	 * Get audio as array, ordered by creation_time DESC
	 *
	 * @param int $start to which record to start
	 * @param int $limit max audio to display
	 * @param int $uid_owner if specifid, only the audio of this user will be returned
	 * @param int $audio_id ID of a single audio to retrieve
	 * @return array of audio
	 */
	function getAudios($start = 0, $limit = 0, $uid_owner = false, $audio_id = false) {
		$criteria = $this->getAudioCriteria($start, $limit, $uid_owner, $audio_id);
		$ret = $this->getObjects($criteria, true, false);
		return $ret;
	}

	/**
	 * Check wether the current user can submit a new audio or not
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
	 * Check whether the user has already reached the upload limit
	 *
	 * @global array $icmsModuleConfig module configuration
	 * @return int number of audios for the current user (icmsUser)
	 */
	function checkUploadLimit() {
		global $icmsUser, $icmsModuleConfig;

		if (!is_object($icmsUser)) return false;
		if ($icmsModuleConfig['nb_audio'] == 0) return true;
		$count = $this->getCount(new CriteriaCompo(new Criteria('uid_owner', $icmsUser->getVar('uid'))));
		return ($count < $icmsModuleConfig['nb_audio']);
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
	 * insert a new object in the database
	 *
	 * @param	object	$obj reference to the object
	 * @param	bool	$force whether to force the query execution despite security settings
	 * @param	bool	$checkObject check if the object is dirty and clean the attributes
	 * @param	bool	$debug debug switch
	 * @return	bool FALSE if failed, TRUE if already present and unchanged or successful
	 */
	function insert(&$obj, $force = false, $checkObject = true, $debug=false) {
		if (count($obj->getErrors()) > 0) return false;
		return parent::insert($obj, $force, $checkObject, $debug);
	}

	/**
	 * insert a new object in the database and output debug message
	 *
	 * @param	object	$obj reference to the object
	 * @param	bool	$force whether to force the query execution despite security settings
	 * @param	bool	$checkObject check if the object is dirty and clean the attributes
	 * @param	bool	$debug debug switch
	 * @return	bool FALSE if failed, TRUE if already present and unchanged or successful
	 */
	function insertD(&$obj, $force = false, $checkObject = true, $debug=false) {
		return $this->insert($obj, $force, $checkObject, true);
	}

	/*
	 * afterDelete event
	 *
	 * Event automatically triggered by IcmsPersistable Framework after the object is deleted
	 *
	 * @param object $obj ProfileAudio object
	 * @return bool
	 */
	function afterDelete(&$obj) {
		$url = $obj->getVar('url');

		if (!empty($url)) {
			unlink(ICMS_UPLOAD_PATH.'/profile/audio/'.$url);
		}
		
		return true;
	}
}
?>