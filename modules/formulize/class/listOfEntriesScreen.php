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

class formulizeListOfEntriesScreen extends formulizeScreen {
    function __construct() {
        parent::__construct();
        $this->initVar("dobr", XOBJ_DTYPE_INT, 1, false);
        $this->initVar("dohtml", XOBJ_DTYPE_INT, 1, false);
        $this->assignVar("dobr", false); // don't convert line breaks to <br> when using the getVar method
        $this->initVar("useworkingmsg", XOBJ_DTYPE_INT);
        $this->initVar("repeatheaders", XOBJ_DTYPE_INT);
        $this->initVar("useaddupdate", XOBJ_DTYPE_TXTBOX, NULL, false, 255);
        $this->initVar("useaddmultiple", XOBJ_DTYPE_TXTBOX, NULL, false, 255);
        $this->initVar("useaddproxy", XOBJ_DTYPE_TXTBOX, NULL, false, 255);
        $this->initVar("usecurrentviewlist", XOBJ_DTYPE_TXTBOX, NULL, false, 255);
        $this->initVar("limitviews", XOBJ_DTYPE_ARRAY); // 'allviews' in array means no limit, otherwise use view id numbers, or 'mine', 'group' and 'all' for the Standard Views
        $this->initVar("defaultview", XOBJ_DTYPE_ARRAY);
		$this->initVar("advanceview", XOBJ_DTYPE_ARRAY);
        $this->initVar("usechangecols", XOBJ_DTYPE_TXTBOX, NULL, false, 255);
        $this->initVar("usecalcs", XOBJ_DTYPE_TXTBOX, NULL, false, 255);
        $this->initVar("useadvcalcs", XOBJ_DTYPE_TXTBOX, NULL, false, 255);
        $this->initVar("useadvsearch", XOBJ_DTYPE_TXTBOX, NULL, false, 255);
        $this->initVar("useexport", XOBJ_DTYPE_TXTBOX, NULL, false, 255);
        $this->initVar("useexportcalcs", XOBJ_DTYPE_TXTBOX, NULL, false, 255);
        $this->initVar("useimport", XOBJ_DTYPE_TXTBOX, NULL, false, 255);
        $this->initVar("useclone", XOBJ_DTYPE_TXTBOX, NULL, false, 255);
        $this->initVar("usedelete", XOBJ_DTYPE_TXTBOX, NULL, false, 255);
        $this->initVar("useselectall", XOBJ_DTYPE_TXTBOX, NULL, false, 255);
        $this->initVar("useclearall", XOBJ_DTYPE_TXTBOX, NULL, false, 255);
        $this->initVar("usenotifications", XOBJ_DTYPE_TXTBOX, NULL, false, 255);
        $this->initVar("usereset", XOBJ_DTYPE_TXTBOX, NULL, false, 255);
        $this->initVar("usesave", XOBJ_DTYPE_TXTBOX, NULL, false, 255);
        $this->initVar("usedeleteview", XOBJ_DTYPE_TXTBOX, NULL, false, 255);
        $this->initVar("useheadings", XOBJ_DTYPE_INT);
        $this->initVar("usesearch", XOBJ_DTYPE_INT);
        $this->initVar("usecheckboxes", XOBJ_DTYPE_INT); // 0 is default, 1 is all, 2 is none
        $this->initVar("useviewentrylinks", XOBJ_DTYPE_INT);
        $this->initVar("usescrollbox", XOBJ_DTYPE_INT);
        $this->initVar("usesearchcalcmsgs", XOBJ_DTYPE_INT); // 0 is neither, 1 is both, 2 is just search, 3 is just calc
        $this->initvar("hiddencolumns", XOBJ_DTYPE_ARRAY); // element ids from entire framework where the current values should be rendered as hidden elements
        $this->initVar("decolumns", XOBJ_DTYPE_ARRAY); // element ids from entire framework that should be rendered as elements
        $this->initVar("dedisplay", XOBJ_DTYPE_INT);
        $this->initVar("desavetext", XOBJ_DTYPE_TXTBOX, NULL, false, 255);
        $this->initVar("columnwidth", XOBJ_DTYPE_INT);
        $this->initVar("textwidth", XOBJ_DTYPE_INT);
        $this->initVar("customactions", XOBJ_DTYPE_ARRAY);
        // CUSTOM ACTIONS
        // multidimensional array, can have multiple actions and then multiple effects within each action
        // array[actionid][handle]
        // array[actionid][buttontext]
        // array[actionid][messagetext] -- text to appear on the screen after this button has been clicked.  Intended for confirmation stuff like "your items have been signed out".
        // array[actionid][appearinline] -- either 1, yes, or 0, no
        // array[actionid][applyto] -- either 'inline', 'selected', 'all', 'new'
        // OLD NOTES: 'selected' for selected, 'individual' for individual meaning the entry where this appears inline, or 'x_new', or 'x_all', or 'x_y_z_etc' -- x is the form (possibly the current form, or some other)
        // so to support apply to setting, we need a fairly complex interface where people can select the main three options (all, or selected or individual in this form) or new entry in this form, or apply to another form and if so to which entries: new or all or x, y, z
        // x, y, z, are essentially a surrogate for user being able to check off entries -- app developer must be able to specify in advance which entries those are.
        // array[actionid][effectid][element] -- element to alter
        // array[actionid][effectid][action] -- type of action
        // array[actionid][effectid][value] -- value to use in action -- need to support pulling a value from $_POST, or gathering a value from an entry (which only works if inline is selected, we use the display function to put that value into the displayButton call at the time the button is drawn in that row) so that needs an element specifier UI, or we allow custom PHP to define the value
        $this->initVar("toptemplate", XOBJ_DTYPE_TXTAREA);
        $this->initVar("listtemplate", XOBJ_DTYPE_TXTAREA);
        $this->initVar("bottomtemplate", XOBJ_DTYPE_TXTAREA);
        $this->initVar("entriesperpage", XOBJ_DTYPE_INT);
        $this->initVar("viewentryscreen", XOBJ_DTYPE_TXTBOX, NULL, false, 10);
        $this->initVar("fundamental_filters", XOBJ_DTYPE_ARRAY);
    }
}


class formulizeListOfEntriesScreenHandler extends formulizeScreenHandler {
    var $db;

    function __construct(&$db) {
        $this->db =& $db;
    }

    function &getInstance(&$db) {
        static $instance;
        if (!isset($instance)) {
            $instance = new formulizeListOfEntriesScreenHandler($db);
        }
        return $instance;
    }

    function &create() {
        return new formulizeListOfEntriesScreen();
    }


    function insert($screen) {
        $update = ($screen->getVar('sid') == 0) ? false : true;
        if (!$sid = parent::insert($screen)) { // write the basic info to the db, handle cleaning vars and all that jazz.  Object passed by reference, so updates will have affected it in the other method.
            return false;
        }
        $screen->assignVar('sid', $sid);
        if (!$update) {
            $sql = sprintf("INSERT INTO %s (sid,
                useworkingmsg,
                repeatheaders,
                useaddupdate,
                useaddmultiple,
                useaddproxy,
                usecurrentviewlist,
                limitviews,
                defaultview,
                advanceview,
                usechangecols,
                usecalcs,
                useadvcalcs,
                useadvsearch,
                useexport,
                useexportcalcs,
                useimport,
                useclone,
                usedelete,
                useselectall,
                useclearall,
                usenotifications,
                usereset,
                usesave,
                usedeleteview,
                useheadings,
                usesearch,
                usecheckboxes,
                useviewentrylinks,
                usescrollbox,
                usesearchcalcmsgs,
                hiddencolumns,
                decolumns,
                desavetext,
                columnwidth,
                textwidth,
                customactions,
                toptemplate,
                listtemplate,
                bottomtemplate,
                entriesperpage,
                viewentryscreen,
                dedisplay,
                fundamental_filters) VALUES (%u,
                %u,
                %u,
                %s,
                %s,
                %s,
                %s,
                %s,
                %s,
                %s,
                %s,
                %s,
                %s,
                %s,
                %s,
                %s,
                %s,
                %s,
                %s,
                %s,
                %s,
                %s,
                %s,
                %s,
                %s,
                %u,
                %u,
                %u,
                %u,
                %u,
                %u,
                %s,
                %s,
                %s,
                %u,
                %u,
                %s,
                %s,
                %s,
                %s,
                %u,
                %s,
                %u,
                %s)",
                $this->db->prefix('formulize_screen_listofentries'),
                $screen->getVar('sid'),
                $screen->getVar('useworkingmsg'),
                $screen->getVar('repeatheaders'),
                $this->db->quoteString($screen->getVar('useaddupdate')),
                $this->db->quoteString($screen->getVar('useaddmultiple')),
                $this->db->quoteString($screen->getVar('useaddproxy')),
                $this->db->quoteString($screen->getVar('usecurrentviewlist')),
                $this->db->quoteString(serialize($screen->getVar('limitviews'))),
                $this->db->quoteString(serialize($screen->getVar('defaultview'))),
                $this->db->quoteString(serialize($screen->getVar('advanceview'))),
                $this->db->quoteString($screen->getVar('usechangecols')),
                $this->db->quoteString($screen->getVar('usecalcs')),
                $this->db->quoteString($screen->getVar('useadvcalcs')),
                $this->db->quoteString($screen->getVar('useadvsearch')),
                $this->db->quoteString($screen->getVar('useexport')),
                $this->db->quoteString($screen->getVar('useexportcalcs')),
                $this->db->quoteString($screen->getVar('useimport')),
                $this->db->quoteString($screen->getVar('useclone')),
                $this->db->quoteString($screen->getVar('usedelete')),
                $this->db->quoteString($screen->getVar('useselectall')),
                $this->db->quoteString($screen->getVar('useclearall')),
                $this->db->quoteString($screen->getVar('usenotifications')),
                $this->db->quoteString($screen->getVar('usereset')),
                $this->db->quoteString($screen->getVar('usesave')),
                $this->db->quoteString($screen->getVar('usedeleteview')),
                $screen->getVar('useheadings'),
                $screen->getVar('usesearch'),
                $screen->getVar('usecheckboxes'),
                $screen->getVar('useviewentrylinks'),
                $screen->getVar('usescrollbox'),
                $screen->getVar('usesearchcalcmsgs'),
                $this->db->quoteString(serialize($screen->getVar('hiddencolumns'))),
                $this->db->quoteString(serialize($screen->getVar('decolumns'))),
                $this->db->quoteString($screen->getVar('desavetext')),
                $screen->getVar('columnwidth'), $screen->getVar('textwidth'),
                $this->db->quoteString(serialize($screen->getVar('customactions'))),
                $this->db->quoteString($screen->getVar('toptemplate')),
                $this->db->quoteString($screen->getVar('listtemplate')),
                $this->db->quoteString($screen->getVar('bottomtemplate')),
                $screen->getVar('entriesperpage'),
                $this->db->quoteString($screen->getVar('viewentryscreen')),
                $screen->getVar('dedisplay'),
                $this->db->quoteString(serialize($screen->getVar('fundamental_filters')))
                );
        } else {
            $sql = sprintf("UPDATE %s SET useworkingmsg = %u, repeatheaders = %u, useaddupdate = %s, useaddmultiple = %s, useaddproxy = %s, usecurrentviewlist = %s, limitviews = %s, defaultview = %s, advanceview = %s, usechangecols = %s, usecalcs = %s, useadvcalcs = %s, useadvsearch = %s, useexport = %s, useexportcalcs = %s, useimport = %s, useclone = %s, usedelete = %s, useselectall = %s, useclearall = %s, usenotifications = %s, usereset = %s, usesave = %s, usedeleteview = %s, useheadings = %u, usesearch = %u, usecheckboxes = %u, useviewentrylinks = %u, usescrollbox = %u, usesearchcalcmsgs = %u, hiddencolumns = %s, decolumns = %s, desavetext = %s, columnwidth = %u, textwidth = %u, customactions = %s, toptemplate = %s, listtemplate = %s, bottomtemplate = %s, entriesperpage = %u, viewentryscreen = %s, dedisplay = %u, fundamental_filters = %s WHERE sid = %u", $this->db->prefix('formulize_screen_listofentries'), $screen->getVar('useworkingmsg'), $screen->getVar('repeatheaders'), $this->db->quoteString($screen->getVar('useaddupdate')), $this->db->quoteString($screen->getVar('useaddmultiple')), $this->db->quoteString($screen->getVar('useaddproxy')), $this->db->quoteString($screen->getVar('usecurrentviewlist')), $this->db->quoteString(serialize($screen->getVar('limitviews'))), $this->db->quoteString(serialize($screen->getVar('defaultview'))), $this->db->quoteString(serialize($screen->getVar('advanceview'))), $this->db->quoteString($screen->getVar('usechangecols')), $this->db->quoteString($screen->getVar('usecalcs')), $this->db->quoteString($screen->getVar('useadvcalcs')), $this->db->quoteString($screen->getVar('useadvsearch')), $this->db->quoteString($screen->getVar('useexport')), $this->db->quoteString($screen->getVar('useexportcalcs')), $this->db->quoteString($screen->getVar('useimport')), $this->db->quoteString($screen->getVar('useclone')), $this->db->quoteString($screen->getVar('usedelete')), $this->db->quoteString($screen->getVar('useselectall')), $this->db->quoteString($screen->getVar('useclearall')), $this->db->quoteString($screen->getVar('usenotifications')), $this->db->quoteString($screen->getVar('usereset')), $this->db->quoteString($screen->getVar('usesave')), $this->db->quoteString($screen->getVar('usedeleteview')), $screen->getVar('useheadings'), $screen->getVar('usesearch'), $screen->getVar('usecheckboxes'), $screen->getVar('useviewentrylinks'), $screen->getVar('usescrollbox'), $screen->getVar('usesearchcalcmsgs'), $this->db->quoteString(serialize($screen->getVar('hiddencolumns'))), $this->db->quoteString(serialize($screen->getVar('decolumns'))), $this->db->quoteString($screen->getVar('desavetext')), $screen->getVar('columnwidth'), $screen->getVar('textwidth'), $this->db->quoteString(serialize($screen->getVar('customactions'))), $this->db->quoteString($screen->getVar('toptemplate')), $this->db->quoteString($screen->getVar('listtemplate')), $this->db->quoteString($screen->getVar('bottomtemplate')), $screen->getVar('entriesperpage'), $this->db->quoteString($screen->getVar('viewentryscreen')), $screen->getVar('dedisplay'), $this->db->quoteString(serialize($screen->getVar('fundamental_filters'))), $screen->getVar('sid'));
        }
        $result = $this->db->query($sql);
        if (!$result) {
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
        $success3 = true;
        if(isset($_POST['screens-listtemplate'])) {
            $success3 = $this->writeTemplateToFile(trim($_POST['screens-listtemplate']), 'listtemplate', $screen);
        }

        if (!$success1 || !$success2 || !$success3) {
            return false;
        }

        return $sid;
    }


    //  THIS METHOD MIGHT BE MOVED UP A LEVEL TO THE PARENT CLASS
    function get($sid) {
        $sid = intval($sid);
        if ($sid > 0) {
            $sql = 'SELECT * FROM '.$this->db->prefix('formulize_screen').' AS t1, '. $this->db->prefix('formulize_screen_listofentries').' AS t2 WHERE t1.sid='.$sid.' AND t1.sid=t2.sid';
            if (!$result = $this->db->query($sql)) {
                return false;
            }
            $numrows = $this->db->getRowsNum($result);
            if ($numrows == 1) {
                $screen = new formulizeListOfEntriesScreen();
                $screen->assignVars($this->db->fetchArray($result));
                return $screen;
            }
        }
        return false;
    }


    // THIS METHOD CLONES A LIST_OF_ENTRIES_SCREEN
    function cloneScreen($sid) {

        $newtitle = parent::titleForClonedScreen($sid);

        $newsid = parent::insertCloneIntoScreenTable($sid, $newtitle);

        if (!$newsid) {
            return false;
        }

        $tablename = "formulize_screen_listofentries";
        $result = parent::insertCloneIntoScreenTypeTable($sid, $newsid, $newtitle, $tablename);

        if (!$result) {
            return false;
        }
    }




    // THIS METHOD HANDLES ALL THE LOGIC ABOUT HOW TO ACTUALLY DISPLAY THIS TYPE OF SCREEN
    // $screen is a screen object
    // since the number of params for the render method can vary from screen type to screen type, this should take a single array that we unpack in the method, so the number of params is common to all types, ie: one array
    function render($screen, $entry, $loadThisView) {
        $previouslyRenderingScreen = $GLOBALS['formulize_screenCurrentlyRendering'];
        $formframe = $screen->getVar('frid') ? $screen->getVar('frid') : $screen->getVar('fid');
        $mainform = $screen->getVar('frid') ? $screen->getVar('fid') : "";
        include_once XOOPS_ROOT_PATH . "/modules/formulize/include/entriesdisplay.php";
        $GLOBALS['formulize_screenCurrentlyRendering'] = $screen;
        displayEntries($formframe, $mainform, $loadThisView, 0, 0, $screen);
        $GLOBALS['formulize_screenCurrentlyRendering'] = $previouslyRenderingScreen;
    }

    public function setDefaultListScreenVars($defaultListScreen, $defaultFormScreenId, $title, $fid)
    {
        // View
        $defaultListScreen->setVar('defaultview', 'all');
        $defaultListScreen->setVar('usecurrentviewlist', _formulize_DE_CURRENT_VIEW);
        $defaultListScreen->setVar('limitviews', serialize(array(0 => 'allviews')));
        $defaultListScreen->setVar('useworkingmsg', 1);
        $defaultListScreen->setVar('usescrollbox', 1);
        $defaultListScreen->setVar('entriesperpage', 10);
        $defaultListScreen->setVar('viewentryscreen', $defaultFormScreenId);
        // Headings
        $defaultListScreen->setVar('useheadings', 1);
        $defaultListScreen->setVar('repeatheaders', 5);
        $defaultListScreen->setVar('usesearchcalcmsgs', 1);
        $defaultListScreen->setVar('usesearch', 1);
        $defaultListScreen->setVar('columnwidth', 0);
        $defaultListScreen->setVar('textwidth', 35);
        $defaultListScreen->setVar('usecheckboxes', 0);
        $defaultListScreen->setVar('useviewentrylinks', 1);
        $defaultListScreen->setVar('desavetext', _formulize_SAVE);
        // Buttons
        $defaultListScreen->setVar('useaddupdate', _formulize_DE_ADDENTRY);
        $defaultListScreen->setVar('useaddmultiple', _formulize_DE_ADD_MULTIPLE_ENTRY);
        $defaultListScreen->setVar('useaddproxy', _formulize_DE_PROXYENTRY);
        $defaultListScreen->setVar('useexport', _formulize_DE_EXPORT);
        $defaultListScreen->setVar('useimport', _formulize_DE_IMPORT);
        $defaultListScreen->setVar('usenotifications', _formulize_DE_NOTBUTTON);
        $defaultListScreen->setVar('usechangecols', _formulize_DE_CHANGECOLS);
        $defaultListScreen->setVar('usecalcs', _formulize_DE_CALCS);
        $defaultListScreen->setVar('useadvcalcs', _formulize_DE_ADVCALCS);
        $defaultListScreen->setVar('useexportcalcs', _formulize_DE_EXPORT_CALCS);
        $defaultListScreen->setVar('useadvsearch', '');
        $defaultListScreen->setVar('useclone', _formulize_DE_CLONESEL);
        $defaultListScreen->setVar('usedelete', _formulize_DE_DELETESEL);
        $defaultListScreen->setVar('useselectall', _formulize_DE_SELALL);
        $defaultListScreen->setVar('useclearall', _formulize_DE_CLEARALL);
        $defaultListScreen->setVar('usereset', _formulize_DE_RESETVIEW);
        $defaultListScreen->setVar('usesave', _formulize_DE_SAVE);
        $defaultListScreen->setVar('usedeleteview', _formulize_DE_DELETE);
        $defaultListScreen->setVar('title', "Entries in '$title'");
        $defaultListScreen->setVar('fid', $fid);
        $defaultListScreen->setVar('frid', 0);
        $defaultListScreen->setVar('type', 'listOfEntries');
        $defaultListScreen->setVar('useToken', 1);
    }
}


// THIS FUNCTION RENDERS AN EXISTING ELEMENT WITHIN A TABLE, THAT WILL LATER BE ADDED TO AN INSERTBREAK METHOD ON THE ACTUAL EDITFORM
function addElementLOE($element, $table) {
    $table .= "<tr>";
    $table .= "<td class=\"head\">" . $element->getCaption();
    if ($element->getDescription() != '') {
        $table .= "<br /><br /><span style=\"font-weight: normal;\">".$element->getDescription()."</span>\n";
    }
    $table .= "</td>";
    $table .= "\n<td class=\"even\">" . $element->render() . "</td></tr>\n";
    return $table;
}


