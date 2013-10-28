<?php
/**
 * Class responsible for managing profile regstep objects
 *
 * @copyright	The ImpressCMS Project <http://www.impresscms.org>
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @since		1.4
 * @author		phoenyx
 * @package		profile
 * @version		$Id: RegstepHandler.php 20122 2010-09-09 18:09:55Z phoenyx $
 */

defined("ICMS_ROOT_PATH") or die("ICMS root path not defined");

class mod_profile_RegstepHandler extends icms_ipf_Handler {
	/**
	 * Constructor
	 *
	 * @param icms_db_legacy_Database $db database connection object
	 */
	public function __construct(&$db) {
		parent::__construct($db, 'regstep', 'step_id', 'step_name', 'step_name', basename(dirname(dirname(__FILE__))));
	}

	/**
	 * Insert a new object
	 * @see icms_ipf_Handler::insert()
	 *
	 * @param mod_profile_Regstep $obj object
	 * @param bool $force
	 * @return bool
	 */
	public function insert(&$obj, $force = false) {
		if (parent::insert($obj, $force)) {
			if ($obj->getVar('step_save') == 1) return $this->updateAll('step_save', 0, new icms_db_criteria_Item('step_id', $obj->getVar('step_id'), "!="));
			return true;
		}
		return false;
	}

	/**
	 * Delete an object from the database
	 * @see icms_ipf_Handler::delete()
	 *
	 * @param mod_profile_Regstep $obj
	 * @param bool $force
	 * @return bool
	 */
	public function delete(&$obj, $force = false) {
		if (parent::delete($obj, $force)) {
			$field_handler = icms_getModuleHandler('field', basename(dirname(dirname(__FILE__))), 'profile');
			return $field_handler->updateAll('step_id', 0, new icms_db_criteria_Item('step_id', $obj->getVar('step_id')));
		}
		return false;
	}

	/**
	 * generate a list of all regsteps including "---" as the null value
	 *
	 * @return array list of all regsteps
	 */
	public function getListForFields() {
		return array_merge(array(0 => '---'), $this->getList());
	}
}
?>