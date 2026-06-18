<?php
###############################################################################
##               Formulize - ad hoc form creation and reporting              ##
##                    Copyright (c) 2009 Freeform Solutions                  ##
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

include_once XOOPS_ROOT_PATH.'/modules/formulize/include/functions.php';
include_once XOOPS_ROOT_PATH.'/modules/formulize/class/forms.php';

#[AllowDynamicProperties]
class formulizePermHandler {

    var $fid;                               // the form this Perm Handler object is attached to
    static $cached_permissions = array();   // permission checks are called frequently for the same entries, so cache the results
    static $formulize_module_id = null;
    private static $ownAccountCache = array(); // cache for isUserOwnAccountEntry results


    /**
     * Determine whether a user is editing their own account entry, either in the system users
     * ad hoc form (where entry_id equals uid) or in an entries-are-users form (where a uid
     * column links the entry to the user).  Results are cached so the DB queries run at most
     * once per (form_id, user_id, entry_id) combination per page load.
     *
     * @param int       $form_id  The form ID
     * @param int       $user_id  The logged-in user ID
     * @param int|mixed $entry_id The entry ID being rendered/saved
     * @return bool True if this is the user's own account entry
     */
    public static function isUserOwnAccountEntry($form_id, $user_id, $entry_id) {
        $form_id  = intval($form_id);
        $user_id  = intval($user_id);
        $entry_id = intval($entry_id);

        if (!$form_id || !$user_id || !$entry_id) {
            return false;
        }

        $cache_key = "$form_id:$user_id:$entry_id";
        if (array_key_exists($cache_key, self::$ownAccountCache)) {
            return self::$ownAccountCache[$cache_key];
        }

        global $xoopsDB;
        $fi_table = $xoopsDB->prefix('formulize_id');

        // System users form: entry_id IS the uid
        $sysRes = $xoopsDB->query("SELECT id_form FROM $fi_table WHERE form_handle = '__system_users' LIMIT 1");
        if ($sysRes && ($sysRow = $xoopsDB->fetchArray($sysRes))) {
            if ((int)$sysRow['id_form'] === $form_id && $entry_id === $user_id) {
                return self::$ownAccountCache[$cache_key] = true;
            }
        }

        // EAU form: the form must have entries_are_users=1 and the entry's uid column must match
        $eauRes = $xoopsDB->query("SELECT form_handle FROM $fi_table WHERE id_form = $form_id AND entries_are_users = 1 LIMIT 1");
        if ($eauRes && ($eauRow = $xoopsDB->fetchArray($eauRes))) {
            $uid_col   = 'formulize_user_account_uid_' . $form_id;
            $data_table = $xoopsDB->prefix('formulize_' . $eauRow['form_handle']);
            $chkRes = $xoopsDB->query("SELECT entry_id FROM $data_table WHERE entry_id = $entry_id AND `$uid_col` = $user_id LIMIT 1");
            if ($chkRes && $xoopsDB->fetchArray($chkRes)) {
                return self::$ownAccountCache[$cache_key] = true;
            }
        }

        return self::$ownAccountCache[$cache_key] = false;
    }


    function __construct($fid) {
        $this->fid = intval($fid);
    }


    /**
     * Return the canonical list of Formulize form permission names.
     *
     * @return array Array of permission name strings
     */
    static function getPermissionList() {
        // canonical list of form permissions
        return array("view_form", "add_own_entry", "update_own_entry", "delete_own_entry", "update_other_entries", "delete_other_entries",
            "add_proxy_entries", "view_groupscope", "view_globalscope", "view_private_elements", "update_other_reports", "delete_other_reports",
            "publish_reports", "publish_globalscope", "set_notifications_for_others", "import_data", "edit_form", "delete_form",
            "update_entry_ownership", "ignore_editing_lock", "update_group_entries", "delete_group_entries");
    }


    /**
     * Check whether a user belongs to any group with delete permission on a form.
     *
     * Used only to decide whether to include a delete button in the UI, not as a security gate.
     *
     * @param int $form_id The form ID
     * @param int $user_id The user ID
     * @return bool True if the user has any delete permission on the form
     */
    static function user_can_delete_from_form($form_id, $user_id) {
        $cache_key = "delete-button $form_id $user_id";
        if (!isset(self::$cached_permissions[$cache_key])) {
            self::$cached_permissions[$cache_key] = false;

            if (null == self::$formulize_module_id)
                self::$formulize_module_id = getFormulizeModId();

            $gperm_handler =& xoops_gethandler('groupperm');
            $member_handler =& xoops_gethandler('member');
            $groups = $member_handler->getGroupsByUser($user_id);

            if ($gperm_handler->checkRight("delete_own_entry", $form_id, $groups, self::$formulize_module_id)
                or $gperm_handler->checkRight("delete_other_entries", $form_id, $groups, self::$formulize_module_id)
                or $gperm_handler->checkRight("delete_group_entries", $form_id, $groups, self::$formulize_module_id))
            {
                // user belongs to a group that can delete entries from the form
                self::$cached_permissions[$cache_key] = true;
            }
        }
        return self::$cached_permissions[$cache_key];
    }


    /**
     * Check whether a user is allowed to update a specific entry.
     *
     * @param int       $form_id  The form ID
     * @param int       $user_id  The user ID
     * @param int|mixed $entry_id The entry ID ("new" or numeric)
     * @return bool True if the user may update the entry
     */
    static function user_can_edit_entry($form_id, $user_id, $entry_id) {
        if (self::isUserOwnAccountEntry($form_id, $user_id, $entry_id)) {
            return true;
        }
        return self::user_can_modify_entry("update", $form_id, $user_id, $entry_id);
    }

    /**
     * Check whether a user has real Formulize form permission to edit a specific entry,
     * without the own-account auto-grant from isUserOwnAccountEntry.
     *
     * Use this when own-account access should not override element-level edit decisions — e.g.
     * to decide whether to disable non-private elements for a user viewing their own EAU entry
     * but holding only view (not edit) rights on the form.
     */
    public static function user_has_formulize_edit_permission($form_id, $user_id, $entry_id) {
        return self::user_can_modify_entry("update", $form_id, $user_id, $entry_id);
    }


    /**
     * Check whether a user is allowed to delete a specific entry.
     *
     * Use this both to show the delete checkbox and as a security check before actual deletion.
     *
     * @param int       $form_id  The form ID
     * @param int       $user_id  The user ID
     * @param int|mixed $entry_id The entry ID
     * @return bool True if the user may delete the entry
     */
    static function user_can_delete_entry($form_id, $user_id, $entry_id) {
        return self::user_can_modify_entry("delete", $form_id, $user_id, $entry_id);
    }


    private static function user_can_modify_entry($action, $form_id, $user_id, $entry_id) {
        if (!in_array($action, array("update", "delete")))
            throw new Exception("Error: specify either update or delete when calling user_can_modify_entry();");

        $cache_key = "$action $form_id $user_id $entry_id";
        if (!isset(self::$cached_permissions[$cache_key])) {
            self::$cached_permissions[$cache_key] = false;

            if (null == self::$formulize_module_id)
                self::$formulize_module_id = getFormulizeModId();

            $gperm_handler =& xoops_gethandler('groupperm');
            $member_handler =& xoops_gethandler('member');
            $groups = $user_id == 0 ? array(XOOPS_GROUP_ANONYMOUS) : $member_handler->getGroupsByUser($user_id);

            if ("new" == $entry_id or "" == $entry_id) {
                if ("update" == $action) {
                    // user has permission to add new entries
                    self::$cached_permissions[$cache_key] = $gperm_handler->checkRight("add_own_entry", $form_id, $groups, self::$formulize_module_id);

                    if (!self::$cached_permissions[$cache_key]) {
                        self::$cached_permissions[$cache_key] = $gperm_handler->checkRight("add_proxy_entries", $form_id, $groups, self::$formulize_module_id);
                    }
                } else {
                    self::$cached_permissions[$cache_key] = false;  // cannot delete an entry which has not been saved
                }
            } else {
                // first check if this an entry by current user and they can edit their own entries
                if (getEntryOwner($entry_id, $form_id) == $user_id) {
                    // user can update entry because it is their own and they have permission to update their own entries
                    self::$cached_permissions[$cache_key] = $gperm_handler->checkRight("{$action}_own_entry", $form_id, $groups, self::$formulize_module_id);
                }
                // next, check group and other permissions, even for own entries
                if (! self::$cached_permissions[$cache_key]) {
                    // user can update entry because they have permission to update entries by others
                    self::$cached_permissions[$cache_key] = $gperm_handler->checkRight("{$action}_other_entries", $form_id, $groups, self::$formulize_module_id);

                    if (!self::$cached_permissions[$cache_key]) {
                        // check if the user belongs to a group with group-edit permission
                        if ($gperm_handler->checkRight("{$action}_group_entries", $form_id, $groups, self::$formulize_module_id)) {
                            // sometimes users can have a special group scope set, so use that if available
                            $formulize_permHandler = new formulizePermHandler($form_id);
                            $view_form_groups = $formulize_permHandler->getGroupScopeGroupIds($groups);
                            if ($view_form_groups === false) {
                                // no special group scope, so use normal view-form permissions
                                $view_form_groups = $gperm_handler->getGroupIds("view_form", $form_id, self::$formulize_module_id);
                                // need the groups the user is a member of, that have view form permission
                                $view_form_groups = array_intersect($view_form_groups, $groups);
                            }

                            // get the owner groups for the entry
                            $data_handler = new formulizeDataHandler($form_id);
                            $owner_groups = $data_handler->getEntryOwnerGroups($entry_id);

                            // check if the entry belongs to a group that is part of the scope that the user is permitted to interact with
                            self::$cached_permissions[$cache_key] = count(array_intersect($owner_groups, $view_form_groups));
                        }
                    }
                }
            }
            //Second update to include custom edit check code

            if("update"== $action && $entry_id > 0){
                $formHandler = xoops_getmodulehandler('forms','formulize');
                $formObject = $formHandler->get($form_id);
                self::$cached_permissions[$cache_key] = $formObject->customEditCheck($form_id,$entry_id,$user_id, self::$cached_permissions[$cache_key]);
            }

        }
        return self::$cached_permissions[$cache_key];
    }


	/**
	 * Return an array of group names (keyed by group ID) that are in the scope of the given groups.
	 *
	 * @param int|array $gids Group ID or array of group IDs whose scope you want to query
	 * @return array|false Group names keyed by group ID, or false if no scope is defined
	 */
	function getGroupScopeGroups($gids) {
		return $this->_getGroupScopeGroupsOrIds($gids, 'group_names');
	}

	/**
	 * Return an array of group IDs that are in the scope of the given groups.
	 *
	 * @param int|array $gids Group ID or array of group IDs whose scope you want to query
	 * @return array|false Group IDs, or false if no scope is defined
	 */
	function getGroupScopeGroupIds($gids) {
		return $this->_getGroupScopeGroupsOrIds($gids, 'group_ids');
	}

	/**
	 * Return an array of group IDs whose defined scope includes all of the given groups.
	 *
	 * This is the reverse of getGroupScopeGroupIds(): instead of asking "what is in this
	 * group's scope?", this asks "which groups have these groups in their scope?".
	 *
	 * @param int|array $gids Group ID or array of group IDs to search for in scope definitions
	 * @return array Group IDs that have $gids in their scope
	 */
	function getGroupsHavingSpecificScope($gids) {
		return $this->_getGroupsHavingScopeInfo($gids);
	}

	/**
	 * Return an array of group IDs that have a defined scope which does NOT include the given groups.
	 *
	 * The inverse of getGroupsHavingSpecificScope(): returns groups whose scope is defined
	 * but excludes all of $gids.
	 *
	 * @param int|array $gids Group ID or array of group IDs to exclude from scope matching
	 * @return array Group IDs whose scope does not include any of $gids
	 */
	function getGroupsHavingDifferentSpecificScope($gids) {
		return $this->_getGroupsHavingScopeInfo($gids, true); // true causes the difference to be returned, instead of the groups that do have the specified gids as their scope
	}

	/**
	 * Set the groupscope (view_groupids) for a group on this form.
	 *
	 * Inserts groups that are newly added and removes groups that are no longer in scope.
	 *
	 * @param int       $gid  The group ID whose scope is being set
	 * @param int|array $gids The group ID(s) to set as the scope (empty array clears the scope)
	 * @return bool True on success, false on database failure
	 */
	function setGroupScopeGroups($gid, $gids) {
		if(!is_array($gids)) {
			if(is_numeric($gids) AND $gids > 0) {
				$gids = array(0=>$gids);
			} else {
				$gids = array();
			}
		}
		$currentViewGroups = $this->getGroupScopeGroupIds($gid);
		if($currentViewGroups !== false) {
			$groupsToInsert = array_diff($gids, (array)$currentViewGroups);
			$groupsToDelete = array_diff((array)$currentViewGroups, $gids);
		} else {
			$groupsToInsert = $gids; // if none are set, then just insert all the ones specified
		}
		global $xoopsDB;
		$insertSQL = "INSERT INTO ".$xoopsDB->prefix("formulize_groupscope_settings"). " (`groupid`, `fid`, `view_groupid`) VALUES ";
		$start = true;
		foreach($groupsToInsert as $k=>$thisInsertGroup) {
			if(is_numeric($thisInsertGroup) AND $thisInsertGroup > 0) {
                if(!$start) { $insertSQL .= ", "; }
                $insertSQL .= "($gid, ".$this->fid.", $thisInsertGroup)";
                $start = false;
            } else {
                unset($groupsToInsert[$k]);
            }
		}
		if(count((array) $groupsToInsert) > 0) {
			if(!$res = $xoopsDB->query($insertSQL))  {
				return false;
			}
		}
		if(count((array) $groupsToDelete) > 0) {
			$deleteSQL = "DELETE FROM ".$xoopsDB->prefix("formulize_groupscope_settings") . " WHERE `fid` = ".$this->fid." AND `groupid` = ".intval($gid)." AND `view_groupid` IN (".implode(", ", $groupsToDelete).")";
			if(!$res = $xoopsDB->query($deleteSQL)) {
				return false;
			}
		}
		$this->_getGroupScopeGroupInfo($gid, true); // true forces the cached values to be updated
		return true;
	}

	/**
	 * Internal helper: retrieve group-scope info of the requested type for one or more groups.
	 *
	 * @param int|array $gids Group ID or array of group IDs
	 * @param string    $type 'group_names' or 'group_ids'
	 * @return array|false Combined scope data, or false if none found
	 */
	function _getGroupScopeGroupsOrIds($gids, $type) {
		if(!is_array($gids)) {
			$gids = array(0=>intval($gids));
		}
		$groupScopeInfo = array();
		foreach($gids as $gid) {
			if($thisGroupScopeInfo = $this->_getGroupScopeGroupInfo($gid)) {
				$groupScopeInfo = $thisGroupScopeInfo[$type] + $groupScopeInfo;
			}
		}
		ksort($groupScopeInfo);
		if(count((array) $groupScopeInfo) > 0) {
			return $groupScopeInfo;
		} else {
			return false;
		}
	}

	/**
	 * Internal helper: retrieve and cache the groupscope info for a single group on this form.
	 *
	 * @param int  $gid         The group ID
	 * @param bool $updateCache True to force a cache refresh
	 * @return array|false Array with 'group_ids' and 'group_names' keys, or false if no scope defined
	 */
	function _getGroupScopeGroupInfo($gid, $updateCache=false) {
		static $cachedGroupScopeInfo = array();
		if(!isset($cachedGroupScopeInfo[$this->fid][$gid]) OR $updateCache) {
			$cachedGroupScopeInfo[$this->fid][$gid] = array();
			global $xoopsDB;
			$sql = "SELECT t1.view_groupid, t2.name FROM ".$xoopsDB->prefix("formulize_groupscope_settings")." as t1, ".$xoopsDB->prefix("groups")." as t2 WHERE t1.groupid=".intval($gid)." AND t1.fid = ".$this->fid." AND t1.view_groupid=t2.groupid AND t1.view_groupid > 0";
			if($res = $xoopsDB->query($sql)) {
        if($xoopsDB->getRowsNum($res) != 0) {
					while($array = $xoopsDB->fetchArray($res)) {
						$cachedGroupScopeInfo[$this->fid][$gid]['group_ids'][$array['view_groupid']] = $array['view_groupid'];
						$cachedGroupScopeInfo[$this->fid][$gid]['group_names'][$array['view_groupid']] = $array['name'];
					}
				} else { // no forced groups specified for this group
					$cachedGroupScopeInfo[$this->fid][$gid] = false;
				}
			} else { // query failed
				$cachedGroupScopeInfo[$this->fid][$gid] = false;
			}
		}
		return $cachedGroupScopeInfo[$this->fid][$gid];
	}

	/**
	 * Internal helper: return the group IDs that have (or don't have) the given groups in their scope.
	 *
	 * @param int|array $gids      Group ID or array of group IDs to search for
	 * @param bool      $different True to return groups whose scope EXCLUDES $gids (inverse)
	 * @return array Group IDs
	 */
	function _getGroupsHavingScopeInfo($gids, $different=false) {
		if(!is_array($gids)) {
			$gids = array(0=>intval($gids));
		}
		global $xoopsDB;
		if(!$different) {
			$sql = "SELECT groupid FROM ".$xoopsDB->prefix("formulize_groupscope_settings")." WHERE view_groupid IN (".formulize_db_escape(implode(", ", $gids)).") AND fid=".$this->fid;
		} else {
			$sql = "SELECT groupid FROM ".$xoopsDB->prefix("formulize_groupscope_settings")." as t1 WHERE fid = ".$this->fid." AND view_groupid != 0 AND NOT EXISTS(SELECT 1 FROM ".$xoopsDB->prefix("formulize_groupscope_settings")." as t2 WHERE view_groupid IN (".formulize_db_escape(implode(", ", $gids)).") AND fid=".$this->fid." AND t1.groupid = t2.groupid)";
		}
		$res = $xoopsDB->query($sql);
		$foundGids = array();
		while($array = $xoopsDB->fetchArray($res)) {
			$foundGids[] = $array['groupid'];
		}
		return $foundGids;
	}

	/**
	 * Copy group permissions, per-group filters, and groupscope from one group to another.
	 * If $modid is specified, only permissions for that module are copied; otherwise all.
	 * Groupscope targets are resolved using an optional mapping, so that relative references
	 * between groups (e.g., template group A scoping template group B) are translated to
	 * corresponding targets (e.g., entry group A scoping entry group B).
	 *
	 * @param int $sourceGroupId The group to copy FROM
	 * @param int $targetGroupId The group to copy TO
	 * @param int|null $modid Module ID to restrict permission copying. Null = all modules.
	 * @param array $groupIdMapping Maps source-side group IDs to target-side group IDs for
	 *   groupscope resolution. When a groupscope target matches a key in this map, the
	 *   corresponding value is used instead. Targets not in the map are copied as-is.
	 * @return bool True on success
	 */
	static function copyGroupPermissions($sourceGroupId, $targetGroupId, $modid = null, $groupIdMapping = array()) {
		$sourceGroupId = intval($sourceGroupId);
		$targetGroupId = intval($targetGroupId);
		if (!$sourceGroupId || !$targetGroupId) {
			return false;
		}

		global $xoopsDB;

		// Copy group_permission records using the groupperm handler
		$gperm_handler = xoops_gethandler('groupperm');
		if (!$gperm_handler->copyGroupIdRights($sourceGroupId, $targetGroupId, $modid)) {
			return false;
		}

		// Delete existing filters for target group and copy from source
		$xoopsDB->queryF("DELETE FROM " . $xoopsDB->prefix("formulize_group_filters") .
			" WHERE groupid = $targetGroupId");

		$sql = "INSERT INTO " . $xoopsDB->prefix("formulize_group_filters") .
			" (fid, groupid, filter) " .
			"SELECT fid, $targetGroupId, filter " .
			"FROM " . $xoopsDB->prefix("formulize_group_filters") .
			" WHERE groupid = $sourceGroupId";
		$xoopsDB->queryF($sql); // OK if no rows to copy

		// Copy groupscope settings with relative mapping across all forms
		$form_handler = xoops_getmodulehandler('forms', 'formulize');
		$forms = $form_handler->getAllForms();
		foreach ($forms as $thisForm) {
			$thisFid = $thisForm->getVar('id_form');
			$permHandler = new formulizePermHandler($thisFid);
			$scopeGroupIds = $permHandler->getGroupScopeGroupIds($sourceGroupId);
			$resolvedScopeGroups = array();
			if (is_array($scopeGroupIds) && count($scopeGroupIds) > 0) {
				foreach ($scopeGroupIds as $scopeGid) {
					$resolvedScopeGroups[] = isset($groupIdMapping[$scopeGid]) ? $groupIdMapping[$scopeGid] : $scopeGid;
				}
			}
			// Always call setGroupScopeGroups so that resetting source to default clears the target too
			$permHandler->setGroupScopeGroups($targetGroupId, $resolvedScopeGroups);
		}

		return true;
	}

	/**
	 * Copy all permission records, per-group filters, and groupscope settings from one form to another.
	 * Replaces all existing permission data for the target form.
	 *
	 * @param int $sourceFid The form ID to copy permissions FROM
	 * @param int $targetFid The form ID to copy permissions TO
	 * @return bool True on success
	 */
	static function copyFormPermissions($sourceFid, $targetFid) {
		$sourceFid = intval($sourceFid);
		$targetFid = intval($targetFid);
		if (!$sourceFid || !$targetFid || $sourceFid === $targetFid) {
			return false;
		}

		global $xoopsDB;

		// Copy group_permission records using the groupperm handler
		$gperm_handler = xoops_gethandler('groupperm');
		$formulize_module_id = getFormulizeModId();
		if (!$gperm_handler->copyItemRights($sourceFid, $targetFid, $formulize_module_id)) {
			throw new Exception("Failed to copy group permissions from form $sourceFid to form $targetFid");
		}

		// can't copy filters, because they are based on form fields and the fields will be different between the forms.

		// Copy groupscope settings: delete target then insert from source
		$xoopsDB->queryF("DELETE FROM " . $xoopsDB->prefix("formulize_groupscope_settings") . " WHERE fid = $targetFid");
		$xoopsDB->queryF("INSERT INTO " . $xoopsDB->prefix("formulize_groupscope_settings") . " (groupid, fid, view_groupid) "
			. "SELECT groupid, $targetFid, view_groupid FROM " . $xoopsDB->prefix("formulize_groupscope_settings") . " WHERE fid = $sourceFid");

		return true;
	}

	/**
	 * Synchronize group references in a single element's settings using a group map.
	 * For each group-based setting (ele_display, ele_disabled, ele_value[3], ele_value['formlink_scope']):
	 *   - If a source group IS present, ensure all its target groups are also present
	 *   - If a source group is NOT present, ensure none of its target groups are present
	 *
	 * This is the general utility for keeping group references in sync. Used by both
	 * template group synchronization and the copy group permissions admin page.
	 *
	 * @param object $element The element object (modified in place via setVar)
	 * @param array $groupMap Maps source group IDs to arrays of target group IDs.
	 *   Example: [templateGroupId => [entryGroupId1, entryGroupId2], ...]
	 *   or: [sourceGroupId => [targetGroupId1, targetGroupId2]]
	 * @return bool True if the element was modified and needs saving
	 */
	static function synchronizeGroupReferencesInElement(&$element, $groupMap) {
		if (empty($groupMap)) {
			return false;
		}

		include_once XOOPS_ROOT_PATH . '/modules/formulize/class/elements.php';

		$ele_display = $element->getVar('ele_display');
		$ele_disabled = $element->getVar('ele_disabled');
		$ele_type = $element->getVar('ele_type');
		$ele_value = $element->getVar('ele_value');
		$modified = false;

		// Synchronize a comma-delimited group string like ",5,12,15,"
		$syncCommaDelimited = function($value) use ($groupMap) {
			if (!is_string($value) || $value === "1" || $value === "0" || $value === "") {
				return array($value, false);
			}
			$changed = false;
			foreach ($groupMap as $sourceGroupId => $targetGroupIds) {
				$sourcePresent = strstr($value, ",$sourceGroupId,") !== false;
				foreach ($targetGroupIds as $targetGroupId) {
					$targetPresent = strstr($value, ",$targetGroupId,") !== false;
					if ($sourcePresent && !$targetPresent) {
						$value .= "$targetGroupId,";
						$changed = true;
					} elseif (!$sourcePresent && $targetPresent) {
						$value = str_replace(",$targetGroupId,", ",", $value);
						$changed = true;
					}
				}
			}
			return array($value, $changed);
		};

		// Synchronize a plain comma-separated list like "5,12,15"
		$syncCommaSeparated = function($value) use ($groupMap) {
			if (!is_string($value) || $value === "") {
				return array($value, false);
			}
			$groups = explode(",", $value);
			$changed = false;
			foreach ($groupMap as $sourceGroupId => $targetGroupIds) {
				$sourcePresent = in_array($sourceGroupId, $groups);
				foreach ($targetGroupIds as $targetGroupId) {
					$targetPresent = in_array($targetGroupId, $groups);
					if ($sourcePresent && !$targetPresent) {
						$groups[] = $targetGroupId;
						$changed = true;
					} elseif (!$sourcePresent && $targetPresent) {
						$groups = array_values(array_diff($groups, array($targetGroupId)));
						$changed = true;
					}
				}
			}
			return array(implode(",", $groups), $changed);
		};

		// Sync ele_display
		list($newDisplay, $displayChanged) = $syncCommaDelimited($ele_display);
		if ($displayChanged) {
			$element->setVar('ele_display', $newDisplay);
			$modified = true;
		}

		// Sync ele_disabled
		list($newDisabled, $disabledChanged) = $syncCommaDelimited($ele_disabled);
		if ($disabledChanged) {
			$element->setVar('ele_disabled', $newDisabled);
			$modified = true;
		}

		// Sync ele_value[3] for select-type elements
		if (anySelectElementType($ele_type) && isset($ele_value[3])) {
			list($newFilterGroups, $filterChanged) = $syncCommaSeparated($ele_value[3]);
			if ($filterChanged) {
				$ele_value[3] = $newFilterGroups;
				$element->setVar('ele_value', $ele_value);
				$modified = true;
			}
		}

		// Sync ele_value['formlink_scope'] for checkbox elements
		if (($ele_type == "checkbox" || $ele_type == "checkboxLinked") && isset($ele_value['formlink_scope'])) {
			list($newScope, $scopeChanged) = $syncCommaSeparated($ele_value['formlink_scope']);
			if ($scopeChanged) {
				$ele_value['formlink_scope'] = $newScope;
				$element->setVar('ele_value', $ele_value);
				$modified = true;
			}
		}

		return $modified;
	}
}
