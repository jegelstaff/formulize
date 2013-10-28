<?php
/**
 * ImpressCMS Mimetypes
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package		System
 * @subpackage	Mimetypes
 * @since		1.2
 * @author		Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @version		SVN: $Id: mimetype.php 21378 2011-03-30 13:46:23Z m0nty_ $
 */

defined("ICMS_ROOT_PATH") or die("ImpressCMS root path not defined");

icms_loadLanguageFile('system', 'mimetype', TRUE);

/**
 * Mimetype management for file handling
 * 
 * @package		System
 * @subpackage	Mimetypes
 */
class SystemMimetype extends icms_ipf_Object {
	public $content = FALSE;

	/**
	 * Constructor
	 * 
	 * @param object $handler
	 */
	function __construct(&$handler) {
		parent::__construct($handler);

		$this->quickInitVar('mimetypeid', XOBJ_DTYPE_INT, TRUE);
		$this->quickInitVar('extension', XOBJ_DTYPE_TXTBOX, TRUE, _CO_ICMS_MIMETYPE_EXTENSION, _CO_ICMS_MIMETYPE_EXTENSION_DSC);
		$this->quickInitVar('types', XOBJ_DTYPE_TXTAREA, TRUE, _CO_ICMS_MIMETYPE_TYPES, _CO_ICMS_MIMETYPE_TYPES_DSC);
		$this->quickInitVar('name', XOBJ_DTYPE_TXTBOX, TRUE, _CO_ICMS_MIMETYPE_NAME, _CO_ICMS_MIMETYPE_NAME_DSC);
		$this->quickInitVar('dirname', XOBJ_DTYPE_SIMPLE_ARRAY, TRUE, _CO_ICMS_MIMETYPE_DIRNAME);

		$this->setControl('dirname', array(
			'name' => 'selectmulti',
			'itemHandler' => 'icms_module',
			'method' => 'getActive'));
	}

	/**
	 * (non-PHPdoc)
	 * @see icms_ipf_Object::getVar()
	 * @return	mixed	Value of the selected property
	 */
	public function getVar($key, $format = 's') {
		if ($format == 's' && in_array($key, array())) {
			return call_user_func(array($this, $key));
		}
		return parent::getVar($key, $format);
	}

	/**
	 * Determines if a variable is a zero length string
	 * @param string $var
	 * @return	boolean
	 */
	public function emptyString($var) {
		return strlen($var) > 0;
	}

	/**
	 * Get the name property of the selected mimetype
	 * @return	string
	 */
	public function getMimetypeName() {
		$ret = $this->getVar('name');
		return $ret;
	}

	/**
	 * Get the type of the selected mimetype
	 * @return	string
	 */
	public function getMimetypeType() {
		$ret = $this->getVar('types');
		return $ret;
	}

	/**
	 * Get the ID of the selected mimetype
	 * @return	int
	 */
	public function getMimetypeId() {
		$ret = (int) $this->getVar('mimetypeid');
		return $ret;
	}
}

/**
 * Handler for the mimetype object class
 * 
 * @package		System
 * @subpackage	Mimetypes
 */
class SystemMimetypeHandler extends icms_ipf_Handler {
	
	public $objects = FALSE;

	/**
	 * Creates an instance of the mimetype handler
	 * 
	 * @param object $db
	 */
	public function __construct($db) {
		parent::__construct($db, 'mimetype', 'mimetypeid', 'mimetypeid', 'name', 'system');
		$this->addPermission('use_extension', _CO_ICMS_MIMETYPE_PERMISSION_VIEW, _CO_ICMS_MIMETYPE_PERMISSION_VIEW_DSC);
	}

	/**
	 * 
	 * @return	array
	 */
	public function UserCanUpload() {
		$handler = new icms_ipf_permission_Handler($this);
		return $handler->getGrantedItems('use_extension');
	}

	/**
	 * Returns a list of mimetypes allowed for the user
	 * @return	array
	 */
	public function AllowedMimeTypes() {
		$GrantedItems =  $this->UserCanUpload();
		$array = array();
		$grantedItemValues = array_values($GrantedItems);
		if (!empty($grantedItemValues)) {
			$sql = "SELECT types " . "FROM " . $this->table . " WHERE (mimetypeid='";
			if (count($grantedItemValues)>1) {
				foreach ($grantedItemValues as $grantedItemValue) {
					$sql .= ($grantedItemValue != $grantedItemValues[0]) ? $grantedItemValue . "' OR mimetypeid='" : "";
				}
			}
			$sql .= $grantedItemValues[0] . "')";
			$Qvalues = $this->query($sql, FALSE);
			for ($i = 0; $i < count($Qvalues); $i++) {
				$values[]= explode(' ', $Qvalues[$i]['types']);
			}
			foreach ($values as $item=>$value) {
				$array = array_merge($array, $value);
			}
		}
		return $array;
	}

	/**
	 * Returns a list of modules
	 * @return	array 
	 * @deprecated	Use icms_module_Handler::getActive, instead
	 * @todo		Remove in version 1.4
	 */
	public function getModuleList() {
		icms_core_Debug::setDeprecated('icms_module_Handler::getActive', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return icms_module_Handler::getActive();
	}

	/**
	 * 
	 * 
	 * @param string $mimetype
	 * @param string $module
	 * @return	boolean
	 */
	public function AllowedModules($mimetype, $module) {
		$mimetypeid_allowed = $dirname_allowed = FALSE;
		$GrantedItems = $this->UserCanUpload();
		$criteria = new icms_db_criteria_Compo(new icms_db_criteria_Item('types', '%' . $mimetype . '%', 'LIKE'));

		$sql = 'SELECT mimetypeid, dirname, types FROM ' . $this->table;
		$rows = $this->query($sql, $criteria);
		if (count($rows) > 1) {
			for ($i = 0; $i < count($rows); $i++) {
				$mimetypeids[]= $rows[$i]['mimetypeid'];
				$dirname[]= explode('|', $rows[$i]['dirname']);
				$types[]= $rows[$i]['types'];
			}

			foreach ($mimetypeids as $mimetypeid) {
				if (in_array($mimetypeid, $GrantedItems)) {
					$mimetypeid_allowed = TRUE;
				}
			}
			foreach ($dirname as $dir) {
				if (!empty($module) && in_array($module, $dir)) {
					$dirname_allowed = TRUE;
				}
			}
		} elseif (count($rows) == 1) {
			$mimetypeid= $rows[0]['mimetypeid'];
			$dirname= explode('|', $rows[0]['dirname']);
			$types= $rows[0]['types'];
			if (in_array($mimetypeid, $GrantedItems)) {
				$mimetypeid_allowed = TRUE;
			}
			if (!empty($module) && in_array($module, $dirname)) {
				$dirname_allowed = TRUE;
			}
		}
		if ($mimetypeid_allowed && $dirname_allowed) {
			return TRUE;
		} else {
			return FALSE;
		}
	}
}