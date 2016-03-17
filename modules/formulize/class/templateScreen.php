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


class formulizeTemplateScreen extends formulizeScreen {

    function formulizeTemplateScreen() {
        $this->formulizeScreen();
        $this->initVar("custom_code", XOBJ_DTYPE_TXTAREA);
        $this->initVar("template", XOBJ_DTYPE_TXTAREA);
    }
}

class formulizeTemplateScreenHandler extends formulizeScreenHandler {
    var $db;

    const TEMPLATE_SCREENS_CACHE_FOLDER = "/modules/formulize/templates/screens/default/";
    const FORMULIZE_CSS_FILE = "/modules/formulize/templates/css/formulize.css";
    const FORMULIZE_JS_FILE = "/modules/formulize/libraries/formulize.js";

    function formulizeTemplateScreenHandler(&$db) {
        $this->db =& $db;
    }

    function &getInstance(&$db) {
        static $instance;
        if (!isset($instance)) {
            $instance = new formulizeTemplateScreenHandler($db);
        }
        return $instance;
    }

    function &create() {
        return new formulizeTemplateScreen();
    }

    function insert($screen) {

        $update = ($screen->getVar('sid') == 0) ? false : true;

        if(!$sid = parent::insert($screen)) {
            return false;
        }
        $screen->assignVar('sid', $sid);

        if (!$update) {
            $sql = sprintf("INSERT INTO %s (sid, custom_code, template) VALUES (%u, %s, %s)", $this->db->prefix('formulize_screen_template'),
                $screen->getVar('sid'), $this->db->quoteString(formulize_db_escape($screen->getVar('custom_code'))), $this->db->quoteString(formulize_db_escape($screen->getVar('template'))));
        } else {
            $sql = sprintf("UPDATE %s SET custom_code = %s, template = %s WHERE sid = %u", $this->db->prefix('formulize_screen_template'),
                formulize_db_escape($screen->getVar('custom_code')), formulize_db_escape($screen->getVar('template')), $sid);
        }
        $result = $this->db->query($sql);
        if (!$result) {
            print "Error: could not save template screen properly: ".$this->db->error()." for query: $sql";
            return false;
        }

        $success1 = true;
        if(isset($_POST['screens-custom_code'])) {
            $success1 = $this->write_custom_code_to_file(trim($_POST['screens-custom_code']), $screen);
        }
        $success2 = true;
        if(isset($_POST['screens-template'])) {
            $success2 = $this->write_template_to_file(trim($_POST['screens-template']), $screen);
        }
        if (!$success1 || !$success2) {
            return false;
        }

        return $sid;
    }


    function get($sid) {
        $sid = intval($sid);
        if ($sid > 0) {
            $sql = 'SELECT * FROM '.$this->db->prefix('formulize_screen').' AS t1, '. $this->db->prefix('formulize_screen_template').' AS t2 WHERE t1.sid='.$sid.' AND t1.sid=t2.sid';
            if (!$result = $this->db->query($sql)) {
                return false;
            }
            $numrows = $this->db->getRowsNum($result);
            if ($numrows == 1) {
                $screen = new formulizeTemplateScreen();
                $screen->assignVars($this->db->fetchArray($result));
                return $screen;
            }
        }
        return false;
    }


    function render($screen) {
        global $xoTheme;
        if($xoTheme) {
            $xoTheme->addStylesheet(self::FORMULIZE_CSS_FILE);
            $xoTheme->addScript(self::FORMULIZE_JS_FILE);
        }

        $custom_code_filename = $this->custom_code_filename($screen);
        $template_filename = $this->template_filename($screen);

        if (file_exists($custom_code_filename) and file_exists($template_filename)) {
            $vars = $this->run_template_php_code($custom_code_filename);
            global $xoopsTpl;
            foreach ($vars as $key => $value) {
                $xoopsTpl->assign($key, $value);
            }
            $xoopsTpl->display("file:".$template_filename);
        } else {
            echo "<p>Error: specified screen template does not exist.</p>";
        }
    }


    function run_template_php_code($code_filename) {
        include_once($code_filename);
        return get_defined_vars();
    }

    // Returns a cache folder name of shape "{ROOT_PATH}/modules/formulize/templates/screens/default/{$sid}/
    function cache_folder_name($screen) {
        return XOOPS_ROOT_PATH. self::TEMPLATE_SCREENS_CACHE_FOLDER .$screen->getVar('sid')."/";
    }

    // Returns a custom code filename of shape "{ROOT_PATH}/modules/formulize/templates/screens/default/{$sid}/code.php"
    function custom_code_filename($screen) {
        return $this->cache_folder_name($screen) . "/code.php";
    }

    // Returns a template filename of shape "{ROOT_PATH}/modules/formulize/templates/screens/default/{$sid}/template.html"
    function template_filename($screen) {
        return $this->cache_folder_name($screen) ."/template.html";
    }

    // returns code.php file
    function getCustomCode($screen) {
        static $templates = array();
        if (!isset($templates['custom_code'])) {
            $pathname = $this->custom_code_filename($screen);
            if (file_exists($pathname)) {
                $templates['custom_code'] = file_get_contents($pathname);
            } else {
                $templates['custom_code'] = $screen->getVar('custom_code');
                if (strlen($templates['custom_code']) > 0) {
                    $this->write_custom_code_to_file(htmlspecialchars_decode($templates['custom_code'], ENT_QUOTES), $screen);
                }
            }
        }
        return $templates['custom_code'];

    }

    // returns template.html file
    function getTemplateHtml($screen) {
        static $templates = array();
        if (!isset($templates['template'])) {
            // there is no template saved in memory, read it from the file;
            $pathname = $this->template_filename($screen);
            if (file_exists($pathname)) {
                $templates['template'] = file_get_contents($pathname);
            } else {
                $templates['template'] = $screen->getVar('template');
                if (strlen($templates['template']) > 0) {
                    $this->write_template_to_file(htmlspecialchars_decode($templates['template'], ENT_QUOTES), $screen);
                }
            }
        }
        return $templates['template'];
    }

    // Writes a code.php file in /modules/formulize/templates/screens/default/$sid/code.php
    function write_custom_code_to_file($content, $screen) {
        return $this->write_to_file($content, $screen, "/code.php");
    }

    // Writes a template.html file in /modules/formulize/templates/screens/default/$sid/template.html
    function write_template_to_file($content, $screen) {
        return $this->write_to_file($content, $screen, "/template.html");
    }

    function write_to_file($content, $screen, $name) {
        $foldername = $this->cache_folder_name($screen);

        if (!is_dir($foldername)) {
            mkdir($foldername, 0777, true);
        }
        if (!is_writable($foldername)) {
            chmod($foldername, 0777);
        }

        $filename = $foldername . $name;

        $success = file_put_contents($filename, $content);
        if (false === $success) {
            error_log("ERROR: Could not write to template screen cache file: $filename");
            return false;
        }
        return true;
    }


    // THIS METHOD CLONES A TEMPLATE_SCREEN
    function cloneScreen($sid) {

        $newtitle = parent::titleForClonedScreen($sid);

        $newsid = parent::insertCloneIntoScreenTable($sid, $newtitle);

        if (!$newsid) {
            return false;
        }

        $tablename = "formulize_screen_template";
        $result = parent::insertCloneIntoScreenTypeTable($sid, $newsid, $newtitle, $tablename);

        if (!$result) {
            return false;
        }
    }
}
