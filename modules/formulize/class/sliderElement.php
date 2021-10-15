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

class formulizeSliderElement extends formulizeformulize {
    function __construct() {
        $this->name = "Range Slider";
        $this->hasData = true;
        $this->needsDataType = false; //should always take integer
        $this->overrideDataType = 'integer';
        $this->adminCanMakeRequired = true;
        $this->alwaysValideInputs = false; //no validation required
        parent::__construct();
    }
}

class formulizeSliderElementHandler extends formulizeElementsHandler {
    var $db;
    var $clickable; // used in formatDataForList
    var $striphtml; // used in formatDataForList
    var $length; // used in formatDataForList

    function __construct($db) {
        $this->db =& $db;
    }

    function create() {
        return new formulizeSliderElement();
    }

    // Gathers data to pass to the template 
    // Excludes $ele_value and other properties that are part of the basic element class
    // Receives the element object
    // Returns array of data to the admin UI template
    // For new elements $element might be FALSE
    function adminPrepare($element) {
        $ele_value = $element ? $element->getVar('ele_value') : array();

        $formlink = createFieldList($ele_value[3], true);
        if (!$element) {
            //Min Velue
            $ele_value[0] = 0;
            //Max Value
            $ele_value[1] = 100;
            //Step size
            $ele_value[2] = 10;
        }
        return array('formlink'=>$formlink->render(), 'ele_value'=>$ele_value);
    }

    // Gather any data to pass to template besides the ele_value
    // Receives the element object
    // Returns an array of data that will go to the admin UI template
    // When dealing with new elements, $element might be FALSE
    function adminSave($element, $ele_value) {
        $changed = false;
        $element->setVar('ele_value', $ele_value);
        return $changed;
    }

    // Reads current state of element, updates ele_value to a renderable state
    function loadValue($value, $ele_value, $element) {
        $ele_value[3] = $value;
        return $ele_value;
    }

    // Renders the element for display in a form
    // Caption is pre-prepared and passed in separately from the element object
    // If element is disabled return a label with some version of the elements value 
    // $ele_value contains options for this element
    // $caption is the prepared caption for the element
    // $markupName name of rendered element in the HTML
    // $isDisabled flags whether the element should be rendered or not
    // $element is the element object
    // $entry_id is the ID of the entry for the element
    function render($ele_value, $caption, $markupName, $isDisabled, $element, $entry_id) {
        $slider_html = "<input type=\"range\" ";
        $slider_html .= "name=\"{$markupName}\"";
        $slider_html .= "id=\"{$markupName}\"";
        $slider_html .= "min=\"{$ele_value[0]}\" ";
        $slider_html .= "max=\"{$ele_value[1]}\" ";
        $slider_html .= "step=\"{$ele_value[2]}\" ";
        $slider_html .= "value=\"{$ele_value[3]}\" ";
        $slider_html .= "oninput=\"updateTextInput(value);formulizechanged=1;\">";
        $slider_html .= "</input>";

        $value_html = "<br><output id=\"rangeValue\" type=\"text\" size=\"2\"";
        $value_html.= "for=\"{$markupName}\"";
        $value_html .= ">{$ele_value[3]}<output>";

        $form_slider_value = new XoopsFormLabel($caption, $value_html);
        $form_slider = new XoopsFormLabel($caption, $slider_html);

        $update_script = "<script type=\"text/javascript\">";
        $update_script .= "function updateTextInput(val) {";
        $update_script .= "document.getElementById('rangeValue').value=val;}\n";
        $update_script .= "document.getElementById('rangeValue').value=document.getElementById('{$markupName}').value;\n";
        $update_script .= "</script>";

        if($isDisabled) {
            $renderedValue = $form_slider_value->render();
            $form_ele = new XoopsFormLabel($caption, "$renderedValue");
        } else {
            $renderedSlider = $form_slider->render();
            $renderedValue = $form_slider_value->render();
            $form_ele = new XoopsFormLabel($caption, "<nobr>$renderedSlider $renderedValue</nobr>$update_script");
        }

        return $form_ele;
    }

    // Returns any custom validation code (javascript) to validate this element
    // 'myform' is a name enforced by convention to refer to the current form 
    // adminCanMakeRequired/alwaysValidateInputs properties control usage
    function generateValidationCode($caption, $markupName, $element, $entry_id) {
    }

    // Reads what the user submitted and packages it up for the database
    // Can return {WRITEASNULL} to cause a null value to be saved in the database
    // $value is what the user submitted
    // $element is the element object
    function prepareDataForSaving($value, $element) {
        return formulize_db_escape($value); 
    }

    // Handle any final actions that have to happen after data has been saved
    // Typically required for modifications to new entries after the entry ID has been assigned because before now the entry ID will have been "new"
    // $value is the value that was just saved
    // $element_id is the id of the element that just had data saved
    // $entry_id is the entry id that was just saved
    // ALSO, $GLOBALS['formulize_afterSavingLogicRequired']['elementId'] = type , must be declared in the prepareDataForSaving step if further action is required now -- see fileUploadElement.php for an example
    function afterSavingLogic($value, $element_id, $entry_id) {
    }

    // Returns data from the database to be printed in the List Screen
    // $value is the raw value that has been found in the database
    // $handle is the element handle for the field being retrieved
    // $entry_id is the element id in the form
    function prepareDataForDataset($value, $handle, $entry_id) {
        return $value;
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
