<?php
/**
 * Class representing the profile audio object
 *
 * @copyright	GNU General Public License (GPL)
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @since		1.3
 * @author		Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @package		profile
 * @version		$Id: Audio.php 21843 2011-06-23 14:54:52Z phoenyx $
 */

defined("ICMS_ROOT_PATH") or die("ICMS root path not defined");

class mod_profile_Audio extends icms_ipf_Object {
	/**
	 * Constructor
	 *
	 * @param mod_profile_AudioHandler $handler object handler
	 */
	public function __construct(&$handler) {
		parent::__construct($handler);

		$this->quickInitVar('audio_id', XOBJ_DTYPE_INT, true);
		$this->quickInitVar('author', XOBJ_DTYPE_TXTBOX, true);
		$this->quickInitVar('title', XOBJ_DTYPE_TXTBOX, true);
		$this->quickInitVar('url', XOBJ_DTYPE_TXTBOX, true);
		$this->quickInitVar('uid_owner', XOBJ_DTYPE_INT, true);
		$this->quickInitVar('creation_time', XOBJ_DTYPE_LTIME, false);

		$this->setControl('uid_owner', 'user');
		$this->setControl('url', 'upload');
	}

	/**
	 * Overriding the icms_ipf_Object::getVar method to assign a custom method on some
	 * specific fields to handle the value before returning it
	 *
	 * @param str $key key of the field
	 * @param str $format format that is requested
	 * @return mixed value of the field that is requested
	 */
	public function getVar($key, $format = 's') {
		if ($format == 's' && in_array($key, array())) {
			return call_user_func(array($this,	$key));
		}
		return parent::getVar($key, $format);
	}

	/**
	 * return audio sender
	 *
	 * @return str linked username
	 */
	public function getAudioSender() {
		return icms_member_user_Handler::getUserLink($this->getVar('uid_owner', 'e'));
	}

	/**
	 * return audio player
	 *
	 * @return str html code to display audio player
	 */
	public function getAudioToDisplay() {
		$ret  = '<object type="application/x-shockwave-flash" data="'.$this->handler->_moduleUrl.'assets/audioplayers/dewplayer.swf?mp3='.$this->getUploadDir().$this->getVar('url', 'e').'" width="200" height="20" id="dewplayer">';
		$ret .= '<param name="wmode" value="transparent" />';
		$ret .= '<param name="movie" value="'.$this->handler->_moduleUrl.'assets/audioplayers/dewplayer.swf?mp3='.$this->getUploadDir().$this->getVar('url', 'e').'" />';
		$ret .= '</object>';

		return $ret;
	}

	/**
	 * return audio title
	 *
	 * @return str audio title
	 */
	public function getAudioTitle() {
		return $this->getVar('title');
	}

	/**
	 * Check to see wether the current user can edit or delete this audio
	 *
	 * @global bool $profile_isAdmin true if current user is admin for profile module
	 * @return bool true if he can, false if not
	 */
	public function userCanEditAndDelete() {
		global $profile_isAdmin;

		if (!is_object(icms::$user)) return false;
		if ($profile_isAdmin) return true;
		return $this->getVar('uid_owner', 'e') == icms::$user->getVar('uid');
	}

	/**
	 * Overridding IcmsPersistable::toArray() method to add a few info
	 *
	 * @return array of audio info
	 */
	public function toArray() {
		$ret = parent::toArray();
		$ret['audio_content'] = $this->getAudioToDisplay();
		$ret['editItemLink'] = $this->getEditItemLink(false, true, true);
		$ret['deleteItemLink'] = $this->getDeleteItemLink(false, true, true);
		$ret['userCanEditAndDelete'] = $this->userCanEditAndDelete();
		$ret['audio_sender_link'] = $this->getAudioSender();
		return $ret;
	}
}
?>