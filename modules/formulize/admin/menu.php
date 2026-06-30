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

// This flyout mirrors the admin homepage's top-level tabs and their sub-tabs (see
// getHomeTabs() in admin/ui.php and the settings registry in
// include/configsettings_registry.php). The admin flyout is a single flat level,
// so the hierarchy is conveyed by order and labels: each subject tab is followed
// by its sub-views.

// Apps — the forms dashboard
$adminmenu[] = array(
	'title'	=> _MI_formulize_ADMIN_HOME,
	'link'	=> 'admin/ui.php');

// Users tab + its sub-tabs
$adminmenu[] = array(
	'title'	=> _MI_formulize_MENU_USERS_EMAIL,
	'link'	=> 'admin/ui.php?page=users&view=email');
$adminmenu[] = array(
	'title'	=> _MI_formulize_MENU_USERS_API_KEYS,
	'link'	=> 'admin/ui.php?page=users&view=apikeys');
$adminmenu[] = array(
	'title'	=> _MI_formulize_MENU_USERS,
	'link'	=> 'admin/ui.php?page=users');
$adminmenu[] = array(
	'title'	=> _MI_formulize_MENU_USERS_TOKENS,
	'link'	=> 'admin/ui.php?page=users&view=tokens');

// Settings tab + its sub-tabs
$adminmenu[] = array(
	'title'	=> _MI_formulize_MENU_SETTINGS_ELEMENTS,
	'link'	=> 'admin/ui.php?page=settings&view=elements');
$adminmenu[] = array(
	'title'	=> _MI_formulize_MENU_SETTINGS_FORMS,
	'link'	=> 'admin/ui.php?page=settings&view=forms');
$adminmenu[] = array(
	'title'	=> _MI_formulize_MENU_SETTINGS_MESSAGING,
	'link'	=> 'admin/ui.php?page=settings&view=messaging');
$adminmenu[] = array(
	'title'	=> _MI_formulize_MENU_SETTINGS_AI,
	'link'	=> 'admin/ui.php?page=settings&view=ai');
$adminmenu[] = array(
	'title'	=> _MI_formulize_MENU_SETTINGS_SYSTEM,
	'link'	=> 'admin/ui.php?page=settings&view=system');
$adminmenu[] = array(
	'title'	=> _MI_formulize_MENU_SETTINGS_ADVANCED,
	'link'	=> 'admin/ui.php?page=settings&view=advanced');
$adminmenu[] = array(
	'title'	=> _MI_formulize_MENU_SETTINGS_PERMISSIONS,
	'link'	=> 'admin/ui.php?page=settings&view=permissions');

// Standalone tool tabs
$adminmenu[] = array(
	'title'	=> _MI_formulize_SYSTEM_LOG_VIEWER,
	'link'	=> 'admin/ui.php?page=logviewer');
$adminmenu[] = array(
	'title'	=> _MI_formulize_IMPORT_EXPORT,
	'link'	=> 'admin/ui.php?page=config-sync');
$adminmenu[] = array(
	'title'	=> _MI_formulize_SYNCHRONIZE,
	'link'	=> 'admin/ui.php?page=synchronize');
