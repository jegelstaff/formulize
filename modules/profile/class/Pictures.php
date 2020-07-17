<?php
/**
 * Class representing the profile pictures object
 *
 * @copyright	GNU General Public License (GPL)
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @since		1.3
 * @author		Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @package		profile
 * @version		$Id: Pictures.php 21843 2011-06-23 14:54:52Z phoenyx $
 */

defined("ICMS_ROOT_PATH") or die("ICMS root path not defined");

class mod_profile_Pictures extends icms_ipf_Object {
	/**
	 * Constructor
	 *
	 * @param ProfilePictureHandler $handler handler object
	 */
	public function __construct(&$handler) {
		parent::__construct($handler);

		$this->quickInitVar('pictures_id', XOBJ_DTYPE_INT, true);
		$this->quickInitVar('title', XOBJ_DTYPE_TXTBOX, true);
		$this->quickInitVar('creation_time', XOBJ_DTYPE_LTIME, false);
		$this->quickInitVar('uid_owner', XOBJ_DTYPE_INT, true);
		$this->quickInitVar('url', XOBJ_DTYPE_IMAGE, true);
		$this->quickInitVar('private', XOBJ_DTYPE_TXTBOX, false);
		$this->initCommonVar('counter', false);

		$this->setControl('uid_owner', 'user');
		$this->setControl('url', array('name' => 'image', 'nourl' => true));
		$this->setControl('private', 'yesno');

		$this->hideFieldFromForm('creation_time');
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
	 * get html code to display picture
	 *
	 * @return str html code to display picture
	 */
	public function getProfilePicture() {
		return '<a href="'.$this->handler->getImageUrl().'resized_'.$this->getVar('url').'" rel="lightbox" title="'.$this->getVar('title').'"><img class="thumb" src="'.$this->handler->getImageUrl().'thumb_'.$this->getVar('url').'" rel="lightbox" title="'.$this->getVar('title').'" /></a>';
	}

	/**
	 * get linked username for the owner
	 *
	 * @return str linked username
	 */
	public function getPictureSender() {
		return icms_member_user_Handler::getUserLink($this->getVar('uid_owner'));
	}

	/**
	 * return the picture title
	 *
	 * @return str picture title
	 */
	public function getPictureTitle() {
		return $this->getVar('title');
	}

	/**
	 * Check to see wether the current user can edit or delete this picture
	 *
	 * @global bool $profile_isAdmin true if current user is admin for profile module
	 * @return bool true if he can, false if not
	 */
	public function userCanEditAndDelete() {
		global $profile_isAdmin;

		if (!is_object(icms::$user)) return false;
		if ($profile_isAdmin) return true;
		return $this->getVar('uid_owner') == icms::$user->getVar('uid');
	}

	/**
	 * Overridding IcmsPersistable::toArray() method to add a few info
	 *
	 * @return array of picture info
	 */
	public function toArray() {
		$ret = parent::toArray();
		$ret['creation_time'] = formatTimestamp($this->getVar('creation_time', 'e'), 'm');
		$ret['picture_content'] = $this->getProfilePicture();
		$ret['editItemLink'] = $this->getEditItemLink(false, true, true);
		$ret['deleteItemLink'] = $this->getDeleteItemLink(false, true, true);
		$ret['userCanEditAndDelete'] = $this->userCanEditAndDelete();
		return $ret;
	}
}
?>