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
        $element->setVar('ele_value', array(
            'apikey'=>$ele_value['apikey'],
            'clientid'=>$ele_value['clientid'],
            'projectnumber'=>$ele_value['projectnumber'],
            'mimetypes'=>str_replace(" ","",$ele_value['mimetypes']),
            'multiselect'=>$ele_value['multiselect'],
            'upload'=>$ele_value['upload'],
            'includeGoogleDrive'=>$ele_value['includeGoogleDrive'],
            'includeSharedDrives'=>$ele_value['includeSharedDrives'],
            'googleDriveDefaultFolder'=>$ele_value['googleDriveDefaultFolder'],
            'sharedDrivesDefaultFolder'=>$ele_value['sharedDrivesDefaultFolder']
        ));
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

        global $formulize_pickerBoilerPlateIncluded;
        if($formulize_pickerBoilerPlateIncluded !== true) {
            $formulize_pickerBoilerPlateIncluded = true;
            // Thanks to Tomalak at https://stackoverflow.com/questions/680929/how-to-extract-extension-from-filename-string-in-javascript/680982
            $picker = "
            <script type='text/javascript'>
                var mimeTypeExtensions = new Array();
                mimeTypeExtensions['text/html'] = 'html';
                mimeTypeExtensions['text/css'] = 'css';
                mimeTypeExtensions['text/xml'] = 'xml';
                mimeTypeExtensions['image/gif'] = 'gif';
                mimeTypeExtensions['image/jpeg'] = 'jpeg';
                mimeTypeExtensions['application/x-javascript'] = 'js';
                mimeTypeExtensions['application/atom+xml'] = 'atom';
                mimeTypeExtensions['application/rss+xml'] = 'rss';
                mimeTypeExtensions['text/mathml'] = 'mml';
                mimeTypeExtensions['text/plain'] = 'txt';
                mimeTypeExtensions['text/vnd.sun.j2me.app-descriptor'] = 'jad';
                mimeTypeExtensions['text/vnd.wap.wml'] = 'wml';
                mimeTypeExtensions['text/x-component'] = 'htc';
                mimeTypeExtensions['image/png'] = 'png';
                mimeTypeExtensions['image/tiff'] = 'tif';
                mimeTypeExtensions['image/vnd.wap.wbmp'] = 'wbmp';
                mimeTypeExtensions['image/x-icon'] = 'ico';
                mimeTypeExtensions['image/x-jng'] = 'jng';
                mimeTypeExtensions['image/x-ms-bmp'] = 'bmp';
                mimeTypeExtensions['image/svg+xml'] = 'svg';
                mimeTypeExtensions['image/webp'] = 'webp';
                mimeTypeExtensions['application/java-archive'] = 'jar';
                mimeTypeExtensions['application/mac-binhex40'] = 'hqx';
                mimeTypeExtensions['application/msword'] = 'doc';
                mimeTypeExtensions['application/pdf'] = 'pdf';
                mimeTypeExtensions['application/postscript'] = 'ps';
                mimeTypeExtensions['application/rtf'] = 'rtf';
                mimeTypeExtensions['application/vnd.ms-excel'] = 'xls';
                mimeTypeExtensions['application/vnd.ms-powerpoint'] = 'ppt';
                mimeTypeExtensions['application/vnd.wap.wmlc'] = 'wmlc';
                mimeTypeExtensions['application/vnd.google-earth.kml+xml'] = 'kml';
                mimeTypeExtensions['application/vnd.google-earth.kmz'] = 'kmz';
                mimeTypeExtensions['application/x-7z-compressed'] = '7z';
                mimeTypeExtensions['application/x-cocoa'] = 'cco';
                mimeTypeExtensions['application/x-java-archive-diff'] = 'jardiff';
                mimeTypeExtensions['application/x-java-jnlp-file'] = 'jnlp';
                mimeTypeExtensions['application/x-makeself'] = 'run';
                mimeTypeExtensions['application/x-perl'] = 'pl';
                mimeTypeExtensions['application/x-pilot'] = 'prc';
                mimeTypeExtensions['application/x-rar-compressed'] = 'rar';
                mimeTypeExtensions['application/x-redhat-package-manager'] = 'rpm';
                mimeTypeExtensions['application/x-sea'] = 'sea';
                mimeTypeExtensions['application/x-shockwave-flash'] = 'swf';
                mimeTypeExtensions['application/x-stuffit'] = 'sit';
                mimeTypeExtensions['application/x-tcl'] = 'tcl';
                mimeTypeExtensions['application/x-x509-ca-cert'] = 'der';
                mimeTypeExtensions['application/x-xpinstall'] = 'xpi';
                mimeTypeExtensions['application/xhtml+xml'] = 'xhtml';
                mimeTypeExtensions['application/zip'] = 'zip';
                mimeTypeExtensions['audio/midi'] = 'mid';
                mimeTypeExtensions['audio/mpeg'] = 'mp3';
                mimeTypeExtensions['audio/ogg'] = 'ogg';
                mimeTypeExtensions['audio/x-realaudio'] = 'ra';
                mimeTypeExtensions['video/3gpp'] = '3gpp';
                mimeTypeExtensions['video/mpeg'] = 'mpeg';
                mimeTypeExtensions['video/quicktime'] = 'mov';
                mimeTypeExtensions['video/x-flv'] = 'flv';
                mimeTypeExtensions['video/x-mng'] = 'mng';
                mimeTypeExtensions['video/x-ms-asf'] = 'asx';
                mimeTypeExtensions['video/x-ms-wmv'] = 'wmv';
                mimeTypeExtensions['video/x-msvideo'] = 'avi';
                mimeTypeExtensions['video/mp4'] = 'm4v';

                function addFileExtension(name, mimeType) {
                    var re = /(?:\.([^.]+))?$/;
                    var ext = re.exec(name)[1];
                    if(typeof ext === 'undefined' && typeof mimeTypeExtensions[mimeType] !== 'undefined') {
                        name = name+'.'+mimeTypeExtensions[mimeType];
                    }
                    return name;
                }

            </script>
            <style>.googlefile { white-space: pre-line; }</style>
            <script type='text/javascript' src='https://apis.google.com/js/api.js'></script>";
        }

        $picker .= "

        <script type='text/javascript'>

            var developerKey$eleId = '".$ele_value['apikey']."';
            var clientId$eleId = '".$ele_value['clientid']."';
            var appId$eleId = '".$ele_value['projectnumber']."';
            var scope$eleId = 'https://www.googleapis.com/auth/drive';
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
                window.gapi.auth2.init();
                window.gapi.auth2.signIn();
              }
            }

            // Create and render a Picker object for searching images.
            function createPicker$eleId() {
              if (pickerApiLoaded$eleId && oauthToken$eleId) {";

            if($ele_value['includeSharedDrives']) {
                $picker .= "
                var sharedDriveView$eleId = new google.picker.DocsView(google.picker.ViewId.DOCS);
                sharedDriveView$eleId.setEnableDrives(true);";
                if($ele_value['sharedDrivesDefaultFolder']) {
                    $picker .= "
                    sharedDriveView$eleId.setParent('".$ele_value['sharedDrivesDefaultFolder']."');";
                } else {
                    $picker .= "
                    sharedDriveView$eleId.setIncludeFolders(true).setSelectFolderEnabled(false);";
                }
                if($ele_value['mimetypes']) {
                $picker .= "
                    sharedDriveView$eleId.setMimeTypes(\"".$ele_value['mimetypes']."\");";
                }
            }

            if($ele_value['includeGoogleDrive']) {
                $picker .= "
                var googleDriveView$eleId = new google.picker.DocsView(google.picker.ViewId.DOCS);";
                if($ele_value['googleDriveDefaultFolder']) {
                    $picker .= "
                    googleDriveView$eleId.setParent('".$ele_value['googleDriveDefaultFolder']."');";
                } else {
                    $picker .= "
                    googleDriveView$eleId.setIncludeFolders(true).setSelectFolderEnabled(false);";
                }
                if($ele_value['mimetypes']) {
                    $picker .= "
                    googleDriveView$eleId.setMimeTypes(\"".$ele_value['mimetypes']."\");";
                }

            }

            if($ele_value['upload']) {
                $picker .= "
                var uploadView$eleId = new google.picker.DocsUploadView();";
                $uploadFolder = ($ele_value['includeGoogleDrive'] AND $ele_value['googleDriveDefaultFolder']) ? $ele_value['googleDriveDefaultFolder'] : "";
                $uploadFolder = ($ele_value['includeSharedDrives'] AND $ele_value['sharedDrivesDefaultFolder']) ? $ele_value['sharedDrivesDefaultFolder'] : $uploadFolder;
                if($uploadFolder) {
                    $picker .= "
                    uploadView$eleId.setParent('".$uploadFolder."');";
                } else {
                    $picker .= "
                    uploadView$eleId.setIncludeFolders(true);";
                }
                if($ele_value['mimetypes']) {
                    $picker .= "
                    uploadView$eleId.setMimeTypes(\"".$ele_value['mimetypes']."\");";
                }
            }

            $picker .= "
                var picker$eleId = new google.picker.PickerBuilder()";

            if($ele_value['multiselect']) {
                $picker .="
                    .enableFeature(google.picker.Feature.MULTISELECT_ENABLED)";
            }

            $picker .= "
                    .enableFeature(google.picker.Feature.SUPPORT_DRIVES)
                    .setAppId(appId$eleId)
                    .setOAuthToken(oauthToken$eleId)";
            if($ele_value['includeGoogleDrive']) {
                $picker .= "
                    .addView(googleDriveView$eleId)";
            }
            if($ele_value['includeSharedDrives']) {
                $picker .= "
                    .addView(sharedDriveView$eleId)";
            }
            if($ele_value['upload']) {
                $picker .= "
                    .addView(uploadView$eleId)";
            }
            $picker .= "
                    .setDeveloperKey(developerKey$eleId)
                    .setCallback(pickerCallback$eleId)
                    .build();
                 picker$eleId.setVisible(true);
              }
            }

            function pickerCallback$eleId(data) {
                if (data.action == google.picker.Action.PICKED) {
                    for(i in data.docs) {
                        addToList$eleId(addFileExtension(data.docs[i].name, data.docs[i].mimeType), data.docs[i].url, data.docs[i].id, data.docs[i].iconUrl);
                        formulizechanged = 1;
                    }
                }
            }

            function addToList$eleId(name, url, id, iconUrl) {
                if(jQuery('#googlefile_".$markupName."_'+id).length == 0) {";
                    $interactiveMarkup = $isDisabled ? "" : "<a href=\"\" onclick=\"warnAboutGoogleDelete$eleId(\''+id+'\', \''+name.replace(/\\\"/g, '&quot;')+'\', \'".$markupName."\');return false;\"><img src=\"".XOOPS_URL."/modules/formulize/images/x.gif\" /></a><input type=\"hidden\" name=\"".$markupName."[]\" value=\"'+name.replace(/\\\"/g, '&quot;')+'<{()}>'+url+'<{()}>'+id+'<{()}>'+iconUrl+'\" />";
                    if($ele_value['multiselect']==false) {
                        $picker .= "
                    jQuery('#".$markupName."_files').empty();";
                    }
                    $picker .= "
                    jQuery('#".$markupName."_files').append('<div class=\"googlefile googlefile_$eleId\" id=\"googlefile_".$markupName."_'+id+'\"><img src=\"'+iconUrl+'\" /> <a href=\"'+url+'\" target=\"_blank\">'+name+'</a> ".$interactiveMarkup."</div>');
                }
            }

            function warnAboutGoogleDelete$eleId(id, name, markupName) {
                var answer = confirm('" . _AM_GOOGLEFILE_DELETE_WARN . " '+name+'?');
                if(answer) {
                    jQuery(\"#googlefile_\"+markupName+\"_\"+id).remove();
                    formulizechanged = 1;
                }
                return false;
            }

        </script>";

        if(!$isDisabled) {
            $picker .= "<p><input type='button' onclick='loadPicker$eleId();' value='"._AM_GOOGLEFILE_SELECT."'></p>
                <div id='".$markupName."_files'>";
        } else {
            $picker .= "<div>";
        }

        if(count((array) $ele_value['files'])>0) {
            foreach($ele_value['files'] as $file) {
                $interactiveMarkup = $isDisabled ? "" : "<a href=\"\" onclick=\"warnAboutGoogleDelete$eleId('".$file['id']."', '".str_replace('"','\"',htmlspecialchars_decode($file['name'], ENT_QUOTES))."', '".$markupName."');return false;\"><img src=\"".XOOPS_URL."/modules/formulize/images/x.gif\" /></a><input type=\"hidden\" name=\"".$markupName."[]\" value=\"".str_replace('"','\"',htmlspecialchars_decode($file['name'], ENT_QUOTES))."<{()}>".$file['url']."<{()}>".$file['id']."<{()}>".$file['iconUrl']."\" />";
                $interactiveId = $isDisabled ? "" : "id=\"googlefile_".$markupName."_".$file['id']."\"";
                $picker .= "
                <div class=\"googlefile googlefile_$eleId\" $interactiveId><img src=\"".$file['iconUrl']."\" /> <a href=\"".$file['url']."\" target=\"_blank\">".str_replace('"','\"',htmlspecialchars_decode($file['name'], ENT_QUOTES))."</a> ".$interactiveMarkup."</div>";
            }
        }

        $picker .= "</div>";

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
        // rendered hidden elements pass back a string with the separator below, and the name, url, id and iconUrl in this order
        // we serialize the data in an array for saving
        $files = array();
        foreach($value as $fileData) {
            $fileData = explode('<{()}>', $fileData);
            $files[] = array('name'=>htmlspecialchars($fileData[0], ENT_QUOTES), 'url'=>$fileData[1], 'id'=>$fileData[2], 'iconUrl'=>$fileData[3]);
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
        $value = unserialize($value);
        $urls = array();
        foreach($value as $fileData) {
            $GLOBALS['formulize_googleFileUploadElementDisplayName'][$fileData['url']] = $fileData['name']; // set aside in GLOBALS for use in creating download link later
            $urls[] = $fileData['url'];
        }
        return implode(',',$urls);
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
    function formatDataForList($value, $handle="", $entry_id=0) {
        $this->clickable = false; // make urls clickable
        $this->striphtml = false; // remove html tags as a security precaution
        $this->length = 100000; // truncate to a maximum of 2000 characters, and append ... on the end
        // value set to array of URLs by prepareDataForDataset
        $links = array();
        foreach(explode(',',$value) as $url) {
            $links[] = $this->createDownloadLink($url);
        }
        return parent::formatDataForList(implode('<br />',$links)); // always return the result of formatDataForList through the parent class (where the properties you set here are enforced)
    }

    // this method is for the google file upload element only.  It will return a href that links to the actual file.
    function createDownloadLink($url) {
        $displayName = $GLOBALS['formulize_googleFileUploadElementDisplayName'][$url]; // set aside in prepareDataForDataset above
        return "<a href=\"".$url."\" target=\"_blank\">".str_replace('"','\"',htmlspecialchars_decode($displayName, ENT_QUOTES))."</a>";
    }

}
