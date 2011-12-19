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

include 'admin_header.php';
xoops_cp_header();

icms_adminMenu(5, "");
$op = isset($_REQUEST['op']) ? trim($_REQUEST['op']) : "visibility";

include_once ICMS_ROOT_PATH."/class/xoopsformloader.php";
$opform = new XoopsSimpleForm('', 'opform', 'permissions.php', "get");
$op_select = new XoopsFormSelect("", 'op', $op);
$op_select->setExtra('onchange="document.forms.opform.submit()"');
$op_select->addOption('visibility', _PROFILE_AM_PROF_VISIBLE);
$op_select->addOption('edit', _PROFILE_AM_PROF_EDITABLE);
$op_select->addOption('search', _PROFILE_AM_PROF_SEARCH);
$opform->addElement($op_select);
$opform->display();

$visibility_handler = icms_getmodulehandler( 'visibility', basename(  dirname(  dirname( __FILE__ ) ) ), 'profile' );
$field_handler =& icms_getmodulehandler( 'field', basename(  dirname(  dirname( __FILE__ ) ) ), 'profile' );
$fields = $field_handler->getList();

if (isset($_REQUEST['submit'])) {
    $visibility = $visibility_handler->create();
    $visibility->setVar('fieldid', intval($_REQUEST['fieldid']));
    $visibility->setVar('user_group', $_REQUEST['ug']);
    $visibility->setVar('profile_group', $_REQUEST['pg']);
    $visibility_handler->insert($visibility);
}
if ($op == "del") {
    $visibility = $visibility_handler->get(array(intval($_REQUEST['fieldid']), $_REQUEST['ug'], $_REQUEST['pg']));
    $visibility_handler->delete($visibility, true);
    header("Location: visibility.php");
}

$visibilities = $visibility_handler->getObjects();
$visifields = '';
foreach (array_keys($visibilities) as $i) {
    $visifields[$visibilities[$i]->getVar('fieldid')][] = $visibilities[$i]->toArray();
}
$member_handler = xoops_gethandler('member');
$groups = $member_handler->getGroupList();
$groups[0] = _PROFILE_AM_FIELDVISIBLETOALL;
asort($groups);

$xoopsTpl->assign('fields', $fields);
$xoopsTpl->assign('visibilities', $visifields);
$xoopsTpl->assign('groups', $groups);

$add_form = new XoopsSimpleForm('', 'addform', 'visibility.php');

$sel_field = new XoopsFormSelect(_PROFILE_AM_FIELDVISIBLE, 'fieldid');
$sel_field->addOptionArray($fields);
$add_form->addElement($sel_field);

$sel_ug = new XoopsFormSelect(_PROFILE_AM_FIELDVISIBLEFOR, 'ug');
$sel_ug->addOptionArray($groups);
$add_form->addElement($sel_ug);

unset($groups[ICMS_GROUP_ANONYMOUS]);
$sel_pg = new XoopsFormSelect(_PROFILE_AM_FIELDVISIBLEON, 'pg');
$sel_pg->addOptionArray($groups);
$add_form->addElement($sel_pg);

$add_form->addElement(new XoopsFormButton('', 'submit', _ADD, 'submit'));
$add_form->assign($xoopsTpl);

$xoopsTpl->display("db:profile_admin_visibility.html");

xoops_cp_footer();
?>