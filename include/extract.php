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

// SET DB PREFIX -- must be done for each installation if the installation uses a prefix other than "xoops_"
define("DBPRE", "xoops_");

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


function prepvalues($value, $type) {

	//print "$value<br>";
	//print "$type<br>";
	// handle cases where the value is linked to another form 
	if(strstr($value, "#*=:*")) {
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

	return $value;
}

// this function returns the framework handle for an element when given the caption (as formatted for form_form)
function handle($cap, $fid, $frid) {
	// switch to the actual format used in 'form' table
	$cap = eregi_replace ("`", "'", $cap);
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
function convertFinal($results, $fid, $frid) {

	for($i=0;$i<count($results);$i++) {
		$results[$i]['ele_value'] = prepvalues($results[$i]['ele_value'], $results[$i]['ele_type']);
	}

	// need to get master list of expected handles
	$fullCapList = go("SELECT ele_caption FROM " . DBPRE . "form WHERE id_form = '$fid'");
	foreach($fullCapList as $acap) {
		$fullHandleList[] = handle($acap['ele_caption'], $fid, $frid);
	}

	// get the handle for this form
	$formHandle = go("SELECT ff_handle FROM " . DBPRE . "formulize_framework_forms WHERE ff_frame_id = '$frid' AND ff_form_id = '$fid'");
	
	for($i=0;$i<count($results);$i++) {
		$handle = handle($results[$i]['ele_caption'], $fid, $frid);
		$values = explode("*=+*:", $results[$i]['ele_value']);
		foreach($values as $value) {
			$finalresults[$formHandle[0]['ff_handle']][$results[$i]['id_req']][$handle][] = $value;
		}
		// flag the current handle as found in the fullhandlelist
		$foundHandleList[$results[$i]['id_req']][] = $handle;
	}

	// add in blanks
	// if there are missing handles, then check to see which ones were not found and add them to the array with a null value
	foreach($foundHandleList as $idreq => $foundHandles) {
		if(count($foundHandles) < count($fullHandleList)) {
			foreach($fullHandleList as $handle) {
				if(!in_array($handle, $foundHandles)) {
					$finalresults[$formHandle[0]['ff_handle']][$idreq][$handle][0] = "";
				}
			}
		} elseif(count($foundHandles) > count($fullHandleList)) {
			exit("Error: more handles found for an entry than there are elements in form $fid");
		}
	}

	// assign metadata (uid, date, proxyid)
	foreach($finalresults[$formHandle[0]['ff_handle']] as $record=>$value) {
		$uid = fieldValues($results, "uid", "id_req][==][$record", "single");
		$date = fieldValues($results, "date", "id_req][==][$record", "single");
		$proxyid = fieldValues($results, "proxyid", "id_req][==][$record", "single");
		$finalresults[$formHandle[0]['ff_handle']][$record]['uid'] = $uid[0];
		$finalresults[$formHandle[0]['ff_handle']][$record]['date'] = $date[0];
		$finalresults[$formHandle[0]['ff_handle']][$record]['proxyid'] = $proxyid[0];
	}

	array_values($finalresults);
	return $finalresults;
}


function dataExtraction($frame, $form, $filter) {

	// NOTE:  currently there is no ability to filter based on group, as there is in the main module logic.  Eventually, some filtering of results based on the userid (and determined group memberships) may be required.
	// NOTE:  this function only supports one level of linking.  ie: a "subform" of a "subform" of a "mainform" will not be included, only the first sub will be.
	// NOTE:  ALL FIELDS IN A FORM **MUST** HAVE A HANDLE ASSIGNED.  Bad results are returned otherwise.

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

	$frameid = go("SELECT frame_id FROM " . DBPRE . "formulize_frameworks WHERE frame_name='$frame'");
	$frid = $frameid[0]['frame_id'];
	$formcheck = go("SELECT ff_form_id FROM " . DBPRE . "formulize_framework_forms WHERE ff_frame_id='$frid' AND ff_handle='$form'");
	$fid = $formcheck[0]['ff_form_id'];
	if (!$fid) { exit("selected form does not exist in framework"); }

	// GET THE LINK INFORMATION FOR THE CURRENT FRAMEWORK BASED ON THE REQUESTED FORM
	$linklist1 = go("SELECT fl_form2_id, fl_key1, fl_key2 FROM " . DBPRE . "formulize_framework_links WHERE fl_frame_id = '$frid' AND fl_form1_id = '$fid'");
	$linklist2 = go("SELECT fl_form1_id, fl_key1, fl_key2 FROM " . DBPRE . "formulize_framework_links WHERE fl_frame_id = '$frid' AND fl_form2_id = '$fid'");

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

	
	// RESULTS ARRAYS:
	// mainresults is the full results from main form
	// linkresults is the full results from all linked forms, where [0..n index][formid] is the form id of the form and [0..n index][results] is the array of rows returned from that form
	$mainresults = go("SELECT id_req, ele_type, ele_caption, ele_value, uid, date, proxyid FROM " . DBPRE . "form_form WHERE id_form = '$fid' ORDER BY id_req");
	$linkformids1 = fieldValues($linklist1, "fl_form2_id");
	$linkformids2 = fieldValues($linklist2, "fl_form1_id");
	$linkformids = array_merge($linkformids1, $linkformids2);
	$indexer=0;
	// here is the issue re: only one level of linking... this loop works off the linkformids array which is based on the one level links from the main form.  A recursive loop here which took all output formids and grabbed their results would produce a complete picture of the framework based on the entrypoint
	// note however that collecting together the unified results array based on such a complete picture of the framework may be an order of magnitude (or two) more complex than the simple collection process currently used below.
	foreach($linkformids as $lfid) {
		$linkresult[$indexer]['formid'] = $lfid;
		$linkresult[$indexer]['result'] = go("SELECT id_req, ele_type, ele_caption, ele_value, uid, date, proxyid FROM " . DBPRE . "form_form WHERE id_form = '$lfid' ORDER BY id_req");
		$indexer++; 
	}

	// call the function that does the conversion to the desired format:
	// [formhandle][row/record][handle/fieldname][0..n] = value(s)
	$finalresults = convertFinal($mainresults, $fid, $frid);
	for($x=0;$x<count($linkresult);$x++) {
		$finallinkresult{$linkresult[$x]['formid']} = convertFinal($linkresult[$x]['result'], $linkresult[$x]['formid'], $frid);
	}

// DEBUG CODE
//	printfclean($finalresults);
//	foreach($linkresult as $lr) {
//		printfclean($finallinkresult{$lr['formid']});
//	}


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
	
	// start the masterresult array with the complete finalresult
	$indexer = 0;
	foreach($finalresults as $form=>$records) {
		foreach($records as $record=>$values) {
			$masterresult[$indexer][$form][$record] = $values;
			// search for links based on uid
			foreach($linkformidsU as $fidu) {
				foreach($finallinkresult{$fidu} as $f => $rs) {
					foreach($rs as $r => $v) {
						if($v['uid'] == $values['uid']) {
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
								}
							}
						}			
					}
				}
			}
			$indexer++;
		}
	}

	return $masterresult;
}

function getData($form=DEFAULTFORM, $filter=DEFAULTFILTER) {
	$result = dataExtraction(FRAMEWORK, $form, $filter);
	return $result;
}

// if XOOPS has not already connected to the database, then connect to it now using user defined constants that are set in another file
// the idea is to include this file from another one

global $xoopsDB;

if(!$xoopsDB) {
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
}

?>