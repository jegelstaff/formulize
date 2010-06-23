<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2005 Freeform Solutions                  ##
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
##  Author of this file: Freeform Solutions 					     ##
##  Project: Formulize                                                       ##
###############################################################################

include 'header.php';

$xoopsOption['template_main'] = 'formulize_application.html';

require(XOOPS_ROOT_PATH."/header.php");

global $xoopsDB;

$aid = (isset($_GET['id'])) ? intval($_GET['id']) : 0 ;
$form_handler = xoops_getmodulehandler('forms', 'formulize');
$application_handler = xoops_getmodulehandler('applications', 'formulize');
$forms = $form_handler->getFormsByApplication($aid, true);
include_once XOOPS_ROOT_PATH . "/modules/formulize/include/functions.php";
$allowedForms = allowedForms();
$formsToShow = array_intersect($forms, $allowedForms);
$formsToSend = array();
$i=0;
foreach($formsToShow as $thisForm) {
	$thisFormObject = $form_handler->get($thisForm);
	if($thisFormObject->getVar('menutext')) {
		$formsToSend[$i]['fid'] = $thisFormObject->getVar('id_form');
		$formsToSend[$i]['title'] = html_entity_decode($thisFormObject->getVar('menutext'), ENT_QUOTES) == "Use the form's title" ? $thisFormObject->getVar('title') : $thisFormObject->getVar('menutext');
		$i++;
	}
}
if($aid) {
	$application = $application_handler->get($aid);
	$app_name = $application->getVar('name');
} else {
	$app_name = _AM_CATGENERAL;
}
if(count($formsToSend)==0) {
	$noforms =  _AM_NOFORMS_AVAIL;
} else {
	$noforms = 0;
}

$xoopsTpl->assign("app_name", $app_name);
$xoopsTpl->assign("noforms", $noforms);
$xoopsTpl->assign("formData", $formsToSend);

require(XOOPS_ROOT_PATH."/footer.php");
