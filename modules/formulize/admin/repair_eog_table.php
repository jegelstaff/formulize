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

// This file receives ajax form submissions from the new admin UI

include_once "../../../mainfile.php";
icms::$logger->disableLogger();
while(ob_get_level()) {
    ob_end_clean();
}
global $xoopsUser, $xoopsDB;
if (!$xoopsUser) {
    print "Error: you are not logged in";
    return;
}
$gperm_handler = xoops_gethandler('groupperm');
include_once XOOPS_ROOT_PATH . "/modules/formulize/include/functions.php";
include_once XOOPS_ROOT_PATH ."/modules/formulize/class/forms.php";

$groups = $xoopsUser->getGroups();
$mid = getFormulizeModId();
$permissionToCheck = "module_admin";
$itemToCheck = $mid;
$moduleToCheck = 1; // system module
if (!$gperm_handler->checkRight($permissionToCheck, $itemToCheck, $groups, $moduleToCheck)) {
    print "Error: you do not have permission to save this data";
    return;
}

//check to see if there are entries in the form which 
//do not appear in the entry_owner_groups table. If so, it finds the 
// owner/creator of the entry and calls setEntryOwnerGroups() which inserts the
//first, get the form ids and handles.  
$missingEntries=q("SELECT main.entry_id,main.creation_uid From " . $xoopsDB->prefix("formulize_".formulize_db_escape($_POST['form_handle'])) . " as main WHERE NOT EXISTS(
SELECT 1 FROM " . $xoopsDB->prefix("formulize_entry_owner_groups") . " as eog WHERE eog.fid=".intval($_POST['form_id'])." and eog.entry_id=main.entry_id )");
//now we got the missing entries in the form and the users who created them.    
$data_handler = new formulizeDataHandler(intval($_POST['form_id']));
foreach ($missingEntries as $entry){
    if (!$groupResult = $data_handler->setEntryOwnerGroups($entry['creation_uid'],$entry['entry_id'])) {
            print "ERROR: failed to write the entry ownership information to the database.<br>";
    }
}

echo "found and fixed ". count((array) $missingEntries) . " ownership problems in your form";
