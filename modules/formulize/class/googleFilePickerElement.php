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

// THIS FILE SHOWS ALL THE METHODS THAT CAN BE PART OF CUSTOM ELEMENT TYPES IN FORMULIZE
// TO SEE THIS ELEMENT IN ACTION, RENAME THE FILE TO dummyElement.php
// There is a corresponding admin template for this element type in the templates/admin folder

require_once XOOPS_ROOT_PATH . "/modules/formulize/class/elements.php"; // you need to make sure the base element class has been read in first!

class formulizeGoogleFilePickerElement extends formulizeformulize {
    
    function __construct() {
        $this->name = "Google File Picker";
        $this->hasData = true; // set to false if this is a non-data element, like the subform or the grid
        $this->needsDataType = false; // set to false if you're going force a specific datatype for this element using the overrideDataType
        $this->overrideDataType = "text"; // use this to set a datatype for the database if you need the element to always have one (like 'date').  set needsDataType to false if you use this.
        $this->adminCanMakeRequired = true; // set to true if the webmaster should be able to toggle this element as required/not required
        $this->alwaysValidateInputs = false; // set to true if you want your custom validation function to always be run.  This will override any required setting that the webmaster might have set, so the recommendation is to set adminCanMakeRequired to false when this is set to true.
        $this->canHaveMultipleValues = false; // set to true if this element can store multiple values at once, such as how a set of checkboxes works
        $this->hasMultipleOptions = false; // set to true if this element has multiple fixed options, such as a radio button set, or checkboxes, or a dropdown list, etc.
        parent::__construct();
    }
    
}

class formulizeGoogleFilePickerElementHandler extends formulizeElementsHandler {
    
    var $db;
    var $clickable; // used in formatDataForList
    var $striphtml; // used in formatDataForList
    var $length; // used in formatDataForList
    
    function __construct($db) {
        $this->db =& $db;
    }
    
    function create() {
        return new formulizeGoogleFilePickerElement();
    }
    
    // this method would gather any data that we need to pass to the template, besides the ele_value and other properties that are already part of the basic element class
    // it receives the element object and returns an array of data that will go to the admin UI template
    // when dealing with new elements, $element might be FALSE
    function adminPrepare($element) {
        $ele_value = array();
        if(is_object($element) AND is_subclass_of($element, 'formulizeformulize')) {
            $ele_value = $element->getVar('ele_value');
        }
        return $ele_value;
    }
    
    // this method would read back any data from the user after they click save in the admin UI, and save the data to the database, if it were something beyond what is handled in the basic element class
    // this is called as part of saving the options tab.  It receives a copy of the element object immediately prior to it being saved, so the element object will have all its properties set as they would be based on the user's changes in the names & settings tab, and in the options tab (the tabs are saved in order from left to right).
    // the exception is the special ele_value array, which is passed separately from the object (this will contain the values the user set in the Options tab)
    // You can modify the element object in this function and since it is an object, and passed by reference by default, then your changes will be saved when the element is saved. Use setVar to set the value of a property. You must do this for the ele_value property if you are changing it!
    // You should return a flag to indicate if any changes were made, so that the page can be reloaded for the user, and they can see the changes you've made here.
    // $ele_value will be only the values parsed out of the Options tab on the element's admin page, which follow the naming convention elements-ele_value -- other values that should be in ele_value will need to be parsed here from $_POST or elsewhere
    function adminSave($element, $ele_value) {
        $changed = false;
        $element->setVar('ele_value', array('apikey'=>$ele_value['apikey'], 'clientid'=>$ele_value['clientid'], 'projectnumber'=>$ele_value['projectnumber'], 'mimetypes'=>str_replace(" ","",$ele_value['mimetypes'])));
        return $changed;
    }
    
    // this method reads the current state of an element based on the user's input, and the admin options, and sets ele_value to what it needs to be so we can render the element correctly
    // it must return $ele_value, with the correct value set in it, so that it will render as expected in the render method
    // $value is the value that was retrieved from the database for this element in the active entry.  It is a raw value, no processing has been applied, it is exactly what is in the database (as prepared in the prepareDataForSaving method and then written to the DB)
    // $ele_value will contain the options set for this element (based on the admin UI choices set by the user, possibly altered in the adminSave method)
    // $element is the element object
    function loadValue($value, $ele_value, $element) {
        // add the saved file info to ele_value as the 'files' key
        $ele_value['files'] = unserialize($value);
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
    // $owner is the user id of the owner of the entry
    // $renderAsHiddenDefault is a flag to control what happens when we render as a hidden element for users who can't normally access the element -- typically we would set the default value inside a hidden element, or the current value if for some reason an entry is passed
    function render($ele_value, $caption, $markupName, $isDisabled, $element, $entry_id, $screen, $owner, $renderAsHiddenDefault = false) {
        
        $eleId = $element->getVar('ele_id');
        
        $picker = "
        
        <script type='text/javascript'>

            var developerKey$eleId = '".$ele_value['apikey']."';
            var clientId$eleId = '".$ele_value['clientid']."'
            var appId$eleId = '".$ele_value['projectnumber']."';
            var scope$eleId = 'https://www.googleapis.com/auth/drive.file';
            var pickerApiLoaded$eleId = false;
            var oauthToken$eleId;
        
            // Use the Google API Loader script to load the google.picker script.
            function loadPicker$eleId() {
              gapi.load('auth2', {'callback': onAuthApiLoad$eleId});              
              gapi.load('picker', {'callback': onPickerApiLoad$eleId});
            }

            function onAuthApiLoad$eleId() {
              window.gapi.auth2.authorize(
                  {
                    'client_id': clientId$eleId,
                    'scope': scope$eleId,
                    'prompt': 'none'
                  },
                  handleAuthResult$eleId);
            }
            
            function onPickerApiLoad$eleId() {
              pickerApiLoaded$eleId = true;
              createPicker$eleId();
            }
        
            function handleAuthResult$eleId(authResult) {
              if (authResult && !authResult.error) {
                oauthToken$eleId = authResult.access_token;
                createPicker$eleId();
              } else if(authResult) {
                // need to add in here the option for using this feature without Google sign in as the default authentication for the website!
                // probably need to use window.gapi.auth2.init and then .signin ??
                // OR WE NEED TO MAKE IT A USER OPTION IN THE ELEMENT, WHETHER TO PROMPT USER OR NOT...OR WE CHECK IF GOOGLE AUTHENTICATION IS ON AND PROMPT USER WHEN IT IS NOT ON.
              }
            }
        
            // Create and render a Picker object for searching images.
            function createPicker$eleId() {
              if (pickerApiLoaded$eleId && oauthToken$eleId) {
                var view$eleId = new google.picker.View(google.picker.ViewId.DOCS);";

            if($ele_value['mimetypes']) {
                $picker .= "
                view$eleId.setMimeTypes(\"".$ele_value['mimetypes']."\");";
            }
            
            $picker .= "
                var picker$eleId = new google.picker.PickerBuilder()
                    .enableFeature(google.picker.Feature.NAV_HIDDEN)
                    .enableFeature(google.picker.Feature.MULTISELECT_ENABLED)
                    .setAppId(appId$eleId)
                    .setOAuthToken(oauthToken$eleId)
                    .addView(view$eleId)
                    .addView(new google.picker.DocsUploadView())
                    .setDeveloperKey(developerKey$eleId)
                    .setCallback(pickerCallback$eleId)
                    .build();
                 picker$eleId.setVisible(true);
              }
            }
            
            function pickerCallback$eleId(data) {
                if (data.action == google.picker.Action.PICKED) {
                    for(i in data.docs) {
                        addToList$eleId(data.docs[i].name, data.docs[i].url, data.docs[i].id);
                    }
                }
            }

            function addToList$eleId(name, url, id) {
                if(jQuery('#googlefile_".$markupName."_'+id).length == 0) {";
                    $interactiveMarkup = $isDisabled ? "" : "<a href=\"\" onclick=\"warnAboutGoogleDelete$eleId(\''+id+'\', \''+name.replace(/\\\"/g, '&quot;')+'\');return false;\"><img src=\"".XOOPS_URL."/modules/formulize/images/x.gif\" /></a><input type=\"hidden\" name=\"".$markupName."[]\" value=\"'+name.replace(/\\\"/g, '&quot;')+'<{()}>'+url+'<{()}>'+id+'\">";
                    $picker .= "
                    jQuery('#".$markupName."_files').append('<div class=\"googlefile googlefile_$eleId\" id=\"googlefile_".$markupName."_'+id+'\"><a href=\"'+url+'\" target=\"_blank\">'+name+'</a> ".$interactiveMarkup."</div>');
                }
            }
            
            function warnAboutGoogleDelete$eleId(id, name) {
                var answer = confirm('" . _AM_GOOGLEFILE_DELETE_WARN . " '+name+'?');
                if(answer) {
                    jQuery(\"#googlefile_".$markupName."_\"+id).remove();
                }
                return false;
            }";
            
            if(count($ele_value['files'])>0) {
                $picker .= "
                jQuery(document).ready(function() {";
                foreach($ele_value['files'] as $file) {
                    $picker .= "
                    addToList$eleId(\"".str_replace('"','\"',htmlspecialchars_decode($file['name'], ENT_QUOTES))."\", \"".$file['url']."\", \"".$file['id']."\");";
                }
                $picker .= "
                });";
            }
            $picker .= "
            
        </script>";
        
        static $boilerplateDrawn = false;
        if(!$boilerplateDrawn) {
            $picker .= "
        <style>.googlefile { white-space: pre-line; }</style>
        <script type='text/javascript' src='https://apis.google.com/js/api.js'></script>";
            $boilerplateDrawn = true;
        }
        
        if(!$isDisabled) {
            $picker .= "<p><input type='button' onclick='loadPicker$eleId();' value='"._AM_GOOGLEFILE_SELECT."'></p>";
        }
        $picker .= "<p id='".$markupName."_files'></p>";
        
        $element = new xoopsFormLabel($caption, $picker);
        
        return $element;
    }
    
    // this method returns any custom validation code (javascript) that should figure out how to validate this element
    // 'myform' is a name enforced by convention that refers to the form where this element resides
    // use the adminCanMakeRequired property and alwaysValidateInputs property to control when/if this validation code is respected
    function generateValidationCode($caption, $markupName, $element, $entry_id) {
        $eleId = $element->getVar('ele_id');
        $validationmsg = _AM_GOOGLEFILE_REQUIRED." '".$caption."'";
        $validationmsg = str_replace("'", "\'", stripslashes( $validationmsg ) );
        $validationCode = array();
        $validationCode[] = "if(jQuery('.googlefile_$eleId').length == 0) {\n";
        $validationCode[] = "  window.alert('"._AM_GOOGLEFILE_REQUIRED."');\n return false;\n ";
        $validationCode[] = "}\n";
        return $validationCode;
    }
    
    // this method will read what the user submitted, and package it up however we want for insertion into the form's datatable
    // You can return {WRITEASNULL} to cause a null value to be saved in the database
    // $value is what the user submitted
    // $element is the element object
	// $entry_id is the ID number of the entry that this data is being saved into. Can be "new", or null in the event of a subformblank entry being saved.
    // $subformBlankCounter is the instance of a blank subform entry we are saving. Multiple blank subform values can be saved on a given pageload and the counter differentiates the set of data belonging to each one prior to them being saved and getting an entry id of their own.
    function prepareDataForSaving($value, $element, $entry_id=null, $subformBlankCounter=null) {
        // rendered hidden elements pass back a string with the separator below, and the name, url and id in this order
        // we serialize the data in an array for saving
        $files = array();
        foreach($value as $fileData) {
            $fileData = explode('<{()}>', $fileData);
            $files[] = array('name'=>htmlspecialchars($fileData[0], ENT_QUOTES), 'url'=>$fileData[1], 'id'=>$fileData[2]);
        }
        return serialize($files);
    }
    
    // this method will handle any final actions that have to happen after data has been saved
    // this is typically required for modifications to new entries, after the entry ID has been assigned, because before now, the entry ID will have been "new"
    // value is the value that was just saved
    // element_id is the id of the element that just had data saved
    // entry_id is the entry id that was just saved
    // ALSO, $GLOBALS['formulize_afterSavingLogicRequired']['elementId'] = type , must be declared in the prepareDataForSaving step if further action is required now -- see fileUploadElement.php for an example
    function afterSavingLogic($value, $element_id, $entry_id) {
    }
    
    // this method will prepare a raw data value from the database, to be included in a dataset when formulize generates a list of entries or the getData API call is made
    // in the standard elements, this particular step is where multivalue elements, like checkboxes, get converted from a string that comes out of the database, into an array, for example
    // $value is the raw value that has been found in the database
    // $handle is the element handle for the field that we're retrieving this for
    // $entry_id is the entry id of the entry in the form that we're retrieving this for
    function prepareDataForDataset($value, $handle, $entry_id) {
        return $value; // we're not making any modifications for this element type
    }
    
    // this method will take a text value that the user has specified at some point, and convert it to a value that will work for comparing with values in the database.  This is used primarily for preparing user submitted text values for saving in the database, or for comparing to values in the database, such as when users search for things.  The typical user submitted values would be coming from a condition form (ie: fieldX = [term the user typed in]) or other situation where the user types in a value that needs to interact with the database.
    // it is only necessary to do special logic here if the values stored in the database do not match what users would be typing, ie: you're using coded numbers in the database, but displaying text on screen to users
    // this would be where a Yes value would be converted to a 1, for example, in the case of a yes/no element, since 1 is how yes is represented in the database for that element type
    // $partialMatch is used to indicate if we should search the values for partial string matches, like On matching Ontario.  This happens in the getData function when processing filter terms (ie: searches typed by users in a list of entries)
    // if $partialMatch is true, then an array may be returned, since there may be more than one matching value, otherwise a single value should be returned.
    // if literal text that users type can be used as is to interact with the database, simply return the $value 
    function prepareLiteralTextForDB($value, $element, $partialMatch=false) {
        return $value;
    }
    
    // this method will format a dataset value for display on screen when a list of entries is prepared
    // for standard elements, this step is where linked selectboxes potentially become clickable or not, among other things
    // Set certain properties in this function, to control whether the output will be sent through a "make clickable" function afterwards, sent through an HTML character filter (a security precaution), and trimmed to a certain length with ... appended.
    function formatDataForList($value, $handle, $entry_id) {
        $this->clickable = true; // make urls clickable
        $this->striphtml = true; // remove html tags as a security precaution
        $this->length = 100; // truncate to a maximum of 100 characters, and append ... on the end
        
        return parent::formatDataForList($value); // always return the result of formatDataForList through the parent class (where the properties you set here are enforced)
    }
    
}