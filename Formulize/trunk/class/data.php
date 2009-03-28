<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2006 Freeform Solutions                  ##
##                Portions copyright (c) 2003 NS Tai (aka tuff)              ##
##                       <http://www.brandycoke.com/>                        ##
###############################################################################
##                    XOOPS - PHP Content Management System                  ##
##                       Copyright (c) 2000 XOOPS.org                        ##
##                          <http://www.xoops.org/>                          ##
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

class formulizeDataHandler  {
	
	var $fid; // the form this Data Handler object is attached to

	// $fid must be an id
	function formulizeDataHandler($fid){
		$this->fid = intval($fid);
	}
	
	// this function copies data from one form to another
	// sourceFid is the ID of the form that we're copying data from
	// map is an array with the keys being the handles in the source form, and the values being the handle in the new cloned form
	function cloneData($sourceFid, $map) {
		global $xoopsDB;
		// 1. get all the data in the source form
		// 2. loop through it, and swap the old field names for the new ones
		// 3. write the data with new field names to the new datatable
		$sourceDataSQL = "SELECT * FROM " . $xoopsDB->prefix("formulize_".$sourceFid);
		if(!$sourceDataRes = $xoopsDB->query($sourceDataSQL)) {
			return false;
		}
		while($sourceDataArray = $xoopsDB->fetchArray($sourceDataRes)) {
			$start = true;
			$insertSQL = "INSERT INTO " . $xoopsDB->prefix("formulize_" . $this->fid) . " SET ";
			
			foreach($sourceDataArray as $field=>$value) {
				if($field == "entry_id") { $value = ""; } // use new ID numbers in the new table, in case there's ever a case where we're copying data into a table that already has data in it
				if(isset($map[$field])) { $field = $map[$field]; } // if this field is in the map, then use the value from the map as the field name (this will match the field name in the cloned form)
				if(!$start) { $insertSQL .= ", "; }
				$insertSQL .= "`$field` = \"" . mysql_real_escape_string($value) . "\"";
				$start = false;
			}
			if(!$insertResult = $xoopsDB->queryF($insertSQL)) {
				return false;
			}
		}
		return true;
	}

	// this function makes a copy of an entry in one form
	function cloneEntry($entry) {
		if(!is_numeric($entry)) {
			return false;
		}
		global $xoopsDB;
		$sql = "SELECT * FROM " . $xoopsDB->prefix("formulize_".$this->fid) . " WHERE entry_id = $entry";
		if(!$res = $xoopsDB->query($sql)) {
			return false;
		}
		$data = $xoopsDB->fetchArray($res);
		$sql = "INSERT INTO " . $xoopsDB->prefix("formulize_".$this->fid) . " SET ";
		$start = 1;
		foreach($data as $field=>$value) {
			if($field == "entry_id") { continue; }
			if(!$start) { $sql .= ", "; }
			$start = 0;
			$sql .= "`$field` = \"" . mysql_real_escape_string($value) . "\"";
		}
		if(!$res = $xoopsDB->query($sql)) {
			return false;
		}
		return $xoopsDB->getInsertId();
	}
	
	// this function looks in a particular entry in a particular form, and finds the entries it is pointing at, and then finds the new entries that it should be pointing at, and reassigns the values to match
	// intended to be called once per pair of linked selectboxes involved in a cloning process
	function reassignLSB($sourceFid, $lsbElement, $entryMap) {
		global $xoopsDB;
		foreach($entryMap[$lsbElement->getVar('id_form')] as $originalEntry=>$newEntries) {
			foreach($newEntries as $newEntryNum=>$thisEntry) {
				$sql = "SELECT `".$lsbElement->getVar('ele_handle') . "` FROM " . $xoopsDB->prefix("formulize_".$this->fid) . " WHERE entry_id=$thisEntry";
				if(!$res = $xoopsDB->query($sql)) {
					return false;
				}
				$array = $xoopsDB->fetchArray($res);
				$sourceEntryIds = explode(",", trim($array[$lsbElement->getVar('ele_handle')], ",")); // trim is meant to remove trailing and leading commas
				// now that we know what this entry was pointing at, we need to find those values in the map, and their corresponding new values, and write them back into this entry
				$newIds = array();
				foreach($sourceEntryIds as $thisId) {
					if(isset($entryMap[$sourceFid][$thisId])) { // only make up new assignments for entries that were actually cloned, leave others alone.
						$newIds[] = $entryMap[$sourceFid][$thisId][$newEntryNum];
					} else {
						$newIds[] = $thisId;
					}
				}
				$sql = "UPDATE " . $xoopsDB->prefix("formulize_".$this->fid) . " SET `".$lsbElement->getVar('ele_handle')."` = \",".implode(",",$newIds).",\" WHERE entry_id=$thisEntry";
				if(!$res = $xoopsDB->query($sql)) {
					return false;
				}
			}
		}
		return true;
	}

	// this function handles deletion of an entry in a form's datatable
	// can take a single ID or an array of IDs
	function deleteEntries($ids) {
		if(!is_array($ids)) {
			$sentID = $ids;
			$ids = array();
			$ids[0] = $sentID;
		}
		global $xoopsDB;
		$sql = "DELETE FROM " .$xoopsDB->prefix("formulize_".$this->fid) . " WHERE entry_id = " . implode(" OR entry_id = ", $ids);
		if(!$deleteSuccess = $xoopsDB->query($sql)) {
			return false;
		}
		$sql = "DELETE FROM " . $xoopsDB->prefix("formulize_entry_owner_groups") . " WHERE fid=".$this->fid." AND (entry_id = " . implode(" OR entry_id = ", $ids) . ")";
		if(!$deleteOwernshipSuccess = $xoopsDB->query($sql)) {
			print "Error: could not delete entry ownership information for form ". $this->fid . ", entries: " . implode(", ", $ids) . ". Check the DB queries debug info for details.";
		}
		return true;
	}
	
	// this function checks to see if a given entry exists
	function entryExists($id) {
		static $cachedEntryExists = array();
		if(!isset($cachedEntryExists[$this->fid][$id])) {
			global $xoopsDB;
			$sql = "SELECT entry_id FROM " . $xoopsDB->prefix("formulize_".$this->fid) . " WHERE entry_id = " . intval($id);
			$res = $xoopsDB->query($sql);
			$row = $xoopsDB->fetchRow($res);
			if($row[0] > 0) {
				$cachedEntryExists[$this->fid][$id] = true;
			} else {
				$cachedEntryExists[$this->fid][$id] = false;
			}
		}
		return $cachedEntryExists[$this->fid][$id];
	}
	
	// this function gets the metadata on an entry
	// returns an array with keys 0 through 3, corresponding to creation datetime, mod datetime, creation uid, mod uid
	// intended to be called like this:
	// $data_handler = new formulizeDataHandler($fid);
  // list($creation_datetime, $mod_datetime, $creation_uid, $mod_uid) = $data_handler->getEntryMeta($entry);
	// if $updateCache is set, then the data should be queried for fresh, and cache reupdated
	function getEntryMeta($id, $updateCache = false) {
		static $cachedEntryMeta = array();
		if(!isset($cachedEntryMeta[$this->fid][$id]) OR $updateCache) {
			global $xoopsDB;
			$sql = "SELECT creation_datetime, mod_datetime, creation_uid, mod_uid FROM " . $xoopsDB->prefix("formulize_".$this->fid) . " WHERE entry_id = " . intval($id);
			if(!$res = $xoopsDB->query($sql)) {
				$cachedEntryMeta[$this->fid][$id] = false;
			}
			$cachedEntryMeta[$this->fid][$id] = $xoopsDB->fetchRow($res);
		}
		return $cachedEntryMeta[$this->fid][$id];
	}
	
	// this function returns the creation users for a series of entries
	function getAllUsersForEntries($ids, $scope_uids=array()) {
		$scopeFilter = $this->_buildScopeFilter($scope_uids);
		global $xoopsDB;
		$sql = "SELECT creation_uid FROM " . $xoopsDB->prefix("formulize_".$this->fid) . " WHERE entry_id IN (" . implode(",", $ids) . ") $scopefilter";
		if(!$res = $xoopsDB->query($sql)) {
			return false;
		}
		$users = array();
		while($row = $xoopsDB->fetchRow($res)) {
			$users[] = $row[0];
		}
		return $users;
	}
	
	
	// this function figures out if a given element has a value in the given entry
	function elementHasValueInEntry($id, $element_id) {
		if(!$element = _getElementObject($element_id)) {
			return false;
		}
		global $xoopsDB;
		$sql = "SELECT `". $element->getVar('ele_handle') . "` FROM " . $xoopsDB->prefix("formulize_".$this->fid) . " WHERE entry_id = " . intval($id);
		if(!$res = $xoopsDB->query($sql)) {
			return false;
		}
		$row = $xoopsDB->fetchRow($res);
		if($row[0] != "") {
			return true;
		} else {
			return false;
		}
	}
	
	// this function returns the value of a given element in the given entry
	// use of $scope_uids should only be for when entries by the current user are searched for.  All other group based scopes should be done based on the scope_groups.
	function getElementValueInEntry($id, $element_id, $scope_uids=array(), $scope_groups=array()) {
		if(!$element = _getElementObject($element_id)) {
			return false;
		}
		global $xoopsDB;
		if(is_array($scope_uids) AND count($scope_uids)>0) {
			$scopeFilter = $this->_buildScopeFilter($scope_uids);
			$sql = "SELECT `". $element->getVar('ele_handle') . "` FROM " . $xoopsDB->prefix("formulize_".$this->fid) . " WHERE entry_id = " . intval($id) . $scopeFilter;
		} elseif(is_array($scope_groups) AND count($scope_groups)>0) {
			$scopeFilter = $this->_buildScopeFilter("", $scope_groups);
			$sql = "SELECT `t1.". $element->getVar('ele_handle') . "` FROM " . $xoopsDB->prefix("formulize_".$this->fid) . "AS t1, " . $xoopsDB->prefix("formulize_entry_owner_groups") . " AS t2 WHERE t1.entry_id = " . intval($id) . $scopeFilter;
		} else {
			$sql = "SELECT `". $element->getVar('ele_handle') . "` FROM " . $xoopsDB->prefix("formulize_".$this->fid) . " WHERE entry_id = " . intval($id);
		}
		if(!$res = $xoopsDB->query($sql)) {
			return false;
		}
		$row = $xoopsDB->fetchRow($res);
		return $row[0];
	}
	
	// this function finds all entries created by a given user in the form
	// use of $scope_uids should only be for when entries by the current user are searched for.  All other group based scopes should be done based on the scope_groups.
	function getAllEntriesForUsers($uids, $scope_uids=array(), $scope_groups=array()) {
		if(!is_array($uids)) {
			$sentID = $uids;
			$uids = array();
			$uids[0] = $sentID;
		}
		global $xoopsDB;
		if(is_array($scope_uids) AND count($scope_uids) > 0) {
			$scopeFilter = $this->_buildScopeFilter($scope_uids);
			$sql = "SELECT entry_id FROM " . $xoopsDB->prefix("formulize_".$this->fid) . " WHERE (creation_uid = " . implode(" OR creation_uid = ", $uids) . ") $scopeFilter ORDER BY entry_id";
		} elseif(is_array($scope_groups) AND count($scope_groups)>0) {
			$scopeFilter = $this->_buildScopeFilter("", $scope_groups);
			$sql = "SELECT t1.entry_id FROM " . $xoopsDB->prefix("formulize_".$this->fid) . "AS t1, " . $xoopsDB->prefix("formulize_entry_owner_groups") . " AS t2 WHERE (t1.creation_uid = " . implode(" OR t1.creation_uid = ", $uids) . ") $scopeFilter ORDER BY t1.entry_id";
		} else {
			$sql = "SELECT entry_id FROM " . $xoopsDB->prefix("formulize_".$this->fid) . " WHERE (creation_uid = " . implode(" OR creation_uid = ", $uids) . ") ORDER BY entry_id";
		}
		if(!$res = $xoopsDB->query($sql)) {
			return false;
		}
		$entries = array();
		while($row = $xoopsDB->fetchRow($res)) {
			$entries[] = $row[0];
		}
		return $entries;
	
	}
	// this function finds the first entry for a given user in the form
	function getFirstEntryForUsers($uids, $scope_uids=array()) {
		if(!is_array($uids)) {
			$sentID = $uids;
			$uids = array();
			$uids[0] = $sentID;
		}
		$scopeFilter = $this->_buildScopeFilter($scope_uids);
		global $xoopsDB;
		$sql = "SELECT entry_id FROM " . $xoopsDB->prefix("formulize_".$this->fid) . " WHERE (creation_uid = " . implode(" OR creation_uid = ", $uids) . ") $scopeFilter ORDER BY entry_id LIMIT 0,1";
		if(!$res = $xoopsDB->query($sql)) {
			return false;
		}
		$row = $xoopsDB->fetchRow($res);
		return $row[0];
	
	}
	
	// this function returns the entry ID of the first entry found in the form with the specified value in the specified element
	function findFirstEntryWithValue($element_id, $value) {
		if(!$element = _getElementObject($element_id)) {
			return false;
		}
		global $xoopsDB;
		$sql = "SELECT entry_id FROM " . $xoopsDB->prefix("formulize_".$this->fid) . " WHERE `". $element->getVar('ele_handle') . "` = \"" . mysql_real_escape_string($value) . "\" ORDER BY entry_id LIMIT 0,1";
		if(!$res = $xoopsDB->query($sql)) {
			return false;
		}
		$row = $xoopsDB->fetchRow($res);
		return $row[0];
	}
	
	// this function returns the entry ID of all entries found in the form with the specified value in the specified element
	// use of $scope_uids should only be for when entries by the current user are searched for.  All other group based scopes should be done based on the scope_groups.
	function findAllEntriesWithValue($element_id, $value, $scope_uids=array(), $scope_groups=array(), $operator="=") {
		if(!$element = _getElementObject($element_id)) {
			return false;
		}
		global $xoopsDB;
		$queryValue = "\"" . mysql_real_escape_string($value) . "\"";
		if($operator == "{LINKEDSEARCH}") {
			$operator = "LIKE";
			$queryValue = "\"%," . mysql_real_escape_string($value) . ",%\"";
		}
		if(is_array($scope_uids) AND count($scope_uids) > 0) {
			$scopeFilter = $this->_buildScopeFilter($scope_uids);
			$sql = "SELECT entry_id FROM " . $xoopsDB->prefix("formulize_".$this->fid) . " WHERE `". $element->getVar('ele_handle') . "` $operator $queryValue $scopeFilter GROUP BY entry_id ORDER BY entry_id";
		} elseif(is_array($scope_groups) AND count($scope_groups)>0) {
			$scopeFilter = $this->_buildScopeFilter("", $scope_groups);
			$sql = "SELECT t1.entry_id FROM " . $xoopsDB->prefix("formulize_".$this->fid) . " AS t1, " . $xoopsDB->prefix("formulize_entry_owner_groups") . " AS t2 WHERE t1.`". $element->getVar('ele_handle') . "` $operator $queryValue $scopeFilter GROUP BY t1.entry_id ORDER BY t1.entry_id";
		} else {
			$sql = "SELECT entry_id FROM " . $xoopsDB->prefix("formulize_".$this->fid) . " WHERE `". $element->getVar('ele_handle') . "` $operator $queryValue GROUP BY entry_id ORDER BY entry_id";			
		}
		if(!$res = $xoopsDB->query($sql)) {
			return false;
		}
		$entries = array();
		while($row = $xoopsDB->fetchRow($res)) {
			$entries[] = $row[0];
		}
		return $entries;
	}
	
	// this function returns all the values of a given field, for the entries that are passed to it
	function findAllValuesForEntries($handle, $entries) {
		if(!is_array($entries)) {
			if(is_numeric($entries)) {
				$tempEntries = $entries;
				unset($entries);
				$entries = array(0=>$tempEntries);
			} else {
				return false;
			}
		}
		static $cachedValues = array();
		$resultArray = array();
		global $xoopsDB;
		foreach($entries as $entry) {
			if(!isset($cachedValues[$field][$entry])) {
				$sql = "SELECT `$handle` FROM ".$xoopsDB->prefix("formulize_".$this->fid). " WHERE entry_id = ".intval($entry);
				if($res = $xoopsDB->query($sql)) {
					$array = $xoopsDB->fetchArray($res);
					$cachedValues[$field][$entry] = $array[$handle];
				} else {
					$cachedValues[$field][$entry] = false;
				}
			} 
			$resultArray[] = $cachedValues[$field][$entry];
		}
		return $resultArray;
	}
	
		
	function _buildScopeFilter($scope_uids, $scope_groups) {
		if(is_array($scope_uids)) {
			if(count($scope_uids) > 0) {
				$scopeFilter = " AND (creation_uid = " . implode(" OR creation_uid = ", $scope_uids) . ")";
			} else {
				$scopeFilter = "";
			}
		} elseif(is_array($scope_groups)) {
			if(count($scope_groups) > 0) {
			  $scopeFilter = " AND (t2.groupid IN (".implode(",", $scope_groups).") AND t2.entry_id=t1.entry_id AND t2.fid=".intval($this->fid).")";
			} else {
				$scopeFilter = "";
			}
		} else {
			$scopeFilter = "";
		}
		return $scopeFilter;
	}
	
	// derive the owner groups and write them to the owner groups table
	// $uids and $entryids can be parallel arrays with multiple users and entries
	// arrays must start with 0 key and increase sequentially (no gaps, no associative keys, etc)
	function setEntryOwnerGroups($uids, $entryids) {
		global $xoopsDB;
		if(!is_array($uids)) {
			$tempuids = $uids;
			$uids = array();
			$uids[] = $tempuids;
		}
		if(!is_array($entryids)) {
			$tempentryids = $entryids;
			$entryids = array();
			$entryids[] = $tempentryids;
		}
		if(count($uids) != count($entryids)) {
			return false;
		}
		$start = true;
		$ownerInsertSQL = "INSERT INTO " . $xoopsDB->prefix("formulize_entry_owner_groups") . " (`fid`, `entry_id`, `groupid`) VALUES ";
		for($i=0;$i<count($uids);$i++) { // loop through all the users
			$ownerGroups = array();
			if($uids[$i]) { // get the user's group
				$member_handler =& xoops_gethandler('member');
				$creationUser = $member_handler->getUser($uids[$i]);
				if(is_object($creationUser)) {
					$ownerGroups = $creationUser->getGroups();
				} else {
					$ownerGroups[] = XOOPS_GROUP_ANONYMOUS;
				}
			} else {
				$ownerGroups[] = XOOPS_GROUP_ANONYMOUS;
			}
			foreach($ownerGroups as $index=>$thisGroup) { // add this user's groups and this entry id to the insert statement
				if(!$start) { 
					$ownerInsertSQL .= ", "; // add a comma between successive inserts
				}
				$start = false;
				$ownerInsertSQL .= "('".$this->fid."', '".intval($entryids[$i])."', '".intval($thisGroup)."')";
			}
		}
		if(!$ownerInsertRes = $xoopsDB->query($ownerInsertSQL)) {
			return false;
		}
		return true;
	}
	
	// This function returns the entry_owner_groups for the given entry
	function getEntryOwnerGroups($entry_id) {
		static $cachedEntryOwnerGroups = array();
		if(!isset($cachedEntryOwnerGroups[$this->fid][$entry_id])) {
			global $xoopsDB;
			$sql = "SELECT groupid FROM ".$xoopsDB->prefix("formulize_entry_owner_groups") . " WHERE fid='".$this->fid."' AND entry_id='".intval($entry_id)."'";
			if($res = $xoopsDB->query($sql)) {
				$groupArray = array();
				while($row = $xoopsDB->fetchRow($res)) {
					$groupArray[] = $row[0];
				}
				$cachedEntryOwnerGroups[$this->fid][$entry_id]=$groupArray;
			} else {
				$cachedEntryOwnerGroups[$this->fid][$entry_id]=false;
			}	
		}
		return $cachedEntryOwnerGroups[$this->fid][$entry_id];
		
	}
	
	// This function writes a set of values to an entry
	// $values will be an array of element ids and prepared values
	// $proxyUser is optional and if present will override the current xoopsuser uid as the creation user
	// $updateMetadata is a flag to allow us to skip updating the modification user and time.  Introduced for when we update derived values and the mod user and time should not change.
	function writeEntry($entry, $values, $proxyUser=false, $forceUpdate=false, $updateMetadata=true) {

		global $xoopsDB, $xoopsUser;
		static $cachedMaps = array();
		// get handle/id equivalents directly from database in one query, since we'll need them later
		// much more efficient to do it this way than query for all the element objects, for instance.
		if(!isset($cachedMaps[$this->fid])) {
			$handleElementMapSQL = "SELECT ele_handle, ele_id FROM ".$xoopsDB->prefix("formulize") . " WHERE id_form=".intval($this->fid);
			if(!$handleElementMapRes = $xoopsDB->query($handleElementMapSQL)) {
				return false;
			}
			$handleElementMap = array();
			while($handleElementMapArray = $xoopsDB->fetchArray($handleElementMapRes)) {
				$handleElementMap[$handleElementMapArray['ele_id']] = $handleElementMapArray['ele_handle'];
			}
			$cachedMap[$this->fid] = $handleElementMap;
		}
		if(!isset($handleElementMap)) {
			$handleElementMap = $cachedMap[$this->fid];
		}
		
		// check for presence of ID or SEQUENCE and look up the values we'll need to write
		$lockIsOn = false;
		$idElements = array_keys($values, "{ID}");
		$seqElements = array_keys($values, "{SEQUENCE}");
		foreach($idElements as $idKey=>$thisIdElement) { // on some versions of PHP, you cannot use the third boolean param with array_keys and get a strict match, so we do a double check on what we found this way to enforce strict matching
			if($values[$thisIdElement] !== "{ID}") {
				unset($idElements[$idKey]);
			}
		}
		foreach($seqElements as $seqKey=>$thisSeqElement) {
			if($values[$thisSeqElement] !== "{SEQUENCE}") {
				unset($seqElements[$seqKey]);
			}
		}
    if(count($idElements)>0 OR count($seqElements)>0) {
      $lockIsOn = true;
      $xoopsDB->query("LOCK TABLES ".$xoopsDB->prefix("formulize_".$this->fid)." WRITE"); // need to lock table since there are multiple operations required on it for this one write transaction
			if(count($idElements)>0) {
				if($entry == "new") {
					$idMaxSQL = "SELECT MAX(entry_id) FROM " . $xoopsDB->prefix("formulize_".$this->fid);
					if($idMaxRes = $xoopsDB->query($idMaxSQL)) {
						$idMaxValue = $xoopsDB->fetchArray($idMaxRes);
						foreach($idElements as $key) {
							$values[$key] = $idMaxValue["MAX(entry_id)"] + 1;
						}
					} else {
						exit("Error: could not determine max value to use for {ID} elements.  SQL:<br>$idMaxSQL<br>");
					}
				} else {
					foreach($idElements as $key) {
						$values[$key] = $entry;
					}
				}
			}
			if(count($seqElements)>0) {
				foreach($seqElements as $seqElement) {
					$maxSQL = "SELECT MAX(`".$handleElementMap[$seqElement]."`) FROM ". $xoopsDB->prefix("formulize_".$this->fid);
					if($maxRes = $xoopsDB->query($maxSQL)) {
						$maxValue = $xoopsDB->fetchArray($maxRes);
						$values[$seqElement] = $maxValue["MAX(`".$handleElementMap[$seqElement]."`)"] + 1;
					} else {
						exit("Error: count not determine max value for use in element $seqElement.  SQL:<br>$maxSQL<br>");
					}
				}	
			}
		}

		// do the actual writing now that we have prepared all the info we need
		$uid = $xoopsUser ? $xoopsUser->getVar('uid') : 0;
		if($entry == "new") {
			$sql = "INSERT INTO ".$xoopsDB->prefix("formulize_".$this->fid)." (`creation_datetime`, `mod_datetime`, `creation_uid`, `mod_uid`";
			$sqlValues = "";
			foreach($values as $id=>$value) {
				$sql .= ", `".$handleElementMap[$id]."`";
				$sqlValues .= ", '".mysql_real_escape_string($value)."'";
			}
			$creation_uid = $proxyUser ? $proxyUser : $uid;
			$sql .= ") VALUES (NOW(), NOW(), ".intval($creation_uid).", ".intval($uid)."$sqlValues)";
			$entry_to_return = "";
		} else {
			$sql = "UPDATE " . $xoopsDB->prefix("formulize_".$this->fid) .  " SET ";
			$needComma = false;
			if($updateMetadata) {
				$sql .= "mod_datetime=NOW(), mod_uid=".intval($uid);
				$needComma = true;
			} 
			foreach($values as $id=>$value) {
				if($needComma) {
					$sql .= ", ";
				}
				$sql .= "`".$handleElementMap[$id]."` = '".mysql_real_escape_string($value)."'";
				$needComma = true;
			}
			$sql .= " WHERE entry_id=".intval($entry);
			$entry_to_return = intval($entry);
		}
		
		if($forceUpdate) {
			if(!$res = $xoopsDB->queryF($sql)) {
				exit("Error: your data could not be saved in the database.  This was the query that failed:<br>$sql<br>Query was forced and still failed so the SQL is probably bad.");
			}
		} elseif(!$res = $xoopsDB->query($sql)) {
			exit("Error: your data could not be saved in the database.  This was the query that failed:<br>$sql");
		}
		$lastWrittenId = $xoopsDB->getInsertId();
		if($lockIsOn) { $xoopsDB->query("UNLOCK TABLES"); }
		if($entry_to_return) { $this->updateCaches($entry_to_return); }
		return $entry_to_return ? $entry_to_return : $lastWrittenId;
	}
	
	// this function updates relevant caches after data has been updated in the database
	function updateCaches($id) {
		//so far, only metadata cache is affected
		$this->getEntryMeta($id, true);
	}
	
	// change radio button data to checkbox format
	// added to handle the situations where the radio button elements are converted to checkboxes
	// $element can be an id or an object
	function convertRadioDataToCheckbox($element) {
		if(!$element = _getElementObject($element)) {
			return false;
		}
		global $xoopsDB;
		// need to add *=+*: to the front of all the options
		$sql = "UPDATE ".$xoopsDB->prefix("formulize_".$this->fid). " SET `".$element->getVar('ele_handle')."` = CONCAT(\"*=+*:\", `".$element->getVar('ele_handle')."`)";
		if(!$res = $xoopsDB->queryF($sql)) {
			return false;
		}
		return true;
	}
	
  // change checkbox data to radio button format
	// added to handle the situations where the radio button elements are converted to checkboxes
	// $element can be an id or an object
	function convertCheckboxDataToRadio($element) {
		if(!$element = _getElementObject($element)) {
			return false;
		}
		global $xoopsDB;
		// need to remove *=+*: from the options, and put a comma in there instead, so it's one string and the "out of range" handling will pick it up and show it on screen
		// replace *=+*: in the field with ", " but only on the part of the string after the first five characters (which will omit the *=+*: that preceeds all items)
    $sql = "UPDATE ".$xoopsDB->prefix("formulize_".$this->fid). " SET `".$element->getVar('ele_handle')."` = REPLACE(RIGHT(`".$element->getVar('ele_handle')."`, CHAR_LENGTH(`".$element->getVar('ele_handle')."`)-5), \"*=+*:\", \", \")";
		if(!$res = $xoopsDB->queryF($sql)) {
			return false;
		}
		return true;
	}
	
	
}
	
