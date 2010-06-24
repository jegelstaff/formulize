<?php
/**
 * Extended User Profile
 *
 *
 * @copyright       The ImpressCMS Project http://www.impresscms.org/
 * @license         LICENSE.txt
 * @license			GNU General Public License (GPL) http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @package         modules
 * @since           1.2
 * @author          Jan Pedersen
 * @author          The SmartFactory <www.smartfactory.ca>
 * @author	   		Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @version         $Id$
 */

include_once("admin_header.php");
xoops_cp_header();

icms_adminMenu(6, "");
$op = isset($_REQUEST['op']) ? trim($_REQUEST['op']) : "edit";
switch ($op) {
    case "visibility":
    header("Location: visibility.php");
    break;
    
    case "edit":
    $title_of_form = _PROFILE_AM_PROF_EDITABLE;
    $perm_name = "profile_edit";
    $restriction = "field_edit";
    $anonymous = false;
    break;
    
    case "search":
    $title_of_form = _PROFILE_AM_PROF_SEARCH;
    $perm_name = "profile_search";
    $restriction = "";
    $anonymous = true;
    break;
}

include_once ICMS_ROOT_PATH."/class/xoopsformloader.php";
$opform = new XoopsSimpleForm('', 'opform', 'permissions.php', "get");
$op_select = new XoopsFormSelect("", 'op', $op);
$op_select->setExtra('onchange="document.forms.opform.submit()"');
$op_select->addOption('visibility', _PROFILE_AM_PROF_VISIBLE);
$op_select->addOption('edit', _PROFILE_AM_PROF_EDITABLE);
$op_select->addOption('search', _PROFILE_AM_PROF_SEARCH);
$opform->addElement($op_select);
$opform->display();

$module_id = $icmsModule->getVar('mid');
$perm_desc = "";
include_once ICMS_ROOT_PATH . '/class/xoopsform/grouppermform.php';
$form = new XoopsGroupPermForm($title_of_form, $module_id, $perm_name, $perm_desc, 'admin/permissions.php', $anonymous);

$profile_handler =& icms_getmodulehandler( 'profile', basename(  dirname(  dirname( __FILE__ ) ) ), 'profile' );
$fields = $profile_handler->loadFields();

if ($op != "search") {
    foreach (array_keys($fields) as $i) {
        if ($restriction == "" || $fields[$i]->getVar($restriction)) {
            $form->addItem($fields[$i]->getVar('fieldid'), $fields[$i]->getVar('field_title'));
        }
    }
}
else {
    $searchable_types = array('textbox',
    'select',
    'radio',
    'yesno',
    'date',
    'datetime',
    'timezone',
    'language');
    foreach (array_keys($fields) as $i) {
        if (in_array($fields[$i]->getVar('field_type'), $searchable_types)) {
            $form->addItem($fields[$i]->getVar('fieldid'), $fields[$i]->getVar('field_title'));
        }
    }
}
$form->display();
xoops_cp_footer();
?>