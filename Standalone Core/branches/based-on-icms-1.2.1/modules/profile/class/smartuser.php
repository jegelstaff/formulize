<?php
/**
 * Extended User Profile
 *
 *
 * @copyright	   The ImpressCMS Project http://www.impresscms.org/
 * @license		 LICENSE.txt
 * @license			GNU General Public License (GPL) http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @package		 modules
 * @since		   1.2
 * @author		  Jan Pedersen
 * @author		  The SmartFactory <www.smartfactory.ca>
 * @author	   		Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @version		 $Id$
 */

if (!defined("ICMS_ROOT_PATH")) {
	die("ICMS root path not defined");
}
include_once ICMS_KERNEL_PATH."icmspersistableobject.php";

class ProfileSmartuser extends IcmsPersistableObject {

	  function ProfileSmartuser(&$handler) {
		//ini_set('memory_limit','32M');
		$this->initNonPersistableVar('uid', XOBJ_DTYPE_INT, false, "_AM_SPROFILE_UID");
		$this->initNonPersistableVar('uname', XOBJ_DTYPE_TXTBOX, false, "_AM_SPROFILE_UNAME");
		$this->initNonPersistableVar('login_name', XOBJ_DTYPE_TXTBOX, false, "_AM_SPROFILE_UNAME");
		$this->initNonPersistableVar('email', XOBJ_DTYPE_TXTBOX, false, "_AM_SPROFILE_EMAIL");

		$this->IcmsPersistableObject($handler);
		$fields =& $this->handler->getFields();
		foreach($fields as $key =>$field){
			$this->initNonPersistableVar($key, XOBJ_DTYPE_TXTBOX, false, $field->getVar('field_title'));
		}

	}

	function getUserLink(){
		return "<a href='".ICMS_URL."/modules/".basename(  dirname(  dirname( __FILE__ ) ) )."/userinfo.php?uid=".$this->getVar('uid')."'>".$this->getVar('uname')."</a>";
	}

	function getUserEail(){
		return "<a href='mailto:".$this->getVar('email')."'>".$this->getVar('email')."</a>";
	}

}
class ProfileSmartuserHandler extends IcmsPersistableObjectHandler {

	function ProfileSmartuserHandler($db) {
		 $this->IcmsPersistableObjectHandler($db, 'smartuser', 'uid', 'uname', 'uname', 'profile');
		 $this->generalSQL = 'SELECT * FROM '.$this->db->prefix('users') . " AS " . $this->_itemname . ' JOIN ' . $this->db->prefix('profile_profile') . ' AS profile ON profileid='.$this->_itemname.'.uid ';

	}
	 function getFields(){
		static $fields_array;
		if (!isset($fields_array)) {
		  	$profile_handler =& icms_getmodulehandler( 'profile', basename(  dirname(  dirname( __FILE__ ) ) ), 'profile' );
			$fields_array =& $profile_handler->loadFields();
		}
		return $fields_array;
	}
}


?>
