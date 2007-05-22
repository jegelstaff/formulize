<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2007 Freeform Solutions                  ##
###############################################################################
##                    XOOPS - PHP Content Management System                  ##
##                       Copyright (c) 2000 XOOPS.org                        ##
##                          <http://www.xoops.org/>                          ##
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
##  Project: Formulize                                                       ##
###############################################################################

// THIS FILE HANDLES EDITS TO SCREENS 
// SCREENS ARE PARTICULAR WAYS OF DISPLAYING FORMS OR THEIR DATA

include("admin_header.php");
include_once '../../../include/cp_header.php';
include_once XOOPS_ROOT_PATH . "/modules/formulize/include/functions.php";
if ( file_exists("../language/".$xoopsConfig['language']."/main.php") ) {
	include "../language/".$xoopsConfig['language']."/main.php";
} else {
	include "../language/english/main.php";
}


$type = $_POST['type'] ? htmlspecialchars(strip_tags($_POST['type'])) : htmlspecialchars(strip_tags($_GET['type'])); // figure out the type
$sid = $_POST['sid'] ? intval($_POST['sid']) : intval($_GET['sid']); // get the screen id 
$fid = $_POST['fid'] ? intval($_POST['fid']) : intval($_GET['fid']); // get the form id

$screen_handler =& xoops_getmodulehandler($type.'Screen', 'formulize');

if(is_numeric(intval($sid)) AND $sid>0) {
	$screen = $screen_handler->get($sid);
} else {
	$screen = $screen_handler->create();
}


xoops_cp_header();

if(isset($_POST['oneditscreen'])) {
	// save the contents of the form 
	// redirect to mailindex.php if we're saving because of the save button itself, not just a page refresh
	$screen_handler->saveForm($screen, $fid);
	
	if(isset($_POST['savescreen'])) {
		redirect_header("mailindex.php?title=$fid", 2, _AM_FORMULIZE_SCREEN_SAVED);
	}
} 

// display the form with the contents of a particular screen if one has been requested
$form = $screen_handler->editForm($screen, $fid);
$form->addElement(new xoopsFormButton('', 'savescreen', _AM_FORMULIZE_SCREEN_SAVE, 'submit'));
print $form->render();

xoops_cp_footer();

?>