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

    // Set to true by processUserAccountSubmission() when 2FA validation fails so that
    // the element renderer can auto-reopen the dialog on the next page render.
    static public $tfaValidationError = false;
    // POST values stored when 2FA validation fails so loadValue() can restore them.
    static public $submittedValues = null;

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

    // Shared helper for Unix timestamp date columns (last_login, user_regdate).
    // Converts a human-readable date string to a MySQL-comparable datetime string at the
    // right granularity so that FROM_UNIXTIME(col) LIKE/comparison works naturally:
    //   "2026"        → "2026"        (LIKE '%2026%' matches any date in 2026)
    //   "Feb 2026"    → "2026-02"     (LIKE '%2026-02%' matches all of February 2026)
    //   "Feb 1 2026"  → "2026-02-01"  (comparison operators work against start of that day)
    // For comparison operators ($partialMatch = false) always returns at least Y-m-d so
    // MySQL can cast it to a DATETIME correctly.
    static function prepareDateTimestampForDB($value, $partialMatch) {
        $value = trim($value);
        $ts = strtotime($value);
        if ($ts === false) {
            return $value;
        }
        if ($partialMatch) {
            if (preg_match('/^\d{4}$/', $value)) {
                return date('Y', $ts);
            }
            if (preg_match('/^[a-zA-Z]+\s+\d{4}$|^\d{1,2}\/\d{4}$|^\d{4}-\d{2}$/', $value)) {
                return date('Y-m', $ts);
            }
        }
        return date('Y-m-d', $ts);
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
			// If 2FA validation failed on the previous submit, restore the submitted values
			// so the user does not have to retype their changes after entering a new code.
			if(self::$tfaValidationError && self::$submittedValues !== null && $element->userProperty != 'pass') {
				$fid = $element->getVar('fid');
				$eleId = $element->getVar('ele_id');
				$postKey = 'de_' . $fid . '_' . $entry_id . '_' . $eleId;
				if(isset(self::$submittedValues[$postKey])) {
					return self::$submittedValues[$postKey];
				}
			}
			$member_handler = xoops_gethandler('member');
			$form_handler = xoops_getmodulehandler('forms', 'formulize');
			$formObject = $form_handler->get($element->getVar('fid'));
			$userId = $formObject ? $formObject->getSystemUserIdFromEntry($entry_id) : 0;
			if($element->userProperty AND $user = $member_handler->getUser($userId)) {
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

	static function getTypeRegistry() {
		return array(
			'uid'                => array('eleType' => 'userAccountUid',                'column' => 'uid',          'profileColumn' => null),
			'fullname'           => array('eleType' => 'userAccountFullName',           'column' => 'uname',        'profileColumn' => null),
			'firstname'          => array('eleType' => 'userAccountFirstName',          'column' => 'uname',        'profileColumn' => null),
			'lastname'           => array('eleType' => 'userAccountLastName',           'column' => 'uname',        'profileColumn' => null),
			'username'           => array('eleType' => 'userAccountUsername',           'column' => 'login_name',   'profileColumn' => null),
			'email'              => array('eleType' => 'userAccountEmail',              'column' => 'email',        'profileColumn' => null),
			'status'             => array('eleType' => 'userAccountStatus',             'column' => 'level',        'profileColumn' => null),
			'notificationmethod' => array('eleType' => 'userAccountNotificationMethod', 'column' => 'notify_method','profileColumn' => null),
			'lastlogin'          => array('eleType' => 'userAccountLastLogin',          'column' => 'last_login',   'profileColumn' => null),
			'registrationdate'   => array('eleType' => 'userAccountRegistrationDate',   'column' => 'user_regdate', 'profileColumn' => null),
			'phone'              => array('eleType' => 'userAccountPhone',              'column' => null,           'profileColumn' => '2faphone'),
			'timezone'           => array('eleType' => 'userAccountTimezone',           'column' => null,           'profileColumn' => 'timezone'),
			'2fa'                => array('eleType' => 'userAccount2FA',                'column' => null,           'profileColumn' => '2famethod'),
			'groupmembership'    => array('eleType' => 'userAccountGroupMembership',    'column' => null,           'profileColumn' => null),
			'masquerade'         => array('eleType' => 'userAccountMasquerade',         'column' => null,           'profileColumn' => null),
			'password'           => array('eleType' => 'userAccountPassword',           'column' => null,           'profileColumn' => null),
		);
	}

	// utility function to render radio buttons for this user account element types
	function renderUserAccountRadioButtons($options, $ele_value, $caption, $markupName, $isDisabled, $disabledOptions = array()) {
		$disabled = ($isDisabled) ? 'disabled="disabled"' : '';
		$form_ele = new XoopsFormElementTray('', '<br>');
		foreach($options as $oKey=>$oValue) {
			$t = new XoopsFormRadio(
				'',
				$markupName,
				$ele_value
			);
			$t->addOption($oKey, $oValue);
			$thisDisabled = $disabled ?: (in_array($oKey, $disabledOptions) ? 'disabled="disabled"' : '');
			$t->setExtra("onchange=\"javascript:formulizechanged=1;\" $thisDisabled");
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

		static $results = array();
		$cacheKey = $formId.'-'.$entryId;
		if(!isset($results[$cacheKey])) {

			$results[$cacheKey] = false;

			// Detect the system users table form.
			// For this form type, entry_id IS the uid; EAU checks do not apply.
			$form_handler = xoops_getmodulehandler('forms', 'formulize');
			$formObject = $form_handler->get($formId);
			$isUserTableForm = $formObject && $formObject->isSystemUsersTableForm();

			if(!self::canProcessUserAccountSubmission($formId, $entryId, $isUserTableForm)) {
				return $results[$cacheKey];
			}

			if($formObject && ($formObject->getVar('entries_are_users') || $isUserTableForm)) {
				// Load or create user context
				$context = self::loadOrCreateUserContext($formObject, $entryId);
				if($context === null) {
					return $results[$cacheKey]; // user was deleted
				}
				$userObject = $context['userObject'];
				$profile = $context['profile'];
				$entryUserId = $context['entryUserId'];
				$old2faMethod = $context['old2faMethod'];
				$old2faPhone = $context['old2faPhone'];
				$oldEmail = $context['oldEmail'];
				
				// Collect all pending changes from form submission
				$changes = self::collectPendingUserVars($formId, $entryId, $userObject, $profile, $entryUserId, $old2faMethod);
				$pendingUserVars = $changes['pendingUserVars'];
				$pendingProfileVars = $changes['pendingProfileVars'];
				$rawSubmitted2faMethod = $changes['rawSubmitted2faMethod'];
				$passwordChanged = $changes['passwordChanged'];
				$cleanupAppSecret = $changes['cleanupAppSecret'];
				
				// Validate 2FA transition if user is editing their own account
				if(!self::validateOwnAccount2faTransition(
					$entryUserId, $userObject, $profile, $pendingUserVars, $pendingProfileVars,
					$rawSubmitted2faMethod, $passwordChanged, $old2faMethod, $old2faPhone, $oldEmail, $cleanupAppSecret
				)) {
					return self::setTfaValidationError($results[$cacheKey]);
				}
				
				// Apply all pending changes now that validation has passed
				foreach($pendingProfileVars as $k => $v) { $profile->setVar($k, $v); }
				foreach($pendingUserVars as $k => $v) { $userObject->setVar($k, $v); }
				
				// Persist to database
				$userId = self::persistUserAndProfile($userObject, $profile, $entryUserId);
				if($userId) {
					$results[$cacheKey] = $userId;
				}
			}
		}
		return $results[$cacheKey];
	}

	private static function canProcessUserAccountSubmission($formId, $entryId, $isUserTableForm) {
		if(!security_check($formId, $entryId)) {
			throw new Exception("You do not have permission to access this entry");
		}
		global $xoopsUser;
		if ($isUserTableForm) {
			$gperm_handler = xoops_gethandler('groupperm');
			$groups = $xoopsUser ? $xoopsUser->getGroups() : array(XOOPS_GROUP_ANONYMOUS);
			if (!$gperm_handler->checkRight('system_admin', XOOPS_SYSTEM_USER, $groups)) {
				throw new Exception("You do not have permission to manage system users.");
			}
			return true;
		}
		$activeUserId = $xoopsUser ? intval($xoopsUser->getVar('uid')) : 0;
		if(!formulizePermHandler::user_can_edit_entry($formId, $activeUserId, $entryId)) {
			throw new Exception("You do not have permission to edit this entry");
		}
		return formulizeHandler::entriesAreUsersEntryMeetsBaseConditions($formId, $entryId, cacheId: 'preWrite');
	}

	private static function setTfaValidationError($returnValue) {
		self::$tfaValidationError = true;
		return $returnValue;
	}

	/**
	 * Load or create user context (user and profile objects) for the given entry
	 * @return array Array with keys: userObject, profile, entryUserId, old2faMethod, old2faPhone, oldEmail
	 */
	private static function loadOrCreateUserContext($formObject, $entryId) {
		$member_handler = xoops_gethandler('member');
		$profile_handler = xoops_getmodulehandler('profile', 'profile');
		$entryUserId = $formObject->getSystemUserIdFromEntry($entryId);
		
		if($entryUserId) {
			$userObject = $member_handler->getUser($entryUserId);
			if (!$userObject) {
				return null; // user was deleted before this submission was processed
			}
			$profile = $profile_handler->get($userObject->getVar('uid'));
			$old2faMethod = intval($profile->getVar('2famethod'));
			$old2faPhone = preg_replace('/[^0-9]/', '', $profile->getVar('2faphone') ?? '');
			$oldEmail = $userObject->getVar('email');
		} else {
			global $xoopsConfig;
			$userObject = $member_handler->createUser();
			$profile = $profile_handler->create();
			$userObject->setVar('user_avatar', 'blank.gif');
			$userObject->setVar('theme', $xoopsConfig['theme_set']);
			$userObject->setVar('level', 1);
			$old2faMethod = 0;
			$old2faPhone = '';
			$oldEmail = '';
		}
		
		return array(
			'userObject' => $userObject,
			'profile' => $profile,
			'entryUserId' => $entryUserId,
			'old2faMethod' => $old2faMethod,
			'old2faPhone' => $old2faPhone,
			'oldEmail' => $oldEmail
		);
	}

	/**
	 * Collect pending user and profile variable changes from POST data
	 * @return array Array with keys: pendingUserVars, pendingProfileVars, rawSubmitted2faMethod, passwordChanged, cleanupAppSecret
	 */
	private static function collectPendingUserVars($formId, $entryId, $userObject, $profile, $entryUserId, $old2faMethod) {
		$form_handler = xoops_getmodulehandler('forms', 'formulize');
		$element_handler = xoops_getmodulehandler('elements', 'formulize');
		
		$pendingProfileVars = array();
		$pendingUserVars = array();
		$rawSubmitted2faMethod = null;
		$passwordChanged = false;
		$cleanupAppSecret = false;
		$unameParts = array();
		
		foreach($form_handler->getUserAccountElementTypes() as $userAccountElementType) {
			$accountElement = $element_handler->get('formulize_user_account_'.strtolower(str_replace("userAccount", "", $userAccountElementType)).'_'.$formId);
			if(!$accountElement) {
				continue;
			}
			
			$elementId = $accountElement->getVar('ele_id');
			$userProperty = $accountElement->userProperty;
			
			if($accountElement->readOnly) {
				continue; // system-managed property
			}
			if(!isset($_POST['decue_'.$formId.'_'.$entryId.'_'.$elementId])) {
				continue; // element not on this form/page
			}
			
			$value = isset($_POST['de_'.$formId.'_'.$entryId.'_'.$elementId]) ? $_POST['de_'.$formId.'_'.$entryId.'_'.$elementId] : '';
			
			// Handle password encryption
			if($userProperty == 'pass') {
				if($value === '') {
					continue; // don't change password if no value entered
				}
				$passwordChanged = true;
				global $icmsConfigUser;
				$icmspass = new icms_core_Password();
				$salt = $icmspass->createSalt();
				$enc_type = $icmsConfigUser['enc_type'];
				$value = $icmspass->encryptPass($value, $salt, $enc_type);
				$pendingUserVars['salt'] = $salt;
				$pendingUserVars['enc_type'] = $enc_type;
			}
			
			// Handle profile properties
			if(substr($userProperty, 0, 8) == 'profile:') {
				$property = substr($userProperty, 8);
				if($property == '2faphone') {
					$value = preg_replace('/[^0-9]/', '', $value);
				}
				if($property == '2famethod') {
					$rawSubmitted2faMethod = intval($value);
					if($entryUserId) {
						$value = self::enforce2faPolicyRules($userObject, $value, $formId, $entryId);
						if($old2faMethod == TFA_APP AND $value != TFA_APP) {
							$cleanupAppSecret = true;
						}
					}
				}
				if($property == 'timezone') {
					$pendingUserVars['timezone_offset'] = formulize_getStandardTimezoneOffset($value);
				}
				$pendingProfileVars[$property] = $value;
			} else {
				// Handle standard user properties
				if($userProperty == 'uname') {
					$unameParts[] = $value;
					$value = implode(' ', $unameParts);
				}
				if($userProperty == 'level' && intval($value) != 1) {
					global $xoopsUser;
					if($entryUserId && $xoopsUser && intval($entryUserId) == intval($xoopsUser->getVar('uid'))) {
						continue; // safety net: active user cannot disable their own account
					}
				}
				$pendingUserVars[$userProperty] = $value;
			}
		}
		
		return array(
			'pendingUserVars' => $pendingUserVars,
			'pendingProfileVars' => $pendingProfileVars,
			'rawSubmitted2faMethod' => $rawSubmitted2faMethod,
			'passwordChanged' => $passwordChanged,
			'cleanupAppSecret' => $cleanupAppSecret
		);
	}

	/**
	 * Enforce 2FA policy rules (group-based requirements and SMS-without-phone)
	 * @return int The adjusted 2FA method value
	 */
	private static function enforce2faPolicyRules($userObject, $value, $formId, $entryId) {
		$element_handler = xoops_getmodulehandler('elements', 'formulize');
		$edituserGroups = $userObject->getGroups();
		$criteria_2fagroups = new Criteria('conf_name', 'auth_2fa_groups');
		$auth_2fa_groups_cfg = icms::handler('icms_config')->getConfigs($criteria_2fagroups);
		$auth_2fa_groups = ($auth_2fa_groups_cfg) ? $auth_2fa_groups_cfg[0]->getConfValueForOutput() : array();
		
		// Get submitted phone to check SMS-without-phone case
		$phoneEleForCheck = $element_handler->get('formulize_user_account_phone_'.$formId);
		$submittedPhoneForCheck = '';
		if($phoneEleForCheck) {
			$phoneEleIdForCheck = $phoneEleForCheck->getVar('ele_id');
			$submittedPhoneForCheck = preg_replace('/[^0-9]/', '', isset($_POST['de_'.$formId.'_'.$entryId.'_'.$phoneEleIdForCheck]) ? $_POST['de_'.$formId.'_'.$entryId.'_'.$phoneEleIdForCheck] : '');
		}
		
		if(($value == TFA_OFF && array_intersect($edituserGroups, (array)$auth_2fa_groups))
		   || ($value == TFA_SMS && !$submittedPhoneForCheck)) {
			return TFA_EMAIL;
		}
		
		return $value;
	}

	/**
	 * Validate 2FA code when user changes their own sensitive account settings
	 * @return bool True if validation passed or not required, false if validation failed
	 */
	private static function validateOwnAccount2faTransition($entryUserId, $userObject, $profile, $pendingUserVars, $pendingProfileVars, $rawSubmitted2faMethod, $passwordChanged, $old2faMethod, $old2faPhone, $oldEmail, $cleanupAppSecret) {
		global $xoopsUser;
		if(!$entryUserId || !$xoopsUser || intval($entryUserId) != intval($xoopsUser->getVar('uid'))) {
			return true; // Not editing own account
		}
		
		$criteria_2fa_sv = new Criteria('conf_name', 'auth_2fa');
		$is2faOn = false;
		if($auth_2fa_sv = icms::handler('icms_config')->getConfigs($criteria_2fa_sv)) {
			$is2faOn = $auth_2fa_sv[0]->getConfValueForOutput();
		}
		if(!$is2faOn) {
			return true; // 2FA not enabled
		}
		
		include_once XOOPS_ROOT_PATH . '/include/2fa/manage.php';
		self::$submittedValues = $_POST;
		$newEmail  = isset($pendingUserVars['email']) ? $pendingUserVars['email'] : $userObject->getVar('email');
		$newPhone  = isset($pendingProfileVars['2faphone']) ? $pendingProfileVars['2faphone'] : $profile->getVar('2faphone');
		$effectiveNewMethod = $rawSubmitted2faMethod !== null ? $rawSubmitted2faMethod : $old2faMethod;
		
		$needsValidation = (
			($rawSubmitted2faMethod != $old2faMethod) ||
			(($old2faMethod == TFA_SMS || $effectiveNewMethod == TFA_SMS) && $newPhone != $old2faPhone) ||
			(($old2faMethod == TFA_EMAIL || $old2faMethod == TFA_OFF || $effectiveNewMethod == TFA_EMAIL || $effectiveNewMethod == TFA_OFF) && $oldEmail && $newEmail != $oldEmail) ||
			$passwordChanged
		);
		
		if(!$needsValidation) {
			return true;
		}
		
		// Two-phase required in three cases:
		// 1. Method unchanged = email, email is changing.
		// 2. Method unchanged = SMS, phone is changing.
		// 3. Method was active and is changing to a different active method.
		$contactChanging = (
			($rawSubmitted2faMethod == $old2faMethod && $old2faMethod == TFA_EMAIL && $oldEmail && $newEmail != $oldEmail) ||
			($rawSubmitted2faMethod == $old2faMethod && $old2faMethod == TFA_SMS && $old2faPhone && $newPhone != $old2faPhone) ||
			($rawSubmitted2faMethod !== null && $old2faMethod != TFA_OFF && $rawSubmitted2faMethod != TFA_OFF && $rawSubmitted2faMethod != $old2faMethod)
		);
		
		if($contactChanging) {
			$step1Token = isset($_POST['formulize_tfa_step1token']) ? trim($_POST['formulize_tfa_step1token']) : '';
			if($rawSubmitted2faMethod == TFA_APP) {
				$newContactId = 'authenticator-app';
			} elseif($rawSubmitted2faMethod == TFA_SMS) {
				$newContactId = $newPhone;
			} else {
				$newContactId = $newEmail;
			}
			if(!icms::$security->validateToken($step1Token, true, $newContactId)) {
				return false;
			}
		} else {
			// Single-phase: validate confirm token
			if($rawSubmitted2faMethod == TFA_APP ||
			   ($rawSubmitted2faMethod == TFA_OFF && $old2faMethod == TFA_APP)) {
				$storedContactId = 'authenticator-app';
			} elseif($rawSubmitted2faMethod == TFA_SMS) {
				$storedContactId = $newPhone;
			} elseif($rawSubmitted2faMethod == TFA_OFF && $old2faMethod == TFA_SMS) {
				$storedContactId = $old2faPhone;
			} else {
				$storedContactId = $oldEmail;
			}
			$confirmToken = isset($_POST['tfa_confirm_token']) ? trim($_POST['tfa_confirm_token']) : '';
			if(!icms::$security->validateToken($confirmToken, true, $storedContactId)) {
				return false;
			}
		}
		
		$submittedCode = isset($_POST['formulize_tfa_code']) ? trim($_POST['formulize_tfa_code']) : '';
		if(!validateCode($submittedCode, intval($entryUserId))) {
			return false;
		}
		
		// Validation passed -- now safe to delete the app secret if switching away from app
		if($cleanupAppSecret) {
			global $xoopsDB;
			$sql = 'DELETE FROM '.$xoopsDB->prefix('tfa_codes').' WHERE uid = '.intval($userObject->getVar('uid')).' AND method = '.TFA_APP;
			$xoopsDB->queryF($sql);
		}
		
		return true;
	}

	/**
	 * Persist user and profile objects to database
	 * @return int|false The user ID on success, false on failure
	 */
	private static function persistUserAndProfile($userObject, $profile, $entryUserId) {
		// login name cannot be empty, set to email if available, or timestamp to attempt to guarantee uniqueness
		if($userObject->getVar('login_name') == '') {
			$altLoginName = $userObject->getVar('email');
			if(!$altLoginName) {
				$altLoginName = microtime(true);
			}
			$userObject->setVar('login_name', $altLoginName);
		}
		
		$member_handler = xoops_gethandler('member');
		$profile_handler = xoops_getmodulehandler('profile', 'profile');
		
		if($member_handler->insertUser($userObject)) {
			$userId = $userObject->getVar('uid');
			if(!$entryUserId) {
				$profile->setVar('profileid', $userId);
			}
			$profile_handler->insert($profile);
			return $userId;
		}
		
		return false;
	}

	/**
	 * Validate that a column name is from the users or profile table
	 * @param string $column The column name to validate
	 * @param bool $isProfile Whether this is a profile column (default false)
	 * @return bool True if valid, false otherwise
	 */
	protected static function isValidColumnName($column, $isProfile = false) {
		$registry = self::getTypeRegistry();
		foreach($registry as $entry) {
			if($isProfile) {
				if($entry['profileColumn'] === $column) {
					return true;
				}
			} else {
				if($entry['column'] === $column) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Build an EXISTS subquery for profile table columns
	 * Used by user account elements that store data in the profile table
	 * @param string $column The profile table column name
	 * @param string $term The search term
	 * @param string $operator The SQL operator (=, !=, LIKE, etc)
	 * @param string $quotes The quote character for the value
	 * @param string $likebits The LIKE wildcards (%, empty, etc)
	 * @param string $tableAlias The alias for the main table
	 * @return string The WHERE clause fragment
	 */
	protected function buildProfileExistsClause($column, $term, $operator, $quotes, $likebits, $tableAlias='main') {
		global $xoopsDB;
		// Validate column name against whitelist
		if(!self::isValidColumnName($column, true)) {
			trigger_error("Invalid profile column name: $column", E_USER_WARNING);
			return "1=0"; // Return a clause that will never match
		}
		
		// Validate operator against whitelist
		$validOperators = array('=', '!=', '<', '>', '<=', '>=', 'LIKE', 'NOT LIKE');
		if(!in_array($operator, $validOperators, true)) {
			trigger_error("Invalid operator: $operator", E_USER_WARNING);
			return "1=0";
		}
		
		$safeTermClause = $operator . $quotes . $likebits . formulize_db_escape($term) . $likebits . $quotes;
		return "EXISTS("
			. "SELECT 1 FROM " . $xoopsDB->prefix('profile_profile') . " AS pp"
			. " WHERE pp.profileid = {$tableAlias}.uid"
			. " AND pp.`{$column}`" . $safeTermClause
			. ")";
	}

	/**
	 * Build a search WHERE clause for Unix timestamp columns
	 * Handles date strings by converting them to MySQL datetime format for comparison
	 * @param string $column The user table column name containing Unix timestamp
	 * @param string $term The search term (date string or number)
	 * @param string $operator The SQL operator
	 * @param bool $partialMatch Whether to allow partial date matches
	 * @param string $tableAlias The alias for the main table
	 * @return string The WHERE clause fragment
	 */
	protected function buildUnixTimestampClause($column, $term, $operator, $partialMatch, $tableAlias='main') {
		// Validate column name against whitelist
		if(!self::isValidColumnName($column, false)) {
			trigger_error("Invalid user table column name: $column", E_USER_WARNING);
			return "1=0"; // Return a clause that will never match
		}
		
		// Validate operator against whitelist
		$validOperators = array('=', '!=', '<', '>', '<=', '>=');
		if(!in_array($operator, $validOperators, true)) {
			trigger_error("Invalid operator: $operator", E_USER_WARNING);
			return "1=0";
		}
		
		$dbTerm = self::prepareDateTimestampForDB($term, $partialMatch);
		$safeTerm = formulize_db_escape($dbTerm);
		if($partialMatch) {
			return "FROM_UNIXTIME({$tableAlias}.`{$column}`) LIKE '%$safeTerm%'";
		} else {
			return "FROM_UNIXTIME({$tableAlias}.`{$column}`) $operator '$safeTerm'";
		}
	}

	/**
	 * Render a simple text input for user account elements
	 * Reduces boilerplate in child classes
	 * @param mixed $ele_value The current value
	 * @param string $caption The field caption
	 * @param string $markupName The HTML input name
	 * @param bool $isDisabled Whether the field is read-only
	 * @param int $size Optional input width (defaults to config or 30)
	 * @param int $maxlength Optional max length (defaults to config or 255)
	 * @return XoopsFormElement The rendered form element
	 */
	protected function renderSimpleTextInput($ele_value, $caption, $markupName, $isDisabled, $size=null, $maxlength=null) {
		if(is_array($ele_value)) {
			$ele_value = "";
		}
		if($isDisabled) {
			return new XoopsFormLabel($caption, $ele_value);
		}
		$config_handler = xoops_gethandler('config');
		$formulizeConfig = $config_handler->getConfigsByCat(0, getFormulizeModId());
		$form_ele = new XoopsFormText(
			$caption,
			$markupName,
			$size !== null ? $size : (isset($formulizeConfig['t_width']) ? $formulizeConfig['t_width'] : 30),
			$maxlength !== null ? $maxlength : (isset($formulizeConfig['t_max']) ? $formulizeConfig['t_max'] : 255),
			$ele_value,
			false,		// autocomplete
			'text'		// type
		);
		$form_ele->setExtra(" onchange=\"javascript:formulizechanged=1;\"");
		return $form_ele;
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
