<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2004 Freeform Solutions                  ##
##                Portions copyright (c) 2003 NS Tai (aka tuff)              ##
##                       <http://www.brandycoke.com/>                        ##
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
##  Author of this file: Freeform Solutions and NS Tai (aka tuff) and others ##
##  URL: http://www.brandycoke.com/                                          ##
##  Project: Formulize                                                       ##
###############################################################################

//include 'header.php'; //redundant as all the code that this calls is now called from within each function (since the functions operate on a standalone basis)

include_once XOOPS_ROOT_PATH.'/class/mail/phpmailer/class.phpmailer.php';

global $xoopsDB, $myts, $xoopsUser, $xoopsModule, $xoopsTpl, $xoopsConfig;

include_once XOOPS_ROOT_PATH . "/modules/formulize/include/functions.php";

// altered sept 8 to use fid instead of title

$fid = ((isset( $_GET['fid'])) AND is_numeric( $_GET['fid'])) ? intval( $_GET['fid']) : "" ;
$fid = ((isset($_POST['fid'])) AND is_numeric($_POST['fid'])) ? intval($_POST['fid']) : $fid ;

$frid = ((isset( $_GET['frid'])) AND is_numeric( $_GET['frid'])) ? intval( $_GET['frid']) : "" ;
$frid = ((isset($_POST['frid'])) AND is_numeric($_POST['frid'])) ? intval($_POST['frid']) : $frid ;

// gather $_GET['sid'] (screen)
if(isset($formulize_screen_id) AND is_numeric($formulize_screen_id)) {
  $sid = $formulize_screen_id;
} elseif(isset($_GET['sid']) AND is_numeric($_GET['sid'])) {
	$sid = $_GET['sid'];
} else {
	$sid="";
}
if($sid) {
	$screen_handler =& xoops_getmodulehandler('screen', 'formulize');
	$thisscreen1 = $screen_handler->get($sid); // first get basic screen object to determine type
	$fid = is_object($thisscreen1) ? $thisscreen1->getVar('fid') : 0;
}

// set the flag to force derived value updates, if it is in the URL
if(isset($_GET['forceDerivedValueUpdate'])) {
	$GLOBALS['formulize_forceDerivedValueUpdate'] = true;
}


// query modified to include singleentry - July 28, 2005 -- part of switch to new intnerface
$sql=sprintf("SELECT admin,groupe,email,expe,singleentry,desc_form FROM ".$xoopsDB->prefix("formulize_id")." WHERE id_form='$fid'");
$res = $xoopsDB->query ( $sql ) or die('SQL Error !<br />'.$sql.'<br />'.mysql_error());
//global $nb_fichier;
 
if ( $res ) {
  while ( $row = $xoopsDB->fetchArray ( $res ) ) {
    $id_form = $fid; // this is important because we use $id_form in the readelements.php file to trigger the updating of the derived values.  This value will be preserved regardless of other logic in readelements.php, so we always will know what the original form id called was.
    $admin = $row['admin'];
    $groupe = $row['groupe'];
    $email = $row['email'];
    $expe = $row['expe'];
    $singleentry = $row['singleentry'];
    $desc_form = $row['desc_form'];
  }
}

$myts =& MyTextSanitizer::getInstance();
$title = $myts->displayTarea($desc_form);
if(!isset($formulize_screen_id)) {
  $xoopsTpl->assign('xoops_pagetitle', $title);
} 

// new logic to handle invoking new interface
// 1. determine if the form is a single or multi
// 1.5 if multi->displayEntries, if single...
// 2. if single, determine if the user has group or global scope
// 2.5 if yes->displayEntries, if no...
// 3 displayForm

// get the global or group permission
$groups = $xoopsUser ? $xoopsUser->getGroups() : array(0=>XOOPS_GROUP_ANONYMOUS);
$uid = $xoopsUser ? $xoopsUser->getVar('uid') : 0;
$mid = getFormulizeModId();
$gperm_handler = &xoops_gethandler('groupperm');
$view_globalscope = $gperm_handler->checkRight("view_globalscope", $id_form, $groups, $mid);
$view_groupscope = $gperm_handler->checkRight("view_groupscope", $id_form, $groups, $mid);

$config_handler =& xoops_gethandler('config');
$formulizeConfig =& $config_handler->getConfigsByCat(0, $mid);

if($fid AND !$view_form = $gperm_handler->checkRight("view_form", $fid, $groups, $mid)) {
	redirect_header(XOOPS_URL . "/user.php?xoops_redirect=".getCurrentURL(), 3, _formulize_NO_PERMISSION);
}

// IF A SCREEN IS REQUESTED, GET DETAILS FOR THAT SCREEN AND CALL THE NECESSARY DISPLAY FUNCTION
$rendered = false;
$screen = false;
if($sid) {
	if(is_object($thisscreen1)) {
		unset($screen_handler); // reset handler to that type of screen
		$screen_handler =& xoops_getmodulehandler($thisscreen1->getVar('type').'Screen', 'formulize');
		$screen = $screen_handler->get($sid); // get the full screen object
    if(is_object($xoopsTpl)) {
      $xoopsTpl->assign('xoops_pagetitle', $screen->getVar('title'));
    }
		$frid = $screen->getVar('frid'); // set these here just in case it's needed in readelements.php
		$id_form = $screen->getVar('fid'); // set these here just in case it's needed in readelements.php
	}
} 

// one time only, the code to read the displayElement elements will be executed
include_once XOOPS_ROOT_PATH . "/modules/formulize/include/readelements.php";
// note...$fid is not reliable after this file is included!!



// check for $xoopsUser added by skenow.  Forces anons to only see the form itself and never the list of entries view.
// Really, we should build in better permission/configuration control so that more precise 
// control over anon behaviour is possible

// gather $_GET['ve'] (viewentry), or prefer a global value that has been set
if(isset($formulize_entry_id) AND is_numeric($formulize_entry_id)) {
  $entry = $formulize_entry_id;  
} elseif(isset($_GET['ve']) AND is_numeric($_GET['ve'])) {
	$entry = $_GET['ve'];
} else {
	$entry = "";
}

if($screen) {
	$formulize_screen_loadview = (!isset($formulize_screen_loadview) OR !is_numeric($formulize_screen_loadview)) ? intval($_GET['loadview']) : $formulize_screen_loadview;
  $loadThisView = (isset($formulize_screen_loadview) AND is_numeric($formulize_screen_loadview)) ? $formulize_screen_loadview : "";
	if(!$loadThisView) { $loadThisView = ""; } // a 0 could possibly screw things up, so change to ""
  $screen_handler->render($screen, $entry, $loadThisView);
  $rendered = true;
}

// IF NO SCREEN IS REQUESTED (or none rendered successfully, ie: a bad screen id was passed), THEN USE THE DEFAULT DISPLAY LOGIC TO DETERMINE WHAT TO SHOW THE USER
if(!$rendered) {
      if(isset($frid) AND is_numeric($frid) AND isset($id_form) AND is_numeric($id_form)) {
      	if(((!$singleentry AND $xoopsUser) OR $view_globalscope OR ($view_groupscope AND $singleentry != "group")) AND !$entry) { // if it's multientry and there's a xoopsUser, or the user has globalscope, or the user has groupscope and it's not a one-per-group form, and after all that, no entry has been requested, then show the list (note that anonymous users default to the form view...to provide them lists of their own entries....well you can't, but groupscope and globalscope will show them all entries by anons or by everyone)
      		include_once XOOPS_ROOT_PATH . "/modules/formulize/include/entriesdisplay.php";
      		displayEntries($frid, $id_form); // if it's a multi, or if a single and they have group or global scope
      	} else { // otherwise, show the form
      		include_once XOOPS_ROOT_PATH . "/modules/formulize/include/formdisplay.php";
      		displayForm($frid, $entry, $id_form, "", "{NOBUTTON}"); // if it's a single and they don't have group or global scope, OR if an entry was specified in particular
      	}
      } elseif(isset($id_form) AND is_numeric($id_form)) {
      	if(((!$singleentry AND $xoopsUser) OR $view_globalscope OR ($view_groupscope AND $singleentry != "group")) AND !$entry) { // if it's multientry and there's a xoopsUser, or the user has globalscope, or the user has groupscope and it's not a one-per-group form, and after all that, no entry has been requested, then show the list (note that anonymous users default to the form view...to provide them lists of their own entries....well you can't, but groupscope and globalscope will show them all entries by anons or by everyone)
      		include_once XOOPS_ROOT_PATH . "/modules/formulize/include/entriesdisplay.php";
      		displayEntries($id_form); // if it's a multi, or if a single and they have group or global scope
      	} else { // otherwise, show the form
      		include_once XOOPS_ROOT_PATH . "/modules/formulize/include/formdisplay.php";
      		displayForm($id_form, $entry, "", "", "{NOBUTTON}"); // if it's a single and they don't have group or global scope, OR if an entry was specified in particular
      	}
      } else { // if no form is specified, then show the General Forms category
      	header("Location: " . XOOPS_URL . "/modules/formulize/cat.php");
      }
}

?>
