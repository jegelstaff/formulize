<?php
/**
 * Class representing the profile videos object
 *
 * @copyright	GNU General Public License (GPL)
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @since		1.3
 * @author		Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @package		profile
 * @version		$Id: Videos.php 21843 2011-06-23 14:54:52Z phoenyx $
 */

defined("ICMS_ROOT_PATH") or die("ICMS root path not defined");

class mod_profile_Videos extends icms_ipf_Object {
	/**
	 * Constructor
	 *
	 * @param mod_profile_VideosHandler $handler object handler
	 */
	public function __construct(&$handler) {
		parent::__construct($handler);

		$this->quickInitVar('videos_id', XOBJ_DTYPE_INT, true);
		$this->quickInitVar('uid_owner', XOBJ_DTYPE_INT, true);
		$this->quickInitVar('video_title', XOBJ_DTYPE_TXTBOX, true);
		$this->quickInitVar('youtube_code', XOBJ_DTYPE_TXTBOX, true);
		$this->quickInitVar('video_desc', XOBJ_DTYPE_TXTAREA, true);
		$this->quickInitVar('creation_time', XOBJ_DTYPE_LTIME, false);
		$this->initCommonVar('dohtml', false, true);
		$this->initCommonVar('dobr', false, true);
		$this->initCommonVar('doimage', false, true);
		$this->initCommonVar('dosmiley', false, true);
		$this->initCommonVar('doxcode', false, true);

		$this->setControl('uid_owner', 'user');
		$this->setControl('video_desc', 'dhtmltextarea');
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
	 * get video to display
	 *
	 * @param bool $main true if video is main video
	 * @return str html code to display video
	 */
	public function getVideoToDisplay($main = false) {
		$module = icms::handler("icms_module")->getByDirname(basename(dirname(dirname(__FILE__))), TRUE);
		$width = $main ? $module->config['width_maintube'] : $module->config['width_tube'];
		$height = $main ? $module->config['height_maintube'] : $module->config['height_tube'];

		$ret = '<embed src="http://www.youtube.com/v/'.$this->getVar('youtube_code').'&fs=1&hl='._LANGCODE.'" type="application/x-shockwave-flash" allowfullscreen="true" width="'.$width.'" height="'.$height.'"></embed>';
		return $ret;
	}

	/**
	 * get linked username from id
	 *
	 * @return str linked user name
	 */
	public function getVideoSender() {
		return icms_member_user_Handler::getUserLink($this->getVar('uid_owner', 'e'));
	}

	/**
	 * return video title
	 *
	 * @return str video title
	 */
	public function getVideoTitle() {
		return $this->getVar('video_title');
	}

	/**
	 * Check to see wether the current user can edit or delete this video
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
	 * @return array of video info
	 */
	public function toArray() {
		$ret = parent::toArray();
		$ret['creation_time'] = formatTimestamp($this->getVar('creation_time', 'e'), 'm');
		$ret['video_content'] = $this->getVideoToDisplay();
		$ret['video_content_main'] = $this->getVideoToDisplay(true);
		$ret['editItemLink'] = $this->getEditItemLink(false, true, true);
		$ret['deleteItemLink'] = $this->getDeleteItemLink(false, true, true);
		$ret['userCanEditAndDelete'] = $this->userCanEditAndDelete();
		return $ret;
	}
}
?>