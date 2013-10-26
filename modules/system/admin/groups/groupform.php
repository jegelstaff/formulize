<?php
/**
 * Form for setting group options
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		LICENSE.txt
 * @package		Administration
 * @author		Kazumi Ono (AKA onokazu) http://www.myweb.ne.jp/, http://www.xoops.org/, http://jp.xoops.org/
 * @author		modified by UnderDog <underdog@impresscms.org>
 * @version		SVN: $Id: groupform.php 21374 2011-03-30 13:23:21Z m0nty_ $
 */

$name_text = new icms_form_elements_Text(_AM_NAME, "name", 30, 50, $name_value);
$desc_text = new icms_form_elements_Textarea(_AM_DESCRIPTION, "desc", $desc_value);

$s_cat_checkbox = new icms_form_elements_Checkbox(_AM_SYSTEMRIGHTS, "system_catids[]", $s_cat_value);

include_once ICMS_MODULES_PATH . '/system/constants.php';
$admin_dir = ICMS_MODULES_PATH . '/system/admin/';
$dirlist = icms_core_Filesystem::getDirList($admin_dir);
/* changes to only allow permission admins you already have */
$gperm = icms::handler('icms_member_groupperm');
$groups = icms::$user->getGroups ();
foreach ($dirlist as $file) {
	if (file_exists(ICMS_MODULES_PATH . '/system/admin/' . $file . '/icms_version.php')) {
		include ICMS_MODULES_PATH . '/system/admin/' . $file . '/icms_version.php';
	} elseif (file_exists(ICMS_MODULES_PATH . '/system/admin/' . $file . '/xoops_version.php')) {
		include ICMS_MODULES_PATH . '/system/admin/' . $file . '/xoops_version.php';
	}
	if (!empty($modversion['category']) && count(array_intersect($groups, $gperm->getGroupIds('system_admin', $modversion['category'])))>0) {
		$s_cat_checkbox->addOption($modversion['category'], $modversion['name']);
	}
	unset($modversion);
}
unset($dirlist);

$a_mod_checkbox = new icms_form_elements_Checkbox(_AM_ACTIVERIGHTS, "admin_mids[]", $a_mod_value);
$module_handler = icms::handler('icms_module');
$criteria = new icms_db_criteria_Compo(new icms_db_criteria_Item('hasadmin', 1));
$criteria->add(new icms_db_criteria_Item('isactive', 1));
$criteria->add(new icms_db_criteria_Item('dirname', 'system', '<>'));
/* criteria added to see if the active user can admin the module, do not filter for administrator group  (module_admin)*/
if (!in_array(XOOPS_GROUP_ADMIN, $groups)) {
	$a_mod = $gperm->getItemIds('module_admin', $groups);
	$criteria->add(new icms_db_criteria_Item('mid', '(' . implode(',', $a_mod) . ')', 'IN'));
}
$a_mod_checkbox->addOptionArray($module_handler->getList($criteria));

$r_mod_checkbox = new icms_form_elements_Checkbox(_AM_ACCESSRIGHTS, "read_mids[]", $r_mod_value);
$criteria = new icms_db_criteria_Compo(new icms_db_criteria_Item('hasmain', 1));
$criteria->add(new icms_db_criteria_Item('isactive', 1));
/* criteria added to see if the active user can access the module, do not filter for administrator group  (module_read)*/
if (!in_array(XOOPS_GROUP_ADMIN, $groups)) {
	$r_mod = $gperm->getItemIds('module_read', $groups);
	$criteria->add(new icms_db_criteria_Item('mid', '(' . implode(',', $r_mod) . ')', 'IN'));
}
$r_mod_checkbox->addOptionArray($module_handler->getList($criteria));

$ed_mod_checkbox = new icms_form_elements_Checkbox(_AM_EDPERM, "useeditor_mids[]", $ed_mod_value);
$criteria = new icms_db_criteria_Compo(new icms_db_criteria_Item('isactive', 1));
/* criteria added to see where the active user can use the wysiwyg editors (use_wysiwygeditor)
 * administrators don't have explicit entries for this, do not filter
 */
if (!in_array(XOOPS_GROUP_ADMIN, $groups)) {
	$ed_mod = $gperm->getItemIds('use_wysiwygeditor', $groups);
	$criteria->add(new icms_db_criteria_Item('mid', '(' . implode(',', $ed_mod) . ')', 'IN'));
}
$ed_mod_checkbox->addOptionArray($module_handler->getList($criteria));

$debug_mod_checkbox = new icms_form_elements_Checkbox(_AM_DEBUG_PERM, "enabledebug_mids[]", $debug_mod_value);
$criteria = new icms_db_criteria_Compo(new icms_db_criteria_Item('isactive', 1));
/* criteria added to see where the active user can view the debug mode (enable_debug)
 * administrators do not have explicit entries for this, do not filter
 */
if (!in_array(XOOPS_GROUP_ADMIN, $groups)) {
	$debug_mod = $gperm->getItemIds('enable_debug', $groups);
	$criteria->add(new icms_db_criteria_Item('mid', '(' . implode(',', $debug_mod) . ')', 'IN'));
}
$debug_mod_checkbox->addOptionArray($module_handler->getList($criteria));

$group_manager_checkbox = new icms_form_elements_Checkbox(_AM_GROUPMANAGER_PERM, "groupmanager_gids[]", $group_manager_value);
$criteria = new icms_db_criteria_Compo(new icms_db_criteria_Item('isactive', 1));
$groups = $member_handler->getGroups();
$gperm_handler = icms::handler('icms_member_groupperm');

foreach ($groups as $group) {
	if ($gperm_handler->checkRight('group_manager', $group->getVar('groupid'), icms::$user->getGroups()))
	$group_manager_checkbox->addOption($group->getVar('groupid'), $group->getVar('name'));
}
$icms_block_handler = icms::handler('icms_view_block');
$posarr = $icms_block_handler->getBlockPositions(true);
$block_checkbox = array();
$i = 0;
$groups = icms::$user->getGroups();
foreach ($posarr as $k=>$v) {
	$tit = (defined($posarr[$k]['title'])) ? constant($posarr[$k]['title']) : $posarr[$k]['title'];
	$block_checkbox[$i] = new icms_form_elements_Checkbox('<strong">' . $tit . '</strong><br />', "read_bids[]", $r_block_value);
	$new_blocks_array = array();
	$blocks_array = $icms_block_handler->getAllBlocks("list", $k);

	/* compare to list of blocks the group can read, do not filter for administrator group */
	if (!in_array(XOOPS_GROUP_ADMIN, $groups)) {
		$r_blocks = $gperm->getItemIds('block_read', $groups);
		$n_blocks_array = array_intersect_key($blocks_array, array_flip($r_blocks));
	} else {
		$n_blocks_array = $blocks_array;
	}
	foreach ($n_blocks_array as $key=>$value) {
		$new_blocks_array[$key] = "<a href='" . ICMS_MODULES_URL . "/system/admin.php?fct=blocksadmin&amp;op=mod&amp;bid=" . $key . "'>" . $value . " (ID: " . $key . ")</a>";
	}
	$block_checkbox[$i]->addOptionArray($new_blocks_array);
	$i++;
}
$r_block_tray = new icms_form_elements_Tray(_AM_BLOCKRIGHTS, "<br /><br />");
foreach ($block_checkbox as $k=>$v) {
	$r_block_tray->addElement($block_checkbox[$k]);
}

$op_hidden = new icms_form_elements_Hidden("op", $op_value);
$fct_hidden = new icms_form_elements_Hidden("fct", "groups");
$submit_button = new icms_form_elements_Button("", "groupsubmit", $submit_value, "submit");
$form = new icms_form_Theme($form_title, "groupform", "admin.php", "post", true);
$form->addElement($name_text);
$form->addElement($desc_text);
$form->addElement($s_cat_checkbox);

if (!isset($g_id) || ($g_id != XOOPS_GROUP_ADMIN && $g_id != XOOPS_GROUP_ANONYMOUS)) {
	$form->addElement($group_manager_checkbox);
}
$form->addElement($a_mod_checkbox);
$form->addElement($r_mod_checkbox);
if (!isset($g_id) || $g_id != XOOPS_GROUP_ANONYMOUS) {
	$form->addElement($ed_mod_checkbox);
}

if (!isset($g_id) || $g_id != XOOPS_GROUP_ADMIN) {
	$form->addElement($debug_mod_checkbox);
}

$form->addElement($r_block_tray);
$form->addElement($op_hidden);
$form->addElement($fct_hidden);
if (!empty($g_id_value)) {
	$g_id_hidden = new icms_form_elements_Hidden("g_id", $g_id_value);
	$form->addElement($g_id_hidden);
}
$form->addElement($submit_button);
$form->setRequired($name_text);
$form->display(); // render() does not output the form, just contains the output

