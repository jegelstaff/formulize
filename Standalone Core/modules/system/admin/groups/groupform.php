<?php
/**
* Form for setting group options
*
* @copyright	http://www.xoops.org/ The XOOPS Project
* @copyright	XOOPS_copyrights.txt
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license	LICENSE.txt
* @package	Administration
* @since	XOOPS
* @author	http://www.xoops.org The XOOPS Project
* @author		Kazumi Ono (AKA onokazu) http://www.myweb.ne.jp/, http://www.xoops.org/, http://jp.xoops.org/
* @author	modified by UnderDog <underdog@impresscms.org>
* @version	$Id: groupform.php 8948 2009-07-02 19:35:23Z Phoenyx $
*/



/** include the general form class */
include_once XOOPS_ROOT_PATH.'/class/xoopsformloader.php';

$name_text = new XoopsFormText(_AM_NAME, "name", 30, 50, $name_value);
$desc_text = new XoopsFormTextArea(_AM_DESCRIPTION, "desc", $desc_value);

$s_cat_checkbox = new XoopsFormCheckBox(_AM_SYSTEMRIGHTS, "system_catids[]", $s_cat_value);
//if (isset($s_cat_disable) && $s_cat_disable) {
//  $s_cat_checkbox->setExtra('checked="checked" disabled="disabled"');
//}
include_once XOOPS_ROOT_PATH.'/modules/system/constants.php';
require_once XOOPS_ROOT_PATH.'/class/xoopslists.php';
$admin_dir = XOOPS_ROOT_PATH.'/modules/system/admin/';
$dirlist = XoopsLists::getDirListAsArray($admin_dir);
/* changes to only allow permission admins you already have */
global $icmsUser;
$gperm =& xoops_gethandler ( 'groupperm' );
$groups = $icmsUser->getGroups ();
foreach($dirlist as $file){
	include XOOPS_ROOT_PATH.'/modules/system/admin/'.$file.'/xoops_version.php';
	if (!empty($modversion['category']) && count(array_intersect($groups, $gperm->getGroupIds('system_admin', $modversion['category'])))>0) {
		$s_cat_checkbox->addOption($modversion['category'], $modversion['name']);
	}
	unset($modversion);
}
unset($dirlist);

$a_mod_checkbox = new XoopsFormCheckBox(_AM_ACTIVERIGHTS, "admin_mids[]", $a_mod_value);
$module_handler =& xoops_gethandler('module');
$criteria = new CriteriaCompo(new Criteria('hasadmin', 1));
$criteria->add(new Criteria('isactive', 1));
$criteria->add(new Criteria('dirname', 'system', '<>'));
/* criteria added to see if the active user can admin the module, do not filter for administrator group  (module_admin)*/
if (!in_array(XOOPS_GROUP_ADMIN, $groups)){
	$a_mod = $gperm->getItemIds('module_admin',$groups);
	$criteria->add(new Criteria('mid', '('.implode(',',$a_mod).')', 'IN'));}
$a_mod_checkbox->addOptionArray($module_handler->getList($criteria));

$r_mod_checkbox = new XoopsFormCheckBox(_AM_ACCESSRIGHTS, "read_mids[]", $r_mod_value);
$criteria = new CriteriaCompo(new Criteria('hasmain', 1));
$criteria->add(new Criteria('isactive', 1));
/* criteria added to see if the active user can access the module, do not filter for administrator group  (module_read)*/
if (!in_array(XOOPS_GROUP_ADMIN, $groups)){
	$r_mod = $gperm->getItemIds('module_read',$groups);
	$criteria->add(new Criteria('mid', '('.implode(',',$r_mod).')', 'IN'));}
$r_mod_checkbox->addOptionArray($module_handler->getList($criteria));

$ed_mod_checkbox = new XoopsFormCheckBox(_AM_EDPERM, "useeditor_mids[]", $ed_mod_value);
$criteria = new CriteriaCompo(new Criteria('isactive', 1));
/* criteria added to see where the active user can use the wysiwyg editors (use_wysiwygeditor)
 * administrators don't have explicit entries for this, do not filter
 */
if (!in_array(XOOPS_GROUP_ADMIN, $groups)){
	$ed_mod = $gperm->getItemIds('use_wysiwygeditor',$groups);
	$criteria->add(new Criteria('mid', '('.implode(',',$ed_mod).')', 'IN'));}
$ed_mod_checkbox->addOptionArray($module_handler->getList($criteria));

$debug_mod_checkbox = new XoopsFormCheckBox(_AM_DEBUG_PERM, "enabledebug_mids[]", $debug_mod_value);
$criteria = new CriteriaCompo(new Criteria('isactive', 1));
/* criteria added to see where the active user can view the debug mode (enable_debug)
 * administrators do not have explicit entries for this, do not filter
 */
if (!in_array(XOOPS_GROUP_ADMIN, $groups)){
	$debug_mod = $gperm->getItemIds('enable_debug',$groups);
	$criteria->add(new Criteria('mid', '('.implode(',',$debug_mod).')', 'IN'));}
$debug_mod_checkbox->addOptionArray($module_handler->getList($criteria));

/**
 * @todo: Needs to be improved... is a test of concept... and works!
 * @todo: Create the language constant.
 */
$group_manager_checkbox = new XoopsFormCheckBox(_AM_GROUPMANAGER_PERM, "groupmanager_gids[]", $group_manager_value);
$criteria = new CriteriaCompo(new Criteria('isactive', 1));
$groups = $member_handler->getGroups();
$gperm_handler =& xoops_gethandler('groupperm');

//global $icmsUser; // already declared above
foreach($groups as $group){
	if($gperm_handler->checkRight('group_manager', $group->getVar('groupid'), $icmsUser->getGroups()))
		$group_manager_checkbox->addOption($group->getVar('groupid'),$group->getVar('name'));
}
$icms_block_handler = xoops_gethandler('block');
$posarr = $icms_block_handler->getBlockPositions(true);
$block_checkbox = array();
$i = 0;
$groups = $icmsUser->getGroups();
foreach ($posarr as $k=>$v){
	$tit = (defined($posarr[$k]['title'])) ? constant($posarr[$k]['title']) : $posarr[$k]['title'];
	$block_checkbox[$i] = new XoopsFormCheckBox('<b>'.$tit.'</b><br />', "read_bids[]", $r_block_value);
	$new_blocks_array = array();
	$blocks_array = $icms_block_handler->getAllBlocks("list", $k);

	/* compare to list of blocks the group can read, do not filter for administrator group */
	if (!in_array(XOOPS_GROUP_ADMIN, $groups)){
		$r_blocks = $gperm->getItemIds('block_read', $groups);
		$n_blocks_array = array_intersect_key($blocks_array, array_flip($r_blocks));
	} else {
		$n_blocks_array = $blocks_array;
	}
	foreach ($n_blocks_array as $key=>$value) {
		$new_blocks_array[$key] = "<a href='".XOOPS_URL."/modules/system/admin.php?fct=blocksadmin&amp;op=mod&amp;bid=".$key."'>".$value." (ID: ".$key.")</a>";
	}
	$block_checkbox[$i]->addOptionArray($new_blocks_array);
	$i++;
}
$r_block_tray = new XoopsFormElementTray(_AM_BLOCKRIGHTS, "<br /><br />");
foreach ($block_checkbox as $k=>$v){
	$r_block_tray->addElement($block_checkbox[$k]);
}


$op_hidden = new XoopsFormHidden("op", $op_value);
$fct_hidden = new XoopsFormHidden("fct", "groups");
$submit_button = new XoopsFormButton("", "groupsubmit", $submit_value, "submit");
$form = new XoopsThemeForm($form_title, "groupform", "admin.php", "post", true);
$form->addElement($name_text);
$form->addElement($desc_text);
$form->addElement($s_cat_checkbox);
/**
 * @todo: use constants instead of hard values
 */ 
if (!isset($g_id) || ($g_id != 1 && $g_id != 3)){
	$form->addElement($group_manager_checkbox);
}
$form->addElement($a_mod_checkbox);
$form->addElement($r_mod_checkbox);
if (!isset($g_id) || $g_id != 3){
	$form->addElement($ed_mod_checkbox);
}
/**
 * @todo: use constants instead of hard values
 */ 

if( !isset($g_id) || $g_id != 1 ){
	$form->addElement($debug_mod_checkbox);
}

$form->addElement($r_block_tray);
$form->addElement($op_hidden);
$form->addElement($fct_hidden);
if ( !empty($g_id_value) ) {
	$g_id_hidden = new XoopsFormHidden("g_id", $g_id_value);
	$form->addElement($g_id_hidden);
}
$form->addElement($submit_button);
$form->setRequired($name_text);
$form->display();

?>