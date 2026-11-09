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
require_once XOOPS_ROOT_PATH . "/modules/formulize/class/elementReferences.php";
require_once XOOPS_ROOT_PATH . "/modules/formulize/class/elementHandleCode.php";
require_once XOOPS_ROOT_PATH . "/modules/formulize/class/elementParameters.php";
// NB userAccountElement.php is required at the BOTTOM of this file, not here. It declares classes that
// extend formulizeElementsHandler, so it is loaded once that class has been declared below. Nothing in this
// file needs those classes at declaration time; the two methods that call them do so at runtime.

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
		$this->initVar('ele_searchtype', XOBJ_DTYPE_TXTBOX, '', false, 30); // the search interface this element gets in a list, when the list doesn't choose one. Empty means not chosen, see listSearchType()
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
		$dataTypeInfo = $this->getDataTypeInformation();
		return self::isNumericDataType($dataTypeInfo['dataType']);
	}

	/**
	 * The type of this element's field in the database. An element that has no field yet, because it is being created,
	 * gets the type its field will be created with, which its class declares (see upsertElementSchemaAndResources()).
	 * The fields of a form's data table are read once per request, for all its elements at once, since lists and the
	 * screen settings ask this of every column.
	 * @return string The type, ie: date, int(11), varchar(255), or blank if the element has no field
	 */
	function fieldDataType() {
		if(!$this->hasData) {
			return '';
		}
		if(!$this->getVar('ele_id') OR !$this->getVar('id_form')) {
			if(method_exists($this, 'getDefaultDataType')) {
				return (string) $this->getDefaultDataType();
			}
			return (string) $this->overrideDataType;
		}
		static $fieldTypesByForm = array();
		$fid = intval($this->getVar('id_form'));
		if(!isset($fieldTypesByForm[$fid])) {
			$fieldTypesByForm[$fid] = array();
			global $xoopsDB;
			$form_handler = xoops_getmodulehandler('forms', 'formulize');
			if($formObject = $form_handler->get($fid)) {
				if($res = $xoopsDB->queryF("SHOW COLUMNS FROM ".$xoopsDB->prefix("formulize_".$formObject->getVar('form_handle')))) {
					while($row = $xoopsDB->fetchArray($res)) {
						$fieldTypesByForm[$fid][$row['Field']] = strtolower((string) $row['Type']);
					}
				}
			}
		}
		return $fieldTypesByForm[$fid][$this->getVar('ele_handle')] ?? '';
	}

	/**
	 * Does this element hold dates? It does if its field in the database has a date or datetime type, whatever kind of
	 * element it is, because the date oriented searches build SQL that only works on those types.
	 * @return bool
	 */
	function holdsDates() {
		return self::isDateDataType($this->fieldDataType());
	}

	/**
	 * Does this element hold numbers? It does if its field in the database has a numeric type.
	 * @return bool
	 */
	function holdsNumbers() {
		return self::isNumericDataType($this->fieldDataType());
	}

	/**
	 * Are this element's values a set of labelled choices: the entries a linked element points to, the users a user list
	 * can name, options of the element's own, like a yes/no or a radio button's, or stored values that have ui text,
	 * the text people see in place of what is stored? Those suit picking from a list in a search. Values people type
	 * in, like names or numbers, do not have labels of their own.
	 * @return bool
	 */
	function hasLabelledValues() {
		if($this->isLinked OR $this->isUserList) {
			return true;
		}
		$uiText = $this->getVar('ele_uitext');
		if(is_array($uiText) AND count(array_filter($uiText, function($text) { return trim((string) $text) !== ''; }))) {
			return true;
		}
		// the type's handler knows what its options are. A new element has no type recorded yet, so it comes from the class.
		$type = (string) $this->getVar('ele_type');
		if($type === '' AND substr(get_class($this), 0, 9) == 'formulize') {
			$type = lcfirst(substr(get_class($this), 9, -7));
		}
		if($type === '' OR !file_exists(XOOPS_ROOT_PATH."/modules/formulize/class/".basename($type)."Element.php")) {
			return false;
		}
		$typeHandler = xoops_getmodulehandler(basename($type).'Element', 'formulize', true);
		return ($typeHandler AND method_exists($typeHandler, 'getFilterOptions') AND !empty($typeHandler->getFilterOptions($this)));
	}

	/**
	 * The type at the start of a database type, ie: datetime for datetime(6), and int for int(10) unsigned
	 * @param string $dataType A type as the database reports it, or as an element type declares it
	 * @return string
	 */
	static function baseDataType($dataType) {
		$parts = preg_split('/[\s(]/', strtolower(trim((string) $dataType)));
		return $parts[0] ?? '';
	}

	/**
	 * Is a database type a date or datetime, with or without a size, ie: datetime(6)?
	 * @param string $dataType A type as the database reports it, or as an element type declares it
	 * @return bool
	 */
	static function isDateDataType($dataType) {
		return in_array(self::baseDataType($dataType), array('date', 'datetime'), true);
	}

	/**
	 * Is a database type numeric, with or without a size or sign, ie: decimal(10,2) or int(10) unsigned?
	 * @param string $dataType A type as the database reports it, or as an element type declares it
	 * @return bool
	 */
	static function isNumericDataType($dataType) {
		return in_array(self::baseDataType($dataType), array('decimal', 'float', 'numeric', 'double', 'int', 'mediumint', 'tinyint', 'bigint', 'smallint', 'integer'), true);
	}

	/**
	 * The search interfaces this element can have in a list: all of them for an element that holds dates, and all but
	 * the date oriented ones for any other element.
	 * @return array Stored values, as in formulizeHandler::listScreenSearchTypes()
	 */
	function allowedListSearchTypes() {
		$types = array_values(formulizeHandler::listScreenSearchTypes());
		if(!$this->holdsDates()) {
			$types = array_values(array_diff($types, formulizeHandler::dateOnlySearchTypes()));
		}
		return $types;
	}

	/**
	 * The search interface this element gets in a list, when the list doesn't choose one itself: the one chosen in the
	 * element's settings, or if none has been chosen (or the one chosen no longer suits the element), a date range for
	 * elements that hold dates, the simple text box for numbers people type in, since picking from a list of them
	 * makes little sense, and the Search box for everything else.
	 * @return string A stored value, as in formulizeHandler::listScreenSearchTypes()
	 */
	function listSearchType() {
		$chosen = (string) $this->getVar('ele_searchtype');
		if($chosen !== '' AND in_array($chosen, $this->allowedListSearchTypes(), true)) {
			return $chosen;
		}
		if($this->holdsDates()) {
			return 'DateRange';
		}
		if($this->holdsNumbers() AND !$this->hasLabelledValues()) {
			return 'Box';
		}
		return 'MultiFilter';
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
				// the fields every entry has, ie: creation_datetime, are not elements, and are stored by name, the same as the admin UI does
				if(isMetaDataField($element)) {
					$conditionElementId = $element;
				} elseif($conditionElementObject = _getElementObject($element)) {
					$conditionElementId = $conditionElementObject->getVar('ele_id');
				} else {
					throw new Exception("You have specified an invalid element in display conditions");
				}
				if(!in_array($operators[$i], array('=', '!=', 'NOT', '<', '>', '<=', '>=', 'LIKE', 'NOT LIKE', 'IN'))) {
					throw new Exception("You have specified an invalid operator in display conditions");
				}
				if(!in_array($types[$i], array('all', 'oom'))) {
					throw new Exception("You have specified an invalid type in display conditions");
				}
				// store element references as IDs, the canonical format used by the admin UI and import/export (conversion is idempotent if an ID was passed)
				$properties['ele_filtersettings'][0][$i] = $conditionElementId;
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
		$properties['ele_searchtype'] = isset($properties['ele_searchtype']) ? (string) $properties['ele_searchtype'] : ($existingElement ? (string) $existingElement->getVar('ele_searchtype') : '');
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
	 * TODO: this returns an array of handles, but no metadata about where/what the dependencies are. That metadata now exists, for the usage report, deletion and rename: the declaration in $modversion['formulize_element_references'] and each type's static $eleValueReferences, read by formulizeElementReferences. This and getEleValueDependencies()/convertEleValueDependenciesForImport/Export could be built from it too, and stop repeating the per-type key lists.
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
				id_form, ele_type, ele_caption, ele_desc, ele_colhead, ele_handle, ele_order, ele_sort, ele_required, ele_value, ele_uitext, ele_uitextshow, ele_delim, ele_display, ele_disabled, ele_forcehidden, ele_private, ele_encrypt, ele_filtersettings, ele_disabledconditions, ele_use_default_when_blank, ele_exportoptions, ele_dynamicdefault_source, ele_dynamicdefault_conditions, ele_searchtype
				) VALUES (
				%u, %s, %s, %s, %s, %s, %u, %u, %u, %s, %s, %u, %s, %s, %s, %u, %u, %u, %s, %s, %u, %s, %u, %s, %s
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
				$this->db->quoteString($ele_dynamicdefault_conditions),
				$this->db->quoteString((string) $ele_searchtype)
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
				ele_dynamicdefault_conditions = %s,
				ele_searchtype = %s
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
				$this->db->quoteString((string) $ele_searchtype),
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
		$elementId = intval($element->getVar('ele_id'));
		$storedElement = $elementId ? $this->get($elementId, bypassCache: true) : false;
		if($ele_handle AND $storedElement AND $storedElement->getVar('ele_handle') === $ele_handle) {
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
		// an existing element's handle is changing, which its field has to be able to follow - checked now, before the
		// handle is saved and every reference to it is rewritten, rather than when the field is renamed afterwards
		if($storedElement AND $storedElement->getVar('ele_handle') !== $ele_handle) {
			xoops_getmodulehandler('forms', 'formulize')->confirmFieldCanFollowHandle($storedElement, $storedElement->getVar('ele_handle'), $ele_handle);
		}
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
	 *   caller knows this - see renameElementCodeFiles() for why it cannot be worked out from the content.
	 * @return array The places that still name the old handle after the rename, in a way that may or may not be the
	 *   element and so was left for a person to update - see formulizeElementHandleCode. Also kept in
	 *   $this->renameWarnings, for callers that reach the rename through another function.
	 */
	function renameElementResources($elementObject, $original_handle, $eleValueWasSavedWithThisChange = false) {
		$this->renameWarnings = array();
		if($original_handle) {
			if(!$elementObject = _getElementObject($elementObject)) {
				throw new Exception("Invalid element object passed to renameElementResources");
			}
			$ele_handle = $elementObject->getVar('ele_handle');
			if($ele_handle !== (string) $original_handle) {
				// the element's own code files, and the cached derived value formulas named after it, before what is
				// in the code files is rewritten, so that a warning names the file as it is now called
				$this->renameElementCodeFiles($elementObject, $original_handle, $eleValueWasSavedWithThisChange);
				// what names the handle in the code files and every screen's own template files, where it can only be
				// the element, and the tags in the notification mail templates
				$this->renameWarnings = array_merge(
					$this->renameHandleInFiles($original_handle, $ele_handle, $elementObject->getVar('fid')),
					$this->renameHandleInMailTemplates($original_handle, $ele_handle));
				// every setting that names this element by its old handle, at the positions declared to hold one,
				// {handle} tokens and notification tags, and the code kept in the database - see
				// formulizeElementReferences
				$references = new formulizeElementReferences($elementObject->getVar('ele_id'), $original_handle, $elementObject->getVar('fid'));
				$this->renameWarnings = array_merge($this->renameWarnings, $references->rename($ele_handle));
				// Everything above rewrote element rows with SQL that went around the element objects: ele_value
				// in every element that links to this one or embeds a {handle} token, and ele_caption and ele_desc
				// wherever that token appears. Any of those already in the cache still holds the old text, and
				// there is no list of which ones changed, so the whole cache goes instead. A handle rename is
				// rare, and a cold cache costs a re-read; a stale one costs a linked element pointing at a handle
				// that no longer exists.
				$this->clearElementCache();
			}
		}
		return $this->renameWarnings;
	}

	/**
	 * What the last rename left for a person to update - see renameElementResources().
	 * @var array
	 */
	public $renameWarnings = array();

	/**
	 * Rename the code files an element keeps under its own name - <type>_<handle>.php in the code folder, for the
	 * types listed in elementCodeFileTypes() - and remove the cached derived value formulas named after its old
	 * handle, so they are regenerated under the new one.
	 *
	 * The old file is only carried across when it is still the truth. It is not, when the same save also set
	 * ele_value: the rename runs after insert(), which runs after the properties were set, so setVar has already
	 * put the file for the NEW handle into whatever state the content calls for - written when the content is code,
	 * deleted when it is not. Renaming the old file over that would put back content the admin had just changed,
	 * or restore code they had just removed. Every caller that sets ele_value sets the whole array (the admin
	 * Options tab posts it, and the MCP tools seed it from the element's current ele_value before overwriting the
	 * keys they were given), so ele_value being part of the save does mean setVar acted on the file.
	 *
	 * It cannot be decided by looking at the content instead. A handle-only rename reads exactly the same as a
	 * cleared one at this moment: the stored value was blanked by an earlier save and the file under the new handle
	 * does not exist yet, so the content reads back empty either way.
	 *
	 * A stale file is deleted rather than left behind. These names are per handle, so one sitting in the code
	 * directory under a handle nothing uses is picked up in full by the next element created with that handle -
	 * the file wins over the stored value on read - which is a worse outcome than not having it.
	 *
	 * Whether a file exists is read from the directory listing, by exact name, rather than asked of file_exists().
	 * On a file system that ignores case - Windows, and macOS by default - a rename that only changes case gives
	 * two names for one file, so file_exists() says the new one is there when it is really the old one, and the old
	 * one would be deleted as spent, taking the element's only copy of its code with it. The listing has the one
	 * name it really has.
	 *
	 * @param object $elementObject The element, with its new handle already set.
	 * @param string $originalHandle The handle before the rename, as it is written in the database.
	 * @param bool $eleValueWasSavedWithThisChange See renameElementResources().
	 * @return void
	 */
	protected function renameElementCodeFiles($elementObject, $originalHandle, $eleValueWasSavedWithThisChange) {
		$newHandle = $elementObject->getVar('ele_handle');
		$currentType = $elementObject->getVar('ele_type');
		$codeDir = XOOPS_ROOT_PATH.'/modules/formulize/code/';
		$codeFilesOnDisk = (array) scandir($codeDir);
		foreach(self::elementCodeFileTypes() as $type) {
			$oldName = $type.'_'.$originalHandle.'.php';
			$newName = $type.'_'.$newHandle.'.php';
			if(!in_array($oldName, $codeFilesOnDisk, true)) { continue; }
			$newExists = in_array($newName, $codeFilesOnDisk, true);
			if(!$newExists AND strtolower($oldName) === strtolower($newName)) {
				// Only the case is changing, and there is one file. Where case is ignored it is the same file under
				// either name, already holding whatever setVar wrote on this save, so it is never deleted - only
				// renamed, in two steps, since a rename to the same name differing only in case is not reliably
				// carried out in one.
				$temporaryName = $codeDir.$newName.'.renaming';
				if(rename($codeDir.$oldName, $temporaryName)) {
					rename($temporaryName, $codeDir.$newName);
				}
				continue;
			}
			// This element's own type is the one setVar wrote on this save, so the new-handle file is already right
			// and the old one is spent. A file named for one of the other types is left over from a type
			// conversion, which setVar knows nothing about, so that one still moves. A file already sitting under
			// the new name is the live one either way - the backstop for callers that do not know whether
			// ele_value was part of the save.
			if(($eleValueWasSavedWithThisChange AND $type === $currentType) OR $newExists) {
				unlink($codeDir.$oldName);
			} else {
				rename($codeDir.$oldName, $codeDir.$newName);
			}
		}
		$cacheDir = XOOPS_ROOT_PATH . '/modules/formulize/cache/';
		if(is_dir($cacheDir)) {
			foreach((array)glob($cacheDir . 'Derived_value_formula_for_' . $originalHandle . '*.php') as $cacheFile) {
				@unlink($cacheFile);
			}
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
	 * The syntaxes some kinds of code file have for naming elements, which a rename rewrites as well as what it
	 * rewrites in every code file: the handle as a quoted string where Formulize's functions take an element, and
	 * filter terms - see formulizeElementHandleCode::renameStrings(). A file whose name starts with none of these
	 * prefixes has only those. The prefix decides, and it is not a formality, because only some code has variables
	 * made for it that are named after handles, and then only for some elements:
	 * - a derived value formula, derived_<element handle>.php, gets a variable for any element whose handle it
	 *   names as one, in whatever form - see formulize_includeDerivedValueFormulas(), which looks each one up.
	 * - the save and delete procedures, on_before_save_<form handle>.php and the others, get a variable for each of
	 *   the elements of their own form, the values being saved or deleted - see the functions formulizeForm writes
	 *   around them. In another form's procedures, a variable of the same name is that procedure's own.
	 * - custom_edit_check, application code and custom button code get none, so a variable named like a handle is
	 *   somebody's own there, and renaming it would be editing unrelated logic.
	 *
	 * The content and default-value files are the mirror image. They are the PHP an element runs to make its
	 * content or its default value - fullWidthContent_<element handle>.php and captionedContent_<element handle>.php
	 * for static content, and text_, textarea_ and number_<element handle>.php for a default value - which names
	 * other elements by {handle} tokens: an element's handle in curly brackets, like {artifacts_year}. Before the code
	 * runs, Formulize swaps each token for a variable holding that element's value in the entry being displayed - see
	 * formulizeElementRenderer::evalAdminPHPWithReferences(). A token is looked up by its handle, whatever form the
	 * element is in, so like a formula's variable it is that element anywhere. Their $value and $default variables are
	 * what the code sets, the content or the default, and are not named after any element.
	 *
	 * @return array filename prefix => reference syntax used in that kind of file: 'variable' for any element,
	 *   'form variable' for the elements of the form the rest of the file name is the handle of, or 'token' for
	 *   {handle} tokens of any element
	 */
	public static function codeFileHandleSyntaxes() {
		return array(
			'derived_' => 'variable',
			'on_before_save_' => 'form variable',
			'on_after_save_' => 'form variable',
			'on_delete_' => 'form variable',
			'fullWidthContent_' => 'token',
			'captionedContent_' => 'token',
			'text_' => 'token',
			'textarea_' => 'token',
			'number_' => 'token',
		);
	}

	/**
	 * How a code file names an element of a form, in a syntax of its own - see codeFileHandleSyntaxes().
	 * @param string $fileName The code file's name.
	 * @param string $formHandle The handle of the element's form.
	 * @return string|false 'variable', 'token', or false for neither.
	 */
	public static function codeFileSyntax($fileName, $formHandle) {
		foreach(self::codeFileHandleSyntaxes() as $prefix => $syntax) {
			if(strpos($fileName, $prefix) === 0) {
				if($syntax == 'form variable') {
					return ($formHandle !== '' AND $fileName === $prefix.$formHandle.'.php') ? 'variable' : false;
				}
				return $syntax;
			}
		}
		return false;
	}

	/**
	 * The names no element handle can be, because Formulize keeps them for its own variables in the code that gets a
	 * variable for each handle - see formulizeElementHandleCode::reservedNames().
	 * @return array
	 */
	public static function reservedHandleNames() {
		xoops_getmodulehandler('templateScreen', 'formulize');
		return formulizeElementHandleCode::reservedNames(formulizeTemplateScreenHandler::RESERVED_TEMPLATE_VARIABLES);
	}

	/**
	 * Every element's handle, which a variable moved out of a renamed handle's way must not take, since it is or
	 * could be a variable named after that element.
	 * @return array
	 */
	private function allHandles() {
		$handles = array();
		if($result = $this->db->query("SELECT ele_handle FROM ".formulize_TABLE)) {
			while($row = $this->db->fetchArray($result)) {
				$handles[] = $row['ele_handle'];
			}
		}
		return $handles;
	}

	/**
	 * The screens whose own templates get variables named after the handles of a form's elements: list screens and
	 * template screens on that form, or on a relationship that includes it. In any other screen a variable of the
	 * same name is the author's own.
	 * @param int $formId The element's form.
	 * @return array sid => the screen.
	 */
	private static function screensSeeingForm($formId) {
		$screens = array();
		$screen_handler = xoops_getmodulehandler('screen', 'formulize');
		xoops_getmodulehandler('templateScreen', 'formulize'); // so its class is there to ask
		$form_relationship_handler = xoops_getmodulehandler('frameworks', 'formulize');
		foreach($screen_handler->getObjects(new Criteria('type', "('template','listOfEntries')", 'IN')) as $screen) {
			if($screen->getVar('type') == 'template') {
				$formIds = formulizeTemplateScreenHandler::formsWithHandleVariables($screen);
			} else {
				// a list's columns can come from any form of its relationship
				$formIds = array($screen->getVar('fid'));
				if($screen->getVar('frid') AND $formRelationship = $form_relationship_handler->get($screen->getVar('frid'))) {
					$formIds = array_merge($formIds, (array) $formRelationship->getVar('fids'));
				}
			}
			if(in_array(intval($formId), array_map('intval', $formIds), true)) {
				$screens[intval($screen->getVar('sid'))] = $screen;
			}
		}
		return $screens;
	}

	/**
	 * The variables Formulize names after a handle in a screen's templates, as what comes before the handle in the
	 * variable's name, for the screens that get them - see screensSeeingForm():
	 * - a template screen's code and template get a $handle variable for every element of the forms it shows -
	 *   see formulizeTemplateScreenHandler::formsWithHandleVariables() - the same as the procedures in
	 *   codeFileHandleSyntaxes().
	 * - a list screen's templates get a variable for each search box, named for the kind of box and the handle -
	 *   $quickSearchhandle, $quickDateRangehandle and the rest - see formulize_quickSearchTypeNames().
	 * @param string $screenType
	 * @return array The prefixes, '' for a variable that is the handle alone, or none.
	 */
	private static function templateVariablePrefixes($screenType) {
		$prefixes = array();
		if($screenType == 'template') {
			$prefixes[] = '';
		} elseif($screenType == 'listOfEntries') {
			foreach(formulize_quickSearchTypeNames() as $typeNames) {
				$prefixes = array_merge($prefixes, $typeNames['prefixes']);
			}
		}
		return $prefixes;
	}

	/**
	 * Every code file, and every screen's own template files - templates/screens/<theme>/<sid>/, which are the
	 * templates themselves - with what kind of code each is. The default templates every screen falls back on are
	 * left out: they are part of Formulize, and name no element.
	 * @param int $formId The element's form, which decides which screens get variables named after its handles.
	 * @return array path => label (where it is, for a person), group (the files sharing one set of variables: a
	 *   screen's templates, or one code file), prefixes (the variables named after a handle that Formulize gives it -
	 *   see formulizeElementHandleCode::variablePattern()), tokens (whether it holds {handle} tokens) and isCode
	 *   (whether it is PHP throughout, rather than text with PHP in it).
	 */
	private static function codeAndTemplateFiles($formId) {
		$files = array();
		$form_handler = xoops_getmodulehandler('forms', 'formulize');
		$formObject = $form_handler->get($formId);
		$formHandle = $formObject ? $formObject->getVar('form_handle') : '';
		$codeDir = XOOPS_ROOT_PATH.'/modules/formulize/code/';
		foreach((array) glob($codeDir.'*.php') as $path) {
			$syntax = self::codeFileSyntax(basename($path), $formHandle);
			$files[$path] = array(
				'label' => "custom code file '".basename($path)."'",
				'group' => $path,
				'prefixes' => $syntax == 'variable' ? array('') : array(),
				'tokens' => $syntax == 'token',
				'isCode' => true,
			);
		}
		$templatesDir = XOOPS_ROOT_PATH.'/modules/formulize/templates/screens/';
		$screensSeeingForm = self::screensSeeingForm($formId);
		$screen_handler = xoops_getmodulehandler('screen', 'formulize');
		foreach((array) glob($templatesDir.'*/*', GLOB_ONLYDIR) as $screenDir) {
			$sid = basename($screenDir);
			if(!is_numeric($sid) OR !$screen = $screen_handler->get(intval($sid))) {
				continue;
			}
			$prefixes = isset($screensSeeingForm[intval($sid)]) ? self::templateVariablePrefixes($screen->getVar('type')) : array();
			foreach((array) glob($screenDir.'/*') as $path) {
				if(is_file($path)) {
					$files[$path] = array(
						'label' => "screen template file '".substr($path, strlen($templatesDir))."'",
						'group' => 'screen '.intval($sid), // in every theme, so all its copies move a variable the same way
						'prefixes' => $prefixes,
						'tokens' => false,
						'isCode' => substr($path, -4) == '.php',
					);
				}
			}
		}
		return $files;
	}

	/**
	 * What renaming a handle makes of the code files and screen templates that name it - see codeAndTemplateFiles()
	 * for which files, and formulizeElementHandleCode for what in each is rewritten: the variables Formulize names
	 * after the handle where it gives them, {handle} tokens, and the strings that can only be the element. Writes
	 * nothing, so the usage report can ask what a rename would leave behind as well.
	 *
	 * A variable of the handle's name is only renamed where Formulize makes one, so never for a handle that is one of
	 * the names it keeps for itself - an element called 'value' from before those were refused, say, whose $value in
	 * a formula is the formula's result.
	 *
	 * @param string $originalHandle The handle as it was.
	 * @param string $newHandle The handle as it is to be.
	 * @param int $formId The element's form.
	 * @return array path => label, original and rewritten, for every file in a group that names the handle.
	 */
	private function handleRewritesInFiles($originalHandle, $newHandle, $formId) {
		$groups = array();
		foreach(self::codeAndTemplateFiles($formId) as $path => $file) {
			if(($contents = file_get_contents($path)) !== false) {
				$groups[$file['group']][$path] = $file + array('original' => $contents);
			}
		}
		$reserved = self::reservedHandleNames();
		$unavailable = null;
		$parameters = formulizeElementParameters::all();
		$rewrites = array();
		foreach($groups as $files) {
			$namesHandle = false;
			foreach($files as $file) {
				$namesHandle = $namesHandle || strpos($file['original'], $originalHandle) !== false;
			}
			if(!$namesHandle) {
				continue;
			}
			$texts = array();
			foreach($files as $path => $file) {
				$texts[$path] = $file['original'];
			}
			$prefixes = reset($files)['prefixes'];
			if(in_array($originalHandle, $reserved, true)) {
				$prefixes = array_values(array_diff($prefixes, array('')));
			}
			if($prefixes) {
				$unavailable = $unavailable ?? array_merge($this->allHandles(), $reserved);
				$texts = formulizeElementHandleCode::renameVariables($texts, $originalHandle, $newHandle, $prefixes, $unavailable);
			}
			foreach($files as $path => $file) {
				$text = $texts[$path];
				if($file['tokens']) {
					$text = formulizeElementHandleCode::renameTokens($text, $originalHandle, $newHandle);
				}
				$rewrites[$path] = array(
					'label' => $file['label'],
					'original' => $file['original'],
					'rewritten' => formulizeElementHandleCode::renameStrings($text, $originalHandle, $newHandle, $parameters, $file['isCode']),
				);
			}
		}
		return $rewrites;
	}

	/**
	 * Rewrite what names an element's handle in the code files and screen templates, when the handle changes - see
	 * handleRewritesInFiles() for what is rewritten - and say which of them still name the old handle afterwards, in
	 * a way that may or may not be the element and so is left for a person, and which could not be written at all.
	 * @param string $originalHandle The handle as it was.
	 * @param string $newHandle The handle as it now is.
	 * @param int $formId The element's form.
	 * @return array Where each file left naming the old handle is, for a person.
	 */
	public function renameHandleInFiles($originalHandle, $newHandle, $formId) {
		$leftByTheRename = array();
		foreach($this->handleRewritesInFiles($originalHandle, $newHandle, $formId) as $path => $rewrite) {
			if($rewrite['rewritten'] !== $rewrite['original']) {
				// a file the web server cannot write - one deployed by someone else, say - keeps all of the old handle,
				// and the person is told that, rather than that it only has what a rename leaves
				$isCodeFile = strpos($path, XOOPS_ROOT_PATH.'/modules/formulize/code/') === 0;
				if(!is_writable($path) OR ($isCodeFile
					? formulize_writeCodeToFile(basename($path), $rewrite['rewritten'])
					: file_put_contents($path, $rewrite['rewritten'])) === false) {
					$leftByTheRename[] = $rewrite['label'].formulizeElementHandleCode::NOT_WRITTEN;
					continue;
				}
				if(!$isCodeFile AND substr($path, -5) == '.html') {
					// a template screen's template, which Smarty compiles - see formulizeTemplateScreenHandler::recompileTemplate()
					formulizeTemplateScreenHandler::recompileTemplate($path);
				}
			}
			if(formulizeElementHandleCode::stillNamed($rewrite['rewritten'], $originalHandle)) {
				$leftByTheRename[] = $rewrite['label'];
			}
		}
		return $leftByTheRename;
	}

	/**
	 * The notification mail templates, in every language - language/<language>/mail_template/*.tpl - which name
	 * elements by the same {ELEMENT_HANDLE} tags as a notification's subject.
	 * @return array The paths.
	 */
	private static function mailTemplateFiles() {
		return array_filter((array) glob(XOOPS_ROOT_PATH.'/modules/formulize/language/*/mail_template/*.tpl'), 'is_file');
	}

	/**
	 * Rewrite the {ELEMENT_HANDLE} tags in the notification mail templates when an element's handle changes, as
	 * formulizeElementReferences does for the tags in a notification's subject.
	 * @param string $originalHandle The handle as it was.
	 * @param string $newHandle The handle as it now is.
	 * @return array Where each mail template that could not be written is, for a person.
	 */
	public function renameHandleInMailTemplates($originalHandle, $newHandle) {
		$notWritten = array();
		$oldTags = formulizeElementReferences::notificationTags($originalHandle);
		$newTags = formulizeElementReferences::notificationTags($newHandle);
		foreach(self::mailTemplateFiles() as $file) {
			$contents = file_get_contents($file);
			if($contents !== false AND ($newContents = str_replace($oldTags, $newTags, $contents)) !== $contents
				AND (!is_writable($file) OR @file_put_contents($file, $newContents) === false)) {
				$notWritten[] = self::mailTemplateLabel($file).formulizeElementHandleCode::NOT_WRITTEN;
			}
		}
		return $notWritten;
	}

	/**
	 * Where a notification mail template is, for a person.
	 * @param string $file Its path.
	 * @return string
	 */
	private static function mailTemplateLabel($file) {
		return "notification mail template '".basename(dirname(dirname($file))).'/'.basename($file)."'";
	}

	/**
	 * Remove every stored reference to an element from the settings that hold one, so that deleting an element
	 * does not leave configuration pointing at something that no longer exists.
	 *
	 * The places are found by formulizeElementReferences::references(), which the usage report reads too, so
	 * what the report promises to clean up and what is actually cleaned up cannot drift apart. Two things are
	 * not cleaned up here: the element's own column in the form's data table, and the pages of multipage
	 * screens, which delete() handles directly.
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
		foreach(formulizeElementReferences::forElement($elementObject)->references() as $reference) {
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
	 * Where is this element used, and what would deleting it cost?
	 *
	 * One report, two audiences: the admin interface shows it when someone asks where an element is used and
	 * again before they delete it, and the MCP delete tool returns it as its preview. It lives here rather
	 * than in either of those so the answer cannot differ between them, and it reads the same
	 * formulizeElementReferences::references() that removeElementReferences() acts on, so the report cannot promise a tidy up
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
		foreach(formulizeElementReferences::forElement($elementObject)->references() as $reference) {
			$sections[$reference['section']][] = $reference['description'];
		}
		$impact['cleaned_up_automatically'] = [];
		foreach($sections as $what => $items) {
			$impact['cleaned_up_automatically'][] = ['what' => $what, 'items' => $items];
		}

		// references that cannot be cleaned up automatically, because they are code rather than configuration
		if($broken = $this->codeReferencesToElement($elementId, $handle, $formId)) {
			$impact['references_in_code_that_you_will_need_to_fix'] = $broken;
			$impact['about_those_references'] = "Deleting an element clears it out of every setting that stores it as configuration - form screen pages and defaults, list screen columns and filters, map and calendar screen settings, custom button effects, display and read only conditions on other elements, dynamic default values, group visibility filters, entries-are-users settings, saved views, and any screen identifying entries by it in the address bar. The references above are different: they are places a person wrote this element's name into code or text - a formula on another element, a custom code file, a screen's template, a custom page or button, a notification's message. Removing a term from someone's code is not a change that can be made safely without knowing what the code is for, so these are left alone and are yours to fix. Renaming the element is different again: a rename updates the variables Formulize names after the handle, {handle} tokens, notification tags, and the handle written as a quoted string where Formulize's functions take an element, or starting a filter, and the references marked above as left for you are the ones it cannot.";
		}

		return $impact;
	}

	/**
	 * The places a person has written this element's handle into text or code, which deleting the element cannot fix.
	 *
	 * In the database, these are found by formulizeElementReferences::mentions(): {some_handle} tokens in
	 * captions, help text, default values, static content and the terms of conditions, a notification's
	 * {ELEMENT_SOME_HANDLE} tags, and code kept in the database. Every file in the code folder and every screen's
	 * own template files are read for the handle written as code can name it - see
	 * formulizeElementHandleCode::mentionPattern() - and the notification mail templates for its tags. Each one a
	 * rename would not entirely update is marked as such - see formulizeElementHandleCode::LEFT_BY_A_RENAME.
	 *
	 * @param int $elementId The element's id.
	 * @param string $handle The element's handle.
	 * @param int $formId The element's form.
	 * @return array The references, described for a person.
	 */
	private function codeReferencesToElement($elementId, $handle, $formId) {
		$broken = [];
		// text and code in the database naming this element - {handle} tokens, notification tags, code - at the
		// places formulizeElementReferences knows they can be
		$elementCodeFilesAlreadyReported = array();
		$references = new formulizeElementReferences($elementId, $handle, $formId);
		foreach($references->mentions() as $mention) {
			$broken[] = $mention['description'];
			if($mention['table'] == 'formulize') {
				// an element that keeps code in ele_value also has that code in a file of its own, named for
				// its type and handle. It is the same reference seen twice, so the file is not reported again
				// below - the element it belongs to has already been named, which is the more useful of the two.
				$elementCodeFilesAlreadyReported[$mention['row']['ele_type'].'_'.$mention['row']['ele_handle'].'.php'] = true;
			}
		}

		// every code file and every screen's own template files that name the handle, including the code files
		// written for derived value and static content elements, which live in the same folder. Found the way a person
		// can tell - see formulizeElementHandleCode::mentionPattern() - which is broader than what a rename rewrites, and
		// for each one whether a rename would leave some of it for a person, asked of the rename itself.
		foreach($this->handleRewritesInFiles($handle, formulizeElementHandleCode::RENAME_CHECK_HANDLE, $formId) as $path => $rewrite) {
			if(isset($elementCodeFilesAlreadyReported[basename($path)])
				OR (!formulizeElementHandleCode::stillNamed($rewrite['original'], $handle) AND $rewrite['rewritten'] === $rewrite['original'])) {
				continue;
			}
			$broken[] = $rewrite['label'].' refers to this element by name'
				.(formulizeElementHandleCode::stillNamed($rewrite['rewritten'], $handle) ? formulizeElementHandleCode::LEFT_BY_A_RENAME : '');
		}

		// notification mail templates, which name it by the tags a notification's subject uses
		$tags = formulizeElementReferences::notificationTags($handle);
		foreach(self::mailTemplateFiles() as $mailTemplate) {
			$contents = file_get_contents($mailTemplate);
			if($contents !== false AND str_replace($tags, '', $contents) !== $contents) {
				$broken[] = self::mailTemplateLabel($mailTemplate)." refers to this element by name";
			}
		}

		return $broken;
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
		list($form_id, $element_handle) = formulize_linkedSourceParts($ele_value[2]);
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
// which is declared above. See the note beside the other requires at the top.
require_once XOOPS_ROOT_PATH . "/modules/formulize/class/userAccountElement.php";
