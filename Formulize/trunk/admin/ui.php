<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2010 Freeform Solutions                  ##
###############################################################################
##  This program is free software; you can redistribute it and/or modify     ##
##  it under the terms of the GNU General Public License as published by     ##
##  the Free Software Foundation; either version 2 of the License, or        ##
##  (at your option) any later version.                                      ##
##                                                                           ##
##  You may not change or alter any portion of this comment or credits       ##
##  of supporting developers from this source code or any supporting         ##
##  source code which is considered copyrighted (c) material of the          ##
##  original comment or credit authors.                                      ##
##                                                                           ##
##  This program is distributed in the hope that it will be useful,          ##
##  but WITHOUT ANY WARRANTY; without even the implied warranty of           ##
##  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            ##
##  GNU General Public License for more details.                             ##
##                                                                           ##
##  You should have received a copy of the GNU General Public License        ##
##  along with this program; if not, write to the Free Software              ##
##  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA ##
###############################################################################
##  Author of this file: Freeform Solutions                                  ##
##  URL: http://www.freeformsolutions.ca/formulize                           ##
##  Project: Formulize                                                       ##
###############################################################################

// initialize the ImpressCMS admin page template
include_once("admin_header.php");
xoops_cp_header();

// include necessary Formulize files/functions
include_once XOOPS_ROOT_PATH . "/modules/formulize/include/functions.php";

// setup a smarty object that we can use for templating our own pages
require_once XOOPS_ROOT_PATH.'/class/template.php';
require_once XOOPS_ROOT_PATH.'/class/theme.php';
require_once XOOPS_ROOT_PATH.'/class/theme_blocks.php';
$xoopsThemeFactory =& new xos_opal_ThemeFactory();
$xoTheme =& $xoopsThemeFactory->createInstance();
$xoopsTpl =& $xoTheme->template;

// handle any operations requested as part of this page load
if(isset($_GET['op'])) {
  include_once "op.php";
}


// create the contents that we want to display for the currently selected page
// the included php files create the values for $adminPage that are used for this page
$adminPage = array();
switch($_GET['page']) {
  case "application":
    include "application.php"; 
    break;
  case "form":
    include "form.php";
    break;
	case "screen":
		include "screen.php";
		break;
	case "relationship":
		include "relationship.php";
		break;
	case "element":
		include "element.php";
		break;
	default:
	case "home":
		include "home.php";
		break;

}
$adminPage['logo'] = "/modules/formulize/images/formulize-logo.png";

// assign the default selected tab, if any:
if(isset($_GET['tab']) AND (!isset($_POST['tabs_selected']) OR $_POST['tabs_selected'] === "")) {
  foreach($adminPage['tabs'] as $selected=>$tabData) {
    if(strtolower($tabData['name']) == $_GET['tab']) {
      $adminPage['tabselected'] = $selected-1;
      break;
    }
  }
} elseif($_POST['tabs_selected'] !== "") {
	$adminPage['tabselected']  = intval($_POST['tabs_selected']);
}

// assign the contents to the template and display
$xoopsTpl->assign('adminPage', $adminPage);
$xoopsTpl->assign('breadcrumbtrail', $breadcrumbtrail);
$xoopsTpl->assign('scrollx', intval($_POST['scrollx']));
$accordion_active = isset($_POST['accordion_active']) ? intval($_POST['accordion_active']) : "false";
$xoopsTpl->assign('accordion_active', $accordion_active);
$xoopsTpl->display("db:admin/ui.html");

include 'footer.php';
xoops_cp_footer();


