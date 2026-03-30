<?php

###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                  Copyright (c) 2018 The Formulize Project                 ##
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
##  Author of this file: Julian Egelstaff                                    ##
##  Project: Formulize                                                       ##
###############################################################################

// grab the metadata from the markupId
// grab the value from value
// lookup what the special validation code is for this element
// run that code
// echo result

include_once "../../mainfile.php";
icms::$logger->disableLogger();
include_once XOOPS_ROOT_PATH."/modules/formulize/include/extract.php";
include_once XOOPS_ROOT_PATH."/modules/formulize/include/functions.php";

// markupId is de_fid_entryId_elementId
$metaData = explode("_", $_GET['markupId']);
$element_id = intval($metaData[3]);
$entry_id = intval($metaData[2]);
$fid = intval($metaData[1]);
$value = strip_tags(htmlspecialchars($_GET['value']));

// get the special validation code for this element
// invoke element handler
// get element object
// get the code specified

$specialValidationColor = "";
$specialValidationCode = "";

if($specialValidationCode) {
    $value = eval($specialValidationCode);
    print json_encode(array('text'=>$value, 'color'=>$specialValidationColor));
}
