<?php
/**
 * Extended User Profile
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		LICENSE.txt
 * @license		GNU General Public License (GPL) http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @package		profile
 * @since		1.2
 * @author		Jan Pedersen
 * @author		The SmartFactory <www.smartfactory.ca>
 * @author		Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @version		$Id: Smartuser.php 20113 2010-09-08 19:12:39Z phoenyx $
 */

defined("ICMS_ROOT_PATH") or die("ICMS root path not defined");

include_once ICMS_KERNEL_PATH.'icmspersistableobject.php';

/**
 * @todo this class needs to be refactored or removed
 */
class ProfileSmartuser extends IcmsPersistableObject {

	public function __construct(&$handler) {
		$this->IcmsPersistableObject($handler);

		$this->initNonPersistableVar('uid', XOBJ_DTYPE_INT, false, "_AM_SPROFILE_UID");
		$this->initNonPersistableVar('uname', XOBJ_DTYPE_TXTBOX, false, "_AM_SPROFILE_UNAME");
		$this->initNonPersistableVar('login_name', XOBJ_DTYPE_TXTBOX, false, "_AM_SPROFILE_UNAME");
		$this->initNonPersistableVar('email', XOBJ_DTYPE_TXTBOX, false, "_AM_SPROFILE_EMAIL");

		$fields = $this->handler->getFields();
		foreach ($fields as $key => $field) $this->initNonPersistableVar($key, XOBJ_DTYPE_TXTBOX, false, $field->getVar('field_title'));
	}

	function getUserLink(){
		return "<a href='".ICMS_URL."/modules/".basename(dirname(dirname(__FILE__)))."/userinfo.php?uid=".$this->getVar('uid')."'>".$this->getVar('uname')."</a>";
	}

	function getUserEail(){
		return "<a href='mailto:".$this->getVar('email')."'>".$this->getVar('email')."</a>";
	}

}
class ProfileSmartuserHandler extends IcmsPersistableObjectHandler {

	function ProfileSmartuserHandler($db) {
		$this->IcmsPersistableObjectHandler($db, 'smartuser', 'uid', 'uname', 'uname', basename(dirname(dirname(__FILE__))));
		$this->generalSQL = 'SELECT * FROM '.$this->db->prefix('users').' AS '.$this->_itemname.' JOIN '.$this->db->prefix('profile_profile').' AS profile ON profile.profileid='.$this->_itemname.'.uid ';
	}

	function &getFields(){
		static $fields_array;
		if (!isset($fields_array)) {
			$profile_handler = icms_getModuleHandler('profile', basename(dirname(dirname(__FILE__))), 'profile');
			$fields_array = $profile_handler->loadFields();
		}
		return $fields_array;
	}
}


?>
