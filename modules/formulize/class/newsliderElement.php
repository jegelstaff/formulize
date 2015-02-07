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

class formulizeNewSliderElement extends formulizeformulize {
    function __construct() {
        $this->name = "Range Slider";
        $this->hasData = true;
        $this->needsDataType = false; //should always take integer
        $this->overrideDataType = 'integer';
        $this->adminCanMakeRequired = true;
        $this->alwaysValideInputs = false; //no validation required
        parent::formulizeformulize();
    }
}

class formulizeNewSliderElementHandler extends formulizeElementsHandler {
    var $db;

    function __construct($db) {}

    function create() {
        return new formulizeNewSliderElement();
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
            $ele_value[0] = 1;
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
        if ($_POST['formlink'] != "none") {
            $ele_value[4] = $_POST['formlink'];
        }
        $element->setVar('ele_value', $ele_value);
        return $changed;
    }

    // Reads current state of element, updates ele_value to a renderable state
    function loadValue($value, $ele_value, $element) {
        $ele_value[3] = $value;
        //$ele_value[3] = eregi_replace("'", "&#039;", $ele_value[3]);
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
        $ele_value = $element->getVar('ele_value');
        $slider_html = "<input type=\"range\" ";
        $slider_html .= "name=\"{$markupName}\"";
        $slider_html .= "id=\"{$markupName}\"";
        $slider_html .= "min=\"{$ele_value[0]}\" ";
        $slider_html .= "max=\"{$ele_value[1]}\" ";
        $slider_html .= "step=\"{$ele_value[2]}\" ";
        $slider_html .= "value=\"{$ele_value[3]}\"";
        $slider_html .= "oninput=\"updateTextInput(value);\"";
        $slider_html .= "</input>";

        $value_html = "<output id=\"rangeValue\" type=\"text\" size=\"2\"";
        $value_html.= "for=\"{$markupName}\"";
        $value_html .= ">$ele_value[3]<output>";

        $form_slider_value = new XoopsFormLabel($caption, $value_html);
        $form_slider = new XoopsFormLabel($caption, $slider_html);

        $update_script = "<script type=\"text/javascript\">";
        $update_script .= "function updateTextInput(val) {";
        $update_script .= "document.getElementById('rangeValue').value=val;}";
        $update_script .= "</script>";

        if($isDisabled) {
            $renderedValue = $form_slider_value->render();
            $form_ele = new XoopsFormLabel($caption, "$renderedValue");
        } else {
            $renderedSlider = $form_slider->render();
            $renderedValue = $form_slider_value->render();
            $form_ele = new XoopsFormLabel($caption, "<nobr>$renderedSlider $renderedValue</nobr>.$update_script");
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
        global $myts;
        if(!$myts) { $myts =& MyTextSanitizer::getInstance(); }

        $ele_value = $element->getVar('ele_value');
        $ele_id = $element->getVar('ele_id');

        $value = ereg_replace ('[^0-9.-]+', '', $value);
        $value = $myts->htmlSpecialChars($value);
        echo $value;

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

}
