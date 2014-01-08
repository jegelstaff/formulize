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
class formulizeScreen extends xoopsObject {

	function formulizeScreen() {
		$this->XoopsObject();
		$this->initVar('sid', XOBJ_DTYPE_INT, '', true);
		$this->initVar('title', XOBJ_DTYPE_TXTBOX, '', true, 255);
		$this->initVar('fid', XOBJ_DTYPE_INT, '', true);
		$this->initVar('frid', XOBJ_DTYPE_INT, '', true);
		$this->initVar('type', XOBJ_DTYPE_TXTBOX, '', true, 100);
		$this->initVar('useToken', XOBJ_DTYPE_INT);
	}

    static function normalize_values($key, $value) {
        switch ($key) {
            case "customactions":
            case "decolumns":
            case "hiddencolumns":
            if (!is_array(unserialize($value))) {
                $value = serialize(array());
            }
            break;
        }
        return $value;
    }

    public function assignVar($key, $value) {
        parent::assignVar($key, self::normalize_values($key, $value));
    }

    public function setVar($key, $value, $not_gpc = false) {
        parent::setVar($key, self::normalize_values($key, $value), $not_gpc);
    }

    public function form_id() {
        return $this->getVar("fid");
    }

    public function relationship_id() {
        return $this->getVar("frid");
    }

    function __get($name) {
        if (!isset($this->$name)) {
            if (method_exists($this, $name)) {
                $this->$name = $this->$name();
            } else {
                $this->$name = $this->getVar($name);
            }
        }
        return $this->$name;
    }


    function getTemplate($templatename) {
        static $templates = array();
        if (!isset($templates[$templatename])) {
            // there is no template saved in memory, read it from the file
            $pathname = XOOPS_ROOT_PATH."/modules/formulize/templates/screens/default/".$this->getVar('sid')."/".$templatename.".php";
            if (file_exists($pathname)) {
                $templates[$templatename] = file_get_contents($pathname);
                // strip out opening <?php since we use this value for comparisons a lot, and it should be otherwise empty in that case
                $templates[$templatename] = substr($templates[$templatename], 5);
            } else {
                $templates[$templatename] = null;
            }
        }
        return $templates[$templatename];
    }
}


class formulizeScreenHandler {
	var $db;
	function formulizeScreenHandler(&$db) {
		$this->db =& $db;
	}
	function &getInstance(&$db) {
		static $instance;
		if (!isset($instance)) {
			$instance = new formulizeScreenHandler($db);
		}
		return $instance;
	}
	function &create() {
		return new formulizeScreen();
	}

    // returns an array of screen objects
    function &getObjects($criteria = null, $fid) {
        $sql = "SELECT * FROM " . $this->db->prefix("formulize_screen");
        if(is_object($criteria)) {
            $sql .= " WHERE " . $criteria->render();
            if (intval($fid) > 0) {
                $sql .= " AND fid=" . intval($fid);
            }
        } else {
            if (intval($fid) > 0) {
                $sql .= " WHERE fid=" . intval($fid);
            }
        }
        $sql .= " order by fid, title";
        if(!$result = $this->db->query($sql)) {
            return false;
        }
        while($array = $this->db->fetchArray($result)) {
            $screen = $this->create();
            $screen->assignVars($array);
            $screens[] = $screen;
            unset($screen);
        }
        return $screens;
    }

	function get($sid) {
		$sid = intval($sid);
		if ($sid > 0) {
			$sql = 'SELECT * FROM '.$this->db->prefix('formulize_screen').' WHERE sid='.$sid;
			if (!$result = $this->db->query($sql)) {
				return false;
			}
			$numrows = $this->db->getRowsNum($result);
			if ($numrows == 1) {
				$screen = new formulizeScreen();
				$screen->assignVars($this->db->fetchArray($result));
				return $screen;
			}
		}
		return false;

	}


	function delete($sid, $type) {
 		$sql1 = "DELETE FROM " . $this->db->prefix("formulize_screen") . " WHERE sid=" . intval($sid);
		$sql2 = "DELETE FROM " . $this->db->prefix("formulize_screen_".strtolower($type)) . " WHERE sid=" . intval($sid);
		if(!$result = $this->db->query($sql1)) {
			return false;
		}
		if(!$result = $this->db->query($sql2)) {
			return false;
		}
		return true;
	}

	// this function handles all the admin side ui for the common parts of the edit screen
	function editForm($screen, $fid) {

		// provide ui for title, ui for frid, hidden fid, hidden sid
		include_once XOOPS_ROOT_PATH."/class/xoopsformloader.php";
		$form = new XoopsThemeForm(_AM_FORMULIZE_SCREEN_FORM, "editscreenform", "editscreen.php");
		$form->addElement(new xoopsFormHidden('fid', $fid));
		$title = is_object($screen) ? $screen->getVar('title') : "";
		$sid = is_object($screen) ? $screen->getVar('sid') : 0;
		$frid = is_object($screen) ? $screen->getVar('frid') : 0;
		$form->addElement(new xoopsFormHidden('sid', $sid));
		$form->addElement(new xoopsFormHidden('oneditscreen', 1));
		$form->addElement(new xoopsFormText(_AM_FORMULIZE_SCREEN_TITLE, 'title', 30, 255, $title));

		// get the frameworks that this form is involved in
		$framework_handler =& xoops_getmodulehandler('frameworks', 'formulize');
		$frameworks = $framework_handler->getFrameworksByForm($fid);
		$options[0] = _AM_FORMULIZE_USE_NO_FRAMEWORK;
		foreach($frameworks as $thisFramework) {
        		$options[$thisFramework->getVar('frid')] = $thisFramework->getVar('name');
		}
		$frameworkChoice = new xoopsFormSelect(_AM_FORMULIZE_SELECT_FRAMEWORK, 'frid', $frid, 1, false);
		$frameworkChoice->setExtra("onchange='javascript:frameworkChange(window.document.editscreenform.frid)'"); // set a javascript event for this element in case parts of some screen forms change depending on the framework selected
		$frameworkChoice->addOptionArray($options);
		$form->addElement($frameworkChoice);
    
    // show the security token question -- added Jan 25 2008 -- jwe
    $useTokenDefault = $screen->getVar('sid') ? $screen->getVar('useToken') : 1;
    $securityQuestion = new xoopsFormRadioYN(_AM_FORMULIZE_SCREEN_SECURITY, 'useToken', $useTokenDefault);
    $securityQuestion->setDescription(_AM_FORMULIZE_SCREEN_SECURITY_DESC);
    $form->addElement($securityQuestion);
		return $form;
	}

	// TO BE CALLED FROM WITHIN THE CHILD CLASS AND THEN RETURNS SCREEN OBJECT, WHICH WILL HAVE THE CORRECT sid IN PLACE NOW
	function insert($screen) {
		if (!is_subclass_of($screen, 'formulizeScreen')) {
                 return false;
            }
             if (!$screen->cleanVars()) {
                 return false;
             }
             foreach ($screen->cleanVars as $k => $v) {
                 ${$k} = $v;
             }
             if ($sid == 0) {
                 //$sid = $this->db->genId($this->db->prefix('formulize_screen').'_sid_seq'); // mysql compatiblity layer just returns 0 here
                 $sql = sprintf("INSERT INTO %s (title, fid, frid, type, useToken) VALUES (%s, %u, %u, %s, %u)", $this->db->prefix('formulize_screen'), $this->db->quoteString($title), $fid, $frid, $this->db->quoteString($type), $useToken);
             } else {
                 $sql = sprintf("UPDATE %s SET title = %s, fid = %u, frid = %u, type = %s, useToken = %u WHERE sid = %u", $this->db->prefix('formulize_screen'), $this->db->quoteString($title), $fid, $frid, $this->db->quoteString($type), $useToken, $sid);
             }
		 $result = $this->db->query($sql);
             if (!$result) {
                 return false;
             }
             if ($sid == 0) {
                 $sid = $this->db->getInsertId();
             }
		 return $sid;
	}

	function writeTemplateToFile($text, $filename, $screen) {

            $pathname = XOOPS_ROOT_PATH."/modules/formulize/templates/screens/default/".$screen->getVar('sid')."/";
            // check if folder exists, if not, make it.
            if (!is_dir($pathname)) {
                mkdir($pathname, 0777, true);
            }
            
            if (!is_writable($pathname)) {
                chmod($pathname, 0777);
	    }

            $fileHandle = fopen($pathname."/".$filename.".php", "w+");
            $success = fwrite($fileHandle, $text);
            fclose($fileHandle);
            
            // return true or false based on writing success or failure 
            // (you'll need to make sure your web server has write permission in the /templates/screens/default/ folder
            if ($success === FALSE) {
                return false;
            } else return true;
        }
	
}
