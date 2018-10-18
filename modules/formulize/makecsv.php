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

// params can come in through the URL
$fid = intval($_GET['fid']);
$frid = isset($_GET['frid']) ? intval($_GET['frid']) : "";
$filter = isset($_GET['filter']) ? $_GET['filter'] : "";
$sortHandle = isset($_GET['sortHandle']) ? $_GET['sortHandle'] : "";
$sortDir = isset($_GET['sortDir']) ? $_GET['sortDir'] : "";
$andor = isset($_GET['andor']) ? $_GET['andor'] : "AND";
$limitStart = isset($_GET['limitStart']) ? $_GET['limitStart'] : "";
$limitSize = (isset($_GET['limitSize']) AND $limitStart !== "") ? $_GET['limitSize'] : "";
$key = preg_replace("/[^A-Za-z0-9]/", "", str_replace(" ","",$_GET['key'])); // keys must be only alphanumeric characters

$member_handler = xoops_gethandler('member');

// authentication block
$apiKeyHandler = xoops_getmodulehandler('apikey', 'formulize');
$apiKeyHandler->delete(); // clear out expired keys
if($key AND $apikey = $apiKeyHandler->get($key)) {
    $uid = $apikey->getVar('uid');
    if($uidObject = $member_handler->getUser($uid)) {
        $groups = $uidObject->getGroups();
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
    $data = getData($frid, $fid, $filter, $andor, $scope, $limitStart, $limitSize, $sortHandle, $sortDir);
    if(count($data)>0) {
        $exportTime = formulize_catchAndWriteExportQuery($fid);
        $cols = getAllColList($fid, $frid, $groups);
        $allCols = array();
        foreach($cols as $form=>$values) {
            foreach($values as $value) {
                $allCols[] = $value['ele_handle'];
            }
        }
        if(isset($_GET['includeMetadata'])) {
            $_GET['cols'] = 'entry_id,creation_uid,mod_uid,creation_datetime,mod_datetime,creator_email,';
        } else {
            $_GET['cols'] = "";
        }
        $_GET['cols'] .= implode(",", $allCols);
        $_GET['eq'] = $exportTime; // set this so we can load the cached query file when doing the export
        $_POST['metachoice'] = 0; // necessary so when we call the export file, it will trigger a download instead of showing UI
        include_once XOOPS_ROOT_PATH . "/modules/formulize/include/export.php"; // actually generates the csv and makes it available as a download
    } else {
        print "No data found";
    }
} else {
    // print out help info
    print "Valid URL parameters for the Formulize makecsv.php file:
    
key,required,a valid authentication key issued by the a webmaster for your site
fid,required,the id number of the form you are querying - if absent this help text is displayed
frid,optional,the id number of the form relationship that you are querying
filter,optional,a filter string compatible with the getData function - details in this document:
,,http://formulize.org/formulize/Using_Formulize-Pageworks_to_Make_Custom_Applications.pdf
andor,optional,if a filter is specified then this determines if multiple filter terms are joined by AND or OR - default is AND
sortHandle,optional,an element handle to sort the data by - default is entry id (creation order)
sortDir,optional,a direction for the sorting of data - default is ASC - valid values are ASC and DESC
limitStart,optional,a number indicating where to start displaying rows from the overall query result - used as part of a standard LIMIT statement in the database query - results are numbered from 0
limitSize,optional,a number indicating how many rows to include from the overall query result - used as part of a standard LIMIT statement in the database query
includeMetadata,optional,if present then the metadata columns will be included in the result - value doesn't matter

Each authentication key is associated with a unique user and will only return data which that user has access to.

Examples:

Query form 2 for entries where the form element 'province' contains 'Newfoundland' in the value
http://mysite.com/formulize/makecsv.php?key=ABC123&fid=2&filter=province/**/Newfoundland

Sort results by the element 'city'
http://mysite.com/formulize/makecsv.php?key=ABC123&fid=2&sortHandle=city

Show results 11 through 20 (results are numbered from 0 so 10 means the 11th result)
http://mysite.com/formulize/makecsv.php?key=ABC123&fid=2&limitStart=10&limitSize=10

You can use all the optional parameters at once if you want to.

";
}
