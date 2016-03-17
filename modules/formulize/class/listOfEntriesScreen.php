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
    function formulizeListOfEntriesScreen() {
        $this->formulizeScreen();
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
        $this->initVar("defaultview", XOBJ_DTYPE_TXTBOX, NULL, false, 20);
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
    }
}


class formulizeListOfEntriesScreenHandler extends formulizeScreenHandler {
    var $db;

    function formulizeListOfEntriesScreenHandler(&$db) {
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

    // this function handles all the admin side ui for this kind of screen
    function editForm($screen, $fid) {
        // javascript required for reloading of form when framework selection changes
        print "\n<script type='text/javascript'>\n";

        print " function frameworkChange() {\n";
        print "     window.document.editscreenform.submit();\n";
        print " }\n";

        print " function confirmDeleteButton() {\n";
        print "     var answer = confirm ('" . _AM_FORMULIZE_CONFIRM_SCREEN_LOE_DELETE_BUTTON . "')\n";
        print "     if (answer) {\n";
        print "         return true;\n";
        print "     } else {\n";
        print "         return false;\n";
        print "     }\n";
        print " }\n";

        print " function confirmDeleteButtonEffect() {\n";
        print "     var answer = confirm ('" . _AM_FORMULIZE_CONFIRM_SCREEN_LOE_DELETE_BUTTONEFFECT . "')\n";
        print "     if (answer) {\n";
        print "         return true;\n";
        print "     } else {\n";
        print "         return false;\n";
        print "     }\n";
        print " }\n";

        print "</script>\n";

        $form = parent::editForm($screen, $fid);
        $form->addElement(new xoopsFormHidden('type', 'listOfEntries'));

        // from here on in, each part of this screen is rendered inside a different break.
        $configTable = "<table class=\"outer\" width=100% cellspacing=1 style=\"background: white;\">\n<tr><th colspan=2>" . _AM_FORMULIZE_SCREEN_LOE_CONFIGINTRO . "</th></tr>\n";
        $configTable .= "<tr><td class=\"head\" colspan=2><p><b>" . _AM_FORMULIZE_SCREEN_LOE_CONFIG_SECTION1 . "</b></p></td></tr>\n";

        // gather all the available views
        // setup an option list of all views, as well as one just for the currently selected Framework setting
        $framework_handler =& xoops_getmodulehandler('frameworks', 'formulize');
        $form_handler =& xoops_getmodulehandler('forms', 'formulize');
        $formObj = $form_handler->get($fid, true); // true causes all elements to be included even if they're not visible.
        $frameworks = $framework_handler->getFrameworksByForm($fid);
        $selectedFramework = isset($_POST['frid']) ? $_POST['frid'] : $screen->getVar('frid');
        $views = $formObj->getVar('views');
        $viewNames = $formObj->getVar('viewNames');
        $viewFrids = $formObj->getVar('viewFrids');
        $viewPublished = $formObj->getVar('viewPublished');
        $defaultViewOptions = array();
        $limitViewOptions = array();
        $defaultViewOptions['blank'] = _AM_FORMULIZE_SCREEN_LOE_BLANK_DEFAULTVIEW;
        $defaultViewOptions['mine'] = _AM_FORMULIZE_SCREEN_LOE_DVMINE;
        $defaultViewOptions['group'] = _AM_FORMULIZE_SCREEN_LOE_DVGROUP;
        $defaultViewOptions['all'] = _AM_FORMULIZE_SCREEN_LOE_DVALL;
        for($i=0;$i<count($views);$i++) {
            if(!$viewPublished[$i]) { continue; }
            $defaultViewOptions[$views[$i]] = $viewNames[$i];
            if($viewFrids[$i]) {
                $defaultViewOptions[$views[$i]] .= " (" . _AM_FORMULIZE_SCREEN_LOE_VIEW_ONLY_IN_FRAME . $frameworks[$viewFrids[$i]]->getVar('name') . ")";
            } else {
                $defaultViewOptions[$views[$i]] .= " (" . _AM_FORMULIZE_SCREEN_LOE_VIEW_ONLY_NO_FRAME . ")";
            }
        }
        $limitViewOptions['allviews'] = _AM_FORMULIZE_SCREEN_LOE_DEFAULTVIEWLIMIT;
        $limitViewOptions += $defaultViewOptions;
        unset($limitViewOptions['blank']);

        $defaultview = new xoopsFormSelect(_AM_FORMULIZE_SCREEN_LOE_DEFAULTVIEW, 'defaultview', $screen->getVar('defaultview'), 1, false);
        $defaultview->setDescription(_AM_FORMULIZE_SCREEN_LOE_DESC_DEFAULTVIEW);
        $defaultview->addOptionArray($defaultViewOptions);
        $configTable = addElementLOE($defaultview, $configTable);

        $usecurrentviewlistDefault = $screen->getVar('sid') ? $screen->getVar('usecurrentviewlist') : _formulize_DE_CURRENT_VIEW;
        $usecurrentviewlist = new xoopsFormText(_AM_FORMULIZE_SCREEN_LOE_CURRENTVIEWLIST, 'usecurrentviewlist', 20, 255, $usecurrentviewlistDefault);
        $usecurrentviewlist->setDescription(_AM_FORMULIZE_SCREEN_LOE_DESC_LEAVEBLANK_LIST);
        $configTable = addElementLOE($usecurrentviewlist, $configTable);

        $limitviewsDefault = $screen->getVar('sid') ? $screen->getVar('limitviews') : 'allviews';
        $limitviews = new xoopsFormSelect(_AM_FORMULIZE_SCREEN_LOE_LIMITVIEWS, 'limitviews', $limitviewsDefault, 8, true);
        $limitviews->setDescription(_AM_FORMULIZE_SCREEN_LOE_DESC_LIMITVIEWS);
        $limitviews->addOptionArray($limitViewOptions);
        $configTable = addElementLOE($limitviews, $configTable);

        $useworkingmsgDefault = $screen->getVar('sid') ? $screen->getVar('useworkingmsg') : 1;
        $useworkingmsg = new xoopsFormRadioYN(_AM_FORMULIZE_SCREEN_LOE_USEWORKING, 'useworkingmsg', $useworkingmsgDefault);
        $useworkingmsg->setDescription(_AM_FORMULIZE_SCREEN_LOE_DESC_USEWORKING);
        $configTable = addElementLOE($useworkingmsg, $configTable);

        $usescrollboxDefault = $screen->getVar('sid') ? $screen->getVar('usescrollbox') : 1;
        $usescrollbox = new xoopsFormRadioYN(_AM_FORMULIZE_SCREEN_LOE_USESCROLLBOX, 'usescrollbox', $usescrollboxDefault);
        $configTable = addElementLOE($usescrollbox, $configTable);

        $entriesperpageDefault = $screen->getVar('sid') ? $screen->getVar('entriesperpage') : 10;
        $entriesperpage = new xoopsFormText(_AM_FORMULIZE_SCREEN_LOE_ENTRIESPERPAGE, 'entriesperpage', 4, 4, $entriesperpageDefault);
        $entriesperpage->setDescription(_AM_FORMULIZE_SCREEN_LOE_DESC_ENTRIESPERPAGE);
        $configTable = addElementLOE($entriesperpage, $configTable);

        $viewentryscreenDefault = $screen->getVar('sid') ? $screen->getVar('viewentryscreen') : "none";

        // if the legacy value 0 is present, then convert that to "none" so the right value is selected by default in the list
        $viewentryscreenDefault = $viewentryscreenDefault === 0 ? "none" : $viewentryscreenDefault;

        // get the available screens
        $screen_handler = xoops_getmodulehandler('screen', 'formulize');
        $viewentryscreenOptionsDB = $screen_handler->getObjects(new Criteria("type", "multiPage"), $fid);
        $viewentryscreenOptions["none"] = _AM_FORMULIZE_SCREEN_LOE_VIEWENTRYSCREEN_DEFAULT;
        foreach($viewentryscreenOptionsDB as $thisViewEntryScreenOption) {
            $viewentryscreenOptions[$thisViewEntryScreenOption->getVar('sid')] = printSmart(trans($thisViewEntryScreenOption->getVar('title')), 100);
        }

        // get all the pageworks page IDs and include them too with a special prefix that will be picked up when this screen is rendered, so we don't confuse "view entry screens" and "view entry pageworks pages" -- added by jwe April 16 2009
        if(file_exists(XOOPS_ROOT_PATH."/modules/pageworks/index.php")) {
            global $xoopsDB;
            $pageworksSQL = "SELECT page_id, page_name, page_title FROM ".$xoopsDB->prefix("pageworks_pages")." ORDER BY page_name, page_title, page_id";
            $pageworksResult = $xoopsDB->query($pageworksSQL);
            while($pageworksArray = $xoopsDB->fetchArray($pageworksResult)) {
                $pageworksName = $pageworksArray['page_name'] ? $pageworksArray['page_name'] : $pageworksArray['page_title'];
                $viewentryscreenOptions["p".$pageworksArray['page_id']] = _AM_FORMULIZE_SCREEN_LOE_VIEWENTRYPAGEWORKS . " -- " . printSmart(trans($pageworksName), 85);
            }
        }
        $viewentryscreen = new xoopsFormSelect(_AM_FORMULIZE_SCREEN_LOE_VIEWENTRYSCREEN, 'viewentryscreen', $viewentryscreenDefault, 1, false); // dropdown

        $viewentryscreen->addOptionArray($viewentryscreenOptions);
        $configTable = addElementLOE($viewentryscreen, $configTable);

        $configTable .= "<tr><td class=\"head\" colspan=2><p><b>" . _AM_FORMULIZE_SCREEN_LOE_CONFIG_SECTION2 . "</b></p></td></tr>\n";

        $useheadingsDefault = $screen->getVar('sid') ? $screen->getVar('useheadings') : 1;
        $useheadings = new xoopsFormRadioYN(_AM_FORMULIZE_SCREEN_LOE_USEHEADINGS, 'useheadings', $useheadingsDefault);
        $useheadings->setDescription(_AM_FORMULIZE_SCREEN_LOE_DESC_USEHEADINGS);
        $configTable = addElementLOE($useheadings, $configTable);

        $repeatheadersDefault = $screen->getVar('sid') ? $screen->getVar('repeatheaders') : 5;
        $repeatheaders = new xoopsFormText(_AM_FORMULIZE_SCREEN_LOE_REPEATHEADERS, 'repeatheaders', 2, 2, $repeatheadersDefault);
        $repeatheaders->setDescription(_AM_FORMULIZE_SCREEN_LOE_DESC_REPEATHEADERS);
        $configTable = addElementLOE($repeatheaders, $configTable);

        $usesearchcalcmsgsDefault = $screen->getVar('sid') ? $screen->getVar('usesearchcalcmsgs') : 1;
        $usesearchcalcmsgs = new xoopsFormRadio(_AM_FORMULIZE_SCREEN_LOE_USESEARCHCALCMSGS, 'usesearchcalcmsgs', $usesearchcalcmsgsDefault);
        $usesearchcalcmsgs->addOptionArray(array(0=>_AM_FORMULIZE_SCREEN_LOE_USCM_NEITHER, 1=>_AM_FORMULIZE_SCREEN_LOE_USCM_BOTH, 2=>_AM_FORMULIZE_SCREEN_LOE_USCM_SEARCH, 3=>_AM_FORMULIZE_SCREEN_LOE_USCM_CALC));
        $configTable = addElementLOE($usesearchcalcmsgs, $configTable);

        $usesearchDefault = $screen->getVar('sid') ? $screen->getVar('usesearch') : 1;
        $usesearch = new xoopsFormRadioYN(_AM_FORMULIZE_SCREEN_LOE_USESEARCH, 'usesearch', $usesearchDefault);
        $usesearch->setDescription(_AM_FORMULIZE_SCREEN_LOE_DESC_USESEARCH);
        $configTable = addElementLOE($usesearch, $configTable);

        $columnwidthDefault = $screen->getVar('sid') ? $screen->getVar('columnwidth') : 0;
        $columnwidth = new xoopsFormText(_AM_FORMULIZE_SCREEN_LOE_COLUMNWIDTH, 'columnwidth', 3, 3, $columnwidthDefault);
        $columnwidth->setDescription(_AM_FORMULIZE_SCREEN_LOE_DESC_COLUMNWIDTH);
        $configTable = addElementLOE($columnwidth, $configTable);

        $textwidthDefault = $screen->getVar('sid') ? $screen->getVar('textwidth') : 35;
        $textwidth = new xoopsFormText(_AM_FORMULIZE_SCREEN_LOE_TEXTWIDTH, 'textwidth', 3, 3, $textwidthDefault);
        $textwidth->setDescription(_AM_FORMULIZE_SCREEN_LOE_DESC_TEXTWIDTH);
        $configTable = addElementLOE($textwidth, $configTable);

        $usecheckboxesDefault = $screen->getVar('sid') ? $screen->getVar('usecheckboxes') : 0;
        $usecheckboxes = new xoopsFormRadio(_AM_FORMULIZE_SCREEN_LOE_USECHECKBOXES, 'usecheckboxes', $usecheckboxesDefault);
        $usecheckboxes->addOptionArray(array(0=>_AM_FORMULIZE_SCREEN_LOE_UCHDEFAULT, 1=>_AM_FORMULIZE_SCREEN_LOE_UCHALL, 2=>_AM_FORMULIZE_SCREEN_LOE_UCHNONE));
        $usecheckboxes->setDescription(_AM_FORMULIZE_SCREEN_LOE_DESC_USECHECKBOXES);
        $configTable = addElementLOE($usecheckboxes, $configTable);

        $useviewentrylinksDefault = $screen->getVar('sid') ? $screen->getVar('useviewentrylinks') : 1;
        $useviewentrylinks = new xoopsFormRadioYN(_AM_FORMULIZE_SCREEN_LOE_USEVIEWENTRYLINKS, 'useviewentrylinks', $useviewentrylinksDefault);
        $configTable = addElementLOE($useviewentrylinks, $configTable);

        //set options for all elements in entire framework
        //also, collect the handles from a framework if any, and prep the list of possible handles/ids for the list template
        if($selectedFramework) {
            $allFids = $frameworks[$selectedFramework]->getVar('fids');
        } else {
            $allFids = array(0=>$fid);
        }
        $thisFidObj = "";
        $allFidObjs = array();
        $elementOptionsFid = array();
        $listTemplateHelp = array();
        $class = "odd";
        foreach($allFids as $thisFid) {
            unset($thisFidObj);
            if($fid == $thisFid) {
                $thisFidObj = $formObj;
            } else {
                $thisFidObj = $form_handler->get($thisFid, true); // true causes all elements to be included, even if they're not visible
            }
            $allFidObjs[$thisFid] = $thisFidObj; // for use later on
            $thisFidElements = $thisFidObj->getVar('elements');
            $thisFidCaptions = $thisFidObj->getVar('elementCaptions');
            $thisFidColheads = $thisFidObj->getVar('elementColheads');
            $thisFidHandles = $thisFidObj->getVar('elementHandles');
		    
            foreach($thisFidElements as $i=>$thisFidElement) {
                $elementHeading = $thisFidColheads[$i] ? $thisFidColheads[$i] : $thisFidCaptions[$i];
                $elementOptions[$thisFidHandles[$i]] = printSmart(trans(strip_tags($elementHeading)), 75);
                $elementOptionsFid[$thisFid][$thisFidElement] = printSmart(trans(strip_tags($elementHeading)), 75); // for passing to custom button logic, so we know all the element options for each form in framework
                $class = $class == "even" ? "odd" : "even";
                $listTemplateHelp[] = "<tr><td class=$class><nobr><b>" . printSmart(trans(strip_tags($elementHeading))) . "</b></nobr></td><td class=$class><nobr>".$thisFidHandles[$i]."</nobr></td></tr>";
            }
        }

        $hiddencolumns = new xoopsFormSelect(_AM_FORMULIZE_SCREEN_LOE_HIDDENCOLUMNS, 'hiddencolumns', $screen->getVar('hiddencolumns'), 10, true);
        $hiddencolumns->addOptionArray($elementOptions);
        $hiddencolumns->setDescription(_AM_FORMULIZE_SCREEN_LOE_DESC_HIDDENCOLUMNS);
        $configTable = addElementLOE($hiddencolumns, $configTable);

        $decolumns = new xoopsFormSelect(_AM_FORMULIZE_SCREEN_LOE_DECOLUMNS, 'decolumns', $screen->getVar('decolumns'), 10, true);
        $decolumns->addOptionArray($elementOptions);
        $decolumns->setDescription(_AM_FORMULIZE_SCREEN_LOE_DESC_DECOLUMNS);
        $configTable = addElementLOE($decolumns, $configTable);

        $desavetextDefault = $screen->getVar('sid') ? $screen->getVar('desavetext') : _formulize_SAVE;
        $desavetext = new xoopsFormText(_AM_FORMULIZE_SCREEN_LOE_DESAVETEXT, 'desavetext', 20, 255, $desavetextDefault);
        $configTable = addElementLOE($desavetext, $configTable);

        $configTable .= "</table>\n";

        $form->insertBreak($configTable, "head");

        $buttonTable = "<table class=\"outer\" width=100% cellspacing=1 style=\"background: white;\">\n<tr><th colspan=2>" . _AM_FORMULIZE_SCREEN_LOE_BUTTONINTRO . "</th></tr>\n";
        $buttonTable .= "<tr><td class=\"head\" colspan=2><p><b>" . _AM_FORMULIZE_SCREEN_LOE_BUTTON_SECTION1 . "</b></p></td></tr>\n";

        $useaddupdateDefault = $screen->getVar('sid') ? $screen->getVar('useaddupdate') : _formulize_DE_ADDENTRY;
        $useaddupdate = new xoopsFormText(_AM_FORMULIZE_SCREEN_LOE_BUTTON1 . _formulize_DE_ADDENTRY . "/" . _formulize_DE_UPDATEENTRY . _AM_FORMULIZE_SCREEN_LOE_BUTTON2, 'useaddupdate', 20, 255, $useaddupdateDefault);
        $useaddupdate->setDescription(_AM_FORMULIZE_SCREEN_LOE_DESC_LEAVEBLANK);
        $buttonTable = addElementLOE($useaddupdate, $buttonTable);

        $useaddmultipleDefault = $screen->getVar('sid') ? $screen->getVar('useaddmultiple') : _formulize_DE_ADD_MULTIPLE_ENTRY;
        $useaddmultiple = new xoopsFormText(_AM_FORMULIZE_SCREEN_LOE_BUTTON1 . _formulize_DE_ADD_MULTIPLE_ENTRY . _AM_FORMULIZE_SCREEN_LOE_BUTTON2, 'useaddmultiple', 20, 255, $useaddmultipleDefault);
        $useaddmultiple->setDescription(_AM_FORMULIZE_SCREEN_LOE_DESC_LEAVEBLANK);
        $buttonTable = addElementLOE($useaddmultiple, $buttonTable);

        $useaddproxyDefault = $screen->getVar('sid') ? $screen->getVar('useaddproxy') : _formulize_DE_PROXYENTRY;
        $useaddproxy = new xoopsFormText(_AM_FORMULIZE_SCREEN_LOE_BUTTON1 . _formulize_DE_PROXYENTRY . _AM_FORMULIZE_SCREEN_LOE_BUTTON2, 'useaddproxy', 20, 255, $useaddproxyDefault);
        $useaddproxy->setDescription(_AM_FORMULIZE_SCREEN_LOE_DESC_LEAVEBLANK);
        $buttonTable = addElementLOE($useaddproxy, $buttonTable);

        $useexportDefault = $screen->getVar('sid') ? $screen->getVar('useexport') : _formulize_DE_EXPORT;
        $useexport = new xoopsFormText(_AM_FORMULIZE_SCREEN_LOE_BUTTON1 . _formulize_DE_EXPORT . _AM_FORMULIZE_SCREEN_LOE_BUTTON2, 'useexport', 20, 255, $useexportDefault);
        $useexport->setDescription(_AM_FORMULIZE_SCREEN_LOE_DESC_LEAVEBLANK);
        $buttonTable = addElementLOE($useexport, $buttonTable);

        $useimportDefault = $screen->getVar('sid') ? $screen->getVar('useimport') : _formulize_DE_IMPORT;
        $useimport = new xoopsFormText(_AM_FORMULIZE_SCREEN_LOE_BUTTON1 . _formulize_DE_IMPORT . _AM_FORMULIZE_SCREEN_LOE_BUTTON2, 'useimport', 20, 255, $useimportDefault);
        $useimport->setDescription(_AM_FORMULIZE_SCREEN_LOE_DESC_LEAVEBLANK);
        $buttonTable = addElementLOE($useimport, $buttonTable);

        $usenotificationsDefault = $screen->getVar('sid') ? $screen->getVar('usenotifications') : _formulize_DE_NOTBUTTON;
        $usenotifications = new xoopsFormText(_AM_FORMULIZE_SCREEN_LOE_BUTTON1 . _formulize_DE_NOTBUTTON . _AM_FORMULIZE_SCREEN_LOE_BUTTON2, 'usenotifications', 20, 255, $usenotificationsDefault);
        $usenotifications->setDescription(_AM_FORMULIZE_SCREEN_LOE_DESC_LEAVEBLANK);
        $buttonTable = addElementLOE($usenotifications, $buttonTable);

        $buttonTable .= "<tr><td class=\"head\" colspan=2><p><b>" . _AM_FORMULIZE_SCREEN_LOE_BUTTON_SECTION2 . "</b></p></td></tr>\n";

        $usechangecolsDefault = $screen->getVar('sid') ? $screen->getVar('usechangecols') : _formulize_DE_CHANGECOLS;
        $usechangecols = new xoopsFormText(_AM_FORMULIZE_SCREEN_LOE_BUTTON1 . _formulize_DE_CHANGECOLS . _AM_FORMULIZE_SCREEN_LOE_BUTTON2, 'usechangecols', 20, 255, $usechangecolsDefault);
        $usechangecols->setDescription(_AM_FORMULIZE_SCREEN_LOE_DESC_LEAVEBLANK);
        $buttonTable = addElementLOE($usechangecols, $buttonTable);

        $usecalcsDefault = $screen->getVar('sid') ? $screen->getVar('usecalcs') : _formulize_DE_CALCS;
        $usecalcs = new xoopsFormText(_AM_FORMULIZE_SCREEN_LOE_BUTTON1 . _formulize_DE_CALCS . _AM_FORMULIZE_SCREEN_LOE_BUTTON2, 'usecalcs', 20, 255, $usecalcsDefault);
        $usecalcs->setDescription(_AM_FORMULIZE_SCREEN_LOE_DESC_LEAVEBLANK);
        $buttonTable = addElementLOE($usecalcs, $buttonTable);

        $useadvcalcsDefault = $screen->getVar('sid') ? $screen->getVar('useadvcalcs') : _formulize_DE_CALCS;
        $useadvcalcs = new xoopsFormText(_AM_FORMULIZE_SCREEN_LOE_BUTTON1 . _formulize_DE_CALCS . _AM_FORMULIZE_SCREEN_LOE_BUTTON2, 'useadvcalcs', 20, 255, $useadvcalcsDefault);
        $useadvcalcs->setDescription(_AM_FORMULIZE_SCREEN_LOE_DESC_LEAVEBLANK);
        $buttonTable = addElementLOE($useadvcalcs, $buttonTable);

        $useexportcalcsDefault = $screen->getVar('sid') ? $screen->getVar('useexportcalcs') : _formulize_DE_EXPORT_CALCS;
        $useexportcalcs = new xoopsFormText(_AM_FORMULIZE_SCREEN_LOE_BUTTON1 . _formulize_DE_EXPORT_CALCS . _AM_FORMULIZE_SCREEN_LOE_BUTTON2, 'useexportcalcs', 20, 255, $useexportcalcsDefault);
        $useexportcalcs->setDescription(_AM_FORMULIZE_SCREEN_LOE_DESC_LEAVEBLANK);
        $buttonTable = addElementLOE($useexportcalcs, $buttonTable);

        $useadvsearchDefault = $screen->getVar('sid') ? $screen->getVar('useadvsearch') : _formulize_DE_ADVSEARCH;
        $useadvsearch = new xoopsFormText(_AM_FORMULIZE_SCREEN_LOE_BUTTON1 . _formulize_DE_ADVSEARCH . _AM_FORMULIZE_SCREEN_LOE_BUTTON2, 'useadvsearch', 20, 255, $useadvsearchDefault);
        $useadvsearch->setDescription(_AM_FORMULIZE_SCREEN_LOE_DESC_LEAVEBLANK);
        $buttonTable = addElementLOE($useadvsearch, $buttonTable);

        $usecloneDefault = $screen->getVar('sid') ? $screen->getVar('useclone') : _formulize_DE_CLONESEL;
        $useclone = new xoopsFormText(_AM_FORMULIZE_SCREEN_LOE_BUTTON1 . _formulize_DE_CLONESEL . _AM_FORMULIZE_SCREEN_LOE_BUTTON2, 'useclone', 20, 255, $usecloneDefault);
        $useclone->setDescription(_AM_FORMULIZE_SCREEN_LOE_DESC_LEAVEBLANK);
        $buttonTable = addElementLOE($useclone, $buttonTable);

        $usedeleteDefault = $screen->getVar('sid') ? $screen->getVar('usedelete') : _formulize_DE_DELETESEL;
        $usedelete = new xoopsFormText(_AM_FORMULIZE_SCREEN_LOE_BUTTON1 . _formulize_DE_DELETESEL . _AM_FORMULIZE_SCREEN_LOE_BUTTON2, 'usedelete', 20, 255, $usedeleteDefault);
        $usedelete->setDescription(_AM_FORMULIZE_SCREEN_LOE_DESC_LEAVEBLANK);
        $buttonTable = addElementLOE($usedelete, $buttonTable);

        $useselectallDefault = $screen->getVar('sid') ? $screen->getVar('useselectall') : _formulize_DE_SELALL;
        $useselectall = new xoopsFormText(_AM_FORMULIZE_SCREEN_LOE_BUTTON1 . _formulize_DE_SELALL . _AM_FORMULIZE_SCREEN_LOE_BUTTON2, 'useselectall', 20, 255, $useselectallDefault);
        $useselectall->setDescription(_AM_FORMULIZE_SCREEN_LOE_DESC_LEAVEBLANK);
        $buttonTable = addElementLOE($useselectall, $buttonTable);

        $useclearallDefault = $screen->getVar('sid') ? $screen->getVar('useclearall') : _formulize_DE_CLEARALL;
        $useclearall = new xoopsFormText(_AM_FORMULIZE_SCREEN_LOE_BUTTON1 . _formulize_DE_CLEARALL . _AM_FORMULIZE_SCREEN_LOE_BUTTON2, 'useclearall', 20, 255, $useclearallDefault);
        $useclearall->setDescription(_AM_FORMULIZE_SCREEN_LOE_DESC_LEAVEBLANK);
        $buttonTable = addElementLOE($useclearall, $buttonTable);

        $useresetDefault = $screen->getVar('sid') ? $screen->getVar('usereset') : _formulize_DE_RESETVIEW;
        $usereset = new xoopsFormText(_AM_FORMULIZE_SCREEN_LOE_BUTTON1 . _formulize_DE_RESETVIEW . _AM_FORMULIZE_SCREEN_LOE_BUTTON2, 'usereset', 20, 255, $useresetDefault);
        $usereset->setDescription(_AM_FORMULIZE_SCREEN_LOE_DESC_LEAVEBLANK);
        $buttonTable = addElementLOE($usereset, $buttonTable);

        $usesaveDefault = $screen->getVar('sid') ? $screen->getVar('usesave') : _formulize_DE_SAVE;
        $usesave = new xoopsFormText(_AM_FORMULIZE_SCREEN_LOE_BUTTON1 . _formulize_DE_SAVE . _AM_FORMULIZE_SCREEN_LOE_BUTTON2, 'usesave', 20, 255, $usesaveDefault);
        $usesave->setDescription(_AM_FORMULIZE_SCREEN_LOE_DESC_LEAVEBLANK);
        $buttonTable = addElementLOE($usesave, $buttonTable);

        $usedeleteviewDefault = $screen->getVar('sid') ? $screen->getVar('usedeleteview') : _formulize_DE_DELETE;
        $usedeleteview = new xoopsFormText(_AM_FORMULIZE_SCREEN_LOE_BUTTON1 . _formulize_DE_DELETE . _AM_FORMULIZE_SCREEN_LOE_BUTTON2, 'usedeleteview', 20, 255, $usedeleteviewDefault);
        $usedeleteview->setDescription(_AM_FORMULIZE_SCREEN_LOE_DESC_LEAVEBLANK);
        $buttonTable = addElementLOE($usedeleteview, $buttonTable);

        $buttonTable .= "</table>\n";

        $form->insertBreak($buttonTable, "head");

        $customButtonTable = "<table class=\"outer\" width=100% cellspacing=1 style=\"background: white;\">\n<tr><th colspan=2>" . _AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTONINTRO . "</th></tr>\n";
        $newButtonButton = new xoopsFormButton('', 'addCustomButton', _AM_FORMULIZE_SCREEN_LOE_ADDCUSTOMBUTTON, 'submit');
        $customButtonTable .= "<tr><td class=\"head\" colspan=2><p><b>" . _AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTONINTRO2 . "</b></p>\n<p>" . $newButtonButton->render() . "</p></td></tr>\n";

        $caid = 0;
        $customButtonUI = "";
        foreach($screen->getVar('customactions') as $caid=>$thisCustomAction) {
            if(!isset($_POST['deleteButton'.$caid])) {
                $customButtonUI .= addCustomButton($caid, $thisCustomAction, $allFids, $allFidObjs, $elementOptionsFid, $fid);
            }
        }
        if(isset($_POST['addCustomButton'])) {
            if($customButtonUI != "") { $caid++; } // increment the caid, if there are already buttons
            $customButtonUI .= addCustomButton($caid, "", $allFids, $allFidObjs, $elementOptionsFid, $fid);
        }

        $customButtonTable .= $customButtonUI;
        $customButtonTable .= "</table>\n";

        $form->insertBreak($customButtonTable, "head");

        $templateTable = "<table class=\"outer\" width=100% cellspacing=1 style=\"background: white;\">\n<tr><th colspan=2>" . _AM_FORMULIZE_SCREEN_LOE_TEMPLATEINTRO . "</th></tr>\n";
        $templateTable .= "<tr><td class=\"head\" colspan=2><p><b>" . _AM_FORMULIZE_SCREEN_LOE_TEMPLATEINTRO2 . "</b></p></td></tr>\n";

        $toptemplateDefault = $screen->getVar('sid') ? $screen->getTemplate('toptemplate') : "";
        $toptemplate = new xoopsFormTextArea(_AM_FORMULIZE_SCREEN_LOE_TOPTEMPLATE, 'toptemplate', $screen->getTemplate('toptemplate'), 20, 65);
        $toptemplate->setDescription(_AM_FORMULIZE_SCREEN_LOE_DESC_TOPTEMPLATE);
        $templateTable = addElementLOE($toptemplate, $templateTable);

        $listtemplateDefault = $screen->getVar('sid') ? $screen->getTemplate('listtemplate') : "";
        $listtemplate = new xoopsFormTextArea(_AM_FORMULIZE_SCREEN_LOE_LISTTEMPLATE, 'listtemplate', $screen->getTemplate('listtemplate'), 20, 65);
        $elementList = "<br /><br />";
        $elementList .= $selectedFramework ? _AM_FORMULIZE_SCREEN_LOE_LISTTEMPLATE_HELPINTRO_FRAMEWORK : _AM_FORMULIZE_SCREEN_LOE_LISTTEMPLATE_HELPINTRO_FORM;
        $elementList .= "<br /><br /><div class=scrollbox style=\"height: 250px; width: 400px; overflow: scroll;\">\n";
        $elementList .= "<table><tr><td style=\"vertical-align: top;\"><table class=outer>\n";
        $secondColumn = false;
        for($i=0;$i<count($listTemplateHelp);$i++) {
            if($i > count($listTemplateHelp)/2 AND $secondColumn == false) {
                $elementList .= "</table></td><td style=\"vertical-align: top;\"><table class=outer>\n";
                $secondColumn = true;
            }
            $elementList .= $listTemplateHelp[$i] . "\n";
        }
        $elementList .= "</table></td></tr></table></div>";
        $listtemplate->setDescription(_AM_FORMULIZE_SCREEN_LOE_DESC_LISTTEMPLATE . $elementList);
        $templateTable = addElementLOE($listtemplate, $templateTable);

        $bottomtemplateDefault = $screen->getVar('sid') ? $screen->getTemplate('bottomtemplate') : "";
        $bottomtemplate = new xoopsFormTextArea(_AM_FORMULIZE_SCREEN_LOE_BOTTOMTEMPLATE, 'bottomtemplate', $screen->getTemplate('bottomtemplate'), 20, 65);
        $templateTable = addElementLOE($bottomtemplate, $templateTable);

        $templateTable .= "</table>\n";

        $form->insertBreak($templateTable, "head");

        return $form;
    }


    function saveForm(&$screen, $fid) {
        // get rid of magic quotes if necessary
        if(get_magic_quotes_gpc()) {
            $valueIsArray = false;
            foreach($_POST as $k=>$v) {
                if(is_array($v)) {
                    foreach($v as $subK=>$subV) {
                        $_POST[$k][$subK] = stripslashes($subV);
                    }
                } else {
                    $_POST[$k] = stripslashes($v);
                }
            }
        }
        $vars['type'] = 'listOfEntries';
        $vars['title'] = $_POST['title'];
        $vars['fid'] = $_POST['fid'];
        $vars['frid'] = $_POST['frid'];
        $vars['useworkingmsg'] = $_POST['useworkingmsg'];
        $vars['repeatheaders'] = $_POST['repeatheaders'];
        $vars['useaddupdate'] = $_POST['useaddupdate'];
        $vars['useaddmultiple'] = $_POST['useaddmultiple'];
        $vars['useaddproxy'] = $_POST['useaddproxy'];
        $vars['usecurrentviewlist'] = $_POST['usecurrentviewlist'];
        $vars['limitviews'] = serialize($_POST['limitviews']);
        $vars['defaultview'] = $_POST['defaultview'];
        $vars['usechangecols'] = $_POST['usechangecols'];
        $vars['usecalcs'] = $_POST['usecalcs'];
        $vars['useadvcalcs'] = $_POST['useadvcalcs'];
        $vars['useadvsearch'] = $_POST['useadvsearch'];
        $vars['useexport'] = $_POST['useexport'];
        $vars['useexportcalcs'] = $_POST['useexportcalcs'];
        $vars['useimport'] = $_POST['useimport'];
        $vars['useclone'] = $_POST['useclone'];
        $vars['usedelete'] = $_POST['usedelete'];
        $vars['useselectall'] = $_POST['useselectall'];
        $vars['useclearall'] = $_POST['useclearall'];
        $vars['usenotifications'] = $_POST['usenotifications'];
        $vars['usereset'] = $_POST['usereset'];
        $vars['usesave'] = $_POST['usesave'];
        $vars['usedeleteview'] = $_POST['usedeleteview'];
        $vars['useheadings'] = $_POST['useheadings'];
        $vars['usesearch'] = $_POST['usesearch'];
        $vars['usecheckboxes'] = $_POST['usecheckboxes'];
        $vars['useviewentrylinks'] = $_POST['useviewentrylinks'];
        $vars['usescrollbox'] = $_POST['usescrollbox'];
        $vars['usesearchcalcmsgs'] = $_POST['usesearchcalcmsgs'];
        $vars['hiddencolumns'] = serialize($_POST['hiddencolumns']);
        $vars['decolumns'] = serialize($_POST['decolumns']);
        $vars['desavetext'] = $_POST['desavetext'];
        $vars['columnwidth'] = $_POST['columnwidth'];
        $vars['textwidth'] = $_POST['textwidth'];
        $customactions = array();
        foreach($_POST as $k=>$v) {
            if(strstr($k, "_")) {
                $cadata = explode("_", $k); // 0 is key name, 1 is the action id, 2 is the effect id if any
                if(isset($cadata[2])) {
                    $customactions[$cadata[1]][$cadata[2]][$cadata[0]] = $v;
                }else{
                    $customactions[$cadata[1]][$cadata[0]] = $v;
                }
            }
        }

        $vars['customactions'] = serialize($customactions);
        $vars['toptemplate'] = htmlspecialchars(trim($_POST['toptemplate']));
        $vars['listtemplate'] = htmlspecialchars(trim($_POST['listtemplate']));
        $vars['bottomtemplate'] = htmlspecialchars(trim($_POST['bottomtemplate']));
        $vars['entriesperpage'] = $_POST['entriesperpage'];
        $vars['viewentryscreen'] = $_POST['viewentryscreen'];
        $screen->assignVars($vars);
        $sid = $this->insert($screen);
        if(!$sid) {
            print "Error: the information could not be saved in the database.";
        }
        $screen->assignVar('sid', $sid);
    }


    function insert($screen) {
        $update = ($screen->getVar('sid') == 0) ? false : true;
        if (!$sid = parent::insert($screen)) { // write the basic info to the db, handle cleaning vars and all that jazz.  Object passed by reference, so updates will have affected it in the other method.
            return false;
        }
        $screen->assignVar('sid', $sid);
        if (!$update) {
            $sql = sprintf("INSERT INTO %s (sid, useworkingmsg, repeatheaders, useaddupdate, useaddmultiple, useaddproxy, usecurrentviewlist, limitviews, defaultview, usechangecols, usecalcs, useadvcalcs, useadvsearch, useexport, useexportcalcs, useimport, useclone, usedelete, useselectall, useclearall, usenotifications, usereset, usesave, usedeleteview, useheadings, usesearch, usecheckboxes, useviewentrylinks, usescrollbox, usesearchcalcmsgs, hiddencolumns, decolumns, desavetext, columnwidth, textwidth, customactions, toptemplate, listtemplate, bottomtemplate, entriesperpage, viewentryscreen, dedisplay) VALUES (%u, %u, %u, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %u, %u, %u, %u, %u, %u, %s, %s, %s, %u, %u, %s, %s, %s, %s, %u, %s, %u)", $this->db->prefix('formulize_screen_listofentries'), $screen->getVar('sid'), $screen->getVar('useworkingmsg'), $screen->getVar('repeatheaders'), $this->db->quoteString($screen->getVar('useaddupdate')), $this->db->quoteString($screen->getVar('useaddmultiple')), $this->db->quoteString($screen->getVar('useaddproxy')), $this->db->quoteString($screen->getVar('usecurrentviewlist')), $this->db->quoteString(serialize($screen->getVar('limitviews'))), $this->db->quoteString($screen->getVar('defaultview')), $this->db->quoteString($screen->getVar('usechangecols')), $this->db->quoteString($screen->getVar('usecalcs')), $this->db->quoteString($screen->getVar('useadvcalcs')), $this->db->quoteString($screen->getVar('useadvsearch')), $this->db->quoteString($screen->getVar('useexport')), $this->db->quoteString($screen->getVar('useexportcalcs')), $this->db->quoteString($screen->getVar('useimport')), $this->db->quoteString($screen->getVar('useclone')), $this->db->quoteString($screen->getVar('usedelete')), $this->db->quoteString($screen->getVar('useselectall')), $this->db->quoteString($screen->getVar('useclearall')), $this->db->quoteString($screen->getVar('usenotifications')), $this->db->quoteString($screen->getVar('usereset')), $this->db->quoteString($screen->getVar('usesave')), $this->db->quoteString($screen->getVar('usedeleteview')), $screen->getVar('useheadings'), $screen->getVar('usesearch'), $screen->getVar('usecheckboxes'), $screen->getVar('useviewentrylinks'), $screen->getVar('usescrollbox'), $screen->getVar('usesearchcalcmsgs'), $this->db->quoteString(serialize($screen->getVar('hiddencolumns'))), $this->db->quoteString(serialize($screen->getVar('decolumns'))), $this->db->quoteString($screen->getVar('desavetext')), $screen->getVar('columnwidth'), $screen->getVar('textwidth'), $this->db->quoteString(serialize($screen->getVar('customactions'))), $this->db->quoteString($screen->getVar('toptemplate')), $this->db->quoteString($screen->getVar('listtemplate')), $this->db->quoteString($screen->getVar('bottomtemplate')), $screen->getVar('entriesperpage'), $this->db->quoteString($screen->getVar('viewentryscreen')), $screen->getVar('dedisplay'));
        } else {
            $sql = sprintf("UPDATE %s SET useworkingmsg = %u, repeatheaders = %u, useaddupdate = %s, useaddmultiple = %s, useaddproxy = %s, usecurrentviewlist = %s, limitviews = %s, defaultview = %s, usechangecols = %s, usecalcs = %s, useadvcalcs = %s, useadvsearch = %s, useexport = %s, useexportcalcs = %s, useimport = %s, useclone = %s, usedelete = %s, useselectall = %s, useclearall = %s, usenotifications = %s, usereset = %s, usesave = %s, usedeleteview = %s, useheadings = %u, usesearch = %u, usecheckboxes = %u, useviewentrylinks = %u, usescrollbox = %u, usesearchcalcmsgs = %u, hiddencolumns = %s, decolumns = %s, desavetext = %s, columnwidth = %u, textwidth = %u, customactions = %s, toptemplate = %s, listtemplate = %s, bottomtemplate = %s, entriesperpage = %u, viewentryscreen = %s, dedisplay = %u WHERE sid = %u", $this->db->prefix('formulize_screen_listofentries'), $screen->getVar('useworkingmsg'), $screen->getVar('repeatheaders'), $this->db->quoteString($screen->getVar('useaddupdate')), $this->db->quoteString($screen->getVar('useaddmultiple')), $this->db->quoteString($screen->getVar('useaddproxy')), $this->db->quoteString($screen->getVar('usecurrentviewlist')), $this->db->quoteString(serialize($screen->getVar('limitviews'))), $this->db->quoteString($screen->getVar('defaultview')), $this->db->quoteString($screen->getVar('usechangecols')), $this->db->quoteString($screen->getVar('usecalcs')), $this->db->quoteString($screen->getVar('useadvcalcs')), $this->db->quoteString($screen->getVar('useadvsearch')), $this->db->quoteString($screen->getVar('useexport')), $this->db->quoteString($screen->getVar('useexportcalcs')), $this->db->quoteString($screen->getVar('useimport')), $this->db->quoteString($screen->getVar('useclone')), $this->db->quoteString($screen->getVar('usedelete')), $this->db->quoteString($screen->getVar('useselectall')), $this->db->quoteString($screen->getVar('useclearall')), $this->db->quoteString($screen->getVar('usenotifications')), $this->db->quoteString($screen->getVar('usereset')), $this->db->quoteString($screen->getVar('usesave')), $this->db->quoteString($screen->getVar('usedeleteview')), $screen->getVar('useheadings'), $screen->getVar('usesearch'), $screen->getVar('usecheckboxes'), $screen->getVar('useviewentrylinks'), $screen->getVar('usescrollbox'), $screen->getVar('usesearchcalcmsgs'), $this->db->quoteString(serialize($screen->getVar('hiddencolumns'))), $this->db->quoteString(serialize($screen->getVar('decolumns'))), $this->db->quoteString($screen->getVar('desavetext')), $screen->getVar('columnwidth'), $screen->getVar('textwidth'), $this->db->quoteString(serialize($screen->getVar('customactions'))), $this->db->quoteString($screen->getVar('toptemplate')), $this->db->quoteString($screen->getVar('listtemplate')), $this->db->quoteString($screen->getVar('bottomtemplate')), $screen->getVar('entriesperpage'), $this->db->quoteString($screen->getVar('viewentryscreen')), $screen->getVar('dedisplay'), $screen->getVar('sid'));
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
        $formframe = $screen->getVar('frid') ? $screen->getVar('frid') : $screen->getVar('fid');
        $mainform = $screen->getVar('frid') ? $screen->getVar('fid') : "";
        include_once XOOPS_ROOT_PATH . "/modules/formulize/include/entriesdisplay.php";
        displayEntries($formframe, $mainform, $loadThisView, 0, 0, $screen);
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


// THIS FUNCTION SETS UP THE UI FOR A CUSTOM BUTTON, BASED ON THE CUSTOM ACTION ARRAY SENT
// fids is an array of all forms currently used on the screen.  First form is mainform.
// elementOptions is an array of arrays of all the elements in each form in the framework.  The fid is the top level key.
function addCustomButton($caid, $thisCustomAction, $allFids, $allFidObjs, $elementOptions, $fid) {

    // set defaults to the current action, if there is one, or the empty defaults if this is a new action
    $headerText = $thisCustomAction ? _AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON . " &mdash; " . $thisCustomAction['handle'] : _AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_NEW;
    $deleteButtonButton = new xoopsFormButton('', 'deleteButton'.$caid, _AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_DELETE, 'submit');
    $headerText .= "&nbsp;&nbsp&nbsp;" . $deleteButtonButton->render();
    $handleDefault = $thisCustomAction ? $thisCustomAction['handle'] : "";
    $textDefault = $thisCustomAction ? $thisCustomAction['buttontext'] : "";
    $messageTextDefault = $thisCustomAction ? $thisCustomAction['messagetext'] : "";
    $appearInlineDefault = $thisCustomAction ? $thisCustomAction['appearinline'] : 0;
    $applyToDefault = $thisCustomAction ? $thisCustomAction['applyto'] : "inline";

    $caTable = "<tr><td><table class=\"outer\" width=100% cellspacing=1 style=\"background: white;\">\n";
    $caTable .= "<tr><th colspan=2>$headerText</th></tr>\n";

    $caHandle = new xoopsFormText(_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_HANDLE, 'handle_'.$caid, 20, 255, $handleDefault);
    $caTable = addElementLOE($caHandle, $caTable);
    $caText = new xoopsFormText(_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_BUTTONTEXT, 'buttontext_'.$caid, 20, 255, $textDefault);
    $caTable = addElementLOE($caText, $caTable);
    $caMessageText = new xoopsFormText(_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_MESSAGETEXT, 'messagetext_'.$caid, 20, 255, $messageTextDefault);
    $caTable = addElementLOE($caMessageText, $caTable);
    $caAppearInline = new xoopsFormRadioYN(_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_INLINE, 'appearinline_'.$caid, $appearInlineDefault);
    $caAppearInline->setDescription(_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_INLINE_DESC);
    $caTable = addElementLOE($caAppearInline, $caTable);
    $caApplyTo = new xoopsFormSelect(_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_APPLYTO, 'applyto_'.$caid, $applyToDefault);
    // pay attention to allFids and if there is more than one form, then we include the option to have this apply to a new entry in the other forms (one option for each of the others)
    $applyToOptions = array('inline'=>_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_APPLYTO_INLINE, 'selected'=>_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_APPLYTO_SELECTED, 'all'=>_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_APPLYTO_ALL, 'new'=>_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_APPLYTO_NEW, 'new_per_selected'=>_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_APPLYTO_NEWPERSELECTED);
    if(count($allFids) > 1) {
        foreach ($allFids as $i=>$thisFid) {
            if($thisFid == $fid) { continue; } // don't treat the current form as if it's an 'other' form
            $applyToOptions['new_'.$thisFid] = _AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_APPLYTO_NEW_OTHER . printSmart($allFidObjs[$thisFid]->getVar('title'), 20) . "'";
            $applyToOptions['new_per_selected_'.$thisFid] = _AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_APPLYTO_NEW_OTHER . printSmart($allFidObjs[$thisFid]->getVar('title'), 20) . _AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_APPLYTO_NEWPERSELECTED_OTHER;
        }
    }
    $applyToOptions['custom_code'] = _AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_APPLYTO_CUSTOM_CODE;
    $applyToOptions['custom_html'] = _AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_APPLYTO_CUSTOM_HTML;
    $caApplyTo->addOptionArray($applyToOptions);
    $caTable = addElementLOE($caApplyTo, $caTable);
    $caNewEffectButton = new xoopsFormButton('', 'addCustomButtonEffect'.$caid, _AM_FORMULIZE_SCREEN_LOE_ADDCUSTOMBUTTON_EFFECT, 'submit'); // note, no _ in name so we don't roll this up into the array stored in the DB
    $caTable .= "<tr><td class=\"head\">&nbsp;</td><td class=\"even\">" . $caNewEffectButton->render() . "</td></tr>\n";
    $effectid = 0;
    $effectTable = "";
    foreach($thisCustomAction as $effectid=>$effectProperties) {
        if(!is_numeric($effectid)) { continue; } // ignore the handle, messagetext, etc.
        if(!isset($_POST['deleteButton'.$caid.'Effect'.$effectid])) {
            $effectTable .= addCustomButtonEffect($caid, $effectid, $thisCustomAction, $allFids, $elementOptions, $applyToDefault, $fid);
        }
    }
    if($effectid) { // may possibly used in the future to determine special events in the case that an action button has effects already.  ie: reload the page so that effects are updated based on changes to the base settings for a button.
        print "<input type=\"hidden\" name=\"existingeffect\"".$caid."\" value=1></input>\n";
    }
    if(isset($_POST['addCustomButtonEffect'.$caid])) {
        $effectid = $effectTable != "" ? $effectid+1 : 0; // if there's effects already, increment, otherwise start at 0.  Cannot do same as above when custom button ui is called, since the non-numeric keys of the array will be looped through immediately above, resulting in applyto being considered the current $effectid
        $effectTable .= addCustomButtonEffect($caid, $effectid, "", $allFids, $elementOptions, $applyToDefault, $fid);
    }
    $caTable .= $effectTable;
    $caTable .= "</table></td></tr>\n";
    return $caTable;
}


// THIS FUNCTION SETS UP THE UI FOR A CUSTOM BUTTON's EFFECT, BASED ON THE CUSTOM ACTION ARRAY SENT
function addCustomButtonEffect($caid, $effectid, $thisCustomAction, $allFids, $elementOptions, $applyToDefault, $fid) {
    // set defaults to the current effect, if there is one, or the empty defaults if this is a new effect
    $effectNumber = $effectid+1;
    $headerText = _AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_EFFECT . " $effectNumber<br /><br />";
    if($_POST['applyto_'.$caid] == 'custom_code' OR $applyToDefault == 'custom_code') {
        $headerText .= _AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_EFFECT_CUSTOM_CODE_DESC;
    } elseif($_POST['applyto_'.$caid] == 'custom_html' OR $applyToDefault == 'custom_html') {
        $headerText .= _AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_EFFECT_CUSTOM_HTML_DESC;
    } else {
        $headerText .= _AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_EFFECT_DESC;
    }

    $deleteEffectButton = new xoopsFormButton('', 'deleteButton'.$caid.'Effect'.$effectid, _AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_EFFECT_DELETE, 'submit');
    $headerText .= "<br /><br />" . $deleteEffectButton->render();
    $elementDefault = $thisCustomAction ? $thisCustomAction[$effectid]['element'] : "";
    $actionDefault = $thisCustomAction ? $thisCustomAction[$effectid]['action'] : "";
    $valueDefault = $thisCustomAction ? $thisCustomAction[$effectid]['value'] : "";
    $codeDefault = $thisCustomAction ? $thisCustomAction[$effectid]['code'] : "";
    $htmlDefault = $thisCustomAction ? $thisCustomAction[$effectid]['html'] : "";

    $effectTable = "<tr><td class=\"head\">$headerText</td><td><table class=\"outer\" width=\"100%\" cellspacing=1 style=\"background: white;\">\n";

    if((isset($_POST['applyto_'.$caid]) AND $_POST['applyto_'.$caid] == 'custom_code') OR $applyToDefault == 'custom_code') {
        $code = new xoopsFormTextArea('', 'code_'.$caid.'_'.$effectid, $codeDefault, 13, 60);
            $code->setExtra("wrap=off");
        $effectTable .= "\n<td class=\"even\">" . $code->render() . "</td>";
    } elseif((isset($_POST['applyto_'.$caid]) AND $_POST['applyto_'.$caid] == 'custom_html') OR $applyToDefault == 'custom_html') {
        $html = new xoopsFormTextArea('', 'html_'.$caid.'_'.$effectid, $htmlDefault, 13, 60);
        $html->setExtra("wrap=off");
        $effectTable .= "\n<td class=\"even\">" . $html->render() . "</td>";
    } else {
        $element = new xoopsFormSelect(_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_EFFECT_ELEMENT, 'element_'.$caid.'_'.$effectid, $elementDefault);
        // if this is supposed to create a new entry in another form, then figure out which form was chosen and set that as the fid key to use to generate the right options
        // check for new per selected first, since that contains new_
        if(isset($_POST['applyto_'.$caid]) AND substr($_POST['applyto_'.$caid], 0, 17) == "new_per_selected_") {
            $thisEffectFid = substr($_POST['applyto_'.$caid], 17);
        } elseif(isset($_POST['applyto_'.$caid]) AND substr($_POST['applyto_'.$caid], 0, 4) == "new_") {
            $thisEffectFid = substr($_POST['applyto_'.$caid], 4);
        } elseif(strstr($applyToDefault, "new_per_selected_")) {
            $thisEffectFid = substr($applyToDefault, 17);
        } elseif(strstr($applyToDefault, "new_")) {
            $thisEffectFid = substr($applyToDefault, 4);
        } else {
            $thisEffectFid = $fid;
        }
        $element->addOptionArray($elementOptions[$thisEffectFid]);
        $effectTable = addElementLOE($element, $effectTable);
        $action = new xoopsFormSelect(_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_EFFECT_ACTION, 'action_'.$caid.'_'.$effectid, $actionDefault);
        $action->addOptionArray(array('replace'=>_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_EFFECT_ACTION_REPLACE, 'remove'=>_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_EFFECT_ACTION_REMOVE, 'append'=>_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_EFFECT_ACTION_APPEND));
        $effectTable = addElementLOE($action, $effectTable);
        $value = new xoopsFormTextArea(_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_EFFECT_VALUE, 'value_'.$caid.'_'.$effectid, $valueDefault, 5, 30);
        $effectTable = addElementLOE($value, $effectTable);
    }

    $effectTable .= "</table></td></tr>";

    return $effectTable;
}
