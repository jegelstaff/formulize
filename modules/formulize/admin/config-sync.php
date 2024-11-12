<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) Formulize Project												 ##
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
##  Author of this file: Formulize Project  					     									 ##
##  Project: Formulize                                                       ##
###############################################################################

/**
 * Configuration as code synchronization admin screen
 */

// Operations may require additional memory and time to perform
ini_set('memory_limit', '1024M');
ini_set('max_execution_time', '600');
ini_set('display_errors', 1);

include_once '../include/formulizeConfigSync.php';

$breadcrumbtrail[1]['url'] = "page=home";
$breadcrumbtrail[1]['text'] = "Home";
$breadcrumbtrail[2]['text'] = "Configuration Synchronization";

$configSync = new FormulizeConfigSync('/config');
$diff = $configSync->compareConfigurations();

$adminPage['template'] = "db:admin/config-sync.html";
$adminPage['success'] = [];
$adminPage['failure'] = [];

if (isset($_POST['action']) && $_POST['action'] == 'export') {
	$export = $configSync->exportConfiguration();
	header('Content-Type: application/json');
	header('Content-Disposition: attachment; filename="forms.json"');
	echo $export;
	exit();
}


if (isset($_POST['action']) && $_POST['action'] == 'apply') {
	$changes = $_POST['handles'] ?? [];
	$result = $configSync->applyChanges($changes);
	$adminPage['success'] = $result['success'];
	$adminPage['failure'] = $result['failure'];
	// Compare the config again if we've applied changes so the results are up to date
	$diff = $configSync->compareConfigurations();
}

$adminPage['changes'] = $diff['changes'];
$adminPage['log'] = $diff['log'];
$adminPage['errors'] = $diff['errors'];
