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
	var $metadataFields; //
    var $metadataFieldTypes;
    var $dataTypeMap; // an array of field, data type pairs, generated when data is written to the DB

	// $fid must be an id
	function __construct($fid){
		$form_handler = xoops_getmodulehandler('forms', 'formulize');
		if(is_object($formObject = $form_handler->get($fid))) {
			$this->fid = intval($fid);
		} else {
			$this->fid = false;
		}
        $this->dataTypeMap = array();
		
		//set the available metadata fields to a global
		$this->metadataFields = array("entry_id",
		  						"creation_datetime",
		   						"creation_uid",
		   						"creator_email",
		  						"mod_datetime",
		   						"mod_uid");

        $this->metadataFieldTypes = array("entry_id" => "text",
                                    "mod_uid" => "text",
                                    "creation_uid" => "text",
                                    "creator_email" => "text",
                                    "mod_datetime" => "date",
                                    "creation_datetime" => "date");
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
					continue; // use new ID numbers in the new table, don't include entry id in the SQL statement. 
				}
				if(isset($map[$field])) { $field = $map[$field]; } // if this field is in the map, then use the value from the map as the field name (this will match the field name in the cloned form)
				if(!$start) { $insertSQL .= ", "; }
				$insertSQL .= "`$field` = \"" . formulize_db_escape($value) . "\"";
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
				print "<p>Error: could not insert entry-owner-group information with this SQL:<br>$sql<br><a href=\"mailto:info@formulize.org\">Please contact info@formulize.org</a> for assistance resolving this issue.</p>\n";
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
	function cloneEntry($entry, $callback = null) {
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

		if (function_exists($callback)) {
			$data = $callback($data);
		}

		unset($data['entry_id']);
		unset($data['creation_datetime']);
		unset($data['mod_datetime']);
		unset($data['creation_uid']);
		unset($data['mod_uid']);
		return $this->writeEntry("new", $data, false, true); // no proxy user (use current xoopsuser, the default behaviour), do force the creation of the entry if we're on a GET request
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
				
				if(count($newIds) > 1) {
					$newEleHandleValue = "\",".implode(",",$newIds).",\"";
				} else {
					$newEleHandleValue = $newIds[0];
				}
				
				$sql = "UPDATE " . $xoopsDB->prefix("formulize_".$formObject->getVar('form_handle')) . " SET `".$lsbElement->getVar('ele_handle')."` = $newEleHandleValue WHERE entry_id=$thisEntry";
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
		$ids = array_map(array($xoopsDB, 'escape'), $ids);
    $form_handler = xoops_getmodulehandler('forms', 'formulize');
    $formObject = $form_handler->get($this->fid);
		$sql = "DELETE FROM " .$xoopsDB->prefix("formulize_".$formObject->getVar('form_handle')) . " WHERE entry_id = " . implode(" OR entry_id = ", $ids);
		if(!$deleteSuccess = $xoopsDB->query($sql)) {
			return false;
		}
		$sql = "DELETE FROM " . $xoopsDB->prefix("formulize_entry_owner_groups") . " WHERE fid=".formulize_db_escape($this->fid)." AND (entry_id = " . implode(" OR entry_id = ", $ids) . ")";
		if(!$deleteOwernshipSuccess = $xoopsDB->query($sql)) {
			print "Error: could not delete entry ownership information for form ". formulize_db_escape($this->fid) . ", entries: " . implode(", ", $ids) . ". Check the DB queries debug info for details.";
		}
		if($formObject->getVar('store_revisions')) {
			global $xoopsUser;
			$uid = $xoopsUser ? $xoopsUser->getVar('uid') : 0;
			$context = serialize(array("get"=>$_GET, "post"=>$_POST));
			foreach($ids as $id) {
				$sql = "INSERT INTO " . $xoopsDB->prefix("formulize_deletion_logs") . " (form_id, entry_id, user_id, context) VALUES (" . formulize_db_escape($this->fid) . ", " . $id . ", " . formulize_db_escape($uid) . ", \"" . formulize_db_escape($context) . "\")";
				if(!$deleteLoggingSuccess = $xoopsDB->query($sql)) {
					print "Error: could not insert delete log entry information for form " . formulize_db_escape($this->fid) . ", entry " . $id . ", user " . formulize_db_escape($uid) . ". Check the DB queries debug info for details.";
				}
			}
			unset($id);
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
	function findFirstEntryWithValue($element_id, $value, $op="=") {
		if(!$element = _getElementObject($element_id)) {
			return false;
		}
        $likeBits = $op == "LIKE" ? "%" : "";
		global $xoopsDB;
        $form_handler = xoops_getmodulehandler('forms', 'formulize');
        $formObject = $form_handler->get($this->fid);
        $sql = "SELECT entry_id FROM " . $xoopsDB->prefix("formulize_".$formObject->getVar('form_handle')) . " WHERE `". $element->getVar('ele_handle') . "` ".formulize_db_escape($op)." \"$likeBits" . formulize_db_escape($value) . "$likeBits\" ORDER BY entry_id LIMIT 0,1";
		if(!$res = $xoopsDB->query($sql)) {
			return false;
		}
        if($xoopsDB->getRowsNum($res)==0) {
            return false;
        }
		$row = $xoopsDB->fetchRow($res);
		return $row[0];
	}
		
    // this function returns the entry ID of the first entry found in the form with all the specified values in the specified elements
    // $values is a key value pair of element handles and values
	function findFirstEntryWithAllValues($values, $op="=") {
        $likeBits = $op == "LIKE" ? "%" : "";
		global $xoopsDB;
        $form_handler = xoops_getmodulehandler('forms', 'formulize');
        $formObject = $form_handler->get($this->fid);
        $sql = "SELECT entry_id FROM " . $xoopsDB->prefix("formulize_".$formObject->getVar('form_handle')) . " WHERE ";
        $valuesSQL = array();
        foreach($values as $elementIdOrHandle=>$value) {
            if(!$element = _getElementObject($elementIdOrHandle)) {
                continue;
            }
            $valuesSQL[] = "`". $element->getVar('ele_handle') . "` ".formulize_db_escape($op)." \"$likeBits" . formulize_db_escape($value) . "$likeBits\"";
        }
        $sql .= implode(' AND ', $valuesSQL)." ORDER BY entry_id LIMIT 0,1";
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
		$queryValue = "\"" . formulize_db_escape($value) . "\"";
		if(is_array($scope_uids) AND count($scope_uids) > 0) {
			$scopeFilter = $this->_buildScopeFilter($scope_uids, array());
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
			if(!isset($cachedValues[$handle][$entry])) {
				$sql = "SELECT `$handle` FROM ".$xoopsDB->prefix("formulize_".$formObject->getVar('form_handle')). " WHERE entry_id = ".intval($entry);
				if($res = $xoopsDB->query($sql)) {
					$array = $xoopsDB->fetchArray($res);
					$cachedValues[$handle][$entry] = $array[$handle];
				} else {
					$cachedValues[$handle][$entry] = false;
				}
			} 
			$resultArray[] = $cachedValues[$handle][$entry];
		}
		return $resultArray;
	}
	
	// this function returns all the values of a given field
	function findAllValuesForField($handle, $sort="") {
		static $cachedValues = array();
		global $xoopsDB;
		if(!isset($cachedValues[$handle]) AND $this->fid) {
			if($sort=="ASC") {
				$sort = " ORDER BY `$handle` ASC";
			} elseif($sort =="DESC") {
				$sort = " ORDER BY `$handle` DESC";
			}
			$form_handler = xoops_getmodulehandler('forms', 'formulize');
			$formObject = $form_handler->get($this->fid);
			$sql = "SELECT `$handle`, `entry_id` FROM ".$xoopsDB->prefix("formulize_".$formObject->getVar('form_handle')).$sort;
			if($res = $xoopsDB->query($sql)) {
				while($array = $xoopsDB->fetchArray($res)) {
					$cachedValues[$handle][$array['entry_id']] = $array[$handle];	
				}
			} else {
				$cachedValues[$handle] = false;
			}
		} else {
			$cachedValues[$handle] = false; // fid passed into constructor was not valid!
		}
		return $cachedValues[$handle];
	}
		
		
	function _buildScopeFilter($scope_uids, $scope_groups=array()) {
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
        if($entryids === false) {
            return false;
        }
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
        if($entry_id == 'new') {
            global $xoopsUser;
            return $xoopsUser ? $xoopsUser->getGroups() : array(XOOPS_GROUP_ANONYMOUS);
        }
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
	// $update_metadata is a flag to allow us to skip updating the modification user and time.  Introduced for when we update derived values and the mod user and time should not change.
	function writeEntry($entry, $values, $proxyUser=false, $forceUpdate=false, $update_metadata=true) {

		global $xoopsDB, $xoopsUser;
		$uid = $xoopsUser ? $xoopsUser->getVar('uid') : 0;
		$form_handler = xoops_getmodulehandler('forms', 'formulize');
		$formObject = $form_handler->get($this->fid);
		$creation_uid = is_numeric($proxyUser) ? intval($proxyUser) : intval($uid);
		static $cachedMaps = array();
        static $cachedDataTypeMaps = array();
		$mapIDs = true; // assume we're mapping elements based on their IDs, because the values array is based on ids as keys
		foreach(array_keys($values) as $thisKey) { // check the values array keys
			if(!is_numeric($thisKey)) { // if we find a non numeric key, then we must map based on handles instead
				$mapIDs = false;
				break;
			}
		}

        $encrypt_element_handles = array(); // array of element handles which should be encrypted

		// get handle/id equivalents directly from database in one query, since we'll need them later
		// much more efficient to do it this way than query for all the element objects, for instance.
		if(!isset($cachedMaps[$this->fid][$mapIDs])) {
			$handleElementMapSQL = "SELECT ele_handle, ele_id, ele_encrypt FROM ".$xoopsDB->prefix("formulize") . " WHERE id_form=".intval($this->fid);
			if(!$handleElementMapRes = $xoopsDB->query($handleElementMapSQL)) {
				return false;
			}
			$handleElementMap = array();
			while($handleElementMapArray = $xoopsDB->fetchArray($handleElementMapRes)) {
				switch($mapIDs) {
					case true:
						$handleElementMap[$handleElementMapArray['ele_id']] = $handleElementMapArray['ele_handle'];
						break;
					case false:
						$handleElementMap[$handleElementMapArray['ele_handle']] = $handleElementMapArray['ele_handle'];
						break;
				}
                if ($handleElementMapArray['ele_encrypt']) {
                    // if this element should be encrypted in the database, save its handle into the encrypted element handle array
                    $encrypt_element_handles[] = $handleElementMapArray['ele_handle'];
                }
			}
			$cachedMaps[$this->fid][$mapIDs] = $handleElementMap;
            
            // also, gather once all the data types for the fid in question
            $cachedDataTypeMaps[$this->fid] = $this->gatherDataTypes(); // cannot write this directly to the object property, do that below, because statics live in this method and ARE SHARED ACROSS ALL OBJECTS. Properties are unique to the object, so we must instantiate this as a static, same as the element maps, then assign to the property below.
		}
		if(!isset($handleElementMap)) {
			$handleElementMap = $cachedMaps[$this->fid][$mapIDs];
		}
        if(count($this->dataTypeMap)==0) {
            $this->dataTypeMap = $cachedDataTypeMaps[$this->fid]; // now assign the value of the property, based on the cached static array
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
					$idMaxSQL = "SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA = '".XOOPS_DB_NAME."' AND TABLE_NAME = '".$xoopsDB->prefix("formulize_".$formObject->getVar('form_handle'))."'";
					if($idMaxRes = $xoopsDB->query($idMaxSQL)) {
						$idMaxValue = $xoopsDB->fetchArray($idMaxRes);
						foreach($idElements as $key) {
							$values[$key] = $idMaxValue['AUTO_INCREMENT'];
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

        // convert the value array keys from IDs to handles
        $element_values = array();
        foreach ($values as $key => $value) {
            $element_values[$handleElementMap[$key]] = $value;
        }

        // if it is a "new" entry, set default values
        if(!is_numeric($entry) AND $entry == "new") {
            $defaultValueMap = getEntryDefaults($this->fid, $entry);
            foreach($defaultValueMap as $defaultValueElementId=>$defaultValueToWrite) {
                if($defaultValueElementId) {
                    $hemKey = $defaultValueElementId;
                    if(!$mapIDs) {
                        $handles = convertElementIdsToElementHandles(array($defaultValueElementId));
                        $hemKey = $handles[0];
                    } 
                    if(!isset($element_values[$handleElementMap[$hemKey]])) {
                        $element_values[$handleElementMap[$hemKey]] = $defaultValueToWrite;
                    }
                }
            }
        }
            
        // call a hook which can modify the values before saving
        list($element_values, $existing_values) = $formObject->onBeforeSave($entry, $element_values);

        // ensure the hook has not created any invalid element handles because that would cause the sql query to fail
        // note that array_flip means both arrays use element handles as keys. values from the second array are ignored in the intersect
        $element_values = array_intersect_key($element_values, array_flip($handleElementMap));

        $clean_element_values = $element_values; // save a clean copy of the original values before the escaping for writing to DB, so we can use these later in "on after save"
        
        foreach($existing_values as $existingHandle=>$existingValue) {
            if($element_values[$existingHandle] === $existingValue) {
                unset($element_values[$existingHandle]); // don't write things that are unchanged from their current state in the database
            }
        }
        
        if (0 == count($element_values)) {
            // no values to save, which is probably caused by the onBeforeSave() handler deleting all of the values
            return null;
        }

        // escape field names and values before writing to database
        $aes_password = getAESPassword();
        foreach ($element_values as $key => $value) {
            $encrypt_this = in_array($key, $encrypt_element_handles);
            unset($element_values[$key]);   // since field name is not escaped, remove from array
            $key = "`".formulize_db_escape($key)."`";                // escape field name
            $element_values[$key] = $this->formatValueForQuery($key, $value);
            
            if ($encrypt_this) {
                // this element should be encrypted. note that the actual value is quoted and escapted already
                $element_values[$key] = "AES_ENCRYPT({$element_values[$key]}, '$aes_password')";
            }
        }

        if ($update_metadata or "new" == $entry) {
            // update entry metadata
            $element_values["`mod_datetime`"]   = "NOW()";
            $element_values["`mod_uid`"]        = intval($uid);
        }
        
        // do the actual writing now that we have prepared all the info we need
        if ($entry == "new") {
            // set metadata for new record
            $element_values["`creation_datetime`"]  = "NOW()";
            $element_values["`creation_uid`"]       = intval($creation_uid);
            if($uid==0) {
                foreach($_SESSION as $sessionVariable=>$value) {
                    if(substr($sessionVariable, 0, 19) == 'formulize_passCode_' AND is_numeric(str_replace('formulize_passCode_', '', $sessionVariable))) {
                        
                        $sid = str_replace('formulize_passCode_', '', $sessionVariable);
                        $screen_handler = xoops_getmodulehandler('screen','formulize');
                        $screenObject = $screen_handler->get($sid);
                        $passcodeFid = $screenObject->getVar('fid');
                        if(in_array('anon_passcode_'.$passcodeFid, $handleElementMap)) { // passcode field exists in this data table, so we need to write the passcode to the entry
                            $element_values['anon_passcode_'.$passcodeFid] = $this->formatValueForQuery('anon_passcode_'.$sid, $value);
                        }
                    }
                }
            }

            // write sql statement to insert new entry
            $sql = "INSERT INTO ".$xoopsDB->prefix("formulize_".$formObject->getVar('form_handle'))." (".
                implode(", ", array_keys($element_values)).") VALUES (".implode(", ", array_values($element_values)).")";
            $entry_to_return = "";
        } else {
            if (!function_exists("make_sql_set_values")) {
                function make_sql_set_values($field, $value) {
                    return "$field = $value";
                }
            }
            $sql_set_values = array_map("make_sql_set_values", array_keys($element_values), array_values($element_values));

            // write sql statement to update entry
            $sql = "UPDATE " . $xoopsDB->prefix("formulize_".$formObject->getVar('form_handle')) .  " SET ".implode(", ", $sql_set_values).
                " WHERE entry_id = ".intval($entry);
            $entry_to_return = intval($entry);
        }

        formulize_updateRevisionData($formObject, $entry_to_return, $forceUpdate);
        
		if($forceUpdate) {
			if(!$res = $xoopsDB->queryF($sql)) {
				exit("Error: your data could not be saved in the database.  This was the query that failed:<br>$sql<br>Query was forced and still failed so the SQL is probably bad.<br>".$xoopsDB->error());
			}
		} elseif(!$res = $xoopsDB->query($sql)) {
			exit("Error: your data could not be saved in the database.  This was the query that failed:<br>$sql<br>".$xoopsDB->error());
		}
		$lastWrittenId = $xoopsDB->getInsertId();
		if($lockIsOn) {
            $xoopsDB->query("UNLOCK TABLES");
        }
		if($entry_to_return) {
            $this->updateCaches($entry_to_return);
        }

		// remove any entry-editing lock that may be in place for this record, since it was just saved successfully...a new lock can now be placed on the entry the next time any element from the form, for this entry, is rendered.
		if($entry != "new") {
            $lock_file_name = XOOPS_ROOT_PATH."/modules/formulize/temp/entry_".intval($entry)."_in_form_".$formObject->getVar('id_form')."_is_locked_for_editing";
            if (file_exists($lock_file_name))
                unlink($lock_file_name);
		}

        $entry_to_return = $entry_to_return ? $entry_to_return : $lastWrittenId;
        $formObject->onAfterSave($entry_to_return, $clean_element_values, $existing_values);

		return $entry_to_return;
	}

    // check a given field against the dataTypeMap, return true if it's a numeric type
    function dataTypeIsNumeric($key) {
        if(count($this->dataTypeMap)==0) {
            $this->dataTypeMap = $this->gatherDataTypes();
        }
        $key = trim($key, "`");
        if(stripos($this->dataTypeMap[$key], "int") !== false
           OR stripos($this->dataTypeMap[$key], "decimal") !== false
           OR stripos($this->dataTypeMap[$key], "numeric") !== false
           OR stripos($this->dataTypeMap[$key], "float") !== false
           OR stripos($this->dataTypeMap[$key], "double") !== false) {
            return true; // yes it is
        }
        return false; // no it's not
    }
    
    function gatherDataTypes() {
        if(count($this->dataTypeMap)!=0) {
            return $this->dataTypeMap;
        }
        global $xoopsDB;
        $dataTypeMap = array();
        $form_handler = xoops_getmodulehandler('forms', 'formulize');
        $formObject = $form_handler->get($this->fid);
        $dataTypeSQL = "SELECT information_schema.columns.data_type, information_schema.columns.column_name FROM information_schema.columns WHERE information_schema.columns.table_schema = '".SDATA_DB_NAME."' AND information_schema.columns.table_name = '".$xoopsDB->prefix("formulize_".$formObject->getVar('form_handle'))."'";
        if($dataTypeRes = $xoopsDB->query($dataTypeSQL)) {
            while($dataTypeRow = $xoopsDB->fetchRow($dataTypeRes)) {
                $dataTypeMap[$dataTypeRow[1]] = $dataTypeRow[0];
            }
        } else {
            print "Error: could not retrieve datatypes for form ".$this->fid." with this SQL: $dataTypeSQL<br>".$xoopsDB->error();
            exit();
        }
        return $dataTypeMap;
    }
    
    // format a given value for inclusion in a DB insert or update query, based on the data type of the element, and the numeric or strig value of the data
    function formatValueForQuery($field, $value) {
        if ("{WRITEASNULL}" === $value or null === $value) {
            return "NULL";
        } else {
            // if this element has a numeric type in the DB, no quotes
            if($this->dataTypeIsNumeric($field)) {
                if(is_numeric($value)) {
                    return formulize_db_escape($value);
                } else { // non numeric values cannot be written to a numeric field, so NULL them
                    return "NULL";
                }
            } else {
                return "'".formulize_db_escape($value)."'";
            }
        }
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
		$type_with_default = ("text" == $dataType ? "text" : "$dataType NULL default NULL");
		$insertFieldSQL = "ALTER TABLE " . $xoopsDB->prefix("formulize_" . $formObject->getVar('form_handle')) . " ADD `$elementHandle` $type_with_default";
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
			$updateSql[] = "UPDATE ".$xoopsDB->prefix("formulize_".$formObject->getVar('form_handle'))." SET `".$element->getVar('ele_handle')."` = '".formulize_db_escape($replacementString)."' WHERE entry_id = ".$array['entry_id'];
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
    
    // this function returns the most recent entry in the revision table for a given entry
    // id is the entry id
    function getRevisionForEntry($id, $revisionId=null) {
        $form_handler = xoops_getmodulehandler('forms','formulize');
        $formObject = $form_handler->get($this->fid);
        if($formObject->getVar('store_revisions') AND $form_handler->revisionsTableExists($this->fid)) {
            $GLOBALS['formulize_getDataFromRevisionsTable'] = true;
            if($revisionId) {
                $data = getData("", $this->fid, 'revision_id/**/'.$revisionId.'/**/=');
            } else {
                $data = getData("", $this->fid, $id, "AND", "", 0, 1, "revision_id", "DESC");
            }
            unset($GLOBALS['formulize_getDataFromRevisionsTable']);
            return $data;
        } else {
            return false;
        }
    }
    
	
}
	
