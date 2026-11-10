<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2011 Freeform Solutions                  ##
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
##  Author of this file: Freeform Solutions                                  ##
##  Project: Formulize                                                       ##
###############################################################################

// There is a corresponding admin template for this element type in the templates/admin folder

require_once XOOPS_ROOT_PATH . "/modules/formulize/class/elements.php"; // you need to make sure the base element class has been read in first!
require_once XOOPS_ROOT_PATH . "/modules/formulize/include/functions.php";
require_once XOOPS_ROOT_PATH . "/modules/formulize/include/griddisplay.php";

class formulizeGridElement extends formulizeElement {

	public static $category = "table";

	function __construct() {
		$this->name = "Table of elements";
		$this->hasData = false; // set to false if this is a non-data element, like the subform or the grid
		$this->needsDataType = false; // set to false if you're going force a specific datatype for this element using the overrideDataType
		$this->overrideDataType = ""; // use this to set a datatype for the database if you need the element to always have one (like 'date').  set needsDataType to false if you use this.
		$this->adminCanMakeRequired = false; // set to true if the webmaster should be able to toggle this element as required/not required
		$this->alwaysValidateInputs = false; // set to true if you want your custom validation function to always be run.  This will override any required setting that the webmaster might have set, so the recommendation is to set adminCanMakeRequired to false when this is set to true.
		$this->canHaveMultipleValues = false;
		$this->hasMultipleOptions = false;
		parent::__construct();
	}

	/**
	 * Static function to provide the mcp server with the schema for the properties that can be used with the create_form_element and update_form_element tools
	 * Concerned with the properties for the ele_value property of the element object
	 * Follows the convention of properties used publically (MCP, Public API, etc).
	 * @param bool|int $update True if this is being called as part of building the properties for Updating, as opposed to properties for Creating. Default is false (Creating).
	 * @return string The schema for the properties that can be used with the create_form_element and update_form_element tools
	 */
	public static function mcpElementPropertiesDescriptionAndExamples($update = false) {
		return
"**Element:** Table of elements (grid).
**Description:** A table that contains elements from the form, so they can be displayed together in rows and/or columns. This is useful for display first name/last name boxes, or parts of addresses, like province and postal code, etc.
**Properties:**
- initialElementId (Required. The element id of the first element in the table. This element will appear in the upper left corner, and the remaining spaces in the table will be filled by the subsequent elements in the form in order.)
- numberOfRows (Required. The number of rows in the table.)
- numberOfColumns (Required. The number of columns in the table.)
- rowLabels (Optional. Comma separated list of labels, one for each row in the table.)
- columnLabels (Optional. Comma separated list of labels for the columns in the table.)
**Examples:**
- A table to show first name and last name beside each other in the form: { initialElementId: 12, numberOfRows: 1, numberOfColumns: 2, rowLabels: \"\", columnLabels: \"First Name, Last Name\" }
- A table to show province and postal code beside each other in the form: { initialElementId: 15, numberOfRows: 1, numberOfColumns: 2, rowLabels: \"\", columnLabels: \"Province, Postal Code\" }
- A table to show five preferences in a single column all together in the form, with no labels (four commas will mean five rows with no text labels): { initialElementId: 20, numberOfRows: 5, numberOfColumns: 1 }
- A table with three rows, each row gives users a choice of meals, a gluten-free yes/no option, and a choice of music. The yes/no option is self-explanatory and so has no column label: { initialElementId: 25, numberOfRows: 3, numberOfColumns: 3, rowLabels: \"Option 1, Option 2, Option 3\", columnLabels: \"Meals, , Music\" }";
	}

}

#[AllowDynamicProperties]
class formulizeGridElementHandler extends formulizeElementsHandler {

	/**
	 * Where a grid's ele_value refers to another element - see formulizeElementReferences. Key 4 is the
	 * element it takes its initial values from, blanked to 'none', the value the editor writes for nothing chosen.
	 */
	public static $eleValueReferences = array(
		// the row and column captions, which can carry {handle} tokens - see displayGrid()
		1 => array('shape' => 'tokens'),
		2 => array('shape' => 'tokens'),
		4 => array('shape' => 'value', 'holds' => 'either', 'on_delete' => 'set', 'set_to' => 'none',
			'describe' => 'the element it takes its initial values from'),
	);

	var $db;
	var $clickable; // used in formatDataForList
	var $striphtml; // used in formatDataForList
	var $length; // used in formatDataForList

	function __construct($db) {
		$this->db =& $db;
	}

	function create() {
		return new formulizeGridElement();
	}

	/**
	 * Validate properties for this element type, based on the structure used publically (MCP, Public API, etc).
	 * The description in the mcpElementPropertiesDescriptionAndExamples static method on the element class, follows this convention
	 * properties are the contents of the ele_value property on the object
	 * @param array $properties The properties to validate
	 * @param array $ele_value The ele_value settings for this element, if applicable. Should be set by the caller, to the current ele_value settings of the element, if this is an existing element.
	 * @param int|string|object $elementIdentifier The element id, handle or object of the element for which we're validating the properties.
	 * @return array An array of properties ready for the object. Usually just ele_value but could be others too.
	 */
	public function validateEleValuePublicAPIProperties($properties, $ele_value = [], $elementIdentifier = null) {
		if($ele_value[1] == '' AND $ele_value[2] == '' AND (!isset($properties['numberOfRows']) OR !isset($properties['numberOfColumns']))) {
			throw new Exception("You must specify at least one row and one column for the grid.");
		}
		if(!$ele_value[4] AND !isset($properties['initialElementId'])) {
			throw new Exception("You must specify the initialElementId property to indicate the first element in the grid.");
		}
		if(isset($properties['initialElementId'])) {
			$ele_value[4] = intval($properties['initialElementId']);
		}
		if(isset($properties['numberOfRows']) AND isset($properties['numberOfColumns'])) {
			$passedRowLabels = isset($properties['rowLabels']) ? explode(",", $properties['rowLabels']) : [];
			$passedColumnLabels = isset($properties['columnLabels']) ? explode(",", $properties['columnLabels']) : [];
			$rowLabels = [];
			$colLabels = [];
			for($row = 0; $row < intval($properties['numberOfRows']); $row++) {
				$rowLabels[] = isset($passedRowLabels[$row]) ? trim($passedRowLabels[$row]) : "";
			}
			for($col = 0; $col < intval($properties['numberOfColumns']); $col++) {
				$colLabels[] = isset($passedColumnLabels[$col]) ? trim($passedColumnLabels[$col]) : "";
			}
			$ele_value[1] = implode(",", $rowLabels);
			$ele_value[2] = implode(",", $colLabels);
		}
		return ['ele_value' => $ele_value ];
	}

	public function getDefaultEleValue() {
		// 0 - string - use heading from: caption or form or none
		// 1 - row captions, comma separated
		// 2 - column captions, comma separated
		// 3 - string - alternate shading in the grid: horizontal (rows) or vertical (columns) (no effect in Anari?)
		// 4 - starting element id
		// 5 - heading at side (1) or above (0)
		$ele_value = array(
			0 => "caption",
			1 => "",
			2 => "",
			3 => "horizontal",
			4 => 0,
			5 => 1
		);
		return $ele_value;
	}

	/**
	 * Take data representing an element's properties, and convert any handles to numeric ids
	 * @param array $elementData An associative array of form data, following the form object structure
	 * @param array $dependencyIdToHandleMap An array mapping numeric element ids to element handles
	 * @return array The modified $formData with numeric dependencies converted to handles
	 */
	public function convertEleValueDependenciesForImport($eleValueData, $dependencyIdToHandleMap) {
		if($initialElementObject = _getElementObject($eleValueData[4])) {
			$eleValueData[4] = $initialElementObject->getVar('ele_id');
		}
		return $eleValueData;
	}

	/**
	 * Take data representing an element's properties, and convert any numeric id refs to handles
	 * @param array $elementData An associative array of form data, following the form object structure
	 * @param array $dependencyIdToHandleMap An array mapping numeric element ids to element handles
	 * @return array The modified $formData with numeric dependencies converted to handles
	 */
	public function convertEleValueDependenciesForExport($eleValueData, $dependencyIdToHandleMap) {
		if($initialElementObject = _getElementObject($eleValueData[4])) {
			$eleValueData[4] = $initialElementObject->getVar('ele_handle');
		}
		return $eleValueData;
	}

	/**
	 * Check an array, structured as ele_value would be structured, and return an array of elements that the element depends on
	 * @param array $values The ele_value array to check for dependencies - numeric element refs ought to have been replaced with handles, when this data was created
	 * @return array An array of element handles that this element depends on
	 */
	public function getEleValueDependencies($values) {
		$dependencies = array();
		if(is_numeric($values[4])) {
			if($initialElementObject = _getElementObject($values[4])) {
				$dependencies[] = $initialElementObject->getVar('ele_handle');
			}
		} elseif($values[4] AND $values[4] != 'none') {
			$dependencies[] = $values[4];
		}
		return $dependencies;
	}

	// this method would gather any data that we need to pass to the template, besides the ele_value and other properties that are already part of the basic element class
	// it receives the element object and returns an array of data that will go to the admin UI template
	// when dealing with new elements, $element might be FALSE
	// can organize template data into two top level keys, advanced-tab-values and options-tab-values, if there are some options for the element type that appear on the Advanced tab in the admin UI. This requires an additional template file with _advanced.html as the end of the name. Text elements have an example.
	function adminPrepare($element) {
		$dataToSendToTemplate = array();
		if(is_object($element) AND is_subclass_of($element, 'formulizeElement')) { // existing element
			$ele_value = $element->getVar('ele_value');
			$dataToSendToTemplate['background'] = $ele_value[3];
			$dataToSendToTemplate['heading'] = $ele_value[0];
			$dataToSendToTemplate['sideortop'] = $ele_value[5] == 1 ? "side" : "above";
			$dataToSendToTemplate[4] = $ele_value[4];
		} else { // new element
			$dataToSendToTemplate['background'] = "horizontal";
			$dataToSendToTemplate['sideortop'] = "side";
			$dataToSendToTemplate['heading'] = "caption";
			$dataToSendToTemplate[4] = 0;
		}
		$grid_elements_criteria = new Criteria('');
		$grid_elements_criteria->setSort('ele_order');
		$grid_elements_criteria->setOrder('ASC');
		$fid = is_object($element) ? $element->getVar('fid') : $_GET['fid'];
		$grid_elements = $this->getObjects($grid_elements_criteria, $fid);
		foreach($grid_elements as $this_element) {
				$grid_start_options[$this_element->getVar('ele_id')] = $this_element->getVar('ele_colhead') ? printSmart(trans($this_element->getVar('ele_colhead'))) : printSmart(trans($this_element->getVar('ele_caption')));
		}
		$dataToSendToTemplate['grid_start_options'] = $grid_start_options;
		return $dataToSendToTemplate;
	}

	// this method would read back any data from the user after they click save in the admin UI, and save the data to the database, if it were something beyond what is handled in the basic element class
	// this is called as part of saving the options tab.  It receives a copy of the element object immediately prior to it being saved, so the element object will have all its properties set as they would be based on the user's changes in the names & settings tab, and in the options tab (the tabs are saved in order from left to right).
	// the exception is the special ele_value array, which is passed separately from the object (this will contain the values the user set in the Options tab)
	// You can modify the element object in this function and since it is an object, and passed by reference by default, then your changes will be saved when the element is saved.
	// You should return a flag to indicate if any changes were made, so that the page can be reloaded for the user, and they can see the changes you've made here.
	// advancedTab is a flag to indicate if this is being called from the advanced tab (as opposed to the Options tab, normal behaviour). In this case, you have to go off first principals based on what is in $_POST to setup the advanced values inside ele_value (presumably).
	function adminSave($element, $ele_value = array(), $advancedTab = false) {
		$element->setVar('ele_value', $ele_value);
		return false;
	}

	// override the insert method so that we can do special stuff with grid elements
	function insert(&$element, $force = false) {

		// position the grid immediately before the first element that's in the grid
		// have to figure out the preceeding element, then request the figureOutOrder with that element's id
		$position = 'top';
		$oldOrder = $element->getVar('ele_order');
		$ele_value = $element->getVar('ele_value');
		$fid = $element->getVar('fid');
		if(is_array($ele_value) AND isset($ele_value[4]) AND $ele_value[4] AND $firstGridElement = _getElementObject($ele_value[4])) {
			$sql = "SELECT ele_id, ele_order FROM ".$this->db->prefix("formulize")." WHERE id_form = ".intval($fid)." AND ele_order < ".intval($firstGridElement->getVar('ele_order'))." ORDER BY ele_order DESC LIMIT 0,1";
			if($res = $this->db->query($sql)) {
				if($this->db->getRowsNum($res) == 1) {
					$array = $this->db->fetchArray($res);
					$position = $array['ele_id'];
					$oldOrder = $array['ele_order'];
				}
			}
		}
		$element->setVar('ele_order', figureOutOrder($position, $oldOrder, $fid));

		// The grid gives its display conditions to the elements in it, below. When the starting element or the size
		// changes, the elements that are no longer in the grid would keep those conditions, so find them before saving.
		$elementsLeavingGrid = array();
		$savedGridFilterSettings = array();
		if($gridId = intval($element->getVar('ele_id')) AND $savedGrid = $this->get($gridId, true)) {
			$savedGridFilterSettings = $savedGrid->getVar('ele_filtersettings');
			$savedEleValue = $savedGrid->getVar('ele_value');
			if($this->hasConditions($savedGridFilterSettings) AND is_array($savedEleValue) AND $savedEleValue[4] AND $savedEleValue[4] !== 'none') {
				$savedElements = elementsInGrid($savedEleValue[4], $fid, $this->gridCount($savedEleValue));
				$currentElements = (is_array($ele_value) AND $ele_value[4] AND $ele_value[4] !== 'none') ? elementsInGrid($ele_value[4], $fid, $this->gridCount($ele_value)) : array();
				$elementsLeavingGrid = array_diff($savedElements, $currentElements);
			}
		}

		// do the insert the normal way
		if($result = parent::insert($element, $force)) {

			// take the grid's display conditions off elements that have left it. Only conditions that match the grid's
			// are removed. Any others were set on the element itself.
			$gridFilterSettings = $element->getVar('ele_filtersettings');
			foreach($elementsLeavingGrid as $formerGridElementId) {
				if($formerGridElementObject = $this->get($formerGridElementId)) {
					$formerGridElementFilterSettings = $formerGridElementObject->getVar('ele_filtersettings');
					if($this->hasConditions($formerGridElementFilterSettings) AND ($formerGridElementFilterSettings == $savedGridFilterSettings OR $formerGridElementFilterSettings == $gridFilterSettings)) {
						$formerGridElementObject->setVar('ele_filtersettings', "");
						if(!parent::insert($formerGridElementObject)) {
							throw new Exception(sprintf(_AM_ELE_GRID_CONDITIONS_NOT_CLEARED, $this->elementLabel($formerGridElementObject))."\n");
						}
					}
				}
			}

			// now propagate display settings to constituent elements
			// if grid has filter settings...
			if($this->hasConditions($gridFilterSettings)) {
				foreach(elementsInGrid($ele_value[4], $element->getVar('id_form'), $this->gridCount($ele_value)) as $gridElementId) {
					$gridElementObject = $this->get($gridElementId);
					// if constitiuent element has no filter settings...
					if(!$this->hasConditions($gridElementObject->getVar('ele_filtersettings'))) {
						$gridElementObject->setVar('ele_filtersettings', $gridFilterSettings);
						if(!parent::insert($gridElementObject)) {
							throw new Exception(sprintf(_AM_ELE_GRID_CONDITIONS_NOT_APPLIED, $this->elementLabel($gridElementObject))."\n");
						}
					}
				}
			}
		}
		return $result;
	}

	/**
	 * Check whether a set of display conditions, as stored in ele_filtersettings, has any conditions in it
	 * @param mixed $filterSettings The ele_filtersettings value of an element
	 * @return bool True if there is at least one condition
	 */
	private function hasConditions($filterSettings) {
		return (is_array($filterSettings) AND isset($filterSettings[0]) AND is_array($filterSettings[0]) AND count($filterSettings[0]) > 0);
	}

	/**
	 * Work out how many elements are in a grid, from its row and column captions
	 * @param array $ele_value The ele_value of the grid
	 * @return int The number of cells in the grid
	 */
	private function gridCount($ele_value) {
		return count(explode(",", $ele_value[1])) * count(explode(",", $ele_value[2]));
	}

	/**
	 * Get the label an admin knows an element by: its column heading if it has one, otherwise its caption
	 * @param object $elementObject The element
	 * @return string The label
	 */
	private function elementLabel($elementObject) {
		return $elementObject->getVar('ele_colhead') ? $elementObject->getVar('ele_colhead') : $elementObject->getVar('ele_caption');
	}

	/**
	 * Returns the default value for this element, for a new entry in the specified form.
	 * Determines database ready values, not necessarily human readable values
	 * @param object $element The element object
	 * @param int|string $entry_id 'new' or the id of an entry we should use when evaluating the default value - only relevant when determining second pass at defaults when subform entries are written? (which would be better done by comprehensive conditional rendering?)
	 * @return mixed The default value
	 */
	function getDefaultValue($element, $entry_id = 'new') {
		return;
	}

	// this method reads the current state of an element based on the user's input, and the admin options, and sets ele_value to what it needs to be so we can render the element correctly
	// it must return $ele_value, with the correct value set in it, so that it will render as expected in the render method
	// $element is the element object
	// $value is the value that was retrieved from the database for this element in the active entry.  It is a raw value, no processing has been applied, it is exactly what is in the database (as prepared in the prepareDataForSaving method and then written to the DB)
	// $entry_id is the ID of the entry being loaded
	function loadValue($element, $value, $entry_id) {
		return;
	}

	// this method renders the element for display in a form
	// the caption has been pre-prepared and passed in separately from the element object
	// if the element is disabled, then the method must take that into account and return a non-interactable label with some version of the element's value in it
	// $ele_value is the options for this element - which will either be the admin values set by the admin user, or will be the value created in the loadValue method
	// $caption is the prepared caption for the element
	// $markupName is what we have to call the rendered element in HTML
	// $isDisabled flags whether the element is disabled or not so we know how to render it
	// $element is the element object
	// $entry_id is the ID number of the entry where this particular element comes from
	// $screen is the screen object that is in effect, if any (may be null)
	function render($ele_value, $caption, $markupName, $isDisabled, $element, $entry_id, $screen=false, $owner=null) {
		$gridCount = count(explode(",", $ele_value[1])) * count(explode(",", $ele_value[2]));
		$elementsInGrid = elementsInGrid($ele_value[4], $element->getVar('fid'), $gridCount);
		$prevEntry = getEntryValues($entry_id, $element->getVar('fid'), $elementsInGrid);
		return renderGrid($element, $entry_id, $prevEntry, $screen);
	}

	// this method will read what the user submitted, and package it up however we want for insertion into the form's datatable
	// You can return null to cause a null value to be saved in the database
	// $value is what the user submitted
	// $element is the element object
	// $entry_id is the ID number of the entry that this data is being saved into. Can be "new", or null in the event of a subformblank entry being saved.
	// $subformBlankCounter is the counter for the subform blank entries, if applicable
	function prepareDataForSaving($value, $element, $entry_id=null, $subformBlankCounter=null) {
		return;
	}

	// this method will handle any final actions that have to happen after data has been saved
	// this is typically required for modifications to new entries, after the entry ID has been assigned, because before now, the entry ID will have been "new"
	// value is the value that was just saved
	// element_id is the id of the element that just had data saved
	// entry_id is the entry id that was just saved
	// ALSO, $GLOBALS['formulize_afterSavingLogicRequired']['elementId'] = type , must be declared in the prepareDataForSaving step if further action is required now -- see fileUploadElement.php for an example
	function afterSavingLogic($value, $element_id, $entry_id) {
	}

	// this method will prepare a raw data value from the database, to be included in a dataset when formulize generates a list of entries or the getData API call is made
	// in the standard elements, this particular step is where multivalue elements, like checkboxes, get converted from a string that comes out of the database, into an array, for example
	// $value is the raw value that has been found in the database
	// $handle is the element handle for the field that we're retrieving this for
	// $entry_id is the entry id of the entry in the form that we're retrieving this for
	function prepareDataForDataset($value, $handle, $entry_id) {
		return $value;
	}

	// this method will take a text value that the user has specified at some point, and convert it to a value that will work for comparing with values in the database.  This is used primarily for preparing user submitted text values for saving in the database, or for comparing to values in the database, such as when users search for things.  The typical user submitted values would be coming from a condition form (ie: fieldX = [term the user typed in]) or other situation where the user types in a value that needs to interact with the database.
	// it is only necessary to do special logic here if the values stored in the database do not match what users would be typing, ie: you're using coded numbers in the database, but displaying text on screen to users
	// this would be where a Yes value would be converted to a 1, for example, in the case of a yes/no element, since 1 is how yes is represented in the database for that element type
	// $partialMatch is used to indicate if we should search the values for partial string matches, like On matching Ontario.  This happens in the gatherDataset function when processing filter terms (ie: searches typed by users in a list of entries)
	// if $partialMatch is true, then an array may be returned, since there may be more than one matching value, otherwise a single value should be returned.
	// if literal text that users type can be used as is to interact with the database, simply return the $value
	// LINKED ELEMENTS AND UITEXT ARE RESOLVED PRIOR TO THIS METHOD BEING CALLED
	function prepareLiteralTextForDB($value, $element, $partialMatch=false) {
		return;
	}

	// this method will format a dataset value for display on screen when a list of entries is prepared
	// for standard elements, this step is where linked selectboxes potentially become clickable or not, among other things
	// Set certain properties in this function, to control whether the output will be sent through a "make clickable" function afterwards, sent through an HTML character filter (a security precaution), and trimmed to a certain length with ... appended.
	function formatDataForList($value, $handle="", $entry_id=0, $textWidth=100) {
		return;
	}

}


