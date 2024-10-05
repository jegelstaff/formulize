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

		$originalEntryId = '';
		while($sourceDataArray = $xoopsDB->fetchArray($sourceDataRes)) {
			$start = true;
      		$formObject = $form_handler->get($this->fid);
			$insertSQL = "INSERT INTO " . $xoopsDB->prefix("formulize_" . $formObject->getVar('form_handle')) . " SET ";
			$originalEntryId = 0;
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
	function cloneEntry($entry_id, $callback = null, $targetEntry = "new") {
		if(!is_numeric($entry_id)) {
			return false;
		}

		global $xoopsDB;
		$form_handler = xoops_getmodulehandler('forms', 'formulize');
		$formObject = $form_handler->get($this->fid);
		$sql = "SELECT * FROM " . $xoopsDB->prefix("formulize_".$formObject->getVar('form_handle')) . " WHERE entry_id = $entry_id";
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
		return $this->writeEntry($targetEntry, $data, false, true); // no proxy user (use current xoopsuser, the default behaviour), do force the creation of the entry if we're on a GET request
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

				if(count((array) $newIds) > 1) {
					$newEleHandleValue = "\",".implode(",",array_filter($newIds, 'is_numeric')).",\"";
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

		$ids = array_filter($ids, 'is_numeric');
        $form_handler = xoops_getmodulehandler('forms', 'formulize');
        $formObject = $form_handler->get($this->fid);
        foreach($ids as $id) {
            $existing_values = $formObject->onDeletePrep($id);
        }
		$sql = "DELETE FROM " .$xoopsDB->prefix("formulize_".$formObject->getVar('form_handle')) . " WHERE entry_id = " . implode(" OR entry_id = ", $ids);
		if(!$deleteSuccess = $xoopsDB->query($sql)) {
			return false;
		}
        foreach($ids as $id) {
            $existing_values = $formObject->onDelete($id);
        }
		$sql = "DELETE FROM " . $xoopsDB->prefix("formulize_entry_owner_groups") . " WHERE fid=".formulize_db_escape($this->fid)." AND (entry_id = " . implode(" OR entry_id = ", array_filter($ids, 'is_numeric')) . ")";
		if(!$deleteOwernshipSuccess = $xoopsDB->query($sql)) {
			print "Error: could not delete entry ownership information for form ". formulize_db_escape($this->fid) . ", entries: " . implode(", ", array_filter($ids, 'is_numeric')) . ". Check the DB queries debug info for details.";
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
    // list($creation_datetime, $mod_datetime, $creation_uid, $mod_uid) = $data_handler->getEntryMeta($entry_id);
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
    function findAllUsersForEntries($ids, $scope_uids=array()) {
        return $this->getAllUsersForEntries($ids, $scope_uids);
    }
	function getAllUsersForEntries($ids, $scope_uids=array()) {
		$scopeFilter = $this->_buildScopeFilter($scope_uids);
		global $xoopsDB;
    $form_handler = xoops_getmodulehandler('forms', 'formulize');
    $formObject = $form_handler->get($this->fid);
		$sql = "SELECT creation_uid FROM " . $xoopsDB->prefix("formulize_".$formObject->getVar('form_handle')) . " WHERE entry_id IN (" . implode(",", array_filter($ids, 'is_numeric')) . ") $scopeFilter";
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
	// use of $scope_uids should only be for when entries by the current user are searched for.  All other group based scopes should be done based on the scope_group_ids.
	function getElementValueInEntry($id, $element_id, $scope_uids=array(), $scope_group_ids=array()) {
		if(!$element = _getElementObject($element_id)) {
			return false;
		}
		global $xoopsDB;
    $form_handler = xoops_getmodulehandler('forms', 'formulize');
    $formObject = $form_handler->get($this->fid);
		if(is_array($scope_uids) AND count($scope_uids)>0) {
			$scopeFilter = $this->_buildScopeFilter($scope_uids);
			$sql = "SELECT `". $element->getVar('ele_handle') . "` FROM " . $xoopsDB->prefix("formulize_".$formObject->getVar('form_handle')) . " WHERE entry_id = " . intval($id) . $scopeFilter;
		} elseif(is_array($scope_group_ids) AND count($scope_group_ids)>0) {
			$scopeFilter = $this->_buildScopeFilter("", $scope_group_ids);
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
	// use of $scope_uids should only be for when entries by the current user are searched for.  All other group based scopes should be done based on the scope_group_ids.
    function findAllEntriesForUsers($uids, $scope_uids=array(), $scope_group_ids=array()) {
        return $this->getAllEntriesForUsers($uids, $scope_uids, $scope_group_ids);
    }
	function getAllEntriesForUsers($uids, $scope_uids=array(), $scope_group_ids=array()) {
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
			$sql = "SELECT entry_id FROM " . $xoopsDB->prefix("formulize_".$formObject->getVar('form_handle')) . " WHERE (creation_uid = " . implode(" OR creation_uid = ", array_filter($uids, 'is_numeric')) . ") $scopeFilter ORDER BY entry_id";
		} elseif(is_array($scope_group_ids) AND count($scope_group_ids)>0) {
			$scopeFilter = $this->_buildScopeFilter("", $scope_group_ids);
			$sql = "SELECT t1.entry_id FROM " . $xoopsDB->prefix("formulize_".$formObject->getVar('form_handle')) . "AS t1, " . $xoopsDB->prefix("formulize_entry_owner_groups") . " AS t2 WHERE (t1.creation_uid = " . implode(" OR t1.creation_uid = ", array_filter($uids, 'is_numeric')) . ") $scopeFilter ORDER BY t1.entry_id";
		} else {
			$sql = "SELECT entry_id FROM " . $xoopsDB->prefix("formulize_".$formObject->getVar('form_handle')) . " WHERE (creation_uid = " . implode(" OR creation_uid = ", array_filter($uids, 'is_numeric')) . ") ORDER BY entry_id";
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
    function findFirstEntryForGroups($group_ids) {
        return $this->getFirstEntryForGroups($group_ids);
    }
	function getFirstEntryForGroups($group_ids) {
		if(!is_array($group_ids)) {
			$group_ids = array(0=>intval($group_ids));
		}

		global $xoopsDB;
    $form_handler = xoops_getmodulehandler('forms', 'formulize');
    $formObject = $form_handler->get($this->fid);
		$sql = "SELECT t1.entry_id FROM ". $xoopsDB->prefix("formulize_".$formObject->getVar('form_handle')) . " as t1, ". $xoopsDB->prefix("formulize_entry_owner_groups") ." as t2 WHERE t1.entry_id = t2.entry_id AND t2.fid=".$this->fid." AND t2.groupid IN (".implode(",",array_filter($group_ids, 'is_numeric')).") ORDER BY t1.entry_id LIMIT 0,1";
		global $xoopsUser;
		if(!$res = $xoopsDB->query($sql)) {
			return false;
		}
		$row = $xoopsDB->fetchRow($res);
		return $row[0];
	}


	// this function finds the first entry for a given user in the form
    function findFirstEntryForUsers($uids) {
        return $this->getFirstEntryForUsers($uids);
    }
	function getFirstEntryForUsers($uids) {
		if(!is_array($uids)) {
			$uids = array($uids);
		}
        foreach($uids as $i=>$uid) {
            if(is_object($uid)) {
                $uids[$i] = intval($uid->getVar('uid'));
            }
        }
		$scopeFilter = $this->_buildScopeFilter($uids);
		global $xoopsDB;
        $form_handler = xoops_getmodulehandler('forms', 'formulize');
        $formObject = $form_handler->get($this->fid);
		$sql = "SELECT entry_id FROM " . $xoopsDB->prefix("formulize_".$formObject->getVar('form_handle')) . " WHERE 1 $scopeFilter ORDER BY entry_id LIMIT 0,1"; // need where 1 so the AND at start of scopeFilter is syntactically sound
		if(!$res = $xoopsDB->query($sql)) {
			return false;
		}
		$row = $xoopsDB->fetchRow($res);
		return $row[0];

	}

    // this function returns the entry ID of the last entry found in the form with the specified value in the specified element
    function findLastEntryWithValue($element_id, $value, $operator="=", $scope_uids=array()) {
        return $this->findFirstEntryWithValue($element_id, $value, $operator, $scope_uids, true);
    }

	// this function returns the entry ID of the first entry found in the form with the specified value in the specified element
	function findFirstEntryWithValue($element_id, $value, $operator="=", $scope_uids=array(), $desc=false) {
		if(!$element = _getElementObject($element_id)) {
			return false;
		}
        $likeBits = $operator == "LIKE" ? "%" : "";
		global $xoopsDB;
        $form_handler = xoops_getmodulehandler('forms', 'formulize');
        $formObject = $form_handler->get($this->fid);
        $scopeFilter = $this->_buildScopeFilter($scope_uids);
        $desc = $desc ? 'DESC' : '';
        $sql = "SELECT entry_id FROM " . $xoopsDB->prefix("formulize_".$formObject->getVar('form_handle')) . " WHERE `". $element->getVar('ele_handle') . "` ".formulize_db_escape($operator)." \"$likeBits" . formulize_db_escape($value) . "$likeBits\" $scopeFilter ORDER BY entry_id $desc LIMIT 0,1";
		if(!$res = $xoopsDB->query($sql)) {
			return false;
		}
        if($xoopsDB->getRowsNum($res)==0) {
            return false;
        }
		$row = $xoopsDB->fetchRow($res);
		return $row[0];
	}

/**
	 * This function returns the requested field(s) found for the first entry in the form which matches all the specified values for the specified elements
	 * @param array $values An array of key=>value pairs, where keys are the element ids or element handles, and the values are the things to look for
	 * @param string $operator Optional. A string indicating the operator to use when looking for the specified values, or an array of operators corresponding to the key value pairs we're looking for.
	 * @param string $fieldsToReturn Optional. A field name or comma separated set of field names, or * which will be used in the SELECT clause. Defaults to entry_id.
	 * @return mixed Returns the value of the specified field for the first entry found, or an array of all the field values if more than one field is requested, where the keys are the field names and values are the values. Returns false if no entries were found or the query failed.
	 */
	function findFirstEntryWithAllValues($values, $operator="=", $fieldsToReturn = "entry_id") {
		return $this->findEntryOrEntriesWithAllValues($values, $operator, true, $fieldsToReturn);
	}

	/**
	 * This function returns the requested field(s) found for all the entries in the form which match all the specified values for the specified elements
	 * @param array $values An array of key=>value pairs, where keys are the element ids or element handles, and the values are the things to look for
	 * @param string $operator Optional. A string indicating the operator to use when looking for the specified values, or an array of operators corresponding to the key value pairs we're looking for.
	 * @param string $fieldsToReturn Optional. A field name or comma separated set of field names, or * which will be used in the SELECT clause. Defaults to entry_id.
	 * @return mixed Returns an array of all entries found, keyed by entry_id if that field is one of the requested fields. Each item in the array contains an array of the requested field values, where the keys are the field names and values are the values. Returns false if no entries were found or the query failed.
	 */
	function findAllEntriesWithAllValues($values, $operator="=", $fieldsToReturn = "entry_id") {
		return $this->findEntryOrEntriesWithAllValues($values, $operator, false, $fieldsToReturn);
	}

	/**
	 * This function returns the requested field(s) found for the first entry, or all entries, in the form which match all the specified values for the specified elements
	 * @param array $values An array of key=>value pairs, where keys are the element ids or element handles, and the values are the things to look for
	 * @param string $operator Optional. A string indicating the operator to use when looking for the specified values, or an array of operators corresponding to the key value pairs we're looking for.
	 * @param boolean $findFirstOnly Optional. A flag to indicate if only the first entry should be returned.
	 * @param string $fieldsToReturn Optional. A field name or comma separated set of field names, or * which will be used in the SELECT clause. Defaults to entry_id.
	 * @return array Returns the value of the specified field for the first entry found, or an array of all the field values if more than one requested, where the keys are the field names and values are the values. Returns an array of records if more than the first one is requested, and in this case the array will use the entry ids as keys, as long as entry_id was a requested field. Returns null if nothing was found.  Returns false if the query failed.
	 */
	function findEntryOrEntriesWithAllValues($values, $operator = "=", $findFirstOnly = true, $fieldsToReturn = "entry_id") {
		global $xoopsDB;
		$form_handler = xoops_getmodulehandler('forms', 'formulize');
		$formObject = $form_handler->get($this->fid);
		$findFirstEntryLimit = $findFirstOnly ? " LIMIT 0,1" : "";
		$sql = "SELECT ".formulize_db_escape($fieldsToReturn)."  FROM " . $xoopsDB->prefix("formulize_".$formObject->getVar('form_handle')) . " WHERE ";
		$valuesSQL = array();

		$operatorKeyCounter = 0;
		foreach($values as $elementIdOrHandle=>$value) {

				$opp = $operator;
				if(is_array($operator)) {
					if(isset($operator[$elementIdOrHandle])) {
						$opp = $operator[$elementIdOrHandle];
					} elseif(isset($operator[$operatorKeyCounter])) {
						$opp = $operator[$operatorKeyCounter];
					}
					$operatorKeyCounter++;
				}

				if(!$element = _getElementObject($elementIdOrHandle)) {
						continue;
				}
				$quotes = '"';
				$likeBits = $opp == "LIKE" ? "%" : "";
				$workingOp = $opp;
				if($value === null) {
						switch($opp) {
								case "!=":
										$value = " IS NOT NULL ";
										break;
								case "=":
								default:
										$value = " IS NULL ";
						}
						$workingOp = '';
						$quotes = '';
						$likeBits = '';
				} else {
						$value = formulize_db_escape($value);
						if($opp == 'IN') {
							$quotes = '';
							$value = "($value)";
						} else {
							$quotes = (is_numeric($value) AND !$likeBits) ? '' : $quotes;
						}
				}
				$valuesSQL[] = "`". $element->getVar('ele_handle') . "` ".formulize_db_escape($workingOp)." ".$quotes.$likeBits.$value.$likeBits.$quotes;
		}
		$sql .= implode(' AND ', $valuesSQL)." ORDER BY entry_id $findFirstEntryLimit";
		if(!$res = $xoopsDB->query($sql)) {
			return false;
		}
		$rows = array();
		while($row = $xoopsDB->fetchArray($res)) {
			if(isset($row['entry_id'])) {
				$rows[$row['entry_id']] = $row;
			} else {
				$rows[] = $row;
			}
		}
		if($findFirstOnly) {
			if(count($rows)==0 ) { // nothing found
				return null;
			}
			$firstKey = array_key_first($rows);
			if(!is_array($rows[$firstKey]) OR count($rows[$firstKey]) > 1) { // multiple fields found, or something goofy (not an array), return the whole thing
				return $rows[$firstKey];
			} else { // one field requested, return that single value
				return $rows[$firstKey][array_key_first($rows[$firstKey])];
			}
		} else {
			return $rows ? $rows : null;
		}
	}

	// this function returns the entry ID of all entries found in the form with the specified value in the specified element
	// use of $scope_uids should only be for when entries by the current user are searched for.  All other group based scopes should be done based on the scope_group_ids.
	function findAllEntriesWithValue($element_id, $value, $scope_uids=array(), $scope_group_ids=array(), $operator="=") {

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
		} elseif(is_array($scope_group_ids) AND count($scope_group_ids)>0) {
			$scopeFilter = $this->_buildScopeFilter("", $scope_group_ids);
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
    // prepValues will cause the values to be cleaned up by the prepValues function, which makes them more readable
	function findAllValuesForEntries($handle, $entries, $prepValues=false) {
		if(!is_array($entries)) {
			if(is_numeric($entries)) {
				$entries = array($entries);
			} else {
				return false;
			}
		}
		static $cachedValues = array();
		global $xoopsDB;
		$form_handler = xoops_getmodulehandler('forms', 'formulize');
		$formObject = $form_handler->get($this->fid);
		foreach($entries as $i=>$entry_id) {
            $entries[$i] = intval($entry_id); // ensure we're not getting any funny business passed in to the DB
        }
        if(!isset($cachedValues[$handle][serialize($entries)])) {
            $sql = "SELECT `$handle`, `entry_id` FROM ".$xoopsDB->prefix("formulize_".$formObject->getVar('form_handle')). " WHERE entry_id IN (".implode(',',array_filter($entries, 'is_numeric')).")";
            if($res = $xoopsDB->query($sql)) {
                while($array = $xoopsDB->fetchArray($res)) {
                    if($prepValues) {
                        $value = prepValues($array[$handle], $handle, $array['entry_id']);
                        $cachedValues[$handle][serialize($entries)][] = is_array($value) ? $value[0] : $value;
                    } else {
                        $cachedValues[$handle][serialize($entries)][] = $array[$handle];
                    }

                }
            } else {
                $cachedValues[$handle][serialize($entries)][] = false;
            }
        }
		$resultArray = is_array($cachedValues[$handle][serialize($entries)]) ? $cachedValues[$handle][serialize($entries)] : array();
		return $resultArray;
	}

	// this function returns all the values of a given field
    // sort can be ASC or DESC, if left out then results are in creation order
    // scope_group_ids can be an array of group ids, which will limit the values to those which are owned by users in the given group(s)
    // scope_uids can be an array of user ids, which will limit the values to those created by the declared users
    // usePerGroupFilters will trigger the use of permission filters set for the user's groups
	function findAllValuesForField($handle, $sort="", $scope_group_ids=array(), $scope_uids=array(), $usePerGroupFilters=false) {
		static $cachedValues = array();
		global $xoopsDB;
		if(!isset($cachedValues[$handle]) AND $this->fid) {
			if($sort=="ASC") {
				$sort = " ORDER BY f.`$handle` ASC";
			} elseif($sort =="DESC") {
				$sort = " ORDER BY f.`$handle` DESC";
			} else {
                $sort = "";
            }
			$form_handler = xoops_getmodulehandler('forms', 'formulize');
			$formObject = $form_handler->get($this->fid);
            $scope = '';
            if(is_array($scope_group_ids) AND count($scope_group_ids)>0) {
                $scopeWhere = array();
                $scope_group_ids = array_unique($scope_group_ids);
                foreach($scope_group_ids as $gid) {
                    if(is_numeric($gid)) {
                        $scopeWhere[] = " eog.groupid = $gid ";
                    }
                }
                if(count($scopeWhere)>0) {
                    $scope = "WHERE EXISTS(SELECT 1 FROM ".$xoopsDB->prefix("formulize_entry_owner_groups")." AS eog WHERE eog.fid = ".$this->fid." AND eog.entry_id = f.entry_id AND (".implode('OR',$scopeWhere)."))";
                }
            }
            $uidFilter = $this->_buildScopeFilter($scope_uids);
            $uidFilter = $scope ? $uidFilter : str_replace(' AND ', ' WHERE ', $uidFilter);
            $uidFilter = str_replace('creation_uid', 'f.creation_uid', $uidFilter);
            $perGroupFilters = "";
            if($usePerGroupFilters) {
                $form_handler = xoops_getmodulehandler('forms', 'formulize');
                $perGroupFilters = $form_handler->getPerGroupFilterWhereClause($this->fid, 'f');
                if(!$scope AND !$uidFilter) {
                    $perGroupFilters = "WHERE 1 ".$perGroupFilters;
                }
            }
			$sql = "SELECT f.`$handle`, f.`entry_id` FROM ".$xoopsDB->prefix("formulize_".$formObject->getVar('form_handle'))." AS f $scope $uidFilter $perGroupFilters $sort";
			if($res = $xoopsDB->query($sql)) {
				while($array = $xoopsDB->fetchArray($res)) {
					$cachedValues[$handle][$array['entry_id']] = $array[$handle];
				}
			} else {
				$cachedValues[$handle] = false;
			}
		} elseif(!$this->fid) {
			$cachedValues[$handle] = false; // fid passed into constructor was not valid!
		}
		return $cachedValues[$handle];
	}


	function _buildScopeFilter($scope_uids, $scope_group_ids=array()) {
		if(is_array($scope_uids)) {
			if(count($scope_uids) > 0) {
				$scopeFilter = " AND (creation_uid = " . implode(" OR creation_uid = ", array_filter($scope_uids, 'is_numeric')) . ")";
			} else {
				$scopeFilter = "";
			}
		} elseif(is_array($scope_group_ids)) {
			if(count($scope_group_ids) > 0) {
			  $scopeFilter = " AND (t2.groupid IN (".implode(",", array_filter($scope_group_ids, 'is_numeric')).") AND t2.entry_id=t1.entry_id AND t2.fid=".intval($this->fid).")";
			} else {
				$scopeFilter = "";
			}
		} else {
			$scopeFilter = "";
		}
		return $scopeFilter;
	}

	// derive the owner groups and write them to the owner groups table
	// $uids and $entryids MUST BE PARALLEL, either single ids, or arrays with multiple users and entries. Single uid with array of entries won't work, needs array of repeated uid in that case, kinda dumb, but that's how it works
	// arrays must start with 0 key and increase sequentially (no gaps, no associative keys, etc)
	// all groups the user is a member of are written to the database, regardless of their current permission on the form
	// interpretation of permissions is to be done when reading this information, to allow for more flexibility
    // $update is deprecated
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
		if(count((array) $uids) != count((array) $entryids)) {
			return false;
		}
        $update = false;
        // check if there is any ownership info for any entry we're setting details for
        // if so, then we need to clear existing ownership info, update creation users
        if($this->getEntryOwnerGroups($entryids)) {
            $update = true;
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
		  for($i=0;$i<count((array) $uids);$i++) { // loop through all the users
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
		for($i=0;$i<count((array) $uids);$i++) { // loop through all the users
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
        global $xoopsDB;
        if($entry_id == 'new') {
            global $xoopsUser;
            return $xoopsUser ? $xoopsUser->getGroups() : array(XOOPS_GROUP_ANONYMOUS);
        }
        // if we're checking a series of entries, just return true/false for whether there's any ownership info for any of them
        // this is an internal feature used by setEntryOwnerGroups
        if(is_array($entry_id)) {
            $entryFilter = " AND entry_id IN (".implode(", ", array_filter($entry_id, 'is_numeric')).") ";
            $sql = "SELECT DISTINCT(groupid) FROM ".$xoopsDB->prefix("formulize_entry_owner_groups") . " WHERE fid='".$this->fid."' $entryFilter ORDER BY groupid";
            if($res = $xoopsDB->query($sql)) {
                return $xoopsDB->getRowsNum($res);
            } else {
                return false;
            }
        } else {
            $entry_id = intval($entry_id);
            if(!isset($cachedEntryOwnerGroups[$this->fid][$entry_id]) OR $cachedEntryOwnerGroups[$this->fid][$entry_id] === false OR $cachedEntryOwnerGroups[$this->fid][$entry_id] === array()) {
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

	}

	/**
	 * Write a set of values to an entry in a form's data table
	 * @param int|string $entry_id The entry that we are writing to, or 'new' for a new entry
	 * @param array $values An array of key-value pairs, where the keys are the element handles or element ids of the fields we are writing to, and the values are the values we are writing. The array must use all ids or all handles as the keys. Cannot mix and match!
	 * @param boolean|int $proxyUser Optional. The user id of the user who is to be recorded as creating this entry, or false if the currently active user should be used
	 * @param boolean $forceUpdate Optional. True/false to indicate if the query should be written even on a GET request. Defaults to false (so data is only written on POST requests)
	 * @param boolean $update_metadata Optional. True/false to indicate if the metadata of the entry should be updated (is set to false when updating derived values for example).
	 */
	function writeEntry($entry_id, $values, $proxyUser=false, $forceUpdate=false, $update_metadata=true) {

		global $xoopsDB, $xoopsUser, $formulize_existingValues;
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
    if(count((array) $this->dataTypeMap)==0) {
      $this->dataTypeMap = $cachedDataTypeMaps[$this->fid]; // now assign the value of the property, based on the cached static array
		}
		if(!isset($handleElementMap)) {
			$handleElementMap = $cachedMaps[$this->fid][$mapIDs];
		}

		// convert the value array keys from IDs to handles if necessary
		if($mapIDs) {
			$element_values = array();
			foreach ($values as $key => $value) {
				$element_values[$handleElementMap[$key]] = $value;
			}
		} else {
			$element_values = $values;
		}

    // if it is a "new" entry, set default values
		if(!is_numeric($entry_id) AND $entry_id == "new") {
			$defaultValueMap = getEntryDefaults($this->fid, $entry_id);
			foreach($defaultValueMap as $defaultValueElementId=>$defaultValueToWrite) {
				if($defaultValueElementId) {
					// setup things to be able to lookup the handle of the element in the map
					$element_valuesKey = $handleElementMap[$defaultValueElementId];
					if(!$mapIDs) {
						$handles = convertElementIdsToElementHandles(array($defaultValueElementId));
						$element_valuesKey = $handleElementMap[$handles[0]];
					}
					// if the element is not a value that we received, then let's use the default value
					if(!isset($element_values[$element_valuesKey])) {
						$element_values[$element_valuesKey] = $defaultValueToWrite;
					}
				}
			}
		}

		// call a hook which can modify the values before saving. If it returns false, then return null up the chain.
		list($element_values, $existing_values) = $formObject->onBeforeSave($entry_id, $element_values);
		if ($element_values === false) { return null; }

		// ensure the hook has not created any invalid element handles because that would cause the sql query to fail
		// note that array_flip means the map will be using the handles as keys, and so the intersect will exclude any values that are not valid handles for the form.
		$element_values = array_intersect_key($element_values, array_flip($handleElementMap));

    $clean_element_values = $element_values; // save a clean copy of the original values before the escaping for writing to DB, so we can use these later in "on after save"

		// don't write things that are unchanged from their current state in the database
		foreach($element_values as $evHandle=>$thisElementValue) {
			$thisElementValue = $thisElementValue === "{WRITEASNULL}" ? NULL : $thisElementValue;
			if(array_key_exists($evHandle, $existing_values) AND $existing_values[$evHandle] === $thisElementValue) {
				unset($element_values[$evHandle]);
			}
		}

		if(isset($GLOBALS['formulize_overrideProxyUser'])) {
      $creation_uid = intval($GLOBALS['formulize_overrideProxyUser']);
    }

		// no values to save, which may be caused by the onBeforeSave() handler deleting all of the values, or nothing has changed from the state in the database, so return null up the chain.
    if (0 == count((array) $element_values)) {
      return null;
    }

		// escape field names and values before writing to database
		$aes_password = getAESPassword();
		foreach ($element_values as $key => $value) {
			$encrypt_this = in_array($key, $encrypt_element_handles);
			unset($element_values[$key]);   // since field name is not escaped, remove from array
			$key = "`".formulize_db_escape($key)."`";                // escape field name
			$element_values[$key] = $this->formatValueForQuery($key, $value, $entry_id);
			if ($encrypt_this) {
				// this element should be encrypted. note that the actual value is quoted and escapted already
				$element_values[$key] = "AES_ENCRYPT({$element_values[$key]}, '$aes_password')";
			}
		}

		// setup various metadata values...
		if ($update_metadata or "new" == $entry_id) {
			// update entry metadata
			$element_values["`mod_datetime`"]   = "NOW()";
			$element_values["`mod_uid`"]        = intval($uid);
		}

		// prepare query to write a new record
		if ($entry_id == "new") {
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
							$element_values['anon_passcode_'.$passcodeFid] = $this->formatValueForQuery('anon_passcode_'.$sid, $value, $entry_id);
						}
					}
				}
			}
			$sql = "INSERT INTO ".$xoopsDB->prefix("formulize_".$formObject->getVar('form_handle'))." (".implode(", ", array_keys($element_values)).") VALUES (".implode(", ", array_values($element_values)).")";
			$entry_to_return = "";

		// prepare query to update existing record
    } else {
			if (!function_exists("make_sql_set_values")) {
				function make_sql_set_values($field, $value) {
						return "$field = $value";
				}
			}
      $sql_set_values = array_map("make_sql_set_values", array_keys($element_values), array_values($element_values));
			$sql = "UPDATE " . $xoopsDB->prefix("formulize_".$formObject->getVar('form_handle')) .  " SET ".implode(", ", $sql_set_values)." WHERE entry_id = ".intval($entry_id);
			$entry_to_return = intval($entry_id);
		}

    formulize_updateRevisionData($formObject, $entry_to_return, $forceUpdate);

		if($forceUpdate) {
			if(!$res = $xoopsDB->queryF($sql)) {
				exit("Error: your data could not be saved in the database.  This was the query that failed:<br>$sql<br>Query was forced and still failed so the SQL is probably bad.<br>".$xoopsDB->error());
			}
		} elseif(!$res = $xoopsDB->query($sql)) {
			exit("Error: your data could not be saved in the database.  This was the query that failed:<br>$sql<br>".$xoopsDB->error());
		}

		if($entry_to_return) {
      $this->updateCaches($entry_to_return);
    } else {
			$entry_to_return = $xoopsDB->getInsertId();
		}

		writeToFormulizeLog(array(
			'formulize_event' => 'saving-data',
			'user_id'=>($xoopsUser ? $xoopsUser->getVar('uid') : 0),
			'form_id' => $this->fid,
			'entry_id' => $entry_to_return
		));

		// if we wrote any {ID} values to the DB that should become the entry id number of the record, update them now to match the actual entry_id
		if($writePrimaryKeyToElements = array_keys($element_values, "'{ID}'", true)) {
			$pkSQL = "UPDATE ". $xoopsDB->prefix("formulize_".$formObject->getVar('form_handle')) .  " SET ".implode(" = entry_id, ", $writePrimaryKeyToElements)." = entry_id WHERE entry_id = $entry_to_return";
			if($forceUpdate) {
				if(!$res = $xoopsDB->queryF($pkSQL)) {
					exit("Error: could not record entry id value for {ID} requested in element(s) ".implode(", ",$writePrimaryKeyToElements).". This was the query that failed:<br>$pkSQL<br>Query was forced and still failed so the SQL is probably bad.<br>".$xoopsDB->error());
				}
			} elseif(!$res = $xoopsDB->query($pkSQL)) {
				exit("Error: could not record entry id value for {ID} requested in element(s) ".implode(", ",$writePrimaryKeyToElements).". This was the query that failed:<br>$pkSQL<br>".$xoopsDB->error());
			}
			// update the officially recorded value that was saved since we've just done a last minute swap (otherwise the officially saved value would be {ID} which isn't correct now)
			foreach($writePrimaryKeyToElements as $wpkElementHandle) {
				$clean_element_values[$wpkElementHandle] = $entry_to_return;
			}
		}

		if($entry_id != "new") {
			// remove any entry-editing lock that may be in place for this record, since it was just saved successfully...a new lock can now be placed on the entry the next time any element from the form, for this entry, is rendered.
      $lock_file_name = XOOPS_ROOT_PATH."/modules/formulize/temp/entry_".intval($entry_id)."_in_form_".$formObject->getVar('id_form')."_is_locked_for_editing";
      if (file_exists($lock_file_name)) {
        unlink($lock_file_name);
			}
			// cache copies of what the state of the data was before and after save, for reference elsewhere (ie: when processing notifications)
			if(!isset($formulize_existingValues[$this->fid][$entry_id]['before_save'])) {
				// cache the existing values only on first run through, because we might end up here again a few times because of derived values and save handlers and so on
				$formulize_existingValues[$this->fid][$entry_id]['before_save'] = $existing_values;
			}
			$formulize_existingValues[$this->fid][$entry_id]['after_save'] = $clean_element_values;
		}

		$formObject->onAfterSave($entry_to_return, $clean_element_values, $existing_values, $entry_id); // last param, original entry id, will be 'new' if new save

		return $entry_to_return;
	}

    // check a given field against the dataTypeMap, return true if it contains 'date'
    function dataTypeIsDate($key) {
        if(count((array) $this->dataTypeMap)==0) {
            $this->dataTypeMap = $this->gatherDataTypes();
        }
        $key = trim($key, "`");
        if(stripos($this->dataTypeMap[$key], "date") !== false) {
            return true;
        }
        return false; // no it's not
    }

    // check a given field against the dataTypeMap, return true if it's a numeric type
    function dataTypeIsNumeric($key) {
        if(count((array) $this->dataTypeMap)==0) {
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
        if(count((array) $this->dataTypeMap)!=0) {
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


		/**
		 * Format a given value for inclusion in a DB insert or update query, based on the data type of the element, and the numeric or string value of the data
		 *
		 * Also handle special cases of { } terms that we know how to handle
		 *
		 * @param string $field The name of the field in the database (element handle).
		 * @param mixed $value The value we're going to try and insert into the DB for the given field
		 * @param mixed $entry_id Optional. The entry id of the record we're inserting this data to, or 'new' to represent a new entry
		 * @return string A valid string for using in a SQL query to insert/update the value of the field in the database
		 */
    function formatValueForQuery($field, $value, $entry_id = null) {
			if ("{WRITEASNULL}" === $value or null === $value) {
				return "NULL";
			} elseif("{SEQUENCE}" === $value) {
				global $xoopsDB;
				$element_handler = xoops_getmodulehandler('elements','formulize');
				$form_handler = xoops_getmodulehandler('forms','formulize');
				$elementObject = $element_handler->get(trim($field, "`"));
				$formObject = $form_handler->get($elementObject->getVar('id_form'));
				return "(SELECT CASE WHEN MAX(seqquerytable.$field) > 0 THEN (MAX(seqquerytable.$field) + 1) ELSE 1 END FROM ".$xoopsDB->prefix('formulize_'.$formObject->getVar('form_handle'))." as seqquerytable)";
			} elseif("{ID}" === $value AND is_numeric($entry_id)) {
				return intval($entry_id);
			} else {
				// if this element has a numeric type in the DB, no quotes
				if($this->dataTypeIsNumeric($field)) {
					if(is_numeric($value)) {
							return formulize_db_escape($value);
					} else { // non numeric values cannot be written to a numeric field, so NULL them
							return "NULL";
					}
				} elseif($this->dataTypeIsDate($field) AND (!$value OR $value == _DATE_DEFAULT OR $value == '0000-00-00')) {
					return "NULL";
				}
			}
			return "'".formulize_db_escape($value)."'";
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
		if(!$element = _getElementObject($element_id_or_handle) AND (!is_array($newValues) OR empty($newValues))) {
			return false;
		}

		// multiple selection elements have data saved with the special prefix to separate values in the cell:  *=+*:
		// we need to determine if this element allows multiple values and prepare to handle it
		$ele_type = $element->getVar('ele_type');
		$ele_value = $element->getVar('ele_value');
		switch($ele_type) {
			case "radio":
				$oldValues = array_keys($ele_value);
				break;
			case "checkbox":
			case "select":
				$oldValues = array_keys($ele_value[2]);
				// special check...if this is a linked selectbox or a fullnames/usernames selectbox, then fail
				if(!is_array($ele_value[2]) OR isset($ele_value[2]["{FULLNAMES}"]) OR isset($ele_value[2]["{USERNAMES}"])) {
					return false;
				}
				break;
		}
		$prefix = ($ele_type == "checkbox" OR ($ele_type == "select" AND $ele_value[1])) ? "*=+*:" : ""; // multiple selection possible? if so, setup prefix
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
			for($i=0;$i<count((array) $newValues);$i++) {
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
					if(count((array) $foundIndex)==count((array) $currentValues)) {
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
		if(count((array) $updateSql) > 0) { // if we have some SQL generated, then run it.
			foreach($updateSql as $thisSql) {
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

