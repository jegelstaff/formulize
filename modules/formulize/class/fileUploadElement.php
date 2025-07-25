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

class formulizeFileUploadElement extends formulizeElement {

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

#[AllowDynamicProperties]
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
            $changed = true;
        }
        $element->setVar('ele_value',$ele_value);
        return $changed;
    }

    // this method reads the current state of an element based on the user's input, and the admin options, and sets ele_value to what it needs to be so we can render the element correctly
    // it must return $ele_value, with the correct value set in it, so that it will render as expected in the render method
    // $value is the value that was retrieved from the database for this element in the active entry.  It is a raw value, no processing has been applied, it is exactly what is in the database (as prepared in the prepareDataForSaving method and then written to the DB)
    // $ele_value will contain the options set for this element (based on the admin UI choices set by the user, possibly altered in the adminSave method)
    // $element is the element object
    function loadValue($value, $ele_value, $element) {
        $value = unserialize($value); // what we've got in the database is a serialized array, first key is filename, second key is flag for whether the filename is for real (might be an error message)
        $ele_value[3] = (isset($value['name']) AND $value['name']) ? $value['name'] : null; // add additional keys to ele_value where we'll put the value that is coming from the database for user's to see, plus other flags and so on
        $ele_value[4] = $this->getFileDisplayName(strval($ele_value[3]));
        $ele_value[5] = (isset($value['isfile']) AND $value['isfile']) ? $ele_value[3] : null;
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
    // $screen is the screen object that is in effect, if any (may be null)
    function render($ele_value, $caption, $markupName, $isDisabled, $element, $entry_id, $screen, $owner) {
        // ele_value[3] is the fileName or error message
        // ele_value[4] is the displayName version of the fileName (only going to be diff from fileName if there is no direct linking to files allowed)
        // ele_value[5] is the flag for whether there's a file or not
        static $fileDeleteCode;
        $introToUploadBox = "";
        if($isDisabled) {
            $displayName = $ele_value[4];
            $url = $this->createFileURL($element, $entry_id, serialize(array('name'=>$ele_value[3], 'isfile'=>$ele_value[5])));
            $link = $this->createDownloadLink($element, $url, $displayName);
            $formElement = new xoopsFormLabel($caption, $link);
        } else {
            // create the file upload element, and also a hidden element with the correct markup name.  That hidden value will trigger the correct saving logic, and is necessary because file upload elements are excluded from POST.
            if(!$ele_value[5]) {
                $introToUploadBox = "<div id='formulize_fileStatus_".$element->getVar('ele_id')."_$entry_id' class='no-print formulize-fileupload-element'>".$ele_value[3]."</div>";
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
                        formulizechanged = 1;
                        response = eval('('+response+')');
                        window.document.getElementById('formulize_fileStatus_'+response.element_id+'_'+response.entry_id).innerHTML = '';
                        jQuery('#fileUploadUI_de_".$element->getVar('id_form')."_'+response.entry_id+'_'+response.element_id).show();
                        var fileExistsFlag = 'formulizeFilede_".$element->getVar('id_form')."_'+response.entry_id+'_'+response.element_id+'Exists = false';
                        eval(fileExistsFlag)
                    }

                    function formulize_delete_failed() {
                        alert('" . _AM_UPLOAD_DELETE_FAIL . "');
                    }

                    </script>";

                    $introToUploadBox .= $fileDeleteCode;
                } elseif($element->getVar('ele_required')) { // just set the flag that tells the required element logic that the file is present
                    $introToUploadBox .= "<script type='text/javascript'>
                    var formulizeFile".$markupName."Exists = true;
                    </script>";
                }
                // have to spoof the raw value from the database, in order to generate the URL for use in the download link, since that's what the URL method is expecting
                $fakeRawValue = serialize(array('name'=>$ele_value[3], 'isfile'=>$ele_value[5]));
                $introToUploadBox .= "<div id='formulize_fileStatus_".$element->getVar('ele_id')."_$entry_id' class='no-print formulize-fileupload-element'>".$this->createDownloadLink($element, $this->createFileURL($element, $entry_id, $fakeRawValue), $ele_value[4])." &mdash; <a class='formulize_fileUploadDeleteButton' href='' onclick='warnAboutFileDelete(\"".str_replace("de_","formulize_",$markupName)."\", \"".$element->getVar('ele_id')."\", \"$entry_id\");return false;'>" . _DELETE . "</a></div>";
            } else {
                $introToUploadBox = "<div id='formulize_fileStatus_".$element->getVar('ele_id')."_$entry_id' class='no-print formulize-fileupload-element'></div>";
            }
            $displayUploadUI = $ele_value[5] ? "style='display: none;'" : "";
            $allowedExtensions = explode(',',strtolower(trim($ele_value[1])));
            $acceptExtensions = array();
            foreach($allowedExtensions as $ext) {
                if($ext) {
                    $acceptExtensions[] = '.'.str_replace('.','',$ext);
                }
            }
            $acceptExtensions = implode(',',$acceptExtensions);
            $acceptAttribute = $acceptExtensions ? 'accept="'.$acceptExtensions.'"' : '';
            $introToUploadBox .= "<input type='hidden' name='MAX_FILE_SIZE' value='".($ele_value[0]*1048576)."' /><div id='fileUploadUI_".$markupName."' $displayUploadUI><input type='file' name='fileupload_".$markupName."' size=50 id='".$markupName."' onchange=\"javascript:formulizechanged=1;\" $acceptAttribute class='no-print' aria-describedby='$markupName-help-text' /><input type='hidden' id='$markupName' name='$markupName' value='$markupName' /></div>";
            $formElement = new xoopsFormLabel($caption, $introToUploadBox);
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
        if($element->getVar('ele_required')) { // need to include this only if the admin wants to force a value for this element, and there's no file selected already
            $validationCode[] = "if(formulizechanged && myform.fileupload_{$markupName}.value == '' && (typeof formulizeFile".$markupName."Exists == 'undefined' || formulizeFile".$markupName."Exists == false)) {\n";
            $validationCode[] = "  window.alert('{$validationmsg}');\n myform.fileupload_{$markupName}.focus();\n return false;\n ";
            $validationCode[] = "} else if(formulizechanged && myform.fileupload_{$markupName}.value == '' && typeof formulizeFile".$markupName."Exists != 'undefined' && formulizeFile".$markupName."Exists == true) {\n";
            $validationCode[] = "  myform.{$cueName}.name = 'skipit!';\n";
            $validationCode[] = "} else if(formulizechanged) {\n";
            $validationCode[] = "  myform.{$cueName}.name = '{$cueName}';\n";
            $validationCode[] = "}\n";
        } else { // file not required, if there's going to be a bonafide form submission, remove the cue if there's no file here
            $validationCode[] = "if(formulizechanged && myform.fileupload_{$markupName}.value == '') {\n"; // if no file has been selected, then neuter the cue element that tells us we need to handle this one when saving
            $validationCode[] = "  myform.{$cueName}.name = 'skipit!';\n";
            $validationCode[] = "} else if(formulizechanged) {\n";
            $validationCode[] = "  myform.{$cueName}.name = '{$cueName}';\n";
            $validationCode[] = "}\n";
        }
        return $validationCode;
    }

    // this method will read what the user submitted, and package it up however we want for insertion into the form's datatable
    // You can return {WRITEASNULL} to cause a null value to be saved in the database
    // $value is what the user submitted
    // $element is the element object
    function prepareDataForSaving($value, $element) {

        // mimetype map - thanks to https://stackoverflow.com/questions/7519393/php-mime-types-list-of-mime-types-publically-available
        // file defines $mime_types_map, array of key=>value pairs that is extensions and mime types
        include XOOPS_ROOT_PATH."/modules/formulize/include/mime_types_map.php";

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
                if(!$allowedExtensions OR in_array($extension,explode(",",$allowedExtensions))) {
                    $deducedMimeType = mime_content_type($_FILES[$fileKey]['tmp_name']);
                    $browserMimeType = $_FILES[$fileKey]['type'];
                    $extensionMimeType = isset($mime_types_map[$extension]) ? $mime_types_map[$extension] : false;
                    // if we accept all files, or this is a type that we don't have records for (at least it matched literal extension), or some of the mime type info is in agreement (if the extension type is a mismatch, maybe the browser type can redeem things?)
                    if(!$allowedExtensions
                        OR !$extensionMimeType
                        OR $extensionMimeType == $deducedMimeType
                        OR $browserMimeType == $deducedMimeType) {
                        $fileExtensionOK = true;
                    }
                }
            }
            // catalogue the mime type if it wasn't in our list
            $this->logMissingMimeType($extension,$deducedMimeType,$mime_types_map);
            $this->logMissingMimeType($extension,$browserMimeType,$mime_types_map);
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
            list($fieldtype, $de, $form_id, $entry_id, $element_id) = explode('_', $fileKey);
            $dataHandler = new formulizeDataHandler($form_id);
            // Return the existing serialized value in the database. This should never happen because validation javascript should eliminate the cue in the relevant cases.
            return $dataHandler->getElementValueInEntry($entry_id, $element_id);
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
            print "<script>alert(\"".str_replace('"','\"',$value)."\");</script>";
        }
        if(!is_array($value)) {
            return "{WRITEASNULL}";
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
        $url = $this->createFileURL($this->get($handle), $entry_id, $value);
        // store the displayName in case we need to format this in a list later -- only the URL will be passed to the formatDataForList method, since that's all we're passing back here, so we need another way of getting the displayName into that method
        $value = unserialize($value);
        $name = isset($value['name']) ? $value['name'] : '';
        $GLOBALS['formulize_fileUploadElementDisplayName'][$entry_id][$handle] = $this->getFileDisplayName($name);
        return $url;
    }

    // this method will take a text value that the user has specified at some point, and convert it to a value that will work for comparing with values in the database.  This is used primarily for preparing user submitted text values for saving in the database, or for comparing to values in the database.  The typical user submitted values would be coming from a condition form (ie: fieldX = [term the user typed in]) or other situation where the user types in a value that needs to interact with the database.
    // this would be where a Yes value would be converted to a 1, for example, in the case of a yes/no element, since 1 is how yes is represented in the database for that element type
    function prepareLiteralTextForDB($value, $element, $partialMatch=false) {
        return $value;
    }

    // this method will format a dataset value for display on screen when a list of entries is prepared
    // for standard elements, this step is where linked selectboxes potentially become clickable or not, among other things
    // Set certain properties in this function, to control whether the output will be sent through a "make clickable" function afterwards, sent through an HTML character filter (a security precaution), and trimmed to a certain length with ... appended.
    function formatDataForList($value, $handle="", $entry_id=0, $textWidth=100) {
        // $value will be the url as determined in prepareDataForDataset above...or an error message, etc, if there's no valid file
        $this->clickable = false; // make urls clickable
        $this->striphtml = false; // remove html tags as a security precaution
        $this->length = 2000; // truncate to a maximum of 2000 characters, and append ... on the end
        $displayName = $GLOBALS['formulize_fileUploadElementDisplayName'][$entry_id][$handle]; // set aside in GLOBALS by the prepareDataForDataset method above
        $value = strstr($value, 'http') ? $this->createDownloadLink($this->get($handle), $value, $displayName) : $value; // we make the clickable links manually here, since we don't just want the URL part to become a link, we want to wrap the display name in a link to the URL
        return parent::formatDataForList($value); // always return the result of formatDataForList through the parent class (where the properties you set here are enforced)
    }

    // this method returns the URL for a file, not a href - or the text value that is in place of a file name, such as an error message
    // $element is the element object
    // $entry_id is the ID number of this entry
    // $value is the serialized raw value from the database for this particular entry
    function createFileURL($element, $entry_id, $value) {
			$value = unserialize($value);
			$fileName = isset($value['name']) ? $value['name'] : '';
			$isFile = isset($value['isfile']) ? $value['isfile'] : '';
			if($isFile) {
				$ele_value = $element->getVar('ele_value');
				$basePath = "/uploads/formulize_".$element->getVar('id_form')."_".$entry_id."_".$element->getVar('ele_id')."/";
				if($ele_value[2]) { // users can connect directly to file or not?
					return XOOPS_URL.$basePath.rawurlencode($fileName);
				} else {
					$fileModTime = fileatime(XOOPS_ROOT_PATH.$basePath.$fileName); // add mod time to change the URL so we don't serve an old file because of browser caching
					return XOOPS_URL."/modules/formulize/download.php?element=".$element->getVar('ele_id')."&entry_id=$entry_id&m=$fileModTime";
				}
			} else {
				return $fileName; // may be an error message or something like that
			}
    }

    // this method is for the file upload element only.  It will return a href that links to the actual file.
    function createDownloadLink($element, $url, $displayName) {
        $ele_value = $element->getVar('ele_value');
        if(fileNameHasImageExtension($displayName)) {
            $linkContents = $this->createImageTag($url, $displayName);
        } else {
            $linkContents = htmlspecialchars(strip_tags($displayName),ENT_QUOTES);
        }
        if($ele_value[2]) { // files we link to directly get a '_blank' target
            return "<a href='".$url."' target='_blank'>".$linkContents."</a>";
        } else {
            return "<a href='".$url."'>".$linkContents."</a>";
        }
    }

    // this method will return the displayName for a file (ie: remove the obscuring timestamp on the front, if any)
    function getFileDisplayName($fileName) {
			  $fileNameParts = explode("+---+",$fileName);
				$displayName = isset($fileNameParts[1]) ? $fileNameParts[1] : $fileNameParts[0];
        return $displayName;
    }

    // this method will write the extension and mimeType to a list for later review
    function logMissingMimeType($extension, $mimeType, $map) {
        if(!isset($map[$extension]) OR $map[$extension] != $mimeType) {
            $missingList = file(XOOPS_ROOT_PATH.'/uploads/missingMimeTypes.txt');
            $line = "$extension,$mimeType\n";
            if(!is_array($missingList) OR !in_array($line,$missingList)) {
                $missingMimeTypes = fopen(XOOPS_ROOT_PATH.'/uploads/missingMimeTypes.txt', 'a');
                fwrite($missingMimeTypes, $line);
                fclose($missingMimeTypes);
            }
        }
    }

		/**
		 * Create an HTML img tag with the src set as the url for the image. Creates a thumbnail version of the image if one does not exist already.
		 *
		 * @param string $url The URL used to access the image.
		 * @param string $displayName Optional. The name of the file/image, for use in the title attribute.
		 * @param string $class Optional. The class to assign to the img tag. Defaults to 'formulize-uploaded-image-thumbnail'
		 * @return string Returns the HTML img tag referring to the image
		 */
		function createImageTag($url, $displayName='', $class='formulize-uploaded-image-thumbnail') {
			$result = $this->getThumbnailUrl($url, $displayName);
			if(is_array($result) AND $result['success']) {
				$url = $result['result'];
				return "<img class='".htmlspecialchars(strip_tags($class), ENT_QUOTES)."' title='".htmlspecialchars(strip_tags($displayName),ENT_QUOTES)."' src='$url' />";
			} else {
				return $result['result']; // return the error message
			}
		}

		/**
		 * Return the URL for the thumbnail version of an image, and create the thumbnail if necessary
		 *
		 * @param string $url The URL used to access the image.
		 * @return string The URL of the thumbnail version of the image. Or the URL passed in if the URL is not for an image.
		 */
		function getThumbnailUrl($url) {

			try {
				list($entry_id, $element_id, $fid) = $this->extractEntryIdAndElementIdFromUrl($url);
				$data_handler = new formulizeDataHandler($fid);
				$fileInfo = $data_handler->getElementValueInEntry($entry_id, $element_id);
				$fileInfo = unserialize($fileInfo);
				if(fileNameHasImageExtension($fileInfo['name'])) {
					$dotPos = strrpos($fileInfo['name'], '.');
					$fileExtension = strtolower(substr($fileInfo['name'], $dotPos+1));
					$thumbFileName = substr_replace($fileInfo['name'], ".thumb.$fileExtension", $dotPos);
					$path = XOOPS_ROOT_PATH."/uploads/formulize_".$fid."_".$entry_id."_".$element_id."/".$fileInfo['name'];
					$thumbPath = XOOPS_ROOT_PATH."/uploads/formulize_".$fid."_".$entry_id."_".$element_id."/".$thumbFileName;
					if(strstr($url, XOOPS_URL."/modules/formulize/download.php?element=")) {
						$thumbUrl = $url . "&size=thumb";
					} else {
						$thumbUrl = str_replace($fileInfo['name'], $thumbFileName, $url);
					}
					if(!file_exists($thumbPath)) {
						$imageInfo = getimagesize($path);
						if ($imageInfo === false) {
							return ["success" => false, "result" => _formulize_COULD_NOT_GENERATE_THUMBNAIL . ' (' . _formulize_IMAGE_NOT_FOUND . ')'];
						} else {
							$width = $imageInfo[0];
							$height = $imageInfo[1];
						}
						if(($width * $height * 4 * 1.8) > ini_get('memory_limit')) {
							return ["success" => false, "result" => _formulize_COULD_NOT_GENERATE_THUMBNAIL . ' (' . _formulize_IMAGE_TOO_LARGE . ')'];
						}
						$image = null;
						switch($fileExtension) {
							case 'gif':
								$image = imagecreatefromgif($path);
								break;
							case 'png':
								$image = imagecreatefrompng($path);
								break;
							case 'webp':
								$image = imagecreatefromwebp($path);
								break;
							case 'jpg':
							case 'jpeg':
								$image = imagecreatefromjpeg($path);
								break;
						}
						$exif = exif_read_data($path);
						if ($image) {
							if($exif AND isset($exif['Orientation']))	{
								$orientation = $exif['Orientation'];
								if ($orientation == 6 OR $orientation == 5) { $image = imagerotate($image, 270, 0); }
								if ($orientation == 3 OR $orientation == 4) { $image = imagerotate($image, 180, 0); }
								if ($orientation == 8 OR $orientation == 7) { $image = imagerotate($image, 90, 0); }
								if ($orientation == 5 OR $orientation == 4 OR $orientation == 7) { imageflip($image, IMG_FLIP_HORIZONTAL); }
							}
							$image = imagescale($image, 200);
							switch($fileExtension) {
								case 'gif':
									imagegif($image, $thumbPath);
									break;
								case 'png':
									imagepng($image, $thumbPath);
									break;
								case 'webp':
									imagewebp($image, $thumbPath);
									break;
								case 'jpg':
								case 'jpeg':
									imagejpeg($image, $thumbPath);
									break;
							}
						}
					}
					$url = $thumbUrl;
				}
			} catch (Exception $e) {
				return ["success" => false, "result" => _formulize_COULD_NOT_GENERATE_THUMBNAIL . ' (' . $e->getMessage() . ')'];
			}
			return ["success" => true, "result" => $url];
		}

		/**
		 * Get the entryId and elementId and form Id from the URL for an image. Parsing depends on the kind of URL
		 *
		 * @param string $url The URL used to access the image.
		 * @return array An array of the entry Id and element Id and form Id
		 */
		function extractEntryIdAndElementIdFromUrl($url) {
			if(strstr($url, XOOPS_URL."/modules/formulize/download.php?element=")) {
				$urlParts = explode('?', $url);
				$urlParams = explode('&', $urlParts[1]);
				$elementIdParamParts = explode('=',$urlParams[0]);
				$entryIdParamParts = explode('=',$urlParams[1]);
				$elementId = $elementIdParamParts[1];
				$entryId = $entryIdParamParts[1];
			} else {
				$metaData = str_replace(XOOPS_URL."/uploads/formulize_", "", $url);
				$metaData = explode('/', $metaData);
				$metaDataParts = explode('_',$metaData[0]);
				$elementId = $metaDataParts[2];
				$entryId = $metaDataParts[1];
			}
			$element_handler = xoops_getmodulehandler('elements', 'formulize');
			$fid = 0;
			if($elementObject = $element_handler->get($elementId)) {
				$fid = $elementObject->getVar('id_form');
			}
			return array($entryId, $elementId, $fid);
		}
}

/**
 * Take the value of a file upload element, and return an image tag if applicable
 *
 * @param array $entryOrDataset The record from a dataset, or the entire dataset, as returned from getData
 * @param string $elementHandle The element handle of the file upload element we're working with
 * @param int $dataSetKey Optional. The key in the dataset array of the entry record we want to work with. Required if $entryOrDataset is the entire dataset.
 * @param int $localId Optional. The ordinal id of the instance of the element handle we want to work with. Only required if there are multiple entries represented in this dataset record which all include data attached to this element handle, ie: if the handle is on the many side of a one to many connection in the dataset.
 * @return string Returns the an HTML img tag referring to the file if the file is an image, or returns the url for the file otherwise. If the file upload failed, this will return the error message from when the upload failed.
 */
function displayFileImage($entryOrDataset, $elementHandle, $dataSetKey=null, $localId='NULL') {
	$displayName = "";
	$url = getValue($entryOrDataset, $elementHandle, $dataSetKey, $localId);
	$element_handler = xoops_getmodulehandler('fileUploadElement', 'formulize');
	list($entryId, $elementId, $fid) = $element_handler->extractEntryIdAndElementIdFromUrl($url);
	// lookup the name
	if($elementObject = $element_handler->get($elementId)) {
		$dataHandler = new formulizeDataHandler($elementObject->getVar('id_form'));
		$value = $dataHandler->getElementValueInEntry($entryId, $elementId);
		$value = unserialize($value);
		$name = isset($value['name']) ? $value['name'] : '';
		$displayName = $element_handler->getFileDisplayName($name);
	}
	if(fileNameHasImageExtension($displayName)) {
    return $element_handler->createImageTag($url, $displayName);
	}
  return $url;
}

/**
 * Take the value of a file upload element, and return an anchor tag of the image thumbnail if applicable
 *
 * @param array $entryOrDataset The record from a dataset, or the entire dataset, as returned from getData
 * @param string $elementHandle The element handle of the file upload element we're working with
 * @param int $dataSetKey Optional. The key in the dataset array of the entry record we want to work with. Required if $entryOrDataset is the entire dataset.
 * @param int $localId Optional. The ordinal id of the instance of the element handle we want to work with. Only required if there are multiple entries represented in this dataset record which all include data attached to this element handle, ie: if the handle is on the many side of a one to many connection in the dataset.
 * @return string Returns the an HTML a tag referring to the file if the file is an image, and using the thumbnail as the clickable element, or returns the url for the file otherwise. If the file upload failed, this will return the error message from when the upload failed.
 */
function displayFileImageLink($entryOrDataset, $elementHandle, $dataSetKey=null, $localId='NULL') {
	$image = displayFileImage($entryOrDataset, $elementHandle, $dataSetKey=null, $localId='NULL');
	if(substr($image, 0, 4) == '<img') {
		$url = getValue($entryOrDataset, $elementHandle, $dataSetKey, $localId);
		$image = "<a href='$url' target='_blank'>$image</a>";
	}
	return $image;
}

/**
 * Check if a filename has one of various common image file type extensions.
 *
 * @param string $displayName The name of the file
 * @return boolean Return true or false depending if the file has a matching extension. If there is no displayName, return false.
 */
function fileNameHasImageExtension($displayName) {
    if(!$displayName) { return false; }
    $dotPos = strrpos($displayName, '.');
    $fileExtension = substr($displayName, $dotPos);
    $imageTypes = array('.gif', '.jpg', '.png', '.jpeg', '.webp');
    return in_array(strtolower($fileExtension), $imageTypes);
}



