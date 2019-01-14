<?php

###############################################################################
##               Formulize - ad hoc form creation and reporting              ##
##                    Copyright (c) 2017 Julian Egelstaff                    ##
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
##  Author of this file: Julian Egelstaff                                    ##
##  Project: Formulize                                                       ##
###############################################################################

// generate a csv file upon a properly authenticated request

error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once "../../mainfile.php";
include_once XOOPS_ROOT_PATH . "/modules/formulize/include/extract.php";

error_reporting(E_ALL);
ini_set('display_errors', 1);

// params can come in through the URL
$fid = intval($_GET['fid']);
$frid = isset($_GET['frid']) ? intval($_GET['frid']) : "";
$filter = isset($_GET['filter']) ? $_GET['filter'] : "";
$sortHandle = isset($_GET['sortHandle']) ? $_GET['sortHandle'] : "";
$sortDir = isset($_GET['sortDir']) ? $_GET['sortDir'] : "";
$andor = isset($_GET['andor']) ? $_GET['andor'] : "AND";
$limitStart = isset($_GET['limitStart']) ? intval($_GET['limitStart']) : 0;
$limitSize = (isset($_GET['limitSize']) AND $limitStart !== "") ? intval($_GET['limitSize']) : "";
$fields = isset($_GET['fields']) ? $_GET['fields'] : "";
$key = preg_replace("/[^A-Za-z0-9]/", "", str_replace(" ","",$_GET['key'])); // keys must be only alphanumeric characters

$member_handler = xoops_gethandler('member');

// authentication block
$apiKeyHandler = xoops_getmodulehandler('apikey', 'formulize');
$apiKeyHandler->delete(); // clear out expired keys
if($key AND $apikey = $apiKeyHandler->get($key)) {
    $uid = $apikey->getVar('uid');
    if($uidObject = $member_handler->getUser($uid)) {
        $groups = $uidObject->getGroups();
        global $xoopsUser, $icmsUser;
        $xoopsUser = $uidObject;
        $icmsUser = $uidObject;
    } else {
        $uid = 0;
        $groups = array(XOOPS_GROUP_ANONYMOUS);
    }
} else {
    print "Invalid authentication key";
    exit();
}


// try for as much data as we can, see what the user is allowed
$currentView = "all";

// extra stuff we need while still using the old interface in the buildScope function
$gperm_handler = xoops_gethandler('groupperm');
$mid = getFormulizeModId();
$scope = buildScope($currentView, $uid, $fid); 
$scope = $scope[0]; // buildScope returns array of scope and possibly altered currentView


if($fid) {
    if($_GET['debug']==1) {
        print "$frid, $fid, $filter, $andor, $scope, $limitStart, $limitSize, $sortHandle, $sortDir, $fields";
        exit();
    }
    $filterElements = array();
    $allCols = array();
    if($fields) {
        $element_handler = xoops_getmodulehandler('elements', 'formulize');
        foreach(explode(",", $fields) as $field) {
            $elementObject = $element_handler->get($field);
            $handle = $elementObject->getVar('ele_handle');
            $filterElements[$elementObject->getVar('id_form')][] = $handle;
            $allCols[] = $handle;
        }
    } else {
        $cols = getAllColList($fid, $frid, $groups);
        foreach($cols as $form=>$values) {
            foreach($values as $value) {
                $allCols[] = $value['ele_handle'];
            }
        }
    }
    
    // hacked in processing of filters, based on code in entriesdisplay.php
    $searches = array();
    foreach($_GET as $getKey=>$getValue) {
        if(!in_array($getKey,array('fid', 'frid', 'filter','sortHandle','showHandles','sortDir','andor','limitSize','limitStart','fields','key','includeMetadata'))) {
            $searches[$getKey] = $getValue;
        }
    }
    
    $element_handler = xoops_getmodulehandler('elements', 'formulize');
    
    foreach($searches as $key => $master_one_search) { // $key is the element handle
		// convert "between 2001-01-01 and 2002-02-02" to a normal date filter with two dates
		$count = preg_match("/^[bB][eE][tT][wW][eE][eE][nN] ([\d]{1,4}[-][\d]{1,2}[-][\d]{1,4}) [aA][nN][dD] ([\d]{1,4}[-][\d]{1,2}[-][\d]{1,4})\$/", $master_one_search, $matches);
		if ($count > 0) {
			$master_one_search = ">={$matches[1]}//<={$matches[2]}";
		}

		// split search based on new split string
		$intermediateArray = explode("//", $master_one_search);

		$searchArray = array();

		foreach($intermediateArray as $one_search) {
			// if $one_search contains both OR and AND, just add it as-is; we don't support this kind of nesting
			if (strpos($one_search, " OR ") !== FALSE AND strpos($one_search, " AND ") !== FALSE) {
				$searchArray[] = $one_search;
			}
			// split on OR and add all split results, prepended with OR
			else if (strpos($one_search, " OR ") !== FALSE) {
				foreach(explode(" OR ", $one_search) as $or_term) {
						$searchArray[] = "OR" . $or_term;
				}
			}
			// split on AND and add all split results
			else if (strpos($one_search, " AND ") !== FALSE) {
				foreach(explode(" AND ", $one_search) as $and_term) {
					$searchArray[] = $and_term;
				}
			}
			// otherwise just add to the array
			else {
				$searchArray[] = $one_search;
			}
		}

		foreach($searchArray as $one_search) {
            // used for trapping the {BLANK} keywords into their own space so they don't interfere with each other, or other filters
            $addToItsOwnORFilter = false;

            if ("creation_uid" == $key OR "entry_id" == $key OR "creation_datetime" == $key OR "mod_datetime" == $key OR "mod_uid" == $key OR "creator_email" == $key) {
                $ele_type = "text";
            } else {
                $elementObject = $element_handler->get($key);
                $ele_type = $elementObject->getVar('ele_type');
            }

		    // remove the qsf_ parts to make the quickfilter searches work
		    if(substr($one_search, 0, 4)=="qsf_") {
              $qsfparts = explode("_", $one_search);
			  $allowsMulti = false;
			  if($ele_type == "select") {
				$ele_value = $elementObject->getVar('ele_value');
				if($ele_value[1]) {
				  $allowsMulti = true;
				}
			  } elseif($ele_type == "checkbox") {
				$allowsMulti = true;
		      }
			  if($allowsMulti) {
				$one_search = $qsfparts[2]; // will default to using LIKE since there's no operator
			  } else {
				$one_search = "=".$qsfparts[2];
			  }
		    }
	
			// strip out any starting and ending ! that indicate that the column should not be stripped
			if(substr($one_search, 0, 1) == "!" AND substr($one_search, -1) == "!") {
				$one_search = substr($one_search, 1, -1);
			}
			
			// look for OR indicators...if all caps OR is at the front, then that means that this search is to put put into a separate set of OR filters that gets appended as a set to the main set of AND filters
		    $addToORFilter = false; // flag to indicate if we need to apply the current search term to a set of "OR'd" terms			
			if(substr($one_search, 0, 2) == "OR" AND strlen($one_search) > 2) {
				$addToORFilter = true;
				$one_search = substr($one_search, 2);
			}
			
			// look for operators
			$operators = array(0=>"=", 1=>">", 2=>"<", 3=>"!");
			$operator = "";
			if(in_array(substr($one_search, 0, 1), $operators)) {
				// operator found, check to see if it's <= or >= and set start point for term accordingly
				$startpoint = (substr($one_search, 0, 2) == ">=" OR substr($one_search, 0, 2) == "<=" OR substr($one_search, 0, 2) == "!=" OR substr($one_search, 0, 2) == "<>") ? 2 : 1;
				$operator = substr($one_search, 0, $startpoint);
        if($operator == "!") { $operator = "NOT LIKE"; }
				$one_search = substr($one_search, $startpoint);
			}

			// look for blank search terms and convert them to {BLANK} so they are handled properly
			if($one_search === "") {
				$one_search = "{BLANK}";
			}

			// look for { } and transform special terms into what they should be for the filter
			if(substr($one_search, 0, 1) == "{" AND substr($one_search, -1) == "}") {
				$searchgetkey = substr($one_search, 1, -1);

				if (ereg_replace("[^A-Z]","", $searchgetkey) == "TODAY") {
					$number = ereg_replace("[^0-9+-]","", $searchgetkey);
					$one_search = date("Y-m-d",mktime(0, 0, 0, date("m") , date("d")+$number, date("Y")));
				} elseif($searchgetkey == "USER") {
					if($xoopsUser) {
                        $one_search = htmlspecialchars_decode($xoopsUser->getVar('name'), ENT_QUOTES);
						if(!$one_search) { $one_search = htmlspecialchars_decode($xoopsUser->getVar('uname'), ENT_QUOTES); }
					} else {
						$one_search = 0;
					}
				} elseif($searchgetkey == "USERNAME") {
					if($xoopsUser) {
                        $one_search = htmlspecialchars_decode($xoopsUser->getVar('name'), ENT_QUOTES);
					} else {
						$one_search = "";
					}
				} elseif($searchgetkey == "BLANK") { // special case, we need to construct a special OR here that will look for "" OR IS NULL
				  if($operator == "!=" OR $operator == "NOT LIKE") {
				    $blankOp1 = "!=";
				    $blankOp2 = " IS NOT NULL ";
				  } else {
				    $addToItsOwnORFilter = $addToORFilter ? false : true; // if this is not going into an OR filter already because the user asked for it to, then let's
				    $blankOp1 = "=";
				    $blankOp2 = " IS NULL ";
				  }
				  $one_search = "/**/$blankOp1][$key/**//**/$blankOp2";
				  $operator = ""; // don't use an operator, we've specially constructed the one_search string to have all the info we need
				} elseif($searchgetkey == "PERGROUPFILTER") {
					$one_search = $searchgetkey;
					$operator = "";
				} elseif(isset($_POST[$searchgetkey]) OR isset($_GET[$searchgetkey])) {
					$one_search = $_POST[$searchgetkey] ? htmlspecialchars(strip_tags(trim($_POST[$searchgetkey])), ENT_QUOTES) : "";
					$one_search = (!$one_search AND $_GET[$searchgetkey]) ? htmlspecialchars(strip_tags(trim($_GET[$searchgetkey])), ENT_QUOTES) : $one_search;
					if(!$one_search) {
						continue;
					}
				} elseif($searchgetkey) { // we were supposed to find something above, but did not, so there is a user defined search term, which has no value, ergo disregard this search term
					continue;
				} else {
					$one_search = "";
					$operator = "";
				}
			} else {
				// handle alterations to non { } search terms here...
				if ($ele_type == "date") {
                    $search_date = strtotime($one_search);
                    // only search on a valid date string (otherwise it will be converted to the unix epoch)
                    if (false !== $search_date) {
                        $one_search = date('Y-m-d', $search_date);
                    }
				}
			}

			// do additional search for {USERNAME} or {USER} in case they are embedded in another string
			if($xoopsUser) {
                $one_search = str_replace("{USER}", htmlspecialchars_decode($xoopsUser->getVar('name'), ENT_QUOTES), $one_search);
				$one_search = str_replace("{USERNAME}", htmlspecialchars_decode($xoopsUser->getVar('uname'), ENT_QUOTES), $one_search);
			}

			
			if($operator) {
				$one_search = $one_search . "/**/" . $operator;
			}
			if($addToItsOwnORFilter) {
				$individualORSearches[] = $key ."/**/$one_search";
			} elseif($addToORFilter) {
				if(!$ORstart) { $ORfilter .= "]["; }
				$ORfilter .= $key . "/**/$one_search"; // . formulize_db_escape($one_search); // mysql_real_escape_string no longer necessary here since the extraction layer does the necessary dirty work for us
				$ORstart = 0;
			} else {
				if(!$start) { $filter .= "]["; }
				$filter .= $key . "/**/$one_search"; // . formulize_db_escape($one_search); // mysql_real_escape_string no longer necessary here since the extraction layer does the necessary dirty work for us
				$start = 0;
			}
			
		}
	}
	//print $filter;
	// if there's a set of options that have been OR'd, then we need to construction a more complex filter
	
	if($ORfilter OR count($individualORSearches)>0) {
		$filterIndex = 0;
		$arrayFilter[$filterIndex][0] = "and";
		$arrayFilter[$filterIndex][1] = $filter;
		if($ORfilter) {
			$filterIndex++;
			$arrayFilter[$filterIndex][0] = "or";
			$arrayFilter[$filterIndex][1] = $ORfilter;
		}
		if(count($individualORSearches)>0) {
			foreach($individualORSearches as $thisORfilter) {
				$filterIndex++;
				$arrayFilter[$filterIndex][0] = "or";
				$arrayFilter[$filterIndex][1] = $thisORfilter;
			}
		}
		$filter = $arrayFilter;
		$filterToCompare = serialize($filter);
	} else {
		$filterToCompare = $filter;
	}
    
    $filter = trim($filter,'][');
    
    $filterElements = count($filterElements) == 0 ? null : $filterElements;
    $data = getData($frid, $fid, $filter, $andor, $scope, $limitStart, $limitSize, $sortHandle, $sortDir, false, 0, false, "", false, 'bypass', $filterElements); // 'bypass' before filterElements means don't even do the query, just prep eveything - avoids potentially expensive query and expensive pass through all the data!
    if($data === true) { // we'll get back false if we weren't able to 
        $exportTime = formulize_catchAndWriteExportQuery($fid);
        if(isset($_GET['includeMetadata'])) {
            $_GET['cols'] = 'entry_id,creation_uid,mod_uid,creation_datetime,mod_datetime,creator_email,';
        } else {
            $_GET['cols'] = "";
        }
        $_GET['cols'] .= implode(",", $allCols);
        $_GET['eq'] = $exportTime; // set this so we can load the cached query file when doing the export
        $_POST['metachoice'] = 0; // necessary so when we call the export file, it will trigger a download instead of showing UI
        
        /*print_r($_GET['cols']);
        print "<br>";
        print_r($data);
        exit();*/
        
        include_once XOOPS_ROOT_PATH . "/modules/formulize/include/export.php"; // actually generates the csv and makes it available as a download
    } else {
        print "No data found";
    }
} else {
    // print out help info
    print "Valid URL parameters for the Formulize makecsv.php file:
    
key,required,a valid authentication key issued by the a webmaster for your site
fid,required,the id number of the form you are querying - if absent this help text is displayed
showHandles,optional,a flag to trigger showing data handles as the second line of the spreadsheet - value doesn't matter
fields,optional,the data handle or id number of the elements you want to display in the spreadsheet (comma separated list)
handle=searchterm,optional,use the handle for a field to specify a search term for the field - search terms are case insensitive and support partial matches and support greater-than/less-than for numbers (ie: >1969)
filter,optional,a filter string compatible with the getData function - details in this document:
,,http://formulize.org/formulize/Using_Formulize-Pageworks_to_Make_Custom_Applications.pdf
frid,optional,the id number of the form relationship that you are querying
andor,optional,if a filter is specified then this determines if multiple filter terms are joined by AND or OR - default is AND
sortHandle,optional,an element handle to sort the data by - default is entry id (creation order)
sortDir,optional,a direction for the sorting of data - default is ASC - valid values are ASC and DESC
limitSize,optional,a number indicating how many rows to include from the overall query result - used as part of a standard LIMIT statement in the database query
limitStart,optional,a number indicating where to start displaying rows from the overall query result - used as part of a standard LIMIT statement in the database query - defaults to 0 (results are numbered from 0)
includeMetadata,optional,if present then the metadata columns will be included in the result - value doesn't matter

Each authentication key is associated with a unique user and will only return data which that user has access to.

Examples:

Query form 2 for entries where the form element 'province' contains 'Newfoundland' in the value
http://mysite.com/formulize/makecsv.php?key=ABC123&fid=2&province=Newfoundland

Show Handles as the second line
http://mysite.com/formulize/makecsv.php?key=ABC123&fid=2&province=Newfoundland&showHandles

Include only the 'population' and 'language' fields
http://mysite.com/formulize/makecsv.php?key=ABC123&fid=2&province=Newfoundland&fields=pop,lang

Sort results by the element 'city'
http://mysite.com/formulize/makecsv.php?key=ABC123&fid=2&sortHandle=city

Show results 11 through 20 (results are numbered from 0 so 10 means the 11th result)
http://mysite.com/formulize/makecsv.php?key=ABC123&fid=2&limitStart=10&limitSize=10

You can use all the optional parameters at once if you want to.

";
}
