<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2006 Freeform Solutions                  ##
##                Portions copyright (c) 2003 NS Tai (aka tuff)              ##
##                       <http://www.brandycoke.com/>                        ##
###############################################################################
##                    XOOPS - PHP Content Management System                  ##
##                       Copyright (c) 2000 XOOPS.org                        ##
##                          <http://www.xoops.org/>                          ##
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
##  Author of this file: Freeform Solutions 					     ##
##  Project: Formulize                                                       ##
###############################################################################
if( !preg_match("/elements.php/", $_SERVER['PHP_SELF']) ){
	exit("Access Denied");
}

if( !empty($ele_id) ){
	$ele_value = $element->getVar('ele_value');
}

if(isset($_POST['subformrefresh'])) {
	$ele_value[0] = $_POST['subform'];
	$ele_value[1] = "";
}

// put in a select box for the valid forms in a framework with this form

global $xoopsDB;
$validForms1 = q("SELECT t1.fl_form1_id, t2.desc_form FROM " . $xoopsDB->prefix("formulize_framework_links") . " AS t1, " . $xoopsDB->prefix("formulize_id") . " AS t2 WHERE t1.fl_form2_id=" . intval($id_form) . " AND t1.fl_unified_display=1 AND t1.fl_relationship != 1 AND t1.fl_form1_id=t2.id_form");
$validForms2 = q("SELECT t1.fl_form2_id, t2.desc_form FROM " . $xoopsDB->prefix("formulize_framework_links") . " AS t1, " . $xoopsDB->prefix("formulize_id") . " AS t2 WHERE t1.fl_form1_id=" . intval($id_form) . " AND t1.fl_unified_display=1 AND t1.fl_relationship != 1 AND t1.fl_form2_id=t2.id_form");

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

$formlist = new xoopsFormSelect(_AM_ELE_SUBFORM_FORM, 'subform', $ele_value[0]);
$formlist->setDescription(_AM_ELE_SUBFORM_DESC);
$formlist->addOptionArray($validForms);
$form->addElement($formlist);

if($caughtfirst) {
	// put in a select box for the elements in the selected form
	// have a refresh button to synch with the current form

	$ele_defaults = explode(",",$ele_value[1]);
	$elementlist = new xoopsFormSelect('', 'subformelements', $ele_defaults, 8, true);
	
	$formtouse = $ele_value[0] ? $ele_value[0] : $firstform; // use the user's selection, unless there isn't one, then use the first form found

	$elementsq = q("SELECT ele_caption, ele_id FROM " . $xoopsDB->prefix("formulize") . " WHERE id_form=" . intval($formtouse) . " AND ele_type != \"ib\" AND ele_type != \"subform\" AND ele_type != \"areamodif\" ORDER BY ele_order");
	foreach($elementsq as $oneele) {
		$elements_array[$oneele['ele_id']] = $oneele['ele_caption'];
	}
	$elementlist->addOptionArray($elements_array);
	
	$elelisttray = new xoopsFormElementTray(_AM_ELE_SUBFORM_ELEMENTS, "<br />");
	$elelisttray->setDescription(_AM_ELE_SUBFORM_ELEMENTS_DESC);
	$elerefresh = new xoopsFormButton('', 'subformrefresh', _AM_ELE_SUBFORM_REFRESH, 'submit');
	$elelisttray->addElement($elementlist);
	$elelisttray->addElement($elerefresh);
	$form->addElement($elelisttray);

}

// number of blank spaces to show by default -- added sept 8 2007
$blankdefault = isset($ele_value[2]) ? intval($ele_value[2]) : 1;
$numblanks = new xoopsFormText(_AM_ELE_SUBFORM_BLANKS, 'subformblanks', 2, 2, $blankdefault);
$form->addElement($numblanks);


?>