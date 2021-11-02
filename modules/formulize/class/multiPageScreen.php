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

class formulizeMultiPageScreen extends formulizeScreen {

	function __construct() {
		parent::__construct();
		$this->initVar("introtext", XOBJ_DTYPE_TXTAREA);
		$this->initVar("thankstext", XOBJ_DTYPE_TXTAREA);
		$this->initVar("donedest", XOBJ_DTYPE_TXTBOX, NULL, false, 255);
		$this->initVar("buttontext", XOBJ_DTYPE_ARRAY);
		$this->initVar("finishisdone", XOBJ_DTYPE_INT);	
		$this->initVar("pages", XOBJ_DTYPE_ARRAY);
		$this->initVar("pagetitles", XOBJ_DTYPE_ARRAY);
		$this->initVar("conditions", XOBJ_DTYPE_ARRAY);
		$this->initVar("printall", XOBJ_DTYPE_INT); //nmc - 2007.03.24
        $this->initVar("paraentryform", XOBJ_DTYPE_INT); 
        $this->initVar("paraentryrelationship", XOBJ_DTYPE_INT);
        $this->initVar("navstyle", XOBJ_DTYPE_INT);	
        $this->initVar("dobr", XOBJ_DTYPE_INT, 1, false);
        $this->initVar("dohtml", XOBJ_DTYPE_INT, 1, false);
        $this->assignVar("dobr", false); // don't convert line breaks to <br> when using the getVar method
        $this->initVar('displaycolumns', XOBJ_DTYPE_INT);
        $this->initVar("column1width", XOBJ_DTYPE_TXTBOX, NULL, false, 255);
        $this->initVar("column2width", XOBJ_DTYPE_TXTBOX, NULL, false, 255);
        $this->initVar('showpagetitles', XOBJ_DTYPE_INT);
        $this->initVar('showpageindicator', XOBJ_DTYPE_INT);
        $this->initVar('showpageselector', XOBJ_DTYPE_INT);
        $this->initVar('displayheading', XOBJ_DTYPE_INT);
        $this->initVar('reloadblank', XOBJ_DTYPE_INT);
        $this->initVar('elementdefaults', XOBJ_DTYPE_ARRAY);
	}
	
	// setup the conditions array...format has changed over time...
	// old format: $conditions[1] = array(pagecons=>yes, details=>array(elements=>$elementsArray, ops=>$opsArray, terms=>$termsArray));
	// the key must be the numerical page number (start at 1)
	function getConditions() {
	    $conditions = $this->getVar('conditions');
	    $processedConditions = array();
	    foreach($conditions as $pageid=>$thisCondition) {
		$pagenumber = $pageid+1;
		if(isset($thisCondition['details'])) {
		    $processedConditions[$pagenumber] = array(0=>$thisCondition['details']['elements'], 1=>$thisCondition['details']['ops'], 2=>$thisCondition['details']['terms']);
		} else {
		    $processedConditions[$pagenumber] = $thisCondition;
		}
	    }
	    ksort($processedConditions);
	    return $processedConditions;
	}
    
    function elementIsPartOfScreen($elementObjectOrId) {
        if(!$element = _getElementObject($elementObjectOrId)) {
            return false;
        }
        foreach($this->getVar('pages') as $page) {
            if(in_array($element->getVar('ele_id'), $page)) {
                return true;
            }
        }
        return false;
    }
    
    // this returns true/false for the UI options showpageselector, showpageindicator, showpagetitles
    // takes into account nav style setting
    function getUIOption($option) {
        $optionValue = $this->getVar($option);
        if(!$optionValue) { // legacy, check navstyle and do whatever we used to do
            switch($this->getVar('navstyle')) {
                case 1: // tabs
                    return false;
                case 2; // tabs and buttons
                    return false;
                default: // buttons
                    return true;
            }
        } else {
            return ($optionValue == 1);
        }
    }
	
}

class formulizeMultiPageScreenHandler extends formulizeScreenHandler {
	var $db;
	function __construct(&$db) {
		$this->db =& $db;
	}
	function &getInstance(&$db) {
		static $instance;
		if (!isset($instance)) {
			$instance = new formulizeMultiPageScreenHandler($db);
		}
		return $instance;
	}
	function &create() {
		return new formulizeMultiPageScreen();
	}

	function insert($screen) {
		$update = ($screen->getVar('sid') == 0) ? false : true;
		if(!$sid = parent::insert($screen)) { // write the basic info to the db, handle cleaning vars and all that jazz.  Object passed by reference, so updates will have affected it in the other method.
			return false;
		}
		$screen->assignVar('sid', $sid);
		// standard flags used by xoopsobject class
    
        // cleanvars not called, so serialize must be used below to construct the query

		// note: conditions is not written to the DB yet, since we're not gathering that info from the UI	
		if (!$update) {
                 $sql = sprintf("INSERT INTO %s (sid, introtext, thankstext, donedest, buttontext, finishisdone, pages, pagetitles, conditions, printall, paraentryform, paraentryrelationship, navstyle, displaycolumns, column1width, column2width, showpagetitles, showpageindicator, showpageselector, displayheading, reloadblank, elementdefaults) VALUES (%u, %s, %s, %s, %s, %u, %s, %s, %s, %u, %u, %u, %u, %u, %s, %s, %u, %u, %u, %u, %u, %s)",
                    $this->db->prefix('formulize_screen_multipage'),
                    $screen->getVar('sid'),
                    $this->db->quoteString($screen->getVar('introtext', "e")),
                    $this->db->quoteString($screen->getVar('thankstext', "e")),
                    $this->db->quoteString($screen->getVar('donedest')),
                    $this->db->quoteString(serialize($screen->getVar('buttontext'))),
                    $screen->getVar('finishisdone'),
                    $this->db->quoteString(serialize($screen->getVar('pages'))),
                    $this->db->quoteString(serialize($screen->getVar('pagetitles'))),
                    $this->db->quoteString(serialize($screen->getVar('conditions'))),
                    $screen->getVar('printall'),
                    $screen->getVar('paraentryform'),
                    $screen->getVar('paraentryrelationship'),
                    $screen->getVar('navstyle'),
                    $screen->getVar('displaycolumns'),
                    $this->db->quoteString($screen->getVar('column1width')),
                    $this->db->quoteString($screen->getVar('column2width')),
                    $screen->getVar('showpagetitles'),
                    $screen->getVar('showpageindicator'),
                    $screen->getVar('showpageselector'),
                    $screen->getVar('displayheading'),
                    $screen->getVar('reloadblank'),
                    $this->db->quoteString(serialize($screen->getVar('elementdefaults')))
                    );
                    //nmc 2007.03.24 added 'printall' & fixed pagetitles
             } else {
                 $sql = sprintf("UPDATE %s SET introtext = %s, thankstext = %s, donedest = %s, buttontext = %s, finishisdone = %u, pages = %s, pagetitles = %s, conditions = %s, printall = %u, paraentryform = %u, paraentryrelationship = %u, navstyle = %u, displaycolumns = %u, column1width = %s, column2width = %s, showpagetitles = %u, showpageindicator = %u, showpageselector = %u, displayheading = %u, reloadblank = %u, elementdefaults = %s WHERE sid = %u", $this->db->prefix('formulize_screen_multipage'), $this->db->quoteString($screen->getVar('introtext', "e")), $this->db->quoteString($screen->getVar('thankstext', "e")), $this->db->quoteString($screen->getVar('donedest')), $this->db->quoteString(serialize($screen->getVar('buttontext'))), $screen->getVar('finishisdone'), $this->db->quoteString(serialize($screen->getVar('pages'))), $this->db->quoteString(serialize($screen->getVar('pagetitles'))), $this->db->quoteString(serialize($screen->getVar('conditions'))), $screen->getVar('printall'), $screen->getVar('paraentryform'), $screen->getVar('paraentryrelationship'), $screen->getVar('navstyle'),  $screen->getVar('displaycolumns'), $this->db->quoteString($screen->getVar('column1width')), $this->db->quoteString($screen->getVar('column2width')), $screen->getVar('showpagetitles'), $screen->getVar('showpageindicator'), $screen->getVar('showpageselector'), $screen->getVar('displayheading'), $screen->getVar('reloadblank'), $this->db->quoteString(serialize($screen->getVar('elementdefaults'))), $screen->getVar('sid')); //nmc 2007.03.24 added 'printall'
             }
		 $result = $this->db->query($sql);
         if(!$result) {
            print $this->db->error(). "<br>\n $sql <br>\n";
            return false;
         } else {
            $success1 = true;
            if(isset($_POST['toptemplate'])) {
                $success1 = $this->writeTemplateToFile(trim($_POST['toptemplate']), 'toptemplate', $screen);
            }
            $success2 = true;
            if(isset($_POST['elementtemplate1'])) {
                $success2 = $this->writeTemplateToFile(trim($_POST['elementtemplate1']), 'elementtemplate1', $screen);
            }
            $success3 = true;
            if(isset($_POST['elementtemplate2'])) {
                $success3 = $this->writeTemplateToFile(trim($_POST['elementtemplate2']), 'elementtemplate2', $screen);
            }
            $success4 = true;
            if(isset($_POST['bottomtemplate'])) {
                $success4 = $this->writeTemplateToFile(trim($_POST['bottomtemplate']), 'bottomtemplate', $screen);
            }
            $success5 = true;
            if(isset($_POST['elementcontainerc'])) {
                $success3 = $this->writeTemplateToFile(trim($_POST['elementcontainerc']), 'elementcontainerc', $screen);
            }
            $success6 = true;
            if(isset($_POST['elementcontainero'])) {
                $success4 = $this->writeTemplateToFile(trim($_POST['elementcontainero']), 'elementcontainero', $screen);
            }
    
            if (!$success1 || !$success2 || !$success3 || !$success4 || !$success5 || !$success6) {
                return false;
            }
         }
		 return $sid;
	}

	// 	THIS METHOD MIGHT BE MOVED UP A LEVEL TO THE PARENT CLASS
	function get($sid) {
		$sid = intval($sid);
		if ($sid > 0) {
			$sql = 'SELECT * FROM '.$this->db->prefix('formulize_screen').' AS t1, '. $this->db->prefix('formulize_screen_multipage').' AS t2 WHERE t1.sid='.$sid.' AND t1.sid=t2.sid';
			if (!$result = $this->db->query($sql)) {
				return false;
			}
			$numrows = $this->db->getRowsNum($result);
			if ($numrows == 1) {
				$screen = new formulizeMultiPageScreen();
				$screen->assignVars($this->db->fetchArray($result));
				return $screen;
			}
		}
		return false;

	}

	// THIS METHOD HANDLES ALL THE LOGIC ABOUT HOW TO ACTUALLY DISPLAY THIS TYPE OF SCREEN
	// $screen is a screen object
    // $settings is used internally to pass list of entries settings back and forth to editing screens
    // $elements_only means we are rendering elements in a special situation, like a modal display or some other disembodied state
	function render($screen, $entry, $settings = array(), $elements_only = null) { 
    
        $previouslyRenderingScreen = $GLOBALS['formulize_screenCurrentlyRendering'];
        
		if(!is_array($settings)) {
				$settings = array();
		}
		
		$formframe = $screen->getVar('frid') ? $screen->getVar('frid') : $screen->getVar('fid');
		$mainform = $screen->getVar('frid') ? $screen->getVar('fid') : "";
		$pages = $screen->getVar('pages');
		$pagetitles = $screen->getVar('pagetitles');
		ksort($pages); // make sure the arrays are sorted by key, ie: page number
		ksort($pagetitles);
		array_unshift($pages, ""); // displayFormPages looks for the page array to start with [1] and not [0], for readability when manually using the API, so we bump up all the numbers by one by adding something to the front of the array
		array_unshift($pagetitles, ""); 
		unset($pages[0]); // get rid of the part we just unshifted, so the page count is correct
		unset($pagetitles[0]);
        $pages['titles'] = $pagetitles;
		$conditions = $screen->getConditions();
		$doneDest = $screen->getVar('donedest');
		if(substr($doneDest, 0, 1)=='/') {
		    $doneDest = XOOPS_URL.$doneDest;
		}
    	include_once XOOPS_ROOT_PATH . "/modules/formulize/include/formdisplaypages.php";
        $GLOBALS['formulize_screenCurrentlyRendering'] = $screen;
        
        // straight string is the thank you text. If saved as array, then multiple texts will be present so extract thank you link text, and pass whole thing as button text too
        $buttonText = $screen->getVar('buttontext');
        $thankYouLinkText = is_array($buttonText) ? $buttonText['thankYouLinkText'] : $buttonText;
        $buttonText = is_array($buttonText) ? $buttonText : array();
        
        updateMultipageTemplates($screen);
        
		displayFormPages($formframe, $entry, $mainform, $pages, $conditions, html_entity_decode(html_entity_decode($screen->getVar('introtext', "e")), ENT_QUOTES), html_entity_decode(html_entity_decode($screen->getVar('thankstext', "e")), ENT_QUOTES), $doneDest, $thankYouLinkText, $settings,"", $screen->getVar('printall'), $screen, $screen->getVar('buttontext'), $elements_only); //nmc 2007.03.24 added 'printall' & 2 empty params
        $GLOBALS['formulize_screenCurrentlyRendering'] = $previouslyRenderingScreen;
	}


    // THIS METHOD CLONES A MULTIPAGE SCREEN
    function cloneScreen($sid) {

        $newtitle = parent::titleForClonedScreen($sid);

        $newsid = parent::insertCloneIntoScreenTable($sid, $newtitle);

        if (!$newsid) {
            return false;
        }

        $tablename = "formulize_screen_multipage";
        $result = parent::insertCloneIntoScreenTypeTable($sid, $newsid, $newtitle, $tablename);

        if (!$result) {
            return false;
        }
    }
    
    public function setDefaultFormScreenVars($defaultFormScreen, $formTitle='', $fid, $pageOneTitle='')
	{
        global $xoopsConfig;
        $defaultFormScreen->setVar('theme', $xoopsConfig['theme_set']);
        $defaultFormScreen->setVar('title', $formTitle);
        $defaultFormScreen->setVar('displayheading', 0);
		$defaultFormScreen->setVar('reloadblank', 0);
        $defaultFormScreen->setVar('finishisdone', 1);
		$defaultFormScreen->setVar('fid', $fid);
		$defaultFormScreen->setVar('frid', 0);
		$defaultFormScreen->setVar('type', 'multiPage');
		$defaultFormScreen->setVar('useToken', 1);
        $defaultFormScreen->setVar('pagetitles',serialize(array(0=>$pageOneTitle)));
        $defaultFormScreen->setVar('pages', serialize(array(0=>array())));
        $defaultFormScreen->setVar('navstyle', 1);
        $defaultFormScreen->setVar('buttontext', serialize(array(
            'thankyoulinktext'=>'',
            'leaveButtonText'=>trans(_formulize_SAVE_AND_LEAVE),
            'prevButtonText'=>trans(_formulize_DMULTI_PREV),
            'saveButtonText'=>trans(_formulize_SAVE),
            'nextButtonText'=>trans(_formulize_DMULTI_NEXT),
            'finishButtonText'=>trans(_formulize_DMULTI_SAVE),
            'printableViewButtonText'=>trans(_formulize_PRINTVIEW)
        )));
        $defaultFormScreen->setVar('printall', 0);
        $defaultFormScreen->setVar('paraentryform', 0); 
        $defaultFormScreen->setVar('paraentryrelationship', 0);
        $defaultFormScreen->setVar('showpagetitles', 0);
        $defaultFormScreen->setVar('showpageindicator', 0);
        $defaultFormScreen->setVar('showpageselector', 0);
        $defaultFormScreen->setVar('displaycolumns', 2);
        $defaultFormScreen->setVar('column1width', '20%');
        $defaultFormScreen->setVar('column2width', 'auto');
    }
    
}

function multiPageScreen_addToOptionsList($fid, $options) {
		$formObject = new formulizeForm($fid);
		$elements = $formObject->getVar('elements');
		$elementCaptions = $formObject->getVar('elementCaptions');
    foreach($elementCaptions as $key=>$elementCaption) {
			$options[$elements[$key]] = printSmart(trans(strip_tags($elementCaption))); // need to pull out potential HTML tags from the caption
		}
		return $options;
}

function pageMeetsConditions($conditions, $currentPage, $entry_id, $fid, $frid) {
            $thesecons = $conditions[$currentPage];
            $elements = $thesecons[0];
            $ops = $thesecons[1];
            $terms = $thesecons[2];
            $types = $thesecons[3]; // indicates if the term is part of a must or may set, ie: boolean and or or
            $filter = "";
            $oomfilter = "";
    $blankORSearch = "";
    
    // new entries cannot meet conditions yet because they are not saved
    // UNLESS the condition is = {BLANK}
	$allPassed = true;
	$oomPassed = false;
    $oomNotExists = true;
    if(count($elements)>0 AND !intval($entry_id)) {
        foreach($ops as $i=>$op) {
			switch($types[$i]) {
				case 'all':
					if($op != "=" OR $terms[$i] != "{BLANK}") { $allPassed = false; }
					break;
				case 'oom':
                    $oomNotExists = false;
					if($op == "=" AND $terms[$i] == "{BLANK}") { $oomPassed = true; }
					break;
            }
        }
        return ($allPassed AND ($oomPassed OR $oomNotExists)); // all conditions are = {BLANK} so new entries would match (except for if there are default values specified for the field??) Also, at least one OOM condition is = BLANK
    }
    
    // pages with no conditions are always allowed!
    if(count($elements)==0) { return true; } 
    
    $element_handler = xoops_getmodulehandler('elements', 'formulize');
            foreach($elements as $i=>$thisElement) {
        if($elementObject = $element_handler->get($thisElement)) {
        $searchTerm = formulize_swapDBText(trans($terms[$i]),$elementObject->getVar('ele_uitext'));
        if($ops[$i] == "NOT") { $ops[$i] = "!="; }
        if($terms[$i] == "{BLANK}") { // NOTE...USE OF BLANKS WON'T WORK CLEANLY IN ALL CASES DEPENDING WHAT OTHER TERMS HAVE BEEN SPECIFIED!!
            if($ops[$i] == "!=" OR $ops[$i] == "NOT LIKE") {
                if($types[$i] != "oom") {
                    // add to the main filter, ie: entry id = 1 AND x=5 AND y IS NOT "" AND y IS NOT NULL
                    if(!$filter) {
                        $filter = $entry_id."][".$elements[$i]."/**//**/!=][".$elements[$i]."/**//**/IS NOT NULL";
                    } else {
                        $filter .= "][".$elements[$i]."/**//**/!=][".$elements[$i]."/**//**/IS NOT NULL";
                    }
                } else {
                    // Add to the OOM filter, ie: entry id = 1 AND (x=5 OR y IS NOT "" OR y IS NOT NULL)
                    if(!$oomfilter) {
                        $oomfilter = $elements[$i]."/**//**/=][".$elements[$i]."/**//**/IS NULL";
                    } else {
                        $oomfilter .= "][".$elements[$i]."/**//**/=][".$elements[$i]."/**//**/IS NULL";
                    }
                }
                    } else {
                if($types[$i] != "oom") {
                    // add to its own OR filter, since we MUST match this condition, but we don't care if it's "" OR NULL
                    // ie: entry id = 1 AND (x=5 OR y=10) AND (z = "" OR z IS NULL)
                    if(!$blankORSearch) {
                        $blankORSearch = $elements[$i]."/**//**/=][".$elements[$i]."/**//**/IS NULL";
                    } else {
                        $blankORSearch .= "][".$elements[$i]."/**//**/=][".$elements[$i]."/**//**/IS NULL";
                    }
                } else {
                    // it's part of the oom filters anyway, so we put it there, because we don't care if it's null or "" or neither
                    if(!$oomfilter) {
                        $oomfilter = $elements[$i]."/**//**/=][".$elements[$i]."/**//**/IS NULL";
                    } else {
                        $oomfilter .= "][".$elements[$i]."/**//**/=][".$elements[$i]."/**//**/IS NULL";
                    }
                }
            }
        } elseif($types[$i] == "oom") {
            if(!$oomfilter) {
                $oomfilter = $elements[$i]."/**/".$searchTerm."/**/".$ops[$i];
            } else {
                $oomfilter .= "][".$elements[$i]."/**/".$searchTerm."/**/".$ops[$i];
                    }
                } else {
            if(!$filter) {
                $filter = $entry_id."][".$elements[$i]."/**/".$searchTerm."/**/".$ops[$i];
                    } else {
                $filter .= "][".$elements[$i]."/**/".$searchTerm."/**/".$ops[$i];
                    }
                }
        } else {
            print "Error: there is a condition on page $currentPage that is referring to element $thisElement but that element either doesn't exist or has been renamed.";
        }
            }
    $finalFilter = array();
            if ($oomfilter AND $filter) {
                $finalFilter[0][0] = "AND";
                $finalFilter[0][1] = $filter;
                $finalFilter[1][0] = "OR";
                $finalFilter[1][1] = $oomfilter;
        if($blankORSearch) {
            $finalFilter[2][0] = "OR";
            $finalFilter[2][1] = $blankORSearch;
        }
            } elseif ($oomfilter) {
        // need to add the $entry_id as a separate filter from the oom, so the entry and oom get an AND in between them
                $finalFilter[0][0] = "AND";
        $finalFilter[0][1] = $entry_id;
                $finalFilter[1][0] = "OR";
                $finalFilter[1][1] = $oomfilter;
        if($blankORSearch) {
            $finalFilter[2][0] = "OR";
            $finalFilter[2][1] = $blankORSearch;
            }
            } else {
        if($blankORSearch) {
            $finalFilter[0][0] = "AND";
            $finalFilter[0][1] = $filter ? $filter : $entry_id;
            $finalFilter[1][0] = "OR";
            $finalFilter[1][1] = $blankORSearch;
            } else {
            $finalFilter = $filter;
            }
        }
    $masterBoolean = "AND";

    include_once XOOPS_ROOT_PATH . "/modules/formulize/include/extract.php";
    $data = getData($frid, $fid, $finalFilter, $masterBoolean, "", "", "", "", "", false, 0, false, "", false, true);
    return $data;
}
