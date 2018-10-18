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

class formulizePermHandler {

    var $fid;                               // the form this Perm Handler object is attached to
    static $cached_permissions = array();   // permission checks are called frequently for the same entries, so cache the results
    static $formulize_module_id = null;


    function __construct($fid) {
        $this->fid = intval($fid);
    }


    static function getPermissionList() {
        // canonical list of form permissions
        return array("view_form", "add_own_entry", "update_own_entry", "delete_own_entry", "update_other_entries", "delete_other_entries",
            "add_proxy_entries", "view_groupscope", "view_globalscope", "view_private_elements", "update_other_reports", "delete_other_reports",
            "publish_reports", "publish_globalscope", "set_notifications_for_others", "import_data", "edit_form", "delete_form",
            "update_entry_ownership", "ignore_editing_lock", "update_group_entries", "delete_group_entries");
    }


    // check if a user belongs to a group with delete permission on a form. only for checking whether the delete button should be included in the page
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


    // check whether a user is able to update an entry in a form
    static function user_can_edit_entry($form_id, $user_id, $entry_id) {
        return self::user_can_modify_entry("update", $form_id, $user_id, $entry_id);
    }


    // check whether a user is able to delete a specific entry in a form. use this to show the delete checkbox and as a security check before actual deletion
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
            $groups = $member_handler->getGroupsByUser($user_id);

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


	// this method returns an array of group names, keys are ids
	// gids can be a group id or array of ids...it is the groupids that you are asking about, and you want to know which specific groups are selected as the scope for these groups you're passing in
	function getGroupScopeGroups($gids) {
		return $this->_getGroupScopeGroupsOrIds($gids, 'group_names');
	}
	
	// this method returns an array of group ids
	// gids can be a group id or array of ids...it is the groupids that you are asking about, and you want to know which specific groups are selected as the scope for these groups you're passing in
	function getGroupScopeGroupIds($gids) {
		return $this->_getGroupScopeGroupsOrIds($gids, 'group_ids');
	}
	
	// this method returns an array of group ids
	// it finds the groups that have the specified gids as part of their defined scope (so backwards to the other methods like getGroupScopeGroupIds which return the specified groups that were selected as the scope for the ones passed in)
	function getGroupsHavingSpecificScope($gids) {
		return $this->_getGroupsHavingScopeInfo($gids);
	}
	
	// this method returns an array of group ids
	// it finds the groups with a defined scope, that DOES NOT include the specified gids (so the inverse of the getGroupsHavingSpecificScope method)
	function getGroupsHavingDifferentSpecificScope($gids) {
		return $this->_getGroupsHavingScopeInfo($gids, true); // true causes the difference to be returned, instead of the groups that do have the specified gids as their scope
	}
	
	// this method sets view_groupids for a group
	// gid is the group we're setting view groupids for
	// gids is the group or groups that are being set...(int or an array)
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
		foreach($groupsToInsert as $thisInsertGroup) {
			if($thisInsertGroup === 0) { continue; }
			if(!$start) { $insertSQL .= ", "; }
			$insertSQL .= "($gid, ".$this->fid.", $thisInsertGroup)";
			$start = false;
		}
		if(count($groupsToInsert) > 0) {
			if(!$res = $xoopsDB->query($insertSQL))  {
				return false;
			}
		}
		if(count($groupsToDelete) > 0) {
			$deleteSQL = "DELETE FROM ".$xoopsDB->prefix("formulize_groupscope_settings") . " WHERE `fid` = ".$this->fid." AND `groupid` = ".intval($gid)." AND `view_groupid` IN (".implode(", ", $groupsToDelete).")";
			if(!$res = $xoopsDB->query($deleteSQL)) {
				return false;
			}
		}
		$this->_getGroupScopeGroupInfo($gid, true); // true forces the cached values to be updated
		return true;
	}
	
	// this internal method gets the specified type of info about a group or groups
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
		if(count($groupScopeInfo) > 0) {
			return $groupScopeInfo;
		} else {
			return false;
		}
	}
	
	// this internal method retrieves the groupscope info for a given group
	function _getGroupScopeGroupInfo($gid, $updateCache=false) {
		static $cachedGroupScopeInfo = array();
		if(!isset($cachedGroupScopeInfo[$this->fid][$gid]) OR $updateCache) {
			$cachedGroupScopeInfo[$this->fid][$gid] = array();
			global $xoopsDB;
			$sql = "SELECT t1.view_groupid, t2.name FROM ".$xoopsDB->prefix("formulize_groupscope_settings")." as t1, ".$xoopsDB->prefix("groups")." as t2 WHERE t1.groupid=".intval($gid)." AND t1.fid = ".$this->fid." AND t1.view_groupid=t2.groupid";
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
	
	// this internal method returns the groups that have specified groups in their scope
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
}
