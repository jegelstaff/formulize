<?php

###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2004 Freeform Solutions                  ##
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
##  Author of this file: Freeform Solutions 					     ##
##  Project: Formulize                                                       ##
###############################################################################

// this file contains functions for interfacing with the Formulize database by using the handles and the relationships between forms described in a Framework

function connect($host, $username, $password, $db) {
     $connect = mysql_connect($host, $username, $password)
     		or printf("<br>Could not connect to mysql host<br>");

     $dbselect = mysql_select_db($db)
            or printf("Could not select database<br>");
}



// RETURNS THE RESULTS OF AN SQL STATEMENT
// WARNING:  HIGHLY INEFFICIENT IN TERMS OF MEMORY USAGE!
// returns a multidimensioned array where the first index is the row of the result and the second index is the field name in that row
function go($query, $keyfield="") {
	//print "$query"; // debug code
	$result = array();
	if($res = mysql_query($query)) { // appears to work OK inside Drupal.  Is this because there is always a previous query to the XOOPS DB before we get to this stage, and so it is pointing to the right place when this fires?  Maybe this should be rewritten to explicitly check for the pressence of $xoopsDB, and use that if it is found.
		while ($array = mysql_fetch_array($res)) {
			if($keyfield) {
				$result[$array[$keyfield]] = $array;
			} else {
				$result[] = $array;
			}
		}
	}
	return $result;
}

// DISPLAYS ON THE SCREEN THE CURRENT MEMORY USAGE OF THE SCRIPT FOR DEBUGGING PURPOSES
function debug_memory($text) {
	if(isset($_GET['debugOFF'])) {
		print "<br>Memory Usage: ";	
		$mem_usage = memory_get_usage();
		$mb_usage = round(($mem_usage/1000000), 2);
		print "$mb_usage ($text)";
	}
}



// DEBUG FUNCTIONS, PRINTS RAW RESULTS ARRAYS IN A READABLE FORMAT
// call with this code:
// printclean($mainresults, $linkresult);
// DO NOT WORK NOW THAT MAINRESULT AND LINKRESULT ARE ACTUAL QUERY RESULT OBJECTS AND NOT ARRAYS
function pc($result) {
	print "<br>PRINTCLEAN RESULTS:<br>";
	for($i=0;$i<count($result);$i++) {
			print "Now printing result row $i:<br>";
			print_r($result[$i]);
			print "<br>";
	}
}
function printclean($mainresults, $linkresult) {
	print "<br>MAINRESULT:";
	pc($mainresults);
	for($i=0;$i<count($linkresult);$i++) {
		print "<br>LINKRESULT for form: " . $linkresult[$i]['formid'];
		pc($linkresult[$i]['result']);
	}
}

//ANOTHER DEBUG FUNCTION, THIS ONE FOR LOOKING AT FINAL RESULTS IN READABLE FORMAT
function printfclean($results) {
	
	$keys = array_keys($results);
	print "FORM HANDLE: ";
	print_r($keys);
	print "<br>";

	foreach($results as $result) {
		print "RECORD IDS: ";
		$keys = array_keys($result);
		print_r($keys);
		print "<br>";
		foreach($result as $k => $row) {
			print "<br>RECORD $k: ";
			print_r($row);
		}
		print "<br><br>";
	}
}


// this is a copy of the regular makeUidFilter function in the functions.php file, but since extract.php must standalone for when it's called by outside sites, we have to make an independently named copy here.
// This function makes a "uid" filter, not "creation_uid" so it will not work with the formulize data tables.
function extract_makeUidFilter($users) {
	$start = 1;
	foreach($users as $user) {
		if($start) {
			$uq = "uid=$user";
			$start = 0;
		} else {
			$uq .= " OR uid=$user";
		}			
	}
	return $uq;
}

// id_req and ffcaption only used for converting 'other' values
function prepvalues($value, $field, $entry_id) { 

  // return metadata values without putting them in an array
  if($field == "creation_uid" OR $field == "mod_uid" OR $field == "creation_datetime" OR $field == "mod_datetime" OR $field=="email" OR $field=="user_viewemail") {
     return $value;
  }

	// handle cases where the value is linked to another form
  
  
  if($source_ele_value = formulize_isLinkedSelectBox($field, true)) {
     // value is an entry id in another form
     // need to get the form id by checking the ele_value[2] property of the element definition, to get the form id from the first part of that
     $sourceMeta = explode("#*=:*", $source_ele_value[2]); // [0] will be the fid of the form we're after, [1] is the handle of that element
     if($value AND $sourceMeta[1]) {
          $sql = "SELECT `".$sourceMeta[1]."` FROM ".DBPRE."formulize_".$sourceMeta[0]." WHERE entry_id IN (".trim($value, ",").") ORDER BY entry_id";
          if(!$res = mysql_query($sql)) {
               print "Error: could not retrieve the source values for a linked selectbox during data extraction.  SQL:<br>$sql<br>";
          } else {
               $value = "";
               while($array = mysql_fetch_array($res)) {
                    $value .= "*=+*:" . $array[$sourceMeta[1]];
               }
          }
     } elseif($value) {
          $value = ""; // if there was no sourceMeta[1], which is the handle for the field in the source form, then the value should be empty, ie: we cannot make a link...this probably only happens in cases where there's a really old element that had its caption changed, and that happened before Formulize automatically updated all the linked selectboxes that rely on that element's caption, back when captions mattered in the pre F3 days
     }
  }

  $elementArray = formulize_getElementMetaData($field, true);
  
  // check if this is fullnames/usernames box
  // wickedly inefficient to go to DB for each value!!  This loop executes once per datapoint in the result set!!
  $type = $elementArray['ele_type'];
  $ele_value = unserialize($elementArray['ele_value']);
  if($type == "select") {
     $listtype = key($ele_value[2]);
     if($listtype === "{USERNAMES}" OR $listtype === "{FULLNAMES}") {
          $uids = explode("*=+*:", $value);
          if(count($uids) > 0) {
               if(count($uids) > 1) { array_shift($uids); }
               $uidFilter = extract_makeUidFilter($uids);
               $listtype = $listtype == "{USERNAMES}" ? 'uname' : 'name';
               $names = go("SELECT $listtype FROM " . DBPRE . "users WHERE $uidFilter ORDER BY $listtype");
               $value = "";
               foreach($names as $thisname) {
                 $value .= "*=+*:" . $thisname[$listtype];
               }     
          } else {
               $value = "";
          }
     }   
  }
  		
	// handle yes/no cases

	if($type == "yn") { // if we've found one
		if($value == "1") {
			$value = _formulize_TEMP_QYES;
		} elseif($value == "2") {
			$value = _formulize_TEMP_QNO;
		} else {
			$value = "";
		}
	}

	//and remove any leading *=+*: while we're at it...
	if(substr($value, 0, 5) == "*=+*:")	{
		$value = substr_replace($value, "", 0, 5);
	}

	// Convert 'Other' options into the actual text the user typed
	if(($type == "radio" OR $type == "checkbox") AND preg_match('/\{OTHER\|+[0-9]+\}/', $value)) {
		// convert ffcaption to regular and then query for id
		$realcap = str_replace("`", "'", $ffcaption);
		$newValueq = go("SELECT other_text FROM " . DBPRE . "formulize_other, " . DBPRE . "formulize WHERE " . DBPRE . "formulize_other.ele_id=" . DBPRE . "formulize.ele_id AND " . DBPRE . "formulize.ele_handle=\"" . mysql_real_escape_string($field) . "\" AND " . DBPRE . "formulize_other.id_req='".intval($entry_id)."' LIMIT 0,1");
		//$value_other = _formulize_OPT_OTHER . $newValueq[0]['other_text'];
    $value_other = $newValueq[0]['other_text']; // removing the "Other: " part...we just want to show what people actually typed...doesn't have to be flagged specifically as an "other" value
		$value = preg_replace('/\{OTHER\|+[0-9]+\}/', $value_other, $value); 
	}

	return explode("*=+*:",$value);
}


// this function returns the handle for an element when given the id
function handleFromId($id, $fid, $frid) {
	if($id === "uid" OR $id === "proxyid" OR $id === "creation_date" OR $id === "mod_date" OR $id === "creator_email" OR $id === "creation_uid" OR $id === "mod_uid" OR $id === "creation_datetime" OR $id === "mod_datetime") { return $id; }
        static $handles = array();
        if(!isset($handles[$id][$fid][$frid])) {
          $handle = go("SELECT fe_handle FROM " . DBPRE . "formulize_framework_elements WHERE fe_frame_id = '$frid' AND fe_form_id = '$fid' AND fe_element_id = '$id'");
          $handles[$id][$fid][$frid] = $handle[0]['fe_handle'];
        }
        return $handles[$id][$fid][$frid];
}

function microtime_float()
{
   list($usec, $sec) = explode(" ", microtime());
   return ((float)$usec + (float)$sec);
}

function getData($framework, $form, $filter="", $andor="AND", $scope="", $limitStart="", $limitSize="", $sortField="", $sortOrder="", $forceQuery=false, $mainFormOnly=0, $includeArchived=false, $dbTableUidField="", $id_reqsOnly=false) { // IDREQS ONLY, only works with the main form!! returns array where keys and values are the id_reqs

     if(substr($filter, 0, 7) == "SELECT ") { // a proper SQL statement has been passed in so use that instead of constructing one...initially added for the new export feature
	  $result = dataExtraction(intval($framework), intval($form), $filter);
	  return $result;
     }


	// have to check for the pressence of the Freeform Solutions archived user patch, and if present, then the includeArchived flag can be used
	// if not present, ignore includeArchived
/*	static $archres = array();
	if(count($archres) == 0) {
		$archres = go("SELECT * FROM " . DBPRE . "users LIMIT 0,1");
	}
	if(isset($archres[0]['archived'])) {
		$includeArchived = $includeArchived ? true : false;
	} else {
		$includeArchived = false;	
	}*/

     // check to see if this form is a "tableform", ie: a reference to plain db table
     $isTableForm = false;
     if(is_numeric($form)) {
          $checkTableForm = mysql_query("SELECT tableform, desc_form FROM ".DBPRE."formulize_id WHERE id_form=$form");
          $tableFormRow = mysql_fetch_row($checkTableForm);
          $isTableForm = $tableFormRow[0] == "" ? false : true;
     }
     
     // handle old style sort and order values...
     $sortOrder = ($sortOrder == "SORT_ASC" OR $sortOrder == "ASC") ? "" : $sortOrder;
     $sortOrder = ($sortOrder == "SORT_DESC") ? "DESC" : $sortOrder;
          
  if($isTableForm) {
     $result = dataExtractionTableForm($tableFormRow[0], $tableFormRow[1], $form, $filter, $andor, $limitStart, $limitSize, $sortField, $sortOrder);
  }elseif(substr($framework, 0, 3) == "db:") { // deprecated...tableforms are preferred approach now for direct table access
		$result = dataExtractionDB(substr($framework, 3), $filter, $andor, $scope, $dbTableUidField);
	} else {
     
	$result = dataExtraction($framework, $form, $filter, $andor, $scope, $limitStart, $limitSize, $sortField, $sortOrder, $forceQuery, $mainFormOnly, $includeArchived, $id_reqsOnly);
	}
	return $result;
}


function dataExtraction($frame="", $form, $filter, $andor, $scope, $limitStart, $limitSize, $sortField, $sortOrder, $forceQuery, $mainFormOnly, $includeArchived=false, $id_reqsOnly=false) {


     if(isset($_GET['debug'])) { $time_start = microtime_float(); }
     
	     if($scope == "uid=\"blankscope\"") { return array(); }
     
     if(is_numeric($frame)) {
		     $frid = $frame;
	     } elseif($frame != "") {
		     $frameid = go("SELECT frame_id FROM " . DBPRE . "formulize_frameworks WHERE frame_name='$frame'");
		     $frid = $frameid[0]['frame_id'];
		     unset($frameid);
	     } else {
		     $frid = "";
	   }
	   if(is_numeric($form)) {
		     $fid = $form;
	     } else {
		     $formcheck = go("SELECT ff_form_id FROM " . DBPRE . "formulize_framework_forms WHERE ff_frame_id='$frid' AND ff_handle='$form'");
		     $fid = $formcheck[0]['ff_form_id'];
		     unset($formcheck);
	   }
	   if (!$fid) { 
		     print "Form Name: " . $form . "<br>";
		     print "Form id: " . $fid . "<br>";
		     print "Frame Name: " . $frame . "<br>";
		     print "Frame id: " . $frid . "<br>";
		     exit("selected form does not exist in framework"); 
	   }
     
       
	     if($frid AND !$mainFormOnly) {
		     // GET THE LINK INFORMATION FOR THE CURRENT FRAMEWORK BASED ON THE REQUESTED FORM
		     $linklist1 = go("SELECT fl_form2_id, fl_key1, fl_key2, fl_relationship, fl_unified_display, fl_common_value FROM " . DBPRE . "formulize_framework_links WHERE fl_frame_id = '$frid' AND fl_form1_id = '$fid'");
		     $linklist2 = go("SELECT fl_form1_id, fl_key1, fl_key2, fl_relationship, fl_unified_display, fl_common_value FROM " . DBPRE . "formulize_framework_links WHERE fl_frame_id = '$frid' AND fl_form2_id = '$fid'");
	     }
       
	     // link list 1 is the list of form2s that the requested form links to
	     // link list 2 is the list of form1s that the requested form links to
	     // ie: the link list number denotes the position of the requested form in the pair
	     
     //	print "Frame: $frame ($frid)<br>";
     //	print "Form: $form ($fid)<br>";
	     
     
	     // generate the list of key fields in the current form, so we can use the values in these fields to filter the linked forms. -- sept 27 2005
			     $linkkeys1 = array();
			     $linkisparent1 = array();
			     $linkformids1 = array();
			     $linktargetids1 = array();
	   $linkselfids1 = array();
			     $linkcommonvalue1 = array();
	   $linkkeys2 = array();
			     $linkisparent2 = array();
			     $linkformids2 = array();
			     $linktargetids2 = array();
	   $linkselfids2 = array();
			     $linkcommonvalue2 = array();
     
	     if($frid AND !$mainFormOnly) {
		     if(count($linklist1) > 0) {
		     foreach($linklist1 as $theselinks) {
			     $linkformids1[] = $theselinks['fl_form2_id'];
			     if($theselinks['fl_key1'] != 0) {
				     $handleforlink = handleFromId($theselinks['fl_key1'], $fid, $frid);
				     $linkkeys1[] = $handleforlink;
				     $linktargetids1[] = $theselinks['fl_key2'];
				     $linkselfids1[] = $theselinks['fl_key1'];
			     } else {
				     $linkkeys1[] = "";
				     $linktargetids1[] = "";
			     }
			     if($theselinks['fl_relationship'] == 2) { // 2 is one to many relationship...this does not appear to be referenced anywhere in the extraction layer!
				     $linkisparent1[] = 1;
			     } else {
				     $linkisparent1[] = 0;
			     }
			     $linkcommonvalue1[] = $theselinks['fl_common_value'];
		     }
		     }
		     
		     if(count($linklist2) > 0) {
		     foreach($linklist2 as $theselinks) {
			     $linkformids2[] = $theselinks['fl_form1_id'];
			     if($theselinks['fl_key2'] != 0) {
				     $handleforlink = handleFromId($theselinks['fl_key2'], $fid, $frid);
				     $linkkeys2[] = $handleforlink;
				     $linktargetids2[] = $theselinks['fl_key1'];
				     $linkselfids2[] = $theselinks['fl_key2'];
			     } else {
				     $linkkeys2[] = "";
				     $linktargetids2[] = "";
			     }
			     if($theselinks['fl_relationship'] == 3) { // 3 is many to one relationship...this does not appear to be referenced anywhere in the extraction layer!
				     $linkisparent2[] = 1;
			     } else {
				     $linkisparent2[] = 0;
			     }
			     $linkcommonvalue2[] = $theselinks['fl_common_value'];
		     }
		     }
		     $linkkeys = array_merge($linkkeys1, $linkkeys2);
		     $linkisparent = array_merge($linkisparent1, $linkisparent2);
		     $linkformids = array_merge($linkformids1, $linkformids2);
		     $linktargetids = array_merge($linktargetids1, $linktargetids2);
	 $linkselfids = array_merge($linkselfids1, $linkselfids2);
		     $linkcommonvalue = array_merge($linkcommonvalue1, $linkcommonvalue2);
	     } else {
		     $linkkeys = "";
		     $linkisparent = "";
		     $linkformids = "";
		     $linktargetids = "";
	 $linkselfids = "";
		     $linkcommonvalue = "";
	     }
     
	  $GLOBALS['formulize_linkformidsForCalcs'] = $linkformids;
     
	      // now that we have the full details from the framework, figure out the full SQL necessary to get the entire dataset
	  // This whole approach is predicated on being able to do reliable joins between the key fields of each form
	  
	  // Structure of the SQL should be...
	  // SELECT main.entry_id, main.creation_uid, main.mod_uid, main.creation_datetime, main.mod_datetime, main.handle1...main.handleN, f2.entry_id, f2.1..f2.n, etc FROM formulize_A AS main [join syntax] WHERE main.handle1 = "whatever" AND/OR f2.handle1 = "whatever"
	  // Join syntax:  if there are query terms on the f2 or subsequent forms, then use INNER JOIN formulize_B AS f2 ON main.1 LIKE CONCAT('%,', f2.entry_id, ',%')
	  // If there are no query terms on the f2 or subsequent forms, then use LEFT JOIN
	  
	  // establish the join type and all that
	  
	  $joinText = "";
	  $linkSelect = "";
	  
	  formulize_getElementMetaData("", false, $fid); // initialize the element metadata for this form...serious performance gain from this
	  if(substr($filter, 0, 6) != "SELECT") { // if the filter is not itself a fully formed select statement...
	       
	       $scopeJoinText = "";
	       $scopeFilter = "";
	       if(is_array($scope)) { // assume any arrays are groupid arrays, and so make a valid scope string based on this.  Use the new entry owner table.
		    if(count($scope) > 0 ) {
			 $start = true;
			 foreach($scope as $groupid) { // need to loop through the array, and not use implode, so we can sanitize the values
			      if(!$start) {
				   $scopeFilter .= " OR scope.groupid=".intval($groupid);
			      } else {
				   $start = false;
				   //$scopeFilter = " AND (scope.groupid=".intval($groupid); // going with an exists statement for the scope, since it's not really an inner join we care about, and the fact there can be a one to many relationship between the main table and the scope table can screw up results.
           $scopeFilter = " AND EXISTS(SELECT 1 FROM ".DBPRE."formulize_entry_owner_groups AS scope WHERE (scope.entry_id=main.entry_id AND scope.fid=".intval($fid).") AND (scope.groupid=".intval($groupid);
			      }
			 }
			 //$scopeFilter .= ") ";
       $scopeFilter .= ")) "; // need two closing brackets for the exists statement and its where clause
			 //$scopeJoinText = " INNER JOIN ".DBPRE."formulize_entry_owner_groups AS scope ON (scope.entry_id=main.entry_id AND scope.fid=".intval($fid).")"; // no need for a join text now
		    } else { // no valid entries found, so show no entries
			 $scopeFilter = " AND main.entry_id<0 ";
		    }
	       } elseif($scope) { // need to handle old "uid = X OR..." syntax
		    $scopeFilter = " AND (".str_replace("uid", "main.creation_uid", $scope).") ";
	       }
	       
	       list($formFieldFilterMap, $whereClause, $orderByClause, $oneSideFilters) = formulize_parseFilter($filter, $andor, $linkformids, $fid, $frid);
	       
	       $limitClause = "";
	       if($limitSize) {
		    $limitClause = " LIMIT $limitStart, $limitSize ";
	       }
	       if($whereClause) {
		    $whereClause = "AND $whereClause";
	       }     
	       
	       
	       
	       if($frid) {
           $joinHandles = formulize_getJoinHandles(array(0=>$linkselfids, 1=>$linktargetids)); // get the element handles for these elements, since we need those to properly construct the join clauses
           $newJoinText = ""; // "new" variables initilized in each loop
           $newOneSideJoinText = "";
           $joinText = ""; // not "new" variables persist (with .= operator)
           $oneSideJoinText = "";
           foreach($linkformids as $id=>$linkedFid) {
             formulize_getElementMetaData("", false, $linkedFid); // initialize the element metadata for this form...serious performance gain from this
             $linkSelect .= ", f$id.entry_id AS f".$id."_entry_id, f$id.creation_uid AS f".$id."_creation_uid, f$id.mod_uid AS f".$id."_mod_uid, f$id.creation_datetime AS f".$id."_creation_datetime, f$id.mod_datetime AS f".$id."_mod_datetime, f$id.*";
             $joinType = isset($formFieldFilterMap[$linkedFid]) ? "INNER" : "LEFT";
             $joinText .= " $joinType JOIN " . DBPRE . "formulize_$linkedFid AS f$id ON"; // NOTE: we are aliasing the linked form tables to f$id where $id is the key of the position in the linked form metadata arrays where that form's info is stored
             $newOneSideJoinText = $oneSideJoinText ? " $andor " : "";
             $newOneSideJoinText = " EXISTS(SELECT 1 FROM ". DBPRE . "formulize_$linkedFid AS f$id WHERE "; // set this up also so we have it available for one to many/many to one calculations that require it 
             if($linkcommonvalue[$id]) { // common value
               $newJoinText = " main.`" . $joinHandles[$linkselfids[$id]] . "`=f$id.`" . $joinHandles[$linktargetids[$id]]."`";
             } elseif($linktargetids[$id]) { // linked selectbox
               if(formulize_isLinkedSelectBox($linktargetids[$id])) { 
                 $newJoinText = " f$id.`" . $joinHandles[$linktargetids[$id]] . "` LIKE CONCAT('%,',main.entry_id,',%')";
               } else {
                 $newJoinText = " main.`" . $joinHandles[$linkselfids[$id]] . "` LIKE CONCAT('%,',f$id.entry_id,',%')";
               }
             } else { // join by uid
               $newJoinText = " main.creation_uid=f$id.creation_uid";
             }
             $joinText .= $newJoinText;
             if(count($oneSideFilters[$linkedFid])>0) { // only setup the oneSideJoinText when there is a where clause that applies to this form...otherwise, we don't care, this form is not relevant to the query that the calculations will do (except maybe when the mainform is not the one-side form...but that's another story)
               $oneSideJoinText .= $newOneSideJoinText . $newJoinText;
               $oneSideJoinText .= " AND (";
               foreach($oneSideFilters[$linkedFid] as $thisOneSideFilter) {
                $oneSideJoinText .= $thisOneSideFilter;
               }
               $oneSideJoinText .= ")) "; // close the where clause and the exists clause itself
             }
           }
	       }
	       
	       // specify the join info for user table (depending whether there's a query on creator_email or not)
	       $userJoinType = $formFieldFilterMap['creator_email'] ? "INNER" : "LEFT";
	       $userJoinText = " $userJoinType JOIN " . DBPRE . "users AS usertable ON main.creation_uid=usertable.uid";
	       
          if(!$orderByClause AND $sortField) {
               
               if($sortField == "creation_uid" OR $sortField == "mod_uid" OR $sortField == "creation_datetime" OR $sortField == "mod_datetime") {
                    $elementMetaData['id_form'] = $fid;
               } elseif($sortField == "uid") {
                    $sortField = "creation_uid";
                    $elementMetaData['id_form'] = $fid;
               } elseif($sortField == "proxyid") {
                    $sortField = "mod_uid";
                    $elementMetaData['id_form'] = $fid;
               } elseif($sortField == "creation_date") {
                    $sortField = "creation_datetime";
                    $elementMetaData['id_form'] = $fid;
               } elseif($sortField == "mod_date") {
                    $sortField = "mod_datetime";
                    $elementMetaData['id_form'] = $fid;
               } elseif($frid) {
                    $elementHandleAndId = formulize_getElementHandleAndIdFromFrameworkHandle($sortField, $frid);
                    $elementMetaData = formulize_getElementMetaData($elementHandleAndId[1]);
                    $sortField = $elementHandleAndId[0]; // use the element handle for the sort field, instead of the framework handle
               } else {
                    $elementMetaData = formulize_getElementMetaData($sortField, true); // need to get form that sort field is part of...               
               }
               $sortFid = $elementMetaData['id_form'];
               if($sortFid == $fid) {
                    $sortFidAlias = "main";
               } else {
                    $sortFidAlias = array_keys($linkformids, $sortFid); // position of this form in the linking relationships is important for identifying which form alias to use
                    $sortFidAlias = "f".$sortFidAlias[0];
               }
               $orderByClause = " ORDER BY $sortFidAlias.`$sortField` $sortOrder ";
          } elseif(!$orderByClause) {
               $orderByClause = "ORDER BY main.entry_id";
          }
	  		    
	       debug_memory("Before retrieving mainresults");
	       
	       //$beforeQueryTime = microtime_float();
	       
	  
	       // If there's an LOE Limit in place, check that we're not over it first
	       global $formulize_LOE_limit;
	  
	       $countMasterResults = "SELECT COUNT(main.entry_id) FROM " . DBPRE . "formulize_$fid AS main ";
	       if($userJointType == "INNER") {
		    $countMasterResults .= "$userJoinText ";
	       }
	       $countMasterResults .= "$userJoinText $scopeJoinText $joinText WHERE main.entry_id>0 $whereClause $scopeFilter";
	       if($countMasterResultsRes = mysql_query($countMasterResults)) {
		    $countMasterResultsRow = mysql_fetch_row($countMasterResultsRes);
		    if($countMasterResultsRow[0] > $formulize_LOE_limit AND $formulize_LOE_limit > 0 AND !$forceQuery AND !$limitClause) {
			 return $countMasterResultsRow[0];
		    } else {
			 $GLOBALS['formulize_countMasterResults'] = $countMasterResultsRow[0]; // put this in the global space so we can pick it up later when determining how many page numbers to create
		    }
	       } else {
		    exit("Error: could not count master results.  SQL:$countMasterResults<br>");
	       }
	  
	  // only drawback in this SQL right now is it does not support one to one relationships in the query, since they are essentially joins on the entry_id and form id through the one_to_one table
	  $masterQuerySQL = "SELECT main.entry_id AS main_entry_id, main.creation_uid AS main_creation_uid, main.mod_uid AS main_mod_uid, main.creation_datetime AS main_creation_datetime, main.mod_datetime AS main_mod_datetime, main.* $linkSelect, usertable.email AS main_email, usertable.user_viewemail AS main_user_viewemail FROM " . DBPRE . "formulize_$fid AS main $userJoinText $scopeJoinText $joinText WHERE main.entry_id>0 $whereClause $scopeFilter $orderByClause $limitClause";
    
    // if this is being done for gathering calculations, and the calculation is requested on the one side of a one to many/many to one relationship, then we will need to use different SQL to avoid duplicate values being returned by the database
    // note: when the main form is on the many side of the relationship, then we need to do something rather different...not sure what it is yet...the SQL as prepared is based on the calculation field and the main form being the one side (and so both are called main), but when field is on one side and main form is many side, then the aliases don't match, and scopefilter issues abound.
    $oneSideSQL = " FROM " . DBPRE . "formulize_$fid AS main $userJoinText $scopeJoinText WHERE main.entry_id>0 $scopeFilter ";
    $oneSideSQL .= $oneSideJoinText ? " AND ($oneSideJoinText) " : "";
    if(count($oneSideFilters[$fid])>0) {
      $oneSideSQL .= " $andor ( " . implode(" ", $oneSideFilters[$fid]) . ")"; // properly introduce these filters
    }
    
	  $GLOBALS['formulize_queryForCalcs'] = " FROM " . DBPRE . "formulize_$fid AS main $userJoinText $scopeJoinText $joinText WHERE main.entry_id>0  $whereClause $scopeFilter ";
    $GLOBALS['formulize_queryForOneSideCalcs'] = $oneSideSQL;
    if($GLOBALS['formulize_returnAfterSettingBaseQuery']) { return true; } // if we are only setting up calculations, then return now that the base query is built
	  $GLOBALS['formulize_queryForExport'] = "SELECT main.entry_id AS main_entry_id, main.creation_uid AS main_creation_uid, main.mod_uid AS main_mod_uid, main.creation_datetime AS main_creation_datetime, main.mod_datetime AS main_mod_datetime, main.* $linkSelect, usertable.email AS main_email, usertable.user_viewemail AS main_user_viewemail FROM " . DBPRE . "formulize_$fid AS main $userJoinText $scopeJoinText $joinText WHERE main.entry_id>0 $whereClause $scopeFilter $orderByClause";
     
	  //$masterQuerySQL = "SELECT * FROM " . DBPRE . "formulize_$fid LIMIT 0,1";
	  //$afterQueryTime = microtime_float();
     
     } else { // end of if the filter has a SELECT in it
	  $masterQuerySQL = $filter;
     }
     /*global $xoopsUser;
     if($xoopsUser->getVar('uid') == 4613) {
          $queryTime = $afterQueryTime - $beforeQueryTime;
          print "Query time: " . $queryTime . "<br>";
     }*/
     
     debug_memory("After retrieving mainresults");
     
     if($frid) {
          $frameworkMap = formulize_mapFramework($frid);
     } else {
          $frameworkMap = false;
     }
     
     
     // Debug Code
     
     /*global $xoopsUser;
     if($xoopsUser->getVar('uid') == 350) {
          print "<br>Count query: $countMasterResults<br><br>";
          print "Master query: $masterQuerySQL<br>";
     }*/
     
		 formulize_benchmark("Before query");
		 
     $masterQueryRes = mysql_query($masterQuerySQL);
     
		 formulize_benchmark("After query");
     
     // need to calculate the derived value metadata
     // 1. figure out which fields in the included forms have derived values
     // 2. setup the metadata for those fields, according to the order they appear
     // -- metadata should be: formhandle (title or framework formhandle), formula, handle (element handle or framework handle)
     // 3. call the derived value function from inside the main loop
     
     if($frid) {
          $sql = "SELECT t1.ele_value, t2.ff_handle, t3.fe_handle FROM ".DBPRE."formulize as t1, ".DBPRE."formulize_framework_forms as t2, ".DBPRE."formulize_framework_elements as t3 WHERE t1.ele_type='derived' AND (t1.id_form='$fid' OR t1.id_form IN (".implode(",",$linkformids).")) AND t1.ele_id=t3.fe_element_id AND t3.fe_frame_id='$frid' AND t1.id_form=t2.ff_form_id AND t2.ff_frame_id='$frid' ORDER BY t1.ele_order";
     } else {
          $sql = "SELECT t1.ele_value, t2.desc_form, t1.ele_handle FROM ".DBPRE."formulize as t1, ".DBPRE."formulize_id as t2 WHERE t1.ele_type='derived' AND t1.id_form='$fid' AND t1.id_form=t2.id_form ORDER BY t1.ele_order";     
     }
     $derivedFieldMetadata = array();
     if($res = mysql_query($sql)) {
          if(mysql_num_rows($res)>0) {
               $multipleIndexer = array();
               while($row = mysql_fetch_row($res)) {
                    $ele_value = unserialize($row[0]); // derived fields have ele_value as an array with only one element (that was done to future proof the data model, so we could add other things to ele_value if necessary)
                    if(!isset($multipleIndexer[$row[1]])) { $multipleIndexer[$row[1]] = 0; }
                    $derivedFieldMetadata[$row[1]][$multipleIndexer[$row[1]]]['formula'] = $ele_value[0]; // use row[1] (the form handle) as the key, so we can eliminate some looping later on
                    $derivedFieldMetadata[$row[1]][$multipleIndexer[$row[1]]]['handle'] = $row[2];
                    $multipleIndexer[$row[1]]++;
               }
          }
     } else {
          print "Error: could not check to see if there were derived value elements in one or more forms.  SQL:<br>$sql";
     }     

     // loop through the found data and create the dataset array in "getData" format
     $prevFieldNotMeta = true;
     $masterIndexer = -1;
     $writtenMains = array();
     $prevFormAlias = "";
     $creationUidLog = array();
     $prevMainId = "";
		 //formulize_benchmark("About to prepare results.");
     while($masterQueryArray = mysql_fetch_assoc($masterQueryRes)) {
					//formulize_benchmark("Starting to process one entry.");
          foreach($masterQueryArray as $field=>$value) {
               //formulize_benchmark("Starting to process one value");
               if($field == "entry_id" OR $field == "creation_uid" OR $field == "mod_uid" OR $field == "creation_datetime" OR $field == "mod_datetime" OR $field == "main_email" OR $field == "main_user_viewemail") { continue; } // ignore those plain fields, since we can only work with the ones that are properly aliased to their respective tables.  More details....Must refer to metadata fields by aliases only!  since * is included in SQL syntax, fetch_assoc will return plain column names from all forms with the values from those columns.....Also want to ignore the email fields, since the fact they're prefixed with "main" can throwoff the calculation of which entry we're currently writing
               if(strstr($field, "creation_uid") OR strstr($field, "creation_datetime") OR strstr($field, "mod_uid") OR strstr($field, "mod_datetime")) {
                    // dealing with a new metadata field
                    $fieldNameParts = explode("_", $field);
                    // We account for a mainform entry appearing multiple times in the list, because when there are multiple entries in a subform, and SQL returns one row per subform,  we need to not change the main form and internal record until we pass to a new mainform entry
                                        
                    if($prevFieldNotMeta) { // only do once for each form
                         $curFormId = $fieldNameParts[0] == "main" ? $fid : $linkformids[substr($fieldNameParts[0], 1)]; // the table aliases are based on the keys of the linked forms in the linkformids array, so if we get the number out of the table alias, that key will give us the form id of the linked form as stored in the linkformids array
                         $prevFormAlias = $curFormAlias;
                         $curFormAlias = $fieldNameParts[0];
                         if($prevFormAlias == "main") { // if we just finished up a main form entry, then log that
                              $writtenMains[$prevMainId] = true;
                         }
                         //print "curFormAlias: $curFormAlias<br>prevMainId: $prevMainId<br>current main id: ". $masterQueryArray['main_entry_id'] . "<br><br>";
                         if($curFormAlias == "main" AND $prevMainId != $masterQueryArray['main_entry_id']) {
                              $masterIndexer++; // If this is a new main entry, then increment the masterIndexer, since the masterIndexer is used to uniquely identify each main entry
                              $prevMainId = $masterQueryArray['main_entry_id']; // if the current form is a main, then store it's ID for use later when we're on a new form
                         }
                    }
                 
                    $prevFieldNotMeta = false;
                    // setup handles to use for metadata fields
                    if($curFormAlias == "main") {
                         if($field == "main_creation_uid" OR $field == "main_mod_uid" OR $field == "main_creation_datetime" OR $field == "main_mod_datetime") {
                              $elementHandle = $fieldNameParts[1] . "_" . $fieldNameParts[2];
                         } else {
                              continue; // do not include main_entry_id as a value in the array...though it should not be in here anyway now that we're checking with strstr for metadata field names above
                         }
                    } else {
                         continue; // do not include metadata from the linked forms, or anything else (such as email, etc)
                    }
               } elseif(!strstr($field, "main_email") AND !strstr($field, "main_user_viewemail") AND !strstr($field, "entry_id")) {
										// dealing with a regular element field
                    $prevFieldNotMeta = true;
                    $elementHandle = $field;
               } else { // it's an e-mail related or entry_id field
		    continue;
	       }               
               // Check to see if this is a main entry that has already been catalogued, and if so, then skip it
               if($curFormAlias == "main" AND isset($writtenMains[$masterQueryArray['main_entry_id']])) {
                    continue;
               } 

               // print "$curFormAlias - $field: $value<br>"; // debug line
               $valueArray = prepvalues($value, $elementHandle, $masterQueryArray[$curFormAlias . "_entry_id"]); // note...metadata fields must not be in an array for compatibility with the 'display' function...not all values returned will actually be arrays, but if there are multiple values in a cell, then those will be arrays
               $masterResults[$masterIndexer][formulize_readFrameworkMap($frameworkMap, $curFormId)][$masterQueryArray[$curFormAlias . "_entry_id"]][formulize_readFrameworkMap($frameworkMap, $curFormId, $elementHandle)] = $valueArray;

               if($elementHandle == "creation_uid" OR $elementHandle == "mod_uid" OR $elementHandle == "creation_datetime" OR $elementHandle == "mod_datetime") {
                    // add in the creator_email when we have done the creation_uid
                    if($elementHandle == "creation_uid") {
                         if(!isset($is_webmaster)) {
                              global $xoopsUser;
                              if(is_object($xoopsUser)) { // determine if the user is a webmaster, in order to control whether the e-mail addresses should be shown or not
                                   $is_webmaster = in_array(XOOPS_GROUP_ADMIN, $xoopsUser->getGroups()) ? true : false;
                                   $gperm_handler =& xoops_gethandler('groupperm');
                                   $view_private_fields = $gperm_handler->checkRight("view_private_elements", $fid, $xoopsUser->getGroups(), getFormulizeModId());
                                   $this_userid = $xoopsUser->getVar('uid');
                              } else {
                                   $view_private_fields = false;
                                   $is_webmaster = false;
                                   $this_userid = 0;
                              }
                         }
                         if($is_webmaster OR $view_private_fields OR $masterQueryArray['main_user_viewemail'] OR $masterQueryArray['main_creation_uid'] == $this_userid) {
                              $masterResults[$masterIndexer][formulize_readFrameworkMap($frameworkMap, $curFormId)][$masterQueryArray[$curFormAlias . "_entry_id"]]['creator_email'] = $masterQueryArray['main_email'];
                         } else {
                              $masterResults[$masterIndexer][formulize_readFrameworkMap($frameworkMap, $curFormId)][$masterQueryArray[$curFormAlias . "_entry_id"]]['creator_email'] = "";
                         }
                    }
                    
                    // for backwards compatibility, replicate the old metadata fields
                    switch($elementHandle) {
                         case "creation_uid":
                              $old_meta = "uid";
                              break;
                         case "mod_uid":
                              $old_meta = "proxyid";
                              break;
                         case "creation_datetime":
                              $old_meta = "creation_date";
                              break;
                         case "mod_datetime":
                              $old_meta = "mod_date";
                              break;
                    }
                    $masterResults[$masterIndexer][formulize_readFrameworkMap($frameworkMap, $curFormId)][$masterQueryArray[$curFormAlias . "_entry_id"]][$old_meta] = $valueArray;
               }
          }
					//formulize_benchmark("Done entry, ready to do derived values.");
					// now that the entire entry has been processed, do the derived values for it
          if(count($derivedFieldMetadata) > 0) {
               $masterResults[$masterIndexer] = formulize_calcDerivedColumns($masterResults[$masterIndexer], $derivedFieldMetadata, $frid, $fid);
          }
					
     }

     return $masterResults;

} // end of dataExtraction function

// this function returns the form id when given the form name, or the form handle and a framework id
function formulize_getFormIdFromNameOrHandle($nameHandle, $frid=0) {
     static $cachedFormIds = array();
     if(!isset($cachedFormIds[$nameHandle][$frid])) {
          if($frid) {
               $formIdData = go("SELECT ff_form_id FROM ".DBPRE."formulize_framework_forms WHERE ff_handle = '".mysql_real_escape_string($nameHandle)."' AND ff_frame_id = ".intval($frid));
               $cachedFormIds[$nameHandle][$frid] = $formIdData[0]['ff_form_id'];
          } else {
               $formIdData = go("SELECT id_form FROM ".DBPRE."formulize_id WHERE desc_form = '".mysql_real_escape_string($nameHandle)."'");
               $cachedFormIds[$nameHandle][$frid] = $formIdData[0]['id_form'];
          }
     }
     return $cachedFormIds[$nameHandle][$frid];
}




// THIS FUNCTION RETURNS THE FORM HANDLE NECESSARY FOR THE MASTER RESULT, BASED ON THE FRAMEWORK MAP IN EFFECT, IF ANY
function formulize_readFrameworkMap($frameworkMap, $form_id, $elementHandle=false) {
     if($elementHandle == false) {
          // returning form handles or titles
          if($frameworkMap != false) {
               return $frameworkMap[$form_id]['handle'];
          } else {
               static $cachedTitles = array();
               if(!isset($cachedTitles[$form_id])) {
                    // must fetch the full title for the form
                    $sql = "SELECT desc_form FROM " . DBPRE . "formulize_id WHERE id_form = " . intval($form_id);
                    $res = mysql_query($sql);
                    $array = mysql_fetch_assoc($res);
                    $cachedTitles[$form_id] = $array['desc_form'];                    
               }
               return $cachedTitles[$form_id];
          }
     }
     
     // returning element ids or handles
     if($elementHandle == "creation_uid" OR $elementHandle == "mod_uid" OR $elementHandle == "creation_datetime" OR $elementHandle == "mod_datetime") {
          return $elementHandle; // always return metadata handles as is
     }
     
     if($frameworkMap != false) {
          return $frameworkMap[$form_id]['elements'][$elementHandle];
     } else {
          return $elementHandle;
     }
}
     
// THIS FUNCTION RETURNS AN ARRAY WITH ALL THE FORM HANDLES AND ELEMENT HANDLES IN AN ARRAY
// Because the extraction layer must be call-able from outside XOOPS, we cannot use the framework class :-(
// $frameworkMap[fid]['handle'] = formHandle
// $frameworkMap[fid]['elements'] = array where keys are element handles, values are framework handles
function formulize_mapFramework($frid) {
     $frameworkMap = array();
     $sql = "SELECT * FROM " . DBPRE . "formulize_framework_forms WHERE ff_frame_id=" . intval($frid);
     $res = mysql_query($sql);
     while($array = mysql_fetch_array($res)) {
          $frameworkMap[$array['ff_form_id']]['handle'] = $array['ff_handle'];
     }
     $currentForm = 0;
     $previousForm = 0;
     $elementMap = array();
     $sql = "SELECT t1.fe_form_id, t2.ele_handle, t1.fe_handle FROM " . DBPRE . "formulize_framework_elements as t1, ".DBPRE."formulize as t2 WHERE t1.fe_frame_id=" . intval($frid) . " AND t1.fe_element_id=t2.ele_id ORDER BY t1.fe_form_id"; // order by the form id so we know we will loop through all handles for a form in one chunk in the while loop
     $res = mysql_query($sql);
     while($array = mysql_fetch_array($res)) {
          if($currentForm != $array['fe_form_id']) {
               if($currentForm > 0) {
                    $previousForm = $currentForm;
                    $frameworkMap[$previousForm]['elements'] = $elementMap;
                    $elementMap = array();
               }
               $currentForm = $array['fe_form_id'];
          }
          $elementMap[$array['ele_handle']] = $array['fe_handle'];
     }
     $frameworkMap[$currentForm]['elements'] = $elementMap; // assign the elements for the last form we found
     return $frameworkMap;
}


// THIS FUNCTION BREAKS DOWN THE FILTER STRING INTO ITS COMPONENTS.  TAKES EVERYTHING UP TO THE TOP LEVEL ARRAY SYNTAX.
// $linkfids is the linked fids in order that they appear in the SQL query
function formulize_parseFilter($filtertemp, $andor, $linkfids, $fid, $frid) {
     
     if($filtertemp == "") { return array(0=>array(), "", ""); }
     
     $formFieldFilterMap = array();
     $whereClause = "";
     $orderByClause = "";
     
     $oneSideFilters = array(); // we need to capture each filter individually, just in case we need to apply them individually to each part of the query for calculations.  Filters for calculations will not work right if the combination of filter terms is excessively complex, ie: includes OR'd terms across different forms in a framework, certain other complicated types of bracketing
     
     if(!is_array($filtertemp)) {
          $filter = array(0=>array(0=>$andor, 1=>$filtertemp));
     } else {
          $filter = $filtertemp;
     }
     
     global $myts;
     $numSeachExps = 0;
     foreach($filter as $filterParts) {
          // evaluate each search expression (collection of terms with a common boolean inbetween
          // Use the global andor setting between expressions
          
          if($filterParts[1] == "") { continue; } // ignore filters that are empty...can happen if only OR filters are specified, and maybe at other times too
          
          if($numSeachExps > 0) {
               $whereClause .= $andor;
          }
          $whereClause .= "(";
          
          $numIndivFilters = 0;
          foreach(explode("][", $filterParts[1]) as $indivFilter) {
                              
               // evaluate each individual search term
               // Use the local andor setting ($filterParts[0]) between terms

               $ifParts = explode("/**/", $indivFilter);
               
               if($numIndivFilters > 0) {
                    $whereClause .= $filterParts[0]; // apply local andor setting
               }
               $whereClause .= "("; // bracket each individual component of the whereclause
                    
               $operator = isset($ifParts[2]) ? $ifParts[2] : "LIKE";
               if(trim($operator) == "LIKE" OR trim($operator) == "NOT LIKE") {
                    if(strlen($ifParts[1]) > 1 AND (substr($ifParts[1], 0, 1) == "%" OR substr($ifParts[1], -1) == "%")) { // if the query term includes % at the front or back (or both), then we let that work as the "likebits" and don't put in any ourselves
                         $likebits = "";                         
                    } else {
                         $likebits = "%";     
                    }
                    $operator = " ".$operator." ";
               } else {
                    $likebits = "";
               }
               $quotes = ((is_numeric($ifParts[1]) AND !strstr(trim(strtoupper($operator)), "LIKE")) OR strstr(strtoupper($operator), "NULL"))  ? "" : "'"; // don't put quotes around numeric queries, unless they're part of a LIKE query.  Don't use quotes on the special IS NULL query either
               
               
               // FINAL NOTE ABOUT SLASHES...Oct 19 2006...patch 22 corrects this slash/magic quote mess.  However, to ensure compatibility with existing Pageworks applications, we are continuing to strip out all slashes in the filterparts[1], the filter strings that are passed in, and then we apply HTML special chars to the filter so that it can match up with the contents of the DB.  Only challenge is that extract.php is meant to be standalone, but we have to refer to the text sanitizer class in XOOPS in order to do the HTML special chars thing correctly.

               $ifParts[1] = str_replace("\\", "", $ifParts[1]);
               $ifParts[1] = $myts->htmlSpecialChars($ifParts[1]);
               
               // convert legacy metadata terms to new terms
               $ifParts[0] = $ifParts[0] == "uid" ? "creation_uid" : $ifParts[0];
               $ifParts[0] = $ifParts[0] == "proxyid" ? "mod_uid" : $ifParts[0];
               $ifParts[0] = $ifParts[0] == "creation_date" ? "creation_datetime" : $ifParts[0];
               $ifParts[0] = $ifParts[0] == "mod_date" ? "mod_datetime" : $ifParts[0];
               
               $formFieldFilterMap['creator_email'] = false; // can be set to true lower down, need to initalize it properly here
               
               if(is_numeric($ifParts[0]) AND $ifParts[0] == $indivFilter) {
                    // if this is a numeric value, then we must treat it specially
                    $whereClause .= "main.entry_id=" . $ifParts[0];
               } elseif($ifParts[0] == "creation_uid" OR $ifParts[0]  == "mod_uid" OR $ifParts[0]  == "creation_datetime" OR $ifParts[0]  == "mod_datetime") {
                    // if this is a user id field, then treat it specially 
                    if(($ifParts[0] == "creation_uid" OR $ifParts[0] == "mod_uid") AND !is_numeric($ifParts[1])) {
                         // subquery the user table for the username or full name
                         $ifParts[1] = "(SELECT uid FROM " . DBPRE . "users WHERE uname " . $operator . $quotes . $likebits . mysql_real_escape_string($ifParts[1]) . $likebits . $quotes . " OR name " . $operator . $quotes . $likebits . mysql_real_escape_string($ifParts[1]) . $likebits . $quotes . ")";
                         $quotes = "";
                         $operator = " = ANY ";
                         $likebits = "";
										} elseif(($ifParts[0] == "creation_uid" OR $ifParts[0] == "mod_uid") AND is_numeric($ifParts[1])) { // numeric uid query, so make operator =
												 $operator = " = ";
												 $quotes = "";
												 $likebits = "";
												 $ifParts[1] = mysql_real_escape_string($ifParts[1]);
                    } else { // need to put mysql_real_escape_string around $ifParts[1] only when it's a date field, since that escaping requirement has been handled already in the subquery for uid filters
                         $ifParts[1] = mysql_real_escape_string($ifParts[1]);
												 
                    }
                    $whereClause .= "main.".$ifParts[0]  . $operator . $quotes . $likebits . $ifParts[1] . $likebits . $quotes;
               } elseif($ifParts[0] == "creator_email") {
                    $formFieldFilterMap['creator_email'] = true;
                    /*$ifParts[1] = "(SELECT uid FROM " . DBPRE . "users WHERE email $operator $quotes $likebits " . mysql_real_escape_string($ifParts[1]) . " $likebits $quotes)";
                    $ifParts[0] = "creation_uid";
                    $quotes = "";
                    $operator = "= ANY";
                    $likebits = "";
                    $whereClause .= "main.".$ifParts[0] . $operator . $quotes . $likebits . mysql_real_escape_string($ifParts[1]) . $likebits . $quotes;*/
                    $whereClause .= "usertable.email" . $operator . $quotes . $likebits . mysql_real_escape_string($ifParts[1]) . $likebits . $quotes;
               } else {
                    
                    // do non-metadata queries
                    
                    // first convert any handles to element Handles, and/or get the element id if necessary...element id is necessary for creating the formfieldfiltermap, since that function was written the first time we tried to do this, when there were no element handles in the mix
                    if($frid AND !is_numeric($ifParts[0])) {
                         list($ifParts[0], $element_id) = formulize_getElementHandleAndIdFromFrameworkHandle($ifParts[0], $frid);
                    } elseif(is_numeric($ifParts[0])) { // using a numeric element id
                         $element_id = $ifParts[0];
                         $ifParts[0] = formulize_getElementHandleFromID($ifParts[0]);
                    } else { // no framework, element handle being used...so we have to derive the element id
                         $element_id = formulize_getIDFromElementHandle($ifParts[0]);
                    }
                    
                    // add ` ` around ifParts[0]...
                    $ifParts[0] = "`".$ifParts[0]."`";
                    
                    // identify the form that the element is associated with and put it in the map
                    list($formFieldFilterMap, $mappedForm) = formulize_mapFormFieldFilter($element_id, $formFieldFilterMap);
                    /*print "map: <br>";
                    print_r($formFieldFilterMap);
                    print "<br>Mappedform: $mappedForm<br>";*/
                    $elementPrefix = $mappedForm == $fid ? "main" : "f" . array_search($mappedForm, $linkfids);
                    
                    // set order by clause for newest operator -- assume only one newest operator per query!
                    if(strstr($ifParts[2], "newest")) {
                         $orderByClause = " ORDER BY $elementPrefix." . $ifParts[0] . " DESC LIMIT 0," . substr($ifParts[2], 6);
                    }
                         
                    // set query term for yes/no questions
                    if($formFieldFilterMap[$mappedForm][$element_id]['isyn']) {
                         if(strstr(strtoupper(_formulize_TEMP_QYES), strtoupper($ifParts[1]))) { // since we're matching based on even a single character match between the query and the yes/no language constants, if the current language has the same letters or letter combinations in yes and no, then sometimes only Yes may be searched for
                              $ifParts[1] = 1;
                         } elseif(strstr(strtoupper(_formulize_TEMP_QNO), strtoupper($ifParts[1]))) {
                              $ifParts[1] = 2;
                         } else {
                              $ifParts[1] = "";
                         }
                    }
                    
                    // build the where clause....
                    
                    // handle 'other' boxes
                    // instead of doing a subquery, this could probably be redone similarly to creator_email and then we would have the "other" value in the raw query result, and then the process in prepValues would not need to requery the other table
                    if($formFieldFilterMap[$mappedForm][$element_id]['hasother']) {
                         $subquery = "(SELECT id_req FROM " . DBPRE . "formulize_other WHERE ele_id=" . intval($element_id) . " AND other_text " . $operator . $quotes . $likebits . mysql_real_escape_string($ifParts[1]) . $likebits . $quotes . ")";
                         $newWhereClause = "(($elementPrefix.entry_id = ANY $subquery)OR("."$elementPrefix." . $ifParts[0] . $operator . $quotes . $likebits . mysql_real_escape_string($ifParts[1]) . $likebits . $quotes."))"; // need to look in the other box and the main field, and return values that match in either case
                    // handle linked selectboxes
                    } elseif($sourceMeta = $formFieldFilterMap[$mappedForm][$element_id]['islinked']) {
                         // Neal's suggestion:  use EXISTS...other forms of subquery using field IN subquery or subquery LIKE field, and a CONCAT in the subquery, failed in various conditions.  IN did not work with multiple selection boxes, and LIKE did not work with search terms too general to return only one match in the source form.  Exists works in all cases.  :-)
                         $newWhereClause = " EXISTS (SELECT 1 FROM ".DBPRE."formulize_".$sourceMeta[0]." AS source WHERE $elementPrefix.".$ifParts[0]." LIKE CONCAT('%,',source.entry_id,',%') AND source.".$sourceMeta[1] . $operator . $quotes . $likebits . mysql_real_escape_string($ifParts[1]) . $likebits . $quotes . ")";
                    // usernames/fullnames boxes
                    } elseif($listtype = $formFieldFilterMap[$mappedForm][$element_id]['isnamelist'] AND $ifParts[1] !== "") {
                         $listtype = $listtype == "{USERNAMES}" ? 'uname' : 'name';
                         if(!is_numeric($ifParts[1])) {
                              $preSearch = "SELECT uid FROM " . DBPRE . "users WHERE " . $listtype . $operator . $quotes . $likebits . mysql_real_escape_string($ifParts[1]) . $likebits . $quotes;
                         } else {
                              $preSearch = "SELECT uid FROM " . DBPRE . "users WHERE uid ".$operator.$quotes.$likebits.$ifParts[1].$likebits.$quotes;
                         }
                         $preSearchResult = mysql_query($preSearch);
                         if(mysql_num_rows($preSearchResult)>0) {
															$nameSearchStart = true;
                              while($preSearchArray = mysql_fetch_array($preSearchResult)) {
																	 if(!$nameSearchStart) {
																				$newWhereClause = "OR";
																	 } else {
																				$nameSearchStart = false;
																	 }
                                   if(formulize_selectboxAllowsMultipleSelections($element_id)) {
                                        $newWhereClause = " (($elementPrefix.".$ifParts[0]." LIKE '%*=+*:" . $preSearchArray['uid'] . "*=+*:%' OR $elementPrefix.".$ifParts[0]." LIKE '%*=+*:" . $preSearchArray['uid'] . "') OR $elementPrefix.".$ifParts[0]." = " . $preSearchArray['uid'] . ") "; // could this be further optimized to remove the = condition, and only use the LIKEs?  We need to check if a multiselection-capable box still uses the delimiter string when only one value is selected...I think it does.
                                   } else {
                                        $newWhereClause = " $elementPrefix.".$ifParts[0]." = " . $preSearchArray['uid'] . " ";
                                   }
                              }
                         } else {
                              $newWhereClause = "main.entry_id<0"; // no matches, so result set should be empty, so set a where clause that will return zero results
                              
                         }
                    // regular whereclause
                    } else {
                         // could/should put better handling in here of multiple value boxes, so = operators actually only look for matches within the individual values??  Is that possible?
                         $newWhereClause = "$elementPrefix." . $ifParts[0] . $operator . $quotes . $likebits . mysql_real_escape_string($ifParts[1]) . $likebits . $quotes;
                    }
                    
                    $whereClause .= $newWhereClause;
                    if(count($oneSideFilters[$mappedForm]) == 0) {
                         $oneSideFilters[$mappedForm][] = " $newWhereClause ";   // don't add the local andor on the first term for a form  
                    } else {
                         $oneSideFilters[$mappedForm][] = $filterParts[0] . " $newWhereClause ";     
                    }
                    
               }
               
               $whereClause .= ")";
               $numIndivFilters++;
          }
               
          $whereClause .= ")";
          $numSeachExps++;
     }
                         
     return array(0=>$formFieldFilterMap, 1=>$whereClause, 2=>$orderByClause, 3=>$oneSideFilters);    
}

// THIS FUNCTION TAKES AN ELEMENT AND COMPILES THE FORM, ELEMENT MAP, NECESSARY FOR KNOWING ALL WE NEED TO KNOW ABOUT THE ELEMENT
// needs work...?  if we can pass in the element_id, it should be OK
function formulize_mapFormFieldFilter($element_id, $formFieldFilterMap) {
     $foundForm = false;
     foreach($formFieldFilterMap as $fid=>$formData) { // check if the element has already been mapped and if so, what is the form
          if(isset($formData[$element_id])) {
               $foundForm = $fid;
          }
     }
     if(!$foundForm) {
          //$sql = "SELECT id_form, ele_value, ele_type FROM " . DBPRE . "formulize WHERE ele_id = " . intval($element_id);
          //$res = mysql_query($sql);
          //$array = mysql_fetch_array($res);
          $array = formulize_getElementMetaData($element_id);
          if(strstr($array['ele_value'], "#*=:*")) {
               $ele_value = unserialize($array['ele_value']);
               $formFieldFilterMap[$array['id_form']][$element_id]['islinked'] = explode("#*=:*", $ele_value[2]); // put an array of the source form id and source handle into the "islinked" flag               
          } else {
               $formFieldFilterMap[$array['id_form']][$element_id]['islinked'] = false;
          }
          $formFieldFilterMap[$array['id_form']][$element_id]['isyn'] = $array['ele_type'] == "yn" ? true : false;
          if(($array['ele_type'] == "radio" OR $array['ele_type'] == "checkbox") AND strstr($array['ele_value'], "{OTHER|")) {
               $formFieldFilterMap[$array['id_form']][$element_id]['hasother'] = true;
          } else {
               $formFieldFilterMap[$array['id_form']][$element_id]['hasother'] = false;
          }
          if($array['ele_type'] == "select" AND (strstr($array['ele_value'], "{FULLNAMES}") OR strstr($array['ele_value'], "{USERNAMES}"))) {
               $formFieldFilterMap[$array['id_form']][$element_id]['isnamelist'] = strstr($array['ele_value'], "{FULLNAMES}") ? "{FULLNAMES}" : "{USERNAMES}";
          } else {
               $formFieldFilterMap[$array['id_form']][$element_id]['isnamelist'] = false;
          }
          $foundForm = $array['id_form'];
     }
     return array(0=>$formFieldFilterMap, 1=>$foundForm);
}

// THIS FUNCTION TAKES A FRAMEWORK HANDLE AND FRID AND RETURNS THE ELEMENT HANDLE -- framework handles must be unique within a framework!
function formulize_getElementHandleAndIdFromFrameworkHandle($handle, $frid) {
     static $cachedHandles = array();
     if(!isset($cachedHandles[$handle][$frid])) {
          $sql = "SELECT t2.ele_handle, t2.ele_id FROM " . DBPRE . "formulize_framework_elements as t1, " . DBPRE . "formulize as t2 WHERE t1.fe_frame_id=" . intval($frid) . " AND t1.fe_handle = \"". mysql_real_escape_string($handle) . "\" AND t1.fe_element_id = t2.ele_id";
          $res = mysql_query($sql);
          while($array = mysql_fetch_array($res)) {
               $cachedHandles[$handle][$frid] = array(0=>$array['ele_handle'], 1=>$array['ele_id']);
          }
     }
     return isset($cachedHandles[$handle][$frid]) ? $cachedHandles[$handle][$frid] : false;
}

// THIS FUNCTION TAKES A HANDLE AND RETURNS THE ELEMENT HANDLE THAT CORRESPONDS
function formulize_getIdFromElementHandle($handle) {
     static $cachedIds = array();
     if(!isset($cachedIds[$handle])) {
          $array = formulize_getElementMetaData($handle, true);
          $cachedIds[$handle] = $array['ele_id'];
     }
     return isset($cachedIds[$handle]) ? $cachedIds[$handle] : false;
}

// THIS FUNCTION TAKES AN ELEMENT ID AND RETURNS THE ELEMENT HANDLE THAT CORRESPONDS
function formulize_getElementHandleFromID($element_id) {
     static $cachedHandles = array();
     if(!isset($cachedHandles[$element_id])) {
          $array = formulize_getElementMetaData($element_id);
          $cachedHandles[$element_id] = $array['ele_handle'];
     }
     return isset($cachedHandles[$element_id]) ? $cachedHandles[$element_id] : false;
}

// THIS FUNCTION figureS out if AN ELEMENT in this form is the source of the linked selectbox
// takes element ID, or handle (second param indicates if it's a handle or not)
// returns the ele_value array for this element if it is a linked selectbox
function formulize_isLinkedSelectBox($elementOrHandle, $isHandle=false) {
     static $cachedElements = array();
     if(!isset($cachedElements[$elementOrHandle])) {
          $evqRow = formulize_getElementMetaData($elementOrHandle, $isHandle);
          $cachedElements[$elementOrHandle] = strstr($evqRow['ele_value'], "#*=:*") ? unserialize($evqRow['ele_value']) : false;
     }
     return $cachedElements[$elementOrHandle];
}

// This function returns true or false depending on whether a selectbox allows multiple values or not
function formulize_selectboxAllowsMultipleSelections($elementOrHandle, $isHandle=false) {
     static $cachedElements = array();
     if(!isset($cachedElements[$elementOrHandle])) {
          $evqRow = formulize_getElementMetaData($elementOrHandle, $isHandle);
          $ele_value = unserialize($evqRow['ele_value']);
          $cachedElements[$elementOrHandle] = ($ele_value[0] > 1 AND $ele_value[1] == 1) ? true : false;
     }
     return $cachedElements[$elementOrHandle];
}


// This function takes an array of arrays of elementids, and returns an array of all the handles for those ids, with the ids as the keys
// Used to get the handles for doing joins in a framework query
// Will build on the same result set if called more than once.  So you will always get back the complete list of all elements that have been identified so far.
// Written for use constructing the join part of the select clause...not intended for use elsewhere
function formulize_getJoinHandles($elementArrays) {
     static $cachedJoinHandles = array();
     foreach($elementArrays as $elementArray) { // must be a multidimensional array, ie: even if we're only asking for one element, it's got to be $elementsArrays[0][0] = idnumber
          foreach($elementArray as $element) {
               if(!isset($cachedJoinHandles[$element])) {
                    $metaData = formulize_getElementMetaData($element);
                    $cachedJoinHandles[$element] = $metaData['ele_handle'];
               }
          }
     }
     return $cachedJoinHandles;
}


// This function gets element data once that can be used for multiple purposes
// first param is the id or handle being asked for, second param is a flag showing whether the first param should be interpretted as an id or a handle
// fid param is only used when this function is called near the start of the extraction layer, when we initialize the cachedElement map for each form that is in use
function formulize_getElementMetaData($elementOrHandle, $isHandle=false, $fid=0) {
     static $cachedElements = array();
     $cacheType = $isHandle ? 'handles' : 'ids';
     if(!isset($cachedElements[$cacheType][$elementOrHandle])) {
          if($fid) {
               $whereClause = "id_form=".intval($fid);
          } else {
               $whereClause = $isHandle ? "ele_handle = '".mysql_real_escape_string($elementOrHandle)."'" : "ele_id = ".intval($elementOrHandle);
          }
          $elementValueQ = "SELECT ele_value, ele_type, ele_id, ele_handle, id_form, ele_uitext, ele_caption, ele_colhead FROM " . DBPRE . "formulize WHERE $whereClause";
          $evqRes = mysql_query($elementValueQ);
          while($evqRow = mysql_fetch_array($evqRes)) {
               $cachedElements['handles'][$evqRow['ele_handle']] = $evqRow; // cached the element according to handle and id, so we don't repeat the same query later just because we're asking for info about the same element in a different way
               $cachedElements['ids'][$evqRow['ele_id']] = $evqRow;
          }
     }
     if(!$fid) {
          return $cachedElements[$cacheType][$elementOrHandle];
     }
}

// THIS FUNCTION LOOPS THROUGH AN ENTRY AND ADDS IN THE DERIVED VALUES IN ANY DERIVED COLUMNS -- March 27 2007
// Odd results may occur when a derived column is inside a subform in a framework!
// Derived values should always be in the mainform only?
function formulize_calcDerivedColumns($entry, $metadata, $frid, $fid) {
     
     global $xoopsDB; // just used as a cue to see if XOOPS is active
     static $parsedFormulas = array();
     foreach($entry as $formHandle=>$record) {
          if(isset($metadata[$formHandle])) { // if there are derived value formulas for this form...
               if(!isset($parsedFormulas[$formHandle])) {
                    $formulaParseResult = formulize_includeDerivedValueFormulas($metadata[$formHandle], $formHandle, $frid, $fid);
                    if($formulaParseResult === "Syntax Error") {
                        print "Error: there is an error in one of the derived value fields.  It could be a syntax error in the formula, or a reference to a form element is not valid.";
                        return $entry;
                    }
                    $parsedFormulas[$formHandle] = true;
               }
               foreach($metadata[$formHandle] as $formulaNumber=>$thisMetaData) {
                    
                    if($entry[$formHandle][key($record)][$thisMetaData['handle']][0] == "" OR isset($GLOBALS['formulize_forceDerivedValueUpdate'])) { // if there's nothing already in the DB, then derive it, unless we're being asked specifically to update the derived values, which happens during a save operation.  In that case, always do a derivation regardless of what's in the DB.
                         $functionName = "derivedValueFormula_".str_replace(" ", "_", str_replace("-", "", str_replace("/", "", str_replace("\\", "", trans($formHandle)))))."_".$formulaNumber;
                         formulize_benchmark(" -- calling derived function.");
                         $derivedValue = $functionName($entry);
                         formulize_benchmark(" -- completed call.");
                         foreach($record as $recordID=>$elements) {
                              $entry[$formHandle][$recordID][$thisMetaData['handle']][0] = $derivedValue;
                              // write value to database if XOOPS is active
                              if($xoopsDB) {
                                   include_once XOOPS_ROOT_PATH . "/modules/formulize/class/data.php";
                                   $data_handler = $data_handler = new formulizeDataHandler(formulize_getFormIdFromNameOrHandle($formHandle, $frid));
                                   if($frid) {
                                        $elementHandleAndId = formulize_getElementHandleAndIdFromFrameworkHandle($thisMetaData['handle'], $frid);
                                        $elementID = formulize_getIdFromElementHandle($elementHandleAndId[0]);
                                   } else {
                                        $elementID = formulize_getIdFromElementHandle($thisMetaData['handle']);
                                   }
                                   $data_handler->writeEntry($recordID, array($elementID=>$derivedValue), false, true); // false is no proxy user, true is force the update even on get requests
                              }
                         }
                    }
               }
          }
     }          
     return $entry;    
}

function formulize_includeDerivedValueFormulas($metadata, $formHandle, $frid, $fid) {
     // open a temporary file
     $fileName = XOOPS_ROOT_PATH."/cache/formulize_derivedValueFormulas_".str_replace(" ", "_", str_replace("/", "", str_replace("\\", "", trans($formHandle)))).".php";
     $derivedValueFormulaFile = fopen($fileName, "w");
     fwrite($derivedValueFormulaFile, "<?php\n\n");
     
     $functionsToWrite = "";
     // loop through the formulas, process them, and write them to the file
     foreach($metadata as $formulaNumber=>$thisMetaData) {
          $formula = $thisMetaData['formula'];
          while($quotePos = strpos($formula, "\"", $quotePos+1)) {
               // print $formula . " -- $quotePos<br>"; // debug code
               $endQuotePos = strpos($formula, "\"", $quotePos+1);
               $term = substr($formula, $quotePos, $endQuotePos-$quotePos+1);
               if(!is_numeric($term)) { //  AND !formulize_validFrameworkHandle($frid, $term)) { // don't need to check for a valid framework handle here, we can do it in the convert function next
                    $newterm = formulize_convertCapOrColHeadToHandle($frid, $fid, $term);
                    if($newterm == "{nonefound}") {
                         return "Syntax Error";
                    } 
               } elseif($frid) { // need to convert numeric terms to framework handles if a framework is in effect
                    return "Syntax Error";
               }
               $replacement = "display(\$entry, '$newterm')";
               $formula = str_replace($term, $replacement, $formula);
               $quotePos = $quotePos + 17 + strlen($newterm); // 17 is the length of the extra characters in the display function
          }
          $addSemiColons = strstr($formula, ";") ? false : true; // only add if we found none in the formula.
          if($addSemiColons) {
               $formulaLines = explode("\n", $formula); // \n may be a linux specific character and other OSs may require a different split
               foreach($formulaLines as $formula_id=>$thisLine) {
                    $formulaLines[$formula_id] .= ";"; // add missing semicolons
               }
               $formula = implode("\n", $formulaLines);
          }
          $functionsToWrite .= "function derivedValueFormula_".str_replace(" ", "_", str_replace("-", "", str_replace("/", "", str_replace("\\", "", trans($formHandle)))))."_".$formulaNumber."(\$entry) {\n$formula\nreturn \$value;\n}\n\n";
     }
     fwrite($derivedValueFormulaFile, $functionsToWrite. "?>");
     fclose($derivedValueFormulaFile);
     include $fileName;     
}

// THIS FUNCTION CHECKS TO SEE IF A GIVEN PIECE OF TEXT IS USED IN A FRAMEWORK AS AN ELEMENT HANDLE
// use a static array to cache results
function formulize_validFrameworkHandle($frid, $term) {
	if(!$frid) { return false; }
     static $results_array = array();
     if(isset($results_array[$term][$frid])) { return $results_array[$term][$frid]; }
     $query_result = go("SELECT * FROM " . DBPRE . "formulize_framework_elements WHERE fe_frame_id = \"$frid\" AND fe_handle = \"" . mysql_real_escape_string($term) . "\"");
     $result = count($query_result) > 0 ? true : false;
     $results_array[$term][$frid] = $result;
     return $result;
}
     
// THIS FUNCTION TAKES A STRING OF TEXT (CAPTION OR COLHEAD) AND DERIVES THE NECESSARY HANDLE OR ELEMENT ID FROM IT
// use a static array to cache results
function formulize_convertCapOrColHeadToHandle($frid, $fid, $term) {
     // first search the $fid, and then if we don't find anything, search the other forms in the $frid
     // check first for a match in the colhead field, then in the caption field
     // once a match is found, then return handleFromId if there's a $frid, otherwise, return the ID
     
     static $results_array = array();
     static $framework_results = array();
         
     $handle = "";
     $term = trim($term, "\"");
     
     if($term == "uid" OR $term == "proxyid" OR $term == "creation_date" OR $term == "mod_date" OR $term == "creator_email" OR $term == "creation_uid" OR $term == "mod_uid" OR $term == "creation_datetime" OR $term == "mod_datetime") {
        return $term;
     }
     
     if(!$frid) {
          $formList[0]['ff_form_id'] = $fid; // mimic what the result of the framework query below would be...
     } else {
          
          if(formulize_validFrameworkHandle($frid, $term)) {
               return $term;
          }
          
          if(isset($framework_results[$frid])) {
               $formList = $framework_results[$frid];
          } else {
               $formList = go("SELECT ff_form_id FROM " . DBPRE . "formulize_framework_forms WHERE ff_frame_id = \"$frid\"");
               $framework_results[$frid] = $formList;
          }
     }
     foreach($formList as $form_id) {
          if(isset($results_array[$form_id['ff_form_id']][$term][$frid])) { return $results_array[$form_id['ff_form_id']][$term][$frid]; }
          
          // first check if this is a handle
          $handle_query = go("SELECT ele_handle FROM " . DBPRE . "formulize WHERE id_form = " . $form_id['ff_form_id'] . " AND ele_handle = \"".mysql_real_escape_string($term)."\"");
          if(count($handle_query) > 0) {
               if(XOOPS_ROOT_PATH != "") {
                    include_once XOOPS_ROOT_PATH . "/modules/formulize/include/functions.php";
                    $handle = $frid ? convertElementHandlesToFrameworkHandles($term, $frid): $term;
               } else {
                    $handle = $term; // can only do the conversion of element handles to framework handles if we are in the full stack, not if we are including extract.php from outside 
               }
          } else {
               $colhead_query = go("SELECT ele_id, ele_handle FROM " . DBPRE . "formulize WHERE id_form = " . $form_id['ff_form_id']. " AND ele_colhead = \"" . mysql_real_escape_string($term) . "\"");
               if(count($colhead_query) > 0) {
                    $handle = $frid ? handleFromId($colhead_query[0]['ele_id'], $form_id['ff_form_id'], $frid) : $colhead_query[0]['ele_handle'];
               } else {
                    $caption_query = go("SELECT ele_id, ele_handle FROM " . DBPRE . "formulize WHERE id_form = " . $form_id['ff_form_id']. " AND ele_caption = \"" . mysql_real_escape_string($term) . "\"");
                    if(count($caption_query) > 0 ) {
                         $handle = $frid ? handleFromId($caption_query[0]['ele_id'], $form_id['ff_form_id'], $frid) : $caption_query[0]['ele_handle'];
                    }
               }
          }
          if($handle) {
               $results_array[$form_id['ff_form_id']][$term][$frid] = $handle;
               break;
          }     
     }
     if(!$handle) { $handle = "{nonefound}"; }
     return $handle;
}


// THIS FUNCTION QUERIES A TABLE IN THE DATABASE AND RETURNS THE RESULTS IN STANDARD getData FORMAT
// Uses the standard filter syntax, and can use scope if a uidField name is specified
// Filters cannot obviously use the standard metadata fields that are part of regular forms
// At the time of writing (Nov 1 2005) supports single table queries only, no joins
// SUPERSEDED BY THE "TABLEFORM" FEATURE
function dataExtractionDB($table, $filter, $andor, $scope, $uidField) {

	global $xoopsDB;

	// numeric filters are assumed to be queries on the primary key
	// string filters are assumed to be WHERE clauses -- note the obvious security issues with that!
	$describe_query = "DESCRIBE $table";
	$res = $xoopsDB->query($describe_query);
	if($res) {

		while($array = $xoopsDB->fetchArray($res)) {
			if($array['Key'] == "PRI") {
				$primary_field = $array['Field'];
				$break;
			}
		}
	} else {
		exit("Describe query failed for table $table");
	}

	if(is_numeric($filter)) {	
		$where_clause = "WHERE `$primary_field`=$filter";
	} elseif($filter) {
		$where_clause = "WHERE $filter";	
	} else {
		$where_clause = "";
	}

	$sql = "SELECT * FROM $table $where_clause";
	$res = $xoopsDB->query($sql);
	if($res) {
		$indexer = 0;
		while($array = $xoopsDB->fetchArray($res)) {
			foreach($array as $field=>$value) {
				if(is_numeric($field)) { continue; }		
				$masterresult[$indexer][$table][$array[$primary_field]][$field] = $value;
			}
			$indexer++;
		}
	} else {
		exit("Database query failed: $sql");
	}
	return $masterresult;
}

// THIS FUNCTION DOES A SIMPLE QUERY AGAINST A TABLE IN THE DATABASE AND RETURNS THE RESULT IN STANDARD "GETDATA" FORMAT
function dataExtractionTableForm($tablename, $formname, $fid, $filter, $andor, $limitStart, $limitSize, $sortField, $sortOrder) {

     // 2. parse the filter
     // 3. construct the where clause based on the filter and andor
     // 4. do the query
     // 5. loop through results to package them up as getdata style data
     // 6. return them

     // setup a translation table for the formulize records of the fields, so we can use that lower down in several places
     $sql = "SELECT ele_id, ele_caption FROM ".DBPRE."formulize WHERE id_form=".intval($fid);
     $res = mysql_query($sql);
     $elementsById = array();
     $elementsByCaption = array();
     $elementsByField = array();
     while($array = mysql_fetch_array($res)) {
          $field = str_replace(" " , "_", str_replace("`", "'", $array['ele_caption']));
          $id = $array['ele_id'];
          $caption = $array['ele_caption'];
          $elementsById[$id]['caption'] = $caption;
          $elementsById[$id]['field'] = $field;
          $elementsByCaption[$caption]['id'] = $id;
          $elementsByCaption[$caption]['field'] = $field;
          $elementsByField[$field]['id'] = $id;
          $elementsByField[$field]['caption'] = $caption;
     }

     $andor = $andor == "AND" ? "AND" : "OR";

     // parse the filter
     $whereClause = "";
     if(is_array($filter)) { // array filters may never be used with tableforms, but whatever...
          foreach($filter as $id=>$thisFilter) {
               // filter array syntax is:
               // $filter[0][0] -- the andor setting to use for all the filters in array 0
               // $filter[0][1] -- the filter in array 0
               if($id > 0) { // there's been a filter already
                    $whereClause .= " $andor ";
               }
               $whereClause .= "(";
               foreach($thisFilter as $thisid=>$thispart) { // loop will only execute twice
                    if($thisid == 0) {
                         $localandor = $thispart;
                         continue;
                    }
                    $whereClause .= parseTableFormFilter($thispart, $localandor, $elementsById);
               }
               $whereClause = ")";
          }
     } else {
          $whereClause = parseTableFormFilter($filter, $andor, $elementsById);
     }
     
     // query for the data
     $whereClause = $whereClause ? "WHERE $whereClause" : "";
     $sql = "SELECT * FROM $tablename $whereClause";
     //print "<br>$sql<br>";
     $res = mysql_query($sql);
     $result = array();
     $indexer = 0;
     // result syntax is:
     // [id][title of form][primary id -- meaningless in tableforms, until we need to edit entries][formulize element id][value id]
     // package up data in the format we need it
     while($array = mysql_fetch_array($res)) {
          foreach($elementsByField as $field=>$fieldDetails) {
               $result[$indexer][$formname][$indexer][$elementsByField[$field]['id']][] = $array[$field];
          }
          $indexer++;
     }
     return $result;
     
     
}

// THIS FUNCTION READS A FILTER STRING AND PARSES IT UP FOR USE IN A "TABLEFORM" WHICH IS JUST A REFERENCE TO A PLAIN DATA TABLE
function parseTableFormFilter($filter, $andor, $elementsById) {
     $whereClause = "";
     $andor = $andor == "AND" ? "AND" : "OR";
     foreach(explode("][", $filter) as $thisFilter) {
          if($thisFilter == "") {continue;}
          if($whereClause != "") {
               $whereClause .= " $andor ";
          }
          $filterParts = explode("/**/", $thisFilter);
          $operator = isset($filterParts[2]) ? $filterParts[2] : "LIKE";
          $likeparts = ($operator == "LIKE" OR $operator == "NOT LIKE") ? "%" : "";
          $whereClause .= $elementsById[$filterParts[0]]['field'] . " $operator '$likeparts" . mysql_real_escape_string($filterParts[1]) . "$likeparts'";
     }
     return $whereClause;
}


// *******************************
// FUNCTIONS BELOW ARE FOR PROCESSING RESULTS
// *******************************

// This function returns the caption, formatted for form (not formulize_form), based on the handle for the element
// assumption is that a handle is unique within a framework
// $colhead flag will cause the colhead to be returned instead of the caption
// $getAll will cause the entire framework to be examined at once, which speeds up the retrieval of headers if we need them all
function getCaption($framework, $handle, $colhead=false, $getAll=false) {
	if(is_numeric($framework)) {
		$frid[0]['frame_id'] = $framework;
	} else {
		$frid = go("SELECT frame_id FROM " . DBPRE . "formulize_frameworks WHERE frame_name = '".mysql_real_escape_string($framework)."'");
	}
  static $cachedCaptions = array();
  if(!isset($cachedCaptions[$frid[0]['frame_id']][$handle])) {
     global $xoopsDB;
     if($getAll) {
          $sql = "SELECT t2.ele_caption, t2.ele_colhead, t1.fe_handle FROM " . DBPRE . "formulize_framework_elements as t1, ".DBPRE."formulize as t2 WHERE t1.fe_frame_id = '" . $frid[0]['frame_id'] . "' AND t1.fe_element_id=t2.ele_id";
     } else {
          $sql = "SELECT t2.ele_caption, t2.ele_colhead, t1.fe_handle FROM " . DBPRE . "formulize_framework_elements as t1, ".DBPRE."formulize as t2 WHERE t1.fe_frame_id = '" . $frid[0]['frame_id'] . "' AND t1.fe_handle = '".mysql_real_escape_string($handle)."' AND t1.fe_element_id=t2.ele_id";
     }
     if(!$elementData = $xoopsDB->query($sql)) {
          print "Error: could not retrieve caption for element '$handle' with this SQL:<br>$sql";
     }
     while($array = $xoopsDB->fetchArray($elementData)) {
          $cachedCaptions[$frid[0]['frame_id']][$array['fe_handle']]['caption'] = $array['ele_caption'];
          $cachedCaptions[$frid[0]['frame_id']][$array['fe_handle']]['colhead'] = $array['ele_colhead'];
     }
  }
	if($colhead AND $cachedCaptions[$frid[0]['frame_id']][$handle]['colhead'] != "") {
		return $cachedCaptions[$frid[0]['frame_id']][$handle]['colhead'];
	} else {
		return $cachedCaptions[$frid[0]['frame_id']][$handle]['caption'];
	}
}


// returns the form handle for the form that the given handle belongs to for the given framework
// DEPRECATED, USE getFormHandleFromEntry instead
// Iterating through the $entry array a couple times is ten times faster than hitting the database over and over again (according to one set of test data, presumably benefit would decrease as datasets get more complex and looping requires more iterations)
function getFormHandle($framework, $handle) {
	if(is_numeric($framework)) {
		$frid = $framework;
	} else {
		$frid_q = go("SELECT frame_id FROM " . DBPRE . "formulize_frameworks WHERE frame_name = '$framework'");
		$frid = $frid_q[0]['frame_id'];
	}
	$fid = go("SELECT fe_form_id FROM " . DBPRE . "formulize_framework_elements WHERE fe_frame_id = '$frid' AND fe_handle='$handle'");
	$formhandle = go("SELECT ff_handle FROM " . DBPRE . "formulize_framework_forms WHERE ff_frame_id = '$frid' AND ff_form_id='" . $fid[0]['fe_form_id'] . "'");
	return $formhandle[0]['ff_handle'];
}

// returns the form handle for the form that the given element handle belongs to
function getFormHandleFromEntry($entry, $handle) {
     if(is_array($entry)) {
     	 foreach($entry as $formHandle=>$record) {
     		 foreach($record as $elements) {
            if(array_key_exists($handle, $elements)) { return $formHandle; }
     		 }
       }
     } else {
        return "";// exit("Error: no form handle found for element handle '$handle'");        
     }
}

// returns all the form handles for the given entry
function getFormHandlesFromEntry($entry) {
	if(is_array($entry)) {
		return array_keys($entry);
	}
	return "";// exit("Error: no form handle found for element handle '$handle'");
}

// THIS FUNCTION IS USED AFTER THE MASTERRESULT HAS BEEN RETURNED.  THIS FUNCTION RETURNS THE VALUE OF THE CORRESPONDING ELEMENT HANDLE FOR THE GIVEN MASTER ENTRY ID.  
// Returns the value if there's only one, or an array if there are more than one
// $entry is normally the master result MINUS the id, ie: it's everything after the initial ID key, so one complete entry from the master result array
// if $id is specified, then $entry is assumed to be the entire result set, in which case the $id is used to isolate the specific entry in the set you want
// $localid is the id of a specific entry to get, ie: if there is more than one instance of handle and we only want one in particular

function display($entry, $handle, $id="NULL", $localid="NULL") {

  if(is_numeric($id)) {
		$entry = $entry[$id];
	}
	
  if(!$formhandle = getFormHandleFromEntry($entry, $handle)) { return ""; } // return nothing if handle is not part of entry

	foreach($entry[$formhandle] as $lid=>$elements) {
		if($localid == "NULL" OR $lid == $localid) {
			if(is_array($elements[$handle])) {
				foreach($elements[$handle] as $value) {
					$foundValues[] = $value;
				}
			} else { // the handle is for metadata
				return $elements[$handle];
			}
		}
	}
  
	if(count($foundValues) == 1) {
		return $foundValues[0];
	} else {
		return $foundValues;
	}
}

// this function puts the results of a display call together into a string using the separator specified.  Allows filtering based on a specific localid of an entry in the given master result entry
// intended for use when there is more than one value that will match
// used for export of data
function displayTogether($entry, $handle, $sep, $id="NULL", $localid="NULL") {
	$result = display($entry, $handle, $id, $localid);
	if(is_array($result)) {
		$result = implode($sep, $result);
	}
	return $result;
}

// this function actually formats a string into a list based on the returns (treats more than one return as one)
// will not turn a single value into a list, just returns the value it was sent in that case
function makeList($string, $type="bulleted") {
	if($type == "numbered") {
		$type = "ol>";
	} else {
		$type = "ul>";
	} 
	$listItems = explode("\r", $string);
	if(count($listItems)>1) {
		$list = "<" . $type;
		foreach($listItems as $item) {
			if(trim($item) != "") { // exclude empty items, ie: this accounts for multiple "returns" at the end of a line
				$list .= "<li>" . trim($item) . "</li>";
			}
		}
		$list .= "</" . $type;
		return $list;
	} else {
		return $string;
	}
}

// this function actually formats a string into a series of HTML paragraphs

function makePara($string, $parasToReturn="NULL") {
	if(strstr($string, "\n\r")) {
		$paras = explode("\n\r", $string);
	} elseif(strstr($string, "\n")) {
		return "<p>" . $string . "</p>";
	} else {
		$paras = explode("\r", $string);
	}
	if(count($paras)>1) {
		$ptr = explode(",", $parasToReturn);
		$counter = 0;
		foreach($paras as $item) {
			if(trim($item) != "") { // exclude empty items, ie: this accounts for multiple "returns" at the end of a line
				if(($parasToReturn == "NULL" AND !is_numeric($parasToReturn)) OR in_array($counter, $ptr)) { // only return paras requested, if specific paras were requested
					$para .= "<p>" . trim($item) . "</p>";
				}
				$counter++;
			}
		}
		return $para;
	} else {
		return "<p>" . $string . "</p>";
	}
}

// this function actually formats a string into a series of BR separated items

function makeBR($string) {
	$brs = explode("\r", $string);
	if(count($brs)>1) {
		$start = 1;
		foreach($brs as $br) {
			if(trim($br) != "") { // exclude empty items, ie: this accounts for multiple "returns" at the end of a line
				if($start) {
					$output .= trim($br);
					$start = 0;
				} else {
					$output .= "<br>" . trim($br);
				}
			}
		}
		return $output;
	} else {
		return $string;
	}
}

// this function returns the contents of a text area as a series of HTML formatted paragraphs

function displayPara($entry, $handle, $id="NULL", $parasToReturn="NULL") {
	$values = display($entry, $handle, $id);
	if(is_array($values)) {
		foreach($values as $value) {
			$para[] = makePara($value, $parasToReturn);
		}	
	} else {
		$para = makePara($values, $parasToReturn);
	}
	return $para;
}

// this function returns the contents of a text are with a BR between each line
function displayBR($entry, $handle, $id="NULL") {

	$values = display($entry, $handle, $id);
	if(is_array($values)) {
		foreach($values as $value) {
			$br[] = makeBR($value);
		}	
	} else {
		$br = makeBR($values);
	}
	return $br;
}

// THIS FUNCTION returns an HTML formatted string that will display a bulleted or numbered list, based on each paragraph in a text area element 

function displayList($entry, $handle, $type="bulleted", $id="NULL", $localid="NULL") {
	$values = display($entry, $handle, $id, $localid);
	if(is_array($values)) {
		foreach($values as $value) {
			$list[] = makeList($value, $type);
		}	
	} else {
		$list = makeList($values, $type);
	}
	return $list;
}

// THIS FUNCTION RETURNS AN ARRAY OF ALL THE INTERNAL IDS ASSOCIATED WITH THE ENTRIES OF A PARTICULAR FORM
// $formhandle can be an array of handles, or if not specified then all entries for all handles are returned
// If formhandle is an array, then $ids becomes a two dimensional array:  $ids[$formhandle][] = $id
function internalRecordIds($entry, $formhandle="", $id="NULL", $fidAsKeys = false) {
	if(is_numeric($id)) {
		$entry = $entry[$id];
	}
	if(!$formhandle) {
		$formhandle = getFormHandlesFromEntry($entry);
	}
	if(is_array($formhandle)) {
		foreach($formhandle as $handle) {
			foreach($entry[$handle] as $id=>$element) {
				if($fidAsKeys) {
					$fid = getFormIdFromHandle($handle); // note, not guaranteed to return proper fid!  form handles are not unique across all frameworks, so incorrect value may be returned here.  Also, if the form is not in a framework, then no value will be returned!
					$ids[$fid][] = $id;
				} else {
					$ids[$handle][] = $id;
				}
			}
		}
	} else {
		foreach($entry[$formhandle] as $id=>$element) {
			$ids[] = $id;
		}
	}
	return $ids;
}

// THIS FUNCTION SORTS A RESULT SET, BASED ON THE VALUES OF ONE NON-MULTI FIELD (IE: CANNOT SORT BY CHECKBOX FIELD)
function resultSort($data, $handle, $order="", $type="") {
	foreach($data as $id=>$entry) {
		$values = display($entry, $handle);
		if(is_array($values)) {
			if($order == "SORT_DESC") {
				$sortedValues = rsort($values);
			} else {
				$sortedValues = sort($values);
			}
			$sortAux[] = strtoupper($values[0]); // this way, the best value from the array will be used
		} else {
			$sortAux[] = strtoupper($values);
		}
	}

	// default is sort ascending, regularly
	if($order == "SORT_DESC") {
		if($type == "SORT_NUMERIC") {
			array_multisort($sortAux, SORT_DESC, SORT_NUMERIC, $data);
		} elseif($type == "SORT_STRING") {
			array_multisort($sortAux, SORT_DESC, SORT_STRING, $data);
		} else {
			array_multisort($sortAux, SORT_DESC, SORT_REGULAR, $data);
		}
	} else {
		if($type == "SORT_NUMERIC") {
			array_multisort($sortAux, SORT_ASC, SORT_NUMERIC, $data);
		} elseif($type == "SORT_STRING") {
			array_multisort($sortAux, SORT_ASC, SORT_STRING, $data);
		} else {
			array_multisort($sortAux, SORT_ASC, SORT_REGULAR, $data);
		}
	}

	return $data;
}

// THIS FUNCTION SORTS A RESULT SET BASED ON THE PREVELANCE OF THE SPECIFIED WORDS IN THE SPECIFIED HANDLES
// weighting can be given to particular handles through the optional $weight array
function resultSortRelevance($data, $handleArray, $wordArray, $weight="") {
	if(!$weight) {
		// if no weights specified then build a weight array with equal weighting for all handles
		unset($weight);
		for($i=0;$i<count($handleArray);$i++) {
			$weight[] = 1;
		}
	}

	// 1. loop through entire dataset
	// 2. get a count of occurances of each word in each handle
	// 2a. multiply occurances by weighting
	// 3. record total "hits" for each entry in an array
	// 4. sort the array of hits and sort the dataset according to this array

	foreach($data as $id=>$entry) {
		foreach($wordArray as $word) {
			for($i=0;$i<count($handleArray);$i++) {
				$hits = countHits($entry, $handleArray[$i], $word);
				$weightedhits = $hits * $weight[$i];
				$wordScores[$id] += $weightedhits;
			}
		}
	}

	array_multisort($wordScores, SORT_DESC, SORT_NUMERIC, $data);
	
	return $data;
}

// THIS FUNCTION IS USED BY THE RESULTSORTRELEVANCE FUNCTION TO COUNT THE OCCURANCES OF A WORD IN A FIELD
function countHits($entry, $handle, $word) {
	$value = display($entry, $handle);
	if(is_array($value)) { $value = implode(" ", $value); }
	$hits = substr_count(strtolower($value), strtolower($word));
	return $hits;
}

function displayMeta($entry, $spechandle, $id="NULL", $localid="NULL") {
     static $cachedDisplayMeta = array();
     if(isset($cachedDisplayMeta[$entry][$spechandle][$id][$localid])) {
          return $cachedDisplayMeta[$entry][$spechandle][$id][$localid];
     }
	if(is_numeric($entry))
    {
	    switch($spechandle) {
	        case "creation_uid-name":
	            $name = go("SELECT name FROM " . DBPRE .
	            	"users WHERE uid=$entry");
               $cachedDisplayMeta[$entry][$spechandle][$id][$localid] = $name[0][0];
	            return $name[0][0]; 
		    break;
		}
    }

	// evaluate spechandle to determine what the user is asking for
	// ie:
	// uid-name is the username of the user who created the entry
	// uid-fullname is the full name of the user who created the entry
	// proxyid-name is the username of the user who last modified the entry
	// mod_date-days is the number of days since the entry was last modified
	// etc, we could concievably have a zillion of these extra 
	// parts-after-the-hyphen for the handles
	// The parts before the hyphen are the standard four metadata handles 
	// as specified in the Using Formulize pdf

	// so, flow of the code...

	// determine which meta handle is being used, by parsing the 
	// $spechandle string (I guess the handle and the part-after-the-hyphen 
	// could actually be two separate params to eliminate this step)
	// note that I may have php syntax errors in here all over the place!
	// $item will be the part-after-the-hyphen
	list($handle, $item) = explode("-", $spechandle);

	// use the existing display function to get the "raw" value
	$values = display($entry, $handle, $id, $localid);


	// using the "raw" value, do whatever is necessary to turn this into 
	// the actual value that has been requested.
	if(is_array($values)) {
	    // do something here to handle arrays, ie: if there is more than one 
	    // value that gets returned
	    // basically, this will be the same as what happens below, just with 
	    // some looping going on to handle the multiple values in the array
	    // obviously, this maybe should involve the use of functions to avoid 
	    // repeating code
	} else {
	    switch($item) {
	        case "name":
	            // some basic checking for coherence in the inputs....this could 
	            // happen above obviously, or not at all(!)
	            if($handle != "creation_uid" AND $handle != "mod_uid") {
		            exit("invalid item requested for handle $handle");
	            }
	            // "go" works exactly like the q function, but it only exists in the 
	            // extraction layer.
	            // also note the use of DBPRE and not any $xoopsDB stuff
	            $name = go("SELECT name FROM " . DBPRE .
	            	"users WHERE uid=$values");
               $cachedDisplayMeta[$entry][$spechandle][$id][$localid] = $name[0][0];
	            return $name[0][0]; 
		    break;
		}
	}        
}


function getFormIdFromHandle($handle)
{
	$sql = "SELECT t1.id_form  " .
		" FROM " . DBPRE . "formulize_id AS t1, " . DBPRE . "formulize_framework_forms AS t2" .
		" WHERE t1.id_form = t2.ff_form_id AND t2.ff_handle = '$handle';";

	//die($sql);
    
	$id_form_results = go($sql);
	if(count($id_form_results) == 0) { // look for the fid based on the desc_form
		$sql = "SELECT id_form FROM " . DBPRE . "formulize_id WHERE desc_form = \"" . mysql_real_escape_string($handle) . "\"";
		$id_form_results = go($sql);
	}
            
	//var_dump($id_form_results);

	return $id_form_results[0]['id_form']; 
}


// if XOOPS has not already connected to the database, then connect to it now using user defined constants that are set in another file
// the idea is to include this file from another one

global $xoopsDB, $myts;

if(!$xoopsDB) {
	// SET DB PREFIX -- must be done for each installation if the installation uses a prefix other than "xoops_"
	define("DBPRE", "xoops_");
	connect(DBHOST, DBUSER, DBPASS, DBNAME);
	// language translation table if xoops own objects (and presumably language files) have not been invoked
	if(LANG == "English") {
		define("_formulize_TEMP_QYES", "Yes");
		define("_formulize_TEMP_QNO", "No");
		define("_formulize_OPT_OTHER", "Other: ");
	}
	if(LANG == "French") {
		define("_formulize_TEMP_QYES", "Oui");
		define("_formulize_TEMP_QNO", "Non");
		define("_formulize_OPT_OTHER", "Autre: ");
	}
        $LOE_limit = 0;
} else {
	define("DBPRE", $xoopsDB->prefix('') . "_");
	if(!defined("_formulize_OPT_OTHER")) {
		global $xoopsConfig;
		switch($xoopsConfig['language']) {
			case "french":
				define("_formulize_OPT_OTHER", "Autre: ");
				define("_formulize_TEMP_QYES", "Oui");
				define("_formulize_TEMP_QNO", "Non");
				break;
			case "english":
			default:
				define("_formulize_OPT_OTHER", "Other: ");
				define("_formulize_TEMP_QYES", "Yes");
				define("_formulize_TEMP_QNO", "No");
				break;
		} 
	}
        include_once XOOPS_ROOT_PATH . "/modules/formulize/include/functions.php";
        $config_handler =& xoops_gethandler('config');
	$formulizeModuleConfig =& $config_handler->getConfigsByCat(0, getFormulizeModId()); // get the *Formulize* module config settings
 	$GLOBALS['formulize_LOE_limit'] = $formulizeModuleConfig['LOE_limit'];
        
}

function formulize_benchmark($text) {
     global $xoopsUser;
     if(isset($GLOBALS['startPageTime']) AND $xoopsUser) {
          if($xoopsUser->getVar('uid') == 4613) {
               $currentPageTime = microtime_float();
               print "<br>$text -- Elapsed: ".($currentPageTime-$GLOBALS['startPageTime'])."<br>";
          }
     }
}


if(!$myts) {
  	// setup text sanitizer too
	$basePath = str_replace("modules/formulize/include/extract.php", "", __FILE__);
	$basePath = str_replace("modules\formulize\include\extract.php", "", $basePath);
	include_once $basePath."/class/module.textsanitizer.php";
	$myts = new MyTextSanitizer();
	$GLOBALS['myts'] = $myts;
}

?>