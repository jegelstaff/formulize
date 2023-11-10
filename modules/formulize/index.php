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

// SET $formulize_screen_id IN A PHP BLOCK, AND THEN INCLUDE
// XOOPS_ROOT_PATH . "/modules/formulize/index.php" TO CALL UP
// A SCREEN IN A BLOCK WITHOUT THE ENTIRE XOOPS TEMPLATE COMING IN

// uncomment these two lines to enable benchmarking of performance...depends also on the user id specified in formulize_benchmark in include/extract.php

/*require_once "../../mainfile.php";
include_once XOOPS_ROOT_PATH . "/modules/formulize/include/extract.php";
$GLOBALS['startPageTime'] = microtime(true);*/

if(!isset($formulize_masterUIOverride)) {
    $formulize_masterUIOverride = false;
}

// a declared screen means mainfile must already have been included. developer must do this elsewhere when declaring screen.
if(!isset($formulize_screen_id) OR !is_numeric($formulize_screen_id)) {
    require_once "../../mainfile.php";
}

// ajax request from a different server, so set $formulize_screen_id now, and we won't use the theme to render the contents of the page
if(!strstr($_SERVER['HTTP_REFERER'], XOOPS_URL)
    AND isset($_SERVER['HTTP_FORMULIZE_REMOTE_INCLUDE']) 
    AND isset($_GET['sid'])
    AND intval($_GET['sid'])) {
        $formulize_screen_id = $_GET['sid'];
}

// need to declare the allowable CORS locations -- must be configurable
header('Access-Control-Allow-Origin: https://polygon.red');
header('Access-Control-Allow-Headers: formulize-remote-include, x-requested-with, Content-Type, referer');
        
include_once XOOPS_ROOT_PATH.'/header.php';

include_once XOOPS_ROOT_PATH.'/modules/formulize/include/common.php';

global $xoTheme;
if($xoTheme) {

    // retrieve the xoops_version info
    $module_handler = xoops_gethandler('module');
    $formulizeModule = $module_handler->getByDirname("formulize");
    $metadata = $formulizeModule->getInfo();

    $xoTheme->addStylesheet("/modules/formulize/templates/css/formulize.css?v=".$metadata['version']);
    $xoTheme->addScript("/modules/formulize/libraries/formulize.js");
    $xoTheme->addStylesheet("/modules/formulize/libraries/jquery/timeentry/jquery.timeentry.css");
    $xoTheme->addScript("modules/formulize/libraries/jquery/timeentry/jquery.plugin.min.js");
    $xoTheme->addScript("modules/formulize/libraries/jquery/timeentry/jquery.timeentry.js");
    $xoTheme->addScript("modules/formulize/libraries/jquery/timeentry/jquery.mousewheel.js");
}
include 'initialize.php';

// a declared screen means the page rendering is being included elsewhere, at the point where the screen id is included, so don't call footer otherwise we will output the entire page template, not just the body contents
if(!isset($formulize_screen_id) OR !is_numeric($formulize_screen_id)) {
    include XOOPS_ROOT_PATH.'/footer.php';
}
