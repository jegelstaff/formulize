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
##  Author of this file: Freeform Solutions                                  ##
##  Project: Formulize                                                       ##
###############################################################################

// internal is an array consisting of:
//  [0] file id
//  [1] (array) column headings
//  [2] formulize form id
//  [3] formulize form elements
//  [4] (array) column headings to formulize form elements
//  [8] formulize form handle

//function importCsv(& $importSets, $id_reqs, $regfid)
function importCsv($csv_name, $id_reqs, $regfid, $validateOverride) {
    global $errors;

    $importSet = array();
    $importSet[] = $csv_name;

    importCsvSetup($importSet, $id_reqs); // will be false on blank templates and user profile templates

    if ((is_array($id_reqs) AND !isset($importSet[7]['idreqs'])) OR ($regfid == $importSet[4] AND !is_array($id_reqs) AND (!isset($importSet[7]['username']) OR !isset($importSet[7]['fullname']) OR !isset($importSet[7]['password']) OR !isset($importSet[7]['email']) OR !isset($importSet[7]['regcode'])))) {
        // necessary metadata columns not present in file
        echo "<br><b>csv not imported!</b><br>Required metadata columns (ie: user who made entry, ID numbers, or account information) not present in the file.";
    } else {
        if (IMPORT_DEBUG) {
            importCsvDebug($importSet);
        }

        if (importCsvValidate($importSet, $id_reqs, $regfid, $validateOverride)) {
            importCsvProcess($importSet, $id_reqs, $regfid, $validateOverride);

            echo "<script type=\"text/javascript\">\n";
            echo "window.opener.document.controls.forcequery.value = 1;\n";
            echo "window.opener.showLoading();\n";
            echo "</script>\n";
        } else {
            echo "<br><b>csv not imported!</b>";
            if (!empty($errors)) {
                echo "<ol>";
                foreach ($errors as $error) {
                    echo $error;
                }
                echo "</ol>";
            }
        }
    } // end of if metadata columns not present

    echo "<b>** Finished</b><br><br>";

    echo "<br><br>";
    echo "<b><a href=\"\" onclick=\"javascript:history.back(-1);return false;\">" . _formulize_DE_IMPORT_BACK . "</a></b></div>";
}


function importCsvSetup(&$importSet, $id_reqs) {
    global $xoopsDB;

    // First cell on the first line of the csv file contained the form name.
    // This is now provided through formulize variable $fid which is now the id,
    // therefore a lookup is required to go from number to name, instead of the
    // original name to number.
    global $fid;

    // 1. verify that files exist
    if (!($importSet[1] = fopen($importSet[0][1], "r"))) {
        exit("<b>STOPPED</b> csv file <i>" . $importSet[0][0] . "</i> not found.");
    }

    // 2. get the first row containing form name
    // 3. get the second row containing column names
    if (feof($importSet[1])) {
        exit("<b>STOPPED</b> <i>column names</i> not found.");
    } else {
        //$importSet[2] = fgetcsv($importSet[1], 4096);
        //$importSet[2] = $importSet[2][0];
        $importSet[2] = getFormTitle($fid);
        $importSet[3] = fgetcsv($importSet[1], 99999);
        foreach ($importSet[3] as $id3=>$value3) {
            $importSet[3][$id3] = str_replace(chr(19).chr(16), "", $value3);
        }
    }

    $importSet[4] = $fid;

    $form_handler = xoops_getmodulehandler('forms', 'formulize');
    $formObject = $form_handler->get($fid);
    $importSet[8] = $formObject->getVar('form_handle');

    // 5. get the form column ids and process linked elements
    if ($importSet[4]) {
        $form_elementsq = q("SELECT * FROM " . $xoopsDB->prefix("formulize") .
            " WHERE id_form='" . $importSet[4] . "'");

        if ($form_elementsq == null) {
            exit("<br><b>STOPPED</b> formulize form <i>" . $importSet[2] . "</i> elements not found.");
        } else {
            $importSet[5] = array($form_elementsq, array());

            $mapped = array();

            $columns = count($importSet[3]);
            for ($column = 0; $column < $columns; $column++) {
                $cell = $importSet[3][$column];

            // need to record location of: _formulize_DE_CALC_CREATOR plus five user profile metadata fields, if they are necessary
            if (!is_array($id_reqs)) {
                // if we're dealing with a blank template...
                if ($cell == _formulize_DE_CALC_CREATOR) {
                    $importSet[7]['creator'] = $column;
                }
                if ($cell == _formulize_DE_IMPORT_USERNAME) {
                    $importSet[7]['username'] = $column;
                }
                if ($cell == _formulize_DE_IMPORT_FULLNAME) {
                    $importSet[7]['fullname'] = $column;
                }
                if ($cell == _formulize_DE_IMPORT_PASSWORD) {
                    $importSet[7]['password'] = $column;
                }
                if ($cell == _formulize_DE_IMPORT_EMAIL) {
                    $importSet[7]['email'] = $column;
                }
                if ($cell == _formulize_DE_IMPORT_REGCODE) {
                    $importSet[7]['regcode'] = $column;
                }
                if ($cell == _formulize_DE_IMPORT_NEWENTRYID) {
                    // columns with this exact heading will have this entry id used
                    $importSet[7]['usethisentryid'] = $column;
                }
            } else {
                if ($cell == _formulize_DE_IMPORT_IDREQCOL) {
                    $importSet[7]['idreqs'] = $column;
                }
            }

            $mapIndex = -1;

            $elements = count($form_elementsq);
                for ($element = 0; $element < $elements; $element++) {
                    $caption = $form_elementsq[$element]["ele_caption"];
                    $colheading = $form_elementsq[$element]["ele_colhead"];

                    // trans caption added by jwe, June 29 2006, colheading added by jwe July 13, 2006
                    if ($cell == $caption OR $cell == trans($caption) OR $cell == $colheading OR $cell == trans($colheading)) {
                        $mapIndex = $element;

                        // links?
                        switch($form_elementsq[$element]["ele_type"]) {
                            case "select":
                                $ele_value = unserialize($form_elementsq[$element]["ele_value"]);
                                $options = $ele_value[2];

                                if (!is_array($options)) {
                                    if (strstr($options, "#*=:*")) {
                                        $parts = explode("#*=:*", $options);

                                        $sql = "SELECT * FROM " . $xoopsDB->prefix("formulize") .
                                            " WHERE id_form='" . $parts[0] . "'" .
                                            " AND ele_handle='" . formulize_db_escape($parts[1]) . "'";
                                        $form_elementlinkq = q($sql);
                                        if ($form_elementlinkq == null) {
                                            exit("<br><b>STOPPED</b> <i>" . "form: " .
                                            $parts[0] . ", element: " . $parts[1] . "<br>$sql");
                                        } else {
                                            //var_dump($form_elementlinkq);
                                            $importSet[5][1][$column] = array(
                                            $parts[0], $parts[1], $form_elementlinkq[0]);
                                        }
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


function importCsvValidate(&$importSet, $id_reqs, $regfid, $validateOverride=false) {
    if ($validateOverride) {
        return true;
    }
    $elementHandler = xoops_getmodulehandler('elements', 'formulize');
    global $errors, $xoopsDB;

    $output = "** <b>Validating</b><br><b>Csv</b>: " . $importSet[0][0] . "<br>" .
        "<b>Form</b>: <i>name</i>: " . $importSet[2] .
        ", <i>id</i>: " . $importSet[4] . "<br><ol>";

    $links = count($importSet[6]);
    $GLOBALS['formulize_ignoreColumnsOnImport'] = array();
    for ($link = 0; $link < $links; $link++) {
        if ($importSet[6][$link] == -1) {
            // Created by, Creation date, Modified by, Modification date, plus profile form special columns
            if (!($importSet[3][$link] == _formulize_DE_CALC_CREATOR
                || $importSet[3][$link] == _formulize_DE_CALC_CREATEDATE
                || $importSet[3][$link] == _formulize_DE_CALC_MODIFIER
                || $importSet[3][$link] == _formulize_DE_CALC_MODDATE
                || $importSet[3][$link] == _formulize_DE_IMPORT_USERNAME
                || $importSet[3][$link] == _formulize_DE_IMPORT_FULLNAME
                || $importSet[3][$link] == _formulize_DE_IMPORT_PASSWORD
                || $importSet[3][$link] == _formulize_DE_IMPORT_EMAIL
                || $importSet[3][$link] == _formulize_DE_IMPORT_REGCODE
                || $importSet[3][$link] == _formulize_DE_IMPORT_IDREQCOL
                || $importSet[3][$link] == _formulize_DE_IMPORT_NEWENTRYID))
            {
                print "<p>Warning: column <b>" . $importSet[3][$link] . "</b> was not found in form.</p>";
                    $GLOBALS['formulize_ignoreColumnsOnImport'][$link] = true;
            }
        }
    }

    $rowCount = 1;
    $currentFilePosition = ftell($importSet[1]);
    // a container for any entry id overrides that a user has set in the spreadsheet
    $useTheseEntryIds = array();
    while (!feof($importSet[1])) {
        $row = fgetcsv($importSet[1], 99999);

        if (is_array($row) AND count($row) > 1) {
            $rowCount++;
            $links = count($importSet[6]);
            for ($link = 0; $link < $links; $link++) {
                if (isset($GLOBALS['formulize_ignoreColumnsOnImport'][$link])) {
                    continue;
                }

                if ($link == ($link-1)) {
                    $cell_value = str_replace(chr(19).chr(16), "", $row[$link]);
                } else {
                    $cell_value = $row[$link];
                }

                if (isset($importSet[5][0][$importSet[6][$link]])) { // if this is an element, then extract that element from the array
                    $element = $importSet[5][0][$importSet[6][$link]];
                } else {
                    $element = array();
                }

                if ($cell_value == "") {
                    if ($importSet[6][$link] == -1) {
                        // this is not a found column in the form
                        // disallow profile metdata fields from being blank
                        if (!is_array($id_reqs) AND $importSet[4] == $regfid) {
                            if ($link == $importSet[7]['username'] OR $link == $importSet[7]['fullname']  OR $link == $row[$importSet[7]['password']] OR $link == $importSet[7]['email'] OR $link == $importSet[7]['regcode']) {
                                $errors[] = "<li>line " . $rowCount . ", column " . $importSet[3][$link] . ",<br> <b>Field cannot be blank</b></li>";
                            }
                        } elseif (is_array($id_reqs) AND $link == $importSet[7]['idreqs']) {
                            $errors[] = "<li>line " . $rowCount . ",<br> <b>No ID number specified</b></li>";
                        }
                    }

                    // need to respect required setting
                    if (isset($element['ele_req'])) {
                        if ($element['ele_req']) {
                            $errors[] = "<li>line " . $rowCount .
                                ", column " . $importSet[3][$link] .
                                ",<br> <b>This column requires a value</b> (cell was blank)</li>";
                        }
                    }
                } else {
                    // check columns not present in form...
                    if ($importSet[6][$link] == -1) {
                        if ($importSet[3][$link] == _formulize_DE_CALC_CREATOR) {
                            $uid = getUserId($cell_value);
                            if ($uid == 0) {
                                $errors[] = "<li>line " . $rowCount .
                                    ", column " . $importSet[3][$link] .
                                    ",<br> <b>user not found</b>: " . $cell_value . "</li>";
                            }
                        }

                        // check validity of account creation stuff
                        if (!is_array($id_reqs) AND $importSet[4] == $regfid) {
                            include_once XOOPS_ROOT_PATH . "/modules/reg_codes/include/functions.php";
                            $stop = userCheck($row[$importSet[7]['username']], $row[$importSet[7]['email']], $row[$importSet[7]['password']], $row[$importSet[7]['password']], $row[$importSet[7]['regcode']]);
                            if ($stop) {
                                $errors[] = "<li>line " . $rowCount . ",<br> <b>Invalid Registration Data:</b> $stop</li>";
                            }
                        }

                        // check validity of the idreqs
                        if (is_array($id_reqs) AND $link == $importSet[7]['idreqs']) {
                            if (!in_array($cell_value, $id_reqs)) {
                                $errors[] = "<li>line " . $rowCount . ",<br> <b>Invalid ID number specified</b></li>";
                            }
                        }

                        // check validity of entry ids if a special entry_ids column is included
                        // store the entry ids that are specified, and then we'll check for the existence of any of them after we're done looping
                        if (isset($importSet[7]['usethisentryid']) AND $link == $importSet[7]['usethisentryid']) {
                            $useTheseEntryIds[] = $cell_value;
                        }
                    } else {
                        // check columns from form
                        switch($element["ele_type"]) {
                            case "select":
                                $ele_value = unserialize($element["ele_value"]);
                                if (isset($importSet[5][1][$link]) AND !strstr($cell_value, ",") AND (!is_numeric($cell_value) OR $cell_value < 10000000))
                                {
                                    // Linked element, but allow entries with commas to pass through unvalidated, and also allow through numeric values with no commas, if they are really big (assumption is big numbers are some kind of special entry_id reference, as in the case of UofT)
                                    $linkElement = $importSet[5][1][$link];

                                    if ($ele_value[1] AND !($ele_value['snapshot'] AND $ele_value[16])) {
                                        // Multiple options
                                        //echo "Multiple options<br>";

                                        $items = explode("\n", $cell_value);
                                        //$all_valid_options = getElementOptions($linkElement[0], $linkElement[1]);
                                        list($all_valid_options, $all_valid_options_ids) = getElementOptions($linkElement[2]['ele_handle'], $linkElement[2]['id_form']);
                                        foreach ($items as $item)
                                        {
                                            $item_value = trim($item);

                                            if (!in_array($item_value, $all_valid_options)) {
                                                $foundit = false;
                                                foreach ($all_valid_options as $thisoption) {
                                                    if (trim($item_value) == stripslashes(trim(trans($thisoption)))) { // stripslashes is necessary only because the data contains slashes in the database (which it should not, so this should be removed when that is fixed)
                                                        $foundit = true;
                                                        break;
                                                    }
                                                }
                                                if (!$foundit) {
                                                    $some_options = array_slice($all_valid_options, 0, 20);
                                                    $errors[] = "<li>line " . $rowCount .
                                                        ", column " . $importSet[3][$link] .
                                                        ",<br> <b>found</b>: " . $item_value .
                                                        ", <b>was expecting values such as</b>: " .
                                                        stripslashes(implode(", ", $some_options)) . "</li>";
                                                }
                                            }
                                        }
                                    } elseif(!($ele_value['snapshot'] AND $ele_value[16])) {
                                        // Single option
                                        list($all_valid_options, $all_valid_options_ids) = getElementOptions($linkElement[2]['ele_handle'], $linkElement[2]['id_form']);
                                        if (!in_array($cell_value, $all_valid_options)) {
                                            foreach ($all_valid_options as $thisoption) {
                                                if (trim($cell_value) == stripslashes(trim(trans($thisoption)))) { // stripslashes is necessary only because the data contains slashes in the database (which it should not, so this should be removed when that is fixed)
                                                    break 2;
                                                }
                                            }
                                            $errors[] = "<li>line " . $rowCount .
                                                ", column " . $importSet[3][$link] .
                                                ",<br> <b>found</b>: " . $cell_value .
                                                ", <b>was expecting</b>: " . stripslashes(implode(", ", $all_valid_options)) . "</li>";
                                        }
                                    }
                                } elseif (!strstr($cell_value, ",") AND (!is_numeric($cell_value) OR $cell_value < 10000000))
                                {
                                    // Not-Linked element
                                    $ele_value = unserialize($element["ele_value"]);

                                    // handle fullnames or usernames
                                    $temparraykeys = array_keys($ele_value[2]);
                                    if ($temparraykeys[0] === "{FULLNAMES}" OR $temparraykeys[0] === "{USERNAMES}") {
                                        // ADDED June 18 2005 to handle pulling in usernames for the user's group(s) -- updated for real live use September 6 2006
                                        if ($temparraykeys[0] === "{FULLNAMES}") {
                                            $nametype = "name";
                                        }
                                        if ($temparraykeys[0] === "{USERNAMES}") {
                                            $nametype = "uname";
                                        }
                                        if (!isset($fullnamelist)) {
                                            $fullnamelistq = q("SELECT uid, $nametype FROM " . $xoopsDB->prefix("users"));
                                            static $fullnamelist = array();
                                            foreach ($fullnamelistq as $thisname) {
                                                $fullnamelist[$thisname['uid']] = $thisname[$nametype];
                                            }
                                        }
                                        if ($ele_value[1]) { // multiple
                                            $items = explode("\n", $cell_value);
                                        } else {
                                            $items = array(0=>$cell_value);
                                        }
                                        foreach ($items as $item) {
                                            if (is_numeric($item)) {
                                                if (!isset($fullnamelist[$item])) {
                                                    $errors[] = "<li>line " . $rowCount .
                                                        ", column " . $importSet[3][$link] .
                                                        ",<br> <b>User Id</b>: " . $item .
                                                        " <b>is not a valid id for a user</b></li>";
                                                }
                                            } else {
                                                $uids = array_keys ($fullnamelist, $item);
                                                if (count($uids) == 0) {
                                                    $errors[] = "<li>line " . $rowCount .
                                                        ", column " . $importSet[3][$link] .
                                                        ",<br> <b>Name</b>: " . $item .
                                                        " <b>is not a valid name for a user</b></li>";
                                                    break;
                                                }
                                            }
                                        }
                                        break;
                                    }
                                    if ($ele_value[1]) {
                                        // Multiple options
                                        $options = $ele_value[2];
                                        $items = explode("\n", $cell_value);
                                        foreach ($items as $item) {
                                            $item_value = trim($item);

                                            if (!in_array($item_value, $options, true)) {
                                                // last option causes strict matching by type
                                                $foundit = false;
                                                foreach ($options as $thisoption=>$default_value) {
                                                
                                                    if (trim($item_value) == trim(trans($thisoption))) {
                                                        $foundit = true;
                                                        break;
                                                    }
                                                }
                                                if (!$foundit) {
                                                    for (reset($options); $key = key($options); next($options)) {
                                                        if (get_magic_quotes_gpc()) {
                                                            $key = stripslashes($key);
                                                        }
                                                        $result[] = $key;
                                                    }

                                                    $errors[] = "<li>line " . $rowCount .
                                                        ", column " . $importSet[3][$link] .
                                                        ",<br> <b>found</b>: " . $item_value .
                                                        ", <b>was expecting</b>: " . implode(", ", $result) . "</li>";
                                                }
                                            }
                                        }
                                    } else {
                                        // Single option
                                        $options = $ele_value[2];
                                        if (!in_array($cell_value, $options, true)) {
                                            // last option causes strict matching by type
                                            // then do a check against the translated options
                                            foreach ($options as $thisoption=>$default_value) {
                                                if (get_magic_quotes_gpc()) {
                                                    $thisoption = stripslashes($thisoption);
                                                }
                                                if (trim($cell_value) == trim(trans($thisoption))) {
                                                    break 2;
                                                }
                                            }

                                            for (reset($options); $key = key($options); next($options)) {
                                                if (get_magic_quotes_gpc()) {
                                                    $key = stripslashes($key);
                                                }
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
                            $options = $options[2];
                            if(strstr($cell_value, "\n")) {
                            $items = explode("\n", $cell_value);
                            } else {
                                $items = explode(",", $cell_value);
                            }
                            foreach ($items as $item) {
                                $item_value = trim($item);
                                if (!in_array($item_value, $options, true)) {
                                    // last option causes strict matching by type
                                    $foundit = false;
                                    $hasother = false;
                                    foreach ($options as $thisoption=>$default_value) {
                                        if (get_magic_quotes_gpc()) {
                                            $thisoption = stripslashes($thisoption);
                                        }
                                        if (trim($item_value) == trim(trans($thisoption))) {
                                            $foundit = true;
                                        }
                                        if (preg_match('/\{OTHER\|+[0-9]+\}/', $thisoption)) {
                                            $hasother = true;
                                        }
                                    }
                                    if (!$foundit AND !$hasother) {
                                        $keys_output = implode(', ', array_keys($options));
                                        /*for (reset($options); $key = key($options); next($options)) {
                                            if (get_magic_quotes_gpc()) {
                                                $key = stripslashes($key);
                                            }
                                            if ($keys_output != "") {
                                                $keys_output .= ", ";
                                            }
                                            $keys_output .= $key;
                                        }*/

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
                            if (!in_array($cell_value, $options, true)) {
                                // last option causes strict matching by type
                                // then do a check against the translated options
                                $foundit = false;
                                $hasother = false;
                                foreach ($options as $thisoption=>$default_value) {
                                    if (get_magic_quotes_gpc()) {
                                        $thisoption = stripslashes($thisoption);
                                    }
                                    if (trim($cell_value) == trim(trans($thisoption))) {
                                        $foundit = true;
                                    }
                                    if (preg_match('/\{OTHER\|+[0-9]+\}/', $thisoption)) { $hasother = true; }
                                }

                                if (!$foundit AND !$hasother) {
                                    $keys_output = "";
                                    for (reset($options); $key = key($options); next($options)) {
                                        if (get_magic_quotes_gpc()) {
                                            $key = stripslashes($key);
                                        }
                                        if ($keys_output != "") {
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
                            $date_value = date("Y-m-d", strtotime($cell_value));
                            if ($date_value == "") {
                                $errors[] = "<li>line " . $rowCount .
                                    ", column " . $importSet[3][$link] .
                                    ",<br> <b>found</b>: " . $cell_value .
                                    ", <b>was expecting</b>: "._DATE_DEFAULT."</li>";
                            }
                            break;


                            case "yn":
                            if (is_numeric($cell_value)) {
                                if (!($cell_value == 1 || $cell_value == 2)) {
                                    $errors[] = "<li>line " . $rowCount .
                                        ", column " . $importSet[3][$link] .
                                        ",<br> <b>found</b>: " . $cell_value .
                                        ", <b>was expecting</b>: { 1, 2, " . _formulize_TEMP_QYES . ", " . _formulize_TEMP_QNO . " }</li>";
                                }
                            } else {
                                $yn_value = strtoupper($cell_value);

                                if (!($yn_value == strtoupper(_formulize_TEMP_QYES) || $yn_value == strtoupper(_formulize_TEMP_QNO))) {
                                    // changed to use language constants, June 29, 2006 {
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

    // check validity of any entry ids the user has set
    if (count($useTheseEntryIds) > 0) {
        global $xoopsDB;
        $checkIdsSQL = "SELECT entry_id FROM ".$xoopsDB->prefix("formulize_".$importSet[8]) . " WHERE entry_id IN (".implode(",",$useTheseEntryIds).")";
        $checkIdsRes = $xoopsDB->query($checkIdsSQL);
        while ($checkIdsArray = $xoopsDB->fetchArray($checkIdsRes)) {
            $errors[] = "<li><b>Entry id ".$checkIdsArray['entry_id']." is already in use.</b>  You cannot import new data with an existing entry id.</li>";
        }
    }

    fseek($importSet[1], $currentFilePosition);
    echo $output . "</ol>";
    return (empty($errors)) ? true : false;
}


function importCsvProcess(& $importSet, $id_reqs, $regfid, $validateOverride) {
    global $xoopsDB, $xoopsUser, $xoopsConfig, $myts; // $xoopsDB is required by q
    $elementHandler = xoops_getmodulehandler('elements', 'formulize');
    if (!$myts) {
        $myts =& MyTextSanitizer::getInstance();
    }

    echo "<b>** Importing</b><br><br>";
    $form_uid = "0";

    global $override_import_proxyid;
    if ( $override_import_proxyid ) {
        $form_proxyid = $override_import_proxyid;
    } else {
        $form_proxyid = $xoopsUser->getVar('uid');
    }

    $form_handler = xoops_getmodulehandler('forms','formulize');
    $formObject = $form_handler->get($importSet[4]);
    $data_handler = new formulizeDataHandler($importSet[4]);

    // lock formulize_form -- note that every table we use needs to be locked, so linked selectbox lookups may fail
    if ($regfid == $importSet[4]) {
        // only lockup reg codes table if we're dealing with a profile form, in which case we assume reg codes is installed and the table exists
            $lockSQL = "LOCK TABLES " . $xoopsDB->prefix("formulize_".$importSet[8]) . " WRITE, ".
            $xoopsDB->prefix("users") . " WRITE, ".
            $xoopsDB->prefix("formulize_entry_owner_groups")." WRITE, ".
            $xoopsDB->prefix("reg_codes") . " WRITE, ".
            $xoopsDB->prefix("groups_users_link") . " WRITE, ".
            $xoopsDB->prefix("modules") . " READ, ".
            $xoopsDB->prefix("config") . " READ, ".
            $xoopsDB->prefix("formulize") . " READ, ".
            $xoopsDB->prefix("formulize_id")." READ, ".
            $xoopsDB->prefix("formulize_saved_views")." READ, ".
            $xoopsDB->prefix("formulize_group_filters")." READ, ".
            $xoopsDB->prefix("formulize_".$formObject->getVar('form_handle'))." WRITE";
            // include the revisions table if necessary
            if($formObject->getVar('store_revisions') AND $form_handler->revisionsTableExists($formObject->getVar('id_form'))) {
                $lockSQL .= ", ".$xoopsDB->prefix("formulize_".$formObject->getVar('form_handle'))."_revisions WRITE";    
            }
    } else {
            $lockSQL = "LOCK TABLES " . $xoopsDB->prefix("formulize_".$importSet[8]) . " WRITE, ".
            $xoopsDB->prefix("users") . " READ, ".
            $xoopsDB->prefix("formulize_entry_owner_groups") . " WRITE, ".
            $xoopsDB->prefix("groups_users_link") . " READ, ".
            $xoopsDB->prefix("formulize") . " READ, ".
            $xoopsDB->prefix("formulize_id")." READ, ".
            $xoopsDB->prefix("formulize_saved_views")." READ, ".
            $xoopsDB->prefix("formulize_group_filters")." READ, ".
            $xoopsDB->prefix("formulize_".$formObject->getVar('form_handle'))." WRITE";
            // include the revisions table if necessary
            if($formObject->getVar('store_revisions') AND $form_handler->revisionsTableExists($formObject->getVar('id_form'))) {
                $lockSQL .= ", ".$xoopsDB->prefix("formulize_".$formObject->getVar('form_handle'))."_revisions WRITE";    
            }
    }
    $xoopsDB->query($lockSQL);

    $rowCount = 1;
    $other_values = array();
    $usersMap = array();
    $entriesMap = array();
    $notEntriesList = array();
    while (!feof($importSet[1])) {
        $row = fgetcsv($importSet[1], 99999);

        if (is_array($row) AND count($row) > 1) {
            $rowCount++;
            $this_id_req = "";
            if (is_array($id_reqs)) { // get the id_req if necessary.  will happen regardless of position of idreq column
                $this_id_req = $row[$importSet[7]['idreqs']];
            }

            $links = count($importSet[6]);
            for ($link = 0; $link < $links; $link++) {
                if (isset($GLOBALS['formulize_ignoreColumnsOnImport'][$link])) {
                    continue;
                }
                if ($importSet[6][$link] == -1) {
                    if ($importSet[3][$link] == _formulize_DE_CALC_CREATOR) {
                        $form_uid = getUserId($row[$link]);
                    }
                }
            }

            // get the current max id_req
            if (!$this_id_req) {
                $max_id_reqq = q("SELECT MAX(entry_id) FROM " . $xoopsDB->prefix("formulize_".$importSet[8]));
                $max_id_req = $max_id_reqq[0]["MAX(entry_id)"] + 1;
            } else {
                $max_id_req = $this_id_req;
                // get the uid and creation date too
                $member_handler =& xoops_gethandler('member');
                $this_metadata = getMetaData($this_id_req, $member_handler, $importSet[4]); // importSet[4] is id_form (fid)
                $this_uid = $this_metadata['created_by_uid'];
                $this_creation_date = $this_metadata['created'];
            }

            // if this is the registration form, and we're making new entries, then handle the creation of the necessary user account
            // need to get the five userprofile fields from the form, $importSet[7] contains the keys for them -- email, username, fullname, password, regcode
            if ($regfid == $importSet[4] AND !is_array($id_reqs)) {
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

            $links = count($importSet[6]);
            $fieldValues = array();
            $newEntryId = "";
            for ($link = 0; $link < $links; $link++) {
                // used as a flag to indicate whether we're dealing with a linked selectbox or not, since if we are, that is the only case where we don't want to do HTML special chars on the value // deprecated in 3.0
                $all_valid_options = false;

                if ($importSet[6][$link] != -1) {
                    $element = $importSet[5][0][$importSet[6][$link]];
                    
                    $id_form = $importSet[4];
                    
                    if ($link == ($links-1)) {
                        // remove some really odd line endings if present, only happens when dealing with legacy outputs of really old/odd systems
                        $row_value = str_replace(chr(19).chr(16), "", $row[$link]);
                    } else {
                        $row_value = $row[$link];
                    }

                    if ($row_value != "") {
                        switch($element["ele_type"]) {
                            
                            case "derived":
                                continue; // ignore derived values for importing
                            
                            case "select":
                            $ele_value = unserialize($element["ele_value"]);
                            if ($importSet[5][1][$link] AND !strstr($row_value, ",")
                                AND (!is_numeric($row_value) OR $row_value < 10000000))
                            {
                                // Linked element
                                $linkElement = $importSet[5][1][$link];
                                $ele_value = unserialize($element["ele_value"]);
                                list($all_valid_options, $all_valid_options_ids) = getElementOptions($linkElement[2]['ele_handle'], $linkElement[2]['id_form']);
                                if ($ele_value[1] AND !($ele_value['snapshot'] AND $ele_value[16])) {
                                    // Multiple options
                                    $element_value = $linkElement[0] . "#*=:*" .
                                    $linkElement[1] . "#*=:*";
                                    $items = explode("\n", $row_value);
                                    if($ele_value['snapshot']) {
                                        $row_value = '';
                                        if(count($items)>1) {
                                            $row_value .= '*=+*:';  
                                        }
                                        $row_value .= implode('*=+*:',$items);
                                    } else {
                                    $row_value = ",";
                                    foreach ($items as $item) {
                                        $item_value = trim($item);
                                        if ($optionIndex = array_search($item_value, $all_valid_options)) {
                                            $ele_id = $all_valid_options_ids[$optionIndex];
                                        } else {
                                            foreach ($all_valid_options as $optionIndex=>$thisoption) {
                                                if (trim($item_value) == trim(trans($thisoption))) {
                                                    $item_value = $thisoption;
                                                    $ele_id = $all_valid_options_ids[$optionIndex];
                                                    break;
                                                }
                                            }
                                        }
                                        $row_value .= $ele_id . ",";
                                    }
                                    }
                                } elseif(!($ele_value['snapshot'] AND $ele_value[16])) {
                                    // Single option
                                    if($ele_value['snapshot']) {
                                        $row_value = $row_value; // take the validated item the user has put into the box
                                        break;
                                    } elseif ($optionIndex = array_search($row_value, $all_valid_options)) {
                                        $ele_id = $all_valid_options_ids[$optionIndex];
                                    } else {
                                        foreach ($all_valid_options as $optionIndex=>$thisoption) {
                                            if (trim($row_value) == trim(trans($thisoption))) {
                                                $row_value = $thisoption;
                                                $ele_id = $all_valid_options_ids[$optionIndex];
                                                break;
                                            }
                                        }
                                    }
                                    $row_value = $ele_id;
                                }
                            } elseif (!strstr($row_value, ",") AND (!is_numeric($row_value) OR $row_value < 10000000)) {
                                // Not-Linked element
                                $ele_value = unserialize($element["ele_value"]);

                                // handle fullnames or usernames
                                $temparraykeys = array_keys($ele_value[2]);
                                if ($temparraykeys[0] === "{FULLNAMES}" OR $temparraykeys[0] === "{USERNAMES}") { // ADDED June 18 2005 to handle pulling in usernames for the user's group(s) -- updated for real live use September 6 2006
                                    if ($temparraykeys[0] === "{FULLNAMES}") {
                                        $nametype = "name";
                                    }
                                    if ($temparraykeys[0] === "{USERNAMES}") {
                                        $nametype = "uname";
                                    }
                                    if (!isset($fullnamelist)) {
                                        $fullnamelistq = q("SELECT uid, $nametype FROM " . $xoopsDB->prefix("users") . " ORDER BY uid");
                                        static $fullnamelist = array();
                                        foreach ($fullnamelistq as $thisname) {
                                            $fullnamelist[$thisname['uid']] = $thisname[$nametype];
                                        }
                                    }
                                    if ($ele_value[1]) {
                                        // multiple
                                        $items = explode("\n", $row_value);
                                    } else {
                                        $items = array(0=>$row_value);
                                    }
                                    $numberOfNames = 0;
                                    $row_value = "";
                                    foreach ($items as $item) {
                                        if (is_numeric($item)) {
                                            $row_value .= "*=+*:" . $item;
                                            $numberOfNames++;
                                        } else {
                                            $uids = array_keys ($fullnamelist, $item);
                                            // instead of matching on all values, like we used to, match only the first name found (lowest user id)
                                            // to match other users besides the first one, use a user id number instead of a name in the import spreadsheet
                                            $row_value .= "*=+*:" . $uids[0];
                                            $numberOfNames++;
                                        }
                                    }
                                    if ($numberOfNames == 1) {
                                        // single entries are not supposed to have the separator at the front
                                        $row_value = substr_replace($row_value, "", 0, 5);
                                    }
                                    break;
                                }

                                if ($ele_value[1]) {
                                    // Multiple options
                                    $element_value = "";
                                    $options = $ele_value[2];
                                    $items = explode("\n", $row_value);
                                    foreach ($items as $item) {
                                        $item_value = trim($item);
                                        if (!in_array($item_value, $options, true)) {
                                            // last option causes strict matching by type
                                            foreach ($options as $thisoption=>$default_value) {
                                                if (trim($item_value) == trim(trans($thisoption))) {
                                                    $item_value = $thisoption;
                                                    break;
                                                }
                                            }
                                        }
                                        $element_value .= "*=+*:" . $item_value;
                                    }
                                    $row_value = $element_value;
                                } else {
                                    // Single option
                                    $options = $ele_value[2];
                                    if (!in_array($row_value, $options, true)) {
                                        // last option causes strict matching by type
                                        foreach ($options as $thisoption=>$default_value) {
                                            if (trim($row_value) == trim(trans($thisoption))) {
                                                $row_value = $thisoption;
                                                break;
                                            }
                                        }
                                    }
                                }
                            } elseif (strstr($row_value, ",") OR (is_numeric($row_value) AND $row_value > 10000000)) {
                                // the value is a comma separated list of linked values, so we need to add commas before and after, to adhere to the Formulize data storage spec
                                if (substr($row_value, 0, 1)!=",") {
                                    $row_value = ",".$row_value;
                                }
                                if (substr($row_value, -1)!=",") {
                                    $row_value = $row_value.",";
                                }
                            }
                            break;


                            case "checkbox":
                            $options = unserialize($element["ele_value"]);
                            $element_value = "";
                            $options = $options[2];
                            if(strstr($cell_value, "\n")) {
                                $items = explode("\n", $cell_value);
                            } else {
                                $items = explode(",", $cell_value);
                            }
                            foreach ($items as $item) {
                                $item_value = trim($item);
                                if (!in_array($item_value, $options, true)) {
                                    // last option causes strict matching by type
                                    $foundit = false;
                                    $hasother = false;
                                    foreach ($options as $thisoption=>$default_value) {
                                        if (trim($item_value) == trim(trans($thisoption))) {
                                            $item_value = $thisoption;
                                            $foundit = true;
                                            break;
                                        }
                                        if (preg_match('/\{OTHER\|+[0-9]+\}/', $thisoption)) {
                                            $hasother = $thisoption;
                                        }
                                    }
                                    if ($foundit) {
                                                    $element_value .= "*=+*:" . $item_value;
                                    } elseif ($hasother) {
                                        $other_values[] = "INSERT INTO " . $xoopsDB->prefix("formulize_other") . " (id_req, ele_id, other_text) VALUES (\"$max_id_req\", \"" . $element["ele_id"] . "\", \"" . $myts->htmlSpecialChars(trim($item_value)) . "\")";
                                        $element_value .= "*=+*:" . $hasother;
                                    } elseif (!$validateOverride) {
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


                            case "radio":
                            $options = unserialize($element["ele_value"]);
                            if (!in_array($row_value, $options, true)) {
                                // last option causes strict matching by type
                                $foundit = false;
                                $hasother = false;
                                foreach ($options as $thisoption=>$default_value) {
                                    if (trim($row_value) == trim(trans($thisoption))) {
                                        $row_value = $thisoption;
                                        $foundit = true;
                                        break;
                                    }
                                    if (preg_match('/\{OTHER\|+[0-9]+\}/', $thisoption)) {
                                        $hasother = $thisoption;
                                    }
                                }
                                if (!$foundit AND $hasother) {
                                    $other_values[] = "INSERT INTO " . $xoopsDB->prefix("formulize_other") . " (id_req, ele_id, other_text) VALUES (\"$max_id_req\", \"" . $element["ele_id"] . "\", \"" . $myts->htmlSpecialChars(trim($row_value)) . "\")";
                                    $row_value = $hasother;
                                } elseif (!$foundit AND !$validateOverride) {
                                    print "ERROR: INVALID TEXT FOUND FOR A RADIO BUTTON ITEM -- $row_value -- IN ROW:<BR>";
                                    print_r($row);
                                    print "<br><br>";
                                }
                            }
                            break;


                            case "date":
                            $row_value = date("Y-m-d", strtotime(str_replace("/", "-", $row_value)));
                            break;


                            case "yn":
                            if (!is_numeric($row_value)) {
                                $yn_value = strtoupper($row_value);

                                if ($yn_value == "YES")
                                    $row_value = 1;
                                else if ($yn_value == "NO")
                                    $row_value = 2;
                            }
                            break;
                        }

                        // record the values for inserting as part of this record
                        // prior to 3.0 we did not do the htmlspecialchars conversion if this was a linked selectbox...don't think that's a necessary exception in 3.0 with new data structure
                        $fieldValues[$element['ele_handle']] = $myts->htmlSpecialChars($row_value);

                    } // end of if there's a value in the current column
                } elseif (isset($importSet[7]['usethisentryid']) AND $link == $importSet[7]['usethisentryid']) {
                    // if this is not a valid column, but it is an entry id column, then capture the entry id from the cell
                    $newEntryId = $row[$link] ? $row[$link] : "";
                } // end of if this is a valid column
            } // end of looping through $links (columns?)

            // now that we've recorded all the values, do the actual updating/inserting of this record
            if ($this_id_req) {
                
                // first, record a revisions if necessary
                formulize_updateRevisionData($formObject, $this_id_req, true);
                
                // updating an entry
                $form_uid = $this_uid;
                $updateSQL = "UPDATE " . $xoopsDB->prefix("formulize_".$importSet[8])." SET ";
                $start = true;
                foreach ($fieldValues as $elementHandle=>$fieldValue) {
                    if (!$start) {
                        // on subsequent fields, add a comma
                        $updateSQL .= ", ";
                    }
                    $start = false;
                    $fieldValue = $data_handler->formatValueForQuery($elementHandle, $fieldValue);
                    $updateSQL .= "`$elementHandle` = $fieldValue";
                }
                $updateSQL .= ", mod_datetime=NOW(), mod_uid=$form_proxyid WHERE entry_id=".intval($this_id_req);

                if (IMPORT_WRITE) {
                    if (!$result = $xoopsDB->queryF($updateSQL)) {
                        print "<br><b>FAILED</b> to update data, SQL: $updateSQL<br>".$xoopsDB->error()."<br>";
                    }
                    $entriesMap[] = $this_id_req;
                    $notEntriesList['update_entry'][$importSet[4]][] = $this_id_req; // log the notification info
                }
            } else {
                // inserting a new entry
                $fields = "";
                $values = "";
                $element_handler = xoops_getmodulehandler('elements', 'formulize');
                foreach ($fieldValues as $elementHandle=>$fieldValue) {
                    $fields .= ", `".$elementHandle."`";
                    $values .= ", ".$data_handler->formatValueForQuery($elementHandle, $fieldValue);
                    $elementObject = $element_handler->get($elementHandle);
                    if ($elementObject->getVar('ele_desc')=="Primary Key") {
                        $newEntryId = $fieldValue;
                    }
                }

                if ($form_uid == 0) {
                    $form_uid = $form_proxyid;
                }

                $entryIdFieldText = $newEntryId ? "entry_id, " : "";
                $newEntryId .= $newEntryId ? ", " : "";
                $insertElement = "INSERT INTO " . $xoopsDB->prefix("formulize_".$importSet[8])." (".$entryIdFieldText."creation_datetime, mod_datetime, creation_uid, mod_uid".$fields.") VALUES (".$newEntryId."NOW(), NOW(), '" . intval($form_uid) . "', '" . intval($form_proxyid)."'".$values.")";

                if (IMPORT_WRITE) {
                    if (!$result = $xoopsDB->queryF($insertElement)) {
                        static $duplicatesFound = false;
                        if (strstr($xoopsDB->error(), "Duplicate entry")) {
                            if (!$duplicatesFound) {
                                print "<br><b>FAILED</b> to insert <i>some</i> data.  At least one duplicate value was found in a column that does not allow duplicate values.<br>";
                                $duplicatesFound = true;
                            }
                        } else {
                            print "<br><b>FAILED</b> to insert data, SQL: $insertElement<br>".$xoopsDB->error()."<br>";
                        }
                    } else {
                        // need to record new group ownership info too
                        $usersMap[] = $form_uid;
                        $insertedId = $xoopsDB->getInsertId();
                        $entriesMap[] = $insertedId;
                        $notEntriesList['new_entry'][$importSet[4]][] = $insertedId; // log the notification info
                    }
                } else {
                    echo "<br>" . $insertElement . "<br>";
                }
            }

            $idToShow = $newEntryId ? $newEntryId : $max_id_req;
            //echo "line $rowCount, id $idToShow<br>";
        } // end of if we have contents in this row
    } // end of looping through each row of the file

    if (count($usersMap) > 0) {
        // if new entries were created...
        include_once XOOPS_ROOT_PATH . "/modules/formulize/class/data.php";
        $data_handler = new formulizeDataHandler($id_form);
        if (!$groupResult = $data_handler->setEntryOwnerGroups($usersMap, $entriesMap)) {
            print "ERROR: failed to write the entry ownership information to the database.<br>".$xoopsDB->error()."<br>";
        }
    }

    // unlock tables
    $xoopsDB->query("UNLOCK TABLES");

    // insert all the other values that were recorded
    foreach ($other_values as $other) {
        if (!$result = $xoopsDB->query($other)) {
            print "ERROR: could not insert 'other' value: $other<br>";
        }
    }
    // fid is $importSet[4] ?!!
    $GLOBALS['formulize_snapshotRevisions'][$importSet[4]] = formulize_getCurrentRevisions($importSet[4], $entriesMap);
    
    if(isset($_POST['updatederived']) AND $_POST['updatederived']) {
    // update derived values based on the form only
        $ele_types = $formObject->getVar('elementTypes');
        if(in_array('derived',$ele_types)) {
    foreach($entriesMap as $entry) {
        //if($entry > 200) { break; }
        //print "Entry $entry Memory usage: ".memory_get_usage()."<br>";
        $GLOBALS['formulize_doNotCacheDataSet'] = true;
        formulize_updateDerivedValues($entry, $importSet[4]); // 4 is the form id
    }
        }
    }
    
    if(isset($_POST['sendnotifications']) AND $_POST['sendnotifications']) {
    // send notifications
    foreach($notEntriesList as $notEvent=>$notDetails) {
        foreach($notDetails as $notFid=>$notEntries) {
            $notEntries = array_unique($notEntries); 
            sendNotifications($notFid, $notEvent, $notEntries);
        }
    }
    }
}


// This function returns the saved values in the data table for the element it is passed
// It also returns a parallel array that contains the entry ids that each value belongs to
//function getElementOptions($id_form, $ele_caption)
function getElementOptions($ele_handle, $fid) {
    static $cachedElementOptions = array();
    if (!isset($cachedElementOptions[$fid][$ele_handle])) {
        global $xoopsDB, $myts;
        $form_handler = xoops_getmodulehandler('forms', 'formulize');
        $formObject = $form_handler->get(intval($fid));
        $result = array();
        if (!$myts) {
            $myts =& MyTextSanitizer::getInstance();
        }

        $sql = "SELECT entry_id, `".$ele_handle."` FROM " . $xoopsDB->prefix("formulize_".$formObject->getVar('form_handle'));
        $res = $xoopsDB->query($sql);
        $result = array();
        $resultIDs = array();
        while ($item = $xoopsDB->fetchArray($res)) {
            $result[] = $myts->undoHtmlSpecialChars($item[$ele_handle]);
            $resultIDs[] = $item['entry_id'];
        }
        $cachedElementOptions[$fid][$ele_handle] = array(0=>$result, 1=>$resultIDs);
    }
    return $cachedElementOptions[$fid][$ele_handle];
}


// THERE IS A BUG HERE WHICH CAUSES IT NOT TO COME UP WITH THE RIGHT ID WHEN PASSED A FULL NAME???
function getUserID($stringName) {
    global $xoopsDB, $xoopsUser;

    $sql = "SELECT uid FROM " . $xoopsDB->prefix("users") .
        " WHERE uname='" . formulize_db_escape($stringName) . "'";

    $result = $xoopsDB->query($sql);
    if ($xoopsDB->getRowsNum($result) > 0) {
        $item = $xoopsDB->fetchArray($result);
        if (@$item["uid"]) {
            return $item["uid"];
        }
    } else {
        // or, if no username match found, get the first matching full name -- added June 29, 2006
        $sql = "SELECT uid FROM " . $xoopsDB->prefix("users") .
        " WHERE name='" . formulize_db_escape($stringName) . "'";

        if ($result = $xoopsDB->query($sql)) {
            $item = $xoopsDB->fetchArray($result);
            if (@$item["uid"]) {
                return $item["uid"];
            }
        }
    }

    if (is_numeric($stringName)) {
        return $stringName;
    }

    // instead of returning 0, return the current user's ID -- added June 29, 2006
    return $xoopsUser->getVar('uid');
}


function formformCaption($ele_caption) {
    $ele_caption = str_replace("'", "`", $ele_caption);
    $ele_caption = str_replace("&quot;", "`", $ele_caption);
    $ele_caption = str_replace("&#039;", "`", $ele_caption);
    return $ele_caption;
}


function importCsvDebug(& $importSet) {
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
    for ($link = 0; $link < $links; $link++) {
        $output .= "<tr valign=\"top\">";
        if ($importSet[6][$link] == -1) {
            $output .= "<td>" .
                "N" .
                "</td><td colspan=\"4\">" .
                $importSet[3][$link] .
                "</td>";
        } else {
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

            switch($element["ele_type"]) {
                case "select":
                    $ele_value = unserialize($element["ele_value"]);
                    $options = $ele_value[2];

                    if (is_array($options)) {
                        $keys_output = "";
                        for (reset($options); $key = key($options); next($options)) {
                            if ($keys_output != "") {
                                $keys_output .= ", ";
                            }
                            $keys_output .= $key;
                        }

                        $output .= "{ " . $keys_output . " }";
                    } else {
                        if ($importSet[5][1][$link]) {
                            $linkElement = $importSet[5][1][$link];

                            $output .= "form: " . $linkElement[0] .
                                "<br>element: " . $linkElement[1] .
                                "<br>type: " . $linkElement[2]['ele_type'];
                        } else {
                            $output .= $options;
                        }
                    }
                    break;

                case "checkbox":
                    $options = unserialize($element["ele_value"]);

                    if (is_array($options)) {
                        $keys_output = "";
                        for (reset($options); $key = key($options); next($options)) {
                            if ($keys_output != "") {
                                $keys_output .= ", ";
                            }
                            $keys_output .= $key;
                        }

                        $output .= "{ " . $keys_output . " }";
                    } else {
                        $output .= $options;
                    }
                    break;


                case "radio":
                    $options = unserialize($element["ele_value"]);

                    if (is_array($options)) {
                        $keys_output = "";
                        for (reset($options); $key = key($options); next($options)) {
                            if ($keys_output != "") {
                                $keys_output .= ", ";
                            }
                            $keys_output .= $key;
                        }

                        $output .= "{ " . $keys_output . " }";
                    } else {
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
