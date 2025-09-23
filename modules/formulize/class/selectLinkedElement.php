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
require_once XOOPS_ROOT_PATH . "/modules/formulize/class/selectElement.php";

class formulizeSelectLinkedElement extends formulizeSelectElement {

	function __construct() {
		parent::__construct();
		$this->name = "Linked Dropdown List";
		$this->hasData = true; // set to false if this is a non-data element, like the subform or the grid
		$this->needsDataType = false; // set to false if you're going force a specific datatype for this element using the overrideDataType
		$this->overrideDataType = "bigint"; // use this to set a datatype for the database if you need the element to always have one (like 'date').  set needsDataType to false if you use this.
		$this->adminCanMakeRequired = true; // set to true if the webmaster should be able to toggle this element as required/not required
		$this->alwaysValidateInputs = false; // set to true if you want your custom validation function to always be run.  This will override any required setting that the webmaster might have set, so the recommendation is to set adminCanMakeRequired to false when this is set to true.
		$this->canHaveMultipleValues = false;
		$this->hasMultipleOptions = true;
		$this->isLinked = true; // set to true if this element can have linked values
	}

	/**
	 * Static function to provide the mcp server with the schema for the properties that can be used with the create_form_element and update_form_element tools
	 * Concerned with the options for the ele_value property of the element object
	 * Follows the convention of properties used publically (MCP, Public API, etc).
	 * @param bool|int $update True if this is being called as part of building the options for Updating, as opposed to options for Creating. Default is false (Creating).
	 * @return string The schema for the properties that can be used with the create_form_element and update_form_element tools
	 */
	public static function mcpElementPropertiesDescriptionAndExamples($update = false) {
		return
"Element: Linked Dropdown List (select_linked).
Properties:
- source_element (int or string, the element ID or element handle of an element in another form. The options displayed in this Linked Dropdown List will be based on the values entered into this source element. Element ID numbers and handles are globally unique, so the form can be determined based on the element reference alone.)
- make_subform_element_in_linked_form (boolean, optional, default false. If true, the linked form will get a special subform interface element, so that when users are editing entries in the linked form, they can interact with the connected entries in this form too. Not all linked forms should have subform elements, but sometimes it makes sense for users to edit related data all in one place. Other times, forms might be linked solely to ensure their data is included together in a report or list but the data entry workflow is separate.)
Examples:
- A dropdown list with options drawn from the values entered in element 7 (element IDs are globally unique and so imply a certain form): { source_element: 7 }
- A dropdown list with options drawn from the values entered in the element with handle 'provinces_name' (element handles are globally unique as well): { source_element: 'provinces_name' }
- A dropdown list of province names (from the element with that handle), and a subform element will be created in the provinces form so the entries in this form are accessible there too: { source_element: 'provinces_name', make_subform_element_in_linked_form: true }";
	}
}

#[AllowDynamicProperties]
class formulizeSelectLinkedElementHandler extends formulizeSelectElementHandler {

	function create() {
		return new formulizeSelectLinkedElement();
	}

	/**
	 * Validate options for this element type, based on the structure used publically (MCP, Public API, etc).
	 * The description in the mcpElementPropertiesDescriptionAndExamples static method on the element class, follows this convention
	 * Options are the contents of the ele_value property on the object
	 * @param int|string|object|null $elementIdentifier the id, handle, or element object of the element we're preparing options for. Null if unknown.
	 * @return array An array of properties ready for the object. Usually just ele_value but could be others too.
	 */
	public function validateEleValuePublicAPIOptions($options, $elementIdentifier = null) {
		if(!$elementObject = _getElementObject($options['source_element'])) {
			throw new Exception("You must provide a valid source_element property for the linked dropdown list element");
		}
		$ele_value = array(
			ELE_VALUE_SELECT_NUMROWS => 1,
			ELE_VALUE_SELECT_MULTIPLE => 0,
			ELE_VALUE_SELECT_OPTIONS => $elementObject->getVar('fid')."#*=:*".$elementObject->getVar('ele_handle'),
			ELE_VALUE_SELECT_LINK_LIMITGROUPS => '',
			ELE_VALUE_SELECT_LINK_USERSGROUPS => 0,
			ELE_VALUE_SELECT_LINK_FILTERS => array(),
			ELE_VALUE_SELECT_LINK_ALLGROUPS => 0,
			ELE_VALUE_SELECT_LINK_USEONLYUSERSENTRIES => 0,
			ELE_VALUE_SELECT_LINK_CLICKABLEINLIST => 0,
			ELE_VALUE_SELECT_AUTOCOMPLETE => 0,
			ELE_VALUE_SELECT_RESTRICTSELECTION => 0,
			ELE_VALUE_SELECT_LINK_ALTLISTELEMENTS => array(),
			ELE_VALUE_SELECT_LINK_ALTEXPORTELEMENTS => array(),
			ELE_VALUE_SELECT_LINK_SORT => 0,
			ELE_VALUE_SELECT_LINK_DEFAULTVALUE => array(),
			ELE_VALUE_SELECT_LINK_SHOWDEFAULTWHENBLANK => 0,
			ELE_VALUE_SELECT_LINK_SORTORDER => 1,
			ELE_VALUE_SELECT_AUTOCOMPLETEALLOWSNEW => 0,
			ELE_VALUE_SELECT_LINK_ALTFORMELEMENTS => array(),
			ELE_VALUE_SELECT_LINK_SNAPSHOT => 0,
			ELE_VALUE_SELECT_LINK_ALLOWSELFREF => 0,
			ELE_VALUE_SELECT_LINK_LIMITBYELEMENT => 0,
			ELE_VALUE_SELECT_LINK_LIMITBYELEMENTFILTER => array(),
			ELE_VALUE_SELECT_LINK_SOURCEMAPPINGS => array(),
		);
		return [
			'ele_value' => $ele_value,
			'upsertParams' => [
				'makeSubformInterface' => ((isset($options['make_subform_element_in_linked_form']) AND $options['make_subform_element_in_linked_form']) ? true : false)
			]
		];
	}

}


