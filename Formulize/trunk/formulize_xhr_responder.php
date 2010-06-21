<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2006 Freeform Solutions                  ##
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
##  Author of this file: Freeform Solutions                                  ##
##  Project: Formulize                                                       ##
###############################################################################

// this file listens for incoming formulize_xhr messages, and responds accordingly

require_once "../../mainfile.php"; // initialize the xoops stack so we have access to the user object, etc if necessary
ob_end_clean(); // stop all buffering of output (ie: related to the error logging, and/or xLangauge?)

// check that the user who sent this request is the same user we have a session for now, if not, bail
$sentUid = $_GET['uid'];
if(($xoopsUser AND $sentUid != $xoopsUser->getVar('uid')) OR (!$xoopsUser AND $sentUid !== 0)) {
  exit(); 
}

// unpack the op 
$op = $_GET['op'];

// validate op
if($op != "check_for_unique_value" AND $op != "get_element_option_list") {
  exit();
}

// unpack params based on op, and do whatever we're supposed to do
switch($op) {
  case 'check_for_unique_value':
    $value = $_GET['param1'];
    $element = $_GET['param2'];
    $entry = $_GET['param3'];
    
    $element_handler = xoops_getmodulehandler('elements', 'formulize');
    $elementObject = $element_handler->get($element);
    if(is_object($elementObject)) {
      include_once XOOPS_ROOT_PATH . "/modules/formulize/class/data.php";
      $data_handler = new formulizeDataHandler($elementObject->getVar('id_form'));
      $entry_id = $data_handler->findFirstEntryWithValue($element, $value);
      if(is_numeric($entry_id) AND $entry_id != $entry) {
        print 'valuefound';
      } else {
        print 'valuenotfound';
      }
    } else {
      print 'invalidelement';
    }
    break;
  case 'get_element_option_list':
    include_once XOOPS_ROOT_PATH . "/modules/formulize/include/functions.php";
  	$elementsq = q("SELECT ele_caption, ele_id FROM " . $xoopsDB->prefix("formulize") . " WHERE id_form=" . intval($_GET['fid']) . " AND ele_type != \"ib\" AND ele_type != \"subform\" ORDER BY ele_order");
    $json = "{ \"options\": [";
    $start = true;
  	foreach($elementsq as $oneele) {
      if(!$start) { $json .= ", "; }
      $json .= "{\"id\": \"".$oneele['ele_id']."\", \"value\": \"".printSmart($oneele['ele_caption'])."\"}";
      $start = false;
  	}
    $json .= "]}";
    print $json;
    break;
}



