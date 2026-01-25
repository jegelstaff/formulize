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

class formulizeSubformListingsElement extends formulizeElement {

	var $defaultValueKey;
	public static $category = "subforms";

	function __construct() {
		$this->name = "Embeded Form (list view)";
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
		$descriptionAndExamples = "
**Subform Interface Type:** Listings (subformListings).
**Description:** This Subform Interface provides a list view of connected entries. Each entry shows up as a row in a table, with a clickable icon to open up the full entry for viewing or editing. This is best for situations when users simply need to see a listing of entries, and/or when forms have a too many elements for comfortably showing in editable rows (generally more than 5).
**Properties:**
- all the common properties for Subform Interfaces, plus:
- elementsInRow (Required. An array of element ids, indicating which elements from the source form should be shown in the list view. The values of these elements will be shown in each row. The values will not be editable, they will be shown as plain text.)
- entryViewingMode (Optional. A string, either 'off', 'form_screen' or 'modal'. If 'off', then there are no clickable icons for opening up each connected entry for viewing/editing. If 'full_screen' then there are clickable icons, and they will cause the page to reload with the correct Form Screen for showing the connected entry. If 'modal' then there are clickable icons, and they will open a modal popup box for showing the connected entry. Default is 'full_screen'. For small forms, 'modal' is usually best. For large forms, 'full_screen' is usually best. If a user should not be able to view/edit the embedded entries, or does not need to, then set this to 'off'.
**Example:**
- A 'Listings' Subform Interface that shows the values of elements 101, 102, 103, and 104, from connected entries in form 97. Sort the entries by the value of element 101. Open entries in a modal popup for viewing/editing: { sourceForm: 97, elementsInRow: [101, 102, 103, 104], sortingElement: 101, entryViewingMode: 'modal' }";
		return $descriptionAndExamples;
	}

}

#[AllowDynamicProperties]
class formulizeSubformListingsElementHandler extends formulizeElementsHandler {

	var $db;
	var $clickable; // used in formatDataForList
	var $striphtml; // used in formatDataForList
	var $length; // used in formatDataForList
	var $defaultValueKey;
	var $associatedElementKey;

	function __construct($db) {
		$this->db =& $db;
	}

	function create() {
		return new formulizeSubformListingsElement();
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

		$elementTypeName = strtolower(str_ireplace(['formulize', 'element'], "", static::class));

		if(isset($properties['sourceForm']) AND $properties['sourceForm'] > 0) {
			$form_handler = xoops_getmodulehandler('forms', 'formulize');
			$sourceFid = intval($properties['sourceForm']);
			if($sourceFormObject = $form_handler->get($sourceFid)) {
				$ele_value[0] = $sourceFid;
				$existingConnection = false;
				if($sourceFid AND $elementObject = _getElementObject($elementIdentifier)) {
					$connection_handler = xoops_getmodulehandler('frameworks', 'formulize');
					if($connections = $connection_handler->getLinksGroupedByForm($connection_handler->get(-1), $elementObject->getVar('fid'))) {
						foreach($connections[$elementObject->getVar('fid')] as $connection) {
							if($connection['form2'] == $sourceFid) {
								$existingConnection = true;
								break;
							}
						}
					}
				}
				if($sourceFid AND $elementObject AND !$existingConnection) {
					$formObject = $form_handler->get($elementObject->getVar('fid'));
					if($pi = $formObject->getVar('pi')) {
						if($newLinkedElementId = makeNewConnectionElement('new-linked-dropdown', $sourceFid, $pi)) {
							// if it's a row-based subform with no specific screen set, then let's try creating a new subform screen for displaying the sub entries
							if($ele_value[8] == 'row' AND $ele_value['display_screen'] == 0 AND $newSubformScreenId = findOrMakeSubformScreen($newLinkedElementId, $elementObject->getVar('fid'))) {
								$ele_value['display_screen'] = $newSubformScreenId;
							}
						}
					} else {
						throw new Exception("There is no Principal Identifier set for '".$formObject->getVar('title')."' (form ".$formObject->getVar('fid').") . The default connection between '".$formObject->getVar('title')."' (form ".$formObject->getVar('fid').") and '".$sourceFormObject->getVar('title')."' (form ".$sourceFormObject->getVar('fid').") requires a Principal Identifier in '".$formObject->getVar('title')."' (form ".$formObject->getVar('fid')."), in order to create a new linked element in '".$sourceFormObject->getVar('title')."' (form ".$sourceFormObject->getVar('fid')."), which will connect the two forms. Set an existing element in '".$formObject->getVar('title')."' (form ".$formObject->getVar('fid').") to be its Principal Indentifier, or create a new element as the Principal Identifier, and then try creating the Subform Interface again.");
					}
				}
			} else {
				throw new Exception("You must provide a valid sourceForm property for the subform listings element. The sourceForm you provided does not exist.");
			}
		}
		if(isset($properties['sortingElement']) AND $properties['sortingElement'] > 0) {
			$ele_value['SortingElement'] = intval($properties['sortingElement']);
		}
		if(isset($properties['sortingDirection']) AND in_array($properties['sortingDirection'], ['ASC','DESC'])) {
			$ele_value['SortingDirection'] = $properties['sortingDirection'];
		}
		if(isset($properties['showAddButton'])) {
			$ele_value[6] = $properties['showAddButton'] ? 'subform' : 'hideaddentries';
		}
		if(isset($properties['showDeleteButton'])) {
			$ele_value['ShowDeleteButton'] = ($properties['showDeleteButton']) ? 1 : 0;
		}
		if((isset($properties['elementsInRow']) AND is_array($properties['elementsInRow']) AND count($properties['elementsInRow']) > 0) OR (isset($properties['elementsInHeading']) AND is_array($properties['elementsInHeading']) AND count($properties['elementsInHeading']) > 0)) {
			$elementsArray = isset($properties['elementsInRow']) ? $properties['elementsInRow'] : $properties['elementsInHeading'];
			$ele_value[1] = implode(',', array_map('intval', $elementsArray));
		}
		if(isset($properties['disabledElementsInRow']) AND is_array($properties['disabledElementsInRow']) AND count($properties['disabledElementsInRow']) > 0) {
			$ele_value['disabledelements'] = implode(',', array_map('intval', $properties['disabledElementsInRow']));
		}
		if(isset($properties['entryViewingMode']) AND in_array($properties['entryViewingMode'], ['off','form_screen','modal'])) {
			switch($properties['entryViewingMode']) {
				case 'off':
					$ele_value[3] = 0;
					break;
				case 'form_screen':
					$ele_value[3] = $elementTypeName == 'subformeditablerow' ? 4 : 1;
					break;
				case 'modal':
					$ele_value[3] = $elementTypeName == 'subformeditablerow' ? 3 : 2;
					break;
			}
		}
		if(isset($properties['fullFormMode']) AND in_array($properties['fullFormMode'], ['collapsable','not_collapsable'])) {
			$ele_value[8] = $properties['fullFormMode'];
		}
		return [
			'ele_value' => $ele_value,
		];
	}

	/**
	 * Take data representing an element's properties, and convert any handle refs to numeric ids
	 * Premise is that everything exists in the database now or else this won't work
	 * @param array $elementData An associative array of form data, following the form object structure
	 * @param array $dependencyIdToHandleMap An array mapping numeric element ids to element handles
	 * @return array The modified $formData with numeric dependencies converted to handles
	 */
	public function convertEleValueDependenciesForImport($eleValueData, $dependencyIdToHandleMap) {

		// display_screen todo when screens supported

		// 0 is form id
		$formHandler = xoops_getmodulehandler('forms', 'formulize');
		$formObject = $formHandler->get($eleValueData[0]);
		$eleValueData[0] = $formObject->getVar('fid');

		// disabledelements and 1 are comma separated element ids
		foreach(array('disabledelements', 1) as $key) {
			$elementIdsArray = array();
			if(isset($eleValueData[$key])) {
				$items = is_array($eleValueData[$key]) ? $eleValueData[$key] : explode(',', $eleValueData[$key]);
				foreach($items as $elementHandle) {
					if($id = array_search($elementHandle, $dependencyIdToHandleMap)) {
						$elementIdsArray[] = $id;
					}
				}
			} else {
				$eleValueData[$key] = '';
			}
			$eleValueData[$key] = count($elementIdsArray) > 0 ? implode(",", $elementIdsArray) : $eleValueData[$key];
		}

		$eleValueData[7] = $this->formulize_convertFilterDependenciesToIds($eleValueData[7], $dependencyIdToHandleMap);

		foreach(array(
			'subform_prepop_element',
			'SortingElement',
			'UserFilterByElement') as $key) {
				if(isset($eleValueData[$key])) {
					$eleValueData[$key] = $this->convertElementRefsToIds($eleValueData[$key], $dependencyIdToHandleMap);
				}
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

		// display_screen todo when screens supported

		// 0 is form id
		$formHandler = xoops_getmodulehandler('forms', 'formulize');
		$formObject = $formHandler->get($eleValueData[0]);
		$eleValueData[0] = $formObject->getVar('form_handle');

		// disabledelements and 1 are comma separated element ids
		foreach(array('disabledelements', 1) as $key) {
			$elementHandlesArray = array();
			if(isset($eleValueData[$key])) {
				$items = is_array($eleValueData[$key]) ? $eleValueData[$key] : explode(',', $eleValueData[$key]);
				foreach($items as $elementId) {
					if(isset($dependencyIdToHandleMap[intval($elementId)])) {
						$elementHandlesArray[] = $dependencyIdToHandleMap[intval($elementId)];
					}
				}
			} else {
				$eleValueData[$key] = '';
			}
			$eleValueData[$key] = count($elementHandlesArray) > 0 ? implode(",", $elementHandlesArray) : $eleValueData[$key];
		}

		$eleValueData[7] = $this->formulize_convertFilterDependenciesToHandles($eleValueData[7], $dependencyIdToHandleMap);

		foreach(array(
			'subform_prepop_element',
			'SortingElement',
			'UserFilterByElement') as $key) {
				if(isset($eleValueData[$key])) {
					$eleValueData[$key] = $this->convertElementRefsToHandles($eleValueData[$key], $dependencyIdToHandleMap);
				}
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

		// disabledelements and 1 are comma separated element ids
		foreach(array('disabledelements', 1) as $key) {
			if(isset($values[$key])) {
				$elementHandlesArray = array();
				$items = is_array($values[$key]) ? $values[$key] : explode(',', $values[$key]);
				foreach($items as $elementIdentifier) {
					if(is_numeric($elementIdentifier)) {
						if($elementObject = _getElementObject($elementIdentifier)) {
							$dependencies[] = $elementObject->getVar('ele_handle');
						}
					} elseif($elementIdentifier) {
						$dependencies[] = $elementIdentifier;
					}
				}
			}
		}

		$filterDependencies = $this->formulize_getFilterDependencies($values[7]);
		$dependencies = array_merge($dependencies, $filterDependencies);

		foreach(array(
			'subform_prepop_element',
			'SortingElement',
			'UserFilterByElement') as $key) {
				if(isset($values[$key])) {
					$dependencies = array_merge($dependencies, $this->formulize_getRegularDependencies($values[$key]));
				}
		}

		return array_filter(array_unique($dependencies), function($value) {
			return $value !== 'none';
		});
	}

	public function getDefaultEleValue() {
			$ele_value = array();
			$ele_value[0] = 0; // form we're linking to
			$ele_value[1] = ''; // elements to show in the subform. A comma separated list of element ids
			$ele_value[2] = 0; // show no entries by default, otherwise it's a number of blanks to show
			$ele_value[3] = 1; // 0 - do not show the View Entry link at all, editing only by inline editing of elements, if it is an editable row subform, 1 - edit entries, and open new entries, in the full form, 2- edit entries, and open new entries, in a modal, 3 - edit entries by modal (new entries show up as rows), 4 - edit entries by full screen (new entries show up as rows)
			$ele_value[4] = 0; // use column headings. 1 means use captions
			$ele_value[5] = 0; // active user will be owner (1 means mainform entry owner will be owner)
			$ele_value[6] = 'subform'; // showing add entries UI requires permission in subform. 'parent' means requires permission in the main form. 'hideaddentries' means don't show Add entry UI.
			$ele_value[7] = []; // no filter conditions by default
			$ele_value[8] = 'row'; // if subformFullForm default to collapsable forms ('form'), otherwise 'flatform' is non collapsable. 'row' or empty string/non value is for subformEditableRow and subformListings.
			$ele_value[9] = _AM_APP_ENTRIES; // default text for add Entries button
			$ele_value['subform_prepop_element'] = 0; // element to prepopulate subform entries, 0 means no prepopulation
			$ele_value['simple_add_one_button'] = 1;
			$ele_value['simple_add_one_button_text'] = _AM_ADD.' '._AM_APP_ONE;
			$ele_value['show_delete_button'] = 1;
			$ele_value['show_clone_button'] = 0;
			$ele_value['enforceFilterChanges'] = 1;
			$ele_value['disabledelements'] = ''; // no disabled elements by default, this is a comma separated list of element ids
			$ele_value['display_screen'] = 0; // use default screen
			$ele_value['SortingElement'] = 0; // element id of element to sort by
			$ele_value['SortingDirection'] = 'ASC'; // ASC or DESC
			$ele_value['addButtonLimit'] = 0; // no limit on how many entries by default
			$ele_value['UserFilterByElement'] = 0; // no element to filter entries on by default
			$ele_value['FilterByElementStartState'] = 1; // Show entries normally. 0 means hide all entries until user applies filter.
			return $ele_value;
	}

	// this method would gather any data that we need to pass to the template, besides the ele_value and other properties that are already part of the basic element class
	// it receives the element object and returns an array of data that will go to the admin UI template
	// when dealing with new elements, $element might be FALSE
	// can organize template data into two top level keys, advanced-tab-values and options-tab-values, if there are some options for the element type that appear on the Advanced tab in the admin UI. This requires an additional template file with _advanced.html as the end of the name. Text elements have an example.
	function adminPrepare($element) {
		$dataToSendToTemplate = array();

		if(!$element) {
			$fid = intval($_GET['fid']);
			$ele_id = 'new';
			$ele_value = $this->getDefaultEleValue();
		} else {
			$ele_value = $element->getVar('ele_value');
			$fid = $element->getVar('fid');
			$ele_id = $element->getVar('ele_id');
		}

		$form_handler = xoops_getmodulehandler('forms', 'formulize');
		$formObject = $form_handler->get($fid);

		if(!isset($ele_value['show_delete_button'])) {
			$ele_value['show_delete_button'] = 1;
		}
		if(!isset($ele_value['show_clone_button'])) {
			$ele_value['show_clone_button'] = 0;
		}
		if(!isset($ele_value['FilterByElementStartState'])) {
			$ele_value['FilterByElementStartState'] = 1;
		}

		$ele_value['enforceFilterChanges'] = isset($ele_value['enforceFilterChanges']) ? $ele_value['enforceFilterChanges'] : 1;

		$ele_value[1] = explode(",",$ele_value[1]);
		if (is_string($ele_value['disabledelements'])) {
			$ele_value['disabledelements'] = explode(",",$ele_value['disabledelements']);
		}
		global $xoopsDB;
		$allFormsQuery = q("SELECT id_form, form_title FROM ".$xoopsDB->prefix("formulize_id")." WHERE id_form != ".intval($fid)." ORDER BY form_title");
		$allForms = array(_AM_FORMLINK_PICK);
		foreach($allFormsQuery as $thisForm) {
			$allForms[$thisForm['id_form']] = $thisForm['form_title'];
		}
		$validForms1 = q("SELECT t1.fl_form1_id, t2.form_title FROM " . $xoopsDB->prefix("formulize_framework_links") . " AS t1, " . $xoopsDB->prefix("formulize_id") . " AS t2 WHERE t1.fl_form2_id=" . intval($fid) . " AND t1.fl_unified_display=1 AND t1.fl_relationship != 1 AND t1.fl_form1_id=t2.id_form");
		$validForms2 = q("SELECT t1.fl_form2_id, t2.form_title FROM " . $xoopsDB->prefix("formulize_framework_links") . " AS t1, " . $xoopsDB->prefix("formulize_id") . " AS t2 WHERE t1.fl_form1_id=" . intval($fid) . " AND t1.fl_unified_display=1 AND t1.fl_relationship != 1 AND t1.fl_form2_id=t2.id_form");
		$validForms = array();
		foreach($validForms1 as $vf1) {
				$validForms[$vf1['fl_form1_id']] = $vf1['form_title'];
		}
		foreach($validForms2 as $vf2) {
				if (!isset($validForms[$vf2['fl_form2_id']])) {
						$validForms[$vf2['fl_form2_id']] = $vf2['form_title'];
				}
		}
		$allForms['new'] = _AM_ELE_SUBFORM_NEW;
		$dataToSendToTemplate['allforms'] = $allForms;
		$dataToSendToTemplate['validForms'] = $validForms;
		$dataToSendToTemplate['subformTitle'] = $ele_id != 'new' ? $validForms[intval($ele_value[0])] : '';
		$formtouse = $ele_value[0] ? $ele_value[0] : 0;
		if($formtouse) {
			$subFormObject = $form_handler->get($formtouse);
			$dataToSendToTemplate['subformUserFilterElements'][0] = _AM_FORMULIZE_SUBFORM_FILTERDEFAULT;
			$subformColheads = $subFormObject->getVar('elementColheads');
			$subformCaptions = $subFormObject->getVar('elementCaptions');
			foreach($subFormObject->getVar('elementsWithData') as $subformElementWithDataId) {
				$dataToSendToTemplate['subformelements'][$subformElementWithDataId] = $subformColheads[$subformElementWithDataId] ? $subformColheads[$subformElementWithDataId] : printSmart($subformCaptions[$subformElementWithDataId]);
				$dataToSendToTemplate['subformUserFilterElements'][$subformElementWithDataId] = $subformColheads[$subformElementWithDataId] ? $subformColheads[$subformElementWithDataId] : printSmart($subformCaptions[$subformElementWithDataId]);
			}
			$dataToSendToTemplate['subformSortingElements'] = $dataToSendToTemplate['subformUserFilterElements'];
			$dataToSendToTemplate['subformSortingElements'][0] = _AM_FORMULIZE_SUBFORM_SORTDEFAULT;
			// compile a list of data-entry screens for this form
			$dataToSendToTemplate['subform_screens'] = array();
			$screen_options = q("SELECT sid, title, type FROM ".$xoopsDB->prefix("formulize_screen")." WHERE fid=".intval($formtouse)." and (type='form' OR type='multiPage')");
			$dataToSendToTemplate['subform_screens'][0] = "(Use Default Screen)";
			foreach($screen_options as $screen_option) {
					$dataToSendToTemplate['subform_screens'][$screen_option["sid"]] = $screen_option["title"];
			}
		}
		$dataToSendToTemplate['selectedSubformScreenAdminURL'] = '';
		$dataToSendToTemplate['fullFormOption1'] = _AM_ELE_SUBFORM_UITYPE_FORM;
		$dataToSendToTemplate['fullFormOption2'] = _AM_ELE_SUBFORM_UITYPE_FLATFORM;
		$dataToSendToTemplate['subformSingular'] = _AM_APP_ONE;
		$dataToSendToTemplate['subformPlural'] = _AM_APP_ENTRIES;
		if($formtouse AND $subformObject = $form_handler->get($formtouse)) {
			$bestAppId = formulize_getFirstApplicationForBothForms($formObject, $subformObject);
			$bestAppId = $bestAppId ? $bestAppId : 0;
			$subformScreenId = $ele_value['display_screen'];
			$subformScreenId = $subformScreenId ? $subformScreenId : $subformObject->getVar('defaultform');
			$dataToSendToTemplate['selectedSubformScreenAdminURL'] = XOOPS_URL."/modules/formulize/admin/ui.php?page=screen&aid=$bestAppId&fid=$formtouse&sid=$subformScreenId";
			$dataToSendToTemplate['fullFormOption1'] = str_replace('subform', $subformObject->getSingular(),_AM_ELE_SUBFORM_UITYPE_FORM);
			$dataToSendToTemplate['fullFormOption2'] = str_replace('subform', $subformObject->getSingular(),_AM_ELE_SUBFORM_UITYPE_FLATFORM);
			$dataToSendToTemplate['subformSingular'] = $subformObject->getSingular();
			$dataToSendToTemplate['subformPlural'] = $subformObject->getPlural();
		}

		// setup the UI for the subform conditions filter
		$dataToSendToTemplate['subformfilter'] = formulize_createFilterUI($ele_value[7], "subformfilter", $ele_value[0], "form-2");

		$dataToSendToTemplate['ele_value'] = $ele_value;

		return $dataToSendToTemplate;
	}

	// this method would read back any data from the user after they click save in the admin UI, and save the data to the database, if it were something beyond what is handled in the basic element class
	// this is called as part of saving the options tab.  It receives a copy of the element object immediately prior to it being saved, so the element object will have all its properties set as they would be based on the user's changes in the names & settings tab, and in the options tab (the tabs are saved in order from left to right).
	// the exception is the special ele_value array, which is passed separately from the object (this will contain the values the user set in the Options tab)
	// You can modify the element object in this function and since it is an object, and passed by reference by default, then your changes will be saved when the element is saved.
	// You should return a flag to indicate if any changes were made, so that the page can be reloaded for the user, and they can see the changes you've made here.
	// advancedTab is a flag to indicate if this is being called from the advanced tab (as opposed to the Options tab, normal behaviour). In this case, you have to go off first principals based on what is in $_POST to setup the advanced values inside ele_value (presumably).
	function adminSave($element, $ele_value = array(), $advancedTab = false) {
		$changed = false;
		if(is_object($element) AND is_subclass_of($element, 'formulizeElement')) {

			// force row unless it's subformFullForm then we'll take whatever the user sent from UI
			if($element->getVar('ele_type') != 'subformFullForm') {
				$ele_value[8] = 'row';
			}

			if(!isset($ele_value['show_delete_button'])) {
					$ele_value['show_delete_button'] = 0;
			}
			if(!isset($ele_value['show_clone_button'])) {
					$ele_value['show_clone_button'] = 0;
			}

			if(!isset($ele_value['enforceFilterChanges'])) {
					$ele_value['enforceFilterChanges'] = 0;
			}

			if(!$_POST['elements-ele_value'][3]) {
				$ele_value[3] = 0;
			}
			// handle the "start" value, formerlly the blanks value (ele_value[2])
			// $_POST['subform_start'] will be 'empty', 'blanks', or 'prepop'
			// We need to set ele_value[2] to be the appropriate number of blanks
			// We need to set ele_value[subform_prepop_element] to be the element id of the element prepops are based on
			switch($_POST['subform_start']) {
				case "blanks":
						$ele_value[2] = intval($_POST['number_of_subform_blanks']);
						$ele_value['subform_prepop_element'] = 0;
						break;
				case "prepop":
						$ele_value[2] = 0;
						$ele_value['subform_prepop_element'] = intval($_POST['subform_start_prepop_element']);
						break;
				default:
						// implicitly case 'empty'
						$ele_value[2] = 0;
						$ele_value['subform_prepop_element'] = 0;
						break;

			}
			$ele_value[1] = implode(",",(array)$_POST['elements_ele_value_1']);
			$ele_value['disabledelements'] = (isset($_POST['elements_ele_value_disabledelements']) AND count((array) $_POST['elements_ele_value_disabledelements']) > 0) ? implode(",",$_POST['elements_ele_value_disabledelements']) : '';
			list($ele_value[7], $changed) = parseSubmittedConditions('subformfilter', 'optionsconditionsdelete'); // post key, delete key

			// check if display_screen changed
			$curEleValue = $element->getVar('ele_value');
			if(isset($curEleValue['display_screen']) AND $curEleValue['display_screen'] != $ele_value['display_screen']) {
				$changed = true;
			}

			$element->setVar('ele_value', $ele_value);
		}
		return $changed;
	}

	/**
	 * Returns the default value for this element, for a new entry in the specified form.
	 * Determines database ready values, not necessarily human readable values
	 * @param object $element The element object
	 * @param int|string $entry_id 'new' or the id of an entry we should use when evaluating the default value - only relevant when determining second pass at defaults when subform entries are written? (which would be better done by comprehensive conditional rendering?)
	 * @return mixed The default value
	 */
	function getDefaultValue($element, $entry_id = 'new') {
		return false;
	}

	// this method reads the current state of an element based on the user's input, and the admin options, and sets ele_value to what it needs to be so we can render the element correctly
	// it must return $ele_value, with the correct value set in it, so that it will render as expected in the render method
	// $element is the element object
	// $value is the value that was retrieved from the database for this element in the active entry.  It is a raw value, no processing has been applied, it is exactly what is in the database (as prepared in the prepareDataForSaving method and then written to the DB)
	// $entry_id is the ID of the entry being loaded
	function loadValue($element, $value, $entry_id) {
		return $value;
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
	function render($ele_value, $caption, $markupName, $isDisabled, $element, $entry_id, $screen=null, $owner=null) {

		$thissfid = $ele_value[0];
		if(!$thissfid) { return false; } // can't display non-specified subforms!

		global $xoopsUser;
		$sub_entries = array($thissfid => array());
		$fid = $element->getVar('fid');
		$frid = $screen ? $screen->getVar('frid') : (isset($_GET['frid']) ? intval($_GET['frid']) : 0);
		$uid = $xoopsUser ? $xoopsUser->getVar('uid') : 0;
		$groups = $xoopsUser ? $xoopsUser->getGroups() : array(XOOPS_GROUP_ANONYMOUS);
		$mid = getFormulizeModId();

		if(isset($GLOBALS['formulize_inlineSubformFrid'])) {
			$newLinkResults = checkForLinks($GLOBALS['formulize_inlineSubformFrid'], array($fid), $fid, array($fid=>array($entry_id)), true); // final true means only include entries from unified display linkages
			$sub_entries = $newLinkResults['sub_entries'];
		}

		$subUICols = drawSubLinks($thissfid, $sub_entries, $uid, $groups, $frid, $mid, $fid, $entry_id, $caption, ($ele_value[1] ? explode(",", $ele_value[1]) : ""), $ele_value[2], $ele_value[3], $ele_value[4], $ele_value[5], getEntryOwner($entry_id, $fid), $ele_value[6], $ele_value[7], $element->getVar('ele_id'), $ele_value[8], $ele_value[9], $element); // 2 is the number of default blanks, 3 is whether to show the view button or not, 4 is whether to use captions as headings or not, 5 is override owner of entry, $owner is mainform entry owner, 6 is hide the add button, 7 is the conditions settings for the subform element, 8 is the setting for showing just a row or the full form, 9 is text for the add entries button
		if(!isset($subUICols['single'])) {
			return new XoopsFormLabel($subUICols['c1'], $subUICols['c2'], $markupName);
		} else {
			return array($subUICols['single'], ''); // return the HTML and a class for the row
		}

	}

	// this method returns any custom validation code (javascript) that should figure out how to validate this element
	// 'myform' is a name enforced by convention that refers to the form where this element resides
	// use the adminCanMakeRequired property and alwaysValidateInputs property to control when/if this validation code is respected
	function generateValidationCode($caption, $markupName, $element, $entry_id=false) {
		$validationCode = array();
		return $validationCode;
	}

	// this method will read what the user submitted, and package it up however we want for insertion into the form's datatable
	// You can return {WRITEASNULL} to cause a null value to be saved in the database
	// $value is what the user submitted
	// $element is the element object
	// $entry_id is the ID number of the entry that this data is being saved into. Can be "new", or null in the event of a subformblank entry being saved.
	// $subformBlankCounter is the counter for the subform blank entries, if applicable
	function prepareDataForSaving($value, $element, $entry_id=null, $subformBlankCounter=null) {
		return $value;
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
		return $value;
	}

	// this method will format a dataset value for display on screen when a list of entries is prepared
	// for standard elements, this step is where linked selectboxes potentially become clickable or not, among other things
	// Set certain properties in this function, to control whether the output will be sent through a "make clickable" function afterwards, sent through an HTML character filter (a security precaution), and trimmed to a certain length with ... appended.
	function formatDataForList($value, $handle="", $entry_id=0, $textWidth=100) {
		return $value;
	}

}

function drawSubLinks($subform_id, $sub_entries, $uid, $groups, $frid, $mid, $fid, $entry,
	$customCaption = "", $customElements = "", $defaultblanks = 0, $showViewButtons = 1, $captionsForHeadings = 0,
	$overrideOwnerOfNewEntries = "", $mainFormOwner = 0, $hideaddentries = "", $subformConditions = null, $subformElementId = 0,
	$rowsOrForms = 'row', $addEntriesText = _formulize_ADD_ENTRIES, $subform_element_object = null, $firstRowToDisplay = 0, $numberOfEntriesToDisplay = null)
{

		$GLOBALS['formulizeCatalogueOfRenderedSubforms']["$frid-$fid-$subform_id"] = true;

    require_once XOOPS_ROOT_PATH.'/modules/formulize/include/subformSaveFunctions.php';

    $renderingSubformUIInModal = strstr($_SERVER['SCRIPT_NAME'], 'subformdisplay-elementsonly.php') ? true : false;

	$nestedSubform = false;
	if(isset($GLOBALS['formulize_inlineSubformFrid'])) {
		$frid = $GLOBALS['formulize_inlineSubformFrid'];
		$nestedSubform = true;
	}

    $member_handler = xoops_gethandler('member');
    $gperm_handler = xoops_gethandler('groupperm');

    $addEntriesText = $addEntriesText ? $addEntriesText : _formulize_ADD_ENTRIES;

	global $xoopsDB, $xoopsUser;
	$form_handler = xoops_getmodulehandler('forms', 'formulize');

	// if no sub entries, then go figure out sub entries again based on the correct main form id
	// This will return different sub entries when the mainform has a one to one form in the relationship, and then the subform is connected to the one to one. Sub is more than one hop away from main, so primary determination of entries does not pick up the sub entries
	if($subform_element_object AND (!is_array($sub_entries) OR (is_array($sub_entries[$subform_id]) AND count((array) $sub_entries[$subform_id]) == 0))) {
		$secondLinkResults = checkForLinks($frid, array($subform_element_object->getVar('id_form')), $subform_element_object->getVar('id_form'), array($subform_element_object->getVar('id_form') => array($entry)), true); // final true means only include entries from unified display linkages
		$sub_entries = $secondLinkResults['sub_entries'];
	}

	// limit the sub_entries array to just the entries that match the conditions, if any
	if(is_array($subformConditions) AND isset($sub_entries[$subform_id]) AND is_array($sub_entries[$subform_id])) {
		list($conditionsFilter, $conditionsFilterOOM, $curlyBracketFormFrom) = buildConditionsFilterSQL($subformConditions, $subform_id, $entry, $mainFormOwner, $fid); // pass in mainFormOwner as the comparison ID for evaluating {USER} so that the included entries are consistent when an admin looks at a set of entries made by someone else.
		$subformObject = $form_handler->get($subform_id);
		$sql = "SELECT subform.entry_id FROM ".$xoopsDB->prefix("formulize_".$subformObject->getVar('form_handle'))." as subform $curlyBracketFormFrom WHERE subform.entry_id IN (".implode(", ", $sub_entries[$subform_id]).") $conditionsFilter $conditionsFilterOOM";
		$sub_entries[$subform_id] = array();
		if($res = $xoopsDB->query($sql)) {
			while($array = $xoopsDB->fetchArray($res)) {
				$sub_entries[$subform_id][] = $array['entry_id'];
			}
		}
	}

	include_once XOOPS_ROOT_PATH . "/modules/formulize/include/extract.php";
	$target_sub_to_use = (isset($_POST['target_sub']) AND $_POST['target_sub'] AND $_POST['target_sub'] == $subform_id AND isset($_POST['target_sub_instance']) AND $_POST['target_sub_instance'] == $subformElementId.$subformInstance) ? $_POST['target_sub'] : $subform_id;
	list($elementq, $element_to_write, $value_to_write, $value_source, $value_source_form, $alt_element_to_write) = formulize_subformSave_determineElementToWrite($frid, $fid, $entry, $target_sub_to_use);

	if (0 == strlen($element_to_write)) {
			error_log("Relationship $frid does not include subform $subform_id, when displaying the main form $fid.");
			$to_return = array("c1"=>"", "c2"=>"", "sigle"=>"");
			if (is_object($xoopsUser) and in_array(XOOPS_GROUP_ADMIN, $xoopsUser->getGroups())) {
					if (0 == $frid) {
							$to_return['single'] = "This subform cannot be shown because no relationship is active.";
					} else {
							$to_return['single'] = "This subform interface cannot be shown because the the form to be displayed (id: $subform_id) is not part of the active relationship (id: $frid). Check if the active screen is using the relationship, or just \"the form only.\", and check whether the relationship includes all the forms it should.";
					}
			}
			return $to_return;
	}

	// check for adding of a sub entry, and handle accordingly -- added September 4 2006
	global $formulize_subformInstance;
	$subformInstance = $formulize_subformInstance+1;
	$formulize_subformInstance = $subformInstance;
	$element_handler = xoops_getmodulehandler('elements', 'formulize');

	$sub_entry_new = false;
	$sub_entry_written = false;
	if(isset($_POST['target_sub']) AND $_POST['target_sub'] AND $_POST['target_sub'] == $subform_id AND isset($_POST['target_sub_instance']) AND $_POST['target_sub_instance'] == $subformElementId.$subformInstance) { // important we only do this on the run through for that particular sub form (hence target_sub == sfid), and also only for the specific instance of this subform on the page too, since not all entries may apply to all subform instances any longer with conditions in effect now
		list($sub_entry_new,$sub_entry_written) = formulize_subformSave_writeNewEntry($element_to_write, $value_to_write, $fid, $frid, $_POST['target_sub'], $entry, $subformConditions, $overrideOwnerOfNewEntries, $mainFormOwner, $_POST['numsubents']);
		$sub_entry_written = (is_array($sub_entry_written) AND !empty($sub_entry_written)) ? $sub_entry_written : false;
		if(is_array($sub_entry_written)) {
			global $formulize_subFidsWithNewEntries, $formulize_subformElementsWithNewEntries, $formulize_newSubformEntries;
			$formulize_subFidsWithNewEntries[] = $_POST['target_sub'];
			$formulize_subformElementsWithNewEntries[] = $subform_element_object;
			$formulize_newSubformEntries[$_POST['target_sub']] = $sub_entry_written; // an array of entries that were written, since multiple subs can be created at once
		}
	}

    $data_handler = new formulizeDataHandler($subform_id);


	// need to do a number of checks here, including looking for single status on subform, and not drawing in add another if there is an entry for a single

	$sub_single_result = getSingle($subform_id, $uid, $groups, $member_handler, $gperm_handler, $mid);
	$sub_single = $sub_single_result['flag'];
	if($sub_single) {
		unset($sub_entries);
		$sub_entries[$subform_id][0] = $sub_single_result['entry'];
	}

	if(!isset($sub_entries[$subform_id]) OR !is_array($sub_entries[$subform_id])) {
			$sub_entries[$subform_id] = array();
	}

	if($sub_entry_new AND !$sub_single AND isset($_POST['target_sub']) AND $_POST['target_sub'] == $subform_id) {
		for($i=0;$i<$_POST['numsubents'];$i++) {
			array_unshift($sub_entries[$subform_id], $sub_entry_new);
		}
	}

	if(is_array($sub_entry_written) AND !$sub_single AND $_POST['target_sub'] == $subform_id) {
		foreach($sub_entry_written as $sew) {
			array_unshift($sub_entries[$subform_id], $sew);
		}
	}

	if(!$customCaption) {
		// get the title of this subform
		// help text removed for F4.0 RC2, this is an experiment
		$subtitle = q("SELECT form_title FROM " . $xoopsDB->prefix("formulize_id") . " WHERE id_form = $subform_id");
        $subtitle = $subtitle[0]['form_title'];
	} else {
        $subtitle = $customCaption;
	}
    $helpText = ($subform_element_object AND $subform_element_object->getVar('ele_desc')) ? '<p class="subform-helptext">'.html_entity_decode($subform_element_object->getVar('ele_desc'),ENT_QUOTES).'</p>' : '';
    $col_one = "<p id=\"subform-caption-f$fid-sf$subform_id\" class=\"subform-caption form-label\"><b>" . trans($subtitle) . "</b></p>$helpText"; // <p style=\"font-weight: normal;\">" . _formulize_ADD_HELP;

	// preopulate entries, if there are no sub_entries yet, and prepop options is selected.
    // prepop will be based on the options in an element in the subform, and should also take into account the non OOM conditional filter choices where = is the operator.
	// does not populate when the mainform entry is new, so the subform interface will be empty until the form is saved, and then the subs will populate, and be linked to the just saved mainform entry
    if($entry AND $entry != 'new' AND count((array) $sub_entries[$subform_id]) == 0 AND $subform_element_object AND $subform_element_object->ele_value['subform_prepop_element']) {

        $optionElementObject = $element_handler->get($subform_element_object->ele_value['subform_prepop_element']);

        // gather filter choices first...
				if(is_array($subformConditions)) {
						$filterValues = getFilterValuesForEntry($subformConditions, $entry);
						$filterValues = $filterValues[key($filterValues)]; // subform element conditions are always on one form only so we just take the first set of values found (filterValues are grouped by form id)
				} else {
						$filterValues = array();
				}

				// gather all the choices for the prepop element, taking into account if the filter choice for this subform instance alters the options for the prepop element
        // render the element, then read the options from the rendered element
        // this will NOT work for autocomplete boxes!
        // call displayElement, this should set the GLOBALS value that we can then check to see what options have been created for this element
        $valuesToWrite = array();
        foreach($filterValues as $elementHandle=>$value) {
            // need to set this special flag, which will cause rendered linked selectboxes to have the subform entry inclusion filters taken into account
            // ie: if this instance of the subform should only render entries where field X = 'Basic Needs', then we want to get the options that would meet that requirement
            $GLOBALS['formulize_asynchronousFormDataInDatabaseReadyFormat']['new'][$elementHandle] = $value;
            $valuesToWrite[$elementHandle] = $value;
        }

        // if the prepop element is a linked field that has conditions on it, we need to ensure that when it is rendered, we are taking the filter into account!!
        // SINCE THIS IS A PHANTOM NEW ENTRY THAT DOESN'T REALLY EXIST, WE CAN ABUSE THAT SITUATION TO INJECT WHATEVER VALUES WE WANT FOR WHATEVER FIELDS THAT NEED TO BE MATCHED, EVEN THOUGH THEY WON'T ACTUALLY EXIST IN THE FORM WE'RE MAKING AN ENTRY IN.
        // THIS IS RELEVANT WHEN YOU ARE SETTING THE CURLY BRACKET CONDITIONS FOR FILTERING WHAT OPTIONS WE SHOULD PAY ATTENTION TO.
        if($optionElementObject->isLinked) {
            $optionElementEleValue = $optionElementObject->getVar('ele_value');
            $optionElementFilterConditions = $optionElementEleValue[5]; // fifth key in selectboxes will be conditions for what options to include when linked. Ack!
            if(is_array($optionElementFilterConditions) AND count((array) $optionElementFilterConditions)>1) {
                // if it's not a curly bracket value, then element name is the id, and value is the term
                // if it is a curly bracket value, then element name is the curly bracket value, and value is the value that field has in the parent entry to this one we're about to create!
                $optionElementEleValue2 = explode("#*=:*", $optionElementEleValue[2]);
                $optionSourceFid = $optionElementEleValue2[0];
                $filterElementHandles = convertElementIdsToElementHandles($optionElementFilterConditions[0], $optionSourceFid);
                $filterElementIds = $optionElementFilterConditions[0];
                $filterTerms = $optionElementFilterConditions[2];
                foreach($filterElementIds as $i=>$thisFilterElement) {
                    if(substr($filterTerms[$i],0,1) == "{" AND substr($filterTerms[$i],-1)=="}" AND !isset($filterValues[substr($filterTerms[$i],1,-1)])) {
                        // lookup value of this field in the parent entry
                        $prepop_source_data_handler = new formulizeDataHandler($fid);
                        $GLOBALS['formulize_asynchronousFormDataInDatabaseReadyFormat']['new'][substr($filterTerms[$i],1,-1)] = $prepop_source_data_handler->getElementValueInEntry($entry, substr($filterTerms[$i],1,-1));
                    } elseif(!isset($filterValues[$filterElementHandles[$i]])) {
                        $GLOBALS['formulize_asynchronousFormDataInDatabaseReadyFormat']['new'][$filterElementHandles[$i]] = $filterTerms[$i];
                    }
                }
            }
        }

				// disembodied render, just to get the options (out of global space, ugh)
        list($prepopElement, $prepopDisabled) = displayElement("", $subform_element_object->ele_value['subform_prepop_element'], "new", false, null, null, false);
        $prepopOptions = $GLOBALS['formulize_lastRenderedElementOptions'];

        // if there are known linking values to the main form, then write those in.
        // Otherwise...we need to add logic to make this work like the blanks do and write links after saving!!
        // Therefore, this feature will not yet work when the mainform entry is new!!
        if($element_to_write) {
					$linkingElementObject = $element_handler->get($element_to_write);
					$valuesToWrite[$linkingElementObject->getVar('ele_handle')] = $value_to_write;
        }
        foreach($prepopOptions as $optionKey=>$optionValue) {
            // get the database ready value for this option
            // write an entry with that value, and all applicable filterValues
            // need to also write the joining value to the main form!!
            // add that entry to the list of sub entries
            $valuesToWrite[$optionElementObject->getVar('ele_handle')] = prepDataForWrite($optionElementObject, $optionKey); // keys are what the form sends back for processing
            if($valuesToWrite[$optionElementObject->getVar('ele_handle')] !== "" AND $valuesToWrite[$optionElementObject->getVar('ele_handle')] !== "{WRITEASNULL}") {
                $proxyUser = $overrideOwnerOfNewEntries ? $mainFormOwner : false;
                if($writtenEntryId = formulize_writeEntry($valuesToWrite, 'new', 'replace', $proxyUser, true)) { // last true forces writing even when not using POST method on page request. Necessary for prepop in modal drawing.
                    secondPassWritingSubformEntryDefaults($subform_id,$writtenEntryId,array_keys($valuesToWrite));
                    $sub_entries[$subform_id][] = $writtenEntryId;
                }
            }
        }
        // IF no main form entry is actually saved in the end, then we want to delete all these subs that we have made??!!
        // We don't have to, however, they will polute the database, it's not necessary to have them around
    }

	// list the entries, including links to them and delete checkboxes

	// get the headerlist for the subform and convert it into handles
	// note big assumption/restriction that we are only using the first header found (ie: only specify one header for a sub form!)
	// setup the array of elements to draw
	if(is_array($customElements)) {
		$headingDescriptions = array();
		$headerq = q("SELECT ele_caption, ele_colhead, ele_desc, ele_id FROM " . $xoopsDB->prefix("formulize") . " WHERE ele_id IN (" . implode(", ", $customElements). ") ORDER BY ele_order");
		foreach($headerq as $thisHeaderResult) {
            if($element_handler->isElementVisibleForUser($thisHeaderResult['ele_id'], $xoopsUser)) {
			$elementsToDraw[] = $thisHeaderResult['ele_id'];
			$headingDescriptions[]  = $thisHeaderResult['ele_desc'] ? $thisHeaderResult['ele_desc'] : "";
			if($captionsForHeadings) {
				$headersToDraw[] = $thisHeaderResult['ele_caption'];
			} else {
				$headersToDraw[] = $thisHeaderResult['ele_colhead'] ? $thisHeaderResult['ele_colhead'] : $thisHeaderResult['ele_caption'];
			}
		}
		}
	} else {
		$subHeaderList = getHeaderList($subform_id);
		$subHeaderList1 = getHeaderList($subform_id, true);
		if (isset($subHeaderList[0])) {
			$headersToDraw[] = trans($subHeaderList[0]);
		}
		if (isset($subHeaderList[1])) {
			$headersToDraw[] = trans($subHeaderList[1]);
		}
		if (isset($subHeaderList[2])) {
			$headersToDraw[] = trans($subHeaderList[2]);
		}
		$elementsToDraw = array_slice($subHeaderList1, 0, 3);
	}

	$drawnHeadersOnce = false;
    static $drawnSubformBlankHidden = array();

    $viewType = ($showViewButtons == 2 OR $showViewButtons == 3) ? 'Modal' : '';
    $viewType = stristr($_SERVER['SCRIPT_NAME'], 'subformdisplay-elementsonly.php') ? 'Modal' : $viewType;
    $addViewType = ($showViewButtons == 2) ? 'Modal' : '';
    $addViewType = stristr($_SERVER['SCRIPT_NAME'], 'subformdisplay-elementsonly.php') ? 'Modal' : $addViewType;

    // div for View button dialog
    $col_two = "
			<div id='subentry-dialog' style='display:none'></div>\n
				<div id='subform_button_controls_$subform_id$subformElementId$subformInstance' class='subform_button_controls'>";

    $deleteButton = "";
		if(((count((array) $sub_entries[$subform_id])>0 AND $sub_entries[$subform_id][0] != "") OR $sub_entry_new OR is_array($sub_entry_written)) AND !strstr($_SERVER['PHP_SELF'], "formulize/printview.php")) {
			if((!isset($subform_element_object->ele_value["show_delete_button"]) OR $subform_element_object->ele_value["show_delete_button"]) AND ($gperm_handler->checkRight("delete_own_entry", $subform_id, $groups, $mid) OR $gperm_handler->checkRight("delete_group_entries", $subform_id, $groups, $mid) OR $gperm_handler->checkRight("delete_other_entries", $subform_id, $groups, $mid))) {
				$deleteButton = "&nbsp;&nbsp;&nbsp;<input class='subform-delete-clone-buttons$subformElementId$subformInstance' style='display: none;' type=button name=deletesubs value='" . _formulize_DELETE_CHECKED . "' onclick=\"javascript:sub_del($subform_id, '$viewType', ".intval($_GET['subformElementId']).", '$fid', '$entry');\">";
			}
			if((!isset($subform_element_object->ele_value["show_clone_button"]) OR $subform_element_object->ele_value["show_clone_button"]) AND $gperm_handler->checkRight("add_own_entry", $subform_id, $groups, $mid)) {
				$deleteButton .= "&nbsp;&nbsp;&nbsp;<input class='subform-delete-clone-buttons$subformElementId$subformInstance' style='display: none' type=button name=clonesubs value='" . _formulize_CLONE_CHECKED . "' onclick=\"javascript:sub_clone($subform_id, '$viewType', ".intval($_GET['subformElementId']).", '$fid', '$entry');\">";
			}
		}

    // if the 'add x entries button' should be hidden or visible
    $hidingAddEntries = false;
    if ("hideaddentries" == $hideaddentries) {
        $hidingAddEntries = true;
    }
    $allowed_to_add_entries = false;
    if ("subform" == $hideaddentries OR 1 == $hideaddentries) {
			// for compatability, accept '1' which is the old value which corresponds to the new use-subform-permissions (saved as "subform")
			// user can add entries if they have permission on the sub form
			$allowed_to_add_entries = $gperm_handler->checkRight("add_own_entry", $subform_id, $groups, $mid);
    } else {
			// user can add entries if they have permission on the main form
			// the user should only be able to add subform entries if they can *edit* the main form entry, since adding a subform entry
			// is like editing the main form entry. otherwise they could add subform entries on main form entries owned by other users
			$allowed_to_add_entries = formulizePermHandler::user_can_edit_entry($fid, $uid, $entry);
    }

    if (($allowed_to_add_entries OR $deleteButton) AND !strstr($_SERVER['PHP_SELF'], "formulize/printview.php")) {
        if ($allowed_to_add_entries AND !$hidingAddEntries AND count((array) $sub_entries[$subform_id]) == 1 AND $sub_entries[$subform_id][0] === "" AND $sub_single) {
            $col_two .= "<input type=button name=addsub value='". _formulize_ADD_ONE . "' onclick=\"javascript:add_sub('$subform_id', 1, ".$subformElementId.$subformInstance.", '$frid', '$fid', '$entry', '$subformElementId', '$addViewType', ".(isset($_GET['subformElementId']) ? intval($_GET['subformElementId']) : 0).");\">";
        } elseif(!$sub_single) {
            $use_simple_add_one_button = (isset($subform_element_object->ele_value["simple_add_one_button"]) ? 1 == $subform_element_object->ele_value["simple_add_one_button"] : false);
            if($allowed_to_add_entries AND !$hidingAddEntries) {
                $col_two .= "<input type=button name=addsub value='".($use_simple_add_one_button ? trans($subform_element_object->ele_value['simple_add_one_button_text']) : _formulize_ADD)."' onclick=\"javascript:add_sub('$subform_id', jQuery('#addsubentries".$subform_id.$subformElementId.$subformInstance."').val(), ".$subformElementId.$subformInstance.", '$frid', '$fid', '$entry', '$subformElementId', '$addViewType', ".(isset($_GET['subformElementId']) ? intval($_GET['subformElementId']) : 0).");\">";
            }
            if ($allowed_to_add_entries AND !$hidingAddEntries AND $use_simple_add_one_button) {
                $col_two .= "<input type=\"hidden\" name=addsubentries$subform_id$subformElementId$subformInstance id=addsubentries$subform_id$subformElementId$subformInstance value=\"1\">";
            } elseif($allowed_to_add_entries AND !$hidingAddEntries) {
                $col_two .= "<input type=text name=addsubentries$subform_id$subformElementId$subformInstance id=addsubentries$subform_id$subformElementId$subformInstance value=1 size=2 maxlength=2>";
                $col_two .= $addEntriesText;
            }
        }
        $col_two .= $deleteButton;
    }

		// hacking in a filter for existing entries
    if($subform_element_object AND isset($subform_element_object->ele_value["UserFilterByElement"]) AND $subform_element_object->ele_value["UserFilterByElement"]) {
			$filterElementObject = $element_handler->get($subform_element_object->ele_value["UserFilterByElement"]);
      $col_two .= "<div class='subform-filter-search'>".sprintf(_formulize_SUBFORM_FILTER_SEARCH, ($subform_element_object->ele_value[4] ? $filterElementObject->getVar('ele_caption') : $filterElementObject->getUIName()))."<input type='text' name='subformFilterBox_$subformInstance' value='".htmlspecialchars(strip_tags(str_replace("'","&#039;",$_POST['subformFilterBox_'.$subformInstance])))."' onkeypress='javascript: if(event.keyCode == 13) validateAndSubmit();'/>&nbsp;&nbsp;&nbsp;<input type='button' value='"._formulize_SUBFORM_FILTER_GO."' onclick='validateAndSubmit();' /></div>";
		}

		$col_two .= "</div>";

	// construct the limit based on what is passed in via $numberOfEntriesToDisplay and $firstRowToDisplay
	$limitClause = "";
	$pageNav = "";
	$numberOfEntriesToDisplay = $numberOfEntriesToDisplay ? $numberOfEntriesToDisplay : (isset($subform_element_object->ele_value['numberOfEntriesPerPage']) ? $subform_element_object->ele_value['numberOfEntriesPerPage'] : 0);
	if($numberOfEntriesToDisplay AND $numberOfEntriesToDisplay < count($sub_entries[$subform_id])) {
		$firstRowToDisplay = intval($firstRowToDisplay);
		if(isset($_POST['formulizeFirstRowToDisplay']) AND isset($_POST['formulizeSubformPagingInstance']) AND $_POST['formulizeSubformPagingInstance'] == $subformElementId.$subformInstance) {
			$firstRowToDisplay = intval($_POST['formulizeFirstRowToDisplay']);
		}
		$limitClause = "LIMIT $firstRowToDisplay, ".intval($numberOfEntriesToDisplay);

		$lastPageNumber = ceil(count($sub_entries[$subform_id]) / $numberOfEntriesToDisplay);
		$firstDisplayPageNumber = ($firstRowToDisplay / $numberOfEntriesToDisplay) + 1 - 4;
		$lastDisplayPageNumber = ($firstRowToDisplay / $numberOfEntriesToDisplay) + 1 + 4;
		$firstDisplayPageNumber = $firstDisplayPageNumber < 1 ? 1 : $firstDisplayPageNumber;
		$lastDisplayPageNumber = $lastDisplayPageNumber > $lastPageNumber ? $lastPageNumber : $lastDisplayPageNumber;
		$pageNav = formulize_buildPageNavMarkup('gotoSubPage'.$subformElementId.$subformInstance, $numberOfEntriesToDisplay, $firstRowToDisplay, $firstDisplayPageNumber, $lastDisplayPageNumber, $lastPageNumber, _formulize_DMULTI_PAGE.":");
	}

	$col_two .= $pageNav; // figured out above

	if($rowsOrForms=="row" OR $rowsOrForms =='') {
		$col_two .= "<div class='formulize-subform-table-scrollbox'><table id=\"formulize-subform-table-$subform_id\" class=\"formulize-subform-table\">";
	} else {
		$col_two .= "";
		if(!strstr($_SERVER['PHP_SELF'], "formulize/printview.php")) {
            $styleDisplayNone = $rowsOrForms == 'flatform' ? "" : "style=\"display: none;\"";
            $accordionClassName = $rowsOrForms == 'flatform' ? "subform-flatform-container" : "subform-accordion-container";
			$col_two .= "<div id=\"subform-$subformElementId$subformInstance\" class=\"$accordionClassName\" subelementid=\"$subformElementId$subformInstance\" $styleDisplayNone>";
		}
		$col_two .= "<input type='hidden' name='subform_entry_".$subformElementId.$subformInstance."_active' id='subform_entry_".$subformElementId.$subformInstance."_active' value='' />";
	}

	$deFrid = $frid ? $frid : ""; // need to set this up so we can pass it as part of the displayElement function, necessary to establish the framework in case this is a framework and no subform element is being used, just the default draw-in-the-one-to-many behaviour

	// if there's been no form submission, and there's no sub_entries, and there are default blanks to show, then do everything differently -- sept 8 2007

    // check if there is a ! flag on the $defaultblanks value
    // if so, we always show blanks as long as there are no subform entries already
    $ignoreFormSubmitted = false;
    if(substr($defaultblanks, -1) == '!') {
        $defaultblanks = intval(substr($defaultblanks, 0, -1));
        $ignoreFormSubmitted = true;
    } else {
        $defaultblanks = intval($defaultblanks);
    }

	if((!isset($_POST['form_submitted']) OR !$_POST['form_submitted'] OR $ignoreFormSubmitted) AND count((array) $sub_entries[$subform_id]) == 0 AND $defaultblanks > 0 AND ($rowsOrForms == "row"  OR $rowsOrForms =='')) {

        if(!isset($GLOBALS['formulize_globalDefaultBlankCounter'])) {
            $GLOBALS['formulize_globalDefaultBlankCounter'] = -1;
        }
		for($i=0;$i<$defaultblanks;$i++) {

            $GLOBALS['formulize_globalDefaultBlankCounter'] = $GLOBALS['formulize_globalDefaultBlankCounter'] + 1;

				// nearly same header drawing code as in the 'else' for drawing regular entries
				if(!$drawnHeadersOnce) {
					$col_two .= "<tr><td>\n";
                    if(!isset($drawnSubformBlankHidden[$subform_id])) {
                        $col_two .= "<input type=\"hidden\" name=\"formulize_subformValueSource_$subform_id\" value=\"$value_source\">\n";
                        $col_two .= "<input type=\"hidden\" name=\"formulize_subformValueSourceForm_$subform_id\" value=\"$value_source_form\">\n";
                        $col_two .= "<input type=\"hidden\" name=\"formulize_subformValueSourceEntry_$subform_id"."[]\" value=\"$entry\">\n";
                        $col_two .= "<input type=\"hidden\" name=\"formulize_subformElementToWrite_$subform_id\" value=\"$element_to_write\">\n";
                        $col_two .= "<input type=\"hidden\" name=\"formulize_subformSourceType_$subform_id\" value=\"".$elementq[0]['fl_common_value']."\">\n";
                        $col_two .= "<input type=\"hidden\" name=\"formulize_subformId_$subform_id\" value=\"$subform_id\">\n"; // this is probably redundant now that we're tracking sfid in the names of the other elements
                        $drawnSubformBlankHidden[$subform_id] = true;
                    }
					$col_two .= "</td>\n";
					$col_two .= drawRowSubformHeaders($headersToDraw, $headingDescriptions);
					$col_two .= "</tr>\n";
					$drawnHeadersOnce = true;
				}
				$col_two .= "<tr>\n<td>";
				$col_two .= "</td>\n";
				include_once XOOPS_ROOT_PATH . "/modules/formulize/include/elementdisplay.php";
				foreach($elementsToDraw as $thisele) {
					if($thisele) {
						$unsetDisabledFlag = false;
						if($subform_element_object AND ($subform_element_object->getVar('subformListings') OR in_array($thisele, explode(',',(string)$subform_element_object->ele_value['disabledelements'])))) {
								$unsetDisabledFlag = !isset($GLOBALS['formulize_forceElementsDisabled']);
								$GLOBALS['formulize_forceElementsDisabled'] = true;
						}
						ob_start();
						// critical that we *don't* ask for displayElement to return the element object, since this way the validation logic is passed back through the global space also (ugh).  Otherwise, no validation logic possible for subforms.
						$renderResult = displayElement($deFrid, $thisele, "subformCreateEntry_".$GLOBALS['formulize_globalDefaultBlankCounter']."_".$subformElementId);
						$col_two_temp = ob_get_contents();
						ob_end_clean();
                        if($unsetDisabledFlag) { unset($GLOBALS['formulize_forceElementsDisabled']); }
						if($col_two_temp OR $renderResult == "rendered" OR $renderResult == "rendered-disabled") { // only draw in a cell if there actually is an element rendered (some elements might be rendered as nothing (such as derived values)
                            $textAreaClass = '';
                            if($elementObject = _getElementObject($thisele)) {
                                $textAreaClass = $elementObject->getVar('ele_type') == 'textarea' ? ' subform-textarea-element' : '';
                            }
							$col_two .= "<td class='formulize_subform_".$thisele.$textAreaClass."'>$col_two_temp</td>\n";
						} else {
							$col_two .= "<td></td>";
						}
					}
				}
				$col_two .= "</tr>\n";

		}

	} elseif(count((array) $sub_entries[$subform_id]) > 0) {

        if(intval($subform_element_object->ele_value["addButtonLimit"]) AND count((array) $sub_entries[$subform_id]) >= intval($subform_element_object->ele_value["addButtonLimit"])) {
            $hideaddentries = 'hideaddentries';
        }

        $sortClause = " sub.entry_id ";
        $joinClause = "";
        if(isset($subform_element_object->ele_value["SortingElement"]) AND $subform_element_object->ele_value["SortingElement"]) {
            $sortElementObject = $element_handler->get($subform_element_object->ele_value["SortingElement"]);
            $sortDirection = $subform_element_object->ele_value["SortingDirection"] == "DESC" ? "DESC" : "ASC";
            $sortTablePrefix = $sortElementObject->isLinked ? 'source' : 'sub';
            // if linked, go join to the source element
            if($sortTablePrefix == 'source') {
                $sortEleValue = $sortElementObject->getVar('ele_value');
                $sortEleValue2Parts = explode("#*=:*", $sortEleValue[2]);
                $sourceFid = $sortEleValue2Parts[0];
                $sourceHandle = $sortEleValue2Parts[1];
                $sourceFormObject = $form_handler->get($sourceFid);
                $joinClause = " LEFT JOIN ".$xoopsDB->prefix("formulize_".$sourceFormObject->getVar('form_handle'))." as source ON sub.`".$sortElementObject->getVar('ele_handle')."` = source.entry_id ";
                $sortClause = " source.`$sourceHandle` ".$sortDirection;
            } else {
                $sortClause = " $sortTablePrefix.`".$sortElementObject->getVar('ele_handle')."` ".$sortDirection;
            }
        }

        // apply any filter from the user if applicable
        // if no start state given, then show nothing
        $filterClause = "";
        if(isset($subform_element_object->ele_value["UserFilterByElement"]) AND $subform_element_object->ele_value["UserFilterByElement"]) {
            $matchingEntryIds = array();
            if(isset($_POST['subformFilterBox_'.$subformInstance]) AND $_POST['subformFilterBox_'.$subformInstance]) {
                $filterElementObject = $element_handler->get($subform_element_object->ele_value["UserFilterByElement"]);
                $matchingEntries = gatherDataset($subform_id, filter: $filterElementObject->getVar('ele_handle').'/**/'.htmlspecialchars(strip_tags(trim($_POST['subformFilterBox_'.$subformInstance])), ENT_QUOTES), frid: 0);
                foreach($matchingEntries as $matchingEntry) {
                    $matchingEntryIds = array_merge($matchingEntryIds, getEntryIds($matchingEntry, $subform_id));
                }
                if(count($matchingEntryIds)>0) {
                    $filterClause = " AND sub.entry_id IN (".implode(",", $matchingEntryIds).")";
                } else {
                    $filterClause = " AND false ";
                }
            } elseif(!isset($subform_element_object->ele_value["FilterByElementStartState"]) OR $subform_element_object->ele_value["FilterByElementStartState"] == 0) {
                $filterClause = " AND false ";
            }
        }

		$sformObject = $form_handler->get($subform_id);
		$subEntriesOrderSQL = "SELECT sub.entry_id FROM ".$xoopsDB->prefix("formulize_".$sformObject->getVar('form_handle'))." as sub $joinClause WHERE sub.entry_id IN (".implode(",", $sub_entries[$subform_id]).") $filterClause ORDER BY $sortClause $limitClause";
		if($subEntriesOrderRes = $xoopsDB->query($subEntriesOrderSQL)) {
			$sub_entries[$subform_id] = array();
			while($subEntriesOrderArray = $xoopsDB->fetchArray($subEntriesOrderRes)) {
				$sub_entries[$subform_id][] = $subEntriesOrderArray['entry_id'];
			}
		}

		$currentSubformInstance = $subformInstance;

        // check if user can delete any subform entry
        if(!$userCouldDeleteOrClone = $gperm_handler->checkRight("add_own_entry", $subform_id, $groups, $mid) AND $deleteButton) {
            foreach($sub_entries[$subform_id] as $sub_ent) {
                if($userCouldDeleteOrClone = formulizePermHandler::user_can_delete_entry($subform_id, $uid, $sub_ent)) {
                    break;
                }
            }
        }

		// if there is a display screen for this subform, then build the base visual URL to it
		$baseVisualURL = '';
		global $formulizeCanonicalURI;
		if($formulizeCanonicalURI AND $display_screen = get_display_screen_for_subform($subform_element_object)) {
			$subScreen_handler = xoops_getmodulehandler('screen', 'formulize');
			if($displayScreenObject = $subScreen_handler->get($display_screen)) {
				if($displayScreenRewriteRuleAddress = $displayScreenObject->getVar('rewriteruleAddress')) {
					$baseVisualURL = XOOPS_URL . '/' . $displayScreenRewriteRuleAddress . '/';
				}
			}
		}

		foreach($sub_entries[$subform_id] as $sub_ent) {

						// only show sub entries the user has permission to view
						if(security_check($subform_id, $sub_ent) == false) {
							continue;
						}

            // validate that the sub entry has a value for the key field that it needs to (in cases where there is a sub linked to a main and a another sub (ie: it's a sub sub of a sub, and a sub of the main, at the same time, we don't want to draw in entries in the wrong place -- they will be part of the sub_entries array, because they are part of the dataset, but they should not be part of the UI for this subform instance!)
            // $element_to_write is the element in the subform that needs to have a value
            // Also, strange relationship config possible where the same sub is linked to the main via two fields. This should only be done when no new entries are being created! Or else we won't know which key element to use for writing, but anyway we can still validate the entries against both possible linkages
            if($element_to_write AND !$subFormKeyElementValue = $data_handler->getElementValueInEntry($sub_ent, $element_to_write)
               AND (!$alt_element_to_write OR !$altSubFormKeyElementValue = $data_handler->getElementValueInEntry($sub_ent, $alt_element_to_write))) {
                continue;
            }

			if($sub_ent != "") {

				if($rowsOrForms=='row' OR $rowsOrForms =='') {

					if(!$drawnHeadersOnce) {
						$col_two .= "<tr>";
                        if ($sub_ent !== "new" AND $deleteButton AND $userCouldDeleteOrClone AND !strstr($_SERVER['PHP_SELF'], "formulize/printview.php")) {
                            $col_two .= "<th class='subentry-delete-cell'></th>\n";
                        }
                        if(!$renderingSubformUIInModal AND $showViewButtons AND !strstr($_SERVER['PHP_SELF'], "formulize/printview.php")) { $col_two .= "<th class='subentry-view-cell'></th>\n"; }
						$col_two .= drawRowSubformHeaders($headersToDraw, $headingDescriptions);
						$col_two .= "</tr>\n";
						$drawnHeadersOnce = true;
					}
                    $subElementId = is_object($subform_element_object) ? $subform_element_object->getVar('ele_id') : 0;
					$col_two .= "<tr class='row-".$sub_ent."-".$subElementId."'>\n";
					// check to see if we draw a delete box or not
					if ($sub_ent !== "new" AND $deleteButton AND $userCouldDeleteOrClone AND !strstr($_SERVER['PHP_SELF'], "formulize/printview.php")) {
						// note: if the add/delete entry buttons are hidden, then these delete checkboxes are hidden as well
						$col_two .= "<td class='subentry-delete-cell'><input type=checkbox class='delbox' name=delbox$sub_ent value=$sub_ent onclick='showHideDeleteClone($subformElementId$subformInstance);'></input></td>";
					}
					$additionalParams = $viewType == 'Modal' ? "'$frid', '$fid', '$entry', $subElementId, 0" : $subElementId;
					// add entry identifier to the visualURL
					$visualURL = '';
					if($baseVisualURL) {
						$entryIdentifier = $sub_ent;
						if($displayScreenRewriteRuleElement = $element_handler->get($displayScreenObject->getVar('rewriteruleElement'))) {
							$dataHandler = new formulizeDataHandler($displayScreenRewriteRuleElement->getVar('fid'));
							$rawEntryIdentifierValue = $dataHandler->getElementValueInEntry($sub_ent, $displayScreenRewriteRuleElement->getVar('ele_handle'));
							$entryIdentifier = prepvalues($rawEntryIdentifierValue, $displayScreenRewriteRuleElement->getVar('ele_handle'), $sub_ent);
							$entryIdentifier = is_array($entryIdentifier) ? urlencode($entryIdentifier[0]) : urlencode($entryIdentifier);
							$entryIdentifier = $entryIdentifier ? $entryIdentifier : $sub_ent;
						}
						$visualURL = $baseVisualURL . $entryIdentifier . '/';
					}
					if(!$renderingSubformUIInModal AND $showViewButtons AND !strstr($_SERVER['PHP_SELF'], "formulize/printview.php")) {
						$col_two .= "<td class='subentry-view-cell'><a href='$visualURL' class='loe-edit-entry' id='view".$sub_ent."' onclick=\"javascript:goSub".$viewType."('$sub_ent', '$subform_id', $additionalParams);return false;\">&nbsp;</a></td>\n";
					}
					include_once XOOPS_ROOT_PATH . "/modules/formulize/include/elementdisplay.php";
					foreach($elementsToDraw as $thisele) {
						if($thisele) {
							$unsetDisabledFlag = false;
							if($subform_element_object AND ($subform_element_object->getVar('ele_type') == 'subformListings' OR in_array($thisele, explode(',',(string) $subform_element_object->ele_value['disabledelements'])))) {
									$unsetDisabledFlag = !isset($GLOBALS['formulize_forceElementsDisabled']);
									$GLOBALS['formulize_forceElementsDisabled'] = true;
							}
							ob_start(function($string) { return $string; }); // set closure output buffer, so this element will never be catalogued as a conditional element. See catalogConditionalElement function for details.
							// critical that we *don't* ask for displayElement to return the element object, since this way the validation logic is passed back through the global space also (ugh).  Otherwise, no validation logic possible for subforms.
							$renderResult = displayElement($deFrid, $thisele, $sub_ent);
							$col_two_temp = trim(ob_get_contents());
							ob_end_clean();
                            if($unsetDisabledFlag) { unset($GLOBALS['formulize_forceElementsDisabled']); }
							if($col_two_temp OR $renderResult == "rendered" OR $renderResult == "rendered-disabled") { // only draw in a cell if there actually is an element rendered (some elements might be rendered as nothing (such as derived values)
                                $textAlign = "";
                                if(is_numeric($col_two_temp)) {
                                    $col_two_temp = formulize_numberFormat($col_two_temp, $thisele);
                                    $textAlign = " right-align-text";
                                }
                                $textAreaClass = '';
                                if($elementObject = _getElementObject($thisele)) {
                                    $textAreaClass = $elementObject->getVar('ele_type') == 'textarea' ? ' subform-textarea-element' : '';
                                }
								$col_two .= "<td class='formulize_subform_".$thisele."$textAlign$textAreaClass'>$col_two_temp</td>\n";
							} else {
								$col_two .= "<td></td>";
							}
						}
					}
					$col_two .= "</tr>\n";

                } else { // display the full form

					$headerValues = array();
					foreach($elementsToDraw as $thisele) {
						$value = $data_handler->getElementValueInEntry($sub_ent, $thisele);
						$element_object = _getElementObject($thisele);
						$value = prepvalues($value, $element_object->getVar("ele_handle"), $sub_ent);
						if (is_array($value))
							$value = implode(" - ", $value); // may be an array if the element allows multiple selections (checkboxes, multiselect list boxes, etc)
						$headerValues[] = undoAllHTMLChars($value);
					}
					$headerToWrite = implode(" &mdash; ", $headerValues);
					if(str_replace(" &mdash; ", "", $headerToWrite) == "") {
						$headerToWrite = _AM_ELE_SUBFORM_NEWENTRY_LABEL;
					}

					// check to see if we draw a delete box or not
					$deleteBox = "";
                    if ($sub_ent !== "new" AND $deleteButton AND $userCouldDeleteOrClone AND !strstr($_SERVER['PHP_SELF'], "formulize/printview.php")) {
					    $deleteBox = "<input type=checkbox class='delbox' name=delbox$sub_ent value=$sub_ent onclick='showHideDeleteClone($subformElementId$subformInstance);'></input>&nbsp;&nbsp;";
					}

					if(!strstr($_SERVER['PHP_SELF'], "formulize/printview.php")) {
                        $flatformClass = ($rowsOrForms == 'flatform') ? 'subform-flatform' : '';
						$col_two .= "<div class=\"subform-deletebox\">$deleteBox</div><div class=\"subform-entry-container $flatformClass\" id=\"subform-".$subform_id."-"."$sub_ent\"><p class=\"subform-header\">";
                        if($rowsOrForms == 'flatform') {
                            $col_two .= "<p class=\"flatform-name\">".$headerToWrite."</p>";
                        } else {
                            $col_two .= "<a class=\"accordion-name-anchor\" href=\"#\"><span class=\"accordion-name\">".$headerToWrite."</span></a>";
                        }
                        $col_two .= "</p><div class=\"accordion-content content\">";
					}
					ob_start();
					$GLOBALS['formulize_inlineSubformFrid'] = $frid;
                    if ($display_screen = get_display_screen_for_subform($subform_element_object)) {
                        $subScreen_handler = xoops_getmodulehandler('screen', 'formulize');
                        $subScreenObject = $subScreen_handler->get($display_screen);
                        $subScreen_handler = xoops_getmodulehandler($subScreenObject->getVar('type').'Screen', 'formulize');
                        $subScreenObject = $subScreen_handler->get($display_screen);
                        $subScreen_handler->render($subScreenObject, $sub_ent, null, true); // null is settings, true is elements only
                    } else {
                        $renderResult = displayForm($subform_id, $sub_ent, "", "",  "", "", "formElementsOnly");
                    }
					if(!$nestedSubform) {
						unset($GLOBALS['formulize_inlineSubformFrid']);
					}
					$col_two_temp = ob_get_contents();
					ob_end_clean();
					$col_two .= $col_two_temp;
					if(!strstr($_SERVER['PHP_SELF'], "formulize/printview.php")) {
						$col_two .= "</div>\n</div>\n";
					}
				}
			}
		}

		$subformInstance = $currentSubformInstance; // instance counter might have changed because the form could include other subforms
	}

	if($rowsOrForms=='row' OR $rowsOrForms =='') {
		// complete the table if we're drawing rows
		$col_two .= "</table></div>";
	} elseif(!strstr($_SERVER['PHP_SELF'], "formulize/printview.php")) {
        // close of the subform-accordion-container, unless we're on a printable view
		$col_two .= "</div>\n";
	}

	  $subformJS = '';
    if($rowsOrForms=='form') { // if we're doing accordions, put in the JS, otherwise it's flat-forms
        $subformJS .= "
            jQuery(document).ready(function() {
                jQuery(\"#subform-$subformElementId$subformInstance\").accordion({
                    heightStyle: 'content',
                    autoHeight: false, // legacy
                    collapsible: true, // sections can be collapsed
                    active: ";
                    if($_POST['target_sub_instance'] == $subformElementId.$subformInstance AND $_POST['target_sub'] == $subform_id) {
                        $subformJS .= count((array) $sub_entries[$subform_id])-$_POST['numsubents'];
                    } elseif(is_numeric($_POST['subform_entry_'.$subformElementId.$subformInstance.'_active'])) {
                        $subformJS .= $_POST['subform_entry_'.$subformElementId.$subformInstance.'_active'];
                    } else {
                        $subformJS .= 'false';
                    }
                    $subformJS .= ",
                    header: \"> div > p.subform-header\"
                });
                jQuery(\"#subform-$subformElementId$subformInstance\").fadeIn();
            });
        ";
    }
    $subformJS .= "
        function showHideDeleteClone(elementInstance) {
            var checkedBoxes = jQuery(\".delbox:checked\");
            if(jQuery(\".subform-delete-clone-buttons\"+elementInstance).css(\"display\") == \"none\" &&
            checkedBoxes.length > 0) {
                jQuery(\".subform-delete-clone-buttons\"+elementInstance).show(200);
            } else if(checkedBoxes.length == 0) {
                jQuery(\".subform-delete-clone-buttons\"+elementInstance).hide(200);
            }
        }
				function gotoSubPage".$subformElementId.$subformInstance."(firstRecordOrdinalOfPage) {
					jQuery.post(FORMULIZE.XOOPS_URL+'/modules/formulize/formulize_xhr_responder.php?uid='+FORMULIZE.XOOPS_UID+'&op=get_element_row_html&elementId=".$subformElementId."&entryId=".$entry."&fid=".$fid."&frid=".$frid."', { formulizeFirstRowToDisplay: firstRecordOrdinalOfPage, formulizeSubformPagingInstance: ".$subformElementId.$subformInstance." }, function(data) {
						if(data) {
							jQuery('#formulize-de_".$fid."_".$entry."_".$subformElementId."').empty();
							jQuery('#formulize-de_".$fid."_".$entry."_".$subformElementId."').append(JSON.parse(data).elements[0].data);
						}
					});
				}
    ";
    $col_two .= "
        <script type='text/javascript'>
            $subformJS
        </script>
    ";

    $to_return['c1'] = $col_one;
    $to_return['c2'] = $col_two;
    $to_return['single'] = $col_one.$col_two;

    return $to_return;
}

function drawRowSubformHeaders($headersToDraw, $headingDescriptions) {
    $col_two = "";
    foreach($headersToDraw as $i=>$thishead) {
        if($thishead) {
            $headerHelpLink = $headingDescriptions[$i] ? "<a class='icon-help' href=\"#\" onclick=\"return false;\" alt=\"".strip_tags(htmlspecialchars($headingDescriptions[$i]))."\" title=\"".strip_tags(htmlspecialchars($headingDescriptions[$i]))."\"></a>" : "";
            $col_two .= "<th><p>$thishead $headerHelpLink</p></th>\n";
        }
    }
    return $col_two;
}
