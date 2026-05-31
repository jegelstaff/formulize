<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2006 Freeform Solutions                  ##
###############################################################################
##  This program is free software; you can redistribute it and/or modify     ##
##  it under the terms of the GNU General Public License as published by     ##
##  the Free Software Foundation; either version 2 of the License, or        ##
##  (at your option) any later version.                                       ##
###############################################################################
##  Author of this file: Formulize Incorporated                               ##
##  Project: Formulize                                                        ##
###############################################################################

// Base class for elements that map directly to columns in the system groups table.
// Subclass to define a typed field (e.g. groupName, groupDescription).
// The isGroupTableElement flag on the element object triggers processGroupSubmission()
// in readelements.php, writing submitted values directly to the groups table rather
// than to a Formulize data table.

if (!defined('XOOPS_ROOT_PATH')) {
	exit();
}

require_once XOOPS_ROOT_PATH . "/modules/formulize/class/elements.php";
require_once XOOPS_ROOT_PATH . "/modules/formulize/class/userAccountGroupMembershipElement.php";

class formulizeGroupTableElement extends formulizeElement {

	var $groupProperty = null; // the groups table column this element represents; overridden in child classes

	function __construct() {
		$this->name               = "Group Table Settings Base Element";
		$this->hasData            = false;
		$this->needsDataType      = false;
		$this->adminCanMakeRequired = false;
		$this->isSystemElement    = true;
		$this->isGroupTableElement = true;
		parent::__construct();
	}

}

#[AllowDynamicProperties]
class formulizeGroupTableElementHandler extends formulizeElementsHandler {

	var $db;
	var $clickable;
	var $striphtml;
	var $length;

	function __construct($db) {
		$this->db =& $db;
	}

	function create() {
		return new formulizeGroupTableElement();
	}

	function adminPrepare($element) {
		return array();
	}

	function adminSave($element, $ele_value = array(), $advancedTab = false) {
		$element->setVar('ele_value', $ele_value);
	}

	// Read the group property for this element from the groups table using entry_id as groupid.
	function loadValue($element, $value, $entry_id) {
		$value = null;
		global $xoopsDB;
		if ($element->groupProperty && is_numeric($entry_id)) {
			$groupId = intval($entry_id);
			$result = $xoopsDB->query(
				"SELECT `" . formulize_db_escape($element->groupProperty) . "` FROM " .
				$xoopsDB->prefix('groups') . " WHERE groupid = $groupId"
			);
			if ($result && $row = $xoopsDB->fetchArray($result)) {
				$value = $row[$element->groupProperty];
			}
		}
		return $value;
	}

	function render($ele_value, $caption, $markupName, $isDisabled, $element, $entry_id, $screen = false, $owner = null) {
		return null;
	}

	function generateValidationCode($caption, $markupName, $element, $entry_id) {
	}

	function prepareDataForSaving($value, $element, $entry_id = null, $subformBlankCounter = null) {
		return $value;
	}

	function afterSavingLogic($value, $element_id, $entry_id) {
	}

	function prepareDataForDataset($value, $handle, $entry_id) {
		return $value;
	}

	function prepareLiteralTextForDB($value, $element, $partialMatch = false) {
		return $value;
	}

	function formatDataForList($value, $handle = "", $entry_id = 0, $textWidth = 100) {
		$this->clickable = false;
		$this->striphtml = true;
		$this->length    = 255;
		return parent::formatDataForList($value);
	}

	/**
	 * Write submitted group table element values directly to the groups table.
	 * Runs once per {formId, entryId} pair; subsequent calls return the cached result.
	 * Supports both editing existing groups (numeric entry_id = groupid) and
	 * creating new groups (entry_id = 'new').
	 *
	 * @param int $formId  The id of the system groups table form
	 * @param int|string $entryId The groupid of the group being edited, or 'new'
	 * @return int|bool  The groupid on success, false on failure or permission denied
	 */
	static public function processGroupSubmission($formId, $entryId) {
		global $xoopsUser, $xoopsDB;

		$form_handler = xoops_getmodulehandler('forms', 'formulize');
		$formObject   = $form_handler->get($formId);

		if (!$formObject) {
			return false;
		}

		$isSystemGroupsForm = $formObject->isSystemGroupsTableForm();
		$isEagForm          = !$isSystemGroupsForm && $formObject->getVar('entries_are_groups');

		if (!$isSystemGroupsForm && !$isEagForm) {
			return false;
		}

		if ($isSystemGroupsForm) {
			$gperm_handler = xoops_gethandler('groupperm');
			$groups = $xoopsUser ? $xoopsUser->getGroups() : array(XOOPS_GROUP_ANONYMOUS);
			if (!$gperm_handler->checkRight('system_admin', XOOPS_SYSTEM_GROUP, $groups)) {
				throw new Exception("You do not have permission to manage system groups.");
			}
		}

		static $results = array();
		$cacheKey = $formId . '-' . $entryId;
		if (isset($results[$cacheKey])) {
			return $results[$cacheKey];
		}

		$results[$cacheKey] = false;

		if ($isEagForm) {
			// For EAG forms the entry_id is the formulize entry; resolve the real groupid.
			// The membership widget shows "save first" for new entries, so nothing to do here.
			if ($entryId !== 'new' && is_numeric($entryId)) {
				$groupsTable = $xoopsDB->prefix('groups');
				$res = $xoopsDB->query(
					"SELECT groupid FROM `$groupsTable`" .
					" WHERE form_id = " . intval($formId) .
					" AND entry_id = " . intval($entryId) .
					" AND is_group_template = 0"
				);
				if ($res && $row = $xoopsDB->fetchArray($res)) {
					$results[$cacheKey] = intval($row['groupid']);
				}
			}
		} else {
			// System groups table form: handle group column updates (name, description, etc.)
			$element_handler  = xoops_getmodulehandler('elements', 'formulize');
			$pendingGroupVars = array();

			foreach ($form_handler->getGroupTableElementTypes() as $groupTableElementType) {
				$handle = 'formulize_group_' . strtolower(str_replace('group', '', $groupTableElementType)) . '_' . $formId;
				if ($groupElement = $element_handler->get($handle)) {
					$elementId = $groupElement->getVar('ele_id');
					if (!isset($_POST['decue_' . $formId . '_' . $entryId . '_' . $elementId])) {
						continue; // element not on this page
					}
					$value = isset($_POST['de_' . $formId . '_' . $entryId . '_' . $elementId])
						? $_POST['de_' . $formId . '_' . $entryId . '_' . $elementId] : '';
					if ($groupElement->groupProperty) {
						$pendingGroupVars[$groupElement->groupProperty] = $value;
					}
				}
			}

			if ($entryId === 'new' || !is_numeric($entryId)) {
				// Create a new group using the collected values
				if (empty($pendingGroupVars['name'])) {
					return false; // name is required
				}
				$member_handler = xoops_gethandler('member');
				$groupObject = $member_handler->createGroup();
				$groupObject->setVar('name', $pendingGroupVars['name']);
				$groupObject->setVar('description', isset($pendingGroupVars['description']) ? $pendingGroupVars['description'] : '');
				$groupObject->setVar('group_type', 'M');
				if ($member_handler->insertGroup($groupObject)) {
					$results[$cacheKey] = intval($groupObject->getVar('groupid'));
				}
			} elseif (!empty($pendingGroupVars)) {
				$setParts = array();
				foreach ($pendingGroupVars as $col => $val) {
					$setParts[] = "`" . formulize_db_escape($col) . "` = " . $xoopsDB->quoteString($val);
				}
				$sql = "UPDATE " . $xoopsDB->prefix('groups') .
					" SET " . implode(', ', $setParts) .
					" WHERE groupid = " . intval($entryId);
				if ($xoopsDB->queryF($sql)) {
					$results[$cacheKey] = intval($entryId);
				}
			} else {
				$results[$cacheKey] = intval($entryId);
			}
		}

		// Process membership changes submitted by the eagGroupMembers widget.
		// Only applies to existing groups — new groups show "save first" in the widget.
		$actualGroupId = $results[$cacheKey];
		if ($actualGroupId && $entryId !== 'new' && is_numeric($entryId)) {
			$addKey    = 'group_members_add_'    . $formId . '_' . $entryId;
			$removeKey = 'group_members_remove_' . $formId . '_' . $entryId;
			$addUids    = array();
			$removeUids = array();
			if (!empty($_POST[$addKey]) && $_POST[$addKey] !== '[]') {
				$decoded = json_decode($_POST[$addKey], true);
				if (is_array($decoded)) {
					$addUids = array_values(array_filter(array_map('intval', $decoded), function($uid) { return $uid > 0; }));
				}
			}
			if (!empty($_POST[$removeKey]) && $_POST[$removeKey] !== '[]') {
				$decoded = json_decode($_POST[$removeKey], true);
				if (is_array($decoded)) {
					$removeUids = array_values(array_filter(array_map('intval', $decoded), function($uid) { return $uid > 0; }));
				}
			}
			$gulTable = $xoopsDB->prefix('groups_users_link');
			if (!empty($removeUids)) {
				// Filter out any user whose membership in this group is mandated by an EAU form.
				$removeUids = array_values(array_filter($removeUids, function($uid) use ($actualGroupId) {
					return !formulizeUserAccountGroupMembershipElementHandler::isGroupMandatoryForUser($uid, $actualGroupId);
				}));
			}
			if (!empty($removeUids)) {
				$removeList = implode(',', $removeUids);
				$xoopsDB->queryF(
					"DELETE FROM `$gulTable` WHERE groupid = $actualGroupId AND uid IN ($removeList)"
				);
			}
			if (!empty($addUids)) {
				$existRes = $xoopsDB->query(
					"SELECT uid FROM `$gulTable` WHERE groupid = $actualGroupId"
				);
				$existingUids = array();
				while ($existRes && $row = $xoopsDB->fetchArray($existRes)) {
					$existingUids[] = intval($row['uid']);
				}
				foreach ($addUids as $uid) {
					if (!in_array($uid, $existingUids)) {
						$xoopsDB->queryF(
							"INSERT INTO `$gulTable` (groupid, uid) VALUES ($actualGroupId, $uid)"
						);
						$existingUids[] = $uid;
					}
				}
			}
		}

		return $results[$cacheKey];
	}

}
