<?php
// ------------------------------------------------------------------------- 
//	Registration Codes
//		Copyright 2004, Freeform Solutions
// 		
//	Template
//		Copyright 2004 Thomas Hill
//		<a href="http://www.worldware.com">worldware.com</a>
// ------------------------------------------------------------------------- 
//  This program is free software; you can redistribute it and/or modify     //
//  it under the terms of the GNU General Public License as published by     //
//  the Free Software Foundation; either version 2 of the License, or        //
//  (at your option) any later version.                                      //
//                                                                           //
//  You may not change or alter any portion of this comment or credits       //
//  of supporting developers from this source code or any supporting         //
//  source code which is considered copyrighted (c) material of the          //
//  original comment or credit authors.                                      //
//                                                                           //
//  This program is distributed in the hope that it will be useful,          //
//  but WITHOUT ANY WARRANTY; without even the implied warranty of           //
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            //
//  GNU General Public License for more details.                             //
//                                                                           //
//  You should have received a copy of the GNU General Public License        //
//  along with this program; if not, write to the Free Software              //
//  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA //
//  ------------------------------------------------------------------------ //

require_once '../../../include/cp_header.php';
require_once(XOOPS_ROOT_PATH ."/modules/reg_codes/include/reg_codes_includes.php");
if ( file_exists("../language/".$xoopsConfig['language']."/modinfo.php") ) {
    include_once "../language/".$xoopsConfig['language']."/modinfo.php";
} else {
	include_once "../language/english/modinfo.php";
}
require_once(XOOPS_ROOT_PATH ."/modules/reg_codes/admin/menu.php");


// Get all HTTP post or get parameters, prefixed by "param_"
import_request_variables("gp", "param_");

xoops_cp_header();

print "<p><a href=" . XOOPS_URL . "/modules/reg_codes/admin/index.php>" . _MI_REG_CODES_MENU_MAIN . "</a>&nbsp;&nbsp;&nbsp;&nbsp;<a href=" . XOOPS_URL . "/modules/reg_codes/admin/index.php?op=manager>" . _MI_REG_CODES_MENU_USERMANAGER . "</a></p><hr>\n";





//
// Displays the page to select which admin page you want.
//
function reg_codes_menu()
{
	global $adminmenu;
	xoops_cp_header();
    $p_title = _AM_REG_CODES_TITLE_ADMIN;
    print "<h4 style='text-align:left;'>$p_title</h4>";
  	reg_codes_admin_hmenu();
    print "<dl><BR>\n";
    foreach($adminmenu as $menu_item)
	{
	    print "<dt>\n";
	    $link = $menu_item['link'];
		$link = "../" . $link;
		print "<a href='" . $link . "'";
		if (isset($menu_item['target']))
			print " target='" . $menu_item['target'] . "'";
		print ">" . $menu_item['title'] . "</a>";
	    print "<dd>\n";
		print $menu_item['desc'] . "<P>&nbsp;</P>";
	}
    print "</dl>\n";
    xoops_cp_footer();
    exit();
}

//
// Displays the configuration page for this module
//
function reg_codes_config()
{
	global $adminmenu;
	xoops_cp_header();
    $p_title = _AM_REG_CODES_CONFIGURE;
    print "<h4 style='text-align:left;'>$p_title</h4>";
  	reg_codes_admin_hmenu();
    xoops_cp_footer();
    exit();
}


function reg_codes_user_manager() {

	if(isset($_POST['groupsubmit'])) {
	
		global $xoopsDB;
		$member_handler =& xoops_gethandler('member');
		$source = $_POST['fs_groups']; // array of groupids
		$dest = $_POST['dest_groups']; // array of groupids
		$move = $_POST['movecopy'] == 'move' ? true : false;

		$start = 1;
		foreach($source as $gid) {
			if($start) {
				$gq = "groupid=$gid";
				$start = 0;
			} else {
				$gq .= " OR groupid=$gid";
			}
		}
		$sql = "SELECT distinct(uid) FROM " . $xoopsDB->prefix("groups_users_link") . " WHERE $gq";
		$res = $xoopsDB->query($sql);
		while($uid = $xoopsDB->fetchArray($res)) {
			$groups = $member_handler->getGroupsByUser($uid['uid']);
			foreach($dest as $gid) {
				if(!in_array($gid, $groups)) {
					$sql2 = "INSERT INTO " . $xoopsDB->prefix("groups_users_link") . " (uid, groupid) VALUES (" . $uid['uid'].", $gid)";
					if(!$res2 = $xoopsDB->query($sql2)) {
						print "Error with this SQL: $sql2<br>";
					}
				}
			}
			unset($groups);
		}

		// remove from source groups is moving
		if($move) {
			$sql = "DELETE FROM " . $xoopsDB->prefix("groups_users_link") . " WHERE $gq";
			if(!$res = $xoopsDB->query($sql)) {
				print "Error with this SQL: $sql<br>";
			}
		}
		print "<script type=\"text/javascript\">alert('" . _AM_GROUP_MULTI_COMPLETE . "');</script>\n";
	}
	drawGroupList();	

}


// FUNCTIONS BORROWED FROM FORMULIZE FORMINDEX.PHP
// Handles group lists
function saveList()
{
    global $xoopsDB;


    $op = 'insert'; 

	$name = $_POST['list_name'];
	$id = $_POST['list_id'];
	$groups = implode(",", $_POST['fs_groups']);  

    //echo "save list: name " . $name . ", id " . $id . ", groups " . $groups;
	if($id > 0)
	{
		// Get exisitng name to see if we update, or create new.	
	    $result = $xoopsDB->query("SELECT gl_name FROM ".$xoopsDB->prefix("group_lists")." WHERE gl_id='".$id."'");
	    if($xoopsDB->getRowsNum($result) > 0)
	    {
	        $entry = $xoopsDB->fetchArray($result); 

			//echo $entry['gl_name'];
			  
            if($entry['gl_name'] == $name)
			{ 
				$op = 'update';
			}				  
	    }
	}

	//echo "op" . $op;
	
	switch($op)
    {
		case 'insert':
	        $insert_query = "INSERT INTO ". $xoopsDB->prefix("group_lists") .
	            " (gl_id, gl_name, gl_groups) VALUES ('', '" . $name . "', '" . $groups . "')";

	        $insert_result = $xoopsDB->queryf($insert_query);
	        
	        return $xoopsDB->getInsertId();
						   
		case 'update':
	        $update_query = "UPDATE ". $xoopsDB->prefix("group_lists") .
	            " SET gl_groups = '" . $groups . "' WHERE gl_id='" . $id . "'";

	        $update_result = $xoopsDB->queryf($update_query);
			
			//echo $update_result . "-----" . $xoopsDB->error();  
	        
	        return $id;
	}        
}


function deleteList()
{
    global $xoopsDB;

	$id = $_POST['list_id'];
    
	//echo "Removing " . $id;

    
	if($id)
    {
	    $delete_query = "DELETE FROM ".$xoopsDB->prefix("group_lists") .
	        " WHERE gl_id='" . $id . "'";

	    $delete_result = $xoopsDB->queryf($delete_query);
	}        
}



function drawGroupList($list_id="") {

global $xoopsDB;
include_once XOOPS_ROOT_PATH."/class/xoopsformloader.php";

$form = new XoopsThemeForm(_formulize_MODFORM_TITLE, "groupform", "index.php?op=manager");

$list_op = $_POST['list_op'];
$list_name = $_POST['list_name'];
global $new_list_id;
if(isset($new_list_id))
{
	//echo "new id " . $new_list_id;
	$list_id = $new_list_id;
}
else
{
	$list_id = $_POST['list_id'];
} 

	 
// Permissions
$fs_form_group_perms = new XoopsSimpleForm("", "fs_grouppermsform", "javascript:;");

$fs_select_groups = new XoopsFormSelect(_AM_MULTI_PERMISSIONS, 'fs_groups', null, 10, true);

if($list_op == 'select')
{
	if(isset($list_id))
	{
	    $saved_group_list_results = $xoopsDB->query("SELECT gl_groups FROM ".$xoopsDB->prefix("group_lists")." WHERE gl_id='" . $list_id . "'");
	    if($xoopsDB->getRowsNum($saved_group_list_results) > 0)
	    {
	        $saved_group_list_rowset = $xoopsDB->fetchArray($saved_group_list_results);
	        //echo $saved_group_list_rowset['gl_groups'];  
	        $saved_group_list_ids = explode(",", $saved_group_list_rowset['gl_groups']);
	        
	        $fs_select_groups->setValue($saved_group_list_ids);  
	    }
	}
	
	$list_op = '';  
}
else if($list_op == 'delete')
{
}
else if(isset($_POST['fs_groups']))
{
	$fs_groups = $_POST['fs_groups'];
	$saved_group_list_ids = $fs_groups;  
	
    $fs_select_groups->setValue($fs_groups);  
}


if($_POST['groupsubmit'] == $submit_value)
{	
	$modification_type = $_POST['fs_groupmodificationtype'];

	/*foreach ($saved_group_list_ids as $saved_group_list_id) 
	{
		//echo 'updating' . $saved_group_list_id;
		modifyMultiGroupItem($saved_group_list_id, $s_cat_value, $a_mod_value, $r_mod_value, $r_block_value, $modification_type);
	} */
}


$fs_member_handler =& xoops_gethandler('member');
$fs_xoops_groups =& $fs_member_handler->getGroups();

$fs_count = count($fs_xoops_groups);
for($i = 0; $i < $fs_count; $i++) 
{
	$fs_select_groups->addOption($fs_xoops_groups[$i]->getVar('groupid'), $fs_xoops_groups[$i]->getVar('name'));	 
}

$fs_permsorder = (isset($_POST['fs_permsorder'])) ? $_POST['fs_permsorder'] : 0;

if($fs_permsorder == 1)
{
	asort($fs_select_groups->_options);
	reset($fs_select_groups->_options);
}

$form->addElement($fs_select_groups);

$fs_select_groups_order = new XoopsFormRadio("", 'fs_permsorder', $fs_permsorder);
$fs_select_groups_order->addOptionArray(array('0' => _AM_MULTI_CREATION_ORDER, '1' => _AM_MULTI_ALPHABETICAL_ORDER));
$form->addElement($fs_select_groups_order);

// Lists
$fs_select_lists = new XoopsFormSelect(_AM_MULTI_GROUP_LISTS, 'fs_grouplistnames');
$fs_select_lists->addOption('0', _AM_MULTI_GROUP_LISTS_NOSELECT);

if(isset($list_id))
{
	$fs_select_lists->setValue($list_id); 
}

$result = $xoopsDB->query("SELECT gl_id, gl_name FROM ".$xoopsDB->prefix("group_lists")." ORDER BY gl_name");
if($xoopsDB->getRowsNum($result) > 0)
{
    while($group_list = $xoopsDB->fetchArray($result)) 
    {
        $fs_select_lists->addOption($group_list['gl_id'], $group_list['gl_name']);
    }

}
$form->addElement($fs_select_lists);

$fs_button_group = new XoopsFormElementTray("");

$fs_button_save_list = new XoopsFormButton("", "save_list", _AM_MULTI_SAVE_LIST, "submit");
$fs_button_group->addElement($fs_button_save_list);

$fs_button_delete_list = new XoopsFormButton("", "delete_list", _AM_MULTI_DELETE_LIST, "submit");
$fs_button_group->addElement($fs_button_delete_list);

$form->addElement($fs_button_group);

$list_op_hidden = new XoopsFormHidden("list_op", $list_op);
$form->addElement($list_op_hidden);
$list_name_hidden = new XoopsFormHidden("list_name", $list_name);
$form->addElement($list_name_hidden);
$list_id_hidden = new XoopsFormHidden("list_id", $list_id);
$form->addElement($list_id_hidden);

$fs_dest_groups = new XoopsFormSelect(_AM_MULTI_DEST_GROUPS, 'dest_groups', null, 10, true);

if(isset($_POST['dest_groups']))
{
	$dest_groups = $_POST['dest_groups'];
    $fs_dest_groups->setValue($dest_groups);  
}



$fs_count = count($fs_xoops_groups);
for($i = 0; $i < $fs_count; $i++) 
{
	$fs_dest_groups->addOption($fs_xoops_groups[$i]->getVar('groupid'), $fs_xoops_groups[$i]->getVar('name'));	 
}

$dest_permsorder = (isset($_POST['dest_permsorder'])) ? $_POST['dest_permsorder'] : 0;

if($dest_permsorder == 1)
{
	asort($fs_dest_groups->_options);
	reset($fs_dest_groups->_options);
}

$fs_dest_groups_order = new XoopsFormRadio("", 'dest_permsorder', $dest_permsorder);
$fs_dest_groups_order->addOptionArray(array('0' => _AM_MULTI_CREATION_ORDER, '1' => _AM_MULTI_ALPHABETICAL_ORDER));

if($_POST['movecopy'] == "") { $_POST['movecopy'] = 'copy'; }
$movecopy = new xoopsFormRadio(_AM_MULTI_MOVECOPY, 'movecopy', $_POST['movecopy']);
$movecopy->addOption('move', _AM_MULTI_MOVE);
$movecopy->addOption('copy', _AM_MULTI_COPY);

$form->addElement($fs_dest_groups);
$form->addElement($fs_dest_groups_order);
$form->addElement($movecopy);

$submit_button = new XoopsFormButton("", "groupsubmit", _formulize_SHOW_PERMS, "submit");
$form->addElement($submit_button);
$form->display();




/*
 * Freeform Solutions -begin 
 */
?>
<script language="javascript">
<!--
	debug = false;


    function ChangeOrder()
    {
		if(debug) alert("Changing Order");

        document.groupform.submit(); 
    }


    function SelectList()
    {
		if(debug) alert("Selected " + document.groupform.elements[3].selectedIndex);
		 
		document.groupform.list_op.value = "select";
		document.groupform.list_id.value = document.groupform.elements[3].options[document.groupform.elements[3].selectedIndex].value;		  
		document.groupform.submit(); 
	}
	

    function SaveList()
    {
		default_value = "";

        if(document.groupform.elements[3].selectedIndex > 0)
		{
			default_value = document.groupform.elements[3].options[document.groupform.elements[3].selectedIndex].text; 		
		}   
		
		if(result = prompt("<? echo _AM_MULTI_GROUP_LIST_NAME ?>", default_value))
        {
            if(debug) alert("Adding " + result);
            
	        document.groupform.list_op.value = "save";
	        document.groupform.list_name.value = result;
	        document.groupform.list_id.value = document.groupform.elements[3].options[document.groupform.elements[3].selectedIndex].value;

			return true;  
        }
		
		return false;  
	}
	

    function DeleteList()
    {
		if(document.groupform.elements[3].selectedIndex > 0)
        {
	        if(confirm("<? echo _AM_MULTI_GROUP_LIST_DELETE ?> '" + 
	            document.groupform.elements[3].options[document.groupform.elements[3].selectedIndex].text + "'?"))
	        {
	            if(debug) alert("Removing " + 
                	document.groupform.elements[3].options[document.groupform.elements[3].selectedIndex].value);

	            document.groupform.list_op.value = "delete";
	            document.groupform.list_name.value = document.groupform.elements[3].options[document.groupform.elements[3].selectedIndex].value;
				
				return true;  
	        }
		}            
		
		return false;  
	}

	function confirmMove() {
		if(confirm("<? echo _AM_MULTI_GROUP_CONFIRMMOVE ?>")) {
		} else {
			document.groupform.elements[13].checked = true;
		}
	}
	 	  

    document.groupform.elements[1].onclick = ChangeOrder;  
    document.groupform.elements[2].onclick = ChangeOrder;  

    document.groupform.elements[3].onchange = SelectList;  

	document.groupform.save_list.onclick = SaveList;  
	document.groupform.delete_list.onclick = DeleteList;  

	document.groupform.elements[10].onclick = ChangeOrder;
	document.groupform.elements[11].onclick = ChangeOrder;
	
	document.groupform.elements[12].onclick = confirmMove;


-->
</script>
<?
/*
 * Freeform Solutions -end 
 */


}



function reg_codes_perms()
{

global $xoopsDB, $xoopsModule; 

// initial settings... (cpheader already included above)
include_once XOOPS_ROOT_PATH.'/class/xoopsform/grouppermform.php';
$module_id = $xoopsModule->getVar('mid');

// set the item array...
// 1. get a list of all the groups
// 2. put the list into the array

array($grouplist_id);
array($grouplist_name);
$grouplistq = "SELECT groupid, name FROM " . $xoopsDB->prefix("groups");
$resgrouplistq = $xoopsDB->query($grouplistq); 
while ($rowgrouplistq = $xoopsDB->fetchRow($resgrouplistq))
{
	$grouplist_id[] = $rowgrouplistq[0];
	$grouplist_name[] = $rowgrouplistq[1];
}

// set title
$title_of_form = _AM_REG_CODES_TITLE_ADMIN;

// set perm name
$perm_name = 'issue_codes';

// set description
$perm_desc = _AM_REG_CODES_PERMDESC;

$form = new XoopsGroupPermForm($title_of_form, $module_id, $perm_name, $perm_desc);
for($i=0;$i<count($grouplist_id);$i++)
{
	$form->addItem($grouplist_id[$i], $grouplist_name[$i]);
}


echo $form->render();
}

// added for patch 2.1, jwe April 27, 2006
function reg_codes_patch() {

	if(!isset($_POST['migratedb'])) {
		print "<form action=\"index.php?op=patch31\" method=post>";
		print "<input type = submit name=migratedb value=\"Update Database\">";
		print "</form>";
	} else {
    global $xoopsDB;      

		// check for existence of reg_codes_instant and if present, then skip the 2.1 update stuff
		// not foolproof -- if no codes are in db then there will be an error
		$checksql = "SELECT * FROM " . $xoopsDB->prefix("reg_codes") . " LIMIT 0,1";
		$checkres = $xoopsDB->query($checksql);
		$checkNumRows = $xoopsDB->getRowsNum($checkres);
		$checkarray = $xoopsDB->fetchArray($checkres);
		if(!array_key_exists('reg_codes_instant',$checkarray) AND $checkNumRows > 0) { // only do this if there are existing codes, so a new installation will not be affected
	      	$sql[0] = "ALTER TABLE " . $xoopsDB->prefix("reg_codes") . " ADD reg_codes_instant tinyint(1)";
      		$sql[1] = "ALTER TABLE " . $xoopsDB->prefix("reg_codes") . " ADD reg_codes_redirect varchar(255)";
      		$sql[2] = "ALTER TABLE " . $xoopsDB->prefix("reg_codes") . " CHANGE reg_codes_code reg_codes_code varchar(100)";
		}
		if(!array_key_exists('reg_codes_approval',$checkarray) AND $checkNumRows > 0) {										// nmc 2007.03.22
			$sql[] = "ALTER TABLE " . $xoopsDB->prefix("reg_codes") . " ADD reg_codes_approval varchar(255)";
		}
		
		$sql[] = "ALTER TABLE ".$xoopsDB->prefix("users") . " CHANGE uname uname VARCHAR( 100 ) NOT NULL";
		
		$testsql = "SHOW TABLES";
		$resultst = $xoopsDB->query($testsql);
		while($table = $xoopsDB->fetchRow($resultst)) {
			$existingTables[] = $table[0];
		}
                
		if(!in_array($xoopsDB->prefix("reg_codes_confirm_user"), $existingTables)) {
				
					// reg_codes_confirm_user new for 3.0.  Will need to be checked for in subsequent versions of the patch routine, so we don't try to create it again!
				$reg_codes_confirm_user_sql = "CREATE TABLE " . $xoopsDB->prefix("reg_codes_confirm_user");		// nmc 2007.03.22
				$reg_codes_confirm_user_sql .= " ( reg_codes_conf_id smallint,";								// nmc 2007.03.22
				$reg_codes_confirm_user_sql .= " reg_codes_conf_actkey varchar(100),";							// nmc 2007.03.22
				$reg_codes_confirm_user_sql .= " reg_codes_conf_name varchar(255),";							// nmc 2007.03.22
				$reg_codes_confirm_user_sql .= " reg_codes_conf_email varchar(255),";							// nmc 2007.03.22
				$reg_codes_confirm_user_sql .= " reg_codes_conf_reg_code varchar(255),";						// nmc 2007.03.22
				$reg_codes_confirm_user_sql .= " primary key (reg_codes_conf_id)";								// nmc 2007.03.22
				$reg_codes_confirm_user_sql .= " ) TYPE=MyISAM";												// nmc 2007.03.22
				$sql[] = $reg_codes_confirm_user_sql;															// nmc 2007.03.22
		}

		
		if(!in_array($xoopsDB->prefix("reg_codes_preapproved_users"), $existingTables)) {
				$sql[] = "CREATE TABLE ".$xoopsDB->prefix("reg_codes_preapproved_users"). " (
										reg_codes_preapproved_id int not null auto_increment,
										reg_codes_key int not null,
										reg_codes_preapproved varchar(255),
										primary key (reg_codes_preapproved_id),
										UNIQUE KEY `preapproved_key` (`reg_codes_key`,`reg_codes_preapproved`)	
									) TYPE=MyISAM;";
		}

		for($i=0;$i<count($sql);$i++) {
			if(!$result = $xoopsDB->query($sql[$i])) {
				exit("Error in database update for Registration Codes 3.1.  SQL dump: " . $sql[$i]."<br>".mysql_error());
			}
		} 
		print "DB update completed.";
	}

}


if (!isset($param_op))
	 $param_op = 'menu';

switch ($param_op) 
{
	case "menu":
		reg_codes_perms();
		break;
	case "manager":
		reg_codes_user_manager();
		break;
	case "patch31":								// nmc 2007.03.22
		reg_codes_patch();						// nmc 2007.03.22
		break;									// nmc 2007.03.22

// removed the other options, since we're starting with only the one page that controls permissions, that's all
/*
		reg_codes_menu();
		break;
	case "config":
		reg_codes_config();
		break;
	case "config_post":
		reg_codes_config_post();
		break;
	case "edit":
		reg_codes_edit();
		break;*/

// retain default switch
	default:
	    xoops_cp_header();
		print "<h1>Unknown method requested '$param_op' in admin/index.php</h1>";
	    xoops_cp_footer();
}

	print "<p>version 3.1 RC1</p>";
	xoops_cp_footer();

?>