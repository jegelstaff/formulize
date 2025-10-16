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
require_once XOOPS_ROOT_PATH . "/modules/formulize/class/baseClassForLists.php";

// constants for the keys in ele_value
define('ELE_VALUE_SELECT_NUMROWS', 0); // number of rows in list box, set 1 when multiple is disabled or when autocomplete, or when dropdown list
define('ELE_VALUE_SELECT_MULTIPLE', 1); // set to 1 if multiple selections allowed in listbox or autocomplete
define('ELE_VALUE_SELECT_OPTIONS', 2); // array of options for the select element, or if a user list its an array with first key {USERNAMES}, or if linked then its a string in format "formid#*=:*elementhandle" (a number and a string, separated by #*=:*)
define('ELE_VALUE_SELECT_LINK_LIMITGROUPS', 3); // a comma separated list of group IDs used to limit the users in a user list, or the options in a linked list based on group ownership of linked form entries
define('ELE_VALUE_SELECT_LINK_USERSGROUPS', 4); // a 1/0 indicating if the groups in key 3 should be only groups the user is a member of
define('ELE_VALUE_SELECT_LINK_FILTERS', 5); // a standard conditions array of the conditions used to filter the linked entries (could/should be applied to a profile form for user lists, if/when we have a profile form attached to users)
define('ELE_VALUE_SELECT_LINK_ALLGROUPS', 6); // a 1/0 indicating if the user needs to be a member of all the groups in scope or any one of them. Groups in scope is first determined based on keys 3 and 4 above. Complex interaction effects when 4 and 6 are both set (only groups the user is a member of will count, but user must be a member of all those groups)
define('ELE_VALUE_SELECT_LINK_USEONLYUSERSENTRIES', 'formlink_useonlyusersentries'); // a 1/0 indicating if only entries belonging to the user should be included in the options list
define('ELE_VALUE_SELECT_LINK_CLICKABLEINLIST', 7); // a 1/0 indicating if the linked entries should be clickable in the list
define('ELE_VALUE_SELECT_AUTOCOMPLETE', 8); // a 1/0 indicating if this is an autocomplete box
define('ELE_VALUE_SELECT_RESTRICTSELECTION', 9); // 0/1/2/3 indicating restrictions on how many times an option can be picked. 0 - no limit, 1 - only once, 2 - once per user, 3 - once per group
define('ELE_VALUE_SELECT_LINK_ALTLISTELEMENTS', 10); // array of element(s) in source form to use as the value shown in lists.
define('ELE_VALUE_SELECT_LINK_ALTEXPORTELEMENTS', 11); // array of element(s) in source form to use as the value in exported spreadsheets
define('ELE_VALUE_SELECT_LINK_SORT', 12); // element ID of the element in the source form used to sort the options
define('ELE_VALUE_SELECT_LINK_DEFAULTVALUE', 13); // array of the entries in the source form that have the values which should be selected by default
// DEPRECATED AND ON DB UPDATE, LINKED SELECTS NEED TO HAVE THIS SETTING APPLIED TO THE ele_use_default_when_blank SETTING, IFF THE VALUE OF THIS SETTING IS 1
define('ELE_VALUE_SELECT_LINK_SHOWDEFAULTWHENBLANK', 14); // 0/1 indicating if the default values should be used when the value is blank (vs default which is only use defaults on new entries)
define('ELE_VALUE_SELECT_LINK_SORTORDER', 15); // 1/2 indicating if the sort order for linked options is ASC or DESC
define('ELE_VALUE_SELECT_AUTOCOMPLETEALLOWSNEW', 16); // 0/1 indicating if the autocomplete allows new values to be entered (vs default which is only allowing selection of existing values)
define('ELE_VALUE_SELECT_LINK_ALTFORMELEMENTS', 17); // array of element(s) in source form to use as the value shown in the rendered dropdown/listbox/autocomplete
define('ELE_VALUE_SELECT_LINK_SNAPSHOT', 'snapshot'); // 0/1 indicating if linked values should be saved as literal values, (vs default which is foreign key reference with the entry ids)
define('ELE_VALUE_SELECT_LINK_ALLOWSELFREF', 'selfreference'); // 0/1 indicating if the current entry can be selected, if this element links to its own form (probably makes no sense semantically to select yourself, you want to select from the other entries)
define('ELE_VALUE_SELECT_LINK_LIMITBYELEMENT', 'optionsLimitByElement'); // element ID of an element in another form which should be used to limit the options, by restricting them to options matching the values chosen in that other element (the specific entry is isolated using the optionsLimitByElementFilter conditions)
define('ELE_VALUE_SELECT_LINK_LIMITBYELEMENTFILTER', 'optionsLimitByElementFilter'); // filter conditions to apply to isolate a sepecific entry in the form that has the element specified in optionsLimitByElement. The value of that element in the specific entry, will be used to limit the linked options.
define('ELE_VALUE_SELECT_LINK_SOURCEMAPPINGS', 'linkedSourceMappings'); // mapping info showing which elements in this form should be used to populate which elements in the source form, when an autocomplete creates new values
class formulizeSelectElement extends formulizeElement {

	var $defaultValueKey;
	public static $category = "lists";

	function __construct() {
		$this->name = "Dropdown List";
		$this->hasData = true; // set to false if this is a non-data element, like the subform or the grid
		$this->needsDataType = true; // set to false if you're going force a specific datatype for this element using the overrideDataType
		$this->overrideDataType = ""; // use this to set a datatype for the database if you need the element to always have one (like 'date').  set needsDataType to false if you use this.
		$this->adminCanMakeRequired = true; // set to true if the webmaster should be able to toggle this element as required/not required
		$this->alwaysValidateInputs = false; // set to true if you want your custom validation function to always be run.  This will override any required setting that the webmaster might have set, so the recommendation is to set adminCanMakeRequired to false when this is set to true.
		$this->canHaveMultipleValues = false;
		$this->hasMultipleOptions = true;
		$this->isLinked = false; // set to true if this element can have linked values
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
**Element:** Dropdown List (select)
**Description:** A dropdown list of options where the user can select one choice. Dropdown lists are best used when there are a moderate number of options (generally between 5 and 20) and you want to save space on the form. For a small number of options, use Radio Buttons instead, and for a large number of options use an Autocomplete List.
**Properties:**
- all the common properties for List elements";
		return $descriptionAndExamples;
	}

	/**
	 * An optional method an element class can implement, if there are special considerations for the datatype that should be used for this element type
	 * Called by formulizeHandler::upsertElementSchemaAndResources when an element is created or updated
	 * Also called from element_advanced_save.php when the admin UI is being used to change the datatype for an element
	 * The if function_exists checks for a function defined in the element_adanced_save.php file, which will indicate if we should follow what the user put into the UI
	 * Otherwise, we should go with a passed in default which will be from the upsertElementSchemaAndResources method
	 * @param string $defaultType The default type to use if the element does not have special considerations
	 * @return string The data type to use for this element
	 */
	public function getDefaultDataType($defaultType = 'text') {
		$ele_value = $this->getVar('ele_value');
		$selectTypeName = strtolower(str_ireplace(['formulize', 'element', 'linked', 'users'], "", static::class));
		if ($ele_value[ELE_VALUE_SELECT_MULTIPLE] == 0 AND $this->isLinked) {
			if($ele_value[ELE_VALUE_SELECT_LINK_SNAPSHOT]) {
				$dataType = 'text';
			} else {
				$dataType = 'bigint';
			}
		} elseif ($ele_value[ELE_VALUE_SELECT_MULTIPLE] == 1) {
			$dataType = 'text';
		} elseif( $this->overrideDataType != "") {
			$dataType = $this->overrideDataType;
		} else {
			$dataType = function_exists('getRequestedDataType') ? getRequestedDataType() : $defaultType;
		}
		return $dataType;
	}

	// returns true if the option is one of the values the user can choose from in this element
	// returns false if the element does not have options
	// does not support linked values!!
	function optionIsValid($option) {
		$ele_value = $this->getVar('ele_value');
		$uitext = $this->getVar('ele_uitext');
		return (isset($ele_value[2][$option]) OR in_array($option, $uitext)) ? true : false;
	}

	/**
	 * Set the canHaveMultipleValues property for the element
	 * Must be done after the element has been instantiated with the values from the database (called as part of the get method in the parent, general, element class)
	 * Relevant for elements where the value of canHaveMultipleValues depends on a configuration choice in the database
	 * @return bool returns the value to set for the canHaveMultipleValues property
	 */
	function setCanHaveMultipleValues() {
		$ele_value = $this->getVar('ele_value');
		return isset($ele_value[ELE_VALUE_SELECT_MULTIPLE]) ? $ele_value[ELE_VALUE_SELECT_MULTIPLE] : false;
	}

}

#[AllowDynamicProperties]
class formulizeSelectElementHandler extends formulizeBaseClassForListsElementHandler {

	var $db;
	var $clickable; // used in formatDataForList
	var $striphtml; // used in formatDataForList
	var $length; // used in formatDataForList

	function __construct($db) {
		$this->db =& $db;
	}

	function create() {
		return new formulizeSelectElement();
	}

	public function getDefaultEleValue() {
		$ele_value = array();
		$ele_value[ELE_VALUE_SELECT_NUMROWS] = 1; // number of rows in list box, set 1 when multiple is disabled or when autocomplete
		$ele_value[ELE_VALUE_SELECT_MULTIPLE] = 0; // set to 1 if multiple selections allowed in listbox or autocomplete
		$ele_value[ELE_VALUE_SELECT_OPTIONS] = array(); // an array of options for the select box
		$ele_value[ELE_VALUE_SELECT_AUTOCOMPLETE] = 0; // a 1/0 indicating if this is an autocomplete box
		$ele_value[ELE_VALUE_SELECT_RESTRICTSELECTION] = 0; // 0/1/2/3 indicating restrictions on how many times an option can be picked. 0 - no limit, 1 - only once, 2 - once per user, 3 - once per group
		return $ele_value;
	}

	// this method would gather any data that we need to pass to the template, besides the ele_value and other properties that are already part of the basic element class
	// it receives the element object and returns an array of data that will go to the admin UI template
	// when dealing with new elements, $element might be FALSE
	// can organize template data into two top level keys, advanced-tab-values and options-tab-values, if there are some options for the element type that appear on the Advanced tab in the admin UI. This requires an additional template file with _advanced.html as the end of the name. Text elements have an example.
	function adminPrepare($element) {

		$form_handler = xoops_getmodulehandler('forms', 'formulize');
		$options = array();
		$fid = (is_object($element) AND is_a($element, 'formulizeElement')) ? $element->getVar('fid') : 0;
		$formObject = $fid ? $form_handler->get($fid) : false;

		// setup admin UI template vars for existing element
		if(is_object($element) AND is_subclass_of($element, 'formulizeElement')) {
			$ele_value = $element->getVar('ele_value');
			$ele_uitext = $element->getVar('ele_uitext');
			$options['listordd'] = $ele_value[ELE_VALUE_SELECT_NUMROWS] == 1 ? 0 : 1;
			$options['listordd'] = $ele_value[ELE_VALUE_SELECT_AUTOCOMPLETE] == 1 ? 2 : $options['listordd'];
			$options['multiple'] = $ele_value[ELE_VALUE_SELECT_MULTIPLE];
			$options['islinked'] = $element->isLinked;
			if($element->isLinked == false AND is_array($ele_value[ELE_VALUE_SELECT_OPTIONS])) {
				if (is_array($ele_uitext) AND count((array) $ele_uitext) > 0) {
					$ele_value[ELE_VALUE_SELECT_OPTIONS] = formulize_mergeUIText($ele_value[ELE_VALUE_SELECT_OPTIONS], $ele_uitext);
				}
				$options['useroptions'] = $ele_value[ELE_VALUE_SELECT_OPTIONS];
				$options['usernameslist'] = (key($options['useroptions']) == '{USERNAMES}' OR key($options['useroptions']) == '{FULLNAMES}') ? true : false;
			}
			$options['formlink_scope'] = explode(",",$ele_value[ELE_VALUE_SELECT_LINK_LIMITGROUPS]);

		// or initialize for new element
		} else {
			$options['listordd'] = 0; // 0 is listbox, 1 is dropdown, 2 is autocomplete
			$options['multiple'] = 0;
			$ele_value[ELE_VALUE_SELECT_NUMROWS] = 1;
			$options['islinked'] = 0;
			$options['usernameslist'] = substr($_GET['type'], -5) == 'users' ? true : false;
			$options['formlink_scope'] = array(0=>'all');
			$ele_value = array();
			$ele_uitext = array();
		}

		list($formlink, $selectedLinkElementId) = createFieldList($ele_value[ELE_VALUE_SELECT_OPTIONS]);
		$options['linkedoptions'] = $formlink->render();

		list($optionsLimitByElement, $limitByElementElementId) = createFieldList($ele_value[ELE_VALUE_SELECT_LINK_LIMITBYELEMENT], false, false, "elements-ele_value[".ELE_VALUE_SELECT_LINK_LIMITBYELEMENT."]", _NONE);
		$options[ELE_VALUE_SELECT_LINK_LIMITBYELEMENT] = $optionsLimitByElement->render();
		if($limitByElementElementId) {
			$limitByElementElementObject = $this->get($limitByElementElementId);
			if($limitByElementElementObject) {
					$options[ELE_VALUE_SELECT_LINK_LIMITBYELEMENTFILTER] = formulize_createFilterUI($ele_value[ELE_VALUE_SELECT_LINK_LIMITBYELEMENTFILTER], "optionsLimitByElementFilter", $limitByElementElementObject->getVar('fid'), "form-2");
			}
		}

		// setup the list value and export value option lists, and the default sort order list, and the list of possible default values
		$linkedSourceFid = 0;
		$options['subformInterfaceAdminUrl'] = '';
		if ($options['islinked']) {
			$linkedMetaDataParts = explode("#*=:*", $ele_value[ELE_VALUE_SELECT_OPTIONS]);
			$linkedSourceFid = $linkedMetaDataParts[0];
			if ($linkedSourceFid) {
				// list of elements to display when showing this element in a list
				$ele_value[ELE_VALUE_SELECT_LINK_ALTLISTELEMENTS] = isset($ele_value[ELE_VALUE_SELECT_LINK_ALTLISTELEMENTS]) ? $ele_value[ELE_VALUE_SELECT_LINK_ALTLISTELEMENTS] : 'none';
				list($listValue, $selectedListValue) = createFieldList($ele_value[ELE_VALUE_SELECT_LINK_ALTLISTELEMENTS], false, $linkedSourceFid,
					"elements-ele_value[".ELE_VALUE_SELECT_LINK_ALTLISTELEMENTS."]", _AM_ELE_LINKSELECTEDABOVE, true, true);
				$listValue->setValue($ele_value[ELE_VALUE_SELECT_LINK_ALTLISTELEMENTS]); // mark the current selections in the form element
				$options['listValue'] = $listValue->render();

				// list of elements to display when showing this element as an html form element (in form or list screens)
				$ele_value[ELE_VALUE_SELECT_LINK_ALTFORMELEMENTS] = isset($ele_value[ELE_VALUE_SELECT_LINK_ALTFORMELEMENTS]) ? $ele_value[ELE_VALUE_SELECT_LINK_ALTFORMELEMENTS] : 'none';
				list($displayElements, $selectedListValue) = createFieldList($ele_value[ELE_VALUE_SELECT_LINK_ALTFORMELEMENTS], false, $linkedSourceFid,
					"elements-ele_value[".ELE_VALUE_SELECT_LINK_ALTFORMELEMENTS."]", _AM_ELE_LINKSELECTEDABOVE, true, true);
				$displayElements->setValue($ele_value[ELE_VALUE_SELECT_LINK_ALTFORMELEMENTS]); // mark the current selections in the form element
				$options['displayElements'] = $displayElements->render();

				// list of elements to export to spreadsheet
				$ele_value[ELE_VALUE_SELECT_LINK_ALTEXPORTELEMENTS] = isset($ele_value[ELE_VALUE_SELECT_LINK_ALTEXPORTELEMENTS]) ? $ele_value[ELE_VALUE_SELECT_LINK_ALTEXPORTELEMENTS] : 'none';
				list($exportValue, $selectedExportValue) = createFieldList($ele_value[ELE_VALUE_SELECT_LINK_ALTEXPORTELEMENTS], false, $linkedSourceFid,
					"elements-ele_value[".ELE_VALUE_SELECT_LINK_ALTEXPORTELEMENTS."]", _AM_ELE_VALUEINLIST, true, true);
				$exportValue->setValue($ele_value[ELE_VALUE_SELECT_LINK_ALTEXPORTELEMENTS]); // mark the current selections in the form element
				$options['exportValue'] = $exportValue->render();

				// sort order
				list($optionSortOrder, $selectedOptionsSortOrder) = createFieldList($ele_value[ELE_VALUE_SELECT_LINK_SORT], false, $linkedSourceFid,
					"elements-ele_value[".ELE_VALUE_SELECT_LINK_SORT."]", _AM_ELE_LINKFIELD_ITSELF, dataElementsOnly: true);
				$options['optionSortOrder'] = $optionSortOrder->render();

				include_once XOOPS_ROOT_PATH . "/modules/formulize/class/data.php";
				$linkedDataHandler = new formulizeDataHandler($linkedSourceFid);
				$allLinkedValues = $linkedDataHandler->findAllValuesForField($linkedMetaDataParts[1], "ASC");
				if (!isset($ele_value[ELE_VALUE_SELECT_LINK_DEFAULTVALUE])) {
					$ele_value[ELE_VALUE_SELECT_LINK_DEFAULTVALUE] = array();
				} elseif(!is_array($ele_value[ELE_VALUE_SELECT_LINK_DEFAULTVALUE])) {
					$ele_value[ELE_VALUE_SELECT_LINK_DEFAULTVALUE] = array($ele_value[ELE_VALUE_SELECT_LINK_DEFAULTVALUE]);
				}
				$options['optionDefaultSelectionDefaults'] = $ele_value[ELE_VALUE_SELECT_LINK_DEFAULTVALUE];
				$options['optionDefaultSelection'] = $allLinkedValues; // array with keys as entry ids and values as text

				// handle additional linked source mapping options...
				list($thisFormFieldList, $thisFormFieldListSelected) = createFieldList('throwawayvalue', false, $fid, 'throwawayname', 'This Form');
				$options['mappingthisformoptions'] = $thisFormFieldList->getOptions();
				list($sourceFormFieldList, $sourceFormFieldListSelected) = createFieldList('throwawayvalue', false, $linkedSourceFid, 'throwawayname', 'Source Form');
				$options['mappingsourceformoptions'] = $sourceFormFieldList->getOptions();
				$options['linkedSourceMappings'] = $ele_value[ELE_VALUE_SELECT_LINK_SOURCEMAPPINGS];

				// subform interface link
				if($mainFormObject = $form_handler->get($linkedSourceFid)) {
					if($subformElementId = $mainFormObject->hasSubformInterfaceForForm($fid)) {
						$bestAppId = formulize_getFirstApplicationForBothForms($mainFormObject, $formObject);
						$bestAppId = $bestAppId ? $bestAppId : 0;
						$options['subformInterfaceAdminUrl'] = XOOPS_URL."/modules/formulize/admin/ui.php?page=element&aid=$bestAppId&ele_id=$subformElementId&tab=options";
					}
				}

			}
		}
		if (!$options['islinked'] OR !$linkedSourceFid) {
				$options['exportValue'] = "";
				$options['listValue'] = "";
				$options['optionSortOrder'] = "";
				$options['optionDefaultSelectionDefaults'] = array();
				$options['optionDefaultSelection'] = "";
		}

		// setup group list:
    $member_handler = xoops_gethandler('member');
    $allGroups = $member_handler->getGroups();
    $formlinkGroups = array();
    foreach($allGroups as $thisGroup) {
      $formlinkGroups[$thisGroup->getVar('groupid')] = $thisGroup->getVar('name');
    }
		$options['formlink_scope_options'] = array('all'=>_AM_ELE_FORMLINK_SCOPE_ALL) + $formlinkGroups;

		// setup conditions:
		// not using old profile form feature presently, but could/should soon when revamped to use a form for user management
		$selectedLinkFormId = "";
		if (is_array($ele_value[ELE_VALUE_SELECT_OPTIONS]) AND (isset($ele_value[ELE_VALUE_SELECT_OPTIONS]['{FULLNAMES}']) OR isset($ele_value[ELE_VALUE_SELECT_OPTIONS]['{USERNAMES}']))) {
			$config_handler = xoops_gethandler('config');
			$formulizeConfig = $config_handler->getConfigsByCat(0, getFormulizeModId());
			if ($formulizeConfig['profileForm']) {
				$selectedLinkFormId = $formulizeConfig['profileForm'];
			}
		}

		if ($selectedLinkElementId) {
			$selectedElementObject = $this->get($selectedLinkElementId);
			if ($selectedElementObject) {
					$options['formlinkfilter'] = formulize_createFilterUI($ele_value[ELE_VALUE_SELECT_LINK_FILTERS], "formlinkfilter", $selectedElementObject->getVar('fid'), "form-2");
			}
		} elseif ($selectedLinkFormId) { // if usernames or fullnames is in effect, we'll have the profile form fid instead
				$options['formlinkfilter'] = formulize_createFilterUI($ele_value[ELE_VALUE_SELECT_LINK_FILTERS], "formlinkfilter", $selectedLinkFormId, "form-2");
		}
		if (!$options['formlinkfilter']) {
				$options['formlinkfilter'] = "<p>The options are not linked.</p>";
		}

		return $options;
	}

	// this method would read back any data from the user after they click save in the admin UI, and save the data to the database, if it were something beyond what is handled in the basic element class
	// this is called as part of saving the options tab.  It receives a copy of the element object immediately prior to it being saved, so the element object will have all its properties set as they would be based on the user's changes in the names & settings tab, and in the options tab (the tabs are saved in order from left to right).
	// the exception is the special ele_value array, which is passed separately from the object (this will contain the values the user set in the Options tab)
	// You can modify the element object in this function and since it is an object, and passed by reference by default, then your changes will be saved when the element is saved.
	// You should return a flag to indicate if any changes were made, so that the page can be reloaded for the user, and they can see the changes you've made here.
	// advancedTab is a flag to indicate if this is being called from the advanced tab (as opposed to the Options tab, normal behaviour). In this case, you have to go off first principals based on what is in $_POST to setup the advanced values inside ele_value (presumably).
	function adminSave($element, $ele_value = array(), $advancedTab = false) {
		$changed = false;

		$postedMultipleValue = isset($_POST['elements_multiple']) ? intval($_POST['elements_multiple']) : 0;

		// for username lists, enforce the ancient convention of using {USERNAMES} as the only option
		$elementTypeName = strtolower(str_ireplace(['formulize', 'elementhandler'], "", static::class));
		$userNameList = strstr($elementTypeName, 'users') ? true : false; // is this a user list?
		if($userNameList) {
			unset($ele_value[ELE_VALUE_SELECT_OPTIONS]);
			$ele_value[ELE_VALUE_SELECT_OPTIONS] = array('{USERNAMES}' => 0);
		}

		$selectTypeName = strtolower(str_ireplace(['formulize', 'elementhandler', 'linked', 'users'], "", static::class));
		switch($selectTypeName) {
			case 'listbox':
				$ele_value[ELE_VALUE_SELECT_AUTOCOMPLETE] = 0;
				$ele_value[ELE_VALUE_SELECT_NUMROWS] = ($userNameList OR $element->isLinked) ? 10 : (count((array)$_POST['ele_value']) < 10 ? count($_POST['ele_value']) : 10);
				$ele_value[ELE_VALUE_SELECT_NUMROWS] = $ele_value[ELE_VALUE_SELECT_NUMROWS] < 1 ? 1 : $ele_value[ELE_VALUE_SELECT_NUMROWS]; // min of 1
				$ele_value[ELE_VALUE_SELECT_MULTIPLE] = $postedMultipleValue;
				break;
			case 'autocomplete':
				$ele_value[ELE_VALUE_SELECT_NUMROWS] = 1; // rows is 1
				$ele_value[ELE_VALUE_SELECT_AUTOCOMPLETE] = 1; // is autocomplete
				$ele_value[ELE_VALUE_SELECT_MULTIPLE] = $postedMultipleValue;
				break;
			case 'select':
			default:
				$ele_value[ELE_VALUE_SELECT_AUTOCOMPLETE] = 0;
				$ele_value[ELE_VALUE_SELECT_NUMROWS] = 1;
				$ele_value[ELE_VALUE_SELECT_MULTIPLE] = 0; // multiple selections not allowed for drop down lists
				break;
		}

		if(is_object($element) AND is_subclass_of($element, 'formulizeElement')) {

			$form_handler = xoops_getmodulehandler('forms', 'formulize');
			$fid = (is_object($element) AND is_a($element, 'formulizeElement')) ? $element->getVar('fid') : 0;
			$formObject = $fid ? $form_handler->get($fid) : false;
			$currentEleValue = $element->getVar('ele_value');

			global $xoopsDB;

			if(isset($_POST['formlink']) AND $_POST['formlink'] != "none") {
				// select box is not currently linked and user is requesting to link (as long as it's not the first save of the element)
				if ($_POST['formulize_admin_key'] != 'new' AND !$element->isLinked) {
					$form_handler->updateField($element, $element->getVar("ele_handle"), "bigint(20)");
				}
				$sql_link = "SELECT id_form, ele_handle FROM " . $xoopsDB->prefix("formulize") . " WHERE ele_id = " . intval($_POST['formlink']);
				$res_link = $xoopsDB->query($sql_link);
				$array_link = $xoopsDB->fetchArray($res_link);
				$ele_value[ELE_VALUE_SELECT_OPTIONS] = $array_link['id_form'] . "#*=:*" . $array_link['ele_handle'];
				// ensure there is a primary relationship link representing this connection / update existing connection
				// Get existing linked settings, if any
				$currentLinkedFormId = 0;
				$currentLinkedElementId = 0;
				if($element->isLinked) {
					$currentEleValue2Parts = explode('#*=:*', $currentEleValue[ELE_VALUE_SELECT_OPTIONS]);
					$currentLinkedFormId = $currentEleValue2Parts[0];
					$currentLinkedElementId = convertElementHandlesToElementIds(array($currentEleValue2Parts[1]));
					$currentLinkedElementId = $currentLinkedElementId[0];
				}
				updateLinkedElementConnectionsInRelationships($element->getVar('fid'), $element->getVar('ele_id'), $array_link['id_form'], $_POST['formlink'], $currentLinkedFormId, $currentLinkedElementId);
				if(isset($_POST['makeSubformInterface']) AND $_POST['makeSubformInterface']) {
					if(makeSubformInterface($array_link['id_form'], $element->getVar('fid'), $element->getVar('ele_id'))) {
						$_POST['reload_option_page'] = true;
					}
				}
			} else {
				// a user requests to unlink the select box and select box is currently linked
				if ($_POST['formlink'] == "none" AND $element->isLinked){
					$form_handler->updateField($element, $element->getVar("ele_handle"), "text");
					// remove any primary relationship link representing this connection
					deleteLinkedElementConnectionsInRelationships($element->getVar('fid'), $element->getVar('ele_id'));
				}
				list($_POST['ele_value'], $ele_uitext) = formulize_extractUIText($_POST['ele_value']);
				foreach($_POST['ele_value'] as $id=>$text) {
					if($text !== "") {
						$ele_value[ELE_VALUE_SELECT_OPTIONS][$text] = isset($_POST['defaultoption'][$id]) ? 1 : 0;
					}
				}
			}

			// gather any additional mappings specified by the user
			$mappingCounter = 0;
			$ele_value[ELE_VALUE_SELECT_LINK_SOURCEMAPPINGS] = array();
			foreach($_POST['mappingthisform'] as $key=>$thisFormValue) {
				if($thisFormValue != 'none' AND $_POST['mappingsourceform'][$key] != 'none') {
					$thisFormValue = is_numeric($thisFormValue) ? intval($thisFormValue) : $thisFormValue;
					$ele_value[ELE_VALUE_SELECT_LINK_SOURCEMAPPINGS][$mappingCounter] = array('thisForm'=>$thisFormValue, 'sourceForm'=>intval($_POST['mappingsourceform'][$key]));
					$mappingCounter++;
				}
			}
			if($mappingCounter == 0) { // nothing written
				$ele_value[ELE_VALUE_SELECT_LINK_SOURCEMAPPINGS] = null;
			}

			// if there is a change to the multiple selection status, need to adjust the database!!
			if (isset($currentEleValue[ELE_VALUE_SELECT_MULTIPLE])
					AND $currentEleValue[ELE_VALUE_SELECT_MULTIPLE] != $postedMultipleValue
					AND !$ele_value[ELE_VALUE_SELECT_LINK_SNAPSHOT]) {
				if ($currentEleValue[ELE_VALUE_SELECT_MULTIPLE] == 0) {
					$result = convertSelectBoxToMulti($xoopsDB->prefix('formulize_'.$formObject->getVar('form_handle')), $element->getVar('ele_handle'));
				} else {
					$result = convertSelectBoxToSingle($xoopsDB->prefix('formulize_'.$formObject->getVar('form_handle')), $element->getVar('ele_handle'));
				}
				if (!$result) {
					print "Could not convert select boxes from multiple options to single option or vice-versa.";
				}
			}
			$ele_value[ELE_VALUE_SELECT_LINK_LIMITGROUPS] = isset($_POST['element_formlink_scope']) ? implode(",", $_POST['element_formlink_scope']) : '';

			list($ele_value[ELE_VALUE_SELECT_LINK_FILTERS], $formLinkFilterChanged) = parseSubmittedConditions('formlinkfilter', 'optionsconditionsdelete');
			list($ele_value[ELE_VALUE_SELECT_LINK_LIMITBYELEMENTFILTER], $optionsLimitChanged) = parseSubmittedConditions('optionsLimitByElementFilter', 'optionsLimitByElementFilterDelete');
			if($ele_value[ELE_VALUE_SELECT_LINK_LIMITBYELEMENT] != $currentEleValue[ELE_VALUE_SELECT_LINK_LIMITBYELEMENT]) {
				$optionsLimitChanged = true;
			}
			$_POST['reload_option_page'] = ($formLinkFilterChanged OR $optionsLimitChanged OR $_POST['reload_option_page']) ? true : false;

			// new entries not allowed when autocomplete is a username list? could be made to work if designated profile forms are used to manage users
			if($ele_value[ELE_VALUE_SELECT_AUTOCOMPLETE] == 1 && is_array($ele_value[ELE_VALUE_SELECT_OPTIONS]) &&
				(isset($ele_value[ELE_VALUE_SELECT_OPTIONS]['{USERNAMES}']) || isset($ele_value[ELE_VALUE_SELECT_OPTIONS]['{FULLNAMES}']))) {
				$ele_value[ELE_VALUE_SELECT_AUTOCOMPLETEALLOWSNEW]=0;
			}

			if(isset($_POST['changeuservalues']) AND $_POST['changeuservalues']==1) {
				$data_handler = new formulizeDataHandler($element->getVar('id_form'));
				if(!$changeResult = $data_handler->changeUserSubmittedValues($element, $ele_value[ELE_VALUE_SELECT_OPTIONS])) {
					print "Error updating user submitted values for the options in element ".$element->getVar('ele_id');
				}
			}

			$element->setVar('ele_value', $ele_value);
			$element->setVar('ele_uitext', $ele_uitext);
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
		$defaultTextToWrite = "";
		$thisDefaultEleValue = $element->getVar('ele_value');
		if($element->isLinked AND !$thisDefaultEleValue['snapshot'])  {
			// default will be a foreign key or keys
			if(is_array($thisDefaultEleValue[ELE_VALUE_SELECT_LINK_DEFAULTVALUE]) AND $thisDefaultEleValue[ELE_VALUE_SELECT_LINK_DEFAULTVALUE][0] != "") {
				$defaultTextToWrite = $thisDefaultEleValue[ELE_VALUE_SELECT_MULTIPLE] ? $thisDefaultEleValue[ELE_VALUE_SELECT_LINK_DEFAULTVALUE] : $thisDefaultEleValue[ELE_VALUE_SELECT_LINK_DEFAULTVALUE][0]; // if not multiple selection, then use first (and only?) specified default value
			} else {
				$defaultTextToWrite = $thisDefaultEleValue[ELE_VALUE_SELECT_LINK_DEFAULTVALUE] ? $thisDefaultEleValue[ELE_VALUE_SELECT_LINK_DEFAULTVALUE] : null;
			}
			$defaultTextToWrite = (is_array($defaultTextToWrite) AND count($defaultTextToWrite) > 0) ? ','.implode(',',$defaultTextToWrite).',' : $defaultTextToWrite;
		} elseif($element->isLinked) {
			// default is the literal value from the source, optionally with separator if multi
			$linkMeta = explode('#*=:*', $thisDefaultEleValue[ELE_VALUE_SELECT_OPTIONS]);
			$linkFormId = $linkMeta[0];
			$linkElementId = $linkMeta[1];
			$dataHandler = new formulizeDataHandler($linkFormId);
			$thisDefaultEleValue[ELE_VALUE_SELECT_LINK_DEFAULTVALUE] = is_array($thisDefaultEleValue[ELE_VALUE_SELECT_LINK_DEFAULTVALUE]) ? $thisDefaultEleValue[ELE_VALUE_SELECT_LINK_DEFAULTVALUE] : array($thisDefaultEleValue[ELE_VALUE_SELECT_LINK_DEFAULTVALUE]);
			foreach($thisDefaultEleValue[ELE_VALUE_SELECT_LINK_DEFAULTVALUE] as $thisDefaultValue) {
				$defaultTextToWrite .= strlen($defaultTextToWrite) > 0 ? '*=+*:' : '';
				$thisDefaultValue = $dataHandler->getElementValueInEntry($thisDefaultValue,$linkElementId);
				$defaultTextToWrite .= $thisDefaultValue;
			}
		} else {
			// default is the literal text from the options list, optionally with separator if multi
			foreach($thisDefaultEleValue[ELE_VALUE_SELECT_OPTIONS] as $thisOption=>$isDefault) {
				if($isDefault) {
					$defaultTextToWrite .= strlen($defaultTextToWrite) > 0 ? '*=+*:' : '';
					$defaultTextToWrite .= $thisOption;
				}
			}
		}
		return $defaultTextToWrite;
	}

	// this method reads the current state of an element based on the user's input, and the admin options, and sets ele_value to what it needs to be so we can render the element correctly
	// it must return $ele_value, with the correct value set in it, so that it will render as expected in the render method
	// $element is the element object
	// $value is the value that was retrieved from the database for this element in the active entry.  It is a raw value, no processing has been applied, it is exactly what is in the database (as prepared in the prepareDataForSaving method and then written to the DB)
	// $entry_id is the ID of the entry being loaded
	function loadValue($element, $value, $entry_id) {

		$ele_value = $element->getVar('ele_value');

		// NOTE: unique delimiter used to identify LINKED select boxes, so they can be handled differently: #*=:*
		if(isset($ele_value[ELE_VALUE_SELECT_OPTIONS]) AND is_string($ele_value[ELE_VALUE_SELECT_OPTIONS]) AND strstr($ele_value[ELE_VALUE_SELECT_OPTIONS], "#*=:*")) {
			$ele_value[ELE_VALUE_SELECT_OPTIONS] .= "#*=:*".$value; // append the selected entry ids to the form and handle info in the element definition

		// not a linked element
		} else {

			// put the array into another array (clearing all default values)
			// then we modify our place holder array and then reassign
			$temparray = $ele_value[ELE_VALUE_SELECT_OPTIONS];
			if (is_array($temparray)) {
				$temparraykeys = array_keys($temparray);
				$temparray = array_fill_keys($temparraykeys, 0); // actually remove the defaults!
			} else {
				$temparraykeys = array();
			}

			if($temparraykeys[0] === "{FULLNAMES}" OR $temparraykeys[0] === "{USERNAMES}") { // ADDED June 18 2005 to handle pulling in usernames for the user's group(s)
				$ele_value[ELE_VALUE_SELECT_OPTIONS]['{SELECTEDNAMES}'] = explode("*=+*:", $value);
				if(count((array) $ele_value[ELE_VALUE_SELECT_OPTIONS]['{SELECTEDNAMES}']) > 1) { array_shift($ele_value[ELE_VALUE_SELECT_OPTIONS]['{SELECTEDNAMES}']); }
				// get the entry owner groups...
				$dataHandler = new formulizeDataHandler($element->getVar('fid'));
				$ele_value[ELE_VALUE_SELECT_OPTIONS]['{OWNERGROUPS}'] = $dataHandler->getEntryOwnerGroups($entry_id);
				return $ele_value; // return early in this case
			}

			// need to turn the prevEntry got from the DB into something the same as what is in the form specification so defaults show up right
			// we're comparing the output of these two lines against what is stored in the form specification, which does not have HTML escaped characters, and has extra slashes.  Assumption is that lack of HTML filtering is okay since only admins and trusted users have access to form creation.  Not good, but acceptable for now.

			global $myts;
			if(!$myts){
				$myts =& MyTextSanitizer::getInstance();
			}
			$value = $myts->undoHtmlSpecialChars($value);
			$selvalarray = explode("*=+*:", $value);
			$numberOfSelectedValues = strstr($value, "*=+*:") ? count((array) $selvalarray)-1 : 1; // if this is a multiple selection value, then count the array values, minus 1 since there will be one leading separator on the string.  Otherwise, it's a single value element so the number of selections is 1.
			$assignedSelectedValues = array();

			foreach($temparraykeys as $k) {

				// if there's a straight match (not a multiple selection)
				if((string)$k === (string)$value) {
					$temparray[$k] = 1;
					$assignedSelectedValues[$k] = true;

				// or if there's a match within a multiple selection array) -- TRUE is like ===, matches type and value
				} elseif( is_array($selvalarray) AND in_array((string)$k, $selvalarray, TRUE) ) {
					$temparray[$k] = 1;
					$assignedSelectedValues[$k] = true;

				// check for a match within an English translated value and assign that, otherwise set to zero
				// assumption is that development was done first in English and then translated
				// this safety net will not work if a system is developed first and gets saved data prior to translation in language other than English!!
				} else {
					foreach($selvalarray as $selvalue) {
						if(!is_numeric($k) AND strlen((string)$k) > 0 AND strpos((string)$k, '[en]') !== false AND trim(trans((string)$k, "en")) == trim(trans($selvalue,"en"))) {
							$temparray[$k] = 1;
							$assignedSelectedValues[$k] = true;
							continue 2; // move on to next iteration of outer loop
						}
					}
					if($temparray[$k] != 1) {
						$temparray[$k] = 0;
					}
				}
			}
			if((!empty($value) OR $value === 0 OR $value === "0") AND count((array) $assignedSelectedValues) < $numberOfSelectedValues) { // if we have not assigned the selected value from the db to one of the options for this element, then lets add it to the array of options, and flag it as out of range.  This is to preserve out of range values in the db that are there from earlier times when the options were different, and also to preserve values that were imported without validation on purpose
				foreach($selvalarray as $selvalue) {
					if(!isset($assignedSelectedValues[$selvalue]) AND (!empty($selvalue) OR $selvalue === 0 OR $selvalue === "0")) {
						$temparray[_formulize_OUTOFRANGE_DATA.$selvalue] = 1;
					}
				}
			}
			$ele_value[ELE_VALUE_SELECT_OPTIONS] = $temparray;
		} // end of IF we have a linked select box
		return $ele_value;
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

		global $xoopsDB, $xoopsUser, $myts;
		$form_handler = xoops_getmodulehandler('forms', 'formulize');
		$id_form = $element->getVar('fid');
		$formObject = $form_handler->get($id_form);

		if(is_string($ele_value[ELE_VALUE_SELECT_OPTIONS]) and strstr($ele_value[ELE_VALUE_SELECT_OPTIONS], "#*=:*")) { // if we've got a link on our hands... -- jwe 7/29/04

			// new process for handling links...May 10 2008...new datastructure for formulize 3.0
			$boxproperties = explode("#*=:*", $ele_value[ELE_VALUE_SELECT_OPTIONS]);
			$sourceFid = $boxproperties[0];
			$sourceHandle = $boxproperties[1];
			$sourceEntryIds = ($ele_value['snapshot'] == 0 AND strlen(trim($boxproperties[2],",")) > 0) ? explode(",", trim($boxproperties[2],",")) : array(); // if we snapshot values, then there are no entry ids
      $snapshotValues = ($ele_value['snapshot'] == 1 AND strlen(trim($boxproperties[2], "*=+*:")) > 0) ? explode("*=+*:", trim($boxproperties[2], "*=+*:")) : array(); // if we snapshot values, then put them into an array
			$sourceFormObject = $form_handler->get($sourceFid);

			// grab the user's groups and the module id
			// PRETTY SURE $regcode is related to ancient reg codes module not in use any longer
			global $regcode;
			if($regcode) { // if we're dealing with a registration code, determine group membership based on the code
				$reggroupsq = q("SELECT reg_codes_groups FROM " . XOOPS_DB_PREFIX . "_reg_codes WHERE reg_codes_code=\"".formulize_db_escape($regcode)."\"");
				$groups = explode("&8(%$", $reggroupsq[0]['reg_codes_groups']);
				if($groups[0] === "") { unset($groups); } // if a code has no groups associated with it, then kill the null value that will be in position 0 in the groups array.
				$groups[] = XOOPS_GROUP_USERS;
				$groups[] = XOOPS_GROUP_ANONYMOUS;
			} else {
				$groups = $xoopsUser ? $xoopsUser->getGroups() : array(0=>XOOPS_GROUP_ANONYMOUS);
			}

			$ele_value[ELE_VALUE_SELECT_LINK_USEONLYUSERSENTRIES] = isset($ele_value[ELE_VALUE_SELECT_LINK_USEONLYUSERSENTRIES]) ? $ele_value[ELE_VALUE_SELECT_LINK_USEONLYUSERSENTRIES] : 0;
			$pgroupsfilter = prepareLinkedElementGroupFilter($sourceFid, $ele_value[ELE_VALUE_SELECT_LINK_LIMITGROUPS], $ele_value[ELE_VALUE_SELECT_LINK_USERSGROUPS], $ele_value[ELE_VALUE_SELECT_LINK_ALLGROUPS], $ele_value[ELE_VALUE_SELECT_LINK_USEONLYUSERSENTRIES]);

			list($conditionsfilter, $conditionsfilter_oom, $parentFormFrom) = buildConditionsFilterSQL($ele_value[ELE_VALUE_SELECT_LINK_FILTERS], $sourceFid, $entry_id, $owner, $formObject, "t1");
			catalogDynamicFilterConditionElements($markupName, $ele_value[ELE_VALUE_SELECT_LINK_FILTERS], $formObject);

			// if there is a restriction in effect, then add some SQL to reject options that have already been selected ??
			$restrictSQL = "";
			if($ele_value[ELE_VALUE_SELECT_RESTRICTSELECTION]) {
        if($ele_value[ELE_VALUE_SELECT_MULTIPLE]) {
					$restrictSQL = "
						AND (
							NOT EXISTS (
								SELECT 1 FROM ".$xoopsDB->prefix("formulize_".$formObject->getVar('form_handle'))." AS t4 WHERE t4.`".$element->getVar('ele_handle')."` LIKE CONCAT( '%,', t1.`entry_id` , ',%' ) AND t4.entry_id != ".intval($entry_id);
				} else {
          $restrictSQL = "
						AND (
            	NOT EXISTS (
              	SELECT 1 FROM ".$xoopsDB->prefix("formulize_".$formObject->getVar('form_handle'))." AS t4 WHERE t4.`".$element->getVar('ele_handle')."` = t1.`entry_id` AND t4.entry_id != ".intval($entry_id);
          $restrictSQL .= $this->addEntryRestrictionSQL($ele_value[9], $id_form, $groups); // pass in the flag about restriction scope, and the form id, and the groups
					$restrictSQL .= "
							) OR EXISTS (
            		SELECT 1 FROM ".$xoopsDB->prefix("formulize_".$formObject->getVar('form_handle'))." AS t4 WHERE t4.`".$element->getVar('ele_handle')."` = t1.`entry_id` AND t4.entry_id = ".intval($entry_id);
				}
				$restrictSQL .= $this->addEntryRestrictionSQL($ele_value[9], $id_form, $groups); // pass in the flag about restriction scope, and the form id, and the groups
				$restrictSQL .= "
						) OR EXISTS (
							SELECT 1 FROM ".$xoopsDB->prefix("formulize_".$formObject->getVar('form_handle'))." AS t4 WHERE t4.`".$element->getVar('ele_handle')."` LIKE CONCAT( '%,', t1.`entry_id` , ',%' ) AND t4.entry_id = ".intval($entry_id);
				$restrictSQL .= $this->addEntryRestrictionSQL($ele_value[9], $id_form, $groups);
				$restrictSQL .= "
						)
					)";
			}

			static $cachedSourceValuesQ = array();
			static $cachedSourceValuesAutocompleteFile = array();
			// horrible hack to handle cases where new subform entries are created and we need to flush values that would have been generated when we were fake making the page before we knew a new subform entry is what we were really aiming for. See comment where global is instantiated.
			// all comes from not having proper controller in charge of what we should be displaying. Ugh.
			if(isset($GLOBALS['formulize_unsetSelectboxCaches'])) {
				//formulize_benchmark('unsetting caches!');
				$cachedSourceValuesQ = array();
				$cachedSourceValuesAutocompleteFile = array();
				unset($GLOBALS['formulize_unsetSelectboxCaches']);
			}

			// setup the sort order based on ele_value[12], which is an element id number
			$sortOrder = $ele_value[ELE_VALUE_SELECT_LINK_SORTORDER] == 2 ? " DESC" : "ASC";
			if($ele_value[ELE_VALUE_SELECT_LINK_SORT]=="none" OR !$ele_value[ELE_VALUE_SELECT_LINK_SORT]) {
				$sortOrderClause = " ORDER BY t1.`$sourceHandle` $sortOrder";
			} else {
				list($sortHandle) = convertElementIdsToElementHandles(array($ele_value[ELE_VALUE_SELECT_LINK_SORT]), $sourceFormObject->getVar('id_form'));
				$sortOrderClause = " ORDER BY t1.`$sortHandle` $sortOrder";
			}

			// if no extra elements are selected for display as a form element, then display the linked element
			if (!is_array($ele_value[ELE_VALUE_SELECT_LINK_ALTFORMELEMENTS]) OR 0 == count((array) $ele_value[ELE_VALUE_SELECT_LINK_ALTFORMELEMENTS]) OR $ele_value[ELE_VALUE_SELECT_LINK_ALTFORMELEMENTS][0] == 'none') {
				$linked_columns = array($boxproperties[1]);
			} else {
				$linked_columns = convertElementIdsToElementHandles($ele_value[ELE_VALUE_SELECT_LINK_ALTFORMELEMENTS], $sourceFormObject->getVar('id_form'));
								// remove empty entries, which can happen if the "use the linked field selected above" option is selected
								$linked_columns = array_filter($linked_columns);
			}
			if (is_array($linked_columns)) {
					$select_column = "t1.`".implode("`, t1.`", $linked_columns)."`";
			} else {
					$select_column = "t1.`{$linked_columns}`";	// in this case, it's just one linked column
			}

			list($sourceEntrySafetyNetStart, $sourceEntrySafetyNetEnd) = prepareLinkedElementSafetyNets($sourceEntryIds);

			$extra_clause = prepareLinkedElementExtraClause($pgroupsfilter, $parentFormFrom, $sourceEntrySafetyNetStart);

			// if we're supposed to limit based on the values in an arbitrary other element, add those to the clause too
			$directLimit = '';
			$dbValue = '';
			if(isset($ele_value[ELE_VALUE_SELECT_LINK_LIMITBYELEMENT]) AND is_numeric($ele_value[ELE_VALUE_SELECT_LINK_LIMITBYELEMENT])) {
				if($optionsLimitByElement_ElementObject = $this->get($ele_value[ELE_VALUE_SELECT_LINK_LIMITBYELEMENT])) {
					$dbValue = '';
					if(isset($GLOBALS['formulize_asynchronousFormDataInDatabaseReadyFormat'][$entry_id][$optionsLimitByElement_ElementObject->getVar('ele_handle')])) {
						$dbValue = $GLOBALS['formulize_asynchronousFormDataInDatabaseReadyFormat'][$entry_id][$optionsLimitByElement_ElementObject->getVar('ele_handle')];
					} else {
						list($optionsLimitFilter, $optionsLimitFilter_oom, $optionsLimitFilter_parentFormFrom) = buildConditionsFilterSQL($ele_value[ELE_VALUE_SELECT_LINK_LIMITBYELEMENTFILTER], $optionsLimitByElement_ElementObject->getVar('id_form'), $entry_id, $owner, $formObject, "olf");
						catalogDynamicFilterConditionElements($markupName, $ele_value[ELE_VALUE_SELECT_LINK_LIMITBYELEMENTFILTER], $formObject);
						$optionsLimitFilterFormObject = $form_handler->get($optionsLimitByElement_ElementObject->getVar('id_form'));
						$sql = "SELECT ".$optionsLimitByElement_ElementObject->getVar('ele_handle')." FROM ".$xoopsDB->prefix('formulize_'.$optionsLimitFilterFormObject->getVar('form_handle'))." as olf $optionsLimitFilter_parentFormFrom WHERE 1 $optionsLimitFilter $optionsLimitFilter_oom";
						if($res = $xoopsDB->query($sql)) {
							if($xoopsDB->getRowsNum($res)==1) {
								$row = $xoopsDB->fetchRow($res);
								$dbValue = $row[0];
							}
						}
					}
					$directLimit = convertEntryIdsFromDBToArray($dbValue);
				}
			}
			if($directLimit) {
				$directLimit = ' AND t1.entry_id IN ('.implode(',',$directLimit).') ';
			}

			$selfReferenceExclusion = generateSelfReferenceExclusionSQL($entry_id, $id_form, $sourceFid, $ele_value, 't1');

			// $extra_clause will always include WHERE, must come first
			// all "AND" clauses come after that, through to $sourceEntrySafetyNetEnd
			// $sourceEntrySafetyNetEnd concludes the "AND" clauses, and starts the "OR" clauses, which must always come last
			// all clauses must be self contained, which means in ( ) if they have multiple parts, and must be introduced with AND if in the first section, or OR if in the last section (after $sourceEntrySafetyNetEnd)
			$sourceValuesQ = "SELECT t1.entry_id, $select_column
				FROM ".$xoopsDB->prefix("formulize_".$sourceFormObject->getVar('form_handle'))." AS t1
				$extra_clause
				$conditionsfilter
				$conditionsfilter_oom
				$restrictSQL
				$directLimit
				$selfReferenceExclusion
				$sourceEntrySafetyNetEnd
				GROUP BY t1.entry_id $sortOrderClause, t1.entry_id ASC ";

			// SETUP THE ELEMENT
			if(!$isDisabled) {
				// set the default selections, based on the entry_ids that have been selected as the defaults, if applicable
				$hasNoValues = count((array) $sourceEntryIds) == 0 ? true : false;
				$useDefaultsWhenEntryHasNoValue = $element->getVar('ele_use_default_when_blank');
				if(($entry_id == "new" OR ($useDefaultsWhenEntryHasNoValue AND $hasNoValues)) AND ((is_array($ele_value[ELE_VALUE_SELECT_LINK_DEFAULTVALUE]) AND count((array) $ele_value[ELE_VALUE_SELECT_LINK_DEFAULTVALUE]) > 0) OR $ele_value[ELE_VALUE_SELECT_LINK_DEFAULTVALUE])) {
					$defaultSelected = $ele_value[ELE_VALUE_SELECT_LINK_DEFAULTVALUE];
				} else {
					$defaultSelected = "";
				}
				$form_ele = new XoopsFormSelect($caption, $markupName, $defaultSelected, $ele_value[ELE_VALUE_SELECT_NUMROWS], $ele_value[ELE_VALUE_SELECT_MULTIPLE]);
				$form_ele->setExtra("onchange=\"javascript:formulizechanged=1;\" jquerytag='$markupName'");
				if($ele_value[ELE_VALUE_SELECT_NUMROWS] == 1) { // add the initial default entry, singular or plural based on whether the box is one line or not.
					$form_ele->addOption("none", _AM_FORMLINK_PICK);
				}
			} else {
				$disabledHiddenValue = array();
				$disabledOutputText = array();
			}

			// GATHER THE OPTIONS
			if(!isset($cachedSourceValuesQ[intval($ele_value[ELE_VALUE_SELECT_LINK_SNAPSHOT])][$sourceValuesQ])) {

				$linkedElementOptions = array();
				$reslinkedvaluesq = $xoopsDB->query($sourceValuesQ);
				if($reslinkedvaluesq) {
					$linked_column_count = count((array) $linked_columns);
					while($rowlinkedvaluesq = $xoopsDB->fetchRow($reslinkedvaluesq)) {
						$linked_column_values = array();
						foreach (range(1, $linked_column_count) as $linked_column_index) {
							$linked_value = '';
							if ($rowlinkedvaluesq[$linked_column_index] !== "") {
								$linked_value = prepvalues($rowlinkedvaluesq[$linked_column_index], $linked_columns[$linked_column_index - 1], $rowlinkedvaluesq[0]);
								$linked_value = $linked_value[0];
							}
							if($linked_value != '' OR is_numeric($linked_value)) {
								$linked_column_values[] = $linked_value;
							}
						}
						if(count((array) $linked_column_values)>0) {
							$leoIndex = $ele_value[ELE_VALUE_SELECT_LINK_SNAPSHOT] ? implode(" | ", $linked_column_values) : $rowlinkedvaluesq[0];
							$linkedElementOptions[$leoIndex] = implode(" | ", $linked_column_values);
						}
					}
				}
				// in case there are duplicate options, and there's a selected value that is a duplicate, then preserve the duplicate value rather than the first duplicate in the list
				// do this by removing duplicate values from the list, other than the one that was selected
				// convoluted process preserves ordering of the array
				if(count((array) $sourceEntryIds) > 0) {
					foreach($sourceEntryIds as $sei) {
						$targetKeys = array_keys($linkedElementOptions, $linkedElementOptions[$sei]);
						foreach($targetKeys as $tk) {
							if($sei != $tk) {
								unset($linkedElementOptions[$tk]);
							}
						}
					}
				}
				$linkedElementOptions = array_unique($linkedElementOptions); // remove duplicates
				$cachedSourceValuesQ[intval($ele_value[ELE_VALUE_SELECT_LINK_SNAPSHOT])][$sourceValuesQ] = $linkedElementOptions;

				/* ALTERED - 20100318 - freeform - jeff/julian - start */
				if(!$isDisabled AND $ele_value[ELE_VALUE_SELECT_AUTOCOMPLETE] == 1) {
					// write the possible values to a cached file so we can look them up easily when we need them, don't want to actually send them to the browser, since it could be huge, but don't want to replicate all the logic that has already gathered the values for us, each time there's an ajax request
					$cachedLinkedOptionsFileName = "formulize_linkedOptions_".str_replace(".","",microtime(true));
					formulize_scandirAndClean(XOOPS_ROOT_PATH."/cache/", "formulize_linkedOptions_");
					$the_values = array();
					asort($linkedElementOptions);
					foreach($linkedElementOptions as $id=>$text) {
						$the_values[$id] = undoAllHTMLChars(trans($text));
					}
					file_put_contents(XOOPS_ROOT_PATH."/cache/$cachedLinkedOptionsFileName",
						"<?php\n\$$cachedLinkedOptionsFileName = ".var_export($the_values, true).";\n");
					$cachedSourceValuesAutocompleteFile[intval($ele_value[ELE_VALUE_SELECT_LINK_SNAPSHOT])][$sourceValuesQ] = $cachedLinkedOptionsFileName;
				}
			}

			// gather the default values...
			$default_value = array();
			if(count((array) $sourceEntryIds) > 0) {
				$default_value = $sourceEntryIds;
			} elseif(count((array) $snapshotValues) > 0) {
				$default_value = $snapshotValues;
			}
			// if we're rendering an autocomplete box...
			if(!$isDisabled AND $ele_value[ELE_VALUE_SELECT_AUTOCOMPLETE] == 1) {
				$default_value_user = array();
				foreach($default_value as $dv) {
					$default_value_user[$dv] = count((array) $snapshotValues) > 0 ? $dv : $cachedSourceValuesQ[intval($ele_value[ELE_VALUE_SELECT_LINK_SNAPSHOT])][$sourceValuesQ][$dv]; // take the literal or the reference, depending if we snapshot or not
				}
				$renderedComboBox = $this->formulize_renderQuickSelect($markupName, $cachedSourceValuesAutocompleteFile[intval($ele_value[ELE_VALUE_SELECT_LINK_SNAPSHOT])][$sourceValuesQ], $default_value, $default_value_user, $ele_value[ELE_VALUE_SELECT_MULTIPLE], (isset($ele_value[ELE_VALUE_SELECT_AUTOCOMPLETEALLOWSNEW]) ? $ele_value[ELE_VALUE_SELECT_AUTOCOMPLETEALLOWSNEW] : 0));
				$form_ele = new xoopsFormLabel($caption, $renderedComboBox, $markupName);

			// if we're rendering a disabled autocomplete box
			} elseif($isDisabled AND $ele_value[ELE_VALUE_SELECT_AUTOCOMPLETE] == 1) {
				if($ele_value[ELE_VALUE_SELECT_LINK_SNAPSHOT] == 1) {
					$disabledOutputText = $snapshotValues;
				} else {
					foreach($default_value as $dv) {
						$disabledOutputText[] = $cachedSourceValuesQ[intval($ele_value[ELE_VALUE_SELECT_LINK_SNAPSHOT])][$sourceValuesQ][$dv];
					}
				}
			}

			// rendering a non-autocomplete box
			if($ele_value[ELE_VALUE_SELECT_AUTOCOMPLETE] == 0) {
				if(!$isDisabled) {
					$form_ele->addOptionArray($cachedSourceValuesQ[intval($ele_value[ELE_VALUE_SELECT_LINK_SNAPSHOT])][$sourceValuesQ]);
				}
				foreach($default_value as $thisDV) {
					if(!$isDisabled) {
						$form_ele->setValue($thisDV);
					} else {
						$disabledOutputText[] = $cachedSourceValuesQ[intval($ele_value[ELE_VALUE_SELECT_LINK_SNAPSHOT])][$sourceValuesQ][$thisDV]; // the text value of the option(s) that are currently selected
					}
				}
			}

			$GLOBALS['formulize_lastRenderedElementOptions'] = $cachedSourceValuesQ[intval($ele_value[ELE_VALUE_SELECT_LINK_SNAPSHOT])][$sourceValuesQ];

			if($isDisabled) {
				$form_ele = new XoopsFormLabel($caption, implode(", ", $disabledOutputText), $markupName);
			} elseif($ele_value[ELE_VALUE_SELECT_AUTOCOMPLETE] == 0) {
				// this is a hack because the size attribute is private and only has a getSize and not a setSize, setting the size can only be done through the constructor
				$count = count((array)  $form_ele->getOptions() );
				$size = $ele_value[ELE_VALUE_SELECT_NUMROWS];
				$new_size = ( $count < $size ) ? $count : $size;
				$form_ele->_size = $new_size;
			}
			/* ALTERED - 20100318 - freeform - jeff/julian - stop */

		}
			else // or if we don't have a link...
		{
			$selected = array();
			$options = array();
			$disabledOutputText	= array();
			$disabledHiddenValue = array();
			$disabledHiddenValues = "";
			$hiddenOutOfRangeValuesToWrite = array();

			// add the initial default entry, singular or plural based on whether the box is one line or not.
			if($ele_value[0] == 1) {
				$options["none"] = _AM_FORMLINK_PICK;
			}

			// set opt_count to 1 if the box is NOT a multiple selection box. -- jwe 7/26/04
			if($ele_value[ELE_VALUE_SELECT_MULTIPLE]) {
				$opt_count = 0;
			} else {
				$opt_count = 1;
			}

			if(is_array($ele_value[ELE_VALUE_SELECT_OPTIONS])) {
				foreach($ele_value[ELE_VALUE_SELECT_OPTIONS] as $iKey=>$iValue) {
					$i = array('key'=>$iKey, 'value'=>$iValue); // kinda ugly compatibility hack to refactor the really ugly use of 'each' for PHP 8
					// handle requests for full names or usernames -- will only kick in if there is no saved value (otherwise ele_value will have been rewritten by the loadValues function in the form display
					// note: if the user is about to make a proxy entry, then the list of users displayed will be from their own groups, but not from the groups of the user they are about to make a proxy entry for.  ie: until the proxy user is known, the choice of users for this list can only be based on the current user.  This could lead to confusing or buggy situations, such as users being selected who are outside the groups of the proxy user (who will become the owner) and so there will be an invalid value stored for this element in the db.

					// DO WE REALLY NEED TO DO EVERYTHING INSIDE THIS IF EVERY TIME THROUGH THE LOOP?? ANCIENT BAD ARCHITECTURE

					if($i['key'] === "{FULLNAMES}" OR $i['key'] === "{USERNAMES}") { // ADDED June 18 2005 to handle pulling in usernames for the user's group(s)
						if($i['key'] === "{FULLNAMES}") { $nametype = "name"; }
						if($i['key'] === "{USERNAMES}") { $nametype = "uname"; }
						if(isset($ele_value[ELE_VALUE_SELECT_OPTIONS]['{OWNERGROUPS}'])) {
							$groups = $ele_value[ELE_VALUE_SELECT_OPTIONS]['{OWNERGROUPS}'];
						} else {
							global $regcode;
							// REALLY DON'T THINK REGCODE IS USED ANYMORE
							if($regcode) { // if we're dealing with a registration code, determine group membership based on the code
								$reggroupsq = q("SELECT reg_codes_groups FROM " . XOOPS_DB_PREFIX . "_reg_codes WHERE reg_codes_code=\"".formulize_db_escape($regcode)."\"");
								$groups = explode("&8(%$", $reggroupsq[0]['reg_codes_groups']);
								if($groups[0] === "") { unset($groups); } // if a code has no groups associated with it, then kill the null value that will be in position 0 in the groups array.
								$groups[] = XOOPS_GROUP_USERS;
								$groups[] = XOOPS_GROUP_ANONYMOUS;
							} else {
								$groups = $xoopsUser ? $xoopsUser->getGroups() : array(0=>XOOPS_GROUP_ANONYMOUS);
							}
						}
						$pgroups = array();
						$declaredUsersGroups = $groups;
						if($ele_value[ELE_VALUE_SELECT_LINK_LIMITGROUPS]) {
							$scopegroups = explode(",",$ele_value[ELE_VALUE_SELECT_LINK_LIMITGROUPS]);
							if(!in_array("all", $scopegroups)) {
								$groups = $scopegroups;
							} else { // use all
								if(!$ele_value[ELE_VALUE_SELECT_LINK_USERSGROUPS]) { // really use all (otherwise, we're just going with all user's groups, so existing value of $groups will be okay
									unset($groups);
									global $xoopsDB;
									$allgroupsq = q("SELECT groupid FROM " . $xoopsDB->prefix("groups")); //  . " WHERE groupid != " . XOOPS_GROUP_USERS); // removed exclusion of registered users group March 18 2009, since it doesn't make sense in this situation.  All groups should mean everyone, period.
									foreach($allgroupsq as $thisgid) {
										$groups[] = $thisgid['groupid'];
									}
								}
							}
						}

						$namelist = gatherNames($groups, $nametype, $ele_value[ELE_VALUE_SELECT_LINK_ALLGROUPS], $ele_value[ELE_VALUE_SELECT_LINK_FILTERS], $ele_value[ELE_VALUE_SELECT_LINK_USERSGROUPS], $declaredUsersGroups);

						$directLimitUserIds = false;
						if(isset($ele_value[ELE_VALUE_SELECT_LINK_LIMITBYELEMENT]) AND is_numeric($ele_value[ELE_VALUE_SELECT_LINK_LIMITBYELEMENT])) {
							if($optionsLimitByElement_ElementObject = $this->get($ele_value[ELE_VALUE_SELECT_LINK_LIMITBYELEMENT])) {
								list($optionsLimitFilter, $optionsLimitFilter_oom, $optionsLimitFilter_parentFormFrom) = buildConditionsFilterSQL($ele_value[ELE_VALUE_SELECT_LINK_LIMITBYELEMENTFILTER], $optionsLimitByElement_ElementObject->getVar('id_form'), $entry_id, $owner, $formObject, "olf");
								catalogDynamicFilterConditionElements($markupName, $ele_value[ELE_VALUE_SELECT_LINK_LIMITBYELEMENTFILTER], $formObject);
								$optionsLimitFilterFormObject = $form_handler->get($optionsLimitByElement_ElementObject->getVar('id_form'));
								$sql = "SELECT ".$optionsLimitByElement_ElementObject->getVar('ele_handle')." FROM ".$xoopsDB->prefix('formulize_'.$optionsLimitFilterFormObject->getVar('form_handle'))." as olf $optionsLimitFilter_parentFormFrom WHERE 1 $optionsLimitFilter $optionsLimitFilter_oom";
								if($res = $xoopsDB->query($sql)) {
									if($xoopsDB->getRowsNum($res)==1) {
										$row = $xoopsDB->fetchRow($res);
										$elementObjectEleValue = $optionsLimitByElement_ElementObject->getVar('ele_value');
										if(is_array($elementObjectEleValue[ELE_VALUE_SELECT_OPTIONS]) AND strstr(implode('',array_keys($elementObjectEleValue[ELE_VALUE_SELECT_OPTIONS])), "NAMES}")) {
											$directLimitUserIds = explode("*=+*:",trim($row[0], "*=+*:"));
										}
									}
								}
							}
						}

						foreach($namelist as $auid=>$aname) {
							if($directLimitUserIds AND !in_array($auid, $directLimitUserIds)) { continue; }
							$options[$auid] = $aname;
						}
					} elseif($i['key'] === "{SELECTEDNAMES}") { // loadValue in formDisplay will create a second option with this key that contains an array of the selected values
						$selected = $i['value'];
					} elseif($i['key'] === "{OWNERGROUPS}") { // do nothing with this piece of metadata that gets set in loadValue, since it's used above
					} else { // regular selection list....
						$options[$opt_count] = $myts->stripSlashesGPC($i['key']);
						if(strstr($i['key'], _formulize_OUTOFRANGE_DATA)) {
							$hiddenOutOfRangeValuesToWrite[$opt_count] = str_replace(_formulize_OUTOFRANGE_DATA, "", $i['key']); // if this is an out of range value, grab the actual value so we can stick it in a hidden element later
						}
						if( $i['value'] > 0 ){
							$selected[] = $opt_count;
						}
						$opt_count++;
					}
				}
			}

			$count = count((array) $options);
			$size = $ele_value[ELE_VALUE_SELECT_NUMROWS];
			$final_size = ( $count < $size ) ? $count : $size;

			$form_ele1 = new XoopsFormSelect(
				$caption,
				$markupName,
				$selected,
				$final_size,	//	size
				$ele_value[ELE_VALUE_SELECT_MULTIPLE]	  //	multiple
			);

			$form_ele1->setExtra("onchange=\"javascript:formulizechanged=1;\" jquerytag='$markupName'");

			// must check the options for uitext before adding to the element -- aug 25, 2007
			foreach($options as $okey=>$ovalue) {
				$options[$okey] = formulize_swapUIText($ovalue, $element->getVar('ele_uitext'));
			}
			$form_ele1->addOptionArray($options);
			$GLOBALS['formulize_lastRenderedElementOptions'] = $options;

			if($selected) {
				if(is_array($selected)) {
					$hiddenElementName = $ele_value[ELE_VALUE_SELECT_MULTIPLE] ? $form_ele1->getName()."[]" : $form_ele1->getName();
					foreach($selected as $thisSelected) {
						$disabledOutputText[] = $options[$thisSelected];
						$disabledHiddenValue[] = "<input type=hidden name=\"$hiddenElementName\" value=\"$thisSelected\">";
					}
				} elseif($ele_value[ELE_VALUE_SELECT_MULTIPLE]) { // need to keep [] in the hidden element name if multiple values are expected, even if only one is chosen
					$disabledOutputText[] = $options[$selected];
					$disabledHiddenValue[] = "<input type=hidden name=\"".$form_ele1->getName()."[]\" value=\"$selected\">";
				} else {
					$disabledOutputText[] = $options[$selected];
					$disabledHiddenValue[] = "<input type=hidden name=\"".$form_ele1->getName()."\" value=\"$selected\">";
				}
			}

			$renderedHoorvs = "";
			if(count((array) $hiddenOutOfRangeValuesToWrite) > 0) {
				foreach($hiddenOutOfRangeValuesToWrite as $hoorKey=>$hoorValue) {
					$thisHoorv = new xoopsFormHidden('formulize_hoorv_'.$element->getVar('ele_id').'_'.$hoorKey, $hoorValue);
					$renderedHoorvs .= $thisHoorv->render() . "\n";
					unset($thisHoorv);
				}
			}

			if($isDisabled) {
				$disabledHiddenValues = implode("\n", $disabledHiddenValue); // glue the individual value elements together into a set of values
				$renderedElement = implode(", ", $disabledOutputText);
			} elseif($ele_value[ELE_VALUE_SELECT_AUTOCOMPLETE] == 1) {
				// autocomplete construction: make sure that $renderedElement is the final output of this chunk of code
				// write the possible values to a cached file so we can look them up easily when we need them,
				//don't want to actually send them to the browser, since it could be huge,
				//but don't want to replicate all the logic that has already gathered the values for us, each time there's an ajax request
				$cachedLinkedOptionsFileName = "formulize_Options_".str_replace(".","",microtime(true));
				formulize_scandirAndClean(XOOPS_ROOT_PATH."/cache/", "formulize_Options_");
				$the_values = array();
				foreach($options as $id => $text) {
					$the_values[$id] = undoAllHTMLChars(trans($text));
				}
				file_put_contents(XOOPS_ROOT_PATH."/cache/$cachedLinkedOptionsFileName",
					"<?php\n\$$cachedLinkedOptionsFileName = ".var_export($the_values, true).";\n");
				$defaultSelected = is_array($selected) ? $selected[0] : $selected;
				$defaultSelectedUser = $options[$defaultSelected];
				if(is_array($selected) AND $ele_value[ELE_VALUE_SELECT_MULTIPLE]) { // multiselect autocompletes work differently, send all values
					$defaultSelected = $selected;
					$defaultSelectedUser = array();
					foreach($selected as $thisSel) {
						$defaultSelectedUser[$thisSel] = $options[$thisSel];
					}
					natsort($defaultSelectedUser);
				}
				$defaultSelected = !is_array($defaultSelected) ? array($defaultSelected) : $defaultSelected;
				$defaultSelectedUser = !is_array($defaultSelectedUser) ? array($defaultSelectedUser) : $defaultSelectedUser;
				$renderedComboBox = $this->formulize_renderQuickSelect($markupName, $cachedLinkedOptionsFileName, $defaultSelected, $defaultSelectedUser, $ele_value[ELE_VALUE_SELECT_MULTIPLE], (isset($ele_value[ELE_VALUE_SELECT_AUTOCOMPLETEALLOWSNEW]) ? $ele_value[ELE_VALUE_SELECT_AUTOCOMPLETEALLOWSNEW] : 0));
				$form_ele2 = new xoopsFormLabel($caption, $renderedComboBox, $markupName);
				$renderedElement = $form_ele2->render();
			} else { // normal element
				$renderedElement = $form_ele1->render();
			}

			$form_ele = new XoopsFormLabel(
				$caption,
				"<nobr>$renderedElement</nobr>\n$renderedHoorvs\n$disabledHiddenValues\n",
				$markupName
			);

		} // end of if we have a link on our hands. -- jwe 7/29/04

		return $form_ele;
	}

	function formulize_renderQuickSelect($markupName, $cachedLinkedOptionsFilename, $default_value, $default_value_user, $multiple = 0, $allow_new_values = 0) {

		if($multiple) {
			global $easiestml_lang;
			$selectedValues = $default_value_user;
			$default_value_user = '';
			$frenchSpace = $easiestml_lang == 'fr' ? '&nbsp;' : '';
			$multipleClass = 'formulize_autocomplete_multiple';
		} else {
			$selectedValues = '';
			$default_value_user = $default_value_user[key($default_value_user)];
			$multipleClass = '';
		}

		// put markup for autocomplete boxes here
		$output = "<div class=\"formulize_autocomplete\"><input type='text' class='formulize_autocomplete $multipleClass' name='".$markupName."_user' id = '".$markupName."_user' autocomplete='off' value='".str_replace("'", "&#039;", $default_value_user)."' aria-describedby='".$markupName."-help-text' /></div><img src='".XOOPS_URL."/modules/formulize/images/magnifying_glass.png' class='autocomplete-icon'>\n";
		$output .= "<div id='".$markupName."_defaults'>\n";
		if(!$multiple) {
				$output .= "<input type='hidden' name='".$markupName."' id = '".$markupName."' value='".$default_value[0]."' />\n";
		} else {
				$output .= "<input type='hidden' name='last_selected_".$markupName."' id = 'last_selected_".$markupName."' value='' />\n";
				foreach($default_value as $i=>$this_default_value) {
						if($this_default_value OR $this_default_value === 0) {
								$output .= "<input type='hidden' name='".$markupName."[]' jquerytag='".$markupName."' id='".$markupName."_".$i."' target='".str_replace("'", "&#039;", $i)."' value='".str_replace("'", "&#039;", $this_default_value)."' />\n";
						}
				}
		}
		$output .= '</div>';
		if(is_array($selectedValues) OR $multiple) {
				$output .= '<div id="'.$markupName.'_formulize_autocomplete_selections" class="formulize_autocomplete_selections" style="padding-right: 10px;">';
				foreach($selectedValues as $id=>$value) {
						if($value OR $value === 0) {
								$output .= "<p class='auto_multi auto_multi_".$markupName."' target='".str_replace("'", "&#039;", $id)."'>".str_replace("'", "&#039;", $value)."</p>\n";
						}
				}
				$output .= "</div>\n";
		}

		// jQuery code for make it work as autocomplete
		// need to wrap it in window.load because Chrome does unusual things with the DOM and makes it ready before it's populated with content!!  (so document.ready doesn't do the trick)
		// item 16 determines whether the list box allows new values to be entered

		// setup the autocomplete, and make it pass the value of the selected item into the hidden element
		// note reference to master jQuery not jq3 in order to cause the change event to affect the normal scope of javascript in the page. Very funky!
		$output .= "<script type='text/javascript'>

		function formulize_initializeAutocomplete".$markupName."() {
			";
			// if it's a single quickselect with an existing value, don't clear the initial value if the user just focuses and blurs
			if(!$multiple AND $default_value_user) {
					$output .= $markupName."_clearbox = false;\n";
			} else {
					// for all other quickselects, clear whatever the user might have typed, if it isn't a matching value to a valid option
					$output .= $markupName."_clearbox = true;\n";
			}
			$output .= "
			jQuery('#".$markupName."_user').autocomplete({
				source: function(request, response) {
				var excludeCurrentSelection = jQuery('input[name=\"".$markupName."[]\"]').map(function () { return $(this).val(); }).get().join(',');
				jQuery.get('".XOOPS_URL."/modules/formulize/include/formulize_quickselect.php?cache=".$cachedLinkedOptionsFilename."&allow_new_values=".$allow_new_values."&term='+encodeURIComponent(request.term)+'&current='+encodeURIComponent(excludeCurrentSelection), function(data) {
					response(eval(data));
				})},
				minLength: 1,
				delay: 0,
				select: function(event, ui) {
					event.preventDefault();
					if(ui.item.value != 'none') {
						jQuery('#".$markupName."_user').val(ui.item.label.replace('"._formulize_NEW_VALUE."', ''));
						setAutocompleteValue('".$markupName."', ui.item.value, 1, ".$multiple.");
						".$markupName."_clearbox = false;
					} else {
						jQuery('#".$markupName."_user').val('');
						setAutocompleteValue('".$markupName."', ui.item.value, 1, ".$multiple.");
					}
				},
				focus: function( event, ui ) {
					event.preventDefault();
					if(ui.item.value != 'none') {
						var itemLabel = ui.item.label;
						var itemLabelPrefix = itemLabel.substr(0, ".strlen(_formulize_NEW_VALUE).");
						if(itemLabelPrefix == '"._formulize_NEW_VALUE."') {
							itemLabel = itemLabel.substr(".strlen(_formulize_NEW_VALUE).");
						}
						jQuery('#".$markupName."_user').val(itemLabel);
						setAutocompleteValue('".$markupName."', ui.item.value, 0, ".$multiple.");
						".$markupName."_clearbox = false;
					} else {
						setAutocompleteValue('".$markupName."', ui.item.value, 0, ".$multiple.");
					}
				},
				search: function(event, ui) {
					".$markupName."_clearbox = true;
				}";
				if($allow_new_values) {
					// if we allow new values and the first (and therefore only) response is a new value item, then mark that for saving right away without selection by user
					$output .= ",
					response: function(event, ui) {
						if(ui.content.length == 1 && typeof ui.content[0].value === 'string' && ui.content[0].value.indexOf('newvalue:')>-1) {
							setAutocompleteValue('".$markupName."', ui.content[0].value, 0, ".$multiple.");
							".$markupName."_clearbox = false;
						}
					}";
				}
				if($multiple) {
					$output .= ",
					close: function(event, ui) {
						value = jQuery('#last_selected_".$markupName."').val();
						label = jQuery('#".$markupName."_user').val();
						label = label.replace('"._formulize_NEW_VALUE."', '');
						if(value != 'none' && (value || value === 0)) {
							if(isNaN(value)) {
								value = String(value).replace(/'/g,\"&#039;\");
								i = value;
							} else {
								i = parseInt(jQuery('#".$markupName."_defaults').children().last().attr('target')) + 1;
							}
							jQuery('#".$markupName."_defaults').append(\"<input type='hidden' name='".$markupName."[]' jquerytag='".$markupName."' id='".$markupName."_\"+i+\"' target='\"+i+\"' value='\"+value+\"' />\");
							jQuery('#".$markupName."_formulize_autocomplete_selections').append(\"<p class='auto_multi auto_multi_".$markupName."' target='\"+value+\"'>\"+label+\"</p>\");
							jQuery('#".$markupName."_user').val('');
							jQuery('#last_selected_".$markupName."').val('');
							triggerChangeOnMultiValueAutocomplete('".$markupName."');
						}
					}";
				}
			$output .= "
			}).blur(function() {
				if(".$markupName."_clearbox == true || jQuery('#".$markupName."_user').val() == '') {
					jQuery('#".$markupName."_user').val('');
					setAutocompleteValue('".$markupName."', 'none', 0, ".$multiple.");
				}
			});
		}

		jQuery(window).load(formulize_initializeAutocomplete".$markupName."());
		jQuery(document).ready(function() { checkForChrome(); });
		";

		if($multiple ){
			$output.= "
				jQuery('#".$markupName."_formulize_autocomplete_selections').on('click', '.auto_multi_".$markupName."', function() {
					removeFromMultiValueAutocomplete(jQuery(this).attr('target'), '".$markupName."');
				});
			";
		}

		$output .= "\n</script>";

		return $output;
	}

	// a function that builds some SQL snippets that we use to properly scope queries related to ensuring the uniqueness of selections in linked selectboxes
	// uniquenessFlag is the ele_value[9] property of the element, that tells us how strict the uniqueness is (per user or per group or neither)
	// id_form is the id of the form where the data resides
	// groups is the list of groups we're using as the membership scope in this case (probably the user's groups, but might not be)
	function addEntryRestrictionSQL($uniquenessFlag, $id_form, $groups) {
		$sql = "";
		global $xoopsUser, $xoopsDB;
		switch($uniquenessFlag) {
			case 2:
				$sql .= " AND t4.`creation_uid` = ";
				$sql .= $xoopsUser ? $xoopsUser->getVar('uid') : 0;
				break;
			case 3:
				$gperm_handler = xoops_gethandler('groupperm');
				$groupsThatCanView = $gperm_handler->getGroupIds("view_form", $id_form, getFormulizeModId());
				$groupsToLimitBy = array_intersect($groups, $groupsThatCanView);
				$sql .= " AND EXISTS(SELECT 1 FROM ".$xoopsDB->prefix("formulize_entry_owner_groups")." AS t5 WHERE t5.groupid IN (".implode(", ",$groupsToLimitBy).") AND t5.fid=$id_form AND t5.entry_id=t4.entry_id) ";
				break;
		}
		return $sql;
	}

	// this method returns any custom validation code (javascript) that should figure out how to validate this element
	// 'myform' is a name enforced by convention that refers to the form where this element resides
	// use the adminCanMakeRequired property and alwaysValidateInputs property to control when/if this validation code is respected
	function generateValidationCode($caption, $markupName, $element, $entry_id=false) {
		$validationCode = array();
		$ele_value = $element->getVar('ele_value');
		$eltname = $markupName;
		$eltcaption = $caption;
		$eltmsg = empty($eltcaption) ? sprintf( _FORM_ENTER, $eltname ) : sprintf( _FORM_ENTER, strip_tags(htmlspecialchars_decode($eltcaption, ENT_QUOTES)));
		$eltmsg = str_replace('"', '\"', stripslashes( $eltmsg ) );
		if($ele_value[ELE_VALUE_SELECT_AUTOCOMPLETE] == 1) {// Has been edited in order to not allow the user to submit a form when "No match found" or "Choose an Option" is selected from the quickselect box.
			if($ele_value[ELE_VALUE_SELECT_MULTIPLE]) {
				$validationCode[] = "\nif ( window.document.getElementsByName('{$eltname}[]').length == 0 ) {\n window.alert(\"{$eltmsg}\");\n myform.{$eltname}_user.focus();\n return false;\n }\n";
			} else {
				$validationCode[] = "\nif ( myform.{$eltname}.value == '' || myform.{$eltname}.value == 'none'  ) {\n window.alert(\"{$eltmsg}\");\n myform.{$eltname}_user.focus();\n return false;\n }\n";
			}
		} elseif($ele_value[ELE_VALUE_SELECT_NUMROWS] == 1) {
			$validationCode[] = "\nif ( myform.{$eltname}.options[0].selected && myform.{$eltname}.options[0].value == 'none') {\n window.alert(\"{$eltmsg}\");\n myform.{$eltname}.focus();\n return false;\n }\n";
		} elseif($ele_value[ELE_VALUE_SELECT_NUMROWS] > 1) {
			$validationCode[] = "selection = false;\n";
			$validationCode[] = "\nfor(i=0;i<myform.{$eltname}.options.length;i++) {\n";
			$validationCode[] = "if(myform.{$eltname}.options[i].selected) {\n";
			$validationCode[] = "selection = true;\n";
			$validationCode[] = "}\n";
			$validationCode[] = "}\n";
			$validationCode[] = "if(selection == false) { window.alert(\"{$eltmsg}\");\n myform.{$eltname}.focus();\n return false;\n }\n";
		}
		return $validationCode;
	}

	// this method will read what the user submitted, and package it up however we want for insertion into the form's datatable
	// You can return {WRITEASNULL} to cause a null value to be saved in the database
	// $value is what the user submitted
	// $element is the element object
	// $entry_id is the ID number of the entry that this data is being saved into. Can be "new", or null in the event of a subformblank entry being saved.
	// $subformBlankCounter is the counter for the subform blank entries, if applicable
	function prepareDataForSaving($value, $element, $entry_id=null, $subformBlankCounter=null) {

		global $myts;
		$ele_value = $element->getVar('ele_value');
		$ele_id = $element->getVar('ele_id');
		$originalValue = $value;

		if ($ele_value[ELE_VALUE_SELECT_NUMROWS] == 1 AND $value == "none") { // none is the flag for the "Choose an option" default value
			return "{WRITEASNULL}"; // this flag is used to terminate processing of this value
		}

		$checkForNewValues = !is_array($value) ? array($value) : $value;
		$newWrittenValues = array();
		foreach($checkForNewValues as $candidateNewValue) {
			if (!$ele_value[ELE_VALUE_SELECT_LINK_SNAPSHOT] AND is_string($candidateNewValue) AND substr($candidateNewValue, 0, 9) == "newvalue:") {
				// need to add a new entry to the underlying source form if this is a link
				// need to add an option to the option list for the element list, if this is not a link.
				// check for the value first, in case we are handling a series of quick ajax requests for new elements, in which a new value is being sent with all of them. We don't want to write the new value once per request!
				$newValue = substr($candidateNewValue, 9);

				// if the element is linked...
				if ($element->isLinked) {
					$boxproperties = explode("#*=:*", $ele_value[ELE_VALUE_SELECT_OPTIONS]);
					$sourceHandle = $boxproperties[1];
					$needToWriteEntry = false;
					$dataArrayToWrite[$sourceHandle] = $newValue;
					if($newValue !== '') {
						$needToWriteEntry = true;
					}
					$sourceFormObject = _getElementObject($sourceHandle);

					// add any mapped values to the data we're going to write
					// get other seed values passed from the form if we're making a new entry
					if($otherMappings = $ele_value[ELE_VALUE_SELECT_LINK_SOURCEMAPPINGS]) {
						foreach($otherMappings as $thisMapping) {
							$otherElementToWrite = _getElementObject($thisMapping['sourceForm']);
							$valueToPrep = '';
							if(is_numeric($thisMapping['thisForm'])) {
								if(!$mappingThisFormElement = _getElementObject($thisMapping['thisForm'])) {
									print 'Error: could not determine the element for mapping a new value. '.strip_tags(htmlspecialchars($thisMapping['thisForm'],ENT_QUOTES)).' is not a valid element reference. Please update the mapping settings in element '.$ele_id;
								}
								if(isset($GLOBALS['formulize_asynchronousFormDataInDatabaseReadyFormat'][$entry_id][$mappingThisFormElement->getVar('ele_handle')])) {
									$newValue = $GLOBALS['formulize_asynchronousFormDataInDatabaseReadyFormat'][$entry_id][$mappingThisFormElement->getVar('ele_handle')];
								} else {
									$valueToPrep = isset($_POST['de_'.$element->getVar('id_form').'_'.$entry_id.'_'.$thisMapping['thisForm']]) ? $_POST['de_'.$element->getVar('id_form').'_'.$entry_id.'_'.$thisMapping['thisForm']] : $_GET['de_'.$element->getVar('id_form').'_'.$entry_id.'_'.$thisMapping['thisForm']]; // GET is used in asynch conditional element evaluation...note this means mapped fields ALSO MUST HAVE A DISPLAY CONDITION!
									if($valueToPrep OR $valueToPrep === 0) {
										$newValue = prepDataForWrite($otherElementToWrite, $valueToPrep, $entry_id);
									} else {
										$thisElementDataHandler = new formulizeDataHandler($element->getVar('id_form'));
										$newValue = $thisElementDataHandler->getElementValueInEntry($entry_id, $thisMapping['thisForm']); // lookup the value if we couldn't get it out of POST
									}
								}
							} else {
								$newValue = $thisMapping['thisForm']; // literal mapping value instead of an element reference
							}
							$otherElementEleValue = $otherElementToWrite->getVar('ele_value');
							if($otherElementToWrite->isLinked AND !$otherElementEleValue[ELE_VALUE_SELECT_LINK_SNAPSHOT] AND !$valueToPrep AND $valueToPrep !== 0) {
								// if the field we're mapping to is linked, and we didn't find a value to prep in POST or GET, then we need to convert the literal value to the correct foreign key
								// UNLESS the two fields are both linked and pointing to the same source, then we can use the value we've got right now, which will be the foreign key
								// OR if the element is two links from the same source at the other, then we need 'newvalue' to be not the value we have deduced at this point, but the value in the DB in that intermediate form, so we write a foreign key to the correct source to the other element
								$thisFormMappingElementLinkProperties = false;
								$linkProperties = false;
								$linkToLinkProperties = false;
								$thisFormMappingElement = _getElementObject($thisMapping['thisForm']);
								if($thisFormMappingElement->isLinked) {
										$thisFormMappingElementEleValue = $thisFormMappingElement->getVar('ele_value');
										$thisFormMappingElementLinkProperties = explode("#*=:*", $thisFormMappingElementEleValue[ELE_VALUE_SELECT_OPTIONS]); // returns array, first key is form id we're linked to, second key is element we're linked to
										// and now go figure out if there's a second level link and we'll use that foreign key instead if the other element links directly there
										if($linkToLinkElement = _getElementObject($thisFormMappingElementLinkProperties[1])) {
											if($linkToLinkElement->isLinked) {
												$linkToLinkEleValue = $linkToLinkElement->getVar('ele_value');
												$linkToLinkProperties = explode("#*=:*", $linkToLinkEleValue[ELE_VALUE_SELECT_OPTIONS]);
											}
										}
								}
								$linkProperties = explode("#*=:*", $otherElementEleValue[ELE_VALUE_SELECT_OPTIONS]); // returns array, first key is form id we're linked to, second key is element we're linked to
								// check what we're supposed to do...use the value we have, lookup the linktolink value, or lookup the value in the source of the other form, based on the value we have
								if($element->isLinked AND $linkProperties[0] == $thisFormMappingElementLinkProperties[0]) {
									// two fields are pointing to the same source, so use the value we have...redundant but captured here for readability
									$newValue = $newValue;
								} elseif($element->isLinked AND $linkToLinkElement AND $linkToLinkElement->isLinked AND $linkProperties[0] == $linkToLinkProperties[0]) {
									// the starting field is linked to an element, that is linked to the same source as the other element, so lookup the value of newvalue in that second form....and we should somehow make this all recursive, right???
									$linkToLinkDataHandler = new formulizeDataHandler($linkToLinkProperties[0]);
									$newValue = $linkToLinkDataHandler->findFirstEntryWithValue($linkToLinkProperties[1], $newValue);
								} else {
									$linkDataHandler = new formulizeDataHandler($linkProperties[0]);
									$newValue = $linkDataHandler->findFirstEntryWithValue($linkProperties[1], $newValue);
								}
							}
							$dataArrayToWrite[$otherElementToWrite->getVar('ele_handle')] = $newValue;
							if($newValue !== '') {
								$needToWriteEntry = true;
							}
						}
					}

					// write new value to the source form if necessary...
					if($needToWriteEntry) {
						// check if the new value plus all mappings, is actually new, and if so, write it. If we find something that matches, don't write it, use that entry id instead.
						$dataHandler = new formulizeDataHandler($boxproperties[0]); // 0 key is the source fid
						if(!$newEntryId = $dataHandler->findFirstEntryWithAllValues($dataArrayToWrite)) { // check if this value has been written already, if so, use that ID
							if($newEntryId = formulize_writeEntry($dataArrayToWrite)) {
								formulize_updateDerivedValues($newEntryId, $sourceFormObject->getVar('id_form'));
							}
						}
						$newWrittenValues[] = $newEntryId;
					}

				// not a linked element...
				} else {
					if(!is_array($ele_value[ELE_VALUE_SELECT_OPTIONS]) OR !isset($ele_value[ELE_VALUE_SELECT_OPTIONS][$newValue])) {
						$ele_value[ELE_VALUE_SELECT_OPTIONS][$newValue] = 0; // create new key in ele_value[2] for this new option, set to 0 to indicate it's not selected by default in new entries
						$element->setVar('ele_value', $ele_value);
						$this->insert($element);
					}
					$allValues = array_keys($ele_value[ELE_VALUE_SELECT_OPTIONS]);
					$selectedKey = array_search($newValue, $allValues); // value to write is the number representing the position in the array of the key that is the text value the user made
					$selectedKey = $element->canHaveMultipleValues ? $selectedKey : $selectedKey + 1; // because we add one to the key when evaluating against single option elements below and these thigns need to line up!! YUCK
					$newWrittenValues[] = $selectedKey;
				}

				// remove the candidate value from the original $value so we don't have a duplicate when trying to sort it out later
				if(is_array($value)) {
					unset($value[array_search($candidateNewValue,$value)]);
				} else {
					$value = '';
				}
			}
		}

		// need to update $value with any newly written values, so they can be processed properly below
		foreach($newWrittenValues as $thisNewValue) {
			if(is_array($value)) {
				$value[] = $thisNewValue;
			} else {
				if(count((array) $newWrittenValues)>1) {
					print "ERROR: more than one new value created in a selectbox, when the selectbox does not allow multiple values. Check the settings of element '".$element->getVar('ele_caption')."'.";
				}
				$value = $thisNewValue;
				$originalValue = $thisNewValue; // update original for handling out of range stuff later
			}
		}
		if(!empty($newWrittenValues) AND !is_array($value) AND count((array) $newWrittenValues)>1) {
			$originalValue = $value; // update original for handling out of range stuff later
		}

		// section to handle linked select boxes differently from others
		// first, snapshots just take the literal value passed, and that is that
		if($ele_value[ELE_VALUE_SELECT_LINK_SNAPSHOT]) {
			$valuesToWrite = is_array($value) ? $value : array($value);
			foreach($valuesToWrite as $i=>$thisValueToWrite) {
				if(substr($thisValueToWrite, 0, 9)=='newvalue:') {
					$valuesToWrite[$i] = substr($thisValueToWrite, 9);
				}
			}
			$value = implode("*=+*:",$valuesToWrite);
			$value = strstr($value, "*=+*:") ? "*=+*:".$value : $value; // stick the multiple value indicator on the beginning if there are multiple values. Otherwise, take the value as is.

		// if we've got a formlink, then handle it here
		} elseif (!$ele_value[ELE_VALUE_SELECT_LINK_SNAPSHOT] AND is_string($ele_value[ELE_VALUE_SELECT_OPTIONS]) and strstr($ele_value[ELE_VALUE_SELECT_OPTIONS], "#*=:*")) {
			if (is_array($value)) {
				$startWhatWasSelected = true;
				$newValue = "";
				foreach ($value as $whatwasselected) {
					if (!is_numeric($whatwasselected)) {
						continue;
					}
					if ($startWhatWasSelected) {
						$newValue = ",";
						$startWhatWasSelected = false;
					}
					$newValue .= $whatwasselected.",";
				}
				$value = $newValue;
			} elseif (is_numeric($value)) {
				$value = $value;
			} else {
				$value = "";
			}

		// not a linked element...
		} else {

			// The following code block is a replacement for the previous method for reading a select box which didn't work reliably -- jwe 7/26/04
			if(!is_array($ele_value[ELE_VALUE_SELECT_OPTIONS])) {
				$ele_value[ELE_VALUE_SELECT_OPTIONS] = array();
				error_log('Formulize error: attempted to save data to selectbox that has no options (element id '.$ele_id.'). In prepDataForWrite function (modules/formulize/include/functions.php)');
			}

			// if we've got a names list....
			$temparraykeys = array_keys($ele_value[ELE_VALUE_SELECT_OPTIONS]);
			// ADDED June 18 2005 to handle pulling in usernames for the user's group(s) -- updated for real live use September 6 2006
			if ($temparraykeys[0] === "{FULLNAMES}" OR $temparraykeys[0] === "{USERNAMES}") {
				if (is_array($value)) {
					$newValue = "";
					foreach ($value as $auid) {
						$newValue .= "*=+*:" . $auid;
					}
					$value = $newValue;
				} else {
					$value = $value;
				}

			// regular element...
			} else {

				// THIS REALLY OLD CODE IS HARD TO READ. HERE'S A GLOSS
				// ele_value[2] (ELE_VALUE_SELECT_OPTIONS) is all the options that make up this element.  The values passed back from the form will be numbers indicating which value was selected.  First value is 0 for a multi-selection box, and 1 for a single selection box.
				// Subsequent values are one number higher and so on all the way to the end.  Five values in a multiple selection box, the numbers are 0, 1, 2, 3, 4.
				// masterentlistjwe and entrycounterjwe will be the same!!  There's these array_keys calls here, which result basically in a list of numbers being created, keysPassedBack, and that list is going to start at 0 and go up to whatever the last value is.  It always starts at zero, even if the list is a single selection list.  entrycounterjwe will also always start at zero.
				// After that, we basically just loop through all the possible places, 0 through n, that the user might have selected, and we check if they did.
				// The check lines are if ($whattheuserselected == $masterentlistjwe) and $value == ($masterentlistjwe+1). note the +1 to make this work for single selection boxes where the numbers start at 1 instead of 0.
				// This is all further complicated by the fact that we're grabbing values from $entriesPassedBack, which is just the list of options in the form, so that we can populate the ultimate $value that is going to be written to the database.
				$entriesPassedBack = array_keys($ele_value[ELE_VALUE_SELECT_OPTIONS]);
				$keysPassedBack = array_keys($entriesPassedBack);
				$entrycounterjwe = 0;
				$numberOfSelectionsFound = 0;
				$newValue = "";
				foreach ($keysPassedBack as $masterentlistjwe) {
					if (is_array($value)) {
						if (in_array($masterentlistjwe, $value)) {
							$entriesPassedBack[$entrycounterjwe] = $myts->htmlSpecialChars($entriesPassedBack[$entrycounterjwe]);
							$newValue .= "*=+*:" . $entriesPassedBack[$entrycounterjwe];
							$numberOfSelectionsFound++;
						}
						$entrycounterjwe++;
					} else {
						// plus 1 because single entry select boxes start their option lists at 1.
						if ($value == ($masterentlistjwe+1)) {
							$entriesPassedBack[$entrycounterjwe] = $myts->htmlSpecialChars($entriesPassedBack[$entrycounterjwe]);
							$newValue = $entriesPassedBack[$entrycounterjwe];
						}
						$entrycounterjwe++;
					}
				}
				$value = $newValue;

				// handle out of range values that are in the DB, added March 2 2008 by jwe
				if (is_array($originalValue)) {
					// if a value was received that was out of range. in this case we are assuming that if there are more values passed back than selections found in the valid options for the element, then there are out-of-range values we want to preserve
					while ($numberOfSelectionsFound < count((array) $originalValue) AND $entrycounterjwe < 1000) {
						// keep looking for more values. get them out of the hiddenOutOfRange info
						if (in_array($entrycounterjwe, $originalValue)) {
							$value = $value.'*=+*:'.$myts->htmlSpecialChars($_POST['formulize_hoorv_'.$ele_id.'_'.$entrycounterjwe]);
							$numberOfSelectionsFound++;
						}
						$entrycounterjwe++;
					}
				} else {
					// if a value was received that was out of range. added by jwe March 2 2008 (note that unlike with radio buttons, we need to check only for greater than, due to the +1 (starting at 1) that happens with single option selectboxes
					if ($originalValue > $entrycounterjwe) {
						// get the out of range value from the hidden values that were passed back
						$value = $myts->htmlSpecialChars($_POST['formulize_hoorv_'.$ele_id.'_'.$originalValue]);
					}
				}
			}
		} // end of if that checks for a linked select box.
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
		return applyReadableValueTransformations($value, $handle, $entry_id);
	}

	// this method will take a text value that the user has specified at some point, and convert it to a value that will work for comparing with values in the database.  This is used primarily for preparing user submitted text values for saving in the database, or for comparing to values in the database, such as when users search for things.  The typical user submitted values would be coming from a condition form (ie: fieldX = [term the user typed in]) or other situation where the user types in a value that needs to interact with the database.
	// it is only necessary to do special logic here if the values stored in the database do not match what users would be typing, ie: you're using coded numbers in the database, but displaying text on screen to users
	// this would be where a Yes value would be converted to a 1, for example, in the case of a yes/no element, since 1 is how yes is represented in the database for that element type
	// $partialMatch is used to indicate if we should search the values for partial string matches, like On matching Ontario.  This happens in the getData function when processing filter terms (ie: searches typed by users in a list of entries)
	// if $partialMatch is true, then an array may be returned, since there may be more than one matching value, otherwise a single value should be returned.
	// if literal text that users type can be used as is to interact with the database, simply return the $value
	// LINKED ELEMENTS AND UITEXT ARE RESOLVED PRIOR TO THIS METHOD BEING CALLED
	function prepareLiteralTextForDB($value, $element, $partialMatch=false) {

		// if this is a user list, we may need to convert from string to uid
		$elementTypeName = strtolower(str_ireplace(['formulize', 'elementhandler'], "", static::class));
		$userNameList = strstr($elementTypeName, 'users') ? true : false; // is this a user list?
		if($userNameList) {
			// if $value is not numeric, search the users table for a match on uname, taking $partialMatch into account
			if(!is_numeric($value) AND $value !== '') {
				global $xoopsDB;
				$sql = "SELECT uid FROM ".$xoopsDB->prefix("users")." WHERE ";
				if($partialMatch) {
					$sql .= "uname LIKE '%".formulize_db_escape($value)."%'";
				} else {
					$sql .= "uname='".formulize_db_escape($value)."'";
				}
				$result = $xoopsDB->query($sql);
				$uids = array();
				while($row = $xoopsDB->fetchArray($result)) {
					$uids[] = $row['uid'];
				}
				if($partialMatch) {
					return $uids;
				} else {
					return isset($uids[0]) ? $uids[0] : 0;
				}
			}
		}

		$ele_value = $element->getVar('ele_value');
		return ($element->isLinked == false AND $ele_value[ELE_VALUE_SELECT_AUTOCOMPLETE] == false)  ? convertStringToUseSpecialCharsToMatchDB($value) : $value;
	}

	// this method will format a dataset value for display on screen when a list of entries is prepared
	// for standard elements, this step is where linked selectboxes potentially become clickable or not, among other things
	// Set certain properties in this function, to control whether the output will be sent through a "make clickable" function afterwards, sent through an HTML character filter (a security precaution), and trimmed to a certain length with ... appended.
	function formatDataForList($value, $handle="", $entry_id=0, $textWidth=100) {
		$this->clickable = true;
		$this->striphtml = true;
		$this->length = $textWidth;

		global $myts, $xoopsDB;
		$element = $this->get($handle);
		$ele_value = $element->getVar('ele_value');
		$fid = $element->getVar('fid');

		if(is_string($ele_value[ELE_VALUE_SELECT_OPTIONS]) AND strstr($ele_value[ELE_VALUE_SELECT_OPTIONS], "#*=:*") AND $ele_value[ELE_VALUE_SELECT_LINK_CLICKABLEINLIST] == 1) {
      $boxproperties = explode("#*=:*", $ele_value[ELE_VALUE_SELECT_OPTIONS]);
      $target_fid = $boxproperties[0];
      if ($target_allowed = security_check($target_fid)) {
				// wild caching and static stuff going on here
				// if multiple sub entries are being formatted in a list
				// the value in the DB could be a comma separated list of foreign keys
				// but for each sub entry, we want only one specific key in the list to be used
				// so we keep a counter of which values we've done and where we are in the set of ids
				// so that we can return the correct one this time and the next time we're formatting the value for display
        static $cachedQueryResults = array();
				static $multiValueCounter = array();
				if(!isset($multiValueCounter[$entry_id])) {
					$multiValueCounter[$entry_id] = 0;
				} else {
					$multiValueCounter[$entry_id]++;
				}
        if (isset($cachedQueryResults[$boxproperties[0]][$boxproperties[1]][$entry_id][$handle])) {
          $id_req = $cachedQueryResults[$boxproperties[0]][$boxproperties[1]][$entry_id][$handle];
        } else {
          // get the targetEntry by checking in the entry we're processing, for the actual value recorded in the DB for the entry id we're pointing to
          if($ele_value[ELE_VALUE_SELECT_LINK_SNAPSHOT]) {
						// lookup the first item that matches the saved text, in the source form... only get the first value when there are multiple, as per the logic below for non-snapshotted elements, but we should probably smartly get them all and build links properly in multiselect cases
						$id_req = findMatchingIdReq($boxproperties[1], $boxproperties[0], $value);
						$cachedQueryResults[$boxproperties[0]][$boxproperties[1]][$entry_id][$handle] = $id_req;
          } else {
						$currentFormId = $element->getVar('fid');
						$data_handler = new formulizeDataHandler($currentFormId);
						$id_req_list = explode(",", trim($data_handler->getElementValueInEntry($entry_id, $handle), ","));
						$id_req_list = array_values(array_filter($id_req_list, fn($item) => ($item !== 0 AND $item !== "0")));
						$id_req = $id_req_list[$multiValueCounter[$entry_id]];
					}
        }
				$clickableText = printSmart(trans($myts->htmlSpecialChars($value)), $textWidth);
				// if this goes to the same form as the one we're displaying, use viewEntryLink to make the link so the user keeps their place -- it's equivalent to drilling into an entry in the list
				if($id_req AND $fid == $target_fid) {
					return viewEntryLink($clickableText,$id_req);
				// otherwise, make a link to a new window/tab
				} elseif ($id_req) {
					return "<a href='" . XOOPS_URL . "/modules/formulize/index.php?fid=$target_fid&ve=$id_req' target='_blank'>" . $clickableText . "</a>";
				}
				// no id_req (entry) found
				return $clickableText;
			}
    } elseif ((isset($ele_value[ELE_VALUE_SELECT_OPTIONS]['{USERNAMES}']) OR isset($ele_value[ELE_VALUE_SELECT_OPTIONS]['{FULLNAMES}'])) AND $ele_value[ELE_VALUE_SELECT_LINK_CLICKABLEINLIST] == 1) {
			$nametype = isset($ele_value[ELE_VALUE_SELECT_OPTIONS]['{USERNAMES}']) ? "uname" : "name";
			static $cachedUidResults = array();
			if (isset($cachedUidResults[$value])) {
				$uids = $cachedUidResults[$value];
			} else {
				$uids = q("SELECT uid FROM " . $xoopsDB->prefix("users") . " WHERE $nametype = '" . formulize_db_escape($value) . "' ");
				$cachedUidResults[$value] = $uids;
			}
			if (count((array) $uids) == 1) {
				return "<a href='" . XOOPS_URL . "/userinfo.php?uid=" . $uids[0]['uid'] . "' target=_blank>" . printSmart(trans($myts->htmlSpecialChars($value)), $textWidth) . "</a>";
			} else {
				return printSmart(trans($myts->htmlSpecialChars($value)), $textWidth);
			}
		} elseif($element->getVar('ele_uitextshow')) {
			$value = formulize_swapUIText($value, $element->getVar('ele_uitext'));
		}
		return parent::formatDataForList($value); // always return the result of formatDataForList through the parent class (where the properties you set here are enforced)
	}

}


