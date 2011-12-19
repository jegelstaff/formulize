<?php
/**
 * Class representing the profile field object
 *
 * @copyright	The ImpressCMS <Project http://www.impresscms.org/>
 * @license		LICENSE.txt
 * @license		GNU General Public License (GPL) http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @since		1.2
 * @author		phoenyx
 * @package		profile
 * @version		$Id: FieldHandler.php 22253 2011-08-18 13:32:42Z phoenyx $
 */

defined('ICMS_ROOT_PATH') or die('ICMS root path not defined');

class mod_profile_FieldHandler extends icms_ipf_Handler {
	private $_fieldTypeArray;
	private $_categoriesArray;

	/**
	 * Constructor
	 *
	 * @param icms_db_legacy_Database $db
	 */
	public function __construct(&$db) {
		parent::__construct($db, 'field', 'fieldid', 'field_name', 'field_description', basename(dirname(dirname(__FILE__))));
		$this->enableUpload(array('image/gif', 'image/jpeg', 'image/pjpeg', 'image/png'), 5120, 18, 18);
	}

	/**
	 * get profile fields
	 *
	 * @param icms_member_user_Object $thisUser
	 * @return array of profile fields
	 */
	public function getProfileFields(&$thisUser) {
		// get handlers
		$category_handler = icms_getModuleHandler('category', basename(dirname(dirname(__FILE__))), 'profile');
		$profile_handler = icms_getModuleHandler('profile', basename(dirname(dirname(__FILE__))), 'profile');
		$visibility_handler = icms_getModuleHandler('visibility', basename(dirname(dirname(__FILE__))), 'profile');

		$groups = is_object(icms::$user) ? icms::$user->getGroups() : array(ICMS_GROUP_ANONYMOUS);
		$criteria = new icms_db_criteria_Compo();
		$criteria->setSort("cat_weight");
		$categories = $category_handler->getObjects($criteria);
		$visible_fields = $visibility_handler->getVisibleFields($groups, $thisUser->getGroups());
		unset($criteria);
		$criteria = new icms_db_criteria_Compo();
		$criteria->add(new icms_db_criteria_Item('fieldid', '('.implode(',', $visible_fields).')', 'IN'));
		$criteria->setSort('field_weight');
		$fields = $this->getObjects($criteria);
		$profile = $profile_handler->get($thisUser->getVar('uid'));
		unset($category_handler, $visibility_handler, $profile_handler, $criteria);

		$module = icms::handler("icms_module")->getByDirname(basename(dirname(dirname(__FILE__))), TRUE);
		$rtn = array();
		for ($i = 0; $i < count($categories); $i++) {
			$first_category = true;
			for ($j = 0; $j < count($fields); $j++) {
				$value = $fields[$j]->getOutputValue($thisUser, $profile);
				if ($fields[$j]->getVar('field_show') && $fields[$j]->getVar('catid') == $categories[$i]->getVar('catid') && ($module->config['show_empty'] || trim($value) || $value == '0')) {;
					if ($first_category) $rtn[$i]['title'] = $categories[$i]->getVar('cat_title');
					$first_category = false;
					$rtn[$i]['fields'][$j]['image'] = $fields[$j]->getImage();
					$rtn[$i]['fields'][$j]['title'] = $fields[$j]->getVar('field_title');
					$rtn[$i]['fields'][$j]['value'] = $value;
				}
			}
		}
		return $rtn;
	}

	/**
	 * Read field information from cached storage
	 *
	 * @param bool   $force_update   read fields from database and not cached storage
	 *
	 * @return array
	 */
	public function &loadFields($force_update = false) {
		static $fields = array();

		if ($force_update || count($fields) == 0) {
			$criteria = new icms_db_criteria_Item('fieldid', 0, '!=');
			$criteria->setSort('field_weight');
			$fieldObjs = $this->getObjects($criteria);
			foreach (array_keys($fieldObjs) as $i) $fields[$fieldObjs[$i]->getVar('field_name')] = $fieldObjs[$i];
		}
		return $fields;
	}

	/**
	 * Save a profile field in the database
	 *
	 * @param object $obj reference to the object
	 * @param bool $force whether to force the query execution despite security settings
	 *
	 * @return bool FALSE if failed, TRUE if already present and unchanged or successful
	 */
	public function insert(&$obj, $force = false) {
		$profile_handler = icms_getmodulehandler('profile', basename(dirname(dirname(__FILE__))), 'profile');

		$obj->cleanVars();
		$defaultstring = "";
		switch ($obj->getVar('field_type')) {
			case "datetime":
			case "date":
				$obj->setVar('field_valuetype', XOBJ_DTYPE_INT);
				$obj->setVar('field_maxlength', 10);
				$obj->setVar('field_default', 0);
				break;
			case "longdate":
				$obj->setVar('field_valuetype', XOBJ_DTYPE_MTIME);
				break;
			case "yesno":
				$obj->setVar('field_valuetype', XOBJ_DTYPE_INT);
				$obj->setVar('field_maxlength', 1);
				break;
			case "textbox":
				if ($obj->getVar('field_valuetype') != XOBJ_DTYPE_INT) $obj->setVar('field_valuetype', XOBJ_DTYPE_TXTBOX);
				break;
			case "autotext":
				if ($obj->getVar('field_valuetype') != XOBJ_DTYPE_INT) $obj->setVar('field_valuetype', XOBJ_DTYPE_TXTAREA);
				break;
			case "group_multi":
			case "select_multi":
			case "checkbox":
				$obj->setVar('field_valuetype', XOBJ_DTYPE_ARRAY);
				break;
			case "language":
			case "timezone":
			case "theme":
				$obj->setVar('field_valuetype', XOBJ_DTYPE_TXTBOX);
				break;
			case "dhtml":
			case "textarea":
				$obj->setVar('field_valuetype', XOBJ_DTYPE_TXTAREA);
				break;
		}

		if ($obj->getVar('field_valuetype') == '') $obj->setVar('field_valuetype', XOBJ_DTYPE_TXTBOX);
		$obj->cleanVars();

		if (!in_array($obj->getVar('field_name'), $this->getUserVars())) {
			if ($obj->isNew()) {
				$changetype = "ADD";
			} else {
				$changetype = "CHANGE ".$obj->getVar('field_name', 'n');
			}
			//set type
			switch ($obj->getVar('field_valuetype')) {
				default:
				case XOBJ_DTYPE_ARRAY:
				case XOBJ_DTYPE_EMAIL:
				case XOBJ_DTYPE_TXTBOX:
				case XOBJ_DTYPE_URL:
					$type = "varchar";
					// varchars must have a maxlength
					if (!($obj->getVar('field_maxlength') > 0)) $obj->setVar('field_maxlength', 255);
					if ($obj->getVar('field_default')) $defaultstring = " DEFAULT ".$this->db->quoteString($obj->cleanVars['field_default']);
					break;
				case XOBJ_DTYPE_INT:
					$type = "int";
					if ($obj->getVar('field_default')) $defaultstring = " DEFAULT ".$this->db->quoteString($obj->cleanVars['field_default']);
					break;
				case XOBJ_DTYPE_OTHER:
				case XOBJ_DTYPE_TXTAREA:
					$type = "text";
					break;
				case XOBJ_DTYPE_MTIME:
					$type = "date";
			}
			$maxlengthstring = $obj->getVar('field_maxlength') > 0 ? "(".$obj->getVar('field_maxlength').")" : "";
			$notnullstring = " NOT NULL";
			$sql = "ALTER TABLE ".$profile_handler->table." ".$changetype." ".$obj->cleanVars['field_name']." ".$type.$maxlengthstring.$notnullstring.$defaultstring;
			if (!$this->db->query($sql)) return false;
		}

		//change this to also update the cached field information storage
		$obj->setDirty();
		if (!parent::insert($obj, $force)) return false;

		return true;
	}

	/**
	* delete a profile field from the database
	*
	* @param object $obj reference to the object to delete
	* @param bool $force
	* @return bool FALSE if failed.
	**/
	public function delete(&$obj, $force = false) {
		$profile_handler = icms_getmodulehandler('profile', basename(dirname(dirname(__FILE__))), 'profile');
		$sql = "ALTER TABLE ".$profile_handler->table." DROP ".$obj->getVar('field_name', 'n');
		if ($this->db->query($sql)) {
			if (!parent::delete($obj, $force)) return false;
			if ($obj->getVar('field_show') || $obj->getVar('field_edit')) {
				$profile_module = icms::handler('icms_module')->getByDirname(basename(dirname(dirname(__FILE__))));
				if (is_object($profile_module)) {
					// Remove group permissions
					$criteria = new icms_db_criteria_Compo(new icms_db_criteria_Item('gperm_modid', $profile_module->getVar('mid')));
					$criteria->add(new icms_db_criteria_Item('gperm_itemid', $obj->getVar('fieldid')));
					return icms::handler('icms_member_groupperm')->deleteAll($criteria);
				}
			}
		}
		return false;
	}

	/**
	 * Get array of standard variable names (user table)
	 *
	 * @return array
	 */
	public function getUserVars() {
		return array('uid', 'uname', 'name', 'email', 'url', 'user_avatar', 'user_regdate', 'user_icq', 'user_from',
			         'user_sig', 'user_viewemail', 'actkey', 'user_aim', 'user_yim', 'user_msnm', 'pass', 'posts', 'attachsig',
			         'rank', 'level', 'theme', 'timezone_offset', 'last_login', 'umode', 'uorder', 'notify_method',
			         'notify_mode', 'user_occ', 'bio', 'user_intrest', 'user_mailok', 'language', 'openid', 'salt',
			         'user_viewoid', 'pass_expired', 'enc_type', 'login_name');
	}

	/**
	 * Get array of field types
	 *
	 * @return array of field types
	 */
	public function getFieldTypeArray() {
		if (!$this->_fieldTypeArray) {
			$this->_fieldTypeArray["checkbox"] = _AM_PROFILE_FIELD_TYPE_CHECKBOX;
			$this->_fieldTypeArray["date"] = _AM_PROFILE_FIELD_TYPE_DATE;
			$this->_fieldTypeArray["datetime"] = _AM_PROFILE_FIELD_TYPE_DATETIME;
			$this->_fieldTypeArray["longdate"] = _AM_PROFILE_FIELD_TYPE_LONGDATE;
			$this->_fieldTypeArray["group"] = _AM_PROFILE_FIELD_TYPE_GROUP;
			$this->_fieldTypeArray["group_multi"] = _AM_PROFILE_FIELD_TYPE_GROUPMULTI;
			$this->_fieldTypeArray["language"] = _AM_PROFILE_FIELD_TYPE_LANGUAGE;
			$this->_fieldTypeArray["radio"] = _AM_PROFILE_FIELD_TYPE_RADIO;
			$this->_fieldTypeArray["select"] = _AM_PROFILE_FIELD_TYPE_SELECT;
			$this->_fieldTypeArray["select_multi"] = _AM_PROFILE_FIELD_TYPE_SELECTMULTI;
			$this->_fieldTypeArray["textarea"] = _AM_PROFILE_FIELD_TYPE_TEXTAREA;
			$this->_fieldTypeArray["dhtml"] = _AM_PROFILE_FIELD_TYPE_DHTMLTEXTAREA;
			$this->_fieldTypeArray["textbox"] = _AM_PROFILE_FIELD_TYPE_TEXTBOX;
			$this->_fieldTypeArray["timezone"] = _AM_PROFILE_FIELD_TYPE_TIMEZONE;
			$this->_fieldTypeArray["image"] = _AM_PROFILE_FIELD_TYPE_IMAGE;
			$this->_fieldTypeArray["yesno"] = _AM_PROFILE_FIELD_TYPE_YESNO;
			$this->_fieldTypeArray["rank"] = _AM_PROFILE_FIELD_TYPE_RANK;
			$this->_fieldTypeArray["theme"] = _AM_PROFILE_FIELD_TYPE_THEME;
			$this->_fieldTypeArray["url"] = _AM_PROFILE_FIELD_TYPE_URL;
			$this->_fieldTypeArray["location"] = _AM_PROFILE_FIELD_TYPE_LOCATION;
			$this->_fieldTypeArray["email"] = _AM_PROFILE_FIELD_TYPE_EMAIL;
			$this->_fieldTypeArray["openid"] = _AM_PROFILE_FIELD_TYPE_OPENID;
			asort($this->_fieldTypeArray);
		}
		return $this->_fieldTypeArray;
	}

	/**
	 * create list of categories for table filter
	 *
	 * @return array list of categories
	 */
	public function getCategoriesArray() {
		if (!$this->_categoriesArray) {
			$profile_category_handler = icms_getModuleHandler('category', basename(dirname(dirname(__FILE__))), 'profile');
			$criteria = new icms_db_criteria_Compo();
			$criteria->setSort('cat_title');
			$criteria->setOrder('ASC');
			$categories = $profile_category_handler->getObjects($criteria);
			foreach ($categories as $category) $this->_categoriesArray[$category->getVar('catid')] = $category->getVar('cat_title');
		}
		return $this->_categoriesArray;
	}

	/*
	 * afterDelete event
	 *
	 * Event automatically triggered by IcmsPersistable Framework after the object is deleted
	 *
	 * @param mod_profile_Field $obj object
	 * @return bool
	 */
	protected function afterDelete(&$obj) {
		$imgPath = $this->getImagePath();
		$imgUrl = $obj->getVar('url');
		if (!empty($imgUrl)) unlink($imgPath.$imgUrl);
		return true;
	}
}
?>