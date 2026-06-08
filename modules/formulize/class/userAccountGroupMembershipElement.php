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
require_once XOOPS_ROOT_PATH . "/modules/formulize/class/userAccountElement.php";
require_once XOOPS_ROOT_PATH . "/modules/formulize/class/autocompleteElement.php";

class formulizeUserAccountGroupMembershipElement extends formulizeUserAccountElement {

		var $excludeTemplateGroups; // whether to exclude template groups from the list, or include all groups. Default is to exclude template groups since they are not meant to be assigned to users.

    function __construct() {
			parent::__construct();
			$this->name = "User Account Group Membership";
			$this->excludeTemplateGroups = true; // default to excluding template groups since they are not meant to be assigned to users, but this can be overridden by setting this property to false
			// Note: no userProperty defined because group membership is stored in groups_users_link table, not on the user object
		}

}

#[AllowDynamicProperties]
class formulizeUserAccountGroupMembershipElementHandler extends formulizeUserAccountElementHandler {

	function create() {
		return new formulizeUserAccountGroupMembershipElement();
	}

	// Override loadValue since we're not reading from a user property
	// Group membership data comes from the groups_users_link table
	function loadValue($element, $value, $entry_id) {
		$member_handler = xoops_gethandler('member');
		$fid = $element->getVar('fid');
		$form_handler = xoops_getmodulehandler('forms', 'formulize');
		$formObject = $form_handler->get($fid);
		$userId = $formObject ? $formObject->getSystemUserIdFromEntry($entry_id) : 0;
		if(!$userId OR !$userObject = $member_handler->getUser($userId)) {
			return array(ELE_VALUE_SELECT_OPTIONS => array(), ELE_VALUE_SELECT_MULTIPLE => 1); // No user yet; keep multiselect flag so render() sees the right mode
		}
		return array(ELE_VALUE_SELECT_OPTIONS => $member_handler->getGroupsByUser($userId), ELE_VALUE_SELECT_MULTIPLE => 1); // unusual use of ELE_VALUE_SELECT_OPTIONS to store the selected group ids, but we will always gather the full set below in the render method and need a way of knowing which are selected
	}

	// this method renders the element for display in a form
	// the caption has been pre-prepared and passed in separately from the element object
	// if the element is disabled, then the method must take that into account and return a non-interactable label with some version of the element's value in it
	// $ele_value is the options for this element - which will either be the admin values set by the admin user, or will be the value created in the loadValue method
	// $caption is the prepared caption for the element
	// $markupName is what we have to call the rendered element in HTML
	// $isDisabled flags whether the element is disabled or not so we know how to render it
	// $element is the element object
	// $entry_id is the ID number of the entry where this particular element comes from
	// $screen is the screen object that is in effect, if any (may be null)
	function render($ele_value, $caption, $markupName, $isDisabled, $element, $entry_id, $screen, $owner) {
		// Setup an autocomplete element to render the group selection UI, and render that
		$autocompleteHandler = xoops_getmodulehandler('autocompleteElement', 'formulize');
		$autocompleteElement = $autocompleteHandler->create();
		$autocomplete_ele_value = $autocompleteHandler->getDefaultEleValue();
		$autocomplete_ele_value[ELE_VALUE_SELECT_MULTIPLE] = $ele_value[ELE_VALUE_SELECT_MULTIPLE];
		list($autocomplete_ele_value[ELE_VALUE_SELECT_OPTIONS], $groupUITextList) = self::getAvailableGroupsForOptions($ele_value[ELE_VALUE_SELECT_OPTIONS], $element->excludeTemplateGroups);
		$autocompleteElement->setVar('ele_uitext', $groupUITextList);
		$autocompleteElement->useOptionsAsValues = true; // will use group ids as the values in the HTML markup, and then we can just save those values directly without having to do extra work to figure out which options were selected based on their ordinal position in the list, which would introduce race conditions too!
		if($isDisabled) {
			$selectedNames = array();
			foreach($autocomplete_ele_value[ELE_VALUE_SELECT_OPTIONS] as $groupId => $isSelected) {
				if($isSelected && isset($groupUITextList[$groupId])) {
					$selectedNames[] = htmlspecialchars($groupUITextList[$groupId], ENT_QUOTES);
				}
			}
			$elementHandle = $element->getVar('ele_handle');
			// Keep the markupName-based class (auto_multi_{markupName}) that the JS in
			// selectElement.php / autocomplete.js relies on, and add the stable
			// handle-based class (auto_multi_{elementHandle}) additively so tests can
			// target the element across entries. Mirrors the enabled-branch behaviour below.
			$labelContent = implode('', array_map(function($name) use ($markupName, $elementHandle) {
				return "<p class='auto_multi auto_multi_{$markupName} auto_multi_{$elementHandle}'>{$name}</p>";
			}, $selectedNames));
			return new XoopsFormLabel($caption, $labelContent, $markupName);
		}
		$formElement = $autocompleteHandler->render($autocomplete_ele_value, $caption, $markupName, $isDisabled, $autocompleteElement, $entry_id, $screen, $owner);
		// Add the stable element-handle CSS class alongside the de_-based class so that both the
		// per-entry de_ name (de_{fid}_{entryId}_{eleId}) and the stable handle-based name are present.
		// GroupMembershipService reads $_POST['de_...'] so the POST key must stay unchanged;
		// we only inject the extra class into the rendered HTML.
		$elementHandle = $element->getVar('ele_handle');
		if($elementHandle && $elementHandle !== $markupName && $formElement instanceof XoopsFormLabel) {
			$html = str_replace(
				"auto_multi auto_multi_{$markupName}",
				"auto_multi auto_multi_{$markupName} auto_multi_{$elementHandle}",
				$formElement->getValue()
			);
			return new XoopsFormLabel($caption, $html, $markupName);
		}
		return $formElement;
	}

	// this method returns any custom validation code (javascript) that should figure out how to validate this element
	// 'myform' is a name enforced by convention that refers to the form where this element resides
	// use the adminCanMakeRequired property and alwaysValidateInputs property to control when/if this validation code is respected
	function generateValidationCode($caption, $markupName, $element, $entry_id=false) {
		$validationCode = array();
		return $validationCode;
	}

	/**
	 * Get all available groups that are not template groups
	 * Exclude registered users group since it is always enforced on users when data is saved
	 * @param array $currentlySelectedGroupIds Optional array of group IDs that should be marked as selected in the options list
	 * @param bool $excludeTemplateGroups Optional flag to exclude template groups (default is true, which excludes template groups)
	 * @return array Option strings as keys, 0 as values
	 */
	static function getAvailableGroupsForOptions($currentlySelectedGroupIds = [], $excludeTemplateGroups = true) {
		global $xoopsDB, $xoopsUser;
		$groupOptionList = array();
		$groupUITextList = array();
		$webmasterGroupExclusion = "";
		$templateGroupExclusion = "";
		if(!in_array(XOOPS_GROUP_ADMIN, $xoopsUser->getGroups())) {
			$webmasterGroupExclusion = " AND groupid != ".XOOPS_GROUP_ADMIN;
		}
		if($excludeTemplateGroups) {
			$templateGroupExclusion = " AND is_group_template = 0 ";
		}
		$sql = "SELECT groupid, name FROM " . $xoopsDB->prefix('groups') . " WHERE groupid != ".XOOPS_GROUP_USERS." AND groupid != ".XOOPS_GROUP_ANONYMOUS." $webmasterGroupExclusion $templateGroupExclusion ORDER BY name";
		$result = $xoopsDB->query($sql);
		if($result) {
			$currentlySelectedGroupIds = is_array($currentlySelectedGroupIds) ? $currentlySelectedGroupIds : [];
			while($row = $xoopsDB->fetchArray($result)) {
				$groupUITextList[$row['groupid']] = $row['name'];
				$groupOptionList[$row['groupid']] = in_array($row['groupid'], $currentlySelectedGroupIds) ? 1 : 0; // Mark as selected if in currently selected group IDs
			}
		}
		return array($groupOptionList, $groupUITextList);
	}

	/**
	 * Update the group membership element's help text (ele_desc) to indicate which default groups are enforced.
	 * Reads all necessary data from the form object: default groups, conditions, and element links.
	 * Called from upsertFormSchemaAndResources so that any change to form settings triggers the update.
	 *
	 * @param object $formObject The form object with entries_are_users settings
	 */
	static function updateGroupMembershipDescription($formObject) {
		$fid = $formObject->getVar('fid');
		$groupMembershipHandle = 'formulize_user_account_groupmembership_' . $fid;
		$element_handler = xoops_getmodulehandler('elements', 'formulize');
		$groupMembershipElement = $element_handler->get($groupMembershipHandle);
		if (!$groupMembershipElement) {
			return;
		}

		include_once XOOPS_ROOT_PATH . '/modules/formulize/class/formulize.php';

		$defaultGroups = $formObject->getVar('entries_are_users_default_groups');
		$allConditions = $formObject->getVar('entries_are_users_conditions');
		$sanitizedLinks = $formObject->getVar('entries_are_users_default_groups_element_links');

		if (!is_array($defaultGroups)) {
			$defaultGroups = array();
		}
		if (!is_array($allConditions)) {
			$allConditions = array();
		}
		if (!is_array($sanitizedLinks)) {
			$sanitizedLinks = array();
		}

		if (empty($defaultGroups)) {
			$groupMembershipElement->setVar('ele_desc', '');
			$element_handler->insert($groupMembershipElement);
			return;
		}

		$member_handler = xoops_gethandler('member');
		$templateGroupMetadata = formulizeHandler::getTemplateGroupMetadataForForm($fid);
		$groupDescriptions = array();

		// First pass: collect structured entries for each group
		$structuredEntries = array();
		foreach ($defaultGroups as $groupId) {
			$groupId = intval($groupId);
			if (isset($sanitizedLinks[$groupId]) && isset($templateGroupMetadata[$groupId])) {
				// Template group
				$meta = $templateGroupMetadata[$groupId];
				$conditionDesc = '';
				if (isset($allConditions[$groupId]) && !empty($allConditions[$groupId])) {
					$conditionDesc = self::describeConditions($allConditions[$groupId], $element_handler);
				}
				$basedOnList = array();
				foreach ($meta['linkedElements'] as $linkedElement) {
					if (in_array(intval($linkedElement['ele_id']), $sanitizedLinks[$groupId])) {
						if (!empty($linkedElement['formName']) && $linkedElement['formName'] !== $meta['formPlural']) {
							$basedOnList[] = sprintf(
							    _AM_SETTINGS_FORM_ENTRIES_ARE_USERS_DEFAULT_GROUPS_ELEMENT_DESC_BASED_ON_IN_FORM,
							    $meta['formSingular'],
							    $linkedElement['caption'],
							    $linkedElement['formName']
							);
						} else {
							$basedOnList[] = sprintf(
							    _AM_SETTINGS_FORM_ENTRIES_ARE_USERS_DEFAULT_GROUPS_ELEMENT_DESC_BASED_ON,
							    $meta['formSingular'],
							    $linkedElement['caption']
							);
						}
					}
				}
				if (!empty($basedOnList)) {
					$structuredEntries[] = array(
						'title' => $meta['formSingular'] . ' - ' . $meta['categoryName'],
						'basedOnList' => $basedOnList,
						'conditionDesc' => $conditionDesc,
						'isTemplate' => true
					);
				}
			} else {
				// Regular group
				$groupObject = $member_handler->getGroup($groupId);
				if ($groupObject) {
					$conditionDesc = '';
					if (isset($allConditions[$groupId]) && !empty($allConditions[$groupId])) {
						$conditionDesc = self::describeConditions($allConditions[$groupId], $element_handler);
					}
					$structuredEntries[] = array(
						'title' => $groupObject->getVar('name'),
						'basedOnList' => array(),
						'conditionDesc' => $conditionDesc,
						'isTemplate' => false
					);
				}
			}
		}

		// Second pass: merge template groups sharing the same basedOnList and conditionDesc
		$mergedEntries = array();
		foreach ($structuredEntries as $entry) {
			if ($entry['isTemplate']) {
				$sortedBasedOn = $entry['basedOnList'];
				sort($sortedBasedOn);
				$mergeKey = implode('|||', $sortedBasedOn) . '~~~~' . $entry['conditionDesc'];
				if (isset($mergedEntries[$mergeKey])) {
					$mergedEntries[$mergeKey]['titles'][] = $entry['title'];
				} else {
					$mergedEntries[$mergeKey] = array(
						'titles' => array($entry['title']),
						'basedOnList' => $entry['basedOnList'],
						'conditionDesc' => $entry['conditionDesc'],
						'isTemplate' => true
					);
				}
			} else {
				$mergedEntries[] = array(
					'titles' => array($entry['title']),
					'basedOnList' => array(),
					'conditionDesc' => $entry['conditionDesc'],
					'isTemplate' => false
				);
			}
		}

		// Build HTML descriptions from merged entries
		foreach ($mergedEntries as $entry) {
			if ($entry['isTemplate']) {
				$titlesHtml = implode('<br>', $entry['titles']);
				$bullets = '';
				foreach ($entry['basedOnList'] as $basedOn) {
					$bullets .= '<li>' . $basedOn . '</li>';
				}
				if ($entry['conditionDesc']) {
					$bullets .= '<li>' . sprintf(_AM_SETTINGS_FORM_ENTRIES_ARE_USERS_DEFAULT_GROUPS_ELEMENT_DESC_ONLY_IF, $entry['conditionDesc']) . '</li>';
				}
				$groupDescriptions[] = $titlesHtml . '<ul>' . $bullets . '</ul>';
			} else {
				$desc = $entry['titles'][0];
				if ($entry['conditionDesc']) {
					$desc .= sprintf(_AM_SETTINGS_FORM_ENTRIES_ARE_USERS_DEFAULT_GROUPS_ELEMENT_DESC_CONDITIONAL, $entry['conditionDesc']);
				}
				$groupDescriptions[] = $desc;
			}
		}

		if (!empty($groupDescriptions)) {
			$count = count($groupDescriptions);
			if ($count > 1) {
				$toggleId = 'groupmembership_details_' . $fid;
				$showText = _AM_SETTINGS_FORM_ENTRIES_ARE_USERS_DEFAULT_GROUPS_ELEMENT_DESC_SHOW;
				$hideText = _AM_SETTINGS_FORM_ENTRIES_ARE_USERS_DEFAULT_GROUPS_ELEMENT_DESC_HIDE;
				$toggleLink = '<a href="javascript:void(0)" onclick="jQuery(\'#' . $toggleId . '\').slideToggle(200);jQuery(this).text(jQuery(this).text()==\'' . $showText . '\'?\'' . $hideText . '\':\'' . $showText . '\')">' . $showText . '</a>';
				$descList = $toggleLink . '<ul id="' . $toggleId . '" class="form-help-text" style="display:none"><li>' . implode('</li><li>', $groupDescriptions) . '</li></ul>';
			} else {
				$descList = $groupDescriptions[0];
			}
			$groupMembershipElement->setVar('ele_desc', sprintf(_AM_SETTINGS_FORM_ENTRIES_ARE_USERS_DEFAULT_GROUPS_ELEMENT_DESC, $formObject->getVar('form_title'), $descList));
		} else {
			$groupMembershipElement->setVar('ele_desc', '');
		}
		$element_handler->insert($groupMembershipElement);
	}

	/**
	 * Describe a set of filter conditions as a human-readable string.
	 * $conditions is expected to have parallel arrays at indexes:
	 *   0 = element IDs/handles, 1 = operators, 2 = terms, 3 = match modes ('oom' or 'all') per condition.
	 * Conditions with 'all' match mode are joined with "and", conditions with 'oom' are joined with "or".
	 * The two groups are then combined with "and" between them.
	 */
	private static function describeConditions($conditions, $element_handler = null) {
		if(!is_array($conditions) || empty($conditions) || !isset($conditions[0])) {
			return '';
		}
		$elements = $conditions[0];
		$ops = isset($conditions[1]) ? $conditions[1] : array();
		$terms = isset($conditions[2]) ? $conditions[2] : array();
		$matchModes = isset($conditions[3]) ? $conditions[3] : array();
		$allParts = array();
		$oomParts = array();
		foreach($elements as $i => $eleIdOrHandle) {
			$elementLabel = $eleIdOrHandle;
			if($element_handler) {
				$elementObject = $element_handler->get($eleIdOrHandle);
				if($elementObject) {
					$elementLabel = strip_tags($elementObject->getVar('ele_caption'));
				}
			}
			$op = isset($ops[$i]) ? $ops[$i] : '=';
			$term = isset($terms[$i]) ? $terms[$i] : '';
			$part = sprintf(_AM_SETTINGS_FORM_ENTRIES_ARE_USERS_DEFAULT_GROUPS_ELEMENT_DESC_CONDITION_ITEM, $elementLabel, $op, $term);
			$mode = isset($matchModes[$i]) ? $matchModes[$i] : 'all';
			if($mode === 'oom') {
				$oomParts[] = $part;
			} else {
				$allParts[] = $part;
			}
		}
		$groups = array();
		if(!empty($allParts)) {
			$groups[] = implode(' and ', $allParts);
		}
		if(!empty($oomParts)) {
			$groups[] = implode(' or ', $oomParts);
		}
		return implode(' and ', $groups);
	}

	/**
	 * Process the user's group memberships based on the submitted values in the group membership element, if it exists, and based on the default groups specified in form settings, subject to any conditions and template group resolution.
	 * @param int $userId The ID of the user whose group memberships we are processing
	 * @param int $formId The ID of the form where the submission is taking place
	 * @param int $entryId The ID of the entry being submitted/edited
	 * @throws Exception if processing group memberships fails for any reason
	 */
	static public function processUserGroupMemberships($userId, $formId, $entryId) {
		// Delegate to the shared service
		GroupMembershipService::processUserGroupMemberships($userId, $formId, $entryId);
	}

	/**
	 * Returns true if the given user must remain a member of the given group
	 * because at least one entries-are-users form mandates that membership.
	 * Checks base conditions and per-group conditions for the user's EAU entry.
	 * Adds are always allowed — this check only governs removals.
	 *
	 * @param int $userId  The uid being evaluated
	 * @param int $groupId The group the user is being removed from
	 * @return bool
	 */
	static public function isGroupMandatoryForUser($userId, $groupId) {
		static $cache = array();
		$userId  = intval($userId);
		$groupId = intval($groupId);
		if (!$userId || !$groupId) {
			return false;
		}
		$cacheKey = $userId . '_' . $groupId;
		if (array_key_exists($cacheKey, $cache)) {
			return $cache[$cacheKey];
		}
		$cache[$cacheKey] = false;

		global $xoopsDB;
		require_once XOOPS_ROOT_PATH . '/modules/formulize/include/functions.php';
		require_once XOOPS_ROOT_PATH . '/modules/formulize/include/extract.php';
		require_once XOOPS_ROOT_PATH . '/modules/formulize/class/data.php';

		$form_handler = xoops_getmodulehandler('forms', 'formulize');
		$allFidsRes = $xoopsDB->query(
			"SELECT id_form FROM " . $xoopsDB->prefix('formulize_id') . " WHERE entries_are_users = 1"
		);
		if (!$allFidsRes) {
			return false;
		}

		while ($fRow = $xoopsDB->fetchArray($allFidsRes)) {
			$eauFid = intval($fRow['id_form']);
			if (!$eauForm = $form_handler->get($eauFid)) {
				continue;
			}
			$defaultGroups = $eauForm->getVar('entries_are_users_default_groups');
			if (!is_array($defaultGroups) || empty($defaultGroups)) {
				continue;
			}

			// Find the relevant default group entry that maps to $groupId
			$relevantDgId = null;
			foreach ($defaultGroups as $dgId) {
				$dgId = intval($dgId);
				if ($dgId === $groupId) {
					$relevantDgId = $groupId;
					break;
				}
				$family = formulizeHandler::getAllGroupsForTemplateCategory($dgId);
				if (!empty($family) && in_array($groupId, $family)) {
					$relevantDgId = $dgId;
					break;
				}
			}
			if ($relevantDgId === null) {
				continue;
			}

			// Find the user's entry in this EAU form
			$uidHandle   = 'formulize_user_account_uid_' . $eauFid;
			$dataHandler = new formulizeDataHandler($eauFid);
			$entryIds    = $dataHandler->findAllEntriesWithValue($uidHandle, $userId);
			if (!$entryIds || empty($entryIds)) {
				continue;
			}
			$eauEntryId = intval($entryIds[0]);

			// Check base conditions
			if (!formulizeHandler::entriesAreUsersEntryMeetsBaseConditions($eauFid, $eauEntryId)) {
				continue;
			}

			// Check per-group conditions for this default group
			$allConditions = $eauForm->getVar('entries_are_users_conditions');
			if (is_array($allConditions) && isset($allConditions[$relevantDgId]) && !empty($allConditions[$relevantDgId])) {
				if (!checkConditionsAgainstAnEntry($allConditions[$relevantDgId], $eauFid, $eauEntryId, null, -1)) {
					continue;
				}
			}

			$cache[$cacheKey] = true;
			return true;
		}

		return false;
	}

	// Build a WHERE clause subquery that searches group membership by group name.
	// Searches groups the user belongs to (excluding built-in registered-users and anonymous groups).
	// Used by formulize_tryDelegatedSearchWhere so this element type can handle its own complex subquery.
	function buildSearchWhereClause($term, $operator, $quotes, $likebits, $fid, $tableAlias = 'main')
	{
		global $xoopsDB;
		$safeTermClause = $operator . $quotes . $likebits . formulize_db_escape($term) . $likebits . $quotes;
		return "EXISTS("
			. "SELECT 1 FROM " . $xoopsDB->prefix('groups_users_link') . " AS gul"
			. " JOIN " . $xoopsDB->prefix('groups') . " AS g ON g.groupid = gul.groupid"
			. " WHERE gul.uid = {$tableAlias}.uid"
			. " AND g.name" . $safeTermClause
			. " AND g.groupid NOT IN (" . XOOPS_GROUP_USERS . "," . XOOPS_GROUP_ANONYMOUS . ")"
			. ")";
	}

}
