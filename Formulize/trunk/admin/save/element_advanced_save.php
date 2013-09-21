<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2010 Freeform Solutions                  ##
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
##  URL: http://www.freeformsolutions.ca/formulize                           ##
##  Project: Formulize                                                       ##
###############################################################################

// this file handles saving of submissions from the element advanced page of the new admin UI

// if we aren't coming from what appears to be save.php, then return nothing
if(!isset($processedValues)) {
  return;
}

// invoke the necessary objects
$element_handler = xoops_getmodulehandler('elements','formulize');
if(!$ele_id = intval($_GET['ele_id'])) { // on new element saves, new ele_id can be passed through the URL of this ajax save
  if(!$ele_id = intval($_POST['formulize_admin_key'])) {
    print "Error: could not determine element id when saving advanced settings";
    return;
  }
}

// get the element object with the right handler, ie: check if it's a custom type
$element = $element_handler->get($ele_id);
$ele_type = $element->getVar('ele_type');
if(file_exists(XOOPS_ROOT_PATH."/modules/formulize/class/".$ele_type."Element.php")) {
  $customTypeHandler = xoops_getmodulehandler($ele_type."Element", 'formulize');
  $element = $customTypeHandler->get($ele_id);
}
$fid = $element->getVar('id_form');

$form_handler = xoops_getmodulehandler('forms', 'formulize');
$formObject = $form_handler->get($fid);
if($formObject->getVar('lockedform')) {
  return;
}
// check if the user has permission to edit the form
if(!$gperm_handler->checkRight("edit_form", $fid, $groups, $mid)) {
  return;
}

$ele_value = $element->getVar('ele_value');
$ele_encrypt = $_POST['elements-ele_encrypt'];
$databaseElement = ($ele_type == "areamodif" OR $ele_type == "ib" OR $ele_type == "sep" OR $ele_type == "subform" OR $ele_type == "grid" OR (property_exists($element,'hasData') AND $element->hasData == false) ) ? false : true; 
$reloadneeded = false;    
if($ele_encrypt != $element->getVar('ele_encrypt') AND $databaseElement AND !$_GET['ele_id']) { // the setting has changed on this pageload, and it's a database element, and it's not new
  $reloadneeded = true; // display of data type goes on/off when encryption is off/on
  // if the encryption setting changed, then we need to encrypt/decrypt all the existing data
  include_once XOOPS_ROOT_PATH . "/modules/formulize/class/data.php";
  $data_handler = new formulizeDataHandler($fid);
  if(!$data_handler->toggleEncryption($_POST['original_handle'], $element->getVar('ele_encrypt'))) {
    print "Warning:  unable to toggle the encryption of the '".$_POST['original_handle']."' field on/off!";
  }
}

if($databaseElement AND $_GET['ele_id']) { // ele_id is only in the URL when we're on the first save for a new element
  global $xoopsDB;
  // figure out what the data type should be.
  // the rules:
  // if it's encrypted, it's a BLOB, otherwise...
  // date fields get 'date'
  // colorpicker gets 'text'
  // for text element types...
  // if 'text' is the specified type, and it's numbers only with a decimal, then use decimal with that number of spaces
  // if 'text' is the specified type, and it's numbers only with 0 decimals, then use int
  // all other cases, use the specified type
  if($ele_encrypt) {
    $dataType = 'blob';
  } else {
    switch($ele_type) {
      case 'date':
            $dataType = 'date';
            break;
      case 'colorpick':
            $dataType = 'text';
            break;
      case 'yn':
            $dataType = 'int'; // they are stored as 1 and 2
            break;
      case 'text':
            if($ele_value[3] == 1 AND $_POST['element_datatype'] == 'text') { // numbers only...and Formulize was asked to figure out the right datatype.....
                  if($datadecimals = intval($ele_value[5])) {
                        if($datadecimals > 20) { // mysql only allows a certain number of digits in a decimal datatype, so we're making some arbitrary size limitations
                              $datadecimals = 20;
                        }
                        $datadigits = $datadecimals < 10 ? 11 : $datadecimals + 1; // digits must be larger than the decimal value, but a minimum of 11
                        $dataType = "decimal($datadigits,$datadecimals)"; 
                  } else {
                        $dataType = 'int(10)'; // value in () is just the visible number of digits to use in a mysql console display
                  }
            } else {
                  $dataType = getRequestedDataType();
            }
            break;
      default:
            // check for custom type and if there's a database type override specified
            // if not, then get requested type
            if(property_exists($element, 'overrideDataType') AND $element->overrideDataType != "") {
              $dataType = $element->overrideDataType;
            } else {
              $dataType = getRequestedDataType();
            }
    }
  }
  
  if(!$insertResult = $form_handler->insertElementField($element, $dataType)) {
   exit("Error: could not add the new element to the data table in the database.");
  }
  
}	elseif(($_POST['original_handle'] != $element->getVar('ele_handle') OR (isset($_POST['element_default_datatype']) AND $_POST['element_datatype'] != $_POST['element_default_datatype'])) AND $databaseElement) {
  // figure out if the datatype needs changing...
  if($ele_encrypt) {
    $dataType = false;
  } elseif(isset($_POST['element_default_datatype']) AND $_POST['element_datatype'] != $_POST['element_default_datatype']) {
        $dataType = getRequestedDataType();
  } else {
        $dataType = false;
  }
  // need to update the name of the field in the data table, and possibly update the type too
  if(!$updateResult = $form_handler->updateField($element, $_POST['original_handle'], $dataType)) {
      print "Error: could not update the data table field name to match the new data handle";
  }
 
}

$element->setVar('ele_encrypt', $ele_encrypt);

if(!$element_handler->insert($element)) {
  print "Error: could not save encryption setting for the element.";
}

//New index handling
    global $xoopsDB;
    
    if($_POST['elements-ele_index'] != $_POST['original_ele_index']){
        if($_POST['elements-ele_index']){
            //create new index
            $element->createIndex();
            $reloadneeded = true;
        }elseif($_POST['original_ele_index'] AND strlen($_POST['original_index_name']) > 0){
            //remove existing index
            $element->deleteIndex($_POST['original_index_name']);
    }
  }

if($reloadneeded) {
  print "/* evalnow */ if(redirect=='') { redirect = 'reloadWithScrollPosition();'; } newhandle = '".$element->getVar('ele_handle')."';"; // pass back the new element handle so we can update the original_handle flag for the next save operation
} else {
  print "/* evalnow */ newhandle = '".$element->getVar('ele_handle')."';"; // pass back the new element handle so we can update the original_handle flag for the next save operation
}


// this function returns the datatype requested for this element
function getRequestedDataType() {
			switch($_POST['element_datatype']) {
						case 'decimal':
												if($datadecimals = intval($_POST['element_datatype_decimalsize'])) {
															if($datadecimals > 20) {
																		$datadecimals = 20;
															}
												} else {
															$datadecimals = 2;
												}
												$datadigits = $datadecimals < 10 ? 11 : $datadecimals + 1; // digits must be larger than the decimal value, but a minimum of 11
												$dataType = "decimal($datadigits,$datadecimals)";
												break;
									case 'int':
												$dataType = 'int(10)';
												break;
									case 'varchar':
												if(!$varcharsize = intval($_POST['element_datatype_varcharsize'])) {
														$varcharsize = 255;  
												}
												$varcharsize = $varcharsize > 255 ? 255 : $varcharsize;
												$dataType = "varchar($varcharsize)";
												break;
									case 'char':
												if(!$charsize = intval($_POST['element_datatype_charsize'])) {
														$charsize = 255;  
												}
												$charsize = $charsize > 255 ? 255 : $charsize;
												$dataType = "char($charsize)";
												break;
									case 'text':
												$dataType = 'text';
												break;
									default:
												print "ERROR: unrecognized datatype has been specified: ".strip_tags(htmlspecialchars($_POST['element_datatype']));
			}
			return $dataType;
}