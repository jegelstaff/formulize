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

	// see formulizeScreen::screenTypeName
	public static function screenTypeName() {
		return defined('_AM_SCREEN_SETTINGS_KIND_LIST') ? _AM_SCREEN_SETTINGS_KIND_LIST : 'List'; // not every language has it
	}

	// a list usually needs the room for its columns, so it has the full width of the window unless the site says otherwise
	public static function defaultPageWidth() {
		return 'full';
	}


    // the pixel width used in 'fixed' columnwidthmode when no width has been set
    const DEFAULT_FIXED_COLUMN_WIDTH = 250;
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
				$this->initVar("usechangeowner", XOBJ_DTYPE_TXTBOX, NULL, false, 255);
        $this->initVar("useheadings", XOBJ_DTYPE_INT);
        $this->initVar("usenumberofentries", XOBJ_DTYPE_INT);
        $this->initVar("usetogglerepeatdata", XOBJ_DTYPE_INT);
        $this->initVar("usesearch", XOBJ_DTYPE_INT); // 0 is off, 1 is on, 2 is on but hidden by default
        $this->initVar("usecheckboxes", XOBJ_DTYPE_INT); // 0 is default, 1 is all, 2 is none
        $this->initVar("useviewentrylinks", XOBJ_DTYPE_INT);
        $this->initVar("usescrollbox", XOBJ_DTYPE_INT);
        $this->initVar("usesearchcalcmsgs", XOBJ_DTYPE_INT); // 0 is neither, 1 is both, 2 is just search, 3 is just calc
        $this->initvar("hiddencolumns", XOBJ_DTYPE_ARRAY); // element ids from entire framework where the current values should be rendered as hidden elements
        $this->initVar("decolumns", XOBJ_DTYPE_ARRAY); // element ids from entire framework that should be rendered as elements
        $this->initVar("dedisplay", XOBJ_DTYPE_INT);
        $this->initVar("desavetext", XOBJ_DTYPE_TXTBOX, NULL, false, 255);
        $this->initVar("columnwidth", XOBJ_DTYPE_INT);
        // how the width of list-view columns is determined: 'natural' (each column sized to its content,
        // the default), 'full' (table stretches to 100% of the container, the old default behaviour), or
        // 'fixed' (every column is the same pixel width, taken from columnwidth)
        $this->initVar("columnwidthmode", XOBJ_DTYPE_TXTBOX, 'natural', false, 10);
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
        $this->initVar("entriesperpage", XOBJ_DTYPE_INT);
        $this->initVar("viewentryscreen", XOBJ_DTYPE_TXTBOX, NULL, false, 10);
        $this->initVar("fundamental_filters", XOBJ_DTYPE_ARRAY);
        // where the edit icon opens the entry form: 'drawer' (right-side drawer) or 'screen'
        // (navigate to the full edit form). Defaults to 'screen' (full form screen) so new
        // lists, and any list with no explicit value, open the edit form on the full screen.
        $this->initVar("editdestination", XOBJ_DTYPE_TXTBOX, 'screen', false, 10);
    }

    /**
     * advanceview holds the element in position 0 of each column, the other two are flat lists - see
     * formulizeScreen::elementColumnSettings().
     */
    protected function elementColumnSettings() {
        return array('advanceview' => 'rows', 'hiddencolumns' => 'list', 'decolumns' => 'list');
    }

}

#[AllowDynamicProperties]
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


    function insert($screen, $force=false) {
        $update = !$screen->getVar('sid') ? false : true;
        // the handle the screen has now, since its custom buttons' files are named for it - see carryCustomButtonFiles()
        $storedHandle = '';
        if ($update AND $result = $this->db->query("SELECT screen_handle FROM ".$this->db->prefix('formulize_screen')." WHERE sid = ".intval($screen->getVar('sid')))) {
            $row = $this->db->fetchArray($result);
            $storedHandle = $row ? (string) $row['screen_handle'] : '';
        }
        if (!$sid = parent::insert($screen, $force)) { // write the basic info to the db, handle cleaning vars and all that jazz.  Object passed by reference, so updates will have affected it in the other method.
            return false;
        }
        $screen->assignVar('sid', $sid);
        if ($storedHandle !== '' AND $storedHandle !== $screen->getVar('screen_handle', 'n')) {
            $this->carryCustomButtonFiles($screen->getVar('customactions'), $storedHandle, $screen->getVar('screen_handle', 'n'), true);
        }
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
								usechangeowner,
                useheadings,
                usenumberofentries,
                usetogglerepeatdata,
                usesearch,
                usecheckboxes,
                useviewentrylinks,
                usescrollbox,
                usesearchcalcmsgs,
                hiddencolumns,
                decolumns,
                desavetext,
                columnwidth,
                columnwidthmode,
                textwidth,
                customactions,
                entriesperpage,
                viewentryscreen,
                dedisplay,
                editdestination,
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
								%s,
                %u,
                %u,
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
                %s,
                %u,
                %s,
                %u,
                %s,
                %u,
                %s,
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
                $this->db->quoteString(serialize($screen->getVar('advanceview', 'n'))),
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
                $this->db->quoteString($screen->getVar('usechangeowner')),
                $screen->getVar('useheadings'),
                $screen->getVar('usenumberofentries'),
                $screen->getVar('usetogglerepeatdata'),
                $screen->getVar('usesearch'),
                $screen->getVar('usecheckboxes'),
                $screen->getVar('useviewentrylinks'),
                $screen->getVar('usescrollbox'),
                $screen->getVar('usesearchcalcmsgs'),
                $this->db->quoteString(serialize($screen->getVar('hiddencolumns', 'n'))),
                $this->db->quoteString(serialize($screen->getVar('decolumns', 'n'))),
                $this->db->quoteString($screen->getVar('desavetext')),
                $screen->getVar('columnwidth'), $this->db->quoteString($screen->getVar('columnwidthmode')), $screen->getVar('textwidth'),
                $this->db->quoteString(serialize($screen->getVar('customactions'))),
                $screen->getVar('entriesperpage'),
                $this->db->quoteString($screen->getVar('viewentryscreen')),
                $screen->getVar('dedisplay'),
                $this->db->quoteString($screen->getVar('editdestination')),
                $this->db->quoteString(serialize($screen->getVar('fundamental_filters')))
                );
        } else {
            $sql = sprintf("UPDATE %s SET useworkingmsg = %u, repeatheaders = %u, useaddupdate = %s, useaddmultiple = %s, useaddproxy = %s, usecurrentviewlist = %s, limitviews = %s, defaultview = %s, advanceview = %s, usechangecols = %s, usecalcs = %s, useadvcalcs = %s, useadvsearch = %s, useexport = %s, useexportcalcs = %s, useimport = %s, useclone = %s, usedelete = %s, useselectall = %s, useclearall = %s, usenotifications = %s, usereset = %s, usesave = %s, usedeleteview = %s, usechangeowner = %s, useheadings = %u, usenumberofentries = %u, usetogglerepeatdata = %u, usesearch = %u, usecheckboxes = %u, useviewentrylinks = %u, usescrollbox = %u, usesearchcalcmsgs = %u, hiddencolumns = %s, decolumns = %s, desavetext = %s, columnwidth = %u, columnwidthmode = %s, textwidth = %u, customactions = %s, entriesperpage = %u, viewentryscreen = %s, dedisplay = %u, editdestination = %s, fundamental_filters = %s WHERE sid = %u", $this->db->prefix('formulize_screen_listofentries'), $screen->getVar('useworkingmsg'), $screen->getVar('repeatheaders'), $this->db->quoteString($screen->getVar('useaddupdate')), $this->db->quoteString($screen->getVar('useaddmultiple')), $this->db->quoteString($screen->getVar('useaddproxy')), $this->db->quoteString($screen->getVar('usecurrentviewlist')), $this->db->quoteString(serialize($screen->getVar('limitviews'))), $this->db->quoteString(serialize($screen->getVar('defaultview'))), $this->db->quoteString(serialize($screen->getVar('advanceview', 'n'))), $this->db->quoteString($screen->getVar('usechangecols')), $this->db->quoteString($screen->getVar('usecalcs')), $this->db->quoteString($screen->getVar('useadvcalcs')), $this->db->quoteString($screen->getVar('useadvsearch')), $this->db->quoteString($screen->getVar('useexport')), $this->db->quoteString($screen->getVar('useexportcalcs')), $this->db->quoteString($screen->getVar('useimport')), $this->db->quoteString($screen->getVar('useclone')), $this->db->quoteString($screen->getVar('usedelete')), $this->db->quoteString($screen->getVar('useselectall')), $this->db->quoteString($screen->getVar('useclearall')), $this->db->quoteString($screen->getVar('usenotifications')), $this->db->quoteString($screen->getVar('usereset')), $this->db->quoteString($screen->getVar('usesave')), $this->db->quoteString($screen->getVar('usedeleteview')), $this->db->quoteString($screen->getVar('usechangeowner')), $screen->getVar('useheadings'), $screen->getVar('usenumberofentries'), $screen->getVar('usetogglerepeatdata'), $screen->getVar('usesearch'), $screen->getVar('usecheckboxes'), $screen->getVar('useviewentrylinks'), $screen->getVar('usescrollbox'), $screen->getVar('usesearchcalcmsgs'), $this->db->quoteString(serialize($screen->getVar('hiddencolumns', 'n'))), $this->db->quoteString(serialize($screen->getVar('decolumns', 'n'))), $this->db->quoteString($screen->getVar('desavetext')), $screen->getVar('columnwidth'), $this->db->quoteString($screen->getVar('columnwidthmode')), $screen->getVar('textwidth'), $this->db->quoteString(serialize($screen->getVar('customactions'))), $screen->getVar('entriesperpage'), $this->db->quoteString($screen->getVar('viewentryscreen')), $screen->getVar('dedisplay'), $this->db->quoteString($screen->getVar('editdestination')), $this->db->quoteString(serialize($screen->getVar('fundamental_filters'))), $screen->getVar('sid'));
        }
				if($force) {
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
        if(isset($_POST['screens-bottomtemplate'])) {
            $success2 = $this->writeTemplateToFile(trim($_POST['screens-bottomtemplate']), 'bottomtemplate', $screen);
        }
        $success3 = true;
        if(isset($_POST['screens-listtemplate'])) {
            $success3 = $this->writeTemplateToFile(trim($_POST['screens-listtemplate']), 'listtemplate', $screen);
        }
        $success4 = true;
        if(isset($_POST['screens-openlisttemplate'])) {
            $success4 = $this->writeTemplateToFile(trim($_POST['screens-openlisttemplate']), 'openlisttemplate', $screen);
        }
        $success5 = true;
        if(isset($_POST['screens-closelisttemplate'])) {
            $success5 = $this->writeTemplateToFile(trim($_POST['screens-closelisttemplate']), 'closelisttemplate', $screen);
        }

        if (!$success1 || !$success2 || !$success3 || !$success4 || !$success5) {
            return false;
        }

        return $sid;
    }


    //  THIS METHOD MIGHT BE MOVED UP A LEVEL TO THE PARENT CLASS
    function get($sid) {
        $sid = intval($sid);
        if ($screen = $this->takeTypedScreenHandoff($sid)) {
            return $screen;
        }
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

        $this->copyCustomButtonFiles($sid, $newsid);
    }

    /**
     * Give a cloned list copies of the code and HTML its custom buttons keep in files, since the clone has a handle
     * of its own - see carryCustomButtonFiles().
     * @param int $sid The list being cloned.
     * @param int $newsid The clone.
     * @return void
     */
    function copyCustomButtonFiles($sid, $newsid) {
        $handles = array();
        $result = $this->db->query("SELECT sid, screen_handle FROM ".$this->db->prefix('formulize_screen')." WHERE sid IN (".intval($sid).", ".intval($newsid).")");
        while($result AND $row = $this->db->fetchArray($result)) {
            $handles[intval($row['sid'])] = $row['screen_handle'];
        }
        $result = $this->db->query("SELECT customactions FROM ".$this->db->prefix('formulize_screen_listofentries')." WHERE sid = ".intval($sid));
        $row = $result ? $this->db->fetchArray($result) : false;
        if($row AND isset($handles[intval($sid)]) AND isset($handles[intval($newsid)])) {
            $this->carryCustomButtonFiles(unserialize((string) $row['customactions']), $handles[intval($sid)], $handles[intval($newsid)], false);
        }
    }

    /**
     * Carry the files a list's custom buttons keep their code and HTML in over to another screen handle. They are
     * named for the screen's handle - custom_code_<effect>_<button>_<screen handle>.php and custom_html_... - so a
     * list whose handle changes needs them moved, and a clone of it needs copies. A file already under the other
     * handle is left as it is.
     * @param array $buttons The list's custom buttons, as stored.
     * @param string $fromHandle The handle the files are named for.
     * @param string $toHandle The handle they are to be named for.
     * @param bool $move Whether to move the files, rather than copy them.
     * @return void
     */
    function carryCustomButtonFiles($buttons, $fromHandle, $toHandle, $move) {
        $buttons = is_string($buttons) ? unserialize($buttons) : $buttons;
        if(!is_array($buttons) OR $fromHandle === $toHandle) {
            return;
        }
        $codeDir = XOOPS_ROOT_PATH.'/modules/formulize/code/';
        foreach($buttons as $button) {
            if(!is_array($button) OR !isset($button['handle'])) {
                continue;
            }
            foreach(array_keys($button) as $effectId) {
                if(!is_numeric($effectId)) {
                    continue; // effects have numeric keys
                }
                foreach(array('custom_code_', 'custom_html_') as $prefix) {
                    $from = $codeDir.$prefix.$effectId.'_'.$button['handle'].'_'.$fromHandle.'.php';
                    $to = $codeDir.$prefix.$effectId.'_'.$button['handle'].'_'.$toHandle.'.php';
                    if(file_exists($from) AND !file_exists($to)) {
                        $move ? rename($from, $to) : copy($from, $to);
                    }
                }
            }
        }
    }




    // THIS METHOD HANDLES ALL THE LOGIC ABOUT HOW TO ACTUALLY DISPLAY THIS TYPE OF SCREEN
    // $screen is a screen object
    // since the number of params for the render method can vary from screen type to screen type, this should take a single array that we unpack in the method, so the number of params is common to all types, ie: one array
    function render($screen, $entry, $loadThisView) {
			$previouslyRenderingScreen = (isset($GLOBALS['formulize_screenCurrentlyRendering']) AND $GLOBALS['formulize_screenCurrentlyRendering']) ? $GLOBALS['formulize_screenCurrentlyRendering'] : null;
        $formframe = $screen->getVar('frid') ? $screen->getVar('frid') : $screen->getVar('fid');
        $mainform = $screen->getVar('frid') ? $screen->getVar('fid') : "";
        include_once XOOPS_ROOT_PATH . "/modules/formulize/include/entriesdisplay.php";
        $GLOBALS['formulize_screenCurrentlyRendering'] = $screen;
        displayEntries($formframe, $mainform, $loadThisView, 0, 0, $screen);
        $GLOBALS['formulize_screenCurrentlyRendering'] = $previouslyRenderingScreen;
    }

    /**
     * Set the default values on a new list screen. The settings with fixed values come from the site's theme's
     * screen-defaults.json (see formulizeScreenHandler::getThemeScreenDefaults). The ones here are the words, which
     * are in the site's language, what comes from the form and the site, and the list's empty content.
     *
     * @param object $defaultListScreen the new list screen
     * @param int|string $defaultFormScreenId the screen entries open in, or 'none'
     * @param object $formObject the form the screen is for
     * @return void
     */
    public function setDefaultListScreenVars($defaultListScreen, $defaultFormScreenId, $formObject) {
			global $xoopsConfig;
			$config_handler = xoops_gethandler('config');
			$formulizeConfig = $config_handler->getConfigsByCat(0, getFormulizeModId());
			// 'single' is a per-group array, so it has to be resolved before it can be compared. This is a
			// default screen being set up rather than something being rendered for a particular user, so it
			// is resolved from the point of view of the Registered Users group, which gives the base value
			// that applies to anyone without a group specific setting.
			$effectiveSingle = resolveEffectiveSingle($formObject->getVar('single'), array(XOOPS_GROUP_USERS));
			if($effectiveSingle != 'group' AND $effectiveSingle != 'user') {
				$addUpdateText = ($formObject->getVar('singular') ? sprintf(_formulize_DE_ADDSINGULAR, $formObject->getVar('singular')) : _formulize_DE_ADDENTRY);
			} else {
				$addUpdateText = ($formObject->getVar('singular') ? sprintf(_formulize_DE_UPDATESINGULAR, $formObject->getVar('singular')) : _formulize_DE_UPDATEENTRY);
			}
			$this->applyDefaultScreenVars($defaultListScreen, array(
				// View
				'defaultview' => serialize(array(XOOPS_GROUP_USERS => FORMULIZE_QUERY_SCOPE_GLOBAL)),
				'usecurrentviewlist' => _formulize_DE_CURRENT_VIEW,
				'viewentryscreen' => $defaultFormScreenId,
				// Headings
				'useviewentrylinks' => isset($formulizeConfig['formulizeDefaultEditIconStyle']) ? $formulizeConfig['formulizeDefaultEditIconStyle'] : FORMULIZE_EDIT_ICON_STYLE_PEN,
				'desavetext' => _formulize_SAVE,
				// Buttons
				'useaddupdate' => $addUpdateText,
				'useaddmultiple' => '', // to set default text: ($formObject->getVar('plural') ? sprintf(_formulize_DE_ADD_PLURAL_ENTRY, $formObject->getVar('plural')) : _formulize_DE_ADD_MULTIPLE_ENTRY));
				'useaddproxy' => _formulize_DE_PROXYENTRY,
				'useexport' => _formulize_DE_EXPORT,
				'useimport' => _formulize_DE_IMPORT,
				'usenotifications' => _formulize_DE_NOTBUTTON,
				'usechangecols' => _formulize_DE_CHANGECOLS,
				'usecalcs' => _formulize_DE_CALCS,
				'useadvcalcs' => _formulize_DE_ADVCALCS,
				'useexportcalcs' => _formulize_DE_EXPORT_CALCS,
				'useadvsearch' => '',
				'useclone' => _formulize_DE_CLONESEL,
				'usedelete' => _formulize_DE_DELETESEL,
				'useselectall' => _formulize_DE_SELALL,
				'useclearall' => _formulize_DE_CLEARALL,
				'usereset' => _formulize_DE_RESETVIEW,
				'usesave' => _formulize_DE_SAVE,
				'usedeleteview' => _formulize_DE_DELETE,
				'usechangeowner' => _formulize_DE_CHANGEOWNER,
				'title' => $formObject->getPlural(),
				'screen_handle' => $this->makeHandleUnique($formObject->getPlural()),
				'fid' => $formObject->getVar('fid'),
				'frid' => -1,
				'type' => 'listOfEntries',
				'theme' => $xoopsConfig['theme_set'],
				// the list's content: columns, custom buttons and filters. ARRAY-typed vars with no non-null initVar default
				'advanceview' => serialize(array()),
				'hiddencolumns' => serialize(array()),
				'decolumns' => serialize(array()),
				'customactions' => serialize(array()),
				'fundamental_filters' => serialize(array()),
			));
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


