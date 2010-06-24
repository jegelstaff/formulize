<?php
// $Id: searchform.php 9520 2009-11-11 14:32:52Z pesianstranger $
/**
* Search Form
*
* Shows form with options where to search in ImpressCMS
*
* @copyright	http://www.xoops.org/ The XOOPS Project
* @copyright	XOOPS_copyrights.txt
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license		LICENSE.txt
* @package	core
* @since		XOOPS
* @author		http://www.xoops.org The XOOPS Project
* @author		modified by author <email@domain.tld>
* @version		$Id: searchform.php 9520 2009-11-11 14:32:52Z pesianstranger $
*/
if (!defined("ICMS_ROOT_PATH")) {
	die("ImpressCMS root path not defined");
}

include_once ICMS_ROOT_PATH."/class/xoopsformloader.php";
$config_handler =& xoops_gethandler('config');
$xoopsConfigSearch = $icmsConfigSearch =& $config_handler->getConfigsByCat(XOOPS_CONF_SEARCH);

// create form
$search_form = new XoopsThemeForm(_SR_SEARCH, "search", "search.php", 'get');

// create form elements
$search_form->addElement(new XoopsFormText(_SR_KEYWORDS, "query", 30, 255, htmlspecialchars(stripslashes(implode(" ", $queries)), ENT_QUOTES)), true);
$type_select = new XoopsFormSelect(_SR_TYPE, "andor", $andor);
$type_select->addOptionArray(array("AND"=>_SR_ALL, "OR"=>_SR_ANY, "exact"=>_SR_EXACT));
$search_form->addElement($type_select);

if (!empty($mids)) {
	$mods_checkbox = new XoopsFormCheckBox(_SR_SEARCHIN, "mids[]", $mids);
} else {
	$mods_checkbox = new XoopsFormCheckBox(_SR_SEARCHIN, "mids[]", $mid);
}

if (empty($modules)) {
	$criteria = new CriteriaCompo();
	$criteria->add(new Criteria('hassearch', 1));
	$criteria->add(new Criteria('isactive', 1));
	if (!empty($available_modules)) {
		$criteria->add(new Criteria('mid', "(".implode(',', $available_modules).")", 'IN'));
	}
	$module_handler =& xoops_gethandler('module');
	$mods_checkbox->addOptionArray($module_handler->getList($criteria));
}
else {
	foreach ($modules as $mid => $module) {
		$module_array[$mid] = $module->getVar('name');
	}
	$mods_checkbox->addOptionArray($module_array);
}

$search_form->addElement($mods_checkbox);
if ($icmsConfigSearch['keyword_min'] > 0) {
	$search_form->addElement(new XoopsFormLabel(_SR_SEARCHRULE, sprintf(_SR_KEYIGNORE, icms_conv_nr2local($icmsConfigSearch['keyword_min']))));
}

$search_form->addElement(new XoopsFormHidden("action", "results"));
$search_form->addElement(new XoopsFormHiddenToken('id'));
$search_form->addElement(new XoopsFormButton("", "submit", _SR_SEARCH, "submit"));
return $search_form->render();	// Added by Lankford on 2007/7/26.

?>