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
		print "     window.document.editscreenform.submit();\n";
		print "	}\n";
		
		print "</script>\n";

		// setup all the elements in this form for use in the listboxes
		include_once XOOPS_ROOT_PATH . "/modules/formulize/class/forms.php";
		$options = multiPageScreen_addToOptionsList($fid, array());
		
		/*$formObject = new formulizeForm($fid); // this code now in the function called multiPageScreen_addToOptionsList
		$elements = $formObject->getVar('elements');
		$elementCaptions = $formObject->getVar('elementCaptions');
		for($i=0;$i<count($elements);$i++) {
			$options[$elements[$i]] = printSmart(trans(strip_tags($elementCaptions[$i]))); // need to pull out potential HTML tags from the caption
		}*/
		
		// add in elements from other forms in the framework, by looping through each link in the framework and checking if it is a display as one, one-to-one link
		// added March 20 2008, by jwe
		$frid = $screen->getVar("frid");
		if($frid) {
				$framework_handler =& xoops_getModuleHandler('frameworks');
				$frameworkObject = $framework_handler->get($frid);
				foreach($frameworkObject->getVar("links") as $thisLinkObject) {
						if($thisLinkObject->getVar("unifiedDisplay") AND $thisLinkObject->getVar("relationship") == 1) {
								$thisFid = $thisLinkObject->getVar("form1") == $fid ? $thisLinkObject->getVar("form2") : $thisLinkObject->getVar("form1");
								$options = multiPageScreen_addToOptionsList($thisFid, $options);
						}
				}
		}
		
		

    // setup the operators
    $ops = array();
    $ops['='] = "=";
    $ops['NOT'] = "NOT";
    $ops['>'] = ">";
    $ops['<'] = "<";
    $ops['>='] = ">=";
    $ops['<='] = "<=";
    $ops['LIKE'] = "LIKE";
    $ops['NOT LIKE'] = "NOT LIKE";

		// get page titles
    $pageTitles = $screen->getVar("pagetitles");
//		array_unshift($pageTitles, ""); // need to add dummy first position value to the array, so the titles line up in the boxes properly based on page number
		
    $elements = $screen->getVar("pages");
    $conditions = $screen->getVar("conditions");
    
    $oldPageNumber = 0;
    $pageNumber = 0;
    $pageCounterOffset = 0;
   
		for($i=0;$i<(count($elements)+$pageCounterOffset);$i++) {
			if(!isset($_POST['delete'.$i]) AND !isset($_POST['insertpage'.$i]) AND !isset($_POST['insertlastpage'.$i])) {
        $form = drawPageUI($pageNumber, $pageTitles[$oldPageNumber], $elements[$oldPageNumber], $conditions[$oldPageNumber], $form, $options, $ops);
        $pageNumber++;
        $oldPageNumber++;
      } 
			elseif(isset($_POST['insertpage'.$i])) {
				$form = drawPageUI($pageNumber, "", array(), array(), $form, $options, $ops);
				$pageNumber++;
				$pageCounterOffset++;						
			} 				
			elseif(isset($_POST['delete'.$i])) {
        $oldPageNumber++;
      }
		}
		
		if(isset($_POST['insertlastpage'.$i])) {
				$lastPageNumber = $i;
				$form = drawPageUI($lastPageNumber, "", array(), array(), $form, $options, $ops);			
			} 
			
		if($pageNumber == 0) { // no pages specified, draw two blank pages	
			for($i=0;$i<2;$i++) {	
				$form = drawPageUI($pageNumber, "", array(), array(), $form, $options, $ops);
				$pageNumber = $i+1;				
			}
		}	
		
		$form->addElement(new xoopsFormButton('', 'insertlastpage'.$pageNumber, _AM_FORMULIZE_SCREEN_INSERTPAGE, 'submit'));		
				
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
    
    // conditions...
    // pagecons1 is the yes/no for conditions -- stored as: conditions[1]['pagecons']
    // page1elements, page1ops, page1terms are arrays with all the condition details -- stored as: conditions[1]['details']['elements'][0..n], etc
    
    
    
		foreach($_POST as $k=>$v) {
			if(substr($k, 0, 9) == "pagetitle") {
				$pagetitles[] = $v;
			}elseif(substr($k, 0, 8) == "pagecons") {
        $conditions[substr($k, 8)]['pagecons'] = $v;
			}elseif(substr($k, 0, 12) == "pageelements") {
        foreach($v as $key=>$value) {
            $conditions[substr($k, 12)]['details']['elements'][$key] = $value; 
        }
      }elseif(substr($k, 0, 7) == "pageops") {
        foreach($v as $key=>$value) {
            $conditions[substr($k, 7)]['details']['ops'][$key] = $value;
        }
      }elseif(substr($k, 0, 9) == "pageterms") {
        foreach($v as $key=>$value) {
            if($value != "") {
                $conditions[substr($k, 9)]['details']['terms'][$key] = $value;
            } else {
                unset($conditions[substr($k, 9)]['details']['elements'][$key]); // don't record elements or ops if there is no term specified
                unset($conditions[substr($k, 9)]['details']['ops'][$key]);
            }
        }
      }elseif(substr($k, 0, 4) == "page") { // page must come last since those letters are common to the beginning of everything
				$pages[] = $v;
			} 
		}
    // handle "deleting" conditions...
    foreach($conditions as $pagenum=>$datapiece) {
       if(isset($datapiece['pagecons']) AND $datapiece['pagecons'] == "none") {
            $conditions[$pagenum]['details']['elements'] = array();
            $conditions[$pagenum]['details']['ops'] = array();
            $conditions[$pagenum]['details']['terms'] = array();
        }
    }
                                  
		$vars['pages'] = serialize($pages);
		$vars['pagetitles'] = serialize($pagetitles);
    $vars['conditions'] = serialize($conditions);
    
    // standard flags used by xoopsobject class
    $screen->setVar('dohtml', 0);
    $screen->setVar('doxcode', 0);
    $screen->setVar('dosmiley', 0);
    $screen->setVar('doimage', 0);
    $screen->setVar('dobr', 0);
    
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
                 $sql = sprintf("INSERT INTO %s (sid, introtext, thankstext, donedest, buttontext, pages, pagetitles, conditions, printall) VALUES (%u, %s, %s, %s, %s, %s, %s, %s, %u)", $this->db->prefix('formulize_screen_multipage'), $screen->getVar('sid'), $this->db->quoteString($screen->getVar('introtext')), $this->db->quoteString($screen->getVar('thankstext')), $this->db->quoteString($screen->getVar('donedest')), $this->db->quoteString($screen->getVar('buttontext')), $this->db->quoteString(serialize($screen->getVar('pages'))), $this->db->quoteString(serialize($screen->getVar('pagetitles'))), $this->db->quoteString(serialize($screen->getVar('conditions'))), $screen->getVar('printall')); //nmc 2007.03.24 added 'printall' & fixed pagetitles
             } else {
                 $sql = sprintf("UPDATE %s SET introtext = %s, thankstext = %s, donedest = %s, buttontext = %s, pages = %s, pagetitles = %s, conditions = %s, printall = %u WHERE sid = %u", $this->db->prefix('formulize_screen_multipage'), $this->db->quoteString($screen->getVar('introtext')), $this->db->quoteString($screen->getVar('thankstext')), $this->db->quoteString($screen->getVar('donedest')), $this->db->quoteString($screen->getVar('buttontext')), $this->db->quoteString(serialize($screen->getVar('pages'))), $this->db->quoteString(serialize($screen->getVar('pagetitles'))), $this->db->quoteString(serialize($screen->getVar('conditions'))), $screen->getVar('printall'), $screen->getVar('sid')); //nmc 2007.03.24 added 'printall'
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
    
    // standard flags used by xoopsobject class
    $screen->setVar('dohtml', 0);
    $screen->setVar('doxcode', 0);
    $screen->setVar('dosmiley', 0);
    $screen->setVar('doimage', 0);
    $screen->setVar('dobr', 0);
    
		$formframe = $screen->getVar('frid') ? $screen->getVar('frid') : $screen->getVar('fid');
		$mainform = $screen->getVar('frid') ? $screen->getVar('fid') : "";
		$pages = $screen->getVar('pages');
		$pagetitles = $screen->getVar('pagetitles');
		array_unshift($pages, ""); // displayFormPages looks for the page array to start with [1] and not [0], for readability when manually using the API.
		array_unshift($pagetitles, ""); 
		$pages['titles'] = $pagetitles;
    unset($pages[0]); // get rid of the part we just unshifted, so the page count is correct
    // setup the conditions array
    // it must be of the format $conditions[1] = array(0=>$elementsArray, 1=>$opsArray, 2=>$termsArray);
    // the key must be the numerical page number (start at 1)
    foreach($screen->getVar('conditions') as $pageid=>$condata) {
        $pagenumber = $pageid+1;
        $conditions[$pagenumber] = array(0=>$condata['details']['elements'], 1=>$condata['details']['ops'], 2=>$condata['details']['terms']);
    }
		include_once XOOPS_ROOT_PATH . "/modules/formulize/include/formdisplaypages.php";
		displayFormPages($formframe, $entry, $mainform, $pages, $conditions, $screen->getVar('introtext'), $screen->getVar('thankstext'), $screen->getVar('donedest'), $screen->getVar('buttontext'), "","", $screen->getVar('printall'), $screen); //nmc 2007.03.24 added 'printall' & 2 empty params
	}

}

function multiPageScreen_addToOptionsList($fid, $options) {
		$formObject = new formulizeForm($fid);
		$elements = $formObject->getVar('elements');
		$elementCaptions = $formObject->getVar('elementCaptions');
		for($i=0;$i<count($elements);$i++) {
			$options[$elements[$i]] = printSmart(trans(strip_tags($elementCaptions[$i]))); // need to pull out potential HTML tags from the caption
		}
		return $options;
}



// $pageNumber is the number of the current page, starting from 0 for the first one
// $pageTitle is the text of the title of this page
// $elements is the array of elements that appear on this page
// $conditions is the array of conditions for when this page should appear
// $form is the form object
// $options is the list of all available elements that go in the element section list

function drawPageUI($pageNumber, $pageTitle, $elements, $conditions, $form, $options, $ops) {

		// insert page here button
		$form->addElement(new xoopsFormButton('', 'insertpage'.$pageNumber, _AM_FORMULIZE_SCREEN_INSERTPAGE, 'submit'));

    // pageNumbers start at 0, since that's how the arrays are indexed, they start from zero
    // but whatever we show users must start at 1 (there is no page 0 as far as they are concerned), so we add one to make the visiblePageNumber
    $visiblePageNumber = $pageNumber+1;
    
    // page title
    $pageTitleBox = new xoopsFormText(_AM_FORMULIZE_SCREEN_PAGETITLE.' '.$visiblePageNumber, 'pagetitle_'.$pageNumber, 50, 255, $pageTitle);
    $form->addElement($pageTitleBox, true);
    
    // elements
    $elementSelection = new xoopsFormSelect(_AM_FORMULIZE_SCREEN_A_PAGE.' '.$visiblePageNumber.'<br><br><input type=submit name=delete'.$pageNumber.' value="'._AM_FORMULIZE_DELETE_THIS_PAGE.'" onclick="javascript:return confirmDeletePage(\''.$pageNumber.'\');">', 'page'.$pageNumber, $elements, 10, true);
    $elementSelection->addOptionArray($options);
    $form->addElement($elementSelection);

    // page conditions -- september 6 2007
    if(!isset($conditions['pagecons'])) { $allConditions['pagecons'] = 'none'; }
    $conditions = new xoopsFormElementTray(_AM_FORMULIZE_SCREEN_CONS_PAGE.' '.$visiblePageNumber, '<br />');
    $conditions->setDescription(_AM_FORMULIZE_SCREEN_CONS_HELP);
    $nocons = new xoopsFormRadio('', 'pagecons'.$pageNumber, $allConditions['pagecons']);
    $nocons->addOption('none', _AM_FORMULIZE_SCREEN_CONS_NONE);

    $conditionlist = "";
    for($z=0;$z<count($conditions['details']['elements']);$z++) {
        $form->addElement(new xoopsFormHidden('pageelements'.$pageNumber.'[]', $conditions['details']['elements'][$z]));
        $form->addElement(new xoopsFormHidden('pageops'.$pageNumber.'[]', $conditions['details']['ops'][$z]));
        $form->addElement(new xoopsFormHidden('pageterms'.$pageNumber.'[]', $conditions['details']['terms'][$z]));
        $conditionlist .= $options[$conditions['details']['elements'][$z]] . " " . $conditions['details']['ops'][$z] . " " . $conditions['details']['terms'][$z] . "<br />";
    } 
    // setup the operator boxes...
    $opterm = new xoopsFormElementTray('', "&nbsp;&nbsp;");
    $element = new xoopsFormSelect('', 'pageelements'.$pageNumber.'[]');
    $element->setExtra("onfocus=\"javascript:window.document.editscreenform.pagecons".$pageNumber."[1].checked=true\"");
    $element->addOptionArray($options);
    $op = new xoopsFormSelect('', 'pageops'.$pageNumber.'[]');
    $op->addOptionArray($ops);
    $op->setExtra("onfocus=\"javascript:window.document.editscreenform.pagecons".$pageNumber."[1].checked=true\"");
    $term = new xoopsFormText('', 'pageterms'.$pageNumber.'[]', 10, 255);
    $term->setExtra("onfocus=\"javascript:window.document.editscreenform.pagecons".$pageNumber."[1].checked=true\"");
    $opterm->addElement($element);
    $opterm->addElement($op);
    $opterm->addElement($term);
    $addcon = new xoopsFormButton('', 'addcon', _AM_FORMULIZE_SCREEN_CONS_ADDCON, 'submit');
    $addcon->setExtra("onfocus=\"javascript:window.document.editscreenform.pagecons".$pageNumber."[1].checked=true\"");
    
    $conditionui = "<br />$conditionlist<nobr>" . $opterm->render() . "</nobr><br />" . $addcon->render();
    
    $yescons = new xoopsFormRadio('', 'pagecons'.$pageNumber, $conditions['pagecons']);
    $yescons->addOption('yes', _AM_FORMULIZE_SCREEN_CONS_YES.$conditionui);
    $conditions->addElement($nocons);
    $conditions->addElement($yescons);
    $form->addElement($conditions);
		
    return $form;
}

?>