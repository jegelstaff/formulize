<?php

###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) Formulize Project                        ##
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
##  Author of this file: Formulize Project                                   ##
##  Project: Formulize                                                       ##
###############################################################################

include_once XOOPS_ROOT_PATH.'/modules/formulize/include/common.php';
#[AllowDynamicProperties]
class FormulizeObject extends XoopsObject {

	static function sanitize_handle_name($handle_name) {
		// strip non-alphanumeric characters from handles
		return strtolower(preg_replace("/[^a-zA-Z0-9_-]+/", "", str_replace(" ", "_", $handle_name)));
	}

	/**
	 * Keep a list of the field names that are serialized arrays in the DB
	 * Eventually this is redundant because we will convert them to JSON, or a more normalized structure
	 * @return array The list of fields names that are serialized arrays
	 */
	static function serializedDBFields() {
		return [
			'formulize' => [
				'ele_value',
				'ele_uitext',
				'ele_filtersettings',
				'ele_disabledconditions',
				'ele_exportoptions',
			],
			'formulize_notification_conditions' => [
				'not_cons_con'
			],
			'formulize_screen_form' => [
				'formelements',
				'elementdefaults',
			],
			'formulize_screen_multipage' => [
				'buttontext',
				'pages',
				'pagetitles',
				'conditions',
				'elementdefaults'
			],
			'formulize_screen_listofentries' => [
				'limitviews',
				'defaultview',
				'advanceview',
				'hiddencolumns',
				'decolumns',
				'customactions',
				'fundamental_filters'
			],
			'formulize_screen_calendar' => [
				'datasets'
			],
			'formulize_group_filters' => [
				'filter'
			],
			'formulize_advanced_calculations' => [
				'steps',
  			'steptitles',
  			'fltr_grps',
  			'fltr_grptitles'
			],
			'formulize_digest_data' => [
				'extra_tags'
			],
			'formulize_screen_calendar' => [
				'datasets'
			]
		];
	}

}
#[AllowDynamicProperties]
class formulizeHandler {

	function __construct() {
	}

	public static function getElementTypeReadableNames() {
		return array(
			'textboxes' => array(
				'singular' => 'text box element',
				'plural' => 'text boxes elements'
			),
			'lists' => array(
				'singular' => 'list element',
				'plural' => 'list elements'
			),
			'selectors' => array(
				'singular' => 'selector element',
				'plural' => 'selector elements'
			),
			'userlists' => array(
				'singular' => 'user list element',
				'plural' => 'user list elements'
			),
			'linked' => array(
				'singular' => 'linked list element',
				'plural' => 'linked list elements'
			),
			'subforms' => array(
				'singular' => 'subform interface',
				'plural' => 'subform interfaces'
			),
			'derived' => array(
				'singular' => 'derived value element',
				'plural' => 'derived value elements'
			),
			'table' => array(
				'singular' => 'table of elements',
				'plural' => 'tables of elements'
			),
			// WILL NEED TO BE FILLED IN FURTHER FOR 'LAYOUT' ELEMENTS WHEN THEY HAVE CLASSES

		);
	}

	public static function getStandardElementTypes() {
		return array(
			'text',
			'textarea',
			'phone',
			'email',
			'number',
			'select',
			'selectLinked',
			'selectUsers',
			'provinceList',
			'radio',
			'yn',
			'provinceRadio',
			'checkbox',
			'checkboxLinked',
			'autocomplete',
			'autocompleteLinked',
			'autocompleteUsers',
			'listbox',
			'listboxLinked',
			'listboxUsers',
			'date',
			'colorpick',
			'time',
			'duration',
			'slider',
			'fileUpload',
			'googleAddress',
			'googleFilePicker',
			'derived',
			'subformFullForm',
			'subformEditableRow',
			'subformListings',
			'grid',
			'areamodif',
			'ib'
		);
	}

	/**
	 * Static function to provide the common properties and examples used by all list elements for with the mcp server
	 * Follows the convention of properties used publically (MCP, Public API, etc).
	 * @param bool|int $update True if this is being called as part of building the properties for Updating, as opposed to properties for Creating. Default is false (Creating).
	 * @return array an array of with the important notes first, common properties first, and the common examples second
	 */
	public static function mcpElementPropertiesBaseDescriptionAndExamplesForLists($update = false) {

		$description = "
**Overview:** List elements let the user select from a set of options. There are several types of list elements: radio buttons, checkboxes, dropdown lists, etc.";

		$description .= $update ? "
**Important notes:**
- When altering options in a list, consider whether any data that users have entered into the form already, should be altered as well to match the new options. See the updateExistingEntriesToMatchTheseOptions property below. This is only relevant when options are being changed. If options are being re-organized, or new ones added, or old ones deleted, you do not need to update existing entries to match." : "";

		$description .= "
**Properties common to all List elements:**
- options (array, list of options for the element)
- selectedByDefault (optional, an array containing a value or values from the options array that should be selected by default when the element appears on screen to users. If this is not specified, no options will be selected by default. The values in this array should match what's in the options array, even if the databaseValues property is being used.)
- databaseValues (optional, an array of values to store in the database, if different from the values shown to users. This is not normally used, but if the application would require a coded value to be stored in the database, for compatibility with other code or other systems, this is useful. Must be the same length as the options array, and each value in this array corresponds by position to the value in the options array. If not provided, the values in the options array will be used as the values stored in the database.)";
		$description .= $update ?
"- updateExistingEntriesToMatchTheseOptions (optional, a 1/0 indicating if existing entries should be updated to match the new options. Default is 0. Set this to 1 when an element has existing options that are being changed, and then every record in the database where the old first option was selected, will change to having the new first option selected, and every record where the old second option was selected will change to the new second option, etc. This works when changing storage formats from numbers to text, and when just changing wording of options. Any kind of change is supported. This is useful when correcting typos and changing wording, such as switching an option from 'Backwards' to 'Back', or when making refinements, such as an element with options 'S', 'M', 'L' that is changing to 'Small', 'Medium', 'Large'. Sometimes changes to options are just reordering the existing options, or adding new options, or removing removing options, and in those cases this setting should be left unspecified or set to 0.)"
: "";
		$description .= "
**Basic examples:**
- A list of toppings for pizza: { options: [ 'pepperoni', 'mushrooms', 'onions', 'extra cheese', 'green peppers', 'bacon' ] }
- A list of toppings for pizza, with 'pepperoni' and 'mushrooms' selected by default: { options: [ 'pepperoni', 'mushrooms', 'onions', 'extra cheese', 'green peppers', 'bacon' ], selectedByDefault: [ 'pepperoni', 'mushrooms' ] }
- A list of movies: { options: [ '2001: A Space Odyssey', 'WarGames', 'WALL-E', 'The Matrix', 'Inception', 'Children of Men' ] }
- A list of movies, with 'Children of Men' selected by default: { options: [ '2001: A Space Odyssey', 'WarGames', 'WALL-E', 'The Matrix', 'Inception', 'Children of Men' ], selectedByDefault: [ 'Children of Men' ] }
- A list of states where the value stored in the database is the shortform code, but the user sees the full state name: { options: [ 'California', 'Delaware', 'Hawaii', 'Maine', 'New York', 'Vermont' ], databaseValues: [ 'CA', 'DE', 'HI', 'ME', 'NY', 'VT' ] }
- A list of chocolate flavours, with both milk chocolate and dark chocolate selected by default: { options: [ 'milk chocolate', 'dark chocolate', 'white chocolate', 'orange chocolate' ], selectedByDefault: [ 'milk chocolate', 'dark chocolate' ] }
- A list of chocolate flavours, with both milk chocolate and dark chocolate selected by default, and numeric values stored in the database instead of text: { options: [ 'milk chocolate', 'dark chocolate', 'white chocolate', 'orange chocolate' ], databaseValues: [ 11, 22, 33, 44 ], selectedByDefault: [ 'milk chocolate', 'dark chocolate' ] }";
		$description .= $update ?
"- A list which previously had the options 'No', 'Maybe', 'Yes', and is now being updated with new options, that should replace the old options in every entry people have made in the form already: { options: [ 'Never', 'Sometimes', 'Always' ], updateExistingEntriesToMatchTheseOptions: 1 }"
: "";

		return $description;
	}

	/**
	 * Static function to provide the common properties and examples used by all 'linked' elements for with the mcp server
	 * Follows the convention of properties used publically (MCP, Public API, etc).
	 * @param bool|int $update True if this is being called as part of building the properties for Updating, as opposed to properties for Creating. Default is false (Creating).
	 * @return array an array of with the important notes first, common properties first, and the common examples second
	 */
	public static function mcpElementPropertiesBaseDescriptionAndExamplesForLinked($update = false) {

		$description = "
**Overview:** Linked elements let the user select from a set of options, that are based on values entered into another form. Most of the standard types of list elements can be implemented as a Linked element.
**Important notes:**
- Linked elements are very powerful tools. They generally work best when pointing to a source element that is the principal identifying element of the entries in the source form. For example, if there is a form called Provinces, and another form called Cities, the Cities form might naturally have a Linked element that points to a Text Box called 'Name' in the Provinces form. This way, when entering a new City, the user can select the Province it is in from a dropdown list of Province names, and the two entries will be connected together.
- When Linked elements allow a single choice by the user (for example, Radio Buttons, Dropdown Lists, etc), they represent one-to-many relationships between forms. The Linked element is on the 'many' side of the relationship. The source element is on the 'one' side. This is the most common way to use Linked elements.
- Some Linked elements can support multiple selections by the user (for example, Checkboxes, Autocomplete Lists with allowMultipleSelections turned on). These Linked elements will represent many-to-many relationships between forms. For example, if there is a form called Participants, and a form called Activities, the Participants form can have a Linked Checkboxes element that points to a Text Box called 'Name' in the Activities form. This way, when entering a new Participant, the user can check the boxes for all the Activities that the person is participating in. This is a more complex situation, and not all Linked element types support multiple selections.
**Properties common to all Linked elements:**
- sourceElement (int or string, the element ID or element handle of an element in another form. The options displayed in this Linked element will be based on the values entered into this source element. Element ID numbers and handles are globally unique, so the form can be determined based on the element reference alone.)";
"**Basic examples:**
- A Linked element with options drawn from the values entered in element 7 (element IDs are globally unique and so imply a certain form): { sourceElement: 7 }
- A Linked element with options drawn from the values entered in the element with handle 'provinces_name' (element handles are globally unique as well): { sourceElement: 'provinces_name' }";
		return $description;
	}

	/**
	 * Static function to provide the common properties and examples used by all 'users' elements for with the mcp server
	 * Follows the convention of properties used publically (MCP, Public API, etc).
	 * @param bool|int $update True if this is being called as part of building the properties for Updating, as opposed to properties for Creating. Default is false (Creating).
	 * @return array an array of with the important notes first, common properties first, and the common examples second
	 */
	public static function mcpElementPropertiesBaseDescriptionAndExamplesForUserlists($update = false) {

		$description = "
**Overview:** 'Users' elements provide a way for people to select from a list of users in the system. Most of the standard types of list elements can be implemented as 'Users' elements.
**Important notes:**
- 'Users' elements are useful for providing lists of users, but they are also useful for setting up customized permissions, because there are other features in Formulize which can control the access to individual form entries based on whether that entry has a user selected in a 'Users' element.
**Properties common to all 'Users' elements:**
- sourceGroups (optional, an array of group ids from which the users in the list should be drawn. Use the list_groups tool see a list of all the groups and their ids. Use the list_group_members tool to see a list of users who are members of a specific group. If this is not specified, or the array is empty, then all users in the system will be shown, regardless of their group membership.)
**Basic examples:**
- A list of all users in the system (no properties): { }
- A list of users from groups 3 and 6: { sourceGroups: [3, 6] }";
		return $description;
	}

	/**
	 * Static function to provide the common properties and examples used by all 'subform' elements for with the mcp server
	 * Follows the convention of properties used publically (MCP, Public API, etc).
	 * @param bool|int $update True if this is being called as part of building the properties for Updating, as opposed to properties for Creating. Default is false (Creating).
	 * @return array an array of with the important notes first, common properties first, and the common examples second
	 */
	public static function mcpElementPropertiesBaseDescriptionAndExamplesForSubforms($update = false) {

		$description = "
**Overview:**
- Subform Interfaces are used to provide an interface in one form, that shows the entries in another form. They are designed to work with forms that are connected in a one to many relationship, such as Each Province has many Cities, or Each Budget has many Budget Line Items, etc.
**Important notes:**
- Subform Interfaces do not store user data. They are a collection of settings that control how the entries in the 'many' form are to be displayed and managed, when viewed from the 'one' form.
- Subform Interfaces are very powerful tools for supporting complex data management scenarios. They allow users to manage entire sets of data through one screen, instead of having to manage each individual entry separately.
**Properties common to all Subform Interfaces:**
- sourceForm (int, the form ID of the form to be displayed in this Subform Interface. If the source form is not already connected to this form, a new Linked Dropdown List will be created in the source form, and it will be linked to the Principal Identifer in this form. For example, if a Cities form is embedded in a Provinces form, and there is no existing connection between them, then a Linked Dropdown List will be added to the Cities form that links to the Province form's principal identifier.)
- sortingElement (int, the element ID of an element in the source form to sort the entries by. If not specified, entries will be shown in creation order.)
- sortingDirection (string, either 'ASC' or 'DESC', indicating if the entries should be sorted in ascending or descending order. Default is 'ASC'.)
- showAddButton (int, either 1 or 0, indicating if an Add Entry button should be shown to users, if they have permission to add entries in the source form. Default is 1. Set to 0 if this Subform Interface should never include an Add Entry button.)
- showDeleteButton (int, either 1 or 0, indicating if a Delete Entry button should be shown to users, if they have permission to delete entries in the source form. Default is 1. Set to 0 if this Subform Interface should never include a Delete Entry button.)";
		return $description;
	}

	/**
	 * Builds or updates a form, including creating or renaming the data table, creating default screens, and setting up permissions, renaming resources...
	 * @param array $formObjectProperties An associative array of properties to set on the form object.  If 'fid' is included and is non-zero, it will update that form.  If 'fid' is not included or is zero, it will create a new form.
	 * @param array $groupIdsThatCanEditForm An array of group ids that should be given edit permissions on this form (only used when creating a new form)
	 * @param array|null $applicationIds An array of existing application ids to assign this form to. Set to null to skip application assignment.  Default is array(0) to assign to the default application.
	 * @param array|null $groupCategories An array of category names for template groups (used when entries_are_groups is enabled). Set to null to skip group category management. "All Users" is always included as a base category.
	 * @throws Exception if there are any problems creating or updating the form
	 * @return object returns the form object
	 */
	public static function upsertFormSchemaAndResources($formObjectProperties = array(), $groupIdsThatCanEditForm = array(), $applicationIds = array(0), $groupCategories = null) {

		$form_handler = xoops_getModuleHandler('forms', 'formulize');
		$application_handler = xoops_getmodulehandler('applications','formulize');
		$gperm_handler = xoops_gethandler('groupperm');
		global $xoopsDB, $xoopsUser;
		$fid = 0;
		// if fid is set in the properties array, use that to load the form object
		if(isset($formObjectProperties['fid'])) {
			$fid = intval($formObjectProperties['fid']);
		}
		// get the form object that we care about, or start a new one from scratch if fid is 0, null, etc.
		$originalFormNames = array();
		$formIsNew = true;
		if($fid AND $formObject = $form_handler->get($fid)) {

			$mid = getFormulizeModId();
			$gperm_handler = xoops_gethandler('groupperm');
			if(!$xoopsUser OR $gperm_handler->checkRight("edit_form", $fid, $xoopsUser->getGroups(), $mid) == false) {
				throw new Exception("Permission denied: You don't have permission to edit this form.");
			}
			$formIsNew = false;
			$originalFormNames = array(
				'singular' => $formObject->getSingular(),
				'plural' => $formObject->getPlural(),
				'form_handle' => $formObject->getVar('form_handle')
			);
		} else {
			if(!$xoopsUser OR !in_array(XOOPS_GROUP_ADMIN, $xoopsUser->getGroups())) {
				throw new Exception("Permission denied: You must be an administrator to create a new form.");
			}
			$formObject = false;
			$formObject = $form_handler->create();
			$formObjectProperties['form_title'] = $formObjectProperties['form_title'] ? $formObjectProperties['form_title'] : 'New Form';
		}

		// set all the properties that were passed in
		foreach($formObjectProperties as $property=>$value) {
  		$formObject->setVar($property, $value);
		}
		// rename any related resources for an existing form, if necessary
		$singularPluralChanged = false;
		if($formIsNew == false) {
			$singularPluralChanged = $form_handler->renameFormResources($formObject, $originalFormNames);
		}
		// write the form object to the DB, and related resource management operations all inside the insert method
		// object passed by reference, so it will update the fid var on object if it was a new form
		if(!$fid = $form_handler->insert($formObject)) {
  		throw new Exception("Could not save the form properly: ".$xoopsDB->error());
		}
		if($formIsNew) {
			// add edit permissions for the selected groups, and view_form for Webmasters
			foreach($groupIdsThatCanEditForm as $thisGroupId) {
				$gperm_handler->addRight('edit_form', $fid, intval($thisGroupId), getFormulizeModId());
			}
			$gperm_handler->addRight('view_form', $fid, XOOPS_GROUP_ADMIN, getFormulizeModId());
			// add menu links for the new form in the selected applications, for the groups that have admin rights (usually just webmasters, so basic interface works when you are buiding systems)
			if(!empty($applicationIds) AND !empty($groupIdsThatCanEditForm)) {
				$singleArray = $formObject->getVar('single');
				$hasMultiEntry = is_array($singleArray)
					? (bool)array_filter($singleArray, function($v){ return $v == 'off'; })
					: ($singleArray == 'off');
				$menuLinkText = $hasMultiEntry ? $formObject->getPlural() : $formObject->getSingular();
				$menuitems = "null::" . formulize_db_escape($menuLinkText) . "::fid=$fid::::".implode(',',$groupIdsThatCanEditForm)."::null";
				foreach($applicationIds as $appid) {
					$application_handler->insertMenuLink($appid, $menuitems);
				}
			}
		}
		// assign the form to the selected applications, remove from any others
		if(self::assignFormToApplications($formObject, $applicationIds) == false) {
			throw new Exception("Could not assign the form to applications properly.");
		}

		// Handle group categories for entries_are_groups feature
		if ($groupCategories !== null) {
			self::syncTemplateGroupsForForm($formObject, $groupCategories, $formIsNew ? null : $originalFormNames['plural']);
		}

		return $formObject;
	}

	/**
	 * Synchronizes template groups for a form that has entries_are_groups enabled.
	 * Creates, renames, or updates template groups based on the desired categories.
	 *
	 * The $groupCategories parameter is an associative array where:
	 * - Numeric keys are existing group IDs (the category may have been renamed)
	 * - String keys starting with "new_" are new categories that need groups created
	 *
	 * IMPORTANT: Groups are NEVER deleted by this function. When the feature is turned off
	 * or categories are removed, the groups are retained to preserve any configuration.
	 *
	 * @param object $formObject The form object
	 * @param array $groupCategories Associative array: groupid => categoryName for existing, "new_X" => categoryName for new
	 * @param string|null $oldPluralName The old plural name of the form (for renaming groups). Null for new forms.
	 */
	public static function syncTemplateGroupsForForm($formObject, $groupCategories, $oldPluralName = null) {
		$group_handler = xoops_gethandler('group');
		$form_handler = xoops_getmodulehandler('forms', 'formulize');
		$newPluralName = $formObject->getPlural();
		$newGroupPrefix = $newPluralName . " - ";

		// If entries_are_groups is disabled, just return without touching groups
		// This preserves the group configuration in case the feature is re-enabled
		if (!$formObject->getVar('entries_are_groups')) {
			return;
		}

		// Get existing group_categories mapping from the form object (groupid => category name)
		$existingMapping = $formObject->getVar('group_categories');
		if (!is_array($existingMapping)) {
			$existingMapping = array();
		}

		// "All Users" is always included as a base category
		$allUsersLabel = defined('_AM_SETTINGS_FORM_GROUP_CATEGORIES_ALL_USERS') ? _AM_SETTINGS_FORM_GROUP_CATEGORIES_ALL_USERS : 'All Users';

		// Build the complete list of categories to process, including "All Users"
		// For "All Users", check if it already exists in the mapping, otherwise mark as new
		$allUsersGroupId = array_search($allUsersLabel, $existingMapping);
		$categoriesToProcess = array();
		if ($allUsersGroupId !== false) {
			$categoriesToProcess[$allUsersGroupId] = $allUsersLabel;
		} else {
			$categoriesToProcess['new_allUsers'] = $allUsersLabel;
		}

		// Add the submitted categories (filtering out empty names and duplicates of "All Users")
		foreach ($groupCategories as $key => $categoryName) {
			$categoryName = trim($categoryName);
			if ($categoryName !== '' && $categoryName !== $allUsersLabel) {
				$categoriesToProcess[$key] = $categoryName;
			}
		}

		// Build the new mapping: groupid => category name
		$newMapping = array();

		// Process all categories uniformly
		$fid = $formObject->getVar('fid');
		global $xoopsDB;

		foreach ($categoriesToProcess as $key => $categoryName) {
			$expectedGroupName = $newGroupPrefix . $categoryName;
			$expectedDescription = 'Template group for ' . $newPluralName . ' - ' . $categoryName;
			$needsSave = true;

			// Existing group ID - get the group
			if (is_numeric($key)) {
				$groupObject = $group_handler->get(intval($key));
				if (!$groupObject) {
					continue; // Group was deleted externally, skip
				}
				$needsSave = ($groupObject->getVar('name') !== $expectedGroupName);

				// Check if category name changed - if so, update all entry groups too
				$oldCategoryName = isset($existingMapping[$key]) ? $existingMapping[$key] : null;
				if ($oldCategoryName !== null && $oldCategoryName !== $categoryName) {
					// Category was renamed, update all entry groups for this form with the old suffix
					// Entry group names follow format: "{PI value} - {Category name}"
					$oldNameSuffix = " - " . $oldCategoryName;
					$newNameSuffix = " - " . $categoryName;
					$sql = "SELECT groupid, name FROM " . $xoopsDB->prefix('groups') .
						   " WHERE form_id = " . intval($fid) . " AND entry_id IS NOT NULL" .
						   " AND name LIKE '%" . formulize_db_escape($oldNameSuffix) . "'";
					$result = $xoopsDB->query($sql);
					while ($row = $xoopsDB->fetchArray($result)) {
						$entryGroup = $group_handler->get($row['groupid']);
						if ($entryGroup) {
							// Extract PI value by removing the old suffix from the group name
							$piValue = substr($row['name'], 0, -strlen($oldNameSuffix));
							$entryGroup->setVar('name', $piValue . $newNameSuffix);
							$entryGroup->setVar('description', $categoryName . ' group for ' . $piValue);
							$group_handler->insert($entryGroup);
						}
					}
				}

			// New category - create a new group
			} else if (strpos($key, 'new_') === 0) {
				$groupObject = $group_handler->create();
				$groupObject->setVar('group_type', 'User');
				$groupObject->setVar('is_group_template', 1);
				$groupObject->setVar('form_id', $fid);

			// Unknown key format, skip
			} else {
				continue;
			}

			// Ensure form_id is set on the template group (may be missing on older groups)
			if ($groupObject->getVar('form_id') != $fid) {
				$groupObject->setVar('form_id', $fid);
				$needsSave = true;
			}

			// Update and save if needed
			if ($needsSave) {
				$groupObject->setVar('name', $expectedGroupName);
				$groupObject->setVar('description', $expectedDescription);
				$group_handler->insert($groupObject);
			}

			$newMapping[$groupObject->getVar('groupid')] = $categoryName;
		}

		// Save the updated mapping to the form object
		$formObject->setVar('group_categories', $newMapping);
		$form_handler->insert($formObject);

		// Sync entry groups for all existing entries to handle new categories, renames, etc.
		$formHandle = $formObject->getVar('form_handle');
		$sql = "SELECT entry_id FROM " . $xoopsDB->prefix("formulize_" . $formHandle);
		$result = $xoopsDB->query($sql);
		while ($row = $xoopsDB->fetchArray($result)) {
			self::syncEntryGroups($fid, intval($row['entry_id']));
		}
	}

	/**
	 * Creates or updates groups for a specific entry in a form with entries_are_groups enabled.
	 * Groups are named: "{PI value} - {Category Name}" (e.g., "Baskets - All Users", "Baskets - Managers")
	 *
	 * @param int $fid The form ID
	 * @param int $entryId The entry ID
	 * @param string|null $oldPiValue The old PI value (for updates, to check if rename needed). Null for new entries.
	 * @return bool True if groups were created/updated, false if form doesn't use entries_are_groups
	 */
	public static function syncEntryGroups($fid, $entryId, $oldPiValue = null) {
		$form_handler = xoops_getmodulehandler('forms', 'formulize');
		if(!$formObject = $form_handler->get($fid)) {
			throw new Exception("Cannot synch groups with entry for entries_are_group form. Form with ID $fid does not exist.");
		}

		// Check if this form uses entries_are_groups
		if (!$formObject || !$formObject->getVar('entries_are_groups')) {
			return false;
		}

		// Get the group categories from the form
		$groupCategories = $formObject->getVar('group_categories');
		if (!is_array($groupCategories) || empty($groupCategories)) {
			return false;
		}

		// Get the PI element for this form
		$piElementId = $formObject->getVar('pi');
		if (!$piElementId OR !$piElementObject = _getElementObject($piElementId)) {
			return false;
		}

		// Get the current PI value from the entry
		$data_handler = new formulizeDataHandler($fid);
		if(!$piValue = $data_handler->getElementValueInEntry($entryId, $piElementId)) {
			return false; // Can't create groups without a PI value
		}

		$group_handler = xoops_gethandler('group');

		// Check if groups already exist for this entry
		global $xoopsDB;
		$sql = "SELECT groupid, name FROM " . $xoopsDB->prefix('groups') .
			   " WHERE form_id = " . intval($fid) . " AND entry_id = " . intval($entryId);
		$result = $xoopsDB->query($sql);
		$existingGroups = array();
		while ($row = $xoopsDB->fetchArray($result)) {
			$existingGroups[$row['groupid']] = $row['name'];
		}

		// For each category, create or update the group, and build a map for permission copying
		$templateToEntryGroupMap = array();
		$newGroupIds = array();
		foreach ($groupCategories as $templateGroupId => $categoryName) {
			$expectedGroupName = $piValue . " - " . $categoryName;
			$expectedDescription = $categoryName . ' group for ' . $piValue;

			// Check if we already have a group for this entry+category
			$existingGroupId = null;
			foreach ($existingGroups as $gid => $gname) {
				// Match by the category suffix (after " - ")
				if (preg_match('/ - ' . preg_quote($categoryName, '/') . '$/', $gname)) {
					$existingGroupId = $gid;
					break;
				}
			}

			// Update existing group if name changed (PI value changed)
			if ($existingGroupId AND $existingGroups[$existingGroupId] !== $expectedGroupName) {
				$groupObject = $group_handler->get($existingGroupId);
				$groupObject->setVar('name', $expectedGroupName);
				$groupObject->setVar('description', $expectedDescription);
				$group_handler->insert($groupObject);
				$templateToEntryGroupMap[$templateGroupId] = $existingGroupId;

			// Create new group
			} elseif(!$existingGroupId) {
				$newGroup = $group_handler->create();
				$newGroup->setVar('name', $expectedGroupName);
				$newGroup->setVar('description', $expectedDescription);
				$newGroup->setVar('group_type', 'User');
				$newGroup->setVar('is_group_template', 0); // Not a template, it's an entry group
				$newGroup->setVar('form_id', $fid);
				$newGroup->setVar('entry_id', $entryId);
				$group_handler->insert($newGroup);
				$newGroupId = $newGroup->getVar('groupid');
				$templateToEntryGroupMap[$templateGroupId] = $newGroupId;
				$newGroupIds[] = $newGroupId;

			} else {
				// Existing group, no name change needed
				$templateToEntryGroupMap[$templateGroupId] = $existingGroupId;
			}
		}

		// Copy all template group settings to newly created entry groups
		if (count($newGroupIds) > 0) {
			self::copyAllTemplateSettingsToEntryGroups($fid, $templateToEntryGroupMap, $newGroupIds);
		}

		return true;
	}

	/**
	 * Copy all template group settings to the corresponding entry groups for a specific entry.
	 * This includes: form permissions, per-group filters, groupscope settings (with relative
	 * mapping between template and entry groups), and element display/disabled/filter settings.
	 *
	 * @param int $fid The entries_are_groups form ID
	 * @param array $templateToEntryGroupMap Maps templateGroupId => entryGroupId for this entry
	 * @param array $newGroupIds Array of entry group IDs that were newly created (only these get settings copied)
	 */
	public static function copyAllTemplateSettingsToEntryGroups($fid, $templateToEntryGroupMap, $newGroupIds = array()) {
		if (empty($templateToEntryGroupMap)) {
			return;
		}

		include_once XOOPS_ROOT_PATH . '/modules/formulize/class/usersGroupsPerms.php';
		include_once XOOPS_ROOT_PATH . '/modules/formulize/class/elements.php';

		$form_handler = xoops_getmodulehandler('forms', 'formulize');
		$element_handler = xoops_getmodulehandler('elements', 'formulize');

		// If no specific new group IDs provided, treat all as new
		if (empty($newGroupIds)) {
			$newGroupIds = array_values($templateToEntryGroupMap);
		}

		// a) Copy permissions, filters, and groupscope for each new entry group
		// The templateToEntryGroupMap serves as the groupscope mapping, translating
		// template group references to corresponding entry group references
		foreach ($templateToEntryGroupMap as $templateGroupId => $entryGroupId) {
			if (!in_array($entryGroupId, $newGroupIds)) {
				continue;
			}
			formulizePermHandler::copyGroupPermissions($templateGroupId, $entryGroupId, null, $templateToEntryGroupMap);
		}

		// b) Synchronize element display/disabled/filter settings across all elements
		// Uses the same logic as individual element saves - ensures entry groups mirror template groups
		$allForms = $form_handler->getAllForms(true);
		foreach ($allForms as $thisForm) {
			$elementIds = $thisForm->getVar('elements');
			if (!is_array($elementIds)) {
				continue;
			}
			foreach ($elementIds as $elementId) {
				$element = $element_handler->get($elementId);
				if (!is_object($element)) {
					continue;
				}
				if (self::synchronizeTemplateGroupReferencesInElement($element)) {
					$element_handler->insert($element);
				}
			}
		}
	}

	/**
	 * Synchronize template group references in a single element's settings.
	 * Builds a map of all template groups to their entry groups (cached per request),
	 * then delegates to formulizePermHandler::synchronizeGroupReferencesInElement().
	 *
	 * Call this before saving an element to keep entry groups in sync with template groups.
	 *
	 * @param object $element The element object (modified in place via setVar)
	 * @return bool True if the element was modified
	 */
	public static function synchronizeTemplateGroupReferencesInElement($element) {
		static $templateGroupMap = null; // templateGroupId => [entryGroupId, ...]

		// Build the map once per request
		if ($templateGroupMap === null) {
			$templateGroupMap = array();

			global $xoopsDB;
			$form_handler = xoops_getmodulehandler('forms', 'formulize');

			// Find all entries_are_groups forms
			$sql = "SELECT id_form FROM " . $xoopsDB->prefix('formulize_id') . " WHERE entries_are_groups = 1";
			$result = $xoopsDB->query($sql);
			while ($row = $xoopsDB->fetchArray($result)) {
				$eagFormId = intval($row['id_form']);
				$eagForm = $form_handler->get($eagFormId);
				if (!$eagForm) continue;
				$groupCategories = $eagForm->getVar('group_categories');
				if (!is_array($groupCategories)) continue;

				foreach ($groupCategories as $templateGroupId => $categoryName) {
					// Get all entry groups for this template group
					$egSql = "SELECT groupid FROM " . $xoopsDB->prefix('groups') .
						" WHERE form_id = " . $eagFormId .
						" AND is_group_template = 0 AND entry_id > 0" .
						" AND name LIKE '%" . formulize_db_escape(" - " . $categoryName) . "'";
					$egResult = $xoopsDB->query($egSql);
					$entryGroups = array();
					while ($egRow = $xoopsDB->fetchArray($egResult)) {
						$entryGroups[] = intval($egRow['groupid']);
					}
					$templateGroupMap[intval($templateGroupId)] = $entryGroups;
				}
			}
		}

		if (empty($templateGroupMap)) {
			return false;
		}

		include_once XOOPS_ROOT_PATH . '/modules/formulize/class/usersGroupsPerms.php';
		return formulizePermHandler::synchronizeGroupReferencesInElement($element, $templateGroupMap);
	}

	/**
	 * Get structured metadata about all template groups and how they relate to a given form.
	 * Scans the form and all forms connected via frameworks for linked elements that
	 * point to entries-are-groups forms, then maps those links to the template group categories.
	 *
	 * Authored by Claude Code and Julian Egelstaff - Feb 2026
	 *
	 * @param int $fid The form ID to analyze relationships from
	 * @return array Keyed by group ID, each entry contains:
	 *   'categoryName'   => string - The category name (e.g., "All Users", "Managers")
	 *   'formSingular'   => string - Singular name of the entries-are-groups form
	 *   'formPlural'     => string - Plural name of the entries-are-groups form
	 *   'eagFormId'      => int    - The form ID of the entries-are-groups form
	 *   'linkedElements' => array  - Each entry: ['caption' => string, 'formName' => string]
	 *                                formName is empty string if element is in the current form ($fid)
	 */
	public static function getTemplateGroupMetadataForForm($fid) {
		include_once XOOPS_ROOT_PATH . '/modules/formulize/class/elements.php';
		include_once XOOPS_ROOT_PATH . '/modules/formulize/class/frameworks.php';

		$fid = intval($fid);
		if (!$fid) {
			return array();
		}

		global $xoopsDB;
		$form_handler = xoops_getmodulehandler('forms', 'formulize');
		$element_handler = xoops_getmodulehandler('elements', 'formulize');

		$formObject = $form_handler->get($fid);
		if (!$formObject) {
			return array();
		}

		// 1. Collect all form IDs to scan: current form + related forms
		$formsToScan = array($fid => $formObject->getPlural());
		$frameworks_handler = xoops_getmodulehandler('frameworks', 'formulize');
		$primaryRelationship = new formulizeFramework(-1);
		$allLinks = $frameworks_handler->getLinksGroupedByForm($primaryRelationship, $fid);
		foreach ($allLinks as $linkedFid => $fidLinks) {
			foreach ($fidLinks as $link) {
				$form1 = intval($link['form1']);
				$form2 = intval($link['form2']);
				if (!isset($formsToScan[$form1]) AND $linkFormObject = $form_handler->get($form1)) {
					$formsToScan[$form1] = $linkFormObject->getPlural();
				}
				if (!isset($formsToScan[$form2]) AND $linkFormObject = $form_handler->get($form2)) {
					$formsToScan[$form2] = $linkFormObject->getPlural();
				}
			}
		}

		// 2. For each form, find elements that link to other forms
		// Build map: target_form_id => [{ele_id, caption, formName}]
		$linkedFormElements = array();
		foreach ($formsToScan as $scanFid => $scanFormName) {
			$scanElements = $element_handler->getObjects(null, $scanFid);
			if (is_array($scanElements)) {
				foreach ($scanElements as $el) {
					$sourceInfo = getSourceFormAndElementForLinkedElement($el);
					if ($sourceInfo) {
						$targetFid = $sourceInfo[0];
						$caption = strip_tags($el->getVar('ele_caption'));
						$entry = array(
							'ele_id' => intval($el->getVar('ele_id')),
							'caption' => $caption
						);
						// Include formName only if the element is NOT in the current form
						$entry['formName'] = ($scanFid == $fid) ? '' : $scanFormName;
						$linkedFormElements[$targetFid][] = $entry;
					}
				}
			}
		}

		// 3. Find all forms with entries_are_groups enabled and build metadata from their group_categories
		// Template groups have form_id set but not entry_id; the category name link is via the form's group_categories mapping
		$metadata = array();
		$eagSql = "SELECT id_form FROM " . $xoopsDB->prefix('formulize_id') . " WHERE entries_are_groups = 1";
		$eagResult = $xoopsDB->query($eagSql);
		while ($eagRow = $xoopsDB->fetchArray($eagResult)) {
			$eagFormId = intval($eagRow['id_form']);
			$eagFormObject = $form_handler->get($eagFormId);
			if (!$eagFormObject) { continue; }

			$tgCategories = $eagFormObject->getVar('group_categories');
			if (!is_array($tgCategories) || empty($tgCategories)) { continue; }

			$formSingular = $eagFormObject->getSingular();
			$formPlural = $eagFormObject->getPlural();

			foreach ($tgCategories as $tgGroupId => $categoryName) {
				if (!$categoryName) { continue; }

				$metadata[intval($tgGroupId)] = array(
					'categoryName' => $categoryName,
					'formSingular' => $formSingular,
					'formPlural' => $formPlural,
					'eagFormId' => $eagFormId,
					'linkedElements' => isset($linkedFormElements[$eagFormId]) ? $linkedFormElements[$eagFormId] : array()
				);
			}
		}

		return $metadata;
	}

	/**
	 * Assigns a form to one or more applications, optionally creating a new application, and optionally creating menu links for the form in the applications
	 * @param object $formObject The form object to assign to applications
	 * @param array $applicationIds An array of existing application ids to assign this form to
	 * @param array $newApplicationProperties An associative array of properties for a new application to create and assign this form to.  If empty, no new application will be created.
	 * @param bool $createMenuLinksForApplications If true, menu links will be created for this form in the selected applications (and/or the new application if one is being created)
	 * @param array $groupIdsThatCanEditForm An array of group ids that should be given edit permissions on this form, and will be able to see the menu link for this form in the applications.  Only used if $createMenuLinksForApplications is true.
	 * @throws Exception if there are any problems assigning the form to applications
	 * @return boolean True if successful
	 */
	public static function assignFormToApplications($formObject, $applicationIds = array()) {

		if(!is_a($formObject, 'formulizeForm') OR !$formObject->getVar('fid')) {
			throw new Exception("Cannot assign a non-form to applications.");
		}
		if(!empty($applicationIds)) {
			global $xoopsDB;
			$application_handler = xoops_getmodulehandler('applications','formulize');
			$fid = $formObject->getVar('fid');
			// get all the applcations that we're supposed to assign this form object to
			$selectedAppObjects = $application_handler->get($applicationIds);
			// get the applications this form is currently assigned to
			$assignedAppsForThisForm = $application_handler->getApplicationsByForm($fid);

			$addedToApps = array();
			// assign this form as required to the selected applications
			foreach($selectedAppObjects as $thisAppObject) {
				$thisAppForms = $thisAppObject->getVar('forms');
				if(!in_array($fid, $thisAppForms)) {
					$addedToApps[] = $thisAppObject->getVar('appid');
					$thisAppForms[] = $fid;
					$thisAppObject->setVar('forms', serialize($thisAppForms));
					if(!$application_handler->insert($thisAppObject)) {
						throw new Exception("Could not add the form to one of the applications properly: ".$xoopsDB->error());
					}
				}
			}

			// now remove the form from any applications it used to be assigned to, which were not selected
			$removedFromApps = array();
			foreach($assignedAppsForThisForm as $assignedApp) {
				if(!in_array($assignedApp->getVar('appid'), $applicationIds)){
					// the form is no longer assigned to this app, so remove it from the apps form list
					$assignedAppForms = $assignedApp->getVar('forms');
					$key = array_search($fid, $assignedAppForms);
					unset($assignedAppForms[$key]);
					sort($assignedAppForms); // resets all the keys so there's no gaps
					$assignedApp->setVar('forms',serialize($assignedAppForms));
					if(!$application_handler->insert($assignedApp)) {
						throw new Exception("Could not update one of the applications this form used to be assigned to, so that it's not assigned anymore.");
					}
					$removedFromApps[] = $assignedApp->getVar('appid');
				}
			}

			// if the form was removed from *one* application and added to *one* other, move the menu links to the new application
			if(count($removedFromApps) == 1 AND count($addedToApps) == 1) {
				$application_handler->moveMenuLinksBetweenApplications($removedFromApps[0], $addedToApps[0], $fid);
			}

		}
		return true;
	}

	// NOTE - ALL ELEMENT TYPES MUST HAVE THE mcpElementPropertiesDescriptionAndExamples STATIC METHOD OR ELSE THEY WON'T BE FOUND AS VALID, ONCE THE ADMIN UI USES THE UPSERT METHOD

	/**
	 * Validate that the element type
	 * @param string $elementType The element type to validate - passed by reference so we can correct the case if needed
	 * @param string|null $requestedCategory Optional. If provided, a case insensitive double check of elements with this static $category designation will be attempted
	 * @param bool $return Default is false. If true, the function will return instead of throwing an exception on invalid type
	 * @throws Exception if the element type is not valid, if return is false.
	 * @return bool Returns true if valid. If $return param is true, returns false if not valid, or throws the exception.
	 */
	public static function validateElementType(&$elementType, $requestedCategory = null, $return = false) {
		if(substr($elementType, 0, 11) == 'userAccount') {
			// userAccount elements are valid (not discoverable for MCP, but valid for upsert)
			return true;
		}
		list($elementTypes, $mcpElementDescriptions, $mcpSingleTypeDescriptions) = formulizeHandler::discoverElementTypes();
		$allValidElementTypes = array();
		foreach($elementTypes as $category=>$categoryTypes) {
			if(in_array($elementType, $categoryTypes)) {
				return true;
			} elseif(!$requestedCategory OR $category == $requestedCategory) {
				// try correcting the case of the element type to see if it is in fact in this category
				foreach($categoryTypes as $validElementType) {
					$allValidElementTypes[] = $validElementType;
					if(strtolower($validElementType) == strtolower($elementType)) {
						$elementType = $validElementType;
						return true;
					}
				}
			}
		}
		if($return) {
			return false;
		} else {
			throw new Exception("Element type '$elementType' is not valid. Valid element types are: ".implode(', ', $allValidElementTypes));
		}
	}

	/**
	 * Builds or updates a form element, including creating or renaming the data table field, adding the element to screens, renaming files...
	 * @param array $elementObjectProperties An associative array of properties to set on the element object.  If 'ele_id' is included and is non-zero, it will update that element.  If 'ele_id' is not included or is zero, it will create a new element.
	 * @param array $screenIdsAndPagesForAdding Optional. An array of screen id keys, each with an array of pages (0 is page 1) which this element should be added to. For new elements, if this is empty then they will be added to all multipage screens, on their form, that include all elements.
	 * @param array $screenIdsAndPagesForRemoving Optional. An array of screen id keys, each with an array of pages (0 is page 1) which this element should be removed from. For new elements, this is ignored.
	 * @param string $dataType The data type to use for the database field for this element. If null, default determination of datatypes is used.
	 * @param bool $pi If true, this element will be set as the Principal Identifier for the form it belongs to. Only one element per form can be the Principal Identifier, so if another element is already the PI it will be replaced.
	 * @param bool $makeSubformInterface If true, and this is a linked element, a subform interface will be created on the main form, that the linked entries in this element's form can be easily created/edited
	 * @throws Exception if there are any problems creating or updating the element
	 * @return object returns the element object
	 */
	public static function upsertElementSchemaAndResources($elementObjectProperties, $screenIdsAndPagesForAdding = array(), $screenIdsAndPagesForRemoving = array(), $dataType = null, $pi = false, $makeSubformInterface = false) {

		formulizeHandler::validateElementType($elementObjectProperties['ele_type']);

		$form_handler = xoops_getmodulehandler('forms', 'formulize');
		$element_handler = xoops_getmodulehandler($elementObjectProperties['ele_type'].'Element','formulize');
		$element_id = 0;
		// if ele_id is set in the properties array, use that to load the element object
		if(isset($elementObjectProperties['ele_id'])) {
			$element_id = intval($elementObjectProperties['ele_id']);
		}

		$originalElementNames = array(
			'ele_handle' => '',
			'source_form_id' => '',
			'source_element_handle' => ''
		);
		$elementIsNew = true;
		// get the element object that we care about, or start a new one from scratch if ele_id is 0, null, etc.
		if($element_id AND $elementObject = $element_handler->get($element_id)) {
			$elementIsNew = false;
			$originalElementNames = array(
				'ele_handle' => $elementObject->getVar('ele_handle'),
				'source_form_id' => getSourceFormIdForLinkedElement($elementObject),
				'source_element_handle' => getSourceElementHandleForLinkedElement($elementObject)
			);
		} elseif(isset($elementObjectProperties['fid']) AND $elementObjectProperties['fid'] > 0 AND isset($elementObjectProperties['ele_type'])) {
			if(isset($elementObjectProperties['ele_id'])) {
				unset($elementObjectProperties['ele_id']); // make sure ele_id is not set, so we're acting like it's a new element request
			}
			// create the element and set initial required properties that are necessary for writing files, etc
			$elementObject = $element_handler->create();
			$elementObjectProperties['ele_caption'] = $elementObjectProperties['ele_caption'] ? $elementObjectProperties['ele_caption'] : 'New Element';
			$elementObject->setVar('ele_caption', $elementObjectProperties['ele_caption']);
			$elementObject->setVar('fid', $elementObjectProperties['fid']);
			$elementObject->setVar('ele_handle', isset($elementObjectProperties['ele_handle']) ? $elementObjectProperties['ele_handle'] : '');
			$elementObjectProperties['ele_handle'] = $element_handler->validateElementHandle($elementObject);
		} else {
			throw new Exception('Must provide a valid ele_id to update an existing element, or a valid fid and ele_type to create a new element');
		}

		global $xoopsUser, $xoopsDB;
		$mid = getFormulizeModId();
		$gperm_handler = xoops_gethandler('groupperm');
		if(!$xoopsUser OR $gperm_handler->checkRight("edit_form", $elementObject->getVar('fid'), $xoopsUser->getGroups(), $mid) == false) {
			throw new Exception("Permission denied: You don't have permission to edit this form.");
		}

		$elementObjectProperties = $element_handler->setupAndValidateElementProperties($elementObjectProperties);

		// set all the properties that were passed in and validated
		foreach($elementObjectProperties as $property=>$value) {
			$elementObject->setVar($property, $value);
		}
		if($element_handler->insert($elementObject) == false) {
			// most likely a DB error?
			throw new Exception('Could not create/update element. '.$xoopsDB->error());
		}

		// set PI if that was requested for this element, or if this is the first element with data on the form
		$formObject = $form_handler->get($elementObject->getVar('fid'));
		if($pi OR ($formObject->getVar('pi') == 0 AND $elementObject->hasData AND count($formObject->getVar('elementsWithData')) == 1)) {
			$formObject->setVar('pi', $elementObject->getVar('ele_id'));
			$form_handler->insert($formObject);
		}

		if($elementIsNew) {
			// If no screens specified for adding, add the element to screens on its form where all elements are included already
			if(empty($screenIdsAndPagesForAdding)) {
				addElementToMultiPageScreens($elementObject->getVar('fid'), $elementObject);
			}
			// override passed in $dataType from the element class, if appropriate
			if($elementObject->hasData) {
				if(method_exists($elementObject, 'getDefaultDataType')) {
					$dataType = $elementObject->getDefaultDataType($dataType);
				} elseif(property_exists($elementObject, 'overrideDataType') AND $elementObject->overrideDataType != "") {
					$dataType = $elementObject->overrideDataType;
				} elseif($dataType === null) {
					$dataType = 'text'; // default if nothing else is specified
				}
				if($form_handler->insertElementField($elementObject, $dataType) == false) {
					throw new Exception("Could not create or update the database field for this element: ".$elementObject->getVar('ele_handle')." DB error: ".$xoopsDB->error());
				}
			}
		}

		if(!$elementIsNew) {

			// rename element resources if necessary
			if($originalElementNames['ele_handle'] != $elementObject->getVar('ele_handle')) {
				$element_handler->renameElementResources($elementObject, $originalElementNames['ele_handle']);
			}

			// rename the field in the data table if necessary
			// also manage the datatype in the database if necessary
	    $currentDataTypeInfo = $elementObject->getDataTypeInformation();
	 	  $currentDataType = $currentDataTypeInfo['dataTypeCompleteString'];
			$ele_value = $elementObject->getVar('ele_value');

			if($elementObject->hasData AND
				($originalElementNames['ele_handle'] != $elementObject->getVar('ele_handle')
    			OR $dataType != $currentDataType
			  	OR (isset($ele_value['snapshot']) AND $ele_value['snapshot'] AND $currentDataTypeInfo != 'text'))
				) {
					// figure out if the datatype needs changing...
					if($elementObject->getVar('ele_encrypt')) {
						$dataType = false;
					} elseif(isset($ele_value['snapshot']) AND $ele_value['snapshot'] AND $currentDataType != 'text') {
						$dataType = 'text';
					} elseif($dataType === $currentDataType) {
						$dataType = false; // does not need changing in the data table
					}
					// need to update the name of the field in the data table, and possibly update the type too
					if(!$updateResult = $form_handler->updateField($elementObject, $originalElementNames['ele_handle'], $dataType)) {
						throw new Exception("Could not update the data table field to match the new settings");
					}
			}

		}

		// handle the add/remove of element from screens/pages
		$screen_handler = xoops_getmodulehandler('multiPageScreen', 'formulize');
		foreach($screenIdsAndPagesForAdding as $screenId=>$pageOrdinals) {
			if($screenObject = $screen_handler->get($screenId)) {
				$pages = $screenObject->getVar('pages');
				foreach($pageOrdinals as $pageOrdinal) {
					$pages[$pageOrdinal][] = $elementObject->getVar('ele_id');
				}
				$screenObject->setVar('pages', serialize($pages)); // serialize ourselves, because screen handler insert method does not pass things through cleanVars, which would serialize for us
				$insertResult = $screen_handler->insert($screenObject, force: true);
				if($insertResult == false) {
					throw new Exception("Could not add element ".$elementObject->getVar('ele_id')." to the screen \"".$screenObject->getVar('title')."\" (id: $screenId).");
				}
			}
		}
		foreach($screenIdsAndPagesForRemoving as $screenId=>$pageOrdinal) {
			if($screenObject = $screen_handler->get($screenId)) {
				$pages = $screenObject->getVar('pages');
				foreach($pageOrdinals as $pageOrdinal) {
					$key = array_search($elementObject->getVar('ele_id'), $pages[$pageOrdinal]);
					if($key !== false) {
						unset($pages[$pageOrdinal][$key]);
					}
				}
				$screenObject->setVar('pages', serialize($pages)); // serialize ourselves, because screen handler insert method does not pass things through cleanVars, which would serialize for us
				$insertResult = $screen_handler->insert($screenObject, force: true);
				if($insertResult == false) {
					throw new Exception("Could not remove element ".$elementObject->getVar('ele_id')." from the screen \"".$screenObject->getVar('title')."\" (id: $screenId).");
				}
			}
		}

		// maintain connections in relationships if this is a linked element (and it's new or the source has changed)
		if($elementObject->isLinked) {
			updateLinkedElementConnectionsInRelationships($elementObject->getVar('fid'), $elementObject->getVar('ele_id'), getSourceFormIdForLinkedElement($elementObject), getSourceElementHandleForLinkedElement($elementObject), $originalElementNames['source_form_id'], $originalElementNames['source_element_handle']);
			if($makeSubformInterface) {
				makeSubformInterface(getSourceFormIdForLinkedElement($elementObject), $elementObject->getVar('fid'), $elementObject->getVar('ele_id'));
			}
		}

		return $elementObject;
	}

	/**
	 * Discover available element types and their MCP descriptions
	 * Caches results statically
	 * @param bool|int $update If true, we're gathering details for update tools. Default is false, for gather details for creation tools.
	 * @return array [elementTypes array, elementDescriptions array]
	 */
	public static function discoverElementTypes($update = false) {
		static $elementTypes = [];
		static $elementDescriptions = [];
		static $singleTypeElementProperties = [];
		$update = $update ? 1 : 0;
		if(empty($elementTypes) OR empty($elementDescriptions[$update])) {
			$elementTypes = [];
			$elementDescriptions[$update] = [];
			$singleTypeElementProperties = [];
			// Scan for element class files
			$elementClassPath = XOOPS_ROOT_PATH . '/modules/formulize/class';
			$elementFiles = glob($elementClassPath . '/*Element.php');
			if($elementFiles === false) {
				throw new FormulizeMCPException('No element class files found in ' . $elementClassPath, 'internal_formulize_error');
			}
			foreach ($elementFiles as $file) {
				include_once XOOPS_ROOT_PATH.'/modules/formulize/class/'.basename($file);
				$elementType = str_replace('Element.php', '', basename($file));
				$className = "formulize".ucfirst($elementType)."Element";
				if(methodExistsInClass($className, 'mcpElementPropertiesDescriptionAndExamples')) {
					$category = $className::$category;
					$elementTypes[$category][] = $elementType;
					$elementDescriptions[$update][$category][] = $className::mcpElementPropertiesDescriptionAndExamples($update);
					$singleTypeElementProperties[$category] = null;
					if(methodExistsInClass($className, 'mcpSingleTypeElementProperties')) {
						$singleTypeElementProperties[$category] = $className::mcpSingleTypeElementProperties($update);
					}
				}
			}
		}
		return [$elementTypes, $elementDescriptions[$update], $singleTypeElementProperties];
	}

	/**
	 * Ensures that an element handle is unique within a form, modifying it if necessary
	 * One of elementIdentifer, or formIdentifier must be provided
	 * @param string $element_handle_name The desired element handle name (not the existing handle name, if we're dealing with an existing element)
	 * @param int $elementIdentifer The element id or handle or full object of the element being updated (0 if creating a new element)
	 * @param int $formIdentifier The form id or handle of the form this element belongs to
	 * @return string A unique element handle name
	 */
	static function enforceUniqueElementHandles($element_handle_name, $elementIdentifer=null, $formIdentifier=null) {
		$element_handle_name = formulizeElement::sanitize_handle_name($element_handle_name);
		if (strlen($element_handle_name)) {
			$firstUniqueCheck = true;
			$element_handler = xoops_getmodulehandler('elements','formulize');
			$form_handler = xoops_getmodulehandler('forms', 'formulize');
			if($elementIdentifer AND $elementObject = $element_handler->get($elementIdentifer)) {
				$formId = $elementObject->getVar('fid');
			} elseif($formIdentifier AND $formObject = $form_handler->get($formIdentifier)) {
				$formId = $formObject->getVar('fid');
			} else {
				throw new Exception("Must provide either elementIdentifer or formIdentifier to enforce unique element handles");
			}
			while (!$uniqueCheck = $form_handler->isElementHandleUnique($element_handle_name, $elementIdentifer)) {
				if ($firstUniqueCheck) {
						$element_handle_name = $element_handle_name . "_".$formId;
						$firstUniqueCheck = false;
				} else {
						$element_handle_name = $element_handle_name . "_copy";
				}
			}
		}
		return $element_handle_name;
	}
}
