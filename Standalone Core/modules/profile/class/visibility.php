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
include_once ICMS_ROOT_PATH . '/kernel/icmspersistableseoobject.php';

class ProfileVisibility extends IcmsPersistableObject  {
	function ProfileVisibility() {
		$this->initVar('fieldid', XOBJ_DTYPE_INT);
		$this->initVar('user_group', XOBJ_DTYPE_INT);
		$this->initVar('profile_group', XOBJ_DTYPE_INT);
	}
}

class ProfileVisibilityHandler extends IcmsPersistableObjectHandler {

	function ProfileVisibilityHandler($db) {
		parent::IcmsPersistableObjectHandler($db, 'visibility', array('fieldid', 'user_group', 'profile_group'), '', '', 'profile');
	}

	/**
	 * Get fields visible to the $user_groups on a $profile_groups profile
	 *
	 * @param array $user_groups
	 * @param array $profile_groups
	 *
	 * @return array
	 */
	function getVisibleFields($user_groups, $profile_groups) {
		global $icmsUser;
		$profile_groups[] = 0;
		$user_groups[] = 0;

		$sql = "SELECT fieldid FROM ".$this->table." WHERE profile_group IN (".implode(',', $profile_groups).")";
		$sql .= " AND user_group IN (".implode(',', $user_groups).")";

		$fieldids = array();
		if ($result = $this->db->query($sql)) {
			while (list($fieldid) = $this->db->fetchRow($result)) {
				$fieldids[] = $fieldid;
			}
		}
		return $fieldids;
	}
}
?>