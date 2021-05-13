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


class formulizeCalendarScreen extends formulizeScreen {

	function __construct() {
		parent::__construct();
        $this->initVar("caltype", XOBJ_DTYPE_TXTBOX, NULL, false, 100);
        $this->initVar("datasets", XOBJ_DTYPE_ARRAY);
        $this->initVar("toptemplate", XOBJ_DTYPE_TXTAREA);
        $this->initVar("bottomtemplate", XOBJ_DTYPE_TXTAREA);
	}
}

class formulizeCalendarScreenDataset extends xoopsObject {

    function __construct() {
        parent::__construct();
        $this->initVar("fid", XOBJ_DTYPE_INT);
        $this->initVar("frid", XOBJ_DTYPE_INT);
        $this->initVar("scope", XOBJ_DTYPE_TXTBOX, NULL, false, 100);
        $this->initVar("useaddicons", XOBJ_DTYPE_INT);
        $this->initVar("usedeleteicons", XOBJ_DTYPE_INT);
        $this->initVar("textcolor", XOBJ_DTYPE_INT);
        $this->initVar("viewentryscreen", XOBJ_DTYPE_TXTBOX, NULL, false, 100);
        $this->initVar("clicktemplate", XOBJ_DTYPE_TXTAREA);
        $this->initVar("datehandle", XOBJ_DTYPE_TXTBOX, NULL, false, 100);
    }
    
}


class formulizeCalendarScreenHandler extends formulizeScreenHandler {
	var $db;
	function __construct(&$db) {
		$this->db =& $db;
	}
	function &getInstance(&$db) {
		static $instance;
		if (!isset($instance)) {
			$instance = new formulizeCalendarScreenHandler($db);
		}
		return $instance;
	}
	function create() {
		return new formulizeCalendarScreen();
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
		
		if (!$update) {
            $sql = sprintf("INSERT INTO %s (sid,
                caltype,
                datasets
                )
                    VALUES
                (%u, %s, %s)",
                $this->db->prefix('formulize_screen_calendar'),
                $screen->getVar('sid'),
                $this->db->quoteString($screen->getVar('caltype')),
                $this->db->quoteString(serialize($screen->getVar('datasets'))));
            } else {
            $sql = sprintf("UPDATE %s SET
                caltype = %s, datasets = %s
                WHERE sid = %u",
                $this->db->prefix('formulize_screen_calendar'),
                $this->db->quoteString($screen->getVar('caltype')),
                $this->db->quoteString(serialize($screen->getVar('datasets'))),
                $screen->getVar('sid'));
        }
        $result = $this->db->query($sql);
        if (!$result) {
            print "Error: could not save the screen properly: ".$this->db->error()." for query: $sql";
            return false;
        }
        
        $success1 = true;
        if(isset($_POST['screens-toptemplate'])) {
            $success1 = $this->writeTemplateToFile(trim($_POST['screens-toptemplate']), 'toptemplate', $screen);
        }
        $success2 = true;
        if(isset($_POST['screens-bottomtemplate'])) {
            $success2 = $this->writeTemplateToFile(trim($_POST['screens-bottomtemplate']), 'bottomtemplate', $screen);
        }
        
        if (!$success1 || !$success2) {
            return false;
        }
        
        return $sid;
	}

	// 	THIS METHOD MIGHT BE MOVED UP A LEVEL TO THE PARENT CLASS
	function get($sid) {
		$sid = intval($sid);
		if ($sid > 0) {
			$sql = 'SELECT * FROM '.$this->db->prefix('formulize_screen').' AS t1, '. $this->db->prefix('formulize_screen_calendar').' AS t2 WHERE t1.sid='.$sid.' AND t1.sid=t2.sid';
			if (!$result = $this->db->query($sql)) {
				return false;
			}
			$numrows = $this->db->getRowsNum($result);
			if ($numrows == 1) {
				$screen = new formulizeCalendarScreen();
                $result = $this->db->fetchArray($result);
                $screen->assignVars($result);
				return $screen;
			}
		}
		return false;

	}

	// THIS METHOD HANDLES ALL THE LOGIC ABOUT HOW TO ACTUALLY DISPLAY THIS TYPE OF SCREEN
	// $screen is a screen object
    // $settings is used internally to pass list of entries settings back and forth to editing screens
    function render($screen) {

        $previouslyRenderingScreen = $GLOBALS['formulize_screenCurrentlyRendering'];
    
        $formframes = array();
        $mainforms = array();
        $dateHandles = array();
        $clickTemplates = array();
        $scopes = array();
        $hidden = null;
        $type = $screen->getVar('caltype'); // needs to be made editable in UI!! ****************
    
        foreach($screen->getVar('datasets') as $i=>$dataset) {
            $formframes[$i] = $dataset->getVar('frid') ? $dataset->getVar('frid') : $screen->getVar('frid') ? $screen->getVar('frid') : $screen->getVar('fid');
            $mainforms[$i] = $dataset->getVar('fid') ? $dataset->getVar('fid') : $screen->getVar('frid') ? $screen->getVar('fid') : '';
            $clickTemplates[$i] = $dataset->getVar('clicktemplate');
            $dateHandles[$i] = $dataset->getVar('datehandle');
            $scopes[$i] = $dataset->getVar('scope');
            $viewentryscreens[$i] = $dataset->getVar('viewentryscreen');
            $useaddicons[$i] = $dataset->getVar('useaddicons');
            $usedeleteicons[$i] = $dataset->getVar('usedeleteicons');
            $textcolors[$i] = $dataset->getVar('textcolor');
        }

        include_once XOOPS_ROOT_PATH.'/modules/formulize/include/calendardisplay.php';
        include_once XOOPS_ROOT_PATH.'/modules/formulize/include/entriesdisplay.php';
        
        global $xoopsConfig;
        $theme = $xoopsConfig['theme_set'];
        
        ob_start();
        eval(substr(file_get_contents(XOOPS_ROOT_PATH.'/modules/formulize/templates/screens/'.$theme.'/'.$screen->getVar('sid').'/toptemplate.php'), 5));
        $toptemplate = ob_get_clean();
        ob_start();
        eval(substr(file_get_contents(XOOPS_ROOT_PATH.'/modules/formulize/templates/screens/'.$theme.'/'.$screen->getVar('sid').'/bottomtemplate.php'), 5));
        $bottomtemplate = ob_get_clean();
     
        $GLOBALS['formulize_screenCurrentlyRendering'] = $screen;
        displayCalendar($formframes, $mainforms, $viewHandles, $dateHandles, $filters, $clickTemplates, $scopes, $hidden, $type="month", $toptemplate, $bottomtemplate, $viewentryscreens, $useaddicons, $usedeleteicons, $textcolors);
        $GLOBALS['formulize_screenCurrentlyRendering'] = $previouslyRenderingScreen;
        
	}

	    // THIS METHOD CLONES A FORM_SCREEN
    function cloneScreen($sid) {

        $newtitle = parent::titleForClonedScreen($sid);

        $newsid = parent::insertCloneIntoScreenTable($sid, $newtitle);

        if (!$newsid) {
            return false;
        }

        $tablename = "formulize_screen_calendar";
        $result = parent::insertCloneIntoScreenTypeTable($sid, $newsid, $newtitle, $tablename);

        if (!$result) {
            return false;
        }
    }

}
?>
