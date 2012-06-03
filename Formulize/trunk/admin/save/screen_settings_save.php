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

// this file handles saving of submissions from the screen_settings page of the new admin UI

// if we aren't coming from what appears to be save.php, then return nothing
if(!isset($processedValues)) {
  return;
}


$aid = intval($_POST['aid']);
$sid = $_POST['formulize_admin_key'];
$fid = intval($_POST['formulize_admin_fid']);

$form_handler = xoops_getmodulehandler('forms', 'formulize');
$formObject = $form_handler->get($fid);
if($formObject->getVar('lockedform')) {
  return;
}
// check if the user has permission to edit the form
if(!$gperm_handler->checkRight("edit_form", $fid, $groups, $mid)) {
  return;
}


$screens = $processedValues['screens'];

$isNew = ($sid=='new');

if($screens['type'] == 'multiPage') {
  $screen_handler = xoops_getmodulehandler('multiPageScreen', 'formulize');
} else if($screens['type'] == 'listOfEntries') {
  $screen_handler = xoops_getmodulehandler('listOfEntriesScreen', 'formulize');
} else if($screens['type'] == 'form') {
  $screen_handler = xoops_getmodulehandler('formScreen', 'formulize');
}


if($isNew) {
  $screen = $screen_handler->create();
  if($screens['type'] == 'multiPage') {
    $screen->setVar('pagetitles',serialize(array(0=>'New page')));
    $screen->setVar('pages', serialize(array(0=>array())));
  } else if($screens['type'] == 'listOfEntries') {

    // set the defaults for the new screen
    
      // View
      $screen->setVar('defaultview','all');
      $screen->setVar('usecurrentviewlist',_formulize_DE_CURRENT_VIEW);
      $screen->setVar('limitviews',serialize(array(0=>'allviews')));
      $screen->setVar('useworkingmsg',1);
      $screen->setVar('usescrollbox',1);
      $screen->setVar('entriesperpage',10);
      $screen->setVar('viewentryscreen','none');
      // Headings
      $screen->setVar('useheadings',1);
      $screen->setVar('repeatheaders',5);
      $screen->setVar('usesearchcalcmsgs',1);
      $screen->setVar('usesearch',1);
      $screen->setVar('columnwidth',0);
      $screen->setVar('textwidth',35);
      $screen->setVar('usecheckboxes',0);
      $screen->setVar('useviewentrylinks',1);
      $screen->setVar('desavetext',_formulize_SAVE);
      // Buttons
      $screen->setVar('useaddupdate',_formulize_DE_ADDENTRY);
      $screen->setVar('useaddmultiple',_formulize_DE_ADD_MULTIPLE_ENTRY);
      $screen->setVar('useaddproxy',_formulize_DE_PROXYENTRY);
      $screen->setVar('useexport',_formulize_DE_EXPORT);
      $screen->setVar('useimport',_formulize_DE_IMPORT);
      $screen->setVar('usenotifications',_formulize_DE_NOTBUTTON);
      $screen->setVar('usechangecols',_formulize_DE_CHANGECOLS);
      $screen->setVar('usecalcs',_formulize_DE_CALCS);
      $screen->setVar('useadvcalcs',_formulize_DE_ADVCALCS);
      $screen->setVar('useexportcalcs',_formulize_DE_EXPORT_CALCS);
      $screen->setVar('useadvsearch',_formulize_DE_ADVSEARCH);
      $screen->setVar('useclone',_formulize_DE_CLONESEL);
      $screen->setVar('usedelete',_formulize_DE_DELETESEL);
      $screen->setVar('useselectall',_formulize_DE_SELALL);
      $screen->setVar('useclearall',_formulize_DE_CLEARALL);
      $screen->setVar('usereset',_formulize_DE_RESETVIEW);
      $screen->setVar('usesave',_formulize_DE_SAVE);
      $screen->setVar('usedeleteview',_formulize_DE_DELETE);
    
  } else if($screens['type'] == 'form') {
      $screen->setVar('displayheading', 1);
      $screen->setVar('reloadblank', 0);
      $screen->setVar('savebuttontext', _formulize_SAVE);
      $screen->setVar('alldonebuttontext', _formulize_DONE);
  } 

} else {
  

  $screen = $screen_handler->get($sid);
}

$screen->setVar('title',$screens['title']);
$screen->setVar('fid',$fid);
$originalFrid = $screen->getVar('frid');
$screen->setVar('frid',$screens['frid']);
$screen->setVar('type',$screens['type']);
$screen->setVar('useToken',$screens['useToken']);

if(!$sid = $screen_handler->insert($screen)) {
  print "Error: could not save the screen properly: ".mysql_error();
}

if($isNew) {
  // send code to client that will to be evaluated
  $url = XOOPS_URL . "/modules/formulize/admin/ui.php?page=screen&tab=settings&aid=".$aid.'&fid='.$fid.'&sid='.$sid;
  print '/* eval */ window.location = "'.$url.'";';
} elseif($originalFrid != $screens['frid']) {
  print '/* eval */ reloadWithScrollPosition();';
  
}
?>
