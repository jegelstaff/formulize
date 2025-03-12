<?php


###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) Formulize Project                        ##
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
##  Author of this file: Formulize Project                                   ##
##  Project: Formulize                                                       ##
###############################################################################

// end point for async update when altering the contents of a multipage screen's page

include "../../../mainfile.php";
icms::$logger->disableLogger();
while(ob_get_level()) {
    ob_end_clean();
}

$sid = intval($_GET['sid']);
$pageNumber = intval($_GET['page']);
$screen_handler = xoops_getmodulehandler('multiPageScreen', 'formulize');
if($screen = $screen_handler->get($sid)) {
    setupXoopsTpl();
    global $xoopsTpl;
    $fid = $screen->getVar('fid');
    $frid = $screen->getVar('frid');
    $elements = $screen->getVar("pages");
    list($title, $elements) = generateElementInfoForScreenPage($elements[$pageNumber], $fid, $frid);
    $xoopsTpl->assign('sectionContent', array(
        'index' => $pageNumber,
        'pageItemTypeTitle' => $title,
        'elements' => $elements
    ));
    $xoopsTpl->display("db:admin/screen_multipage_pages_sections.html");
}

function setupXoopsTpl() {
    global $xoopsOption, $xoopsConfig, $xoopsModule;

    $xoopsOption['theme_use_smarty'] = 1;

    // include Smarty template engine and initialize it
    require_once XOOPS_ROOT_PATH . '/class/template.php';
    require_once XOOPS_ROOT_PATH . '/class/theme.php';
    require_once XOOPS_ROOT_PATH . '/class/theme_blocks.php';

    if ( @$xoopsOption['template_main'] ) {
        if ( false === strpos( $xoopsOption['template_main'], ':' ) ) {
            $xoopsOption['template_main'] = 'db:' . $xoopsOption['template_main'];
        }
    }
    $xoopsThemeFactory = new xos_opal_ThemeFactory();
    $xoopsThemeFactory->allowedThemes = $xoopsConfig['theme_set_allowed'];
    $xoopsThemeFactory->defaultTheme = $xoopsConfig['theme_set'];

    $xoTheme =& $xoopsThemeFactory->createInstance(array(
        'contentTemplate' => @$xoopsOption['template_main'],
    ));
    global $xoopsTpl;
    $xoopsTpl =& $xoTheme->template;
}