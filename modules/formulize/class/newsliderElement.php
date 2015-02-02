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

    function adminPrepare($element) {
        $ele_value = $element ? $element->getVar('ele_value') : array();

        $formlink = createFieldList($ele_value[4], true);
        if (!$element) {
            //Min Velue
            $ele_value[0] = 1;
            //Max Value
            $ele_value[1] = 100;
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
        $ele_value[2] = $value;
        $ele_value[2] = eregi_replace("'", "&#039;", $ele_value[2]);
        return $ele_value;
    }

    function render($ele_value, $caption, $markupName, $isDisabled, $element, $entry_id) {
        $ele_value = $element->getVar('ele_value');
        $form_ele = new XoopsFormLabel($caption, ele
        if($isDisabled) {
            //$disabledHiddenValues = implode("\n", $disabledHiddenValue); // glue the individual value elements together into a set of values
            //$renderedElement = implode(", ", $disabledOutputText);
        } else {
            $renderedElement = $form_ele->render();
        }

    }
}
