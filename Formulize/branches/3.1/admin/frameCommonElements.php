<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2004 Freeform Solutions                  ##
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

// This file handles the selection of elements that are meant to have common values between two forms

// put javascript function here:
function saveJavascript() {

?>

<script type='text/javascript'>
<!--

function saveElements(form1choice, form2choice, lid) {
	for (var i=0; i < form1choice.options.length; i++) {
		if(form1choice.options[i].selected) {
			window.opener.document.updateframe.common1choice.value = form1choice.options[i].value;
			break;
		}
	}
	for (var i=0; i < form2choice.options.length; i++) {
		if(form2choice.options[i].selected) {
			window.opener.document.updateframe.common2choice.value = form2choice.options[i].value;
			break;
		}
	}
	window.opener.document.updateframe.common_fl_id.value = lid;
	window.opener.document.updateframe.submit();
	window.self.close();
}

-->
</script>

<?php

}

require_once "../../../mainfile.php";

global $xoopsConfig, $xoopsDB;
// load the formulize language constants if they haven't been loaded already
	if ( file_exists(XOOPS_ROOT_PATH."/modules/formulize/language/".$xoopsConfig['language']."/main.php") ) {
		include_once XOOPS_ROOT_PATH."/modules/formulize/language/".$xoopsConfig['language']."/main.php";
	} else {
		include_once XOOPS_ROOT_PATH."/modules/formulize/language/english/main.php";
	}

include_once XOOPS_ROOT_PATH . "/modules/formulize/class/forms.php";
include_once XOOPS_ROOT_PATH . "/modules/formulize/class/frameworks.php";

global $xoopsDB; 

include_once XOOPS_ROOT_PATH . "/class/xoopsformloader.php";

$form1 = is_numeric($_GET['form1']) ? $_GET['form1'] : 0;
$form2 = is_numeric($_GET['form2']) ? $_GET['form2'] : 0;
$lid = is_numeric($_GET['lid']) ? $_GET['lid'] : 0;

print "<HTML>";
print "<head>";
print "<title>" . _AM_FRAME_WHICH_ELEMENTS . "</title>\n";

print "<link rel=\"stylesheet\" type=\"text/css\" media=\"screen\" href=\"" . XOOPS_URL . "/xoops.css\" />\n";
$themecss = xoops_getcss();
//$themecss = substr($themecss, 0, -6);
//$themecss .= ".css";
print "<link rel=\"stylesheet\" type=\"text/css\" media=\"screen\" href=\"$themecss\" />\n";

print "</head>";
print "<body><center>"; 
print "<table width=100%><tr><td width=5%></td><td width=90%>";

$chooseElementsForm = new xoopsThemeForm(_AM_FRAME_WHICH_ELEMENTS, 'whichElements', XOOPS_URL."/modules/formulize/admin/frameCommonElements.php?form1=$form1&form2=$form2&lid=$lid");


$formObj1 = new formulizeForm($form1);
$formObj2 = new formulizeForm($form2);

$form1Elements = generateElementList($formObj1);
$form2Elements = generateElementList($formObj2);

$form1Default = getDefault($lid, 1);
$form2Default = getDefault($lid, 2);

$form1Choice = new xoopsFormSelect(_AM_FRAME_SELECT_COMMON . "'" . $formObj1->getVar('title') . "'", 'form1choice', $form1Default, 1, false);
$form1Choice->addOptionArray($form1Elements);
$form2Choice = new xoopsFormSelect(_AM_FRAME_SELECT_COMMON . "'" . $formObj2->getVar('title') . "'", 'form2choice', $form2Default, 1, false);
$form2Choice->addOptionArray($form2Elements);

$chooseElementsForm->addElement($form1Choice);
$chooseElementsForm->addElement($form2Choice);

$subButton = new xoopsFormButton('', 'submitx', _SUBMIT, 'button');
$subButton->setExtra("onclick=\"javascript:saveElements(this.form.elements[0], this.form.elements[1], $lid);\"");
$chooseElementsForm->addElement($subButton);
$chooseElementsForm->insertBreak("<p>" . _AM_FRAME_COMMON_WARNING . "</p>", "head");

saveJavascript();

print $chooseElementsForm->render();

print "</td><td width=5%></td></tr></table>";
print "</center></body>";
print "</HTML>";

// THIS FUNCTION CREATES THE ARRAY OF ELEMENTS FOR USE IN THE LISTBOXES
function generateElementList($form) {

	$element_handler =& xoops_getmodulehandler('elements', 'formulize');
	foreach($form->getVar('elements') as $element) {
		$ele = $element_handler->get($element);
		if($ele->getVar('ele_type') != "ib" AND $ele->getVar('ele_type') != "areamodif" AND $ele->getVar('ele_type') != "subform"){
			$saveoptions[$ele->getVar('ele_id')] = $ele->getVar('ele_colhead') ? $ele->getVar('ele_colhead') : $ele->getVar('ele_caption');
		}
	}
	return $saveoptions;
}

function getDefault($lid, $order) {

	$link = new formulizeFrameworkLink($lid);
	if($link->getVar('common')) {
		if($order == 1) { return $link->getVar('key1'); }
		if($order == 2) { return $link->getVar('key2'); }
	} else {
		return false;
	}
}

?>

