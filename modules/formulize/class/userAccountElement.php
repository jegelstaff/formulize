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
require_once XOOPS_ROOT_PATH . "/include/2fa/constants.php"; // defines TFA_OFF, TFA_EMAIL, TFA_SMS, TFA_APP used by the 2FA policy logic below

class formulizeUserAccountElement extends formulizeElement {

	// Maps this element to its backing store in the XOOPS user account system.
	// Drives loadValue(), processUserAccountSubmission(), and getTypeRegistry().
	//
	// Values:
	//   null or ''       → no database column (e.g. group membership, masquerade)
	//   'profile:{col}'  → column {col} in the profile_profile table (e.g. 'profile:2faphone')
	//   'pass'           → write-only password field; never read back or used in SQL queries
	//   anything else    → column name in the users table (e.g. 'uname', 'email', 'level')
	var $userProperty = null;

	// When true, this account field is for webmasters (XOOPS_GROUP_ADMIN) only: it is created
	// with webmaster-only ele_display (so non-webmasters never see it), and its submitted value
	// is ignored server-side for non-webmasters (see collectPendingUserVars). This is the single
	// source of truth that replaced the hardcoded "webmasters only" type lists in forms.php.
	var $adminOnly = false;

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
    // Set to the human-readable name of the conflicting field (e.g. "email address") by
    // processUserAccountSubmission() when a submitted account value collides with an existing
    // account. Callers (e.g. signup.php) can display it; the save itself is refused regardless.
    static public $uniquenessError = '';

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

    /**
     * Convert a human-readable date string to a MySQL-comparable datetime string.
     *
     * Adapts granularity to the search context so that FROM_UNIXTIME(col) comparisons
     * work naturally:
     *   "2026"        → "2026"        (LIKE '%2026%' matches any date in 2026)
     *   "Feb 2026"    → "2026-02"     (LIKE '%2026-02%' matches all of February 2026)
     *   "Feb 1 2026"  → "2026-02-01"  (comparison operators work against start of that day)
     * For comparison operators ($partialMatch = false) always returns at least Y-m-d so
     * MySQL can cast it to a DATETIME correctly.
     *
     * @param string $value        Human-readable date string
     * @param bool   $partialMatch True for LIKE searches, false for range operators
     * @return string MySQL-formatted date string
     */
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

    /**
     * Gather data to pass to the admin UI template for this element type.
     *
     * Returns an array with the default-value-source dropdown options and a human-readable
     * label derived from the element name. $element may be false when creating a new element.
     *
     * @param object|false $element The element object, or false for new elements
     * @return array Template data array
     */
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

    /**
     * Save admin UI option-tab data back to the element object.
     *
     * Called after the user clicks Save on the Options tab. The element object already
     * has its name/settings-tab properties set; $ele_value contains the Options tab values.
     *
     * @param object $element     The element object (modified in place)
     * @param array  $ele_value   Values from the Options tab
     * @param bool   $advancedTab True when called from the Advanced tab instead of Options
     * @return void
     */
		function adminSave($element, $ele_value = array(), $advancedTab = false) {
			$element->setVar('ele_value', $ele_value);
    }

    /**
     * Load the current value for this element from the XOOPS user or profile record.
     *
     * Reads from the users table, profile_profile table, or the configured default-value
     * source element, depending on the element's userProperty. If a 2FA validation error
     * occurred on the previous submit, restores the submitted value so the user does not
     * have to retype their changes.
     *
     * @param object    $element  The element object
     * @param mixed     $value    Ignored; value is read directly from the user/profile record
     * @param int|mixed $entry_id The entry ID being loaded
     * @return mixed The current field value, or null if not found
     */
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

    /**
     * Return JavaScript validation code for this element type (base: none).
     *
     * Subclasses override this to emit JS that validates user input before submit.
     * 'myform' refers by convention to the form element in the DOM.
     *
     * @param string    $caption    Field caption
     * @param string    $markupName HTML input name
     * @param object    $element    The element object
     * @param int|mixed $entry_id   The current entry ID
     * @return void
     */
    function generateValidationCode($caption, $markupName, $element, $entry_id) {
    }

    /**
     * Return a SQL expression for sorting by this element's value, or null to use the default
     * column reference. Subclasses override this when the sort expression must differ from a
     * plain column reference (e.g. extracting the last name from a combined uname field).
     *
     * @param string $tableAlias SQL alias for the row in the users/profile table
     * @return string|null SQL expression, or null to fall back to the default column sort
     */
    public function buildSortExpression($tableAlias) {
        return null;
    }

    /**
     * Prepare a submitted value for insertion into the form's data table (base: pass-through).
     *
     * Return null to save a SQL NULL. $entry_id may be "new" for new entries or null
     * for subform-blank entries.
     *
     * @param mixed     $value              The submitted value
     * @param object    $element            The element object
     * @param int|mixed $entry_id           Entry ID, "new", or null
     * @param int|null  $subformBlankCounter Instance counter for multiple blank subform saves
     * @return mixed The value to write to the database
     */
    function prepareDataForSaving($value, $element, $entry_id=null, $subformBlankCounter=null) {
      return $value;
    }

    /**
     * Perform any final actions required after an entry has been saved (base: no-op).
     *
     * Typically needed when post-save actions depend on the newly assigned entry ID.
     * To trigger this method, set $GLOBALS['formulize_afterSavingLogicRequired'][$elementId]
     * in prepareDataForSaving().
     *
     * @param mixed $value      The value that was just saved
     * @param int   $element_id The element ID whose data was just saved
     * @param int   $entry_id   The entry ID that was just saved
     * @return void
     */
    function afterSavingLogic($value, $element_id, $entry_id) {
    }

    /**
     * Prepare a raw database value for inclusion in a dataset (base: pass-through).
     *
     * Called when Formulize builds a list of entries or the getData API is invoked.
     *
     * @param mixed  $value    Raw value from the database
     * @param string $handle   Element handle
     * @param int    $entry_id Entry ID
     * @return mixed The value as it should appear in the dataset
     */
    function prepareDataForDataset($value, $handle, $entry_id) {
      return $value; // we're not making any modifications for this element type
    }

	/**
	 * Convert a user-supplied text value to a form suitable for database comparison (base: pass-through).
	 *
	 * Only needed when stored values differ from what users type (e.g. "Yes" → 1). When
	 * $partialMatch is true an array may be returned (multiple matching values possible).
	 * Linked elements and UI-text are resolved before this method is called.
	 *
	 * @param mixed  $value        The user-supplied text value
	 * @param object $element      The element object
	 * @param bool   $partialMatch True for LIKE/contains searches, false for exact/range
	 * @return mixed Value ready for database comparison
	 */
	function prepareLiteralTextForDB($value, $element, $partialMatch=false) {
    return $value;
  }

	/**
	 * Format a dataset value for display in a list of entries.
	 *
	 * Sets clickable/striphtml/length properties that are enforced by the parent class.
	 *
	 * @param mixed  $value     The value from the dataset
	 * @param string $handle    Element handle
	 * @param int    $entry_id  Entry ID
	 * @param int    $textWidth Column display width hint
	 * @return string Formatted display value
	 */
	function formatDataForList($value, $handle="", $entry_id=0, $textWidth=100) {
		$this->clickable = false; // make urls clickable
		$this->striphtml = true; // remove html tags as a security precaution
		$this->length = 255; // truncate to a maximum of 100 characters, and append ... on the end

		return parent::formatDataForList($value); // always return the result of formatDataForList through the parent class (where the properties you set here are enforced)
	}

	/**
	 * Build the user account element type registry by reflecting over class files.
	 *
	 * Scans every userAccount*Element.php class file; each element's $userProperty drives
	 * the column/profileColumn mapping, so adding a new element type automatically
	 * appears here with no manual update.
	 *
	 * Registry structure: key = lowercase type suffix (e.g. 'email', '2fa', 'fullname')
	 *   'eleType'       => element type string (e.g. 'userAccountEmail')
	 *   'column'        => users table column name, or null
	 *   'profileColumn' => profile_profile table column name, or null
	 *
	 * @return array The type registry, cached after the first call
	 */
	static function getTypeRegistry() {
		static $registry = null;
		if ($registry !== null) {
			return $registry;
		}

		$registry   = array();
		$classFiles = glob(XOOPS_ROOT_PATH . '/modules/formulize/class/userAccount*Element.php');

		foreach ($classFiles as $classFile) {
			$basename   = basename($classFile, '.php');           // e.g. 'userAccountEmailElement'
			$typeWithUA = str_replace('Element', '', $basename); // e.g. 'userAccountEmail'
			$typeSuffix = str_replace('userAccount', '', $typeWithUA); // e.g. 'Email'

			// Skip the base class file (userAccountElement.php → empty suffix)
			if ($typeSuffix === '') {
				continue;
			}

			require_once $classFile;

			// Class names are PascalCase-prefixed: formulizeUserAccountEmailElement
			$elementClass = 'formulize' . ucfirst($typeWithUA) . 'Element';
			if (!class_exists($elementClass)) {
				continue;
			}

			$element     = new $elementClass();
			$userProp    = $element->userProperty;
			$registryKey = strtolower($typeSuffix); // e.g. 'email', '2fa', 'groupmembership'
			$eleType     = $typeWithUA;              // e.g. 'userAccountEmail', 'userAccount2FA'

			// Derive column/profileColumn from $userProperty (see comment on that property above)
			if (!empty($userProp) && strpos($userProp, 'profile:') === 0) {
				$column        = null;
				$profileColumn = substr($userProp, 8);
			} elseif (!empty($userProp) && $userProp !== 'pass') {
				$column        = $userProp;
				$profileColumn = null;
			} else {
				$column        = null;
				$profileColumn = null;
			}

			$registry[$registryKey] = array(
				'eleType'       => $eleType,
				'column'        => $column,
				'profileColumn' => $profileColumn,
			);
		}

		return $registry;
	}

	/**
	 * Render a group of radio buttons for a user account element.
	 *
	 * @param array  $options         Associative array of value => label
	 * @param mixed  $ele_value       Currently selected value
	 * @param string $caption         Field caption
	 * @param string $markupName      HTML input name
	 * @param bool   $isDisabled      Whether all radio buttons are disabled
	 * @param array  $disabledOptions Values that should be individually disabled
	 * @return XoopsFormElement
	 */
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

				// Enforce account-field uniqueness on the server. The form's JS/XHR checks the same
				// thing, but a submission that bypasses the browser (a forged/scripted POST) must not
				// be able to create a duplicate email/username/phone. login_name is additionally
				// protected by a DB UNIQUE key, but email and phone are not, so this is their only
				// backstop. Refuse the save (fail closed) if a conflict is found.
				if($conflictField = self::accountFieldConflict($pendingUserVars, $pendingProfileVars, $entryUserId)) {
					self::$uniquenessError = $conflictField;
					return $results[$cacheKey];
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

		// Never let a non-webmaster modify a webmaster's account. Editing a user's account entry
		// lets you change their password/email — i.e. take the account over — and would also enable
		// masquerading "up" to an admin. Both gates below (EAU user_can_edit_entry, and system_admin
		// on the users table) can be held by a non-webmaster, so this is the backstop that keeps
		// those rights from reaching an admin's account. New entries have no target user yet
		// (getSystemUserIdFromEntry / intval of a non-numeric entryId is 0), so account creation and
		// self-registration are unaffected; a webmaster editing any account is unaffected.
		if ($isUserTableForm) {
			$targetUidForWebmasterCheck = intval($entryId);
		} else {
			$guardFormHandler = xoops_getmodulehandler('forms', 'formulize');
			$guardFormObject = $guardFormHandler->get($formId);
			$targetUidForWebmasterCheck = $guardFormObject ? intval($guardFormObject->getSystemUserIdFromEntry($entryId)) : 0;
		}
		if ($targetUidForWebmasterCheck) {
			$activeIsWebmaster = $xoopsUser && in_array(XOOPS_GROUP_ADMIN, $xoopsUser->getGroups());
			if (!$activeIsWebmaster) {
				$guardMemberHandler = xoops_gethandler('member');
				$targetUserObj = $guardMemberHandler->getUser($targetUidForWebmasterCheck);
				if ($targetUserObj && in_array(XOOPS_GROUP_ADMIN, $targetUserObj->getGroups())) {
					throw new Exception("You do not have permission to modify a webmaster account.");
				}
			}
		}

		if ($isUserTableForm) {
			if (self::activeUserCanManageUsers()) {
				return true;
			}
			// Self-registration: during the public signup flow (signup.php) an anonymous visitor may
			// create their own brand-new account. Gated by formulize_selfRegistrationActive() and
			// limited to a new entry; the resulting user is forced inactive (level 0) until they
			// confirm ownership of their email/phone. See loadOrCreateUserContext().
			if (formulize_selfRegistrationActive() && !is_numeric($entryId)) {
				return true;
			}
			// Self-service: a logged-in user may edit their own account. For the system users
			// form, entry_id IS the uid, so a tampered entry_id pointing at another user fails
			// this equality check and falls through to the exception below.
			$activeUserId = $xoopsUser ? intval($xoopsUser->getVar('uid')) : 0;
			if ($activeUserId && $activeUserId === intval($entryId)) {
				return true;
			}
			throw new Exception("You do not have permission to manage system users.");
		}
		$activeUserId = $xoopsUser ? intval($xoopsUser->getVar('uid')) : 0;
		if(!formulizePermHandler::user_can_edit_entry($formId, $activeUserId, $entryId)) {
			throw new Exception("You do not have permission to edit this entry");
		}
		return formulizeHandler::entriesAreUsersEntryMeetsBaseConditions($formId, $entryId, cacheId: 'preWrite');
	}

	/**
	 * Does the currently logged-in user have the right to administer user accounts?
	 * This is the system_admin permission on the user system (XOOPS_SYSTEM_USER) — a permission
	 * that could be granted to a non-webmaster — NOT membership in the webmasters group. Used to
	 * gate adminOnly user-account fields during submission processing.
	 * @return bool
	 */
	public static function activeUserCanManageUsers() {
		global $xoopsUser;
		$gperm_handler = xoops_gethandler('groupperm');
		$groups = $xoopsUser ? $xoopsUser->getGroups() : array(XOOPS_GROUP_ANONYMOUS);
		return (bool) $gperm_handler->checkRight('system_admin', XOOPS_SYSTEM_USER, $groups);
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
			// Sensible defaults for a freshly created account so the signup form doesn't have to
			// collect timezone / notification preferences (they can be changed later in Edit Account).
			$userObject->setVar('user_regdate', time());
			$userObject->setVar('notify_method', 2); // email, matching the standard registration default
			if(isset($xoopsConfig['default_TZ'])) {
				$userObject->setVar('timezone_offset', $xoopsConfig['default_TZ']);
			}
			// Self-registered accounts start inactive (level 0) and are activated only once the person
			// confirms the code we send to their email/phone. Admin-created users (users.php) stay
			// active (level 1) as before.
			$userObject->setVar('level', formulize_selfRegistrationActive() ? 0 : 1);
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
		$activeUserCanManageUsers = self::activeUserCanManageUsers();

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
			if(!empty($accountElement->adminOnly) && !$activeUserCanManageUsers) {
				continue; // admin-only field; never trust the submitted value from a user without user-management rights
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
				$value = $icmspass->hashPassword($value);
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
			($effectiveNewMethod != $old2faMethod) ||
			(($old2faMethod == TFA_SMS || $effectiveNewMethod == TFA_SMS) && $newPhone != $old2faPhone) ||
			(($old2faMethod == TFA_EMAIL || $old2faMethod == TFA_OFF || $effectiveNewMethod == TFA_EMAIL || $effectiveNewMethod == TFA_OFF) && $oldEmail && $newEmail != $oldEmail) ||
			$passwordChanged
		);

		if(!$needsValidation) {
			return true;
		}

		// Two-phase (verify the OLD contact, then the NEW contact) is required in three cases:
		// 1. Staying on email, email is changing. Email is the default 2FA contact when no method
		//    is set, so TFA_OFF is treated as email-ish here: changing your email while 2FA is off
		//    (or set to email) double-validates old + new email.
		// 2. Staying on SMS, phone is changing.
		// 3. Switching between two different active (non-off) methods.
		$oldMethodIsEmailish = ($old2faMethod == TFA_EMAIL || $old2faMethod == TFA_OFF);
		$newMethodIsEmailish = ($effectiveNewMethod == TFA_EMAIL || $effectiveNewMethod == TFA_OFF);
		$contactChanging = (
			($oldMethodIsEmailish && $newMethodIsEmailish && $oldEmail && $newEmail != $oldEmail) ||
			($old2faMethod == TFA_SMS && $effectiveNewMethod == TFA_SMS && $old2faPhone && $newPhone != $old2faPhone) ||
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
	 * Check submitted account fields for collisions with an existing account (server-side
	 * uniqueness enforcement, mirroring the form's client-side XHR check). Returns the
	 * human-readable name of the first conflicting field, or '' when all submitted values are
	 * unique. The account's own record is excluded (via $entryUserId) so that editing an account
	 * without changing these fields is not flagged as a conflict.
	 *
	 * @param array $pendingUserVars    Pending users-table changes (may include email / login_name)
	 * @param array $pendingProfileVars Pending profile-table changes (may include 2faphone)
	 * @param int   $entryUserId        The uid of the account being edited, or 0 for a new account
	 * @return string The conflicting field's label, or '' if there is no conflict
	 */
	private static function accountFieldConflict($pendingUserVars, $pendingProfileVars, $entryUserId) {
		global $xoopsDB;
		$entryUserId = intval($entryUserId);

		// Each check: [table, column, id column used to exclude the account's own row, value, label]
		$checks = array();
		if(isset($pendingUserVars['email']) AND $pendingUserVars['email'] !== '') {
			$checks[] = array('users', 'email', 'uid', $pendingUserVars['email'],
				defined('_formulize_USERACCOUNTEMAIL') ? strtolower(_formulize_USERACCOUNTEMAIL) : 'email address');
		}
		if(isset($pendingUserVars['login_name']) AND $pendingUserVars['login_name'] !== '') {
			$checks[] = array('users', 'login_name', 'uid', $pendingUserVars['login_name'],
				defined('_formulize_USERACCOUNTUSERNAME') ? strtolower(_formulize_USERACCOUNTUSERNAME) : 'username');
		}
		if(isset($pendingProfileVars['2faphone']) AND $pendingProfileVars['2faphone'] !== '') {
			$checks[] = array('profile_profile', '2faphone', 'profileid', $pendingProfileVars['2faphone'],
				defined('_formulize_USERACCOUNTPHONE') ? strtolower(_formulize_USERACCOUNTPHONE) : 'phone number');
		}

		foreach($checks as $check) {
			list($table, $column, $idColumn, $value, $label) = $check;
			$exclude = $entryUserId ? " AND `$idColumn` != $entryUserId" : '';
			$sql = "SELECT COUNT(*) AS count FROM ".$xoopsDB->prefix($table)." WHERE `$column` = '".formulize_db_escape($value)."'".$exclude;
			if($res = $xoopsDB->query($sql)) {
				$row = $xoopsDB->fetchArray($res);
				if($row AND $row['count'] > 0) {
					return $label;
				}
			}
		}
		return '';
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
		$validOperators = array('=', '!=', '<', '>', '<=', '>=', 'LIKE', 'NOT LIKE');
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
			// No $element/$entry_id in this shared helper's signature - they are only used to TAG
			// purification log events, so they are simply omitted rather than plumbed in.
			return new XoopsFormLabel($caption, $this->makeValueSafeForReadOnlyDisplay($ele_value));
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

/**
 * Generate the shared JS validation code for email and phone user account elements.
 *
 * Defined as a standalone function (not a handler method) so the output is byte-identical
 * regardless of which element calls it; this allows the deduplication logic in
 * _drawValidationJS() (formdisplay.php) to emit it only once per form.
 *
 * @param object    $element  The element object (used to resolve sibling element IDs)
 * @param int|mixed $entry_id The current entry ID
 * @return array Array of JavaScript statement strings
 */
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
