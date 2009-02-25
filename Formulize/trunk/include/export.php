<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2008 Freeform Solutions                  ##
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

// this file generates the export popup
require_once "../../../mainfile.php";
global $xoopsConfig;
// load the formulize language constants if they haven't been loaded already
	if ( file_exists(XOOPS_ROOT_PATH."/modules/formulize/language/".$xoopsConfig['language']."/main.php") ) {
		include_once XOOPS_ROOT_PATH."/modules/formulize/language/".$xoopsConfig['language']."/main.php";
	} else {
		include_once XOOPS_ROOT_PATH."/modules/formulize/language/english/main.php";
	}
include_once XOOPS_ROOT_PATH . "/modules/formulize/include/functions.php";
include_once XOOPS_ROOT_PATH . "/modules/formulize/include/extract.php";
print "<HTML>";
print "<head>";
print "<title>" . _formulize_DE_EXPORT . "</title>\n";

print "<link rel=\"stylesheet\" type=\"text/css\" media=\"screen\" href=\"" . XOOPS_URL . "/xoops.css\" />\n";
$themecss = xoops_getcss();
//$themecss = substr($themecss, 0, -6);
//$themecss .= ".css";
print "<link rel=\"stylesheet\" type=\"text/css\" media=\"screen\" href=\"$themecss\" />\n";

print "</head>";
print "<body><center>"; 
print "<table width=100%><tr><td width=5%></td><td width=90%>";

// 1. need to pickup the full query that was used for the dataset on the page where the button was clicked
// 2. need to run that query and make a complete dataset
// 3. need to send that dataset to the prepexport function to make the spreadsheet
// 4. need to provide a link to the finished file
// 5. need to make sure import templates are created appropriately

// read the query data from the cached file
$queryData = file(XOOPS_ROOT_PATH."/cache/exportQuery_".intval($_GET['eq']).".formulize_cached_query_for_export");
global $xoopsUser;
$exportUid = $xoopsUser ? $xoopsUser->getVar('uid') : 0;
$fid = intval($_GET['fid']);
$frid = intval($_GET['frid']);
$groups = $xoopsUser ? $xoopsUser->getGroups() : array(0=>XOOPS_GROUP_ANONYMOUS);
if(trim($queryData[0]) == intval($_GET['fid']) AND trim($queryData[1]) == $exportUid) { // query fid must match passed fid in URL, and the current user id must match the userid at the time the export file was created
    print "<center><h1>"._formulize_DE_EXPORTTITLE."</h1></center>\n";
    $data = getData($frid, $fid, $queryData[2]);
    
    $cols = explode(",",$_GET['cols']);
    $headers = array();
    foreach($cols as $thiscol) {
			if($thiscol == "creator_email") {
				$headers[] = _formulize_DE_CALC_CREATOR_EMAIL;
			} else {
				$colMeta = formulize_getElementMetaData($thiscol, true);
				$headers[] = $colMeta['ele_colhead'] ? trans($colMeta['ele_colhead']) : trans($colMeta['ele_caption']);
			}
    }
		if($_GET['type'] == "update") {
			$fdchoice = "update";
			$linkText = _formulize_DE_CLICKSAVE_TEMPLATE;
		} else {
			$linkText = _formulize_DE_CLICKSAVE;
			$fdchoice = "comma";
			//$cols = array();
			//$headers = array();
		}
		/*print "<pre>";
		print_r($cols);
		print_r($headers);
		print "</pre>";*/
		if($frid) {
			$filename = prepExport($headers, convertElementHandlesToFrameworkHandles($cols, $frid), $data, $fdchoice, "", "", false, $fid, $groups);
		} else {
			$filename = prepExport($headers, $cols, $data, $fdchoice, "", "", false, $fid, $groups);
		}
    print "<center><p><a href=\"$filename\">$linkText</a></p></center>\n";
    
    if($_GET['type']=="update") {
        print "<p>"._formulize_DE_IMPORT_DATATEMP4." <a href=\"\" onclick=\"javascript:window.opener.showPop('" . XOOPS_URL . "/modules/formulize/include/import.php?fid=$fid&eq=".intval($_GET['eq'])."');return false;\">"._formulize_DE_IMPORT_DATATEMP5."</a></p>\n";
        print "<p>"._formulize_DE_IMPORT_DATATEMP3."</p>\n";
    }
    
} else {
    print _formulize_DE_EXPORT_FILE_ERROR;
}

print "</td><td width=5%></td></tr></table>";
print "</center></body>";
print "</HTML>";


?>