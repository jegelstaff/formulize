<?php
/**
 * Class representing the profile tribepost objects
 *
 * @copyright	GNU General Public License (GPL)
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @since		1.3
 * @author		phoenyx
 * @package		profile
 * @version		$Id: Tribepost.php 21843 2011-06-23 14:54:52Z phoenyx $
 */

defined("ICMS_ROOT_PATH") or die("ICMS root path not defined");

class mod_profile_Tribepost extends icms_ipf_seo_Object {
	/**
	 * Constructor
	 *
	 * @param mod_profile_TribepostHandler $handler mod_profile_TribepostHandler object
	 */
	public function __construct(&$handler) {
		icms_ipf_Object::__construct($handler);

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

		$this->initiateSEO();
	}

	/**
	 * Check to see wether the current user can edit or delete this tribe
	 *
	 * @global bool $profile_isAdmin true if current user is admin for profile module
	 * @return bool true if he can, false if not
	 */
	public function userCanEditAndDelete() {
		global $profile_isAdmin;

		if (!is_object(icms::$user)) return false;
		if ($profile_isAdmin) return true;
		return $this->getVar('poster_uid', 'e') == icms::$user->getVar('uid');
	}

	/**
	 * Overridding IcmsPersistable::toArray() method to add a few info
	 *
	 * @global array $icmsConfigUser user configuration
	 * @return array of tribetopic info
	 */
	public function toArray() {
		global $icmsConfigUser;

		$ret = parent :: toArray();
		$ret['post_time'] = formatTimestamp($this->getVar('post_time', 'e'), 'm');
		$ret['poster_uname'] = icms_member_user_Handler::getUserLink($this->getVar('poster_uid'));
		$ret['userCanEditAndDelete'] = $this->userCanEditAndDelete();
		$thisUser = icms::handler('icms_member')->getUser($this->getVar('poster_uid'));
		if (is_object($thisUser)) {
			// get poster avatar
			$avatar = $thisUser->gravatar();
			if ($icmsConfigUser['avatar_allow_gravatar'] || strpos($avatar, 'http://www.gravatar.com/avatar/') === false) $ret['poster_avatar'] =  '<img src="'.$thisUser->gravatar().'" />';
			// get poster signature
			if (trim($thisUser->getVar('user_sig')) && $this->getVar('attachsig')) {
				$ret['poster_signature'] = icms_core_DataFilter::checkVar($thisUser->getVar('user_sig', 'N'), 'html', 'output');
			}
		}
		// rewrite edit and delete item links to work with tribes.php
		$ret['editItemLink'] = str_replace($this->handler->_itemname.'.php?op=mod', 'tribes.php?tribes_id='.$this->getVar('tribes_id').'&amp;topic_id='.$this->getVar('topic_id').'&amp;op=edittribepost', $this->getEditItemLink(false, true, true));
		$ret['deleteItemLink'] = str_replace($this->handler->_itemname.'.php?op=del', 'tribes.php?tribes_id='.$this->getVar('tribes_id').'&amp;topic_id='.$this->getVar('topic_id').'&amp;op=deltribepost', $this->getDeleteItemLink(false, true, true));
		return $ret;
	}
}