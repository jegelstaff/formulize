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

if(!defined('FORMULIZE_COMMON_INCLUDED')) {
	define('FORMULIZE_COMMON_INCLUDED', 1);
}
include_once XOOPS_ROOT_PATH . '/class/xoopsformloader.php';
include_once XOOPS_ROOT_PATH . '/modules/formulize/class/formulize.php';
include_once XOOPS_ROOT_PATH . '/modules/formulize/class/elementrenderer.php';
include_once XOOPS_ROOT_PATH . '/modules/formulize/include/constants.php';
include_once XOOPS_ROOT_PATH . '/modules/formulize/include/functions.php';
include_once XOOPS_ROOT_PATH . '/modules/formulize/include/formdisplay.php';
include_once XOOPS_ROOT_PATH . '/modules/formulize/include/entriesdisplay.php';
include_once XOOPS_ROOT_PATH . '/modules/formulize/include/graphdisplay.php';
include_once XOOPS_ROOT_PATH . '/modules/formulize/include/calendardisplay.php';
include_once XOOPS_ROOT_PATH . '/modules/formulize/include/elementdisplay.php';
include_once XOOPS_ROOT_PATH . '/modules/formulize/include/griddisplay.php';
include_once XOOPS_ROOT_PATH . '/modules/formulize/include/extract.php';
include_once XOOPS_ROOT_PATH . '/modules/formulize/include/customCodeForApplications.php';
include_once XOOPS_ROOT_PATH . '/modules/formulize/class/usersGroupsPerms.php';
include_once XOOPS_ROOT_PATH . '/modules/formulize/class/data.php';
include_once XOOPS_ROOT_PATH . '/modules/formulize/class/screen.php';
//Add the language constants
global $xoopsConfig;
if (file_exists(XOOPS_ROOT_PATH . "/modules/formulize/language/".$xoopsConfig['language']."/main.php") ) {
    include_once XOOPS_ROOT_PATH . "/modules/formulize/language/".$xoopsConfig['language']."/main.php";
    include_once XOOPS_ROOT_PATH . "/modules/formulize/language/".$xoopsConfig['language']."/admin.php";
		include_once XOOPS_ROOT_PATH . "/language/".$xoopsConfig['language']."/global.php";
} else {
    include_once XOOPS_ROOT_PATH . "/modules/formulize/language/english/main.php";
    include_once XOOPS_ROOT_PATH . "/modules/formulize/language/english/admin.php";
		include_once XOOPS_ROOT_PATH . "/language/english/global.php";
}

formulize_handleHtaccessRewriteRule();

$GLOBALS['formulize_subformInstance'] = 100;
