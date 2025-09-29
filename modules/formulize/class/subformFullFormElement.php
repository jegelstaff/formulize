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
require_once XOOPS_ROOT_PATH . "/modules/formulize/class/subformListingsElement.php";

class formulizeSubformFullFormElement extends formulizeSubformListingsElement {

	var $defaultValueKey;

	function __construct() {
		parent::__construct();
		$this->name = "Embeded Form (full entries)";
	}

	/**
	 * Static function to provide the mcp server with the schema for the properties that can be used with the create_form_element and update_form_element tools
	 * Concerned with the properties for the ele_value property of the element object
	 * Follows the convention of properties used publically (MCP, Public API, etc).
	 * @param bool|int $update True if this is being called as part of building the properties for Updating, as opposed to properties for Creating. Default is false (Creating).
	 * @return string The schema for the properties that can be used with the create_form_element and update_form_element tools
	 */
	public static function mcpElementPropertiesDescriptionAndExamples($update = false) {
list($commonNotes, $commonProperties, $commonExamples) = formulizeHandler::mcpElementPropertiesBaseDescriptionAndExamplesForSubforms($update);
		$descriptionAndExamples = "
**Subform Interface Type:** Full Form (subformFullForm).
**Description:** This Subform Interface embeds a full version of the connected form, inside this form (ie: inside the form that the Subform Interface belongs to). The connected entries are shown as full forms, one after the other, inside the interface. They can be organized into collapsable accordions (generally the preferred option), or simply embedded right into the page (generally best for small forms, so as not to overwhelm the user). 'Full Form' Subform Interfaces are good for situations where users need to be able to edit the _entire_ connected entry quickly, without necessarily having to open up each entry in a separate interface via a clickable icon.";
		if($commonNotes) {
			$descriptionAndExamples .= "
$commonNotes";
		}
		if($commonProperties) {
			$descriptionAndExamples .= "
$commonProperties
- showAddButton (int, either 1 or 0, indicating if an Add Entry button should be shown to users, based on their permission to add entries in the Subform Interface. Default is 1. Set to 0 if this Subform Interface is for viewing only and should never include an Add Entry button.)
- elementsInHeading (Required. An array of element ids, indicating which elements from the source form should be shown as the headings that introduce each connected entry.)
- fullFormMode (Optional. A string, either 'collapsable' or 'not_collapsable'. Default is 'collapsable'. If 'collapsable', then the connected entries will be shown in collapsable accordions, labelled with the values of the element specified in the elementsInHeading property. If 'not_collapsable', then the connected entries will be embedded in the page one after the other, with the elements specified in the elementsInHeading property used as headers above each form.)";
		}
		if($commonExamples) {
			$descriptionAndExamples .= "
$commonExamples
- A 'Full Form' Subform Interface that shows connected entries in form 198. Show the values of elements 201 and 202 as the heading for each connected entry. Do not show the Add Entry button: { sourceForm: 198, elementsInHeading: [201, 202],showAddButton: 0 }
- A 'Full Form' Subform Interface that shows connected entries in form 31. Show the value of element 69 as the heading for each connected entry. Sort the connected entries by the value of element 69. Show the connected entries as embedded forms one after the other, not in collapsable accordions: { sourceForm: 31, elementsInHeading: [69], sortingElement: 69, fullFormMode: 'not_collapsable' }";
		}
		return $descriptionAndExamples;
	}

}

#[AllowDynamicProperties]
class formulizeSubformFullFormElementHandler extends formulizeSubformListingsElementHandler {

	function create() {
		return new formulizeSubformFullFormElement();
	}

	/**
	 * Validate properties for this element type, based on the structure used publically (MCP, Public API, etc).
	 * The description in the mcpElementPropertiesDescriptionAndExamples static method on the element class, follows this convention
	 * properties are the contents of the ele_value property on the object
	 * @param array $properties The properties to validate
	 * @param array $ele_value The ele_value settings for this element, if applicable. Should be set by the caller, to the current ele_value settings of the element, if this is an existing element.
	 * @return array An array of properties ready for the object. Usually just ele_value but could be others too.
	 */
	public function validateEleValuePublicAPIProperties($properties, $ele_value = []) {
		// subform has no stated public properties yet!
		return ['ele_value' => array() ];
	}

}
