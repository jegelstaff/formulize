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


class formulizeFormScreen extends formulizeScreen {

	function __construct() {
		parent::__construct();
		$this->initVar("donedest", XOBJ_DTYPE_TXTBOX, NULL, false, 255);
		$this->initVar("savebuttontext", XOBJ_DTYPE_TXTBOX, NULL, false, 255);
        $this->initVar("saveandleavebuttontext", XOBJ_DTYPE_TXTBOX, NULL, false, 255);
        $this->initVar("printableviewbuttontext", XOBJ_DTYPE_TXTBOX, NULL, false, 255);
		$this->initVar("alldonebuttontext", XOBJ_DTYPE_TXTBOX, NULL, false, 255);
    $this->initVar('displayheading', XOBJ_DTYPE_INT);
    $this->initVar('reloadblank', XOBJ_DTYPE_INT);
    $this->initVar('formelements', XOBJ_DTYPE_ARRAY);
    $this->initVar('elementdefaults', XOBJ_DTYPE_ARRAY);
    $this->initVar('displaycolumns', XOBJ_DTYPE_INT);
    $this->initVar("column1width", XOBJ_DTYPE_TXTBOX, NULL, false, 255);
    $this->initVar("column2width", XOBJ_DTYPE_TXTBOX, NULL, false, 255);
    $this->initVar("displayType", XOBJ_DTYPE_TXTBOX, NULL, false, 255);
	}
    
    function elementIsPartOfScreen($elementObjectOrId) {
        if(!$element = _getElementObject($elementObjectOrId)) {
            return false;
        }
        if(!is_array($this->getVar('formelements')) OR count($this->getVar('formelements')) == 0 OR in_array($element->getVar('ele_id'), $this->getVar('formelements'))) {
            return true;
        }
        return false;
    }
    
    // return the displayType setting, unless there is no element container template, then use table-row which matches the default templates
    function getDisplayType() {
        if($this->getTemplate('elementcontainero')) {
            return $this->getVar('displayType');
        } else {
            return 'table-row';
        }
    }
    

    
}

class formulizeFormScreenHandler extends formulizeScreenHandler {
	var $db;
	function __construct(&$db) {
		$this->db =& $db;
	}
	function &getInstance(&$db) {
		static $instance;
		if (!isset($instance)) {
			$instance = new formulizeFormScreenHandler($db);
		}
		return $instance;
	}
	function &create() {
		return new formulizeFormScreen();
	}


	function insert($screen) {
		$update = ($screen->getVar('sid') == 0) ? false : true;
		if(!$sid = parent::insert($screen)) { // write the basic info to the db, handle cleaning vars and all that jazz.  Object passed by reference, so updates will have affected it in the other method.
			return false;
		}
		$screen->assignVar('sid', $sid);
		// standard flags used by xoopsobject class
	    $screen->setVar('dohtml', 0);
	    $screen->setVar('doxcode', 0);
	    $screen->setVar('dosmiley', 0);
	    $screen->setVar('doimage', 0);
	    $screen->setVar('dobr', 0);
		// note: conditions is not written to the DB yet, since we're not gathering that info from the UI	
		if (!$update) {
            $sql = sprintf("INSERT INTO %s (sid, donedest, savebuttontext, saveandleavebuttontext, printableviewbuttontext, alldonebuttontext, displayheading, reloadblank, formelements, elementdefaults, displaycolumns, column1width, column2width, displayType)
                VALUES (%u, %s, %s, %s, %s, %s, %u, %u, %s, %s, %u, %s, %s, %s)",
                $this->db->prefix('formulize_screen_form'),
                $screen->getVar('sid'),
                $this->db->quoteString($screen->getVar('donedest')),
                $this->db->quoteString($screen->getVar('savebuttontext')),
                $this->db->quoteString($screen->getVar('saveandleavebuttontext')),
                $this->db->quoteString($screen->getVar('printableviewbuttontext')),
                $this->db->quoteString($screen->getVar('alldonebuttontext')),
                $screen->getVar('displayheading'),
                $screen->getVar('reloadblank'),
                $this->db->quoteString(serialize($screen->getVar('formelements'))), 
                $this->db->quoteString(serialize($screen->getVar('elementdefaults'))),
                $screen->getVar('displaycolumns'),
                $this->db->quoteString($screen->getVar('column1width')),
                $this->db->quoteString($screen->getVar('column2width')),
                $this->db->quoteString($screen->getVar('displayType'))
                ); // need to use serialize because cleanvars not called on object, and that would transparently serialize arrays in the background. Ugh.
        } else {
            $sql = sprintf("UPDATE %s SET donedest = %s, savebuttontext = %s, saveandleavebuttontext = %s, printableviewbuttontext = %s, alldonebuttontext = %s, displayheading = %u, reloadblank = %u, formelements = %s, elementdefaults = %s, displaycolumns = %u, column1width = %s, column2width = %s, displayType = %s
                WHERE sid = %u",
                $this->db->prefix('formulize_screen_form'),
                $this->db->quoteString($screen->getVar('donedest')),
                $this->db->quoteString($screen->getVar('savebuttontext')),
                $this->db->quoteString($screen->getVar('saveandleavebuttontext')),
                $this->db->quoteString($screen->getVar('printableviewbuttontext')),
                $this->db->quoteString($screen->getVar('alldonebuttontext')),
                $screen->getVar('displayheading'), $screen->getVar('reloadblank'),
                $this->db->quoteString(serialize($screen->getVar('formelements'))),
                $this->db->quoteString(serialize($screen->getVar('elementdefaults'))),
                $screen->getVar('displaycolumns'),
                $this->db->quoteString($screen->getVar('column1width')),
                $this->db->quoteString($screen->getVar('column2width')),
                $this->db->quoteString($screen->getVar('displayType')),
                $screen->getVar('sid'));
        }
        $result = $this->db->query($sql);
        if (!$result) {
            print "Error: could not save the screen properly: ".$this->db->error()." for query: $sql";
            return false;
        }
        
        $success1 = true;
        if(isset($_POST['toptemplate'])) {
            $success1 = $this->writeTemplateToFile(trim($_POST['toptemplate']), 'toptemplate', $screen);
        }
        $success2 = true;
        if(isset($_POST['elementtemplate1'])) {
            $success2 = $this->writeTemplateToFile(trim($_POST['elementtemplate1']), 'elementtemplate1', $screen);
        }
        $success3 = true;
        if(isset($_POST['elementtemplate2'])) {
            $success3 = $this->writeTemplateToFile(trim($_POST['elementtemplate2']), 'elementtemplate2', $screen);
        }
        $success4 = true;
        if(isset($_POST['bottomtemplate'])) {
            $success4 = $this->writeTemplateToFile(trim($_POST['bottomtemplate']), 'bottomtemplate', $screen);
        }
        $success5 = true;
        if(isset($_POST['elementcontainerc'])) {
            $success3 = $this->writeTemplateToFile(trim($_POST['elementcontainerc']), 'elementcontainerc', $screen);
        }
        $success6 = true;
        if(isset($_POST['elementcontainero'])) {
            $success4 = $this->writeTemplateToFile(trim($_POST['elementcontainero']), 'elementcontainero', $screen);
        }

        if (!$success1 || !$success2 || !$success3 || !$success4 || !$success5 || !$success6) {
            return false;
        }
        
        return $sid;
	}

	// 	THIS METHOD MIGHT BE MOVED UP A LEVEL TO THE PARENT CLASS
	function get($sid) {
		$sid = intval($sid);
		if ($sid > 0) {
			$sql = 'SELECT * FROM '.$this->db->prefix('formulize_screen').' AS t1, '. $this->db->prefix('formulize_screen_form').' AS t2 WHERE t1.sid='.$sid.' AND t1.sid=t2.sid';
			if (!$result = $this->db->query($sql)) {
				return false;
			}
			$numrows = $this->db->getRowsNum($result);
			if ($numrows == 1) {
				$screen = new formulizeFormScreen();
				$screen->assignVars($this->db->fetchArray($result));
				return $screen;
			}
		}
		return false;

	}

	// THIS METHOD HANDLES ALL THE LOGIC ABOUT HOW TO ACTUALLY DISPLAY THIS TYPE OF SCREEN
	// $screen is a screen object
    // $settings is used internally to pass list of entries settings back and forth to editing screens
    function render($screen, $entry, $settings = array(), $elements_only = false) {
        $previouslyRenderingScreen = $GLOBALS['formulize_screenCurrentlyRendering'];
		if(!is_array($settings)) {
				$settings = array();
		}
		$formframe = $screen->getVar('frid') ? $screen->getVar('frid') : $screen->getVar('fid');
		$mainform = $screen->getVar('frid') ? $screen->getVar('fid') : "";
		$donedest = $screen->getVar("donedest");
		$savebuttontext = $screen->getVar("savebuttontext");
        $saveandleavebuttontext = $screen->getVar("saveandleavebuttontext");
		$alldonebuttontext = $screen->getVar("alldonebuttontext");
        $printableviewbuttontext = $screen->getVar("printableviewbuttontext");
		$savebuttontext = $savebuttontext ? $savebuttontext : "{NOBUTTON}";
        $saveandleavebuttontext = $saveandleavebuttontext ? $saveandleavebuttontext : "{NOBUTTON}";
		$alldonebuttontext = $alldonebuttontext ? $alldonebuttontext : "{NOBUTTON}";
        $printableviewbuttontext = $printableviewbuttontext ? $printableviewbuttontext : "{NOBUTTON}";
        
		$displayheading = $screen->getVar('displayheading');
		$displayheading = $displayheading ? "" : "all"; // if displayheading is off, then need to pass the "all" keyword to supress all the headers
		$displayheading = $elements_only ? "formElementsOnly" : $displayheading;
		$reloadblank = $screen->getVar('reloadblank');
		// figure out the form's properties...
		// if it's more than one entry per user, and we have requested reload blank, then override multi is 0, otherwise 1
		// if it's one entry per user, and we have requested reload blank, then override multi is 1, otherwise 0
		$form_handler = xoops_getmodulehandler('forms', 'formulize');
		$formObject = $form_handler->get($screen->getVar('fid'));
		if($formObject->getVar('single')=="off" AND $reloadblank) { 
			$overrideMulti = 0;
		} elseif($formObject->getVar('single')=="off" AND !$reloadblank) {
			$overrideMulti = 1;
		} elseif(($formObject->getVar('single')=="group" OR $formObject->getVar('single')=="user") AND $reloadblank) {
			$overrideMulti = 1;
		} elseif(($formObject->getVar('single')=="group" OR $formObject->getVar('single')=="user") AND !$reloadblank) {
			$overrideMulti = 0;
		} else {
			$overrideMulti = 0;
		}
		include_once XOOPS_ROOT_PATH . "/modules/formulize/include/formdisplay.php";
        $GLOBALS['formulize_screenCurrentlyRendering'] = $screen;
		displayForm($formframe, $entry, $mainform, $donedest, array(0=>$alldonebuttontext, 1=>$savebuttontext, 2=>$saveandleavebuttontext, 3=>$printableviewbuttontext),
            $settings, $displayheading, "", $overrideMulti, "", 0, 0, 0, $screen);
        $GLOBALS['formulize_screenCurrentlyRendering'] = $previouslyRenderingScreen;
	}

	function _getElementsForScreen($fid, $options) {
	    $formObject = new formulizeForm($fid, true); // true causes all elements, even ones now shown to any user, to be included
	    $elements = $formObject->getVar('elements');
	    $elementCaptions = $formObject->getVar('elementCaptions');
	    foreach($elementCaptions as $key=>$elementCaption) {
	      $options[$elements[$key]] = printSmart(trans(strip_tags($elementCaption))); // need to pull out potential HTML tags from the caption
	    }
	    return $options;
	}

	public function getSelectedElementsForScreen($sid) {
		$screen_handler = xoops_getmodulehandler('formScreen', 'formulize');
    	$screen = $screen_handler->get($sid);
    	$elements = $screen->getVar('formelements');
        if (!is_array($elements)) {
            // this is always expected to be an array, so make sure it is
            $elements = array();
        }
    	return $elements;
	}

	public function getScreensForElement($fid) {

		$screens = array();
		$screen_handler = xoops_getmodulehandler('screen', 'formulize');
		$criteria_object = new CriteriaCompo(new Criteria('type','form'));
		$formScreens = $screen_handler->getObjects($criteria_object,$fid);
		foreach($formScreens as $screen) {
			$sid = $screen->getVar('sid');
		  	$screens[$sid]['sid'] = $screen->getVar('sid');
		  	$screens[$sid]['title'] = $screen->getVar('title');
		  	$screens[$sid]['type'] = $screen->getVar('type');
		}
		return $screens;
	}

	public function getMultiScreens($fid) {

		$screens = array();
		$screen_handler = xoops_getmodulehandler('multiPageScreen', 'formulize');
		$criteria_object = new CriteriaCompo(new Criteria('type','multiPage'));
		$formScreens = $screen_handler->getObjects($criteria_object,$fid);
		foreach($formScreens as $screen) {
			$sid = $screen->getVar('sid');
			$screenData = $screen_handler->get($sid);	
		  	$screens[$sid]['sid'] = $screenData->getVar('sid');
		  	$screens[$sid]['title'] = $screenData->getVar('title');
		  	$screens[$sid]['type'] = $screenData->getVar('type');
		  	$screens[$sid]['pages'] = $screenData->getVar('pages');
		  	$screens[$sid]['pagetitles'] = $screenData->getVar('pagetitles');
		}
		return $screens;
	}	

	public function getSelectedScreens($fid) {
		$selected_screens = array();
		$screen_handler = xoops_getmodulehandler('screen', 'formulize');
		$criteria_object = new CriteriaCompo(new Criteria('type','form'));
		$formScreens = $screen_handler->getObjects($criteria_object,$fid);
		foreach($formScreens as $screen) {
			$sid = $screen->getVar('sid');

	  		// See if this element is being selected by the screen(s) already
			$selected_elements = $this->getSelectedElementsForScreen($sid);
			if (in_array($_GET['ele_id'], $selected_elements)) {
				$selected_screens[$sid] = " selected";
			}
		}

		return $selected_screens;
	}

	public function getSelectedScreensForNewElement() {
		global $xoopsDB;
		$selected_screens = array();
		$screenElementsSQL =   "SELECT sid, formelements
								FROM " . $xoopsDB->prefix("formulize_screen_form") .
							   " WHERE formid = " .$_GET['fid'];
		$screenElementsResult = $xoopsDB->query($screenElementsSQL);

		while ($screenElements = $xoopsDB->fetchArray($screenElementsResult)){
			$selected_elements = unserialize($screenElements['formelements']);
			if (is_array($selected_elements) AND count($selected_elements)>0){
				$this_screen_id = $screenElements['sid'];
				//space before 'selected' is needed as this is output directly into html
				$selected_screens[$this_screen_id] = " selected";
				break;
			}
		}
		return $selected_screens;
	}

    // THIS METHOD CLONES A FORM_SCREEN
    function cloneScreen($sid) {

        $newtitle = parent::titleForClonedScreen($sid);

        $newsid = parent::insertCloneIntoScreenTable($sid, $newtitle);

        if (!$newsid) {
            return false;
        }

        $tablename = "formulize_screen_form";
        $result = parent::insertCloneIntoScreenTypeTable($sid, $newsid, $newtitle, $tablename);

        if (!$result) {
            return false;
        }
    }

	public function setDefaultFormScreenVars($defaultFormScreen, $title, $fid)
	{
		$defaultFormScreen->setVar('displayheading', 1);
		$defaultFormScreen->setVar('reloadblank', 0);
		$defaultFormScreen->setVar('savebuttontext', _formulize_SAVE);
		$defaultFormScreen->setVar('alldonebuttontext', _formulize_DONE);
		$defaultFormScreen->setVar('title', "Regular '$title'");
		$defaultFormScreen->setVar('fid', $fid);
		$defaultFormScreen->setVar('frid', 0);
		$defaultFormScreen->setVar('type', 'form');
		$defaultFormScreen->setVar('useToken', 1);
	}

}
?>
