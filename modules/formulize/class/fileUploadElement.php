<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2011 Freeform Solutions                  ##
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

require_once XOOPS_ROOT_PATH . "/modules/formulize/class/elements.php"; // you need to make sure the base element class has been read in first!

class formulizeFileUploadElement extends formulizeformulize {
    
    var $needsDataType;
    var $overrideDataType;
    var $hasData;
    var $name;
    var $adminCanMakeRequired;
    var $alwaysValidateInputs;
    function __construct() {
        $this->name = "File upload box";
        $this->hasData = true; // set to false if this is a non-data element, like the subform or the grid
        $this->needsDataType = false; // set to false if you're going force a specific datatype for this element using the overrideDataType
        $this->overrideDataType = "text"; // use this to set a datatype for the database if you need the element to always have one (like 'date').  set needsDataType to false if you use this.
        $this->adminCanMakeRequired = true; // set to true if the webmaster should be able to toggle this element as required/not required
        $this->alwaysValidateInputs = true; // set to true if you want your custom validation function to always be run.  This will override any required setting that the webmaster might have set, so the recommendation is to set adminCanMakeRequired to false when this is set to true.
        parent::__construct();
    }
    
}

class formulizeFileUploadElementHandler extends formulizeElementsHandler {
    
    var $db;
    var $clickable; // used in formatDataForList
    var $striphtml; // used in formatDataForList
    var $length; // used in formatDataForList
    
    function __construct($db) {
        $this->db =& $db;
    }
    
    function create() {
        return new formulizeFileUploadElement();
    }
    
    // this method would gather any data that we need to pass to the template, besides the ele_value and other properties that are already part of the basic element class
    // it receives the element object and returns an array of data that will go to the admin UI template
    // when dealing with new elements, $element might be FALSE
    function adminPrepare($element) {
        if(!$element) {
            $ele_value = array(10,'doc,docx,xls,xlsx,ppt,pptx,csv,txt,pdf,jpg,jpeg,gif,png,odt,ods,odp'); // nothing has been saved yet, so let's set a default of 10MB, and some default file types
            $directLinkNo = " checked ";
            $directLinkYes = "";
        } else {
            $ele_value = $element->getVar('ele_value');
            if(!$ele_value[0]) {
                $ele_value[0] = 10; // adminSave should enforce a default of 10, but just to be safe...
            }
            $directLinkNo = $ele_value[2] ? "" : " checked";
            $directLinkYes = $ele_value[2] ? " checked" : "";
        }
        return array('maxfilesize'=>$ele_value[0],'extensions'=>$ele_value[1],'directlinkno'=>$directLinkNo,'directlinkyes'=>$directLinkYes);
    }
    
    // this method would read back any data from the user after they click save in the admin UI, and save the data to the database, if it were something beyond what is handled in the basic element class
    // this is called as part of saving the options tab.  It receives a copy of the element object immediately prior to it being saved, so the element object will have all its properties set as they would be based on the user's changes in the names & settings tab, and in the options tab (the tabs are saved in order from left to right).
    // the exception is the special ele_value array, which is passed separately from the object (this will contain the values the user set in the Options tab)
    // You can modify the element object in this function and since it is an object, and passed by reference by default, then your changes will be saved when the element is saved.
    // You should return a flag to indicate if any changes were made, so that the page can be reloaded for the user, and they can see the changes you've made here.
    function adminSave($element, $ele_value) {
        $changed = false;
        if($ele_value[0] == 0) {
            $ele_value[0] = 10; // set ten as a default if there is no file size specified
            $element->setVar('ele_value',$ele_value);
            $changed = true;
        }        
        return $changed;
    }
    
    // this method reads the current state of an element based on the user's input, and the admin options, and sets ele_value to what it needs to be so we can render the element correctly
    // it must return $ele_value, with the correct value set in it, so that it will render as expected in the render method
    // $value is the value that was retrieved from the database for this element in the active entry.  It is a raw value, no processing has been applied, it is exactly what is in the database (as prepared in the prepareDataForSaving method and then written to the DB)
    // $ele_value will contain the options set for this element (based on the admin UI choices set by the user, possibly altered in the adminSave method)
    // $element is the element object
    function loadValue($value, $ele_value, $element) {
        $value = unserialize($value); // what we've got in the database is a serialized array, first key is filename, second key is flag for whether the filename is for real (might be an error message)
        $ele_value[3] = $value['name']; // add additional keys to ele_value where we'll put the value that is coming from the database for user's to see, plus other flags and so on
        $ele_value[4] = $this->getFileDisplayName($value['name']); 
        $ele_value[5] = $value['isfile'];
        return $ele_value; 
    }
    
    // this method renders the element for display in a form
    // the caption has been pre-prepared and passed in separately from the element object
    // if the element is disabled, then the method must take that into account and return a non-interactable label with some version of the element's value in it
    // $ele_value is the options for this element - which will either be the admin values set by the admin user, or will be the value created in the loadValue method
    // $caption is the prepared caption for the element
    // $markupName is what we have to call the rendered element in HTML
    // $isDisabled flags whether the element is disabled or not so we know how to render it
    // $element is the element object
    // $entry_id is the ID number of the entry where this particular element comes from
    function render($ele_value, $caption, $markupName, $isDisabled, $element, $entry_id) {
        // ele_value[3] is the fileName or error message
        // ele_value[4] is the displayName version of the fileName (only going to be diff from fileName if there is no direct linking to files allowed)
        // ele_value[5] is the flag for whether there's a file or not
        static $fileDeleteCode;
        $introToUploadBox = "";
        if($isDisabled) {
            $formElement = new xoopsFormLabel($caption, $ele_value[3]);
        } else {
            // create the file upload element, and also a hidden element with the correct markup name.  That hidden value will trigger the correct saving logic, and is necessary because file upload elements are excluded from POST.
            if(!$ele_value[5]) {
                $introToUploadBox = "<div id='formulize_fileStatus_".$element->getVar('ele_id')."_$entry_id' class='no-print'>".$ele_value[3]."" . _AM_UPLOAD . "</div>";
            } elseif($ele_value[3]) {
                if(!$fileDeleteCode) { // only do this once per page load
                    $fileDeleteCode = "<script type='text/javascript'>
                
                    var formulizeFile".$markupName."Exists = true;
                
                    function warnAboutFileDelete(folderName, element_id, entry_id) {
                        var answer = confirm('" . _AM_UPLOAD_DELETE_WARN . "');
                        if(answer) {
                            var xhr_params = [];
                            xhr_params[0] = folderName;
                            xhr_params[1] = element_id;
                            xhr_params[2] = entry_id;
                            formulize_xhr_send('delete_uploaded_file', xhr_params);
                        }
                    }
                    
                    function formulize_delete_successful(response) {
                        response = eval('('+response+')');
                        window.document.getElementById('formulize_fileStatus_'+response.element_id+'_'+response.entry_id).innerHTML = 'Upload a file:';
                        var fileExistsFlag = 'formulizeFilede_".$element->getVar('id_form')."_'+response.entry_id+'_'+response.element_id+'Exists = false';
                        eval(fileExistsFlag) 
                    }
                    
                    function formulize_delete_failed() {
                        alert('" . _AM_UPLOAD_DELETE_FAIL . "');
                    }
                    
                    </script>";
                    
                    $introToUploadBox .= $fileDeleteCode;
                    // <input type='hidden' id='formulize_fileDelete' name='formulize_fileDelete' value= '' />";
                } elseif($element->getVar('ele_req')) { // just set the flag that tells the required element logic that the file is present
                    $introToUploadBox .= "<script type='text/javascript'>              
                    var formulizeFile".$markupName."Exists = true;
                    </script>";
                }
                $introToUploadBox .= "<div id='formulize_fileStatus_".$element->getVar('ele_id')."_$entry_id' class='no-print'>".$this->createDownloadLink($element, $entry_id, $ele_value[3], $ele_value[4])." &mdash; <a href='' onclick='warnAboutFileDelete(\"".str_replace("de_","formulize_",$markupName)."\", \"".$element->getVar('ele_id')."\", \"$entry_id\");return false;'><img src='".XOOPS_URL."/modules/formulize/images/x.gif' />" . _AM_UPLOAD_DELETE . "</a><br />" . _AM_UPLOAD_MOD . "</div>";
            } else {
                $introToUploadBox = "<div id='formulize_fileStatus_".$element->getVar('ele_id')."_$entry_id' class='no-print'>" . _AM_UPLOAD . "</div>";
            }
            $htmlForUpload = "$introToUploadBox<div><input type='hidden' name='MAX_FILE_SIZE' value='".($ele_value[0]*1048576)."' /><input type='file' name='fileupload_".$markupName."' size=50 id='".$markupName."' onchange=\"javascript:formulizechanged=1;\"  class='no-print' /><input type='hidden' id='$markupName' name='$markupName' value='$markupName' /></div>";
            $formElement = new xoopsFormLabel($caption, $htmlForUpload);
        }
        return $formElement;
    }
    
    // this method returns any custom validation code (javascript) that should figure out how to validate this element
    // 'myform' is a name enforced by convention that refers to the form where this element resides
    // use the adminCanMakeRequired property and alwaysValidateInputs property to control when/if this validation code is respected
    function generateValidationCode($caption, $markupName, $element) {
        // always set the cue properly, based on whether there's a file or not to be uploaded
        $validationmsg = "You must upload a file for '$caption'.";
	$validationmsg = str_replace("'", "\'", stripslashes( $validationmsg ) );
        $cueName = str_replace("de_","decue_",$markupName);
        $validationCode = array();
        $validationCode[] = "if(myform.fileupload_{$markupName}.value == '') {\n"; // if no file has been selected, then neuter the cue element that tells us we need to handle this one when saving
        $validationCode[] = "  myform.{$cueName}.name = 'skipit!';\n";
        $validationCode[] = "} else {\n";
        $validationCode[] = "  myform.{$cueName}.name = '{$cueName}';\n";
        $validationCode[] = "}\n";
        if($element->getVar('ele_req')) { // need to include this only if the admin wants to force a value for this element, and there's no file selected already
            $validationCode[] = "if(myform.fileupload_{$markupName}.value == '' && (typeof formulizeFile".$markupName."Exists == 'undefined' || formulizeFile".$markupName."Exists == false)) {\n";
            $validationCode[] = "  window.alert('{$validationmsg}');\n myform.fileupload_{$markupName}.focus();\n return false;\n ";
            $validationCode[] = "}\n";
        }
        return $validationCode;
    }
    
    // this method will read what the user submitted, and package it up however we want for insertion into the form's datatable
    // You can return {WRITEASNULL} to cause a null value to be saved in the database
    // $value is what the user submitted
    // $element is the element object
    function prepareDataForSaving($value, $element) {
        
        $fileKey = 'fileupload_'.$value;
        if($_FILES[$fileKey]['error'] == 0) {
            // get the extension for the uploaded file
            $dotPos = 0;
            $fileExtensionOK = false;
            while($location = strpos($_FILES[$fileKey]['name'],".",$dotPos+1)) {
                $dotPos = $location;
            }
            if($dotPos) {
                $extension = strtolower(substr($_FILES[$fileKey]['name'],$dotPos+1));
                $ele_value = $element->getVar('ele_value');
                $allowedExtensions = str_replace(array(" ","."),"",strtolower(trim($ele_value[1])));
                if(in_array($extension,explode(",",$allowedExtensions))) {
                    $fileExtensionOK = true;
                }
            }
            if($fileExtensionOK) {
                $ele_value = $element->getVar('ele_value');
                $obscureFile = $ele_value[2] ? "" : microtime(true)."+---+";
                // if it's a blank subform entry that we're saving, do things a bit differently...elements are named differently in markup in this case
                if(strstr($value, "desubform")) {
                    $underscorePos = strpos($value, "_"); // everything after the first underscore is the normal identifier for the element
                    $replacementString = substr($value, 0, $underscorePos+1);
                } else {
                    $replacementString = "de_";
                }
                $folderLocation = XOOPS_ROOT_PATH."/uploads/".str_replace($replacementString,"formulize_",$value);
                $folderExists = file_exists($folderLocation);
                if(!$folderExists) {
                    $folderExists = mkdir($folderLocation);
                }
                if($folderExists) {
                    $moveResult = move_uploaded_file($_FILES[$fileKey]['tmp_name'],$folderLocation."/$obscureFile".$_FILES[$fileKey]['name']);
                    if($moveResult) {
                        $value = array();
                        $value['name'] = $obscureFile.$_FILES[$fileKey]['name'];
                        $value['isfile'] = true; // second array position will indicate that we have a real file here
                        $value['type'] = $_FILES[$fileKey]['type']; // save the mime type for later use
                        $value['size'] = $_FILES[$fileKey]['size']; // save the size for later use
                        if(strstr($folderLocation, "_new_")) {
                            $GLOBALS['formulize_afterSavingLogicRequired'][$element->getVar('ele_id')] = $element->getVar('ele_type'); // set the flag that will trigger a post-save operation when we can rename the folder where the file resides to match the newly assigned entry id
                        }
                    } else {
                        $value = _AM_UPLOAD_LOST;
                        print "<p><b>$value</b></p>";    
                    }
                } else {
                    $value = _AM_UPLOAD_NOLOCATION;
                    print "<p><b>$value</b></p>";
                }
            } else {
                $value = _AM_UPLOAD_ERROR_MIMETYPES . $element->getVar('ele_caption')."'";
                print "<p><b>$value</b></p>";
            }
        } elseif($_FILES[$fileKey]['error'] == UPLOAD_ERR_NO_FILE) {
            return "{WRITEASNULL}"; // no replacement file sent, so keep whatever we've got already...we should be skipping these uploads!
        } else {
            switch($_FILES[$fileKey]['error']) {
                case UPLOAD_ERR_INI_SIZE:
                    $value = _AM_UPLOAD_ERR_INI_SIZE;
                    break;
                case UPLOAD_ERR_FORM_SIZE:
                    $value = _AM_UPLOAD_ERR_FORM_SIZE . $element->getVar('ele_caption')."'";
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $value = _AM_UPLOAD_ERR_PARTIAL;
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    $value = _AM_UPLOAD_ERR_NO_TMP_DIR;
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    $value = _AM_UPLOAD_ERR_CANT_WRITE;
                    break;
                case UPLOAD_ERR_EXTENSION:
                    $value = _AM_UPLOAD_ERR_EXTENSION;
                    break;
            }
            print "<p><b>$value</b></p>";
        }
        if(!is_array($value)) {
            $value = array('name'=>$value, 'isfile'=>false);
        }
        return serialize($value); 
    }
    
    // this method will handle any final actions that have to happen after data has been saved
    // this is typically required for modifications to new entries, after the entry ID has been assigned, because before now, the entry ID will have been "new"
    // value is the value that was just saved
    // element_id is the id of the element that just had data saved
    // entry_id is the entry id that was just saved
    // ALSO, $GLOBALS['formulize_afterSavingLogicRequired']['elementId'] = type must be declared in the prepareDataForSaving step if further action is required now -- see fileUploadElement.php for an example
    function afterSavingLogic($value, $element_id, $entry_id) {
        // we need to update the folder path so that "_new_" is replaced with the _entryid_ and the file can then be accessible.
        $element_handler = xoops_getmodulehandler('elements','formulize');
        $elementObject = $element_handler->get($element_id);
        rename(XOOPS_ROOT_PATH."/uploads/formulize_".$elementObject->getVar('id_form')."_new_".$element_id,XOOPS_ROOT_PATH."/uploads/formulize_".$elementObject->getVar('id_form')."_".$entry_id."_".$element_id);
    }
    
    // this method will prepare a raw data value from the database, to be included in a dataset when formulize generates a list of entries or the getData API call is made
    // in the standard elements, this particular step is where multivalue elements, like checkboxes, get converted from a string that comes out of the database, into an array, for example
    // $value is the raw value that has been found in the database
    // $handle is the element handle for the field that we're retrieving this for
    // $entry_id is the entry id of the entry in the form that we're retrieving this for
    function prepareDataForDataset($value, $handle, $entry_id) {
        return $value;
    }
    
    // this method will take a text value that the user has specified at some point, and convert it to a value that will work for comparing with values in the database.  This is used primarily for preparing user submitted text values for saving in the database, or for comparing to values in the database.  The typical user submitted values would be coming from a condition form (ie: fieldX = [term the user typed in]) or other situation where the user types in a value that needs to interact with the database.
    // this would be where a Yes value would be converted to a 1, for example, in the case of a yes/no element, since 1 is how yes is represented in the database for that element type
    function prepareLiteralTextForDB($value, $element) {
        return $value;
    }
    
    // this method will format a dataset value for display on screen when a list of entries is prepared
    // for standard elements, this step is where linked selectboxes potentially become clickable or not, among other things
    // Set certain properties in this function, to control whether the output will be sent through a "make clickable" function afterwards, sent through an HTML character filter (a security precaution), and trimmed to a certain length with ... appended.
    function formatDataForList($value, $handle, $entry_id) {
        $this->clickable = false; // make urls clickable
        $this->striphtml = false; // remove html tags as a security precaution
        $this->length = 1000; // truncate to a maximum of 1000 characters, and append ... on the end
        $value = unserialize($value); // file upload elements have a serialized array as their data value, and there's not a lot we can do with that except unserialize it here.  Without putting a hook of some kind in the display function, we have no way of knowing when we should be unserializing the values that we are packaging up when the display function is called.
        $displayName = $this->getFileDisplayName($value['name']);
        $value = $value['isfile'] ? $this->createDownloadLink($this->get($handle), $entry_id, $value['name'], $displayName) : $value['name'];
        return parent::formatDataForList($value); // always return the result of formatDataForList through the parent class (where the properties you set here are enforced)
    }
    
    // this method is for the file upload element only.  It will return a href that links to the actual file.
    function createDownloadLink($element, $entry_id, $fileName, $displayName) {
        $ele_value = $element->getVar('ele_value');
        if($ele_value[2]) {
            return "<a href='".XOOPS_URL."/uploads/formulize_".$element->getVar('id_form')."_".$entry_id."_".$element->getVar('ele_id')."/$fileName' target='_blank'>".htmlspecialchars(strip_tags($displayName),ENT_QUOTES)."</a>";            
        } else {
            return "<a href='".XOOPS_URL."/modules/formulize/download.php?element=".$element->getVar('ele_id')."&entry_id=$entry_id'>".htmlspecialchars(strip_tags($displayName),ENT_QUOTES)."</a>";
        }
    }

    // this method will return the displayName for a file (ie: remove the obscuring timestamp on the front, if any)
    function getFileDisplayName($fileName) {
        $fileNameParts = explode("+---+",$fileName);
        $displayName = isset($fileNameParts[1]) ? $fileNameParts[1] : $fileNameParts[0];
        return $displayName;
    }
    
    
}