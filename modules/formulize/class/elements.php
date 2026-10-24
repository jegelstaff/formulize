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
require_once XOOPS_ROOT_PATH . "/modules/formulize/class/elementReferenceScanTrait.php";
// NB userAccountElement.php is required at the BOTTOM of this file, not here. It declares classes that
// extend formulizeElementsHandler, and formulizeElementsHandler is no longer available part way through
// this file: PHP early binds - hoists - a class declaration only when it does not compose traits, and this
// handler now uses formulizeElementReferenceScanTrait. So the class does not exist until execution reaches
// its declaration below, and anything extending it has to be loaded after that point. Nothing in this file
// needs those classes at declaration time; the two methods that call them do so at runtime.

global $xoopsDB;
define('formulize_TABLE', $xoopsDB->prefix("formulize"));

// The element object cache, keyed by ele_id, and the index that lets a handle find its way in.
//
// Keyed by id alone so that one element is one cached object, however it was asked for. Keying
// by whatever the caller happened to pass would let the same element sit in the cache twice,
// once under its id and once under its handle, as two objects that drift apart the moment
// either is modified - and would leave the handle copy behind when insert() or delete()
// invalidated the id.
global $formulizeCachedElementObjects; // ele_id => element object, or false when the lookup failed
global $formulizeCachedElementIdsByHandle; // ele_handle => ele_id
if (!isset($formulizeCachedElementObjects)) {
	$formulizeCachedElementObjects = array();
}
if (!isset($formulizeCachedElementIdsByHandle)) {
	$formulizeCachedElementIdsByHandle = array();
}

class formulizeElement extends FormulizeObject {

	var $isLinked;
	var $needsDataType;
	var $overrideDataType;
	var $hasData;
	var $name;
	var $adminCanMakeRequired;
	var $alwaysValidateInputs;
	var $canHaveMultipleValues; // whether the element is CURRENTLY configured to hold more than one value at a time. For some types this is fixed (checkboxes always can, radio buttons never can) and for others it depends on the element's settings, in which case the class implements setCanHaveMultipleValues (see _setElementProperties)
	var $adminCanAllowMultipleValues = false; // whether the webmaster can CHOOSE if this type holds more than one value at a time (listbox and autocomplete style lists can, dropdowns and radio buttons and checkboxes cannot - their multiplicity is inherent to the type). Not the same question as canHaveMultipleValues, which is the current state, not the capability
	var $adminCanAllowNewValues = false; // whether the webmaster can choose to let users save values that are not among the element's options (autocomplete style lists)
	var $isUserList = false; // whether the options for this element are the users of the site, instead of a list of options the webmaster defines
	var $hasMultipleOptions;
	var $isSystemElement; // only set to true in custom element class, if you want an element to exist in the form but be primarily managed by the system
	var $readOnly = false; // set to true in element classes whose values should never be written back — treated as $isDisabled throughout the rendering pipeline
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
			// NB: element types whose ele_value can hold PHP code write and delete their own code files in their
			// own classes: formulize{Text,Textarea,Number}Element, formulizeDerivedElement, and
			// formulize{FullWidthContent,CaptionedContent}Element. There is deliberately nothing here, so the
			// code file is only ever written by the class that knows which key of ele_value the code lives in.
			parent::setVar($key, $value, $not_gpc);
		}

		public function getVar($key, $format = 's') {
			$format = $key == "ele_value" ? "f" : $format;
			$value = parent::getVar($key, $format);
			// NB: the element types listed in setVar above read their own code-file contents back in their own
			// classes, where the file wins over the stored value so that code edited on disk is picked up.
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

	// the shared vocabulary for working out what refers to an element. This handler is the one that asks
	// the question - see findReferencesToElement() - and also answers it for its own table's columns.
	use formulizeElementReferenceScanTrait;

	var $db;
	// The four properties below are set by an element type's formatDataForList() and enforced by the
	// base formatDataForList(). See treatDataAsHtml() for the meaning of $dataIsHtml's three states.
	var $clickable;         // used in formatDataForList
	var $striphtml;         // DEPRECATED - superseded by $dataIsHtml, see treatDataAsHtml()
	var $length;            // used in formatDataForList
	var $dataIsHtml = null; // null = escape (unless legacy striphtml===false), false = escape, true = purify

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
	 * A method to remove values from the ele_value array prior to insert into the database,
	 * so we don't have any duplication of the stored values. File is the only source of truth.
	 * Overridden by element classes that store things in files.
	 *
	 * insert() calls this on the local copy of ele_value it is about to write, so the element object keeps
	 * the value it was given either way - only the copy going to the database is stripped.
	 *
	 * MUST be protected, not private: insert() calls it as $this->clearEleValueFileKeysForInsert(), and PHP
	 * resolves a private method non-virtually, so a private one here would silently run instead of the
	 * override on the element type's own handler.
	 *
	 * @param array|string $ele_value The ele_value about to be written, in whatever form cleanVars produced -
	 * a serialized string when the property was changed, the array itself when it was not
	 * @param object|null $element The element being written. Used to find the element type's handler, since
	 * the generic handler has no other way of knowing what kind of element it has been asked to write.
	 * @return array|string The ele_value to write, in the same form it arrived in. insert() passes it
	 * straight to quoteString(), so an array handed back where a string arrived would be written as "Array".
	 */
	protected function clearEleValueFileKeysForInsert($ele_value, $element = null) {
		// Called on the generic elements handler, hand off to the element type's own handler. insert() is
		// called on the generic handler nearly everywhere in the system (the element save pages, element
		// conversions, cloning, the ad hoc table forms) and only through upsertElementSchemaAndResources()
		// on the type's own handler, so without this the same element would store one thing when saved from
		// one place and something else when saved from another.
		//
		// Only the generic handler hands off: a type handler that does not override this inherits this copy,
		// and handing off from there would call itself forever.
		if(get_class($this) !== 'formulizeElementsHandler' OR !is_object($element)) {
			return $ele_value;
		}
		$ele_type = $element->getVar('ele_type');
		if($ele_type AND file_exists(XOOPS_ROOT_PATH."/modules/formulize/class/".$ele_type."Element.php")) {
			if($typeHandler = xoops_getmodulehandler($ele_type.'Element', 'formulize')
				AND get_class($typeHandler) !== 'formulizeElementsHandler') {
					return $typeHandler->clearEleValueFileKeysForInsert($ele_value, $element);
			}
		}
		return $ele_value;
	}

	/**
	 * Whether the code file that holds this element's ele_value content exists and has something in it.
	 *
	 * The overrides of clearEleValueFileKeysForInsert must only blank a key when this is true. Blanking it
	 * when the file is not there would destroy the only copy of the code: what insert() is handed is
	 * whatever cleanVars() produced, which is the stored value when ele_value was NOT the property that
	 * changed, so a save that never touched the content at all (the Display tab, a reorder) would empty it.
	 * Code can legitimately be in ele_value with no file yet - patch 000 wrote the files but left the
	 * database copies in place, cloneForm() copies element rows with raw SQL and no files, and an install
	 * whose modules/formulize/code/ did not survive a deploy is in the same position.
	 *
	 * All six types that keep code in files name it for the element's type and handle: the text, textarea
	 * and number classes derive that name from their class name (which is the element's type, since the
	 * handler builds the object from the type), derived hardcodes its own type, and the static content
	 * types read ele_type directly. It is the same name deleteAssociatedDataAndResources() cleans up.
	 *
	 * @param object|null $element The element being written
	 * @return bool True only if the code file is there to hold the content
	 */
	protected function codeFileHasContentForElement($element) {
		if(!is_object($element)) {
			return false; // no element, no way to name the file, so nothing can be shown to hold the content
		}
		$ele_type = $element->getVar('ele_type');
		$ele_handle = $element->getVar('ele_handle');
		if(!$ele_type OR !$ele_handle) {
			return false;
		}
		$path = XOOPS_ROOT_PATH.'/modules/formulize/code/'.$ele_type.'_'.$ele_handle.'.php';
		clearstatcache(true, $path); // setVar wrote this file moments ago, in this same request
		return (is_file($path) AND filesize($path) > 0);
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
	 * @param object|null $existingElement The element object being updated, if there is one.
	 *   The caller MUST only pass this if the operation is an update to an existing element.
	 *   If this is a new element created as part of this page load, then do not pass anything,
	 *   even if the new element object in fact exists in PHP memory presently.
	 *   Any property not present in $properties falls back to the $existingElement's current value
	 *   instead of a hardcoded default, so that incomplete $properties don't clobber values the
	 *   caller didn't intend to touch on existing elements.
	 * @return array The processed properties that are ready to set on the element object
	 */
	public function setupAndValidateElementProperties($properties, $existingElement = null) {

		$config_handler = xoops_gethandler('config');
		$formulizeConfig = $config_handler->getConfigsByCat(0, getFormulizeModId());

		$properties['fid'] = isset($properties['fid']) ? intval($properties['fid']) : ($existingElement ? intval($existingElement->getVar('fid')) : 0);
		if($properties['fid'] <= 0) {
			throw new Exception("You must use a valid form when working with an element");
		}
		formulizeHandler::validateElementType($properties['ele_type']);
		$properties['ele_caption'] = isset($properties['ele_caption']) ? $properties['ele_caption'] : ($existingElement ? $existingElement->getVar('ele_caption', 'n') : '');
		$properties['ele_caption'] = trim($properties['ele_caption']);
		if($properties['ele_caption'] == "") {
			throw new Exception("You must use a caption when working with an element");
		}

		if(isset($properties['ele_filtersettings']) AND is_array($properties['ele_filtersettings']) AND count($properties['ele_filtersettings']) > 0) {
			$elements = $properties['ele_filtersettings'][0];
			$operators = $properties['ele_filtersettings'][1];
			$types = $properties['ele_filtersettings'][3];
			foreach($elements as $i => $element) {
				if(!($conditionElementObject = _getElementObject($element))) {
					throw new Exception("You have specified an invalid element in display conditions");
				}
				if(!in_array($operators[$i], array('=', '!=', 'NOT', '<', '>', '<=', '>=', 'LIKE', 'NOT LIKE', 'IN'))) {
					throw new Exception("You have specified an invalid operator in display conditions");
				}
				if(!in_array($types[$i], array('all', 'oom'))) {
					throw new Exception("You have specified an invalid type in display conditions");
				}
				// store element references as IDs, the canonical format used by the admin UI and import/export (conversion is idempotent if an ID was passed)
				$properties['ele_filtersettings'][0][$i] = $conditionElementObject->getVar('ele_id');
			}
		} elseif(!isset($properties['ele_filtersettings']) AND $existingElement) {
			$properties['ele_filtersettings'] = $existingElement->getVar('ele_filtersettings');
		} else {
			$properties['ele_filtersettings'] = "";
		}

		$properties['ele_colhead'] = isset($properties['ele_colhead']) ? $properties['ele_colhead'] : ($existingElement ? $existingElement->getVar('ele_colhead', 'n') : '');
		$properties['ele_colhead'] = trim($properties['ele_colhead']);
		$properties['ele_handle'] = isset($properties['ele_handle']) ? $properties['ele_handle'] : ($existingElement ? $existingElement->getVar('ele_handle', 'n') : '');
		$properties['ele_handle'] = trim($properties['ele_handle']);
		$properties['ele_desc'] = isset($properties['ele_desc']) ? $properties['ele_desc'] : ($existingElement ? $existingElement->getVar('ele_desc', 'n') : '');
		$properties['ele_desc'] = trim($properties['ele_desc']);
		$properties['ele_required'] = isset($properties['ele_required']) ? $properties['ele_required'] : ($existingElement ? $existingElement->getVar('ele_required') : 0);
		$properties['ele_required'] = $properties['ele_required'] ? 1 : 0;
		$properties['ele_delim'] = isset($properties['ele_delim']) ? $properties['ele_delim'] : ($existingElement ? $existingElement->getVar('ele_delim', 'n') : $formulizeConfig['delimeter']);
		$properties['ele_uitextshow'] = isset($properties['ele_uitextshow']) ? $properties['ele_uitextshow'] : ($existingElement ? $existingElement->getVar('ele_uitextshow') : 0);
		$properties['ele_order'] = isset($properties['ele_order']) ? intval($properties['ele_order']) : ($existingElement ? $existingElement->getVar('ele_order') : figureOutOrder('bottom', fid: $properties['fid']));
		$properties['ele_display'] = isset($properties['ele_display']) ? $properties['ele_display'] : ($existingElement ? $existingElement->getVar('ele_display', 'n') : 1);
		$properties['ele_disabled'] = isset($properties['ele_disabled']) ? $properties['ele_disabled'] : ($existingElement ? $existingElement->getVar('ele_disabled', 'n') : 0);
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
	 * TODO: this returns an array of handles, but no metadata about where/what the dependencies are, it could be smarter about that and return more metadata, but the callers would have to be able to handle it
	 * TODO: when this is smarter, a lot of the removeElementFromEleValue methods could collapse into the same flow, because those methods exist purely because a more complete set of metadata needs to exist for the usage report and deletion report. The same metadata would also let convertEleValueDependenciesForImport/Export stop repeating the per-type key lists a third and fourth time.
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
	 * @param bool $bypassCache If true, will not use the cached element and will always hit the database
	 * @return mixed The element object, or false if not found
	 */
	function get($idOrHandle, $bypassCache = false){
		global $formulizeCachedElementObjects, $formulizeCachedElementIdsByHandle;
		// resolve whatever was passed to the id the cache is keyed by. A handle can only be
		// resolved once it has been seen; before that there is nothing to check and we query.
		$isId = (is_numeric($idOrHandle) AND $idOrHandle > 0);
		$elementId = $isId ? intval($idOrHandle)
			: (isset($formulizeCachedElementIdsByHandle[$idOrHandle]) ? $formulizeCachedElementIdsByHandle[$idOrHandle] : null);
		if(!$bypassCache && $elementId !== null && isset($formulizeCachedElementObjects[$elementId])) {
			return $formulizeCachedElementObjects[$elementId];
		}
		if ($isId) {
			$sql = 'SELECT * FROM '.formulize_TABLE.' WHERE ele_id='.$idOrHandle;
			if (!$result = $this->db->query($sql)) {
				$formulizeCachedElementObjects[$elementId] = false;
				return false;
			}
		} else {
			$sql = "SELECT * FROM ".formulize_TABLE." WHERE ele_handle='".formulize_db_escape($idOrHandle)."'";
			if (!$result = $this->db->query($sql)) {
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
			$this->cacheElementObject($element);
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

	/**
	 * Put an element object in the cache, under its id, with its handle indexed to it.
	 *
	 * The element must already have been through _setElementProperties, since everything that
	 * reads the cache expects those derived properties to be set.
	 *
	 * @param object $element The element to cache
	 * @return void
	 */
	private function cacheElementObject($element) {
		global $formulizeCachedElementObjects, $formulizeCachedElementIdsByHandle;
		if(!is_object($element) OR !$elementId = intval($element->getVar('ele_id'))) {
			return;
		}
		$formulizeCachedElementObjects[$elementId] = $element;
		// An element can change its handle, and a handle freed up that way can be taken by
		// another element, so any handle still pointing here is dropped before the current one
		// is recorded. Otherwise the old name would go on resolving to this element.
		foreach($formulizeCachedElementIdsByHandle as $thisHandle => $thisElementId) {
			if($thisElementId === $elementId) {
				unset($formulizeCachedElementIdsByHandle[$thisHandle]);
			}
		}
		if($elementHandle = $element->getVar('ele_handle', 'n')) {
			$formulizeCachedElementIdsByHandle[$elementHandle] = $elementId;
		}
	}

	/**
	 * Forget one cached element, by id, along with any handle that points to it.
	 * @param int $elementId The element to forget
	 * @return void
	 */
	private function clearCachedElement($elementId) {
		global $formulizeCachedElementObjects, $formulizeCachedElementIdsByHandle;
		$elementId = intval($elementId);
		unset($formulizeCachedElementObjects[$elementId]);
		foreach($formulizeCachedElementIdsByHandle as $thisHandle => $thisElementId) {
			if($thisElementId === $elementId) {
				unset($formulizeCachedElementIdsByHandle[$thisHandle]);
			}
		}
	}

	/**
	 * Forget every cached element.
	 *
	 * For when elements have been changed by SQL that went around the objects, leaving no list
	 * of which ones are now stale. Public, because that SQL is not all in this class: anything
	 * that writes to the formulize table directly has to say so, or the objects already loaded
	 * go on reporting what the rows used to say for the rest of the request.
	 *
	 * @return void
	 */
	public function clearElementCache() {
		global $formulizeCachedElementObjects, $formulizeCachedElementIdsByHandle;
		$formulizeCachedElementObjects = array();
		$formulizeCachedElementIdsByHandle = array();
	}

	public function insert(&$element, $force = false){
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
				$ele_value = $this->clearEleValueFileKeysForInsert($ele_value, $element);

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

		$element = $this->_setElementProperties($element);
		$this->cacheElementObject($element);

		return $ele_id;
	}

	/**
	 * Get default values for an element object
	 * Instantiate the handler for the element type if one exists, and get the default value from the getDefaultValue method
	 * The type handler method must be called because different types of elements have different ways of defining defaults
	 * This method is simply necessary for cases where the generic element handler has been invoked, instead of a type handler, and we don't want to make the user do the work below each time they need a default value
	 * @param int|string|object $elementIdentifier The element object or id or handle to get the default value for
	 * @param int $entry_id The entry id to get the default value for
	 * @return mixed The default value for the element, or false if none can be determined. The value is the database value, not necessarily human readable.
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
		// An existing element whose handle is the one already stored has nothing to validate. The stored
		// element is read bypassing the cache, because the cached object may be this very object, already
		// carrying the handle we are checking.
		if($ele_handle
			AND $elementId = intval($element->getVar('ele_id'))
			AND $storedElement = $this->get($elementId, bypassCache: true)
			AND $storedElement->getVar('ele_handle') === $ele_handle) {
			return $ele_handle;
		}
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
		$ele_handle = substr($ele_handle, 0, 59); // enforce max length of 64 characters... leave space for _f### or _x### if unique enforcement needs it
		$ele_handle = formulizeHandler::enforceUniqueElementHandles($ele_handle, $element->getVar('ele_id'), $element->getVar('fid'));
		$element->setVar('ele_handle', $ele_handle);
		return $ele_handle;
	}

	/**
	 * Renames all references to an element's handle when the handle is changed.
	 * Called both from the admin UI save path and from the schema migration patch.
	 * The element's field in the data table is not renamed here - that is formulizeFormsHandler::updateField().
	 *
	 * @param object $elementObject The element object with its new handle already set
	 * @param string $original_handle The handle before the rename, spelled exactly as it is written in the database.
	 *   Read it from the database rather than from getVar() when it may not be lowercase: assignVar() lowercases
	 *   every handle on loading, so getVar() can never give back an old spelling with capitals in it.
	 * @param bool $eleValueWasSavedWithThisChange Whether the save that changed the handle also set ele_value.
	 *   When it did, setVar has already written or deleted the element's code file under the new handle, and
	 *   the file under the old handle is a spent copy that is removed rather than renamed over it. Only the
	 *   caller knows this - see the code file loop below for why it cannot be worked out from the content.
	 * @return void
	 */
	function renameElementResources($elementObject, $original_handle, $eleValueWasSavedWithThisChange = false) {
		if($original_handle) {
			if(!$elementObject = _getElementObject($elementObject)) {
				throw new Exception("Invalid element object passed to renameElementResources");
			}
			$ele_handle = $elementObject->getVar('ele_handle');
			$fid = $elementObject->getVar('fid');
			if($ele_handle !== (string) $original_handle) {
				// whether a reference spelled differently from the old handle - capitals, hyphens - is still taken
				// to mean this element. See oldSpellingMatchesLoosely().
				$matchLoosely = $this->oldSpellingMatchesLoosely($elementObject, $original_handle);
				// the source of every linked list that takes its options from this element
				$this->renameLinkedListSources($fid, $original_handle, $ele_handle, $matchLoosely);
				// rewrite handle references inside the code files that genuinely hold them - $handle in derived
				// values and the three save/delete procedures, {handle} in static content and default values.
				// Files where a matching name is not a reference to this element are left alone; see
				// codeFilesThatReferenceHandles().
				$this->renameHandleInCodeFiles($original_handle, $ele_handle);
				// rename element code files (fullWidthContent, captionedContent, text, textarea, number, derived)
				// and purge stale derived-value cache files so they regenerate with the new handle
				//
				// The old file is only carried across when it is still the truth. It is not, when the same save
				// also set ele_value: this runs after insert(), which runs after the properties were set, so
				// setVar has already put the file for the NEW handle into whatever state the content calls for -
				// written when the content is code, deleted when it is not. Renaming the old file over that
				// would put back content the admin had just changed, or restore code they had just removed.
				// Every caller that sets ele_value sets the whole array (the admin Options tab posts it, and
				// the MCP tools seed it from the element's current ele_value before overwriting the keys they
				// were given), so ele_value being part of the save does mean setVar acted on the file.
				//
				// It cannot be decided by looking at the content instead. A handle-only rename reads exactly the
				// same as a cleared one at this moment: the stored value was blanked by an earlier save and the
				// file under the new handle does not exist yet, so the content reads back empty either way.
				//
				// A stale file is deleted rather than left behind. These names are per handle, so one sitting
				// in the code directory under a handle nothing uses is picked up in full by the next element
				// created with that handle - the file wins over the stored value on read - which is a worse
				// outcome than not having it.
				//
				// Whether a file exists is read from the directory listing, by exact name, rather than asked of
				// file_exists(). On a file system that ignores case - Windows, and macOS by default - a rename
				// that only changes case gives two names for one file, so file_exists() says the new one is
				// there when it is really the old one, and the old one would be deleted as spent, taking the
				// element's only copy of its code with it. The listing has the one name it really has.
				$currentType = $elementObject->getVar('ele_type');
				$codeDir = XOOPS_ROOT_PATH.'/modules/formulize/code/';
				$codeFilesOnDisk = (array) scandir($codeDir);
				foreach(self::elementCodeFileTypes() as $type) {
					$oldName = $type.'_'.$original_handle.'.php';
					$newName = $type.'_'.$ele_handle.'.php';
					if(!in_array($oldName, $codeFilesOnDisk, true)) { continue; }
					$newExists = in_array($newName, $codeFilesOnDisk, true);
					if(!$newExists AND strtolower($oldName) === strtolower($newName)) {
						// Only the case is changing, and there is one file. Where case is ignored it is the same
						// file under either name, already holding whatever setVar wrote on this save, so it is
						// never deleted - only renamed, in two steps, since a rename to the same name differing
						// only in case is not reliably carried out in one.
						$temporaryName = $codeDir.$newName.'.renaming';
						if(rename($codeDir.$oldName, $temporaryName)) {
							rename($temporaryName, $codeDir.$newName);
						}
						continue;
					}
					// This element's own type is the one setVar wrote on this save, so the new-handle file is
					// already right and the old one is spent. A file named for one of the other types is left
					// over from a type conversion, which setVar knows nothing about, so that one still moves.
					// A file already sitting under the new name is the live one either way - the backstop for
					// callers that do not know whether ele_value was part of the save.
					if(($eleValueWasSavedWithThisChange AND $type === $currentType) OR $newExists) {
						unlink($codeDir.$oldName);
					} else {
						rename($codeDir.$oldName, $codeDir.$newName);
					}
				}
				$cacheDir = XOOPS_ROOT_PATH . '/modules/formulize/cache/';
				if(is_dir($cacheDir)) {
					foreach((array)glob($cacheDir . 'Derived_value_formula_for_' . $original_handle . '*.php') as $cacheFile) {
						@unlink($cacheFile);
					}
				}
				// settings that name this element by its handle - screens, saved views, conditions, filters,
				// custom buttons - found by the same scan that cleans up after a deletion
				$this->renameElementInSettings($elementObject, $original_handle, $matchLoosely);
				// {handle} tokens wherever a person can write one: captions, help text, default values, static
				// content held in the database, the values of conditions, screen text
				$this->renameHandleTokens($original_handle, $ele_handle, $matchLoosely);
				// Everything above rewrote element rows with SQL that went around the element
				// objects: ele_value in every element that links to this one or embeds a {handle}
				// token, and ele_caption and ele_desc wherever that token appears. Any of those
				// already in the cache still holds the old text, and the passes are written as
				// LIKE searches rather than as a list of ids, so there is no way to name the ones
				// that changed. The whole cache goes instead. A handle rename is rare, and a cold
				// cache costs a re-read; a stale one costs a linked element pointing at a handle
				// that no longer exists.
				$this->clearElementCache();
			}
		}
	}

	/**
	 * Delete an element and all associated data, index, and screen resources.
	 *
	 * Runs pre-delete processing via the type-specific handler, removes the element row,
	 * drops the backing data table column (for elements with data), removes framework
	 * relationship connections, and strips the element from all screen pages.
	 *
	 * @param formulizeElement $elementObject The element to delete
	 * @param bool             $force         True to use queryF (bypass transaction handling)
	 * @return bool True if all cleanup steps succeeded, false if any failed
	 */
	/**
	 * The regular expression that matches a reference to an element handle in code.
	 *
	 * One definition, because there are two callers with opposite appetites and they had already drifted: the
	 * rename rewrites what it matches, and the usage report only reports it. The part that must not differ is
	 * what counts as a character continuing a name, which is PHP's own variable-name grammar,
	 * [a-zA-Z0-9_\x80-\xff]. The high bytes matter: they are what let a variable name hold accented and other
	 * non-ASCII characters, and leaving them out means $artifacts_year matches inside $artifacts_yeare-acute
	 * and a rename silently corrupts it. No /u modifier - that grammar is defined in bytes, and a multibyte
	 * character is a sequence of bytes all inside the excluded range anyway.
	 *
	 * @param string $handle The element handle.
	 * @param string $form Which reference syntax to match: 'variable' for $handle, 'token' for {handle},
	 *        or 'either' for both.
	 * @return string The regular expression.
	 */
	public static function handleReferencePattern($handle, $form = 'either') {
		$quoted = preg_quote($handle, '/');
		$continuesAName = '(?![A-Za-z0-9_\x80-\xff])';
		$variable = '\$'.$quoted.$continuesAName;
		$token = '\{'.$quoted.'\}';
		switch($form) {
			case 'variable': return '/'.$variable.'/';
			case 'token': return '/'.$token.'/';
			default: return '/('.$variable.'|'.$token.')/';
		}
	}

	/**
	 * The element types that can keep code in a file of their own, named <type>_<handle>.php in the code folder,
	 * which is renamed along with the element's handle.
	 * @return array The type names, as they begin the file names.
	 */
	public static function elementCodeFileTypes() {
		return array('fullWidthContent', 'captionedContent', 'text', 'textarea', 'number', 'derived');
	}

	/**
	 * Which code files reference an element handle in which syntax, and therefore which files a rename may
	 * rewrite. The prefix decides, and it is not a formality: only the three save/delete procedures have the
	 * entry's values exploded into variables named after the handles (see the generated wrappers in
	 * forms.php), so only there is $handle a reference to this element. In custom_edit_check, application code
	 * and custom button code nothing is injected, so a variable of that name is somebody's own and renaming it
	 * would be editing unrelated logic - and could collide with a variable already called by the new name,
	 * which the global uniqueness of handles does nothing to prevent.
	 *
	 * The content and default-value files are the mirror image: their references are {handle} tokens that
	 * Formulize substitutes, and their $value / $default variables are outputs rather than element references.
	 *
	 * @return array filename prefix => reference syntax used in that kind of file
	 */
	public static function codeFilesThatReferenceHandles() {
		return array(
			'derived_' => 'variable',
			'on_before_save_' => 'variable',
			'on_after_save_' => 'variable',
			'on_delete_' => 'variable',
			'fullWidthContent_' => 'token',
			'captionedContent_' => 'token',
			'text_' => 'token',
			'textarea_' => 'token',
		);
	}

	/**
	 * Rewrite references to an element handle inside the code files that genuinely contain them, when the
	 * handle changes. See codeFilesThatReferenceHandles() for which files those are and why the rest are
	 * deliberately left alone.
	 * @param string $originalHandle The handle as it was.
	 * @param string $newHandle The handle as it now is.
	 * @return void
	 */
	public function renameHandleInCodeFiles($originalHandle, $newHandle) {
		$codeDir = XOOPS_ROOT_PATH.'/modules/formulize/code/';
		$prefixes = self::codeFilesThatReferenceHandles();
		foreach((array) scandir($codeDir) as $file) {
			if(substr($file, -4) != '.php') {
				continue;
			}
			$syntax = false;
			foreach($prefixes as $prefix => $prefixSyntax) {
				if(strpos($file, $prefix) === 0) {
					$syntax = $prefixSyntax;
					break;
				}
			}
			if(!$syntax) {
				continue; // a kind of file where a name matching the handle is not a reference to it
			}
			$code = file_get_contents($codeDir.$file);
			if($code === false) {
				continue;
			}
			$replacement = ($syntax == 'variable') ? '\$'.$newHandle : '{'.$newHandle.'}';
			$newCode = preg_replace(self::handleReferencePattern($originalHandle, $syntax), $replacement, $code);
			if($newCode !== null AND $newCode != $code) {
				formulize_writeCodeToFile($file, $newCode);
			}
		}
	}

	/**
	 * Rewrite the settings that name an element by its handle, after the handle has changed.
	 *
	 * The places are found by findReferencesToElement(), the same scan the usage report and
	 * removeElementReferences() read, so a rename reaches every setting a deletion would clean up, and a setting
	 * added to that scan later is renamed as well as cleaned up without anyone coming back here.
	 *
	 * The scan is only used to find the rows. What is written is the stored value with the old handle swapped for
	 * the new one and nothing else changed - a rename does not change the shape of anything, so there is no need
	 * for each owner to know how to rename as well as how to remove. Wherever a setting stores an id instead,
	 * the row is found and left as it is, since a rename cannot invalidate an id.
	 *
	 * The swap is made on whole values: a string that is the old handle, an entry in a comma separated list that
	 * is, and the same with the hiddencolumn_ prefix saved views put on some columns. It is not made inside
	 * other text, so a handle mentioned in prose or code is left alone here. Because it is a swap of whole values
	 * rather than of the positions each owner knows about, a value in one of those rows that is exactly the old
	 * handle but means something else - a condition comparing against the literal text of the handle, say - is
	 * changed as well. Handles are long and distinctive enough for that to be vanishingly rare.
	 *
	 * Handles that are numbers are legacy handles that equal their element's id. They are not swapped generally,
	 * because that would turn references by id into references by handle, in settings that may only hold ids.
	 * Those settings go on working after the rename anyway, because they resolve what they hold through the
	 * element handler, where the number is the element's id. Saved views do not - see
	 * renameNumericHandleInSavedViews().
	 *
	 * @param object $elementObject The element, with its new handle already set.
	 * @param string $originalHandle The handle before the rename, as it is written in the database.
	 * @param bool $matchLoosely Whether other spellings of the old handle are swapped too - see
	 *   oldSpellingMatchesLoosely().
	 * @return void
	 */
	protected function renameElementInSettings($elementObject, $originalHandle, $matchLoosely = true) {
		$newHandle = $elementObject->getVar('ele_handle');
		if(is_numeric($originalHandle)) {
			$this->renameNumericHandleInSavedViews($originalHandle, $newHandle);
			return;
		}
		// The scan looks for the element by its handle, and the settings still say the old one, so it is asked
		// about a copy of the element under that name. It recognises every spelling a handle has had (see
		// canonicalHandle()), so capitals and hyphens left over from before are found along with the old name.
		// The old handle is written straight into the copy, because assignVar() and setVar() would sanitize it,
		// and a legacy handle with a character sanitizing removes would then be looked for under a name its
		// references do not have. A whole new array goes in, so nothing is shared with the original element.
		$elementAsItWas = clone $elementObject;
		$elementAsItWas->vars['ele_handle'] = array_merge($elementObject->vars['ele_handle'], array('value' => (string) $originalHandle));
		foreach($this->findReferencesToElement($elementAsItWas) as $reference) {
			if(!$reference['table']) {
				continue; // reported only, because it is cleaned up somewhere else - see the scan that found it
			}
			$columns = array_keys($reference['updates']);
			// An element's own type decides what its ele_value means, and a type that cannot rewrite its own
			// references reports them without proposing an update. So for another element, every column that
			// can name an element is read, not only the ones a deletion would change, and whether or not the
			// scan proposed any change at all.
			if($reference['table'] == 'formulize') {
				$columns = array_values(array_unique(array_merge($columns, array('ele_value', 'ele_filtersettings', 'ele_disabledconditions', 'ele_dynamicdefault_conditions'))));
			}
			if(!$columns) {
				continue;
			}
			$this->rewriteStoredSettings($reference['table'], $reference['key_column'], $reference['key_value'], $columns,
				fn($text) => self::renameHandleInSettingText($text, $originalHandle, $newHandle, $matchLoosely));
		}
	}

	/**
	 * Rename a numeric handle in saved views. Their columns and the column they sort by are compared with the
	 * current handles as text, rather than resolved through the element handler, so a view still naming the
	 * number after the element is given a readable handle would quietly lose that column. Only those two
	 * settings, and only where an entry is exactly the number - a number is far too common to swap anywhere
	 * else, such as in a saved search.
	 * @param string $originalHandle The numeric handle.
	 * @param string $newHandle The handle as it is now.
	 * @return void
	 */
	protected function renameNumericHandleInSavedViews($originalHandle, $newHandle) {
		$sql = "SELECT sv_id FROM ".$this->db->prefix('formulize_saved_views')."
			WHERE sv_oldcols LIKE ".$this->db->quoteString('%'.$originalHandle.'%')."
			OR sv_sort = ".$this->db->quoteString($originalHandle);
		if(!$result = $this->db->query($sql)) {
			return;
		}
		while($row = $this->db->fetchArray($result)) {
			$this->rewriteStoredSettings('formulize_saved_views', 'sv_id', $row['sv_id'], array('sv_oldcols', 'sv_sort'),
				fn($text) => self::renameHandleInSettingText($text, $originalHandle, $newHandle, false));
		}
	}

	/**
	 * Whether references written with a different spelling of an element's old handle - other capitals, hyphens
	 * where there are now underscores - are taken to mean the element when its handle changes.
	 *
	 * They are, unless another element is called by that other spelling. That happens when a handle could not
	 * simply lose its capitals or hyphens because the result was already taken, so the element was given a
	 * suffixed handle instead: my-handle becoming my_handle_f12 because another element is my_handle. Matched
	 * loosely, every reference to that other element would be rewritten as well. Only the exact old spelling is
	 * swapped then.
	 *
	 * @param object $elementObject The element being renamed.
	 * @param string $originalHandle The handle before the rename, as it is written in the database.
	 * @return bool
	 */
	protected function oldSpellingMatchesLoosely($elementObject, $originalHandle) {
		// compared through the collation, so a not yet lowercased Foo_Bar counts as being called foo_bar
		$sql = "SELECT ele_id FROM ".$this->db->prefix('formulize')."
			WHERE ele_handle = ".$this->db->quoteString(self::canonicalHandle($originalHandle))."
			AND ele_id != ".intval($elementObject->getVar('ele_id'));
		if(!$result = $this->db->query($sql)) {
			return false; // exact matching is never wrong, only incomplete
		}
		return $this->db->getRowsNum($result) == 0;
	}

	/**
	 * Does a stored spelling of a handle stand for the old handle being renamed?
	 * @param string $spelling The spelling found.
	 * @param string $originalHandle The handle as it was.
	 * @param bool $matchLoosely Whether any spelling with the same canonical form counts, or only the exact one.
	 * @return bool
	 */
	protected static function spellingMatches($spelling, $originalHandle, $matchLoosely) {
		if($matchLoosely) {
			return self::canonicalHandle($spelling) === self::canonicalHandle($originalHandle);
		}
		return (string) $spelling === (string) $originalHandle;
	}

	/**
	 * Point linked lists that take their options from an element at its new handle. The source is stored in
	 * the linked list's ele_value as "fid#*=:*handle", which no other setting looks like, so every string of
	 * that shape in every element's ele_value is checked.
	 * @param int $fid The form the renamed element belongs to.
	 * @param string $originalHandle The handle as it was.
	 * @param string $newHandle The handle as it is now.
	 * @param bool $matchLoosely Whether other spellings of the old handle are swapped too.
	 * @return void
	 */
	protected function renameLinkedListSources($fid, $originalHandle, $newHandle, $matchLoosely) {
		$fid = intval($fid);
		$rewrite = function($text) use ($fid, $originalHandle, $newHandle, $matchLoosely) {
			if(preg_match('/^'.$fid.'#\*=:\*(.*)$/s', $text, $source) AND self::spellingMatches(trim($source[1]), $originalHandle, $matchLoosely)) {
				return $fid.'#*=:*'.$newHandle;
			}
			return $text;
		};
		// a coarse filter only - which strings really are this element's source is decided in PHP
		$sql = "SELECT ele_id FROM ".$this->db->prefix('formulize')." WHERE ele_value LIKE ".$this->db->quoteString('%'.$fid.'#*=:*%');
		if(!$result = $this->db->query($sql)) {
			return;
		}
		while($row = $this->db->fetchArray($result)) {
			$this->rewriteStoredSettings('formulize', 'ele_id', $row['ele_id'], array('ele_value'), $rewrite);
		}
	}

	/**
	 * Swap a handle for another in a stored setting's text, where the text is the handle, or is a comma separated
	 * list with the handle as one of its entries.
	 * @param string $text The text, which is one string out of a stored value if the value is serialized.
	 * @param string $originalHandle The handle as it was.
	 * @param string $newHandle The handle as it is now.
	 * @param bool $matchLoosely Whether any spelling the old handle has had is swapped (see canonicalHandle()), or
	 *   only the exact one.
	 * @return string
	 */
	protected static function renameHandleInSettingText($text, $originalHandle, $newHandle, $matchLoosely = true) {
		$hiddenColumnPrefix = 'hiddencolumn_'; // formulizeSavedViewsHandler::HIDDEN_COLUMN_PREFIX, which may not be loaded
		$entries = explode(',', $text);
		foreach($entries as $i => $entry) {
			$prefix = strpos($entry, $hiddenColumnPrefix) === 0 ? $hiddenColumnPrefix : '';
			$bare = substr($entry, strlen($prefix));
			if($bare !== $newHandle AND self::spellingMatches($bare, $originalHandle, $matchLoosely)) {
				$entries[$i] = $prefix.$newHandle;
			}
		}
		return implode(',', $entries);
	}

	/**
	 * Rewrite {handle} tokens after a handle has changed, wherever a person can write one: captions, help text,
	 * default values, static content held in the database, the values conditions compare against, screen text.
	 *
	 * Rather than keep a list of those places, every text column of every table Formulize declares is searched
	 * (see configurationTables()). The token syntax is distinctive enough that nothing else looks like one, and
	 * the declared tables are only Formulize's own configuration, never the data tables behind the forms.
	 *
	 * Code that lives in files rather than in the database is renameHandleInCodeFiles()'s job.
	 *
	 * @param string $originalHandle The handle as it was.
	 * @param string $newHandle The handle as it is now.
	 * @param bool $matchLoosely Whether tokens with other spellings of the old handle are swapped too.
	 * @return void
	 */
	protected function renameHandleTokens($originalHandle, $newHandle, $matchLoosely = true) {
		$rewrite = fn($text) => self::renameHandleTokensInText($text, $originalHandle, $newHandle, $matchLoosely);
		// A coarse filter only, to read just the rows that may hold a token: the collation ignores case, and the
		// underscores in a handle are wildcards that match hyphens, so every spelling the handle has had is
		// caught. Which tokens really are this handle is decided in PHP, one token at a time.
		$likeToken = $this->db->quoteString('%{'.$originalHandle.'}%');
		foreach(self::configurationTables() as $table) {
			list($keyColumn, $textColumns) = $this->tableKeyAndTextColumns($table);
			if(!$keyColumn OR !$textColumns) {
				continue;
			}
			$where = implode(' OR ', array_map(fn($column) => "`$column` LIKE $likeToken", $textColumns));
			if(!$result = $this->db->query("SELECT `$keyColumn` FROM ".$this->db->prefix($table)." WHERE $where")) {
				continue;
			}
			while($row = $this->db->fetchArray($result)) {
				$this->rewriteStoredSettings($table, $keyColumn, $row[$keyColumn], $textColumns, $rewrite);
			}
		}
	}

	/**
	 * Swap the {handle} tokens for one handle, in any spelling it has had, for tokens of another.
	 * @param string $text The text.
	 * @param string $originalHandle The handle as it was.
	 * @param string $newHandle The handle as it is now.
	 * @param bool $matchLoosely Whether tokens with other spellings of the old handle are swapped too, or only the
	 *   exact one.
	 * @return string
	 */
	protected static function renameHandleTokensInText($text, $originalHandle, $newHandle, $matchLoosely = true) {
		// the exact spelling first, because a legacy handle can hold a character the token pattern does not
		// accept, a period or a space, and a token of it would otherwise be passed over
		$text = str_replace('{'.$originalHandle.'}', '{'.$newHandle.'}', $text);
		if(!$matchLoosely) {
			return $text;
		}
		return preg_replace_callback(self::HANDLE_TOKEN_PATTERN, function($match) use ($originalHandle, $newHandle) {
			return self::canonicalHandle($match[1]) === self::canonicalHandle($originalHandle) ? '{'.$newHandle.'}' : $match[0];
		}, $text);
	}

	/**
	 * A {handle} token, in any spelling a handle has had: capitals and hyphens included, and the high bytes that
	 * let a name hold accented characters (see handleReferencePattern()).
	 */
	const HANDLE_TOKEN_PATTERN = '/\{([A-Za-z0-9_\-\x80-\xff]+)\}/';

	/**
	 * Every spelling of a current element handle, other than the handle itself, that is still written somewhere
	 * in Formulize's configuration - as a {token}, as a whole stored value, or as an entry in a comma separated
	 * list. These are what is left behind when a handle loses its capitals or hyphens without every reference to
	 * it being rewritten, which is what happened when handles were lowercased in 2025-10 and when hyphens were
	 * removed in 2026-07.
	 *
	 * A value only counts if it has a capital or a hyphen, and has an underscore or a hyphen - the shape of a
	 * handle made from a form handle and a caption. A single word does not count, because "Name" in a caption
	 * or a list of options is far more likely to be a word than an old spelling of an element called "name".
	 *
	 * Reads every text column of every configuration table once, so this is for an update patch, not a page load.
	 *
	 * @return array current handle => list of the other spellings found for it
	 */
	public function findStaleHandleSpellings() {
		$handles = array(); // canonical form => the handle
		if($res = $this->db->query("SELECT ele_handle FROM ".$this->db->prefix('formulize'))) {
			while($row = $this->db->fetchArray($res)) {
				if(!is_numeric($row['ele_handle'])) {
					$handles[self::canonicalHandle($row['ele_handle'])] = $row['ele_handle'];
				}
			}
		}
		$found = array();
		$collect = function($text) use (&$found, $handles) {
			$candidates = array();
			if(preg_match_all(self::HANDLE_TOKEN_PATTERN, $text, $tokens)) {
				$candidates = $tokens[1];
			}
			foreach(explode(',', $text) as $entry) {
				$entry = strpos($entry, 'hiddencolumn_') === 0 ? substr($entry, strlen('hiddencolumn_')) : $entry;
				// the source of a linked list, "fid#*=:*handle"
				if(preg_match('/^\d+#\*=:\*(.+)$/s', $entry, $source)) {
					$entry = trim($source[1]);
				}
				$candidates[] = $entry;
			}
			foreach($candidates as $candidate) {
				$canonical = self::canonicalHandle($candidate);
				if(isset($handles[$canonical]) AND $candidate !== $handles[$canonical]
					AND preg_match('/[A-Z\-]/', $candidate) AND preg_match('/[_\-]/', $candidate)) {
					$found[$handles[$canonical]][$candidate] = true;
				}
			}
			return $text; // nothing is changed, this only looks
		};
		foreach(self::configurationTables() as $table) {
			list($keyColumn, $textColumns) = $this->tableKeyAndTextColumns($table);
			if(!$keyColumn OR !$textColumns) {
				continue;
			}
			$columnList = implode(', ', array_map(fn($column) => "`$column`", $textColumns));
			if(!$result = $this->db->query("SELECT $columnList FROM ".$this->db->prefix($table))) {
				continue;
			}
			while($row = $this->db->fetchArray($result)) {
				foreach($textColumns as $column) {
					self::rewriteStringsInStoredValue($row[$column], $collect);
				}
			}
		}
		return array_map('array_keys', $found);
	}

	/**
	 * Where handles are still written in a way a rename does not rewrite: a person's own code and markup -
	 * screen templates, custom code files, code and text stored in the database. These are reported for a person
	 * to look at, because a name in someone's code can mean something a rename cannot know about.
	 *
	 * A mention is the spelling as a whole word, with the same idea of a word as handleReferencePattern(), and in
	 * that exact case - an old spelling that differs from the current handle only in case would otherwise be
	 * found everywhere the current handle is used correctly.
	 *
	 * @param array $spellings The spellings to look for.
	 * @return array Descriptions of where each was found, for a person to read.
	 */
	public function findMentionsOfHandles($spellings) {
		$spellings = array_values(array_unique(array_filter($spellings, 'strlen')));
		if(!$spellings) {
			return array();
		}
		$pattern = '/(?<![A-Za-z0-9_\x80-\xff])('.implode('|', array_map(fn($spelling) => preg_quote($spelling, '/'), $spellings)).')(?![A-Za-z0-9_\x80-\xff])/';
		$mentions = array();

		$folders = array(XOOPS_ROOT_PATH.'/modules/formulize/templates/screens', XOOPS_ROOT_PATH.'/modules/formulize/code');
		foreach($folders as $folder) {
			if(!is_dir($folder)) {
				continue;
			}
			foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folder, FilesystemIterator::SKIP_DOTS)) as $file) {
				if(!$file->isFile() OR !in_array(strtolower($file->getExtension()), array('php', 'html', 'js', 'css'))) {
					continue;
				}
				$contents = file_get_contents($file->getPathname());
				if($contents !== false AND preg_match_all($pattern, $contents, $found)) {
					$mentions[] = 'the file '.substr($file->getPathname(), strlen(XOOPS_ROOT_PATH)).' mentions '.implode(', ', array_unique($found[1]));
				}
			}
		}

		foreach(self::configurationTables() as $table) {
			list($keyColumn, $textColumns) = $this->tableKeyAndTextColumns($table);
			if(!$keyColumn OR !$textColumns) {
				continue;
			}
			$likes = array();
			foreach($textColumns as $column) {
				foreach($spellings as $spelling) {
					$likes[] = "`$column` LIKE BINARY ".$this->db->quoteString('%'.$spelling.'%');
				}
			}
			$columnList = implode(', ', array_map(fn($column) => "`$column`", $textColumns));
			if(!$result = $this->db->query("SELECT `$keyColumn`, $columnList FROM ".$this->db->prefix($table)." WHERE ".implode(' OR ', $likes))) {
				continue;
			}
			while($row = $this->db->fetchArray($result)) {
				foreach($textColumns as $column) {
					if(is_string($row[$column]) AND preg_match_all($pattern, $row[$column], $found)) {
						$mentions[] = "$column in $table, where $keyColumn is ".intval($row[$keyColumn]).', mentions '.implode(', ', array_unique($found[1]));
					}
				}
			}
		}
		return $mentions;
	}

	/**
	 * Read some columns of one row, rewrite every string in them, and save the columns that changed.
	 * @param string $table The unprefixed table name.
	 * @param string $keyColumn The table's primary key column, which holds an integer.
	 * @param int $keyValue The row's primary key.
	 * @param array $columns The columns to rewrite.
	 * @param callable $rewrite Given one string, returns it rewritten - see rewriteStringsInStoredValue().
	 * @return void
	 */
	protected function rewriteStoredSettings($table, $keyColumn, $keyValue, $columns, $rewrite) {
		$columnList = implode(', ', array_map(fn($column) => "`$column`", $columns));
		$where = " WHERE `$keyColumn` = ".intval($keyValue);
		if(!$result = $this->db->query("SELECT $columnList FROM ".$this->db->prefix($table).$where)
			OR !$row = $this->db->fetchArray($result)) {
			return;
		}
		$sets = array();
		foreach($columns as $column) {
			$rewritten = self::rewriteStringsInStoredValue($row[$column], $rewrite);
			if($rewritten !== $row[$column]) {
				$sets[] = "`$column` = ".$this->db->quoteString($rewritten);
			}
		}
		if($sets) {
			// queryF, the same as the rename has always used, because a rename can be made from a GET request (an
			// update patch run from a link), which query() refuses to write from
			$this->db->queryF("UPDATE ".$this->db->prefix($table)." SET ".implode(', ', $sets).$where);
		}
	}

	/**
	 * Apply a change to every string in a stored value, whether the value is plain text or serialized.
	 *
	 * A serialized value is changed in place, string by string, with each string's length recomputed - rather
	 * than unserialized, changed and serialized again. That way a value holding objects, as calendar datasets do,
	 * can be changed without its classes being loaded, and everything that is not a string comes back exactly as
	 * it was. If the result would not unserialize, the value is left as it was.
	 *
	 * @param mixed $value The stored value. Anything other than a non-empty string is returned as it is.
	 * @param callable $rewrite Given one string, returns it rewritten.
	 * @return mixed
	 */
	public static function rewriteStringsInStoredValue($value, $rewrite) {
		if(!is_string($value) OR $value === '') {
			return $value;
		}
		if(!preg_match('/^(a|O|s):\d+:/', $value) OR @unserialize($value, array('allowed_classes' => false)) === false) {
			return $rewrite($value);
		}
		$rewritten = '';
		$position = 0;
		// Each string is s:<length in bytes>:"<that many bytes>"; and is read by its length, so a quote or a
		// semicolon inside one is never taken for the end of it, and searching resumes after the whole string.
		while(preg_match('/s:(\d+):"/', $value, $match, PREG_OFFSET_CAPTURE, $position)) {
			$tokenStart = $match[0][1];
			$contentStart = $tokenStart + strlen($match[0][0]);
			$content = substr($value, $contentStart, intval($match[1][0]));
			$newContent = $rewrite($content);
			$rewritten .= substr($value, $position, $tokenStart - $position).'s:'.strlen($newContent).':"'.$newContent;
			$position = $contentStart + strlen($content);
		}
		$rewritten .= substr($value, $position);
		return @unserialize($rewritten, array('allowed_classes' => false)) === false ? $value : $rewritten;
	}

	/**
	 * The tables that hold Formulize's configuration: every table the module declares in xoops_version.php,
	 * except the ones that hold records rather than settings. The data tables behind each form are not
	 * declared, so they are never among these.
	 * @return array Unprefixed table names.
	 */
	protected static function configurationTables() {
		static $tables = null;
		if($tables === null) {
			$module_handler = xoops_gethandler('module');
			$formulizeModule = $module_handler->getByDirname('formulize');
			$declared = $formulizeModule ? (array) $formulizeModule->getInfo('tables') : array();
			// what happened and to whom, who holds which key or code, who owns which entry, and what people typed
			// as their "Other" answer. A handle written into one of these is a record or an answer, not a
			// setting that refers to an element.
			$records = array('formulize_entry_owner_groups', 'formulize_other', 'formulize_valid_imports', 'formulize_procedure_logs',
				'formulize_procedure_logs_params', 'formulize_deletion_logs', 'formulize_apikeys', 'formulize_tokens',
				'formulize_digest_data', 'formulize_passcodes', 'tfa_codes');
			$tables = array_values(array_diff($declared, $records));
		}
		return $tables;
	}

	/**
	 * A table's primary key, if it is a single integer column, and its text columns.
	 * @param string $table The unprefixed table name.
	 * @return array array(key column or false, list of text columns)
	 */
	protected function tableKeyAndTextColumns($table) {
		static $cache = array();
		if(!isset($cache[$table])) {
			$keyColumns = array();
			$textColumns = array();
			if($res = $this->db->query("SHOW COLUMNS FROM `".$this->db->prefix($table)."`")) {
				while($row = $this->db->fetchArray($res)) {
					if($row['Key'] == 'PRI') {
						$keyColumns[] = preg_match('/int/i', $row['Type']) ? $row['Field'] : false;
					} elseif(preg_match('/char|text/i', $row['Type'])) {
						$textColumns[] = $row['Field'];
					}
				}
			}
			$cache[$table] = array(count($keyColumns) === 1 ? $keyColumns[0] : false, $textColumns);
		}
		return $cache[$table];
	}

	/**
	 * Remove every stored reference to an element from the settings that hold one, so that deleting an element
	 * does not leave configuration pointing at something that no longer exists.
	 *
	 * The places are found by findReferencesToElement(), which the usage report reads too, so what the report
	 * promises to clean up and what is actually cleaned up cannot drift apart.
	 *
	 * This covers the references that are DATA - columns, conditions, effects, sort settings. It cannot cover
	 * the two that are CODE: a derived value formula naming $handle, and a custom code file doing the same.
	 * Rewriting someone's PHP to remove a term is not a transformation this can make safely, so those are
	 * reported instead and are a decision for a person.
	 *
	 * Called from delete(), so it applies to every route an element is deleted through, not only the tools.
	 *
	 * @param object $elementObject The element being deleted.
	 * @return void
	 */
	public function removeElementReferences($elementObject) {
		foreach($this->findReferencesToElement($elementObject) as $reference) {
			if(!$reference['table'] OR !$reference['updates']) {
				continue; // reported so a person knows, but cleaned up elsewhere - see the scan that found it
			}
			$sets = array();
			foreach($reference['updates'] as $column => $value) {
				$sets[] = "`$column` = ".$this->db->quoteString((string) $value);
			}
			// query() rather than queryF(), matching the DELETE in delete() that this accompanies - whatever
			// stops one from running should stop the other, or an element survives with its references gone
			$this->db->query("UPDATE ".$this->db->prefix($reference['table'])." SET ".implode(', ', $sets)
				." WHERE `".$reference['key_column']."` = ".$reference['key_value']);
		}
	}

	/**
	 * Every stored reference to an element, gathered by asking whoever owns the setting.
	 *
	 * This exists so that the usage report and the cleanup cannot disagree. A report that promises to tidy
	 * something up and a cleanup that misses it is worse than either problem alone, and the only way to be
	 * sure they match is for both to be the same piece of work: the report groups these by section and prints
	 * the descriptions, and removeElementReferences() runs the updates.
	 *
	 * The work itself is not done here. Each class that stores a reference answers for its own storage - a
	 * list screen knows how it keeps its columns, a calendar knows its datasets are serialized objects, a
	 * saved view knows its searches run parallel to its columns. That is the same arrangement the
	 * configuration-as-code system uses, where convertDependenciesForExport() handles what every element has
	 * and hands ele_value to the element's own type handler. The point of it is that when one of those
	 * changes how it stores something, the code that has to change with it is in the same file.
	 *
	 * Not covered here, deliberately:
	 * - the element's own column in the form's data table, and the form's principal identifier, which delete()
	 *   handles directly and the report accounts for on its own
	 * - anything a person wrote in code, which cannot be rewritten safely - see codeReferencesToElement()
	 *
	 * @param object $elementObject The element being asked about.
	 * @return array The references, in report order.
	 */
	private function findReferencesToElement($elementObject) {
		$elementId = intval($elementObject->getVar('ele_id'));
		if(!$elementId OR !strlen($elementObject->getVar('ele_handle'))) {
			return array();
		}

		include_once XOOPS_ROOT_PATH.'/modules/formulize/class/savedViews.php';
		include_once XOOPS_ROOT_PATH.'/modules/formulize/class/frameworks.php';
		$references = array();

		// Each screen type, asked about its own table. Optional: a type that stores no element references
		// simply does not implement it, which is how template screens get skipped without being listed here.
		foreach($this->screenTypeHandlers() as $typeHandler) {
			if(method_exists($typeHandler, 'scanForElementReferences')) {
				$references = array_merge($references, $typeHandler->scanForElementReferences($elementObject));
			}
		}

		// The columns every screen has whatever its type, asked once of the base handler, under a different
		// name on purpose: every type handler extends it, so a shared name would be inherited by all of them
		// and its one row reported once per type.
		$screen_handler = xoops_getmodulehandler('screen', 'formulize');
		$references = array_merge($references, $screen_handler->scanScreenTableForElementReferences($elementObject));

		// Everyone else who owns somewhere an element can be referenced from, in the order their answers
		// should be reported - the usage report groups its sections by the order they first appear, so this
		// is what a person ends up reading top to bottom. $this is in the list because this handler owns the
		// elements table like any other owner owns its own.
		foreach(array(
			$this,
			xoops_getmodulehandler('savedViews', 'formulize'),
			xoops_getmodulehandler('forms', 'formulize'),
			xoops_getmodulehandler('frameworks', 'formulize')
		) as $owner) {
			$references = array_merge($references, $owner->scanForElementReferences($elementObject));
		}

		return $references;
	}

	/**
	 * Every screen type there is, read off the class folder rather than from a list kept here.
	 *
	 * A screen type is a class file called <type>Screen.php, and its handler is formulize<Type>ScreenHandler
	 * - the same convention initialize.php dispatches on when it renders one, and the same one
	 * xoops_getmodulehandler() resolves. Reading the folder means a screen type added later is asked about
	 * because it exists, not because somebody remembered to come here: the list in admin/application.php has
	 * never heard of calendar or template screens, which is exactly the rot this avoids.
	 *
	 * Asked for optionally, so a file whose handler is named off convention is skipped rather than being a
	 * fatal error - xoops_getmodulehandler() raises E_USER_ERROR otherwise, and this now asks about files
	 * nobody has vetted. Sorted so the report reads the same way twice running.
	 *
	 * The caller checks method_exists() before asking any of them anything, so a type that stores no element
	 * references - template screens today - needs no entry anywhere and no code of its own.
	 *
	 * @return array The handlers.
	 */
	private function screenTypeHandlers() {
		$handlers = array();
		$screenFiles = (array) glob(XOOPS_ROOT_PATH.'/modules/formulize/class/*Screen.php');
		sort($screenFiles);
		foreach($screenFiles as $screenFile) {
			$type = substr(basename($screenFile), 0, -strlen('Screen.php'));
			// screen.php holds the base class rather than a type. It does not match the pattern above on a
			// case sensitive filesystem, but it costs nothing to be sure, and a stray file called Screen.php
			// would otherwise be asked for as a handler with no name at all.
			if($type === '' OR strtolower($type) === 'screen') {
				continue;
			}
			if($typeHandler = xoops_getmodulehandler($type.'Screen', 'formulize', true)) {
				$handlers[] = $typeHandler;
			}
		}
		return $handlers;
	}

	/**
	 * Where does one element refer to another, and what do those settings look like once it is gone? Whether
	 * an element is shown, whether it is read only, where its dynamic default value is read from, and
	 * whatever its own type keeps in ele_value.
	 *
	 * This handler answers for the elements table the same way a list screen handler answers for its own -
	 * the columns read here are the ones initVar() declares in formulizeElement at the top of this file. It
	 * happens to also be the class that asks everybody else the question, in findReferencesToElement(), but
	 * that is a separate job that this one knows nothing about.
	 *
	 * @param object $elementObject The element being asked about.
	 * @return array The references found.
	 */
	public function scanForElementReferences($elementObject) {
		list($elementId, $handle) = $this->elementIdAndHandle($elementObject);
		$references = array();
		$sql = "SELECT ele_id, ele_handle, ele_type, id_form, ele_value, ele_filtersettings, ele_disabledconditions,
			ele_dynamicdefault_source, ele_dynamicdefault_conditions
			FROM ".formulize_TABLE." WHERE ele_id != ".intval($elementId);
		if(!$result = $this->db->query($sql)) {
			return $references;
		}
		while($row = $this->db->fetchArray($result)) {
			$updates = array();
			$usedAs = array();

			// Does this element refer to the one being deleted at all? Asked of getElementDependencies(),
			// which is the system's existing answer to that question - the configuration-as-code export uses
			// it to work out what an element needs before it can be created, and it already knows every
			// property an element can hold a reference in, including handing ele_value to the element's own
			// type. Asking it rather than working it out again means there is one definition of what counts
			// as a reference, and a property added to it later is picked up here without anyone remembering
			// to. It is given only the settings this method can actually rewrite: a handle written into a
			// caption or a description is a reference too, but it is prose, and it is reported by
			// codeReferencesToElement() as something a person has to deal with rather than cleaned up here.
			// compared in canonical form, the same as referencePointsAtElement(), so a dependency still written
			// with capitals or hyphens from before handles lost them is recognised
			if(!in_array(self::canonicalHandle($handle), array_map(fn($dependency) => self::canonicalHandle($dependency), $this->elementDependencyHandles($row)), true)) {
				continue;
			}

			foreach(array(
				'ele_filtersettings' => 'in the conditions that decide whether it is shown',
				'ele_disabledconditions' => 'in the conditions that decide whether it is read only',
				'ele_dynamicdefault_conditions' => 'in the conditions on its dynamic default value'
			) as $column => $description) {
				$newConditions = $this->removeElementFromConditions(@unserialize((string) $row[$column]), $elementId, $handle);
				if($newConditions !== false) {
					$updates[$column] = serialize($newConditions);
					$usedAs[] = $description;
				}
			}

			// a dynamic default reads its value out of another element, so once that element is gone there is
			// nothing left to read. The conditions that went with it go too, which is what the element editor
			// does when the source is set back to none.
			if(intval($row['ele_dynamicdefault_source']) === intval($elementId)) {
				$updates['ele_dynamicdefault_source'] = 0;
				$updates['ele_dynamicdefault_conditions'] = serialize(array());
				$usedAs[] = 'as the source of its dynamic default value';
			}

			// ele_value holds whatever the element's own type wants it to, so the type is the only thing that
			// can rewrite it - a linked list keeps element ids in seven different keys, a subform in six, a
			// grid in one, each with its own idea of what the setting looks like once the reference is gone.
			// Discovered with method_exists(), the same arrangement getEleValueDependencies() uses for the
			// configuration-as-code system. A type that cannot rewrite itself simply does not implement it,
			// and its reference is reported by the fallback below instead of being cleaned up.
			$eleValueResult = $this->removeElementFromEleValueForType($row, $elementId, $handle);
			if($eleValueResult !== false) {
				if($eleValueResult['ele_value'] !== null) {
					$updates['ele_value'] = serialize($eleValueResult['ele_value']);
				}
				$usedAs = array_merge($usedAs, $eleValueResult['used_as']);
			}

			// getElementDependencies() said this element refers to the one going, so it is reported whether or
			// not anything above worked out what to change. The two are not the same question - finding a
			// reference needs only to spot it, removing one needs to know which setting it sits in and what
			// that setting is allowed to look like without it - and where only the first has an answer, a
			// person is told rather than left to find out.
			if(!$usedAs) {
				$usedAs[] = 'somewhere in its settings, which you will need to look at yourself';
			}

			if($usedAs) {
				$references[] = $this->elementReference(
					_AM_ELE_USAGE_SECTION_OTHER_ELEMENTS,
					"the ".$row['ele_type']." element '".$row['ele_handle']."' (form ".intval($row['id_form']).") - ".implode(', ', $usedAs),
					'formulize', 'ele_id', $row['ele_id'], $updates
				);
			}
		}
		return $references;
	}

	/**
	 * Which element handles does this element refer to, in the settings the reference cleanup can rewrite?
	 *
	 * Answered by getElementDependencies(), the same routine the configuration-as-code export uses to work
	 * out what an element needs before it can be created. It is not cheap - it resolves every id it finds by
	 * loading that element - and the answer is about the element being examined rather than the one being
	 * deleted, so it is worked out once and kept. The scan walks the whole elements table once per element
	 * asked about, which without this turns a question into a question per pair.
	 *
	 * Keyed on what was read rather than on the id alone, so an element that changes during the request -
	 * which is exactly what the integrity test does when it builds its fixtures - is not answered from a
	 * stale reading.
	 *
	 * @param array $row The element row, with the settings columns and ele_type.
	 * @return array The handles this element refers to.
	 */
	private function elementDependencyHandles($row) {
		static $cache = array();
		$settings = array(
			'ele_type' => $row['ele_type'],
			'ele_filtersettings' => $row['ele_filtersettings'],
			'ele_disabledconditions' => $row['ele_disabledconditions'],
			'ele_dynamicdefault_conditions' => $row['ele_dynamicdefault_conditions'],
			'ele_dynamicdefault_source' => $row['ele_dynamicdefault_source'],
			'ele_value' => $row['ele_value']
		);
		$key = intval($row['ele_id']).':'.md5(serialize($settings));
		if(!isset($cache[$key])) {
			$cache[$key] = $this->getElementDependencies($settings);
		}
		return $cache[$key];
	}

	/**
	 * Ask an element's own type to rewrite its ele_value without the element being deleted.
	 *
	 * Types that can do this implement removeElementFromEleValue(); the rest do not, and their reference is
	 * reported rather than removed. Loading and instantiating a type handler is not free, so both are cached
	 * per type across the whole scan - the elements table is read in one pass and most systems have only a
	 * handful of types in play.
	 *
	 * @param array $row The element row being examined, with ele_type and ele_value.
	 * @param int $elementId The id of the element being deleted.
	 * @param string $handle The handle of the element being deleted.
	 * @return array|false What the type reported, or false if it has nothing to say.
	 */
	private function removeElementFromEleValueForType($row, $elementId, $handle) {
		static $typeHandlers = array();
		$type = $row['ele_type'];
		if(!isset($typeHandlers[$type])) {
			$typeHandlers[$type] = false;
			if(file_exists(XOOPS_ROOT_PATH.'/modules/formulize/class/'.$type.'Element.php')) {
				require_once XOOPS_ROOT_PATH.'/modules/formulize/class/'.$type.'Element.php';
				$typeHandler = xoops_getmodulehandler($type.'Element', 'formulize');
				if($typeHandler AND method_exists($typeHandler, 'removeElementFromEleValue')) {
					$typeHandlers[$type] = $typeHandler;
				}
			}
		}
		if(!$typeHandlers[$type]) {
			return false;
		}
		$eleValue = @unserialize((string) $row['ele_value']);
		if(!is_array($eleValue)) {
			return false;
		}
		return $typeHandlers[$type]->removeElementFromEleValue($eleValue, $elementId, $handle);
	}

	/**
	 * Where is this element used, and what would deleting it cost?
	 *
	 * One report, two audiences: the admin interface shows it when someone asks where an element is used and
	 * again before they delete it, and the MCP delete tool returns it as its preview. It lives here rather
	 * than in either of those so the answer cannot differ between them, and it reads the same
	 * findReferencesToElement() that removeElementReferences() acts on, so the report cannot promise a tidy up
	 * that does not happen or stay quiet about one that does.
	 *
	 * The distinction the report draws is between what deletion handles for you and the one thing it cannot:
	 * a person's own code naming the handle. See removeElementReferences() for why.
	 *
	 * Places the element is not used are left out entirely rather than listed as empty. Someone reading this
	 * wants to know what deleting will disturb, and a wall of headings saying "none" buries that.
	 *
	 * @param object $elementObject The element being asked about
	 * @return array The usage report
	 */
	public function elementUsageReport($elementObject) {

		$elementId = intval($elementObject->getVar('ele_id'));
		$formId = intval($elementObject->getVar('fid'));
		$handle = $elementObject->getVar('ele_handle');
		$form_handler = xoops_getmodulehandler('forms', 'formulize');
		$formObject = $form_handler->get($formId);

		$impact = [
			'element_id' => $elementId,
			'element_handle' => $handle,
			'element_caption' => $elementObject->getVar('ele_caption'),
			'element_type' => $elementObject->getVar('ele_type'),
			'form_id' => $formId,
			'form_title' => $formObject ? $formObject->getVar('form_title') : null,
		];

		// how much data would be destroyed
		$impact['stores_data'] = (bool) $elementObject->hasData;
		$impact['entries_with_a_value_in_this_element'] = 0;
		if($elementObject->hasData AND $formObject) {
			$dataTable = $this->db->prefix('formulize_'.$formObject->getVar('form_handle'));
			$countSql = "SELECT COUNT(*) AS c FROM `$dataTable` WHERE `".formulize_db_escape($handle)."` IS NOT NULL AND `".formulize_db_escape($handle)."` != ''";
			if($countResult = $this->db->query($countSql)) {
				$countRow = $this->db->fetchArray($countResult);
				$impact['entries_with_a_value_in_this_element'] = intval($countRow['c']);
			}
		}

		// the form's principal identifier is reset to nothing if this element was it
		$impact['is_the_principal_identifier'] = ($formObject AND intval($formObject->getVar('pi')) === $elementId);

		// everywhere else the element is referenced from, grouped under the heading each belongs to. The
		// order the sections come out in is the order the scan finds them, which puts the screens people
		// look at first and the bookkeeping last.
		$sections = [];
		foreach($this->findReferencesToElement($elementObject) as $reference) {
			$sections[$reference['section']][] = $reference['description'];
		}
		$impact['cleaned_up_automatically'] = [];
		foreach($sections as $what => $items) {
			$impact['cleaned_up_automatically'][] = ['what' => $what, 'items' => $items];
		}

		// references that cannot be cleaned up automatically, because they are code rather than configuration
		if($broken = $this->codeReferencesToElement($elementId, $handle)) {
			$impact['references_in_code_that_you_will_need_to_fix'] = $broken;
			$impact['about_those_references'] = "Deleting an element clears it out of every setting that stores it as configuration - form screen pages and defaults, list screen columns and filters, map and calendar screen settings, custom button effects, display and read only conditions on other elements, dynamic default values, group visibility filters, entries-are-users settings, saved views, and any screen identifying entries by it in the address bar. The references above are different: they are places a person wrote this element's name into code, either a formula on another element or a custom code file. Removing a term from someone's code is not a change that can be made safely without knowing what the code is for, so these are left alone and are yours to fix.";
		}

		return $impact;
	}

	/**
	 * The places a person has written this element's handle into code, which deleting the element cannot fix.
	 *
	 * There are two ways a handle gets referenced: as a PHP variable in derived value code ($some_handle),
	 * and in curly braces in the default value of a text or textarea element and in the content of a static
	 * content element ({some_handle}).
	 *
	 * The SQL is only a coarse filter - it is deliberately loose, because an underscore is a single character
	 * wildcard in LIKE and handles are full of them. The precise test happens in PHP, where a trailing name
	 * character can be excluded so that $artifacts_year does not match $artifacts_year_era. The excluded set
	 * is PHP's own grammar for what may continue a variable name, [a-zA-Z0-9_\x80-\xff], which includes the
	 * high bytes that let variable names hold accented and other non-ASCII characters. No /u modifier: that
	 * grammar is defined in bytes, and a multibyte character is a sequence of bytes that are all inside the
	 * excluded range anyway.
	 *
	 * @param int $elementId The element's id.
	 * @param string $handle The element's handle.
	 * @return array The references, described for a person.
	 */
	private function codeReferencesToElement($elementId, $handle) {
		$broken = [];
		$continuesAName = '(?![A-Za-z0-9_\x80-\xff])';
		$referencePattern = '/(\$'.preg_quote($handle, '/').$continuesAName.'|\{'.preg_quote($handle, '/').'\})/';

		// other elements naming this one in their settings
		$elementCodeFilesAlreadyReported = array();
		$referenceSql = "SELECT ele_id, ele_handle, ele_type, id_form, ele_value FROM ".$this->db->prefix('formulize')."
			WHERE ele_value LIKE ".$this->db->quoteString('%'.$handle.'%');
		if($referenceResult = $this->db->query($referenceSql)) {
			while($referenceRow = $this->db->fetchArray($referenceResult)) {
				if(intval($referenceRow['ele_id']) === $elementId) { continue; }
				if(preg_match($referencePattern, (string) $referenceRow['ele_value'])) {
					$broken[] = "the ".$referenceRow['ele_type']." element '".$referenceRow['ele_handle']."' (form ".intval($referenceRow['id_form']).") refers to this element by name in its settings";
					// an element that keeps code in ele_value also has that code in a file of its own, named for
					// its type and handle. It is the same reference seen twice, so the file is not reported again
					// below - the element it belongs to has already been named, which is the more useful of the two.
					$elementCodeFilesAlreadyReported[$referenceRow['ele_type'].'_'.$referenceRow['ele_handle'].'.php'] = true;
				}
			}
		}

		// custom code files that name the handle, including the code files written for derived value and
		// static content elements, which live in the same folder
		$codeDir = XOOPS_ROOT_PATH.'/modules/formulize/code/';
		if(is_dir($codeDir)) {
			foreach((array) glob($codeDir.'*.php') as $codeFile) {
				if(isset($elementCodeFilesAlreadyReported[basename($codeFile)])) { continue; }
				$contents = file_get_contents($codeFile);
				if($contents !== false AND preg_match($referencePattern, $contents)) {
					$broken[] = "custom code file '".basename($codeFile)."' refers to this element by name";
				}
			}
		}

		return $broken;
	}

	function delete($elementObject, $force = false){
		$elementType = $elementObject->getVar('ele_type');
		if(file_exists(XOOPS_ROOT_PATH . "/modules/formulize/class/".$elementType."Element.php")) {
			$typeElementHandler = xoops_getmodulehandler($elementType.'Element', 'formulize');
		} else {
			$typeElementHandler = xoops_getmodulehandler('elements', 'formulize');
		}
		$deletedFilePaths = $typeElementHandler->deleteAssociatedDataAndResources($elementObject, entryScope: 'all');
		$result0 = ($deletedFilePaths !== false);
		if(!$result0) {
			print "Error: pre-delete processing for element ".htmlspecialchars(strip_tags($elementObject->getVar('ele_id')))." failed";
		}
		$form_handler = xoops_getmodulehandler('forms', 'formulize');

		// Before the row goes, not after. Working out what refers to this element leans on
		// getElementDependencies(), which resolves an id written into a setting by looking the element up so
		// it can compare handles - and an element that has already been deleted cannot be looked up, so every
		// reference stored as an id would come back clean and be left behind.
		$this->removeElementReferences($elementObject);

		$sql = "DELETE FROM ".formulize_TABLE." WHERE ele_id=".$elementObject->getVar("ele_id")."";
		if( false != $force ){
			$result1 = $this->db->queryF($sql);
		}else{
			$result1 = $this->db->query($sql);
		}
		$result2 = deleteElementConnectionsInRelationships($elementObject->getVar('fid'), $elementObject->getVar('ele_id'));
		$result3 = true;
		if($elementObject->hasData) {
			if(!$result3 = $form_handler->deleteElementField($elementObject)) {
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

		// a page left with nothing on it is kept rather than removed. traverseScreenPages() does not carry an
		// empty page through to displayFormPages(), so it does not appear in the form either way, and keeping
		// it means the page a person built - its title, its conditions, its read only flag - is still there
		// for them to put something else on. Deleting an element should not delete their page as well.
		$screenHandler = xoops_getmodulehandler('multiPageScreen', 'formulize');
		$screenHandler->removeElementsFromScreens($elementObject->getVar('ele_id'));

		$this->clearCachedElement($elementObject->getVar('ele_id'));

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

		global $formulizeCachedElementObjects;
		while( $myrow = $this->db->fetchArray($result) ){
			// instantiate the right kind of element, depending on the type
			$elementId = $myrow['ele_id'];
			// an entry that is not an object is the negative cache get() writes when a lookup
			// fails, and we have the row here, so rebuild rather than hand back the false
			if(isset($formulizeCachedElementObjects[$elementId]) AND is_object($formulizeCachedElementObjects[$elementId])) {
				$elementObject = $formulizeCachedElementObjects[$elementId];
			} else {
				$ele_type = $myrow['ele_type'];
				if(file_exists(XOOPS_ROOT_PATH."/modules/formulize/class/".$ele_type."Element.php")) {
					$customTypeHandler = xoops_getmodulehandler($ele_type."Element", 'formulize');
					$elementObject = $customTypeHandler->create();
				} else {
					$elementObject = new formulizeElement();
				}
				$elementObject->assignVars($myrow);
				$elementObject = $this->_setElementProperties($elementObject);
				$this->cacheElementObject($elementObject);
			}
			if($id_as_key === true OR $id_as_key == "element_id"){
				$ret[$myrow['ele_id']] =& $elementObject;
			}elseif($id_as_key == "handle") {
				$ret[$myrow['ele_handle']] =& $elementObject;
			} else {
				$ret[] =& $elementObject;
			}
			unset($elementObject);
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

	/**
	 * Return the options that users can choose from when filtering/searching on this element.
	 *
	 * The KEYS of the returned array are the values that get submitted by the filter UI and
	 * matched against the data (they are passed through the handler's prepareLiteralTextForDB()
	 * before hitting the database, so an element whose stored values are codes can return
	 * human readable keys here and translate them there - the yn element does exactly that).
	 * The array values are only used as labels when the caller sets its $useValue flag, which
	 * it does for linked elements and user lists.
	 *
	 * Element types whose options cannot be enumerated should return an empty array; the
	 * caller then falls back to finding the distinct values present in the data.
	 *
	 * A string may be returned for linked elements, whose ele_value[2] is a "fid#*=:*handle"
	 * specification that the caller resolves against the source form.
	 *
	 * @param object $element The element object to return the filter options for
	 * @return array|string Associative array of filter value => label, or a linked element spec
	 */
	function getFilterOptions($element = null) {
		return array();
	}

	// this method is used by custom elements, to do final output from the "local" formatDataForList method, so the custom element developer can simply set booleans there, and they will be enforced here
	//
	// ORDER OF OPERATIONS IS SECURITY-CRITICAL - do not reorder:
	//   1. make the value safe (escape plain text, or purify markup)  <- the ONE canonical safety step
	//   2. let the element compose its own markup AROUND the now-safe value
	//   3. truncate (skipped for HTML - cutString is not tag-aware and would cut tags in half)
	//   4. make URLs clickable (operates on already-safe text)
	//
	// Values arriving here are RAW: getValue() html-decodes on the way out of the dataset by design,
	// and undoAllHTMLChars() strips arbitrarily many levels of encoding. Escaping applied when the
	// value was SAVED is therefore not a defence and must never be relied on here.
	function formatDataForList($value, $handle="", $entry_id=0, $textWidth=100) {
		global $myts;
		$value = trans($value);
		if(!$this->length AND $this->length !== 0 AND $this->length !== '0') {
			$this->length = 255;
		}

		// The raw (unescaped) value is handed to composeMarkupForList so an element that needs the
		// original text - to look something up, or to build an href - does not have to decode the escaped
		// value back. It is entry-specific and passed as an argument, never stored on the (singleton)
		// handler, since one handler renders many entries per page.
		$rawValue = $value;

		// 1. THE canonical safety step. Applies to every value, every element type, no opt-out.
		if($this->treatDataAsHtml()) {
			// intentional markup (derived values, rich text): allow-list filter rather than escape,
			// so admin-authored formatting survives while script vectors do not
			$value = formulize_purifyHtmlValue($value, $handle, $entry_id);
		} else {
			$value = $myts->htmlSpecialChars($value);
		}

		// 2. the element wraps its (already safe) value in any markup of its own. It receives the raw
		// value and the column width too, so a markup-composer that truncates its own display text (and
		// sets length=0 so step 3 does not then truncate the markup it built) has what it needs.
		$value = $this->composeMarkupForList($value, $handle, $entry_id, $rawValue, $textWidth);

		// 3. truncation - never for HTML, since cutString() counts characters and would sever tags
		if(!$this->treatDataAsHtml() AND $this->length > 0) {
			$value = printSmart($value,$this->length);
		}

		// 4.
		if($this->clickable) {
			$value = formulize_text_to_hyperlink($value);
		}
		$value = formulize_handleRandomAndDateText($value);
		return $value;
	}

	/**
	 * Resolve whether this element's data is to be treated as intentional HTML (to be PURIFIED and
	 * preserved) rather than plain text (to be ESCAPED).
	 *
	 * $dataIsHtml is a property of the DATA, not of the element's rendering style. Elements that merely
	 * wrap their value in markup (a link, etc.) must leave it FALSE and build that markup in
	 * composeMarkupForList(), which runs AFTER the value has been escaped. Its three states:
	 *
	 *   false -> plain text, HTML-ESCAPED (the safe default for a declared element)
	 *   true  -> intentional markup: PURIFIED (allow-list) instead of escaped, and not truncated
	 *            (truncation is not HTML-aware and would cut tags in half)
	 *   null  -> not declared by this element type. Falls back to the legacy $striphtml signal:
	 *            striphtml===false meant "this emits HTML", so it is PURIFIED (not passed through raw),
	 *            keeping third-party/custom element types working while still filtering them. A brand
	 *            new element that declares NEITHER property lands here with striphtml===null, so
	 *            (null===false) is false and it ESCAPES - i.e. still secure by default.
	 *
	 * @return bool
	 */
	function treatDataAsHtml() {
		if($this->dataIsHtml === null) {
			return ($this->striphtml === false); // legacy signal from element types predating $dataIsHtml
		}
		return (bool) $this->dataIsHtml;
	}

	/**
	 * Wrap the value in any markup this element type needs (a link, etc).
	 *
	 * IMPORTANT: $value has ALREADY been escaped/purified when this is called, so markup built here
	 * is preserved as-is. Never escape $value again in here, and never build markup anywhere that
	 * runs before this point - doing so is what allows user data into an href/attribute unescaped.
	 *
	 * Any entry-specific state this needs (eg. a lookup keyed off the raw value + entry_id) must be
	 * derived here, per call, NOT stored on the handler between formatDataForList() and here - the handler
	 * is a singleton that renders many entries per page, so a property set for one entry would leak to the
	 * next. Function-static caches/counters (keyed by entry) are fine and are how it is done today.
	 *
	 * @param string $value The escaped/purified value (safe to place in HTML as-is)
	 * @param string $handle The element handle
	 * @param int $entry_id The entry the value belongs to
	 * @param string|null $rawValue The value BEFORE escaping - use for lookups / hrefs, never output it raw
	 * @param int $textWidth The list column width; if this composer truncates its own display text it
	 *                       should do so here and set $this->length=0 so step 3 does not truncate the markup
	 * @return string The value, optionally wrapped in markup
	 */
	function composeMarkupForList($value, $handle="", $entry_id=0, $rawValue=null, $textWidth=100) {
		return $value; // default: no markup of our own
	}

	/**
	 * Make a value safe to display READ-ONLY in a form - ie. on its way into a xoopsFormLabel, which
	 * renders whatever it is given as-is.
	 *
	 * THE RULE, and why read-only is purified rather than escaped:
	 *
	 *   EDITABLE  (an input's value attribute, a textarea's body) -> ESCAPE. The user has to be able to see
	 *             and edit exactly what they typed, so markup must appear literally, as text.
	 *             Handled at the core sinks - icms_form_elements_Text/Textarea::render().
	 *   READ-ONLY (disabled elements, print view) -> PURIFY. Nothing is being edited here, so allow-list
	 *             filtering is both safer and kinder: in the odd case where a user did type markup, they
	 *             get safe markup rendered rather than a wall of escaped tags.
	 *
	 * Purifying (not escaping) also keeps a read-only element consistent with how the SAME value is shown
	 * in a list, where formatDataForList() purifies anything whose data is HTML. Before this existed, the
	 * disabled/print branches of text, select, checkbox and radio - and every {OTHER|n} value - passed raw
	 * user data straight into a Label.
	 *
	 * IMPORTANT - pass the DATA, not markup this element has already composed. HTMLPurifier strips
	 * <form>, <input>, <button> and <select> outright, so running a composed control through here would
	 * delete it. Make the value safe first, then build markup around it (same order as
	 * formatDataForList -> composeMarkupForList).
	 *
	 * Multi-value elements (checkbox, multi-select) pass an ARRAY plus the $joiner they want between the
	 * parts. Each part is made safe individually and the joiner is added afterwards, so the joiner itself -
	 * which is ours, not the user's - is never filtered as though it were content. Callers must NOT
	 * pre-join and pass a single string for this reason.
	 *
	 * @param string|array $value The value(s) to display read-only
	 * @param string $handle Optional. Element handle, for tagging purification log events
	 * @param int $entry_id Optional. Entry id, for tagging purification log events
	 * @param string|null $joiner Optional. When $value is an array, the separator to join the safe parts
	 *                            with (eg. ", " for a select, "<br>" for a checkbox). Defaults to ", ".
	 * @return string The value, purified (or escaped, if purification was unavailable)
	 */
	function makeValueSafeForReadOnlyDisplay($value, $handle="", $entry_id=0, $joiner=null) {
		if(is_array($value)) {
			$safeParts = array();
			foreach($value as $part) {
				$safeParts[] = $this->makeValueSafeForReadOnlyDisplay($part, $handle, $entry_id);
			}
			return implode($joiner === null ? ", " : $joiner, $safeParts);
		}
		// Numbers and other non-strings cannot carry markup - return them untouched rather than coercing,
		// so an element that hands over an int still gets an int back.
		if(!is_string($value) OR $value === '') {
			return $value;
		}
		return formulize_purifyHtmlValue($value, $handle, $entry_id);
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

    /**
		 * Determine if the element is displayed for the specified user
		 * @param int|string|object $elementIdentifier - The element id, handle, or object to check
		 * @param int|object $userIdOrObject - Optional. The user id or user object to validate the element for. If not specified, the active xoopsUser will be used instead.
		 * @return bool Returns true if the user can see the element, false otherwise. Throws exception if the specified element or user does not exist.
		 */
    function isElementVisibleForUser($elementIdentifier, $userIdOrObject=0) {
			if(!$elementObject = _getElementObject($elementIdentifier)) {
				throw new Exception("Invalid element identifier passed to isElementVisibleForUser");
			}
      $ele_display = $elementObject->getVar('ele_display');
      if($ele_display == 1) {
				return true;
			}	elseif(!is_numeric($ele_display)) {
				if(is_object($userIdOrObject)) {
						$userObject = $userIdOrObject;
				} elseif($userIdOrObject) {
						$memberHandler = xoops_gethandler('member');
						if(!$userObject = $memberHandler->getUser($userIdOrObject)) {
							throw new Exception("Could not retrieve user object for id ".$userIdOrObject." when checking element display settings.");
						}
				} else {
					global $xoopsUser;
					$userObject = $xoopsUser;
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

	/**
	 * Process group table element data through the base groupTableElement handler class
	 * @param int $formId  The id of the form the element is in
	 * @param int $entryId The groupid of the group being edited
	 * @return int|bool the groupid or false on failure
	 */
	static public function processGroupSubmission($formId, $entryId) {
		require_once XOOPS_ROOT_PATH . "/modules/formulize/class/groupTableElement.php";
		return formulizeGroupTableElementHandler::processGroupSubmission($formId, $entryId);
	}

	/**
	 * Evaluate an entry regarding the default group memberships for users that may be impacted by the data in the entry
	 * Some group memberships are conditional, based on the data in the entry, and this method will evaluate those conditions and add or remove group memberships accordingly
	 * The form associated with the groups might be a different one, the form associated with the user might be a different one. Everything is worked out in the method.
	 * Processed through the base userAccountGroupMembershipElement handler class
	 * @param int $userId The id of the user to process group memberships for
	 * @param int $formId The id of the entries_are_users form that is being worked with
	 * @param int $entryId The id of the entry that was submitted
	 * @return empty Has no return value
	 */
	static public function processUserGroupMemberships($userId, $formId, $entryId) {
		return formulizeUserAccountGroupMembershipElementHandler::processUserGroupMemberships($userId, $formId, $entryId);
	}

}

function optionIsValidForElement($option, $elementHandleOrId) {
    if(!$element = _getElementObject($elementHandleOrId)) {
			return false;
    }
    return $element->optionIsValid($option);
}

/**
 * Take a type string or an element object and return true if it is any type of element based on the Select type
 *
 * A "select type" is the select element itself, or any element whose class extends it -
 * selectLinked, selectUsers, autocomplete/autocompleteLinked/autocompleteUsers,
 * listbox/listboxLinked/listboxUsers, and any custom subclass of those. They are all
 * subclasses of formulizeSelectElement, so this reduces to the generic is-or-extends test.
 *
 * @param string|object $type The ele_type string to test, or an element object
 * @return bool
 */
function anySelectElementType($type) {
	return elementTypeIsOrExtends($type, "select");
}

/**
 * Take a type string and return true if it is any type of element based on the Radio type
 *
 * This covers the radio element itself, the yes/no element, and any custom element type that
 * extends the radio element. Where the behaviour of those types genuinely differs (the option
 * labels of a yes/no element, for instance) the difference is handled by the element classes
 * themselves - see formulizeRadioElementHandler::getFilterOptions() and
 * previousEntryOptionKey(), and the yn overrides of them - so generic code can safely treat
 * every one of these as a radio.
 *
 * NOTE: code gated by this function calls radio-family methods (previousEntryOptionKey) on
 * the type's handler. This function tests the ELEMENT class hierarchy, so a custom radio-based
 * element must have a handler that extends formulizeRadioElementHandler (or another radio-family
 * handler) - the two hierarchies must be parallel, which is a requirement when writing any custom
 * element class.
 *
 * @param string|object $type The ele_type string to test, or an element object
 * @return bool
 */
function anyRadioElementType($type) {
	return elementTypeIsOrExtends($type, "radio");
}

/**
 * Take a type string and return true if it is any type of element based on the Checkbox type
 *
 * This covers the checkbox element, the checkboxLinked element (which extends it), and any
 * custom element type that extends either. Checkbox based elements can hold multiple values,
 * which is what most callers are really asking about.
 *
 * @param string|object $type The ele_type string to test, or an element object
 * @return bool
 */
function anyCheckboxElementType($type) {
	return elementTypeIsOrExtends($type, "checkbox");
}

/**
 * Determine whether an element type IS another element type, or descends from it. This is the
 * "family" test that most code wants: treat this element like an X, whether it is an X itself
 * or a custom type that extends X. The anySelectElementType/anyRadioElementType/
 * anyCheckboxElementType functions are just readable shorthands for the common families.
 *
 * @param string|object $type The ele_type string to test, or an element object
 * @param string|array $parentType The ele_type of the family, e.g. 'select', 'radio', 'yn', as a string or an array of types. If an array, returns true if $type is any of the listed types or a descendant of any of them.
 * @return bool True if $type is $parentType or a descendant of it
 */
function elementTypeIsOrExtends($type, $parentType) {
	if(is_object($type)) {
		$type = $type->getVar('ele_type');
	}
	if(is_array($parentType)) {
		foreach($parentType as $pt) {
			if($type === $pt || elementTypeHasOtherTypeAsParent($type, $pt)) {
				return true;
			}
		}
		return false;
	} else {
		return ($type === $parentType) ? true : elementTypeHasOtherTypeAsParent($type, $parentType);
	}
}

/**
 * Determine whether an element type is a subclass of another element type: i.e. its element
 * class descends from (but is not) the parent type's element class.
 *
 * This is the general form of the pattern that anySelectElementType() used to hardcode.
 * Throughout the codebase there are switch/case and if statements keyed on literal ele_type
 * strings (e.g. case "radio":). Those miss custom element types that extend a built-in type.
 * Instead of naming every subclass in every switch, code can ask
 * elementTypeHasOtherTypeAsParent($type, 'select') and get a true answer for every descendant
 * of formulizeSelectElement.
 *
 * Note: this is a strict subclass test (like is_subclass_of), so it returns FALSE when $type
 * IS $parentType. Callers that want "is this type X or a subclass of X" should use
 * elementTypeIsOrExtends() instead, which is what the anyXElementType functions do.
 *
 * The element-class naming convention is relied upon: type "radio" -> class formulizeRadioElement,
 * type "checkboxLinked" -> class formulizeCheckboxLinkedElement, etc. ("formulize" . ucfirst(type)
 * . "Element").
 *
 * @param string|object $type The ele_type string to test, or an element object
 * @param string $parentType The candidate parent ele_type, e.g. 'select', 'radio'
 * @return bool True if $type's element class is a subclass of the parent type's element class
 */
function elementTypeHasOtherTypeAsParent($type, $parentType) {
	if(is_object($type)) {
		$type = $type->getVar('ele_type');
	}
	if($type === $parentType) {
		return false; // a type is not a subclass of itself
	}
	// the ancestry is cached per type, so this is just an in_array over a very short array
	return in_array($parentType, formulize_eleTypeAncestry($type), true);
}

/**
 * Return the ancestry of an element type: the ele_types of every element class it extends, with
 * the nearest ancestor first. The walk stops at formulizeElement, which every element extends and
 * which therefore tells us nothing.
 *
 * For example: "listboxLinked" -> array("selectLinked", "select")
 *              "pointsRedemptionRadio" -> array("radio")
 *              "text" -> array()
 *
 * This is THE ONLY place that pays the cost of inspecting the class hierarchy, and it is cached
 * per element type - one entry per type, computed at most once per request. Every type check in
 * this file (anySelectElementType, anyRadioElementType, anyCheckboxElementType,
 * elementTypeHasOtherTypeAsParent, formulize_resolveEleType) then reduces to an in_array over an
 * array that is typically empty or one or two entries long. That matters because these checks
 * replaced straight string comparisons in hot loops (importing a file walks every column of every
 * row), so they must not do any real work on the repeat calls.
 *
 * @param string $type The ele_type whose ancestry we want
 * @return array The ele_types this type descends from, nearest ancestor first (empty if none)
 */
function formulize_eleTypeAncestry($type) {
	static $cachedAncestry = array();
	if(isset($cachedAncestry[$type])) {
		return $cachedAncestry[$type];
	}
	$ancestry = array();
	if(file_exists(XOOPS_ROOT_PATH."/modules/formulize/class/".$type."Element.php")) {
		// the true flag makes the handler optional: if the class file exists but does not define a
		// conventionally named handler class, we get false back instead of a fatal error, and the
		// type is simply treated as having no ancestry
		if($customTypeHandler = xoops_getmodulehandler($type."Element", 'formulize', true)) {
			$className = get_class($customTypeHandler->create());
			while($className = get_parent_class($className)) {
				$ancestorType = formulize_eleTypeForClassName($className);
				if($ancestorType === '') {
					break; // reached formulizeElement (or something unconventionally named), so there is nothing more to learn
				}
				$ancestry[] = $ancestorType;
			}
		}
	}
	$cachedAncestry[$type] = $ancestry; // cache the misses too, so a type with no class file is not looked up on disk again
	return $ancestry;
}

/**
 * Find the admin UI template for an element type, falling back up the element class ancestry.
 *
 * Custom element types that extend a built-in type usually have no admin template of their own -
 * they inherit the parent type's adminPrepare/adminSave methods, so they should inherit the
 * admin template those methods are written for, too. This looks for a template belonging to the
 * type itself first, then to each ancestor type in turn.
 *
 * @param string $ele_type The element type
 * @param string $suffix Optional suffix on the template name: "_advanced" for the Advanced tab template
 * @return string The db: template reference for the nearest type that has one, or an empty string if none found
 */
function formulize_elementTypeAdminTemplate($ele_type, $suffix = "") {
	foreach(array_merge(array($ele_type), formulize_eleTypeAncestry($ele_type)) as $candidateType) {
		if(file_exists(XOOPS_ROOT_PATH."/modules/formulize/templates/admin/element_type_".$candidateType.$suffix.".html")) {
			return "db:admin/element_type_".$candidateType.$suffix.".html";
		}
	}
	return "";
}

/**
 * Convert an element class name back into its ele_type, relying on the naming convention
 * (type "checkboxLinked" <-> class formulizeCheckboxLinkedElement).
 *
 * @param string $className The element class name
 * @return string The ele_type, or an empty string if the class is not a conventionally named
 *                element class (formulizeElement itself resolves to an empty string, which is
 *                what stops the hierarchy walk in formulize_resolveEleType)
 */
function formulize_eleTypeForClassName($className) {
	if(substr($className, 0, 9) !== 'formulize' OR substr($className, -7) !== 'Element') {
		return '';
	}
	return lcfirst(substr($className, 9, strlen($className) - 16)); // strip the "formulize" prefix (9 chars) and the "Element" suffix (7 chars)
}

/**
 * Resolve an element type to the nearest type that the caller actually knows how to handle.
 *
 * This is the general answer to a problem that recurs everywhere the codebase switches on
 * ele_type: a custom element type that extends a built-in type (a subclass of the radio
 * element, or of the checkbox element, or of anything else) will not match any case in the
 * switch, and so silently falls through to the default - even though it should behave exactly
 * like the type it extends.
 *
 * Rather than needing a central registry of "canonical" element types (which cannot be derived
 * reliably, since custom types are just files in the class folder), each caller passes the set
 * of types IT handles - which is simply the list of its own case labels. This function then
 * walks up the element's class hierarchy and returns the first ancestor type that appears in
 * that set.
 *
 * Exact matches always win, which is what keeps subclasses that need to stay distinct working:
 * the yn element extends the radio element, so a switch that lists both "yn" and "radio" still
 * gets "yn" for a yes/no element, while a switch that only lists "radio" gets "radio" for it.
 *
 * If no ancestor is known to the caller, the type is returned unchanged, so the caller's
 * default case applies exactly as it does today.
 *
 * Example:
 *   $switchEleType = formulize_resolveEleType($ele_type, array('select', 'checkbox', 'radio'));
 *   switch($switchEleType) { case 'radio': ... }
 *   // "pointsRedemptionRadio" and "yn" both resolve to "radio"
 *   // "listboxLinked" and "autocomplete" both resolve to "select"
 *   // a custom subclass of the checkbox element resolves to "checkbox"
 *
 * @param string $type The ele_type to resolve
 * @param array $knownTypes The ele_types the caller handles (ie: the case labels of its switch)
 * @return string The nearest type in $knownTypes, or $type unchanged if none of its ancestors are known
 */
function formulize_resolveEleType($type, $knownTypes) {
	// An exact match always wins, so types that need to stay distinct do. This is also the fast
	// path: for the built in types that these switches are written around, it is the only work done.
	if(in_array($type, $knownTypes, true)) {
		return $type;
	}
	// Otherwise walk the (cached) ancestry, nearest ancestor first, and take the first one the
	// caller knows about. No caching is needed here beyond the ancestry itself - this is a couple
	// of in_array calls over arrays that are only ever a few entries long.
	foreach(formulize_eleTypeAncestry($type) as $ancestorType) {
		if(in_array($ancestorType, $knownTypes, true)) {
			return $ancestorType;
		}
	}
	return $type; // no ancestor is known to the caller, so its default case applies, as it does today
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

// Loaded here rather than at the top of the file because the classes in it extend formulizeElementsHandler,
// which is declared above and - since it composes a trait - is not hoisted to the top of this file. See the
// note beside the other requires at the top.
require_once XOOPS_ROOT_PATH . "/modules/formulize/class/userAccountElement.php";
