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



// OPTIONS FOR CONNECTION MUST BE SET, INCLUDING EXTRA ONES FOR ONE TO ONE
// IF ONE TO MANY, ADDITIONAL OPTION FOR SUBFORM ELEMENT CREATION
// OPTION TO EMBED FORM 2 INSIDE FORM 1, SHOW FORM INSIDE - THE OLD "DISPLAY TOGETHER" IDEA
// - ONLY AVAILABLE IF THERE ISN'T A SUBFORM ELEMENT ALREADY INSIDE FORM 1 THAT SHOWS FORM 2
// - GIVE AN OPTION FOR WHICH PAGE OF THE DEFAULT SCREEN THE ELEMENT SHOULD SHOW UP ON, OR ADD A NEW PAGE FOR IT

include "../../../mainfile.php";
icms::$logger->disableLogger();
while(ob_get_level()) {
    ob_end_clean();
}

include_once XOOPS_ROOT_PATH.'/modules/formulize/include/common.php';

$form1Id = intval($_GET['form1']);
$form2Id = intval($_GET['form2']);
$rel = intval($_GET['rel']);
$submittedPI = intval($_GET['pi']);

$form_handler = xoops_getmodulehandler('forms', 'formulize');
if($form1Object = $form_handler->get($form1Id)) {

	$currentPI = $form1Object->getVar('pi');

	// prompt for PI, and exit
	if(!$currentPI AND !$submittedPI) {
		include XOOPS_ROOT_PATH.'/header.php';
		global $xoopsTpl;
		$pioptions = array();
		$captions = $form1Object->getVar('elementCaptions');
		$headings = $form1Object->getVar('elementColheads');
		foreach($form1Object->getVar('elementsWithData') as $elementId) {
			$pioptions[$elementId] = $headings[$elementId] ? trans(strip_tags($headings[$elementId])) : trans(strip_tags($captions[$elementId]));
		}
		$content['formTitle'] = trans($form1Object->getVar('title'));
		$content['defaultpi'] = 0;
		$content['pioptions'] = $pioptions;
		$xoopsTpl->assign('content', $content);
		$xoopsTpl->display("db:admin/primary_identifier_selection.html");
		exit(); // nothing further to do at this moment

	// save PI if applicable
	} elseif(!$currentPI) {
		$form1Object->setVar('pi', $submittedPI);
		$form_handler->insert($form1Object);
	}

	// prepare the options for the new connection, based on form 1 PI and the elements in the forms already

	// Know PI form 1, and the existing connections, Options could then be:
	// - any linked element in form 2 that points to the same source as a linked element in form 1, as a common value (would be a pair)
	// - any linked element in form 2 that points to any element in form 1 (would be a pair)
	// -- also take vice versa for multiselect linked elements, if relationship is one to many
	// -- also take vice versa for non-multiselect linked elements, if relationship is one to one
	// - any textbox element in form 2 as a common value to PI form 1 (would be pair of PI form 1 plus an element selector for form 2)
	// - a new element option in selector for form 2 (above) to create a connection to PI form 1:
	// -- textbox (common value), or checkboxes or dropdown or autocomplete or multiselect autocomplete (linked element)
	// -- Offer the name of the form or the caption of PI for name of new element, or let them type in a name?



}
