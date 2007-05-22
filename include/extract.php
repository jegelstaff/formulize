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

// FUNCTION RETURNS A FILTER FOR AN SQL QUERY
function sqlfilter($list, $field) {
	$start = 1;
	$filter = "(";
	foreach($list as $value) {
		if(!$start) { 
			$filter .= " OR ";
		}
		$start = 0;
		$filter .= "$field='$value'";
	}
	$filter .= ")";
	return $filter;
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

// FUNCTION RETURNS ALL THE VALUES IN A GIVEN FIELD FROM A RESULT SET.
// logic is based on raw result format, not final
// optional filter param will limit results to rows where filter is true
// filter is flat array that is made up of following elements separated by ][
// fieldname][operator][searchterm
// valid operator values are:
// ==
// <
// >
// <=
// >=
// LIKE
// NOT
// NOT LIKE
// optional single param tells the function to stop after getting the first hit
function fieldValues($results, $field, $filter="", $single="") {
	// check for a filter and set params accordingly
	if($filter) {
		$filterArray = explode("][", $filter);
		$filterField = $filterArray[0];
		$filterOp = $filterArray[1];
		$filterTerm = $filterArray[2];
	}
	foreach($results as $row) {
		// end search if single is set and we've found something
		if($single AND $values[0]) { break; }
		if(!$filter) {
			$values[] = $row[$field];
		} else {
			switch($filterOp) {
				case "<":				
					if($row[$filterField] < $filterTerm) {
						$values[] = $row[$field];
					}
					break;
				case ">":				
					if($row[$filterField] > $filterTerm) {
						$values[] = $row[$field];
					}
					break;
				case "=<":				
				case "<=":
					if($row[$filterField] <= $filterTerm) {
						$values[] = $row[$field];
					}
					break;
				case "=>":				
				case ">=":
					if($row[$filterField] >= $filterTerm) {
						$values[] = $row[$field];
					}
					break;
				case "LIKE":				
					if(strstr($row[$filterField], $filterTerm)) {
						$values[] = $row[$field];
					}
					break;
				case "NOT LIKE":
					if(!strstr($row[$filterField], $filterTerm)) {
						$values[] = $row[$field];
					}
					break;
				case "NOT":
					if($row[$filterField] != $filterTerm) {
						$values[] = $row[$field];
					}
					break;
				case "==":
				default:
					if($row[$filterField] == $filterTerm) {
						$values[] = $row[$field];
					}
					break;
			}
		}
	}
	return $values;
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

// THIS FUNCTION CHECKS TO SEE IF THE ELEMENT IN THE FORM IS A FULLNAME/USERNAME LIST OR NOT
function isNameList($fid, $ffcaption) {
	$caption = str_replace("`", "'", $ffcaption);
	$evq = go("SELECT ele_value FROM " . DBPRE . "formulize WHERE id_form = " . intval($fid) . " AND ele_caption = '" . mysql_real_escape_string($caption) . "'");
	$ele_value = unserialize($evq[0]['ele_value']);
	if(!is_array($ele_value[2])) { return false; }
	$key1 = key($ele_value[2]);
	if($key1 == "{USERNAMES}" OR $key1 == "{FULLNAMES}") {
		return $key1;
	} else {
		return false;
	}
}


// this is a copy of the regular makeUidFilter function in the functions.php file, but since extract.php must standalone for when it's called by outside sites, we have to make an independently named copy here.
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
function prepvalues(&$value, $type, $islinksource, $id_req, $ffcaption, $fid) {

	// handle cases where the value is linked to another form 

	$templinkedvals2 = "";
	if($islinksource) {
		$templinkedvals = explode("#*=:*", $value);
		$templinkedvals2 = explode("[=*9*:", $templinkedvals[2]);
//		print "templinkedvals for display: ";
//		print_r($templinkedvals2);
		$value = "";
		foreach($templinkedvals2 as $anentry)
		{
			$textq = go("SELECT ele_value FROM " . DBPRE . "formulize_form WHERE ele_id=$anentry GROUP BY ele_value ORDER BY ele_value");
			if(isset($textq[0])) {
				$value .= "*=+*:" . $textq[0]['ele_value'];
			} else {
				$value .= "*=+*:";
			}
		}
	}
//print "<br>$value<br>";
	// handle fullnames or usernames
	// 1. check type, fid/caption
	// 2. if this is a fullnames/usernames situation, then get the usernames and replace the ids with them
	if($type == "select" AND $listtype = isNameList($fid, $ffcaption)) {
		$uids = explode("*=+*:", $value);
		if(count($uids) > 1) { array_shift($uids); }
		$uidFilter = extract_makeUidFilter($uids);		
		$listtype = $listtype == "{USERNAMES}" ? 'uname' : 'name';
		$names = go("SELECT $listtype FROM " . DBPRE . "users WHERE $uidFilter ORDER BY $listtype");
		$value = "";
		foreach($names as $thisname) {
			$value .= "*=+*:" . $thisname[$listtype];
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

	// $value = stripslashes($value); should not be needed after patch 22

	//and remove any leading *=+*: while we're at it...
	if(substr($value, 0, 5) == "*=+*:")	{
		$value = substr_replace($value, "", 0, 5);
	}

	// Convert 'Other' options into the actual text the user typed
	if(($type == "radio" OR $type == "checkbox") AND preg_match('/\{OTHER\|+[0-9]+\}/', $value)) {
		// convert ffcaption to regular and then query for id
		$realcap = str_replace("`", "'", $ffcaption);
		$newValueq = go("SELECT other_text FROM " . DBPRE . "formulize_other, " . DBPRE . "formulize WHERE " . DBPRE . "formulize_other.ele_id=" . DBPRE . "formulize.ele_id AND " . DBPRE . "formulize.ele_caption=\"" . mysql_real_escape_string($realcap) . "\" AND " . DBPRE . "formulize_other.id_req='$id_req' LIMIT 0,1");
		$value_other = _formulize_OPT_OTHER . $newValueq[0]['other_text'];
		$value = preg_replace('/\{OTHER\|+[0-9]+\}/', $value_other, $value); 
	}


	return $templinkedvals2; // used for including in the linkfilters -- added sept 28 2005
//	return $value; // not necessary if passing by reference
}

// this function returns the framework handle for an element when given the caption (as formatted for formulize_form)
function handle($cap, $fid, $frid) {
	// switch to the actual format used in 'form' table
	$cap = eregi_replace ("`", "'", $cap);
	$cap = addslashes($cap);
	$idq = go("SELECT ele_id FROM " . DBPRE . "formulize WHERE ele_caption='$cap' AND id_form = '$fid'");	
	$handleq = go("SELECT fe_handle FROM " . DBPRE . "formulize_framework_elements WHERE fe_frame_id = '$frid' AND fe_form_id = '$fid' AND fe_element_id = '" . $idq[0]['ele_id'] . "'");
	return $handleq[0]['fe_handle'];
}

// this function returns the handle for an element when given the id
function handleFromId($id, $fid, $frid) {
	if($id === "uid" OR $id === "proxyid" OR $id === "creation_date" OR $id === "mod_date" OR $id === "creator_email") { return $id; }
	$handle = go("SELECT fe_handle FROM " . DBPRE . "formulize_framework_elements WHERE fe_frame_id = '$frid' AND fe_form_id = '$fid' AND fe_element_id = '$id'");
	return $handle[0]['fe_handle'];
}

// this function converts a results array into the finalresults format
// $results is actual raw array
// $fid is formid
// $frid is framework id
// $linkkeys is an array of the the handles which this form uses to link to others -- only used to identify the linked values that a main form needs to use to limit queries in linked forms
// $linkisparent is an array of flags that indicates whether a result set is the parent or child in a link relationship (ie: the source of the values or the receiver of the values).  For parent forms, we need to record the ele_id of the key, for child forms, we need to record the prep'd value.  The keys of this array correspond to the keys of linkkeys.
// $linkformids is an array of the ids of the linked forms.  the keys correspond to the keys of linkkeys.
// $linktargetids is the ele_ids of the elements in the linked form that these handles/values link up to
function convertFinal($resultsRaw, $fid, $frid="", $linkkeys="", $linkisparent="", $linkformids="", $linktargetids="", $linkcommonvalue="") {

debug_memory("Start of covertFinal");

	// get the handle for this form, of the full name if not
	if($frid) {
		$formHandle = go("SELECT ff_handle FROM " . DBPRE . "formulize_framework_forms WHERE ff_frame_id = '$frid' AND ff_form_id = '$fid'");
	} else {
		$formHandle = go("SELECT desc_form FROM " . DBPRE . "formulize_id WHERE id_form='$fid'");
	}

	// need to get master list of expected handles
          // also store any information about derived columns for use later -- March 27 2007
          $GLOBALS['formulize_calcDerivedColumnsData'][$fid] = array();
	$fullCapList = go("SELECT ele_caption, ele_id, ele_value, ele_type FROM " . DBPRE . "formulize WHERE id_form = '$fid' AND ele_display != \"0\"");
	foreach($fullCapList as $acap) {
		if($frid) {
			$fullHandleList[$acap['ele_caption']] = handle($acap['ele_caption'], $fid, $frid);
		} else { // use full caption if we're doing just a form
			$fullHandleList[$acap['ele_caption']] = $acap['ele_id'];
		}
                if($acap['ele_type'] == "derived") {
                    $GLOBALS['formulize_calcDerivedColumnsData'][$fid][] = array('handle'=>$fullHandleList[$acap['ele_caption']], 'formula'=>unserialize($acap['ele_value']), 'formhandle'=>$formHandle[0][0]);
                }
	}

	$foundHandleList = array();	
	$finalidreqs = array();
	$linkindexer=0;
        $totalResultRows = mysql_num_rows($resultsRaw);
        $results = array();
	for($i=0;$i<$totalResultRows;$i++) {
               $results = mysql_fetch_array($resultsRaw);
               	$islinksource = strstr($results['ele_value'], "#*=:*");
		$linkids = prepvalues($results['ele_value'], $results['ele_type'], $islinksource, $results['id_req'], $results['ele_caption'], $fid); // linkids is an array of the ele_ids of the values that this field links to, if it is a linked field.
		$cap = eregi_replace ("`", "'", $results['ele_caption']);  // convert caption to _formulize table format so that we can get the handle from the full Handle List which has array keys drawn from that table
		$handle = $fullHandleList[$cap];
//		$foundHandleList[$results['id_req']] = array();
		// check to see that we haven't already stored this value for this entry (takes care of situations where the database as duplicate entries with the same id_req, causing the number of values in results array to be greater than it ought to be -- problem likely caused by users clicking on the submit button more than one time?
		if(!isset($foundHandleList[$results['id_req']][$handle])) {
			$values = explode("*=+*:", $results['ele_value']);
			foreach($values as $id=>$value) {
				$finalresults[$formHandle[0][0]][$results['id_req']][$handle][$id] = $value;
				if(!isset($finalidreqs[$results['id_req']])) {
					$finalidreqs[$results['id_req']] = $results['id_req'];
				}
			}
			// store any values involved in a link so we can filter out the main queries on linked forms
			// major performance improvement by doing this
			if(is_array($linkkeys)) {
				if($keys = array_keys($linkkeys, $handle) AND $results['ele_value']) { // if these values are the ones the link is based on...
					foreach($keys as $key) {
						$linkvalue['fid'][$linkindexer] = $linkformids[$key];
						$linkvalue['targetid'][$linkindexer] = $linktargetids[$key];
						if($linkcommonvalue[$key]) { // link by common values, so record the current value
							$linkvalue['values'][$linkindexer] = $results['ele_value'];
						} elseif($islinksource) { // link by ids related to linked selectboxes...
							$linkvalue['values'][$linkindexer] = $linkids;
						} else {
							$linkvalue['values'][$linkindexer] = $results['ele_id'];
						}
						$linkindexer++;
					}
				}
			} // end of if isarray
			// flag the current handle as found in the fullhandlelist
			$foundHandleList[$results['id_req']][$handle] = $handle;
		}
     }

debug_memory("after building main array");

	// add in blanks
	// if there are missing handles, then check to see which ones were not found and add them to the array with a null value
	foreach($foundHandleList as $idreq => $foundHandles) {
		if(count($foundHandles) < count($fullHandleList)) {
			foreach($fullHandleList as $handle) {
				if(!isset($foundHandles[$handle])) {
					$finalresults[$formHandle[0][0]][$idreq][$handle][0] = "";
				}
			}
		} elseif(count($foundHandles) > count($fullHandleList)) {
			sort($foundHandles);
			sort($fullHandleList);
			print "IDREQ: $idreq<br>";
			print "<table>";
			print "<tr><td valign=top>";
			foreach($fullHandleList as $fh) {
				print "<p>$fh</p>";
			}
			print "</td><td valign=top>";
			foreach($foundHandles as $fh) {
				print "<p>$fh</p>";
			}
			print "</td></tr></table>";
			exit("Error: more handles found for an entry than there are elements in form $fid");
		}
		unset($foundHandleList[$idreq]);
	}
debug_memory("after adding blanks");
	// assign metadata (uid, date, proxyid, creation_date)
	if(isset($finalresults)) { 
		foreach($finalresults[$formHandle[0][0]] as $record=>$value) {
			$metadata = go("SELECT uid, date FROM " . DBPRE . "formulize_form WHERE id_req='$record' AND date>0 ORDER BY date DESC LIMIT 0,1");
			$metadata_proxyid = go("SELECT proxyid FROM " . DBPRE . "formulize_form WHERE id_req='$record' AND proxyid != uid ORDER BY date DESC LIMIT 0,1");
			$metadata_creation_date = go("SELECT creation_date FROM " . DBPRE . "formulize_form WHERE id_req='$record' AND creation_date>0 ORDER BY creation_date ASC LIMIT 0,1");
			// NOTE: the four keys used below are "reserved terms" that cannot be used as handles for elements in the framework
			$finalresults[$formHandle[0][0]][$record]['uid'] = $metadata[0]['uid'];
			$GLOBALS['formulize_extraction_uid_list'][] = $metadata[0]['uid']; // added September 24 2006 so we can refer to these uids later and get their e-mails
			$finalresults[$formHandle[0][0]][$record]['mod_date'] = $metadata[0]['date'];
			if(isset($metadata_proxyid[0])) {
				$finalresults[$formHandle[0][0]][$record]['proxyid'] = $metadata_proxyid[0]['proxyid'];
			} else {
				$finalresults[$formHandle[0][0]][$record]['proxyid'] = $metadata[0]['uid'];
			}
			$finalresults[$formHandle[0][0]][$record]['creation_date'] = $metadata_creation_date[0]['creation_date'];
			if(is_array($linkkeys)) {
				if($keys = array_keys($linkkeys, "")) { // find all links based on uids...
					foreach($keys as $thiskey) {
						$linkvalue['fid'][$linkindexer] = $linkformids[$thiskey];
						$linkvalue['uid'][$linkindexer] = $metadata[0]['uid'];
					}
					$linkindexer++;
				}
			} // end if is_array
		}
	} else {
		$finalresults = "";
	}

debug_memory("before returning convertFinal results");
	//array_values($finalresults); // don't understand what this is for
	if(!isset($linkvalue)) { $linkvalue = "";	}
	return array($finalresults, $linkvalue, $finalidreqs);
}

// This function returns the caption, formatted for formulize_form, based on the handle for the element
function getCaptionFF($handle, $frid, $fid) {
	$elementId = go("SELECT fe_element_id FROM " . DBPRE . "formulize_framework_elements WHERE fe_frame_id = '$frid' AND fe_form_id = '$fid' AND fe_handle = '$handle'");
	$caption = go("SELECT ele_caption FROM " . DBPRE . "formulize WHERE ele_id = '" . $elementId[0]['fe_element_id'] . "'"); 
	$ffcaption = eregi_replace ("&#039;", "`", $caption[0]['ele_caption']);
	$ffcaption = eregi_replace ("&quot;", "`", $ffcaption);
	$ffcaption = str_replace ("'", "`", $ffcaption);
	return $ffcaption;
}

function getCaptionFFbyId($id, $frid, $fid) {
	$caption = go("SELECT ele_caption FROM " . DBPRE . "formulize WHERE ele_id = '$id' AND id_form='$fid'"); // must query by id and fid, even though id is unique, since it's possible to pass an id and fid that do not belong together, and in that case, we need to return nothing.  In that case, if we queried only based on ID, we would return something.
	$ffcaption = eregi_replace ("&#039;", "`", $caption[0]['ele_caption']);
	$ffcaption = eregi_replace ("&quot;", "`", $ffcaption);
	$ffcaption = str_replace ("'", "`", $ffcaption);
	return $ffcaption;
}

function makeFilter($filter, $frid="", $fid, $andor, $scope, $linkform=0) {
	if(is_array($filter)) {
		foreach($filter as $id=>$thisfilter) {
			// filter array syntax is:
			// $filter[0][0] -- the andor setting to use for all the filters in array 0
			// $filter[0][1] -- the filter in array 0
			// check what the boolean is for this array
			// then for remaining filters in the array, run makeFilterInternal on them and then merge the results or not depending on the andor for that array
			// lastly, merge all the results returned from all filter arrays according to the $andor setting passed to this function
			foreach($thisfilter as $thisid=>$thispart) {
				if($thisid==0) {
					$localandor = $thispart;
					continue;
				}
				$thisMadeFilter[$id] = makeFilterInternal($thispart, $frid, $fid, $localandor, $scope, $linkform);
				$thisMadeFilter[$id] = substr($thisMadeFilter[$id], 5);				
			}
		}
		if($andor != "AND" AND $andor != "OR") {
			print "Unknown boolean operator requested as part of data extraction.";
			exit;
		}
		$start=1;
		$madeFilter = "";
		foreach($thisMadeFilter as $oneFilter) {	
			if($oneFilter == "") { continue; }
			if($start) {
				$madeFilter = " AND (($oneFilter)";
				$start=0;
			} else {
				$madeFilter .= " $andor ($oneFilter)";
			}
		}
		if(!$start) { $madeFilter .= ")"; }
	} else {
		$madeFilter = makeFilterInternal($filter, $frid, $fid, $andor, $scope, $linkform);
	}
	return $madeFilter;
}

// DETERMINE THE FILTER TO USE ON THE MAIN FORM
// NOTE: FILTER USES A LIKE QUERY TO MATCH, SO IT WILL PICKUP THE FILTER TERM ANYWHERE IN THE VALUE OF THE FIELD
// This loop can be optimized further in the case of AND queries, by using the previously found id_reqs as part of the WHERE clause for subsequent searches
function makeFilterInternal($filter, $frid="", $fid, $andor, $scope, $linkform) {

	global $myts;

	$filterNotForThisForm = 0;
	if($filter) {
		if(is_numeric($filter)) {
			$filter = " AND id_req='$filter'";
			return $filter;
		} else { 
			// have to pre-query for id_reqs and then generate a filter based on that.
			$filters = explode("][", $filter);
			foreach($filters as $afilter) {
				$filterparts = explode("/**/", $afilter);
				if($filterparts[1] == "{BLANK}") { $filterparts[1] = ""; } // not sure how this will work, since if there is no value in an element, it won't exist in the database due to the way the formulize_form table is structured
				if(is_numeric($filterparts[0]) AND $filterparts[0] == $afilter) { // if this is a numeric value, then we must treat it specially
					$capforfilter = "id_req";
					$filterNotForThisForm++; // just in case nothing is found, we don't treat this as a filter that is for this form since we don't know which form it might apply to
				} elseif($filterparts[0] == "uid" OR $filterparts[0] == "proxyid" OR $filterparts[0] == "creation_date" OR $filterparts[0] == "mod_date" OR $filterparts[0] == "creator_email") {
					if(!$linkform) {
					$capforfilter = $filterparts[0];
					} else {
						$capforfilter = "";
					}
				} elseif($frid) {
					if(is_numeric($filterparts[0])) { // if we're receiving a number, then assume it is an ele_id and get the caption based on that
						$capforfilter = getCaptionFFById($filterparts[0], $frid, $fid);
					} else { // otherwise, use a handle
						$capforfilter = getCaptionFF($filterparts[0], $frid, $fid);
					}
				} else { // when a plain form is passed, filters must use the full formulize_form captions for the first filterpart, or the numeric ele_id.
					$capforfilter = $filterparts[0];
					if(is_numeric($capforfilter)) { // if we're receiving a number, then assume it is an ele_id and replace with the corresponding caption
						$capq = go("SELECT ele_caption FROM " . DBPRE . "formulize WHERE ele_id='$capforfilter'");
						$capforfilter = $capq[0]['ele_caption'];	
					} 
					$capforfilter = str_replace("'", "`", $capforfilter);
				}
				if($capforfilter == "") {  // ignore this filter if it's not part of this form (ie: it's for another part of the framework)
					$filterNotForThisForm++;
					continue; 
				}
			
				// NOTE ABOUT SLASHES...about July 20 2006...a somewhat shaky premise of querying in Formulize, is that all data in the database will be consistent in terms of the pressence of slashes or not (ie: consistent regarding escape characters on quotes).  The assumption is that magic quotes will either be on or off in PHP and all data going into the system, and all queries going into the system will therefore be treated in the same way and slashes will either be present or not present both when data goes in and when people type queries into boxes in the list of entries view.  (The pressence of slashes is actually due to using addslashes again on all incoming values, even though magic quotes will have escaped the standard quote characters already.)  However, this assumption about consistency in how search terms will be treated regarding slashes in the same way that data in the DB will be treated, fails when people are manually crafting queries in Pageworks.   A direct DB INSERT or UPDATE query that does not add slashes to the inputs will result in data in the DB that is not consistent with the rest of the data which would have arrived there through POST and had magic quotes add slashes to it.  Therefore, all manually added data that lacks appropriate slashes will not be found by normal getData filters.

				// FURTHER NOTES ON SLASHES...July 24 2006...if magic quotes is on, then all apostrophes and other escapable chars in DB will have a slash preceeding them in the DB.  If magic quotes is off, then DB will be slash free in this respect.  The result of this is that five slashes are necessary preceeding any escapable character in a DB query that is looking for data, when magic quotes is on (one from magic quotes plus four from two applications of a function that adds slashes).  When magic quotes if off, only one slash is required.  Input of data into the DB appears to be consistent from all sources regarding the application of slashes, and the key difference seems to be the magic quotes setting on the server.  What a mess.  A future patch must rationalize all of this and correct data input too.

				// we are killing all slashes in filterparts[1] and then adding the right number back ourselves, to ensure compatibilty with existing applications following the major additions of mysql_real_escape_string to this routine.  Without this, existing apps that prep for slashes will break now that we are doing it right here.
				// note -- side effect of this is that we do not support filters that are actually looking for slashes!

				// FINAL NOTE ABOUT SLASHES...Oct 19 2006...patch 22 corrects this slash/magic quote mess.  However, to ensure compatibility with existing Pageworks applications, we are continuing to strip out all slashes in the filterparts[1], the filter strings that are passed in, and then we apply HTML special chars to the filter so that it can match up with the contents of the DB.  Only challenge is that extract.php is meant to be standalone, but we have to refer to the text sanitizer class in XOOPS in order to do the HTML special chars thing correctly.

				$filterparts[1] = str_replace("\\", "", $filterparts[1]);
				// if(get_magic_quotes_gpc()) { $filterparts[1] = addslashes(addslashes($filterparts[1])); } // not necessary after patch 22
				$filterparts[1] = $myts->htmlSpecialChars($filterparts[1]);

				$capforfilter = addslashes($capforfilter);
				$operator = $filterparts[2]; // introduced to handle "newest" type of query
				// handle links...
				// 1. check if element in filter contains links
				// 2. search for filterpart[1] in the destination of the link, retrieve the id
				// 3. convert filterpart[1] to that id
				$checkLink = go("SELECT ele_value FROM " . DBPRE . "formulize_form WHERE id_form = '$fid' AND ele_caption='$capforfilter'");
				foreach($checkLink as $thisLink) {
					if(strstr($thisLink['ele_value'], "#*=:*")) {
						$parts = explode("#*=:*", $thisLink['ele_value']);
						$targetCap = str_replace ("'", "`", $parts[1]);
						$targetCap = str_replace ("&quot;", "`", $targetCap);
                                                $targetCap = str_replace ("&#039;", "`", $targetCap);
						//if(isset($_GET['debug'])) { print "SELECT ele_id FROM " . DBPRE . "formulize_form WHERE id_form = '" . $parts[0] . "' AND ele_caption='$targetCap' AND ele_value LIKE '%" . mysql_real_escape_string($filterparts[1]) . "%'<br>"; }											
						$targetQuery = go("SELECT ele_id FROM " . DBPRE . "formulize_form WHERE id_form = '" . $parts[0] . "' AND ele_caption='$targetCap' AND ele_value LIKE '%" . mysql_real_escape_string($filterparts[1]) . "%'");	

						//if(isset($_GET['debug'])) { print_r($targetQuery); }
						if(count($targetQuery) == 0) { continue 1; } // ignore this filter since no values where found
						if(count($targetQuery) == 1) { // only one thing found, so...
							$filterparts[1] = "(ele_value LIKE '%#*=:*" . $targetQuery[0]['ele_id'] . "' OR ele_value LIKE '%#*=:*" . $targetQuery[0]['ele_id'] . "[=*9*:%' OR ele_value LIKE '%[=*9*:" . $targetQuery[0]['ele_id'] . "')";
						} else { // make a more complex string to slot in below
							$start = 1;
							foreach($targetQuery as $tq) {
								if($start) {
									$filterparts[1] = "(ele_value LIKE '%#*=:*" . $tq['ele_id'] . "' OR ele_value LIKE '%#*=:*" . $tq['ele_id'] . "[=*9*:%' OR ele_value LIKE '%[=*9*:" . $tq['ele_id'] . "')";
									$start = 0;
								} else {
									$filterparts[1] .= " OR (ele_value LIKE '%#*=:*" . $tq['ele_id'] . "' OR ele_value LIKE '%#*=:*" . $tq['ele_id'] . "[=*9*:%' OR ele_value LIKE '%[=*9*:" . $tq['ele_id'] . "')";
								}
							}
						}
						break; // only deal with the first thing returned from the $checkLink query -- assume first is authoritative
					}
				}
				$tempfilter = "";
				$orderbyfilter = "";
				$uidProxyidQuery = "";
				$emailQuery = "";
				if(strstr($operator, "newest")) {
					$limit = substr($operator, 6);
					$tempfilter = "(ele_caption = '$capforfilter')"; 
					$orderbyfilter = " ORDER BY ele_value DESC LIMIT 0,$limit";
				} elseif ($capforfilter == "uid" OR $capforfilter == "proxyid" OR $capforfilter == "creation_date" OR $capforfilter == "mod_date" OR $capforfilter == "creator_email") { 
					// if it's a non-numeric uid or proxy id filter, then do everything differently...
					if(!is_numeric($filterparts[1]) AND ($capforfilter == "uid" OR $capforfilter == "proxyid")) {
						// do a join with the user table and query based on the full name
						$uidProxyidOverride = 1;
						if(!$operator) { $operator = "LIKE"; }
						$uidProxyidQuery = "SELECT id_req FROM " . DBPRE . "formulize_form, " . DBPRE . "users WHERE " . DBPRE . "formulize_form.$capforfilter=" . DBPRE . "users.uid AND " . DBPRE . "users.name $operator";
						if($operator == "LIKE" OR $operator == "NOT LIKE") {
							$uidProxyidQuery .= " '%" . mysql_real_escape_string($filterparts[1]) . "%'";
						} elseif(is_numeric($filterparts[1])) {
							$uidProxyidQuery .= " " . $filterparts[1];
						} else {
							$uidProxyidQuery .= " '" . mysql_real_escape_string($filterparts[1]) . "'";
						}
						$prequery = go($uidProxyidQuery);					
					}

					if($capforfilter == "mod_date") { $capforfilter = "date"; }
					$tempfilter = "($capforfilter ";
					if($operator) {
						$tempterm = is_numeric($filterparts[1]) ? $filterparts[1] : " '" . mysql_real_escape_string($filterparts[1]) . "'";
						$tempfilter .= $operator . $tempterm .")";
					} elseif($capforfilter == "date" OR $capforfilter == "creation_date") {
						$tempfilter .= "LIKE '%" . mysql_real_escape_string($filterparts[1]) . "%')";
					} else {
						$tempfilter .= "= '" . mysql_real_escape_string($filterparts[1]) . "')";
					}

					if($capforfilter == "creator_email") { // handling for searching creator's email added September 24 2006
					// same logic as for the uid filter above, we create a custom prequery against the user table
// do a join with the user table and query based on the full name
						if(!$operator) { $operator = "LIKE"; }
						$emailQuery = "SELECT id_req FROM " . DBPRE . "formulize_form, " . DBPRE . "users WHERE " . DBPRE . "formulize_form.uid=" . DBPRE . "users.uid AND " . DBPRE . "users.email $operator";
						if($operator == "LIKE" OR $operator == "NOT LIKE") {
							$emailQuery .= " '%" . mysql_real_escape_string($filterparts[1]) . "%'";
						} elseif(is_numeric($filterparts[1])) {
							$emailQuery .= " " . $filterparts[1];
						} else {
							$emailQuery .= " '" . mysql_real_escape_string($filterparts[1]) . "'";
						}
						$prequery = go($emailQuery);					
					}

				} elseif(is_numeric($filterparts[0]) AND $filterparts[0] == $afilter) { // looking for a specific id_req by number
					$tempfilter = $capforfilter."=".$filterparts[0];
				} else {
					// if the element type is yes/no then convert yes/YES to 1 and no/NO to 2, which are the actual values in the DB -- added April 4 2006 jwe
					$yncap = str_replace("`", "'", $capforfilter);
					$ynq = go("SELECT ele_type, ele_id FROM " . DBPRE . "formulize WHERE id_form = '$fid' AND ele_caption = '" . mysql_real_escape_string($yncap) . "'");
					if($ynq[0]['ele_type'] == "yn") {
						if(strtoupper($filterparts[1]) == strtoupper(_formulize_TEMP_QYES)) {
							$filterparts[1] = 1;
						} elseif(strtoupper($filterparts[1]) == strtoupper(_formulize_TEMP_QNO)) {
							$filterparts[1] = 2;
						} else {
							$filterparts[1] = "";
						}
					}

					// prequery for the 'other' options -- added June 1 2006 by jwe
					$tempfilter="(";
					
					if($ynq[0]['ele_type'] == "radio" OR $ynq[0]['ele_type'] == "checkbox") {
						$other_prequery = "SELECT id_req FROM " . DBPRE . "formulize_other WHERE ele_id='" . $ynq[0]['ele_id'] . "' AND other_text";
						if($operator) {
							if($operator == "NOT LIKE" OR $operator == "LIKE") {
								$other_prequery .= " $operator '%" . mysql_real_escape_string($filterparts[1]) . "%'";
							} else {
								$tempterm = is_numeric($filterparts[1]) ? $filterparts[1] : " '" . mysql_real_escape_string($filterparts[1]) . "'";
								$other_prequery .= $operator . $tempterm;
							}
						} else {
							$other_prequery .= " LIKE '%" . mysql_real_escape_string($filterparts[1]) . "%'";
						}
						$other_id_reqs = go($other_prequery);
						if(isset($other_id_reqs[0]['id_req'])) {
							$start = 1;
							foreach($other_id_reqs as $id=>$row) {
								if($start) {
									$id_req_list=$row['id_req'];
									$start = 0;
								} else {
 									$id_req_list.="," .$row['id_req'];
								}
							}
							$tempfilter .= "id_req IN ($id_req_list) OR (";							
						}
					}

					// HANDLE SEARCHES ON FULLNAMES AND USERNAMES SELECT BOXES -- added September 6 2006
					// 1. identify the element based on caption and fid (and select type)
					// 2. execute search in the user table
					// 3. create an ele_value LIKE '%*=+*:uid%' filter based on each uid found
					// 4. put that filter in $filterparts[1] and trigger the next condition so the query looks just like when a linked selectbox is used

					if($ynq[0]['ele_type'] == "select" AND $listtype = isNameList($fid, $capforfilter) AND !is_numeric($filterparts[1])) {
						$listtype = $listtype == "{USERNAMES}" ? 'uname' : 'name';
						if($operator) {
							if($operator == "NOT LIKE" OR $operator == "LIKE") {
      	 						$userqstring = "$operator '%" . mysql_real_escape_string($filterparts[1]) . "%'";
							} else {
								$tempterm = is_numeric($filterparts[1]) ? $filterparts[1] : " '" . mysql_real_escape_string($filterparts[1]) . "'";
								$userqstring = $operator . $tempterm;
							} 
						} else {
							$userqstring = "LIKE '%" . mysql_real_escape_string($filterparts[1]) . "%'";
						}
						$uids = go("SELECT uid FROM " .  DBPRE . "users WHERE $listtype $userqstring");
						$start = 1;
						foreach($uids as $thisuid) {
							if($start) {
								unset($filterparts[1]);
								$filterparts[1] = "((ele_value LIKE '%*=+*:" . $thisuid['uid'] . "*=+*:%' OR ele_value LIKE '%*=+*:" . $thisuid['uid'] . "') OR ele_value = " . $thisuid['uid'] . ")";
								$start = 0;
							} else {
								$filterparts[1] .= " OR ((ele_value LIKE '%*=+*:" . $thisuid['uid'] . "*=+*:%' OR ele_value LIKE '%*=+*:" . $thisuid['uid'] . "') OR ele_value = " . $thisuid['uid'] . ")";
							}
						}
					}

					// generate regular prequery filter
					if(count($targetQuery) > 0 OR ($listtype AND !is_numeric($filterparts[1]))) { // if this is a linked field, then bracketing in the query needs to be different OR if this is a FULLNAMES or USERNAMES list
						$tempfilter .= "ele_caption = '$capforfilter' AND (" . $filterparts[1] . "))";
					} else {
           					$tempfilter .= "ele_caption = '$capforfilter' AND ele_value ";
       					if($operator) {
       						if($operator == "NOT LIKE" OR $operator == "LIKE") {
       							$tempfilter .= "$operator '%" . mysql_real_escape_string($filterparts[1]) . "%')";
       						} else {
								$tempterm = is_numeric($filterparts[1]) ? $filterparts[1] : " '" . mysql_real_escape_string($filterparts[1]) . "'";
       							$tempfilter .= $operator . $tempterm .")";
       						}
       					} else {
       						$tempfilter .= "LIKE '%" . mysql_real_escape_string($filterparts[1]) . "%')";
       					}
					}
					// if an "other" filter is in effect, then we need another closing ')'
					if(isset($other_id_reqs[0]['id_req'])) {
						$tempfilter .= ")";
					}
				}

				if(isset($_GET['debug'])) { print "SELECT id_req FROM " . DBPRE . "formulize_form WHERE id_form = '$fid' AND $tempfilter $scope GROUP BY id_req $orderbyfilter" . "<br><br>"; } // DEBUG LINE

				if(!$uidProxyidQuery AND !$emailQuery) {
					$prequery = go("SELECT id_req FROM " . DBPRE . "formulize_form WHERE id_form = '$fid' AND $tempfilter $scope GROUP BY id_req $orderbyfilter");
				}
				unset($targetQuery); // need to unset this so the use of a linked selectbox filter as the first part of a multipart filter doesn't screwup the querying on subsequent non-linked selectbox filters
				// if OR, then simply append id_reqs together, if AND, then save the overlap set
				if($andor == "OR") {
					foreach($prequery as $thisprequery) {
						$filterids[] = $thisprequery['id_req'];
					}
				} elseif ($andor == "AND") {
					// if nothing was found, then unset the filterids and don't bother with the next filter; since we are looking for the overlap of all filters, not finding anything on one of the passes means this whole query is a failure
					if(count($prequery) == 0) {
						unset($filterids);
						break;
					}
					$theseids = array();
					foreach($prequery as $thisprequery) {
						$theseids[] = $thisprequery['id_req'];
					}
					if($theseids[0] != "") {
						if(!isset($savedids)) { $savedids = $theseids; } // the first time through, make sure we save all the ids found so we have something to compare against on the next round
						$filterids = array_intersect($savedids, $theseids);
						unset($theseids); // important!  clear the IDs from this $afilter!
						$savedids = $filterids;
					}					
				} else {
					print "Unknown boolean operator requested as part of data extraction.";
					exit;
				}
			} // end of for each filter
		} 
	}
	if(count($filterids)>0) {
		array_unique($filterids);
		$startfilter = 1;
		$filter = " AND (";
		foreach($filterids as $thisid) {
			if(!$startfilter) {
				$filter .= " OR ";
			}
			$startfilter = 0;
			$filter .= "id_req='" . $thisid . "'";
		}
		$filter .= ")";
	} elseif($filter AND ($filterNotForThisForm < count($filters))) { // if we were supposed to find something but didn't, then set $filter to return nothing (but if there's a filter that wasn't for this form, then proceed to next condition)
		$filter = " AND (id_req='0')";
	} else { // no filter at all (applies to cases where no filter was passed, or cases where all the filter terms are meant for a different form in the framework)
		$filter = "";
	}
	return $filter;
}

function microtime_float()
{
   list($usec, $sec) = explode(" ", microtime());
   return ((float)$usec + (float)$sec);
}

function buildLinkValueCondition1($thistarget, $thesevalues, $frid, $fid) {
	$FFcaption = getCaptionFFbyId($thistarget, $frid, $fid);
	$linkValueCondition = "ele_caption='$FFcaption' AND (";
	$linkValueCondition .= buildLinkValueCondition2($thesevalues);
	$linkValueCondition .= ")";
	return $linkValueCondition;
}

function buildLinkValueCondition2($thesevalues) {


// This function produces a very loose match condition, and can produce a query which will return more id_reqs than are actually wanted.  However, that will still be less than the total id_reqs in the linked form, so there should still be a considerable improvement in speed.
// This function produces a query that looks for a LIKE, not an =, and looks in ele_id and ele_value, so that's what produces false positives (a value that you are looking for in ele_id might also appear in ele_value, you just don't know.  And numeric values in ele_id will of course include others, ie: 100 includes 1 and 10.
// The reason it looks in ele_value and ele_id is that the ele_value field contains the ele_id numbers when the target field is a linked field.  $thisvalue will only ever be an ele_id number, and the issue is that this function doesn't know whether the link is the source or destination of the link, so it looks in both places for the ele_id
// However, we hope to use the ele_value searching that this function does to facilitate matches based on equivalence of ele_value between different fields -- July 19 2006

// Present belief is that three sets of slashes need to be added to this, because the ele_value that may be being passed has had slashes stripped by prepvalues as part of the convertfinal process.  And two sets of slashes are required for queries to the DB on top of the normal slashes that are part of each entry.  Whether magic quotes is on or not should not matter, since everything should be consistent among the data going in and out(?).

// UPDATE for patch 22...contents of the DB is fixed now and all these slashes are not necessary any longer

	$start = 1;
	if(is_array($thesevalues)) {
		foreach($thesevalues as $thisvalue) {
			if($start) {
				$linkValueCondition = "(ele_value LIKE '%" . smartAddSlashes($thisvalue) . "%' OR ele_id LIKE '%" . mysql_real_escape_string($thisvalue) . "%')";
				$start = 0;
			} else {
				$linkValueCondition .= " OR (ele_value LIKE '%" . smartAddSlashes($thisvalue) . "%' OR ele_id LIKE '%" . mysql_real_escape_string($thisvalue) . "%')";
			}	
		}	
	} else {
		$linkValueCondition = "(ele_value LIKE '%" . smartAddSlashes($thisvalue) . "%' OR ele_id LIKE '%" . mysql_real_escape_string($thesevalues) . "%')";
	}

	return $linkValueCondition;
}

// THIS FUNCTION INTELLIGENTLY ADDS THE RIGHT NUMBER OF SLASHES SO THE DATA CAN BE FOUND IN THE db
// used only by buildlinkvaluecondition2
// must add 1 slash if magic_quotes is off, or five if magic quotes is on
// see note in makefilterinternal for more details
function smartAddSlashes($text) {
	global $myts;
	return mysql_real_escape_string($myts->htmlSpecialChars($text));
}

// THIS FUNCTION CHECKS TO SEE IF A GIVEN FORM HAS UNIFIED DISPLAY TURNED ON FOR IT IN A FRAMEWORK, and is in a onetoone relationship
// Note: a form may exist twice in a framework and have unified display turned on only one time.  

// That will yield strange results and is not an expected circumstance
function checkUDAndOneToOne($thisfid, $linklist1, $linklist2) {
	$ud = 0;
	
	if(is_array($linklist1)) {
		foreach($linklist1 as $udcheck) {
			if($udcheck['fl_form2_id'] == $thisfid AND $udcheck['fl_unified_display'] AND $udcheck['fl_relationship'] == 1) {
				$ud = 1;
				break;
			}
		}
	}
	if(!$ud) {
		if(is_array($linklist2)) {
			foreach($linklist2 as $udcheck) {
				if($udcheck['fl_form1_id'] == $thisfid AND $udcheck['fl_unified_display'] AND $udcheck['fl_relationship'] == 1) {
					$ud = 1;
					break;
				}
			}
		}
	}
	return $ud;
}

// THIS FUNCTION RETURNS THE ID_REQS OF THE ENTRIES THAT THE $RECORD IS LINKED TO
// accepts an array of records and finds all matches -- new March 22, 2006
function getOneToOneLinks($record) {
	$onetoonecheck = array();
	if(is_array($record)) {
		$start = 1;
		foreach($record as $idreq) {
			if($start) {
				$filter = "main_form='$idreq'";
				$start = 0;
			} else {
				$filter .= " OR main_form='$idreq'";
			}
		}
	} else {
		$filter = "main_form='$record'";
	}

	$onetoonecheck_sql = "SELECT link_form FROM " . DBPRE . "formulize_onetoone_links WHERE $filter";
	$onetoonecheck_res = mysql_query($onetoonecheck_sql);
	while ($array = mysql_fetch_array($onetoonecheck_res)) {
		$onetoonecheck[$array['link_form']] = $array['link_form'];
	}	
	return $onetoonecheck;
}


function dataExtraction($frame="", $form, $filter, $andor, $scope, $sortField, $sortOrder, $pageStart, $pageSize, $mainFormOnly, $includeArchived=false) {

if(isset($_GET['debug'])) { $time_start = microtime_float(); }

	// NOTE:  currently there is no ability to filter based on group, as there is in the main module logic.  Eventually, some filtering of results based on the userid (and determined group memberships) may be required.
	// NOTE:  this function only supports one level of linking.  ie: a "subform" of a "subform" of a "mainform" will not be included, only the first sub will be.
	// NOTE:  ALL FIELDS IN A FORM **MUST** HAVE A HANDLE ASSIGNED.  Bad results are returned otherwise.
	// NOTE:  all element handles must be unique
	// NOTE:  'date' is a reserved term (by PHP?) and cannot be used as an element handle

	// order of operations:
	// 1. Get list of forms in framework
	// 2. verify that requested form is in framework, otherwise return error
	// 3. Get list of links between requested form and others 
	// 4. Generate complete result set
	// 5. Apply filters to complete result set
	// 6. Return result set

	// Get list of forms linked to requested form in framework
	// 1. get frameid based on name
	// 2. get form id for form based on frameid and form handle
	// 3. get link list based on form id

	if(is_array($scope)) { // assume any arrays are groupid arrays, and so make a valid scope string based on this.
		$start = 1;
		foreach($scope as $scopegroup) {
			if($start) {
				$scopequery = "groupid=$scopegroup";
				$start = 0;
			} else {
				$scopequery .= " OR groupid=$scopegroup";
			}
		}
		$scopeusers = go("SELECT uid FROM " . DBPRE . "groups_users_link WHERE $scopequery");
		$start = 1;
		unset($scope);
		foreach($scopeusers as $user) {
			if($start) {
				$scope = "uid=" . $user['uid'];
				$start = 0;
			} else {
				$scope .= " OR uid=" . $user['uid'];

			}			
		}
	}
      if($scope) { 
		$scope = "AND (" . $scope . ")"; 
	}

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

	$mainfilter = makeFilter($filter, $frid, $fid, $andor, $scope);

	if($frid AND !$mainFormOnly) {
		// GET THE LINK INFORMATION FOR THE CURRENT FRAMEWORK BASED ON THE REQUESTED FORM
		$linklist1 = go("SELECT fl_form2_id, fl_key1, fl_key2, fl_relationship, fl_unified_display, fl_common_value FROM " . DBPRE . "formulize_framework_links WHERE fl_frame_id = '$frid' AND fl_form1_id = '$fid'");
		$linklist2 = go("SELECT fl_form1_id, fl_key1, fl_key2, fl_relationship, fl_unified_display, fl_common_value FROM " . DBPRE . "formulize_framework_links WHERE fl_frame_id = '$frid' AND fl_form2_id = '$fid'");
	}
	// link list 1 is the list of form2s that the requested form links to
	// link list 2 is the list of form1s that the requested form links to
	// ie: the link list number denotes the position of the requested form in the pair
	
	// Generate complete result set -- have to take into account all the special cases like linked selectboxes, etc
	// 1. get all entries in the requested form
	// 2. get all entries in the linked forms
	// 3. get the caption name (formatted for formulize_form) that is for the linked field(s)
	// 4. get each value for the linked field(s)
	// 5. for each linked form, get the caption name (formatted for formulize_form) for its linked fields
	// 6. for each linked form, get all entries that match the link criteria

//	print "Frame: $frame ($frid)<br>";
//	print "Form: $form ($fid)<br>";
	
	// RESULTS ARRAYS:
	// mainresults is the full results from main form
	// linkresults is the full results from all linked forms, where [0..n index][formid] is the form id of the form and [0..n index][results] is the array of rows returned from that form
	//if(isset($_GET['debug'])) { print "SELECT id_req, ele_type, ele_caption, ele_value, ele_id FROM " . DBPRE . "formulize_form WHERE id_form = '$fid' $mainfilter $scope ORDER BY id_req"; }// DEBUG LINE
        
               
debug_memory("Before retrieving mainresults");

//	if($includeArchived) { // INCLUSION OR EXCLUSION OF ARCHIVED ENTRIES DEFINITELY ON HOLD

               if(!$frid AND $pageSize > 0) {
                    // EXCITING NEW CODE ON HOLD FOR FRAMEWORKS, SINCE WE CANNOT SUCCESSFULLY PAGE RESULTS WHEN THERE IS A FILTER ON A FIELD IN A DIFFERENT FORM WITHIN THE FRAMEWORK
                    // In such cases, we have to get all the entries in the other form first, and then filter our main form results based on ones that match only the found entries
                    // ie: we need proper joins!  in a real table!               
                    
                    // PAGING OF RESULTS CANNOT BE DONE WHEN SOMETHING LIKE AN ADVANCED SEARCH IS APPLIED AFTERWARDS EITHER
                    // The advanced search needs to be applied across the entire possible dataset, not just the entries shown on a particular page!
                    
                    // NEW CODE TO HANDLE SORTING AND PAGING RESULTS
                    
                    // convert a framework-based sortField to numeric
                    $ascDesc = $sortOrder == "SORT_DESC" ? "DESC" : "ASC"; // use PHP syntax for param passed to this function, but convert to SQL syntax for inclusion in query
                    if($sortField) {
                         if(!is_numeric($sortField) AND $frid) {
                              $sortFieldClause = "AND ele_caption = '" . getCaptionFF($sortField, $frid, $fid) . "'";
                         } elseif(is_numeric($sortField)){
                              $sortFieldClause = "AND ele_caption = '" . getCaptionFFbyId($sortField, $frid, $fid) . "'";
                         } else {
                              exit("Error: A column was specified for sorting the results, but no framework was specified");
                         }
                         $orderByClause = "ORDER BY ele_value $ascDesc";
                    } else {
                         $sortFieldClause = "";
                         $orderByClause = "ORDER BY id_req $ascDesc";
                    }
                    if($pageSize) {
                         $limitClause = "LIMIT $pageStart,$pageSize";
                    } else {
                         $limitClause = "";
                    }
                    // not using "go" since we want to avoid one level of looping
                    // when integrating with Drupal, as long as the XOOPS DB connection happens second, mysql_query below should work okay.  (mysql_query uses the last opened connection by mysql_connect)
                    $premainresultsSQL = "SELECT DISTINCT(id_req) FROM " . DBPRE . "formulize_form WHERE id_form = '$fid' $mainfilter $scope $sortFieldClause $orderByClause $limitClause";
                    $pmrRes = mysql_query($premainresultsSQL);
                    $id_reqsForPage = array();
                    while($pmrArray = mysql_fetch_array($pmrRes)) {
                         $id_reqsForPage[] = $pmrArray['id_req'];
                    }
                    $id_reqsForPageClause = "AND id_req IN (" . implode(",",$id_reqsForPage) . ")";
                    
                    // DO ONE QUERY TO COUNT THE TOTAL NUMBER OF RESULTS, SINCE WE NEED TO KNOW THAT IN ORDER TO PROVIDE PAGE NAVIGATION
                    // Put the result in global memory space so we can pick it up again when necessary
                    $countMainResultsSQL = "SELECT COUNT(DISTINCT(id_req)) FROM " . DBPRE . "formulize_form WHERE id_form = '$fid' $mainfilter $scope $sortFieldClause $orderByClause";
                    $countMRRes = mysql_query($countMainResultsSQL);
                    $countMRArray = mysql_fetch_array($countMRRes);
                    $GLOBALS['formulize_countMainResults'] = $countMRArray['id_req'];
                    
               } else {
                    $id_reqsForPageClause = "";
               }
               //$mainresults = go("SELECT id_req, ele_type, ele_caption, ele_value, ele_id FROM " . DBPRE . "formulize_form WHERE id_form = '$fid' $mainfilter $scope $id_reqsForPageClause ORDER BY id_req");
               $mainresults = mysql_query("SELECT id_req, ele_type, ele_caption, ele_value, ele_id FROM " . DBPRE . "formulize_form WHERE id_form = '$fid' $mainfilter $scope $id_reqsForPageClause ORDER BY id_req");


// THIS IS MORE CODE RELATED TO THE ARCHIVED FILTERING...               
//	} else {
//		$mainfilter1 = str_replace("id_req", "t1.id_req", $mainfilter);
//		$scope1 = str_replace("uid", "t1.uid", $scope);
		          //print "SELECT t1.id_req, t1.ele_type, t1.ele_caption, t1.ele_value, t1.ele_id FROM " . DBPRE . "formulize_form AS t1, " . DBPRE . "users AS t2 WHERE t1.id_form = '$fid' $mainfilter1 $scope1  AND (t1.uid=0 OR (t1.uid=t2.uid AND t2.archived=0)) ORDER BY t1.id_req";
//		$mainresults = go("SELECT t1.id_req, t1.ele_type, t1.ele_caption, t1.ele_value, t1.ele_id FROM " . DBPRE . "formulize_form AS t1, " . DBPRE . "users AS t2 WHERE t1.id_form = '$fid' $mainfilter1 $scope1  AND (t1.uid=0 OR (t1.uid=t2.uid AND t2.archived=0)) ORDER BY t1.id_req");
//	}

debug_memory("After retrieving mainresults");

	// generate the list of key fields in the current form, so we can use the values in these fields to filter the linked forms. -- sept 27 2005
	if($frid AND !$mainFormOnly) {
		if(count($linklist1) > 0) {
      		foreach($linklist1 as $theselinks) {
      			$linkformids1[] = $theselinks['fl_form2_id'];
      			if($theselinks['fl_key1'] != 0) {
      				$handleforlink = handleFromId($theselinks['fl_key1'], $fid, $frid);
      				$linkkeys1[] = $handleforlink;
      				$linktargetids1[] = $theselinks['fl_key2'];
      			} else {
      				$linkkeys1[] = "";
      				$linktargetids1[] = "";
      			}
      			if($theselinks['fl_relationship'] == 2) { // 2 is one to many relationship
      				$linkisparent1[] = 1;
      			} else {
      				$linkisparent1[] = 0;
      			}
      			$linkcommonvalue1[] = $theselinks['fl_common_value'];
			}
		} else {
			$linkkeys1 = array();
			$linkisparent1 = array();
			$linkformids1 = array();
			$linktargetids1 = array();
			$linkcommonvalue1 = array();
		}
		if(count($linklist2) > 0) {
      		foreach($linklist2 as $theselinks) {
      			$linkformids2[] = $theselinks['fl_form1_id'];
      			if($theselinks['fl_key2'] != 0) {
      				$handleforlink = handleFromId($theselinks['fl_key2'], $fid, $frid);
      				$linkkeys2[] = $handleforlink;
      				$linktargetids2[] = $theselinks['fl_key1'];
      			} else {
      				$linkkeys2[] = "";
      				$linktargetids2[] = "";
      			}
      			if($theselinks['fl_relationship'] == 3) { // 3 is many to one relationship
      				$linkisparent2[] = 1;
      			} else {
      				$linkisparent2[] = 0;
      			}
      			$linkcommonvalue2[] = $theselinks['fl_common_value'];
      		}
		} else {
			$linkkeys2 = array();
			$linkisparent2 = array();
			$linkformids2 = array();
			$linktargetids2 = array();
			$linkcommonvalue2 = array();
		}
		$linkkeys = array_merge($linkkeys1, $linkkeys2);
		$linkisparent = array_merge($linkisparent1, $linkisparent2);
		$linkformids = array_merge($linkformids1, $linkformids2);
		$linktargetids = array_merge($linktargetids1, $linktargetids2);
		$linkcommonvalue = array_merge($linkcommonvalue1, $linkcommonvalue2);
	} else {
		$linkkeys = "";
		$linkisparent = "";
		$linkformids = "";
		$linktargetids = "";
		$linkcommonvalue = "";
	}

	unset($mainfilter);

	// call the function that does the conversion to the desired format:
	// [formhandle][row/record][handle/fieldname][0..n] = value(s)
	list($finalresults, $linkvalue, $finalidreqs) = convertFinal($mainresults, $fid, $frid, $linkkeys, $linkisparent, $linkformids, $linktargetids, $linkcommonvalue);

debug_memory("after convertFinal");
	  
	unset($mainresults);
	
	if($frid AND !$mainFormOnly) {
		$udOneToOneLinks = array();
//		these are now set above as part of the generation of the values of the linked fields for filtering -- sept 27 2005 
//		if(!isset($linkformids)) {
//			$linkformids1 = fieldValues($linklist1, "fl_form2_id");
//			$linkformids2 = fieldValues($linklist2, "fl_form1_id");
//			$linkformids = array_merge($linkformids1, $linkformids2);
//		}
		// here is the issue re: only one level of linking... this loop works off the linkformids array which is based on the one level links from the main form.  A recursive loop here which took all output formids and grabbed their results would produce a complete picture of the framework based on the entrypoint
		// note however that collecting together the unified results array based on such a complete picture of the framework may be an order of magnitude (or two) more complex than the simple collection process currently used below.
		foreach($linkformids as $lfid) {
			if(!is_numeric($filter)) { // do not use numeric filters, since they are for grabbing a specific id out of the main form
				$linkfilter = makeFilter($filter, $frid, $lfid, $andor, $scope, 1); // final 1 indicates this is a linkform
			} else {
				$linkfilter = "";
			}
			if($linkfilter) { // set a flag that counts the linkforms where there was a filter in place, but exclude  
				$filters = explode("][", $filter);
				foreach($filters as $afilter) {
					$filterparts = explode("/**/", $afilter);
					if($filterparts[0] != "uid" AND $filterparts[0] != "proxyid" AND $filterparts[0] != "creation_date" AND $filterparts[0] != "mod_date" AND $filterparts[0] != "creator_email") {
						$lfexists[$lfid] = 1; // at least one filter was not a metadata filter (which should only be applied to the main form)
						break;
					}
				}
			}

			$checklfid = checkUDAndOneToOne($lfid, $linklist1, $linklist2);
			if($checklfid) { 
				$linkfilterLocal = "";
				// added March 22, 2006, get the idreqs from onetoone table based on the finalidreqs
				if(!isset($linkfilterLocalUD)) { // we only need to get it once.
					$udOneToOneLinks = getOneToOneLinks($finalidreqs);
					$start = 1;
					foreach($udOneToOneLinks as $thisid) {
						if($start) {
							$linkfilterLocalUD = " AND (id_req='" . $thisid . "'";
							$start = 0;
						} else {
							$linkfilterLocalUD .= " OR id_req='" . $thisid  . "'";
						}
					} 
					if(isset($linkfilterLocalUD)) { // close the string if it has been started
						$linkfilterLocalUD .= ")"; 
					} else { // if no matches in the one to one table were found, then it's either because there are none, so return nothing, or because there are no mainform results, so return nothing.
						$linkfilterLocalUD = " AND (id_req='0')"; 
					}
				}
				$linkfilterLocal = $linkfilterLocalUD; // for this linked form, set the link filter to the unified display filter based on the one-to-one table
			} else {

				// generate a restiction based on the values in the main form that this form is linked to
				if($foundlinks = array_keys($linkvalue['fid'], $lfid)) {

					$startuid = 1;
					$startvalue = 1;
					unset($linkUidCondition);
					unset($linkValueCondition);
					// NOTE: THIS LOOP IS ESSENTIALLY SET UP TO ASSUME ONLY ONE KIND OF LINKING PER FORM.  IF THERE ARE TWO KINDS OF LINKS FOR A FORM, THEN THE RESULTING CONDITIONS WILL LOOK FOR ENTRIES THAT MATCH BOTH LINKS, WHICH IS OBVIOUSLY IMPOSSIBLE.  THEREFORE, THE RESULTS IN THAT CASE WILL INCLUDE NO VALUES FROM THE LINKED FORM.


					foreach($foundlinks as $oneFoundLink) {
						if(isset($linkvalue['uid'][$oneFoundLink])) {
							if($startuid) {
								$linkUidCondition = " AND (uid=" . $linkvalue['uid'][$oneFoundLink];
								$startuid = 0;
							} else {
								$linkUidCondition .= " OR uid=" . $linkvalue['uid'][$oneFoundLink];
							}
						} elseif(isset($linkvalue['values'][$oneFoundLink]) AND isset($linkvalue['targetid'][$oneFoundLink])) {
							if($startvalue) {
								// get the caption for the targetid
								$linkValueCondition = " AND ((";
								$linkValueCondition .= buildLinkValueCondition1($linkvalue['targetid'][$oneFoundLink], $linkvalue['values'][$oneFoundLink], $frid, $lfid);
								$linkValueCondition .= ")";
								$startvalue = 0;									
							} else {
								$linkValueCondition .= " OR (";
								$linkValueCondition .= buildLinkValueCondition1($linkvalue['targetid'][$oneFoundLink], $linkvalue['values'][$oneFoundLink], $frid, $lfid);
								$linkValueCondition .= ")";
							}
						}
					}
					if($linkUidCondition) { $linkUidCondition .= ")"; }
					if($linkValueCondition) { $linkValueCondition .= ")"; }
					// now prequery for id_reqs so the linkresult query can operate
					if(isset($_GET['debug'])) { print "<br>LINK QUERY: SELECT id_req FROM " . DBPRE . "formulize_form WHERE id_form = '$lfid' $linkfilter $linkUidCondition $linkValueCondition ORDER BY id_req<br>"; }
					$prequery = go("SELECT id_req FROM " . DBPRE . "formulize_form WHERE id_form = '$lfid' $linkfilter $linkUidCondition $linkValueCondition ORDER BY id_req");
					$start = 1;
					unset($linkfilterLocal);
					if(count($prequery)>0) {
						foreach($prequery as $thisid) {
							if($start) {
								$linkfilterLocal = " AND (id_req='" . $thisid['id_req'] . "'";
								$start = 0;
							} else {
								$linkfilterLocal .= " OR id_req='" . $thisid['id_req']  . "'";
							}
						}
						$linkfilterLocal .= ")"; // guaranteed to be something since the count of prequery is verified above.
					} else { // no id_reqs found that match, so return nothing
						$linkfilterLocal = " AND (id_req='0')";
					}
				} else { // nothing found that links up with this form, so return nothing in the link form.
					$linkfilterLocal = " AND (id_req='0')";
				}
			}// end of IF checkUDAndOneToOne

debug_memory("Before retrieving a linkresult");
if(isset($_GET['debug'])) {
	print "Form: $lfid<br>";
	print_r($linkfilterLocal);
	print "<br>";
}
			//$linkresult = go("SELECT id_req, ele_type, ele_caption, ele_value FROM " . DBPRE . "formulize_form WHERE id_form = '$lfid' $linkfilterLocal ORDER BY id_req"); // scope not used in this query for now
                         $linkresult = mysql_query("SELECT id_req, ele_type, ele_caption, ele_value FROM " . DBPRE . "formulize_form WHERE id_form = '$lfid' $linkfilterLocal ORDER BY id_req"); // scope not used in this query for now
			unset($linkfilter);
			unset($linkfilterLocal);
debug_memory("After retrieving a linkresult");
			list($finallinkresult{$lfid}, $linkthrowaway, $idreqthrowaway) = convertFinal($linkresult, $lfid, $frid); // note...must not call returned "linkvalue" anything useful since we only care about using the returned info from the main query. 
			unset($linkresult);
		}
	}
debug_memory("Before unsetting used arrays");
unset($linkvalue);
unset($finalidreqs);
unset($linkthrowaway);
unset($idreqthrowaway);
debug_memory("After unsetting used arrays");
// DEBUG CODE
if(isset($_GET['debug'])) {
	printfclean($finalresults);
	foreach($linkformids as $lfid) {
		printfclean($finallinkresult{$lfid}); 
	}
}
	if($frid AND !$mainFormOnly) {
                //generate the mainHandles and linkHandles arrays
		$indexer = 0;
		foreach($linklist1 as $linkinfo) {
			if($linkinfo['fl_key1'] != 0) {
				$mainHandles[$indexer]['lfid'] = $linkinfo['fl_form2_id'];
				$mainHandles[$indexer]['handle'] = handleFromId($linkinfo['fl_key1'], $fid, $frid);
				$linkHandles[$indexer]['lfid'] = $linkinfo['fl_form2_id'];
				$linkHandles[$indexer]['handle'] = handleFromId($linkinfo['fl_key2'], $linkinfo['fl_form2_id'], $frid);
				$indexer++;
			}
		}
		foreach($linklist2 as $linkinfo) {
			if($linkinfo['fl_key1'] != 0) {
				$mainHandles[$indexer]['lfid'] = $linkinfo['fl_form1_id'];
				$mainHandles[$indexer]['handle'] = handleFromId($linkinfo['fl_key2'], $fid, $frid);
				$linkHandles[$indexer]['lfid'] = $linkinfo['fl_form1_id'];
				$linkHandles[$indexer]['handle'] = handleFromId($linkinfo['fl_key1'], $linkinfo['fl_form1_id'], $frid);
				$indexer++;
			}
		}

		$linkformidsE = array();
		$linkformidsU = array();
		$linkfids1 = fieldValues($linklist1, "fl_form2_id", "fl_key1][NOT][0");
		$linkfids2 = fieldValues($linklist2, "fl_form1_id", "fl_key2][NOT][0");
		if(is_array($linkfids1) AND is_array($linkfids2)) {
			$linkformidsE = array_merge($linkfids1, $linkfids2);
			array_unique($linkformidsE);
		} elseif(is_array($linkfids1)) {
			$linkformidsE = $linkfids1;
		} elseif(is_array($linkfids2)) {
			$linkformidsE = $linkfids2;
		}

		$linkuids1 = fieldValues($linklist1, "fl_form2_id", "fl_key1][==][0");
		$linkuids2 = fieldValues($linklist2, "fl_form1_id", "fl_key2][==][0");
		if(is_array($linkuids1) AND is_array($linkuids2)) {
			$linkformidsU = array_merge($linkuids1, $linkuids2);
			array_unique($linkformidsU);
		} elseif(is_array($linkfids1)) {
			$linkformidsU = $linkuids1;
		} elseif(is_array($linkfids2)) {
			$linkformidsU = $linkuids2;
		}

	} // end of if there's a frid (framework)

	// mainHandles  -- THE HANDLES OF THE LINKED FIELDS IN THE REQUESTED FORM
	// linkHandles  -- THE HANDLES OF THE LINKED FIELDS IN THE LINKED FORMS 
	// linkformidsE -- THE FORM_IDS OF THE LINKED FORMS WHICH ARE LINKED THROUGH THESE ELE_IDS
	// linkformidsU -- THE FORM_IDS OF THE LINKED FORMS WHICH ARE LINKED THROUGH UIDS

	// need to link up result sets
	// in the case of uids, pair records where value of uid matches 
	// in the case of specific fields, pair records where value of specific fields matches

	// HOW TO HANDLE SITUATIONS WHERE THERE IS MORE THAN ONE LINK BETWEEN FORMS!?  Activity Log links to vol who is author, plus other vols who participated!
	// PROBLEM IS HOW TO IDENTIFY WHICH ENTRIES FROM THE LINKED FORM ARE TIED TO WHICH PARTS OF THE MAIN FORM
	// NEED TO RECORD THE RECORD/FIELD/VALUE THAT CAUSES THE SPECIFIC RECORD FROM ANOTHER FORM TO BE BROUGH IN.
	// IE:
	// [0][activities][12][name][0] = frank
	// [0][volprofile][33][volname][0] = frank
	// recorded link-> 12/name/0 'causes' volprofile[33]
	// That info is necessary in order to fully understand the result array
	// Add to result array the following (METADATA is a reserved word)
	// ???? metadata is still under construction
	// [0][METADATA][name][0] = 0 // the zero here equals the 
	// [0][METADATA][volprofile][0] = volprofile][33
	// [0][METADATA][forms][0] = activities
	// [0][METADATA][forms][0] = volprofile
	// BEST IDEA IS TO HAVE MASTER METADATA THAT DESCRIBES THE STRUCTURE OF THE FRAMEWORK: ALL POSSIBLE FORMS THAT FEED INTO EACH ENTRY, AND THE HANDLES THAT THEY LINK UP ON
	// THEN METADATA FOR EACH ENTRY (FIRST INDEX IN THE ARRAY, 0..N) WHICH GIVES THE ID OF THE SPECIFIC VALUE IN THE MAIN FORM THAT IS BEING LINKED FROM AND THE RECORD ID IN THE TARGET FORM THAT IS BEING LINKED TO

debug_memory("Start of adding e-mail addresses");

	// September 24 2006 -- go get all the e-mail addresses and store them here so we can include them intelligently
	global $formulize_extraction_uid_list;
	$uid_list = implode(",",$formulize_extraction_uid_list);
	$user_emails = go("SELECT uid, email, user_viewemail FROM " . DBPRE . "users WHERE uid IN ($uid_list)", "uid"); // last param indicates to return the results with one of the fields as the keys of the array

debug_memory("Start of compiling masterresult");
	
	// start the masterresult array with the complete finalresult
	$indexer = 0;
	if(is_array($finalresults)) {
      	foreach($finalresults as $form=>$records) {
      		foreach($records as $record=>$values) {
      if(isset($_GET['debug'])) {
       print "FinalResults count: " . count($records) . "<br>";
       print "MasterResults count: " . count($masterresult) . "<br>";

      }
      			$masterresult[$indexer][$form][$record] = $values; // values contains all the data for this entry

                         global $xoopsUser;
                         if(is_object($xoopsUser)) { // determine if the user is a webmaster, in order to control whether the e-mail addresses should be shown or not
                              $is_webmaster = in_array(XOOPS_GROUP_ADMIN, $xoopsUser->getGroups()) ? true : false;
                              $this_userid = $xoopsUser->getVar('uid');
                         } else {
                              $is_webmaster = false;
                              $this_userid = 0;
                         } 
                         if($is_webmaster OR $user_emails[$masterresult[$indexer][$form][$record]['uid']]['user_viewemail'] OR $masterresult[$indexer][$form][$record]['uid'] == $this_userid) {
                              $masterresult[$indexer][$form][$record]['creator_email'] = $user_emails[$masterresult[$indexer][$form][$record]['uid']]['email']; // add e-mail address to main form only
                         } else {
                              $masterresult[$indexer][$form][$record]['creator_email'] = ""; // blank for non webmasters if the user flagged their e-mail as private
                         }

      			if($frid AND !$mainFormOnly) {
      				// search for links based on one-to-one relationships
      				// 1. search for this record in the one-to-one db list
      				// 2. if there are results, then if those results are part of the linkresults then add them
      				// this matching is preformed within the other two loops
      				// this query does not use "go" in order to eliminate the need for one round of looping
      				// Addition - Oct 22 2005 - if it is onetoone and unified display, then the uid or element match should be ignored!
      				// 
      				unset($onetoonecheck);
      				$onetoonecheck = getOneToOneLinks($record);	
      			
      				// search for links based on uid
      				foreach($linkformidsU as $fidu) {

      					// check to see if the current form has unified display turned on, and if so then do not match based on UID, match only based on onetoone
      					$noUidMatch = checkUDAndOneToOne($fidu, $linklist1, $linklist2);

      					foreach($finallinkresult{$fidu} as $f => $rs) {
      						foreach($rs as $r => $v) {
      							// check for onetoonelinks
      							if(isset($onetoonecheck[$r])) {
      								$masterresult[$indexer][$f][$r] = $v;
      								continue;
      							}	
      							if($v['uid'] == $values['uid'] AND !$noUidMatch) {
      								$masterresult[$indexer][$f][$r] = $v;
      							}
      						}
      					}	
      				}

      				// search for link based on elements
      				foreach($linkformidsE as $fide) {

      					// check to see if the current form has unified display turned on, and if so then do not match based on element values, match only based on onetoone
      					$noEleMatch = checkUDAndOneToOne($fide, $linklist1, $linklist2);

      					foreach($finallinkresult{$fide} as $f => $rs) {
      						foreach($rs as $r => $v) {
      							// check for onetoonelinks
      							if(isset($onetoonecheck[$r])) {
      								$masterresult[$indexer][$f][$r] = $v;
      								continue;
      							} elseif(!$noEleMatch) {
      								//compare the each of the values in each of the linked elements to each of the values in the main element (make arrays and then do an intersection?)
      								//need handles to do this
      								for($i=0;$i<count($mainHandles);$i++) {
      									if($mainHandles[$i]['lfid'] == $fide) {
      										if($v[$linkHandles[$i]['handle']] == $values[$mainHandles[$i]['handle']]) { 
      											$masterresult[$indexer][$f][$r] = $v;
      											continue;
      										} elseif (count($v[$linkHandles[$i]['handle']])>1) { // look for one value inside the multiple array addresses of the other value -- assumption is that one of them must be a single value, ie: two multiples can't possibly be linked
      											if(in_array($values[$mainHandles[$i]['handle']][0], $v[$linkHandles[$i]['handle']])) {
      												$masterresult[$indexer][$f][$r] = $v;
      												continue;
      											}
      										} elseif (count($values[$mainHandles[$i]['handle']])>1) {
      											if(in_array($v[$linkHandles[$i]['handle']][0], $values[$mainHandles[$i]['handle']])) {
      												$masterresult[$indexer][$f][$r] = $v;
      												continue;
      											}
      										}
      									}
      								}
      							}
      						}
      					}
      				}
      			} // end of if frid
      			$indexer++;
                        unset($records[$record]);
      		}
                unset($finalresults[$form]);
      	} // end foreach
	} // end if the count is greater than 0

//print_r($masterresult);
//print "<br>";

     // handle derived columns, using data collected in the convertFinal function
     $masterresult = formulize_calcDerivedColumns($masterresult, $GLOBALS['formulize_calcDerivedColumnsData'][$fid], $frid, $fid);         
	foreach($linkformids as $lfid) {
		$masterresult = formulize_calcDerivedColumns($masterresult, $GLOBALS['formulize_calcDerivedColumnsData'][$lfid], $frid, $lfid);         
	}

	if(isset($lfexists)) { // remove any entries where there is no link result but there was a link filter in effect (ie: we're limiting results by hits on the link filter)
               $reindex = false;
		foreach($lfexists as $lid=>$flag) {
			// look for the presence of this form in the masterresult
			// unset any masterresult entries where it is not found
			$lfhandle = go("SELECT ff_handle FROM " . DBPRE . "formulize_framework_forms WHERE ff_frame_id = '$frid' AND ff_form_id = '$lid'");
			foreach($masterresult as $masterid=>$mres) {
				if(!$mres[$lfhandle[0]['ff_handle']]) { // no subentry for this link form is present
					unset($masterresult[$masterid]);
                                        $reindex = true;
				}		
			}
		}
                if($reindex) {
                    $masterresult = array_values($masterresult);
                }
	}

debug_memory("before returning result");

if(isset($_GET['debug'])) { 
	$time_end = microtime_float();
	$time = $time_end - $time_start;
	echo "Execution time is <b>$time</b> seconds\n"; 
}
	if(!isset($masterresult)) { $masterresult = ""; }

	return $masterresult; 

debug_memory("after returning result");

}

// THIS FUNCTION LOOPS THROUGH A MASTERRESULT AND ADDS IN THE DERIVED VALUES IN ANY DERIVED COLUMNS -- March 27 2007
// Odd results may occur when a derived column is inside a subform in a framework!
// Derived values should always be in the mainform only?
function formulize_calcDerivedColumns($result, $metadata, $frid, $fid) {
     if(count($metadata) > 0) {
          foreach($result as $id=>$entry) {
               foreach($metadata as $thisMetaData) {
                    $value = formulize_calcDerivedColumnValue($entry, $thisMetaData['formula'][0], $frid, $fid); // formula is an array, hence the [0]
                    foreach($entry as $formHandle=>$record) {
                         if($formHandle == $thisMetaData['formhandle']) {
                              foreach($record as $recordID=>$elements) {
                                   $result[$id][$formHandle][$recordID][$thisMetaData['handle']][0] = "$value";
                              }
                              break;
                         }
                    }          
               }
          }
     }
     return $result;    
}

// THIS FUNCTION DETERMINES HOW TO HANDLE THE FORMULA IN A DERIVED COLUMN AND THEN WORKS OUT THE VALUE BASED ON THE ENTRY IT RECEIVED
// use a static array to cache formulas that we already have parsed
function formulize_calcDerivedColumnValue($entry, $formula, $frid, $fid) {

     static $cached_formulas = array();
     if(!isset($cached_formulas[$formula][$frid][$fid])) {
          $original_formula = $formula;
          // loop through the formula and convert all quoted terms to display function calls
          while($quotePos = strpos($formula, "\"", $quotePos+1)) {
               // print $formula . " -- $quotePos<br>"; // debug code
               $endQuotePos = strpos($formula, "\"", $quotePos+1);
               $term = substr($formula, $quotePos, $endQuotePos-$quotePos+1);
               if(!is_numeric($term) AND !formulize_validFrameworkHandle($frid, $term)) { 
                    $newterm = formulize_convertCapOrColHeadToHandle($frid, $fid, $term);
               }
               $replacement = "display(\$entry, \"$newterm\")";
               $formula = str_replace($term, $replacement, $formula);
               $quotePos = $quotePos + 17 + strlen($newterm); // 17 is the length of the extra characters in the display function
          }
          $cached_formulas[$original_formula][$frid][$fid] = $formula;
     } else {
          $formula = $cached_formulas[$formula][$frid][$fid];
     }
     $addSemiColons = strstr($formula, ";") ? false : true;
     if($addSemiColons) {
          $formulaLines = explode("\n", $formula); // \n may be a linux specific character and other OSs may require a different split
          foreach($formulaLines as $thisLine) {
               $thisLine = trim($thisLine, ";"); // get rid of semi-colons if present and then add them again
               $thisLine .= ";";
          }
          eval($thisLine);
     } else {
          eval($formula);
     }
     return $value; // by convention, $value is the variable that gets assigned the results of the calculation used in the derived column (ie: it is set inside the code eval'd above)
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
     
     if($term == "uid" OR $term == "proxyid" OR $term == "creation_date" OR $term == "mod_date" OR $term == "creator_email") {
        return $term;
     }
     
     if(!$frid) {
          $formList[0]['ff_form_id'] = $fid; // mimic what the result of the framework query below would be...
     } else {
          if(isset($framework_results[$frid])) {
               $formList = $framework_results[$frid];
          } else {
               $formList = go("SELECT ff_form_id FROM " . DBPRE . "formulize_framework_forms WHERE ff_frame_id = \"$frid\"");
               $framework_results[$frid] = $formList;
          }
     }
     foreach($formList as $form_id) {
          if(isset($results_array[$form_id['ff_form_id']][$term][$frid])) { return $results_array[$form_id['ff_form_id']][$term][$frid]; }
          $colhead_query = go("SELECT ele_id FROM " . DBPRE . "formulize WHERE id_form = " . $form_id['ff_form_id']. " AND ele_colhead = \"" . mysql_real_escape_string($term) . "\"");
          if(count($colhead_query) > 0) {
               $handle = $frid ? handleFromId($colhead_query[0]['ele_id'], $form_id['ff_form_id'], $frid) : $colhead_query[0]['ele_id'];
          } else {
               $caption_query = go("SELECT ele_id FROM " . DBPRE . "formulize WHERE id_form = " . $form_id['ff_form_id']. " AND ele_caption = \"" . mysql_real_escape_string($term) . "\"");
               if(count($caption_query) > 0 ) {
                    $handle = $frid ? handleFromId($caption_query[0]['ele_id'], $form_id['ff_form_id'], $frid) : $caption_query[0]['ele_id'];
               }
          }    
          if($handle) {
               $results_array[$form_id['ff_form_id']][$term][$frid] = $handle;
               break;
          }     
     }
     if(!$handle) { exit("Error: column heading or caption referred to in a derived column was not found in any form"); }
     return $handle;
}
     

// THIS FUNCTION QUERIES A TABLE IN THE DATABASE AND RETURNS THE RESULTS IN STANDARD getData FORMAT
// Uses the standard filter syntax, and can use scope if a uidField name is specified
// Filters cannot obviously use the standard metadata fields that are part of regular forms
// At the time of writing (Nov 1 2005) supports single table queries only, no joins
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


// MODIFIED NOVEMBER 1 2005 -- ADDED QUERY DIRECT TO DB TABLES 
function getData($framework, $form, $filter="", $andor="AND", $scope="", $sortField="", $sortOrder="", $pageStart=0, $pageSize=0, $mainFormOnly=0, $includeArchived=false, $dbTableUidField="") {

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

	if(substr($framework, 0, 3) == "db:") {
		$result = dataExtractionDB(substr($framework, 3), $filter, $andor, $scope, $dbTableUidField);
	} else {
	$result = dataExtraction($framework, $form, $filter, $andor, $scope, $sortField, $sortOrder, $pageStart, $pageSize, $mainFormOnly, $includeArchived);
	}
	return $result;
}

// *******************************
// FUNCTIONS BELOW ARE FOR PROCESSING RESULTS
// *******************************

// This function returns the caption, formatted for form (not formulize_form), based on the handle for the element
// assumption is that a handle is unique within a framework
// $colhead flag will cause the colhead to be returned instead of the caption
function getCaption($framework, $handle, $colhead=false) {
	if(is_numeric($framework)) {
		$frid[0]['frame_id'] = $framework;
	} else {
		$frid = go("SELECT frame_id FROM " . DBPRE . "formulize_frameworks WHERE frame_name = '$framework'");
	}
	$elementId = go("SELECT fe_element_id FROM " . DBPRE . "formulize_framework_elements WHERE fe_frame_id = '" . $frid[0]['frame_id'] . "' AND fe_handle = '$handle'");
	$caption = go("SELECT ele_caption, ele_colhead FROM " . DBPRE . "formulize WHERE ele_id = '" . $elementId[0]['fe_element_id'] . "'"); 
	if($colhead AND $caption[0]['ele_colhead'] != "") {
		return $caption[0]['ele_colhead'];
	} else {
		return $caption[0]['ele_caption'];
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
				foreach($elements as $element=>$values) {
					if($element == $handle) {
						return $formHandle;
					}
				}

			}
		}
	}
	return "";// exit("Error: no form handle found for element handle '$handle'");
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
	
	$formhandle = getFormHandleFromEntry($entry, $handle);

	if(!$formhandle) { return ""; } // return nothing if the passed handle is not part of the result set
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
			if($order = "SORT_DESC") {
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
	if(is_int($entry))
    {
	    switch($spechandle) {
	        case "uid-name":
	            $name = go("SELECT name FROM " . DBPRE .
	            	"users WHERE uid=$entry");

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
	            if($handle != "uid" AND $handle != "proxyid") {
		            exit("invalid item requested for handle $handle");
	            }
	            // "go" works exactly like the q function, but it only exists in the 
	            // extraction layer.
	            // also note the use of DBPRE and not any $xoopsDB stuff
	            $name = go("SELECT name FROM " . DBPRE .
	            	"users WHERE uid=$values");

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

function getFormId($formname)
{
	$sql = "SELECT id_form  " .
		" FROM " . DBPRE . "formulize_id" .
		" WHERE desc_form = '$formname';";

	//die($sql);
    
	$id_form_results = go($sql);
            
	//var_dump($id_form_results);

	return $id_form_results[0]['id_form']; 
}


function getFormElement($formframe, $handle)
{
	$sql = "SELECT *" .
		" FROM " . DBPRE . "formulize, " . DBPRE . "formulize_frameworks, " . DBPRE . "formulize_framework_elements" .
		" WHERE " . DBPRE . "formulize_framework_elements.fe_element_id = " . DBPRE . "formulize.ele_id" . 
        " AND " . DBPRE . "formulize_frameworks.frame_name = '$formframe'" .
		" AND " . DBPRE . "formulize_framework_elements.fe_handle = '$handle'" .
		" AND " . DBPRE . "formulize_framework_elements.fe_frame_id = " . DBPRE . "formulize_frameworks.frame_id";

	//die($sql);
    
	$results = go($sql);
            
	//var_dump($results);

	return $results[0]; 
}


function getFrameworkElementId($formframe, $handle)
{
	$sql = "SELECT fe_element_id" .
		" FROM " . DBPRE . "formulize_frameworks, " . DBPRE . "formulize_framework_elements" .
		" WHERE " . DBPRE . "formulize_frameworks.frame_name = '$formframe'" .
		" AND " . DBPRE . "formulize_framework_elements.fe_handle = '$handle'" .
		" AND " . DBPRE . "formulize_framework_elements.fe_frame_id = " . DBPRE . "formulize_frameworks.frame_id";

	//die($sql);
    
	$fe_element_id_results = go($sql);
            
	//var_dump($fe_element_id_results);

	return $fe_element_id_results[0]['fe_element_id']; 
}


function getPageId($name)
{
	/*global $xoopsDB;

	$sql = "SELECT page_id" .
		" FROM " . $xoopsDB->prefix("pageworks_pages") .
		" WHERE " . $xoopsDB->prefix("pageworks_pages") . ".page_name = '$name'";

	//die($sql);
    $sql_res = $xoopsDB->query($sql);
    $sql_result = $xoopsDB->fetchRow($sql_res);

	return $sql_result[0];*/ 

	$sql = "SELECT page_id" .
		" FROM " . DBPRE . "pageworks_pages" .
		" WHERE page_name = '$name'";

	//die($sql);
    
	$page_id_results = go($sql);
            
	//var_dump($page_id_results);

	return $page_id_results[0]['page_id']; 
}


function getGroupId($groupname)
{
	$sql = "SELECT groupid" .
		" FROM " . DBPRE . "groups" .
		" WHERE name = '$groupname';";

	$groupid_results = go($sql);
            
	return $groupid_results[0]['groupid']; 
}


function getGroupName($groupid)
{
	$sql = "SELECT name" .
		" FROM " . DBPRE . "groups" .
		" WHERE groupid = '$groupid';";

	$name_results = go($sql);
            
	return $name_results[0]['name']; 
}


function getDataIds($sql)
{
	$results = go($sql);
    
    /*if(!$results)
		echo "Error in <br>" . $sql . "<br>";*/

	$return_results = array();
    
    foreach($results as $result)
    {
		//echo $result['id_req'] . "<br>";    
		$return_results[] = (int)$result['id_req'];    
    }
            
	return $return_results; 
}


function linkForm($formname)
{
	//require_once XOOPS_ROOT_PATH . "/modules/formulize/include/extract.php";

	return XOOPS_URL . "/modules/formulize/index.php?fid=" . 
    	getFormId($formname);
}


function linkPage($pagename)
{
	//require_once XOOPS_ROOT_PATH . "/modules/formulize/include/extract.php";
	//require_once XOOPS_ROOT_PATH . "/modules/pageworks/include/functions.php";

	return XOOPS_URL . "/modules/pageworks/index.php?page=" . 
    	getPageId($pagename);
}

function includePage($name)
{
	global $includedPages;

	if(!is_array($includedPages))
    	$includedPages = array();

	if(in_array($name, $includedPages))
    {
		//echo "$name is already an included page";    
    }
    else    
	{    
	    $sql = "SELECT page_template" .
	        " FROM " . DBPRE . "pageworks_pages" .
	        " WHERE page_name = '$name'";

	    //die($sql);
	    
	    $page_id_results = go($sql);
	            
	    eval($page_id_results[0]['page_template']);
        
        $includedPages[] = $name;
	}         
}


function templatePrint($arguments)
{
	print($arguments["message"]);
}


function templateExplode($arguments)
{
	global $xoopsTpl;

    $xoopsTpl->assign($arguments["return"], 
    	explode($arguments["seperator"], $arguments["value"]));
}    


function templateHas($arguments)
{
	global $xoopsTpl;

    $xoopsTpl->assign($arguments["return"], 
    	in_array($arguments["key"], $arguments["value"]));
}    


function templateConcat($arguments)
{
	global $xoopsTpl;

    $xoopsTpl->assign($arguments["return"], 
    	$arguments["one"] . $arguments["two"]);
}    


function templateInternalRecordIds($arguments)
{
	global $xoopsTpl;

	//var_dump($arguments["entry"]);

    $ids = internalRecordIds($arguments["entry"], $arguments["handle"]);

	if(isset($arguments["return"]))
    {
	    $xoopsTpl->assign($arguments["return"], $ids[0]);
	}
    else    
    {    
		print $ids[0];
	}             
}


function templateDisplay($arguments)
{
	global $xoopsTpl;
    
	if(isset($arguments["return"]))
	{
	    $xoopsTpl->assign($arguments["return"], 
	        display($arguments["entry"], $arguments["handle"]));
	}
    else    
    {    
		print display($arguments["entry"], $arguments["handle"]);
	}             
}


function templateDisplayButton($arguments)
{
	if(!isset($arguments["entry"]))
    	$arguments["entry"] = "new";

	if(!isset($arguments["action"]))
    	$arguments["action"] = "replace";

	if(!isset($arguments["buttonOrLink"]))
    	$arguments["buttonOrLink"] = "button";

	if(!isset($arguments["formframe"]))
    	$arguments["formframe"] = "";


	include_once XOOPS_ROOT_PATH . "/modules/pageworks/include/displayButton_HTML.php";
	include_once XOOPS_ROOT_PATH . "/modules/pageworks/include/displayButton_Javascript.php";
	require_once "elementdisplay.php";
    
	displayButton($arguments["text"], $arguments["ele"], 
    	$arguments["value"], $arguments["entry"], $arguments["action"], 
        $arguments["buttonOrLink"], $arguments["formframe"]);
}

function templateDisplayElement($arguments) {

	if(!isset($arguments["framework"]))
	$arguments["framework"] = "";

	if(!isset($arguments["element"]))
	$arguments["element"] = "";	
	
	if(!isset($arguments["entry"]))
	$arguments["entry"] = "new";

	require_once "elementdisplay.php";

	displayElement($arguments["framework"], $arguments["element"], $arguments["entry"]);

}

function templateDisplayElementSave($arguments) {
	if(!isset($arguments["text"]))
	$arguments["text"] = "";

	if(isset($arguments["redirect_page"]))
    {
	    if(!is_numeric($arguments["redirect_page"]))
	    {
	        $arguments["redirect_page"] = getPageId($arguments["redirect_page"]);   
		}
    }
    else
	$arguments["redirect_page"] = "";	

	require_once "elementdisplay.php";

	displayElementSave($arguments["text"], $arguments["redirect_page"]);

}



function templateDisplayForm($arguments)
{
	//$formframe, $entry="", $mainform="", $done_dest="", $done_text="", $settings="", $onetooneTitles="", $overrideValue="", $overrideMulti="", $overrideSubMulti="", $viewallforms=0, $profileForm=0
	if(!isset($arguments["entry"]))
    	$arguments["entry"] = "";

	if(!isset($arguments["mainform"]))
    	$arguments["mainform"] = "";

	if(!isset($arguments["done_dest"]))
    	$arguments["done_dest"] = XOOPS_URL;

	if(!isset($arguments["done_text"]))
    	$arguments["done_text"] = "";

	if(!isset($arguments["settings"]))
    	$arguments["settings"] = "";

	if(!isset($arguments["onetooneTitles"]))
    	$arguments["onetooneTitles"] = "";

	if(!isset($arguments["overrideValue"]))
    	$arguments["overrideValue"] = "";


	if(!isset($arguments["overrideMulti"]))
    	$arguments["overrideMulti"] = "";

	if(!isset($arguments["overrideSubMulti"]))
    	$arguments["overrideSubMulti"] = "";

	if(!isset($arguments["viewallforms"]))
    	$arguments["viewallforms"] = 0;

	if(!isset($arguments["profileForm"]))
    	$arguments["profileForm"] = 0;

	require_once "formdisplay.php";
    
	displayForm($arguments["formframe"], $arguments["entry"], 
    	$arguments["mainform"], $arguments["done_dest"], $arguments["done_text"], 
    	$arguments["settings"], $arguments["onetooneTitles"], $arguments["overrideValue"], 
    	$arguments["overrideMulti"], $arguments["overrideSubMulti"], $arguments["viewallforms"], 
        $arguments["profileForm"]);
}


function templateDisplayMeta($arguments)
{
	global $xoopsTpl;
    
	if(isset($arguments["return"]))
    {
	    $xoopsTpl->assign($arguments["return"], 
	        displayMeta($arguments["entry"], $arguments["handle"]));
    }
    else
    {    
		print displayMeta($arguments["entry"], $arguments["handle"]);
	}             
}


function templateLinkForm($arguments)
{
	print linkForm($arguments["name"]);
}


function templateLinkPage($arguments)
{
	print linkPage($arguments["name"]);
}


function templateRequestId($arguments)
{
  global $xoopsTpl;

  $xoopsTpl->assign($arguments["return"], $arguments["uid"] . ",");
} 


function displayTemplate($templatename)
{
	global $xoopsTpl;


	// the following line sets the user id of the current user to the smarty 
	// variable called 'uid'
	/*global $xoopsUser;
	$xoopsTpl->assign("uid", $xoopsUser->getVar('uid'));

	$xoopsTpl->assign("groups", 
    	$xoopsUser ? $xoopsUser->getGroups() : XOOPS_GROUP_ANONYMOUS);
        
	$regusers = array_keys($groups, XOOPS_GROUP_USERS);
	unset($groups[$regusers[0]]);
	$xoopsTpl->assign("regusers", $regusers);*/

    
	$xoopsTpl->register_function("print", "templatePrint");
	//$xoopsTpl->register_function("explode", "templateExplode");
	$xoopsTpl->register_function("has", "templateHas");
	//$xoopsTpl->register_function("concat", "templateConcat");
	//$xoopsTpl->register_function("recordId", "templateInternalRecordIds");
	$xoopsTpl->register_function("meta", "templateDisplayMeta");
	$xoopsTpl->register_function("displayButton", "templateDisplayButton");
	$xoopsTpl->register_function("display", "templateDisplay");
	$xoopsTpl->register_function("displayForm", "templateDisplayForm");
	$xoopsTpl->register_function("linkForm", "templateLinkForm");
	$xoopsTpl->register_function("linkPage", "templateLinkPage");
	$xoopsTpl->register_function("displayElement", "templateDisplayElement");
	$xoopsTpl->register_function("displayElementSave", "templateDisplayElementSave");

	$xoopsTpl->register_function("requestId", "templateRequestId");

	$xoopsTpl->force_compile = true;
	$xoopsTpl->display($templatename);
}

//displayTemplate(XOOPS_ROOT_PATH . "/modules/formulize/templates/freecycle.html");


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