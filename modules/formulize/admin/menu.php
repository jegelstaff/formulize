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

$adminmenu[] = array(
	'title'	=> _MI_formulize_ADMIN_HOME,
	'link'	=> 'admin/ui.php',
	'icon'  => 'images/admin/users_big.png',
	'small' => 'images/admin/users_small.png');
$adminmenu[] = array(
	'title'	=> _MI_formulize_EMAIL_USERS,
	'link'	=> 'admin/ui.php?page=mailusers',
	'icon'  => 'images/admin/users_big.png',
	'small' => 'images/admin/users_small.png');
$adminmenu[] = array(
	'title'	=> _MI_formulize_MANAGE_API_KEYS,
	'link'	=> 'admin/ui.php?page=managekeys',
	'icon'  => 'images/admin/categories_big.png',
	'small' => 'images/admin/categories_small.png');
$adminmenu[] = array(
	'title'	=> _MI_formulize_IMPORT_EXPORT,
	'link'	=> 'admin/ui.php?page=config-sync',
	'icon'  => 'images/admin/fields_big.png',
	'small' => 'images/admin/fields_small.png');
$adminmenu[] = array(
	'title'	=> _MI_formulize_SYNCHRONIZE,
	'link'	=> 'admin/ui.php?page=synchronize',
	'icon'  => 'images/admin/regstep_big.png',
	'small' => 'images/admin/regstep_small.png');
$adminmenu[] = array(
	'title'	=> _MI_formulize_COPY_GROUP_PERMS,
	'link'	=> 'admin/ui.php?page=managepermissions',
	'icon'  => 'images/admin/visibility_big.png',
	'small' => 'images/admin/visibility_small.png');
$adminmenu[] = array(
	'title'	=> _MI_formulize_MANAGE_ACCOUNT_CREATION_TOKENS,
	'link'	=> 'admin/ui.php?page=managetokens',
	'icon'  => 'images/admin/permissions_big.png',
	'small' => 'images/admin/permissions_small.png');
