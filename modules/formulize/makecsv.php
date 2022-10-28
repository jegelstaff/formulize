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

include_once "../../mainfile.php";
include_once XOOPS_ROOT_PATH . "/modules/formulize/include/extract.php";

icms::$logger->disableLogger();

while(ob_get_level()) {
    ob_end_clean();
}

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
$excludeFields  = isset($_GET['excludeFields']) ? explode(',',$_GET['excludeFields']) : array();
$key = preg_replace("/[^A-Za-z0-9]/", "", str_replace(" ","",$_GET['key'])); // keys must be only alphanumeric characters
if(isset($_GET['showForeignKeys'])) {
    $GLOBALS['formulize_useForeignKeysInDataset']['all'] = true;
}

// unpack any filters that are declared in the special formulize_makeCSVFilters param
if(isset($_GET['formulize_makeCSVFilters'])) {
    foreach(explode(',',$_GET['formulize_makeCSVFilters']) as $filterName) {
        if(isset($_SESSION['formulize_makeCSVFilters'][$filterName]) AND is_array($_SESSION['formulize_makeCSVFilters'][$filterName])) {
            $_GET[$_SESSION['formulize_makeCSVFilters'][$filterName]['element']] = $_SESSION['formulize_makeCSVFilters'][$filterName]['filter'];
        }
    }
}

$member_handler = xoops_gethandler('member');
global $xoopsUser, $icmsUser;

// authentication block
$apiKeyHandler = xoops_getmodulehandler('apikey', 'formulize');
$apiKeyHandler->delete(); // clear out expired keys
$uid = 0;
if($key AND $apikey = $apiKeyHandler->get($key)) {
    $uid = $apikey->getVar('uid');
    if($uidObject = $member_handler->getUser($uid)) {
        $groups = $uidObject->getGroups();
        $xoopsUser = $uidObject;
        $icmsUser = $uidObject;
    } else {
        $uid = 0;
        $groups = array(XOOPS_GROUP_ANONYMOUS);
    }
} elseif($xoopsUser) {
    $uid = $xoopsUser->getVar('uid');
    $groups = $xoopsUser->getGroups();
} elseif($key) {
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


if($fid AND $uid) {
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
            if(in_array($handle,$excludeFields) OR in_array($elementObject->getVar('ele_id'),$excludeFields)) {continue;}
            $allCols[] = $handle;
        }
    } else {
        $cols = getAllColList($fid, $frid, $groups);
        foreach($cols as $form=>$values) {
            foreach($values as $value) {
                if(in_array($value['ele_handle'],$excludeFields) OR in_array($value['ele_id'],$excludeFields)) {continue;}
                $allCols[] = $value['ele_handle'];
            }
        }
    }
    
    // hacked in processing of filters, based on code in entriesdisplay.php
    $searches = array();
    foreach($_GET as $getKey=>$getValue) {
        if(!in_array($getKey,array('fid', 'frid', 'filter','sortHandle','showHandles','sortDir','andor','limitSize','limitStart','fields','key','includeMetadata','excludeFields', 'formulize_makeCSVFilters'))) {
            if(strstr($getValue, '&amp;quot;') OR strstr($getValue, '&amp;#34;') OR strstr($getValue, '&amp;#39;') OR strstr($getValue, '&amp;apos;')) { // could do better detection of this! but hard to know how many levels we ought to undo
                $getValue = htmlspecialchars_decode($getValue); // special chars that have been urlencoded will have &amp; on the front instead of the true & so we need to fix that!
            }
            $searches[$getKey] = $getValue;
        }
    }
    
    $searchFilter = formulize_parseSearchesIntoFilter($searches);
    if($searchFilter AND !is_array($searchFilter)) { // if search is a string, append it to any existing filter or use it outright
        $filter .= $filter ? $filter.']['.$searchFilter : $searchFilter;
    } elseif($filter AND $searchFilter) { // if search is an array, stick an existing filter into the array and use that
        $filterIndex = count((array) $searchFilter);
        $searchFilter[$filterIndex][0] = $andor;
        $searchFilter[$filterIndex][1] = $filter;
        $filter = $searchFilter;
    } elseif($searchFilter) { // if search is an array and there is no existing filter, then use the search array as the filter
        $filter = $searchFilter;
    }
    $filterElements = count((array) $filterElements) == 0 ? null : $filterElements;
    $GLOBALS['formulize_setQueryForExport'] = true;
    $data = getData($frid, $fid, $filter, $andor, $scope, $limitStart, $limitSize, $sortHandle, $sortDir, false, 0, false, "", false, 'bypass', $filterElements); // 'bypass' before filterElements means don't even do the query, just prep eveything - avoids potentially expensive query and expensive pass through all the data!
    if($data === true) { // we'll get back false if we weren't able to 
        $exportTime = formulize_catchAndWriteExportQuery($fid);
        $_GET['cols'] = "";
        if(isset($_GET['includeMetadata'])) {
            foreach(array('entry_id','creation_uid','mod_uid','creation_datetime','mod_datetime','creator_email') as $metadataField) {
                if(in_array($metadataField,$excludeFields)) {continue;}
                $_GET['cols'] .= $metadataField.',';
            }
        } 
        $_GET['cols'] .= implode(",", $allCols);
        $_GET['eq'] = $exportTime; // set this so we can load the cached query file when doing the export
        $_POST['metachoice'] = 0; // necessary so when we call the export file, it will trigger a download instead of showing UI
        if(isset($_GET['excelutf8'])) {
            $_POST['excel'] = 1; // will cause the byte marker for excel to be included, so accents, etc, work right
        }
        
        /*print_r($_GET['cols']);
        print "<br>";
        print_r($data);
        exit();*/
        
        include XOOPS_ROOT_PATH . "/modules/formulize/include/export.php"; // actually generates the csv and makes it available as a download
    } else {
        print "No data found";
    }
} else {
    // print out help info
    print "<pre>
Valid URL parameters for the Formulize makecsv.php file:
    
key,a valid authentication key issued by a webmaster for your site (if there is no key, a user must be logged in)
fid,required,the id number of the form you are querying - if absent this help text is displayed
showHandles,optional,a flag to trigger showing data handles as the second line of the spreadsheet - value doesn't matter
fields,optional,the data handle or id number of the elements you want to display in the spreadsheet (comma separated list)
excludeFields,optional,the data handle of elements that you do not want displayed in the spreadsheet (comma separated list)
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
showForeignKeys,optional,if present then the raw values in the database will be shown for references to other forms instead of the value from the other form - value doesn't matter

Each authentication key is associated with a unique user and will only return data which that user has access to.

Examples:

Query form 2 for entries where the form element 'province' contains 'Newfoundland' in the value
http://mysite.com/formulize/makecsv.php?key=ABC123&fid=2&province=Newfoundland

Show Handles as the second line
http://mysite.com/formulize/makecsv.php?key=ABC123&fid=2&province=Newfoundland&showHandles

Include only the 'population' and 'language' fields
http://mysite.com/formulize/makecsv.php?key=ABC123&fid=2&province=Newfoundland&fields=pop,lang

Filter on the 'pop' field for greater than 1000 
http://mysite.com/formulize/makecsv.php?key=ABC123&fid=2&pop=>1000
(note the > is included in the search term, but the = sign is still necessary because this is a URL)

Sort results by the element 'city'
http://mysite.com/formulize/makecsv.php?key=ABC123&fid=2&sortHandle=city

Show results 11 through 20 (results are numbered from 0 so 10 means the 11th result)
http://mysite.com/formulize/makecsv.php?key=ABC123&fid=2&limitStart=10&limitSize=10

You can use all the optional parameters at once if you want to.

";
}
