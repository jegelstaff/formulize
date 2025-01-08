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

require_once "../../../mainfile.php";

icms::$logger->disableLogger();
while(ob_get_level()) {
		ob_end_clean();
}

include_once("admin_header.php");

include_once XOOPS_ROOT_PATH."/modules/formulize/include/functions.php";
include_once XOOPS_ROOT_PATH."/class/xoopsformloader.php";
$form_handler = xoops_getmodulehandler('forms', 'formulize');

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

$oneFormNames = array();
$manyFormNames = array();
$form1Id = intval($_GET['form1Id']);
$form2Ids = (isset($_GET['form2Ids']) AND is_array($_GET['form2Ids']) AND count($_GET['form2Ids']) > 0) ? $_GET['form2Ids'] : array();
$getAllElementsEvenUnDisplayedOnes = true;
$withNoConnectionsToThisFormId = $form1Id;
$formObjects = $form_handler->getAllForms($getAllElementsEvenUnDisplayedOnes, $form2Ids, $withNoConnectionsToThisFormId);

if($formObjects) {
	foreach($formObjects as $formObject) {
		$oneFormNames[$formObject->getVar('fid')] = $formObject->getSingular();
		$manyFormNames[$formObject->getVar('fid')] = $formObject->getPlural();
	}

	$formObject = $form_handler->get($form1Id);
	$content = array(
		'formTitle'=>$formObject->getVar('title'),
		'formSingular'=>$formObject->getSingular(),
		'oneFormNames'=>$oneFormNames,
		'manyFormNames'=>$manyFormNames
	);
	$content['isSaveLocked'] = sendSaveLockPrefToTemplate();

	$xoopsTpl->assign("content",$content);
	$xoopsTpl->display("db:admin/relationship_create_connection.html");

}
