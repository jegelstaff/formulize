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

// this file handles saving of submissions from the screen_multipage_pages page of the new admin UI

// if we aren't coming from what appears to be save.php, then return nothing
if(!isset($processedValues)) {
  return;
}

//print_r($_POST);
//print_r($processedValues);


$aid = intval($_POST['aid']);
$sid = $_POST['formulize_admin_key'];
$op = $_POST['formulize_admin_op'];
$index = $_POST['formulize_admin_index'];

$screens = $processedValues['screens'];

$screen_handler = xoops_getmodulehandler('multiPageScreen', 'formulize');
$screen = $screen_handler->get($sid);
// CHECK IF THE FORM IS LOCKED DOWN AND SCOOT IF SO
$form_handler = xoops_getmodulehandler('forms', 'formulize');
$formObject = $form_handler->get($screen->getVar('fid'));
if($formObject->getVar('lockedform')) {
  return;
}
// check if the user has permission to edit the form
if(!$gperm_handler->checkRight("edit_form", $screen->getVar('fid'), $groups, $mid)) {
  return;
}

// get page titles

$pages = $screen->getVar('pages');

$pagetitles = $screen->getVar('pagetitles');

$conditions = $screen->getVar('conditions');


// get the new order of the elements...
$newOrder = explode("drawer-5[]=", str_replace("&", "", $_POST['pageorder']));
unset($newOrder[0]);


if(count((array) $newOrder) != count((array) $pagetitles)) {

	print "Error: number of pages being saved does not match number of pages in this screen!";

	return;

}



// newOrder will have keys corresponding to the new order, and values corresponding to the old order
// need to add in conditions handling here too
$newpages = array();
$newpagetitles = array();
$newconditions = array();
$pagesHaveBeenReordered = false;
foreach($pagetitles as $oldOrderNumber=>$values) {
	$newOrderNumber = array_search($oldOrderNumber,$newOrder);
	$newOrderNumberKey = $newOrderNumber-1;
	$newpages[$newOrderNumberKey] = $pages[$oldOrderNumber];
	$newpagetitles[$newOrderNumberKey] = $pagetitles[$oldOrderNumber];
	$newconditions[$newOrderNumberKey] = $conditions[$oldOrderNumber];
	if(($newOrderNumber - 1) != $oldOrderNumber) {
		$pagesHaveBeenReordered = true;
		$_POST['reload_multipage_pages'] = 1;
	}
}

if($pagesHaveBeenReordered) {
	$pages = $newpages;
	$pagetitles = $newpagetitles;
	$conditions = $newconditions;
	// change the deletion index so we get the page at its new position!!

	$index = array_search($index,$newOrder);

	$index--;

}


// handle "deleting" conditions...
/*foreach($conditions as $pagenum=>$datapiece) {
   if(isset($datapiece['pagecons']) AND $datapiece['pagecons'] == "none") {
        $conditions[$pagenum]['details']['elements'] = array();
        $conditions[$pagenum]['details']['ops'] = array();
        $conditions[$pagenum]['details']['terms'] = array();
    }
}*/

// alter the information based on a user add or delete
switch ($op) {
	case "addpage":
    $pages[]=array();
    $pagetitles[]='New page';
    $conditions[]=array();
		break;
	case "delpage":
		ksort($pages);

		ksort($pagetitles);

		ksort($conditions);

    array_splice($pages, $index, 1);
    array_splice($pagetitles, $index, 1);
    array_splice($conditions, $index, 1);
		break;
}

$screen->setVar('pages',serialize($pages));
$screen->setVar('pagetitles',serialize($pagetitles));
$screen->setVar('conditions',serialize($conditions));


if(!$screen_handler->insert($screen)) {
  print "Error: could not save the screen properly: ".$xoopsDB->error();
}


// reload the page if the state has changed
if($op == "addpage" OR $op=="delpage" OR $_POST['reload_multipage_pages']) {
    print "/* eval */ reloadWithScrollPosition();";
}
?>
