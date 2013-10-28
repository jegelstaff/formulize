<?php
/**
 * Class representing the profile category object
 *
 * @copyright	The ImpressCMS Project <http://www.impresscms.org>
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @since		1.4
 * @author		phoenyx
 * @package		profile
 * @version		$Id: CategoryHandler.php 20122 2010-09-09 18:09:55Z phoenyx $
 */

defined("ICMS_ROOT_PATH") or die("ICMS root path not defined");

class mod_profile_CategoryHandler extends icms_ipf_Handler {
	/**
	 * Constructor
	 *
	 * @param icms_db_legacy_Database $db database connection object
	 */
	public function __construct(&$db) {
		parent::__construct($db, 'category', 'catid', 'cat_title', 'cat_description', basename(dirname(dirname(__FILE__))));
	}

	/*
	 * beforeDelete event
	 *
	 * Event automatically triggered by IcmsPersistable Framework before the object is deleted
	 *
	 * @param mod_profile_Category $obj object
	 * @return bool
	 */
	protected function beforeDelete(&$obj) {
		$profile_fields_handler = icms_getModuleHandler('field', basename(dirname(dirname(__FILE__))), 'profile');
		$fields_count = $profile_fields_handler->getCount(icms_buildCriteria(array('catid' => $obj->getVar('catid'))));
		if ($fields_count == 0) return true;
		$obj->setErrors(sprintf(_AM_PROFILE_CATEGORY_NOTDELETED_FIELDS, $fields_count));
		return false;
	}
}
?>