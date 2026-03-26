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

class formulizeMapScreen extends formulizeScreen {
    function __construct() {
        parent::__construct();
        $this->initVar("dobr", XOBJ_DTYPE_INT, 1, false);
        $this->initVar("dohtml", XOBJ_DTYPE_INT, 1, false);
        $this->assignVar("dobr", false); // don't convert line breaks to <br> when using the getVar method
        $this->initVar("lat_element", XOBJ_DTYPE_TXTBOX, NULL, false, 255);
        $this->initVar("lng_element", XOBJ_DTYPE_TXTBOX, NULL, false, 255);
        $this->initVar("label_element", XOBJ_DTYPE_TXTBOX, NULL, false, 255);
        $this->initVar("description_element", XOBJ_DTYPE_TXTBOX, NULL, false, 255);
        $this->initVar("viewentryscreen", XOBJ_DTYPE_TXTBOX, NULL, false, 10);
        $this->initVar("columns", XOBJ_DTYPE_ARRAY);
        $this->initVar("fundamental_filters", XOBJ_DTYPE_ARRAY);
        $this->initVar("filter_button_text", XOBJ_DTYPE_TXTBOX, NULL, false, 255);
        $this->initVar("tileset", XOBJ_DTYPE_TXTBOX, 'osm', false, 50);
        $this->initVar("tileset_url", XOBJ_DTYPE_TXTBOX, NULL, false, 1000);
        $this->initVar("tileset_key", XOBJ_DTYPE_TXTBOX, NULL, false, 255);
        $this->initVar("tileset_attribution", XOBJ_DTYPE_TXTBOX, NULL, false, 1000);
    }
}

#[AllowDynamicProperties]
class formulizeMapScreenHandler extends formulizeScreenHandler {
    var $db;

    function __construct(&$db) {
        $this->db =& $db;
    }

    function &getInstance(&$db) {
        static $instance;
        if (!isset($instance)) {
            $instance = new formulizeMapScreenHandler($db);
        }
        return $instance;
    }

    function &create() {
        return new formulizeMapScreen();
    }

    function insert($screen, $force=false) {
        $update = !$screen->getVar('sid') ? false : true;
        if (!$sid = parent::insert($screen, $force)) {
            return false;
        }
        $screen->assignVar('sid', $sid);
        if (!$update) {
            $sql = sprintf("INSERT INTO %s (sid, lat_element, lng_element, label_element, description_element, viewentryscreen, columns, fundamental_filters, filter_button_text, tileset, tileset_url, tileset_key, tileset_attribution) VALUES (%u, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
                $this->db->prefix('formulize_screen_map'),
                $screen->getVar('sid'),
                $this->db->quoteString($screen->getVar('lat_element')),
                $this->db->quoteString($screen->getVar('lng_element')),
                $this->db->quoteString($screen->getVar('label_element')),
                $this->db->quoteString($screen->getVar('description_element')),
                $this->db->quoteString($screen->getVar('viewentryscreen')),
                $this->db->quoteString(serialize($screen->getVar('columns'))),
                $this->db->quoteString(serialize($screen->getVar('fundamental_filters'))),
                $this->db->quoteString($screen->getVar('filter_button_text')),
                $this->db->quoteString($screen->getVar('tileset', 'n')),
                $this->db->quoteString($screen->getVar('tileset_url', 'n')),
                $this->db->quoteString($screen->getVar('tileset_key', 'n')),
                $this->db->quoteString($screen->getVar('tileset_attribution', 'n'))
            );
        } else {
            $sql = sprintf("UPDATE %s SET lat_element = %s, lng_element = %s, label_element = %s, description_element = %s, viewentryscreen = %s, columns = %s, fundamental_filters = %s, filter_button_text = %s, tileset = %s, tileset_url = %s, tileset_key = %s, tileset_attribution = %s WHERE sid = %u",
                $this->db->prefix('formulize_screen_map'),
                $this->db->quoteString($screen->getVar('lat_element')),
                $this->db->quoteString($screen->getVar('lng_element')),
                $this->db->quoteString($screen->getVar('label_element')),
                $this->db->quoteString($screen->getVar('description_element')),
                $this->db->quoteString($screen->getVar('viewentryscreen')),
                $this->db->quoteString(serialize($screen->getVar('columns'))),
                $this->db->quoteString(serialize($screen->getVar('fundamental_filters'))),
                $this->db->quoteString($screen->getVar('filter_button_text')),
                $this->db->quoteString($screen->getVar('tileset', 'n')),
                $this->db->quoteString($screen->getVar('tileset_url', 'n')),
                $this->db->quoteString($screen->getVar('tileset_key', 'n')),
                $this->db->quoteString($screen->getVar('tileset_attribution', 'n')),
                $screen->getVar('sid')
            );
        }
        if ($force) {
            $result = $this->db->queryF($sql);
        } else {
            $result = $this->db->query($sql);
        }
        if (!$result) {
            return false;
        }

        $success1 = true;
        if(isset($_POST['screens-toptemplate'])) {
            $success1 = $this->writeTemplateToFile(trim($_POST['screens-toptemplate']), 'toptemplate', $screen);
        }
        $success2 = true;
        if(isset($_POST['screens-maptemplate'])) {
            $success2 = $this->writeTemplateToFile(trim($_POST['screens-maptemplate']), 'maptemplate', $screen);
        }
        $success3 = true;
        if(isset($_POST['screens-bottomtemplate'])) {
            $success3 = $this->writeTemplateToFile(trim($_POST['screens-bottomtemplate']), 'bottomtemplate', $screen);
        }

        if (!$success1 || !$success2 || !$success3) {
            return false;
        }

        return $sid;
    }

    function get($sid) {
        $sid = intval($sid);
        if ($sid > 0) {
            $sql = 'SELECT * FROM '.$this->db->prefix('formulize_screen').' AS t1, '.$this->db->prefix('formulize_screen_map').' AS t2 WHERE t1.sid='.$sid.' AND t1.sid=t2.sid';
            if (!$result = $this->db->query($sql)) {
                return false;
            }
            $numrows = $this->db->getRowsNum($result);
            if ($numrows == 1) {
                $screen = new formulizeMapScreen();
                $screen->assignVars($this->db->fetchArray($result));
                return $screen;
            }
        }
        return false;
    }

    function cloneScreen($sid) {
        $newtitle = parent::titleForClonedScreen($sid);
        $newsid = parent::insertCloneIntoScreenTable($sid, $newtitle);
        if (!$newsid) {
            return false;
        }
        $tablename = "formulize_screen_map";
        $result = parent::insertCloneIntoScreenTypeTable($sid, $newsid, $newtitle, $tablename);
        if (!$result) {
            return false;
        }
    }

    function render($screen) {
        $previouslyRenderingScreen = (isset($GLOBALS['formulize_screenCurrentlyRendering']) AND $GLOBALS['formulize_screenCurrentlyRendering']) ? $GLOBALS['formulize_screenCurrentlyRendering'] : null;
        $GLOBALS['formulize_screenCurrentlyRendering'] = $screen;
        displayMap(screen: $screen);
        $GLOBALS['formulize_screenCurrentlyRendering'] = $previouslyRenderingScreen;
    }
}
