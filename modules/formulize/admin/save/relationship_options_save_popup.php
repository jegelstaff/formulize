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
##  URL: http://www.formulize.org                           ##
##  Project: Formulize                                                       ##
###############################################################################

// if we aren't coming from what appears to be save.php, then return nothing
if(!isset($processedValues)) {
	return;
}

global $xoopsDB;
$lid = $_POST['lid'];
$deleteLid = $_POST['delete-link-id'];
if($deleteLid = intval($deleteLid)) {
	if(deleteLinkFromDatabase($deleteLid)) {
		print "/* eval */ reloadWithScrollPosition();";
	} else {
		print "Error: could not delete link number $lid from the database.";
	}
} elseif($lid = intval($lid)) {
	$del = isset($_POST["relationships-delete-$lid"]) ? intval($_POST["relationships-delete-$lid"]) : 0;
	$con = isset($_POST["relationships-conditional-$lid"]) ? intval($_POST["relationships-conditional-$lid"]) : 0;
	$book = isset($_POST["relationships-bookkeeping-$lid"]) ? intval($_POST["relationships-bookkeeping-$lid"]) : 0;
	$sql = "UPDATE ".$xoopsDB->prefix('formulize_framework_links')." SET fl_unified_delete = $del, fl_one2one_conditional = $con, fl_one2one_bookkeeping = $book WHERE fl_id = $lid";
	if(!$res = $xoopsDB->query($sql)) {
		print "Error: could not update link options with this SQL: $sql\n\n".$xoopsDB->error();
	}
	if(isset($_POST["relationships-create-subform"]) AND intval($_POST["relationships-create-subform"])) {
		$link = new formulizeFrameworkLink($lid);
		makeSubformInterface($link->getVar('form1'), $link->getVar('form2'), $link->getVar('key2'));
		print "/* eval */ jQuery('label[for^=relationships-create-subform]').remove(); $('#dialog-relationship-options').fadeTo(1, 1); window.document.getElementById('relationship-options-popupsavewarning').style.display = 'none';";
	}
}
