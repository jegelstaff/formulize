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
	 * @return array An array with two elements: the form id of the created or updated form, and a boolean indicating whether the singular or plural names were changed (which may require a reload of the settings page if we're in the standard admin UI)
	 */
	public static function upsertFormSchemaAndResources($formObjectProperties = array(), $groupIdsThatCanEditForm = array(), $applicationIds = array(0)) {

		$form_handler = xoops_getModuleHandler('forms', 'formulize');
		$application_handler = xoops_getmodulehandler('applications','formulize');
		$gperm_handler = xoops_gethandler('groupperm');
		global $xoopsDB;
		$fid = 0;
		// if fid is set in the properties array, use that to load the form object
		if(isset($formObjectProperties['fid'])) {
			$fid = intval($formObjectProperties['fid']);
		}
		// get the form object that we care about, or start a new one from scratch if fid is 0, null, etc.
		$formIsNew = true;
		if($fid AND $formObject = $form_handler->get($fid)) {
			$formIsNew = false;
		} else {
			$formObject = $form_handler->create();
		}
		$originalFormNames = array(
			'singular' => $formObject->getSingular(),
			'plural' => $formObject->getPlural(),
			'form_handle' => $formObject->getVar('form_handle')
		);
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
		return array($fid, $singularPluralChanged);
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

}
