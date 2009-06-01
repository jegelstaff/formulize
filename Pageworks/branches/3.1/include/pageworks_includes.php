<?php
// ------------------------------------------------------------------------- //
// Copyright 2004, Thomas Hill,                                              //
// <a href="http://www.worldware.com">worldware.com</a>                      //
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
//  ------------------------------------------------------------------------ //

// Get the definitions for the strings used in the user interface.
if ( file_exists(XOOPS_ROOT_PATH ."/modules/pageworks/language/english/admin.php") ) 
	require_once XOOPS_ROOT_PATH ."/modules/pageworks/language/english/admin.php";
else 
	include_once XOOPS_ROOT_PATH ."/modules/pageworks/language/english/admin.php";


//
// Formats an array as a comma delimited string
//
function pageworks_to_string($assoc_array)
{
	$str = "";
	foreach($assoc_array as $key => $value)
	{
		if (strlen($str) != 0)
			$str .= ", ";
		$str .= $key;
	}
	return $str;
}

//
// Shows the error message and query for a failed database access
//
function pageworks_show_sql_error($title, $error, $sql)
{
		print 
		"<table>\n"
		. "<TR><TH colspan = '2'>$title</TH></TR>\n"
		. "<TR><TD class='head' valign = 'top'>". _AM_PAGEWORKS_ERR_ERROR . "</TD><TD class='even'>$error</TD></TR>\n"
		. "<TR><TD class='head' valign = 'top'>". _AM_PAGEWORKS_ERR_QUERY . "</TD><TD class='even'>$sql</TD></TR>\n"
		. "</table>\n";
}


//
// Formats and returns a horizontal menu
//
function pageworks_hmenu($menu_def)
{
    $str = "<P>";
    $first = true;
    foreach($menu_def as $menu_title => $menu_link)
	{
	    if ($first) $first = false; else $str .= "| ";
		$str .= "<a href='" . $menu_link . "'>" . $menu_title . "</a> ";
	}
    $str .= "</p>";
    return $str;
}

//
// Displays the horizontal menu for pageworks administration
//
function pageworks_admin_hmenu()
{
	// ZZZFix this. Clean up menu sharing.
	global $adminmenu;
    print "<table><tr>";
    $first = true;
    foreach($adminmenu as $menu_item)
	{
	    print "<td>\n";
	    $link = $menu_item['link'];
		$link = "../" . $link;
	    if ($first) $first = false; else print "| ";
		print "<a href='" . $link . "'";
		if (isset($menu_item['target']))
			print " target='" . $menu_item['target'] . "'";
		print ">" . $menu_item['title'] . "</a>";
	    print "</td>\n";
	}
    print "</tr></table><BR>";
}

//
// Loads the strings used for internationalizing the templates into the current template
//
function pageworks_add_intl()
{
	global $xoopsTpl;
	$intl = pageworks_get_intl();
	$xoopsTpl->assign("lang", $intl);
}

//
// Returns an associative array, with the constants used for internationalizing the templates
// This allows a module to run in multiple languages at a time (each user can choose a language)
//
function pageworks_get_intl()
{
	$intl = array
	(
		"block_title" =>_AM_PAGEWORKS_LANG_BLOCK_TITLE,
		"error" =>_AM_PAGEWORKS_LANG_ERROR,
		"sample" =>_AM_PAGEWORKS_LANG_SAMPLE,
		"welcome" =>_AM_PAGEWORKS_LANG_WELCOME,
	);
	return $intl;
}

//
// Gets the configuration that is currently stored in the database.
// $param is the name of the field we want to retrieve
// $config is the configuration record from the database.
//			If you are querying multiple values, you can reuse the config object
//			instead of querying the database for each field.
//	$default_value The default value for the parameter
//
function pageworks_get_config_item($param, $config = null, $default_value = 7)
{
	if (!$config)
		$config = pageworks_get_config();
	$config_item = $config[$param];
	if ($config_item < 0)
		$config_item = $default_value;
	return $config_item;
}

//
// Gets the configuration that is currently stored in the database.
//
function pageworks_get_config()
{
	global $xoopsDB;
	$bci = pageworks_get_config_fields();
	$sql =	"select " . pageworks_to_string($bci) . " from " . $xoopsDB->prefix("pageworks_config");
	$result = $xoopsDB->query($sql);
	if (!$result)
	{
		$error = $xoopsDB->error();
		pageworks_show_sql_error(_AM_PAGEWORKS_ERR_QUERY_FAILED, $error, $sql);
		return null;
	}
	$values = $xoopsDB->fetchArray($result); 
	return $values;
}


//
// Gets a list of the fields in the configuration database
//
function pageworks_get_config_fields()
{
	// ZZZ We need to generate this from the database schema
	$config_info = array
	(
		// Database field			// Name used in web user interface.
		"config_id"				=>	"X ERROR X",
		"config_main_count"		=>	_AM_PAGEWORKS_LABEL_CONFIG_MAIN_COUNT,
		"config_main_where"		=>	_AM_PAGEWORKS_LABEL_CONFIG_MAIN_WHERE,
		"config_block_count"	=>	_AM_PAGEWORKS_LABEL_CONFIG_BLOCK_COUNT,
		"config_block_where"	=>	_AM_PAGEWORKS_LABEL_CONFIG_BLOCK_WHERE,
	);
	return $config_info;
}

//
// Gets the value as a string. If the value is an array, it concatenates the elements, separated by spaces.
//
function pageworks_get_value($v)
{
		if (!is_array($v))
			return $v;
		$str = "";
		$first = true;
		foreach($v as $i)
		{
			if (!$first)
				$str .= ", ";
			$str .= $i;
			$first = false;
		}
		return $str;
}

//
// Returns a list of all the fields in pages
//
function pageworks_get_pages_fields()
{
	// ZZZ We need to generate this from actual database field list.
	return array
	(
		// Field										   Prompt
		"pages_key"		=> "pages_key",
		"pages_char"		=> "pages_char",
		"pages_text"		=> "pages_text",
	);
}

// RETURNS THE RESULTS OF AN SQL STATEMENT -- ADDED April 25/05
// returns a multidimensioned array where the first index is the row of the result and the second index is the field name in that row
// borrowed from the extraction layer, but modified to use the XOOPS DB class
/*function q($query) {

	global $xoopsDB;

	//print "$query"; // debug code
	$res = $xoopsDB->query($query);
	while ($array = $xoopsDB->fetchArray($res)) {
		$result[] = $array;
	}
	return $result;
}*/
// since we can't redeclare q here, because formulize includes are (probably) being pulled in from the form menu... we'll include formulize functions once
include_once XOOPS_ROOT_PATH.'/modules/formulize/include/functions.php';



//
// RETURNS ALL THE FRAMEWORK INFO FOR A PAGE
//

function getFrameworks($page_id) {
	global $xoopsDB;
	$framework_data = q("SELECT pf_framework, pf_mainform, pf_filters, pf_sort, pf_output_name, pf_sortable, pf_sortdir FROM " . $xoopsDB->prefix("pageworks_frameworks") . " WHERE pf_page_id = '$page_id' ORDER BY pf_id"); // ordering by pf_id ensures that the first framework entered is the one returned first.
	$indexer = 0;
	foreach($framework_data as $afw) {
		$frame_name = q("SELECT frame_name FROM " . $xoopsDB->prefix("formulize_frameworks") . " WHERE frame_id='" . $afw['pf_framework'] . "'");
		$mainform = q("SELECT ff_handle FROM " . $xoopsDB->prefix("formulize_framework_forms") . " WHERE ff_frame_id='" . $afw['pf_framework'] . "' AND ff_form_id='" . $afw['pf_mainform'] . "'");
		$sort = q("SELECT fe_handle FROM " . $xoopsDB->prefix("formulize_framework_elements") . " WHERE fe_id='" . $afw['pf_sort'] . "'");
		/*print_r($frame_name); // DEBUG CODE
		print "<br>";
		print_r($mainform);
		print "<br>";
		print_r($sort);
		print "<br>";*/
		$frameworks[$indexer]['framework'] = $frame_name[0]['frame_name'];
		$frameworks[$indexer]['mainform'] = $mainform[0]['ff_handle'];
		$frameworks[$indexer]['filters'] = $afw['pf_filters'];
		$frameworks[$indexer]['sort'] = $sort[0]['fe_handle'];
		$frameworks[$indexer]['output'] = $afw['pf_output_name'];
		$frameworks[$indexer]['sortable'] = $afw['pf_sortable'];
		$frameworks[$indexer]['sortdir'] = $afw['pf_sortdir'];
		$indexer++;
	}
	return $frameworks;
}

//
// THIS FUNCTION RUNS THE EXTRACTION OF DATA
//
function start($framework, $form, $filter="", $andor="AND") {
	error_reporting(0);

	/*print $framework . "<br>"; // DEBUG CODE
	print $form . "<br>";
	print $filter . "<br>";
	print $andor . "<br>";*/
	
	$data = getData($framework, $form, $filter, $andor);
	error_reporting(E_ALL ^ E_NOTICE);
	return $data;
}



?>