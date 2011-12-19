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
 * @version         $Id: visibility.php 20562 2010-12-19 18:26:36Z phoenyx $
 */

include 'admin_header.php';
icms_cp_header();

icms::$module->displayAdminMenu(4, _MI_PROFILE_VISIBILITY);
$op = isset($_REQUEST['op']) ? trim($_REQUEST['op']) : "visibility";

$visibility_handler = icms_getmodulehandler('visibility', basename(dirname(dirname(__FILE__))), 'profile');

if (isset($_REQUEST['submit'])) {
    $visibility = $visibility_handler->create();
    $visibility->setVar('fieldid', (int)$_REQUEST['fieldid']);
    $visibility->setVar('user_group', (int)$_REQUEST['ug']);
    $visibility->setVar('profile_group', (int)$_REQUEST['pg']);
    $visibility_handler->insert($visibility);
} elseif ($op == "del") {
    $visibility = $visibility_handler->get(array((int)$_REQUEST['fieldid'], (int)$_REQUEST['ug'], (int)$_REQUEST['pg']));
    $visibility_handler->delete($visibility, true);
}

$field_handler = icms_getmodulehandler('field', basename(dirname(dirname(__FILE__))), 'profile');
$fields = $field_handler->getList();
$visibilities = $visibility_handler->getObjects();
foreach (array_keys($visibilities) as $i) $visifields[$visibilities[$i]->getVar('fieldid')][] = $visibilities[$i]->toArray();

$groups = icms::handler('icms_member')->getGroupList();
asort($groups);
$groups = array(0 => _AM_PROFILE_FIELDVISIBLETOALL)+$groups;

$icmsAdminTpl->assign('fields', $fields);
$icmsAdminTpl->assign('visibilities', $visifields);
$icmsAdminTpl->assign('groups', $groups);

$add_form = new icms_form_Simple('', 'addform', 'visibility.php');
$sel_field = new icms_form_elements_Select(_AM_PROFILE_FIELDVISIBLE, 'fieldid');
$sel_field->addOptionArray($fields);
$add_form->addElement($sel_field);
$sel_ug = new icms_form_elements_Select(_AM_PROFILE_FIELDVISIBLEFOR, 'ug');
$sel_ug->addOptionArray($groups);
$add_form->addElement($sel_ug);
unset($groups[ICMS_GROUP_ANONYMOUS]);
$sel_pg = new icms_form_elements_Select(_AM_PROFILE_FIELDVISIBLEON, 'pg');
$sel_pg->addOptionArray($groups);
$add_form->addElement($sel_pg);
$add_form->addElement(new icms_form_elements_Button('', 'submit', _ADD, 'submit'));
$add_form->assign($icmsAdminTpl);

$icmsAdminTpl->display("db:profile_admin_visibility.html");

icms_cp_footer();
?>