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
    $form_handler = xoops_getmodulehandler('forms', 'formulize');
    $sourceFormObject = $form_handler->get($sourceFid);
		$sourceDataSQL = "SELECT * FROM " . $xoopsDB->prefix("formulize_".$sourceFormObject->getVar('form_handle'));
		if(!$sourceDataRes = $xoopsDB->query($sourceDataSQL)) {
			return false;
		}
		$oldNewEntryIdMap = array();
				
		while($sourceDataArray = $xoopsDB->fetchArray($sourceDataRes)) {
			$start = true;
      		$formObject = $form_handler->get($this->fid);
			$insertSQL = "INSERT INTO " . $xoopsDB->prefix("formulize_" . $formObject->getVar('form_handle')) . " SET ";
			foreach($sourceDataArray as $field=>$value) {
				if($field == "entry_id") {
					$originalEntryId = $value;
					$value = ""; // use new ID numbers in the new table, in case there's ever a case where we're copying data into a table that already has data in it
				}
				if(isset($map[$field])) { $field = $map[$field]; } // if this field is in the map, then use the value from the map as the field name (this will match the field name in the cloned form)
				if(!$start) { $insertSQL .= ", "; }
				$insertSQL .= "`$field` = \"" . mysql_real_escape_string($value) . "\"";
				$start = false;
			}
			if(!$insertResult = $xoopsDB->queryF($insertSQL)) {
				return false;
			}
			$oldNewEntryIdMap[$originalEntryId] = $xoopsDB->getInsertId();
		}
		// copy the entry_owner_group info from the old form to the new form, for the corresponding entries
		foreach($oldNewEntryIdMap as $oldEntryId=>$newEntryId) {
			// make one SQL statement per entry id that grabs all the groupids for that old entry id and inserts them as records for the new form with the new entry id
			$sql = "INSERT INTO ".$xoopsDB->prefix("formulize_entry_owner_groups")." (`fid`, `entry_id`, `groupid`) SELECT ".$this->fid.", ".intval($newEntryId).", groupid FROM ".$xoopsDB->prefix("formulize_entry_owner_groups")." WHERE fid=".intval($sourceFid)." AND entry_id=".intval($oldEntryId);
			if(!$res = $xoopsDB->queryF($sql)) {
				print "<p>Error: could not insert entry-owner-group information with this SQL:<br>$sql<br><a href=\"mailto:formulize@freeformsolutions.ca\">Please contact Freeform Solutions</a> for assistance resolving this issue.</p>\n";
			}
		}
		// cache the maps of old/new fields and entry ids, so we can refer to them later if other forms are cloned with data and linked selectboxes need to have references updated
		$cacheFile = fopen(XOOPS_ROOT_PATH . "/cache/formulize_clonedFormMaps_".$sourceFid."_to_".$this->fid,"w");
		fwrite($cacheFile, serialize($map)."\r\n".serialize($oldNewEntryIdMap));
		fclose($cacheFile);
		// check of relinking of linked selectboxes was requested, and if so, grab the metadata from the new target form, and then reassign the element defintion to point there, and update all the recorded entry ids to match the corresponding entry ids in the new form
		if(isset($_POST['relink'])) {
			$elementsToRelink = array();
			foreach($_POST as $k=>$v) {
				if(substr($k,0,7)=="element") {
					$elementsToRelink[substr($k,7)] = $v; // keys are the elements to relink, values are the forms to relink them to
				}
			}
			foreach($elementsToRelink as $elementId=>$targetForm) {
				// 1. open up the metadata for the target form
				// 2. get and reinsert the element object after changing the ele_value[2]
				// 3. do SQL with the replace function, to change all the recorded entry_ids to the new ones
				
				// figure out current source form
				$element_handler = xoops_getmodulehandler('elements', 'formulize');
				$elementObject = $element_handler->get($elementId); // gets the element from the old form, that the user just clicked on to make a copy of
				$ele_value = $elementObject->getVar('ele_value');
				$boxproperties = explode("#*=:*", $ele_value[2]); // split the linked selectbox properties into target fid and target element handle
				$lines = file_get_contents(XOOPS_ROOT_PATH."/cache/formulize_clonedFormMaps_".$boxproperties[0]."_to_".$targetForm); // read the cached info about the cloning of the target form that happened in the past
        list($targetHandleMap, $targetEntryMap) = explode("\r\n", $lines); // get the mapping info about that cloning
				$targetHandleMap = unserialize($targetHandleMap);
				$targetEntryMap = unserialize($targetEntryMap);
				// get the corresponding element to this linked selectbox, from the form we just created in the cloning process
				$newElement = $element_handler->get($map[$elementObject->getVar('ele_handle')]);
				$newEleValue = $newElement->getVar('ele_value');
				$newEleValue[2] = $targetForm."#*=:*".$targetHandleMap[$boxproperties[1]]; // change the element pointer
				$newElement->setVar('ele_value', $newEleValue);				
				if(!$element_handler->insert($newElement)) { // update the element properties in the database
					print "Error: could not relink linked selectbox element ".$map[$elementObject->getVar('ele_handle')]." to the selected new target form.<br>";
					return false;
				}
				// go through the process of relinking all the entry references to the new entry ids in the new form
				// seems rather inefficient to loop through once for each pair of entries, but refactoring this to a more streamlined data approach will have to wait
				foreach($targetEntryMap as $oldId=>$newId) {
					$sql = "UPDATE ".$xoopsDB->prefix("formulize_".$formObject->getVar('form_handle')). " SET `".$map[$elementObject->getVar('ele_handle')]."` = REPLACE(`".$map[$elementObject->getVar('ele_handle')]."`,\",$oldId,\",\",$newId,\")";
					if(!$res = $xoopsDB->query($sql)) {
						print "Error: could not relink entries in newly created form (".$this->fid.") to the selected target form ($targetForm)<br>";
						return false;
					}
				}
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
    $form_handler = xoops_getmodulehandler('forms', 'formulize');
    $formObject = $form_handler->get($this->fid);
		$sql = "SELECT * FROM " . $xoopsDB->prefix("formulize_".$formObject->getVar('form_handle')) . " WHERE entry_id = $entry";
		if(!$res = $xoopsDB->query($sql)) {
			return false;
		}
		$data = $xoopsDB->fetchArray($res);
		$sql = "INSERT INTO " . $xoopsDB->prefix("formulize_".$formObject->getVar('form_handle')) . " SET ";
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
	// intended to be called once per pair of linked selectboxes involved in an entry cloning process
	function reassignLSB($sourceFid, $lsbElement, $entryMap) {
		global $xoopsDB;
		foreach($entryMap[$lsbElement->getVar('id_form')] as $originalEntry=>$newEntries) {
			foreach($newEntries as $newEntryNum=>$thisEntry) {
        $form_handler = xoops_getmodulehandler('forms', 'formulize');
        $formObject = $form_handler->get($this->fid);
				$sql = "SELECT `".$lsbElement->getVar('ele_handle') . "` FROM " . $xoopsDB->prefix("formulize_".$formObject->getVar('form_handle')) . " WHERE entry_id=$thisEntry";
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
				$sql = "UPDATE " . $xoopsDB->prefix("formulize_".$formObject->getVar('form_handle')) . " SET `".$lsbElement->getVar('ele_handle')."` = \",".implode(",",$newIds).",\" WHERE entry_id=$thisEntry";
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
    $form_handler = xoops_getmodulehandler('forms', 'formulize');
    $formObject = $form_handler->get($this->fid);
		$sql = "DELETE FROM " .$xoopsDB->prefix("formulize_".$formObject->getVar('form_handle')) . " WHERE entry_id = " . implode(" OR entry_id = ", $ids);
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
      $form_handler = xoops_getmodulehandler('forms', 'formulize');
      $formObject = $form_handler->get($this->fid);
			$sql = "SELECT entry_id FROM " . $xoopsDB->prefix("formulize_".$formObject->getVar('form_handle')) . " WHERE entry_id = " . intval($id);
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
      $form_handler = xoops_getmodulehandler('forms', 'formulize');
      $formObject = $form_handler->get($this->fid);
			$sql = "SELECT creation_datetime, mod_datetime, creation_uid, mod_uid FROM " . $xoopsDB->prefix("formulize_".$formObject->getVar('form_handle')) . " WHERE entry_id = " . intval($id);
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
    $form_handler = xoops_getmodulehandler('forms', 'formulize');
    $formObject = $form_handler->get($this->fid);
		$sql = "SELECT creation_uid FROM " . $xoopsDB->prefix("formulize_".$formObject->getVar('form_handle')) . " WHERE entry_id IN (" . implode(",", $ids) . ") $scopefilter";
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
    $form_handler = xoops_getmodulehandler('forms', 'formulize');
    $formObject = $form_handler->get($this->fid);
		$sql = "SELECT `". $element->getVar('ele_handle') . "` FROM " . $xoopsDB->prefix("formulize_".$formObject->getVar('form_handle')) . " WHERE entry_id = " . intval($id);
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
    $form_handler = xoops_getmodulehandler('forms', 'formulize');
    $formObject = $form_handler->get($this->fid);
		if(is_array($scope_uids) AND count($scope_uids)>0) {
			$scopeFilter = $this->_buildScopeFilter($scope_uids);
			$sql = "SELECT `". $element->getVar('ele_handle') . "` FROM " . $xoopsDB->prefix("formulize_".$formObject->getVar('form_handle')) . " WHERE entry_id = " . intval($id) . $scopeFilter;
		} elseif(is_array($scope_groups) AND count($scope_groups)>0) {
			$scopeFilter = $this->_buildScopeFilter("", $scope_groups);
			$sql = "SELECT `t1.". $element->getVar('ele_handle') . "` FROM " . $xoopsDB->prefix("formulize_".$formObject->getVar('form_handle')) . "AS t1, " . $xoopsDB->prefix("formulize_entry_owner_groups") . " AS t2 WHERE t1.entry_id = " . intval($id) . $scopeFilter;
		} else {
			$sql = "SELECT `". $element->getVar('ele_handle') . "` FROM " . $xoopsDB->prefix("formulize_".$formObject->getVar('form_handle')) . " WHERE entry_id = " . intval($id);
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
    $form_handler = xoops_getmodulehandler('forms', 'formulize');
    $formObject = $form_handler->get($this->fid);
		if(is_array($scope_uids) AND count($scope_uids) > 0) {
			$scopeFilter = $this->_buildScopeFilter($scope_uids);
			$sql = "SELECT entry_id FROM " . $xoopsDB->prefix("formulize_".$formObject->getVar('form_handle')) . " WHERE (creation_uid = " . implode(" OR creation_uid = ", $uids) . ") $scopeFilter ORDER BY entry_id";
		} elseif(is_array($scope_groups) AND count($scope_groups)>0) {
			$scopeFilter = $this->_buildScopeFilter("", $scope_groups);
			$sql = "SELECT t1.entry_id FROM " . $xoopsDB->prefix("formulize_".$formObject->getVar('form_handle')) . "AS t1, " . $xoopsDB->prefix("formulize_entry_owner_groups") . " AS t2 WHERE (t1.creation_uid = " . implode(" OR t1.creation_uid = ", $uids) . ") $scopeFilter ORDER BY t1.entry_id";
		} else {
			$sql = "SELECT entry_id FROM " . $xoopsDB->prefix("formulize_".$formObject->getVar('form_handle')) . " WHERE (creation_uid = " . implode(" OR creation_uid = ", $uids) . ") ORDER BY entry_id";
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
	function getFirstEntryForGroups($group_ids) {
		if(!is_array($group_ids)) {
			$group_ids = array(0=>intval($groupids));
		}
		
		global $xoopsDB;
    $form_handler = xoops_getmodulehandler('forms', 'formulize');
    $formObject = $form_handler->get($this->fid);
		$sql = "SELECT t1.entry_id FROM ". $xoopsDB->prefix("formulize_".$formObject->getVar('form_handle')) . " as t1, ". $xoopsDB->prefix("formulize_entry_owner_groups") ." as t2 WHERE t1.entry_id = t2.entry_id AND t2.fid=".$this->fid." AND t2.groupid IN (".implode(",",$group_ids).") ORDER BY t1.entry_id LIMIT 0,1";
		global $xoopsUser;
		if(!$res = $xoopsDB->query($sql)) {
			return false;
		}
		$row = $xoopsDB->fetchRow($res);
		return $row[0];
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
    $form_handler = xoops_getmodulehandler('forms', 'formulize');
    $formObject = $form_handler->get($this->fid);
		$sql = "SELECT entry_id FROM " . $xoopsDB->prefix("formulize_".$formObject->getVar('form_handle')) . " WHERE (creation_uid = " . implode(" OR creation_uid = ", $uids) . ") $scopeFilter ORDER BY entry_id LIMIT 0,1";
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
    $form_handler = xoops_getmodulehandler('forms', 'formulize');
    $formObject = $form_handler->get($this->fid);
		$sql = "SELECT entry_id FROM " . $xoopsDB->prefix("formulize_".$formObject->getVar('form_handle')) . " WHERE `". $element->getVar('ele_handle') . "` = \"" . mysql_real_escape_string($value) . "\" ORDER BY entry_id LIMIT 0,1";
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
    $form_handler = xoops_getmodulehandler('forms', 'formulize');
    $formObject = $form_handler->get($this->fid);
		$queryValue = "\"" . mysql_real_escape_string($value) . "\"";
		if($operator == "{LINKEDSEARCH}") {
			$operator = "LIKE";
			$queryValue = "\"%," . mysql_real_escape_string($value) . ",%\"";
		}
		if(is_array($scope_uids) AND count($scope_uids) > 0) {
			$scopeFilter = $this->_buildScopeFilter($scope_uids);
			$sql = "SELECT entry_id FROM " . $xoopsDB->prefix("formulize_".$formObject->getVar('form_handle')) . " WHERE `". $element->getVar('ele_handle') . "` $operator $queryValue $scopeFilter GROUP BY entry_id ORDER BY entry_id";
		} elseif(is_array($scope_groups) AND count($scope_groups)>0) {
			$scopeFilter = $this->_buildScopeFilter("", $scope_groups);
			$sql = "SELECT t1.entry_id FROM " . $xoopsDB->prefix("formulize_".$formObject->getVar('form_handle')) . " AS t1, " . $xoopsDB->prefix("formulize_entry_owner_groups") . " AS t2 WHERE t1.`". $element->getVar('ele_handle') . "` $operator $queryValue $scopeFilter GROUP BY t1.entry_id ORDER BY t1.entry_id";
		} else {
			$sql = "SELECT entry_id FROM " . $xoopsDB->prefix("formulize_".$formObject->getVar('form_handle')) . " WHERE `". $element->getVar('ele_handle') . "` $operator $queryValue GROUP BY entry_id ORDER BY entry_id";			
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
    $form_handler = xoops_getmodulehandler('forms', 'formulize');
    $formObject = $form_handler->get($this->fid);
		foreach($entries as $entry) {
			if(!isset($cachedValues[$field][$entry])) {
				$sql = "SELECT `$handle` FROM ".$xoopsDB->prefix("formulize_".$formObject->getVar('form_handle')). " WHERE entry_id = ".intval($entry);
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
	// all groups the user is a member of are written to the database, regardless of their current permission on the form
	// interpretation of permissions is to be done when reading this information, to allow for more flexibility
	function setEntryOwnerGroups($uids, $entryids, $update=false) {
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
		if($update) { // clear the ownership records for these entries first...
		  $ownerClearSQL = "DELETE FROM	".$xoopsDB->prefix("formulize_entry_owner_groups") . " WHERE `fid` = ".$this->fid." AND `entry_id` IN (";
		  $start = true;
		  foreach($entryids as $thisEntryId) {
			$ownerClearSQL .= $start ? intval($thisEntryId) : ", ".intval($thisEntryId);
			$start = false;
		  }
		  $ownerClearSQL .= ")";
		  if(!$clearResult = $xoopsDB->queryF($ownerClearSQL)) {
			return false;
		  }
		  // update the entry's creation and modification user ids
		  $form_handler = xoops_getmodulehandler('forms', 'formulize');
		  $formObject = $form_handler->get($this->fid);
		  for($i=0;$i<count($uids);$i++) { // loop through all the users
			$uidUpdateSQL = "UPDATE ".$xoopsDB->prefix("formulize_".$formObject->getVar('form_handle'))." SET `creation_uid` = ".intval($uids[$i])." WHERE `entry_id` = ".intval($entryids[$i]);
			if(!$uidUpdateResult = $xoopsDB->queryF($uidUpdateSQL)) {
				return false;
			}
		  }
		} 
		$start = true;
		$ownerInsertSQLArray = array();
		$ownerInsertSQLBase = "INSERT INTO " . $xoopsDB->prefix("formulize_entry_owner_groups") . " (`fid`, `entry_id`, `groupid`) VALUES ";
		$ownerInsertSQLCurrent = $ownerInsertSQLBase;
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
					$ownerInsertSQLCurrent .= ", "; // add a comma between successive inserts
				}
				$start = false;
				$ownerInsertSQLCurrent .= "('".$this->fid."', '".intval($entryids[$i])."', '".intval($thisGroup)."')";
			}
			if(strlen($ownerInsertSQLCurrent) > 250000) {
				$ownerInsertSQLArray[] = $ownerInsertSQLCurrent;
				$ownerInsertSQLCurrent = $ownerInsertSQLBase;
				$start = true;
			}
		}
		if(!$start) {
			$ownerInsertSQLArray[] = $ownerInsertSQLCurrent;	
		}
		foreach($ownerInsertSQLArray as $ownerInsertSQL) {
			if(!$ownerInsertRes = $xoopsDB->queryF($ownerInsertSQL)) {
				return false;
			}
		}
		return true;
	}
	
	// This function returns the entry_owner_groups for the given entry
	// if no entry is specified, then returns all groups that have entries on this form
	// remember that all groups the creator was a member of at the time of creation will be returned...interpretation of which groups are important must still be performed in logic once this info has been retrieved
	function getEntryOwnerGroups($entry_id=0) {
		static $cachedEntryOwnerGroups = array();
		$entry_id = intval($entry_id);
		if(!isset($cachedEntryOwnerGroups[$this->fid][$entry_id])) {
			global $xoopsDB;
			$entryFilter = $entry_id ? " AND entry_id='".intval($entry_id)."' " : ""; // when making strings that get dropped into others, good habit is to leave spaces at ends
			$sql = "SELECT DISTINCT(groupid) FROM ".$xoopsDB->prefix("formulize_entry_owner_groups") . " WHERE fid='".$this->fid."' $entryFilter ORDER BY groupid";
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
	// $values will be an array of element ids and prepared values, or handles and prepared values.  Array must use all ids as keys or all handles as keys!
	// $proxyUser is optional and if present will override the current xoopsuser uid as the creation user
	// $updateMetadata is a flag to allow us to skip updating the modification user and time.  Introduced for when we update derived values and the mod user and time should not change.
	function writeEntry($entry, $values, $proxyUser=false, $forceUpdate=false, $updateMetadata=true) {

		global $xoopsDB, $xoopsUser;
    $form_handler = xoops_getmodulehandler('forms', 'formulize');
    $formObject = $form_handler->get($this->fid);
		static $cachedMaps = array();
		$mapIDs = true; // assume we're mapping elements based on their IDs, because the values array is based on ids as keys
		foreach(array_keys($values) as $thisKey) { // check the values array keys
			if(!is_numeric($thisKey)) { // if we find a non numeric key, then we must map based on handles instead
				$mapIDs = false;
				break;
			}
		}
		
		// get handle/id equivalents directly from database in one query, since we'll need them later
		// much more efficient to do it this way than query for all the element objects, for instance.
		if(!isset($cachedMaps[$this->fid][$mapIDs])) {
			$handleElementMapSQL = "SELECT ele_handle, ele_id, ele_encrypt FROM ".$xoopsDB->prefix("formulize") . " WHERE id_form=".intval($this->fid);
			if(!$handleElementMapRes = $xoopsDB->query($handleElementMapSQL)) {
				return false;
			}
			$handleElementMap = array();
			$encryptElementMap = array();
			while($handleElementMapArray = $xoopsDB->fetchArray($handleElementMapRes)) {
				switch($mapIDs) {
					case true:
						$handleElementMap[$handleElementMapArray['ele_id']] = $handleElementMapArray['ele_handle'];
						$encryptElementMap[$handleElementMapArray['ele_id']] = $handleElementMapArray['ele_encrypt'];
						break;
					case false:
						$handleElementMap[$handleElementMapArray['ele_handle']] = $handleElementMapArray['ele_handle'];
						$encryptElementMap[$handleElementMapArray['ele_handle']] = $handleElementMapArray['ele_encrypt'];
						break;
				}
			}
			$cachedMap[$this->fid][$mapIDs] = $handleElementMap;
		}
		if(!isset($handleElementMap)) {
			$handleElementMap = $cachedMap[$this->fid][$mapIDs];
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
      $xoopsDB->query("LOCK TABLES ".$xoopsDB->prefix("formulize_".$formObject->getVar('form_handle'))." WRITE"); // need to lock table since there are multiple operations required on it for this one write transaction
			if(count($idElements)>0) {
				if($entry == "new") {
					$idMaxSQL = "SELECT MAX(entry_id) FROM " . $xoopsDB->prefix("formulize_".$formObject->getVar('form_handle'));
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
					$maxSQL = "SELECT MAX(`".$handleElementMap[$seqElement]."`) FROM ". $xoopsDB->prefix("formulize_".$formObject->getVar('form_handle'));
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
			$sql = "INSERT INTO ".$xoopsDB->prefix("formulize_".$formObject->getVar('form_handle'))." (`creation_datetime`, `mod_datetime`, `creation_uid`, `mod_uid`";
			$sqlValues = "";
			foreach($values as $id=>$value) {
				$sql .= ", `".$handleElementMap[$id]."`";
				if($encryptElementMap[$id]) {
					$value = $value === "{WRITEASNULL}" ? "" : $value;
					$sqlValues .= ", AES_ENCRYPT('".mysql_real_escape_string($value)."', '".getAESPassword()."')";
				} elseif($value === "{WRITEASNULL}") {
					$sqlValues .= ", NULL";
				} else {
					$sqlValues .= ", '".mysql_real_escape_string($value)."'";
				}
			}
			$creation_uid = $proxyUser ? $proxyUser : $uid;
			$sql .= ") VALUES (NOW(), NOW(), ".intval($creation_uid).", ".intval($uid)."$sqlValues)";
			$entry_to_return = "";
		} else {
			$sql = "UPDATE " . $xoopsDB->prefix("formulize_".$formObject->getVar('form_handle')) .  " SET ";
			$needComma = false;
			if($updateMetadata) {
				$sql .= "mod_datetime=NOW(), mod_uid=".intval($uid);
				$needComma = true;
			} 
			foreach($values as $id=>$value) { // note, id might be handle or element id...this is why we choose how we're going to map the info above (at one time, it had to be element id, hence the name)
				if($needComma) {
					$sql .= ", ";
				}
				if($encryptElementMap[$id]) {
					$value = $value === "{WRITEASNULL}" ? "" : $value;
					$sql .= "`".$handleElementMap[$id]."` = AES_ENCRYPT('".mysql_real_escape_string($value)."', '".getAESPassword()."')";
				} elseif($value === "{WRITEASNULL}") {
					$sql .= "`".$handleElementMap[$id]."` = NULL";
				} else {
					$sql .= "`".$handleElementMap[$id]."` = '".mysql_real_escape_string($value)."'";
				}
				$needComma = true;
			}
			$sql .= " WHERE entry_id=".intval($entry);
			$entry_to_return = intval($entry);
		}
		
		if($forceUpdate) {
			if(!$res = $xoopsDB->queryF($sql)) {
				exit("Error: your data could not be saved in the database.  This was the query that failed:<br>$sql<br>Query was forced and still failed so the SQL is probably bad.<br>".mysql_error());
			}
		} elseif(!$res = $xoopsDB->query($sql)) {
			exit("Error: your data could not be saved in the database.  This was the query that failed:<br>$sql<br>".mysql_error());
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
    $form_handler = xoops_getmodulehandler('forms', 'formulize');
    $formObject = $form_handler->get($this->fid);
		// need to add *=+*: to the front of all the options
		$sql = "UPDATE ".$xoopsDB->prefix("formulize_".$formObject->getVar('form_handle')). " SET `".$element->getVar('ele_handle')."` = CONCAT(\"*=+*:\", `".$element->getVar('ele_handle')."`)";
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
    $form_handler = xoops_getmodulehandler('forms', 'formulize');
    $formObject = $form_handler->get($this->fid);
		// need to remove *=+*: from the options, and put a comma in there instead, so it's one string and the "out of range" handling will pick it up and show it on screen
		// replace *=+*: in the field with ", " but only on the part of the string after the first five characters (which will omit the *=+*: that preceeds all items)
    $sql = "UPDATE ".$xoopsDB->prefix("formulize_".$formObject->getVar('form_handle')). " SET `".$element->getVar('ele_handle')."` = REPLACE(RIGHT(`".$element->getVar('ele_handle')."`, CHAR_LENGTH(`".$element->getVar('ele_handle')."`)-5), \"*=+*:\", \", \")";
		if(!$res = $xoopsDB->queryF($sql)) {
			return false; 
		}
		return true;
	}
	
	// this method does some operations on the database to convert the values in a field to/from encrypted status
	// note that $elementHandle is the current value of the ele_handle, and we can't go off the element object's properties, since the element is in the middle of being updated in element_save.php when this method is called
	function toggleEncryption($elementHandle, $currentEncryptionStatus) {
		if(!$element = _getElementObject($elementHandle)) {
			return false;
		}
		// 1. rename to the current field
		// 2. create a new field to put this data in
		// 3. run the encrypt/decrypt SQL to populate the new field
		// 4. drop the old field
		$form_handler = xoops_getmodulehandler('forms', 'formulize');
		if(!$form_handler->updateField($element, $elementHandle, false, $elementHandle."qaz")) { // false is no datatype change, last param is the new name
			return false;
		}
		if($currentEncryptionStatus) {
			$dataType = 'text';
			$aesFunction = 'AES_DECRYPT';
		} else {
			$dataType = 'blob';
			$aesFunction = 'AES_ENCRYPT';			
		}
		global $xoopsDB;
		$formObject = $form_handler->get($element->getVar('id_form'));
		$insertFieldSQL = "ALTER TABLE " . $xoopsDB->prefix("formulize_" . $formObject->getVar('form_handle')) . " ADD `$elementHandle` $dataType NULL default NULL";
		if(!$insertFieldRes = $xoopsDB->queryF($insertFieldSQL)) {
			return false;
		}
		$toggleSQL = "UPDATE " . $xoopsDB->prefix("formulize_" . $formObject->getVar('form_handle')) . " SET `$elementHandle` = ".$aesFunction."(".$elementHandle."qaz, '".getAESPassword()."')";
		if(!$toggleSQLRes = $xoopsDB->queryF($toggleSQL)) {
			return false;
		}
		$dropSQL = "ALTER TABLE " . $xoopsDB->prefix("formulize_" . $formObject->getVar('form_handle')) . " DROP ".$elementHandle."qaz";
		if(!$dropSQLRes = $xoopsDB->queryF($dropSQL)) {
			return false;
		}
		return true;
	}
	
	// this function changes selected options that users have made in radio buttons, checkboxes or selectboxes so that they match new options specified by the user...ie: old first option converted to new first option, etc
	// newValues is the array that is about to be passed in as the new $ele_value[2], which is the array of options
	// element_id_or_handle is the element we're working with, cannot pass in object!
	// this function must be called prior to the new values actually being inserted to the element in the DB, since this function retrieves the current options for the element from the db in order to make a comparison
	function changeUserSubmittedValues($element_id_or_handle, $newValues) {
		if(!$element = _getElementObject($element_id_or_handle)) {
			return false;
		}
		
		// multiple selection elements have data saved with the special prefix to separate values in the cell:  *=+*:
		// we need to determine if this element allows multiple values and prepare to handle it
		$ele_type = $element->getVar('ele_type');
		$ele_value = $element->getVar('ele_value');
		switch($ele_type) {
			case "check":
			case "radio":
				$oldValues = array_keys($ele_value);
				break;
			case "select":
				$oldValues = array_keys($ele_value[2]);
				// special check...if this is a linked selectbox or a fullnames/usernames selectbox, then fail
				if(!is_array($ele_value[2]) OR isset($ele_value[2]["{FULLNAMES}"]) OR isset($ele_value[2]["{USERNAMES}"])) {
					return false;
				}
				break;
		}
		$prefix = ($ele_type == "check" OR ($ele_type == "select" AND $ele_value[1])) ? "#*=:*" : ""; // multiple selection possible? if so, setup prefix
		$newValues = array_keys($newValues);
		global $xoopsDB;
    $form_handler = xoops_getmodulehandler('forms', 'formulize');
    $formObject = $form_handler->get($this->fid);
		$sql = "SELECT `entry_id`, `".$element->getVar('ele_handle')."` FROM ".$xoopsDB->prefix("formulize_".$formObject->getVar('form_handle'));
		if(!$res = $xoopsDB->query($sql)) {
			return false;
		}
		$updateSql = array();
		while($array = $xoopsDB->fetchArray($res)) {
			// do a search/replace inside the returned value, then construct one insert for each (yuck)
			// necessary to do the search/replace inside PHP where we have more control, since the possible matching conditions when multiple options can be selected, are prohibitively difficult (impossible?) to capture.
			if($prefix) {
				$currentValues = explode($prefix, ltrim($array[$element->getVar('ele_handle')], $prefix)); // since prefix is at the beginning of the string, we need to remove it before doing the explode
			} else {
				$currentValues = array(0=>$array[$element->getVar('ele_handle')]);				
			}
			for($i=0;$i<count($newValues);$i++) {
				if($newValues[$i] === $oldValues[$i]) { // ignore values that haven't changed
					continue;
				}
				$foundIndex = array();
				$key = array_search($oldValues[$i], $currentValues); 
				if($key !== false AND !isset($foundIndex[$key])) { // if we find one of the old values in the current values, then swap in the new value it should have
					// need to check that the match wasn't a 0 or on a string, etc...cannot use strict matching in array_search since that screws up all matches since the values don't really have their correct type owing to having been spun through lots of functions by now
					if(!is_numeric($currentValues[$key]) AND $oldValues[$i] == '0') { continue; }
					$currentValues[$key] = $newValues[$i];
					$foundIndex[$key] = true;
					if(count($foundIndex)==count($currentValues)) {
						break; // all currentValues have been replaced, so let's move on
					}
				}
			}
			if($prefix) {
				$replacementString = $prefix . implode($prefix,$currentValues); // put prefix back at the beginning after the implode
			} else {
				$replacementString = $currentValues[0];
			}
			$updateSql[] = "UPDATE ".$xoopsDB->prefix("formulize_".$formObject->getVar('form_handle'))." SET `".$element->getVar('ele_handle')."` = '".mysql_real_escape_string($replacementString)."' WHERE entry_id = ".$array['entry_id'];
		}
		if(count($updateSql) > 0) { // if we have some SQL generated, then run it.
			foreach($updateSql as $thisSql) {
				//print $thisSql."<br>";
				if(!$res = $xoopsDB->query($thisSql)) {
					return false;
				}
			}
		}
		return true;
	}
	
}
	
