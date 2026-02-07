<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2004 Freeform Solutions                  ##
##                Portions copyright (c) 2003 NS Tai (aka tuff)              ##
##                       <http://www.brandycoke.com/>                        ##
###############################################################################
##                    XOOPS - PHP Content Management System                  ##
##                       Copyright (c) 2000 XOOPS.org                        ##
##                          <http://www.xoops.org/>                          ##
###############################################################################
##  This program is free software; you can redistribute it and/or modify     ##
##  it under the terms of the GNU General Public License as published by     ##
##  the Free Software Foundation; either version 2 of the License, or        ##
##  (at your option) any later version.                                      ##
##                                                                           ##
##  You may not change or alter any portion of this comment or credits       ##
##  of supporting developers from this source code or any supporting         ##
##  source code which is considered copyrighted (c) material of the          ##
##  original comment or credit authors.                                      ##
##                                                                           ##
##  This program is distributed in the hope that it will be useful,          ##
##  but WITHOUT ANY WARRANTY; without even the implied warranty of           ##
##  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            ##
##  GNU General Public License for more details.                             ##
##                                                                           ##
##  You should have received a copy of the GNU General Public License        ##
##  along with this program; if not, write to the Free Software              ##
##  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA ##
###############################################################################
##  Author of this file: Freeform Solutions and NS Tai (aka tuff) and others ##
##  URL: http://www.brandycoke.com/                                          ##
##  Project: Formulize                                                       ##
###############################################################################

require_once XOOPS_ROOT_PATH.'/kernel/object.php';
require_once XOOPS_ROOT_PATH.'/modules/formulize/include/functions.php';
require_once XOOPS_ROOT_PATH . "/modules/formulize/class/userAccountElement.php";

global $xoopsDB;
define('formulize_TABLE', $xoopsDB->prefix("formulize"));

class formulizeElement extends FormulizeObject {

	var $isLinked;
	var $needsDataType;
	var $overrideDataType;
	var $hasData;
	var $name;
	var $adminCanMakeRequired;
	var $alwaysValidateInputs;
	var $canHaveMultipleValues;
	var $hasMultipleOptions;
	var $isSystemElement; // only set to true in custom element class, if you want an element to exist in the form but be primarily managed by the system
	var $isUserAccountElement; // set to true in user account element classes
	var $useOptionsAsValues; // only applicable to non-linked, non-user select list elements. Set to true if the options for this element should also be used as the values saved to the database.  Default is false, in which case the values used in the HTML markup will be the ordinal position of the option in the list. The list is then recreated on submission so the right value can be retrieved based on ordinal position.
	public static $category = 'misc'; // the category this element belongs to - textboxes, selectors, lists, layout, misc, subforms

	function __construct(){
        parent::__construct();
	//	key, data_type, value, req, max, opt
		$this->initVar("id_form", XOBJ_DTYPE_INT, NULL, false);
		$this->initVar("fid", XOBJ_DTYPE_INT, NULL, false);
		$this->initVar("ele_id", XOBJ_DTYPE_INT, NULL, false);
		$this->initVar("ele_type", XOBJ_DTYPE_TXTBOX, NULL, true, 100);
		$this->initVar("ele_caption", XOBJ_DTYPE_TXTAREA);
		// added descriptive text June 6 2006 -- jwe
		$this->initVar("ele_desc", XOBJ_DTYPE_TXTAREA);
		$this->initVar("ele_colhead", XOBJ_DTYPE_TXTBOX, NULL, false, 255);
		$this->initVar("ele_handle", XOBJ_DTYPE_TXTBOX, NULL, false, 255);
		$this->initVar("ele_order", XOBJ_DTYPE_INT, NULL, false);
    $this->initVar("ele_sort", XOBJ_DTYPE_INT);
		$this->initVar("ele_required", XOBJ_DTYPE_INT);
		$this->initVar("ele_value", XOBJ_DTYPE_ARRAY);
		$this->initVar("ele_uitext", XOBJ_DTYPE_ARRAY); // used for having an alternative text to display on screen, versus the actual value recorded in the database, for radio buttons, checkboxes and selectboxes
    $this->initVar("ele_uitextshow", XOBJ_DTYPE_INT);
		$this->initVar("ele_delim", XOBJ_DTYPE_TXTBOX, NULL, false, 255);
		$this->initVar("ele_forcehidden", XOBJ_DTYPE_INT);
		$this->initVar("ele_private", XOBJ_DTYPE_INT);
 		// changed - start - August 19 2005 - jpc
		//$this->initVar("ele_display", XOBJ_DTYPE_INT);
		$this->initVar("ele_display", XOBJ_DTYPE_TXTBOX);
		// changed - end - August 19 2005 - jpc
		$this->initVar("ele_disabled", XOBJ_DTYPE_TXTBOX); // added June 17 2007 by jwe
		$this->initVar("ele_encrypt", XOBJ_DTYPE_INT); // added July 15 2009 by jwe
		$this->initVar("ele_filtersettings", XOBJ_DTYPE_ARRAY);
		$this->initVar("ele_disabledconditions", XOBJ_DTYPE_ARRAY);
		$this->initVar("ele_use_default_when_blank", XOBJ_DTYPE_INT);
    $this->initVar("ele_exportoptions", XOBJ_DTYPE_ARRAY);
		$this->initVar("ele_dynamicdefault_source", XOBJ_DTYPE_INT);
		$this->initVar('ele_dynamicdefault_conditions', XOBJ_DTYPE_ARRAY);
		$this->useOptionsAsValues = false;
	}

	/**
	 * Return the name that should be used for the element in UI - colhead if there is one, or caption
	 * @return string The name that should be used
	 */
	public function getUIName() {
		$colhead = trans(strip_tags($this->getVar('ele_colhead')));
		return $colhead ? $colhead : trans(strip_tags($this->getVar('ele_caption')));
	}

	/**
	 * Get the screen ids and pages that this element appears on
	 * @return array An array of arrays, primary key is sid, and each sid has an array of page ordinals, 0 is page 1
	 */
	function getScreenIdsAndPages() {
		global $xoopsDB;
		$screenIdsAndPages = array();
		$sql = "SELECT `sid`, `pages` FROM ".$xoopsDB->prefix("formulize_screen_multipage");
		if($res = $xoopsDB->query($sql)) {
			while($array = $xoopsDB->fetchArray($res)) {
				$sid = $array['sid'];
				$pages = unserialize($array['pages']);
				ksort($pages);
				foreach($pages as $pageNumber=>$items) {
					// check that page is a list of element ids
					$firstItem = (is_array($items) AND count($items) > 0) ? $items[array_key_first($items)] : null;
					if(is_numeric($firstItem)) {
						// if element is on this page, record the page as part of that screen
						if(in_array($this->getVar('ele_id'), $items)) {
							$screenIdsAndPages[$sid][] = $pageNumber;
						}
					}
				}
			}
		}
		return $screenIdsAndPages;
	}


	//this method is used to to retreive the elements dataType and size
	function getDataTypeInformation() {
		$defaultType = "text";
		$defaultTypeSize = "";
		global $xoopsDB;
		$form_handler = xoops_getmodulehandler('forms', 'formulize');
		$formObject = $form_handler->get($this->getVar('id_form'));
		$elementDataSQL = "SHOW COLUMNS FROM ".$xoopsDB->prefix("formulize_".$formObject->getVar('form_handle'))." LIKE '".$this->getVar('ele_handle')."'";
		$elementDataRes = $xoopsDB->queryF($elementDataSQL);
		$elementDataArray = $xoopsDB->fetchArray($elementDataRes);
		$defaultTypeComplete = $elementDataArray['Type'];
		$parenLoc = strpos($defaultTypeComplete, "(");
		if($parenLoc) {
			$defaultType = substr($defaultTypeComplete,0,$parenLoc);
			$lengthOfSizeValues = strlen($defaultTypeComplete)-($parenLoc+2);
			$defaultTypeSize = substr($defaultTypeComplete,($parenLoc+1),$lengthOfSizeValues);
			if($defaultType == "decimal") {
				$sizeParts = explode(",", $defaultTypeSize);
				$defaultTypeSize = $sizeParts[1]; // second part of the comma separated value is the number of decimal places declaration
			}
		} else {
			$defaultType = $defaultTypeComplete;
			$defaultTypeSize = '';
		}
		//define array and return type and size
		return array("dataType" => $defaultType, "dataTypeSize" => $defaultTypeSize, "dataTypeCompleteString" => $defaultTypeComplete);
	}

	/**
	 * Check if the element's data type in the database is numeric
	 * @return boolean Returns true or false
	 */
	function hasNumericDataType() {
		$numericDataTypes = array('decimal'=>0, 'float'=>0, 'numeric'=>0, 'double'=>0, 'int'=>0, 'mediumint'=>0, 'tinyint'=>0, 'bigint'=>0, 'smallint'=>0, 'integer'=>0);
		$dataTypeInfo = $this->getDataTypeInformation();
		return isset($numericDataTypes[$dataTypeInfo['dataType']]);
	}

  function createIndex(){
		global $xoopsDB;
		$form_handler = xoops_getmodulehandler('forms', 'formulize');
		$formObject = $form_handler->get($this->getVar('id_form'));

		$defaultTypeInformation = $this->getDataTypeInformation();
		$defaultType = $defaultTypeInformation['dataType'];
		$defaultTypeSize = $defaultTypeInformation['dataTypeSize'];

		$index_fulltext = $defaultType == "text" ? "FULLTEXT" : "INDEX";

		$sql = "ALTER TABLE ".$xoopsDB->prefix("formulize_".formulize_db_escape($formObject->getVar('form_handle')))." ADD $index_fulltext `". formulize_db_escape($this->getVar('ele_handle')) ."` (`".formulize_db_escape($this->getVar('ele_handle'))."`)";
		$res = $xoopsDB->query($sql);
		return $res ? true : false;
	}

	function deleteIndex($original_index_name){
		global $xoopsDB;
		$form_handler = xoops_getmodulehandler('forms', 'formulize');
		$formObject = $form_handler->get($this->getVar('id_form'));
		$sql = "DROP INDEX `".formulize_db_escape($original_index_name)."` ON ".$xoopsDB->prefix("formulize_".formulize_db_escape($formObject->getVar('form_handle')));
		$res = $xoopsDB->query($sql);
	}

	function has_index(){
		global $xoopsDB;
		$indexType = "";

		$form_handler = xoops_getmodulehandler('forms', 'formulize');
		$formObject = $form_handler->get($this->getVar('id_form'));

		//Complex check if
        $elementDataSQL = "SELECT stats.index_name FROM information_schema.statistics AS stats INNER JOIN (SELECT count( 1 ) AS amountCols, index_name FROM information_schema.statistics WHERE table_schema='".XOOPS_DB_NAME."' AND table_name = '".$xoopsDB->prefix("formulize_".$formObject->getVar('form_handle'))."' GROUP BY index_name) AS amount ON amount.index_name = stats.index_name WHERE stats.table_schema='".XOOPS_DB_NAME."' AND stats.table_name = '".$xoopsDB->prefix("formulize_".$formObject->getVar('form_handle'))."' AND stats.column_name = '".$this->getVar('ele_handle')."' AND amount.amountCols =1";

		$elementDataRes = $xoopsDB->queryF($elementDataSQL);
		$elementDataArray = $xoopsDB->fetchArray($elementDataRes);
		$indexType = $elementDataArray['index_name'];

		return $indexType;
	}

    public function assignVar($key, $value) {
        if ("ele_handle" == $key) {
            $value = self::sanitize_handle_name($value);
        }
        parent::assignVar($key, $value);
    }

    public function setVar($key, $value, $not_gpc = false) {
      if ("ele_handle" == $key) {
        $value = self::sanitize_handle_name($value);
      }
			if("id_form" == $key) {
				parent::setVar("fid", $value, $not_gpc);
			}
			if("fid" == $key) {
				parent::setVar("id_form", $value, $not_gpc);
			}
			if($key == 'ele_value') {
				$ele_type = $this->getVar('ele_type');
				$valueToWrite = is_array($value) ? $value : unserialize($value);
				$filename = $ele_type.'_'.$this->getVar('ele_handle').'.php';

				// check if the value is a code block, and if so write to file instead of assigning to property of object
				if(
					(($ele_type == 'ib' OR $ele_type == 'areamodif') AND strstr((string)$valueToWrite[0], "\$value"))
					OR ($ele_type == 'textarea' AND strstr((string)$valueToWrite[0], "\$default"))
				) {
					formulize_writeCodeToFile($filename, $valueToWrite[0]);
					$valueToWrite[0] = '';
					$value = is_array($value) ? $valueToWrite : serialize($valueToWrite);

				// delete the file if it exists but the value no longer contains code, since these elements can have code or plain text values, and plain text is not written as a file
				} elseif(
					((($ele_type == 'ib' OR $ele_type == 'areamodif') AND strstr((string)$valueToWrite[0], "\$value") === false)
					OR ($ele_type == 'textarea' AND strstr((string)$valueToWrite[0], "\$default") === false))
					AND file_exists(XOOPS_ROOT_PATH.'/modules/formulize/code/'.$filename)) {
						unlink(XOOPS_ROOT_PATH.'/modules/formulize/code/'.$filename);
				}
			}
			parent::setVar($key, $value, $not_gpc);
		}

		public function getVar($key, $format = 's') {
			$format = $key == "ele_value" ? "f" : $format;
			$value = parent::getVar($key, $format);
			if($key == 'ele_value') {
				$ele_type = $this->getVar('ele_type');
				if(($ele_type == 'ib'
					OR $ele_type == 'areamodif')
					AND is_array($value)) {
						$filename = $ele_type.'_'.$this->getVar('ele_handle').'.php';
						$filePath = XOOPS_ROOT_PATH.'/modules/formulize/code/'.$filename;
						$fileValue = "";
						if(file_exists($filePath)) {
							$fileValue = strval(file_get_contents($filePath));
						}
						$value[0] = $fileValue ? $fileValue : $value[0];
				}
			}
			return $value;
		}

    // returns true if the option is one of the values the user can choose from in this element
    // returns false if the element does not have options
		// must be overridden in the child class
    function optionIsValid($option) {
        return false;
    }

}

#[AllowDynamicProperties]
class formulizeElementsHandler {

	var $db;
	var $clickable; // used in formatDataForList
	var $striphtml; // used in formatDataForList
	var $length; // used in formatDataForList

	function __construct(&$db) {
		$this->db =& $db;
	}
	function &getInstance(&$db) {
		static $instance;
		if (!isset($instance)) {
			$instance = new formulizeElementsHandler($db);
		}
		return $instance;
	}
	function create() {
		return new formulizeElement();
	}

	/**
	 * Delete any associated data and resources for this element when an element is deleted from a form
	 * @param object $element The element object that is being deleted
	 * @param string|null $entryScope Required. The scope of the deletion. Can be 'all' or an entry ID. If null (default), no action is taken.
	 * @return array A list of the full paths to the files that were deleted
	 */
	function deleteAssociatedDataAndResources($element, $entryScope = null) {
		$deletedFilePaths = array();
		if($entryScope !== 'null' AND $entryScope === 'all') { // only remove code files if the element is being deleted, or the form is being deleted. When an entry is being deleted, entryScope will be the entry ID.
			// we need to delete saved code files if any for this element
			$ele_type = $element->getVar('ele_type');
			$filename = $ele_type.'_'.$element->getVar('ele_handle').'.php';
			if(file_exists(XOOPS_ROOT_PATH.'/modules/formulize/code/'.$filename)) {
				if(unlink(XOOPS_ROOT_PATH.'/modules/formulize/code/'.$filename)) {
					$deletedFilePaths[] = XOOPS_ROOT_PATH.'/modules/formulize/code/'.$filename;
				}
			}
		}
		return $deletedFilePaths;
	}

	/**
	 * Set up and validate a set of element properties
	 * Focuses on the non ele_value properties that are common to all element types
	 * The ele_value options are handled in the child class, since they are element-type specific
	 * @param array $properties The properties to set on the element object
	 * @return array The processed properties that are ready to set on the element object
	 */
	public function setupAndValidateElementProperties($properties) {

		// KEY ASSUMPTION IS THAT THE PROPERTIES HAVE BEEN SET ALREADY BASED ON THE EXISTING ELEMENT OBJECT, IF THERE IS ONE
		// SOME PROPERTIES NOT HANDLED BY THE MCP LAYER (UITEXTSHOW) AND WE'RE ESSENTIALLY FORCING THEM ALWAYS TO DEFAULTS HERE
		// IF/WHEN THIS IS USED MORE WIDELY, WE WILL NEED TO MAKE SURE THAT ALL PROPERTIES ARE HANDLED APPROPRIATELY, ON NEW *AND* EXISTING ELEMENTS

		$config_handler = xoops_gethandler('config');
		$formulizeConfig = $config_handler->getConfigsByCat(0, getFormulizeModId());

		$properties['fid'] = intval($properties['fid']) ? intval($properties['fid']) : 0;
		if($properties['fid'] <= 0) {
			throw new Exception("You must use a valid form when working with an element");
		}
		formulizeHandler::validateElementType($properties['ele_type']);
		$properties['ele_caption'] = trim($properties['ele_caption']);
		if($properties['ele_caption'] == "") {
			throw new Exception("You must use a caption when working with an element");
		}
		$properties['ele_colhead'] = trim($properties['ele_colhead']);
		$properties['ele_handle'] = trim($properties['ele_handle']);
		$properties['ele_desc'] = trim($properties['ele_desc']);
		$properties['ele_required']	= $properties['ele_required'] ? 1 : 0;
		$properties['ele_delim'] = isset($properties['ele_delim']) ? $properties['ele_delim'] : $formulizeConfig['delimeter'];
		$properties['ele_uitextshow'] = isset($properties['ele_uitextshow']) ? $properties['ele_uitextshow'] : 0;
		$properties['ele_order'] = isset($properties['ele_order']) ? intval($properties['ele_order']) : figureOutOrder('bottom', fid: $properties['fid']);
		$properties['ele_display'] = isset($properties['ele_display']) ? $properties['ele_display'] : 1;
		$properties['ele_disabled'] = isset($properties['ele_disabled']) ? $properties['ele_disabled'] : 0;
		return $properties;
	}

	/**
	 * Take data representing a form's properties, and convert any handle refs to ids
	 * Premised on the idea that all the dependencies exist in the database by the time this is being run!
	 * @param array $elementData An associative array of form data, following the form object structure
	 * @return array The modified $formData with numeric dependencies converted to handles
	 */
	public function convertDependenciesForImport($elementData) {
		if($dependencyIdToHandleMap = $this->getElementDependencies($elementData, keyByIds: true)) {
			// handles that should become ids are...
			// ele_filtersettings could have references to other elements in the 0 array
			// ele_disabledconditions could have references to other elements in the 0 array
			$elementData['ele_filtersettings'] = $this->formulize_convertFilterDependenciesToIds($elementData['ele_filtersettings'], $dependencyIdToHandleMap);
			$elementData['ele_disabledconditions'] = $this->formulize_convertFilterDependenciesToIds($elementData['ele_disabledconditions'], $dependencyIdToHandleMap);
			$elementData['ele_dynamicdefault_conditions'] = $this->formulize_convertFilterDependenciesToIds($elementData['ele_dynamicdefault_conditions'], $dependencyIdToHandleMap);
			$elementData['ele_dynamicdefault_source'] = $this->convertElementRefsToIds($elementData['ele_dynamicdefault_source'], $dependencyIdToHandleMap);
			// after replacing those, pass elementData to submethod based on type to element
			if(file_exists(XOOPS_ROOT_PATH.'/modules/formulize/class/'.$elementData['ele_type'].'Element.php')) {
				require_once XOOPS_ROOT_PATH.'/modules/formulize/class/'.$elementData['ele_type'].'Element.php';
				$typeHandler = xoops_getmodulehandler($elementData['ele_type'].'Element', 'formulize');
				if(method_exists($typeHandler, 'convertEleValueDependenciesForImport')) {
					$settingsArray = is_array($elementData['ele_value']) ? $elementData['ele_value'] : unserialize($elementData['ele_value']);
					$settingsArray = $typeHandler->convertEleValueDependenciesForImport($settingsArray, $dependencyIdToHandleMap);
					$elementData['ele_value'] = is_array($elementData['ele_value']) ? $settingsArray : serialize($settingsArray);
				}
			}
		}
		return $elementData;
	}

	/**
	 * Take data representing a form's properties, and convert any numeric dependencies to handles
	 * @param array $elementData An associative array of form data, following the form object structure
	 * @return array The modified $formData with numeric dependencies converted to handles
	 */
	public function convertDependenciesForExport($elementData) {
		if($dependencyIdToHandleMap = $this->getElementDependencies($elementData, keyByIds: true)) {
			// ids that should become handles are...
			// ele_filtersettings could have references to other elements in the 0 array
			// ele_disabledconditions could have references to other elements in the 0 array
			$elementData['ele_filtersettings'] = $this->formulize_convertFilterDependenciesToHandles($elementData['ele_filtersettings'], $dependencyIdToHandleMap);
			$elementData['ele_disabledconditions'] = $this->formulize_convertFilterDependenciesToHandles($elementData['ele_disabledconditions'], $dependencyIdToHandleMap);
			$elementData['ele_dynamicdefault_conditions'] = $this->formulize_convertFilterDependenciesToHandles($elementData['ele_dynamicdefault_conditions'], $dependencyIdToHandleMap);
			$elementData['ele_dynamicdefault_source'] = $this->convertElementRefsToHandles($elementData['ele_dynamicdefault_source'], $dependencyIdToHandleMap);
			// after replacing those, pass elementData to submethod based on type to element
			if(file_exists(XOOPS_ROOT_PATH.'/modules/formulize/class/'.$elementData['ele_type'].'Element.php')) {
				require_once XOOPS_ROOT_PATH.'/modules/formulize/class/'.$elementData['ele_type'].'Element.php';
				$typeHandler = xoops_getmodulehandler($elementData['ele_type'].'Element', 'formulize');
				if(method_exists($typeHandler, 'convertEleValueDependenciesForExport')) {
					$settingsArray = is_array($elementData['ele_value']) ? $elementData['ele_value'] : unserialize($elementData['ele_value']);
					$settingsArray = $typeHandler->convertEleValueDependenciesForExport($settingsArray, $dependencyIdToHandleMap);
					$elementData['ele_value'] = is_array($elementData['ele_value']) ? $settingsArray : serialize($settingsArray);
				}
			}
		}
		return $elementData;
	}

	/**
	 * Get the elements that the passed in element depends on
	 * The elementData ought to be an array coming from config-as-code, which has had all numeric references to elements converted to element handles!
	 * @param array $elementData The element data to check for dependencies, conforms to the structure of the properties of an element object
	 * @param boolean $keyWithIds If true, the returned array is keyed by the element ids of the dependent elements. Must only be used when the passed in elementData is based on current database data, where the ids can be determined from the handles!
	 * @return array An array of element handles that this element depends on, keyed by the element ids of those handles
	 */
	public function getElementDependencies($elementData, $keyByIds = false) {
		$dependencies = array();
		// possible depedencies:
		foreach($elementData as $property => $value) {
			// ele_caption could have { } references to other element handles
			// ele_desc could have { } references to other element handles
			if($property == 'ele_caption' OR $property == 'ele_desc') {
				$text = $value;
				if(strstr($text, "}") AND strstr($text, "{")) {
					$bracketPos = 0;
					$start = true; // flag used to force the loop to execute, even if the 0th position has the {
					while($bracketPos <= strlen($text) AND $bracketPos = strpos($text, "{", $bracketPos) OR $start == true) {
						$start = false;
						$endBracketPos = strpos($text, "}", $bracketPos+1);
						$dependencies[] = substr($text, $bracketPos+1, $endBracketPos-$bracketPos-1);
						$bracketPos = $bracketPos + 1;
					}
				}
			}
			// ele_filtersettings could have references to other elements in the 0 array
			// ele_disabledconditions could have references to other elements in the 0 array
			// passed in elementData ought to have had all numeric references converted to element handles already! Or else formulize_getFilterDependencies will not work. If numeric refs are valid for the current state of database, then we're OK.
			if($property == 'ele_filtersettings' OR $property == 'ele_disabledconditions' OR $property == 'ele_dynamicdefault_conditions') {
				$filterDependencies = $this->formulize_getFilterDependencies($value);
				$dependencies = array_merge($dependencies, $filterDependencies);
			}
			if($property == 'ele_dynamicdefault_source') {
				$dependencies = array_merge($dependencies, $this->formulize_getRegularDependencies($value));
			}
			// ele_value could have various references depending on the element type
			if($property == 'ele_value' AND file_exists(XOOPS_ROOT_PATH.'/modules/formulize/class/'.$elementData['ele_type'].'Element.php')) {
				require_once XOOPS_ROOT_PATH.'/modules/formulize/class/'.$elementData['ele_type'].'Element.php';
				$typeHandler = xoops_getmodulehandler($elementData['ele_type'].'Element', 'formulize');
				if(method_exists($typeHandler, 'getEleValueDependencies')) {
					$settingsArray = is_array($value) ? $value : unserialize($value);
					if(is_array($settingsArray)) {
						$dependencies = array_merge($dependencies, $typeHandler->getEleValueDependencies($settingsArray));
					}
				}
			}
		}
		$dependencies = array_filter(array_unique($dependencies), function($value) {
			return $value !== 'none';
		});
		if($keyByIds) {
			$mappedDependencies = array();
			foreach($dependencies as $depHandle) {
				if($depHandle) {
					if($depElement = _getElementObject($depHandle)) {
						$mappedDependencies[$depElement->getVar('ele_id')] = $depHandle;
					} else {
						throw new Exception("Could not find element with handle $depHandle when trying to map dependencies for export");
					}
				}
			}
			$dependencies = $mappedDependencies;
		}
		return $dependencies;
	}

	/**
	 * Convert passed in filter settings to use handles for the zero array
	 * @param mixed $filterSettings The filter settings, either as an array or a serialized array
	 * @param array $idHandleMap An associative array mapping element ids to element handles
	 * @return array The converteed filterSettings, or throws exception if non-array passed in
	 */
	protected function formulize_convertFilterDependenciesToHandles($filterSettings, $idHandleMap) {
		return $this->formulize_convertFilterDependencies($filterSettings, $idHandleMap, 'export');
	}

	/**
	 * Convert passed in filter settings to use ids for the zero array
	 * @param mixed $filterSettings The filter settings, either as an array or a serialized array
	 * @param array $idHandleMap An associative array mapping element ids to element handles
	 * @return array The converteed filterSettings, or throws exception if non-array passed in
	 */
	protected function formulize_convertFilterDependenciesToIds($filterSettings, $idHandleMap) {
		return $this->formulize_convertFilterDependencies($filterSettings, $idHandleMap, 'import');
	}

	/**
	 * Convert passed in filter settings to use handles for the zero array
	 * @param mixed $filterSettings The filter settings, either as an array or a serialized array
	 * @param array $idHandleMap An associative array mapping element ids to element handles
	 * @param string $direction Either 'import' or 'export' - determines the direction of conversion. Import means handles to ids, export means ids to handles
	 * @return array The converteed filterSettings, or throws exception if non-array passed in
	 */
	private function formulize_convertFilterDependencies($filterSettings, $idHandleMap, $direction) {
		if($direction != 'import' AND $direction != 'export') {
			throw new Exception("Invalid direction passed to convertDependencies: ".$direction.".	Must be 'import' or 'export'.");
			return $filterSettings; // might have exited with the exception, but we'll send this back anyway just in case
		}
		$settingsArray = is_array($filterSettings) ? $filterSettings : unserialize($filterSettings);
		if(is_array($settingsArray) AND !empty($settingsArray)) {
			foreach($settingsArray[0] as $i => $elementIdentifier) {
				if(($direction === 'import' && !is_numeric($elementIdentifier)) || ($direction === 'export' && is_numeric($elementIdentifier))) {
					if($direction === 'export') {
						$settingsArray[0][$i] = isset($idHandleMap[$elementIdentifier]) ? $idHandleMap[$elementIdentifier] : $elementIdentifier;
					} else {
						$foundValue = array_search($elementIdentifier, $idHandleMap);
						$settingsArray[0][$i] = $foundValue !== false ? $foundValue : $elementIdentifier;
					}
				}
			}
			$filterSettings = !is_array($filterSettings) ? serialize($settingsArray) : $settingsArray;
		}
		return $filterSettings;
	}

	/**
	 * Convert element references in a value from handles to ids
	 * @param mixed $value The value to convert, either serialized array string or real array
	 * @param array $idToHandleMap An array mapping element ids to handles
	 * @return mixed The converted value
	 */
	protected function convertElementRefsToIds($value, $idToHandleMap) {
		return $this->convertElementRefsToHandlesOrIds($value, $idToHandleMap, 'import');
	}

	/**
	 * Convert element references in a value from ids to handles
	 * @param mixed $value The value to convert, either serialized array string or real array
	 * @param array $idToHandleMap An array mapping element ids to handles
	 * @return mixed The converted value
	 */
	protected function convertElementRefsToHandles($value, $idToHandleMap) {
		return $this->convertElementRefsToHandlesOrIds($value, $idToHandleMap, 'export');
	}

	/**
	 * Convert element references in a value between handles and ids
	 * @param mixed $value The value to convert, either serialized array string or real array
	 * @param array $idToHandleMap An array mapping element ids to handles
	 * @param string $direction 'import' to convert handles to ids, 'export' to convert ids to handles
	 * @return mixed The converted value
	 */
	private function convertElementRefsToHandlesOrIds($value, $idToHandleMap, $direction) {
		if($direction != 'import' AND $direction != 'export') {
			throw new Exception("Invalid direction passed to convert dependencies: ".$direction.".	Must be 'import' or 'export'.");
			return $value; // might have exited with the exception, but we'll send this back anyway just in case
		}
		// prep as array
		if(!is_array($value)) {
			$unserialized = unserialize($value);
			if(is_array($unserialized)) {
				$workingValues = $unserialized;
			} else {
				$workingValues = array($value);
			}
		} else {
			$workingValues = $value;
		}
		// convert
		foreach($workingValues as $i => $element) {
			if($direction == 'import' AND !is_numeric($element)) {
				$foundValue = array_search($element, $idToHandleMap);
				$workingValues[$i] = $foundValue !== false ? $foundValue : $element;
			} elseif($direction == 'export' AND is_numeric($element)) {
				$workingValues[$i] = isset($idToHandleMap[$element]) ? $idToHandleMap[$element] : $element;
			}
		}
		// put back in original format
		if(!is_array($value)) {
			$unserialized = unserialize($value);
			if(is_array($unserialized)) {
				$value = serialize($workingValues);
			} else {
				$value = $workingValues[0];
			}
		} else {
			$value = $workingValues;
		}
		return $value;
	}

	/**
	 * Get element dependencies from a value that may contain element references
	 * @param mixed $value The value to check, either an integer, string, an array or a serialized array string
	 * @param array $dependencies An array of element handles that this value depends on
	 */
	protected function formulize_getRegularDependencies($value) {
		$dependencies = array();
		if(!is_array($value)) {
			$unserialized = unserialize($value);
			if(is_array($unserialized)) {
				$value = $unserialized;
			} else {
				$value = array($value);
			}
		}
		foreach($value as $element) {
			if(is_numeric($element)) {
				if($elementObject = _getElementObject($element)) {
					$dependencies[] = $elementObject->getVar('ele_handle');
				}
			} elseif($element AND $element != 'none') {
				$dependencies[] = $element;
			}
		}
		return $dependencies;
	}

	/**
	 * Get element dependencies from a standard filter settings array
	 * @param mixed $filterSettings The filter settings, either as an array or a serialized array
	 * @return array An array of element handles that this filter depends on
	 */
	protected function formulize_getFilterDependencies($filterSettings) {
		$dependencies = array();
		$settingsArray = is_array($filterSettings) ? $filterSettings : unserialize($filterSettings);
		if(is_array($settingsArray) AND !empty($settingsArray)) {
			foreach($settingsArray[0] as $dependency) {
				if(is_numeric($dependency)) {
					if($depElement = _getElementObject($dependency)) {
						$dependencies[] = $depElement->getVar('ele_handle');
					}
				} else {
					$dependencies[] = $dependency;
				}
			}
		}
		return $dependencies;
	}

	/**
	 * Get an element object based on id or handle
	 * Caches elements so that multiple calls for the same element do not hit the database more than once
	 * @param mixed $id The element id (int) or handle (string)
	 * @return mixed The element object, or false if not found
	 */
	function get($idOrHandle){
		static $cachedElements = array();
		if(isset($cachedElements[$idOrHandle])) {
			return $cachedElements[$idOrHandle];
		}
		if (is_numeric($idOrHandle) AND $idOrHandle > 0) {
			$sql = 'SELECT * FROM '.formulize_TABLE.' WHERE ele_id='.$idOrHandle;
			if (!$result = $this->db->query($sql)) {
				$cachedElements[$idOrHandle] = false;
				return false;
			}
		} else {
			$sql = "SELECT * FROM ".formulize_TABLE." WHERE ele_handle='".formulize_db_escape($idOrHandle)."'";
			if (!$result = $this->db->query($sql)) {
				$cachedElements[$idOrHandle] = false;
				return false;
			}
		}
		$numrows = $this->db->getRowsNum($result);
		if ($numrows == 1) {
			// instantiate the right kind of element, depending on the type
			$array = $this->db->fetchArray($result);
			$ele_type = $array['ele_type'];
			if(file_exists(XOOPS_ROOT_PATH."/modules/formulize/class/".$ele_type."Element.php")) {
				$customTypeHandler = xoops_getmodulehandler($ele_type."Element", 'formulize');
				$element = $customTypeHandler->create();
			} else {
				$element = new formulizeElement();
			}
			$element->assignVars($array);
      $element = $this->_setElementProperties($element);
			$cachedElements[$idOrHandle] = $element;
			return $element;
		}
		return false;
	}

    function _setElementProperties($element) {
			$element->isLinked = is_bool($element->isLinked) ? $element->isLinked : false;
			$element->isSystemElement = is_bool($element->isSystemElement) ? $element->isSystemElement : false;
			$element->isUserAccountElement = is_bool($element->isUserAccountElement) ? $element->isUserAccountElement : false;
			$element->hasMultipleOptions = is_bool($element->hasMultipleOptions) ? $element->hasMultipleOptions : false;
			$element->setVar('fid', $element->getVar('id_form'));
			if(method_exists($element, 'setCanHaveMultipleValues')) {
				$element->canHaveMultipleValues = $element->setCanHaveMultipleValues();
			} elseif(!is_bool($element->canHaveMultipleValues)) {
				$element->canHaveMultipleValues = false;
			}
			return $element;
    }

	function insert(&$element, $force = false){
        if( get_class($element) != 'formulizeElement' AND is_subclass_of($element, 'formulizeElement') == false){
            return false;
        }
        if( !$element->isDirty() ){
            return true;
        }
        if( !$element->cleanVars() ){
            return false;
        }
				foreach( $element->cleanVars as $k=>$v ){
					${$k} = $v;
				}

				$ele_handle = $this->validateElementHandle($element);

   		if( $element->isNew() || !$ele_id ) { // isNew is never set on the element object or parent??
				$sql = sprintf("INSERT INTO %s (
				id_form, ele_type, ele_caption, ele_desc, ele_colhead, ele_handle, ele_order, ele_sort, ele_required, ele_value, ele_uitext, ele_uitextshow, ele_delim, ele_display, ele_disabled, ele_forcehidden, ele_private, ele_encrypt, ele_filtersettings, ele_disabledconditions, ele_use_default_when_blank, ele_exportoptions, ele_dynamicdefault_source, ele_dynamicdefault_conditions
				) VALUES (
				%u, %s, %s, %s, %s, %s, %u, %u, %u, %s, %s, %u, %s, %s, %s, %u, %u, %u, %s, %s, %u, %s, %u, %s
				)",
				formulize_TABLE,
				$id_form,
				$this->db->quoteString($ele_type),
				$this->db->quoteString($ele_caption),
				$this->db->quoteString($ele_desc),
				$this->db->quoteString($ele_colhead),
				$this->db->quoteString($ele_handle),
				$ele_order,
                $ele_sort,
				$ele_required,
				$this->db->quoteString($ele_value),
				$this->db->quoteString($ele_uitext),
                $ele_uitextshow,
				$this->db->quoteString($ele_delim),
				$this->db->quoteString($ele_display),
				$this->db->quoteString($ele_disabled),
				$ele_forcehidden,
				$ele_private,
				$ele_encrypt,
				$this->db->quoteString($ele_filtersettings),
				$this->db->quoteString($ele_disabledconditions),
				$ele_use_default_when_blank,
        $this->db->quoteString($ele_exportoptions),
				$ele_dynamicdefault_source,
				$this->db->quoteString($ele_dynamicdefault_conditions)
			);
            // changed - end - August 19 2005 - jpc
			}else{
            // changed - start - August 19 2005 - jpc
            $sql = sprintf("UPDATE %s SET
				ele_type = %s,
				ele_caption = %s,
				ele_desc = %s,
				ele_colhead = %s,
				ele_handle = %s,
				ele_order = %u,
                ele_sort = %u,
				ele_required = %u,
				ele_value = %s,
				ele_uitext = %s,
                ele_uitextshow = %u,
				ele_delim = %s,
				ele_display = %s,
				ele_disabled = %s,
				ele_forcehidden = %u,
				ele_private = %u,
				ele_encrypt = %u,
				ele_filtersettings = %s,
				ele_disabledconditions = %s,
				ele_use_default_when_blank = %u,
        ele_exportoptions = %s,
				ele_dynamicdefault_source = %u,
				ele_dynamicdefault_conditions = %s
				WHERE ele_id = %u AND id_form = %u",
				formulize_TABLE,
				$this->db->quoteString($ele_type),
				$this->db->quoteString($ele_caption),
				$this->db->quoteString($ele_desc),
				$this->db->quoteString($ele_colhead),
				$this->db->quoteString($ele_handle),
				$ele_order,
                $ele_sort,
				$ele_required,
				$this->db->quoteString($ele_value),
				$this->db->quoteString($ele_uitext),
                $ele_uitextshow,
				$this->db->quoteString($ele_delim),
				$this->db->quoteString($ele_display),
				$this->db->quoteString($ele_disabled),
				$ele_forcehidden,
				$ele_private,
				$ele_encrypt,
				$this->db->quoteString($ele_filtersettings),
				$this->db->quoteString($ele_disabledconditions),
				$ele_use_default_when_blank,
        $this->db->quoteString($ele_exportoptions),
				$ele_dynamicdefault_source,
				$this->db->quoteString($ele_dynamicdefault_conditions),
				$ele_id,
				$id_form
			);
            // changed - end - August 19 2005 - jpc
 		}

        if( false != $force ){
            $result = $this->db->queryF($sql);
        }else{
            $result = $this->db->query($sql);
        }

		if( !$result ){
			print "Error: this element could not be saved in the database.  SQL: $sql<br>".$this->db->error();
			return false;
		}
		if( !$ele_id ){ // only occurs for new elements
			$ele_id = $this->db->getInsertId();
			$element->setVar('ele_id', $ele_id);
		}
		return $ele_id;
	}

	/**
	 * Get default values for an element object
	 * Instantiate the handler for the element type if one exists, and get the default value from the getDefaultValue method
	 * The type handler method must be called because different types of elements have different ways of defining defaults
	 * This method is simply necessary for cases where the generic element handler has been invoked, instead of a type handler, and we don't want to make the user do the work below each time they need a default value
	 * @param int|string|object $elementIdentifier The element object or id or handle to get the default value for
	 * @param int $entry_id The entry id to get the default value for
	 * @return mixed The default value for the element, or false if none can be determined
	 * @throws Exception If the element object cannot be retrieved
	 */
	function getDefaultValue($elementIdentifier, $entry_id = 'new') {
    if(!$elementObject = _getElementObject($elementIdentifier)) {
      throw new Exception("Invalid element object passed to getDefaultValue");
    }
    $ele_type = $elementObject->getVar('ele_type');
    if(file_exists(XOOPS_ROOT_PATH.'/modules/formulize/class/'.$ele_type.'Element.php')) {
      require_once XOOPS_ROOT_PATH.'/modules/formulize/class/'.$ele_type.'Element.php';
      $typeHandler = xoops_getmodulehandler($ele_type.'Element', 'formulize');
      // Check if the method is declared in the child class specifically
      if(method_exists($typeHandler, 'getDefaultValue')) {
        $reflection = new ReflectionMethod($typeHandler, 'getDefaultValue');
        // Check if declaring class is NOT the parent class
        if($reflection->getDeclaringClass()->getName() !== 'formulizeElementsHandler') {
          return $typeHandler->getDefaultValue($elementObject, $entry_id);
        }
      }
    }
    return false;
	}

	/**
	 * Initialize an element handle based on the caption, or element id if no caption
	 * @param object $element The element object to initialize the handle for
	 * @return string The initialized element handle, or existing handle if there is one
	 */
	function validateElementHandle($element) {
		if(!$element instanceof formulizeElement) {
			throw new Exception("Invalid element object passed to initializeElementHandle");
		}
		$ele_handle = $element->getVar('ele_handle');
		if(!$ele_handle) {
			// make a sanitized handle based on the caption
			// if no caption, use the element id
			$form_handler = xoops_getmodulehandler('forms', 'formulize');
			if(!$formObject = $form_handler->get($element->getVar('fid'))) {
				throw new Exception("Could not retrieve form object for id ".$element->getVar('fid').", when trying to make default ele_handle for element.");
			}
			$form_handle = $formObject->getVar('form_handle');
			$ele_handle = $form_handle.'_'.formulizeElement::sanitize_handle_name($element->getVar('ele_caption'));
		}
		$ele_handle = formulizeHandler::enforceUniqueElementHandles($ele_handle, $element->getVar('ele_id'), $element->getVar('fid'));
		$ele_handle = substr($ele_handle, 0, 64); // enforce max length of 64 characters
		$element->setVar('ele_handle', $ele_handle);
		return $ele_handle;
	}

	/**
	 * Renames references to an element's handle in other elements, code files, and element definitions when the handle is changed.
	 * NOT COMPLETE - still need to update references in various places.
	 * @param object $elementObject The element object that was changed
	 * @param string $original_handle The original handle of the element before it was changed
	 * @return void
	 */
	function renameElementResources($elementObject, $original_handle) {
		if($original_handle) {
			if(!$elementObject = _getElementObject($elementObject)) {
				throw new Exception("Invalid element object passed to renameElementResources");
			}
			global $xoopsDB;
			$ele_handle = $elementObject->getVar('ele_handle');
			$fid = $elementObject->getVar('fid');
			if($ele_handle != $original_handle) {
				// rewrite references in other elements to this handle (linked selectboxes)
				$ele_handle_len = strlen($ele_handle) + 5 + strlen($fid);
				$orig_handle_len = strlen($original_handle) + 5 + strlen($fid);
				$lsbHandleFormDefSQL = "UPDATE " . $xoopsDB->prefix("formulize") . " SET ele_value = REPLACE(ele_value, 's:$orig_handle_len:\"$fid#*=:*$original_handle', 's:$ele_handle_len:\"$fid#*=:*$ele_handle') WHERE ele_value LIKE '%$fid#*=:*$original_handle%'"; // must include the cap lengths or else the unserialization of this info won't work right later, since ele_value is a serialized array!
				if(!$res = $xoopsDB->query($lsbHandleFormDefSQL)) {
					print "Error:  update of linked selectbox element definitions failed.";
				}
				// rewrite references in derived values code
				foreach((array)scandir(XOOPS_ROOT_PATH.'/modules/formulize/code/') as $file) {
					if(strstr($file, 'derived_') !== false) {
						$code = file_get_contents(XOOPS_ROOT_PATH.'/modules/formulize/code/'.$file);
						$encapsulatingCharacter1 = '"';
						$encapsulatingCharacter2 = '"';
						$newCode = str_replace($encapsulatingCharacter1.$original_handle.$encapsulatingCharacter2, $encapsulatingCharacter1.$ele_handle.$encapsulatingCharacter2, $code);
						if($newCode != $code) {
							formulize_writeCodeToFile($file, $newCode);
						}
					}
				}
				// rewrite references in text for display
				$selectElementsSQL = "SELECT ele_id, ele_value FROM " . $xoopsDB->prefix("formulize") . " WHERE ele_value LIKE '%".$original_handle."%' AND (ele_type = 'areamodif' OR ele_type = 'ib')";
				if($res = $xoopsDB->query($selectElementsSQL)) {
						while($row = $xoopsDB->fetchRow($res)) {
								$thisEleId = $row[0];
								$thisEleValue = $row[1];
								$encapsulatingCharacter1 = '{';
								$encapsulatingCharacter2 = '}';
								$thisEleValue = unserialize($thisEleValue);
								$eleValueZero = $thisEleValue[0];
								$eleValueZero = str_replace($encapsulatingCharacter1.$original_handle.$encapsulatingCharacter2, $encapsulatingCharacter1.$ele_handle.$encapsulatingCharacter2, $eleValueZero);
								$thisEleValue[0] = $eleValueZero;
								$thisEleValue = serialize($thisEleValue);
								$updateSQL = "UPDATE " . $xoopsDB->prefix("formulize") . " SET ele_value = '".formulize_db_escape($thisEleValue)."' WHERE ele_id = $thisEleId";
								$xoopsDB->query($updateSQL);
						}
				}
				// update element code file names
				$elementTypes = array('ib', 'areamodif', 'text', 'textarea', 'derived');
				foreach($elementTypes as $type) {
					$oldFileName = XOOPS_ROOT_PATH.'/modules/formulize/code/'.$type.'_'.$original_handle.'.php';
					$newFileName = XOOPS_ROOT_PATH.'/modules/formulize/code/'.$type.'_'.$ele_handle.'.php';
					if(file_exists($oldFileName)) {
						rename($oldFileName, $newFileName);
					}
				}
			}
		}
	}

	function delete($elementObject, $force = false){
		$elementType = $elementObject->getVar('ele_type');
		if(file_exists(XOOPS_ROOT_PATH . "/modules/formulize/class/".$elementType."Element.php")) {
			$typeElementHandler = xoops_getmodulehandler($elementType.'Element', 'formulize');
		} else {
			$typeElementHandler = xoops_getmodulehandler('elements', 'formulize');
		}
		if($result0 = $typeElementHandler->deleteAssociatedDataAndResources($elementObject, entryScope: 'all') === false) {
			print "Error: pre-delete processing for element ".htmlspecialchars(strip_tags($elementObject->getVar('ele_id')))." failed";
		}
		$form_handler = xoops_getmodulehandler('forms', 'formulize');
		$sql = "DELETE FROM ".formulize_TABLE." WHERE ele_id=".$elementObject->getVar("ele_id")."";
		if( false != $force ){
			$result1 = $this->db->queryF($sql);
		}else{
			$result1 = $this->db->query($sql);
		}
		$result2 = deleteElementConnectionsInRelationships($elementObject->getVar('fid'), $elementObject->getVar('ele_id'));
		if($elementObject->hasData) {
			if(!$result3 = $form_handler->deleteElementField($elementObject->getVar('ele_id'))) {
				print "Error: could not drop field from data table";
			}
    }
		$result4 = true;
		if($formObject = $form_handler->get($elementObject->getVar('fid'))) {
			if($elementObject->getVar('ele_id') == $formObject->getVar('pi')) {
				$formObject->setVar('pi', 0);
				$result4 = $form_handler->insert($formObject);
			}
		}

		$screenHandler = xoops_getmodulehandler('multiPageScreen', 'formulize');
		$screenHandler->removeElementsFromScreens($elementObject->getVar('ele_id'));

		return ($result0 AND $result1 AND $result2 AND $result3 AND $result4) ? true : false;
	}

	// id_as_key can be true, false or "handle" or "element_id" in which case handles or the element ids will be used
	function &getObjects($criteria = null, $id_form = 0, $id_as_key = false){
		$ret = array();
		$limit = $start = 0;
//		awareness of $criteria added, Sept 1 2005, jwe
//		removal of ele_display=1 from next line and addition of the renderWhere line in the conditional below
        $idFormOperator = $id_form > 0 ? "=" : ">";
		$sql = 'SELECT * FROM '.formulize_TABLE.' WHERE id_form '.$idFormOperator.' '.intval($id_form);

		if( isset($criteria)) {
			$sql .= $criteria->render() ? ' AND ('.$criteria->render().')' : '';
			if( $criteria->getSort() != '' ){
				$criteriaByClause = ' ORDER BY '.$criteria->getSort().' '.$criteria->getOrder();
			}
			$limit = $criteria->getLimit();
			$start = $criteria->getStart();
		}
		if(!isset($criteriaByClause)) {
			$sql .= " ORDER BY ele_order ASC";
		} else {
			$sql .= $criteriaByClause;
		}
		$result = $this->db->query($sql, $limit, $start);

		if( !$result ){
			return false;
		}
		while( $myrow = $this->db->fetchArray($result) ){
			// instantiate the right kind of element, depending on the type
			$ele_type = $myrow['ele_type'];
			if(file_exists(XOOPS_ROOT_PATH."/modules/formulize/class/".$ele_type."Element.php")) {
				$customTypeHandler = xoops_getmodulehandler($ele_type."Element", 'formulize');
				$elements = $customTypeHandler->create();
			} else {
				$elements = new formulizeElement();
			}
			$elements->assignVars($myrow);
      $elements = $this->_setElementProperties($elements);
			if($id_as_key === true OR $id_as_key == "element_id"){
				$ret[$myrow['ele_id']] =& $elements;
			}elseif($id_as_key == "handle") {
				$ret[$myrow['ele_handle']] =& $elements;
			} else {
				$ret[] =& $elements;
			}
			unset($elements);
		}
		return $ret;
	}


  function getCount($criteria = null){
		$sql = 'SELECT COUNT(*) FROM '.formulize_TABLE;
		if( isset($criteria) ) {
			$sql .= ' '.$criteria->renderWhere();
		}
		$result = $this->db->query($sql);
		if( !$result ){
			return 0;
		}
		list($count) = $this->db->fetchRow($result);
		return $count;
	}

	// this method returns the id number of the element with the next highest order, below the specified order, in the specified form
	function getPreviousElement($order, $fid) {
		global $xoopsDB;
		$sql = "SELECT ele_id FROM ".$xoopsDB->prefix("formulize")." WHERE ele_order < $order AND id_form = $fid ORDER BY ele_order DESC LIMIT 0,1";
		if($result = $xoopsDB->query($sql)) {
			$array = $xoopsDB->fetchArray($result);
			return isset($array['ele_id']) ? $array['ele_id'] : 0;
		} else {
			return false;
		}
	}

	// this method is used by custom elements, to do final output from the "local" formatDataForList method, so the custom element developer can simply set booleans there, and they will be enforced here
	function formatDataForList($value, $handle="", $entry_id=0, $textWidth=100) {
		global $myts;
		$value = trans($value);
		if(!$this->length AND $this->length !== 0 AND $this->length !== '0') {
			$this->length = 35;
		}
		if($this->striphtml !== false) { // want to do this all the time, no matter what, unless the user specifically turns it off, because it's a security precaution
			$value = $myts->htmlSpecialChars($value, ENT_QUOTES);
		}
		if($this->length > 0) {
			$value = printSmart($value,$this->length);
		}
		if($this->clickable) {
			$value = formulize_text_to_hyperlink($value);
		}
		$value = formulize_handleRandomAndDateText($value);
		return $value;
	}

	    // determine if the element is disabled for the specified user
    function isElementDisabledForUser($elementIdOrObject, $userIdOrObject=0) {
        if(is_object($elementIdOrObject)) {
            $elementObject = $elementIdOrObject;
        } else {
            $elementObject = $this->get($elementIdOrObject);
        }
        $ele_disabled = $elementObject->getVar('ele_disabled');
        if($ele_disabled == 1) {
			return true;
		} elseif(!is_numeric($ele_disabled)) {
            if(is_object($userIdOrObject)) {
                $userObject = $userIdOrObject;
            } elseif($userIdOrObject) {
                $memberHandler = xoops_gethandler('member');
                $userObject = $memberHandler->getUser($userIdOrObject);
            }
            $groups = $userObject ? $userObject->getGroups() : array(XOOPS_GROUP_ANONYMOUS);
            $disabled_groups = explode(",", $ele_disabled);
            // user must not be a member of any group that the element is NOT disabled for. If they are in one group that can interact, the element will be enabled.
            if(array_intersect($groups, $disabled_groups) AND !array_diff($groups, $disabled_groups)) {
                return true;
			}
		}
        return false;
    }

    // determine if the element is displayed for the specified user
    function isElementVisibleForUser($elementIdOrObject, $userIdOrObject=0) {
        if(is_object($elementIdOrObject)) {
            $elementObject = $elementIdOrObject;
        } else {
            $elementObject = $this->get($elementIdOrObject);
        }
        $ele_display = $elementObject->getVar('ele_display');
        if($ele_display == 1) {
			return true;
		} elseif(!is_numeric($ele_display)) {
            if(is_object($userIdOrObject)) {
                $userObject = $userIdOrObject;
            } elseif($userIdOrObject) {
                $memberHandler = xoops_gethandler('member');
                $userObject = $memberHandler->getUser($userIdOrObject);
            }
            $groups = $userObject ? $userObject->getGroups() : array(XOOPS_GROUP_ANONYMOUS);
            $display_groups = explode(",", $ele_display);
            if(array_intersect($groups, $display_groups)) {
                return true;
			}
		}
        return false;
    }

	// overridden in child classes
	// LINKED ELEMENTS AND UITEXT ARE RESOLVED PRIOR TO THIS METHOD BEING CALLED
	function prepareLiteralTextForDB($value, $element, $partialMatch=false) {
		return $value;
	}

	/**
	 * Process user account data through the base userAccountElement handler class
	 * @param int $formId The id of the form the element is in
	 * @param int $entryId The id of the entry that was submitted
	 * @return int|bool the user id or false on failure
	 */
	static public function processUserAccountSubmission($formId, $entryId) {
		return formulizeUserAccountElementHandler::processUserAccountSubmission($formId, $entryId);
	}
}

function optionIsValidForElement($option, $elementHandleOrId) {
    if(!$element = _getElementObject($elementHandleOrId)) {
			return false;
    }
    return $element->optionIsValid($option);
}

/**
 * Take a type string and return true if it is any type of element based on the Select type
 * @param string $type
 * @return bool
 */
function anySelectElementType($type) {
	$baseTypes = array("select","autocomplete","listbox");
	$subTypes = array("","Linked","Users");
	foreach($baseTypes as $base) {
		foreach($subTypes as $sub) {
			if ($type == $base.$sub) {
				return true;
			}
		}
	}
	return false;
}

/**
 * Extract the form id and element handle from the ele_value of a linked element
 * @param object $elementObject The element object to check
 * @return array An array with the form id as the first element and the element handle as the second element, or false if not found or not a linked element
 */
function getSourceFormAndElementForLinkedElement($elementObject) {
	if(is_a($elementObject, 'formulizeElement') AND $elementObject->isLinked) {
		$ele_value = $elementObject->getVar('ele_value');
		list($form_id, $element_handle) = explode("#*=:*", $ele_value[2]);
		if($form_id AND $element_handle) {
			return array(intval($form_id), $element_handle);
		}
	}
	return false;
}

/**
 * Look at the link settings for an element and return the form id of the source form for the linked element
 * @param object $elementObject The element object to check
 * @return int The id of the source form for the linked element, or false if not found or not a linked element
 */
function getSourceFormIdForLinkedElement($elementObject) {
	list($form_id, $element_handle) = getSourceFormAndElementForLinkedElement($elementObject);
	return $form_id;
}

/**
 * Look at the link settings for an element and return the element id of the source element for the linked element
 * @param object $elementObject The element object to check
 * @return int The id of the source element for the linked element, or false if not found or not a linked element
 */
function getSourceElementHandleForLinkedElement($elementObject) {
	list($form_id, $element_handle) = getSourceFormAndElementForLinkedElement($elementObject);
	return $element_handle;
}
