<?php
if (!defined("XOOPS_ROOT_PATH")) {
    die("XOOPS root path not defined");
}

require_once XOOPS_ROOT_PATH.'/kernel/object.php';
require_once XOOPS_ROOT_PATH.'/modules/formulize/class/screen.php';
include_once XOOPS_ROOT_PATH.'/modules/formulize/include/functions.php';


class formulizeGraphScreen extends formulizeScreen {
    function formulizeGraphScreen() {
        $this->formulizeScreen();
        $this->initvar("width", XOBJ_DTYPE_INT);
        $this->initvar("height", XOBJ_DTYPE_INT);
        $this->initvar("orientation", XOBJ_DTYPE_TXTBOX, NULL, false, 255);
        $this->initvar("bgr", XOBJ_DTYPE_INT);
        $this->initvar("bgg", XOBJ_DTYPE_INT);
        $this->initvar("bgb", XOBJ_DTYPE_INT);
        $this->initvar("barr", XOBJ_DTYPE_INT);
        $this->initvar("barg", XOBJ_DTYPE_INT);
        $this->initvar("barb", XOBJ_DTYPE_INT);
        $this->initvar("ops", XOBJ_DTYPE_TXTBOX, NULL, false, 255);
        $this->initvar("labelelem", XOBJ_DTYPE_INT);
        $this->initvar("dataelem", XOBJ_DTYPE_INT);
        $this->initvar("defaultview", XOBJ_DTYPE_TXTBOX, NULL, false, 20);
        $this->initVar("limitviews", XOBJ_DTYPE_ARRAY); // 'allviews' in array means no limit, otherwise use view id numbers, or 'mine', 'group' and 'all' for the Standard Views
        $this->initVar("usecurrentviewlist", XOBJ_DTYPE_TXTBOX, NULL, false, 255);
    }
}


class formulizeGraphScreenHandler extends formulizeScreenHandler {
    var $db;

    function formulizeGraphScreenHandler(&$db) {
        $this->db =& $db;
    }

    function &getInstance(&$db) {
        static $instance;
        if (!isset($instance)) {
            $instance = new formulizeGraphScreenHandler($db);
        }
        return $instance;
    }

    function &create() {
        return new formulizeGraphScreen();
    }

    function insert($screen) {
        // sid is being used as a flag to update or not
        $update = ($screen->getVar('sid') == 0) ? false : true;
        // insert or update and get the actual sid
        if (!$sid = parent::insert($screen)) {
            // write the basic info to the db, handle cleaning vars and all that jazz.  Object passed by reference, so updates will have affected it in the other method.
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
            $sql = sprintf("INSERT INTO %s (sid, width, height, orientation, bgr, bgg, bgb, barr, barg, barb, ops, labelelem, dataelem, defaultview, limitviews, usecurrentviewlist)
               VALUES (%u, %u, %u, %s, %u, %u, %u, %u, %u, %u, %s, %u, %u, %s, %s, %s)",
               $this->db->prefix('formulize_screen_graph'), $screen->getVar('sid'), $screen->getVar('width'),
               $screen->getVar('height'), $this->db->quoteString($screen->getVar('orientation')),
               $screen->getVar('bgr'), $screen->getVar('bgg'), $screen->getVar('bgb'), $screen->getVar('barr'), $screen->getVar('barg'), $screen->getVar('barb'),
               $this->db->quoteString($screen->getVar('ops')), $screen->getVar('labelelem'), $screen->getVar('dataelem'), $this->db->quoteString($screen->getVar('defaultview')),
               $this->db->quoteString(serialize($screen->getVar('limitviews'))), $this->db->quoteString($screen->getVar('usecurrentviewlist')));
        } else {
            $sql = sprintf("UPDATE %s SET width = %u, height = %u, orientation = %s, bgr = %u, bgg = %u, bgb = %u, barr = %u, barg = %u, barb = %u, ops = %s, labelelem = %u, dataelem = %u, defaultview = %s, limitviews = %s, usecurrentviewlist = %s WHERE sid = %u", $this->db->prefix('formulize_screen_graph'), $screen->getVar('width'), $screen->getVar('height'), $this->db->quoteString($screen->getVar('orientation')), $screen->getVar('bgr'), $screen->getVar('bgg'), $screen->getVar('bgb'), $screen->getVar('barr'), $screen->getVar('barg'), $screen->getVar('barb'), $this->db->quoteString($screen->getVar('ops')), $screen->getVar('labelelem'), $screen->getVar('dataelem'), $this->db->quoteString($screen->getVar('defaultview')), $this->db->quoteString(serialize($screen->getVar('limitviews'))), $this->db->quoteString($screen->getVar('usecurrentviewlist')), $screen->getVar('sid'));
        }
        $result = $this->db->query($sql);
        if (!$result) {
            print "Error: could not save the screen properly: ".mysql_error()." for query: $sql";
            return false;
        }
        return $sid;
    }


    // this method might be moved up a level to the parent class
    function get($sid) {
        $sid = intval($sid);
        if ($sid > 0) {
            $sql = 'SELECT * FROM '.$this->db->prefix('formulize_screen').' AS t1, '. $this->db->prefix('formulize_screen_graph').' AS t2 WHERE t1.sid='.$sid.' AND t1.sid=t2.sid';
            if (!$result = $this->db->query($sql)) {
                return false;
            }
            $numrows = $this->db->getRowsNum($result);
            if ($numrows == 1) {
                $screen = new formulizeGraphScreen();
                $screen->assignVars($this->db->fetchArray($result));
                return $screen;
            }
        }
        return false;
    }


    // this method handles all the logic about how to actually display this type of screen
    // $screen is a screen object
    function render($screen, $entry, $settings = "") {
        // $settings is used internally to pass list of entries settings back and forth to editing screens
        $bgc = array(
            "R" => $screen->getVar('bgr'),
            "G" => $screen->getVar('bgg'),
            "B" => $screen->getVar('bgb')
        );
        $barc = array(
            "R" => $screen->getVar('barr'),
            "G" => $screen->getVar('barg'),
            "B" => $screen->getVar('barb')
        );
        $options = array(
            "width" => $screen->getVar('width'),
            "height" => $screen->getVar('height'),
            "orientation" => $screen->getVar('orientation'),
            "backgroundcolor" => $bgc,
            "barcolor" => $barc,
            "defaultview" => $screen->getVar('defaultview'),
            "limitviews" => $screen->getVar('limitviews'),
            "usecurrentviewlist" => $screen->getVar('usecurrentviewlist')
        );
        include_once XOOPS_ROOT_PATH."/modules/formulize/include/graphdisplay.php";

        //convert element ids to element handles
        include_once XOOPS_ROOT_PATH . "/modules/formulize/include/extract.php";

        $labelElementMetaData = formulize_getElementMetaData($screen->getVar('labelelem'), false);
        $labelEleHandle=$labelElementMetaData['ele_handle'];
        $dataElementMetaData = formulize_getElementMetaData($screen->getVar('dataelem'), false);
        $dataEleHandle=$dataElementMetaData['ele_handle'];
        $graphType;
        if ($screen->getVar('orientation')=='pie')
            $graphType='Pie';
        else
            $graphType='Bar';
        displayGraph($graphType, $screen->getVar('fid'), $screen->getVar('frid'), $labelEleHandle, $dataEleHandle, $screen->getVar('ops'), $options);
    }
}
