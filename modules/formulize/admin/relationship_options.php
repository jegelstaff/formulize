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

// this file gets all the data about a particular page of a screen, so it can be edited

require_once "../../../mainfile.php";
include_once("admin_header.php");

include_once XOOPS_ROOT_PATH."/modules/formulize/include/functions.php";
include_once XOOPS_ROOT_PATH."/class/xoopsformloader.php";
$framework_handler = xoops_getmodulehandler('frameworks', 'formulize');

// setup a smarty object that we can use for templating our own pages

global $icmsConfig;
require_once XOOPS_ROOT_PATH.'/class/template.php';
require_once XOOPS_ROOT_PATH.'/class/theme.php';
require_once XOOPS_ROOT_PATH.'/class/theme_blocks.php';
$xoopsThemeFactory = new icms_view_theme_Factory();
$xoopsThemeFactory->allowedThemes = $icmsConfig['theme_set_allowed'];
$xoopsThemeFactory->defaultTheme = $icmsConfig['theme_set'];
$xoTheme = $xoopsThemeFactory->createInstance();
$xoopsTpl = $xoTheme->template;

$linkId = intval($_GET['linkId']);
$link = new formulizeFrameworkLink($linkId);
$content = $framework_handler->gatherRelationshipHelpAndOptionsContent($link);
$content['isSaveLocked'] = sendSaveLockPrefToTemplate();

icms::$logger->disableLogger();
while(ob_get_level()) {
    ob_end_clean();
}

$xoopsTpl->assign("content",$content);
$xoopsTpl->display("db:admin/relationship_options.html");

