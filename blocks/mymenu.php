<?php
// Copyright (c) 2004 Freeform Solutions and Marcel Widmer (for the original
// mymenu module).
// ------------------------------------------------------------------------- //
//                XOOPS - PHP Content Management System                      //
//                       <http://www.xoops.org/>                             //
// ------------------------------------------------------------------------- //
//  This program is free software; you can redistribute it and/or modify     //
//  it under the terms of the GNU General Public License as published by     //
//  the Free Software Foundation; either version 2 of the License, or        //
//  (at your option) any later version.                                      //
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

function block_formulizeMENU_show() {
        global $xoopsDB, $xoopsUser, $xoopsModule, $myts;
		    $myts =& MyTextSanitizer::getInstance();

        $block = array();
        $groups = array();
        $block['title'] = ""; //_MB_formulizeMENU_TITLE;
        $block['content'] = "<table cellspacing='0' border='0'><tr><td id=\"mainmenu\">";

	// MODIFIED April 25/05 to handle menu categories

	include_once XOOPS_ROOT_PATH.'/modules/formulize/include/functions.php';

	$cats = fetchCats();

	// GENERATE THE ID_FORM
	$id_form = ((isset( $_GET['fid'])) AND is_numeric( $_GET['fid'])) ? intval( $_GET['fid']) : "" ;
  $id_form = ((isset($_POST['fid'])) AND is_numeric($_POST['fid'])) ? intval($_POST['fid']) : $id_form ;

/*	if(!isset($_POST['fid'])){
		$id_form = isset ($_GET['fid']) ? $_GET['fid'] : '';
	} else {
		$id_form = $_POST['fid'];
	}
	if(!isset($_POST['title'])){
		$title = isset ($_GET['title']) ? $_GET['title'] : '';
	} else {
		$title = $_POST['title'];
	}
	if($title) {
		$sql=q("SELECT id_form FROM ".$xoopsDB->prefix("formulize_id")." WHERE desc_form=\"$title\"");
		$id_form = $sql[0]['id_form'];
	} else {
		$id_form = 0;
	}
*/
	$allowedForms = allowedForms();

	$topwritten = 0;

	$force_open = 0;
	$allowedCats = allowedCats($cats, $allowedForms);
	if(count($allowedCats)<2 AND (!isset($_GET['cat']) AND !isset($_GET['title']))) { $force_open = 1; } // if only one category, then force it to open up
	foreach($cats as $thisid=>$thiscat) {
		$temp = drawMenu($thisid, $thiscat, $allowedForms, $id_form, $topwritten, $force_open);
		if($temp != "") {
			$block['content'] .= $temp;
			$topwritten = 1;
		}
	}
	if(count($allowedCats) == 1) { $force_open = 0; } // if a previous category was already displayed, do not open up general forms too (but open general forms if there are no previous categories)
	$block['content'] .= drawMenu(0, _AM_CATGENERAL, $allowedForms, $id_form, $topwritten, $force_open);

	  $block['content'] .= "</td></tr></table>";

        return $block;
}
?>