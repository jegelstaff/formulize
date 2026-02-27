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

require_once XOOPS_ROOT_PATH . "/modules/formulize/class/elements.php"; // you need to make sure the base element class has been read in first!

class formulizeUserAccountElement extends formulizeElement {

		var $userProperty = null; // the user property that this element represents, ie: 'uid', 'email', 'name', etc. Overridden in child classes.

    function __construct() {
        $this->name = "User Account Settings Base Element";
        $this->hasData = false; // set to false if this is a non-data element, like the subform or the grid
        $this->needsDataType = false; // set to false if you're going force a specific datatype for this element using the overrideDataType
        $this->adminCanMakeRequired = false; // set to true if the webmaster should be able to toggle this element as required/not required
        $this->alwaysValidateInputs = true; // set to true if you want your custom validation function to always be run.  This will override any required setting that the webmaster might have set, so the recommendation is to set adminCanMakeRequired to false when this is set to true.
        $this->isSystemElement = true;
				$this->isUserAccountElement = true;
        parent::__construct();
    }

}

#[AllowDynamicProperties]
class formulizeUserAccountElementHandler extends formulizeElementsHandler {

    var $db;
    var $clickable; // used in formatDataForList
    var $striphtml; // used in formatDataForList
    var $length; // used in formatDataForList

    function __construct($db) {
        $this->db =& $db;
    }

    function create() {
        return new formulizeUserAccountElement();
    }

    // this method would gather any data that we need to pass to the template, besides the ele_value and other properties that are already part of the basic element class
    // it receives the element object and returns an array of data that will go to the admin UI template
    // when dealing with new elements, $element might be FALSE
    // can organize template data into two top level keys, advanced-tab-values and options-tab-values, if there are some options for the element type that appear on the Advanced tab in the admin UI. This requires an additional template file with _advanced.html as the end of the name. Text elements have an example.
		function adminPrepare($element) {
			$ele_value = $element ? $element->getVar('ele_value') : array();
			$defaultValueSource = isset($ele_value['defaultValueSource']) ? $ele_value['defaultValueSource'] : '';

			// Build list of non-userAccount elements in the same form for the dropdown
			$nonUserAccountElements = array();
			if($element) {
				$form_handler = xoops_getmodulehandler('forms', 'formulize');
				$formObject = $form_handler->get($element->getVar('fid'));
				$elementTypes = $formObject->getVar('elementTypes');
				$elementCaptions = $formObject->getVar('elementCaptions');
				foreach($elementTypes as $elementId => $type) {
					if(substr($type, 0, 11) !== 'userAccount') {
						$nonUserAccountElements[$elementId] = strip_tags($elementCaptions[$elementId]);
					}
				}
			}

			// Derive label from element name, e.g. "User Account First Name" -> "first name"
			$elementObject = $this->create();
			$fieldLabel = strtolower(str_replace('User Account ', '', $elementObject->name));

			return array(
				'defaultValueSource' => $defaultValueSource,
				'nonUserAccountElements' => $nonUserAccountElements,
				'defaultValueFieldLabel' => $fieldLabel,
			);
    }

    // this method would read back any data from the user after they click save in the admin UI, and save the data to the database, if it were something beyond what is handled in the basic element class
    // this is called as part of saving the options tab.  It receives a copy of the element object immediately prior to it being saved, so the element object will have all its properties set as they would be based on the user's changes in the names & settings tab, and in the options tab (the tabs are saved in order from left to right).
    // the exception is the special ele_value array, which is passed separately from the object (this will contain the values the user set in the Options tab)
    // You can modify the element object in this function and since it is an object, and passed by reference by default, then your changes will be saved when the element is saved.
    // You should return a flag to indicate if any changes were made, so that the page can be reloaded for the user, and they can see the changes you've made here.
    // advancedTab is a flag to indicate if this is being called from the advanced tab (as opposed to the Options tab, normal behaviour). In this case, you have to go off first principals based on what is in $_POST to setup the advanced values inside ele_value (presumably).
		function adminSave($element, $ele_value = array(), $advancedTab = false) {
			$element->setVar('ele_value', $ele_value);
    }

    // this method reads the current state of an element based on the user's input, and the admin options, and sets ele_value to what it needs to be so we can render the element correctly
    // it must return $ele_value, with the correct value set in it, so that it will render as expected in the render method
		// $element is the element object
		// $value is the value that was retrieved from the database for this element in the active entry.  It is a raw value, no processing has been applied, it is exactly what is in the database (as prepared in the prepareDataForSaving method and then written to the DB)
    // $entry_id is the ID of the entry being loaded
		function loadValue($element, $value, $entry_id) {
			$value = null;
			$member_handler = xoops_gethandler('member');
			$dataHandler = new formulizeDataHandler($element->getVar('fid'));
			if($element->userProperty AND $user = $member_handler->getUser($dataHandler->getElementValueInEntry($entry_id, 'formulize_user_account_uid_'.$element->getVar('fid')))) {
				if(substr($element->userProperty, 0, 8) == 'profile:') {
					$profile_handler = xoops_getmodulehandler('profile', 'profile');
					$profile = $profile_handler->get($user->getVar('uid'));
					$property = substr($element->userProperty, 8);
					$value = $profile->getVar($property);
				} elseif($element->userProperty != 'pass') { // don't show password, UI just used for entering new password
					$value = $user->getVar($element->userProperty);
				}
			}
			// If no user exists yet and a default value source is configured, use the source element's value from the current entry
			if($value === null AND is_numeric($entry_id)) {
				$ele_value = $element->getVar('ele_value');
				if(isset($ele_value['defaultValueSource']) AND $ele_value['defaultValueSource']) {
					$value = $dataHandler->getElementValueInEntry($entry_id, intval($ele_value['defaultValueSource']));
				}
			}
			return $value;
    }

    // this method returns any custom validation code (javascript) that should figure out how to validate this element
    // 'myform' is a name enforced by convention that refers to the form where this element resides
    // use the adminCanMakeRequired property and alwaysValidateInputs property to control when/if this validation code is respected
    function generateValidationCode($caption, $markupName, $element, $entry_id) {
    }

    // this method will read what the user submitted, and package it up however we want for insertion into the form's datatable
    // You can return {WRITEASNULL} to cause a null value to be saved in the database
    // $value is what the user submitted
    // $element is the element object
		// $entry_id is the ID number of the entry that this data is being saved into. Can be "new", or null in the event of a subformblank entry being saved.
    // $subformBlankCounter is the instance of a blank subform entry we are saving. Multiple blank subform values can be saved on a given pageload and the counter differentiates the set of data belonging to each one prior to them being saved and getting an entry id of their own.
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
      return $value; // we're not making any modifications for this element type
    }

    // this method will take a text value that the user has specified at some point, and convert it to a value that will work for comparing with values in the database.  This is used primarily for preparing user submitted text values for saving in the database, or for comparing to values in the database, such as when users search for things.  The typical user submitted values would be coming from a condition form (ie: fieldX = [term the user typed in]) or other situation where the user types in a value that needs to interact with the database.
    // it is only necessary to do special logic here if the values stored in the database do not match what users would be typing, ie: you're using coded numbers in the database, but displaying text on screen to users
    // this would be where a Yes value would be converted to a 1, for example, in the case of a yes/no element, since 1 is how yes is represented in the database for that element type
    // $partialMatch is used to indicate if we should search the values for partial string matches, like On matching Ontario.  This happens in the getData function when processing filter terms (ie: searches typed by users in a list of entries)
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
		$this->clickable = false; // make urls clickable
		$this->striphtml = true; // remove html tags as a security precaution
		$this->length = 255; // truncate to a maximum of 100 characters, and append ... on the end

		return parent::formatDataForList($value); // always return the result of formatDataForList through the parent class (where the properties you set here are enforced)
	}

	// utility function to render radio buttons for this user account element types
	function renderUserAccountRadioButtons($options, $ele_value, $caption, $markupName, $isDisabled) {
		$disabled = ($isDisabled) ? 'disabled="disabled"' : '';
		$form_ele = new XoopsFormElementTray('', '<br>');
		foreach($options as $oKey=>$oValue) {
			$t = new XoopsFormRadio(
				'',
				$markupName,
				$ele_value
			);
			$t->addOption($oKey, $oValue);
			$t->setExtra("onchange=\"javascript:formulizechanged=1;\" $disabled");
			$form_ele->addElement($t);
			unset($t);
		}
		$form_ele = new XoopsFormLabel(
			$caption,
			$form_ele->render(),
			$markupName
		);
		return $form_ele;
	}

	/**
	 * Process user account data that was submitted in POST
	 * Will only run once, caching the user id for future calls
	 * @param int $formId The id of the form the element is in
	 * @param int $entryId The id of the entry that was submitted
	 * @return int|bool the user id or false on failure
	 */
	static public function processUserAccountSubmission($formId, $entryId) {
		if(!security_check($formId, $entryId)) {
			throw new Exception("You do not have permission to access this entry");
		}
		global $xoopsUser;
		if(!formulizePermHandler::user_can_edit_entry($formId, $xoopsUser->getVar('uid'), $entryId)) {
			throw new Exception("You do not have permission to edit this entry");
		}
		static $results = array();
		$cacheKey = $formId.'-'.$entryId;
		if(!isset($results[$cacheKey])) {
			$results[$cacheKey] = false;
			$form_handler = xoops_getmodulehandler('forms', 'formulize');
			if($formObject = $form_handler->get($formId) AND $formObject->getVar('entries_are_users')) {
				$data_handler = new formulizeDataHandler($formId);
				$member_handler = xoops_gethandler('member');
				$profile_handler = xoops_getmodulehandler('profile', 'profile');
				$element_handler = xoops_getmodulehandler('elements', 'formulize');
				if($entryUserId = intval($data_handler->getElementValueInEntry($entryId, 'formulize_user_account_uid_'.$formId))) {
					$userObject = $member_handler->getUser($entryUserId);
					$profile = $profile_handler->get($userObject->getVar('uid'));
				} else {
					global $xoopsConfig;
					$userObject = $member_handler->createUser();
					$profile = $profile_handler->create();
					$userObject->setVar('user_avatar', 'blank.gif');
					$userObject->setVar('theme', $xoopsConfig['theme_set']);
					$userObject->setVar('level', 1);
				}
				$unameParts = array();
				foreach($form_handler->getUserAccountElementTypes() as $userAccountElementType) {
					if($userAccountElementType != 'Uid' AND $accountElement = $element_handler->get('formulize_user_account_'.strtolower(str_replace("userAccount", "", $userAccountElementType)).'_'.$formId)) {
						$elementId = $accountElement->getVar('ele_id');
						$userProperty = $accountElement->userProperty;
						$value = $_POST['de_'.$formId.'_'.$entryId.'_'.$elementId];
						if($userProperty == 'pass') {
							if($value === '') {
								continue; // don't change password if no value entered
							}
							global $icmsConfigUser;
            	$icmspass = new icms_core_Password();
            	$salt = $icmspass->createSalt();
            	$enc_type = $icmsConfigUser['enc_type'];
            	$value = $icmspass->encryptPass($value, $salt, $enc_type);
  	          $userObject->setVar('salt', $salt);
    	        $userObject->setVar('enc_type', $enc_type);
						}
						if(substr($userProperty, 0, 8) == 'profile:') {
							$property = substr($userProperty, 8);
							if($property == '2faphone') {
								$value = preg_replace('/[^0-9]/', '', $value);
							}
							if($property == 'timezone') {
								// Also set legacy timezone_offset to the standard (non-DST) offset
								$userObject->setVar('timezone_offset', formulize_getStandardTimezoneOffset($value));
							}
							$profile->setVar($property, $value);
						} else {
							if($userProperty == 'uname') {
								$unameParts[] = $value;
								$value = implode(' ', $unameParts);
							}
							$userObject->setVar($userProperty, $value);
						}
					}
				}
				// update base user object
				if($member_handler->insertUser($userObject)) {
					$userId = $userObject->getVar('uid');
					$results[$cacheKey] = $userId;

					// update profile object associated with user
					if(!$entryUserId) {
						$profile->setVar('profileid', $userId);
					}
					$profile_handler->insert($profile);

					// update user's group memberships based on the groups selected in the Group Membership element, if it exists.  If the element doesn't exist, then we won't make any changes to group memberships (since submitted and current group ids will be identical -- except for the enforcement of registered users and anonymous user group memberships/exclusions, see below).
					$currentGroupIds = $member_handler->getGroupsByUser($userId);
					$submittedGroupIds = $currentGroupIds; // default to their current groups in case we can't find a submission
					if($groupMembershipElement = $element_handler->get('formulize_user_account_groupmembership_'.$formId)) {
						$groupMembershipElementId = $groupMembershipElement->getVar('ele_id');
						$submittedGroupIds = isset($_POST['de_'.$formId.'_'.$entryId.'_'.$groupMembershipElementId]) ? $_POST['de_'.$formId.'_'.$entryId.'_'.$groupMembershipElementId] : array();
						if(!is_array($submittedGroupIds)) {
							$submittedGroupIds = array($submittedGroupIds);
						}
					}
					// Always ensure user is in Registered Users group and not in the anonymous users group
					if(!in_array(XOOPS_GROUP_USERS, $submittedGroupIds)) {
						$submittedGroupIds[] = XOOPS_GROUP_USERS;
					}
					$anonGroupKey = array_search(XOOPS_GROUP_ANONYMOUS, $submittedGroupIds);
					if($anonGroupKey !== false) {
						unset($submittedGroupIds[$anonGroupKey]);
					}
					// only webmasters can assign to the webmasters group
					$webmastersGroupKey = array_search(XOOPS_GROUP_ADMIN, $submittedGroupIds);
					if($webmastersGroupKey !== false AND !in_array(XOOPS_GROUP_ADMIN, $xoopsUser->getGroups())) {
						unset($submittedGroupIds[$webmastersGroupKey]);
					}
					// conversely, if the target user is a webmaster, make sure they stay in the webmasters group, if a non-webmaster is submitting groups
					if(in_array(XOOPS_GROUP_ADMIN, $currentGroupIds)
					AND !in_array(XOOPS_GROUP_ADMIN, $submittedGroupIds)
				  AND !in_array(XOOPS_GROUP_ADMIN, $xoopsUser->getGroups())) {
						$submittedGroupIds[] = XOOPS_GROUP_ADMIN;
					}
					// Ensure user is in the default groups specified in the form settings,
					// subject to per-group conditions and template group resolution
					$defaultGroups = $formObject->getVar('entries_are_users_default_groups');
					if(is_array($defaultGroups) && !empty($defaultGroups)) {
						$allConditions = $formObject->getVar('entries_are_users_conditions');
						if(!is_array($allConditions)) {
							$allConditions = array();
						}
						$elementLinks = $formObject->getVar('entries_are_users_default_groups_element_links');
						if(!is_array($elementLinks)) {
							$elementLinks = array();
						}
						foreach($defaultGroups as $defaultGroupId) {
							$defaultGroupId = intval($defaultGroupId);
							// Check per-group conditions if any exist
							if(isset($allConditions[$defaultGroupId]) && !empty($allConditions[$defaultGroupId])) {
								$conditionsMet = checkElementConditions($allConditions[$defaultGroupId], $formId, $entryId, null, -1);
								if(!$conditionsMet) {
									continue; // conditions not met, skip this group
								}
							}
							// Resolve template groups to the actual entry group
							if($resolvedGroupIds = self::resolveDefaultGroupId($defaultGroupId, $formId, $entryId, $elementLinks, $data_handler)) {
								foreach($resolvedGroupIds as $resolvedGroupId) {
									if($resolvedGroupId && !in_array($resolvedGroupId, $submittedGroupIds)) {
										$submittedGroupIds[] = $resolvedGroupId;
									}
								}
							}
						}
					}
					// Add/remove from appropriate groups
					$validGroupIds = array_keys($member_handler->getGroups(id_as_key: true));
					foreach($submittedGroupIds as $i=>$groupId) {
						// make sure everything is an integer
						$groupId = intval($groupId);
						$submittedGroupIds[$i] = $groupId;
						if(!in_array($groupId, $currentGroupIds) AND in_array($groupId, $validGroupIds)) {
							if($member_handler->addUserToGroup($groupId, $userId) == false) {
								throw new Exception("Failed to add user to group ID $groupId");
							}
						}
					}
					foreach($currentGroupIds as $groupId) {
						if(!in_array($groupId, $submittedGroupIds)) {
							if($member_handler->removeUsersFromGroup($groupId, array($userId)) == false) {
								throw new Exception("Failed to remove user from group ID $groupId");
							}
						}
					}
				}
			}
		}
		return $results[$cacheKey];
	}

	/**
	 * Resolve a default group ID to the actual group(s) the user should be added to.
	 * For regular groups, returns the group ID as-is.
	 * For template groups, uses element_links to find which entry in the entries_are_groups
	 * form is referenced by the current entry, then looks up the actual group for that
	 * entry + category combination.
	 *
	 * @param int $defaultGroupId The default group ID (may be a template group)
	 * @param int $formId The entries_are_users form ID
	 * @param int $entryId The current entry being processed
	 * @param array $elementLinks The entries_are_users_default_groups_element_links from form settings
	 * @param object $data_handler The data handler for the form
	 * @return array The resolved group IDs, or an empty array if resolution failed
	 */
	static private function resolveDefaultGroupId($defaultGroupId, $formId, $entryId, $elementLinks, $data_handler) {
		global $xoopsDB;
		// Check if this is a template group
		$sql = "SELECT is_group_template, form_id FROM " . $xoopsDB->prefix('groups') . " WHERE groupid = " . intval($defaultGroupId);
		$result = $xoopsDB->query($sql);
		$groupRow = $xoopsDB->fetchArray($result);
		if(!$groupRow) {
			return array();
		}
		// Regular group — use as-is
		if(!$groupRow['is_group_template']) {
			return array($defaultGroupId);
		}
		// Template group — resolve to the actual entry group
		// Template groups store form_id (the entries_are_groups form) but not entry_id
		$eagFormId = intval($groupRow['form_id']);
		$form_handler = xoops_getmodulehandler('forms', 'formulize');
		$eagFormObject = $form_handler->get($eagFormId);
		if(!$eagFormObject) {
			return array();
		}
		$groupCategories = $eagFormObject->getVar('group_categories');
		if(!is_array($groupCategories) || !isset($groupCategories[$defaultGroupId])) {
			return array();
		}
		$categoryName = $groupCategories[$defaultGroupId];

		// Get the element links for this template group — these are element IDs in the entries_are_users
		// form (or related forms) that link to the entries_are_groups form
		if(!isset($elementLinks[$defaultGroupId]) || !is_array($elementLinks[$defaultGroupId]) || empty($elementLinks[$defaultGroupId])) {
			return array(); // no element links configured, can't resolve
		}
		$linkedElementIds = $elementLinks[$defaultGroupId];

		// Use gatherDataset with frid=-1 (primary relationship) to get data across related forms,
		// so we can read linked element values even if the element is in a different form
		$element_handler = xoops_getmodulehandler('elements', 'formulize');
		$entryData = gatherDataset($formId, filter: $entryId, frid: -1, bypassCache: true);

		$resolvedGroupIds = array();

		foreach($linkedElementIds as $linkedEleId) {
			$linkedEleId = intval($linkedEleId);
			$linkedElement = $element_handler->get($linkedEleId);
			if(!$linkedElement) {
				continue;
			}
			$linkedHandle = $linkedElement->getVar('ele_handle');
			// Get the value from the gathered dataset (works across related forms)
			if(!isset($entryData[0]) || !$entryData[0]) {
				continue;
			}
			$linkedValue = getValue($entryData[0], $linkedHandle, raw: true);
			if(!$linkedValue) {
				continue;
			}
			// The linked value is the entry_id in the entries_are_groups form
			// For multi-value linked elements (comma-separated), handle each
			$linkedEntryIds = is_array($linkedValue) ? $linkedValue : explode(',', trim($linkedValue, ','));
			foreach($linkedEntryIds as $eagEntryId) {
				$eagEntryId = intval($eagEntryId);
				if(!$eagEntryId) {
					continue;
				}
				// Look up the actual group for this entry + category
				$sql = "SELECT groupid FROM " . $xoopsDB->prefix('groups') .
					   " WHERE form_id = " . intval($eagFormId) .
					   " AND entry_id = " . intval($eagEntryId) .
					   " AND is_group_template = 0" .
					   " AND name LIKE '%" . formulize_db_escape(" - " . $categoryName) . "'";
				$result = $xoopsDB->query($sql);
				if($row = $xoopsDB->fetchArray($result)) {
					$resolvedGroupIds[] = intval($row['groupid']);
				}
			}
		}
		return $resolvedGroupIds;
	}

	/**
	 * Re-evaluate conditional default group memberships for an entries_are_users entry.
	 * This is called when a save occurs that could affect per-group conditions or template
	 * group resolution, but processUserAccountSubmission did not run (e.g., the user account
	 * elements were not in the form submission, or the save was in a connected form).
	 *
	 * Only operates when:
	 * - The form has entries_are_users enabled
	 * - A user is already associated with the entry
	 * - Base conditions (key 0) are met (or no base conditions exist)
	 * - The form has default groups with per-group conditions
	 *
	 * For conditional default groups:
	 * - If conditions pass: adds user to the resolved group
	 * - If conditions fail: removes user from the resolved group (and any template family groups)
	 *
	 * @param int $formId The entries_are_users form ID
	 * @param int $entryId The entry ID to re-evaluate
	 * @return bool True if re-evaluation was performed, false if skipped
	 */
	static public function reevaluateDefaultGroupMemberships($formId, $entryId) {
		$form_handler = xoops_getmodulehandler('forms', 'formulize');
		$formObject = $form_handler->get($formId);
		if(!$formObject || !$formObject->getVar('entries_are_users')) {
			return false;
		}

		$defaultGroups = $formObject->getVar('entries_are_users_default_groups');
		if(!is_array($defaultGroups) || empty($defaultGroups)) {
			return false;
		}

		// Check if a user is already associated with this entry
		$data_handler = new formulizeDataHandler($formId);
		$userId = intval($data_handler->getElementValueInEntry($entryId, 'formulize_user_account_uid_'.$formId));
		if(!$userId) {
			return false; // no user associated, nothing to re-evaluate
		}

		$allConditions = $formObject->getVar('entries_are_users_conditions');
		if(!is_array($allConditions)) {
			$allConditions = array();
		}

		$elementLinks = $formObject->getVar('entries_are_users_default_groups_element_links');
		if(!is_array($elementLinks)) {
			$elementLinks = array();
		}

		// Check if there's anything to re-evaluate: either conditional groups, or
		// template groups with element_links (whose resolved group may have changed)
		$hasWorkToDo = false;
		foreach($defaultGroups as $gid) {
			$gid = intval($gid);
			if(isset($allConditions[$gid]) && !empty($allConditions[$gid])) {
				$hasWorkToDo = true;
				break;
			}
			if(isset($elementLinks[$gid]) && is_array($elementLinks[$gid]) && !empty($elementLinks[$gid])) {
				$hasWorkToDo = true;
				break;
			}
		}
		if(!$hasWorkToDo) {
			return false;
		}

		include_once XOOPS_ROOT_PATH . '/modules/formulize/include/elementdisplay.php';
		include_once XOOPS_ROOT_PATH . '/modules/formulize/include/extract.php';

		// Check base conditions (key 0) — if they exist and aren't met, skip
		if(isset($allConditions[0]) && !empty($allConditions[0])) {
			$baseConditionsMet = checkElementConditions($allConditions[0], $formId, $entryId, null, -1);
			if(!$baseConditionsMet) {
				return false; // base conditions not met
			}
		}

		$member_handler = xoops_gethandler('member');
		$currentGroupIds = $member_handler->getGroupsByUser($userId);

		foreach($defaultGroups as $defaultGroupId) {
			$defaultGroupId = intval($defaultGroupId);
			$hasConditions = isset($allConditions[$defaultGroupId]) && !empty($allConditions[$defaultGroupId]);
			$hasLinks = isset($elementLinks[$defaultGroupId]) && is_array($elementLinks[$defaultGroupId]) && !empty($elementLinks[$defaultGroupId]);

			// Skip groups that have neither conditions nor element_links to re-evaluate
			if(!$hasConditions && !$hasLinks) {
				continue;
			}

			// Check per-group conditions if any exist; default to true (unconditional)
			$conditionsMet = true;
			if($hasConditions) {
				$conditionsMet = checkElementConditions($allConditions[$defaultGroupId], $formId, $entryId, null, -1);
			}

			$resolvedGroupIds = self::resolveDefaultGroupId($defaultGroupId, $formId, $entryId, $elementLinks, $data_handler);
			foreach($resolvedGroupIds as $resolvedGroupId) {

				if($conditionsMet && $resolvedGroupId) {
					// Conditions met (or unconditional): add to the resolved group,
					// and remove from any other groups in the same template family
					// (handles the case where the resolved group changed)
					$templateFamilyGroups = self::getAllGroupsForTemplateCategory($defaultGroupId);
					foreach($templateFamilyGroups as $familyGroupId) {
						if(!in_array($familyGroupId, $resolvedGroupIds) && in_array($familyGroupId, $currentGroupIds)) {
							$member_handler->removeUsersFromGroup($familyGroupId, array($userId));
							$currentGroupIds = array_values(array_diff($currentGroupIds, array($familyGroupId)));
						}
					}
					if(!in_array($resolvedGroupId, $currentGroupIds)) {
						$member_handler->addUserToGroup($resolvedGroupId, $userId);
						$currentGroupIds[] = $resolvedGroupId;
					}
				} elseif(!$conditionsMet) {
					// Conditions not met: remove from the resolved group and any template family groups
					$groupsToRemove = array();
					if($resolvedGroupId) {
						$groupsToRemove[] = $resolvedGroupId;
					}
					$templateFamilyGroups = self::getAllGroupsForTemplateCategory($defaultGroupId);
					$groupsToRemove = array_unique(array_merge($groupsToRemove, $templateFamilyGroups));
					foreach($groupsToRemove as $removeGroupId) {
						if(in_array($removeGroupId, $currentGroupIds)) {
							$member_handler->removeUsersFromGroup($removeGroupId, array($userId));
							$currentGroupIds = array_values(array_diff($currentGroupIds, array($removeGroupId)));
						}
					}
				}

			}
		}

		return true;
	}

	/**
	 * Get all entry-specific groups that belong to the same template family as the given template group.
	 * These are groups created for individual entries in an entries_are_groups form, sharing the same
	 * category name suffix. Returns empty array if the group is not a template group.
	 *
	 * @param int $templateGroupId The template group ID
	 * @return array Array of group IDs in the same template family
	 */
	static private function getAllGroupsForTemplateCategory($templateGroupId) {
		global $xoopsDB;
		$sql = "SELECT is_group_template, form_id FROM " . $xoopsDB->prefix('groups') . " WHERE groupid = " . intval($templateGroupId);
		$result = $xoopsDB->query($sql);
		$row = $xoopsDB->fetchArray($result);
		if(!$row || !$row['is_group_template']) {
			return array(); // not a template group, no family to find
		}
		$eagFormId = intval($row['form_id']);

		// Get the category name for this template group
		$form_handler = xoops_getmodulehandler('forms', 'formulize');
		$eagFormObject = $form_handler->get($eagFormId);
		if(!$eagFormObject) {
			return array();
		}
		$groupCategories = $eagFormObject->getVar('group_categories');
		if(!is_array($groupCategories) || !isset($groupCategories[$templateGroupId])) {
			return array();
		}
		$categoryName = $groupCategories[$templateGroupId];

		// Find all entry-specific groups with the same category suffix
		$sql = "SELECT groupid FROM " . $xoopsDB->prefix('groups') .
			   " WHERE form_id = " . intval($eagFormId) .
			   " AND is_group_template = 0" .
			   " AND name LIKE '%" . formulize_db_escape(" - " . $categoryName) . "'";
		$result = $xoopsDB->query($sql);
		$groups = array();
		while($groupRow = $xoopsDB->fetchArray($result)) {
			$groups[] = intval($groupRow['groupid']);
		}
		return $groups;
	}

}

// Standalone function for generating the shared email/phone validation code.
// Not a method on the handler class, so other element types won't inherit it.
// Both the email and phone element handlers call this from their generateValidationCode methods.
// The output must be byte-identical regardless of which element calls it, so that
// the deduplication logic in _drawValidationJS() (formdisplay.php) ensures it only runs once.
function formulizeGenerateUserAccountEmailPhoneValidation($element, $entry_id) {
	$fid = $element->getVar('id_form');
	$element_handler = xoops_getmodulehandler('elements', 'formulize');

	// Always look up both elements by handle, regardless of which one is calling
	$emailElement = $element_handler->get('formulize_user_account_email_'.$fid);
	$phoneElement = $element_handler->get('formulize_user_account_phone_'.$fid);
	$emailElementId = $emailElement ? $emailElement->getVar('ele_id') : 0;
	$phoneElementId = $phoneElement ? $phoneElement->getVar('ele_id') : 0;

	// Build markup names from the standard pattern
	$emailMarkupName = $emailElement ? 'de_'.$fid.'_'.$entry_id.'_'.$emailElementId : '';
	$phoneMarkupName = $phoneElement ? 'de_'.$fid.'_'.$entry_id.'_'.$phoneElementId : '';

	// Get phone format for digit count validation
	$phoneFormat = 'XXX-XXX-XXXX';
	if($phoneElement) {
		$phoneEleValue = $phoneElement->getVar('ele_value');
		if(isset($phoneEleValue['format']) AND $phoneEleValue['format']) {
			$phoneFormat = $phoneEleValue['format'];
		}
	}
	$numberOfXs = substr_count($phoneFormat, 'X');

	$xhr_entry_to_send = is_numeric($entry_id) ? $entry_id : "'".$entry_id."'";

	$eltmsgUniqueEmail = sprintf(_formulize_REQUIRED_UNIQUE, strtolower(_formulize_USERACCOUNTEMAIL));
	$eltmsgUniquePhone = sprintf(_formulize_REQUIRED_UNIQUE, strtolower(_formulize_USERACCOUNTPHONE));

	$validationCode = array();

	// Get references to both elements in the DOM (they may or may not be present)
	$validationCode[] = "var formulize_emailEl = ".($emailMarkupName ? "myform.elements['{$emailMarkupName}'] || null" : "null").";";
	$validationCode[] = "var formulize_phoneEl = ".($phoneMarkupName ? "myform.elements['{$phoneMarkupName}'] || null" : "null").";";
	$validationCode[] = "var formulize_emailVal = formulize_emailEl ? formulize_emailEl.value.replace(/^\s+|\s+$/g, '') : '';";
	$validationCode[] = "var formulize_phoneVal = formulize_phoneEl ? formulize_phoneEl.value.replace(/^\s+|\s+$/g, '') : '';";

	// If both present values are empty, require at least one (check DOM presence at runtime)
	$validationCode[] = "if(formulize_emailVal == '' && formulize_phoneVal == '') {";
	$validationCode[] = "  if(formulize_emailEl && formulize_phoneEl) {";
	$validationCode[] = "    alert('Please enter either an email address or a phone number.');";
	$validationCode[] = "    formulize_emailEl.focus();";
	$validationCode[] = "  } else if(formulize_emailEl) {";
	$validationCode[] = "    alert('Please enter an email address.');";
	$validationCode[] = "    formulize_emailEl.focus();";
	$validationCode[] = "  } else if(formulize_phoneEl) {";
	$validationCode[] = "    alert('Please enter a phone number.');";
	$validationCode[] = "    formulize_phoneEl.focus();";
	$validationCode[] = "  }";
	$validationCode[] = "  return false;";
	$validationCode[] = "}";

	// Validate email format if provided
	$validationCode[] = "if(formulize_emailVal != '' && /^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,63})+$/.test(formulize_emailVal) == false) {";
	$validationCode[] = "  alert('The email address you have entered is not valid.');";
	$validationCode[] = "  formulize_emailEl.focus();";
	$validationCode[] = "  return false;";
	$validationCode[] = "}";

	// Validate phone digit count if provided
	$validationCode[] = "if(formulize_phoneVal != '' && formulize_phoneVal.replace(/[^0-9]/g,'').length != {$numberOfXs} && formulize_phoneVal.replace(/[^0-9]/g,'').length > 0) {";
	$validationCode[] = "  alert('Please enter a phone number with {$numberOfXs} digits, ie: {$phoneFormat}');";
	$validationCode[] = "  formulize_phoneEl.focus();";
	$validationCode[] = "  return false;";
	$validationCode[] = "}";

	// Email uniqueness check via XHR (only if email element is in the DOM and has a value)
	if($emailElement) {
		$validationCode[] = "if(formulize_emailVal != '') {";
		$validationCode[] = "if(\"{$emailMarkupName}\" in formulize_xhr_returned_check_for_unique_value && formulize_xhr_returned_check_for_unique_value[\"{$emailMarkupName}\"] != 'notreturned') {";
		$validationCode[] = "if(\"{$emailMarkupName}\" in formulize_xhr_returned_check_for_unique_value && formulize_xhr_returned_check_for_unique_value[\"{$emailMarkupName}\"] != 'valuenotfound') {";
		$validationCode[] = "window.alert(\"{$eltmsgUniqueEmail}\");";
		$validationCode[] = "hideSavingGraphic();";
		$validationCode[] = "delete formulize_xhr_returned_check_for_unique_value.{$emailMarkupName};";
		$validationCode[] = "formulize_emailEl.focus();\n return false;";
		$validationCode[] = "}";
		$validationCode[] = "} else {";
		$validationCode[] = "var formulize_xhr_params = [];";
		$validationCode[] = "formulize_xhr_params[0] = formulize_emailVal;";
		$validationCode[] = "formulize_xhr_params[1] = {$emailElementId};";
		$validationCode[] = "formulize_xhr_params[2] = {$xhr_entry_to_send};";
		$validationCode[] = "formulize_xhr_params[4] = leave;";
		$validationCode[] = "formulize_xhr_send('check_for_unique_value', formulize_xhr_params);";
		$validationCode[] = "return false;";
		$validationCode[] = "}";
		$validationCode[] = "}";
	}

	// Phone uniqueness check via XHR (only if phone element is in the DOM and has a value)
	if($phoneElement) {
		$validationCode[] = "if(formulize_phoneVal != '') {";
		$validationCode[] = "if(\"{$phoneMarkupName}\" in formulize_xhr_returned_check_for_unique_value && formulize_xhr_returned_check_for_unique_value[\"{$phoneMarkupName}\"] != 'notreturned') {";
		$validationCode[] = "if(\"{$phoneMarkupName}\" in formulize_xhr_returned_check_for_unique_value && formulize_xhr_returned_check_for_unique_value[\"{$phoneMarkupName}\"] != 'valuenotfound') {";
		$validationCode[] = "window.alert(\"{$eltmsgUniquePhone}\");";
		$validationCode[] = "hideSavingGraphic();";
		$validationCode[] = "delete formulize_xhr_returned_check_for_unique_value.{$phoneMarkupName};";
		$validationCode[] = "formulize_phoneEl.focus();\n return false;";
		$validationCode[] = "}";
		$validationCode[] = "} else {";
		$validationCode[] = "var formulize_xhr_params = [];";
		$validationCode[] = "formulize_xhr_params[0] = formulize_phoneVal.replace(/[^0-9]/g,'');";
		$validationCode[] = "formulize_xhr_params[1] = {$phoneElementId};";
		$validationCode[] = "formulize_xhr_params[2] = {$xhr_entry_to_send};";
		$validationCode[] = "formulize_xhr_params[4] = leave;";
		$validationCode[] = "formulize_xhr_send('check_for_unique_value', formulize_xhr_params);";
		$validationCode[] = "return false;";
		$validationCode[] = "}";
		$validationCode[] = "}";
	}

	return $validationCode;
}
