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

class formulizeNewSubformElement extends formulizeformulize {
    
    var $needsDataType;
    var $overrideDataType;
    var $hasData;
    var $name;
    var $adminCanMakeRequired;
    var $alwaysValidateInputs;
    function __construct() {
        $this->name = "Custom Subform (another form with a relationship to this one)";
        $this->hasData = true; // set to false if this is a non-data element, like the subform or the grid
        $this->needsDataType = true; // set to false if you're going force a specific datatype for this element using the overrideDataType
        $this->overrideDataType = ""; // use this to set a datatype for the database if you need the element to always have one (like 'date').  set needsDataType to false if you use this.
        $this->adminCanMakeRequired = false; // set to true if the webmaster should be able to toggle this element as required/not required
        $this->alwaysValidateInputs = false; // set to true if you want your custom validation function to always be run.  This will override any required setting that the webmaster might have set, so the recommendation is to set adminCanMakeRequired to false when this is set to true.
        parent::formulizeformulize();
    }
    
}

class formulizeNewSubformElementHandler extends formulizeElementsHandler {
    
    var $db;
    var $clickable; // used in formatDataForList
    var $striphtml; // used in formatDataForList
    var $length; // used in formatDataForList
    
    function __construct($db) {
    }
    
    function create() {
        return new formulizeNewSubformElement();
    }
    
    // this method would gather any data that we need to pass to the template, besides the ele_value and other properties that are already part of the basic element class
    // it receives the element object and returns an array of data that will go to the admin UI template
    // when dealing with new elements, $element might be FALSE
    function adminPrepare($element) {
        $ele_value = $element ? $element->getVar('ele_value') : array();
        
		if (!$element) {
			//Assign default values
			$ele_value[2] = 1;
			$ele_value[3] = 1;
		}
		
		$ele_value[1] = explode(",",$ele_value[1]);
		global $xoopsDB;
		global $xoopsUser;
		$uid = $xoopsUser->getVar('uid');
		$validForms1 = q("SELECT t1.fl_form1_id, t2.desc_form FROM " . $xoopsDB->prefix("formulize_framework_links") . " AS t1, " . $xoopsDB->prefix("formulize_id") . " AS t2 WHERE t1.fl_form2_id=" . intval($fid) . " AND t1.fl_unified_display=1 AND t1.fl_relationship != 1 AND t1.fl_form1_id=t2.id_form");
		$validForms2 = q("SELECT t1.fl_form2_id, t2.desc_form FROM " . $xoopsDB->prefix("formulize_framework_links") . " AS t1, " . $xoopsDB->prefix("formulize_id") . " AS t2 WHERE t1.fl_form1_id=" . intval($fid) . " AND t1.fl_unified_display=1 AND t1.fl_relationship != 1 AND t1.fl_form2_id=t2.id_form");
		$caughtfirst = false;
		foreach($validForms1 as $vf1) {
			$validForms[$vf1['fl_form1_id']] = $vf1['desc_form'];
			if(!$caughtfirst) { 
				$firstform = $vf1['fl_form1_id']; 
				$caughtfirst = true;
			}
		}
		
		foreach($validForms2 as $vf2) {
			if(!isset($validForms[$vf2['fl_form2_id']])) {
				$validForms[$vf2['fl_form2_id']] = $vf2['desc_form'];
				if(!$caughtfirst) { 
					$firstform = $vf2['fl_form2_id']; 
					$caughtfirst = true;
				}
			}
		}
		
		if(count($validForms) == 0) { $validForms['none'] = _AM_ELE_SUBFORM_NONE; }
		$subforms = $validForms;
		if($caughtfirst) {
			$formtouse = $ele_value[0] ? $ele_value[0] : $firstform; // use the user's selection, unless there isn't one, then use the first form found
			$elementsq = q("SELECT ele_caption, ele_id FROM " . $xoopsDB->prefix("formulize") . " WHERE id_form=" . intval($formtouse) . " AND ele_type != \"areamodif\" AND ele_type != \"grid\" AND ele_type != \"ib\" AND ele_type != \"subform\" ORDER BY ele_order");
			foreach($elementsq as $oneele) {
				$subformelements[$oneele['ele_id']] = printSmart($oneele['ele_caption']);
			}
		} else {
			$subformelements[0] = "";
		}
  
		// setup the UI for the subform conditions filter
		$subformfilter = formulize_createFilterUI($ele_value[7], "subformfilter", $ele_value[0], "form-2");
		
		return array('subformfilter'=>$subformfilter, 'subformelements'=>$subformelements, 'subforms'=>$subfroms, 'uid'=>$uid);
    }
    
    // this method would read back any data from the user after they click save in the admin UI, and save the data to the database, if it were something beyond what is handled in the basic element class
    // this is called as part of saving the options tab.  It receives a copy of the element object immediately prior to it being saved, so the element object will have all its properties set as they would be based on the user's changes in the names & settings tab, and in the options tab (the tabs are saved in order from left to right).
    // the exception is the special ele_value array, which is passed separately from the object (this will contain the values the user set in the Options tab)
    // You can modify the element object in this function and since it is an object, and passed by reference by default, then your changes will be saved when the element is saved.
    // You should return a flag to indicate if any changes were made, so that the page can be reloaded for the user, and they can see the changes you've made here.
    function adminSave($element, $ele_value) {
        $changed = false;
        if(!$_POST['elements-ele_value'][3]) {
			$ele_value[3] = 0;
		}
		$ele_value[1] = implode(",",$_POST['elements_ele_value_1']);
		$ele_value[6] = !isset($ele_value[6]) ? 'hideaddentries' : 1;
		$processedValues = parseSubmittedConditions('subformfilter', 'optionsconditionsdelete', $processedValues, 7); // post key, delete key, processedValues, ele_value key for conditions
		
		foreach($processedValues['elements'] as $property=>$value) {
			$element->setVar($property, $value);
		}
		
		$element->setVar('ele_value', $ele_value);
		
        return $changed;
    }
    
    // this method reads the current state of an element based on the user's input, and the admin options, and sets ele_value to what it needs to be so we can render the element correctly
    // it must return $ele_value, with the correct value set in it, so that it will render as expected in the render method
    // $value is the value that was retrieved from the database for this element in the active entry.  It is a raw value, no processing has been applied, it is exactly what is in the database (as prepared in the prepareDataForSaving method and then written to the DB)
    // $ele_value will contain the options set for this element (based on the admin UI choices set by the user, possibly altered in the adminSave method)
    // $element is the element object
    function loadValue($value, $ele_value, $element) {
        // dummy element will have a single value stored in the database, but when rendered, it will pickup the values from ele_value[0] and [1] and use those as the default.  See the render method.
        // So, we'll erase ele_value[1] and set the value from the database as ele_value[0], and then everything will render right
        $ele_value[0] = $value;
        $ele_value[1] = "";
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
        // dummy element is rendered as a textboxes, with the values set by the user in the admin side smushed together as the default value for the textbox
        if($isDisabled) {
            $formElement = new xoopsFormLabel($caption, $ele_value[0] . $ele_value[1]);
        } else {
            $formElement = new xoopsFormText($caption, $markupName, 50, 50, $ele_value[0] . $ele_value[1]); // caption, markup name, size, maxlength, default value, according to the xoops form class
        }
        return $formElement;
    }
    
    // this method returns any custom validation code (javascript) that should figure out how to validate this element
    // 'myform' is a name enforced by convention that refers to the form where this element resides
    // use the adminCanMakeRequired property and alwaysValidateInputs property to control when/if this validation code is respected
    function generateValidationCode($caption, $markupName, $element) {
        $validationmsg = "Your value for $caption should not match the default value.";
	$validationmsg = str_replace("'", "\'", stripslashes( $validationmsg ) );
        $ele_value = $element->getVar('ele_value');
        $validationCode = array();
        $validationCode[] = "if(myform.{$markupName}.value == '".$ele_value[0].$ele_value[1]."') {\n";
        $validationCode[] = "  window.alert('{$validationmsg}');\n myform.{$markupName}.focus();\n return false;\n ";
        $validationCode[] = "}\n";
        return $validationCode;
    }
    
    // this method will read what the user submitted, and package it up however we want for insertion into the form's datatable
    // You can return {WRITEASNULL} to cause a null value to be saved in the database
    // $value is what the user submitted
    // $element is the element object
    function prepareDataForSaving($value, $element) {
        return mysql_real_escape_string($value); // strictly speaking, formulize will already escape all values it writes to the database, but it's always a good habit to never trust what the user is sending you!
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
    
    // this method will take a text value that the user has specified at some point, and convert it to a value that will work for comparing with values in the database.  This is used primarily for preparing user submitted text values for saving in the database, or for comparing to values in the database.  The typical user submitted values would be coming from a condition form (ie: fieldX = [term the user typed in]) or other situation where the user types in a value that needs to interact with the database.
    // this would be where a Yes value would be converted to a 1, for example, in the case of a yes/no element, since 1 is how yes is represented in the database for that element type
    function prepareLiteralTextForDB($value, $element) {
        return $value;
    }
    
    // this method will format a dataset value for display on screen when a list of entries is prepared
    // for standard elements, this step is where linked selectboxes potentially become clickable or not, among other things
    // Set certain properties in this function, to control whether the output will be sent through a "make clickable" function afterwards, sent through an HTML character filter (a security precaution), and trimmed to a certain length with ... appended.
    function formatDataForList($value, $handle, $entry_id) {
        $this->clickable = true; // make urls clickable
        $this->striphtml = true; // remove html tags as a security precaution
        $this->length = 100; // truncate to a maximum of 100 characters, and append ... on the end
        
        $value = strtoupper($value); // just as an example, we'll uppercase all text when displaying in a list
        
        return parent::formatDataForList($value); // always return the result of formatDataForList through the parent class (where the properties you set here are enforced)
    }
    
}