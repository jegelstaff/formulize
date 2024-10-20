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
class formulizeScreen extends FormulizeObject {

	function __construct() {
        parent::__construct();
		$this->initVar('sid', XOBJ_DTYPE_INT, '', true);
        $this->initVar('screen_handle', XOBJ_DTYPE_TXTBOX, '', 255);
		$this->initVar('title', XOBJ_DTYPE_TXTBOX, '', true, 255);
		$this->initVar('fid', XOBJ_DTYPE_INT, '', true);
		$this->initVar('frid', XOBJ_DTYPE_INT, '', true);
		$this->initVar('type', XOBJ_DTYPE_TXTBOX, '', true, 100);
		$this->initVar('useToken', XOBJ_DTYPE_INT);
        $this->initVar('anonNeedsPasscode', XOBJ_DTYPE_INT);
        $this->initVar('theme', XOBJ_DTYPE_TXTBOX, '', true, 100);
		$this->initVar('rewriteruleAddress', XOBJ_DTYPE_TXTBOX, '', false, 255);
        $this->initVar('rewriteruleElement', XOBJ_DTYPE_INT, '', true);
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

    // get the default template for this screen, either for theme or site wide
	function getDefaultTemplate($templateName, $theme="") {
		return getDefaultTemplate($templateName, $this->getVar('type'), $theme);
	}

    // get a custom template specified by admin user for this screen
    function getTemplate($templateName, $theme="") {
        if(!$theme) {
            global $xoopsConfig;
            $theme = $xoopsConfig['theme_set'];
        }
        static $templates = array();
        if (!isset($templates[$theme][$this->getVar('sid')][$templateName])) {
            // there is no template saved in memory, read it from the file
            $pathname = XOOPS_ROOT_PATH."/modules/formulize/templates/screens/".$theme."/".$this->getVar('sid')."/".$templateName.".php";
            if (file_exists($pathname)) {
                $templates[$theme][$this->getVar('sid')][$templateName] = file_get_contents($pathname);
            } else {
                $templates[$theme][$this->getVar('sid')][$templateName] = htmlspecialchars_decode($this->getVar($templateName), ENT_QUOTES);
                if (strlen($templates[$theme][$this->getVar('sid')][$templateName]) > 0) {
                    // the template content is stored in the database, but not the cache file
                    // database may have been copied from another site, so write to cache file
                    // Or, user has switched themes, so we initialze the theme file based on the last loaded theme file that was written to DB
                    $this->writeTemplateFile(htmlspecialchars_decode($templates[$theme][$this->getVar('sid')][$templateName], ENT_QUOTES), $templateName, $theme);
                }
            }
        }
        return $templates[$theme][$this->getVar('sid')][$templateName];
    }

    function writeTemplateFile($template_content, $template_name, $theme="") {
        if(!$theme) {
            global $xoopsConfig;
            $theme = $xoopsConfig['theme_set'];
        }
        $pathname = XOOPS_ROOT_PATH."/modules/formulize/templates/screens/".$theme."/".$this->getVar('sid')."/";
        // check if folder exists, if not, make it.
        if (!is_dir($pathname)) {
            mkdir($pathname, 0777, true);
        }

        if (!is_writable($pathname)) {
            chmod($pathname, 0777);
        }

        $filename = $pathname."/".$template_name.".php";

        $success = file_put_contents($filename, $template_content);
        if (false === $success) {
            error_log("ERROR: Could not write to template cache file: $filename");
            return false;
        }

        return true;
    }

}


class formulizeScreenHandler {
	var $db;
	function __construct(&$db) {
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


	// check to see if a handle is unique within a form
	function isScreenHandleUnique($handle, $screen_id="") {
		$handle = formulizeScreen::sanitize_handle_name($handle);
		global $xoopsDB;
		$screen_id_condition = $screen_id ? " AND sid != " . intval($screen_id) : "";
		$sql = "SELECT count(screen_handle) FROM " . $xoopsDB->prefix("formulize_screen") . " WHERE screen_handle = '" . formulize_db_escape($handle) . "' $screen_id_condition";
		if(!$res = $xoopsDB->query($sql)) {
			print "Error: could not verify uniqueness of handle '$handle'";
		} else {
			$row = $xoopsDB->fetchRow($res);
			if($row[0] == 0) { // zero rows found with that handle in this form
				return true;
			} else {
				return false;
			}
		}
	}

	function makeHandleUnique($handle, $sid) {
		$firstUniqueCheck = true;
		$handle = formulizeScreen::sanitize_handle_name($handle);
		while (!$uniqueCheck = $this->isScreenHandleUnique($handle, $sid)) {
			if ($firstUniqueCheck AND $sid) {
				$handle = $handle . "_".$sid;
				$firstUniqueCheck = false;
			} else {
				$handle = $handle . "_copy";
			}
		}
		return $handle;
	}

	// returns an array of screen objects
    function &getObjects($criteria = null, $fid = 0, $appid = -1, $sort = null, $order = null, $paged = false, $offset = -1, $limit = 20) {
        $sql = "SELECT * FROM " . $this->db->prefix("formulize_screen") . " AS screentable";
        if(is_object($criteria)) {
            $sql .= " WHERE " . $criteria->render();
            if (intval($fid) > 0) {
                $sql .= " AND fid=" . intval($fid);
            }
            if ($appid > 0) {
            	$sql .= " AND EXISTS(SELECT 1 FROM ".$this->db->prefix("formulize_application_form_link")." as linktable WHERE linktable.appid=" . $appid . " AND linktable.fid=screentable.fid)";
            } else if ($appid === 0) {
            	$sql .= " AND NOT EXISTS(SELECT 1 FROM ".$this->db->prefix("formulize_application_form_link")." as linktable WHERE linktable.appid>" . $appid . " AND linktable.fid=screentable.fid)";
            }
        } else {
            if (intval($fid) > 0) {
                $sql .= " WHERE fid=" . intval($fid);

                if ($appid > 0) {
                	$sql .= " AND EXISTS(SELECT 1 FROM ".$this->db->prefix("formulize_application_form_link")." as linktable WHERE linktable.appid=" . $appid . " AND linktable.fid=screentable.fid)";
                } else if ($appid === 0) {
            		$sql .= " AND NOT EXISTS(SELECT 1 FROM ".$this->db->prefix("formulize_application_form_link")." as linktable WHERE linktable.appid>" . $appid . " AND linktable.fid=screentable.fid)";
            	}
            } else if ($appid > 0) {
            	$sql .= " WHERE EXISTS(SELECT 1 FROM ".$this->db->prefix("formulize_application_form_link")." as linktable WHERE linktable.appid=" . $appid . " AND linktable.fid=screentable.fid)";
            } else if ($appid === 0) {
            	$sql .= " WHERE NOT EXISTS(SELECT 1 FROM ".$this->db->prefix("formulize_application_form_link")." as linktable WHERE linktable.appid>" . $appid . " AND linktable.fid=screentable.fid)";
            }
        }
        if($sort == null) {
        	$sort = "fid, title";
        }
        if($order == null) {
        	$order = "ASC";
        }
        $sql .= " order by " . $sort . " " . $order;
        if($paged) {
        	$sql .= " LIMIT " . $limit;
        	$begin = $offset < 2 ? 0 : ($offset - 1) * $limit;
        	$sql .= " OFFSET " . $begin;
        }
        $screens = array();
        if($result = $this->db->query($sql)) {
        while($array = $this->db->fetchArray($result)) {
            $screen = $this->create();
            $screen->assignVars($array);
            $screens[] = $screen;
            unset($screen);
            }
        }
        return $screens;
    }

	function get($sid_or_screen_handle) {
		if (is_numeric($sid_or_screen_handle)) {
			$sql = 'SELECT * FROM '.$this->db->prefix('formulize_screen').' WHERE sid='.intval($sid_or_screen_handle);
		} else {
				$sql = 'SELECT * FROM '.$this->db->prefix('formulize_screen').' WHERE screen_handle="'.formulize_db_escape($sid_or_screen_handle).'"';
		}
		if (!$result = $this->db->query($sql)) {
				return false;
		}
		$numrows = $this->db->getRowsNum($result);
		if ($numrows == 1) {
				$screen = new formulizeScreen();
				$screen->assignVars($this->db->fetchArray($result));
				return $screen;
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

	// TO BE CALLED FROM WITHIN THE CHILD CLASS AND THEN RETURNS SCREEN OBJECT, WHICH WILL HAVE THE CORRECT sid IN PLACE NOW
	function insert($screen) {
		if (!is_a($screen, 'formulizeScreen')) {
            return false;
        }
        if (!$screen->cleanVars()) {
            return false;
        }
        foreach ($screen->cleanVars as $k => $v) {
            ${$k} = $v;
        }
        if (!$sid) {
            $sql = sprintf("INSERT INTO %s (screen_handle, title, fid, frid, type, useToken, anonNeedsPasscode, theme, rewriteruleAddress, rewriteruleElement) VALUES (%s, %s, %u, %u, %s, %u, %u, %s, %s, %u)", $this->db->prefix('formulize_screen'), $this->db->quoteString($screen_handle), $this->db->quoteString($title), $fid, $frid, $this->db->quoteString($type), $useToken, $anonNeedsPasscode, $this->db->quoteString($theme), $this->db->quoteString($rewriteruleAddress), $rewriteruleElement);
        } else {
            $sql = sprintf("UPDATE %s SET screen_handle = %s, title = %s, fid = %u, frid = %u, type = %s, useToken = %u, anonNeedsPasscode = %u, theme = %s, rewriteruleAddress = %s, rewriteruleElement = %u WHERE sid = %u", $this->db->prefix('formulize_screen'), $this->db->quoteString($screen_handle), $this->db->quoteString($title), $fid, $frid, $this->db->quoteString($type), $useToken, $anonNeedsPasscode, $this->db->quoteString($theme), $this->db->quoteString($rewriteruleAddress), $rewriteruleElement, $sid);
        }
        $result = $this->db->query($sql);
        if (!$result) {
            print 'DB error: '.$this->db->error() . '<br>'.$sql.'<br>';
            return false;
        }
        if (!$sid) {
            $sid = $this->db->getInsertId();
        }
        return $sid;
	}

    function writeTemplateToFile($text, $filename, $screen) {
        return $screen->writeTemplateFile($text, $filename, $screen->getVar('theme'));
    }

    // FINDS AND RETURNS A NEW TITLE FOR A CLONED SCREEN
    // Pattern for naming is "[Original Screen Name] - Cloned", "[Original Screen Name] - Cloned 2", etc.
    function titleForClonedScreen($sid) {
        $foundTitle = 1;
        $titleCounter = 0;
        $screenObject = $this->get($sid);
        $title = $screenObject->getVar('title');
        while ($foundTitle) {
            $titleCounter++;
            if ($titleCounter > 1) {
                // add a number to the new form name to ensure it is unique
                $newtitle = sprintf(_FORM_MODCLONED, $title)." $titleCounter";
            } else {
                $newtitle = sprintf(_FORM_MODCLONED, $title);
            }
            $titleCheckSQL = "SELECT title FROM " . $this->db->prefix("formulize_screen") . " WHERE title = '".formulize_db_escape($newtitle)."'";
            $titleCheckResult = $this->db->query($titleCheckSQL);
            $foundTitle = $this->db->getRowsNum($titleCheckResult);
        }
        return $newtitle; // use the last searched title (because it was not found)
    }

    // CLONE AND INSERT CLONED FORM INTO FORMULIZE_SCREEN TABLE
    // Takes the screen id of the screen being cloned
    // Returns the id of the newly cloned screen inserted into table
    function insertCloneIntoScreenTable($sid, $newtitle) {
        $getrow = q("SELECT * FROM " . $this->db->prefix("formulize_screen") . " WHERE sid = $sid");
        $insert_sql = "INSERT INTO " . $this->db->prefix("formulize_screen") . " (";
        $start = 1;
        foreach($getrow[0] as $field=>$value) {
            if($field == "sid") { continue; }
            if(!$start) { $insert_sql .= ", "; }
            $start = 0;
            $insert_sql .= $field;
        }
        $insert_sql .= ") VALUES (";
        $start = 1;

        foreach($getrow[0] as $field=>$value) {
            if($field == "sid") { continue; }
            if($field == "title") { $value = $newtitle; }
            if(!$start) { $insert_sql .= ", "; }
            $start = 0;
            $insert_sql .= '"'.formulize_db_escape($value).'"';
        }
        $insert_sql .= ")";
        if(!$result = $this->db->query($insert_sql)) {
            print "error cloning screen: '$title'<br>SQL: $insert_sql<br>".$xoopsDB->error();
            return false;
        }
        $newsid = $this->db->getInsertId();
        return $newsid;
    }

    // INSERT CLONED SCREEN INTO SCREEN-TYPE SPECIFIC TABLE
    // Takes the screen id of the screen being cloned, the screen id of the newly cloned screen,
    // and the title of the newly cloned screen.
    // Inserts the newly cloned screen into the specific table for that type of screen
    // (i.e. form, listOfEntries, or multiPage).
    function insertCloneIntoScreenTypeTable($sid, $newsid, $newtitle, $tablename) {
        $getrow = q("SELECT * FROM " . $this->db->prefix($tablename) . " WHERE sid = $sid");
        $insert_sql = "INSERT INTO " . $this->db->prefix($tablename) . " (";
        $start = 1;
        foreach($getrow[0] as $field=>$value) {
            if($field == "formid" OR $field == "listofentriesid" OR $field == "multipageid" OR $field == "templateid" OR $field == "calendarid") { continue; }
            if(!$start) { $insert_sql .= ", "; }
            $start = 0;
            $insert_sql .= $field;
        }
        $insert_sql .= ") VALUES (";
        $start = 1;

        foreach($getrow[0] as $field=>$value) {
            if($field == "formid" OR $field == "listofentriesid" OR $field == "multipageid" OR $field == "templateid"  OR $field == "calendarid") { continue; }
            if($field == "sid") { $value = $newsid; }
            if($field == "title") { $value = $newtitle; }
            if(!$start) { $insert_sql .= ", "; }
            $start = 0;
            $insert_sql .= '"'.formulize_db_escape($value).'"';
        }
        $insert_sql .= ")";
        if(!$result = $this->db->query($insert_sql)) {
            print "error cloning screen: '$title'<br>SQL: $insert_sql<br>".$xoopsDB->error();
            return false;
        }
        return $result;
    }

}

function getDefaultTemplate($templateName, $type, $theme="") {
	global $xoopsConfig;
	$theme = $theme ? $theme : $xoopsConfig['theme_set'];
	static $cachedDefaultTemplates = array();
	if(!isset($cachedDefaultTemplates[$theme][$type][$templateName])) {
		$themeDefaultPath = XOOPS_ROOT_PATH."/modules/formulize/templates/screens/".$theme."/default/".$type."/".$templateName.".php";
		if (file_exists($themeDefaultPath)) {
			$template = file_get_contents($themeDefaultPath);
		} else {
			$systemDefaultPath = XOOPS_ROOT_PATH."/modules/formulize/templates/screens/default/".$type."/".$templateName.".php";
			if (file_exists($systemDefaultPath)) {
				$template = file_get_contents($systemDefaultPath);
			}
		}
		$cachedDefaultTemplates[$theme][$type][$templateName] = $template;
	}
	return $cachedDefaultTemplates[$theme][$type][$templateName];
}

// returns the actual template we will use at render time. Reverts to default template if no specific template is set for this screen.
function getTemplateToRender($templateName, $screenOrScreenType="", $theme="") {
	if(is_object($screenOrScreenType) AND is_a($screenOrScreenType, 'formulizeScreen')) {
		$template = $screenOrScreenType->getTemplate($templateName, $theme);
		if(!$template) {
			$template = $screenOrScreenType->getDefaultTemplate($templateName, $theme);
			if(!$template) {
				error_log('Formulize Error: could not locate a '.$templateName.' for screen '.$screenOrScreenType->getVar('sid').'. No screen template set, and no theme default at: '.$themeDefaultPath.'. And no system default at: '.$systemDefaultPath);
			}
		}
		return $template;
	} else {
		if(!$screenOrScreenType) { exit('Cannot getTemplateToRender for non-screen without a Type specified'); }
		return getDefaultTemplate($templateName, $screenOrScreenType);
	}
}

function getTemplatePath($screenOrScreenType, $templateName) {
	global $xoopsConfig;
	$paths = array();
	$type = $screenOrScreenType;
	if(is_object($screenOrScreenType) AND is_a($screenOrScreenType, 'formulizeScreen')) {
		$paths[] = XOOPS_ROOT_PATH."/modules/formulize/templates/screens/".$xoopsConfig['theme_set']."/".$screenOrScreenType->getVar('sid')."/".$templateName.".php";
		$type = $screenOrScreenType->getVar('type');
	}
	$paths[] = XOOPS_ROOT_PATH."/modules/formulize/templates/screens/".$xoopsConfig['theme_set']."/default/".$type."/".$templateName.".php";
	$paths[] = XOOPS_ROOT_PATH."/modules/formulize/templates/screens/default/".$type."/".$templateName.".php";
	foreach($paths as $path) {
		if(file_exists($path) AND filesize($path)) {
			return $path;
		}
	}
}
