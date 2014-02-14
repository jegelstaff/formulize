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

	function formulizeFormScreen() {
		$this->formulizeScreen();
		$this->initVar("donedest", XOBJ_DTYPE_TXTBOX, NULL, false, 255);
		$this->initVar("savebuttontext", XOBJ_DTYPE_TXTBOX, NULL, false, 255);
		$this->initVar("alldonebuttontext", XOBJ_DTYPE_TXTBOX, NULL, false, 255);
    $this->initVar('displayheading', XOBJ_DTYPE_INT);
    $this->initVar('reloadblank', XOBJ_DTYPE_INT);
    $this->initVar('formelements', XOBJ_DTYPE_ARRAY);
	}
}

class formulizeFormScreenHandler extends formulizeScreenHandler {
	var $db;
	function formulizeFormScreenHandler(&$db) {
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
            $sql = sprintf("INSERT INTO %s (sid, donedest, savebuttontext, alldonebuttontext, displayheading, reloadblank, formelements) VALUES (%u, %s, %s, %s, %u, %u, %s)", $this->db->prefix('formulize_screen_form'), $screen->getVar('sid'), $this->db->quoteString($screen->getVar('donedest')), $this->db->quoteString($screen->getVar('savebuttontext')), $this->db->quoteString($screen->getVar('alldonebuttontext')), $screen->getVar('displayheading'), $screen->getVar('reloadblank'), $this->db->quoteString(serialize($screen->getVar('formelements'))));
        } else {
            $sql = sprintf("UPDATE %s SET donedest = %s, savebuttontext = %s, alldonebuttontext = %s, displayheading = %u, reloadblank = %u, formelements = %s WHERE sid = %u", $this->db->prefix('formulize_screen_form'), $this->db->quoteString($screen->getVar('donedest')), $this->db->quoteString($screen->getVar('savebuttontext')), $this->db->quoteString($screen->getVar('alldonebuttontext')), $screen->getVar('displayheading'), $screen->getVar('reloadblank'), $this->db->quoteString(serialize($screen->getVar('formelements'))), $screen->getVar('sid'));
        }
        $result = $this->db->query($sql);
        if (!$result) {
            print "Error: could not save the screen properly: ".$xoopsDB->error()." for query: $sql";
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
    function render($screen, $entry, $settings = "", $elements_only = false) {
		if(!is_array($settings)) {
				$settings = "";
		}
		$formframe = $screen->getVar('frid') ? $screen->getVar('frid') : $screen->getVar('fid');
		$mainform = $screen->getVar('frid') ? $screen->getVar('fid') : "";
		$donedest = $screen->getVar("donedest");
		$savebuttontext = $screen->getVar("savebuttontext");
		$savebuttontext = $savebuttontext ? $savebuttontext : _formulize_SAVE;
		$alldonebuttontext = $screen->getVar("alldonebuttontext");
		$alldonebuttontext = $alldonebuttontext ? $alldonebuttontext : "{NOBUTTON}";
		$displayheading = $screen->getVar('displayheading');
		$displayheading = $displayheading ? "" : "all"; // if displayheading is off, then need to pass the "all" keyword to supress all the headers
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
		displayForm($formframe, $entry, $mainform, $donedest, array(0=>$alldonebuttontext, 1=>$savebuttontext),
            $settings, $displayheading, "", $overrideMulti, "", 0, 0, 0, $screen, $elements_only);
	}
}
?>
