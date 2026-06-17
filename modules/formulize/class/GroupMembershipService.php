<?php
/**
 * GroupMembershipService - Service class for managing group memberships
 * 
 * Consolidates logic for processing user-to-group membership changes
 * that was previously duplicated in userAccountGroupMembershipElement 
 * and groupTableElement classes.
 * 
 * @package formulize
 * @subpackage classes
 */

class GroupMembershipService {

	/**
	 * Parse and validate a UID list from JSON or array input
	 * @param mixed $input JSON string or array of user IDs
	 * @return array Array of positive integer UIDs
	 */
	public static function parseUidList($input) {
		if(empty($input) || $input === '[]') {
			return array();
		}
		
		if(is_string($input)) {
			$decoded = json_decode($input, true);
			if(!is_array($decoded)) {
				return array();
			}
			$input = $decoded;
		}
		
		if(!is_array($input)) {
			return array();
		}
		
		// Filter to positive integers only
		return array_values(array_filter(array_map('intval', $input), function($uid) { 
			return $uid > 0; 
		}));
	}

	/**
	 * Ensure user is in required system groups and not in forbidden ones
	 * @param array $groupIds Array of group IDs to modify (passed by reference)
	 * @param array $currentUserGroups Current user's groups (for permission checks)
	 * @param array $targetUserCurrentGroups Target user's current groups (for preserving memberships)
	 */
	public static function enforceSystemGroupRules(&$groupIds, $currentUserGroups, $targetUserCurrentGroups = array()) {
		// Always ensure user is in Registered Users group
		if(!in_array(XOOPS_GROUP_USERS, $groupIds)) {
			$groupIds[] = XOOPS_GROUP_USERS;
		}
		
		// Never allow user to be in Anonymous Users group
		$anonGroupKey = array_search(XOOPS_GROUP_ANONYMOUS, $groupIds);
		if($anonGroupKey !== false) {
			unset($groupIds[$anonGroupKey]);
		}
		
		// Only webmasters can assign to the webmasters group
		$webmastersGroupKey = array_search(XOOPS_GROUP_ADMIN, $groupIds);
		if($webmastersGroupKey !== false && !in_array(XOOPS_GROUP_ADMIN, $currentUserGroups)) {
			unset($groupIds[$webmastersGroupKey]);
		}
		
		// If target user is a webmaster, non-webmasters cannot remove them from that group
		if(!empty($targetUserCurrentGroups) 
			&& in_array(XOOPS_GROUP_ADMIN, $targetUserCurrentGroups)
			&& !in_array(XOOPS_GROUP_ADMIN, $groupIds)
			&& !in_array(XOOPS_GROUP_ADMIN, $currentUserGroups)) {
			$groupIds[] = XOOPS_GROUP_ADMIN;
		}
		
		// Re-index array after unset operations
		$groupIds = array_values($groupIds);
	}

	/**
	 * Resolve default groups and their conditions for entries-are-users forms
	 * @param object $formObject The form object
	 * @param int $formId The form ID
	 * @param int $entryId The entry ID
	 * @param array $submittedGroupIds Array of submitted group IDs (passed by reference)
	 */
	public static function applyDefaultGroupsAndConditions($formObject, $formId, $entryId, &$submittedGroupIds) {
		$defaultGroups = $formObject->getVar('entries_are_users_default_groups');
		if(!is_array($defaultGroups) || empty($defaultGroups)) {
			return;
		}
		
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
			
			// Resolve template group to entry groups, if necessary
			$resolvedGroupIds = formulizeHandler::resolveDefaultGroupId($defaultGroupId, $formId, $entryId, $elementLinks);
			
			// If there's a family of entry groups for a template, remove all from that family first
			if($templateFamilyGroups = formulizeHandler::getAllGroupsForTemplateCategory($defaultGroupId)) {
				$submittedGroupIds = array_diff($submittedGroupIds, $templateFamilyGroups);
			}
			
			// Check conditions if they exist for this group
			if(isset($allConditions[$defaultGroupId]) && !empty($allConditions[$defaultGroupId])) {
				$conditionsMet = checkConditionsAgainstAnEntry($allConditions[$defaultGroupId], $formId, $entryId, null, -1);
				if(!$conditionsMet) {
					// Conditions not met, remove these groups
					$submittedGroupIds = array_diff($submittedGroupIds, $resolvedGroupIds);
					continue;
				}
			}
			
			// Add the resolved groups
			$submittedGroupIds = array_unique(array_merge($submittedGroupIds, $resolvedGroupIds));
		}
	}

	/**
	 * Calculate which groups to add and which to remove
	 * @param array $submittedGroupIds Array of desired group IDs
	 * @param array $currentGroupIds Array of current group IDs
	 * @return array Array with 'toAdd' and 'toRemove' keys
	 */
	public static function diffMemberships($submittedGroupIds, $currentGroupIds) {
		// Normalize to integers
		$submittedGroupIds = array_map('intval', array_unique($submittedGroupIds));
		$currentGroupIds = array_map('intval', array_unique($currentGroupIds));
		
		return array(
			'toAdd' => array_diff($submittedGroupIds, $currentGroupIds),
			'toRemove' => array_diff($currentGroupIds, $submittedGroupIds),
		);
	}

	/**
	 * Apply membership changes to a user
	 * @param int $userId The user ID
	 * @param array $toAdd Array of group IDs to add
	 * @param array $toRemove Array of group IDs to remove
	 * @throws Exception If membership changes fail
	 */
	public static function applyMembershipChanges($userId, $toAdd, $toRemove) {
		$member_handler = xoops_gethandler('member');
		$validGroupIds = array_keys($member_handler->getGroups(id_as_key: true));
		
		// Add user to new groups
		foreach($toAdd as $groupId) {
			if(in_array($groupId, $validGroupIds)) {
				if($member_handler->addUserToGroup($groupId, $userId) === false) {
					throw new Exception("Failed to add user to group ID $groupId");
				}
			}
		}
		
		// Remove user from groups
		foreach($toRemove as $groupId) {
			if($member_handler->removeUsersFromGroup($groupId, array($userId)) === false) {
				throw new Exception("Failed to remove user from group ID $groupId");
			}
		}
	}

	/**
	 * Check if there are any membership changes needed
	 * @param array $submittedGroupIds Array of desired group IDs
	 * @param array $currentGroupIds Array of current group IDs
	 * @return bool True if changes are needed, false otherwise
	 */
	public static function hasMembershipChanges($submittedGroupIds, $currentGroupIds) {
		$diff = self::diffMemberships($submittedGroupIds, $currentGroupIds);
		return !empty($diff['toAdd']) || !empty($diff['toRemove']);
	}

	/**
	 * Filter out users who cannot be removed from a group due to EAU conditions
	 * @param array $uids Array of user IDs to remove
	 * @param int $groupId The group ID
	 * @return array Filtered array of user IDs that can actually be removed
	 */
	public static function filterMandatoryMemberships($uids, $groupId) {
		return array_values(array_filter($uids, function($uid) use ($groupId) {
			return !formulizeUserAccountGroupMembershipElementHandler::isGroupMandatoryForUser($uid, $groupId);
		}));
	}

	/**
	 * Process group membership changes from POST data for a user in an entries-are-users form
	 * @param int $userId The user ID
	 * @param int $formId The form ID
	 * @param int $entryId The entry ID
	 * @param object $formObject The form object (optional, will be loaded if not provided)
	 * @throws Exception If permissions are insufficient or changes fail
	 */
	public static function processUserGroupMemberships($userId, $formId, $entryId, $formObject = null) {
		global $xoopsUser;
		
		if(!$formObject) {
			$form_handler = xoops_getmodulehandler('forms', 'formulize');
			if(!$formObject = $form_handler->get($formId)) {
				throw new Exception("Could not retrieve form object for form ID $formId");
			}
		}
		
		// Permission checks
		$isUserTableForm = $formObject->isSystemUsersTableForm();
		$gperm_handler = xoops_gethandler('groupperm');
		$activeUserGroups = $xoopsUser ? $xoopsUser->getGroups() : array(XOOPS_GROUP_ANONYMOUS);
		$activeUserId = $xoopsUser ? intval($xoopsUser->getVar('uid')) : 0;
		// The authority to administer user accounts is the system_admin permission on the user
		// system — NOT membership in the webmasters group (a non-webmaster could be granted it).
		$canManageUsers = (bool) $gperm_handler->checkRight('system_admin', XOOPS_SYSTEM_USER, $activeUserGroups);
		if($isUserTableForm) {
			if(!$canManageUsers) {
				// The system users form has no condition-driven memberships, so there is nothing
				// for a non-administrator to process. A user editing their OWN record (edituser.php
				// self-service) legitimately reaches here — no-op. Anyone reaching here for someone
				// else's record without user-management rights is a genuine violation.
				if($activeUserId && intval($entryId) === $activeUserId) {
					return;
				}
				throw new Exception("You do not have permission to manage system users.");
			}
		} else {
			if(!formulizePermHandler::user_can_edit_entry($formId, $xoopsUser->getVar('uid'), $entryId)) {
				throw new Exception("You do not have permission to edit this entry");
			}
		}
		
		// Check base conditions for entries-are-users
		if(!$isUserTableForm && !formulizeHandler::entriesAreUsersEntryMeetsBaseConditions($formId, $entryId)) {
			return; // Entry doesn't represent a user
		}
		
		// Get current and submitted group memberships
		$member_handler = xoops_gethandler('member');
		$currentGroupIds = $member_handler->getGroupsByUser($userId);
		$submittedGroupIds = $currentGroupIds; // default to current
		
		// Check if group membership element was submitted. The group membership element is
		// adminOnly, so only honour a submitted value when the active user can manage users; for
		// everyone else the submitted value is ignored and only condition-driven default groups
		// (below) apply. Never trust the rendered form — the field may be absent from the page but
		// a value could still be forged into POST.
		$element_handler = xoops_getmodulehandler('elements', 'formulize');
		if($canManageUsers && $groupMembershipElement = $element_handler->get('formulize_user_account_groupmembership_'.$formId)) {
			$elementId = $groupMembershipElement->getVar('ele_id');
			if(isset($_POST['de_'.$formId.'_'.$entryId.'_'.$elementId])) {
				$submittedGroupIds = $_POST['de_'.$formId.'_'.$entryId.'_'.$elementId];
				if(!is_array($submittedGroupIds)) {
					$submittedGroupIds = array($submittedGroupIds);
				}
			}
		}
		
		// Apply system group rules
		self::enforceSystemGroupRules($submittedGroupIds, $xoopsUser->getGroups(), $currentGroupIds);
		
		// Apply default groups and their conditions
		self::applyDefaultGroupsAndConditions($formObject, $formId, $entryId, $submittedGroupIds);
		
		// Check if any changes are needed
		if(!self::hasMembershipChanges($submittedGroupIds, $currentGroupIds)) {
			return;
		}
		
		// Apply the changes
		$diff = self::diffMemberships($submittedGroupIds, $currentGroupIds);
		self::applyMembershipChanges($userId, $diff['toAdd'], $diff['toRemove']);
	}

	/**
	 * Process group membership changes from the eagGroupMembers widget
	 * @param int $groupId The group ID
	 * @param int $formId The form ID
	 * @param int $entryId The entry ID (used for POST key construction)
	 * @throws Exception If membership changes fail
	 */
	public static function processGroupMembershipWidget($groupId, $formId, $entryId) {
		global $xoopsDB;
		
		$addKey = 'group_members_add_' . $formId . '_' . $entryId . '_' . $groupId;
		$removeKey = 'group_members_remove_' . $formId . '_' . $entryId . '_' . $groupId;
		
		// Parse UIDs from POST
		$addUids = array();
		$removeUids = array();
		if(isset($_POST[$addKey])) {
			$addUids = self::parseUidList($_POST[$addKey]);
		}
		if(isset($_POST[$removeKey])) {
			$removeUids = self::parseUidList($_POST[$removeKey]);
		}
		
		// Filter out users with mandatory membership
		if(!empty($removeUids)) {
			$removeUids = self::filterMandatoryMemberships($removeUids, $groupId);
		}
		
		// Apply removals
		$gulTable = $xoopsDB->prefix('groups_users_link');
		if(!empty($removeUids)) {
			$removeList = implode(',', $removeUids);
			$xoopsDB->queryF(
				"DELETE FROM `$gulTable` WHERE groupid = $groupId AND uid IN ($removeList)"
			);
		}
		
		// Apply additions (check for existing memberships first)
		if(!empty($addUids)) {
			$existRes = $xoopsDB->query(
				"SELECT uid FROM `$gulTable` WHERE groupid = $groupId"
			);
			$existingUids = array();
			while($existRes && $row = $xoopsDB->fetchArray($existRes)) {
				$existingUids[] = intval($row['uid']);
			}
			foreach($addUids as $uid) {
				if(!in_array($uid, $existingUids)) {
					$xoopsDB->queryF(
						"INSERT INTO `$gulTable` (groupid, uid) VALUES ($groupId, $uid)"
					);
					$existingUids[] = $uid;
				}
			}
		}
	}
}
