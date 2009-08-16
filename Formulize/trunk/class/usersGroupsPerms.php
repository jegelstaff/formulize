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

// require_once XOOPS_ROOT_PATH.'/kernel/object.php'; // xoops object not used
include_once XOOPS_ROOT_PATH.'/modules/formulize/include/functions.php';

class formulizePermHandler  {
	
	var $fid; // the form this Perm Handler object is attached to

	// $fid must be an id
	function formulizePermHandler($fid){
		$this->fid = intval($fid);
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
			$sql = "SELECT groupid FROM ".$xoopsDB->prefix("formulize_groupscope_settings")." WHERE view_groupid IN (".mysql_real_escape_string(implode(", ", $gids)).") AND fid=".$this->fid;
		} else {
			$sql = "SELECT groupid FROM ".$xoopsDB->prefix("formulize_groupscope_settings")." as t1 WHERE fid = ".$this->fid." AND NOT EXISTS(SELECT 1 FROM ".$xoopsDB->prefix("formulize_groupscope_settings")." as t2 WHERE view_groupid IN (".mysql_real_escape_string(implode(", ", $gids)).") AND fid=".$this->fid." AND t1.groupid = t2.groupid)";
		}
		$res = $xoopsDB->query($sql);
		$foundGids = array();
		while($array = $xoopsDB->fetchArray($res)) {
			$foundGids[] = $array['groupid'];			
		}
		return $foundGids;
	}
	
}
	