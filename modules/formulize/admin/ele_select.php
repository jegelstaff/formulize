<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2004 Freeform Solutions                  ##
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
##  Author of this file: Freeform Solutions and NS Tai (aka tuff) and others ##
##  URL: http://www.brandycoke.com/                                          ##
##  Project: Formulize                                                       ##
###############################################################################
if( !preg_match("/elements.php/", $_SERVER['PHP_SELF']) ){
	exit("Access Denied");
}
/*if( empty($addopt) && !empty($ele_id) ){
	$ele_value = $element->getVar('ele_value');
}*/

$ele_value = $value; // value is set in the admin/elements.php file already, based on ele_value from the element object -- aug 25, 2007
$ele_size = !empty($ele_value[0]) ? $ele_value[0] : 1;
$size = new XoopsFormText(_AM_ELE_SIZE, 'ele_value[0]', 3, 2, $ele_size);
$allow_multi = empty($ele_value[1]) ? 0 : 1;
$multiple = new XoopsFormRadioYN(_AM_ELE_MULTIPLE, 'ele_value[1]', $allow_multi);

// handling of scope limit defaults -- August 30 2006
$scopelimit = (isset($ele_value[3]) AND $ele_value[3] != 'all') ? explode(",", $ele_value[3]) : array(0=>'all');

$options = array();
$opt_count = 0;
if( !empty($ele_id) ){
	$keys = array_keys($ele_value[2]);
	for( $i=0; $i<count($keys); $i++ ){
		$v = $myts->makeTboxData4PreviewInForm($keys[$i]);
		// addOption is a function in admin/elements.php!!
		$options[] = addOption('ele_value[2]['.$opt_count.']', 'checked['.$opt_count.']', $v, 'check', $ele_value[2][$keys[$i]]);
		$opt_count++;
	}
}

	// added check below to add in blank rows that are unaccounted for above.
	// above code adds in all the options the user has typed in.  If there are blank rows on the form, this code will add in the appropriate amount, based on the 'rowcount' hidden element.
	// This code added by jwe 01/05/05
	if($opt_count < $_POST['rowcount']) {
		for($i=$opt_count;$i<$_POST['rowcount'];$i++) {
			$options[] = addOption('ele_value[2]['.$i.']', 'checked['.$i.']');
		}
		$opt_count = $_POST['rowcount']; // make the opt_count equal to the number of rows, since we've now brought the number back up to where it should be.
	}

	if(empty($addopt) AND empty($ele_id)) {
		$addopt = 2;
	} 
	for( $i=0; $i<$addopt; $i++ ){
		$options[] = addOption('ele_value[2]['.$opt_count.']', 'checked['.$opt_count.']');
		$opt_count++;
	}
	// these two lines part of the jwe added code
	$rowcount = new XoopsFormHidden("rowcount", $opt_count);
	$form->addElement($rowcount);

$add_opt = addOptionsTray();
$options[] = $add_opt;

$opt_tray = new XoopsFormElementTray(_AM_ELE_OPT, '<br />');
$opt_tray->setDescription(_AM_ELE_OPT_DESC._AM_ELE_OPT_DESC1.'<br /><br />'._AM_ELE_OPT_UITEXT);
for( $i=0; $i<count($options); $i++ ){
	$opt_tray->addElement($options[$i]);
}

// add setting to flag whether to change corresponding values in people's entries, when webmaster changes options for questions
$changeUserValues = new xoopsFormCheckbox('', 'changeuservalues');
$changeUserValues->addOption(1, _AM_ELE_OPT_CHANGEUSERVALUES);
$opt_tray->addElement($changeUserValues);

list($formlink, $selectedLinkElementId) = createFieldList($ele_value[2]); // two values passed back by this function when called from a selectbox
// if there's no selected element, then if FULLNAMES or USERNAMES are in effect, then use the profile form if one is specified in the module config options
$selectedLinkFormId = "";
if(isset($ele_value[2]['{FULLNAMES}']) OR isset($ele_value[2]['{USERNAMES}'])) {
	$module_handler =& xoops_gethandler('module');
	$config_handler =& xoops_gethandler('config');
	$formulizeModule =& $module_handler->getByDirname("formulize");
	$formulizeConfig =& $config_handler->getConfigsByCat(0, $formulizeModule->getVar('mid'));
	if($formulizeConfig['profileForm']) {
		$selectedLinkFormId = $formulizeConfig['profileForm'];
	}
}	

// scope control for linked selectboxes -- added August 30 2006 by jwe
$linkscope = new xoopsFormSelect('', 'formlink_scope', $scopelimit, 10, true);
$linkscope->addOption('all', _AM_ELE_FORMLINK_SCOPE_ALL);
$fs_member_handler =& xoops_gethandler('member');
$fs_xoops_groups =& $fs_member_handler->getGroups();
$fs_count = count($fs_xoops_groups);
for($i = 0; $i < $fs_count; $i++) {
	$linkscope->addOption($fs_xoops_groups[$i]->getVar('groupid'), $fs_xoops_groups[$i]->getVar('name'));     
}

$linkscopetray = new xoopsFormElementTray(_AM_ELE_FORMLINK_SCOPE, "<br />");
$linkscopetray->setDescription(_AM_ELE_FORMLINK_SCOPE_DESC);

$linkscopedefault = isset($ele_value[4]) ? $ele_value[4] : 0;
$linkscopelimit = new xoopsFormRadio('', 'linkscopelimit', $linkscopedefault);
$linkscopelimit->addOption(0, _AM_ELE_FORMLINK_SCOPELIMIT_NO);
$linkscopelimit->addOption(1, _AM_ELE_FORMLINK_SCOPELIMIT_YES);

$anyalldefault = isset($ele_value[6]) ? $ele_value[6] : 0;
$linkscopeanyall = new xoopsFormRadio('', 'linkscopeanyall', $anyalldefault);
$linkscopeanyall->addOption(0, _AM_ELE_FORMLINK_ANYALL_ANY);
$linkscopeanyall->addOption(1, _AM_ELE_FORMLINK_ANYALL_ALL);

$linkscopetray->addElement($linkscope);
$linkscopetray->addElement($linkscopelimit);
$linkscopetray->addElement($linkscopeanyall);

// added ele_value 5...the array of conditions that control what entries in the target form to use...Feb 6 2008
if(!is_array($ele_value[5])) {
	$scopeFilter = 'all';
} else {
	$scopeFilter = 'con';
}

$setfor = new xoopsFormElementTray(_AM_ELE_FORMLINK_SCOPEFILTER, "<br />");
$setfor->setDescription(_AM_ELE_FORMLINK_SCOPEFILTER_DESC);
$setfor_all = new xoopsFormRadio('', 'setfor', $scopeFilter);
$setfor_all->addOption('all', _AM_ELE_FORMLINK_SCOPEFILTER_ALL);

// setup the options array for use in the condition UI
unset($options); // note this same name is used above, must clear it first.
// must figure out the form to use...based on the selected link if there is one
// value of the currently set option in the formlink list is what we should use
$cols = "";
if($selectedLinkElementId) {
	$element_handler =& xoops_getmodulehandler('elements');
	$selectedEle = $element_handler->get($selectedLinkElementId);
	$cols = getAllColList($selectedEle->getVar('id_form'), "", $groups);
} elseif($selectedLinkFormId) { // if usernames or fullnames is in effect, we'll have the profile form fid instead
	$cols = getAllColList($selectedLinkFormId, "", $groups);
}
	
if(is_array($cols)) {
	// setup the options array for form elements
	foreach($cols as $f=>$vs) {
		foreach($vs as $row=>$values) {
			if($values['ele_colhead'] != "") {
				$options[$values['ele_id']] = printSmart(trans($values['ele_colhead']), 40);
			} else {
				$options[$values['ele_id']] = printSmart(trans(strip_tags($values['ele_caption'])), 40);
			}
		}
	}
	
} else {
  $options = array();	
}


// process existing conditions...
$conditionlist = "";

if(!isset($_POST['elements']) AND is_array($ele_value[5])) { // unpack existing conditions if this is the first page load...no sanitizing of these values...lazy assumption that admin side will never be available to attackers
	$_POST['elements'] = $ele_value[5][0];
	$_POST['ops'] = $ele_value[5][1];
	$_POST['terms'] = $ele_value[5][2];
} 

// do not need to listen for new elements, ops and terms from the UI since they will already have been saved into the element object

for($i=0;$i<count($_POST['elements']);$i++) {
		$form->addElement(new xoopsFormHidden('elements[]', $_POST['elements'][$i]));
		$form->addElement(new xoopsFormHidden('ops[]', $_POST['ops'][$i]));
		$form->addElement(new xoopsFormHidden('terms[]', $_POST['terms'][$i]));
		$conditionlist .= $options[$_POST['elements'][$i]] . " " . $_POST['ops'][$i] . " " . $_POST['terms'][$i] . "<br />";
} 


// setup the operator boxes...
$opterm = new xoopsFormElementTray('', "&nbsp;&nbsp;");
$new_element = new xoopsFormSelect('', 'new_element');
$new_element->setExtra("onfocus=\"javascript:window.document.form_ele.setfor[1].checked=true\"");
$new_element->addOptionArray($options);
$op = new xoopsFormSelect('', 'new_op');
$ops['='] = "=";
$ops['NOT'] = "NOT";
$ops['>'] = ">";
$ops['<'] = "<";
$ops['>='] = ">=";
$ops['<='] = "<=";
$ops['LIKE'] = "LIKE";
$ops['NOT LIKE'] = "NOT LIKE";
$op->addOptionArray($ops);
$op->setExtra("onfocus=\"javascript:window.document.form_ele.setfor[1].checked=true\"");
$term = new xoopsFormText('', 'new_term', 10, 255);
$term->setExtra("onfocus=\"javascript:window.document.form_ele.setfor[1].checked=true\"");
$opterm->addElement($new_element);
$opterm->addElement($op);
$opterm->addElement($term);

$addcon = new xoopsFormButton('', 'addcon', _AM_ELE_FORMLINK_SCOPEFILTER_ADDCON, 'submit');
$addcon->setExtra("onfocus=\"javascript:window.document.form_ele.setfor[1].checked=true\"");

$conditionui = "<br />$conditionlist" . $opterm->render() . "<br />" . $addcon->render() . "<br /><br />" . _AM_ELE_FORMLINK_SCOPEFILTER_REFRESHHINT;

$setfor_con = new xoopsFormRadio('' , 'setfor', $scopeFilter);
$setfor_con->addOption('con', _AM_ELE_FORMLINK_SCOPEFILTER_CON.$conditionui);
$setfor->addElement($setfor_all);
$setfor->addElement($setfor_con);


$form->addElement($size, 1);
$form->addElement($multiple);
$form->addElement($opt_tray);
// added another form element, the dynamic link to another form's field to populate the selectbox.  -- jwe 7/29/04
$form->addElement($formlink);
$form->addElement($linkscopetray);
$form->addElement($setfor);

// print_r($options);
// 	echo '<br />';

?>