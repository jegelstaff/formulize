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
    }

    // this method would read back any data from the user after they click save in the admin UI, and save the data to the database, if it were something beyond what is handled in the basic element class
    // this is called as part of saving the options tab.  It receives a copy of the element object immediately prior to it being saved, so the element object will have all its properties set as they would be based on the user's changes in the names & settings tab, and in the options tab (the tabs are saved in order from left to right).
    // the exception is the special ele_value array, which is passed separately from the object (this will contain the values the user set in the Options tab)
    // You can modify the element object in this function and since it is an object, and passed by reference by default, then your changes will be saved when the element is saved.
    // You should return a flag to indicate if any changes were made, so that the page can be reloaded for the user, and they can see the changes you've made here.
    // advancedTab is a flag to indicate if this is being called from the advanced tab (as opposed to the Options tab, normal behaviour). In this case, you have to go off first principals based on what is in $_POST to setup the advanced values inside ele_value (presumably).
		function adminSave($element, $ele_value = array(), $advancedTab = false) {
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
					if($userAccountElementType != 'Uid' AND $accountElement = $element_handler->get('formulize_user_account_'.$userAccountElementType.'_'.$formId)) {
						$elementId = $accountElement->getVar('element_id');
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
				if($userId = $member_handler->insertUser($userObject)) {
					if(!$entryUserId) {
						$profile->setVar('profileid', $userId);
					}
					$profile_handler->insert($profile);
					$results[$cacheKey] = $userId;
				}
			}
		}
		return $results[$cacheKey];
	}

}
