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

include_once XOOPS_ROOT_PATH . "/modules/formulize/include/functions.php";

// this file contains the functions for gathering a dataset from the database and interacting with the dataset


// RETURNS THE RESULTS OF AN SQL STATEMENT
// WARNING:  HIGHLY INEFFICIENT IN TERMS OF MEMORY USAGE!
// returns a multidimensioned array where the first index is the row of the result and the second index is the field name in that row
function go($query, $keyfield="") {
	global $xoopsDB;
	//print "$query"; // debug code
	$result = array();
	if($res = $xoopsDB->query($query)) { // appears to work OK inside Drupal.  Is this because there is always a previous query to the XOOPS DB before we get to this stage, and so it is pointing to the right place when this fires?  Maybe this should be rewritten to explicitly check for the pressence of $xoopsDB, and use that if it is found.
		while ($array = $xoopsDB->fetchBoth($res)) {
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

  global $xoopsDB;

  // return metadata values without putting them in an array
  if(isMetaDataField($field)) {
     return $value;
  }

  $elementArray = formulize_getElementMetaData($field, true);
  $type = $elementArray['ele_type'];
	
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

  // decrypt encrypted values...pretty inefficient to do this here, one query in the DB per value to decrypt them....but we'd need proper select statements with field names specified in them, instead of *, in order to be able to swap in the AES DECRYPT at the time the data is retrieved in the master query
	if($elementArray['ele_encrypt']) {		 
		 $decryptSQL = "SELECT AES_DECRYPT('".formulize_db_escape($value)."', '".getAESPassword()."')";
		 if($decryptResult = $xoopsDB->query($decryptSQL)) {
					$decryptRow = $xoopsDB->fetchRow($decryptResult);
					return $decryptRow[0];
		 } else {
					return "";
		 }
	}

    // handle cases where the value is linked to another form
    if($source_ele_value = formulize_isLinkedSelectBox($field, true)) {
        // value is an entry id in another form
        // need to get the form id by checking the ele_value[2] property of the element definition, to get the form id from the first part of that
        $sourceMeta = explode("#*=:*", $source_ele_value[2]); // [0] will be the fid of the form we're after, [1] is the handle of that element
        if($value AND $sourceMeta[1]) {
            // need to check if an alternative value field has been defined, or if we're in an export and an alterative field for exports has been defined
            // save the value before convertElementIdsToElementHandles()
            $before_conversion = $sourceMeta[1];
            $altFieldSource = "";
            if($GLOBALS['formulize_doingExport'] AND isset($source_ele_value[11]) AND $source_ele_value[11] != "none") {
                $altFieldSource = $source_ele_value[11];
            } elseif(isset($source_ele_value[EV_MULTIPLE_LIST_COLUMNS]) AND $source_ele_value[EV_MULTIPLE_LIST_COLUMNS] != "none") {
                $altFieldSource = $source_ele_value[EV_MULTIPLE_LIST_COLUMNS];
            }
            if($altFieldSource) {
                $altFieldSource = is_array($altFieldSource) ? $altFieldSource : array($altFieldSource);
                $sourceMeta[1] = convertElementIdsToElementHandles($altFieldSource, $sourceMeta[0]);
                // remove empty entries, which can happen if the "use the linked field selected above" option is selected
                $sourceMeta[1] = array_filter($sourceMeta[1]);
                // unfortunately, sometimes sourceMeta[1] seems to be saved as element handles rather than element IDs, and in that case,
                // convertElementIdsToElementHandles() returns array(0 => 'none') which causes an error in the query below.
                // check for that case here and revert back to the value of sourceMeta[1] before convertElementIdsToElementHandles()
                if ((1 == count($sourceMeta[1]) and isset($sourceMeta[1][0]) and "none" == $sourceMeta[1][0]) OR $sourceMeta[1] == "none") {
                    $sourceMeta[1] = $before_conversion;
                }
            }
            $form_handler = xoops_getmodulehandler('forms', 'formulize');
            $sourceFormObject = $form_handler->get($sourceMeta[0]);
            $sourceMeta[1] = is_array($sourceMeta[1]) ? $sourceMeta[1] : array($sourceMeta[1]);
            $query_columns = array();
            foreach ($sourceMeta[1] as $key => $handle) {
                // check if this is a link to a link
                if ($second_source_ele_value = formulize_isLinkedSelectBox($handle, true)) {
                    $secondSourceMeta = explode("#*=:*", $second_source_ele_value[2]);
                    $secondFormObject = $form_handler->get($secondSourceMeta[0]);
                    $sql = "SELECT t1.`".$secondSourceMeta[1]."` FROM ".DBPRE."formulize_".$secondFormObject->getVar('form_handle').
                        " as t1, ".DBPRE."formulize_".$sourceFormObject->getVar('form_handle'). " as t2 WHERE t2.`entry_id` IN (".trim($value, ",").
                        ") AND t1.`entry_id` IN (TRIM(',' FROM t2.`".$handle."`)) ORDER BY t2.`entry_id`";
                    if(!$res = $xoopsDB->query($sql)) {
                        print "Error: could not retrieve the source values for a linked linked selectbox ($field) during data extraction for entry number $entry_id.  SQL:<br>$sql<br>";
                    } else {
                        $row = $xoopsDB->fetchRow($res);
                        $linkedvalue = prepvalues($row[0], $handle, $entry_id);
                        $query_columns[] = "'".formulize_db_escape($linkedvalue[0])."'";
                    }
                } else {
                    $query_columns[] = "`$handle`";
                }
            }
            $sql = "SELECT ".implode(", ", $query_columns)." FROM ".DBPRE."formulize_".$sourceFormObject->getVar('form_handle').
                " WHERE entry_id IN (".trim($value, ",").") ORDER BY entry_id";
            if(!$res = $xoopsDB->query($sql)) {
                print "Error: could not retrieve the source values for a linked selectbox during data extraction for entry number $entry_id.  SQL:<br>$sql<br>";
            } else {
                $value = "";
                while($row = $xoopsDB->fetchRow($res)) {
                    $value .= "*=+*:" . implode(" - ", $row);
                }
            }
        } elseif($value) {
            $value = ""; // if there was no sourceMeta[1], which is the handle for the field in the source form, then the value should be empty, ie: we cannot make a link...this probably only happens in cases where there's a really old element that had its caption changed, and that happened before Formulize automatically updated all the linked selectboxes that rely on that element's caption, back when captions mattered in the pre F3 days
        }
    }

    // check if this is fullnames/usernames box
    // wickedly inefficient to go to DB for each value!!  This loop executes once per datapoint in the result set!!
    if($type == "select") {
        $ele_value = unserialize($elementArray['ele_value']);
        if (is_array($ele_value[2])) {
            $listtype = key($ele_value[2]);
            if($listtype === "{USERNAMES}" OR $listtype === "{FULLNAMES}") {
                $uids = explode("*=+*:", $value);
                if(count($uids) > 0) {
                    if(count($uids) > 1) {
                        array_shift($uids);
                    }
                    $uidFilter = extract_makeUidFilter($uids);
                    $listtype = $listtype == "{USERNAMES}" ? 'uname' : 'name';
                    $value = "";
                    if (strlen($uidFilter) > 4) {   // skip this when $uidFilter = "uid=" becaues the query will fail
                        $names = go("SELECT uname, name FROM " . DBPRE . "users WHERE $uidFilter ORDER BY $listtype");
                        foreach($names as $thisname) {
                            if($thisname[$listtype]) {
                                $value .= "*=+*:" . $thisname[$listtype];
                            } else {
                                $value .= "*=+*:" . $thisname['uname'];
                            }
                        }
                    }
                } else {
                    $value = "";
                }
            }
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
		$newValueq = go("SELECT other_text FROM " . DBPRE . "formulize_other, " . DBPRE . "formulize WHERE " . DBPRE . "formulize_other.ele_id=" . DBPRE . "formulize.ele_id AND " . DBPRE . "formulize.ele_handle=\"" . formulize_db_escape($field) . "\" AND " . DBPRE . "formulize_other.id_req='".intval($entry_id)."' LIMIT 0,1");
		//$value_other = _formulize_OPT_OTHER . $newValueq[0]['other_text'];
        // removing the "Other: " part...we just want to show what people actually typed...doesn't have to be flagged specifically as an "other" value
        $value_other = $newValueq[0]['other_text'];
		$value = preg_replace('/\{OTHER\|+[0-9]+\}/', $value_other, $value); 
	} else {
        $value = formulize_swapUIText($value, unserialize($elementArray['ele_uitext']));
    }

	  if(file_exists(XOOPS_ROOT_PATH."/modules/formulize/class/".$type."Element.php")) {
	       $elementTypeHandler = xoops_getmodulehandler($type."Element", "formulize");
	       $preppedValue = $elementTypeHandler->prepareDataForDataset($value, $field, $entry_id);
	       if(!is_array($preppedValue)) {
		    return array($preppedValue);
	       } else {
		    return $preppedValue;
	       }
	  }


	return explode("*=+*:",$value);
}

function microtime_float()
{
   list($usec, $sec) = explode(" ", microtime());
   return ((float)$usec + (float)$sec);
}

function getData($framework, $form, $filter="", $andor="AND", $scope="", $limitStart="", $limitSize="", $sortField="",
    $sortOrder="", $forceQuery=false, $mainFormOnly=0, $includeArchived=false, $dbTableUidField="", $id_reqsOnly=false,
    $resultOnly=false, $filterElements=null, $cacheKey="")
{
    // $id_reqsOnly, only works with the main form!! returns array where keys and values are the id_reqs
    if ($framework == "") {
        // we want to afirmatively make this a zero and not a null or anything else, for purposes of having
        //  consistent cacheKeys
        $framework = 0;
    }
    if (is_numeric($framework)) {
        // further standardization, to make cachekeys work better
        $framework = intval($framework);
    }
    if (is_numeric($form)) {
        $form = intval($form);
    }
    if (is_numeric($filter)) {
        $filter = intval($filter);
    }
    if (!$cacheKey) {
        return getDataCached($framework, $form, $filter, $andor, $scope, $limitStart, $limitSize, $sortField,
            $sortOrder, $forceQuery, $mainFormOnly, $includeArchived, $dbTableUidField, $id_reqsOnly, $resultOnly,
            $filterElements);
    }

    global $xoopsDB;

    if (substr($filter, 0, 7) == "SELECT ") {
        // use the SQL statement that has been passed in the $filter variable (for the export feature)
        $result = dataExtraction(intval($framework), intval($form), $filter, null, null, $limitStart, $limitSize,
            null, null, null, null);
        return $result;
    }

    include_once XOOPS_ROOT_PATH . "/modules/formulize/include/functions.php";
    $sortField = dealWithDeprecatedFrameworkHandles($sortField, $framework);

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
    if (is_numeric($form)) {
        $checkTableForm = $xoopsDB->query("SELECT tableform, desc_form FROM ".DBPRE."formulize_id WHERE id_form=$form");
        $tableFormRow = $xoopsDB->fetchRow($checkTableForm);
        $isTableForm = $tableFormRow[0] == "" ? false : true;
    }

    // handle old style sort and order values...
    $sortOrder = ($sortOrder == "SORT_ASC" OR $sortOrder == "ASC") ? "" : $sortOrder;
    $sortOrder = ($sortOrder == "SORT_DESC") ? "DESC" : $sortOrder;

    if ($isTableForm) {
        $result = dataExtractionTableForm($tableFormRow[0], $tableFormRow[1], $form, $filter, $andor, $limitStart, $limitSize, $sortField, $sortOrder);
    } elseif (substr($framework, 0, 3) == "db:") {
        // deprecated...tableforms are preferred approach now for direct table access
        $result = dataExtractionDB(substr($framework, 3), $filter, $andor, $scope, $dbTableUidField);
    } else {
        $result = dataExtraction($framework, $form, $filter, $andor, $scope, $limitStart, $limitSize, $sortField, $sortOrder, $forceQuery, $mainFormOnly, $includeArchived, $id_reqsOnly, $resultOnly, $filterElements);
    }

    if ($cacheKey AND !isset($GLOBALS['formulize_doNotCacheDataSet'])) {
        // doNotCacheDataSet can be set, so that this query will be repeated next time instead of pulled from the cache.  This is most useful to declare in derived value formulas that cause a change to the underlyin dataset by writing things to a form that is involved in this dataset.
        $GLOBALS['formulize_cachedGetDataResults'][$cacheKey] = $result;
    }

    if (isset($GLOBALS['formulize_doNotCacheDataSet'])) {
        // this needs to be declared before or during each extraction that should now be cached...caching is too important a cost savings when building the page
        unset($GLOBALS['formulize_doNotCacheDataSet']);
    }

    return $result;
}


function getDataCached($framework, $form, $filter="", $andor="AND", $scope="", $limitStart="", $limitSize="",
    $sortField="", $sortOrder="", $forceQuery=false, $mainFormOnly=0, $includeArchived=false, $dbTableUidField="",
    $id_reqsOnly=false, $resultOnly=false, $filterElements=null)
{
    if (isset($GLOBALS['formulize_cachedGetDataResults'][serialize(func_get_args())])) {
        return $GLOBALS['formulize_cachedGetDataResults'][serialize(func_get_args())];
    } else {
        $cacheKey = serialize(func_get_args());
        return getData($framework, $form, $filter, $andor, $scope, $limitStart, $limitSize, $sortField, $sortOrder, $forceQuery, $mainFormOnly, $includeArchived, $dbTableUidField, $id_reqsOnly, $resultOnly, $filterElements, $cacheKey);
    }
}


function dataExtraction($frame="", $form, $filter, $andor, $scope, $limitStart, $limitSize, $sortField, $sortOrder, $forceQuery, $mainFormOnly, $includeArchived=false, $id_reqsOnly=false, $resultOnly=false, $filterElements=null) {
     global $xoopsDB;
     
     $limitStart = intval($limitStart);
     $limitSize = intval($limitSize);
     $sortField = formulize_db_escape($sortField);

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
	   } elseif($GLOBALS['formulize_versionFourOrHigher'] == false) {
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

	  $form_handler = xoops_getmodulehandler('forms', 'formulize');
	  $formObject = $form_handler->get($fid);
       
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
				     $handleforlink = formulize_getElementHandleFromID($theselinks['fl_key1']);
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
				     $handleforlink = formulize_getElementHandleFromID($theselinks['fl_key2']);
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
             $linkformids = array();
		     $linktargetids = "";
	 $linkselfids = "";
		     $linkcommonvalue = "";
	     }
			 //print_r( $linkformids );
			 $GLOBALS['formulize_linkformidsForCalcs'] = $linkformids; 
  
	      // now that we have the full details from the framework, figure out the full SQL necessary to get the entire dataset
	  // This whole approach is predicated on being able to do reliable joins between the key fields of each form
	  
	  // Structure of the SQL should be...
	  // SELECT main.entry_id, main.creation_uid, main.mod_uid, main.creation_datetime, main.mod_datetime, main.handle1...main.handleN, f2.entry_id, f2.1..f2.n, etc FROM formulize_A AS main [join syntax] WHERE main.handle1 = "whatever" AND/OR f2.handle1 = "whatever"
	  // Join syntax:  if there are query terms on the f2 or subsequent forms, then use INNER JOIN formulize_B AS f2 ON main.1 LIKE CONCAT('%,', f2.entry_id, ',%') -- or no %, ,% if only one value is allowed
	  // If there are no query terms on the f2 or subsequent forms, then use LEFT JOIN
	  
	  // establish the join type and all that
	  
	  $joinText = "";
	  $linkSelect = "";
	  $exportOverrideQueries = array();

    $limitClause = "";
    if ($limitSize) {
        $limitClause = " LIMIT $limitStart, $limitSize ";
    }

	  if(is_array($filter) OR (substr($filter, 0, 6) != "SELECT" AND substr($filter, 0, 6) != "INSERT")) { // if the filter is not itself a fully formed SQL statement...
    
	       $scopeFilter = "";
	       if(is_array($scope)) { // assume any arrays are groupid arrays, and so make a valid scope string based on this.  Use the new entry owner table.
		    if(count($scope) > 0 ) {
			 $start = true;
			 foreach($scope as $groupid) { // need to loop through the array, and not use implode, so we can sanitize the values
			      if(!$start) {
				   $scopeFilter .= " OR scope.groupid=".intval($groupid);
			      } else {
				   $start = false;
           $scopeFilter = " AND EXISTS(SELECT 1 FROM ".DBPRE."formulize_entry_owner_groups AS scope WHERE (scope.entry_id=main.entry_id AND scope.fid=".intval($fid).") AND (scope.groupid=".intval($groupid);
			      }
			 }
       $scopeFilter .= ")) "; // need two closing brackets for the exists statement and its where clause
		    } else { // no valid entries found, so show no entries
			 $scopeFilter = " AND main.entry_id<0 ";
		    }
	       } elseif($scope) { // need to handle old "uid = X OR..." syntax
		    $scopeFilter = " AND (".str_replace("uid", "main.creation_uid", $scope).") ";
	       }

         formulize_getElementMetaData("", false, $fid); // initialize the element metadata for this form...serious performance gain from this 
	       list($formFieldFilterMap, $whereClause, $orderByClause, $oneSideFilters, $otherPerGroupFilterJoins, $otherPerGroupFilterWhereClause) = formulize_parseFilter($filter, $andor, $linkformids, $fid, $frid);
         
         // ***********************
         // NOTE:  the oneSideFilters are divided into two sections, the AND filters and OR filters for a given form
         // These will need to be constructed differently if we are ever to support OR filters that are spread across forms.
         // Right now, oneSideFilters get rendered with all other filters for their form, which is fine if the OR filters all belong to the same form
         // But if there are OR filters on two different forms, then we will need to do some kind of much more complex handling of the OR filters, or else the count query, and the queries for calculations, will be screwed up
         // The proper approach to this would be to have the AND oneSideFilters divided by form, like now, but for the ORs, we would need to loop through all ORs on all forms at once, and concatenate them somehow, ie:
         // foreach($oneSideFilters as $thisOneSideFid=>$oneSideFilterData) {
         //   foreach($oneSideFilterData as $oneSideAndOr=>$thisOneSideFilter) {
         //     if($oneSideAndOr == "and") { // note, it's forced to lowercase in the parseFilter function
         //       // then add this filter to the exists/other construction/whatever for this particular form
         //     } else {
         //       // then add this filter to a more complex exists/other construction/whatever that contains all the ORs, grouped by form, with ORs in between them
         //       // ie, the final output would be like:  AND ( exists(select 1 from table1 where field11=x or field12=y) OR exists(select 1 from table2 where field21 LIKE '%t%') OR (exists(select 1 from table3 where field31 > 23) )
         //       // And this entire construction would be then added to queries, to account properly for all the OR operators that had been requested
         //     }
         //   }
         // }
	 
	 // NOTE: Oct 17 2011 -- since we are now splitting multiform queries into may different individual collections of entries, it may be possible to do what's suggested above more easily. However, we still need the full where clause at our disposal in the main query that gets the main form entry ids, or else we'll have an incorrect master list of entry ids to return.  :-(
	 
         // ***********************
         
         if(isset($oneSideFilters[$fid])) {
               foreach($oneSideFilters[$fid] as $thisOneSideFilter) {
                    $mainFormWhereClause .= " AND ( $thisOneSideFilter ) ";
               }
         } else {
               $mainFormWhereClause = "";
         }

	       if($whereClause) {
		    $whereClause = "AND $whereClause";
	       }     
	       
	       // create the per-group filters, if any, that apply to this user...only available when all XOOPS is invoked, not available when extract.php is being direct included
					global $xoopsDB;
					$perGroupFilter = "";
					$perGroupFiltersPerForms = array(); // used with exists clauses and other per-form situations
					if($xoopsDB) {
							 $form_handler = xoops_getmodulehandler('forms', 'formulize');
							 $perGroupFilter = $form_handler->getPerGroupFilterWhereClause($fid, "main");
							 $perGroupFiltersPerForms[$fid] = $perGroupFilter;
							 if($frid) {
										foreach($linkformids as $id=>$thisLinkFid) {
												 $perGroupFiltersPerForms[$thisLinkFid] = $form_handler->getPerGroupFilterWhereClause($thisLinkFid, "f".$id);
										}
							 }
					}			 
				 
	       
	       if($frid) {
           $joinHandles = formulize_getJoinHandles(array(0=>$linkselfids, 1=>$linktargetids)); // get the element handles for these elements, since we need those to properly construct the join clauses
           $newJoinText = ""; // "new" variables initilized in each loop
           $joinTextIndex = array();
	   $joinTextTableRef = array();
	   $linkSelectIndex = array();
           $newexistsJoinText = "";
           $joinText = ""; // not "new" variables persist (with .= operator)
           $existsJoinText = "";
           foreach($linkformids as $id=>$linkedFid) {
	       // validate that the join conditions are valid...either both must have a value, or neither must have a value (match on user id)...otherwise the join is not possible
	       if(($joinHandles[$linkselfids[$id]] AND $joinHandles[$linktargetids[$id]]) OR ($linkselfids[$id] == '' AND $linktargetids[$id] == '')) { 
		   
		    formulize_getElementMetaData("", false, $linkedFid); // initialize the element metadata for this form...serious performance gain from this
		    $linkSelectIndex[$linkedFid] = "f$id.entry_id AS f".$id."_entry_id, f$id.creation_uid AS f".$id."_creation_uid, f$id.mod_uid AS f".$id."_mod_uid, f$id.creation_datetime AS f".$id."_creation_datetime, f$id.mod_datetime AS f".$id."_mod_datetime, f$id.*";
		    $linkSelect .= ", f$id.entry_id AS f".$id."_entry_id, f$id.creation_uid AS f".$id."_creation_uid, f$id.mod_uid AS f".$id."_mod_uid, f$id.creation_datetime AS f".$id."_creation_datetime, f$id.mod_datetime AS f".$id."_mod_datetime, f$id.*";
		    $joinType = isset($formFieldFilterMap[$linkedFid]) ? "INNER" : "LEFT";
		    $linkedFormObject = $form_handler->get($linkedFid);
		    $joinTextTableRef[$linkedFid] = DBPRE . "formulize_" . $linkedFormObject->getVar('form_handle') . " AS f$id ON ";
		    $joinText .= " $joinType JOIN " . DBPRE . "formulize_" . $linkedFormObject->getVar('form_handle') . " AS f$id ON"; // NOTE: we are aliasing the linked form tables to f$id where $id is the key of the position in the linked form metadata arrays where that form's info is stored
		    $newexistsJoinText = $existsJoinText ? " $andor " : "";
		    $newexistsJoinText .= " EXISTS(SELECT 1 FROM ". DBPRE . "formulize_" . $linkedFormObject->getVar('form_handle') . " AS f$id WHERE "; // set this up also so we have it available for one to many/many to one calculations that require it 
		    if($linkcommonvalue[$id]) { // common value
		      $newJoinText = " main.`" . $joinHandles[$linkselfids[$id]] . "`=f$id.`" . $joinHandles[$linktargetids[$id]]."`";
		    } elseif($linktargetids[$id]) { // linked selectbox
		      
		      
		      if($target_ele_value = formulize_isLinkedSelectBox($linktargetids[$id])) {
                            if ($target_ele_value[1]) {
                                // multiple values allowed
                                $newJoinText = " f$id.`" . $joinHandles[$linktargetids[$id]] . "` LIKE CONCAT('%,',main.entry_id,',%')";
                            } else {
                                // single value only
                                $newJoinText = " f$id.`" . $joinHandles[$linktargetids[$id]] . "` = main.entry_id";
                            }
                        } else {
                            $main_ele_value = formulize_isLinkedSelectBox($linkselfids[$id]); 
                            //  we know it's linked because this is a linked selectbox join, we just need the ele_value properties
                            if ($main_ele_value[1]) {
                                // multiple values allowed
                                $newJoinText = " main.`" . $joinHandles[$linkselfids[$id]] . "` LIKE CONCAT('%,',f$id.entry_id,',%')";
                            } else {
                                // single value only
                                $newJoinText = " main.`" . $joinHandles[$linkselfids[$id]] . "` = f$id.entry_id";
                            }
                        }
		      
		      
		      
		    } else { // join by uid
		      $newJoinText = " main.creation_uid=f$id.creation_uid";
		    }
							if(isset($perGroupFiltersPerForms[$linkedFid])) {
								$newJoinText .= $perGroupFiltersPerForms[$linkedFid];
							}
		    $joinTextIndex[$linkedFid] = $newJoinText;
		    
		    $joinText .= $newJoinText;
		    if(count($oneSideFilters[$linkedFid])>0) { // only setup the existsJoinText when there is a where clause that applies to this form...otherwise, we don't care, this form is not relevant to the query that the calculations will do (except maybe when the mainform is not the one-side form...but that's another story)
		      $existsJoinText .= $newexistsJoinText . $newJoinText;
		      foreach($oneSideFilters[$linkedFid] as $thisOneSideFilter) {
										       $thisLinkedFidPerGroupFilter = isset($perGroupFiltersPerForms[$linkedFid]) ? $perGroupFiltersPerForms[$linkedFid] : "";
			   $existsJoinText .= " AND ( $thisOneSideFilter $thisLinkedFidPerGroupFilter) ";
		      }
		      $existsJoinText .= ") "; // close the exists clause itself
		    }
	       }
           }
	       }
	       
	       // specify the join info for user table (depending whether there's a query on creator_email or not)
	       $userJoinType = $formFieldFilterMap['creator_email'] ? "INNER" : "LEFT";
	       $userJoinText = " $userJoinType JOIN " . DBPRE . "users AS usertable ON main.creation_uid=usertable.uid";
	       
          $sortIsOnMain = true;
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
               } elseif($sortField == "entry_id") {
                    $sortField = "entry_id";
                    $elementMetaData['id_form'] = $fid;
               } else {
                    $elementMetaData = formulize_getElementMetaData($sortField, true); // need to get form that sort field is part of...               
               }
               $sortFid = $elementMetaData['id_form'];
               if($sortFid == $fid) {
                    $sortFidAlias = "main";
               } else {
                    $sortFidAlias = array_keys($linkformids, $sortFid); // position of this form in the linking relationships is important for identifying which form alias to use
                    $sortFidAlias = "f".$sortFidAlias[0];
                    $sortIsOnMain = false;
               }
							 $sortFieldMetaData = formulize_getElementMetaData($sortField, true);
							 if($sortFieldMetaData['ele_encrypt']) {
										$sortFieldFullValue = "AES_DECRYPT($sortFidAlias.`$sortField`, '".getAESPassword()."')"; // sorts as text, which will screw up number fields
							 } elseif(formulize_isLinkedSelectBox($sortField, true)) {
							    $ele_value = unserialize($sortFieldMetaData['ele_value']);
							    $boxproperties = explode("#*=:*", $ele_value[2]);
							    $target_fid = $boxproperties[0];
							    $target_element_handle = $boxproperties[1];
							    $form_handler = xoops_getmodulehandler('forms', 'formulize');
							    $targetFormObject = $form_handler->get($target_fid);
							    // note you cannot sort by multi select boxes!
							    $sortFieldFullValue = "(SELECT sourceSortForm.`".$target_element_handle."` FROM ".DBPRE."formulize_".$targetFormObject->getVar('form_handle')." as sourceSortForm WHERE sourceSortForm.`entry_id` = ".$sortFidAlias.".`".$sortField."`)";
							 } else {
								$sortFieldFullValue = "$sortFidAlias.`$sortField`";
							 }
               $orderByClause = " ORDER BY $sortFieldFullValue $sortOrder ";
          } elseif(!$orderByClause) {
               $orderByClause = "ORDER BY main.entry_id";
          }
	  		    
	       debug_memory("Before retrieving mainresults");
					
	       //$beforeQueryTime = microtime_float();
	       
		$countMasterResults = "SELECT COUNT(main.entry_id) FROM " . DBPRE . "formulize_" . $formObject->getVar('form_handle') . " AS main ";
	    $countMasterResults .= "$userJoinText $otherPerGroupFilterJoins WHERE main.entry_id>0 $mainFormWhereClause $scopeFilter $otherPerGroupFilterWhereClause "; 
	    $countMasterResults .= $existsJoinText ? " AND ($existsJoinText) " : "";
	    $countMasterResults .= isset($perGroupFiltersPerForms[$fid]) ? $perGroupFiltersPerForms[$fid] : "";
		if(isset($GLOBALS['formulize_getCountForPageNumbers'])) {
	       // If there's an LOE Limit in place, check that we're not over it first
	       global $formulize_LOE_limit;
	       if($countMasterResultsRes = $xoopsDB->query($countMasterResults)) {
		    $countMasterResultsRow = $xoopsDB->fetchRow($countMasterResultsRes);
		    if($countMasterResultsRow[0] > $formulize_LOE_limit AND $formulize_LOE_limit > 0 AND !$forceQuery AND !$limitClause) {
			 return $countMasterResultsRow[0];
		    } else {
			 // if we're in a getData call from displayEntries, put the count in a special place for use in generating page numbers
			 $GLOBALS['formulize_countMasterResultsForPageNumbers'] = $countMasterResultsRow[0]; 
		    } 
	       } else {
		    exit("Error: could not count master results.<br>".$xoopsDB->error()."<br>SQL:$countMasterResults<br>");
	       }
	       unset($GLOBALS['formulize_getCountForPageNumbers']);
		}   
        // now, if there's framework in effect, get the entry ids of the entries in the main form that match the criteria, so we can use a specific query for them instead of the order clause in the master query
        $limitByEntryId = "";
		$useAsSortSubQuery = "";
        if($frid) {
            $limitByEntryId = " AND (";
            $entryIdQuery = str_replace("COUNT(main.entry_id)", "main.entry_id as main_entry_id", $countMasterResults); // don't count the entries, select their id numbers
            if(!$sortIsOnMain) {
				$sortFieldMetaData = formulize_getElementMetaData($sortField, true);
				$sortFormObject = $form_handler->get($sortFid);
				if($sortFieldMetaData['ele_encrypt']) {
					$useAsSortSubQuery = "(SELECT max(AES_DECRYPT(`$sortField`, '".getAESPassword()."')) as subsort FROM ".DBPRE."formulize_" . $sortFormObject->getVar('form_handle') . " as $sortFidAlias WHERE ".$joinTextIndex[$sortFid]. " ORDER BY subsort $sortOrder) as usethissort";
				} else {
					$useAsSortSubQuery = "(SELECT max(`$sortField`) as subsort FROM ".DBPRE."formulize_" . $sortFormObject->getVar('form_handle') . " as $sortFidAlias WHERE ".$joinTextIndex[$sortFid]. " ORDER BY subsort $sortOrder) as usethissort";
				}
				$entryIdQuery = str_replace("SELECT main.entry_id as main_entry_id ", "SELECT $useAsSortSubQuery, main.entry_id as main_entry_id ", $entryIdQuery); // sorts as text which will screw up number fields
				$thisOrderByClause = " ORDER BY usethissort $sortOrder ";
	        } else {
				$thisOrderByClause = $orderByClause;
	        }
			$entryIdQuery .= " $thisOrderByClause $limitClause";
		    $entryIdResult = $xoopsDB->query($entryIdQuery);
		    $start = true;
		    while($entryIdValue = $xoopsDB->fetchArray($entryIdResult)) {
			    $limitByEntryId .= !$start ? " OR " : "";
			    $limitByEntryId .= "main.entry_id = " . $entryIdValue['main_entry_id'];
			    $start = false;
		    }
		    $limitByEntryId .= ") ";
		    if(!$start) {
				$limitClause = ""; // nullify the existing limitClause since we don't want to use it in the actual query 
		    } else {
				$limitByEntryId = "";
		    }
	    }
      

    $selectClause = "";
    $sqlFilterElements = array();
    if( $filterElements ) { // THIS IS HIGHLY EXPERIMENTAL...BECAUSE THE PROCESSING OF DATASETS RELIES RIGHT NOW ON METADATA BEING PRESENT AT THE FRONT OF EACH SET OF FIELDS, THERE IS FURTHER WORK REQUIRED TO MAKE THIS FUNCTION WITH THE CODE THAT PROCESSES ENTRIES
      //print_r( $filterElements );
      //print_r( $linkformids );
      foreach($filterElements as $passedForm=>$passedElements) {
        if($passedForm == $fid) {
           $formAlias = "main";
        } else {
           $keys = array_keys( $linkformids, $passedForm );
           //print_r( $keys );
           $formAlias = "f" . $keys[0];
        }
        foreach($passedElements as $thisPassedElement) {
          $sqlFilterElements[] = $formAlias . ".`" . formulize_db_escape($thisPassedElement) . "`";
        }
      }
    }
    if( count( $sqlFilterElements ) > 0 ) {
      $selectClause = implode( ",", $sqlFilterElements );
    } else {
      $selectClause = "main.entry_id AS main_entry_id, main.creation_uid AS main_creation_uid, main.mod_uid AS main_mod_uid, main.creation_datetime AS main_creation_datetime, main.mod_datetime AS main_mod_datetime, main.* $linkSelect";
    }


    // if this is being done for gathering calculations, and the calculation is requested on the one side of a one to many/many to one relationship, then we will need to use different SQL to avoid duplicate values being returned by the database
    // note: when the main form is on the many side of the relationship, then we need to do something rather different...not sure what it is yet...the SQL as prepared is based on the calculation field and the main form being the one side (and so both are called main), but when field is on one side and main form is many side, then the aliases don't match, and scopefilter issues abound.
    // NOTE: Oct 17 2011 - the $oneSideSQL is also used when there are multiple linked subforms, since the exists structure is efficient compared to multiple joins
    $oneSideSQL = " FROM " . DBPRE . "formulize_" . $formObject->getVar('form_handle') . " AS main $userJoinText WHERE main.entry_id>0 $scopeFilter "; // does the mainFormWhereClause need to be used here too?  Needs to be tested. -- further note: Oct 17 2011 -- appears oneSideFilters[fid] is the same as the mainformwhereclause
    $oneSideSQL .= $existsJoinText ? " AND ($existsJoinText) " : "";
    if(count($oneSideFilters[$fid])>0) {
       foreach($oneSideFilters[$fid] as $thisOneSideFilter) {
          $oneSideSQL .= " $andor ( $thisOneSideFilter ) ";  // properly introduce these filters...need to move $andor to a higher level and put this inside ( ) ?? or maybe this just all gets redone if/when the OR bug is fixed (see big note up where oneSideFilters are first received from parseFilter function)
       }
    }
    $oneSideSQL .= isset($perGroupFiltersPerForms[$fid]) ? $perGroupFiltersPerForms[$fid] : "";

     $restOfTheSQL = " FROM " . DBPRE . "formulize_" . $formObject->getVar('form_handle') . " AS main $userJoinText $joinText $otherPerGroupFilterJoins WHERE main.entry_id>0 $whereClause $scopeFilter $perGroupFilter $otherPerGroupFilterWhereClause $limitByEntryId $orderByClause ";
     $restOfTheSQLForExport = " FROM " . DBPRE . "formulize_" . $formObject->getVar('form_handle') . " AS main $userJoinText $joinText $otherPerGroupFilterJoins WHERE main.entry_id>0 $whereClause $scopeFilter $perGroupFilter $otherPerGroupFilterWhereClause $orderByClause ";  // don't use limitByEntryId since exports include all entries
     if(count($linkformids)>1) { // AND $dummy == "never") { // when there is more than 1 joined form, we can get an exponential explosion of records returned, because SQL will give you all combinations of the joins
       if(!$sortIsOnMain) {
	    $orderByToUse = " ORDER BY usethissort $sortOrder ";
	    $useAsSortSubQuery = " @rownum:=@rownum+1, $useAsSortSubQuery,"; // need to add a counter as the first field, used as the master sorting key
       } else {
	    $orderByToUse = $orderByClause;
	    $useAsSortSubQuery = "  @rownum:=@rownum+1, "; // need to add a counter as the first field, used as the master sorting key
       }
       $oneSideSQLToUse = str_replace(" AS main $userJoinText"," AS main JOIN (SELECT @rownum := 0) as r $userJoinText",$oneSideSQL); // need to add the initialization of the rownum, which is what we use as the master sorting key
       $masterQuerySQL = "SELECT $useAsSortSubQuery main.entry_id $oneSideSQLToUse $limitByEntryId $orderByToUse ";
       $masterQuerySQLForExport = "SELECT $useAsSortSubQuery main.entry_id $oneSideSQLToUse $orderByToUse "; // no limit by entry id, since all entries should be included in exports
       if(!$resultOnly) {
	    // so let's build a temp table with the unique entry ids in the forms that we care about, and then query each linked form separately for its records, so that we end up processing as few result rows as possible
	    $masterQuerySQL = "INSERT INTO ".DBPRE."formulize_temp_extract_REPLACEWITHTIMESTAMP $masterQuerySQL ";
	    $masterQuerySQLForExport = "INSERT INTO ".DBPRE."formulize_temp_extract_REPLACEWITHTIMESTAMP $masterQuerySQLForExport ";
       }
     } else { 
	  $masterQuerySQL = "SELECT $selectClause, usertable.email AS main_email, usertable.user_viewemail AS main_user_viewemail $restOfTheSQL ";
	  $masterQuerySQLForExport = "SELECT $selectClause, usertable.email AS main_email, usertable.user_viewemail AS main_user_viewemail $restOfTheSQLForExport ";
     }
     

     $GLOBALS['formulize_queryForCalcs'] = " FROM " . DBPRE . "formulize_" . $formObject->getVar('form_handle') . " AS main $userJoinText $joinText WHERE main.entry_id>0  $whereClause $scopeFilter ";
     $GLOBALS['formulize_queryForCalcs'] .= isset($perGroupFiltersPerForms[$fid]) ? $perGroupFiltersPerForms[$fid] : "";
     $GLOBALS['formulize_queryForOneSideCalcs'] = $oneSideSQL;
     if($GLOBALS['formulize_returnAfterSettingBaseQuery']) { return true; } // if we are only setting up calculations, then return now that the base query is built
	  $sortIsOnMainFlag = $sortIsOnMain ? 1 : 0;
	  // need to include the query first, so the SELECT or INSERT is the first thing in the string, so we catch it properly when coming back through the export process
	  $GLOBALS['formulize_queryForExport'] = $masterQuerySQLForExport." -- SEPARATOR FOR EXPORT QUERIES -- ".$sortIsOnMainFlag; // "$selectClauseToUse FROM " . DBPRE . "formulize_" . $formObject->getVar('form_handle') . " AS main $userJoinText $joinText $otherPerGroupFilterJoins WHERE main.entry_id>0 $whereClause $scopeFilter $perGroupFilter $otherPerGroupFilterWhereClause $limitByEntryId $orderByClause $limitClause";
	  
        $useFidForCurFormId = false;
  } else { // end of if the filter has a SELECT in it
	  if(strstr($filter," -- SEPARATOR FOR EXPORT QUERIES -- ")) {
	       $exportOverrideQueries = explode(" -- SEPARATOR FOR EXPORT QUERIES -- ",$filter);
	       $masterQuerySQL = $exportOverrideQueries[0];
	       $sortIsOnMain = $exportOverrideQueries[1];
	  } else {
	       $masterQuerySQL = $filter; // need to split this based on some separator, because export ends up passing in a series of statements     
	  }
      $useFidForCurFormId = true;
  }
  
  // after the export query has been generated, then let's put the limit on:
  $masterQuerySQL .= $limitClause;
  
     /*global $xoopsUser;
     if($xoopsUser->getVar('uid') == 4613) {
          $queryTime = $afterQueryTime - $beforeQueryTime;
          print "Query time: " . $queryTime . "<br>";
     }*/
     
     debug_memory("After retrieving mainresults");
     
     // Debug Code
     
     //global $xoopsUser;
     //if($xoopsUser->getVar('uid') == 1) {
     //     print "<br>Count query: $countMasterResults<br><br>";
    //    print "Master query: $masterQuerySQL<br>";
     //}
     
		 formulize_benchmark("Before query");

     if(count($linkformids)>1) { // AND $dummy=="never") { // when there is more than 1 joined form, we can get an exponential explosion of records returned, because SQL will give you all combinations of the joins, so we create a series of queries that will each handle the main form plus one of the linked forms, then we put all the data together into a single result set below
	  
         if($resultOnly) {
	       $masterQueryRes = $xoopsDB->query($masterQuerySQL);
	 } else {
	         $timestamp = str_replace(".","",microtime(true));
		 if(!$sortIsOnMain) {
		    $creatTableSQL = "CREATE TABLE ".DBPRE."formulize_temp_extract_$timestamp ( `mastersort` BIGINT(11), `throwaway_sort_values` BIGINT(11), `entry_id` BIGINT(11), PRIMARY KEY (`mastersort`), INDEX i_entry_id (`entry_id`) ) ENGINE=MyISAM;"; // when the sort is not on the main form, then we are including a special field in the select statement that we sort it by, so that the order is correct, and so it has to have a place to get inserted here
		 } else {
		    $creatTableSQL = "CREATE TABLE ".DBPRE."formulize_temp_extract_$timestamp ( `mastersort` BIGINT(11), `entry_id` BIGINT(11), PRIMARY KEY (`mastersort`), INDEX i_entry_id (`entry_id`) ) ENGINE=MyISAM;";
		 }
		 $createTableRes = $xoopsDB->queryF($creatTableSQL);
        $gatherIdsRes = $xoopsDB->queryF(str_replace("REPLACEWITHTIMESTAMP", $timestamp, $masterQuerySQL));
		 $linkQueryRes = array();
	         if(isset($exportOverrideQueries[2])) {
		    for($i=2;$i<count($exportOverrideQueries);$i++) {
			 $sql = str_replace("REPLACEWITHTIMESTAMP",$timestamp,$exportOverrideQueries[$i]);
			 $linkQueryRes[] = $xoopsDB->query($sql);
		    }
		 } else {
		    // FURTHER OPTIMIZATIONS ARE POSSIBLE HERE...WE COULD NOT INCLUDE THE MAIN FORM AGAIN IN ALL THE SELECTS, THAT WOULD IMPROVE THE PROCESSING TIME A BIT, BUT WE WOULD HAVE TO CAREFULLY REFACTOR MORE OF THE LOOPING CODE BELOW THAT PARSES THE ENTRIES, BECAUSE RIGHT NOW IT'S ASSUMING THE FULL MAIN ENTRY IS PRESENT.  AT LEAST THE MAIN ENTRY ID WOULD NEED TO STILL BE USED, SINCE WE USE THAT TO SYNCH UP ALL THE ENTRIES FROM THE OTHER FORMS.
		    foreach($linkformids as $linkId=>$thisLinkFid) {
			  
			  $linkQuery = "SELECT
   main.entry_id AS main_entry_id, main.creation_uid AS main_creation_uid, main.mod_uid AS main_mod_uid, main.creation_datetime AS main_creation_datetime, main.mod_datetime AS main_mod_datetime, main.*, "
   .$linkSelectIndex[$thisLinkFid].
   ", usertable.email AS main_email, usertable.user_viewemail AS main_user_viewemail FROM "
   .DBPRE."formulize_" . $formObject->getVar('form_handle') . " AS main
   LEFT JOIN " . DBPRE . "users AS usertable ON main.creation_uid=usertable.uid
   LEFT JOIN ".$joinTextTableRef[$thisLinkFid] . $joinTextIndex[$thisLinkFid]."
   INNER JOIN ".DBPRE."formulize_temp_extract_REPLACEWITHTIMESTAMP as sort_and_limit_table ON main.entry_id = sort_and_limit_table.entry_id ";
            if (isset($oneSideFilters[$thisLinkFid]) and is_array($oneSideFilters[$thisLinkFid])) {
                $start = true;
                foreach($oneSideFilters[$thisLinkFid] as $thisOneSideFilter) {
                    if(!$start) {
                        $linkQuery .= " AND ( $thisOneSideFilter ) ";
                    } else {
                        $linkQuery .= " WHERE ( $thisOneSideFilter ) ";
                        $start = false;
                    }
                }
            }
			 $linkQuery .= " ORDER BY sort_and_limit_table.mastersort";
			  $linkQueryRes[] = $xoopsDB->query(str_replace("REPLACEWITHTIMESTAMP",$timestamp,$linkQuery));
			  $GLOBALS['formulize_queryForExport'] .= " -- SEPARATOR FOR EXPORT QUERIES -- ".$linkQuery;
		    }
		 }
	         $dropRes = $xoopsDB->queryF("DROP TABLE ".DBPRE."formulize_temp_extract_$timestamp");
		 
	 }
     } else { 
         $masterQueryRes = $xoopsDB->query($masterQuerySQL);
     }

    if($resultOnly) {
      if($masterQueryRes) {
        if($xoopsDB->getRowsNum($masterQueryRes)>0) {
          return $masterQueryRes;
        } else {
          return false;
        }
      }
    }

     formulize_benchmark("After query");
     
     // need to calculate the derived value metadata
     // 1. figure out which fields in the included forms have derived values
     // 2. setup the metadata for those fields, according to the order they appear
     // -- metadata should be: formhandle (title or framework formhandle), formula, handle (element handle or framework handle)
     // 3. call the derived value function from inside the main loop
     
     $linkFormIdsFilter = "";
     if($frid) {
	  $linkFormIdsFilter = (is_array($linkformids) AND count($linkformids)>0) ? " OR t1.id_form IN (".implode(",",$linkformids).") " : "";
     }
     $sql = "SELECT t1.ele_value, t2.desc_form, t1.ele_handle, t2.id_form FROM ".DBPRE."formulize as t1, ".DBPRE."formulize_id as t2 WHERE t1.ele_type='derived' AND (t1.id_form='$fid' $linkFormIdsFilter ) AND t1.id_form=t2.id_form ORDER BY t1.ele_order";     
     
     $derivedFieldMetadata = array();
     if($res = $xoopsDB->query($sql)) {
          if($xoopsDB->getRowsNum($res)>0) {
               $multipleIndexer = array();
               while($row = $xoopsDB->fetchRow($res)) {
                    $ele_value = unserialize($row[0]); // derived fields have ele_value as an array with only one element (that was done to future proof the data model, so we could add other things to ele_value if necessary)
                    if(!isset($multipleIndexer[$row[1]])) { $multipleIndexer[$row[1]] = 0; }
                    $derivedFieldMetadata[$row[1]][$multipleIndexer[$row[1]]]['formula'] = $ele_value[0]; // use row[1] (the form handle) as the key, so we can eliminate some looping later on
                    $derivedFieldMetadata[$row[1]][$multipleIndexer[$row[1]]]['handle'] = $row[2];
		    $derivedFieldMetadata[$row[1]][$multipleIndexer[$row[1]]]['form_id'] = $row[3];
                    $multipleIndexer[$row[1]]++;
               }
          }
     } else {
          print "Error: could not check to see if there were derived value elements in one or more forms.  SQL:<br>$sql";
     }     

     if(count($linkformids)>1) { // AND $dummy == "never") {
	  
	  // this is a refactoring of the original code that is in the else part of this structure.
	  // it's virtually the same, except for the part that sets the $masterIndexer, since we will need to reuse masterindex positions when parsing subsequent queries, so we don't just increment the $masterIndexer
	  // Also, derived value formulas are processed all at the end, because until we've parsed the last query, we don't have a complete set of data for any record in the masterResults array
	  // once this is proven stable, we should refactor this into a function and have a more unified/common way of parsing query results, but for now we'll keep it split out since we know the old way works intact in its current form and this new one is a little bit experimental
	  
	  // we need to loop through all the query results that were generated above, and gradually build up the same full results array out of them
	  // then we also need to loop through all main entries one more time once we're done building, and set all the derived values
	  // this is done to avoid an exponential explosion of results in the SQL, and instead we only have a linear progression of results to parse

	  $masterResults = array();
	  $masterIndexer = -1;
	  $writtenMains = array();
	  $masterQueryArrayIndex = array();

	  foreach($linkQueryRes as $thisRes) {     
	  
	       // loop through the found data and create the dataset array in "getData" format
	       $prevFieldNotMeta = true;
	       $prevFormAlias = "";
	       $prevMainId = "";

        if($useFidForCurFormId) {
            $curFormId = $fid;
        }
           
           
	       while($masterQueryArray = $xoopsDB->fetchArray($thisRes)) {
		    
		    foreach($masterQueryArray as $field=>$value) {
			 if($field == "entry_id" OR $field == "creation_uid" OR $field == "mod_uid" OR $field == "creation_datetime" OR $field == "mod_datetime" OR $field == "main_email" OR $field == "main_user_viewemail") { continue; } // ignore those plain fields, since we can only work with the ones that are properly aliased to their respective tables.  More details....Must refer to metadata fields by aliases only!  since * is included in SQL syntax, fetch_assoc will return plain column names from all forms with the values from those columns.....Also want to ignore the email fields, since the fact they're prefixed with "main" can throwoff the calculation of which entry we're currently writing
			 if(strstr($field, "creation_uid") OR strstr($field, "creation_datetime") OR strstr($field, "mod_uid") OR strstr($field, "mod_datetime") OR strstr($field, "entry_id")) {
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
        					if($writtenMains[$masterQueryArray['main_entry_id']]) {
        					     $masterIndexer = $masterQueryArrayIndex[$masterQueryArray['main_entry_id']]; // use the master index value for this main entry id if we've already logged it
        					} else {
        					     $masterIndexer = count($masterResults); // use the next available number for the master indexer
        					     $masterQueryArrayIndex[$masterQueryArray['main_entry_id']] = $masterIndexer; // log it so we can reuse it for this entry when it comes up in another query
        					}
    					   $prevMainId = $masterQueryArray['main_entry_id']; // if the current form is a main, then store it's ID for use later when we're on a new form
    				   }
			      }
                  
			      $prevFieldNotMeta = false;
			      // setup handles to use for metadata fields
			      if($curFormAlias == "main") {
				      if($field == "main_creation_uid" OR $field == "main_mod_uid" OR $field == "main_creation_datetime" OR $field == "main_mod_datetime" OR $field == "main_entry_id") {
					      $elementHandle = $fieldNameParts[1] . "_" . $fieldNameParts[2];
				      } 
			      } else {
				      continue; // do not include metadata from the linked forms, or anything else (such as email, etc)
			      }
			 } elseif(!strstr($field, "main_email") AND !strstr($field, "main_user_viewemail")) {
			      // dealing with a regular element field
			      $prevFieldNotMeta = true;
			      $elementHandle = $field;
			 } else { // it's some other field...
			      continue;
			 }               
			 // Check to see if this is a main entry that has already been catalogued, and if so, then skip it
			 if($curFormAlias == "main" AND isset($writtenMains[$masterQueryArray['main_entry_id']])) {
			      continue;
			 } 
			 //print "<br>$curFormAlias - $field: $value<br>"; // debug line
			 formulize_benchmark("preping value...");
			 $valueArray = prepvalues($value, $elementHandle, $masterQueryArray[$curFormAlias . "_entry_id"]); // note...metadata fields must not be in an array for compatibility with the 'display' function...not all values returned will actually be arrays, but if there are multiple values in a cell, then those will be arrays
			 formulize_benchmark("done preping value");
			 $masterResults[$masterIndexer][getFormTitle($curFormId)][$masterQueryArray[$curFormAlias . "_entry_id"]][$elementHandle] = $valueArray;
			 if($elementHandle == "creation_uid" OR $elementHandle == "mod_uid" OR $elementHandle == "creation_datetime" OR $elementHandle == "mod_datetime" OR $elementHandle == "entry_id") {
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
					$masterResults[$masterIndexer][getFormTitle($curFormId)][$masterQueryArray[$curFormAlias . "_entry_id"]]['creator_email'] = $masterQueryArray['main_email'];
				   } else {
					$masterResults[$masterIndexer][getFormTitle($curFormId)][$masterQueryArray[$curFormAlias . "_entry_id"]]['creator_email'] = "";
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
			      $masterResults[$masterIndexer][getFormTitle($curFormId)][$masterQueryArray[$curFormAlias . "_entry_id"]][$old_meta] = $valueArray;
			 }
			 
		    } // end of foreach field loop within a record
	       } // end of main while loop for all records
	  
	  } // end of foreach linked query result
	  
	  if(count($derivedFieldMetadata) > 0 AND $masterIndexer > -1) { // if there is derived value info for this data set and we have started to create values...need to do this one more time for the last value that we would have gathered data for...
	    foreach($masterResults as $masterIndex=>$thisRecord) {
	       $masterResults[$masterIndex] = formulize_calcDerivedColumns($thisRecord, $derivedFieldMetadata, $frid, $fid);
	    }
          }
     
     } else {

	  // loop through the found data and create the dataset array in "getData" format
	  $prevFieldNotMeta = true;
	  $masterIndexer = -1;
	  $writtenMains = array();
	  $prevFormAlias = "";
	  $prevMainId = "";
		      //formulize_benchmark("About to prepare results.");
              
    if($useFidForCurFormId) {
        $curFormId = $fid;
    }
              
	  while($masterQueryArray = $xoopsDB->fetchArray($masterQueryRes)) {
            set_time_limit(120);
	     //formulize_benchmark("Starting to process one entry.");
	       foreach($masterQueryArray as $field=>$value) {
		    //formulize_benchmark("Starting to process one value");
		    if($field == "entry_id" OR $field == "creation_uid" OR $field == "mod_uid" OR $field == "creation_datetime" OR $field == "mod_datetime" OR $field == "main_email" OR $field == "main_user_viewemail") { continue; } // ignore those plain fields, since we can only work with the ones that are properly aliased to their respective tables.  More details....Must refer to metadata fields by aliases only!  since * is included in SQL syntax, fetch_assoc will return plain column names from all forms with the values from those columns.....Also want to ignore the email fields, since the fact they're prefixed with "main" can throwoff the calculation of which entry we're currently writing
		    if(strstr($field, "creation_uid") OR strstr($field, "creation_datetime") OR strstr($field, "mod_uid") OR strstr($field, "mod_datetime") OR strstr($field, "entry_id")) {
			 //formulize_benchmark("Starting to process metadata");
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
				   //formulize_benchmark("Done entry, ready to do derived values.");
							     // now that the entire entry has been processed, do the derived values for it
				   if(count($derivedFieldMetadata) > 0 AND $masterIndexer > -1) { // if there is derived value info for this data set and we have started to create values...
					//print "fid: $fid<br>";
					//print "frid: $frid<br>";
					formulize_benchmark("before doing derived...");
					$masterResults[$masterIndexer] = formulize_calcDerivedColumns($masterResults[$masterIndexer], $derivedFieldMetadata, $frid, $fid);
					formulize_benchmark("after doing derived");
				   }
				   $masterIndexer++; // If this is a new main entry, then increment the masterIndexer, since the masterIndexer is used to uniquely identify each main entry
				   $prevMainId = $masterQueryArray['main_entry_id']; // if the current form is a main, then store it's ID for use later when we're on a new form
			      }
			 }
             
			 $prevFieldNotMeta = false;
			 // setup handles to use for metadata fields
			 if($curFormAlias == "main") {
			      if($field == "main_creation_uid" OR $field == "main_mod_uid" OR $field == "main_creation_datetime" OR $field == "main_mod_datetime" OR $field == "main_entry_id") {
				   $elementHandle = $fieldNameParts[1] . "_" . $fieldNameParts[2];
			      } else {
				   continue; // do not include main_entry_id as a value in the array...though it should not be in here anyway now that we're checking with strstr for metadata field names above
			      }
			 } else {
			      continue; // do not include metadata from the linked forms, or anything else (such as email, etc)
			 }
		    } elseif(!strstr($field, "main_email") AND !strstr($field, "main_user_viewemail")) {
										     // dealing with a regular element field
			 $prevFieldNotMeta = true;
			 $elementHandle = $field;
		    } else { // it's some other field
        			 continue;
		    }               
		    // Check to see if this is a main entry that has already been catalogued, and if so, then skip it
		    if($curFormAlias == "main" AND isset($writtenMains[$masterQueryArray['main_entry_id']])) {
			 continue;
		    } 
     
		    //print "<br>$curFormAlias - $field: $value<br>"; // debug line
		    formulize_benchmark("preping value...");
		    $valueArray = prepvalues($value, $elementHandle, $masterQueryArray[$curFormAlias . "_entry_id"]); // note...metadata fields must not be in an array for compatibility with the 'display' function...not all values returned will actually be arrays, but if there are multiple values in a cell, then those will be arrays
		    formulize_benchmark("done preping value");
		    $masterResults[$masterIndexer][getFormTitle($curFormId)][$masterQueryArray[$curFormAlias . "_entry_id"]][$elementHandle] = $valueArray;
		    if($elementHandle == "creation_uid" OR $elementHandle == "mod_uid" OR $elementHandle == "creation_datetime" OR $elementHandle == "mod_datetime" OR $elementHandle == "entry_id") {
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
				   $masterResults[$masterIndexer][getFormTitle($curFormId)][$masterQueryArray[$curFormAlias . "_entry_id"]]['creator_email'] = $masterQueryArray['main_email'];
			      } else {
				   $masterResults[$masterIndexer][getFormTitle($curFormId)][$masterQueryArray[$curFormAlias . "_entry_id"]]['creator_email'] = "";
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
			 $masterResults[$masterIndexer][getFormTitle($curFormId)][$masterQueryArray[$curFormAlias . "_entry_id"]][$old_meta] = $valueArray;
		    }
		    
	       } // end of foreach field loop within a record
	  } // end of main while loop for all records
	  
	  if(count($derivedFieldMetadata) > 0 AND $masterIndexer > -1) { // if there is derived value info for this data set and we have started to create values...need to do this one more time for the last value that we would have gathered data for...
	       //print "fid: $fid<br>";
	       //print "frid: $frid<br>";
	       $masterResults[$masterIndexer] = formulize_calcDerivedColumns($masterResults[$masterIndexer], $derivedFieldMetadata, $frid, $fid);
	  }	  
	  
     } // end if if there's more the 1 linked fid
     
     return $masterResults;

} // end of dataExtraction function

// this function returns the form id when given the form name
function formulize_getFormIdFromName($nameHandle) {
     static $cachedFormIds = array();
     if(!isset($cachedFormIds[$nameHandle])) {
          $formIdData = go("SELECT id_form FROM ".DBPRE."formulize_id WHERE desc_form = '".formulize_db_escape($nameHandle)."'");
          $cachedFormIds[$nameHandle] = $formIdData[0]['id_form'];
     }
     return $cachedFormIds[$nameHandle];
}

// THIS FUNCTION BREAKS DOWN THE FILTER STRING INTO ITS COMPONENTS.  TAKES EVERYTHING UP TO THE TOP LEVEL ARRAY SYNTAX.
// $linkfids is the linked fids in order that they appear in the SQL query
function formulize_parseFilter($filtertemp, $andor, $linkfids, $fid, $frid) {
     global $xoopsDB;
     if($filtertemp == "") { return array(0=>array(), "", ""); }
     
     $formFieldFilterMap = array();
     $whereClause = "";
     $orderByClause = "";
     $otherPerGroupFilterJoins = "";
     $otherPerGroupFilterWhereClause = "";
     
     $oneSideFilters = array(); // we need to capture each filter individually, just in case we need to apply them individually to each part of the query for calculations.  Filters for calculations will not work right if the combination of filter terms is excessively complex, ie: includes OR'd terms across different forms in a framework, certain other complicated types of bracketing
          
     if(!is_array($filtertemp)) {
          $filter = array(0=>array(0=>$andor, 1=>$filtertemp));
     } else {
          $filter = $filtertemp;
     }
     
     $form_handler = xoops_getmodulehandler('forms', 'formulize');
     
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
               
							 // FINAL NOTE ABOUT SLASHES...Oct 19 2006...patch 22 corrects this slash/magic quote mess.  However, to ensure compatibility with existing Pageworks applications, we are continuing to strip out all slashes in the filterparts[1], the filter strings that are passed in, and then we apply HTML special chars to the filter so that it can match up with the contents of the DB.  Only challenge is that extract.php is meant to be standalone, but we have to refer to the text sanitizer class in XOOPS in order to do the HTML special chars thing correctly.

               $ifParts[1] = str_replace("\\", "", $ifParts[1]);
               $ifParts[1] = $myts->htmlSpecialChars($ifParts[1]);
               
               // convert legacy metadata terms to new terms
               $ifParts[0] = $ifParts[0] == "uid" ? "creation_uid" : $ifParts[0];
               $ifParts[0] = $ifParts[0] == "proxyid" ? "mod_uid" : $ifParts[0];
               $ifParts[0] = $ifParts[0] == "creation_date" ? "creation_datetime" : $ifParts[0];
               $ifParts[0] = $ifParts[0] == "mod_date" ? "mod_datetime" : $ifParts[0];
							 
							 // set order by clause for newest operator -- assume only one newest operator per query!
							 // does this need to be based on entry_id and not use $queryElement (which is based on ifParts[0]) ??
							 if(strstr($ifParts[2], "newest")) {
										if($ifParts[0] == "creation_datetime" OR $ifParts[0] == "mod_datetime") {
												 $queryElement = $ifParts[0];
										} else {
												 list($ifParts[0], $formFieldFilterMap, $mappedForm, $element_id, $elementPrefix, $queryElement) = prepareElementMetaData($frid, $fid, $linkfids, $ifParts[0], $formFieldFilterMap);
										}
										$orderByClause = " ORDER BY $queryElement DESC LIMIT 0," . substr($ifParts[2], 6);
										continue;
							 }
							 
               if($numIndivFilters > 0) {
                    $whereClause .= $filterParts[0]; // apply local andor setting
               }
               
               $newWhereClause = ""; // tracks just the current iteration of this loop, so we can capture this filter and add it to the record of filters for this form lower down
               
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
               
               $formFieldFilterMap['creator_email'] = false; // can be set to true lower down, need to initalize it properly here
               
               if(is_numeric($ifParts[0]) AND $ifParts[0] == $indivFilter) {
                    // if this is a numeric value, then we must treat it specially
                    $newWhereClause = "main.entry_id=" . $ifParts[0];
                    $mappedForm = $fid;
               } elseif($ifParts[0] == "creation_uid" OR $ifParts[0]  == "mod_uid" OR $ifParts[0]  == "creation_datetime" OR $ifParts[0]  == "mod_datetime") {
                    // if this is a user id field, then treat it specially 
                    if(($ifParts[0] == "creation_uid" OR $ifParts[0] == "mod_uid") AND !is_numeric($ifParts[1])) {
                         // subquery the user table for the username or full name
                         $ifParts[1] = "(SELECT uid FROM " . DBPRE . "users WHERE uname " . $operator . $quotes . $likebits . formulize_db_escape($ifParts[1]) . $likebits . $quotes . " OR name " . $operator . $quotes . $likebits . formulize_db_escape($ifParts[1]) . $likebits . $quotes . ")";
                         $quotes = "";
                         $operator = " = ANY ";
                         $likebits = "";
										} elseif(($ifParts[0] == "creation_uid" OR $ifParts[0] == "mod_uid") AND is_numeric($ifParts[1])) { // numeric uid query, so make operator =
												 $operator = " = ";
												 $quotes = "";
												 $likebits = "";
												 $ifParts[1] = formulize_db_escape($ifParts[1]);
                    } else { // need to put mysql_real_escape_string around $ifParts[1] only when it's a date field, since that escaping requirement has been handled already in the subquery for uid filters
                         $ifParts[1] = formulize_db_escape($ifParts[1]);
												 
                    }
                    $newWhereClause = "main.".$ifParts[0]  . $operator . $quotes . $likebits . $ifParts[1] . $likebits . $quotes;
                    $mappedForm = $fid;
               } elseif($ifParts[0] == "creator_email") {
                    $formFieldFilterMap['creator_email'] = true;
                    $newWhereClause = "usertable.email" . $operator . $quotes . $likebits . formulize_db_escape($ifParts[1]) . $likebits . $quotes;
                    $mappedForm = $fid;
	       } elseif($ifParts[0] == "entry_id") {
		    $formFieldFilterMap['entry_id'] = true;
		    $newWhereClause = "main.entry_id" . $operator . $quotes . $likebits . formulize_db_escape($ifParts[1]) . $likebits . $quotes;
		    $mappedForm = $fid;		    
               } else {
                    
                    // do non-metadata queries
                    
										list($ifParts[0], $formFieldFilterMap, $mappedForm, $element_id, $elementPrefix, $queryElement) = prepareElementMetaData($frid, $fid, $linkfids, $ifParts[0], $formFieldFilterMap);
                    
                    // set query term for yes/no questions
                    if($formFieldFilterMap[$mappedForm][$element_id]['isyn']) {
                         if(strstr(strtoupper(_formulize_TEMP_QYES), strtoupper($ifParts[1])) OR strtoupper($ifParts[1]) == "YES") { // since we're matching based on even a single character match between the query and the yes/no language constants, if the current language has the same letters or letter combinations in yes and no, then sometimes only Yes may be searched for
                              $ifParts[1] = 1;
                         } elseif(strstr(strtoupper(_formulize_TEMP_QNO), strtoupper($ifParts[1])) OR strtoupper($ifParts[1]) == "NO") {
                              $ifParts[1] = 2;
                         } else {
                              $ifParts[1] = "";
                         }
                    }
                    
                    // build the where clause....
                    
                    // handle 'other' boxes
                    // instead of doing a subquery, this could probably be redone similarly to creator_email and then we would have the "other" value in the raw query result, and then the process in prepValues would not need to requery the other table
                    if($formFieldFilterMap[$mappedForm][$element_id]['hasother']) {
                         $subquery = "(SELECT id_req FROM " . DBPRE . "formulize_other WHERE ele_id=" . intval($element_id) . " AND other_text " . $operator . $quotes . $likebits . formulize_db_escape($ifParts[1]) . $likebits . $quotes . ")";
                         $newWhereClause = "(($elementPrefix.entry_id = ANY $subquery)OR($queryElement " . $operator . $quotes . $likebits . formulize_db_escape($ifParts[1]) . $likebits . $quotes."))"; // need to look in the other box and the main field, and return values that match in either case
                    // handle linked selectboxes
                    } elseif($sourceMeta = $formFieldFilterMap[$mappedForm][$element_id]['islinked']) {
			 
                        // check if user is searching for blank values, and if so, then query this element directly, rather than looking in the source
                        if($ifParts[1]==='' OR $operator == ' IS NULL ' OR $operator == ' IS NOT NULL ') {
                             $newWhereClause = "$queryElement " . $operator . $quotes . $likebits . formulize_db_escape($ifParts[1]) . $likebits . $quotes;
                        } else {
                             
                             // need to check if an alternative value field has been defined for use in lists or data sets and search on that field instead 
                             if(isset($formFieldFilterMap[$mappedForm][$element_id]['ele_value'][10]) AND $formFieldFilterMap[$mappedForm][$element_id]['ele_value'][10][0] != "none") {
                              list($sourceMeta[1]) = convertElementIdsToElementHandles(array($formFieldFilterMap[$mappedForm][$element_id]['ele_value'][10]), $sourceMeta[0]); // ele_value 10 is the alternate field to use for datasets and in lists
                             }
           
                           $sourceFormObject = $form_handler->get($sourceMeta[0]);
                           if($ifParts[1] == "PERGROUPFILTER") {
                               // invoke the per group filter that applies to the form that we are pointing to...if XOOPS is in effect (ie: we're not included directly in other code as per Formulize 1)
                               global $xoopsDB;
                               if($xoopsDB) {
                                   $form_handler = xoops_getmodulehandler('forms', 'formulize');
                                   $otherpgfCount = count($otherPerGroupFilterJoins) + 1;
                                   $otherPerGroupFilterWhereClause[] = $form_handler->getPerGroupFilterWhereClause($sourceMeta[0], "otherpgf".$otherpgfCount);
                                   $tempOtherPGFJoin = " LEFT JOIN ".DBPRE."formulize_".$sourceFormObject->getVar('form_handle')." AS otherpgf".$otherpgfCount." ON ";
                                   $tempOtherPGFJoin .= " otherpgf".$otherpgfCount.".entry_id IN (TRIM(',' FROM $queryElement)) ";
                                   $otherPerGroupFilterJoins[] = $tempOtherPGFJoin;
                               }
                               $newWhereClause = "1";
                           } else {
                               // Neal's suggestion:  use EXISTS...other forms of subquery using field IN subquery or subquery LIKE field,
                               //  and a CONCAT in the subquery, failed in various conditions.  IN did not work with multiple selection boxes
                               //  and LIKE did not work with search terms too general to return only one match in the source form.
                               // Exists works in all cases.  :-)
                               if (is_array($sourceMeta[1])) {
                                   // when searching a linked box which presents multiple columns, concat the columns to search
                                   $search_column = convertElementIdsToElementHandles($sourceMeta[1], $sourceMeta[0]);
				   $search_column = "CONCAT_WS('', source.`".implode("`, source.`", $search_column)."`)";
                               } else {
                                   $search_column = "source.`" . $sourceMeta[1] . "`";
                               }
                       $queryElementMetaData = formulize_getElementMetaData($ifParts[0], true);
                               $ele_value = $queryElementMetaData['ele_value'];
                               if ($ele_value[0] > 1 AND $ele_value[1]) { // if the number of rows is greater than 1, and the element supports multiple selections
                                    $newWhereClause = " EXISTS (SELECT 1 FROM " . DBPRE . "formulize_" . $sourceFormObject->getVar('form_handle') . " AS source WHERE $queryElement LIKE CONCAT('%,',source.entry_id,',%') AND " . $search_column . $operator . $quotes . $likebits . formulize_db_escape($ifParts[1]) . $likebits . $quotes . ")";
                               } else {
                                    $newWhereClause = " EXISTS (SELECT 1 FROM " . DBPRE . "formulize_" . $sourceFormObject->getVar('form_handle') . " AS source WHERE $queryElement = source.entry_id AND " . $search_column . $operator . $quotes . $likebits . formulize_db_escape($ifParts[1]) . $likebits . $quotes . ")";
                               }
                           }
                       }
                    // usernames/fullnames boxes
                    } elseif($listtype = $formFieldFilterMap[$mappedForm][$element_id]['isnamelist'] AND $ifParts[1] !== "") {
                         if(!is_numeric($ifParts[1])) {
                              $preSearch = "SELECT uid FROM " . DBPRE . "users WHERE uname " . $operator . $quotes . $likebits . formulize_db_escape($ifParts[1]) . $likebits . $quotes . " OR name " . $operator . $quotes . $likebits . formulize_db_escape($ifParts[1]) . $likebits . $quotes;  // search name and uname, since often name might be empty these days
                         } else {
                              $preSearch = "SELECT uid FROM " . DBPRE . "users WHERE uid ".$operator.$quotes.$likebits.$ifParts[1].$likebits.$quotes;
                         }
                         $preSearchResult = $xoopsDB->query($preSearch);
                         if($xoopsDB->getRowsNum($preSearchResult)>0) {
															$nameSearchStart = true;
                              while($preSearchArray = $xoopsDB->fetchArray($preSearchResult)) {
																	 if(!$nameSearchStart) {
																				$newWhereClause .= "OR";
																	 } else {
                                        $newWhereClause = " (";
																				$nameSearchStart = false;
																	 }
                                   if(formulize_selectboxAllowsMultipleSelections($element_id)) {
                                        $newWhereClause .= " (($queryElement LIKE '%*=+*:" . $preSearchArray['uid'] . "*=+*:%' OR $queryElement LIKE '%*=+*:" . $preSearchArray['uid'] . "') OR $queryElement = " . $preSearchArray['uid'] . ") "; // could this be further optimized to remove the = condition, and only use the LIKEs?  We need to check if a multiselection-capable box still uses the delimiter string when only one value is selected...I think it does.
                                   } else {
                                        $newWhereClause .= " $queryElement = " . $preSearchArray['uid'] . " ";
                                   }
                              }
                              if(!$nameSearchStart) {
                                   $newWhereClause .= ") ";
                              }
                         } else {
                              $newWhereClause = "main.entry_id<0"; // no matches, so result set should be empty, so set a where clause that will return zero results
                              
                         }
                    // regular whereclause
                    } else {
			 // check if there's any conversion necessary from what the user typed into a special value that will work in the database
			 $searchTerm = $ifParts[1];
			 $searchTermToUse = "";
			 if(file_exists(XOOPS_ROOT_PATH."/modules/formulize/class/".$formFieldFilterMap[$mappedForm][$element_id]['ele_type']."Element.php")) {
			      $customTypeHandler = xoops_getmodulehandler($formFieldFilterMap[$mappedForm][$element_id]['ele_type']."Element", 'formulize');
			      if(trim($operator) == "LIKE" OR trim($operator) == "NOT LIKE") {
				   $searchTerm = $customTypeHandler->prepareLiteralTextForDB($ifParts[1], $customTypeHandler->get($element_id), true); // true means partial matching is in effect
				   if(is_array($searchTerm) AND count($searchTerm) > 1) { // method has returned a list of complete values in the database that match the term, so we need to construct an OR series to match this value in the database
					$searchTermToUse = " (";
					$start = true;
					foreach($searchTerm as $thisTerm) {
					     if(!$start) { $searchTermToUse .= " OR "; }
					     $searchTermToUse .= "$queryElement " . $operator . $quotes . $likebits . $thisTerm . $likebits . $quotes;
					     $start = false;
					}
					$searchTermToUse .= ") ";
				   } elseif(is_array($searchTerm)) {
					$searchTerm = $searchTerm[0];
				   }
			      } else {
				   $searchTerm = $customTypeHandler->prepareLiteralTextForDB($ifParts[1], $customTypeHandler->get($element_id));
			      }
			 }
			 if($searchTerm === $ifParts[1]) {
			      // no change, so let's escape it, otherwise the prepareLiteralTextForDB method should have returned a safe value
			      $searchTerm = formulize_db_escape($ifParts[1]);	
			 }
			 if($searchTerm !== false) {
			      if($searchTermToUse) { // set as an override value in certain cases above
				   $newWhereClause = $searchTermToUse;
			      } else {
          			   // could/should put better handling in here of multiple value boxes, so = operators actually only look for matches within the individual values??  Is that possible?
          			   $newWhereClause = "$queryElement " . $operator . $quotes . $likebits . $searchTerm . $likebits . $quotes;
			      }
			 } else {
			      $newWhereClause = "($queryElement = 1 AND $queryElement = 2)"; // impossible condition, since no values were found that match the user's states search terms (false or nothing must have been passed back from the prepareLiteralTextForDB method)
			 }
                    }
               }

               $whereClause .= $newWhereClause;
               if(count($oneSideFilters[$mappedForm][strtolower(trim($filterParts[0]))]) == 0) {
                    $oneSideFilters[$mappedForm][strtolower(trim($filterParts[0]))] = " $newWhereClause ";   // don't add the local andor on the first term for a form  
               } else {
                    $oneSideFilters[$mappedForm][strtolower(trim($filterParts[0]))] .= " ". $filterParts[0] . " $newWhereClause ";
               }
               
               $whereClause .= ")";
               $numIndivFilters++;
          }
          
					if($whereClause == "(") { // if no contents for the whereclause where generated...make a fake contents (should only happen if the only filter term passed in is a newest operator)
							 $whereClause .= "main.entry_id>0";
					}
          $whereClause .= ")";
          $numSeachExps++;
     }

    $otherPerGroupFilterJoins = is_array($otherPerGroupFilterJoins) ? implode(" ", $otherPerGroupFilterJoins) : "";
    $otherPerGroupFilterWhereClause = is_array($otherPerGroupFilterWhereClause) ? implode(" ", $otherPerGroupFilterWhereClause) : "";
    return array(0=>$formFieldFilterMap, 1=>$whereClause, 2=>$orderByClause, 3=>$oneSideFilters, 4=>$otherPerGroupFilterJoins, 5=>$otherPerGroupFilterWhereClause);
}


// THIS FUNCTION TAKES INPUTS ABOUT AND ELEMENT, AND RETURNS A SET OF INFORMATION THAT IS NECESSARY WHEN BUILDING VARIOUS PARTS OF THE WHERE CLAUSE
function prepareElementMetaData($frid, $fid, $linkfids, $ifPartsZero, $formFieldFilterMap){
		 // first convert any handles to element Handles, and/or get the element id if necessary...element id is necessary for creating the formfieldfiltermap, since that function was written the first time we tried to do this, when there were no element handles in the mix
		 if($frid AND !is_numeric($ifPartsZero)) {
					$ifPartsZero = dealWithDeprecatedFrameworkHandles($ifPartsZero, $frid); // will convert a framework handle if necessary
					$element_id = formulize_getIdFromElementHandle($ifPartsZero);
		 } elseif(is_numeric($ifPartsZero)) { // using a numeric element id
					$element_id = $ifPartsZero;
					$ifPartsZero = formulize_getElementHandleFromID($ifPartsZero);
		 } else { // no framework, element handle being used...so we have to derive the element id
					$element_id = formulize_getIDFromElementHandle($ifPartsZero);
		 }
	 
		 // identify the form that the element is associated with and put it in the map
		 list($formFieldFilterMap, $mappedForm) = formulize_mapFormFieldFilter($element_id, $formFieldFilterMap);
		 /*print "map: <br>";
		 print_r($formFieldFilterMap);
		 print "<br>Mappedform: $mappedForm<br>";
		 print "<br>fid: $fid";*/
		 $elementPrefix = $mappedForm == $fid ? "main" : "f" . array_search($mappedForm, $linkfids);
		 
		 // check if its encrypted or not, and setup the proper field reference
		 $queryElementMetaData = formulize_getElementMetaData($ifPartsZero, true);
		 
		 // add ` ` around ifParts[0]...
		 $ifPartsZero = "`".$ifPartsZero."`";
		 
		 if($queryElementMetaData['ele_encrypt']) {
					$queryElement = "AES_DECRYPT($elementPrefix.".$ifPartsZero.", '".getAESPassword()."')";
		 } else {
					$queryElement = "$elementPrefix." . $ifPartsZero;
		 }
		 
		 // return in this order:  $ifParts[0], $formFieldFilterMap, $mappedForm, $element_id, $elementPrefix, $queryElement
		 $to_return = array();
		 $to_return[] = $ifPartsZero;
		 $to_return[] = $formFieldFilterMap;
		 $to_return[] = $mappedForm;
		 $to_return[] = $element_id;
		 $to_return[] = $elementPrefix;
		 $to_return[] = $queryElement;
		 return $to_return;
		 
}

// THIS FUNCTION TAKES AN ELEMENT AND COMPILES THE FORM, ELEMENT MAP, NECESSARY FOR KNOWING ALL WE NEED TO KNOW ABOUT THE ELEMENT
// needs work...?  if we can pass in the element_id, it should be OK
function formulize_mapFormFieldFilter($element_id, $formFieldFilterMap) {
     global $xoopsDB;
     $foundForm = false;
     foreach($formFieldFilterMap as $fid=>$formData) { // check if the element has already been mapped and if so, what is the form
          if(isset($formData[$element_id])) {
               $foundForm = $fid;
          }
     }
     if(!$foundForm) {
          //$sql = "SELECT id_form, ele_value, ele_type FROM " . DBPRE . "formulize WHERE ele_id = " . intval($element_id);
          //$res = $xoopsDB->query($sql);
          //$array = $xoopsDB->fetchArray($res);
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
	  $formFieldFilterMap[$array['id_form']][$element_id]['ele_value'] = $ele_value; // just to be on the safe side, send the entire ele_value as well in case we need something from it
	  $formFieldFilterMap[$array['id_form']][$element_id]['ele_type'] = $array['ele_type']; 
     }
     return array(0=>$formFieldFilterMap, 1=>$foundForm);
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
     global $xoopsDB;
     static $cachedElements = array();
     $cacheType = $isHandle ? 'handles' : 'ids';
     $elementOrHandle = str_replace("`","",$elementOrHandle);
     if(!isset($cachedElements[$cacheType][$elementOrHandle])) {
          if($fid) {
               $whereClause = "id_form=".intval($fid);
          } else {
               $whereClause = $isHandle ? "ele_handle = '".formulize_db_escape($elementOrHandle)."'" : "ele_id = ".intval($elementOrHandle);
          }
          $elementValueQ = "SELECT ele_value, ele_type, ele_id, ele_handle, id_form, ele_uitext, ele_caption, ele_colhead, ele_encrypt FROM " . DBPRE . "formulize WHERE $whereClause";
          $evqRes = $xoopsDB->query($elementValueQ);
          while($evqRow = $xoopsDB->fetchArray($evqRes)) {
               $cachedElements['handles'][$evqRow['ele_handle']] = $evqRow; // cached the element according to handle and id, so we don't repeat the same query later just because we're asking for info about the same element in a different way
               $cachedElements['ids'][$evqRow['ele_id']] = $evqRow;
          }
     }
     if(!$fid) {
          return $cachedElements[$cacheType][$elementOrHandle];
     }
}


// THIS FUNCTION LOOPS THROUGH AN ENTRY AND ADDS IN THE DERIVED VALUES IN ANY DERIVED COLUMNS
// Odd results may occur when a derived column is inside a subform in a framework!
// Derived values should always be in the mainform only?
function formulize_calcDerivedColumns($entry, $metadata, $relationship_id, $form_id) {
    global $xoopsDB;
    static $parsedFormulas = array();
    include_once XOOPS_ROOT_PATH . "/modules/formulize/class/data.php";
    foreach ($entry as $formHandle => $record) {
        $data_handler = new formulizeDataHandler(formulize_getFormIdFromName($formHandle));
        $formHandle = htmlspecialchars_decode($formHandle, ENT_QUOTES);
        if (isset($metadata[$formHandle])) {
            // if there are derived value formulas for this form
            if (!isset($parsedFormulas[$formHandle])) {
                formulize_includeDerivedValueFormulas($metadata[$formHandle], $formHandle, $relationship_id, $form_id);
                $parsedFormulas[$formHandle] = true;
            }
            foreach ($record as $primary_entry_id => $elements) {
                $dataToWrite = array();
                foreach ($metadata[$formHandle] as $formulaNumber => $thisMetaData) {
                    // if there's nothing already in the DB, then derive it, unless we're being asked specifically to update the derived values, which happens during a save operation.  In that case, always do a derivation regardless of what's in the DB.
                    if ((isset($GLOBALS['formulize_forceDerivedValueUpdate'])) AND !isset($GLOBALS['formulize_doingExport'])) {
                        $functionName = "derivedValueFormula_".str_replace(array(" ", "-", "/", "'", "`", "\\", ".", "’", ",", ")", "(", "[", "]"), "_", $formHandle)."_".$formulaNumber;
                        // want to turn off the derived value update flag for the actual processing of a value, since the function might have a getData call in it!!
                        $resetDerivedValueFlag = false;
                        if (isset($GLOBALS['formulize_forceDerivedValueUpdate'])) {
                            unset($GLOBALS['formulize_forceDerivedValueUpdate']);
                            $resetDerivedValueFlag = true;
                        }
                        $derivedValue = $functionName($entry, $form_id, $primary_entry_id, $relationship_id);
                        if ($resetDerivedValueFlag) {
                            $GLOBALS['formulize_forceDerivedValueUpdate'] = true;
                        }
                        // if the new value is the same as the previous one, then skip updating and saving
                        if ($derivedValue != $entry[$formHandle][$primary_entry_id][$thisMetaData['handle']][0]) {
                            $entry[$formHandle][$primary_entry_id][$thisMetaData['handle']][0] = $derivedValue;
                            if ($xoopsDB) {
                                // save value for writing to database if XOOPS is active
                                $elementID = formulize_getIdFromElementHandle($thisMetaData['handle']);
                                $dataToWrite[$elementID] = $derivedValue;
                            }
                        }
                    }
                }
                if ($xoopsDB and count($dataToWrite) > 0) {
                    // false for no proxy user, true to force the update even on get requests, false is do not update the metadata (modification user)
                    $data_handler->writeEntry($primary_entry_id, $dataToWrite, false, true, false);
                }
            }
        }
    }
    return $entry;
}


function formulize_includeDerivedValueFormulas($metadata, $formHandle, $frid, $fid) {
    $functionsToWrite = "";
    // loop through the formulas, process them, and write them to the file
    foreach($metadata as $formulaNumber => $thisMetaData) {
        $formula = $thisMetaData['formula'];
        $quotePos = 0;
        while ((strlen($formula) > $quotePos + 1) and ($quotePos = strpos($formula, "\"", $quotePos + 1))) {
            $endQuotePos = strpos($formula, "\"", $quotePos + 1);
            $term = substr($formula, $quotePos, $endQuotePos - $quotePos+1);
            if(!is_numeric($term)) {
                list($newterm, $termFid) = formulize_convertCapOrColHeadToHandle($frid, $fid, $term);
                if($newterm != "{nonefound}") {
                    if($frid AND $termFid == $thisMetaData['form_id'] AND $thisMetaData['form_id'] != $fid) {
                        // need to pass in a "local id" since we want the value of this field in this particular entry,
                        //  not in the entire framework.  If a user wants all the values for this field from the other
                        //  entries in the framework, they will have to use the display function manually in the derived value formula.
                        $replacement = "display(\$entry, '$newterm', '', \$entry_id)";
                        $numberOfChars = 34; // 34 is the number of extra characters besides the term
                    } else {
                        $replacement = "display(\$entry, '$newterm')";
                        $numberOfChars = 19; // 19 is the length of the extra characters in the display function
                    }
                    $quotePos = $quotePos + $numberOfChars + strlen($newterm);
                    $formula = str_replace($term, $replacement, $formula);
                } else {
                    $quotePos = $quotePos + strlen($term) + 2; // move ahead the length of the found term, plus its quotes
                }
            }
        }
        $addSemiColons = strstr($formula, ";") ? false : true; // only add if we found none in the formula.
        if($addSemiColons) {
            $formulaLines = explode("\n", $formula);    // \n may be a linux specific character and other OSs may require a different split
            foreach($formulaLines as $formula_id => $thisLine) {
                $formulaLines[$formula_id] .= ";";  // add missing semicolons
            }
            $formula = implode("\n", $formulaLines);
        }
        $functionsToWrite .= "function derivedValueFormula_".
            str_replace(array(" ", "-", "/", "'", "`", "\\", ".", "’", ",", ")", "(", "[", "]"), "_", $formHandle).
            "_".$formulaNumber."(\$entry, \$form_id, \$entry_id, \$relationship_id) {\n$formula\nreturn \$value;\n}\n\n";
    }
    eval($functionsToWrite);
}

// THIS FUNCTION TAKES A STRING OF TEXT (CAPTION OR COLHEAD) AND DERIVES THE NECESSARY HANDLE OR ELEMENT ID FROM IT
// use a static array to cache results
function formulize_convertCapOrColHeadToHandle($frid, $fid, $term) {
     // first search the $fid, and then if we don't find anything, search the other forms in the $frid
     // check first for a match in the colhead field, then in the caption field
     // once a match is found return the handle
     
		 global $xoopsDB; // just used to check if XOOPS is in effect or not (in which case extract.php is being included directly)
     static $results_array = array();
     static $framework_results = array();
		 static $formNames = array();
     $handle = "";
     $term = trim($term, "\"");
     
		 if(strstr($term, "\$formName") AND $xoopsDB) { 		 // setup the name of the form and replace that value in the term, only when $xoopsDB is in effect, ie: full XOOPS stack
					if(!isset($formNames[$fid])) {
					  $form_handler = xoops_getmodulehandler('forms', 'formulize');
					  $formObject = $form_handler->get($fid);
						$formNames[$fid] = $formObject->getVar('title');
					}
					$term = str_replace("\$formName", $formNames[$fid], $term);
		 }
		 
     if($term == "uid" OR $term == "proxyid" OR $term == "creation_date" OR $term == "mod_date" OR $term == "creator_email" OR $term == "creation_uid" OR $term == "mod_uid" OR $term == "creation_datetime" OR $term == "mod_datetime") {
        return array($term, $fid);
     }
     
     if(!$frid) {
          $formList[] = $fid; // mimic what the result of the framework query below would be...
     } else {
          
					list($termAfterDeal, $dealFid) = dealWithDeprecatedFrameworkHandles($term, $frid, true); // true will cause it to return the form id as well when you're getting only asking for a single handle back.
					if($termAfterDeal != $term) { // if the terms was found and converted to an element handle, then return that.
               return array($termAfterDeal, $dealFid);
          }
					
          if(isset($framework_results[$frid])) {
               $formList = $framework_results[$frid];
          } else {
               $framework_handler = xoops_getmodulehandler('frameworks', 'formulize');
							 $frameworkObject = $framework_handler->get($frid);
							 $formList = $frameworkObject->getVar('fids');
							 $framework_results[$frid] = $formList;
          }
     }
     foreach($formList as $form_id) {
          if(isset($results_array[$form_id][$term][$frid])) { return $results_array[$form_id][$term][$frid]; }
          
          // first check if this is a handle
					//print "hq: SELECT ele_handle FROM " . DBPRE . "formulize WHERE id_form = " . $form_id . " AND ele_handle = \"".formulize_db_escape($term)."\"";
          $handle_query = go("SELECT ele_handle FROM " . DBPRE . "formulize WHERE id_form = " . $form_id . " AND ele_handle = \"".formulize_db_escape($term)."\"");
          if(count($handle_query) > 0) { // if this is a valid handle, then use it
							 $handle = $term;
          } else {
							 //print "chq: SELECT ele_id, ele_handle FROM " . DBPRE . "formulize WHERE id_form = " . $form_id . " AND ele_colhead = \"" . formulize_db_escape($term) . "\"";
               $colhead_query = go("SELECT ele_id, ele_handle FROM " . DBPRE . "formulize WHERE id_form = " . $form_id . " AND (ele_colhead = \"" . formulize_db_escape($term) . "\" OR ele_colhead LIKE '%]".formulize_db_escape($term)."[/%')");
               if(count($colhead_query) > 0) {
										$handle = $colhead_query[0]['ele_handle'];
               } else {
										//print "capq: SELECT ele_id, ele_handle FROM " . DBPRE . "formulize WHERE id_form = " . $form_id . " AND ele_caption = \"" . formulize_db_escape($term) . "\"";
                    $caption_query = go("SELECT ele_id, ele_handle FROM " . DBPRE . "formulize WHERE id_form = " . $form_id . " AND (ele_caption = \"" . formulize_db_escape($term) . "\" OR ele_caption LIKE '%]".formulize_db_escape($term)."[/%')");
                    if(count($caption_query) > 0 ) {
												 $handle = $caption_query[0]['ele_handle'];
                    }
               }
          }
          if($handle) {
               $results_array[$form_id][$term][$frid] = array($handle, $form_id);
               break;
          }     
     }
     if(!$handle) {
	  $handle = "{nonefound}";
	  $form_id = 0;
     }
     return array($handle, $form_id);
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

    $GLOBALS['formulize_queryForExport'] = "USETABLEFORM -- $tablename -- $formname -- $fid -- $filter -- $andor -- $limitStart -- $limitSize -- $sortField -- $sortOrder";

     global $xoopsDB;

     // 2. parse the filter
     // 3. construct the where clause based on the filter and andor
     // 4. do the query
     // 5. loop through results to package them up as getdata style data
     // 6. return them

     // setup a translation table for the formulize records of the fields, so we can use that lower down in several places
     $sql = "SELECT ele_id, ele_caption FROM ".DBPRE."formulize WHERE id_form=".intval($fid);
     $res = $xoopsDB->query($sql);
     $elementsById = array();
     $elementsByCaption = array();
     $elementsByField = array();
     while($array = $xoopsDB->fetchArray($res)) {
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
     $basesql = "SELECT * FROM $tablename $whereClause ";
		 $sql = $basesql;
		 if($sortField) {
					$sql .= " ORDER BY `".$elementsById[$sortField]['field']."` $sortOrder ";
		 }
		 if($limitSize) {
					$sql .= " LIMIT $limitStart,$limitSize ";
		 }
     //print "<br>$sql<br>";
     $res = $xoopsDB->query($sql);
     $result = array();
     $indexer = 0;
     // result syntax is:
     // [id][title of form][primary id -- meaningless in tableforms, until we need to edit entries][formulize element id][value id]
     // package up data in the format we need it
     while($array = $xoopsDB->fetchArray($res)) {
          foreach($elementsByField as $field=>$fieldDetails) {
               $result[$indexer][$formname][$indexer][$elementsByField[$field]['id']][] = $array[$field];
          }
          $indexer++;
     }
		 
		 // count master results
		 $countSQL = str_replace("SELECT * FROM", "SELECT count(*) FROM", $basesql);
		 $countRes = $xoopsDB->query($countSQL);
		 $countRow = $xoopsDB->fetchRow($countRes);
		 $GLOBALS['formulize_countMasterResults'] = $countRow[0];
		 if(isset($GLOBALS['formulize_getCountForPageNumbers'])) {
					$GLOBALS['formulize_countMasterResultsForPageNumbers'] = $countRow[0]; 
		      unset($GLOBALS['formulize_getCountForPageNumbers']);
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
          $whereClause .= $elementsById[$filterParts[0]]['field'] . " $operator '$likeparts" . formulize_db_escape($filterParts[1]) . "$likeparts'";
     }
     return $whereClause;
}


// *******************************
// FUNCTIONS BELOW ARE FOR PROCESSING RESULTS
// *******************************

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
	
	$handle = dealWithDeprecatedFrameworkHandles($handle);
	
  if(!$formhandle = getFormHandleFromEntry($entry, $handle)) { return ""; } // return nothing if handle is not part of entry

  $GLOBALS['formulize_mostRecentLocalId'] = array();
	foreach($entry[$formhandle] as $lid=>$elements) {
		if($localid == "NULL" OR $lid == $localid) {
			if(is_array($elements[$handle])) {
				foreach($elements[$handle] as $value) {
					$foundValues[] = $value;
					$GLOBALS['formulize_mostRecentLocalId'][] = $lid;
				}
			} else { // the handle is for metadata, all other fields will be arrays in the dataset
        return $elements[$handle];  
			}
		}
	}
  
	if(count($foundValues) == 1) {
    $GLOBALS['formulize_mostRecentLocalId'] = $GLOBALS['formulize_mostRecentLocalId'][0];
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
// $formhandle can all just be a single handle
// $formhandle values can be: the title of the form, or the id number of the form
// If formhandle is an array, then $ids becomes a two dimensional array:  $ids[$formhandle][] = $id
// Crazy in efficiencies going on here in terms of how ids and titles are getting converted back and forth and back and forth.  And using form titles in the data array is the last bit of general stupidity that should be squeezed out, but its a backwards compatibility issue now.
function internalRecordIds($entry, $formhandle="", $id="NULL", $fidAsKeys = false) {
	if(is_numeric($id)) {
		$entry = $entry[$id];
	}
  
	if(!$formhandle) {
		$formhandle = getFormHandlesFromEntry($entry);
	} else {
		 // need to convert possible legacy framework form handles to form ids
		 $formhandle = dealWithDeprecatedFormHandles($formhandle);
	}
	if(is_array($formhandle)) {
		foreach($formhandle as $handle) {
      $handle = _parseInternalRecordIdsFormHandle($handle);
			foreach($entry[$handle] as $id=>$element) {
				if($fidAsKeys) {
					$fid = formulize_getFormIdFromName($handle); 
					$ids[$fid][] = $id;
				} else {
					$ids[$handle][] = $id;
				}
			}
		}
	} else {
		$formhandle = _parseInternalRecordIdsFormHandle($formhandle);
		if (is_array($entry[$formhandle])) {
			foreach($entry[$formhandle] as $id=>$element) {
				$ids[] = $id;
			}
		}
	}
	return $ids;
}

// this function takes a formhandle and if it's numeric, returns the title for that form
function _parseInternalRecordIdsFormHandle($formhandle) {
     global $xoopsDB;
     if(!is_numeric($formhandle)) { return $formhandle; }
     static $cachedDescForm = array();
     if(!isset($cachedDescForm[$formhandle])) {
          $sql = "SELECT desc_form FROM ".DBPRE."formulize_id WHERE id_form=".intval($formhandle);
          $res = $xoopsDB->query($sql);
          $array = $xoopsDB->fetchArray($res);
          $cachedDescForm[$formhandle] = $array['desc_form'];
     }
     return $cachedDescForm[$formhandle];
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


// DEPRECATED -- this file used to be rigorously written so you could include it from another code base and access all functions in order to interact with form data
// That architectural approach is now fully deprecated.  This file should not be preferenced directly.

// Nonetheless, in accordance with the historical practice, for now, we will invoke certain objects and settings if this file appears to be launching outside the XOOPS core.  Watch for refactoring here in the future.

// if XOOPS has not already connected to the database, then connect to it now using user defined constants that are set in another file
// the idea is to include this file from another one

global $xoopsDB, $myts;


define("DBPRE", $xoopsDB->prefix('') . "_");
if(!defined("_formulize_OPT_OTHER")) {
	global $xoopsConfig;
	switch($xoopsConfig['language']) {
		case "french":
			define("_formulize_OPT_OTHER", "Autre : ");
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
$config_handler =& xoops_gethandler('config');
        
$formulizeModuleConfig =& $config_handler->getConfigsByCat(0, getFormulizeModId()); // get the *Formulize* module config settings
$GLOBALS['formulize_LOE_limit'] = $formulizeModuleConfig['LOE_limit'];       


function formulize_benchmark($text, $dumpLog = false) {
     global $xoopsUser;
		 static $prevPageTime = 0;
		 static $elapsedLog = array();
     if(isset($GLOBALS['startPageTime']) AND $xoopsUser) {
          if($xoopsUser->getVar('uid') == 1) {
               $currentPageTime = microtime_float();
							 if(!$prevPageTime) {
										$prevPageTime = $currentPageTime;
							 }
               print "<br>$text --<br>\nElapsed since last: ".round($currentPageTime - $prevPageTime, 4)."<br>\n";
							 print "Elapsed since start: ".($currentPageTime-$GLOBALS['startPageTime'])."<br>";
							 $elapsedLog[] = round($currentPageTime - $prevPageTime, 4);
							 $prevPageTime = $currentPageTime;
							 if($dumpLog) {
							  		sort($elapsedLog);
							  		print "<br>DUMPING LOG DATA:<br>\nMin elapsed time: ".$elapsedLog[0]."<br>\n";
							  		print "Max elapsed time: ".$elapsedLog[count($elapsedLog)-1]."<br>\n";
							  		print "Average elapsed time: ".round(array_sum($elapsedLog)/count($elapsedLog),4)."<br>\n";
							  		$elapsedLog = array();
							 }	
					}
     }
}


if(!$myts) {
 	// setup text sanitizer too
	include_once XOOPS_ROOT_PATH."/class/module.textsanitizer.php";
	$myts = new MyTextSanitizer();
	$GLOBALS['myts'] = $myts;
}
