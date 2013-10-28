<?php
/**
 * Extended User Profile
 *
 * @copyright       The ImpressCMS Project http://www.impresscms.org/
 * @license         LICENSE.txt
 * @license			GNU General Public License (GPL) http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @package         modules
 * @since           1.2
 * @author          Jan Pedersen
 * @author          The SmartFactory <www.smartfactory.ca>
 * @author	   		Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @version         $Id: permissions.php 20428 2010-11-21 12:38:18Z phoenyx $
 */

include_once 'admin_header.php';
icms_cp_header();

icms::$module->displayAdminMenu(5, _MI_PROFILE_PERMISSIONS);
$op = isset($_REQUEST['op']) ? trim($_REQUEST['op']) : "edit";
switch ($op) {
	case "edit":
		$title_of_form = _AM_PROFILE_PROF_EDITABLE;
		$perm_name = "profile_edit";
		$restriction = "field_edit";
		$anonymous = false;
		break;
	case "search":
		$title_of_form = _AM_PROFILE_PROF_SEARCH;
		$perm_name = "profile_search";
		$restriction = "";
		$anonymous = true;
		break;
}

$opform = new icms_form_Simple('', 'opform', 'permissions.php', "get");
$op_select = new icms_form_elements_Select("", 'op', $op);
$op_select->setExtra('onchange="document.forms.opform.submit()"');
$op_select->addOption('edit', _AM_PROFILE_PROF_EDITABLE);
$op_select->addOption('search', _AM_PROFILE_PROF_SEARCH);
$opform->addElement($op_select);
$opform->display();

$form = new icms_form_Groupperm($title_of_form, icms::$module->getVar('mid'), $perm_name, '', 'admin/permissions.php', $anonymous);

$profile_handler = icms_getmodulehandler('profile', basename(dirname(dirname(__FILE__))), 'profile');
$fields = $profile_handler->loadFields();

if ($op == "search") {
	$searchable_types = array('textbox', 'select', 'radio', 'yesno', 'date', 'datetime', 'timezone', 'language');
	foreach (array_keys($fields) as $i) {
		if (in_array($fields[$i]->getVar('field_type'), $searchable_types)) $form->addItem($fields[$i]->getVar('fieldid'), $fields[$i]->getVar('field_title'));
	}
} else {
	foreach (array_keys($fields) as $i) {
		if ($restriction == "" || $fields[$i]->getVar($restriction)) $form->addItem($fields[$i]->getVar('fieldid'), $fields[$i]->getVar('field_title'));
	}
}
$form->display();
icms_cp_footer();
?>