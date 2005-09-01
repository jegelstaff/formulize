<?php

###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2004 Freeform Solutions                  ##
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

//THIS FILE HANDLES THE DISPLAY OF INDIVIDUAL FORM ELEMENTS.  FUNCTIONS CAN BE CALLED FROM ANYWHERE (INTENDED FOR PAGEWORKS MODULE)

function displayElement($ele, $entry="new") {

	include_once XOOPS_ROOT_PATH . "/class/xoopsformloader.php";
	include_once XOOPS_ROOT_PATH . "/modules/formulize/include/formdisplay.php";
	include_once XOOPS_ROOT_PATH . "/modules/formulize/class/elementrenderer.php";

	if(!$formulize_mgr) {
		$formulize_mgr =& xoops_getmodulehandler('elements', 'formulize');
	}

	$element =& $formulize_mgr->get($ele);
	$renderer =& new formulizeElementRenderer($element);

	$ele_value = $element->getVar('ele_value');
	if($entry != "new") {
		$prevEntry = getEntryValues($entry);
	}
	if($prevEntry) { 
		$ele_value = loadValue($prevEntry, $element, $ele_value); // get the value of this element for this entry as stored in the DB 
	}
	$form_ele =& $renderer->constructElement('de_' . $entry . '_'.$element->getVar('ele_id'), $ele_value);
	print $form_ele->render();

}
?>
