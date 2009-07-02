<?php
// ------------------------------------------------------------------------- 
//	pageworks
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
require_once(XOOPS_ROOT_PATH ."/modules/pageworks/include/pageworks_includes.php");
if ( file_exists("../language/".$xoopsConfig['language']."/modinfo.php") ) {
    include_once "../language/".$xoopsConfig['language']."/modinfo.php";
} else {
	include_once "../language/english/modinfo.php";
}
require_once(XOOPS_ROOT_PATH ."/modules/pageworks/admin/menu.php");

$myts = new MyTextSanitizer();

// Get all HTTP post or get parameters, prefixed by "param_"
import_request_variables("gp", "param_");


// ******* jpc
//$result = $xoopsDB->query("SELECT * FROM " . $xoopsDB->prefix("formulize_frameworks") . ";");
//$result = $xoopsDB->query("SELECT * FROM " . $xoopsDB->prefix("formulize_framework_forms") . " WHERE ff_frame_id = 1;");
//$result = $xoopsDB->query("SELECT * FROM " . $xoopsDB->prefix("formulize_framework_elements") . " WHERE fe_frame_id = 1 AND fe_form_id = 1;");
/*$result = $xoopsDB->query("SELECT * FROM " . $xoopsDB->prefix("pageworks_frameworks") . ";");
if($result)
{
    while($resultArray = $xoopsDB->fetchArray($result)) 
    {
		var_dump($resultArray);
    }
}*/


require_once(XOOPS_ROOT_PATH ."/modules/pageworks/admin/pages.php");
require_once(XOOPS_ROOT_PATH ."/modules/pageworks/admin/frameworks.php");
// *******


//
// Displays the page to select which admin page you want.
//
function pageworks_menu()
{
	global $adminmenu;
	
  	pageworks_admin_hmenu();

    

}

//
// Displays the configuration page for this module
//
function pageworks_config()
{
	global $adminmenu;
	xoops_cp_header();
    $p_title = _AM_PAGEWORKS_CONFIGURE;
    print "<h4 style='text-align:left;'>$p_title</h4>";
  	pageworks_admin_hmenu();
    xoops_cp_footer();
    exit();
}

function patch31() {
  if(!$_POST['patch31']) {
   		print "<form action=\"index.php?op=patch31\" method=post>";
		print "<input type = submit name=patch31 value=\"Apply Database Patch for Pageworks 3.1\">";
		print "</form>";
  } else {
	  global $xoopsDB;
        $checksql = "SELECT * FROM " . $xoopsDB->prefix("pageworks_pages") . " LIMIT 0,1";
        $checkres = $xoopsDB->query($sql);
        $checkarray = $xoopsDB->fetchArray($checkres);
        if(!isset($checkarray['page_html_from_db'])) {
      	  $sql = "ALTER TABLE " . $xoopsDB->prefix("pageworks_pages") . " ADD page_html_from_db smallint(5) default '0'";
      	  if(!$res = $xoopsDB->query($sql)) {
      		  print "<p>'HTML from db' option already exists.  Result: OK</p>";
      	  }
        }
	  print "<p>Done patching DB.  Result: OK</p>";
  }
}


if (!isset($param_op))
	 $param_op = 'menu';

xoops_cp_header();

switch ($param_op) 
{
	case "patch31":
		patch31();
		break;
	case "logs":
		pageworks_menu();
		include "logs.php";
		break;
	case "menu":
		pageworks_menu();
		buildPagesSummary();
		break;
	case "config":
		pageworks_config();
		break;
	case "config_post":
		pageworks_config_post();
		break;
	case "edit":
		pageworks_edit();
		break;

	case "addpage":
	    	if(@$_REQUEST["savebutton"] == "Save")
      	{
			//echo "post??";
            	if($page_id = insertPage($_REQUEST["page_name"], $_REQUEST["page_title"], $_REQUEST["page_template"], $_REQUEST["page_searchable"], $_REQUEST["page_html_from_db"])) {
				$_REQUEST = array();            
	            }
			$param_op = "editpage";
			$_REQUEST = $xoopsDB->fetchArray(selectPage($page_id));
			$_REQUEST["page_id"] = $page_id;
		}
		buildPageForm("nostrip"); // never strip when redrawing a page saved for the first time
//		if(get_magic_quotes_gpc()) {
//			buildPageForm();
//		} else {
//			buildPageForm("nostrip");
//		}
		break;
	case "deletepage":
		$sql = "SELECT pf_id FROM " . $xoopsDB->prefix("pageworks_frameworks") . " WHERE pf_page_id='" . $_REQUEST['page_id'] . "'";
		$res = $xoopsDB->query($sql);
		while($array = $xoopsDB->fetchArray($res)) {
			deleteFramework($array['pf_id']);
		}
		deletePage($_REQUEST["page_id"]);
		pageworks_menu();
		buildPagesSummary();
		break;
	case "editpage":
    	if(@$_REQUEST["savebutton"] == "Save")
        {
            updatePage($_REQUEST["page_name"], $_REQUEST["page_title"],
            	$_REQUEST["page_template"], $_REQUEST["page_id"], $_REQUEST["page_searchable"], $_REQUEST["page_html_from_db"]);
		if(get_magic_quotes_gpc()) {
			buildPageForm();
		} else {
			buildPageForm("nostrip");
		}
		}
        else
        {            
        	$page_id = $_REQUEST["page_id"];
            $_REQUEST = $xoopsDB->fetchArray(selectPage($_REQUEST["page_id"]));
            $_REQUEST["page_id"] = $page_id;
		buildPageForm("nostrip");// never strip when loading first time from the db
//		if(get_magic_quotes_gpc()) {
//			buildPageForm("");
//		} else {
//			buildPageForm("nostrip");
//		}
		}            

		break;

	case "addframework":
    	if(@$_REQUEST["savebutton"] == "Save")
        {
            if($framework_id = insertFramework($_REQUEST["page_id"], $_REQUEST["pf_framework"], 
            	$_REQUEST["pf_mainform"], $_REQUEST["pf_filters"],
            	$_REQUEST["pf_sort"], $_REQUEST["pf_output_name"], $_REQUEST["pf_search_title"], $_REQUEST['pf_sortable'], $_REQUEST["pf_sortdir"]))
            {
				//redirect_header("index.php?page_id=" . $_REQUEST["page_id"] .		"&op=editpage", 0);
				//$_REQUEST = array();            
            }
            else
            {
				echo "Error: unable to save the framework<br>";
            }
		$param_op = "editframework";
		$page_id = $_REQUEST["page_id"];
            $_REQUEST = $xoopsDB->fetchArray(selectFramework($framework_id));
            $_REQUEST["page_id"] = $page_id;
            $_REQUEST["pf_page_id"] = $page_id;
            $_REQUEST["framework_id"] = $framework_id;

		}            
        	buildFrameworkForm();
		break;
	case "deleteframework":
		deleteFramework($_REQUEST["framework_id"]);
		$param_op="editpage";
		$page_id = $_REQUEST["page_id"];
            $_REQUEST = $xoopsDB->fetchArray(selectPage($_REQUEST["page_id"]));
            $_REQUEST["page_id"] = $page_id;
		if(get_magic_quotes_gpc()) {
			buildPageForm();
		} else {
			buildPageForm("nostrip");
		}
		break;
	case "editframework":
    	if(@$_REQUEST["savebutton"] == "Save")
        {
            if(updateFramework($_REQUEST["page_id"], $_REQUEST["pf_framework"], 
            	$_REQUEST["pf_mainform"], $_REQUEST["pf_filters"],
            	$_REQUEST["pf_sort"], $_REQUEST["pf_output_name"], $_REQUEST["pf_search_title"], $_REQUEST["framework_id"], $_REQUEST['pf_sortable'], $_REQUEST["pf_sortdir"]))
			{
				//redirect_header("index.php?page_id=" . $_REQUEST["page_id"] .  "&op=editpage", 0);
            }
            else
            {
				echo "Error: unable to update the framework<br>";
            }
        }
                  
        	$page_id = $_REQUEST["page_id"];
        	$framework_id = $_REQUEST["framework_id"];
          
            $_REQUEST = $xoopsDB->fetchArray(selectFramework($_REQUEST["framework_id"]));
          
            
            $_REQUEST["page_id"] = $page_id;
            $_REQUEST["pf_page_id"] = $page_id;
            $_REQUEST["framework_id"] = $framework_id;
            $_REQUEST["pf_framework"] = $_REQUEST["pf_framework"];
		
            //var_dump($_REQUEST);
		buildFrameworkForm();
		break;

	case "perm":
		pageworks_menu();
		include_once("perm.php");
		permform();
		break;

	default:
		print "<h1>Unknown method requested '$param_op' in admin/index.php</h1>";
}

print "<p>version 3.1 Final</p>";

    xoops_cp_footer();
    exit();

?>