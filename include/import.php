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
// load the formulize language constants if they haven't been loaded already
	if ( file_exists(XOOPS_ROOT_PATH."/modules/formulize/language/".$xoopsConfig['language']."/main.php") ) {
		include_once XOOPS_ROOT_PATH."/modules/formulize/language/".$xoopsConfig['language']."/main.php";
	} else {
		include_once XOOPS_ROOT_PATH."/modules/formulize/language/english/main.php";
	}


global $xoopsDB, $xoopsUser;
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
//print "<body><center>"; 
print "<body>"; 



define("IMPORT_WRITE", true);
define("IMPORT_DEBUG", false);

//define("IMPORT_WRITE", false);
//define("IMPORT_DEBUG", true);

$errors = array();

// Test if the filename of the temporary uploaded csv is empty
//$csv_name = @$_POST["csv_name"];
$csv_name = @$HTTP_POST_FILES['csv_name']['tmp_name'];
if($csv_name != "")
{
	//$csv_name = "../import/$csv_name.csv";
	importCsv(array($HTTP_POST_FILES['csv_name']['name'], $csv_name));
}
else
{
?>
	<center>
	<form method="post" ENCTYPE="multipart/form-data">
    Csv Name
    <!--
    <input type="text" name="csv_name">
    -->
	<input type="file" name="csv_name" size="40" class="textinput" />

	<br>
    <input type="submit" value="Go">
    </form>
	</center>
<?
}
//print "Hello World";



//print "</center></body>";
print "</body>";
print "</HTML>";






// internal is an array consisting of:
//	[0] file id
//	[1] (array) column headings
//	[2] formulize form id 
//	[3] formulize form elements
//	[4] (array) column headings to formulize form elements
//function importCsv(& $importSets)
function importCsv($csv_name)
{
	global $errors;
    

	//set_time_limit(0);
    
	// Initialize import rules for Activity Booking System
	$importSet = array();
	//$importSet[] = "../import/$csv_name.csv";
	$importSet[] = $csv_name;

	importCsvSetup($importSet);

	if(IMPORT_DEBUG)
    {
		importCsvDebug($importSet);
    }

    if(importCsvValidate($importSet))
    {
        importCsvProcess($importSet);
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
    
    
    //importCsvCleanup($importSet);

    echo "<br><br><div><b><a href=\"http://" . $_ENV["HTTP_HOST"] . "/" . 
    	$_ENV["SCRIPT_NAME"] . "?" . 
    	$_ENV["QUERY_STRING"] . "\">Back</a></b></div>"; 
}


//function importCsvSetup(& $importSets)
function importCsvSetup(& $importSet)
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
    /*$form_idq = q("SELECT id_form FROM " . $xoopsDB->prefix("form_id") . 
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
        $form_elementsq = q("SELECT * FROM " . $xoopsDB->prefix("form") . 
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

                $mapIndex = -1;

	            $elements = count($form_elementsq);
	            for($element = 0; $element < $elements; $element++)
	            {
                    $caption = $form_elementsq[$element]["ele_caption"];

                    //echo "$cell == $caption<br>";
                    if($cell == $caption)
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
                                    
	                                $sql = "SELECT * FROM " . $xoopsDB->prefix("form") . 
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


function importCsvValidate(& $importSet)
{
	global $errors;


    $output = "** <b>Validating</b><br><b>Csv</b>: " . $importSet[0][0] . "<br>" .
        "<b>Form</b>: <i>name</i>: " . $importSet[2] .
        ", <i>id</i>: " . $importSet[4] . "<br><ol>";


    $links = count($importSet[6]);
    for($link = 0; $link < $links; $link++)
    {
        if($importSet[6][$link] == -1)
        {
			// Created by, Creation date, Modified by, Modification date
            if(!($importSet[3][$link] == "Created by"
            	|| $importSet[3][$link] == "Creation date"
	            || $importSet[3][$link] == "Modified by"
            	|| $importSet[3][$link] == "Modification date"))
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
                
                if($cell_value != "")
				{                  
	                if($importSet[6][$link] == -1)
	                {
                        if($importSet[3][$link] == "Created by")
	                    {
	                        $uid = getUserId($cell_value);
                            if($uid == 0)
	                        {
	                            $errors[] = "<li>line " . $rowCount . 
	                                ", column " . $importSet[3][$link] .
	                                ",<br> <b>user not found</b>: " . $cell_value . "</li>"; 
	                        }
	                    }
	                }
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
	                                    foreach($items as $item)
	                                    {
	                                        $item_value = trim($item);
	                                        $id_req = getRecordID($linkElement[0], $linkElement[1], $item_value);
	                                        if($id_req == 0)
	                                        {
	                                            $errors[] = "<li>line " . $rowCount . 
	                                                ", column " . $importSet[3][$link] .
	                                                ",<br> <b>found</b>: " . $item_value .  
	                                                ", <b>was expecting</b>: " . implode(", ", getElementOptions($linkElement[0], $linkElement[1])) . "</li>"; 
	                                        }
	                                    }                                        
	                                }
	                                else
	                                {
                                        // Single option
                                        //echo "Single option<br>";                                

                                        $id_req = getRecordID($linkElement[0], $linkElement[1], $cell_value);
                                        if($id_req == 0)
                                        {
                                            $errors[] = "<li>line " . $rowCount . 
                                                ", column " . $importSet[3][$link] .
                                                ",<br> <b>found</b>: " . $cell_value . 
                                                ", <b>was expecting</b>: " . implode(", ", getElementOptions($linkElement[0], $linkElement[1])) . "</li>"; 
                                        }
	                                }
                                }
								else
                                {
									// Not-Linked element
                                    //echo "Not-Linked element<br>";                                

	                                $ele_value = unserialize($element["ele_value"]);

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

	                                        if(!isset($options[$item_value]))
	                                        {
	                                            for(reset($options); $key = key($options); next($options))
	                                            {
	                                                $result[] = $key;
	                                            }
	                                            
	                                            $errors[] = "<li>line " . $rowCount . 
	                                                ", column " . $importSet[3][$link] .
	                                                ",<br> <b>found</b>: " . $item_value . 
	                                                ", <b>was expecting</b>: " . implode(", ", $result) . "</li>"; 
	                                        }
	                                    }                                        
	                                }
	                                else
	                                {
                                        // Single option
                                        //echo "Single option<br>";                                

                                        $id_req = getRecordID($importSet[4], $importSet[3][$link], $cell_value);
                                        if($id_req == 0)
                                        {
                                            $options = $ele_value[2];
                                            
	                                        for(reset($options); $key = key($options); next($options))
	                                        {
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
                                if(!in_array($cell_value, $options))
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

                                    $errors[] = "<li>line " . $rowCount . 
                                        ", column " . $importSet[3][$link] .
                                        ",<br> <b>found</b>: " . $cell_value . 
                                        ", <b>was expecting</b>: { " . $keys_output . " }</li>";
                                }
	                            break;                    

	                        case "radio":
	                            $options = unserialize($element["ele_value"]);

                                //echo $cell_value . "," . $options;
                                if(!in_array($cell_value, $options))
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

                                    $errors[] = "<li>line " . $rowCount . 
                                        ", column " . $importSet[3][$link] .
                                        ",<br> <b>found</b>: " . $cell_value . 
                                        ", <b>was expecting</b>: { " . $keys_output . " }</li>";
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
	                                        ", <b>was expecting</b>: { 1, 2, YES, NO }</li>";
									}                                            
                                }
                                else
                                {
                                	$yn_value = strtoupper($cell_value);

                                    if(!($yn_value == "YES" || $yn_value == "NO"))
                                    {
	                                    $errors[] = "<li>line " . $rowCount . 
	                                        ", column " . $importSet[3][$link] .
	                                        ",<br> <b>found</b>: " . $cell_value . 
	                                        ", <b>was expecting</b>: { 1, 2, YES, NO }</li>";
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


function importCsvProcess(& $importSet)
{
	global $xoopsDB, $xoopsUser;		// $xoopsDB is required by q
    
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
    $form_proxyid = "0"; 
    $form_creation_date = "$dateYear-$dateMonth-$dateDay";
    

    //$rowCount = 2;
    $rowCount = 1;
    
    while(!feof($importSet[1]))
    {
        $row = fgetcsv($importSet[1], 4096);

        if(is_array($row))
        {
            $rowCount++;

            $links = count($importSet[6]);
            for($link = 0; $link < $links; $link++)
            {
                if($importSet[6][$link] == -1)
                {
                    if($importSet[3][$link] == "Created by")
                    {
                        $form_uid = getUserId($row[$link]);
                        $form_proxyid = $xoopsUser->getVar('uid');
                    }
                }
			}			
            
            //var_dump($row);        
                    
            // get the current max id_req
            $max_id_reqq = q("SELECT MAX(id_req) FROM " . $xoopsDB->prefix("form_form"));
            $max_id_req = $max_id_reqq[0]["MAX(id_req)"] + 1;

            echo "line $rowCount, id $max_id_req<br>";

            $links = count($importSet[6]);
            for($link = 0; $link < $links; $link++)
            {
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

	                                if($ele_value[1])
	                                {
                                        // Multiple options                                
                                        //echo "Multiple options<br>";                                

	                                    $element_value = "";
	                                     
	                                    $items = explode("\n", $row_value);
	                                    foreach($items as $item)
	                                    {
	                                        $item_value = trim($item);

	                                        $element_value .= "*=+*:" . $item_value;
	                                    }
	                                    
	                                    $row_value = $element_value;                                        
	                                }
	                                /*else
	                                {
                                        // Single option
                                        //echo "Single option<br>";                                
	                                }*/
 	                            }
	                            break;
	                            
	                        case "checkbox":
                                echo "checkbox<br>";                                
                                
                                $element_value = "";
                                 
                                $items = explode("\n", $row_value);
                                foreach($items as $item)
                                {
                                    $item_value = trim($item);

                                    $element_value .= "*=+*:" . $item_value;
                                }
                                
                                $row_value = $element_value;                                        
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
	                    
	                    $insertElement = "INSERT INTO " . $xoopsDB->prefix("form_form") .  
	                        " (id_form, id_req, ele_type, ele_caption, ele_value," . 
	                        " date, uid, proxyid, creation_date)" .
	                        " VALUES ($id_form, $max_id_req, '" . 
	                        $element["ele_type"] . "', '" . 
	                        mysql_real_escape_string(formformCaption($element["ele_caption"])) . "', '" . 
	                        mysql_real_escape_string($row_value) . "', '" . 
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
}


function getRecordID($id_form, $ele_caption, $ele_value)
{
	global $xoopsDB;

    $sql = "SELECT id_req FROM " . $xoopsDB->prefix("form_form") .  
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
	global $xoopsDB;

    $sql = "SELECT ele_id FROM " . $xoopsDB->prefix("form_form") .  
        " WHERE id_form='" . $id_form . "'" .
        " AND ele_caption='" . mysql_real_escape_string(formformCaption($ele_caption)) . "'" .
        " AND ele_value='" . mysql_real_escape_string($ele_value) . "'";

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
	global $xoopsDB;

    $sql = "SELECT ele_value FROM " . $xoopsDB->prefix("form_form") .  
        " WHERE id_form='" . $id_form . "'" .
        " AND ele_caption='" . mysql_real_escape_string(formformCaption($ele_caption)) . "'";

	$res = $xoopsDB->query($sql);
	while ($item = $xoopsDB->fetchArray($res)) {
		$result[] = $item["ele_value"];
	}
	return $result;
}


function getUserID($stringName)
{
	global $xoopsDB;

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
        
    return 0;
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