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

require_once XOOPS_ROOT_PATH . "/modules/formulize/class/elements.php"; 

class formulizeProvinceListElement extends formulizeformulize {
    
    var $needsDataType;
    var $overrideDataType;
    var $hasData;
    var $name;
    var $adminCanMakeRequired;
    var $alwaysValidateInputs;
    function __construct() {
        $this->name = "Province List";
        $this->hasData = true; // set to false if this is a non-data element, like the subform or the grid
        $this->needsDataType = false; // set to false if you're going force a specific datatype for this element using the overrideDataType
        $this->overrideDataType = "tinyint(5)"; // use this to set a datatype for the database if you need the element to always have one (like 'date').  set needsDataType to false if you use this.
        $this->adminCanMakeRequired = true; // set to true if the webmaster should be able to toggle this element as required/not required
        $this->alwaysValidateInputs = false; // set to true if you want your custom validation function to always be run.  This will override any required setting that the webmaster might have set, so the recommendation is to set adminCanMakeRequired to false when this is set to true.
        parent::__construct();
    }    
}

class formulizeProvinceListElementHandler extends formulizeElementsHandler {
    
    var $db;
    var $clickable; // used in formatDataForList
    var $striphtml; // used in formatDataForList
    var $length; // used in formatDataForList
    
    function __construct($db) {
		$this->db =& $db;
    }
    
    function create() {
        return new formulizeProvinceListElement();
    }
    
    // this method would gather any data that we need to pass to the template, besides the ele_value and other properties that are already part of the basic element class
    // it receives the element object and returns an array of data that will go to the admin UI template
    // when dealing with new elements, $element might be FALSE
    function adminPrepare($element) {	
		if (!$element){
			$provinceSelected = 0;
			$elementSelected = 0;
			$sortSelected = 0;
		} else {
			$ele_value = $element->getVar('ele_value'); 
			$provinceSelected = $ele_value[0];
			$elementSelected = $ele_value[1];
			$sortSelected = $ele_value[2];
		}

		$provinceOptions['none'] = "None";
		$provinceOptions = $provinceOptions + $this->getProvinceList(); // the + operator does not affect the keys, whereas if we unshifted none onto the beginning, we'd lose the key->value associations
		$elementOptions = array("Dropdown list", "Radio buttons");
		$sortOptions = array("Order alphabetically", "Order by population");		
		
		return array(
			'provinceOptions'=>$provinceOptions, 'provinceSelected'=>$provinceSelected, 
			'elementOptions'=>$elementOptions, 'elementSelected'=>$elementSelected,
			'sortOptions'=>$sortOptions, 'sortSelected'=>$sortSelected
		);
    }
    
    // this method would read back any data from the user after they click save in the admin UI, and save the data to the database, if it were something beyond what is handled in the basic element class
    // this is called as part of saving the options tab.  It receives a copy of the element object immediately prior to it being saved, so the element object will have all its properties set as they would be based on the user's changes in the names & settings tab, and in the options tab (the tabs are saved in order from left to right).
    // the exception is the special ele_value array, which is passed separately from the object (this will contain the values the user set in the Options tab)
    // You can modify the element object in this function and since it is an object, and passed by reference by default, then your changes will be saved when the element is saved.
    // You should return a flag to indicate if any changes were made, so that the page can be reloaded for the user, and they can see the changes you've made here.
    function adminSave($element, $ele_value) {
        return false;
    }
    
    // this method reads the current state of an element based on the user's input, and the admin options, and sets ele_value to what it needs to be so we can render the element correctly
    // it must return $ele_value, with the correct value set in it, so that it will render as expected in the render method
    // $value is the value that was retrieved from the database for this element in the active entry.  It is a raw value, no processing has been applied, it is exactly what is in the database (as prepared in the prepareDataForSaving method and then written to the DB)
    // $ele_value will contain the options set for this element (based on the admin UI choices set by the user, possibly altered in the adminSave method)
    // $element is the element object
    function loadValue($value, $ele_value, $element) { 
		$ele_value[0] = $value;
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
		$provinceList = $this->getProvinceList();	
		
		if($isDisabled) {
		  return new xoopsFormLabel($caption, $provinceList[$ele_value[0]]);
		}
		
		if ($ele_value[2] != 1){
			// Sort provinces alphabetically			
			asort($provinceList);
		}
		
		if ($ele_value[1] == 1){
			// Radio Buttons
			$formElement = new XoopsFormRadio($caption,  $markupName, $ele_value[0], "<br />");
		} else {
			// Single Select Menu	
			$formElement = new XoopsFormSelect($caption,  $markupName, $ele_value[0], 1, false);			
			$formElement -> addOption("none", "Choose a Province");
		}	
		 
		$formElement->addOptionArray($provinceList);
			
        return $formElement;
    }
    
    // this method returns any custom validation code (javascript) that should figure out how to validate this element
    // 'myform' is a name enforced by convention that refers to the form where this element resides
    // use the adminCanMakeRequired property and alwaysValidateInputs property to control when/if this validation code is respected
    function generateValidationCode($caption, $markupName, $element, $entry_id) {
		$ele_value = $element->getVar('ele_value');
		$validationCode = array();
		$eltmsg = "Please select a province for '$caption'";
		if($ele_value[1] == 0) { // validation for dropdown list
		    $validationCode[] = "\nif ( myform.{$markupName}.options[0].selected ) {\n window.alert(\"{$eltmsg}\");\n myform.{$markupName}.focus();\n return false;\n }\n";
		} else { // validation for radio buttons
		    $validationCode[] = "selection = false;\n";
		    $validationCode[] = "if(myform.{$markupName}.length) {\n";
		    $validationCode[] = "for(var i=0;i<myform.{$markupName}.length;i++){\n";
	    	$validationCode[] = "if(myform.{$markupName}[i].checked){\n";
		    $validationCode[] = "selection = true;\n";
		    $validationCode[] = "}\n";
	    	$validationCode[] = "}\n";
		    $validationCode[] = "}\n";
		    $validationCode[] = "if(selection == false) { window.alert(\"{$eltmsg}\");\n myform.{$markupName}.focus();\n return false;\n }\n";   
		}	
        return $validationCode;
    }
    
    // this method will read what the user submitted, and package it up however we want for insertion into the form's datatable
    // You can return {WRITEASNULL} to cause a null value to be saved in the database
    // $value is what the user submitted
    // $element is the element object
    function prepareDataForSaving($value, $element) {
		if($value == "none") {
			$value = "{WRITEASNULL}";
		}
        return formulize_db_escape($value); // strictly speaking, formulize will already escape all values it writes to the database, but it's always a good habit to never trust what the user is sending you!
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
    function prepareDataForDataset($value, $handle="", $entry_id="") {	
		$provinceList = formulizeProvinceListElementHandler::getProvinceList();		
        return $provinceList[$value];
    }
    
    // this method will take a text value that the user has specified at some point, and convert it to a value that will work for comparing with values in the database.  This is used primarily for preparing user submitted text values for saving in the database, or for comparing to values in the database, such as when users search for things.  The typical user submitted values would be coming from a condition form (ie: fieldX = [term the user typed in]) or other situation where the user types in a value that needs to interact with the database.
    // it is only necessary to do special logic here if the values stored in the database do not match what users would be typing, ie: you're using coded numbers in the database, but displaying text on screen to users
    // this would be where a Yes value would be converted to a 1, for example, in the case of a yes/no element, since 1 is how yes is represented in the database for that element type
    // $partialMatch is used to indicate if we should search the values for partial string matches, like On matching Ontario.  This happens in the getData function when processing filter terms (ie: searches typed by users in a list of entries)
    // if $partialMatch is true, then an array may be returned, since there may be more than one matching value, otherwise a single value should be returned.
    // if literal text that users type can be used as is to interact with the database, simply return the $value
    // otherwise, if we need to do a conversion on literal text, then the values returned from this method should always correspond to a complete value in the database, that could be searched for with = in a SQL statement
    function prepareLiteralTextForDB($value, $element, $partialMatch=false) {
		$provinceList = $this->getProvinceList();
		if($partialMatch) {
	    	$foundKeys = array();
		    foreach($provinceList as $key=>$thisProvince) {
			if(strstr(strtolower($thisProvince), strtolower($value))) {
			    $foundKeys[] = $key;
			}
		    }
	    	return empty($foundKeys) ? false : $foundKeys;
		} else {
		    return array_search($value, $provinceList);
		}
    }
    
    // this method will format a dataset value for display on screen when a list of entries is prepared
    // for standard elements, this step is where linked selectboxes potentially become clickable or not, among other things
    // Set certain properties in this function, to control whether the output will be sent through a "make clickable" function afterwards, sent through an HTML character filter (a security precaution), and trimmed to a certain length with ... appended.
    function formatDataForList($value, $handle, $entry_id) {        
        return parent::formatDataForList($value);
    }
    
    static function getProvinceList(){
        // values should not start with 0, or else you run into difficulties when setting "no default" value
        return array(1=>"Ontario", 2=>"Quebec", 3=>"British Columbia", 4=>"Alberta", 5=>"Manitoba", 6=>"Saskatchewan", 7=>"Nova Scotia", 8=>"New Brunswick", 9=>"Newfoundland and Labrador", 10=>"Prince Edward Island", 11=>"Northwest Territories", 12=>"Yukon", 13=>"Nunavut");
    }

}