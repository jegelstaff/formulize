<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2005 Freeform Solutions                  ##
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
##  Author of this file: Freeform Solutions 					     ##
##  Project: Formulize                                                       ##
###############################################################################

include 'header.php';

$xoopsOption['template_main'] = 'formulize_cat.html';

require(XOOPS_ROOT_PATH."/header.php");

global $xoopsDB;

$cat_id = (isset($_GET['cat'])) ? intval($_GET['cat']) : 0 ;
//$cat_id = $_GET['cat'];
$cat_name_q = q("SELECT cat_name FROM " . $xoopsDB->prefix("formulize_menu_cats") . " WHERE cat_id='$cat_id'");
$cat_name = $cat_name_q[0]['cat_name'];
if(!$cat_name) { $cat_name = _AM_CATGENERAL; }

$formsInCat = fetchFormsInCat($cat_id);
// altered sept 8 to use ids instead of titles
//$formNames = fetchFormNames($formsInCat);
$indexer = 0;
foreach($formsInCat as $thisform) {
	$formData[$indexer]['fid'] = $thisform;
	$formData[$indexer]['title'] = fetchFormNames($thisform);
	$indexer++;
}

$xoopsTpl->assign("cat_name", $cat_name);
if($indexer==0) {
	$xoopsTpl->assign("noforms", _AM_NOFORMS_AVAIL);
} else {
	$xoopsTpl->assign("formData", $formData);
}


require(XOOPS_ROOT_PATH."/footer.php");

?>