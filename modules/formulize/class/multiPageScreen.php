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

	// see formulizeScreen::screenTypeName
	public static function screenTypeName() {
		return defined('_AM_SCREEN_SETTINGS_KIND_FORM') ? _AM_SCREEN_SETTINGS_KIND_FORM : 'Form'; // not every language has it
	}


	// The text the administrator wrote for the top of the first page and for the Thank You page. It is kept in
	// files, not the database - see codeFileName() - and read into the object the first time it is asked for.
	const TEXT_VARS = array('introtext', 'thankstext');

	// The handle the screen had when it was loaded, which its text files are named for. A handle set on the object
	// since then only names the files once insert() has moved them.
	protected $loadedScreenHandle = '';

	// Which of TEXT_VARS have been read from their files into the object
	protected $textReadFromFile = array();

	function __construct() {
		parent::__construct();
		$this->initVar("introtext", XOBJ_DTYPE_TXTAREA); // not a column - read from a file, see getVar()
		$this->initVar("thankstext", XOBJ_DTYPE_TXTAREA); // not a column - read from a file, see getVar()
		$this->initVar("donedest", XOBJ_DTYPE_TXTBOX, NULL, false, 255);
		$this->initVar("buttontext", XOBJ_DTYPE_ARRAY);
		$this->initVar("finishisdone", XOBJ_DTYPE_INT);
		$this->initVar("pages", XOBJ_DTYPE_ARRAY);
		$this->initVar("pagetitles", XOBJ_DTYPE_ARRAY);
		$this->initVar("conditions", XOBJ_DTYPE_ARRAY);
		$this->initVar("disabledpages", XOBJ_DTYPE_ARRAY); // per-page 0/1 flag, parallel to pages/pagetitles/conditions; 1 = render all elements on that page as disabled (read-only)
		$this->initVar("printall", XOBJ_DTYPE_INT); //nmc - 2007.03.24
		$this->initVar("paraentryform", XOBJ_DTYPE_INT);
		$this->initVar("paraentryrelationship", XOBJ_DTYPE_INT);
		// which navigation the screen offers, each 1 for on and 0 for off: the tabs across the top, and the four
		// buttons at the bottom. prev is Save and Go Back (Save and Close on the first page), next is Save and
		// Continue (Save and Finish on the last page). See formulize_multipageButtonSet().
		$this->initVar('showtabs', XOBJ_DTYPE_INT);
		$this->initVar('showprevbutton', XOBJ_DTYPE_INT);
		$this->initVar('showsavebutton', XOBJ_DTYPE_INT);
		$this->initVar('showclosebutton', XOBJ_DTYPE_INT);
		$this->initVar('shownextbutton', XOBJ_DTYPE_INT);
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
		$this->initVar('formorder', XOBJ_DTYPE_ARRAY); // ordered array of fids controlling the order that forms appear in when the screen unifies elements from multiple related forms
	}

	/**
	 * Determine the page item type based on the elements recorded for displaying on the page
	 * @param array|string pageElements - the items for showing on the page
	 * @return string returns either pit-elements, pit-screen, or pit-custom, depending if the page contains elements, and entire screen, or custom code
	 */
	function determinePageItemType($pageElements) {
		if($pageElements[0] == "PHP") {
			return "pit-custom";
		} elseif(substr($pageElements[0], 0, 4) == "sid:") {
			return "pit-screen";
		} else {
			return "pit-elements";
		}
	}

	// setup the conditions array...format has changed over time...
	// old format: $conditions[1] = array(pagecons=>yes, details=>array(elements=>$elementsArray, ops=>$opsArray, terms=>$termsArray));
	// the key must be the numerical page number (start at 1)
	function getConditions() {
	    $conditions = $this->getVar('conditions');
	    $processedConditions = array();
			if(is_array($conditions)) {
				foreach($conditions as $pageid=>$thisCondition) {
					$pagenumber = $pageid+1;
					if(is_array($thisCondition) AND isset($thisCondition['details'])) {
							$processedConditions[$pagenumber] = array(0=>$thisCondition['details']['elements'], 1=>$thisCondition['details']['ops'], 2=>$thisCondition['details']['terms']);
					} elseif(is_array($thisCondition)) {
							$processedConditions[$pagenumber] = $thisCondition;
					} else {
							$processedConditions[$pagenumber] = array(); // normalize false/non-array (legacy empty placeholder) to empty
					}
				}
				ksort($processedConditions);
			}
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

    // returns true/false for the UI options showpageselector, showpageindicator, showpagetitles
    // 1 = on, 0 = off
    function getUIOption($option) {
        return ($this->getVar($option) == 1);
    }

	/**
	 * The name of the file in modules/formulize/code/ that holds one of the screen's texts, for a given handle.
	 * @param string $textVar introtext or thankstext
	 * @param string $screenHandle The screen's handle
	 * @return string The file name, or '' when there is no handle to name it for
	 */
	public static function codeFileName($textVar, $screenHandle) {
		$screenHandle = (string) $screenHandle;
		if(!in_array($textVar, self::TEXT_VARS) OR $screenHandle === '') {
			return '';
		}
		return 'multipage_'.$textVar.'_'.$screenHandle.'.php';
	}

	// remember the handle the screen was loaded with, which is what its text files are named for until insert() moves them
	public function assignVar($key, $value) {
		if($key == 'screen_handle') {
			$this->loadedScreenHandle = (string) $value;
		}
		parent::assignVar($key, $value);
	}

	// The handle the text files are named for right now. insert() calls this once it has moved them.
	public function setLoadedScreenHandle($handle) {
		$this->loadedScreenHandle = (string) $handle;
	}

	/**
	 * The intro and thanks text are read from their files the first time they are asked for. Once one has been set
	 * on the object, the object holds what it was given until insert() writes it, so a second getVar in the same
	 * save sees the new text rather than the file it is about to replace.
	 */
	public function getVar($key, $format = 's') {
		if(in_array($key, self::TEXT_VARS) AND $this->isVarClean($key) AND !isset($this->textReadFromFile[$key])) {
			$this->textReadFromFile[$key] = true;
			$contents = '';
			if($fileName = self::codeFileName($key, $this->loadedScreenHandle)) {
				$path = XOOPS_ROOT_PATH.'/modules/formulize/code/'.$fileName;
				if(file_exists($path)) {
					$contents = strval(file_get_contents($path));
				}
			}
			parent::assignVar($key, $contents);
		}
		return parent::getVar($key, $format);
	}

}

#[AllowDynamicProperties]
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

	function insert($screen, $force=false) {
		$update = !$screen->getVar('sid') ? false : true;
		// the handle the screen has now, since its text files are named for it - see moveTextFiles()
		$storedHandle = '';
		if ($update AND $result = $this->db->query("SELECT screen_handle FROM ".$this->db->prefix('formulize_screen')." WHERE sid = ".intval($screen->getVar('sid')))) {
			$row = $this->db->fetchArray($result);
			$storedHandle = $row ? (string) $row['screen_handle'] : '';
		}
		// read the text the screen has before its handle can change, so a text nobody touched stays where it is
		foreach(formulizeMultiPageScreen::TEXT_VARS as $textVar) {
			$screen->getVar($textVar, 'n');
		}
		if(!$sid = parent::insert($screen, $force)) { // write the basic info to the db, handle cleaning vars and all that jazz.  Object passed by reference, so updates will have affected it in the other method.
			return false;
		}
		$screen->assignVar('sid', $sid);
		$currentHandle = $screen->getVar('screen_handle', 'n');
		if($storedHandle !== '' AND $storedHandle !== $currentHandle) {
			$this->carryTextFiles($storedHandle, $currentHandle, true);
		}
		$screen->setLoadedScreenHandle($currentHandle);
		// standard flags used by xoopsobject class

        // cleanvars not called, so serialize must be used below to construct the query
        // introtext and thankstext are not columns: they are written to their files below, exactly as the admin typed them

		// note: conditions is not written to the DB yet, since we're not gathering that info from the UI
		if (!$update) {
                 $sql = sprintf("INSERT INTO %s (sid, donedest, buttontext, finishisdone, pages, pagetitles, conditions, disabledpages, printall, paraentryform, paraentryrelationship, showtabs, showprevbutton, showsavebutton, showclosebutton, shownextbutton, displaycolumns, column1width, column2width, showpagetitles, showpageindicator, showpageselector, displayheading, reloadblank, elementdefaults, formorder) VALUES (%u, %s, %s, %u, %s, %s, %s, %s, %u, %u, %u, %u, %u, %u, %u, %u, %u, %s, %s, %u, %u, %u, %u, %u, %s, %s)",
                    $this->db->prefix('formulize_screen_multipage'),
                    $screen->getVar('sid'),
                    $this->db->quoteString($screen->getVar('donedest')),
                    $this->db->quoteString(serialize($screen->getVar('buttontext'))),
                    $screen->getVar('finishisdone'),
                    $this->db->quoteString(serialize($screen->getVar('pages'))),
                    $this->db->quoteString(serialize($screen->getVar('pagetitles'))),
                    $this->db->quoteString(serialize($screen->getVar('conditions'))),
                    $this->db->quoteString(serialize($screen->getVar('disabledpages'))),
                    $screen->getVar('printall'),
                    $screen->getVar('paraentryform'),
                    $screen->getVar('paraentryrelationship'),
                    $screen->getVar('showtabs'),
                    $screen->getVar('showprevbutton'),
                    $screen->getVar('showsavebutton'),
                    $screen->getVar('showclosebutton'),
                    $screen->getVar('shownextbutton'),
                    $screen->getVar('displaycolumns'),
                    $this->db->quoteString($screen->getVar('column1width')),
                    $this->db->quoteString($screen->getVar('column2width')),
                    $screen->getVar('showpagetitles'),
                    $screen->getVar('showpageindicator'),
                    $screen->getVar('showpageselector'),
                    $screen->getVar('displayheading'),
                    $screen->getVar('reloadblank'),
                    $this->db->quoteString(serialize($screen->getVar('elementdefaults'))),
                    $this->db->quoteString(serialize($screen->getVar('formorder')))
                    );
                    //nmc 2007.03.24 added 'printall' & fixed pagetitles
             } else {
                 $sql = sprintf("UPDATE %s SET donedest = %s, buttontext = %s, finishisdone = %u, pages = %s, pagetitles = %s, conditions = %s, disabledpages = %s, printall = %u, paraentryform = %u, paraentryrelationship = %u, showtabs = %u, showprevbutton = %u, showsavebutton = %u, showclosebutton = %u, shownextbutton = %u, displaycolumns = %u, column1width = %s, column2width = %s, showpagetitles = %u, showpageindicator = %u, showpageselector = %u, displayheading = %u, reloadblank = %u, elementdefaults = %s, formorder = %s WHERE sid = %u", $this->db->prefix('formulize_screen_multipage'), $this->db->quoteString($screen->getVar('donedest')), $this->db->quoteString(serialize($screen->getVar('buttontext'))), $screen->getVar('finishisdone'), $this->db->quoteString(serialize($screen->getVar('pages'))), $this->db->quoteString(serialize($screen->getVar('pagetitles'))), $this->db->quoteString(serialize($screen->getVar('conditions'))), $this->db->quoteString(serialize($screen->getVar('disabledpages'))), $screen->getVar('printall'), $screen->getVar('paraentryform'), $screen->getVar('paraentryrelationship'), $screen->getVar('showtabs'), $screen->getVar('showprevbutton'), $screen->getVar('showsavebutton'), $screen->getVar('showclosebutton'), $screen->getVar('shownextbutton'), $screen->getVar('displaycolumns'), $this->db->quoteString($screen->getVar('column1width')), $this->db->quoteString($screen->getVar('column2width')), $screen->getVar('showpagetitles'), $screen->getVar('showpageindicator'), $screen->getVar('showpageselector'), $screen->getVar('displayheading'), $screen->getVar('reloadblank'), $this->db->quoteString(serialize($screen->getVar('elementdefaults'))), $this->db->quoteString(serialize($screen->getVar('formorder'))), $screen->getVar('sid')); //nmc 2007.03.24 added 'printall'
             }
					if($force) {
						$result = $this->db->queryF($sql);
					} else {
		 				$result = $this->db->query($sql);
					}
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
                $success5 = $this->writeTemplateToFile(trim($_POST['elementcontainerc']), 'elementcontainerc', $screen);
            }
            $success6 = true;
            if(isset($_POST['elementcontainero'])) {
                $success6 = $this->writeTemplateToFile(trim($_POST['elementcontainero']), 'elementcontainero', $screen);
            }

            if (!$success1 || !$success2 || !$success3 || !$success4 || !$success5 || !$success6) {
                return false;
            }
            if (!$this->writeTextFiles($screen)) {
                return false;
            }
         }
		 return $sid;
	}

	/**
	 * Write the intro and thanks text that were set on the screen object to their files, exactly as they were typed.
	 * Only a text set on this object is written, so an object loaded before something else changed a file can't put
	 * the old text back when it is saved for some other reason. A text that is empty - see
	 * formulize_authoredTextIsEmpty() - has no file.
	 * @param formulizeMultiPageScreen $screen The screen, after its row has been written, so its handle is final
	 * @return bool False if a file could not be written or removed
	 */
	function writeTextFiles($screen) {
		$codeDir = XOOPS_ROOT_PATH.'/modules/formulize/code/';
		foreach(formulizeMultiPageScreen::TEXT_VARS as $textVar) {
			if($screen->isVarClean($textVar)) {
				continue;
			}
			if(!$fileName = formulizeMultiPageScreen::codeFileName($textVar, $screen->getVar('screen_handle', 'n'))) {
				return false;
			}
			$text = (string) $screen->getVar($textVar, 'n');
			if(formulize_authoredTextIsEmpty($text)) {
				if(file_exists($codeDir.$fileName) AND !unlink($codeDir.$fileName)) {
					error_log("Formulize Error: could not remove $codeDir$fileName");
					return false;
				}
			} elseif(formulize_writeCodeToFile($fileName, $text) === false) {
				error_log("Formulize Error: could not write $codeDir$fileName");
				return false;
			}
		}
		return true;
	}

	/**
	 * Carry a screen's intro and thanks text files over to another screen handle: moved when the screen's handle
	 * changes, copied when it is cloned. A file already under the other handle is left as it is.
	 * @param string $fromHandle The handle the files are named for
	 * @param string $toHandle The handle they are to be named for
	 * @param bool $move Whether to move the files, rather than copy them
	 * @return void
	 */
	function carryTextFiles($fromHandle, $toHandle, $move) {
		if($fromHandle === $toHandle) {
			return;
		}
		$codeDir = XOOPS_ROOT_PATH.'/modules/formulize/code/';
		foreach(formulizeMultiPageScreen::TEXT_VARS as $textVar) {
			$from = formulizeMultiPageScreen::codeFileName($textVar, $fromHandle);
			$to = formulizeMultiPageScreen::codeFileName($textVar, $toHandle);
			if($from AND $to AND file_exists($codeDir.$from) AND !file_exists($codeDir.$to)) {
				if(!($move ? rename($codeDir.$from, $codeDir.$to) : copy($codeDir.$from, $codeDir.$to))) {
					error_log("Formulize Error: could not ".($move ? "move" : "copy")." $codeDir$from to $codeDir$to");
				}
			}
		}
	}

	/**
	 * Remove a deleted screen's intro and thanks text files
	 * @param string $screenHandle The handle of the screen that has been deleted
	 * @return void
	 */
	function deleteTextFiles($screenHandle) {
		$codeDir = XOOPS_ROOT_PATH.'/modules/formulize/code/';
		foreach(formulizeMultiPageScreen::TEXT_VARS as $textVar) {
			if($fileName = formulizeMultiPageScreen::codeFileName($textVar, $screenHandle)) {
				if(file_exists($codeDir.$fileName) AND !unlink($codeDir.$fileName)) {
					error_log("Formulize Error: could not remove $codeDir$fileName of a deleted screen");
				}
			}
		}
	}

	/**
	 * Add an element to the specified multi-paged screens and their pages, and/or remove it from screens/pages where it is not specified
	 * Tuned for handling the UI input from the element display settings page
	 * @param mixed $elementIdentifier - either an element object or an element identifier
	 * @param array $multiPageScreens - an array of multi-paged screen and page pairs in the format screenid-pageindex (e.g. 3-0 for screen id 3, page index 0)
	 * @param array $legacyFormScreens - an array of legacy form screen ids
	 * @return boolean returns true on success, false on failure
	 */

	public function addElementToScreenPagesFromUI($elementIdentifier, $multiPageScreens = array(), $legacyFormScreens = array()) {

		if(!$elementObject = _getElementObject($elementIdentifier)) {
			return false;
		}

		$ele_id = $elementObject->getVar('ele_id');
		$form_handler = xoops_getmodulehandler('forms', 'formulize');
		$all_multi_screens = $form_handler->getMultiScreens($elementObject->getVar('fid'));

		// Go through each possible multi-paged screen's pages, and make changes accordingly
		foreach ($all_multi_screens as $i => $screen_array) {
  		$screen_id = $screen_array['sid'];
  		$screen = $this->get($screen_id);
  		$existing_pages = $screen->getVar('pages');

  		if (empty($multiPageScreens)) {
    		// Check if this element exists in each page, if it exists, then unset them
    		foreach ($existing_pages as $page_index => $page_elements) {
      		// Since PHP does not know how to differentiate between false and 0 that well, have a double checking for element existence
      		$element_exists = in_array($ele_id, $page_elements);
      		$element_index = array_search($ele_id, $page_elements);
      		if ($element_exists) {
        		unset($existing_pages[$page_index][$element_index]);
      		}
		    }
  		} else {
     		foreach ($existing_pages as $page_index => $page_elements) {
      		if (empty($page_elements)) {
        		// Necessary to loop through each treeview checkbox value, since every value is concatenated with its page index
        		$element_exists_in_treeview = false;
        		foreach ($multiPageScreens as $k => $raw_pair) {
          		$screen_page = explode("-", $raw_pair);
		    	    if ($screen_id == $screen_page[0] && $page_index == $screen_page[1]) {
    		        $element_exists_in_treeview = true;
          		}
        		}
		        if ($element_exists_in_treeview) {
    		      if (!in_array($ele_id, $page_elements)) {
        		    // If this element does not exist yet under the page's element array, then add it
            		array_push($existing_pages[$page_index], $ele_id);
          		}
        		}
      		} else {
						foreach ($page_elements as $ele_index => $page_element) {
							// Necessary to loop through each treeview checkbox value, since every value is concatenated with its page index
							$element_exists_in_treeview = false;
							foreach ($multiPageScreens as $k => $raw_pair) {
								$screen_page = explode("-", $raw_pair);
								if ($screen_id == $screen_page[0] && $page_index == $screen_page[1]) {
									$element_exists_in_treeview = true;
								}
							}
							if ($element_exists_in_treeview) {
								if (!in_array($ele_id, $existing_pages[$page_index])) {
									// If this element does not exist yet under the page's element array, then add it
									array_push($existing_pages[$page_index], $ele_id);
								}
							} else {
								if ($page_element == $ele_id AND in_array($ele_id, $existing_pages[$page_index])) {
									// If this element exists under the page's element array, then remove it
									unset($existing_pages[$page_index][$ele_index]);
								}
							}
						}
					}
    		}
  		}
 			$screen->setVar('pages',serialize($existing_pages));
  		if(!$this->insert($screen)) {
      	throw new Exception("Error: could not save the screen properly: ".$this->db->error());
  		}
		}

		// Handle legacy form screens too...

		// go through each possible screen, and save whether the element in the UI accordingly by appending to existing screen's elements
		// If the screen is not highlighted in the UI, then we must unset it manually by going through each screen's saved array
		$formScreenHandler = xoops_getmodulehandler('formScreen', 'formulize');
		$all_screens = $formScreenHandler->getScreensForElement($elementObject->getVar('fid'));

		// Due to security, not possible to retrieve formelements from using getmodulehandler, hence used abstract method.
		// For saving, use getmodulehandler directly
		foreach ($all_screens as $key => $screen) {
			$screen_elements = $formScreenHandler->getSelectedElementsForScreen($screen['sid']);
			$screen_stream = $formScreenHandler->get($screen['sid']);
			if (in_array($screen['sid'], $legacyFormScreens)) {
				// avoid adding duplicate element within the list if the screen already has it
				if (!in_array($ele_id, $screen_elements)) {
					array_push($screen_elements, strval($ele_id));
				}
				$save_element = serialize($screen_elements);
				$screen_stream->setVar('formelements', $save_element);
			} else {
				if (in_array($ele_id, $screen_elements)) {
					// If the element exists in the screen's element, array, then unset it
					if(($index = array_search($ele_id, $screen_elements)) !== false) {
							unset($screen_elements[$index]);
					}
					// if resulting array is empty, then send an empty quotation as data to setVar
					if (empty($screen_elements)){
						$screen_stream->setVar('formelements', "");
					} else {
						$save_element = serialize($screen_elements);
						$screen_stream->setVar('formelements', $save_element);
					}
				}
			}
			if(!$formScreenHandler->insert($screen_stream)) {
				throw new Exception("Error: could not save the screen properly: ".$this->db->error());
			}
		}

		return true;
	}


	// 	THIS METHOD MIGHT BE MOVED UP A LEVEL TO THE PARENT CLASS
	function get($sid) {
		$sid = intval($sid);
		if ($screen = $this->takeTypedScreenHandoff($sid)) {
			return $screen;
		}
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

		$previouslyRenderingScreen = (isset($GLOBALS['formulize_screenCurrentlyRendering']) AND $GLOBALS['formulize_screenCurrentlyRendering']) ? $GLOBALS['formulize_screenCurrentlyRendering'] : null;

		if(!is_array($settings)) {
			$settings = array();
		}

		$formframe = $screen->getVar('frid') ? $screen->getVar('frid') : $screen->getVar('fid');
		$mainform = $screen->getVar('frid') ? $screen->getVar('fid') : "";
		list($pages, $pageTitles, $pageConditions, $disabledPages) = $this->traverseScreenPages($screen);
		$pages['titles'] = $pageTitles; // silly convention that this named key in pages has the title. Handled in the start of displayFormPages function
		$pages['disabled'] = $disabledPages; // same convention as titles: per-page disabled flags, extracted at the start of displayFormPages
		$doneDest = $screen->getVar('donedest');
		if(substr($doneDest, 0, 1)=='/') {
		  $doneDest = XOOPS_URL.$doneDest;
		}
    include_once XOOPS_ROOT_PATH . "/modules/formulize/include/formdisplaypages.php";
    $GLOBALS['formulize_screenCurrentlyRendering'] = $screen;

    // the text for the link on the Thank You page is one of the button texts. thankyoulinktext is the key it is saved
    // under; thankYouLinkText is accepted too, in case anything ever saved it that way. Blank falls back to the default in displayFormPages.
    $buttonText = $screen->getVar('buttontext');
    $buttonText = is_array($buttonText) ? $buttonText : array();
    $thankYouLinkText = '';
    foreach(array('thankyoulinktext', 'thankYouLinkText') as $thankYouLinkKey) {
        if(isset($buttonText[$thankYouLinkKey]) AND trim((string) $buttonText[$thankYouLinkKey]) !== '') {
            $thankYouLinkText = $buttonText[$thankYouLinkKey];
            break;
        }
    }

    updateMultipageTemplates($screen);

		displayFormPages($formframe, $entry, $mainform, $pages, $pageConditions, $screen->getVar('introtext', "n"), $screen->getVar('thankstext', "n"), $doneDest, $thankYouLinkText, $settings,"", $screen->getVar('printall'), $screen, $screen->getVar('buttontext'), $elements_only); //nmc 2007.03.24 added 'printall' & 2 empty params
    $GLOBALS['formulize_screenCurrentlyRendering'] = $previouslyRenderingScreen;
	}

	/**
	 * Loop through all the pages in multipage screen, and add their contents and titles to the master list of pages and titles we're building
	 * Recursive, because we can have pages that reference other screens
	 * @param object screen - a multipage screen object
	 * @param array completePages - an array of the pages compiled from all the screens we've traversed
	 * @param array completePageTitles - an array of the all the page titles compiled from the screens we've traversed
	 * @param array completePageConditions - an array of all the page conditions compiled from the screens we've traversed
	 * @param array conditionsAppliedToAllPages - an array of conditions that should be applied to all pages in this screen. Happens when a page references another screen, and that page has conditions. Then that page's conditions are added to any conditions in the referenced screen.
	 * @return array An array with three items, one is completePages, one is completePageTitles, one is completePageConditions
	 */
	function traverseScreenPages(formulizeMultiPageScreen $screen, array $completePages=array(), array $completePageTitles=array(), array $completePageConditions=array(), array $conditionsAppliedToAllPages=array(), array $completeDisabledPages=array()): array {
		static $screenCatalogue = array();
		if(!isset($screenCatalogue[$screen->getVar('sid')])) { // avoid an infinite loop, don't redo a screen, until we're finished with that screen
			$screenCatalogue[$screen->getVar('sid')] = true;
			list($pages, $pageTitles, $disabledPages) = $this->gatherPagesAndTitlesFromScreen($screen);
			$pageConditions = $screen->getConditions();
			if(!empty($conditionsAppliedToAllPages)) { // add these conditions to each page in this screen
				foreach($pages as $pageNumber=>$items) {
					if(isset($pageConditions[$pageNumber]) AND isset($pageConditions[$pageNumber][0])) { // this page already has conditions, so merge them
						$pageConditions[$pageNumber][0] = array_merge($pageConditions[$pageNumber][0], $conditionsAppliedToAllPages[0]);
						$pageConditions[$pageNumber][1] = array_merge($pageConditions[$pageNumber][1], $conditionsAppliedToAllPages[1]);
						$pageConditions[$pageNumber][2] = array_merge($pageConditions[$pageNumber][2], $conditionsAppliedToAllPages[2]);
						$pageConditions[$pageNumber][3] = array_merge($pageConditions[$pageNumber][3], $conditionsAppliedToAllPages[3]);
					} else { // just add the conditions to this page
						$pageConditions[$pageNumber] = $conditionsAppliedToAllPages;
					}
				}
			}
			foreach($pages as $pageNumber=>$items) {
				if(!is_array($items) OR !$items) {
					continue;
				}
				$firstItem = $items[array_key_first($items)];
				if(!is_numeric($firstItem) AND $firstItem != "PHP") {
					$pageScreenId = substr($firstItem, 4);
					if($pageScreenObject = $this->get($pageScreenId)) {
						list($completePages, $completePageTitles, $completePageConditions, $completeDisabledPages) = $this->traverseScreenPages($pageScreenObject, $completePages, $completePageTitles, $completePageConditions, $pageConditions[$pageNumber], $completeDisabledPages);
					} else {
						error_log("Formulize Error: invalid screen reference on page ".$pageTitles[$pageNumber]." ($pageNumber) of screen ".$screen->getVar('title'));
					}
				} else {
					$completePageNumber = count($completePages) + 1; // arrays are keyed from 1 in this case, so the new page will be count plus 1
					$completePages[$completePageNumber] = $items;
					$completePageTitles[$completePageNumber] = $pageTitles[$pageNumber];
					$completePageConditions[$completePageNumber] = isset($pageConditions[$pageNumber]) ? $pageConditions[$pageNumber] : array();
					$completeDisabledPages[$completePageNumber] = isset($disabledPages[$pageNumber]) ? $disabledPages[$pageNumber] : 0;
				}
			}
			unset($screenCatalogue[$screen->getVar('sid')]); // we can revisit this screen now safely, since we're done traversing it.
		}
		return array($completePages, $completePageTitles, $completePageConditions, $completeDisabledPages);
	}

	/**
	 * Gather the pages and page titles from a multipage screen object, ensuring the keys (page numbers) start with 1, and the pages are in the correct order
	 * @param object screen - a multipage screen object
	 * @return array An array where first value is the pages array, second is the pageTitles array, and the third is the disabledPages array
	 */
	function gatherPagesAndTitlesFromScreen($screen) {
		$pages = $screen->getVar('pages');
		$pageTitles = $screen->getVar('pagetitles');
		$disabledPages = $screen->getVar('disabledpages');
		$disabledPages = is_array($disabledPages) ? $disabledPages : array();
		// make sure $disabledPages has all the pages represented
		foreach($pages as $pageNumber=>$pageElements) {
			if(!isset($disabledPages[$pageNumber])) {
				$disabledPages[$pageNumber] = 0;
			}
		}
		ksort($pages); // make sure the arrays are sorted by key, ie: page number
		ksort($pageTitles);
		ksort($disabledPages);
		array_unshift($pages, ""); // displayFormPages looks for the page array to start with [1] and not [0], for readability when manually using the API, so we bump up all the numbers by one by adding something to the front of the array
		array_unshift($pageTitles, "");
		array_unshift($disabledPages, "");
		unset($pages[0]); // get rid of the part we just unshifted, so the page count is correct
		unset($pageTitles[0]);
		unset($disabledPages[0]);
		return array($pages, $pageTitles, $disabledPages);
	}

	/**
	 * Remove a given element or elements from multipage screens
	 * @param mixed $elementIdOrIds - either a single element id, or an array of element ids to remove from multipage screens
	 * @param boolean $removeEmptyPages - Optional. Whether to remove pages that become empty after removing the elements. Defaults to false.
	 * @return void
	 */
	function removeElementsFromScreens($elementIdOrIds, $removeEmptyPages = false) {
		if(!is_array($elementIdOrIds)) {
			$elementIdOrIds = array($elementIdOrIds);
		}
		$elementIdOrIds = array_filter(array_map('intval', $elementIdOrIds));
		if(!$elementIdOrIds) {
			return;
		}
		// the LIKE is a coarse filter on the serialized pages, so it can match rows that do not really hold
		// these ids - the exact test is the in_array below
		$sql = "SELECT `sid`, `pages`, `pagetitles`, `conditions`, `disabledpages` FROM ".$this->db->prefix('formulize_screen_multipage')." WHERE `pages` LIKE '%".implode("%' OR `pages` LIKE '%", $elementIdOrIds)."%'";
		if($result = $this->db->query($sql)) {
			while($array = $this->db->fetchArray($result)) {
				$pages = unserialize($array['pages']);
				if(!is_array($pages)) {
					continue;
				}
				$pagetitles = unserialize($array['pagetitles']);
				$pagetitles = is_array($pagetitles) ? $pagetitles : array();
				$pageconditions = unserialize($array['conditions']);
				$pageconditions = is_array($pageconditions) ? $pageconditions : array();
				$disabledpages = unserialize($array['disabledpages']);
				$disabledpages = is_array($disabledpages) ? $disabledpages : array();
				$changed = false;
				$emptiedPages = array();
				foreach($pages as $pageIndex=>$pageElements) {
					if(!is_array($pageElements) OR !$pageElements) {
						continue;
					}
					// a page holds element ids, but it can instead hold a reference to another screen, which
					// is not something to search for element ids in. Every item is checked rather than only
					// the first, so a page cannot keep an element just because of what happens to lead it.
					$pageChanged = false;
					foreach($pageElements as $key=>$pageElement) {
						if(is_numeric($pageElement) AND in_array($pageElement, $elementIdOrIds)) {
							unset($pages[$pageIndex][$key]);
							$pageChanged = true;
						}
					}
					if($pageChanged) {
						if(count($pages[$pageIndex]) == 0 AND $removeEmptyPages) {
							// collected and dealt with after the loop: reindexing here would renumber the
							// pages while the loop is still walking the numbering it started with
							$emptiedPages[] = $pageIndex;
						} else {
							$pages[$pageIndex] = array_values($pages[$pageIndex]); // reindex the array
						}
						$changed = true;
					}
				}
				foreach($emptiedPages as $pageIndex) {
					unset($pages[$pageIndex]);
					unset($pagetitles[$pageIndex]);
					unset($pageconditions[$pageIndex]);
					unset($disabledpages[$pageIndex]);
				}
				if($emptiedPages) {
					// the four run parallel to each other by page number, so they are renumbered together
					$pages = array_values($pages);
					$pagetitles = array_values($pagetitles);
					$pageconditions = array_values($pageconditions);
					$disabledpages = array_values($disabledpages);
				}
				if($changed) {
					$sql = "UPDATE ".$this->db->prefix('formulize_screen_multipage')."
						SET `pages` = ".$this->db->quoteString(serialize($pages)).",
						`pagetitles` = ".$this->db->quoteString(serialize($pagetitles)).",
						`conditions` = ".$this->db->quoteString(serialize($pageconditions)).",
						`disabledpages` = ".$this->db->quoteString(serialize($disabledpages))."
						WHERE `sid` = ".intval($array['sid']);
					$this->db->queryF($sql);
				}
			}
		}
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

        // the clone has a handle of its own, so it needs its own copies of the text files
        $handles = array();
        $handleResult = $this->db->query("SELECT sid, screen_handle FROM ".$this->db->prefix('formulize_screen')." WHERE sid IN (".intval($sid).", ".intval($newsid).")");
        while($handleResult AND $row = $this->db->fetchArray($handleResult)) {
            $handles[intval($row['sid'])] = (string) $row['screen_handle'];
        }
        if(isset($handles[intval($sid)]) AND isset($handles[intval($newsid)])) {
            $this->carryTextFiles($handles[intval($sid)], $handles[intval($newsid)], false);
        }
    }

    public function setDefaultFormScreenVars($defaultFormScreen, $formObject)
	{
        global $xoopsConfig;
        $defaultFormScreen->setVar('theme', $xoopsConfig['theme_set']);
        $defaultFormScreen->setVar('title', $formObject->getSingular());
				$defaultFormScreen->setVar('screen_handle', $this->makeHandleUnique($formObject->getSingular()));
        $defaultFormScreen->setVar('displayheading', 0);
		$defaultFormScreen->setVar('reloadblank', 0);
        $defaultFormScreen->setVar('finishisdone', 1);
		$defaultFormScreen->setVar('fid', $formObject->getVar('fid'));
		$defaultFormScreen->setVar('frid', -1);
		$defaultFormScreen->setVar('type', 'multiPage');
		$defaultFormScreen->setVar('useToken', 1);
        $defaultFormScreen->setVar('pagetitles',serialize(array(0=>$formObject->getSingular())));
        $defaultFormScreen->setVar('pages', serialize(array(0=>array())));
        $defaultFormScreen->setVar('disabledpages', serialize(array(0=>0)));
        // tabs across the top, and Save and Close at the bottom. No Thank You page, so no Save and Continue.
        $defaultFormScreen->setVar('showtabs', 1);
        $defaultFormScreen->setVar('showprevbutton', 0);
        $defaultFormScreen->setVar('showsavebutton', 1);
        $defaultFormScreen->setVar('showclosebutton', 1);
        $defaultFormScreen->setVar('shownextbutton', 0);
        $defaultFormScreen->setVar('buttontext', serialize(array(
            'thankyoulinktext'=>trans(_formulize_DMULTI_CONTINUE),
            'leaveButtonText'=>trans(_formulize_SAVE_AND_LEAVE),
            'prevButtonText'=>trans(_formulize_DMULTI_PREV),
            'saveButtonText'=>trans(_formulize_SAVE),
            'nextButtonText'=>trans(_formulize_DMULTI_NEXT),
            'finishButtonText'=>trans(_formulize_DMULTI_SAVE),
            'printableViewButtonText'=>trans(_formulize_PRINTVIEW),
						'closeButtonText'=>trans(_formulize_DONE)
        )));
        $defaultFormScreen->setVar('printall', 2);
        $defaultFormScreen->setVar('paraentryform', 0);
        $defaultFormScreen->setVar('paraentryrelationship', 0);
        $defaultFormScreen->setVar('showpagetitles', 0);
        $defaultFormScreen->setVar('showpageindicator', 0);
        $defaultFormScreen->setVar('showpageselector', 0);
				// WE NEED TO ADD IN PER-SCREEN DEFAULTS AND THINGS LIKE THE COLUMNS NEED TO RESPECT THE DEFAULT PROVIDED BY THE SCREEN
				// WE CAN PROBABLY JUST REUSE THE SETTINGS SYSTEM, STORE THE SETTINGS ASSOCIATED WITH EACH THEM, AND LOAD THE VALUES
				// BASED ON THE THEME THAT IS ACTIVE
        $defaultFormScreen->setVar('displaycolumns', 1);
        $defaultFormScreen->setVar('column1width', 'auto');
        $defaultFormScreen->setVar('column2width', 'auto');
				$defaultFormScreen->setVar('anonNeedsPasscode', 1);
    }

}

/**
 * Generates an array for all the screens on forms in a one-to-one connection with this form
 * If there is no relationship, return screens of the specified form, except for the excluded screen
 * @param int fid The form id of the mainform
 * @param int frid Optional. The relationship id if any
 * @param int excludedSid Optional. A sid to not include in the results
 * @return array an array of the compiled screens. Keys are 'sid:<screenId>' and values are the form titles and screen titles
 */
function multiPageScreenMakeScreenOptionsList($fid, $frid=0, $excludedSid=0) {
	$options = multiPageScreenScreenOptionsList_append($fid, $excludedSid);
	if($frid) {
		// figure out which forms in the relationship we care about, and append them to the array
		$framework_handler = xoops_getModuleHandler('frameworks', 'formulize');
		$frameworkObject = $framework_handler->get($frid);
		foreach($frameworkObject->getVar("links") as $thisLinkObject) {
			$form1 = $thisLinkObject->getVar("form1");
			$form2 = $thisLinkObject->getVar("form2");
			if ($thisLinkObject->getVar("unifiedDisplay")
				AND $thisLinkObject->getVar("relationship") == 1
				AND ($form1 == $fid OR $form2 == $fid)) {
					$otherFid = $form1 == $fid ? $form2 : $form1;
					$options = multiPageScreenScreenOptionsList_append($otherFid, $excludedSid, $options);
			}
		}
	}
	return $options;
}

/**
 * Finds all the screens for a given form, except any specified excluded screen
 * @param int fid The form id of the mainform
 * @param int excludedSid Optional. A sid to not include in the results
 * @param array screens Optional. An array of options to append to, if not specified then a new array is made
 */
function multiPageScreenScreenOptionsList_append($fid, $excludedSid=0, $screens=array()) {
	$form_handler = xoops_getmodulehandler('forms', 'formulize');
	$formObject = $form_handler->get($fid);
	$multiPageScreens = $form_handler->getMultiScreens($fid);
	array_multisort(array_column($multiPageScreens, 'title'), $multiPageScreens);
	foreach($multiPageScreens as $screenMetaData) {
		if($excludedSid != $screenMetaData['sid']) {
			$screens['sid:'.$screenMetaData['sid']] = printSmart(trans(strip_tags($formObject->getVar('title'))), 25).": ".printSmart(trans(strip_tags($screenMetaData['title'])), 25);
		}
	}
	return $screens;
}


/**
 * Generates an array, keys are element ids, values are the element colhead or caption, for all the elements available in a dataset that should be shown in multipage form screen admin UI (based on the relationship id if any)
 * Originally created March 20 2008 - refactored Sep 22 2024 (!)
 * @param int fid The form id of the mainform
 * @param int frid Optional. The relationship id if any
 * @return array Returns the array of compiled elements
 */
function multiPageScreen_addToOptionsList($fid, $frid=0) {
		// setup elements for the passed in fid
		$options = multiPageScreen_addToOptionsListByFid($fid, $frid);
		if($frid) {
			// figure out which forms in the relationship we care about, and append them to the array
			// getUnifiedDisplayFids returns $fid first followed by the connected forms that qualify, so skip the first one which we've already added
			$framework_handler =& xoops_getModuleHandler('frameworks', 'formulize');
			$frameworkObject = $framework_handler->get($frid);
			$unifiedFids = $frameworkObject->getUnifiedDisplayFids($fid);
			array_shift($unifiedFids); // drop $fid itself, already handled above
			foreach($unifiedFids as $thisFid) {
				$options = multiPageScreen_addToOptionsListByFid($thisFid, $frid, $options); // append to the array
			}
		}
		return $options;
}

/**
 * Generates an array, or appends to a passed in array, of all the elements in the given form. Keys are element ids, values are the element colhead or caption. Used by multiPageScreen_addToOptionsList.
 * @param int fid The form for which we're gather elements
 * @param int frid Optional. A relationship ID indicating if we should prepend the form titles to the values in the array for readability.
 * @param array options Optional. An array that we should append to.
 * @return array The array of the elements that we generated, or that was passed in and appended to
 */
function multiPageScreen_addToOptionsListByFid($fid, $frid=0, $options=array()) {
	if(!is_array($options)) { return array(); }
	if($formObject = new formulizeForm($fid)) {
		$elements = $formObject->getVar('elements');
		$elementCaptions = $formObject->getVar('elementCaptions');
		$elementColheads = $formObject->getVar('elementColheads');
		foreach($elementCaptions as $key=>$elementCaption) {
			$elementLabel = $elementColheads[$key] ? $elementColheads[$key] : $elementCaption;
			$options[$elements[$key]] = $frid ? printSmart(trans(strip_tags($formObject->title)), 25).': '.printSmart(trans(strip_tags($elementLabel)), 25) : printSmart(trans(strip_tags($elementLabel)), 40); // need to pull out potential HTML tags from the caption/colhead
		}
	}
	return $options;
}

function pageMeetsConditions($conditions, $currentPage, $entry_id, $fid, $frid) {

	if($thesecons = (is_array($conditions) AND isset($conditions[$currentPage])) ? $conditions[$currentPage] : array()) {
		$elements = $thesecons[0];
		$ops = $thesecons[1];
		$terms = $thesecons[2];
		$types = $thesecons[3]; // indicates if the term is part of a must or may set, ie: boolean and or or
	} else {
		$elements = array();
		$ops = array();
		$terms = array();
		$types = array();
	}
	$filter = "";
	$oomfilter = "";
	$blankORSearch = "";

  // new entries cannot meet conditions yet because they are not saved
  // UNLESS the condition is = {BLANK}
	$allPassed = true;
	$oomPassed = false;
    $oomNotExists = true;
    if(is_array($elements) AND count($elements)>0 AND !intval($entry_id)) {
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
    if(!is_array($elements) OR count($elements)==0) { return true; }

    $element_handler = xoops_getmodulehandler('elements', 'formulize');
            foreach($elements as $i=>$thisElement) {
        if($elementObject = $element_handler->get($thisElement)) {
					$elements[$i] = $elementObject->getVar('ele_handle'); // safety net for when the elements array is a set of element ids! Code is designed and filter conditions below designed, to work with element handles
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

    include_once XOOPS_ROOT_PATH . "/modules/formulize/include/extract.php";
    $data = getData($frid, $fid, $finalFilter, resultOnly: true);
    return $data;
}

/**
 * Gather the page description info that should show up on the admin UI for a given page
 * @param array itemsForPage - an array of the items that make up the contents of the page
 * @param int fid - the form id
 * @param int frid - the form relationship id, if any
 * @return array An array with two items, the descriptor for this type of page, and the items that make up the page
 */
function generateElementInfoForScreenPage($itemsForPage, $fid, $frid) {
    $options = multiPageScreen_addToOptionsList($fid, $frid);
    $elements = array();
    $pageItemTypeTitle = "Elements displayed on this page:"; // default, historically the only option
		$wasNumericItems = false;
    foreach($itemsForPage as $thisPageItem) {
        // default is for the elements that make up the page to be a series of element ids
        if(is_numeric($thisPageItem)) {
					if(isset($options[$thisPageItem])) {
            $elements[] = $options[$thisPageItem];
						$wasNumericItems = true;
					}
        // alternatively, it could be a string of PHP code
        } elseif($thisPageItem == "PHP") {
            $pageItemTypeTitle = "This page uses custom code to display content.";
            break;
        // alternatively, it's a series of screen ids, prefixed by "sid:" which must be removed
        } else {
            $pageItemTypeTitle = "Screen displayed on this page:";
            $pageScreenId = substr($thisPageItem, 4);
            if(!isset($screen_handler)) {
                $screen_handler = xoops_getmodulehandler('screen', 'formulize');
                $form_handler = xoops_getmodulehandler('forms', 'formulize');
            }
            $pageScreenObject = $screen_handler->get($pageScreenId);
            $pageFormObject = $form_handler->get($pageScreenObject->getVar('fid'));
            $elements[] = printSmart($pageFormObject->getVar('title').": ".$pageScreenObject->getVar('title'), 200);
        }
    }
		if(count($elements) > 0 AND $wasNumericItems) {
			// sort the elements according to their position in the options list
			$optionsForSorting = array_values($options);
			usort($elements, function($a, $b) use ($optionsForSorting) {
				$posA = array_search($a, $optionsForSorting);
				$posB = array_search($b, $optionsForSorting);
				return $posA - $posB;
			} );
		}
    return array($pageItemTypeTitle, $elements);
}
