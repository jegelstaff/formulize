<?php
// ------------------------------------------------------------------------- 
//	Registration Codes
//		Copyright 2004, Freeform Solutions
// 		
//	Template
//		Copyright 2004 Thomas Hill
//		<a href="http://www.worldware.com">worldware.com</a>
// ------------------------------------------------------------------------- 
// ------------------------------------------------------------------------- //
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
// ------------------------------------------------------------------------- //

require_once '../../../include/cp_header.php';
require_once(XOOPS_ROOT_PATH ."/modules/reg_codes/include/reg_codes_includes.php");

if ( file_exists("../language/".$xoopsConfig['language']."/modinfo.php") ) {
    include_once "../language/".$xoopsConfig['language']."/modinfo.php";
} else {
	include_once "../language/english/modinfo.php";
}
require_once(XOOPS_ROOT_PATH ."/modules/reg_codes/admin/menu.php");

// Get HTTP post/get parameters.
import_request_variables("gp", "param_");


//
// Writes out the form to get all config parameters.
//
function reg_codes_r_edit_form()
{
	reg_codes_r_form(true, _AM_REG_CODES_LABEL_R_EDIT);
	reg_codes_r_del_form();
}
function reg_codes_r_add_form()
{
	reg_codes_r_form(false, _AM_REG_CODES_LABEL_R_ADD);
}

//
// Writes out the form used for adding a row, and editing a row
// $edit == true writes out a form to edit an existing row
// $edit == false writes out a form to add a new row
// returns nothing
//
function reg_codes_r_form($editing, $heading)
{
	global $param_r_key;
	global $xoopsDB;

	if ($editing)
	{
		list($result, $error) = reg_codes_get_r_result($param_r_key);
		if ($error)
		{
			printf(_AM_REG_CODES_FMT_ERROR, $error['msg'], $error['data']);
			return;
		}
		$values = $xoopsDB->fetchArray($result);
	}
    print "
    <form action='database_table.php' method='POST' enctype='application/x-www-form-urlencoded'>\n
    <table border='1' cellpadding='0' cellspacing='0' width='100%'>\n
        <tr><th>$heading</th></tr>\n
        <tr>\n
        <td class='bg2'>\n
            <table width='100%' border='0' cellpadding='4' cellspacing='1'>\n";
			$field_list = reg_codes_get_r_fields();
            foreach ($field_list as $field => $prompt)
            {
				$pname = "param_" . $field;
				if ($editing OR ($field != 'r_key'))
				{
		            print "
					<tr nowrap='nowrap'>\n
					<td class ='head'>" . $prompt . "</td>\n
	                <td class='even'>\n";
					if ($field == 'r_key')
					{
						print $values[$field];
		                print "<input type='hidden' name='r_key' value='". $values[$field] . "' />\n";
					}	
					else
					{	
						print "<input type='text' name='$field' size='32' maxlength='256' value =\"";
						if ($editing)
						{
//							$clean_string = str_replace("\"", "'", $values[$field]);
							$clean_string = htmlentities($values[$field]);
							print $clean_string;
						}
			            print "\">\n";
					}
					print "</td>\n";
					print "</tr>\n";
				}
            }
			print "<tr>
                <td class='head'>&nbsp;</td>\n
                <td class='even'>\n";
            print "<input type='hidden' name='op' value='edit_post' />\n
                <input type='hidden' name='window' value='config' />\n
                <input type='submit' value='"._AM_REG_CODES_BUT_SAVE."' />\n
                </td></tr>\n
            </table>\n
        </td></tr>\n
    </table>\n
    </form>\n";
}

//
// Writes out the form used for deleting 
// returns nothing
//
function reg_codes_r_del_form()
{
	global $param_r_key;
	$heading = _AM_REG_CODES_LABEL_R_DEL;
    print "
    <form action='database_table.php' method='POST' enctype='application/x-www-form-urlencoded'>\n
    <table border='1' cellpadding='0' cellspacing='0' width='100%'>\n
        <tr><th>$heading</th></tr>\n
        <tr>\n
        <td class='bg2'>\n
            <table width='100%' border='0' cellpadding='4' cellspacing='1'>\n";
			print "<tr>
                <td class='head'>$heading</td>\n
                <td class='even'>\n
                    <input type='hidden' name='op' value='del_post' />\n
                    <input type='hidden' name='r_key' value='$param_r_key' />\n
                    <input type='submit' value='"._AM_REG_CODES_BUT_DELETE."' />\n
                </td></tr>\n
            </table>\n
        </td></tr>\n
    </table>\n
    </form>\n";
}

//
// Gets a the category that is currently stored in the database.
// returns array($result, $errormessage)
// if no error, $errormessage is null
//
function reg_codes_get_r_result($r_key = null)
{
	global $xoopsDB;
	$bci = reg_codes_get_r_fields();
	
	$sql =	"select " . reg_codes_to_string($bci) . " from " . $xoopsDB->prefix("reg_codes_r");

	if ($r_key != null)
	{
		// Make sure $r_key is valid, since it comes from the user. 
		// A cracker could set it to some sql string
		if (!is_numeric($r_key))
		{
			$error = array('msg' => _AM_REG_CODES_ERR_r_BAD_KEY, 'data' => $sql);
			return array(null, $error);
		}
		$sql .= " where r_key = $r_key";
	}
	$result = $xoopsDB->query($sql);
	if (!$result)
	{
		
		$error = array('msg' => $xoopsDB->error(), 'data' => $sql);
		return array(null, $error);
	}
	return array($result, null);
}

//
// Writes out the form used for selecting a row to edit
// returns nothing
//
function reg_codes_r_main_select()
{
	$method = "POST";// POST, except when debugging
	global $xoopsDB;
	list($result, $error) = reg_codes_get_r_result();
	if ($error)
	{
		printf(_AM_REG_CODES_FMT_ERROR, $error['msg'], $error['data']);
		return;
	}
    print "<table border='1' cellpadding='0' cellspacing='0' width='100%'>\n";
    print "<tr>\n";
    print "<td class='bg2'>\n";

	print "<table width='100%' border='0' cellpadding='4' cellspacing='1'>\n";
    print "<tr>";
	$field_list = reg_codes_get_r_fields();
	foreach ($field_list as $field => $prompt)
	{
		print "<th>$prompt</th>\n";
	}
	print "<th>" . _AM_REG_CODES_LABEL_R_EDIT2 . "</th>\n";
	print "</tr>\n";
	$are_there_any = false;
	while ($values = $xoopsDB->fetchArray($result))
	{
		$are_there_any = true;
		print "<tr nowrap='nowrap'>\n";
	    $limit = 64;
		foreach($values as $field => $value)
		{
			print "<td class='even' valign = 'top' >\n";
			$value = htmlentities($value);
			if (strlen($value) > $limit)
				$value = substr($value, 0, $limit) . "...";
			print $value;
			print "</td>\n";
		}
		print "<td class='even' valign = 'top'>\n";
	    print "<form action='database_table.php' method='$method' enctype='application/x-www-form-urlencoded'>\n";
        print "<input type='hidden' name='r_key' value='". $values['r_key']. "' />\n";
        print "<input type='hidden' name='op' value='select_post' />\n";
        print "<input type='submit' value='"._AM_REG_CODES_BUT_EDIT."' />\n";
		print "</form>\n";
        print "</td>\n";
		print "</tr>\n";
	}
	if (!$are_there_any)
		print "<tr><td class = 'even' colspan = '" . (count($field_list)+1) . "'>". _AM_REG_CODES_ERR_R_NONE . "</td></tr>";
    print "</table>\n";
    print "</td></tr>\n";
	print "</table>\n";
}

//
// Displays the main admin interface
//
function reg_codes_main()
{
    $p_title = _AM_REG_CODES_R_TITLE;
    xoops_cp_header();
    print "<h4 style='text-align:left;'>$p_title</h4>";
    reg_codes_admin_hmenu();
    reg_codes_r_add_form();
    reg_codes_r_main_select();
    xoops_cp_footer();
    exit();
}
//
// Edit one row from r
//
function reg_codes_r_main_select_post()
{
	xoops_cp_header();
    $p_title = _AM_REG_CODES_R_TITLE;
    print "<h4 style='text-align:left;'>$p_title</h4>";
    reg_codes_admin_hmenu();
    reg_codes_r_edit_form();
    xoops_cp_footer();
    exit();
}

//
// Delete one row from r
//
function reg_codes_r_delete()
{
	global $xoopsDB;
	global $param_r_key;
	
	$sql =	"delete from " .
		$xoopsDB->prefix("reg_codes_r")
		. " where r_key = " 
		. "'" . mysql_escape_string($param_r_key) . "'";


	if (!$xoopsDB->query($sql))
	{
		$error = $xoopsDB->error();
		xoops_cp_header();
		reg_codes_show_sql_error(_AM_REG_CODES_ERR_DELETE_FAILED, $error, $sql);
		xoops_cp_footer();
	}
	else 
	{
        	redirect_header("database_table.php",1,_AM_REG_CODES_OK_DB);
	}
    exit();
}

// Processes the configuration update request, by 
// getting the HTTP parameters, and putting them into the database.
function reg_codes_r_update()
{
	global $xoopsDB;
	$field_list = reg_codes_get_r_fields();
	foreach ($field_list as $field => $prompt)
    {
		$param = "param_" . $field;
		global $$param;
	}
	$sql =	"REPLACE INTO  " 
		. $xoopsDB->prefix("reg_codes_r")
		. " (" . reg_codes_to_string($field_list) . ") VALUES (";

	$first = true;
    foreach ($field_list as $field => $prompt)
	{
		$param = "param_" . $field;
		$param_value = $$param;
		if (get_magic_quotes_gpc())
			$param_value = stripslashes($param_value);
			
		if (!$first)
			$sql .= ", ";
		$param_value = get_magic_quotes_gpc()?stripslashes($$param):$$param;
		$sql .= "'" . mysql_escape_string($param_value) .  "'";
		$first = false;
	}
	$sql .= " )";
	if (!$xoopsDB->query($sql))
	{
		$error = $xoopsDB->error();
		xoops_cp_header();
		reg_codes_show_sql_error(_AM_REG_CODES_ERR_REPLACE_FAILED, $error, $sql);
		xoops_cp_footer();
	} else 
	{
		redirect_header("database_table.php",1,_AM_REG_CODES_OK_DB);
	}
	exit();
}

if (!isset($param_op))
	 $param_op = 'main';

switch ($param_op) 
{
	case "main":
		reg_codes_main();
		break;
	case "select_post":
		reg_codes_r_main_select_post();
		break;
	case "edit_post":
		reg_codes_r_update();
		break;
	case "add_post":
		reg_codes_r_add();
		break;
	case "del_post":
		reg_codes_r_delete();
		break;
	default:
	    xoops_cp_header();
		print "<h1>Unknown method requested '$param_op'</h1>";
	    xoops_cp_footer();
}
?>