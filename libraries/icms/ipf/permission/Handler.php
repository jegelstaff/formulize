<?php
/**
 * IcmsPermission
 *
 * This class easily manage the permission affected to an IcmsPersistablebject
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @category	ICMS
 * @package		Ipf
 * @subpackage	Permission
 * @since		1.1
 * @author		marcan <marcan@impresscms.org>
 * @version		SVN: $Id: Handler.php 20558 2010-12-19 18:17:29Z phoenyx $
 */

defined("ICMS_ROOT_PATH") or die("ImpressCMS root path not defined");

/**
 *
 * @category	ICMS
 * @package		Ipf
 * @subpackage	Permission
 *
 */
class icms_ipf_permission_Handler {

	/**
	 *
	 * @var unknown_type
	 */
	public $handler;

	/**
	 * Constructor
	 *
	 * @param unknown_type $handler
	 */
	public function __construct($handler) {
		$this->handler = $handler;
	}

	/**
	 * Returns permissions for a certain type
	 *
	 * @param string $type "global", "forum" or "topic" (should perhaps have "post" as well - but I don't know)
	 * @param int $id id of the item (forum, topic or possibly post) to get permissions for
	 *
	 * @return array
	 */
	public function getGrantedGroups($gperm_name, $id = null) {
		static $groups;

		if (!isset($groups[$gperm_name]) || ($id != null && !isset($groups[$gperm_name][$id]))) {
			$icmsModule =& $this->handler->getModuleInfo();
			//Get group permissions handler
			$gperm_handler = icms::handler('icms_member_groupperm');

			//Get groups allowed for an item id
			$allowedgroups = $gperm_handler->getGroupIds($gperm_name, $id, $icmsModule->getVar('mid'));
			$groups[$gperm_name][$id] = $allowedgroups;
		}
		//Return the permission array
		return isset($groups[$gperm_name][$id]) ? $groups[$gperm_name][$id] : array();
	}

	/**
	 *
	 * @param	arr		$item_ids_array
	 * @param	str		$gperm_name
	 */
	public function getGrantedGroupsForIds($item_ids_array, $gperm_name = false) {

		static $groups;

		if ($gperm_name) {
			if (isset($groups[$gperm_name])) {
				return $groups[$gperm_name];
			}
		} else {
			// if !$gperm_name then we will fetch all permissions in the module so we don't need them again
			return $groups;
		}

		$icmsModule =& $this->handler->getModuleInfo();

		$criteria = new icms_db_criteria_Compo();
		$criteria->add(new icms_db_criteria_Item('gperm_modid', $icmsModule->getVar('mid')));

		if ($gperm_name) {
			$criteria->add(new icms_db_criteria_Item('gperm_name', $gperm_name));
		}

		//Get group permissions handler
		$gperm_handler = icms::handler('icms_member_groupperm');

		$permissionsObj = $gperm_handler->getObjects($criteria);

		foreach ($permissionsObj as $permissionObj) {
			$groups[$permissionObj->getVar('gperm_name')][$permissionObj->getVar('gperm_itemid')][] = $permissionObj->getVar('gperm_groupid');
		}

		//Return the permission array
		if ($gperm_name) {
			return isset($groups[$gperm_name]) ? $groups[$gperm_name] : array();
		} else {
			return isset($groups) ? $groups : array();
		}
	}

	/**
	 * Returns permissions for a certain type
	 *
	 * @param string $type "global", "forum" or "topic" (should perhaps have "post" as well - but I don't know)
	 * @param int $id id of the item (forum, topic or possibly post) to get permissions for
	 *
	 * @return array
	 */
	public function getGrantedItems($gperm_name, $id = null) {
		static $permissions;

		if (!isset($permissions[$gperm_name]) || ($id != null && !isset($permissions[$gperm_name][$id]))) {

			$icmsModule =& $this->handler->getModuleInfo();

			if (is_object($icmsModule)) {

				//Get group permissions handler
				$gperm_handler = icms::handler('icms_member_groupperm');

				//Get user's groups
				$groups = is_object(icms::$user) ? icms::$user->getGroups() : array(ICMS_GROUP_ANONYMOUS);

				//Get all allowed item ids in this module and for this user's groups
				$userpermissions =& $gperm_handler->getItemIds($gperm_name, $groups, $icmsModule->getVar('mid'));
				$permissions[$gperm_name] = $userpermissions;
			}
		}
		//Return the permission array
		return isset($permissions[$gperm_name]) ? $permissions[$gperm_name] : array();
	}

	/**
	 *
	 * @param int $id
	 */
	public function storeAllPermissionsForId($id) {
		foreach ($this->handler->getPermissions() as $permission) {
			$this->saveItem_Permissions($_POST[$permission['perm_name']], $id, $permission['perm_name']);
		}
	}

	/**
	 * Saves permissions for the selected category
	 *
	 *  saveCategory_Permissions()
	 *
	 * @param array $groups : group with granted permission
	 * @param integer $categoryID : categoryID on which we are setting permissions for Categories and Forums
	 * @param string $perm_name : name of the permission
	 * @return boolean : TRUE if the no errors occured
	 **/
	public function saveItem_Permissions($groups, $itemid, $perm_name) {
		$icmsModule =& $this->handler->getModuleInfo();

		$result = true;
		$module_id = $icmsModule->getVar('mid');
		$gperm_handler = icms::handler('icms_member_groupperm');

		// First, if the permissions are already there, delete them
		$gperm_handler->deleteByModule($module_id, $perm_name, $itemid);

		// Save the new permissions
		if (count($groups) > 0) {
			foreach ($groups as $group_id) {
				$gperm_handler->addRight($perm_name, $itemid, $group_id, $module_id);
			}
		}
		return $result;
	}

	/**
	 * Delete all permission for a specific item
	 *
	 *  deletePermissions()
	 *
	 * @param integer $itemid : id of the item for which to delete the permissions
	 * @return boolean : TRUE if the no errors occured
	 **/

	/**
	 * @todo not completed....
	 */
	/*	function deletePermissions($itemid, $gperm_name)
	 {
		global $icmsModule;

		$icmsModule =& smartsection_getModuleInfo();

		$result = true;
		$module_id = $icmsModule->getVar('mid')   ;
		$gperm_handler = ('icms_member_groupperm');

		$gperm_handler->deleteByModule($module_id, $gperm_name, $itemid);

		return $result;
		}
		*/
	/**
	 * Checks if the user has access to a specific permission on a given object
	 *
	 * @param string $gperm_name name of the permission to test
	 * @param int $gperm_itemid id of the object to check
	 * @return boolean : TRUE if user has access, FALSE if not
	 **/
	public function accessGranted($gperm_name, $gperm_itemid) {
		$gperm_groupid = is_object(icms::$user) ? icms::$user->getGroups() : array(ICMS_GROUP_ANONYMOUS);
		$icmsModule =& $this->handler->getModuleInfo();
		$gperm_modid = $icmsModule->getVar('mid')   ;

		//Get group permissions handler
		$gperm_handler = icms::handler('icms_member_groupperm');

		return $gperm_handler->checkRight($gperm_name, $gperm_itemid, $gperm_groupid, $gperm_modid);
	}
}

