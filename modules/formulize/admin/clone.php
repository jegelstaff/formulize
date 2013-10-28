<?php
###############################################################################
##               Formulize - ad hoc form creation and reporting              ##
##                    Copyright (c) 2009 Freeform Solutions                  ##
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

// 2. find all linked selectboxes
// 3. find all cached metadata for recently cloned forms that those selectboxes are linked to
// 4. make UI for selecting which cloned forms, if any, links should be switched to point to
// 5. submit the form and then all the appropriate linkages are updated/maintained

include("admin_header.php");
include_once '../../../include/cp_header.php';
if ( file_exists("../language/".$xoopsConfig['language']."/admin.php") ) {
	include "../language/".$xoopsConfig['language']."/admin.php";
} else {
	include "../language/english/admin.php";
}

include_once XOOPS_ROOT_PATH . "/modules/formulize/include/functions.php";

$fid = intval($_GET['title']);

$form_handler = xoops_getmodulehandler('forms', 'formulize');
$element_handler = xoops_getmodulehandler('elements', 'formulize');
$formObject = $form_handler->get($fid);
if(!is_object($formObject)) {
  print "error: could not get information about this form";
} else {

  // garbage collection on old cloned form data
	formulize_scandirAndClean(XOOPS_ROOT_PATH . "/cache/", "formulize_clonedFormMaps_", 1209600); // final param is the number of seconds in the past to allow files to stick around for.  This number is two weeks.  Files older than two weeks will be deleted.

  // look for linked selectboxes in this form and find any data about cloned copies of the target forms
  $newLinkOptions = array();
  $selectBoxKeys = array_keys($formObject->getVar('elementTypes'), "select");
  foreach($selectBoxKeys as $elementId) {
    $elementObject = $element_handler->get($elementId);
    $ele_value = $elementObject->getVar('ele_value');
    if(!is_array($ele_value[2]) AND strstr($ele_value[2], "#*=:*")) {
      // this is a linked selectbox, so let's check what it's pointing at and then check for recently cloned copies of that form
      $boxproperties = explode("#*=:*", $ele_value[2]); // [0] will be the fid of the form we're after, [1] is the handle of that element
      $clonedMetaDataFiles = formulize_scandirAndClean(XOOPS_ROOT_PATH . "/cache/", "formulize_clonedFormMaps_".$boxproperties[0], 1209600);
      if(count($clonedMetaDataFiles)>0) {
        foreach($clonedMetaDataFiles as $thisFile) {
          // get the name of the new form so we can offer an option to relink to its copy of the element instead
          $clonedFormInfo = explode("_", $thisFile); // form id will be 4
          $clonedFormObject = $form_handler->get($clonedFormInfo[4]);
          if(is_object($clonedFormObject)) {
            $newLinkOptions[$elementId][$clonedFormInfo[4]] = $clonedFormObject->getVar('title');
          }
        }
      }
    }
  }
  
  if(count($newLinkOptions)>0) {
  
    // print out our options if any
    
    xoops_cp_header();
    
    print "<h1>"._AM_FORMULIZE_CLONING_TITLE."</h1>\n";
    print "<h2>"._AM_FORMULIZE_CLONING_FOUND_ELEMENTS."</h2>";
  
    print "<form name=\"relinkoptions\" action=\"".XOOPS_URL."/modules/formulize/admin/formindex.php?title=".$fid."&op=clonedata\" method=\"post\">\n";
  
    foreach($newLinkOptions as $elementId=>$targetData) {
      $elementObject = $element_handler->get($elementId);
      print "<p><b>".printSmart(trans($elementObject->getVar('ele_caption')), 75)."</b> "._AM_FORMULIZE_CLONING_CANBELINKEDTO."</p>\n";
      print "<blockquote>\n";
      foreach($targetData as $sourceForm=>$formName) {
        print "<input type=\"radio\" name=\"element".$elementId."\" value=\"".$sourceForm."\"></input> $formName<br>\n";
      }
      print "<input type=\"radio\" name=\"element".$elementId."\" value=\"0\" checked='checked'></input> "._AM_FORMULIZE_CLONING_NOCHANGE."\n";
      print "</blockquote>\n";
    }
  
    print "<input type=submit name=relink value=\""._FORM_CLONEDATA_TEXT."\"></input>\n";
    print "</form>\n";
    
  
    include 'footer.php';
    xoops_cp_footer();
  
  } else {
    // no link options found, so just redirect to the clonedata URL
    header("Location: ".XOOPS_URL."/modules/formulize/admin/formindex.php?title=".$fid."&op=clonedata");
  }

} // end of if we could get the form object or not



