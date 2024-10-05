<?php
###############################################################################
##                Formulize - ad hoc form creation and reporting             ##
##                    Copyright (c) 2012 Freeform Solutions                  ##
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

require_once "../../mainfile.php";
require_once XOOPS_ROOT_PATH . "/modules/formulize/include/functions.php";

// check permission on the fid, and if user has edit_form permission, then set the master override flag
// then either way, carry on with normal rendering of the form

$groups = $xoopsUser ? $xoopsUser->getGroups() : array(0=>XOOPS_GROUP_ANONYMOUS);
$uid = $xoopsUser ? $xoopsUser->getVar('uid') : 0;
if(!$uid) {
    print "Error: only logged in users can access master.php";
    exit();
}
$mid = getFormulizeModId();
$gperm_handler = xoops_gethandler('groupperm');
if($gperm_handler->checkRight("edit_form", $fid, $groups, $mid)) {
    $formulize_masterUIOverride = true; // user has requested the master UI by using this URL, and they have edit_form permission, so we will give them the full default UI for the form
} else {
    $formulize_masterUIOverride = false;
}

include "index.php";
