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

// This file contains the logic for the import popup

// The other popups like this are handled by the following files:
// changecols.php
// advsearch.php
// changescope.php
// pickcalcs.php
// save.php

// all of those use javascript to communicate options back to the parent window that opened them.
// The import process probably does not need to communicate back to the parent window, but if so,
// consult those files to see how that has been done in the past.

require_once "../../../mainfile.php";

global $xoopsConfig;
// load the formulize language constants if they haven't been loaded already -- also other language constants for user registration
	if ( file_exists(XOOPS_ROOT_PATH."/modules/formulize/language/".$xoopsConfig['language']."/main.php") ) {
		include_once XOOPS_ROOT_PATH."/modules/formulize/language/".$xoopsConfig['language']."/main.php";
	} else {
		include_once XOOPS_ROOT_PATH."/modules/formulize/language/english/main.php";
	}
	if ( file_exists(XOOPS_ROOT_PATH."/language/".$xoopsConfig['language']."/user.php") ) {
		include_once XOOPS_ROOT_PATH."/language/".$xoopsConfig['language']."/user.php";
	} else {
		include_once XOOPS_ROOT_PATH."/language/english/user.php";
	}



global $xoopsDB, $xoopsUser;

$xoopsConfigUser =& $config_handler->getConfigsByCat(XOOPS_CONF_USER);
$config_handler =& xoops_gethandler('config');

include_once XOOPS_ROOT_PATH.'/modules/formulize/include/functions.php';

	// Set some required variables
	$mid = getFormulizeModId();
	$fid="";
	if(!$fid = $_GET['fid']) {
		$fid = $_POST['fid'];
	}
	$frid = "";
	if(!$frid = $_GET['frid']) {
		$frid = $_POST['frid'];	
	}

	$gperm_handler = &xoops_gethandler('groupperm');
	$member_handler =& xoops_gethandler('member');
	$groups = $xoopsUser ? $xoopsUser->getGroups() : XOOPS_GROUP_ANONYMOUS;
	$uid = $xoopsUser->getVar('uid');

	// additional check to see if the user has import_data permission for this form
	if(!$scheck = security_check($fid, "", $uid, "", $groups, $mid, $gperm_handler, "") OR !$import_data = $gperm_handler->checkRight("import_data", $fid, $groups, $mid)) {
		print "<p>" . _NO_PERM . "</p>";
		exit;
	}


// main body of page and logic goes here...

// basic premise is that we have the $fid, and that is the form that we are importing data into.
// We need a browse box that the user can use to select the .csv they have prepared, and then when 
// they click the submit button to upload that file, presto, the import process begins.  If there
// are parse errors on the file, the import process communicates them.  If the parse is successful, 
// the import begins and the user gets a message, maybe a report of the number of records entered into
// the DB, or whatever seems appropriate.  Then there is a button to close the window.

// This popup window can be reloaded and receive form submissions in it just like any other window, of 
// course.  It's essentially a compartmentalized extension of the main "list of entries" UI.


print "<HTML>";
print "<head>";
print "<title>" . _formulize_DE_IMPORTDATA . "</title>\n";

print "<link rel=\"stylesheet\" type=\"text/css\" media=\"screen\" href=\"" . XOOPS_URL . "/xoops.css\" />\n";
$themecss = xoops_getcss();
//$themecss = substr($themecss, 0, -6);
//$themecss .= ".css";
print "<link rel=\"stylesheet\" type=\"text/css\" media=\"screen\" href=\"$themecss\" />\n";

print "</head>";
print "<body><center>"; 
print "<table width=100%><tr><td width=5%></td><td width=90%>";

print "<table class=outer><tr><th colspan=2>" . _formulize_DE_IMPORT . "</th></tr>";

define("IMPORT_WRITE", true);
define("IMPORT_DEBUG", false);

//define("IMPORT_WRITE", false);
//define("IMPORT_DEBUG", true);

$errors = array();

// get id of profile form
$module_handler =& xoops_gethandler('module');
$formulizeModule =& $module_handler->getByDirname("formulize");
$formulizeConfig =& $config_handler->getConfigsByCat(0, $formulizeModule->getVar('mid'));
$regfid = $formulizeConfig['profileForm'];

// Test if the filename of the temporary uploaded csv is empty
//$csv_name = @$_POST["csv_name"];
$csv_name = @$_FILES['csv_name']['tmp_name'];
if($csv_name != "")
{
	//$csv_name = "../import/$csv_name.csv";
	print "<tr><td class=head><p>" . _formulize_DE_IMPORT_RESULTS . "</p></td><td class=odd>\n";
	
	// check for this file in the list of valid imports, and if necessary, pass the valid id_reqs along with it
	$filenameparts = explode("_", $_FILES['csv_name']['name']);
	$fileid = substr($filenameparts[1], 0, -4);
	$filesql = "SELECT id_reqs FROM " . $xoopsDB->prefix("formulize_valid_imports") . " WHERE file=" . intval($fileid);
	$fileres = $xoopsDB->query($filesql);
	$filerow = $xoopsDB->fetchRow($fileres);
	if($filerow[0]) {
		$id_reqs = unserialize($filerow[0]);
	} else {
		$id_reqs = false;
	}
	importCsv(array($_FILES['csv_name']['name'], $csv_name), $id_reqs, $regfid);
	print "</td></tr>\n";
}
else
{

print "<tr><td class=head><p>" . _formulize_DE_IMPORT_STEP1 . "</p></td><td class=even>";

// provide a blank template, and a blank update template
// store the id_reqs and the filename in the DB for later reference in the case of the update template

// determine if this is the profile form and if so, send special flag to template creation

$cols1 = getAllColList($fid, "", $groups);
$cols = array();
foreach($cols1[$fid] as $col) {
	$cols[] = $col['ele_id'];
}
$headers = getHeaders($cols);
$template = $regfid == $fid ? "blankprofile" : "blank";
$blank_template = prepExport($headers, $cols, "", "comma", "", "", $template);

print "<p><b>" . _formulize_DE_IMPORT_EITHEROR . "</b><p>";

print "<ul><li>" . _formulize_DE_IMPORT_BLANK . "<br><a href=$blank_template target=_blank>" . _formulize_DE_IMPORT_BLANK2 . "</a></li></ul>\n";
print "<Center><p><b>" . _formulize_DE_IMPORT_OR . "</b></p></center>";
print "<ul><li>" . _formulize_DE_IMPORT_DATATEMP . "<br><a href=\"\" onclick=\"javascript:window.opener.runExport('update');window.opener.focus();return false;\">" . _formulize_DE_IMPORT_DATATEMP2 . "</a><br>" . _formulize_DE_IMPORT_DATATEMP3;

print "</li></ul></td></tr>\n";
print "<tr><td class=head><p>" . _formulize_DE_IMPORT_STEP2 . "</p></td><td class=even>" . _formulize_DE_IMPORT_INSTRUCTIONS;

if($regfid == $fid) { 
	print _formulize_DE_IMPORT_INSTNEWPROFILE;
} else {
	print _formulize_DE_IMPORT_INSTNEW;
}

print _formulize_DE_IMPORT_INSTUPDATE . "</td></tr>\n";
print "<tr><td class=head><p>" . _formulize_DE_IMPORT_STEP3 . "</p></td><td class=even><p>" . _formulize_DE_IMPORT_FILE . ": <form method=\"post\" ENCTYPE=\"multipart/form-data\"><input type=\"file\" name=\"csv_name\" size=\"40\" /></p><p><input type=\"submit\" value=\"" . _formulize_DE_IMPORT_GO . "\"></form></p></td></tr>\n";

}

print "</table>";
print "</td><td width=5%></td></tr></table>";
print "</center></body>";
print "</HTML>";


// internal is an array consisting of:
//	[0] file id
//	[1] (array) column headings
//	[2] formulize form id 
//	[3] formulize form elements
//	[4] (array) column headings to formulize form elements
//function importCsv(& $importSets, $id_reqs, $regfid)
function importCsv($csv_name, $id_reqs, $regfid)
{
	global $errors;
    

	//set_time_limit(0);
    
	// Initialize import rules for Activity Booking System
	$importSet = array();
	//$importSet[] = "../import/$csv_name.csv";
	$importSet[] = $csv_name;

	importCsvSetup($importSet, $id_reqs); // will be false on blank templates and user profile templates


	if((is_array($id_reqs) AND !isset($importSet[7]['idreqs'])) OR (!is_array($id_reqs) AND !isset($importSet[7]['creator']) AND $regfid != $importSet[4]) OR ($regfid == $importSet[4] AND !is_array($id_reqs) AND (!isset($importSet[7]['username']) OR !isset($importSet[7]['fullname']) OR !isset($importSet[7]['password']) OR !isset($importSet[7]['email']) OR !isset($importSet[7]['regcode'])))) {
		// necessary metadata columns not present in file
		echo "<br><b>csv not imported!</b><br>Required metadata columns (ie: user who made entry, ID numbers, or account information) not present in the file.";
	} else {

	if(IMPORT_DEBUG)
    {
		importCsvDebug($importSet);
    }

    if(importCsvValidate($importSet, $id_reqs, $regfid))
    {
        importCsvProcess($importSet, $id_reqs, $regfid);

		echo "<script type=\"text/javascript\">\n";
		echo "window.opener.showLoading();\n";
		echo "</script>\n";
    }
    else
    {
        echo "<br><b>csv not imported!</b>"; 
        if(!empty($errors))
        {
            echo "<ol>"; 
            foreach($errors as $error)
            {
                echo $error; 
            }        
            echo "</ol>"; 
        }
    }
    }// end of if metadata columns not present
    
    //importCsvCleanup($importSet);

    echo "<br><br>";
	echo "<b><a href=\"\" onclick=\"javascript:history.back(-1);return false;\">" . _formulize_DE_IMPORT_BACK . "</a></b></div>"; 
}


//function importCsvSetup(& $importSets)
function importCsvSetup(& $importSet, $id_reqs)
{
	global $xoopsDB;

    // First cell on the first line of the csv file contained the form name.
    // This is now provided through formulize variable $fid which is now the id,
    // therefore a lookup is required to go from number to name, instead of the
    // original name to number.
    global $fid;

    /*for($importSetsCount = 0; $importSetsCount < count($importSets); $importSetsCount++)
    {
		$importSet = & $importSets[$importSetsCount];*/

    // 1. verify that files exist
    if(!($importSet[1] = fopen($importSet[0][1], "r")))
    {
        exit("<b>STOPPED</b> csv file <i>" . $importSet[0][0] . "</i> not found.");
    }            

    // 2. get the first row containing form name
    // 3. get the second row containing column names
    if(feof($importSet[1]))
    {
        exit("<b>STOPPED</b> <i>column names</i> not found.");
    }            
    else
    {
        //$importSet[2] = fgetcsv($importSet[1], 4096);
        //$importSet[2] = $importSet[2][0];
        $importSet[2] = getFormTitle($fid);
        $importSet[3] = fgetcsv($importSet[1], 4096);
    }

    // 4. get the form id
    /*$form_idq = q("SELECT id_form FROM " . $xoopsDB->prefix("formulize_id") . 
        " WHERE desc_form='" . $importSet[2] . "'");
    if($form_idq == null)
    {
        exit("<br><b>STOPPED</b> formulize form <i>" . $importSet[2] . "</i> not found.");
    }            
    else
    {
        $importSet[4] = $form_idq[0]["id_form"];
    }*/
    $importSet[4] = $fid;
    
    // 5. get the form column ids and process linked elements
	if($importSet[4])
    {    
        $form_elementsq = q("SELECT * FROM " . $xoopsDB->prefix("formulize") . 
        	" WHERE id_form='" . $importSet[4] . "'");
        if($form_elementsq == null)
        {
            exit("<br><b>STOPPED</b> formulize form <i>" . $importSet[2] . "</i> elements not found.");
        }            
        else
        {
            $importSet[5] = array($form_elementsq, array());
            
            $mapped = array();
            
            $columns = count($importSet[3]);
            for($column = 0; $column < $columns; $column++)
            {
                $cell = $importSet[3][$column];

			// need to record location of: _formulize_DE_CALC_CREATOR plus five user profile metadata fields, if they are necessary
			if(!is_array($id_reqs)) { // if we're dealing with a blank template...
				if($cell == _formulize_DE_CALC_CREATOR) {
					$importSet[7]['creator'] = $column;
				}
				if($cell == _formulize_DE_IMPORT_USERNAME) {
					$importSet[7]['username'] = $column;
				}
				if($cell == _formulize_DE_IMPORT_FULLNAME) {
					$importSet[7]['fullname'] = $column;
				}
				if($cell == _formulize_DE_IMPORT_PASSWORD) {
					$importSet[7]['password'] = $column;
				}
				if($cell == _formulize_DE_IMPORT_EMAIL) {
					$importSet[7]['email'] = $column;
				}
				if($cell == _formulize_DE_IMPORT_REGCODE) {
					$importSet[7]['regcode'] = $column;
				}
			} else {
				if($cell == _formulize_DE_IMPORT_IDREQCOL) {
					$importSet[7]['idreqs'] = $column;
				}
			}



                $mapIndex = -1;

	            $elements = count($form_elementsq);
	            for($element = 0; $element < $elements; $element++)
	            {
                    $caption = $form_elementsq[$element]["ele_caption"];
                    $colheading = $form_elementsq[$element]["ele_colhead"];

                    //echo "$cell == $caption<br>";
                    if($cell == $caption OR $cell == trans($caption) OR $cell == $colheading OR $cell == trans($colheading)) // trans caption added by jwe, June 29 2006, colheading added by jwe July 13, 2006
                    {
                    	$mapIndex = $element;
                        
	                    // links?
                        switch($form_elementsq[$element]["ele_type"])
	                    {
	                        case "select":
	                            $ele_value = unserialize($form_elementsq[$element]["ele_value"]);
	                            $options = $ele_value[2];

	                            if(strstr($options, "#*=:*")) 
                                {
									//echo "linked select<br>";
	                                $parts = explode("#*=:*", $options);

	                                /*echo "form: " . $parts[0] . 
	                                    "<br>element: " . $parts[1];*/
                                    
	                                $sql = "SELECT * FROM " . $xoopsDB->prefix("formulize") . 
	                                    " WHERE id_form='" . $parts[0] . "'" .
                                        " AND ele_caption='" . mysql_real_escape_string($parts[1]) . "'";
									$form_elementlinkq = q($sql);
	                                if($form_elementlinkq == null)
	                                {
	                                    exit("<br><b>STOPPED</b> <i>" . "form: " .
                                        	$parts[0] . ", element: " . $parts[1] . "<br>$sql");
	                                }
                                    else
                                    {
										//var_dump($form_elementlinkq);
							            $importSet[5][1][$column] = array(
                                        	$parts[0], $parts[1], $form_elementlinkq[0]);
                                    }            
								}                                    
                            	break;
						}                        
                        break;                            
                    }
				}

                $mapped[] = $mapIndex;

            }                        

            $importSet[6] = $mapped; 
        }                        
	}
}


function importCsvValidate(& $importSet, $id_reqs, $regfid)
{
	global $errors, $xoopsDB;


    $output = "** <b>Validating</b><br><b>Csv</b>: " . $importSet[0][0] . "<br>" .
        "<b>Form</b>: <i>name</i>: " . $importSet[2] .
        ", <i>id</i>: " . $importSet[4] . "<br><ol>";


    $links = count($importSet[6]);
    for($link = 0; $link < $links; $link++)
    {
        if($importSet[6][$link] == -1)
        {
			// Created by, Creation date, Modified by, Modification date, plus profile form special columns
            if(!($importSet[3][$link] == _formulize_DE_CALC_CREATOR
            	|| $importSet[3][$link] == _formulize_DE_CALC_CREATEDATE
	            || $importSet[3][$link] == _formulize_DE_CALC_MODIFIER
            	|| $importSet[3][$link] == _formulize_DE_CALC_MODDATE
	            || $importSet[3][$link] == _formulize_DE_IMPORT_USERNAME
	            || $importSet[3][$link] == _formulize_DE_IMPORT_FULLNAME
	            || $importSet[3][$link] == _formulize_DE_IMPORT_PASSWORD
	            || $importSet[3][$link] == _formulize_DE_IMPORT_EMAIL
	            || $importSet[3][$link] == _formulize_DE_IMPORT_REGCODE
	            || $importSet[3][$link] == _formulize_DE_IMPORT_IDREQCOL))
            {
                $errors[] = "<li>column <b>" .
                    $importSet[3][$link] .
                    "</b> not found in form.</li>";
            }                    
        }
	}


    //$rowCount = 2;
    $rowCount = 1;
    
    $currentFilePosition = ftell($importSet[1]);
    
    while(!feof($importSet[1]))
    {
        $row = fgetcsv($importSet[1], 4096);

        if(is_array($row))
        {
            $rowCount++;
            
            //var_dump($row);        
                    
            $links = count($importSet[6]);
            for($link = 0; $link < $links; $link++)
            {
	            $cell_value = $row[$link];
                
			if($cell_value == "") {
				if($importSet[6][$link] == -1) { // this is not a found column in the form
					// disallow profile metdata fields from being blank
					if(!is_array($id_reqs) AND $importSet[4] == $regfid) {
						if($link == $importSet[7]['username'] OR $link == $importSet[7]['fullname']  OR $link == $row[$importSet[7]['password']] OR $link == $importSet[7]['email'] OR $link == $importSet[7]['regcode']) {
							$errors[] = "<li>line " . $rowCount . ", column " . $importSet[3][$link] . ",<br> <b>Field cannot be blank</b></li>";
						}
					} elseif(is_array($id_reqs) AND $link == $importSet[7]['idreqs']) {
						$errors[] = "<li>line " . $rowCount . ",<br> <b>No ID number specified</b></li>";
					} 
				}
			} else { 
				// check columns not present in form...
	                if($importSet[6][$link] == -1)
	                {
                        if($importSet[3][$link] == _formulize_DE_CALC_CREATOR)
	                    {
	                        $uid = getUserId($cell_value);
                            if($uid == 0)
	                        {
	                            $errors[] = "<li>line " . $rowCount . 
	                                ", column " . $importSet[3][$link] .
	                                ",<br> <b>user not found</b>: " . $cell_value . "</li>"; 
	                        }
	                    }

				// check validity of account creation stuff
				if(!is_array($id_reqs) AND $importSet[4] == $regfid) {
					include_once XOOPS_ROOT_PATH . "/modules/reg_codes/include/functions.php";
					$stop = userCheck($row[$importSet[7]['username']], $row[$importSet[7]['email']], $row[$importSet[7]['password']], $row[$importSet[7]['password']], $row[$importSet[7]['regcode']]);
					if($stop) {
						$errors[] = "<li>line " . $rowCount . ",<br> <b>Invalid Registration Data:</b> $stop</li>";
					}

				}

				// check validity of the idreqs
				if(is_array($id_reqs) AND $link == $importSet[7]['idreqs']) {
					if(!in_array($cell_value, $id_reqs)) {
						$errors[] = "<li>line " . $rowCount . ",<br> <b>Invalid ID number specified</b></li>";
					}
				}
	                }
				// check columns from form...
	                else
	                {
	                    $element = & $importSet[5][0][$importSet[6][$link]];

                        switch($element["ele_type"])
	                    {
	                        case "select":
                                if($importSet[5][1][$link]) 
                                {
									// Linked element
                                    // echo "Linked element<br>";

                                    $linkElement = $importSet[5][1][$link];
	                                $ele_value = unserialize($element["ele_value"]);

	                                if($ele_value[1])
	                                {
                                        // Multiple options                                
                                        //echo "Multiple options<br>";                                

	                                    $items = explode("\n", $cell_value);
							$all_valid_options = getElementOptions($linkElement[0], $linkElement[1]);
	                                    foreach($items as $item)
	                                    {
	                                        $item_value = trim($item);

//	                                        $id_req = getRecordID($linkElement[0], $linkElement[1], $item_value);
//	                                        if($id_req == 0)

								if(!in_array($item_value, $all_valid_options)) 
	                                        {

								  $foundit = false;
         								foreach($all_valid_options as $thisoption) {
         									if(trim($item_value) == stripslashes(trim(trans($thisoption)))) { // stripslashes is necessary only because the data contains slashes in the database (which it should not, so this should be removed when that is fixed)
         										$foundit = true;
											break;
         									}
         								}
	     								if(!$foundit) {
       	                                            $errors[] = "<li>line " . $rowCount . 
       	                                                ", column " . $importSet[3][$link] .
       	                                                ",<br> <b>found</b>: " . $item_value .  
       	                                                ", <b>was expecting</b>: " . stripslashes(implode(", ", $all_valid_options)) . "</li>"; 
									}
	                                        }
	                                    }                                        
	                                }
	                                else
	                                {
                                        // Single option
                                        //echo "Single option<br>";                                
							$all_valid_options = getElementOptions($linkElement[0], $linkElement[1]);

//                                        $id_req = getRecordID($linkElement[0], $linkElement[1], $cell_value);
//                                        if($id_req == 0)
							  	if(!in_array($cell_value, $all_valid_options)) 
	                                        {
         								foreach($all_valid_options as $thisoption) {
         									if(trim($cell_value) == stripslashes(trim(trans($thisoption)))) { // stripslashes is necessary only because the data contains slashes in the database (which it should not, so this should be removed when that is fixed)
											break 2;
         									}
         								}
                                                      	$errors[] = "<li>line " . $rowCount . 
                                                            ", column " . $importSet[3][$link] .
                                                            ",<br> <b>found</b>: " . $cell_value . 
                                                            ", <b>was expecting</b>: " . stripslashes(implode(", ", $all_valid_options)) . "</li>"; 
                                        }
	                                }
                                }
								else
                                {
									// Not-Linked element
                                    //echo "Not-Linked element<br>";                                

	                                $ele_value = unserialize($element["ele_value"]);

							// handle fullnames or usernames
							$temparraykeys = array_keys($ele_value[2]);
							if($temparraykeys[0] == "{FULLNAMES}" OR $temparraykeys[0] == "{USERNAMES}") { // ADDED June 18 2005 to handle pulling in usernames for the user's group(s) -- updated for real live use September 6 2006
								if($temparraykeys[0] == "{FULLNAMES}") { $nametype = "name"; }
								if($temparraykeys[0] == "{USERNAMES}") { $nametype = "uname"; }
								if(!isset($fullnamelist)) {
									$fullnamelistq = q("SELECT uid, $nametype FROM " . $xoopsDB->prefix("users"));
									static $fullnamelist = array();
									foreach($fullnamelistq as $thisname) {
										$fullnamelist[$thisname['uid']] = $thisname[$nametype];
									}
								}
								if($ele_value[1]) { // multiple
									$items = explode("\n", $cell_value);
								} else {
									$items = array(0=>$cell_value);
								}
								foreach($items as $item) {
									$uids = array_keys ($fullnamelist, $item);
									if(count($uids) == 0) {
										$errors[] = "<li>line " . $rowCount . 
                                                            ", column " . $importSet[3][$link] .
                                                            ",<br> <b>Name</b>: " . $item . 
                                                            " <b>is not a valid name for a user</b></li>";
										break;
									}
								}
								break;
							}




	                                if($ele_value[1])
	                                {
                                        // Multiple options                                
                                        //echo "Multiple options<br>";                                

	                                	$options = $ele_value[2];
                                        
                                        //var_dump($options);
                                        
	                                    $items = explode("\n", $cell_value);
	                                    foreach($items as $item)
	                                    {
	                                        $item_value = trim($item);

								if(!in_array($item_value, $options, true)) // last option causes strict matching by type, June 29, 2006
		      	                          {
         								$foundit = false;
         								foreach($options as $thisoption=>$default_value) {
										if(get_magic_quotes_gpc()) { $thisoption = stripslashes($thisoption); }
         									if(trim($item_value) == trim(trans($thisoption))) {
         										$foundit = true;
											break;
         									}
         								}
         								if(!$foundit) {
        
         	                                            for(reset($options); $key = key($options); next($options))
         	                                            {
										if(get_magic_quotes_gpc()) { $key = stripslashes($key); }
         	                                                $result[] = $key;
         	                                            }
         	                                            
         	                                            $errors[] = "<li>line " . $rowCount . 
         	                                                ", column " . $importSet[3][$link] .
         	                                                ",<br> <b>found</b>: " . $item_value . 
         	                                                ", <b>was expecting</b>: " . implode(", ", $result) . "</li>"; 
									}
	                                        }
	                                    }                                        
	                                }
	                                else
	                                {
                                        // Single option
                                        //echo "Single option<br>";                                

							// id_req request commented, does not make any sense! Why should the prior existence of a record that contains this value have anything to do with validating the contents of the cell? -- June 29, 2006
                                        //$id_req = getRecordID($importSet[4], $importSet[3][$link], $cell_value);
                                        //if($id_req == 0)
                                        //{
                                            $options = $ele_value[2];
                                            if(!in_array($cell_value, $options, true)) // last option causes strict matching by type, June 29, 2006
                                            {

            						// then do a check against the translated options -- added June 29, 2006
            						foreach($options as $thisoption=>$default_value) {
									if(get_magic_quotes_gpc()) { $thisoption = stripslashes($thisoption); }
            							if(trim($cell_value) == trim(trans($thisoption))) {
            								break 2;
            							}
            						}
                                           
	                                        for(reset($options); $key = key($options); next($options))
	                                        {
								if(get_magic_quotes_gpc()) { $key = stripslashes($key); }
	                                            $result[] = $key;
	                                        }
                                            
                                            $errors[] = "<li>line " . $rowCount . 
                                                ", column " . $importSet[3][$link] .
                                                ",<br> <b>found</b>: " . $cell_value . 
                                                ", <b>was expecting</b>: " . implode(", ", $result) . "</li>"; 
                                        }
	                                }
                                }
	                            break;                    

	                        case "checkbox":
	                            $options = unserialize($element["ele_value"]);

						

                                //echo $cell_value . "," . $options;
                                $items = explode("\n", $cell_value);
                                foreach($items as $item)
                                {
                                    $item_value = trim($item);
							if(!in_array($item_value, $options, true)) // last option causes strict matching by type, June 29, 2006
	      	                          {
								$foundit = false;
								$hasother = false;
								foreach($options as $thisoption=>$default_value) {
									if(get_magic_quotes_gpc()) { $thisoption = stripslashes($thisoption); }
									if(trim($item_value) == trim(trans($thisoption))) {
										$foundit = true;
									}
									if(preg_match('/\{OTHER\|+[0-9]+\}/', $thisoption)) { $hasother = true; }
								}
								if(!$foundit AND !$hasother) {
                                                      $keys_output = "";
                                                      for(reset($options); $key = key($options); next($options))
                                                      {
	             						    if(get_magic_quotes_gpc()) { $key = stripslashes($key); }
                                                          if($keys_output != "")
                                                          {
                                                              $keys_output .= ", ";
                                                          }
                                                          $keys_output .= $key;
                                                      }

                                                      $errors[] = "<li>line " . $rowCount . 
                                                          ", column " . $importSet[3][$link] .
                                                          ",<br> <b>found</b>: " . $item_value . 
                                                          ", <b>was expecting</b>: { " . $keys_output . " }</li>";
								}
                                       }
					}
	                            break;                    

	                        case "radio":
	                            $options = unserialize($element["ele_value"]);

	                                //echo $cell_value . ",";
						//print_r($options);
                                if(!in_array($cell_value, $options, true)) // last option causes strict matching by type, June 29, 2006
                                {

						// then do a check against the translated options -- added June 29, 2006
						$foundit = false;
						$hasother = false;
						foreach($options as $thisoption=>$default_value) {
							if(get_magic_quotes_gpc()) { $thisoption = stripslashes($thisoption); }
							if(trim($cell_value) == trim(trans($thisoption))) {
								$foundit = true;
							}
							if(preg_match('/\{OTHER\|+[0-9]+\}/', $thisoption)) { $hasother = true; }
						}

						if(!$foundit AND !$hasother) {
	                                    $keys_output = "";
      	                              for(reset($options); $key = key($options); next($options))
            	                        {
							    if(get_magic_quotes_gpc()) { $key = stripslashes($key); }
                  	                      if($keys_output != "")
                        	                {
                              	              $keys_output .= ", ";
                                    	    }
	                                        $keys_output .= $key;
      	                              }
	
      	                              $errors[] = "<li>line " . $rowCount . 
            	                            ", column " . $importSet[3][$link] .
                  	                      ",<br> <b>found</b>: " . $cell_value . 
                        	                ", <b>was expecting</b>: { " . $keys_output . " }</li>";
                              	} 
	            		   }
			               break;                    
				                            
	                        case "date":
                                //echo "date: " . $cell_value . "<br>";                                
                                $date_value = date("Y-m-d", strtotime($cell_value));
                                if($date_value == "")
                                { 
                                    $errors[] = "<li>line " . $rowCount . 
                                        ", column " . $importSet[3][$link] .
                                        ",<br> <b>found</b>: " . $cell_value . 
                                        ", <b>was expecting</b>: YYYY-mm-dd</li>";
                                }
	                            break;
	                            
	                        case "yn":
                                //echo "yn: " . $cell_value . "<br>";
                                if(is_numeric($cell_value))
                                {
                                    if(!($cell_value == 1 || $cell_value == 2))
                                    {
	                                    $errors[] = "<li>line " . $rowCount . 
	                                        ", column " . $importSet[3][$link] .
	                                        ",<br> <b>found</b>: " . $cell_value . 
	                                        ", <b>was expecting</b>: { 1, 2, " . _formulize_TEMP_QYES . ", " . _formulize_TEMP_QNO . " }</li>";
									}                                            
                                }
                                else
                                {
                                	$yn_value = strtoupper($cell_value);

                                    if(!($yn_value == strtoupper(_formulize_TEMP_QYES) || $yn_value == strtoupper(_formulize_TEMP_QNO))) // changed to use language constants, June 29, 2006
                                    {
	                                    $errors[] = "<li>line " . $rowCount . 
	                                        ", column " . $importSet[3][$link] .
	                                        ",<br> <b>found</b>: " . $cell_value . 
	                                        ", <b>was expecting</b>: { 1, 2, " . _formulize_TEMP_QYES . ", " . _formulize_TEMP_QNO . " }</li>";
									}                                            
								}                                                                   
	                            break;
	                    }
	                }
                }
            }
        }
    }

    fseek($importSet[1], $currentFilePosition);

    echo $output . "</ol>";
    
    return (empty($errors)) ? true : false;
}


function importCsvProcess(& $importSet, $id_reqs, $regfid)
{
	global $xoopsDB, $xoopsUser, $xoopsConfig, $myts;		// $xoopsDB is required by q
    if(!$myts) { $myts =& MyTextSanitizer::getInstance(); }

    echo "<b>** Importing</b><br><br>"; 
    /*echo "<b>Importing</b><br>" . 
        "<i>from</i> <b>Csv</b>: " . $importSet[0][0] . "<br>" .
        "<i>to</i> <b>Form</b>: <i>name</i>: " . $importSet[2] .
        ", <i>id</i>: " . $importSet[4] . "<br>";*/

    $arrayDate = getdate(time());
    $dateMonth = $arrayDate["mon"]; 
    $dateDay = $arrayDate["mday"];
    $dateYear = $arrayDate["year"];
    $form_date = "$dateYear-$dateMonth-$dateDay";
     
    $form_uid = "0"; 
    $form_proxyid = $xoopsUser->getVar('uid');
     $form_creation_date = "$dateYear-$dateMonth-$dateDay";

	// lock formulize_form
	if($regfid == $importSet[4]) { // only lockup reg codes table if we're dealing with a profile form, in which case we assume reg codes is installed and the table exists
		$xoopsDB->query("LOCK TABLES " . $xoopsDB->prefix("formulize_form") . " WRITE, ". $xoopsDB->prefix("users") . " WRITE, " . $xoopsDB->prefix("reg_codes") . " WRITE, " . $xoopsDB->prefix("group_users_link") . " WRITE, ". $xoopsDB->prefix("modules") . " READ, " . $xoopsDB->prefix("config") . " READ");
	} else {
		$xoopsDB->query("LOCK TABLES " . $xoopsDB->prefix("formulize_form") . " WRITE, ". $xoopsDB->prefix("users") . " READ");
	}
    

    //$rowCount = 2;
    $rowCount = 1;
    $other_values = array();
    while(!feof($importSet[1]))
    {
        $row = fgetcsv($importSet[1], 4096);

        if(is_array($row))
        {

            $rowCount++;
		$this_id_req = "";
		if(is_array($id_reqs)) { // get the id_req if necessary.  will happen regardless of position of idreq column
			$this_id_req = $row[$importSet[7]['idreqs']];
		}


            $links = count($importSet[6]);
            for($link = 0; $link < $links; $link++)
            {
                if($importSet[6][$link] == -1)
                {
                    if($importSet[3][$link] == _formulize_DE_CALC_CREATOR)
                    {
                        $form_uid = getUserId($row[$link]);
                    }
                }
		}			
            
            //var_dump($row);        

                    
            // get the current max id_req
		if(!$this_id_req) {
	           $max_id_reqq = q("SELECT MAX(id_req) FROM " . $xoopsDB->prefix("formulize_form"));
      	     $max_id_req = $max_id_reqq[0]["MAX(id_req)"] + 1;
		} else {
			$max_id_req = $this_id_req;
			// get the uid and creation date too
			$member_handler =& xoops_gethandler('member');
			$this_metadata = getMetaData($this_id_req, $member_handler);
			$this_uid = $this_metadata['created_by_uid'];
			$this_creation_date = $this_metadata['created'];
		}

		// if this is the registration form, and we're making new entries, then handle the creation of the necessary user account
		// need to get the five userprofile fields from the form, $importSet[7] contains the keys for them -- email, username, fullname, password, regcode
		if($regfid == $importSet[4] AND !is_array($id_reqs)) {
			$up_regcode = $row[$importSet[7]['regcode']];
			$up_username = $row[$importSet[7]['username']];
			$up_fullname = $row[$importSet[7]['fullname']];
			$up_password = $row[$importSet[7]['password']];
			$up_email = $row[$importSet[7]['email']];

			$tz = $xoopsConfig['default_TZ'];

			list($newid, $actkey) = createMember(array('regcode'=>'', 'approval'=>false, 'user_viewemail'=>0, 'uname'=>$up_username, 'name'=>$up_fullname, 'email'=>$up_email, 'pass'=>$up_password, 'timezone_offset'=>$tz));
			processCode($up_regcode, $newid);

			$form_uid = $newid; // put in new user id here

		}
		

            echo "line $rowCount, id $max_id_req<br>";

            $links = count($importSet[6]);
            for($link = 0; $link < $links; $link++)
            {
			$all_valid_options = false; // used as a flag to indicate whether we're dealing with a linked selectbox or not, since if we are, that is the only case where we don't want to do HTML special chars on the value

                if($importSet[6][$link] != -1)
                {
                    $element = & $importSet[5][0][$importSet[6][$link]];

                    $id_form = $importSet[4];
                    
                    $row_value = $row[$link];
     
                    if($row_value != "")
                    {
	                    switch($element["ele_type"])
	                    {
	                        case "select":
	                            if($importSet[5][1][$link]) 
	                            {
	                                // Linked element
	                                //echo "Linked element<br>";

	                                $linkElement = $importSet[5][1][$link];
	                                $ele_value = unserialize($element["ele_value"]);
						$all_valid_options = getElementOptions($linkElement[0], $linkElement[1]);

	                                if($ele_value[1])
	                                {
	                                    // Multiple options                                
	                                    //echo "Multiple options<br>";                                

	                                    $element_value = $linkElement[0] . "#*=:*" . 
	                                        $linkElement[1] . "#*=:*";
	                                    
	                                    $items = explode("\n", $row_value);
							
                                        $is_first = true;
	                                    foreach($items as $item)
	                                    {
	                                        $item_value = trim($item);

								if(!in_array($item_value, $all_valid_options)) {
									foreach($all_valid_options as $thisoption) {
										if(trim($item_value) == trim(trans($thisoption))) {
											$item_value = $thisoption;
										}
									}
								}		


	                                        $ele_id = getElementID($linkElement[0], $linkElement[1], $item_value);
	                                        
											if($is_first)
                                            {
												$is_first = false;
                                            }
                                            else
                                            {
		                                        $element_value .= "[=*9*:";
                                            }
                                            
	                                        $element_value .= $ele_id;
	                                    }
	                                    
	                                    $row_value = $element_value;                                        
	                                }
	                                else
	                                {
	                                    // Single option
	                                    //echo "Single option<br>";                                

								if(!in_array($row_value, $all_valid_options)) {
									foreach($all_valid_options as $thisoption) {
										if(trim($row_value) == trim(trans($thisoption))) {
											$row_value = $thisoption;
										}
									}
								}


	                                    $ele_id = getElementID($linkElement[0], $linkElement[1], $row_value);
	                                    
	                                    $row_value = $linkElement[0] . "#*=:*" . 
	                                        $linkElement[1] . "#*=:*" . $ele_id;
	                                }
	                            }
	                            else
	                            {
	                                // Not-Linked element
	                                //echo "Not-Linked element<br>";                                

	                                $ele_value = unserialize($element["ele_value"]);

							// handle fullnames or usernames
							$temparraykeys = array_keys($ele_value[2]);
							if($temparraykeys[0] == "{FULLNAMES}" OR $temparraykeys[0] == "{USERNAMES}") { // ADDED June 18 2005 to handle pulling in usernames for the user's group(s) -- updated for real live use September 6 2006
								if($temparraykeys[0] == "{FULLNAMES}") { $nametype = "name"; }
								if($temparraykeys[0] == "{USERNAMES}") { $nametype = "uname"; }
								if(!isset($fullnamelist)) {
									$fullnamelistq = q("SELECT uid, $nametype FROM " . $xoopsDB->prefix("users"));
									static $fullnamelist = array();
									foreach($fullnamelistq as $thisname) {
										$fullnamelist[$thisname['uid']] = $thisname[$nametype];
									}
								}
								if($ele_value[1]) { // multiple
									$items = explode("\n", $row_value);
								} else {
									$items = array(0=>$row_value);
								}
								$numberOfNames = 0;
								$row_value = "";
								foreach($items as $item) {
									$uids = array_keys ($fullnamelist, $item);
									foreach($uids as $uid) { // already validated so we don't have to worry about not finding the right stuff
										$row_value .= "*=+*:" . $uid; // setup the format to simply be right for inserting into DB.
										$numberOfNames++;
									} 
								}
								if($numberOfNames == 1) { // single entries are not supposed to have the separator at the front
									$row_value = substr_replace($row_value, "", 0, 5);
								}
								break;
							}


	                                if($ele_value[1])
	                                {
                                        // Multiple options                                
                                        //echo "Multiple options<br>";                                

	                                    $element_value = "";
	                                     $options = $ele_value[2];
	                                    $items = explode("\n", $row_value);
	                                    foreach($items as $item)
	                                    {
	                                        $item_value = trim($item);
      							if(!in_array($item_value, $options, true)) // last option causes strict matching by type, June 29, 2006
      	      	                          {
      								foreach($options as $thisoption=>$default_value) {
      									if(trim($item_value) == trim(trans($thisoption))) {
      										$item_value = $thisoption;
      										break;
      									}
      								}
      							  }

	                                        $element_value .= "*=+*:" . $item_value;
	                                    }
	                                    
	                                    $row_value = $element_value;                                        
	                                }
	                                else
	                                {
                                        // Single option
                                        //echo "Single option<br>";                                

							$options = $ele_value[2];

							if(!in_array($row_value, $options, true)) // last option causes strict matching by type, June 29, 2006
	      	                          {
								foreach($options as $thisoption=>$default_value) {
									if(trim($row_value) == trim(trans($thisoption))) {
										$row_value = $thisoption;
										break;
									}
								}
							  }
	                                }
 	                            }
	                            break;
	                            
	                        case "checkbox":
                                //echo "checkbox<br>";                                
                                
						$options = unserialize($element["ele_value"]);
	                                
                                $element_value = "";
                                 
                                $items = explode("\n", $row_value);
                                foreach($items as $item)
                                {
                                    $item_value = trim($item);
							if(!in_array($item_value, $options, true)) // last option causes strict matching by type, June 29, 2006
	      	                          {
								$foundit = false;
								$hasother = false;
								foreach($options as $thisoption=>$default_value) {
									if(trim($item_value) == trim(trans($thisoption))) {
										$item_value = $thisoption;
										$foundit=true;
										break;
									}
									if(preg_match('/\{OTHER\|+[0-9]+\}/', $thisoption)) { 
										$hasother = $thisoption;
									}										
								}
             						if($foundit) {
             	                                    $element_value .= "*=+*:" . $item_value;
             						} elseif($hasother) {
             							$other_values[] = "INSERT INTO " . $xoopsDB->prefix("formulize_other") . " (id_req, ele_id, other_text) VALUES (\"$max_id_req\", \"" . $element["ele_id"] . "\", \"" . gpcaddslashes(trim($item_value)) . "\")";
             							$element_value .= "*=+*:" . $hasother;
             						} else {
             							print "ERROR: INVALID TEXT FOUND FOR A CHECKBOX ITEM -- $item_value -- IN ROW:<BR>";
             							print_r($row);
             							print "<br><br>";
             						}
							} else {
								$element_value .= "*=+*:" . $item_value;
							}
                                }
                                
                                $row_value = $element_value;                                        
	                            break;

					// radio added June 29, 2006
					case "radio":
						$options = unserialize($element["ele_value"]);
	                                if(!in_array($row_value, $options, true)) // last option causes strict matching by type, June 29, 2006
      	                          {
							$foundit = false;
							$hasother = false;
							foreach($options as $thisoption=>$default_value) {
								if(trim($row_value) == trim(trans($thisoption))) {
									$row_value = $thisoption;
									$foundit = true;
									break;
								}
								if(preg_match('/\{OTHER\|+[0-9]+\}/', $thisoption)) { 
									$hasother = $thisoption;
								}										
							}
							if(!$foundit AND $hasother) {
								$other_values[] = "INSERT INTO " . $xoopsDB->prefix("formulize_other") . " (id_req, ele_id, other_text) VALUES (\"$max_id_req\", \"" . $element["ele_id"] . "\", \"" . gpcaddslashes(trim($row_value)) . "\")";
								$row_value = $hasother;
							} elseif(!$foundit) {
								print "ERROR: INVALID TEXT FOUND FOR A RADIO BUTTON ITEM -- $row_value -- IN ROW:<BR>";
								print_r($row);
								print "<br><br>";
							}
						  }
						break;

	                        case "date":
                                $row_value = date("Y-m-d", strtotime($row_value)); 
	                            break;

	                            
	                        case "yn":
                                //echo "yn: " . $row_value . "<br>";
                                if(!is_numeric($row_value))
                                {
                                	$yn_value = strtoupper($row_value);

                                    if($yn_value == "YES")
										$row_value = 1;                                    
                                    else if($yn_value == "NO")
										$row_value = 2;                                    
								}                                                                   
	                            break;
	                    }                            

			if($this_id_req) {
				// NOTE ABOUT UPDATING ENTRIES:
				// if an entry is deleted after someone has downloaded an update template, and then another entry is created and it gets the same id_req, and that entry has been created in this same form, then the upload process will overwrite the existing entry.
				// assumption is this will never happen since only senior users will be uploading and they will be taking care that entries are not deleted!
				$cleanFirst = "DELETE FROM " . $xoopsDB->prefix("formulize_form") . " WHERE id_form=$id_form AND id_req=$max_id_req AND ele_caption=\"" . mysql_real_escape_string(formformCaption($element["ele_caption"])) . "\"";
				if(IMPORT_WRITE) {
					if(!$result = $xoopsDB->queryF($cleanFirst)) 
      	                  {
            	                exit("<br><b>STOPPED</b> failed to clean data, SQL: $cleanFirst");
                  	      }
				}
				$form_uid = $this_uid;
				$form_creation_date = $this_creation_date;
			} 
	                    if(!$all_valid_options) { $row_value = $myts->htmlSpecialChars($row_value); }

	                    $insertElement = "INSERT INTO " . $xoopsDB->prefix("formulize_form") .  
	                        " (id_form, id_req, ele_type, ele_caption, ele_value," . 
	                        " date, uid, proxyid, creation_date)" .
	                        " VALUES ($id_form, $max_id_req, '" . 
	                        $element["ele_type"] . "', '" . 
	                        mysql_real_escape_string(formformCaption($element["ele_caption"])) . "', '" . 
	                        mysql_real_escape_string(trim($row_value)) . "', '" . 
	                        $form_date . "', " .
	                        $form_uid . ", " .
	                        $form_proxyid . ", '" .
	                        $form_creation_date .
	                        "')";

	                    if(IMPORT_WRITE)
	                    {
	                        if(!$result = $xoopsDB->queryF($insertElement)) 
	                        {
	                            exit("<br><b>STOPPED</b> failed to insert data, SQL: $insertElement");
	                        }

	                        //echo "<i>id</i>: " . $xoopsDB->getInsertId() . "<br>";                         
	                    }
	                    else
	                    {                        
	                        echo "<br>" . $insertElement . "<br>";                        
	                    }                                                
	                }
				}
            }
        }
    }

	// unlock tables
	$xoopsDB->query("UNLOCK TABLES");

    // insert all the other values that were recorded
    foreach($other_values as $other) {
		if(!$result = $xoopsDB->query($other)) {
			print "ERROR: could not insert 'other' value: $other<br>";
		}
    }

}

// deprecated...not used currently
function getRecordID($id_form, $ele_caption, $ele_value)
{
	global $xoopsDB;

    $sql = "SELECT id_req FROM " . $xoopsDB->prefix("formulize_form") .  
        " WHERE id_form='" . $id_form . "'" .
        " AND ele_caption='" . mysql_real_escape_string(formformCaption($ele_caption)) . "'" .
        " AND ele_value='" . mysql_real_escape_string($ele_value) . "'";

    //echo $sql . "<br>";
    
    if($result = $xoopsDB->query($sql))
    {
		$item = $xoopsDB->fetchArray($result);
        if(@$item["id_req"])
        {
			return $item["id_req"];
        }                         
    }
        
    return 0;
}


function getElementID($id_form, $ele_caption, $ele_value)
{
	global $xoopsDB, $myts;
	if(!$myts) { $myts =& MyTextSanitizer::getInstance(); }


    $sql = "SELECT ele_id FROM " . $xoopsDB->prefix("formulize_form") .  
        " WHERE id_form='" . $id_form . "'" .
        " AND ele_caption='" . mysql_real_escape_string(formformCaption($ele_caption)) . "'" .
        " AND ele_value='" . mysql_real_escape_string($myts->htmlSpecialChars($ele_value)) . "'";

    //echo $sql . "<br>";
    
    if($result = $xoopsDB->query($sql))
    {
		$item = $xoopsDB->fetchArray($result);
        if(@$item["ele_id"])
        {
			return $item["ele_id"];
        }                         
    }
        
    return 0;
}


function getElementOptions($id_form, $ele_caption)
{
	global $xoopsDB, $myts;
	if(!$myts) { $myts =& MyTextSanitizer::getInstance(); }

    $sql = "SELECT ele_value FROM " . $xoopsDB->prefix("formulize_form") .  
        " WHERE id_form='" . $id_form . "'" .
        " AND ele_caption='" . mysql_real_escape_string(formformCaption($ele_caption)) . "'";

	$res = $xoopsDB->query($sql);
	while ($item = $xoopsDB->fetchArray($res)) {
		$result[] = $myts->undoHtmlSpecialChars($item["ele_value"]);
	}
	return $result;
}


function getUserID($stringName)
{
	global $xoopsDB, $xoopsUser;

    $sql = "SELECT uid FROM " . $xoopsDB->prefix("users") .  
        " WHERE uname='" . $stringName . "'";

    if($result = $xoopsDB->query($sql))
    {
		$item = $xoopsDB->fetchArray($result);
        if(@$item["uid"])
        {
			return $item["uid"];
        }                         
    }
    else // or, if no username match found, get the first matching full name -- added June 29, 2006
    {
	    $sql = "SELECT uid FROM " . $xoopsDB->prefix("users") .  
        " WHERE name='" . $stringName . "'";

	    if($result = $xoopsDB->query($sql))
	    {
			$item = $xoopsDB->fetchArray($result);
		        if(@$item["uid"])
		        {
				return $item["uid"];
		        }                         
	    }
    }

	// instead of returning 0, return the current user's ID -- added June 29, 2006
    return $xoopsUser->getVar('uid');
	
        
}


function formformCaption($ele_caption)
{
	return str_replace("'", "`", $ele_caption);
}


function importCsvDebug(& $importSet)
{
    $output = "** <b>Csv</b>: " . $importSet[0][0] . "<br>" .
        "<b>Form</b>: <i>name</i>: " . $importSet[2] .
        ", <i>id</i>: " . $importSet[4] . "<br>";

    $output .= "<table border=\"1\">";

    $output .= "<tr><td><i>exists</i></td>" .
    	"<td><i>caption</i></td>" .
    	"<td><i>id</i></td>" .
        "<td><i>type</i></td>" .
        "<td><i>link</i></td></tr>";

    $links = count($importSet[6]);
    for($link = 0; $link < $links; $link++)
    {
	    $output .= "<tr valign=\"top\">";

        if($importSet[6][$link] == -1)
        {
		    $output .= "<td>" .
				"N" .
	            "</td><td colspan=\"4\">" .
            	$importSet[3][$link] .
		    	"</td>";
        }
        else
        {
            $element = $importSet[5][0][$importSet[6][$link]];
            
		    $output .= "<td>" .

	            "Y" .
	            "</td><td>" .
	            $importSet[3][$link] .
	            "</td><td>" .
	            $importSet[6][$link] .
	            "</td><td>" .
	            $element["ele_type"];
            
		    $output .= "<td>";
            
            switch($element["ele_type"])
            {
	            case "select":
	                $ele_value = unserialize($element["ele_value"]);
	                $options = $ele_value[2];

	                if(is_array($options))
	                {                       
	                    $keys_output = "";
	                    for(reset($options); $key = key($options); next($options))
	                    {
	                        if($keys_output != "")
	                        {
	                            $keys_output .= ", ";
	                        }
	                        $keys_output .= $key;
	                    }

	                    $output .= "{ " . $keys_output . " }";
	                }
	                else
	                {                       
						if($importSet[5][1][$link]) 
                        {
							$linkElement = $importSet[5][1][$link];
                            
                        	$output .= "form: " . $linkElement[0] . 
                            	"<br>element: " . $linkElement[1] .
                            	"<br>type: " . $linkElement[2]['ele_type'];
                        } 
                        else
                        {
	                    	$output .= $options;
                        }
	                }
					break;                    

	            case "checkbox":
	                $options = unserialize($element["ele_value"]);

	                if(is_array($options))
	                {                       
	                    $keys_output = "";
	                    for(reset($options); $key = key($options); next($options))
	                    {
	                        if($keys_output != "")
	                        {
	                            $keys_output .= ", ";
	                        }
	                        $keys_output .= $key;
	                    }

	                    $output .= "{ " . $keys_output . " }";
	                }
	                else
	                {                       
	                    $output .= $options;
	                }
					break;                    

	            case "radio":
	                $options = unserialize($element["ele_value"]);

	                if(is_array($options))
	                {                       
	                    $keys_output = "";
	                    for(reset($options); $key = key($options); next($options))
	                    {
	                        if($keys_output != "")
	                        {
	                            $keys_output .= ", ";
	                        }
	                        $keys_output .= $key;
	                    }

	                    $output .= "{ " . $keys_output . " }";
	                }
	                else
	                {                       
	                    $output .= $options;
	                }
					break;                    
            }

		    $output .= "</td>";

		    $output .= "</tr>";
        }
    }
    
    echo $output . "</table>";
}
?>