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
			'subforms' => array(
				'singular' => 'subform interface',
				'plural' => 'subform interfaces'
			),
			// WILL NEED TO BE FILLED IN FURTHER FOR 'LAYOUT' ELEMENTS WHEN THEY HAVE CLASSES
			// AND WHAT TO DO ABOUT DERIVED ELEMENTS IS NOT ENTIRELY CLEAR (NOR OTHER MISC ELEMENTS)
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
	 * Builds or updates a form, including creating or renaming the data table, creating default screens, and setting up permissions, renaming resources...
	 * @param array $formObjectProperties An associative array of properties to set on the form object.  If 'fid' is included and is non-zero, it will update that form.  If 'fid' is not included or is zero, it will create a new form.
	 * @param array $groupIdsThatCanEditForm An array of group ids that should be given edit permissions on this form (only used when creating a new form)
	 * @param array $applicationIds An array of existing application ids to assign this form to (only used when creating a new form)
	 * @throws Exception if there are any problems creating or updating the form
	 * @return object returns the form object
	 */
	public static function upsertFormSchemaAndResources($formObjectProperties = array(), $groupIdsThatCanEditForm = array(), $applicationIds = array(0)) {

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
			$formObjectProperties['title'] = $formObjectProperties['title'] ? $formObjectProperties['title'] : 'New Form';
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
				$menuLinkText = ($formObject->getVar('single') == 'user' OR $formObject->getVar('single') == 'group') ? $formObject->getSingular() : $formObject->getPlural();
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
		return $formObject;
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

	/**
	 * Builds or updates a form element, including creating or renaming the data table field, adding the element to screens, renaming files...
	 * @param array $elementObjectProperties An associative array of properties to set on the element object.  If 'ele_id' is included and is non-zero, it will update that element.  If 'ele_id' is not included or is zero, it will create a new element.
	 * @param array $screenIdsAndPagesForAdding Optional. An array of screen id keys, each with an array of pages (keyed from zero) which this element should be added to. For new elements, if this is empty then they will be added to all multipage screens, on their form, that include all elements.
	 * @param array $screenIdsAndPagesForRemoving Optional. An array of screen id keys, each with an array of pages (keyed from zero) which this element should be removed from. For new elements, this is ignored.
	 * @param string $dataType The data type to use for the database field for this element. If null, default determination of datatypes is used.
	 * @param bool $pi If true, this element will be set as the Principal Identifier for the form it belongs to. Only one element per form can be the Principal Identifier, so if another element is already the PI it will be replaced.
	 * @throws Exception if there are any problems creating or updating the element
	 * @return object returns the element object
	 */
	public static function upsertElementSchemaAndResources($elementObjectProperties, $screenIdsAndPagesForAdding = array(), $screenIdsAndPagesForRemoving = array(), $dataType = null, $pi = false) {

		list($elementTypes, $mcpElementDescriptions) = formulizeHandler::discoverElementTypes();
		if(!in_array($elementObjectProperties['ele_type'], $elementTypes)) {
			throw new Exception('Invalid element type: '.$elementObjectProperties['ele_type']. ' valid_element_types: '.implode(', ', $elementTypes));
		}

		$form_handler = xoops_getmodulehandler('forms', 'formulize');
		$element_handler = xoops_getmodulehandler($elementObjectProperties['ele_type'].'Element','formulize');
		$element_id = 0;
		// if ele_id is set in the properties array, use that to load the element object
		if(isset($elementObjectProperties['ele_id'])) {
			$element_id = intval($elementObjectProperties['ele_id']);
		}

		list($elementTypes, $mpcElementDescriptions) = formulizeHandler::discoverElementTypes();
		$originalElementNames = array();
		$elementIsNew = true;
		// get the element object that we care about, or start a new one from scratch if ele_id is 0, null, etc.
		if($element_id AND $elementObject = $element_handler->get($element_id)) {
			$elementIsNew = false;
			$form_id = $elementObject->getVar('fid');
			$originalElementNames = array(
				'ele_handle' => $elementObject->getVar('ele_handle')
			);
		} elseif(isset($elementObjectProperties['fid']) AND $elementObjectProperties['fid'] > 0 AND isset($elementObjectProperties['ele_type']) AND in_array($elementObjectProperties['ele_type'], $elementTypes)) {
			$elementObject = $element_handler->create();
			$elementObjectProperties['ele_caption'] = $elementObjectProperties['ele_caption'] ? $elementObjectProperties['ele_caption'] : 'New Element';
			$form_id = intval($elementObjectProperties['fid']);
		} else {
			if(!in_array($elementObjectProperties['ele_type'], $elementTypes)) {
				throw new Exception('Invalid element type: '.$elementObjectProperties['ele_type'].'. Valid types: '.implode(', ', $elementTypes));
			}
			throw new Exception('Must provide a valid ele_id to update an existing element, or a valid fid and ele_type to create a new element');
		}

		global $xoopsUser, $xoopsDB;
		$mid = getFormulizeModId();
		$gperm_handler = xoops_gethandler('groupperm');
		if(!$xoopsUser OR $gperm_handler->checkRight("edit_form", $form_id, $xoopsUser->getGroups(), $mid) == false) {
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

		// set PI if that was requested for this element
		if($pi) {
			$formObject = $form_handler->get($elementObject->getVar('fid'));
			$formObject->setVar('pi', $elementObject->getVar('ele_id'));
			$form_handler->insert($formObject);
		}

		if($elementIsNew) {
			// If no screens specified for adding, add the element to screens on its form where all elements are included already
			if(empty($screenIdsAndPagesForAdding)) {
				addElementToMultiPageScreens($elementObject->getVar('fid'), $elementObject);
			}
			// override passed in $dataType from the element class, if appropriate
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

		if(!$elementIsNew) {

			// rename element resources if necessary
			if($originalElementNames['ele_handle'] != $elementObject->getVar('ele_handle')) {
				$element_handler->renameElementResources($elementObject, $originalElementNames['ele_handle']);
			}

			// rename the field in the data table if necessary
			// also manage the datatype in the database if necessary
	    $currentDataTypeInfo = $elementObject->getDataTypeInformation();
	 	  $currentDataType = $currentDataTypeInfo['dataType'];
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

			// handle the add/remove of element from screens/pages
			$screen_handler = xoops_getmodulehandler('multiPageScreen', 'formulize');
			foreach($screenIdsAndPagesForAdding as $screenId=>$pageOrdinals) {
				$screenObject = $screen_handler->get($screenId);
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
			foreach($screenIdsAndPagesForRemoving as $screenId=>$pageOrdinal) {
				$screenObject = $screen_handler->get($screenId);
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

		return $elementObject;
	}

	/**
	 * Discover available element types and their MCP descriptions
	 * Caches results statically
	 * @param bool $mcpTypeNames If true, will return the element type names as they should be used in the MCP (with _ instead of no space, before users or linked modifiers)
	 * @return array [elementTypes array, elementDescriptions array]
	 */
	public static function discoverElementTypes($mcpTypeNames = false) {
		static $elementTypes = [];
		static $elementDescriptions = [];
		if(empty($elementTypes) OR empty($elementDescriptions)) {
			// Scan for element class files
			$elementClassPath = XOOPS_ROOT_PATH . '/modules/formulize/class';
			$elementFiles = glob($elementClassPath . '/*Element.php');
			if($elementFiles === false) {
				throw new FormulizeMCPException('No element class files found in ' . $elementClassPath, 'internal_formulize_error');
			}
			foreach ($elementFiles as $file) {
				include_once XOOPS_ROOT_PATH.'/modules/formulize/class/'.basename($file);
				$elementType = str_replace('Element.php', '', basename($file));
				if(method_exists('formulize'.ucfirst($elementType).'Element', 'mcpElementPropertiesDescriptionAndExamples')) {
					$elementTypes[] = $mcpTypeNames ? str_replace('linked', '_linked', str_replace('users', '_users', $elementType)) : $elementType;
					$className = "formulize".ucfirst($elementType)."Element";
					$elementDescriptions[] = $className::mcpElementPropertiesDescriptionAndExamples();
				}
			}
		}
		return [$elementTypes, $elementDescriptions];
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
