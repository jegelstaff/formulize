<?

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
// returns a multidimensioned array where the first index is the row of the result and the second index is the field name in that row
function go($query) {
	//print "$query"; // debug code
	$res = mysql_query($query);
	while ($array = mysql_fetch_array($res)) {
		$result[] = $array;
	}
	return $result;
}

// DISPLAYS ON THE SCREEN THE CURRENT MEMORY USAGE OF THE SCRIPT FOR DEBUGGING PURPOSES
function debug_memory($text) {
	if($_GET['debug']) {
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

function prepvalues(&$value, $type, $islinksource) {

	// handle cases where the value is linked to another form 
	if($islinksource) {
		$templinkedvals = explode("#*=:*", $value);
		$templinkedvals2 = explode("[=*9*:", $templinkedvals[2]);
		/*print "templinkedvals for display: ";
		print_r($templinkedvals2);*/
		$value = "";
		foreach($templinkedvals2 as $anentry)
		{
			$textq = go("SELECT ele_value FROM " . DBPRE . "form_form WHERE ele_id=$anentry GROUP BY ele_value ORDER BY ele_value");
			$value .= "*=+*:" . $textq[0]['ele_value'];
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

	$value = stripslashes($value);

	//and remove any leading *=+*: while we're at it...
	if(substr($value, 0, 5) == "*=+*:")	{
		$value = substr_replace($value, "", 0, 5);
	}

	return $templinkedvals2; // used for including in the linkfilters -- added sept 28 2005
//	return $value; // not necessary if passing by reference
}

// this function returns the framework handle for an element when given the caption (as formatted for form_form)
function handle($cap, $fid, $frid) {
	// switch to the actual format used in 'form' table
	$cap = eregi_replace ("`", "'", $cap);
	$cap = addslashes($cap);
	$idq = go("SELECT ele_id FROM " . DBPRE . "form WHERE ele_caption='$cap' AND id_form = '$fid'");	
	$handleq = go("SELECT fe_handle FROM " . DBPRE . "formulize_framework_elements WHERE fe_frame_id = '$frid' AND fe_form_id = '$fid' AND fe_element_id = '" . $idq[0]['ele_id'] . "'");
	return $handleq[0]['fe_handle'];
}

// this function returns the handle for an element when given the id
function handleFromId($id, $fid, $frid) {
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
// note: $results is passed by reference, saves lots of memory.  
function convertFinal(&$results, $fid, $frid="", $linkkeys="", $linkisparent="", $linkformids="", $linktargetids="") {

// prepvalues call moved to loop below for efficiency
//	for($i=0;$i<count($results);$i++) {
//		$results[$i]['ele_value'] = prepvalues($results[$i]['ele_value'], $results[$i]['ele_type']);
//	}

debug_memory("Start of covertFinal");

	// need to get master list of expected handles
	$fullCapList = go("SELECT ele_caption, ele_id FROM " . DBPRE . "form WHERE id_form = '$fid'");
	foreach($fullCapList as $acap) {
		if($frid) {
			$fullHandleList[$acap['ele_caption']] = handle($acap['ele_caption'], $fid, $frid);
		} else { // use full caption if we're doing just a form
			$fullHandleList[$acap['ele_caption']] = $acap['ele_id'];
		}
	}


	// get the handle for this form, of the full name if not
	if($frid) {
		$formHandle = go("SELECT ff_handle FROM " . DBPRE . "formulize_framework_forms WHERE ff_frame_id = '$frid' AND ff_form_id = '$fid'");
	} else {
		$formHandle = go("SELECT desc_form FROM " . DBPRE . "form_id WHERE id_form='$fid'");
	}
	
	$linkindexer=0;
	for($i=0;isset($results[$i]);$i++) {
		$islinksource = strstr($results[$i]['ele_value'], "#*=:*");
		$linkids = prepvalues($results[$i]['ele_value'], $results[$i]['ele_type'], $islinksource); // linkids is an array of the ele_ids of the values that this field links to, if it is a linked field.
		$cap = eregi_replace ("`", "'", $results[$i]['ele_caption']);  // convert caption to _form table format so that we can get the handle from the full Handle List which has array keys drawn from that table
		$handle = $fullHandleList[$cap];
		// check to see that we haven't already stored this value for this entry (takes care of situations where the database as duplicate entries with the same id_req, causing the number of values in results array to be greater than it ought to be -- problem likely caused by users clicking on the submit button more than one time?
		if(!in_array($handle, $foundHandleList[$results[$i]['id_req']])) {
			$values = explode("*=+*:", $results[$i]['ele_value']);
			foreach($values as $id=>$value) {
				$finalresults[$formHandle[0][0]][$results[$i]['id_req']][$handle][$id] = $value;
			}
			// store any values involved in a link so we can filter out the main queries on linked forms
			// major performance improvement by doing this
			if($keys = array_keys($linkkeys, $handle) AND $results[$i]['ele_value']) { // if these values are the ones the link is based on...
				foreach($keys as $key) {
					$linkvalue['fid'][$linkindexer] = $linkformids[$key];
					$linkvalue['targetid'][$linkindexer] = $linktargetids[$key];
					if($linkisparent[$key] OR $islinksource) {
						$linkvalue['values'][$linkindexer] = $linkids;
					} else {
						$linkvalue['values'][$linkindexer] = $results[$i]['ele_id'];
					}
					$linkindexer++;
				}
			}
			// flag the current handle as found in the fullhandlelist
			$foundHandleList[$results[$i]['id_req']][] = $handle;
		}
		unset($results[$i]); // free up some memory!!
	}

debug_memory("after building main array");

	// add in blanks
	// if there are missing handles, then check to see which ones were not found and add them to the array with a null value
	foreach($foundHandleList as $idreq => $foundHandles) {
		if(count($foundHandles) < count($fullHandleList)) {
			foreach($fullHandleList as $handle) {
				if(!in_array($handle, $foundHandles)) {
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

	// assign metadata (uid, date, proxyid, creation_date) 
	foreach($finalresults[$formHandle[0][0]] as $record=>$value) {
		$metadata = go("SELECT uid, proxyid, date, creation_date FROM " . DBPRE . "form_form WHERE id_req='$record' ORDER BY date DESC LIMIT 0,1");
		// NOTE: the four keys used below are "reserved terms" that cannot be used as handles for elements in the framework
		$finalresults[$formHandle[0][0]][$record]['uid'] = $metadata[0]['uid'];
		$finalresults[$formHandle[0][0]][$record]['mod_date'] = $metadata[0]['date'];
		$finalresults[$formHandle[0][0]][$record]['proxyid'] = $metadata[0]['proxyid'];
		$finalresults[$formHandle[0][0]][$record]['creation_date'] = $metadata[0]['creation_date'];
		if($keys = array_keys($linkkeys, "")) { // find all links based on uids...
			foreach($keys as $thiskey) {
				$linkvalue['fid'][$linkindexer] = $linkformids[$thiskey];
				$linkvalue['uid'][$linkindexer] = $metadata[0]['uid'];
			}
			$linkindexer++;
		}
	}

debug_memory("before returning convertFinal results");
	//array_values($finalresults); // don't understand what this is for

	return array($finalresults, $linkvalue);
}

// This function returns the caption, formatted for form_form, based on the handle for the element
function getCaptionFF($handle, $frid, $fid) {
	$elementId = go("SELECT fe_element_id FROM " . DBPRE . "formulize_framework_elements WHERE fe_frame_id = '$frid' AND fe_form_id = '$fid' AND fe_handle = '$handle'");
	$caption = go("SELECT ele_caption FROM " . DBPRE . "form WHERE ele_id = '" . $elementId[0]['fe_element_id'] . "'"); 
	$ffcaption = eregi_replace ("&#039;", "`", $caption[0]['ele_caption']);
	$ffcaption = eregi_replace ("&quot;", "`", $ffcaption);
	$ffcaption = str_replace ("'", "`", $ffcaption);
	return $ffcaption;
}

function getCaptionFFbyId($id) {
	$caption = go("SELECT ele_caption FROM " . DBPRE . "form WHERE ele_id = '$id'"); 
	$ffcaption = eregi_replace ("&#039;", "`", $caption[0]['ele_caption']);
	$ffcaption = eregi_replace ("&quot;", "`", $ffcaption);
	$ffcaption = str_replace ("'", "`", $ffcaption);
	return $ffcaption;
}

function makeFilter($filter, $frid="", $fid, $andor, $scope) {
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
				$thisMadeFilter[$id] = makeFilterInternal($thispart, $frid, $fid, $localandor, $scope);
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
		$madeFilter = makeFilterInternal($filter, $frid, $fid, $andor, $scope);
	}
	return $madeFilter;
}

// DETERMINE THE FILTER TO USE ON THE MAIN FORM
// NOTE: FILTER USES A LIKE QUERY TO MATCH, SO IT WILL PICKUP THE FILTER TERM ANYWHERE IN THE VALUE OF THE FIELD
// This loop can be optimized further in the case of AND queries, by using the previously found id_reqs as part of the WHERE clause for subsequent searches
function makeFilterInternal($filter, $frid="", $fid, $andor, $scope) {

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
				if($filterparts[0] == "uid" OR $filterparts[0] == "proxyid" OR $filterparts[0] == "creation_date" OR $filterparts[0] == "mod_date") {
					$capforfilter = $filterparts[0];
				} elseif($frid) {
					$capforfilter = getCaptionFF($filterparts[0], $frid, $fid);
				} else { // when a plain form is passed, filters must use the full form_form captions for the first filterpart, or the numeric ele_id.
					$capforfilter = $filterparts[0];
					if(is_numeric($capforfilter)) { // if we're receiving a number, then assume it is an ele_id and replace with the corresponding caption
						$capq = go("SELECT ele_caption FROM " . DBPRE . "form WHERE ele_id='$capforfilter'");
						$capforfilter = $capq[0]['ele_caption'];	
					} 
					$capforfilter = str_replace("'", "`", $capforfilter);
				}
				if($capforfilter == "") {  // ignore this filter if it's not part of this form (ie: it's for another part of the framework)
					$filterNotForThisForm++;
					continue; 
				}
				$capforfilter = addslashes($capforfilter);
				$operator = $filterparts[2]; // introduced to handle "newest" type of query
				// handle links...
				// 1. check if element in filter contains links
				// 2. search for filterpart[1] in the destination of the link, retrieve the id
				// 3. convert filterpart[1] to that id
				$checkLink = go("SELECT ele_value FROM " . DBPRE . "form_form WHERE id_form = '$fid' AND ele_caption='$capforfilter'");
				foreach($checkLink as $thisLink) {
					if(strstr($thisLink['ele_value'], "#*=:*")) {
						$parts = explode("#*=:*", $thisLink['ele_value']);
						$targetCap = str_replace ("'", "`", $parts[1]);
						//if($_GET['debug1']) { print "SELECT ele_id FROM " . DBPRE . "form_form WHERE id_form = '" . $parts[0] . "' AND ele_caption='$targetCap' AND ele_value LIKE '%" . $filterparts[1] . "%'<br>"; }											
						$targetQuery = go("SELECT ele_id FROM " . DBPRE . "form_form WHERE id_form = '" . $parts[0] . "' AND ele_caption='$targetCap' AND ele_value LIKE '%" . $filterparts[1] . "%'");											
						//if($_GET['debug1']) { print_r($targetQuery); }
						if(count($targetQuery) == 0) { continue 2; } // ignore this filter since no values where found
						if(count($targetQuery) == 1) { // only one thing found, so...
							$filterparts[1] = $targetQuery[0]['ele_id'];
						} else { // make a more complex string to slot in below
							$start = 1;
							foreach($targetQuery as $tq) {
								if($start) {
									$filterparts[1] = $tq['ele_id'];
									$start = 0;
								} else {
									$filterparts[1] .= "%' OR ele_value LIKE '%" . $tq['ele_id'];
								}
							}
						}
						break; // only deal with the first thing returned from the $checkLink query -- assume first is authoritative
					}
				}
				$tempfilter = "";
				$orderbyfilter = "";
				if(strstr($operator, "newest")) {
					$limit = substr($operator, 6);
					$tempfilter = "(ele_caption = '$capforfilter')"; 
					$orderbyfilter = " ORDER BY ele_value DESC LIMIT 0,$limit";
				} elseif ($capforfilter == "uid" OR $capforfilter == "proxyid" OR $capforfilter == "creation_date" OR $capforfilter == "mod_date") {
					if($capforfilter == "mod_date") { $capforfilter = "date"; }
					$tempfilter = "($capforfilter";
					if($operator) {
						$tempfilter .= $operator;
					} else {
						$tempfilter .= "=";
					}
					$tempfilter .= "'" . $filterparts[1] . "')";
					//print "SELECT id_req FROM " . DBPRE . "form_form WHERE id_form = '$fid' AND $tempfilter $scope GROUP BY id_req $orderbyfilter" . "<br><br>"; // DEBUG LINE
				} else {
					$tempfilter = "(ele_caption = '$capforfilter' AND ele_value";
					if($operator) {
						if($operator == "NOT LIKE") {
							$tempfilter .= " NOT LIKE '%" . $filterparts[1] . "%')";
						} else {
							$tempfilter .= $operator . "'" . $filterparts[1] . "')";
						}
					} else {
						$tempfilter .= " LIKE '%" . $filterparts[1] . "%')";
					}
				}
				// if($_GET['debug1']) { print "SELECT id_req FROM " . DBPRE . "form_form WHERE id_form = '$fid' AND $tempfilter $scope GROUP BY id_req $orderbyfilter" . "<br><br>"; } // DEBUG LINE
				$prequery = go("SELECT id_req FROM " . DBPRE . "form_form WHERE id_form = '$fid' AND $tempfilter $scope GROUP BY id_req $orderbyfilter");
				// if OR, then simply append id_reqs together, if AND, then save the overlap set
				if($andor == "OR") {
					foreach($prequery as $this) {
						$filterids[] = $this['id_req'];
					}
				} elseif ($andor == "AND") {
					foreach($prequery as $this) {
						$theseids[] = $this['id_req'];
					}
					if(count($theseids)<1) { $theseids[] = ""; }
					if(!isset($savedids)) { $savedids = $theseids; } // the first time through, make sure we save all the ids found so we have something to compare against on the next round
					$filterids = array_intersect($savedids, $theseids);
					unset($theseids); // important!  clear the IDs from this $afilter!
					$savedids = $filterids;
					
				} else {
					print "Unknown boolean operator requested as part of data extraction.";
					exit;
				}
			}
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

function buildLinkValueCondition1($thistarget, $thesevalues) {
	$FFcaption = getCaptionFFbyId($thistarget);
	$linkValueCondition = "ele_caption='$FFcaption' AND (";
	$linkValueCondition .= buildLinkValueCondition2($thesevalues);
	$linkValueCondition .= ")";
	return $linkValueCondition;
}

function buildLinkValueCondition2($thesevalues) {

// This function produces a very loose match condition, and can produce a query which will return more id_reqs than are actually wanted.  However, that will still be less than the total id_reqs in the linked form, so there should still be a considerable improvement in speed.
// This function produces a query that looks for a LIKE, not an =, and looks in ele_id and ele_value, so that's what produces false positives (a value that you are looking for in ele_id might also appear in ele_value, you just don't know.  And numeric values in ele_id will of course include others, ie: 100 includes 1 and 10.
	$start = 1;
	if(is_array($thesevalues)) {
		foreach($thesevalues as $thisvalue) {
			if($start) {
				$linkValueCondition = "(ele_value LIKE '%" . mysql_real_escape_string($thisvalue) . "%' OR ele_id LIKE '%" . mysql_real_escape_string($thisvalue) . "%')";
				$start = 0;
			} else {
				$linkValueCondition .= " OR (ele_value LIKE '%" . mysql_real_escape_string($thisvalue) . "%' OR ele_id LIKE '%" . mysql_real_escape_string($thisvalue) . "%')";
			}	
		}	
	} else {
		$linkValueCondition = "(ele_value LIKE '%" . mysql_real_escape_string($thesevalues) . "%' OR ele_id LIKE '%" . mysql_real_escape_string($thesevalues) . "%')";
	}

	return $linkValueCondition;
}


function dataExtraction($frame="", $form, $filter, $andor, $scope) {

if($_GET['debug']) { $time_start = microtime_float(); }

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


	if($frid) {
		// GET THE LINK INFORMATION FOR THE CURRENT FRAMEWORK BASED ON THE REQUESTED FORM
		$linklist1 = go("SELECT fl_form2_id, fl_key1, fl_key2, fl_relationship FROM " . DBPRE . "formulize_framework_links WHERE fl_frame_id = '$frid' AND fl_form1_id = '$fid'");
		$linklist2 = go("SELECT fl_form1_id, fl_key1, fl_key2, fl_relationship FROM " . DBPRE . "formulize_framework_links WHERE fl_frame_id = '$frid' AND fl_form2_id = '$fid'");
	}
	// link list 1 is the list of form2s that the requested form links to
	// link list 2 is the list of form1s that the requested form links to
	// ie: the link list number denotes the position of the requested form in the pair
	
	// Generate complete result set -- have to take into account all the special cases like linked selectboxes, etc
	// 1. get all entries in the requested form
	// 2. get all entries in the linked forms
	// 3. get the caption name (formatted for form_form) that is for the linked field(s)
	// 4. get each value for the linked field(s)
	// 5. for each linked form, get the caption name (formatted for form_form) for its linked fields
	// 6. for each linked form, get all entries that match the link criteria

//	print "Frame: $frame ($frid)<br>";
//	print "Form: $form ($fid)<br>";
	
	// RESULTS ARRAYS:
	// mainresults is the full results from main form
	// linkresults is the full results from all linked forms, where [0..n index][formid] is the form id of the form and [0..n index][results] is the array of rows returned from that form
	// if($_GET['debug']) { print "SELECT id_req, ele_type, ele_caption, ele_value, ele_id FROM " . DBPRE . "form_form WHERE id_form = '$fid' $mainfilter $scope ORDER BY id_req"; }// DEBUG LINE
debug_memory("Before retrieving mainresults");
	$mainresults = go("SELECT id_req, ele_type, ele_caption, ele_value, ele_id FROM " . DBPRE . "form_form WHERE id_form = '$fid' $mainfilter $scope ORDER BY id_req");
debug_memory("After retrieving mainresults");

	// generate the list of key fields in the current form, so we can use the values in these fields to filter the linked forms. -- sept 27 2005
	if($frid) {
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
		}
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
		}
		$linkkeys = array_merge($linkkeys1, $linkkeys2);
		$linkisparent = array_merge($linkisparent1, $linkisparent2);
		$linkformids = array_merge($linkformids1, $linkformids2);
		$linktargetids = array_merge($linktargetids1, $linktargetids2);

	}

	unset($mainfilter);

	// call the function that does the conversion to the desired format:
	// [formhandle][row/record][handle/fieldname][0..n] = value(s)
	list($finalresults, $linkvalue) = convertFinal($mainresults, $fid, $frid, $linkkeys, $linkisparent, $linkformids, $linktargetids);

debug_memory("after convertFinal");

	unset($mainresults);

	if($frid) {
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
				$linkfilter = makeFilter($filter, $frid, $lfid, $andor, $scope);
			} else {
				$linkfilter = "";
			}
			if($linkfilter) { // set a flag that counts the linkforms where there was a filter in place, but exclude  
				$filters = explode("][", $filter);
				foreach($filters as $afilter) {
					$filterparts = explode("/**/", $afilter);
					if($filterparts[0] != "uid" AND $filterparts[0] != "proxyid" AND $filterparts[0] != "creation_date" AND $filterparts[0] != "mod_date") {
						$lfexists[$lfid] = 1; // at least one filter was not a metadata filter (which should only be applied to the main form)
						break;
					}
				}
			}

			// generate a restiction based on the values in the main form that this form is linked to
			if($foundlinks = array_keys($linkvalue['fid'], $lfid)) {
				$startuid = 1;
				$startvalue = 1;
				unset($linkUidCondition);
				unset($linkValueCondition);
				// NOTE: THIS LOOP IS ESSENTIALLY SET UP TO ASSUME ONLY ONE KIND OF LINKING PER FORM.  IF THERE ARE TWO KINDS OF LINKS FOR A FORM, THEN THE RESULTING CONDITIONS WILL LOOK FOR ENTRIES THAT MATCH BOTH LINKS, WHICH IS OBVIOUSLY IMPOSSIBLE.  THEREFORE, THE RESULTS IN THAT CASE WILL INCLUDE NO VALUES FROM THE LINKED FORM.
				foreach($foundlinks as $oneFoundLink) {
					if($linkvalue['uid'][$oneFoundLink]) {
						if($startuid) {
							$linkUidCondition = " AND (uid=" . $linkvalue['uid'][$oneFoundLink];
							$startuid = 0;
						} else {
							$linkUidCondition .= " OR uid=" . $linkvalue['uid'][$oneFoundLink];
						}
					} elseif($linkvalue['values'][$oneFoundLink] AND $linkvalue['targetid'][$oneFoundLink]) {
						if($startvalue) {
							// get the caption for the targetid
							$linkValueCondition = " AND ((";
							$linkValueCondition .= buildLinkValueCondition1($linkvalue['targetid'][$oneFoundLink], $linkvalue['values'][$oneFoundLink]);
							$linkValueCondition .= ")";
							$startvalue = 0;									
						} else {
							$linkValueCondition .= " OR (";
							$linkValueCondition .= buildLinkValueCondition1($linkvalue['targetid'][$oneFoundLink], $linkvalue['values'][$oneFoundLink]);
							$linkValueCondition .= ")";
						}
					}
				}
				if($linkUidCondition) { $linkUidCondition .= ")"; }
				if($linkValueCondition) { $linkValueCondition .= ")"; }
				// now prequery for id_reqs so the linkresult query can operate
				//print "<br>SELECT id_req FROM " . DBPRE . "form_form WHERE id_form = '$lfid' $linkfilter $linkUidCondition $linkValueCondition ORDER BY id_req<br>"; 
				$prequery = go("SELECT id_req FROM " . DBPRE . "form_form WHERE id_form = '$lfid' $linkfilter $linkUidCondition $linkValueCondition ORDER BY id_req");
				$start = 1;
				unset($linkfilter);
				if(count($prequery)>0) {
					foreach($prequery as $thisid) {
						if($start) {
							$linkfilter = " AND (id_req='" . $thisid['id_req'] . "'";
							$start = 0;
						} else {
							$linkfilter .= " OR id_req='" . $thisid['id_req']  . "'";
						}
					}
					$linkfilter .= ")"; // guaranteed to be something since the count of prequery is verified above.
				} else { // no id_reqs found that match, so return nothing
					$linkfilter = " AND (id_req='0')";
				}
			} else { // nothing found that links up with this form, so return nothing in the link form.
				$linkfilter = " AND (id_req='0')";
			}

debug_memory("Before retrieving a linkresult");

			$linkresult = go("SELECT id_req, ele_type, ele_caption, ele_value FROM " . DBPRE . "form_form WHERE id_form = '$lfid' $linkfilter ORDER BY id_req"); // scope not used in this query for now

			unset($linkfilter);
debug_memory("After retrieving a linkresult");
			list($finallinkresult{$lfid}, $linkthroughaway) = convertFinal($linkresult, $lfid, $frid); // note...must not call returned "linkvalue" anything useful since we only care about using the returned info from the main query. 
			unset($linkresult);

		}
	}

// DEBUG CODE
//	printfclean($finalresults);
//	foreach($linkformids as $lfid) {
//		printfclean($finallinkresult{$lfid}); 
//	}

	if($frid) {
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

		$linkfids1 = fieldValues($linklist1, "fl_form2_id", "fl_key1][NOT][0");
		$linkfids2 = fieldValues($linklist2, "fl_form1_id", "fl_key2][NOT][0");
		if(is_array($linkfids1) OR is_array($linkfids2)) {
			$linkformidsE = array_merge($linkfids1, $linkfids2);
			array_unique($linkformidsE);
		}

		$linkuids1 = fieldValues($linklist1, "fl_form2_id", "fl_key1][==][0");
		$linkuids2 = fieldValues($linklist2, "fl_form1_id", "fl_key2][==][0");
		if(is_array($linkuids1) OR is_array($linkuids2)) {
			$linkformidsU = array_merge($linkuids1, $linkuids2);
			array_unique($linkformidsU);
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

debug_memory("Start of compiling masterresult");
	
	// start the masterresult array with the complete finalresult
	$indexer = 0;
	foreach($finalresults as $form=>$records) {
		foreach($records as $record=>$values) {
			$masterresult[$indexer][$form][$record] = $values;
			if($frid) {
				// search for links based on one-to-one relationships
				// 1. search for this record in the one-to-one db list
				// 2. if there are results, then if those results are part of the linkresults then add them
				// this matching is preformed within the other two loops
				// this query does not use "go" in order to eliminate the need for one round of looping
				unset($onetoonecheck);
				$onetoonecheck_sql = "SELECT link_form FROM " . DBPRE . "formulize_onetoone_links WHERE main_form='$record'";
				$onetoonecheck_res = mysql_query($onetoonecheck_sql);
				while ($array = mysql_fetch_array($onetoonecheck_res)) {
					$onetoonecheck[] = $array['link_form'];
				}
				// search for links based on uid
				foreach($linkformidsU as $fidu) {
					foreach($finallinkresult{$fidu} as $f => $rs) {
						foreach($rs as $r => $v) {
							if($v['uid'] == $values['uid']) {
								$masterresult[$indexer][$f][$r] = $v;
								continue;
							}
							// check for onetoonelinks
							if(in_array($r, $onetoonecheck)) {
								$masterresult[$indexer][$f][$r] = $v;
							}
						}
					}	
				}
				// search for link based on elements
				foreach($linkformidsE as $fide) {
					foreach($finallinkresult{$fide} as $f => $rs) {
						foreach($rs as $r => $v) {
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
							// check for onetoonelinks
							if(in_array($r, $onetoonecheck)) {
								$masterresult[$indexer][$f][$r] = $v;
							}
						}
					}
				}
			} // end of if frid
			$indexer++;
		}
	}

//print_r($masterresult);
//print "<br>";

	// remove any entries where there is no link result but there was a link filter in effect (ie: we're limiting results by hits on the link filter)
	foreach($lfexists as $lid=>$flag) {
		// look for the presence of this form in the masterresult
		// unset any masterresult entries where it is not found
		$lfhandle = go("SELECT ff_handle FROM " . DBPRE . "formulize_framework_forms WHERE ff_frame_id = '$frid' AND ff_form_id = '$lid'");
		foreach($masterresult as $masterid=>$mres) {
			if(!$mres[$lfhandle[0]['ff_handle']]) { // no subentry for this link form is present
				unset($masterresult[$masterid]);
			}		
		}
	}
//print_r($masterresult);

debug_memory("before returning result");

if($_GET['debug']) { 
	$time_end = microtime_float();
	$time = $time_end - $time_start;
	echo "Execution time is <b>$time</b> seconds\n"; 
}

	return $masterresult; 

debug_memory("after returning result");

}

function getData($framework, $form, $filter="", $andor="AND", $scope="") {
	$result = dataExtraction($framework, $form, $filter, $andor, $scope);
	return $result;
}

// *******************************
// FUNCTIONS BELOW ARE FOR PROCESSING RESULTS
// *******************************

// This function returns the caption, formatted for form (not form_form), based on the handle for the element
// assumption is that a handle is unique within a framework
function getCaption($framework, $handle) {
	if(is_numeric($framework)) {
		$frid[0]['frame_id'] = $framework;
	} else {
		$frid = go("SELECT frame_id FROM " . DBPRE . "formulize_frameworks WHERE frame_name = '$framework'");
	}
	$elementId = go("SELECT fe_element_id FROM " . DBPRE . "formulize_framework_elements WHERE fe_frame_id = '" . $frid[0]['frame_id'] . "' AND fe_handle = '$handle'");
	$caption = go("SELECT ele_caption FROM " . DBPRE . "form WHERE ele_id = '" . $elementId[0]['fe_element_id'] . "'"); 
	return $caption[0]['ele_caption'];
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
	foreach($entry as $formHandle=>$record) {
		foreach($record as $elements) {
			foreach($elements as $element=>$values) {
				if($element == $handle) {
					return $formHandle;
				}
			}
		}
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
	$paras = explode("\r", $string);
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
function internalRecordIds($entry, $formhandle, $id="NULL") {
	if(is_numeric($id)) {
		$entry = $entry[$id];
	}
	foreach($entry[$formhandle] as $id=>$element) {
		$ids[] = $id;
	}
	return $ids;
}

// THIS FUNCTION SORTS A RESULT SET, BASED ON THE VALUES OF ONE NON-MULTI FIELD (IE: CANNOT SORT BY CHECKBOX FIELD)
function resultSort($data, $handle, $order="", $type="") {
	foreach($data as $id=>$entry) {
		$sortAux[] = display($entry, $handle);
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


// if XOOPS has not already connected to the database, then connect to it now using user defined constants that are set in another file
// the idea is to include this file from another one

global $xoopsDB;

if(!$xoopsDB) {
	// SET DB PREFIX -- must be done for each installation if the installation uses a prefix other than "xoops_"
	define("DBPRE", "xoops_");
	connect(DBHOST, DBUSER, DBPASS, DBNAME);
	// language translation table if xoops own objects (and presumably language files) have not been invoked
	if(LANG == "English") {
		define("_formulize_TEMP_QYES", "Yes");
		define("_formulize_TEMP_QNO", "No");
	}
	if(LANG == "French") {
		define("_formulize_TEMP_QYES", "Oui");
		define("_formulize_TEMP_QNO", "Non");
	}
} else {
	define("DBPRE", $xoopsDB->prefix('') . "_");
}

?>