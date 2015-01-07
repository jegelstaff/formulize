<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2011 Freeform Solutions                  ##
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

// this file checks the entry id and form element id passed to it, and also the current user's permissions,
// and if they have access to the entry and element, then it queues up a download for the user

include "../../mainfile.php";

$groups = $xoopsUser ? $xoopsUser->getGroups() : array(0=>XOOPS_GROUP_ANONYMOUS);
$uid = $xoopsUser ? $xoopsUser->getVar('uid') : 0;

$entry_id = intval($_GET['entry_id']);
$element_id = intval($_GET['element']);

include_once XOOPS_ROOT_PATH ."/modules/formulize/class/elements.php"; // fileUploadElement extends this so needs it included before we instantiate the handler
$element_handler = xoops_getmodulehandler('fileUploadElement','formulize');
$elementObject = $element_handler->get($element_id);
$fid = $elementObject->getVar('id_form');

include_once XOOPS_ROOT_PATH . "/modules/formulize/include/functions.php";
if(security_check($fid, $entry_id, $uid)) {
    // USER IS ALLOWED TO SEE THIS ENTRY IN THIS FORM
    // check if the user is allowed to see this element in the form
    $ele_display = $elementObject->getVar('ele_display');
    $userCanAccessElement = false;
    if($ele_display == 1) {
        $userCanAccessElement = true;
    } elseif(strstr($ele_display,",")) { // comma separated list of groups
        $allowedGroups = explode(",",trim($ele_display,","));
        if(array_intersect($groups, $allowedGroups)) {
            $userCanAccessElement = true;
        }
    }
    if($userCanAccessElement) {
        // USER IS ALLOWED TO SEE THIS ELEMENT
        include_once XOOPS_ROOT_PATH . "/modules/formulize/class/data.php";
        $data_handler = new formulizeDataHandler($fid);
        $fileInfo = $data_handler->getElementValueInEntry($entry_id, $elementObject);
        $fileInfo = unserialize($fileInfo);
        $filePath = XOOPS_ROOT_PATH."/uploads/formulize_".$fid."_".$entry_id."_".$element_id."/".$fileInfo['name'];
        if (file_exists($filePath)) {
            header('Content-Description: File Transfer');
            header('Content-Type: '.$fileInfo['type']);
            header('Content-Disposition: attachment; filename='.$element_handler->getFileDisplayName($fileInfo['name']));
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
            header('Content-Length: ' . $fileInfo['size']);
            readfile($filePath);
            exit;
        } else {
            include "../../header.php";
            print "<p><b>The file you requested could not be found.  It may have been deleted from the server.</b></p>";
            include "../../footer.php";
        }
    }
}

