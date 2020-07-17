<?php
/**
 * Class representing the profile tribes objects
 *
 * @copyright	GNU General Public License (GPL)
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @since		1.3
 * @author		Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @package		profile
 * @version		$Id: Tribes.php 21843 2011-06-23 14:54:52Z phoenyx $
 */

defined("ICMS_ROOT_PATH") or die("ICMS root path not defined");

class mod_profile_Tribes extends icms_ipf_seo_Object {
	/**
	 * Constructor
	 *
	 * @param ProfilePostHandler $handler ProfilePostHandler object
	 */
	public function __construct(&$handler) {
		icms_ipf_object::__construct($handler);

		$this->quickInitVar('tribes_id', XOBJ_DTYPE_INT, true);
		$this->quickInitVar('uid_owner', XOBJ_DTYPE_INT, true);
		$this->quickInitVar('title', XOBJ_DTYPE_TXTBOX, true);
		$this->quickInitVar('tribe_desc', XOBJ_DTYPE_TXTAREA, true);
		$this->quickInitVar('tribe_img', XOBJ_DTYPE_IMAGE, false);
		$this->quickInitVar('creation_time', XOBJ_DTYPE_LTIME, false);
		$this->quickInitVar('security', XOBJ_DTYPE_INT, false, false, false, PROFILE_TRIBES_SECURITY_EVERYBODY);
		$this->initCommonVar('counter', false);
		$this->initCommonVar('dohtml', false, true);
		$this->initCommonVar('dobr', false, true);
		$this->initCommonVar('doimage', false, true);
		$this->initCommonVar('dosmiley', false, true);
		$this->initCommonVar('doxcode', false, true);

		$this->setControl('uid_owner', 'user');
		$this->setControl('tribe_img', array('name' => 'image', 'nourl' => true));
		$this->setControl('tribe_desc', 'dhtmltextarea');
		$this->setControl('security', array ('itemHandler' => 'tribes',	'method' => 'getTribes_securityArray', 'module' => 'profile'));

		$this->hideFieldFromForm('creation_time');

		$this->initiateSEO();
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
	 * get the colorboxed tribe picture
	 *
	 * @return str lightboxed image of the tribe
	 */
	public function getTribePicture() {
		if ($this->getVar('tribe_img')) {
			$ret = '<a href="'.$this->handler->getImageUrl().'resized_'.$this->getVar('tribe_img').'" rel="lightbox" title="'.$this->getVar('title').'">
					<img class="thumb" src="'.$this->handler->getImageUrl().'thumb_'.$this->getVar('tribe_img').'" rel="lightbox" title="'.$this->getVar ('title').'" />
					</a>';
		} else {
			$ret = '';
		}
		return $ret;
	}

	/**
	 * get the linked tribe picture
	 *
	 * @param str $itemUrl link to the tribe
	 * @return str linked image or title of the tribe
	 */
	public function getTribePictureLink($itemUrl) {
		if ($this->getVar('tribe_img')) {
			$ret = '<a href="'.$itemUrl.'">
					<img class="thumb" src="'.$this->handler->getImageUrl().'thumb_'.$this->getVar('tribe_img').'" rel="lightbox" title="'.$this->getVar('title').'" />
					</a>';
		} else {
			$ret = '<a href="'.$itemUrl.'">'.$this->getVar('title').'</a>';
		}
		return $ret;
	}

	/**
	 * Get the avatar for the tribe owner
	 *
	 * @global array $icmsConfigUser user configuration
	 * @return str html image tag for the avatar of the user
	 */
	public function getProfileTribeSenderAvatar() {
		global $icmsConfigUser;

		$thisUser = icms::handler('icms_member')->getUser($this->getVar('uid_owner', 'e'));
		if (!is_object($thisUser)) return;
		$avatar = $thisUser->gravatar();
		if (!$icmsConfigUser['avatar_allow_gravatar'] && strpos($avatar, 'http://www.gravatar.com/avatar/') !== false) return false;
		return '<img src="'.$avatar.'" />';
	}

	/**
	 * Generate the linked user name
	 *
	 * @return str linked username
	 */
	public function getTribeSender() {
		return icms_member_user_Handler::getUserLink($this->getVar('uid_owner', 'e'));
	}

	/**
	 * return linked tribe title
	 *
	 * @return str linked tribe title
	 */
	public function getLinkedTribeTitle() {
		$link = $this->handler->_moduleUrl.$this->handler->_page.'?uid='.$this->getVar('uid_owner').'&tribes_id='.$this->getVar('tribes_id');
		return '<a href="'.$link.'">'.$this->getVar('title').'</a>';
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
		return $this->getVar('uid_owner', 'e') == icms::$user->getVar('uid');
	}

	/**
	 * check if a user is a member of the tribe
	 *
	 * @param int $uid user ID
	 * @global bool $profile_isAdmin true if current user is admin for profile module
	 * @return bool true if $uid is a member of this tribe
	 */
	public function isMember($uid) {
		global $profile_isAdmin;

		if (!is_object(icms::$user)) return false;

		$profile_tribeuser_handler = icms_getModuleHandler('tribeuser', basename(dirname(dirname(__FILE__))), 'profile');
		if ($this->getVar('security') == PROFILE_TRIBES_SECURITY_EVERYBODY) {
			$tribeusers = $profile_tribeuser_handler->getTribeusers(0, 1, icms::$user->getVar('uid'), false, $this->getVar('tribes_id'));
		} elseif ($this->getVar('security') == PROFILE_TRIBES_SECURITY_APPROVAL) {
			$tribeusers = $profile_tribeuser_handler->getTribeusers(0, 1, icms::$user->getVar('uid'), false, $this->getVar('tribes_id'), '=', 1);
		} elseif ($this->getVar('security') == PROFILE_TRIBES_SECURITY_INVITATION) {
			$tribeusers = $profile_tribeuser_handler->getTribeusers(0, 1, icms::$user->getVar('uid'), false, $this->getVar('tribes_id'), '=', false, 1);
		}
		if (is_array($tribeusers) && (count($tribeusers) == 1 || $profile_isAdmin || $uid == $this->getVar('uid_owner'))) return true;

		return false;
	}

	/**
	 * build merge item link for object list (acp)
	 *
	 * @return str
	 */
	public function getMergeItemLink() {
		return '<a href="'.$this->handler->_moduleUrl.'admin/'.$this->handler->_itemname.'.php?op=merge&amp;tribes_id='.$this->getVar('tribes_id').'" title="'._AM_PROFILE_TRIBES_MERGE_DSC.'"><img src="'.$this->handler->_moduleUrl.'images/admin/merge.png" /></a>';
	}

	/**
	 * generate merge form
	 *
	 * @return icms_form_Theme merge form
	 */
	public function getMergeForm() {
		$form = new icms_form_Theme(_AM_PROFILE_TRIBES_MERGE_DSC, 'mergetribes', '');
		$form->addElement(new icms_form_elements_Label(_AM_PROFILE_TRIBE, $this->getVar('title')));
		$tribes_select = new icms_form_elements_Select(_AM_PROFILE_TRIBES_MERGEWITH, 'merge_tribes_id');
		$tribes_select->addOptionArray($this->handler->getList(new icms_db_criteria_Compo(new icms_db_criteria_Item('tribes_id', $this->getVar('tribes_id'), '<>'))));
		$form->addElement($tribes_select);
		$form->addElement(new icms_form_elements_Label(_AM_PROFILE_TRIBES_MERGE_WARNING, sprintf(_AM_PROFILE_TRIBES_MERGE_WARNING_DSC, $this->getVar('title'))));
		$button_tray = new icms_form_elements_Tray('', '');
		$button_tray->addElement(new icms_form_elements_Button('', 'modify_button', _AM_PROFILE_TRIBES_MERGE, 'submit'));
		$butt_cancel = new icms_form_elements_Button('', 'cancel_button', _CO_ICMS_CANCEL, 'button');
		$butt_cancel->setExtra('onclick="history.go(-1)"');
		$button_tray->addElement($butt_cancel);
		$form->addElement($button_tray);
		$form->addElement(new icms_form_elements_Hidden('tribes_id', $this->getVar('tribes_id')));
		$form->addElement(new icms_form_elements_Hidden('op', 'mergefinal'));
		return $form;
	}

	/**
	 * Overridding IcmsPersistable::toArray() method to add a few info
	 *
	 * @return array of tribe info
	 */
	public function toArray() {
		$ret = parent::toArray();
		$ret['itemLink'] = str_replace($this->handler->_itemname.'.php?', $this->handler->_itemname.'.php?uid='.$this->getVar('uid_owner').'&', $ret['itemLink']);
		$ret['itemUrl'] = str_replace($this->handler->_itemname.'.php?', $this->handler->_itemname.'.php?uid='.$this->getVar('uid_owner').'&', $ret['itemUrl']);
		$ret['creation_time'] = formatTimestamp($this->getVar('creation_time', 'e'));
		$ret['creation_time_short'] = formatTimestamp($this->getVar('creation_time', 'e'), 's');
		$ret['tribe_title'] = $this->getVar('title','e');
		$ret['tribe_content'] = $this->getTribePicture();
		$ret['picture_link'] = $this->getTribePictureLink($ret['itemUrl']);
		$ret['editItemLink'] = $this->getEditItemLink(false, true, true);
		$ret['deleteItemLink'] = $this->getDeleteItemLink(false, true, true);
		$ret['userCanEditAndDelete'] = $this->userCanEditAndDelete();
		$ret['tribe_senderid'] = $this->getVar('uid_owner','e');
		$ret['tribe_sender_link'] = $this->getTribeSender();
		$ret['tribe_sender_avatar'] = $this->getProfileTribeSenderAvatar();
		return $ret;
	}
}
?>