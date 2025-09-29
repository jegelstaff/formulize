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

class formulizeSubformEditableRowElement extends formulizeSubformListingsElement {

	var $defaultValueKey;

	function __construct() {
		parent::__construct();
		$this->name = "Embeded Form (editable rows)";
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
**Subform Interface Type:** Editable Row (subformEditableRow).
**Description:** This kind of Subform Interface allows users to view and edit the connected entries. Each connected entry shows up as a row of form elements, which can be edited in place. This is best for situations when only a few elements in the source form need to be edited at once (generally less than 5) and where users need to be able to edit multiple entries quickly, without necessarily having to open up each entry in a separate full form or modal popup.";
		if($commonNotes) {
			$descriptionAndExamples .= "
$commonNotes";
		}
		if($commonProperties) {
			$descriptionAndExamples .= "
$commonProperties
- showAddButton (int, either 1 or 0, indicating if an Add Entry button should be shown to users, if they have permission to add entries in the source form. Default is 1. Set to 0 if this Subform Interface is for viewing only and should never include an Add Entry button.)
- elementsInRow (Required. An array of element ids, indicating which elements from the source form should be shown in each row.)
- disabledElementsInRow (Optional. An array of element ids, indicating which elements in the row should be disabled (not editable). Default is an empty array, meaning all elements in the row are editable.)
- entryViewingMode (Optional. A string, either 'off', 'form_screen' or 'modal'. Default is 'off', which means there are no clickable icons for opening up each source form entry for viewing/editing. If 'full_screen' then there are clickable icons, and they will cause the page to reload with the correct Form Screen for showing the source form entry. If 'modal' then there are clickable icons, and they will open a modal popup box for showing the source form entry. For small forms, 'modal' is usually best. For large forms, 'full_screen' is usually best. If a user should not be able to view/edit the source form entries, or does not need to, then set this to 'off'.";
		}
		if($commonExamples) {
			$descriptionAndExamples .= "
$commonExamples
- An 'Editable Row' Subform Interface that shows elements 52, 66 and 71 from connected entries in form 7. Sort the entries by the value of element 52. Do not show a Delete button. Open entries in a modal popup for viewing/editing: { sourceForm: 7, elementsInRow: [52, 66, 71], sortingElement: 52, showDeleteButton: 0, entryViewingMode: 'modal' }
- An 'Editable Row' Subform Interface that shows elements 52, 66 and 71 from connected entries in form 3. Open entries in the full form for viewing/editing. Disable elements 12 and 13 in the row: { sourceForm: 3, elementsInRow: [12, 13, 14, 15, 16, 17], disabledElementsInRow: [12, 13], entryViewingMode: 'form_screen' }";
		}
		return $descriptionAndExamples;
	}

}

#[AllowDynamicProperties]
class formulizeSubformEditableRowElementHandler extends formulizeSubformListingsElementHandler {

	function create() {
		return new formulizeSubformEditableRowElement();
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
		// for entryViewingMode, we accept 'off', 'form_screen' and 'modal' as valid valuesm and they correspond to 0, 4 (full form) or 3 (modal) in ele_value[3] -- need to make sure admin UI elements work this way too (strip down options in template)
		// entryViewingMode is optional, default to 'off'
		return ['ele_value' => array() ];
	}

}
