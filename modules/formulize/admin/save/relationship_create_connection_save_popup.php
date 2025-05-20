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

$makeSubformInterface = (isset($_POST['relationships-create-subform']) AND $_POST['relationships-create-subform']) ? true : false;
$makeSubformScreenOnF2 = (isset($_POST['makeSubformScreenOnF2']) AND intval($_POST['makeSubformScreenOnF2'])) ? true : false;
$del = isset($_POST['relationships-delete-0']) ? intval($_POST['relationships-delete-0']) : 0; // normally this would have a link id after the last hyphen, but when creating there is no link id yet
$con = isset($_POST['relationships-conditional-0']) ? intval($_POST['relationships-conditional-0']) : 0;
$book = isset($_POST['relationships-bookkeeping-0']) ? intval($_POST['relationships-bookkeeping-0']) : 0;
$rel = isset($_POST['rel']) ? intval($_POST['rel']) : 0;
$f1 = isset($_POST['f1']) ? intval($_POST['f1']) : 0;
$f2 = isset($_POST['f2']) ? intval($_POST['f2']) : 0;
if($_POST['pairSelection'] == 'pair-manual') {
	$k1 = $_POST['form1ElementId'];
	$k2 = $_POST['form2ElementId'];
	if((is_numeric($k1) AND is_numeric($k2)) OR $k1 == 'new-common-parallel' OR $k1 == 'new-common-textbox' OR $k2 == 'new-common-parallel' OR $k2 == 'new-common-textbox') {
		$cv = 1;
	} else {
		$cv = 0;
	}
} else {
	// explode metadata into array
	// 0 - element id of element in form 1, or 'new'
	// 1 - element id of element in form 2, or 'new'
	// 2 - flag indicating what type of new element situation, or 'regular', or 'common'
	// see addPair function for more details
	$keysData = explode("+", $_POST['pairSelection']);
	$k1 = $keysData[0] == 'new' ? $keysData[2] : $keysData[0];
	$k2 = $keysData[1] == 'new' ? $keysData[2] : $keysData[1];
	if($keysData[2] == 'common' OR $keysData[2] == 'new-common-parallel' OR $keysData[2] == 'new-common-textbox') {
		$cv = 1;
	} else {
		$cv = 0;
	}
}

// only max of one key will be a flag for new element, we can't make two new elements at once!
if(is_numeric($k1) AND is_numeric($k2)) {
	$k1 = intval($k1);
	$k2 = intval($k2);
} elseif(!is_numeric($k1)) {
	$k2 = intval($k2);
	$k1 = makeNewConnectionElement($k1, $f1, $k2);
} else {
	$k1 = intval($k1);
	$k2 = makeNewConnectionElement($k2, $f2, $k1);
}

if($f1 AND $f2 AND $k1 AND $k2) {
	if(!linkExistsInPrimaryRelationship($cv, $rel, $k1, $k2)) {
		$result = insertLinkIntoPrimaryRelationship($cv, $rel, $f1, $f2, $k1, $k2, $del, $con, $book);
		if($result !== true) { // result will be text error message on failure
			print $result;
		}
	}
	if($makeSubformInterface) {
		makeSubformInterface($f1, $f2, $k2);
	}
	if($makeSubformScreenOnF2) {
		print findOrMakeSubformScreen($k2, $f1);
	}
}


