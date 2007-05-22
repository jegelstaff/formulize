<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2007 Freeform Solutions                  ##
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
##  Author of this file: Freeform Solutions                                  ##
##  Project: Formulize                                                       ##
###############################################################################

if (!defined("XOOPS_ROOT_PATH")) {
    die("XOOPS root path not defined");
}

require_once XOOPS_ROOT_PATH.'/kernel/object.php';
require_once XOOPS_ROOT_PATH.'/modules/formulize/class/screen.php';
include_once XOOPS_ROOT_PATH.'/modules/formulize/include/functions.php';

class formulizeMultiPageScreen extends formulizeScreen {

	function formulizeMultiPageScreen() {
		$this->formulizeScreen();
		$this->initVar("introtext", XOBJ_DTYPE_TXTAREA);
		$this->initVar("thankstext", XOBJ_DTYPE_TXTAREA);
		$this->initVar("donedest", XOBJ_DTYPE_TXTBOX, NULL, false, 255);
		$this->initVar("buttontext", XOBJ_DTYPE_TXTBOX, NULL, false, 255);
		$this->initVar("pages", XOBJ_DTYPE_ARRAY);
		$this->initVar("pagetitles", XOBJ_DTYPE_ARRAY);
		$this->initVar("conditions", XOBJ_DTYPE_ARRAY);
		$this->initVar("printall", XOBJ_DTYPE_INT); //nmc - 2007.03.24
	}
}

class formulizeMultiPageScreenHandler extends formulizeScreenHandler {
	var $db;
	function formulizeMultiPageScreenHandler(&$db) {
		$this->db =& $db;
	}
	function &getInstance(&$db) {
		static $instance;
		if (!isset($instance)) {
			$instance = new formulizeMultiPageScreenHandler($db);
		}
		return $instance;
	}
	function &create() {
		return new formulizeMultiPageScreen();
	}

	// this function handles all the admin side ui for this kind of screen
	function editForm($screen, $fid) {

		$form = parent::editForm($screen, $fid);
		$form->addElement(new xoopsFormHidden('type', 'multiPage'));
		$form->addElement(new xoopsFormTextArea(_AM_FORMULIZE_SCREEN_INTRO, 'introtext', $screen->getVar('introtext'), 20, 85));
		$form->addElement(new xoopsFormTextArea(_AM_FORMULIZE_SCREEN_THANKS, 'thankstext', $screen->getVar('thankstext'), 20, 85));
		$form->addElement(new xoopsFormText(_AM_FORMULIZE_SCREEN_DONEDEST, 'donedest', 50, 255, $screen->getVar('donedest')));
		$form->addElement(new xoopsFormText(_AM_FORMULIZE_SCREEN_BUTTONTEXT, 'buttontext', 50, 255, $screen->getVar('buttontext')));
		$fe_printall = new XoopsFormRadio(_AM_FORMULIZE_SCREEN_PRINTALL, 'printall', $screen->getVar('printall'));  			//nmc 2007.03.24
		$fe_printall->addOptionArray(array('1' => _AM_FORMULIZE_SCREEN_PRINTALL_Y, '0' => _AM_FORMULIZE_SCREEN_PRINTALL_N));  	//nmc 2007.03.24
		$form->addElement($fe_printall);  																						//nmc 2007.03.24
		
		// javascript required for form
		print "\n<script type='text/javascript'>\n";

		print "	function confirmDeletePage() {\n";
		print "		var answer = confirm ('" . _AM_FORMULIZE_CONFIRM_SCREEN_DELETE_PAGE . "')\n";
		print "		if (answer) {\n";
		print "			return true;\n";
		print "		} else {\n";
		print "			return false;\n";
		print "		}\n";
		print "	}\n";

                print "	function frameworkChange() {\n";
                // this function has no effect in this type of screen (but is required due to javascript event set in the screen.php file)
		print "	}\n";

		print "</script>\n";

		// need to add the pages -- conditions not included in first pass
		$form->addElement(new xoopsFormButton('', 'addpage', _AM_FORMULIZE_SCREEN_ADDPAGE, 'submit'));

		// setup all the elements in this form for use in the listboxes
		include_once XOOPS_ROOT_PATH . "/modules/formulize/class/forms.php";
		$formObject = new formulizeForm($fid);
		$elements = $formObject->getVar('elements');
		$elementCaptions = $formObject->getVar('elementCaptions');
		for($i=0;$i<count($elements);$i++) {
			$options[$elements[$i]] = printSmart(trans(strip_tags($elementCaptions[$i]))); // need to pull out potential HTML tags from the caption
		}

		// get page titles
		$pagetitles = $screen->getVar('pagetitles');
		array_unshift($pagetitles, ""); // need to add dummy first position value to the array, so the titles line up in the boxes properly based on page number

		$pagecount = 0;		
		$pageTitleDeleteOffset = 0;		
		foreach($screen->getVar('pages') as $pagenum=>$thispage) {
			if(!isset($_POST['delete'.$pagenum])) {
				$pagenumber = $pagecount+1;

				// page title
				$pageTitleNumber = $pagenumber+$pageTitleDeleteOffset;
				$pageTitleBox = new xoopsFormText(_AM_FORMULIZE_SCREEN_PAGETITLE.' '.$pageTitleNumber, 'pagetitle_'.$pageTitleNumber, 50, 255, $pagetitles[$pageTitleNumber]);
				$form->addElement($pageTitleBox, true);
				unset($pageTitleBox);

				// page elements
				$elementSelection = new xoopsFormSelect(_AM_FORMULIZE_SCREEN_A_PAGE.' '.$pagenumber.'<br><br><input type=submit name=delete'.$pagecount.' value="'._AM_FORMULIZE_DELETE_THIS_PAGE.'" onclick="javascript:return confirmDeletePage(\'$pagecount\');">', 'page'.$pagecount, $thispage, 10, true);
				$elementSelection->addOptionArray($options);
				$form->addElement($elementSelection);
				unset($elementSelection);
				$pagecount++;
			} else {
				$pageTitleDeleteOffset++;
			}
		}
		if($pagecount == 0) { // no pages specified
			for($i=0;$i<2;$i++) {
				$pagenumber = $i+1;

				// page title
				$pageTitleNumber = $pagenumber+$pageTitleDeleteOffset;
				$pageTitleBox = new xoopsFormText(_AM_FORMULIZE_SCREEN_PAGETITLE.' '.$pageTitleNumber, 'pagetitle_'.$pageTitleNumber, 50, 255, $pagetitles[$pageTitleNumber]);
				$form->addElement($pageTitleBox, true);
				unset($pageTitleBox);

				// page elements
				$elementSelection = new xoopsFormSelect(_AM_FORMULIZE_SCREEN_A_PAGE.' '.$pagenumber.'<br><br><input type=submit name=delete'.$i.' value="'._AM_FORMULIZE_DELETE_THIS_PAGE.'" onclick="javascript:return confirmDeletePage(\'$i\');">', 'page'.$i, '', 10, true);
				$elementSelection->addOptionArray($options);
				$form->addElement($elementSelection);
				unset($elementSelection);
			}
			$pagecount = 2;
		}
		
		if(isset($_POST['addpage'])) {
			$pagenumber++;

			// page title
			$pageTitleNumber = $pagenumber+$pageTitleDeleteOffset;
			$pageTitleBox = new xoopsFormText(_AM_FORMULIZE_SCREEN_PAGETITLE.' '.$pageTitleNumber, 'pagetitle_'.$pageTitleNumber, 50, 255, $pagetitles[$pageTitleNumber]);
			$form->addElement($pageTitleBox, true);
			unset($pageTitleBox);

			// page elements
			$elementSelection = new xoopsFormSelect(_AM_FORMULIZE_SCREEN_A_PAGE.' '.$pagenumber.'<br><br><input type=submit name=delete'.$pagecount.' value="'._AM_FORMULIZE_DELETE_THIS_PAGE.'" onclick="javascript:return confirmDeletePage(\'$pagecount\');">', 'page'.$pagecount, '', 10, true);
			$elementSelection->addOptionArray($options);
			$form->addElement($elementSelection);
		}

		return $form;
	}


	function saveForm(&$screen, $fid) {
		$vars['title'] = $_POST['title'];
		$vars['fid'] = $_POST['fid'];
		$vars['frid'] = $_POST['frid'];
		$vars['introtext'] = $_POST['introtext'];
		$vars['buttontext'] = $_POST['buttontext'];
		$vars['thankstext'] = $_POST['thankstext'];
		$vars['donedest'] = $_POST['donedest'];
		$vars['printall'] = $_POST['printall'];
		$vars['type'] = 'multiPage';
		$pages = array();
		$pagetitles = array();
		foreach($_POST as $k=>$v) {
			if(substr($k, 0, 9) == "pagetitle") {
				$pagetitles[] = $v;
			} elseif(substr($k, 0, 4) == "page") {
				$pages[] = $v;
			}
		}
		$vars['pages'] = serialize($pages);
		$vars['pagetitles'] = serialize($pagetitles);
		$screen->assignVars($vars);
		$sid = $this->insert($screen);
		if(!$sid) {
			print "Error: the information could not be saved in the database.";
		}
		$screen->assignVar('sid', $sid);
	}

	function insert($screen) {
		$update = ($screen->getVar('sid') == 0) ? false : true;
		if(!$sid = parent::insert($screen)) { // write the basic info to the db, handle cleaning vars and all that jazz.  Object passed by reference, so updates will have affected it in the other method.
			return false;
		}
		$screen->assignVar('sid', $sid);
		// note: conditions is not written to the DB yet, since we're not gathering that info from the UI	
		if (!$update) {
                 $sql = sprintf("INSERT INTO %s (sid, introtext, thankstext, donedest, buttontext, pages, pagetitles, printall) VALUES (%u, %s, %s, %s, %s, %s, %s, %u)", $this->db->prefix('formulize_screen_multipage'), $screen->getVar('sid'), $this->db->quoteString($screen->getVar('introtext')), $this->db->quoteString($screen->getVar('thankstext')), $this->db->quoteString($screen->getVar('donedest')), $this->db->quoteString($screen->getVar('buttontext')), $this->db->quoteString(serialize($screen->getVar('pages'))), $this->db->quoteString(serialize($screen->getVar('pagetitles'))), $screen->getVar('printall')); //nmc 2007.03.24 added 'printall' & fixed pagetitles
             } else {
                 $sql = sprintf("UPDATE %s SET introtext = %s, thankstext = %s, donedest = %s, buttontext = %s, pages = %s, pagetitles = %s, printall = %u WHERE sid = %u", $this->db->prefix('formulize_screen_multipage'), $this->db->quoteString($screen->getVar('introtext')), $this->db->quoteString($screen->getVar('thankstext')), $this->db->quoteString($screen->getVar('donedest')), $this->db->quoteString($screen->getVar('buttontext')), $this->db->quoteString(serialize($screen->getVar('pages'))), $this->db->quoteString(serialize($screen->getVar('pagetitles'))),$screen->getVar('printall'), $screen->getVar('sid')); //nmc 2007.03.24 added 'printall'
             }
		 $result = $this->db->query($sql);
             if (!$result) {
                 return false;
             }
		 return $sid;
	}

	// 	THIS METHOD MIGHT BE MOVED UP A LEVEL TO THE PARENT CLASS
	function get($sid) {
		$sid = intval($sid);
		if ($sid > 0) {
			$sql = 'SELECT * FROM '.$this->db->prefix('formulize_screen').' AS t1, '. $this->db->prefix('formulize_screen_multipage').' AS t2 WHERE t1.sid='.$sid.' AND t1.sid=t2.sid';
			if (!$result = $this->db->query($sql)) {
				return false;
			}
			$numrows = $this->db->getRowsNum($result);
			if ($numrows == 1) {
				$screen = new formulizeMultiPageScreen();
				$screen->assignVars($this->db->fetchArray($result));
				return $screen;
			}
		}
		return false;

	}

	// THIS METHOD HANDLES ALL THE LOGIC ABOUT HOW TO ACTUALLY DISPLAY THIS TYPE OF SCREEN
	// $screen is a screen object
	function render($screen, $entry) {
		$formframe = $screen->getVar('frid') ? $screen->getVar('frid') : $screen->getVar('fid');
		$mainform = $screen->getVar('frid') ? $screen->getVar('fid') : "";
		$pages = $screen->getVar('pages');
		$pagetitles = $screen->getVar('pagetitles');
		array_unshift($pages, ""); // displayFormPages looks for the page array to start with [1] and not [0], for readability when manually using the API.
		array_unshift($pagetitles, ""); 
		$pages['titles'] = $pagetitles;
		unset($pages[0]); // get rid of the part we just unshifted, so the page count is correct
		include_once XOOPS_ROOT_PATH . "/modules/formulize/include/formdisplaypages.php";
		displayFormPages($formframe, $entry, $mainform, $pages, "", $screen->getVar('introtext'), $screen->getVar('thankstext'), $screen->getVar('donedest'), $screen->getVar('buttontext'), "","", $screen->getVar('printall')); //nmc 2007.03.24 added 'printall' & 2 empty params
	}

}

?>