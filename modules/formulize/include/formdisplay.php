<?php

###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2004 Freeform Solutions                  ##
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

//THIS FILE HANDLES THE DISPLAY OF FORMS.  FUNCTIONS CAN BE CALLED FROM ANYWHERE (INTENDED FOR PAGEWORKS MODULE)

global $xoopsConfig;
// load the formulize language constants if they haven't been loaded already
if ( file_exists(XOOPS_ROOT_PATH."/modules/formulize/language/".$xoopsConfig['language']."/main.php") ) {
    include_once XOOPS_ROOT_PATH."/modules/formulize/language/".$xoopsConfig['language']."/main.php";
} else {
    include_once XOOPS_ROOT_PATH."/modules/formulize/language/english/main.php";
}

include_once XOOPS_ROOT_PATH."/modules/formulize/include/functions.php";
include_once XOOPS_ROOT_PATH ."/modules/formulize/class/data.php";
require_once XOOPS_ROOT_PATH.'/modules/formulize/include/subformSaveFunctions.php';

include_once XOOPS_ROOT_PATH."/class/xoopsformloader.php";
include_once XOOPS_ROOT_PATH . "/include/functions.php";

global $fullJsCatalogue;
if(!is_array($fullJsCatalogue)) { $fullJsCatalogue = array(); }

function memory_usage() {
	$mem_usage = memory_get_usage(true);
	if ($mem_usage < 1024) {
		$mem_usage .= ' bytes';
	} elseif ($mem_usage < 1048576) {
		$mem_usage = round($mem_usage/1024,2) . ' kilobytes';
	} else {
		$mem_usage = round($mem_usage/1048576,2) . ' megabytes';
	}
	return $mem_usage;
}


// set once, outside class and methods, so it is set once and then all forms and subforms can fill it up as they are called and rendered
$GLOBALS['formulize_startHiddenElements'] = array();

// NEED TO USE OUR OWN VERSION OF THE CLASS, TO GET ELEMENT NAMES IN THE TR TAGS FOR EACH ROW <-- that's how it started... now so much more
class formulize_themeForm extends XoopsThemeForm {
    
    private $frid = 0;
    private $screen;
    
    // $screen is the screen being rendered, either a multipage or a single page form screen - multipage screen is passed through when rendering happens
    function __construct($title, $name, $action, $method = "post", $addtoken = false, $frid = 0, $screen = null) {
        $this->frid = $frid;
        $this->screen = $screen;
        parent::__construct($title, $name, $action, $method, $addtoken);
    }
    
    /**
     * Insert an empty row in the table to serve as a seperator.
     *
     * @param   string  $extra  HTML to be displayed in the empty row.
     * @param   string  $class  CSS class name for <td> tag
     * @name    string  $name   name of the element being inserted, which we keep so we can then put the right id tag into its row
     */
    public function insertBreakFormulize($extra = '', $class= '', $name='', $element_handle='') {
        $ibContents = $extra."<<||>>".$name."<<||>>".$element_handle."<<||>>".$class; // can only assign strings or real element objects with addElement, not arrays
        $this->addElement($ibContents);
    }

    /**
     * get the template of the specified type, from the screen and for the active theme, or fail over to the default template
     *
     */
    public function getTemplate($type) {
        $template = '';
        if($screenObject = formulize_themeForm::getScreenObject()) {
            $template = $screenObject->getTemplate($type);
        }
        if(!$template) {
            global $xoopsConfig;
            $themeDefaultPath = XOOPS_ROOT_PATH."/modules/formulize/templates/screens/".$xoopsConfig['theme_set']."/default/form/".$type.".php";
            if (file_exists($themeDefaultPath)) {
                $template = file_get_contents($themeDefaultPath);
            } else {
                $systemDefaultPath = XOOPS_ROOT_PATH."/modules/formulize/templates/screens/default/form/".$type.".php";
                if (file_exists($systemDefaultPath)) {
                    $template = file_get_contents($systemDefaultPath);
                } else {
                    exit('Error: could not locate a template for displaying the form: '.$this->getTitle().'<br>No template found for this screen if any, and no theme default at:<br>'.$themeDefaultPath.'<br>and no system default at:<br>'.$systemDefaultPath);
                }
            }
        }
        return $template;
    }
    
    public function getScreenObject() {
        global $xoopsUser;
        $uid = $xoopsUser ? $xoopsUser->getVar('uid') : 0;
        if(isset($this) AND $this instanceof formulize_themeForm AND is_object($this->screen)) {
            return $this->screen;
        } elseif(isset($_SESSION['formulizeScreenId'][$uid]) AND $sid = $_SESSION['formulizeScreenId'][$uid]) {
            $screen_handler = xoops_getmodulehandler('screen', 'formulize');
            $screen = $screen_handler->get($sid);
            $type = $screen->getVar('type');
            $screen_handler = xoops_getmodulehandler($type.'Screen', 'formulize');
            return $screen_handler->get($sid);
        } 
    }
    
	/**
	 * create HTML to output the form as a theme-enabled table with validation.
	 *
	 * @return	string
	 */
	public function render() {
		$ele_name = $this->getName();
        $displayStyle = !strstr(getCurrentURL(), "printview.php") ? "style='display: none;'" : ""; 
		$ret = "<form id='" . $ele_name
                . "' autocomplete='off' "
				. " name='" . $ele_name
                . "' class='formulizeThemeForm' $displayStyle"
				. " action='" . $this->getAction()
				. "' method='" . $this->getMethod()
				. "' onsubmit='return xoopsFormValidate_" . $ele_name . "();'" . $this->getExtra() . ">";
        $template = $this->getTemplate('toptemplate');
        $ret .= $this->processTemplate($template, array('formTitle'=>$this->getTitle()));
		$hidden = '';
		list($ret, $hidden) = $this->_drawElements($this->getElements(), $ret, $hidden);
        $template = $this->getTemplate('bottomtemplate');
        $ret .= $this->processTemplate($template);
		$ret .= "\n$hidden\n</form>\n";
		$ret .= $this->renderValidationJS(true);
		return $ret;
	}
	
    public function processTemplate($templateCode, $variables=array()) {
        foreach($variables as $k=>$v) {
            ${$k} = $v;
        }
        if(substr($templateCode, 0, 5)=='<?php') {
            $templateCode = substr($templateCode, 5);
        }
        ob_start();
        eval($templateCode);
        return ob_get_clean();
    }
    
	public function renderValidationJS( $withtags = true, $skipConditionalCheck = false ) {
		$js = "";
		if ( $withtags ) {
			$js .= "\n<!-- Start Form Validation JavaScript //-->\n<script type='text/javascript'>\n<!--//\n";
		}
        $js .= "jQuery(document).ready(function() {\n";
        $js .= "    jQuery('.formulizeThemeForm').each(function() {\n";
        $js .= "        jQuery(this).show(75);\n";
        $js .= "    });\n";
        
        foreach($GLOBALS['formulize_startHiddenElements'] as $markupName) {
            $js .= "    jQuery('#formulize-".$markupName."').hide();\n";
        }
        
        $js .= "});\n";
		$formname = $this->getName();
		$js .= "function xoopsFormValidate_{$formname}(leave, myform) { \n";
		$js .= $this->_drawValidationJS($skipConditionalCheck);
		$js .= "\nreturn true;\n}\n";
		if ( $withtags ) {
			$js .= "//--></script>\n<!-- End Form Vaidation JavaScript //-->\n";
		}
		return $js;
	}

    // reset will cause the cached copy of columns to be bypassed - this is done when a form is rendered, so that we don't reuse the setting for an element when it appeared on a different screen in the past in this same session
    function _getColumns($ele, $reset = false) {
        global $xoopsUser;
        $uid = $xoopsUser ? $xoopsUser->getVar('uid') : 0;
        // cache in the session the column setting we used for a given element last time it was rendered
        // should be unnecessary to segregate by uid in the session superglobal, but can't hurt
        // This caching is necessary so that conditional calls for rendering the element work as expected!
        
        // cache also the screen id since we need to lookup the template according to the screen object settings
        
        // if a numeric value is passed as ele, that is an element id, sent specifically so we can seed the right column value based on the screen setting
        if(is_numeric($ele)) {
            $eleKey = $ele;
        } elseif(is_object($ele) AND isset($ele->formulize_element)) {
            $eleKey = $ele->formulize_element->getVar('ele_id');
        } elseif(is_object($ele)) {
			$eleKey = md5(serialize($ele));
        } elseif(strstr($ele, "<<||>>")) {
            $ele = explode("<<||>>", $ele);
            $parts = explode('_', $ele[1]);
            $ele_id = $parts[3];
            $eleKey = $ele_id;
        } else {
            $eleKey = md5($ele);
        }
        if(isset($_SESSION['columns'][$uid][$eleKey]) AND !$reset) {
            $columns = $_SESSION['columns'][$uid][$eleKey];
        } elseif(isset($this) AND $this instanceof formulize_themeForm AND is_object($this->screen)) {
            $_SESSION['formulizeScreenId'][$uid] = $this->screen->getVar('sid');
            $columns = $this->screen->getVar('displaycolumns') == 1 ? 1 : 2;
            if($this->screen->getVar('column1width')) {
                $column1width = $this->screen->getVar('column1width');
            } elseif($columns == 2) {
                $column1width = '20%';
            } else {
                $column1width = 'auto';
            }
            $column2width = $this->screen->getVar('column2width') ? $this->screen->getVar('column2width') : 'auto';
            $columns = array($columns, $column1width, $column2width);
        } else {
            $columns = array(2, '20%', '80%');
        }
        $_SESSION['columns'][$uid][$eleKey] = $columns;
        return $columns;
    }
    
	function _drawElements($elements, $ret, $hidden) {

		foreach ( $elements as $ele ) {
            
            if(is_string($ele) AND isset($GLOBALS['formulize_renderedElementHasConditions'])) {
                // check if this is a placeholder row for an element that has conditions, and if so, deduce the element ID from the key in the global array of rendered elements that had conditions
                foreach($GLOBALS['formulize_renderedElementHasConditions'] as $deKey=>$conditions) {
                    if(strstr($ele, $deKey)) {
                        $deParts = explode('_', $deKey);
                        $eleToSetForColumns = $deParts[3];
                        break;
                    }
                }
            } else {
                $eleToSetForColumns = $ele;
            }

            // set the column value for all elements, regardless of if they're being rendered or not, so we can pick up the value from session if a conditional element is activated later asynchronously
            // but only do this when rendering the screen that the element is natively part of! -- conditionally hidden elements could otherwise end up with a different screen's settings as their cached-in-session settings
            $eleToCheckForReset = is_numeric($eleToSetForColumns) ? $eleToSetForColumns : $ele->formulize_element;
            if($this->screen AND $this->screen->elementIsPartOfScreen($eleToCheckForReset)) {
                $columns = $this->_getColumns($eleToSetForColumns, 'reset');
            }

			if (!is_object($ele)) {// just plain add stuff if it's a literal string...
                $columnData = $this->_getColumns($ele);
                $columns = $columnData[0];
                $column1Width = str_replace(';','',$columnData[1]);
                $column2Width = str_replace(';','',$columnData[2]);
				if(strstr($ele, "<<||>>")) { 
					$ele = explode("<<||>>", $ele);
                    if($ele[0] == '{STARTHIDDEN}') {
                        $ele[0] = '';
                        $GLOBALS['formulize_startHiddenElements'][] = $ele[1];
                    }
                    $templateVariables = array(
                        'elementContainerId'=>'formulize-'.$ele[1],
                        'elementClass'=>'',
                        'elementCaption'=>'',
                        'elementHelpText'=>'',
                        'renderedElement'=>$ele[0],
                        'labelClass'=>"formulize-label-".$ele[2],
                        'column1Width'=>$column1Width,
                    );
                    if($columnData[0] == 2 AND isset($ele[3])) { // by convention, only formulizeInsertBreak element, "spanning both columns" has a [3] key, so we need to put in the span flag
                        $columns = 1;
                        $templateVariables['colSpan'] = 'colspan=2';
                    }
                    if(isset($ele[3])) { // respect any declared class
                        $templateVariables['cellClass'] = $ele[3];
                    }
				} else {
                    $templateVariables = array(
                        'elementContainerId'=>'',
                        'elementClass'=>'',
                        'elementCaption'=>'',
                        'elementHelpText'=>'',
                        'renderedElement'=>$ele,
                        'column1Width'=>$column1Width,
                        'column2Width'=>$column2Width
                    );
				}
                if(($columnData[0] != 1 AND $columnData[2] != 'auto' AND $columnData[1] != 'auto')
                    OR ($columnData[0] == 1 AND $columnData[1] != 'auto')) {
                        $templateVariables['spacerNeeded'] = true;
                }
                
                $template = $this->getTemplate('elementcontainero');
                $ret .= $this->processTemplate($template, $templateVariables);
                
                $template = $this->getTemplate('elementtemplate'.$columns);
                $ret .= $this->processTemplate($template, $templateVariables);
                
                $template = $this->getTemplate('elementcontainerc');
                $ret .= $this->processTemplate($template, $templateVariables);
            
			} elseif ( !$ele->isHidden() ) {
                $template = $this->getTemplate('elementcontainero');
                $ret .= $this->processTemplate($template, array('elementContainerId'=>'formulize-'.$ele->getName(), 'elementClass'=>$ele->getClass()));
				$ret .= $this->_drawElementElementHTML($ele);
                $template = $this->getTemplate('elementcontainerc');
                $ret .= $this->processTemplate($template);
			} else {
				$hidden .= $ele->render();
			}
		}
		return array($ret, $hidden);
	}

	// draw the HTML for the element, a table row normally
	// $ele is the renderable element object
	function _drawElementElementHTML($ele) {
	
        if(!$ele) { return ""; }
    
		static $show_element_edit_link = null;
		global $formulize_drawnElements;
        $columnData = formulize_themeForm::_getColumns($ele); // we might be in a static context so can't call via $this
		// initialize things first time through...
		if($show_element_edit_link === null) {
			$formulize_drawnElements = array();
			global $xoopsUser;
			$show_element_edit_link = (is_object($xoopsUser) and in_array(XOOPS_GROUP_ADMIN, $xoopsUser->getGroups()));
		}
		
        if(isset($ele->formulize_element) AND isset($formulize_drawnElements[trim($ele->getName())])) {
			return $formulize_drawnElements[trim($ele->getName())];
		} elseif(isset($ele->formulize_element)) {
			$templateVariables['labelClass'] = " formulize-label-".$ele->formulize_element->getVar("ele_handle");
			$templateVariables['inputClass'] = " formulize-input-".$ele->formulize_element->getVar("ele_handle");
		}
        
        $element_name = trim($ele->getName());
        
        $templateVariables['editElementLink'] = '';
        if ($show_element_edit_link) {
            switch ($element_name) {
                case 'control_buttons':
                case 'proxyuser':
                    // Do nothing
                    break;
                default:
                    if (is_object($ele) and isset($ele->formulize_element)) {
                        $templateVariables['editElementLink'] = "<a class='formulize-element-edit-link' tabindex='-1' href='" . XOOPS_URL .
                            "/modules/formulize/admin/ui.php?page=element&aid=0&ele_id=" .
                    $ele->formulize_element->getVar("ele_id") . "' target='_blank'>edit element</a>";
                    }
                    break;
            }
        }
        
        $templateVariables['elementName'] = $element_name;
        $templateVariables['elementCaption'] = $ele->getCaption();
        $templateVariables['elementHelpText'] = $ele->getDescription();
        $templateVariables['elementIsRequired'] = $ele->isRequired();
        $templateVariables['renderedElement'] = trim($ele->render());
        $templateVariables['elementObject'] = $ele->formulize_element;
        $column1Width = str_replace(';','',$columnData[1]);
        $column2Width = str_replace(';','',$columnData[2]);
        $columns = $columnData[0];
        
        $templateVariables['spacerNeeded'] = false;
        if(($columns == 2 AND $column2Width != 'auto' AND $column1Width != 'auto')
            OR ($columns == 1 AND $column1Width != 'auto')) {
            $templateVariables['spacerNeeded'] = true;
        }
        
        $templateVariables['column1Width'] = $column1Width;
        $templateVariables['column2Width'] = $column2Width;
        
        // run the template for the specified number of columns
        $template = formulize_themeForm::getTemplate('elementtemplate'.$columns);
        $html = formulize_themeForm::processTemplate($template, $templateVariables);
		if(isset($ele->formulize_element) AND $element_name) { // cache the element's html
			$formulize_drawnElements[$element_name] = $html;
		}
		return $html;
	}

	// need to check whether the element is a standard element, if if so, add the check for whether its row exists or not	
	function _drawValidationJS($skipConditionalCheck) {
        global $fullJsCatalogue;
		$fullJs = "";
		
		$elements = $this->getElements( true );
		foreach ( $elements as $elt ) {
            
			if ( method_exists( $elt, 'renderValidationJS' ) ) {
                $validationJs = $elt->renderValidationJS();
                $catalogueKey = md5(trim($validationJs));
                if(!$validationJs OR isset($fullJsCatalogue[$catalogueKey])) {
                    continue;
				} else {
                    $fullJsCatalogue[$catalogueKey] = true;
                }
					$checkConditionalRow = false;
				if(substr($elt->getName(),0,3)=="de_") {
                    $elementNameParts = explode("_", $elt->getName());
                    $our_fid = $elementNameParts[1];
                    $our_entry_id = $elementNameParts[2];
                    $linkedEntries = checkForLinks($this->frid,array($our_fid),$our_fid,array($our_fid=>array($our_entry_id)),true);
                    // do not do validation checks on sub entries that are going to be deleted
                    // check for any possible deletion button/checkbox combos being in effect
                    // possible combos are determined by the relationships in effect based on this element's entry id, fid and active frid
                    // active frid is set when the theme form object is constructed
                    // THIS DEPENDS ON RELATIONSHIPS BEING NARROWLY DEFINED
                    // If different entries might match up under different circumstances, ie: a one-to-one connection between form a and b, except there are multiple entries in form b that connect to form a, but because we might be going from the form a side in this case, we would be stuck with the first entry found, which might not be the one used in this case!!
                    // Relationships should be used in limited circumstances, in limited ways. No big relationships that capture the entire ERD when you don't need it.
                    $js = "";
                    foreach($linkedEntries['entries'] as $fid=>$theseEntries) {
                        foreach($theseEntries as $entry_id) {
                            if(!$entry_id) { continue; }
                            $condition = "(jQuery('#formulize_mainform').length && parseInt(document.formulize_mainform.deletesubsflag.value) == ".$fid." && jQuery(\"input[name='delbox" . $entry_id . "']\").length && jQuery(\"input[name='delbox" . $entry_id . "']\").prop('checked'))";
                            if($js) {
                                $js .= " || $condition";
                            } else {
                                $js = "if(($condition";
				}
                        }
                    }
                    $js .= ")==false) {\n".$validationJs."\n}";
                    if(!$skipConditionalCheck) {
                        $checkConditionalRow = true;
                    }
				} else {
                    $js = $validationJs;
				} 
				if($checkConditionalRow) {
					$fullJs .= "if(window.document.getElementById('formulize-".$elt->getName()."').style.display != 'none') {\n".$js."\n}\n\n";
				} else {
					$fullJs .= "\n".$js."\n";
				}
			}
		}
		return $fullJs;
	}
	
}

// SPECIAL CLASS TO HANDLE SITUATIONS WHERE WE'RE RENDERING ONLY THE ROWS FOR THE FORM, NOT THE ENTIRE FORM 
class formulize_elementsOnlyForm extends formulize_themeForm {
	
	function render() {
		// just a slight modification of the render method so that we display only the elements and none of the extra form stuff
		$ele_name = $this->getName();
        
        $template = $this->getTemplate('toptemplate');
        $ret = $this->processTemplate($template, array('formTitle'=>$this->getTitle()));
        
		$hidden = '';
		list($ret, $hidden) = $this->_drawElements($this->getElements(), $ret, $hidden);
        $template = $this->getTemplate('bottomtemplate');
        $ret .= $this->processTemplate($template);
		$ret .= "\n$hidden\n";
		return $ret;
	}

	// render the validation code without the opening/closing part of the validation function, since the form is embedded inside another
	public function renderValidationJS() {
		return $this->_drawValidationJS(false);
	}
}

// this function gets the element that is linked from a form to its parent form
// returns the ele_ids from form table
// note: no enforcement of only one link to a parent form.  You can screw up your framework structure and this function will dutifully return several links to the same parent form
function getParentLinks($fid, $frid) {

	global $xoopsDB;

	$check1 = q("SELECT fl_key1, fl_key2 FROM " . $xoopsDB->prefix("formulize_framework_links") . " WHERE fl_form1_id='$fid' AND fl_frame_id = '$frid' AND fl_unified_display = '1' AND fl_relationship = '3'");
	$check2 = q("SELECT fl_key1, fl_key2 FROM " . $xoopsDB->prefix("formulize_framework_links") . " WHERE fl_form2_id='$fid' AND fl_frame_id = '$frid' AND fl_unified_display = '1' AND fl_relationship = '2'");
	foreach($check1 as $c) {
		$source[] = $c['fl_key2'];
		$self[] = $c['fl_key1'];
	}
	foreach($check2 as $c) {
		$source[] = $c['fl_key1'];
		$self[] = $c['fl_key2'];
	}

	$to_return['source'] = $source;
	$to_return['self'] = $self;

	return $to_return;

}


// this function returns the captions and values that are in the DB for an existing entry
// $elements is used to specify a shortlist of elements to display, based on their IDs.  Used in conjunction with the array option for $formform
// $element_handler is not required any longer!
function getEntryValues($entry, $element_handler, $groups, $fid, $elements="", $mid, $uid, $owner, $groupEntryWithUpdateRights) {

	if(!$fid) { // fid is required
		return "";
	}
	
	if(!is_numeric($entry) OR !$entry) {
		return "";
	}

	static $cachedEntryValues = array();
	$serializedElements = serialize($elements);
	if(!isset($cachedEntryValues[$fid][$entry][$serializedElements])) {
	
		global $xoopsDB;
	
		if(!$mid) { $mid = getFormulizeModId(); }
	
		if(!$uid) {
			global $xoopsUser;
			$uid = $xoopsUser ? $xoopsUser->getVar("uid") : 0; // if there is no uid, then use the $xoopsUser uid if there is one, or zero for anons			
		}

		if(!$owner) {
			$owner = getEntryOwner($entry, $fid); // if there is no owner, then get the owner for this entry in this form
		}
		
		// viewquery changed in light of 3.0 data structure changes...
		//$viewquery = q("SELECT ele_caption, ele_value FROM " . $xoopsDB->prefix("formulize_form") . " WHERE id_req=$entry $element_query");
		// NEED TO CHECK THE FORM FOR ENCRYPTED ELEMENTS, AND ADD THEM AFTER THE * WITH SPECIAL ALIASES. tHEN IN THE LOOP, LOOK FOR THE ALIASES, AND SKIP PROCESSING THOSE ELEMENTS NORMALLY, BUT IF WHEN PROCESSING A NORMAL ELEMENT, IT IS IN THE LIST OF ENCRYPTED ELEMENTS, THEN GET THE ALIASED, DECRYPTED VALUE INSTEAD OF THE NORMAL ONE
		// NEED TO ADD RETRIEVING ENCRYPTED ELEMENT LIST FROM FORM OBJECT
		$form_handler =& xoops_getmodulehandler('forms', 'formulize');
		$formObject = $form_handler->get($fid);
		$formHandles = $formObject->getVar('elementHandles');
		$formCaptions = $formObject->getVar('elementCaptions');
		$formEncryptedElements = $formObject->getVar('encryptedElements');
		$encryptedSelect = "";
		foreach($formEncryptedElements as $thisEncryptedElement) {
			$encryptedSelect .= ", AES_DECRYPT(`".$thisEncryptedElement."`, '".getAESPassword()."') as 'decrypted_value_for_".$thisEncryptedElement."'";
		}
		
		$viewquerydb = q("SELECT * $encryptedSelect FROM " . $xoopsDB->prefix("formulize_" . $formObject->getVar('form_handle')) . " WHERE entry_id=$entry");
		$viewquery = array();
		
		// need to parse the result based on the elements requested and setup the viewquery array for use later on
		$vqindexer = 0;
		foreach($viewquerydb[0] as $thisField=>$thisValue) {
			if(strstr($thisField, "decrypted_value_for_")) { continue; } // don't process these values normally, instead, we just refer to them later to grab the decrypted value, if this iteration is over an encrypted element.
			$includeElement = false;
			if(is_array($elements)) {
				if(in_array(array_search($thisField, $formHandles), $elements) AND $thisValue !== "") {
					$includeElement = true;
				}
			} elseif(!strstr($thisField, "creation_uid") AND !strstr($thisField, "creation_datetime") AND !strstr($thisField, "mod_uid") AND !strstr($thisField, "mod_datetime") AND !strstr($thisField, "entry_id") AND $thisValue !== "") {
				$includeElement = true;
			}
			if($includeElement) {
				$viewquery[$vqindexer]["ele_handle"] = $thisField;
				$viewquery[$vqindexer]["ele_caption"] = $formCaptions[array_search($thisField, $formHandles)];
				if(in_array($thisField, $formEncryptedElements)) {
					$viewquery[$vqindexer]["ele_value"] = $viewquerydb[0]["decrypted_value_for_".$thisField];
				} else {
					$viewquery[$vqindexer]["ele_value"] = $thisValue;	
				}
			}
			$vqindexer++;
		}
	
		// build query for display groups and disabled
		foreach($groups as $thisgroup) {
			$gq .= " OR ele_display LIKE '%,$thisgroup,%'";
			//$dgq .= " AND ele_disabled NOT LIKE '%,$thisgroup,%'"; // not sure that this is necessary
		}
	
		// exclude private elements unless the user has view_private_elements permission, or update_entry permission on a one-entry-per group entry
		$private_filter = "";
		$gperm_handler =& xoops_gethandler('groupperm');
		$view_private_elements = $gperm_handler->checkRight("view_private_elements", $fid, $groups, $mid);
	
		if(!$view_private_elements AND $uid != $owner AND !$groupEntryWithUpdateRights) {
			$private_filter = " AND ele_private=0";
		} 
	
		$allowedquery = q("SELECT ele_caption, ele_disabled, ele_handle FROM " . $xoopsDB->prefix("formulize") . " WHERE id_form=$fid AND (ele_display='1' $gq) $private_filter"); // AND (ele_disabled != 1 $dgq)"); // not sure that filtering for disabled elements is necessary
		$allowedDisabledStatus = array();
		$allowedhandles = array();
		foreach($allowedquery as $onecap) {
			$allowedhandles[] = $onecap['ele_handle'];
			$allowedDisabledStatus[$onecap['ele_handle']] = $onecap['ele_disabled'];
		}
	
		foreach($viewquery as $vq) {
			// check that this caption is an allowed caption before recording the value
			if(in_array($vq["ele_handle"], $allowedhandles)) {
				$prevEntry['handles'][] = $vq["ele_handle"];
				$prevEntry['captions'][] = $vq["ele_caption"];
				$prevEntry['values'][] = $vq["ele_value"];
				$prevEntry['disabled'][] = $allowedDisabledStatus[$vq['ele_handle']];
			}
		}
		$cachedEntryValues[$fid][$entry][$serializedElements] = $prevEntry;
	}
	return $cachedEntryValues[$fid][$entry][$serializedElements];
	
}


function displayForm($formframe, $entry="", $mainform="", $done_dest="", $button_text="", $settings=array(), $titleOverride="", $overrideValue="",
    $overrideMulti="", $overrideSubMulti="", $viewallforms=0, $profileForm=0, $printall=0, $screen=null) 
{
    include_once XOOPS_ROOT_PATH.'/modules/formulize/include/functions.php';
    include_once XOOPS_ROOT_PATH.'/modules/formulize/include/extract.php';
	$element_handler = xoops_getmodulehandler('elements', 'formulize');

    $settings = $settings === "" ? array() : $settings; // properly set array in case old code is passing in "" which used to be cool, but PHP 7 is grown up and it's not cool now.
    
    formulize_benchmark("Start of formDisplay.");

    $formElementsOnly = false;
    if($titleOverride == "formElementsOnly") {
        $titleOverride = "all";
        $formElementsOnly = true;
    }

    if(!is_numeric($titleOverride) AND $titleOverride != "" AND $titleOverride != "all") {
        // we can pass in a text title for the form, and that will cause the $titleOverride "all" behaviour to be invoked, and meanwhile we will use this title for the top of the form
        $passedInTitle = $titleOverride;
        $titleOverride = "all";
    }

    //syntax:
    //displayform($formframe, $entry, $mainform)
    //$formframe is the id of the form OR title of the form OR name of the framework.  Can also be an array.  If it is an array, then flag 'formframe' is the $formframe variable, and flag 'elements' is an array of all the elements that are to be displayed.
    //the array option is intended for displaying only part of a form at a time
    //$entry is the numeric entry to display in the form -- if $entry is the word 'proxy' then it is meant to force a new form entry when the form is a single-entry form that the user already may have an entry in
    //$mainform is the starting form to use, if this is a framework (can be specified by form id or by handle)
    //$done_dest is the URL to go to after the form has been submitted
    //Steps:
    //1. identify form or framework
    //2. if framework, check for unified display options
    //3. if entry specified, then get data for that entry
    //4. drawform with data if necessary

	global $xoopsDB, $xoopsUser, $myts, $formulize_subFidsWithNewEntries;
    $formulize_subFidsWithNewEntries = is_array($formulize_subFidsWithNewEntries) ? $formulize_subFidsWithNewEntries : array(); // initialize to an array

	global $sfidsDrawn;
	if(!is_array($sfidsDrawn)) {
		$sfidsDrawn = array();
	}

    $uid = $xoopsUser ? $xoopsUser->getVar('uid') : '0';
	$groups = $xoopsUser ? $xoopsUser->getGroups() : array(0=>XOOPS_GROUP_ANONYMOUS);

	$original_entry = $entry; // flag used to tell whether the function was called with an actual entry specified, ie: we're supposed to be editing this entry, versus the entry being set by coming back form a sub_form or other situation.

	$mid = getFormulizeModId();

	$currentURL = getCurrentURL();

    // if the go_back form was triggered, ie: we're coming back from displaying a different entry, then we need to adjust and show the parent entry/form/etc
    // important to do these setup things only once per page load
    static $cameBackFromSubformAlready = false;
	if($_POST['parent_form'] AND !$cameBackFromSubformAlready) { // if we're coming back from a subform
        $cameBackFromSubformAlready = true;

        $parent_form = htmlspecialchars(strip_tags($_POST['parent_form']));
        $parent_form = strstr($parent_form, ',') ? explode(',',$parent_form) : array($parent_form);
        $parent_entry = htmlspecialchars(strip_tags($_POST['parent_entry']));
        $parent_entry = strstr($parent_entry, ',') ? explode(',',$parent_entry) : array($parent_entry);
        $parent_page = htmlspecialchars(strip_tags($_POST['parent_page']));
        $parent_page = strstr($parent_page, ',') ? explode(',',$parent_page) : array($parent_page);
        $parent_subformElementId = htmlspecialchars(strip_tags($_POST['parent_subformElementId']));
        $parent_subformElementId = strstr($parent_subformElementId, ',') ? explode(',',$parent_subformElementId) : array($parent_subformElementId);
        $lastKey = count($parent_entry)-1;
        $entry =  $parent_entry[$lastKey]; // is based on the canonical go_back['entry'] value...need to pull off the right value from it
		$fid = $parent_form[$lastKey]; // is based on the canonical go_back['form'] value...need to pull off the right value from it
        $_POST['goto_subformElementId'] = $parent_subformElementId[$lastKey];
        unset($parent_form[$lastKey]);
        unset($parent_entry[$lastKey]);
        unset($parent_page[$lastKey]);
        unset($parent_subformElementId[$lastKey]);
        
        // if there are values left in stack, setup flag so we will parse the subform element id to use
        if(count($parent_entry)>0) {
            $cameBackFromSubformAlready = $fid;
            /*$go_back['form'] = implode(',',$parent_form);
            $go_back['entry'] = implode(',',$parent_entry);
            $go_back['page'] = implode(',',$parent_page);
            $go_back['subformElementId'] = implode(',',$parent_subformElementId); */
            $_POST['go_back_form'] = implode(',',$parent_form);
            $_POST['go_back_entry'] = implode(',',$parent_entry);
            $_POST['go_back_page'] = implode(',',$parent_page);
            $_POST['go_back_subformElementId'] = implode(',',$parent_subformElementId);
            $_POST['sub_submitted'] = $entry;
            $_POST['sub_fid'] = $fid;
        }
    }

    // if we're going to a subform after a view button, then swap the screen for the default screen of the subform
    $originalEntry = "";
    $originalFid = "";

    // important to do this only once per page load
    // note that camebackfromsubformalready is also set only once per page load
    global $formulize_displayingSubform;
    $formulize_displayingSubform = $formulize_displayingSubform ? $formulize_displayingSubform : false; // set to false unless there's an affirmative value already
    if(!$formulize_displayingSubform AND ($_POST['goto_sfid'] OR $_POST['sub_fid'] OR ($cameBackFromSubformAlready AND is_numeric($cameBackFromSubformAlready)))) {
        $subformElementIdToUse = isset($_POST['goto_subformElementId']) ? intval($_POST['goto_subformElementId']) : 0; 
        if($subformElementObject = $element_handler->get($subformElementIdToUse)) {
            if($subformDisplayScreen = get_display_screen_for_subform($subformElementObject)) {
                
                $screenHandler = xoops_getmodulehandler('screen', 'formulize');
                $plainScreenObject = $screenHandler->get($subformDisplayScreen);
                $subScreen_handler = xoops_getmodulehandler($plainScreenObject->getVar('type').'Screen', 'formulize');
                $screen = $subScreen_handler->get($subformDisplayScreen);
                if($_POST['sub_fid']) {
                    $originalFid = intval($_POST['sub_fid']);
                    $originalEntry = intval($_POST['sub_submitted']);
                } else {
                    list($passedFid, $passedFrid) = getFormFramework($formframe, $mainform); // don't assign to canonical fid and frid since there is some odd stuff below that happens in order, before these are actually assigned
                    $originalFid = $passedFid;
                $originalEntry = $entry;
                }
                $formframe = $screen->getVar('frid');
                $mainform = $screen->getVar('fid');
                $formulize_displayingSubform = array('originalFid'=>$originalFid, 'originalEntry'=>$originalEntry);
            }
        } else {
            print "Error: you have landed on a page that is supposed to be showing you a subform entry, but the subform element where you clicked a view button to get here, is not a valid element, does not exist, or something. Please contact <a href='mailto:info@formulize.org'>info@formulize.org</a> for assistance.";
        }
    }
    
	// identify form or framework
	$elements_allowed = "";
	// if a screen object is passed in, select the elements for display based on the screen's settings
	if ($screen and is_a($screen, "formulizeFormScreen")) {
		$elements_allowed = $screen->getVar("formelements");
	}
	if(is_array($formframe)) {
		$elements_allowed = $formframe['elements'];
		$printViewPages = isset($formframe['pages']) ? $formframe['pages'] : "";
		$printViewPageTitles = isset($formframe['pagetitles']) ? $formframe['pagetitles'] : "";
		$formframetemp = $formframe['formframe'];
		unset($formframe);
		$formframe = $formframetemp;
	}

    list($fid, $frid) = getFormFramework($formframe, $mainform);

    // propagate the go_back values from page load to page load, so we can eventually return there when the user is ready
	if($_POST['go_back_form']) { // we just received a subform submission
		$entry = intval($_POST['sub_submitted']);
		$fid = intval($_POST['sub_fid']);
		$go_back['form'] = htmlspecialchars(strip_tags($_POST['go_back_form']));
		$go_back['entry'] = htmlspecialchars(strip_tags($_POST['go_back_entry']));
        $go_back['page'] = htmlspecialchars(strip_tags($_POST['go_back_page']));
        $go_back['subformElementId'] = htmlspecialchars(strip_tags($_POST['go_back_subformElementId']));
        
	}

	// set $entry in the case of a form_submission where we were editing an entry (just in case that entry is not what is used to call this function in the first place -- ie: we're on a subform and the mainform has no entry specified, or we're clicking submit over again on a single-entry form where we started with no entry)
	$entrykey = "entry" . $fid;
	if((!$entry OR $entry=="proxy") AND $_POST[$entrykey]) { // $entrykey will only be set when *editing* an entry, not on new saves <-- NOT TRUE?! it is on all saves, and would perpetuate the 'new' flag??
		$entry = $_POST[$entrykey];
	}
	
	// this is probably not necessary any more, due to architecture changes in Formulize 3 <-- NOT TRUE?! This in fact is the only way to pick up the saved entry after making a new entry
	// formulize_newEntryIds is set when saving data
	if(!$entry AND isset($GLOBALS['formulize_newEntryIds'][$fid])) {
		$entry = $GLOBALS['formulize_newEntryIds'][$fid][0];
	}

	if($_POST['deletesubsflag']) { // if deletion of sub entries requested
    $updateMainformDerivedAfterSubEntryDeletion = false;
    $subs_to_del = array();
		foreach($_POST as $k=>$v) {
			if(strstr($k, "delbox") AND intval($v) > 0) {
				$subs_to_del[] = $v;
			}
		}
		if(count($subs_to_del) > 0) {
			$excludeFids = array($fid);
            // target_sub_fid is the fid that spawned the sub entry, in the case of a modal being displayed (or other similar situations?)
            if(isset($_POST['target_sub_fid']) AND intval($_POST['target_sub_fid'])) {
                $excludeFids[] = intval($_POST['target_sub_fid']);
            }
			foreach($subs_to_del as $id_req) {
                if(formulizePermHandler::user_can_delete_entry(intval($_POST['deletesubsflag']), $uid, $id_req)){
                    deleteEntry($id_req, $frid, intval($_POST['deletesubsflag']), $excludeFids);
                }
            }
            $updateMainformDerivedAfterSubEntryDeletion = true;
		}
        //formulize_updateDerivedValues($entry, $fid, $frid); // need to update the derived value of the parent entry when subs have been deleted!
        unset($_POST['deletesubsflag']); // only do this once per page load!!! Due to nested calls of displayForm with subforms, calling multiple times will lead to very nasty results, since deleteEntry calls checkForLinks and the mainform entries will be returned alongside the subform entries, but the excludefids will not include the mainform when this is called during a nested elementsonlyform call...so nasty
	}

    if($_POST['clonesubsflag']) { // if cloning of sub entries requested
        $subs_to_clone = array();
		foreach($_POST as $k=>$v) {
			if(strstr($k, "delbox") AND intval($v) > 0) {
				$subs_to_clone[] = $v;
			}
		}
		if(count($subs_to_clone) > 0) {
            foreach($subs_to_clone as $entry_id) {
                cloneEntry($entry_id, '', intval($_POST['clonesubsflag']));
            }
        }
        unset($_POST['clonesubsflag']);
    }
    
	$member_handler =& xoops_gethandler('member');
	$gperm_handler = &xoops_gethandler('groupperm');
	if($profileForm === "new") { 
		 // spoof the $groups array based on the settings for the regcode that has been validated by register.php
		$reggroupsq = q("SELECT reg_codes_groups FROM " . XOOPS_DB_PREFIX . "_reg_codes WHERE reg_codes_code=\"" . $GLOBALS['regcode'] . "\"");
		$groups = explode("&8(%$", $reggroupsq[0]['reg_codes_groups']);
		if($groups[0] === "") { unset($groups); } // if a code has no groups associated with it, then kill the null value that will be in position 0 in the groups array.
		$groups[] = XOOPS_GROUP_USERS;
		$groups[] = XOOPS_GROUP_ANONYMOUS;
	}	

	$single_result = getSingle($fid, $uid, $groups, $member_handler, $gperm_handler, $mid);
	$single = $single_result['flag'];
	// if we're looking at a single entry form with no entry specified and where the user has no entry of their own, or it's an anonymous user, then set the entry based on a cookie if one is present
	// want to do this check here and override $entry prior to the security check since we don't like trusting cookies!
    
    // first, check for an entry that matches an anon_passcode, if any, and settle on that entry no matter what. Otherwise, default to cookie when there is no passcode on the screen.
    $anon_override_entry = "";
    if((!$entry OR $entry == 'new') AND $single AND ($single_result['entry'] == "" OR intval($uid) === 0)) {
        // lookup entry for anon user with passcode
        if($uid == 0 AND $screen AND $screen->getVar('anonNeedsPasscode') AND isset($_SESSION['formulize_passCode_'.$screen->getVar('sid')])) {
            $data_handler = new formulizeDataHandler($screen->getVar('fid'));
            $anon_override_entry = $data_handler->findFirstEntryWithValue('anon_passcode_'.$screen->getVar('fid'), $_SESSION['formulize_passCode_'.$screen->getVar('sid')]);
        }
        // use cookie for registered users
        // or anon users that don't require passcodes
        if( ($uid
            OR (!$screen OR $screen->getVar('anonNeedsPasscode') == false) )
           AND isset($_COOKIE['entryid_'.$fid]) ) {
        	$anon_override_entry = $_COOKIE['entryid_'.$fid];
        }
    }
	include_once XOOPS_ROOT_PATH . "/modules/formulize/class/data.php";
	$data_handler = new formulizeDataHandler($fid);
	if($anon_override_entry) { 
		// check to make sure the cookie_entry exists...
		//$check_cookie_entry = q("SELECT id_req FROM " . $xoopsDB->prefix("formulize_form") . " WHERE id_req=" . intval($cookie_entry));
		//if($check_cookie_entry[0]['id_req'] > 0) {
		if($data_handler->entryExists(intval($anon_override_entry))) {
			$entry = $anon_override_entry; 
		} else {
			$anon_override_entry = "";
		}
	}
	$owner = ($anon_override_entry AND $uid) ? $uid : getEntryOwner($entry, $fid); // if we're pulling a cookie value and there is a valid UID in effect, then assume this user owns the entry, otherwise, figure out who does own the entry
	$owner_groups = $data_handler->getEntryOwnerGroups($entry);

	if($single AND !$entry AND !$overrideMulti AND $profileForm !== "new") { // only adjust the active entry if we're not already looking at an entry, and there is no overrideMulti which can be used to display a new blank form even on a single entry form -- useful for when multiple anonymous users need to be able to enter information in a form that is "one per user" for registered users. -- the pressence of a cookie on the hard drive of a user will override other settings
		$entry = $single_result['entry'];
		$owner = getEntryOwner($entry, $fid);
		unset($owner_groups);
		//$owner_groups =& $member_handler->getGroupsByUser($owner, FALSE);
		$owner_groups = $data_handler->getEntryOwnerGroups($entry);
	}

	if($entry == "proxy") { $entry = ""; } // convert the proxy flag to the actual null value expected for new entry situations (do this after the single check!)
	$editing = is_numeric($entry); // will be true if there is an entry we're looking at already

	if(!$scheck = security_check($fid, $entry, $uid, $owner, $groups) AND !$viewallforms AND !$profileForm) {
		print "<p>" . _NO_PERM . "</p>";
		return;
	}

    if($entry AND $updateMainformDerivedAfterSubEntryDeletion) {
        formulize_updateDerivedValues($entry, $fid, $frid);
    }
    
	// main security check passed, so let's initialize flags	
	$go_back['url'] = substr($done_dest, 0, 1) == "/" ? XOOPS_URL . $done_dest : $done_dest;

	// set these arrays for the one form, and they are added to by the framework if it is in effect
	$fids[0] = $fid;
	if($entry) {
		$entries[$fid][0] = $entry;
	} else {
		$entries[$fid][0] = "";
	}


	if($frid) { 
		$linkResults = checkForLinks($frid, $fids, $fid, $entries, true); // final true means only include entries from unified display linkages
		unset($entries);
		unset($fids);

		$fids = $linkResults['fids'];
		$entries = $linkResults['entries'];
		$sub_fids = $linkResults['sub_fids'];
		$sub_entries = $linkResults['sub_entries'];
	}
 
	$info_received_msg = 0;
	$info_continue = 0;
    if($entries[$fid][0]) {
        $info_continue = 1;
    }
	
	$add_own_entry = $gperm_handler->checkRight("add_own_entry", $fid, $groups, $mid);
	$add_proxy_entries = $gperm_handler->checkRight("add_proxy_entries", $fid, $groups, $mid);
	
	if ($_POST['form_submitted'] and $profileForm !== "new" and formulizePermHandler::user_can_edit_entry($fid, $uid, $entry)) {
		$info_received_msg = "1"; // flag for display of info received message
		if(!isset($GLOBALS['formulize_readElementsWasRun'])) {
			include_once XOOPS_ROOT_PATH . "/modules/formulize/include/readelements.php";
		}
		$temp_entries = $GLOBALS['formulize_allWrittenEntryIds']; // set in readelements.php
		
		if(!$formElementsOnly AND ($single OR $_POST['target_sub'] OR ($entries[$fid][0] AND ($original_entry OR ($_POST[$entrykey] AND !$_POST['back_from_sub']))) OR $overrideMulti OR ($_POST['go_back_form'] AND $overrideSubMulti))) { // if we just did a submission on a single form, or we just edited a multi, then assume the identity of the new entry.  Can be overridden by values passed to this function, to force multi forms to redisplay the just-saved entry.  Back_from_sub is used to override the override, when we're saving after returning from a multi-which is like editing an entry since entries are saved prior to going to a sub. -- Sept 4 2006: adding an entry in a subform forces us to stay on the same page too! -- Dec 21 2011: added check for !$formElementsOnly so that when we're getting just the elements in the form, we ignore any possible overriding, since that is an API driven situation where the called entry is the only one we want to display, period.
			if($entry == 'new' OR $entry == '') {
    			$entry = $temp_entries[$fid][0]; // adopt written entry if there is one, and we started out as 'new'
                $entries[$fid][0] = $entry;
                // $fids may now contain more than the mainform fid, since checkforlinks can fill in fids not just subfids...but we use checkforlinks above, so this should be OK?
                $linkResults = checkForLinks($frid, $fids, $fid, $entries, true); // final true means only include entries from unified display linkages
			unset($entries);
        		unset($fids);
        		$fids = $linkResults['fids'];
        		$entries = $linkResults['entries'];
        		$sub_fids = $linkResults['sub_fids'];
        		$sub_entries = $linkResults['sub_entries'];
			$owner = getEntryOwner($entry, $fid);
			unset($owner_groups);
			$owner_groups = $data_handler->getEntryOwnerGroups($entry);
            }
			$info_continue = 1;
		} elseif(!$_POST['target_sub']) { // as long as the form was submitted and we're not going to a sub form, then display the info received message and carry on with a blank form
			if(!$original_entry) { // if we're on a multi-form where the display form function was called without an entry, then clear the entries and behave as if we're doing a new add
				unset($entries);
				unset($sub_entries);
				$entries[$fid][0] = "";
				$sub_entries[$sub_fids[0]][0] = "";
			}
			$info_continue = 2;
		}
	}

	$sub_entries_synched = synchSubformBlankDefaults(); // they will already have been synched in readelements, but we will return the entries created when this function is called later, so that we can use them here
	foreach($sub_entries_synched as $synched_sfid=>$synched_ids) {
		foreach($synched_ids as $synched_id) {
			$sub_entries[$synched_sfid][] = $synched_id;
		}
	}
	if(count($sub_entries_synched)>0) {
		formulize_updateDerivedValues($entry, $fid, $frid);
	}

	// special use of $settings added August 2 2006 -- jwe -- break out of form if $settings so indicates
	// used to allow saving of information when you don't want the form itself to reappear
	if($settings == "{RETURNAFTERSAVE}" AND $_POST['form_submitted']) { return "returning_after_save"; }

      // need to add code here to switch some things around if we're on a subform for the first time (add)
	// note: double nested sub forms will not work currently, since on the way back to the intermediate level, the go_back values will not be set correctly
	if(isset($_POST['goto_sfid']) AND is_numeric($_POST['goto_sfid']) AND $_POST['goto_sfid'] > 0) {
        
        // unpack details of the parent entry that we were showing, if we're now displaying a subform screen
        $originalFid = "";
        $originalEntry = "";
        if($formulize_displayingSubform) {
            $originalFid = $formulize_displayingSubform['originalFid'];
            $originalEntry = $formulize_displayingSubform['originalEntry'];
        }
        
		$info_continue = 0;
        // need to append values since we're treating go_back as a stack of things that we move up and down
        $newFid = $originalFid ? $originalFid : $fid;
        $newEntry = $originalEntry ? $originalEntry : $temp_entries[$fid][0];
		$go_back['form'] .= $go_back['form'] ? ','.$newFid : $newFid;
		$go_back['entry'] .= $go_back['entry'] ? ','.$newEntry : $newEntry;
        $go_back['page'] .= (isset($go_back['page']) AND $go_back['page'] !== '') ? ','.htmlspecialchars(string_tags($_POST['formulize_prevPage'])) : htmlspecialchars(strip_tags($_POST['formulize_prevPage']));
        $go_back['subformElementId'] .= (isset($go_back['subformElementId']) AND $go_back['subformElementId'] !== '') ? ','.intval($_POST['prev_subformElementId']) : intval($_POST['prev_subformElementId']);
		unset($entries);
		unset($fids);
		unset($sub_fids);
		unset($sub_entries);
        // reset $fid
        if($_POST['goto_sfid']) {
			$fid = $_POST['goto_sfid'];
		}
		$fids[0] = $fid;
		$entry = $_POST['goto_sub'];
		$entries[$fid][0] = $entry;
		$single_result = getSingle($fid, $uid, $groups, $member_handler, $gperm_handler, $mid);
		$single = $single_result['flag'];
		if($single AND !$entry) {
			$entry = $single_result['entry'];
			unset($entries);
			$entries[$fid][0] = $entry;
		}
		unset($owner);
		$owner = getEntryOwner($entries[$fid][0], $fid); 
		$editing = is_numeric($entry); 
		unset($owner_groups);
		//$owner_groups =& $member_handler->getGroupsByUser($owner, FALSE);
		$newFidData_handler = new formulizeDataHandler($fid);
		$owner_groups = $newFidData_handler->getEntryOwnerGroups($entries[$fid][0]);
		$info_received_msg = 0;// never display this message when a subform is displayed the first time.	
		if($entry) { $info_continue = 1; }
		if(!$scheck = security_check($fid, $entries[$fid][0], $uid, $owner, $groups, $mid, $gperm_handler) AND !$viewallforms) {
			print "<p>" . _NO_PERM . "</p>";
			return;
		}
        // setup all fids/entries/subfids/subentries based on new fid and entry
        if($frid) {
            $linkResults = checkForLinks($frid, $fids, $fid, $entries, true); // final true means only include entries from unified display linkages
            $fids = $linkResults['fids'];
            $entries = $linkResults['entries'];
            $sub_fids = $linkResults['sub_fids'];
            $sub_entries = $linkResults['sub_entries'];
        }
	}

    // there are several points above where $entry is set, and now that we have a final value, store in ventry
    $settings['formulize_originalVentry'] = $settings['ventry'];
    if ($entry > 0 and (!isset($settings['ventry']) or ("addnew" != $settings['ventry'] and "single" != $settings['ventry'] and "proxy" != $settings['ventry']) )) {
        $settings['ventry'] = $entry;
    }

	// set the alldoneoverride if necessary -- August 22 2006
	$config_handler =& xoops_gethandler('config');
	$formulizeConfig = $config_handler->getConfigsByCat(0, $mid);
	// remove the all done button if the config option says 'no', and we're on a single-entry form, or the function was called to look at an existing entry, or we're on an overridden Multi-entry form
    $allDoneOverride = (!$formulizeConfig['all_done_singles'] AND !$profileForm AND (($single OR $overrideMulti OR $original_entry) AND !$_POST['target_sub'] AND !$_POST['goto_sfid'] AND !$_POST['deletesubsflag'] AND !$_POST['parent_form'])) ? true : false;
    if(($allDoneOverride OR (isset($_POST['save_and_leave']) AND $_POST['save_and_leave'])) AND $_POST['form_submitted']) {
		drawGoBackForm($go_back, $currentURL, $settings, $entry, $screen);
		print "<script type=\"text/javascript\">window.document.go_parent.submit();</script>\n";
		return;
	} else {
        
		// only do all this stuff below, the normal form displaying stuff, if we are not leaving this page now due to the all done button being overridden

		// we cannot have the back logic above invoked when dealing with a subform, but if the override is supposed to be in place, then we need to invoke it
		if(!$allDoneOverride AND !$formulizeConfig['all_done_singles'] AND !$profileForm AND ($_POST['target_sub'] OR $_POST['goto_sfid'] OR $_POST['deletesubsflag'] OR $_POST['parent_form']) AND ($single OR $original_entry OR $overrideMulti)) {
			$allDoneOverride = true;
		}

		/*if($uid==19) {
		print "Forms: ";
		print_r($fids);
		print "<br>Entries: ";
		print_r($entries);
		print "<br>Subforms: ";
		print_r($sub_fids);
		print "<br>Subentries: ";
		print_r($sub_entries); // debug block - ONLY VISIBLE TO USER 1 RIGHT NOW 
		} */
		
		formulize_benchmark("Ready to start building form.");
		
		$title = "";
        
        // determine the order of fids in $elements_allowed and go by that.
        // currently we don't generally finesse the order in $elements_allowed, but this will be sort of ready for controlling the order if we ever do??
        // compile elements probably needs a really big refactor, actually
        $elements_handler = xoops_getmodulehandler('elements', 'formulize');
        $newFids = array();
        foreach($elements_allowed as $ele_id) {
            if($elementObject = $elements_handler->get($ele_id)) {
                $elementFid = $elementObject->getVar('id_form');
                // if we could refactor so the newFids array is a series of fid/elements_allowed pairs, and we set them as the start of the main foreach(fids) loop, then maybe that would work to respect whatever order of whatever elements in whatever form? As long as elements allowed is constructed in the right order going into this function
                if(!isset($newFids[$elementFid])) {
                    $newFids[$elementFid] = $elementFid;
                }
            }
        }
        if(count($newFids)>0) {
            $fids = $newFids;
        }
		foreach($fids as $this_fid) {
	
			if(!$scheck = security_check($this_fid, $entries[$this_fid][0]) AND !$viewallforms) {
				continue;
			}
			
            // if there is more than one form, try to make the 1-1 links
            // and if we made any, then include the newly linked up entries
            // in the index of entries that we're keeping track of
            // makeOneToOneLinks will return the relevant ids, based on the links that were made when it was called earlier in readelements.php
            if(count($fids) > 1) {
                list($form1s, $form2s, $form1EntryIds, $form2EntryIds) = formulize_makeOneToOneLinks($frid, $this_fid);
                foreach($form1EntryIds as $i=>$form1EntryId) {
                    // $form1EntryId set above, now set other values for this iteration based on the key
                    $form2EntryId = $form2EntryIds[$i];
                    $form1 = $form1s[$i];
                    $form2 = $form2s[$i];
						if($form1EntryId) {
							$entries[$form1][0] = $form1EntryId;
						}
						if($form2EntryId) {
							$entries[$form2][0] = $form2EntryId;
						}
					} 
				}
			
				unset($prevEntry);
            // if there is an entry, then get the data for that entry
            if ($entries[$this_fid]) {
                $groupEntryWithUpdateRights = ($single == "group" AND $gperm_handler->checkRight("update_own_entry", $fid, $groups, $mid) AND $entry == $single_result['entry']);
					$prevEntry = getEntryValues($entries[$this_fid][0], $element_handler, $groups, $this_fid, $elements_allowed, $mid, $uid, $owner, $groupEntryWithUpdateRights); 
				}

				// display the form

				//get the form title: (do only once)
			$firstform = 0;
			if(!$form) {

                $firstform = 1;
                if(isset($passedInTitle) OR $titleOverride == 'all') {
                    $title = trans($passedInTitle);
                } elseif($screen) {
                    $title = trans($screen->getVar('title'));
                } else {
                    $title = trans(getFormTitle($this_fid));
                }
                unset($form);
                if($screen AND $screen->getVar('type')=='multiPage' AND isset($subScreen_handler)) {
                    $subScreen_handler->render($screen, $entry, $settings);
                    return;
                } elseif($formElementsOnly) {
                    $form = new formulize_elementsOnlyForm($title, 'formulize_eo_form', "$currentURL", "post", false, $frid, $screen);
                } else {
                    // extended class that puts formulize element names into the tr tags for the table, so we can show/hide them as required
                    $form = new formulize_themeForm($title, 'formulize_mainform', "$currentURL", "post", true, $frid, $screen);
                    // necessary to trigger the proper reloading of the form page, until Done is called and that form does not have this flag.
                    if (!isset($settings['ventry'])) {
                        $settings['ventry'] = 'new';
                    }
                    $form->addElement (new XoopsFormHidden ('ventry', $settings['ventry']));
                }
                $form->setExtra("enctype='multipart/form-data'"); // impératif!

                // include who the entry belongs to and the date
                // include acknowledgement that information has been updated if we have just done a submit
                // form_meta includes: last_update, created, last_update_by, created_by

                $breakHTML = "";

                if(!$profileForm AND $titleOverride != "all") {
                    // build the break HTML and then add the break to the form
                    if(!strstr($currentURL, "printview.php")) {
                        $breakHTML .= "<center class=\"no-print\">";
                        $breakHTML .= "<p><b>";
                        if($info_received_msg) {
                            $breakHTML .= _formulize_INFO_SAVED . "&nbsp;";
                        }
                        if($info_continue == 1 and formulizePermHandler::user_can_edit_entry($fid, $uid, $entry)) {
                            $breakHTML .= "<p class=\"no-print\">"._formulize_INFO_CONTINUE1."</p>";
                        } elseif($info_continue == 2) {
                            $breakHTML .=  "<p class=\"no-print\">"._formulize_INFO_CONTINUE2."</p>";
                        } elseif(!$entry and formulizePermHandler::user_can_edit_entry($fid, $uid, $entry)) {
                            $breakHTML .=  "<p class=\"no-print\">"._formulize_INFO_MAKENEW."</p>";
                        }
                        $breakHTML .= "</b></p>";
                        $breakHTML .= "</center>";
                    }

                    $breakHTML .= "<table cellpadding=5 width=100%><tr><td width=50% style=\"vertical-align: bottom;\">";
                    $breakHTML .= "<p><b>" . _formulize_FD_ABOUT . "</b><br>";

                    if(isset($entries[$this_fid][0]) AND $entries[$this_fid][0] AND $entries[$this_fid][0] != 'new') {
                        $form_meta = getMetaData($entries[$this_fid][0], $member_handler, $this_fid);
                        $breakHTML .= _formulize_FD_CREATED . $form_meta['created_by'] . " " . formulize_formatDateTime($form_meta['created']) . "<br>" . _formulize_FD_MODIFIED . $form_meta['last_update_by'] . " " . formulize_formatDateTime($form_meta['last_update']) . "</p>";
                    } else {
                        $breakHTML .= _formulize_FD_NEWENTRY . "</p>";
                    }

					$breakHTML .= "</td><td width=50% style=\"vertical-align: bottom;\">";
					if (strstr($currentURL, "printview.php") or !formulizePermHandler::user_can_edit_entry($fid, $uid, $entry)) {
						$breakHTML .= "<p>";
					} else {
                        $breakHTML .= "<p class='no-print'>";
						// get save and button button options
						$save_button_text = "";
						$done_button_text = "";
						if(is_array($button_text)) {
							$save_button_text = $button_text[1];
							$done_button_text = $button_text[0];						
                            $save_and_leave_button_text = $button_text[2];
						} else { 
							$done_button_text = $button_text;						
						}
						if(!$done_button_text AND !$allDoneOverride) {
							$done_button_text = _formulize_INFO_DONE1 . _formulize_DONE . _formulize_INFO_DONE2;
						} elseif($done_button_text != "{NOBUTTON}" AND !$allDoneOverride) {
							$done_button_text = _formulize_INFO_DONE1 . $done_button_text . _formulize_INFO_DONE2;
						// check to see if the user is allowed to modify the existing entry, and if they're not, then we have to draw in the all done button so they have a way of getting back where they're going
						} elseif (($entry and formulizePermHandler::user_can_edit_entry($fid, $uid, $entry)) OR !$entry) {
							$done_button_text = "";
						} else {
							$done_button_text = _formulize_INFO_DONE1 . _formulize_DONE . _formulize_INFO_DONE2;					
						}

						$nosave = false;
						if(!$save_button_text and formulizePermHandler::user_can_edit_entry($fid, $uid, $entry)) {
							$save_button_text = _formulize_INFO_SAVEBUTTON;
						} elseif ($save_button_text != "{NOBUTTON}" AND $save_button_text AND formulizePermHandler::user_can_edit_entry($fid, $uid, $entry)) {
							$save_button_text = _formulize_INFO_SAVE1 . $save_button_text . _formulize_INFO_SAVE2;
                        } elseif (formulizePermHandler::user_can_edit_entry($fid, $uid, $entry) == false OR (($save_button_text == "{NOBUTTON}" OR !$save_button_text) AND ($save_and_leave_button_text == "{NOBUTTON}" OR !$save_and_leave_button_text))) {
							$save_button_text = _formulize_INFO_NOSAVE;
							$nosave = true;
						}
                        $breakHTML .= $save_button_text;
                        if($save_and_leave_button_text != "{NOBUTTON}" AND $save_and_leave_button_text AND formulizePermHandler::user_can_edit_entry($fid, $uid, $entry)) {
                            $breakHTML .= '<span id="save_and_leave_help"><br>'._formulize_INFO_SAVEANDLEAVE1.$save_and_leave_button_text._formulize_INFO_SAVEANDLEAVE2.'</span>';    
                        }
						if($done_button_text) {
							$breakHTML .= "<br>" . $done_button_text;
						}
					}
					$breakHTML .= "</p></td></tr></table>";
					$form->insertBreakFormulize($breakHTML, "even", 'entry-metadata', 'entry-metadata');
				} elseif($profileForm) {
					// if we have a profile form, put the profile fields at the top of the form, populated based on the DB values from the _users table
					$form = addProfileFields($form, $profileForm);
				}
			}

			if($titleOverride=="1" AND !$firstform) { // set onetooneTitle flag to 1 when function invoked to force drawing of the form title over again
				$title = trans(getFormTitle($this_fid));
				$form->insertBreakFormulize("<table><th>$title</th></table>","head");
			}

			// if this form has a parent, then determine the $parentLinks
			if($go_back['form'] AND !$parentLinks[$this_fid]) {
				$parentLinks[$this_fid] = getParentLinks($this_fid, $frid);
			}

			formulize_benchmark("Before Compile Elements.");
			$form = compileElements($this_fid, $form, $element_handler, $prevEntry, $entries[$this_fid][0], $go_back,
				$parentLinks[$this_fid], $owner_groups, $groups, $overrideValue, $elements_allowed, $profileForm,
				$frid, $mid, $sub_entries, $sub_fids, $member_handler, $gperm_handler, $title, $screen,
				$printViewPages, $printViewPageTitles);
			formulize_benchmark("After Compile Elements.");
		}	// end of for each fids

        // if a new entry was created in a subform element that displays in multipage, then jump to that entry
        global $formulize_subformElementsWithNewEntries, $formulize_newSubformEntries;
        global $xoopsUser;

        // opens new entries in subform, if subform element is displaying sub entries via a multipage screen
        // should it be any kind of screen??
        // a multipage record is harder to represent as a row. A single page form could just be the more complete version of a row, so we show row and let people dive in.
        // We could make a more complex series of view button options in the subform element controls
        if(!$formulize_displayingSubform AND is_array($formulize_subformElementsWithNewEntries) AND is_array($formulize_newSubformEntries)) {
            foreach($formulize_subformElementsWithNewEntries as $candidate) {
                if($subformDisplayScreen = get_display_screen_for_subform($candidate)) {
                    $screenHandler = xoops_getmodulehandler('screen', 'formulize');
                    $plainScreenObject = $screenHandler->get($subformDisplayScreen);
                    if($plainScreenObject->getVar('type') == 'multiPage') {
                        $subformElementEleValue = $candidate->getVar('ele_value');
                        $subformId = $subformElementEleValue[0];
                        if($subformElementEleValue[3] == 1 AND isset($formulize_newSubformEntries[$subformId])) {
                            $newSubEntry = $formulize_newSubformEntries[$subformId][0]; // go to the first entry created in the subform
                            $newSubEntryScreen_handler = xoops_getmodulehandler($plainScreenObject->getVar('type').'Screen', 'formulize');
                            $subScreenObject = $newSubEntryScreen_handler->get($subformDisplayScreen);
                            $formulize_displayingSubform = array('originalFid'=>$fid, 'originalEntry'=>$entry);
                            $_POST['goto_sfid'] = $subScreenObject->getVar('fid');
                            $_POST['goto_sub'] = $newSubEntry;
                            $_POST['goto_subformElementId'] = $candidate->getVar('ele_id');
                            unset($_POST['formulize_currentPage']); // want to make sure we land on page 1
                            $newSubEntryScreen_handler->render($subScreenObject, $newSubEntry, $settings);                            
                            return;
                        }
                    }
                }
            }
        }
        
        if(!is_object($form)) {
            exit("Error: the form cannot be displayed.  Does the current group have permission to access the form?");
        }

        if(is_array($settings) AND !$formElementsOnly) {
            $form = writeHiddenSettings($settings, $form, $entries, $sub_entries, $screen);
        }
        
        if(count($sub_fids) > 0) { // if there are subforms, then draw them in...only once we have a bonafide entry in place already
            // draw in special params for this form, but only once per page
            global $formulize_subformHiddenFieldsDrawn;
            if ($formulize_subformHiddenFieldsDrawn != true) {
                $formulize_subformHiddenFieldsDrawn = true;
                $form->addElement (new XoopsFormHidden ('target_sub', ''));
                $form->addElement (new XoopsFormHidden ('target_sub_frid', ''));
                $form->addElement (new XoopsFormHidden ('target_sub_fid', ''));
                $form->addElement (new XoopsFormHidden ('target_sub_mainformentry', ''));
                $form->addElement (new XoopsFormHidden ('target_sub_subformelement', ''));
                $form->addElement (new XoopsFormHidden ('target_sub_parent_subformelement', '')); // used to pickup declared subform element when modals are rendered?
                $form->addElement (new XoopsFormHidden ('target_sub_open_modal', ''));
                $form->addElement (new XoopsFormHidden ('target_sub_instance', ''));
                $form->addElement (new XoopsFormHidden ('numsubents', 1));
                $form->addElement (new XoopsFormHidden ('del_subs', ''));
                $form->addElement (new XoopsFormHidden ('goto_sub', ''));
                $form->addElement (new XoopsFormHidden ('goto_sfid', ''));
            }

            // DRAW IN THE SPECIAL UI FOR A SUBFORM LINK (ONE TO MANY)
			foreach($sub_fids as $subform_id) {
				// only draw in the subform UI if the subform hasn't been drawn in previously, courtesy of a subform element in the form.
				// Subform elements are recommended since they provide 1. specific placement, 2. custom captions, 3. direct choice of form elements to include
				if(in_array($subform_id, $sfidsDrawn) OR $elements_allowed OR (!$scheck = security_check($subform_id, "", $uid, $owner, $groups, $mid, $gperm_handler) AND !$viewallforms)) { // no entry passed so this will simply check whether they have permission for the form or not
					continue;
				}
				$subUICols = drawSubLinks($subform_id, $sub_entries, $uid, $groups, $frid, $mid, $fid, $entry);
				unset($subLinkUI);
				if(isset($subUICols['single'])) {
					$form->insertBreakFormulize($subUICols['single'], "even");
				} else {
					$subLinkUI = new XoopsFormLabel($subUICols['c1'], $subUICols['c2']);
					$form->addElement($subLinkUI);
				}
			}
		} 
	
	
		// draw in proxy box if necessary (only if they have permission and only on new entries, not on edits)
		if(!strstr($_SERVER['PHP_SELF'], "formulize/printview.php")) {
			if($gperm_handler->checkRight("add_proxy_entries", $fid, $groups, $mid) AND !$entries[$fid][0]) {
				$form = addOwnershipList($form, $groups, $member_handler, $gperm_handler, $fid, $mid);
			} elseif($entries[$fid][0] AND $gperm_handler->checkRight("update_entry_ownership", $fid, $groups, $mid)) {
				$form = addOwnershipList($form, $groups, $member_handler, $gperm_handler, $fid, $mid, $entries[$fid][0]);	
			}
		}
	
		// draw in the submitbutton if necessary
		if (!$formElementsOnly) {
			$form = addSubmitButton($form, _formulize_SAVE, $go_back, $currentURL, $button_text, $settings, $entry, $fids, $formframe, $mainform, $entry, $profileForm, $elements_allowed, $allDoneOverride, $printall, $screen);
			}
	
		if(!$formElementsOnly) {
			
			// add flag to indicate that the form has been submitted
			$form->addElement (new XoopsFormHidden ('form_submitted', "1"));
			if($go_back['form']) { // if this is set, then we're doing a subform, so put in a flag to prevent the parent from being drawn again on submission
				$form->addElement (new XoopsFormHidden ('sub_fid', $fid));
				$form->addElement (new XoopsFormHidden ('sub_submitted', $entries[$fid][0]));
				$form->addElement (new XoopsFormHidden ('go_back_form', $go_back['form']));
				$form->addElement (new XoopsFormHidden ('go_back_entry', $go_back['entry']));
                $form->addElement (new XoopsFormHidden ('go_back_page', $go_back['page']));
                $form->addElement (new XoopsFormHidden ('go_back_subformElementId', $go_back['subformElementId']));
                $form->addElement (new XoopsFormHidden ('deletesubsflag', 0)); // necessary so validation javascript will function
                $form->addElement (new XoopsFormHidden ('clonesubsflag', 0)); 
                $form->addElement (new XoopsFormHidden ('modalscroll', 0));
			} else {
				// drawing a main form...put in the scroll position flag
                $form->addElement (new XoopsFormHidden ('modalscroll', 0));
				$form->addElement (new XoopsFormHidden ('yposition', 0));
                $form->addElement (new XoopsFormHidden ('deletesubsflag', 0));
                $form->addElement (new XoopsFormHidden ('clonesubsflag', 0));
			}
			
			drawJavascript($nosave);
            $form->addElement(new xoopsFormHidden('save_and_leave', 0));
		// lastly, put in a hidden element, that will tell us what the first, primary form was that we were working with on this form submission
		$form->addElement (new XoopsFormHidden ('primaryfid', $fids[0]));
		
		}

		global $formulize_governingElements;
		global $formulize_oneToOneElements;
		global $formulize_oneToOneMetaData;
		if(!is_array($formulize_governingElements)) {
				$formulize_governingElements = array();
		}
		if(!is_array($formulize_oneToOneElements)) {
				$oneToOneElements = array();
		}
		if(!is_array($oneToOneMetaData)) {
				$oneToOneMetaData = array();		
		}
		if(count($GLOBALS['formulize_renderedElementHasConditions'])>0) {
			$governingElements1 = compileGoverningElementsForConditionalElements($GLOBALS['formulize_renderedElementHasConditions'], $entries, $sub_entries);
			foreach($governingElements1 as $key=>$value) {
					$oneToOneElements[$key]	= false;
			}
			$formulize_governingElements = mergeGoverningElements($formulize_governingElements, $governingElements1);
		}
		// add in any onetoone elements that we need to deal with at the same time (in case their joining key value changes on the fly)
		if(count($fids)>1) {
            foreach($fids as $thisFid) {
                $relationship_handler = xoops_getmodulehandler('frameworks', 'formulize');
                $relationship = $relationship_handler->get($frid);
                foreach($relationship->getVar('links') as $thisLink) {
                        if($thisLink->getVar('form1') == $thisFid) {
                                $keyElement = $thisLink->getVar('key2');
                                break;
                        } elseif($thisLink->getVar('form2') == $thisFid) {
                                $keyElement = $thisLink->getVar('key1');
                                break;
                        }
                }
                if($keyElementObject = _getElementObject($keyElement)) {
                    // prepare to loop through elements for the rendered entry, or 'new', if there is no rendered entry
                    $entryToLoop = isset($entries[$thisFid][0]) ? $entries[$thisFid][0] : null;
                    if(!$entryToLoop AND isset($GLOBALS['formulize_renderedElementsForForm'][$thisFid]['new'])) {
                        $entryToLoop = 'new';
                    }
                    foreach($GLOBALS['formulize_renderedElementsForForm'][$thisFid][$entryToLoop] as $renderedMarkupName => $thisElement) {
                            $GLOBALS['formulize_renderedElementHasConditions'][$renderedMarkupName] = $thisElement;
					        $governingElements2 = _compileGoverningElements($entries, $keyElementObject, $renderedMarkupName, true); // last true marks it as one to one compiling, when matching entry ids between governed and governing elements doesn't matter
                            foreach($governingElements2 as $key=>$value) {
                                    $formulize_oneToOneElements[$key] = true;
                                    $formulize_oneToOneMetaData[$key] = array('onetoonefrid' => $frid, 'onetoonefid' => $fid, 'onetooneentries' => urlencode(serialize($entries)), 'onetoonefids'=>urlencode(serialize($fids)));			
                            }
                            $formulize_governingElements = mergeGoverningElements($formulize_governingElements, $governingElements2);
                    }
                }
            }
		}
        // if there are elements we need to pay attention to, draw the necessary javascript code
        // unless we're doing an embedded 'elements only form' -- unless we're doing that for displaying a subform entry specifically as its own thing (as part of a modal for example (and only example right now))
		if(count($formulize_governingElements)> 0 AND (!$formElementsOnly OR (isset($formulize_displayingSubform) AND $formulize_displayingSubform == true))) { 
			drawJavascriptForConditionalElements($GLOBALS['formulize_renderedElementHasConditions'], $formulize_governingElements, $formulize_oneToOneElements, $formulize_oneToOneMetaData);	
		}
		
        // need to always include, once, the subformelementid that is being displayed, regardless of whether there are more subs below this or not
        $idForForm = "";
        if(!$formElementsOnly) {
            $subformElementIdToUse = isset($_POST['goto_subformElementId']) ? intval($_POST['goto_subformElementId']) : 0; 
            $form->addElement (new XoopsFormHidden ('goto_subformElementId', $subformElementIdToUse)); // switches to new one if we're drilling down
            $form->addElement (new XoopsFormHidden ('prev_subformElementId', $subformElementIdToUse)); // always remains the current one
            $idForForm = "id=\"formulizeform\""; // only use the master id when rendering a "normal" form, the master one on the page, not when rendering disembodied elements only forms!
        }

		print "<div $idForForm>".$form->render()."</div><!-- end of formulizeform -->"; // note, security token is included in the form by the xoops themeform render method, that's why there's no explicity references to the token in the compiling/generation of the main form object

        // floating save button
        if($printall != 2 AND $formulizeConfig['floatSave'] AND !strstr($currentURL, "printview.php") AND !$formElementsOnly){
            print "<div id=floattest></div>";
            if( $done_text !="{NOBUTTON}" OR $save_text !="{NOBUTTON}") {
                print "<div id=floatingsave>";
                if( $subButtonText == _formulize_SAVE ){
                    if($save_text) { $subButtonText = $save_text; }
                    if($subButtonText != "{NOBUTTON}") {
                        print "<input type='button' name='submitx' id='submitx' class=floatingsavebuttons onclick=javascript:validateAndSubmit(); value='"._formulize_SAVE."' >";
                        print "<input type='button' name='submit_save_and_leave' id='submit_save_and_leave' class=floatingsavebuttons onclick=javascript:validateAndSubmit('leave'); value='"._formulize_SAVE_AND_LEAVE."' >";
                    }
                }
                if((($button_text != "{NOBUTTON}" AND !$done_text) OR (isset($done_text) AND $done_text != "{NOBUTTON}")) AND !$allDoneOverride){
                    if($done_text) { $button_text = $done_text_temp; }
                    print "<input type='button' class=floatingsavebuttons onclick=javascript:verifyDone(); value='"._formulize_DONE."' >";
                }
                print "</div>";
            }
        }
        // end floating save button

		// if we're in Drupal, include the main XOOPS js file, so the calendar will work if present...
		// assumption is that the calendar javascript has already been included by the datebox due to no
		// $xoopsTpl being in effect in Drupal -- this assumption will fail if Drupal is displaying a pageworks
		// page that uses the $xoopsTpl, for instance.  (Date select box file itself checks for $xoopsTpl)
		global $user;
		static $includedXoopsJs = false;
		if(is_object($user) AND !$includedXoopsJs) {
			print "<script type=\"text/javascript\" src=\"" . XOOPS_URL . "/include/xoops.js\"></script>\n";
			$includedXoopsJs = true;
		}
	}// end of if we're not going back to the prev page because of an all done button override
    
    // create any sub entries requested from a modal subform add-new button click
    // only when processing the main form, not any elements-only forms embedded as subs within the page
    if(!strstr($currentURL, "printview.php") AND !$formElementsOnly) {
        $newSubEntryInModal = false;
        if(!in_array($_POST['target_sub'], $formulize_subFidsWithNewEntries) AND isset($_POST['target_sub']) AND $_POST['target_sub'] AND count($subs_to_del)==0 AND count($subs_to_clone)==0) {
            list($elementq, $element_to_write, $value_to_write, $value_source, $value_source_form) = formulize_subformSave_determineElementToWrite($_POST['target_sub_frid'], $_POST['target_sub_fid'], $_POST['target_sub_mainformentry'], $_POST['target_sub']);
            $element_handler = xoops_getmodulehandler('elements','formulize');
            $subformElementObject = $element_handler->get($_POST['target_sub_subformelement']);
            $subformElementEleValue = $subformElementObject->getVar('ele_value');
            formulize_subformSave_writeNewEntry($element_to_write, $value_to_write, $fid, $frid, $_POST['target_sub'], $_POST['target_sub_mainformentry'], $subformElementEleValue[7], $subformElementEleValue[5], getEntryOwner($_POST['target_sub_mainformentry'], $fid), $_POST['numsubents']);
            $newSubEntryInModal = true; // we didn't make the entry as part of the normal subform creation process, therefore it is a new subsub entry inside a modal dialog, so when displaying it, open the parent entry in the modal (the sub entry) so the sub sub entry is shown properly
        }
        // force open a modal if we have just made a new entry and modal is active for that subfid
        global $subformSubEntryMap;
        // if a sub requested, and we just made a new subentry or just deleted a subentry (that's not a sub of the mainform), and we did that through a subform UI that triggers modals...
        if(isset($_POST['target_sub']) AND $_POST['target_sub'] AND 
           (isset($subformSubEntryMap[$_POST['target_sub']]) OR ((count($subs_to_del)>0 OR count($subs_to_clone)>0) AND $_POST['target_sub_fid'] != $fid))
           AND isset($_POST['target_sub_open_modal']) AND $_POST['target_sub_open_modal'] == 'Modal') {
            if(count($subs_to_del)>0 OR count($subs_to_clone)>0) {
                $entryToShowInModal = intval($_POST['target_sub_mainformentry']);
                $formToShowInModal = intval($_POST['target_sub_fid']);
                $subformElementIdForModal = intval($_POST['target_sub_parent_subformelement']);
            } elseif($newSubEntryInModal) {
                $entryToShowInModal = intval($subformSubEntryMap[$_POST['target_sub']][0]['parent']);
                $formToShowInModal = intval($_POST['target_sub_fid']);
                $subformElementIdForModal = intval($_POST['target_sub_parent_subformelement']);
            } else {
                $entryToShowInModal = intval($subformSubEntryMap[$_POST['target_sub']][0]['self']);
                $formToShowInModal = intval($_POST['target_sub']);
                $subformElementIdForModal = intval($_POST['target_sub_subformelement']);
            }
            $modalScroll = isset($_POST['modalscroll']) ? intval($_POST['modalscroll']) : 0;
            print "<script type='text/javascript'>\n
    jQuery(document).ready(function() {
        goSubModal(".$entryToShowInModal.", ".$formToShowInModal.", ".$frid.", ".$fid.", ".$entries[$fid][0].", ".$subformElementIdForModal.", ".$modalScroll.");
    });
</script>
";
        }
    }

    $GLOBALS['formulize_completedFormRendering'] = true; // a flag used by multipage form display, to understand when we've finally output a form to the screen, so it's okay to render headers and the form we have just rendered
    
}

// THIS FUNCTION ADDS THE SPECIAL PROFILE FIELDS TO THE TOP OF A PROFILE FORM
function addProfileFields($form, $profileForm) {
	// add... 
	// username
	// full name
	// e-mail
	// timezone
	// password

	global $xoopsUser, $xoopsConfig, $xoopsConfigUser;
	$config_handler =& xoops_gethandler('config');
	$xoopsConfigUser =& $config_handler->getConfigsByCat(XOOPS_CONF_USER);
	$user_handler =& xoops_gethandler('user');
	$thisUser = $user_handler->get($profileForm);

	// initialize $thisUser
	if($thisUser) {
		$thisUser_name = $thisUser->getVar('name', 'E');
		$thisUser_uname = $thisUser->getVar('uname');
		$thisUser_timezone_offset = $thisUser->getVar('timezone_offset');
		$thisUser_email = $thisUser->getVar('email');
		$thisUser_uid = $thisUser->getVar('uid');
		$thisUser_viewemail = $thisUser->user_viewemail();
		$thisUser_umode = $thisUser->getVar('umode');
		$thisUser_uorder = $thisUser->getVar('uorder');
		$thisUser_notify_method = $thisUser->getVar('notify_method');
		$thisUser_notify_mode = $thisUser->getVar('notify_mode');
		$thisUser_user_sig = $thisUser->getVar('user_sig', 'E');
		$thisUser_attachsig = $thisUser->getVar('attachsig');
	} else { // anon user
		$thisUser_name = $GLOBALS['name']; //urldecode($_GET['name']);
		$thisUser_uname = $GLOBALS['uname']; //urldecode($_GET['uname']);
		$thisUser_timezone_offset = isset($GLOBALS['timezone_offset']) ? $GLOBALS['timezone_offset'] : $xoopsConfig['default_TZ']; // isset($_GET['timezone_offset']) ? urldecode($_GET['timezone_offset']) : $xoopsConfig['default_TZ'];
		$thisUser_email = $GLOBALS['email']; //urldecode($_GET['email']);
		$thisUser_viewemail = $GLOBALS['user_viewemail']; //urldecode($_GET['viewemail']);
		$thisUser_uid = 0;
		$agree_disc = $GLOBALS['agree_disc'];
	}

		include_once XOOPS_ROOT_PATH . "/language/" . $xoopsConfig['language'] . "/user.php";

	$form->insertBreak(_formulize_ACTDETAILS, "head");
	// Check reg_codes module option to use email address as username
	$module_handler =& xoops_gethandler('module');
	$regcodesModule =& $module_handler->getByDirname("reg_codes");
	$regcodesConfig =& $config_handler->getConfigsByCat(0, $regcodesModule->getVar('mid'));

	// following borrowed from edituser.php
	if($profileForm == "new") {
		// 'new' should ONLY be coming from the modified register.php file that the registration codes module uses
		// ie: we are assuming registration codes is installed
		$form->addElement(new XoopsFormHidden('userprofile_regcode', $GLOBALS['regcode']));
		$uname_size = $xoopsConfigUser['maxuname'] < 255 ? $xoopsConfigUser['maxuname'] : 255;
		$labelhelptext = _formulize_USERNAME_HELP1; // set it to a variable so we can test for its existence; don't want to print this stuff if there's no translation
		$labeltext = $labelhelptext == "" ? _US_NICKNAME : _US_NICKNAME . _formulize_USERNAME_HELP1 . $xoopsConfigUser['minuname'] . _formulize_USERNAME_HELP2 . $uname_size . _formulize_USERNAME_HELP3;
		if ($regcodesConfig['email_as_username'] == 0)	{
			// Allow User names to be created
			$uname_label = new XoopsFormText($labeltext, 'userprofile_uname', $uname_size, $uname_size, $thisUser_uname);
			$uname_reqd = 1;
		}
		else {
			// Usernames are created based on email address
			$uname_label = new XoopsFormHidden('userprofile_uname', $thisUser_uname);
			$uname_reqd = 0;
		}
		$form->addElement($uname_label, $uname_reqd);
	} else {
		$uname_label = new XoopsFormLabel(_US_NICKNAME, $thisUser_uname);
		$form->addElement($uname_label);
	}
	$email_tray = new XoopsFormElementTray(_US_EMAIL, '<br />');
	if ($profileForm == "new" OR (($xoopsConfigUser['allow_chgmail'] == 1) && ($regcodesConfig['email_as_username'] == 0))) {
      	$email_text = new XoopsFormText('', 'userprofile_email', 30, 255, $thisUser_email);
		$email_tray->addElement($email_text, 1);
	}
	else {
        $email_text = new XoopsFormLabel('', $thisUser_email);
		$email_tray->addElement($email_text);
	}
	$email_cbox_value = $thisUser_viewemail ? 1 : 0;
	$email_cbox = new XoopsFormCheckBox('', 'userprofile_user_viewemail', $email_cbox_value);
	$email_cbox->addOption(1, _US_ALLOWVIEWEMAIL);
	$email_tray->addElement($email_cbox);
	$form->addElement($email_tray, 1);
	
		
	$passlabel = $profileForm == "new" ? _formulize_TYPEPASSTWICE_NEW : _formulize_TYPEPASSTWICE_CHANGE;
	$passlabel .= $xoopsConfigUser['minpass'] . _formulize_PASSWORD_HELP1;
	$pwd_tray = new XoopsFormElementTray(_US_PASSWORD.'<br />'.$passlabel);
	$pwd_text = new XoopsFormPassword('', 'userprofile_password', 10, 32);
	$pwd_text2 = new XoopsFormPassword('', 'userprofile_vpass', 10, 32);
	$pass_required = $profileForm == "new" ? 1 : 0;
	$pwd_tray->addElement($pwd_text, $pass_required);
	$pwd_tray->addElement($pwd_text2, $pass_required);
	$form->addElement($pwd_tray, $pass_required);
	$name_text = new XoopsFormText(_US_REALNAME, 'userprofile_name', 30, 60, $thisUser_name);
	$form->addElement($name_text, 1);
	$timezone_select = new XoopsFormSelectTimezone(_US_TIMEZONE, 'userprofile_timezone_offset', $thisUser_timezone_offset);
	$form->addElement($timezone_select);

	if($profileForm != "new") {
      	$umode_select = new XoopsFormSelect(_formulize_CDISPLAYMODE, 'userprofile_umode', $thisUser_umode);
      	$umode_select->addOptionArray(array('nest'=>_NESTED, 'flat'=>_FLAT, 'thread'=>_THREADED));
      	$form->addElement($umode_select);
      	$uorder_select = new XoopsFormSelect(_formulize_CSORTORDER, 'userprofile_uorder', $thisUser_uorder);
      	$uorder_select->addOptionArray(array(XOOPS_COMMENT_OLD1ST => _OLDESTFIRST, XOOPS_COMMENT_NEW1ST => _NEWESTFIRST));
      	$form->addElement($uorder_select);
      	include_once XOOPS_ROOT_PATH . "/language/" . $xoopsConfig['language'] . '/notification.php';
      	include_once XOOPS_ROOT_PATH . '/include/notification_constants.php';
      	$notify_method_select = new XoopsFormSelect(_NOT_NOTIFYMETHOD, 'userprofile_notify_method', $thisUser_notify_method);
      	$notify_method_select->addOptionArray(array(XOOPS_NOTIFICATION_METHOD_DISABLE=>_NOT_METHOD_DISABLE, XOOPS_NOTIFICATION_METHOD_PM=>_NOT_METHOD_PM, XOOPS_NOTIFICATION_METHOD_EMAIL=>_NOT_METHOD_EMAIL));
      	$form->addElement($notify_method_select);
      	$notify_mode_select = new XoopsFormSelect(_NOT_NOTIFYMODE, 'userprofile_notify_mode', $thisUser_notify_mode);
      	$notify_mode_select->addOptionArray(array(XOOPS_NOTIFICATION_MODE_SENDALWAYS=>_NOT_MODE_SENDALWAYS, XOOPS_NOTIFICATION_MODE_SENDONCETHENDELETE=>_NOT_MODE_SENDONCE, XOOPS_NOTIFICATION_MODE_SENDONCETHENWAIT=>_NOT_MODE_SENDONCEPERLOGIN));
      	$form->addElement($notify_mode_select);
      	$sig_tray = new XoopsFormElementTray(_US_SIGNATURE, '<br />');
      	include_once XOOPS_ROOT_PATH . '/include/xoopscodes.php';
      	$sig_tarea = new XoopsFormDhtmlTextArea('', 'userprofile_user_sig', $thisUser_user_sig);
      	$sig_tray->addElement($sig_tarea);
      	$sig_cbox_value = $thisUser_attachsig ? 1 : 0;
      	$sig_cbox = new XoopsFormCheckBox('', 'userprofile_attachsig', $sig_cbox_value);
      	$sig_cbox->addOption(1, _US_SHOWSIG);
      	$sig_tray->addElement($sig_cbox);
      	$form->addElement($sig_tray);
	} else { // display only on new account creation...
		if ($xoopsConfigUser['reg_dispdsclmr'] != 0 && $xoopsConfigUser['reg_disclaimer'] != '') {
			$disc_tray = new XoopsFormElementTray(_US_DISCLAIMER, '<br />');
			$disc_text = new XoopsFormTextarea('', 'disclaimer', trans($xoopsConfigUser['reg_disclaimer']), 8);
			$disc_text->setExtra('readonly="readonly"');
			$disc_tray->addElement($disc_text);
			$agree_chk = new XoopsFormCheckBox('', 'userprofile_agree_disc', $agree_disc);
			$agree_chk->addOption(1, "<span style=\"font-size: 14pt;\">" . _US_IAGREE . "</span>");
			$disc_tray->addElement($agree_chk);
			$form->addElement($disc_tray);
		}
		$form->addElement(new XoopsFormHidden("op", "newuser"));
	}

	$uid_check = new XoopsFormHidden("userprofile_uid", $thisUser_uid);
	$form->addElement($uid_check);
	$form->insertBreak(_formulize_PERSONALDETAILS, "head");

	return $form;

} 


// add the submit button to a form
function addSubmitButton($form, $subButtonText, $go_back="", $currentURL, $button_text, $settings, $entry, $fids, $formframe, $mainform, $cur_entry, $profileForm, $elements_allowed="", $allDoneOverride=false, $printall=0, $screen=null) { //nmc 2007.03.24 - added $printall

    global $xoopsUser;
    $fid = $fids[key($fids)]; // get first element in array, might not be keyed as 0 :(
    $uid = $xoopsUser ? $xoopsUser->getVar('uid') : 0;

	if($printall == 2) { // 2 is special setting in multipage screens that means do not include any printable buttons of any kind
		return $form;
	}

	if(strstr($currentURL, "printview.php")) { // don't do anything if we're on the print view
		return $form;
	} else {

	drawGoBackForm($go_back, $currentURL, $settings, $entry, $screen);

        $pv_text_temp = _formulize_PRINTVIEW;
        if(!$button_text OR ($button_text == "{NOBUTTON}" AND $go_back['form'])) { // presence of a goback form (ie: parent form) overrides {NOBUTTON} -- assumption is the save button will not also be overridden at the same time
		$button_text = _formulize_DONE; 
	} elseif(is_array($button_text)) {
		if(!$button_text[0]) { 
			$done_text_temp = _formulize_DONE; 
		} else {
			$done_text_temp = $button_text[0];
		}
		if(!$button_text[1]) { 
			$save_text_temp = _formulize_SAVE; 
		} else {
			$save_text_temp = $button_text[1];
		}
            if(!$button_text[2]) { 
                $save_and_leave_text_temp = _formulize_SAVE_AND_LEAVE; 
            } else {
                $save_and_leave_text_temp = $button_text[2];
            }
            if($button_text[3]) { 
                $pv_text_temp = $button_text[3];
	}
        }
    




        // formulize_displayingMultipageScreen is set in formdisplaypages to indicate we're displaying a multipage form
        global $formulize_displayingMultipageScreen;
        // do not use printable button for profile forms
        if(!$profileForm AND $pv_text_temp != "{NOBUTTON}") {

		$newcurrentURL= XOOPS_URL . "/modules/formulize/printview.php";
		print "<form name='printview' action='".$newcurrentURL."' method=post target=_blank>\n";
		
		// add security token
		if(isset($GLOBALS['xoopsSecurity'])) {
			print $GLOBALS['xoopsSecurity']->getTokenHTML();
		}
		
		$currentPage = "";
		$screenid = "";
    if($screen) {
		  $screenid = $screen->getVar('sid');
			// check for a current page setting
			if(isset($settings['formulize_currentPage'])) {
				$currentPage = $settings['formulize_currentPage'];
			}
		}
    
		print "<input type=hidden name=screenid value='".$screenid."'>";
		print "<input type=hidden name=currentpage value='".$currentPage."'>";

		print "<input type=hidden name=lastentry value=".$cur_entry.">";
		if($go_back['form']) { // we're on a sub, so display this form only
			print "<input type=hidden name=formframe value=".$fids[0].">";	
		} else { // otherwise, display like normal
			print "<input type=hidden name=formframe value='".$formframe."'>";	
			print "<input type=hidden name=mainform value='".$mainform."'>";
		}
		if(is_array($elements_allowed)) {
			$ele_allowed = implode(",",$elements_allowed);
			print "<input type=hidden name=elements_allowed value='".$ele_allowed."'>";
		} else {
			print "<input type=hidden name=elements_allowed value=''>";
		}
		print "</form>";
		//added by Cory Aug 27, 2005 to make forms printable
            
            $printbutton = new XoopsFormButton('', 'printbutton',  $pv_text_temp, 'button');
            if(is_array($elements_allowed)) {
                $ele_allowed = implode(",",$elements_allowed);
            }
            $printbutton->setExtra("onclick='javascript:PrintPop(\"$ele_allowed\");'");
            $rendered_buttons = $printbutton->render(); // nmc 2007.03.24 - added
            if ($printall) {																					// nmc 2007.03.24 - added
                $printallbutton = new XoopsFormButton('', 'printallbutton', str_replace(_formulize_PRINTVIEW, $pv_text_temp, _formulize_PRINTALLVIEW), 'button');	// nmc 2007.03.24 - added
                $printallbutton->setExtra("onclick='javascript:PrintAllPop();'");								// nmc 2007.03.24 - added
                $rendered_buttons .= "&nbsp;&nbsp;&nbsp;" . $printallbutton->render();							// nmc 2007.03.24 - added
                }
            $buttontray = new XoopsFormElementTray($rendered_buttons, "&nbsp;&nbsp;&nbsp;", 'button-controls'); // nmc 2007.03.24 - amended [nb: FormElementTray 'caption' is actually either 1 or 2 buttons]
        } else {
            $buttontray = new XoopsFormElementTray("", "&nbsp;&nbsp;&nbsp;", 'button-controls');
        }
        $buttontray->setClass("no-print");
    
        if($save_text_temp) { $subButtonText = $save_text_temp; }
        
        if($subButtonText != "{NOBUTTON}" AND formulizePermHandler::user_can_edit_entry($fid, $uid, $entry)) {
            $saveButton = new XoopsFormButton('', 'submitx', trans($subButtonText), 'button'); // doesn't use name submit since that conflicts with the submit javascript function
            $saveButton->setExtra("onclick=javascript:validateAndSubmit();");
            $buttontray->addElement($saveButton);
        }

        if(isset($save_and_leave_text_temp) AND $save_and_leave_text_temp != "{NOBUTTON}" AND formulizePermHandler::user_can_edit_entry($fid, $uid, $entry)) {
            // also add in the save and leave button
            $saveAndLeaveButton = new XoopsFormButton('', 'submit_save_and_leave', trans($save_and_leave_text_temp), 'button');
            $saveAndLeaveButton->setExtra("onclick=javascript:validateAndSubmit('leave');");
            $buttontray->addElement($saveAndLeaveButton);
        }
    
        if((($button_text != "{NOBUTTON}" AND !$done_text_temp) OR (isset($done_text_temp) AND $done_text_temp != "{NOBUTTON}")) AND !$allDoneOverride) { 
            if($done_text_temp) { $button_text = $done_text_temp; }
            $donebutton = new XoopsFormButton('', 'donebutton', trans($button_text), 'button');
            $donebutton->setExtra("onclick=javascript:verifyDone();");
            $buttontray->addElement($donebutton); 
	}

	$trayElements = $buttontray->getElements();
        if(count($trayElements) > 0 OR $formulize_displayingMultipageScreen) {
		$form->addElement($buttontray);
	}
	return $form;
	}
}

// this function draws in the hidden form that handles the All Done logic that sends user off the form
function drawGoBackForm($go_back, $currentURL, $settings, $entry, $screen) {
	if($go_back['url'] == "" AND !isset($go_back['form'])) { // there are no back instructions at all, then make the done button go to the front page of whatever is going on in pageworks
		print "<form name=go_parent action=\"$currentURL\" method=post>"; //onsubmit=\"javascript:verifyDone();\" method=post>";
		if(is_array($settings)) { writeHiddenSettings($settings, null, array(), array(), $screen); }
		print "<input type=hidden name=lastentry value=$entry>";
		print "</form>";
	}
	if($go_back['form']) { // parent form overrides specified back URL
		print "<form name=go_parent action=\"$currentURL\" method=post>"; //onsubmit=\"javascript:verifyDone();\" method=post>";
		print "<input type=hidden name=parent_form value=" . $go_back['form'] . ">";
		print "<input type=hidden name=parent_entry value=" . $go_back['entry'] . ">";
        print "<input type=hidden name=parent_page value=" . $go_back['page'] . ">";
        print "<input type=hidden name=parent_subformElementId value=" . $go_back['subformElementId'] . ">";
		print "<input type=hidden name=ventry value=" . $settings['ventry'] . ">";
		if(is_array($settings)) { writeHiddenSettings($settings, null, array(), array(), $screen); }
		print "<input type=hidden name=lastentry value=$entry>";
		print "</form>";
	} elseif($go_back['url']) {
		print "<form name=go_parent action=\"" . $go_back['url'] . "\" method=post>"; //onsubmit=\"javascript:verifyDone();\" method=post>";
		if(is_array($settings)) { writeHiddenSettings($settings, null, array(), array(), $screen); }		
		print "<input type=hidden name=lastentry value=$entry>";
		print "</form>";
	} 
}

// this function draws in the UI for sub links
function drawSubLinks($subform_id, $sub_entries, $uid, $groups, $frid, $mid, $fid, $entry,
	$customCaption = "", $customElements = "", $defaultblanks = 0, $showViewButtons = 1, $captionsForHeadings = 0,
	$overrideOwnerOfNewEntries = "", $mainFormOwner = 0, $hideaddentries = "", $subformConditions = null, $subformElementId = 0,
	$rowsOrForms = 'row', $addEntriesText = _formulize_ADD_ENTRIES, $subform_element_object = null)
{
    
    require_once XOOPS_ROOT_PATH.'/modules/formulize/include/subformSaveFunctions.php';
    
    $renderingSubformUIInModal = strstr($_SERVER['SCRIPT_NAME'], 'subformdisplay-elementsonly.php') ? true : false;
    
	$nestedSubform = false;
	if(isset($GLOBALS['formulize_inlineSubformFrid'])) {
		$frid = $GLOBALS['formulize_inlineSubformFrid'];
		$nestedSubform = true;
	}

    $member_handler = xoops_gethandler('member');
    $gperm_handler = xoops_gethandler('groupperm');

    $addEntriesText = $addEntriesText ? $addEntriesText : _formulize_ADD_ENTRIES;

	global $xoopsDB, $xoopsUser;
    
	$GLOBALS['framework'] = $frid;
	$form_handler = xoops_getmodulehandler('forms', 'formulize');

	// limit the sub_entries array to just the entries that match the conditions, if any
	if(is_array($subformConditions) and is_array($sub_entries[$subform_id])) {
		list($conditionsFilter, $conditionsFilterOOM, $curlyBracketFormFrom) = buildConditionsFilterSQL($subformConditions, $subform_id, $entry, $mainFormOwner, $fid); // pass in mainFormOwner as the comparison ID for evaluating {USER} so that the included entries are consistent when an admin looks at a set of entries made by someone else.
		$subformObject = $form_handler->get($subform_id);
		$sql = "SELECT subform.entry_id FROM ".$xoopsDB->prefix("formulize_".$subformObject->getVar('form_handle'))." as subform $curlyBracketFormFrom WHERE subform.entry_id IN (".implode(", ", $sub_entries[$subform_id]).") $conditionsFilter $conditionsFilterOOM";
		$sub_entries[$subform_id] = array();
		if($res = $xoopsDB->query($sql)) {
			while($array = $xoopsDB->fetchArray($res)) {
				$sub_entries[$subform_id][] = $array['entry_id'];
			}
		}
	}
	
	include_once XOOPS_ROOT_PATH . "/modules/formulize/include/extract.php";
	$target_sub_to_use = ($_POST['target_sub'] AND $_POST['target_sub'] == $subform_id AND $_POST['target_sub_instance'] == $subformElementId.$subformInstance) ? $_POST['target_sub'] : $subform_id; 
    list($elementq, $element_to_write, $value_to_write, $value_source, $value_source_form) = formulize_subformSave_determineElementToWrite($frid, $fid, $entry, $target_sub_to_use);

    if (0 == strlen($element_to_write)) {
        error_log("Relationship $frid for subform $subform_id on form $fid is invalid.");
        $to_return = array("c1"=>"", "c2"=>"", "sigle"=>"");
        if (is_object($xoopsUser) and in_array(XOOPS_GROUP_ADMIN, $xoopsUser->getGroups())) {
            if (0 == $frid) {
                $to_return['single'] = "This subform cannot be shown because no relationship is active.";
            } else {
                $to_return['single'] = "This subform cannot be shown because relationship $frid for subform ".
                    "$subform_id on form $fid is invalid.";
            }
        }
        return $to_return;
    }

	// check for adding of a sub entry, and handle accordingly -- added September 4 2006
	global $formulize_subformInstance;
	$subformInstance = $formulize_subformInstance+1;
    $formulize_subformInstance = $subformInstance;
    $element_handler = xoops_getmodulehandler('elements', 'formulize');
	
	if($_POST['target_sub'] AND $_POST['target_sub'] == $subform_id AND $_POST['target_sub_instance'] == $subformElementId.$subformInstance) { // important we only do this on the run through for that particular sub form (hence target_sub == sfid), and also only for the specific instance of this subform on the page too, since not all entries may apply to all subform instances any longer with conditions in effect now
        list($sub_entry_new,$sub_entry_written,$filterValues) = formulize_subformSave_writeNewEntry($element_to_write, $value_to_write, $fid, $frid, $_POST['target_sub'], $entry, $subformConditions, $overrideOwnerOfNewEntries, $mainFormOwner, $_POST['numsubents']);
        if(is_array($sub_entry_written)) {
            global $formulize_subFidsWithNewEntries, $formulize_subformElementsWithNewEntries, $formulize_newSubformEntries;
            $formulize_subFidsWithNewEntries[] = $_POST['target_sub'];
            $formulize_subformElementsWithNewEntries[] = $subform_element_object;
            $formulize_newSubformEntries[$_POST['target_sub']] = $sub_entry_written; // an array of entries that were written, since multiple subs can be created at once
			}
			}
	
    $data_handler = new formulizeDataHandler($subform_id);
	

	// need to do a number of checks here, including looking for single status on subform, and not drawing in add another if there is an entry for a single

	$sub_single_result = getSingle($subform_id, $uid, $groups, $member_handler, $gperm_handler, $mid);
	$sub_single = $sub_single_result['flag'];
	if($sub_single) {
		unset($sub_entries);
		$sub_entries[$subform_id][0] = $sub_single_result['entry'];
	}

    if(!is_array($sub_entries[$subform_id])) {
        $sub_entries[$subform_id] = array();
    }

	if($sub_entry_new AND !$sub_single AND $_POST['target_sub'] == $subform_id) {
		for($i=0;$i<$_POST['numsubents'];$i++) {
			array_unshift($sub_entries[$subform_id], $sub_entry_new);
		}
	}

	if(is_array($sub_entry_written) AND !$sub_single AND $_POST['target_sub'] == $subform_id) {
		foreach($sub_entry_written as $sew) {
			array_unshift($sub_entries[$subform_id], $sew);
		}
	}

	if(!$customCaption) {
		// get the title of this subform
		// help text removed for F4.0 RC2, this is an experiment
		$subtitle = q("SELECT desc_form FROM " . $xoopsDB->prefix("formulize_id") . " WHERE id_form = $subform_id");
        $subtitle = $subtitle[0]['desc_form'];
	} else {
        $subtitle = $customCaption;
	}
    $helpText = ($subform_element_object AND $subform_element_object->getVar('ele_desc')) ? '<p class="subform-helptext">'.html_entity_decode($subform_element_object->getVar('ele_desc'),ENT_QUOTES).'</p>' : '';
    $col_one = "<p id=\"subform-caption-f$fid-sf$subform_id\" class=\"subform-caption\"><b>" . trans($subtitle) . "</b></p>$helpText"; // <p style=\"font-weight: normal;\">" . _formulize_ADD_HELP;

	// preopulate entries, if there are no sub_entries yet, and prepop options is selected.
    // prepop will be based on the options in an element in the subform, and should also take into account the non OOM conditional filter choices where = is the operator.
    if(count($sub_entries[$subform_id]) == 0 AND $subform_element_object AND $subform_element_object->ele_value['subform_prepop_element']) {
        
        $optionElementObject = $element_handler->get($subform_element_object->ele_value['subform_prepop_element']);
        
        // gather filter choices first...
        if(!isset($filterValues)) {
            if(is_array($subformConditions)) {
    			$filterValues = getFilterValuesForEntry($subformConditions, $entry);
                $filterValues = $filterValues[key($filterValues)]; // subform element conditions are always on one form only so we just take the first set of values found (filterValues are grouped by form id)
            } else {
                $filterValues = array();
            }
        }
        // gather all the choices for the prepop element, taking into account if the filter choice for this subform instance alters the options for the prepop element
        // render the element, then read the options from the rendered element
        // this will NOT work for autocomplete boxes!
        // call displayElement, this should set the GLOBALS value that we can then check to see what options have been created for this element
        $valuesToWrite = array();
        foreach($filterValues as $elementHandle=>$value) {
            // need to set this special flag, which will cause rendered linked selectboxes to have the subform entry inclusion filters taken into account
            // ie: if this instance of the subform should only render entries where field X = 'Basic Needs', then we want to get the options that would meet that requirement
            $GLOBALS['formulize_asynchronousFormDataInDatabaseReadyFormat']['new'][$elementHandle] = $value;
            $valuesToWrite[$elementHandle] = $value;
        }
        
        // if the prepop element is a linked field that has conditions on it, we need to ensure that when it is rendered, we are taking the filter into account!!
        // SINCE THIS IS A PHANTOM NEW ENTRY THAT DOESN'T REALLY EXIST, WE CAN ABUSE THAT SITUATION TO INJECT WHATEVER VALUES WE WANT FOR WHATEVER FIELDS THAT NEED TO BE MATCHED, EVEN THOUGH THEY WON'T ACTUALLY EXIST IN THE FORM WE'RE MAKING AN ENTRY IN.
        // THIS IS RELEVANT WHEN YOU ARE SETTING THE CURLY BRACKET CONDITIONS FOR FILTERING WHAT OPTIONS WE SHOULD PAY ATTENTION TO.
        if($optionElementObject->isLinked) {
            $optionElementEleValue = $optionElementObject->getVar('ele_value');
            $optionElementFilterConditions = $optionElementEleValue[5]; // fifth key in selectboxes will be conditions for what options to include when linked. Ack!
            if(is_array($optionElementFilterConditions) AND count($optionElementFilterConditions)>1) {
                // if it's not a curly bracket value, then element name is the id, and value is the term
                // if it is a curly bracket value, then element name is the curly bracket value, and value is the value that field has in the parent entry to this one we're about to create!
                $optionElementEleValue2 = explode("#*=:*", $optionElementEleValue[2]);
                $optionSourceFid = $optionElementEleValue2[0];
                $filterElementHandles = convertElementIdsToElementHandles($optionElementFilterConditions[0], $optionSourceFid);
                $filterElementIds = $optionElementFilterConditions[0];
                $filterTerms = $optionElementFilterConditions[2];
                foreach($filterElementIds as $i=>$thisFilterElement) {
                    if(substr($filterTerms[$i],0,1) == "{" AND substr($filterTerms[$i],-1)=="}" AND !isset($filterValues[substr($filterTerms[$i],1,-1)])) {
                        // lookup value of this field in the parent entry
                        $prepop_source_data_handler = new formulizeDataHandler($fid);
                        $GLOBALS['formulize_asynchronousFormDataInDatabaseReadyFormat']['new'][substr($filterTerms[$i],1,-1)] = $prepop_source_data_handler->getElementValueInEntry($entry, substr($filterTerms[$i],1,-1));
                    } elseif(!isset($filterValues[$filterElementHandles[$i]])) {
                        $GLOBALS['formulize_asynchronousFormDataInDatabaseReadyFormat']['new'][$filterElementHandles[$i]] = $filterTerms[$i];                
                    }
                }
            }
        }
        
        list($prepopElement, $prepopDisabled) = displayElement("", $subform_element_object->ele_value['subform_prepop_element'], "new", false, null, null, false);
        unset($GLOBALS['formulize_synchronousFormDataInDatabaseReadyFormat']); // clear the special flag, just in case
        $prepopOptions = $GLOBALS['formulize_lastRenderedElementOptions'];
        // if there are known linking values to the main form, then write those in.
        // Otherwise...we need to add logic to make this work like the blanks do and write links after saving!!
        // Therefore, this feature will not yet work when the mainform entry is new!!
        if($element_to_write) {
            $linkingElementObject = $element_handler->get($element_to_write);
            $valuesToWrite[$linkingElementObject->getVar('ele_handle')] = $value_to_write;
        }
        foreach($prepopOptions as $optionKey=>$optionValue) {
            // get the database ready value for this option
            // write an entry with that value, and all applicable filterValues
            // need to also write the joining value to the main form!!
            // add that entry to the list of sub entries
            $valuesToWrite[$optionElementObject->getVar('ele_handle')] = prepDataForWrite($optionElementObject, $optionKey); // keys are what the form sends back for processing
            if($valuesToWrite[$optionElementObject->getVar('ele_handle')] !== "" AND $valuesToWrite[$optionElementObject->getVar('ele_handle')] !== "{WRITEASNULL}") {
                $proxyUser = $overrideOwnerOfNewEntries ? $mainFormOwner : false;
                if($writtenEntryId = formulize_writeEntry($valuesToWrite, 'new', 'replace', $proxyUser, true)) { // last true forces writing even when not using POST method on page request. Necessary for prepop in modal drawing.
                writeEntryDefaults($subform_id,$writtenEntryId);
                $sub_entries[$subform_id][] = $writtenEntryId;
            }
        }
        }
        // IF no main form entry is actually saved in the end, then we want to delete all these subs that we have made??!!
        // We don't have to, however, they will polute the database, it's not necessary to have them around
    }

	// list the entries, including links to them and delete checkboxes
	
	// get the headerlist for the subform and convert it into handles
	// note big assumption/restriction that we are only using the first header found (ie: only specify one header for a sub form!)
	// setup the array of elements to draw
	if(is_array($customElements)) {
		$headingDescriptions = array();
		$headerq = q("SELECT ele_caption, ele_colhead, ele_desc, ele_id FROM " . $xoopsDB->prefix("formulize") . " WHERE ele_id IN (" . implode(", ", $customElements). ") ORDER BY ele_order");
		foreach($headerq as $thisHeaderResult) {
            if($element_handler->isElementVisibleForUser($thisHeaderResult['ele_id'], $xoopsUser)) {
			$elementsToDraw[] = $thisHeaderResult['ele_id'];
			$headingDescriptions[]  = $thisHeaderResult['ele_desc'] ? $thisHeaderResult['ele_desc'] : "";
			if($captionsForHeadings) {
				$headersToDraw[] = $thisHeaderResult['ele_caption'];
			} else {
				$headersToDraw[] = $thisHeaderResult['ele_colhead'] ? $thisHeaderResult['ele_colhead'] : $thisHeaderResult['ele_caption'];
			}
		}
		}
	} else {
		$subHeaderList = getHeaderList($subform_id);
		$subHeaderList1 = getHeaderList($subform_id, true);
		if (isset($subHeaderList[0])) {
			$headersToDraw[] = trans($subHeaderList[0]);
		}
		if (isset($subHeaderList[1])) {
			$headersToDraw[] = trans($subHeaderList[1]);
		}
		if (isset($subHeaderList[2])) {
			$headersToDraw[] = trans($subHeaderList[2]);
		}
		$elementsToDraw = array_slice($subHeaderList1, 0, 3);
	}

	$need_delete = 0;
	$drawnHeadersOnce = false;
    static $drawnSubformBlankHidden = array();

    // div for View button dialog
    $col_two = "<div id='subentry-dialog' style='display:none'></div>\n";
    
    // hacking in a filter for existing entries
    if($subform_element_object AND isset($subform_element_object->ele_value["UserFilterByElement"]) AND $subform_element_object->ele_value["UserFilterByElement"]) {
        $col_two .= "<br>"._formulize_SUBFORM_FILTER_SEARCH."<input type='text' name='subformFilterBox_$subformInstance' value='".htmlspecialchars(strip_tags(str_replace("'","&#039;",$_POST['subformFilterBox_'.$subformInstance])))."' /> <input type='button' value='"._formulize_SUBFORM_FILTER_GO."' onclick='validateAndSubmit();' /><br>";
	} else {
		$col_two .= "";
    }
    
	if($rowsOrForms=="row" OR $rowsOrForms =='') {
		$col_two .= "<table id=\"formulize-subform-table-$subform_id\" class=\"formulize-subform-table\">";
	} else {
		$col_two .= "";
		if(!strstr($_SERVER['PHP_SELF'], "formulize/printview.php")) {
			$col_two .= "<div id=\"subform-$subformElementId$subformInstance\" class=\"subform-accordion-container\" subelementid=\"$subformElementId$subformInstance\" style=\"display: none;\">";
		}
		$col_two .= "<input type='hidden' name='subform_entry_".$subformElementId.$subformInstance."_active' id='subform_entry_".$subformElementId.$subformInstance."_active' value='' />";
	}

	$deFrid = $frid ? $frid : ""; // need to set this up so we can pass it as part of the displayElement function, necessary to establish the framework in case this is a framework and no subform element is being used, just the default draw-in-the-one-to-many behaviour
	
	// if there's been no form submission, and there's no sub_entries, and there are default blanks to show, then do everything differently -- sept 8 2007
	
    // check if there is a ! flag on the $defaultblanks value
    // if so, we always show blanks as long as there are no subform entries already
    $ignoreFormSubmitted = false;
    if(substr($defaultblanks, -1) == '!') {
        $defaultblanks = intval(substr($defaultblanks, 0, -1));
        $ignoreFormSubmitted = true;
    } else {
        $defaultblanks = intval($defaultblanks);
    }
    
    $viewType = ($showViewButtons == 2 OR $showViewButtons == 3) ? 'Modal' : '';
    $viewType = stristr($_SERVER['SCRIPT_NAME'], 'subformdisplay-elementsonly.php') ? 'Modal' : $viewType;
    $addViewType = ($showViewButtons == 2) ? 'Modal' : '';
    $addViewType = stristr($_SERVER['SCRIPT_NAME'], 'subformdisplay-elementsonly.php') ? 'Modal' : $addViewType;
    
	if((!$_POST['form_submitted'] OR $ignoreFormSubmitted) AND count($sub_entries[$subform_id]) == 0 AND $defaultblanks > 0 AND ($rowsOrForms == "row"  OR $rowsOrForms =='')) {
	
        if(!isset($GLOBALS['formulize_globalDefaultBlankCounter'])) {
            $GLOBALS['formulize_globalDefaultBlankCounter'] = -1;
        }
		for($i=0;$i<$defaultblanks;$i++) {
            
            $GLOBALS['formulize_globalDefaultBlankCounter'] = $GLOBALS['formulize_globalDefaultBlankCounter'] + 1;
	
				// nearly same header drawing code as in the 'else' for drawing regular entries
				if(!$drawnHeadersOnce) {
					$col_two .= "<tr><td>\n";
                    if(!isset($drawnSubformBlankHidden[$subform_id])) {
                        $col_two .= "<input type=\"hidden\" name=\"formulize_subformValueSource_$subform_id\" value=\"$value_source\">\n";
                        $col_two .= "<input type=\"hidden\" name=\"formulize_subformValueSourceForm_$subform_id\" value=\"$value_source_form\">\n";
                        $col_two .= "<input type=\"hidden\" name=\"formulize_subformValueSourceEntry_$subform_id"."[]\" value=\"$entry\">\n";
                        $col_two .= "<input type=\"hidden\" name=\"formulize_subformElementToWrite_$subform_id\" value=\"$element_to_write\">\n";
                        $col_two .= "<input type=\"hidden\" name=\"formulize_subformSourceType_$subform_id\" value=\"".$elementq[0]['fl_common_value']."\">\n";
                        $col_two .= "<input type=\"hidden\" name=\"formulize_subformId_$subform_id\" value=\"$subform_id\">\n"; // this is probably redundant now that we're tracking sfid in the names of the other elements
                        $drawnSubformBlankHidden[$subform_id] = true;
                    }
					$col_two .= "</td>\n";
					foreach($headersToDraw as $x=>$thishead) {
						if($thishead) {
							$headerHelpLinkPart1 = $headingDescriptions[$i] ? "<a href=\"#\" onclick=\"return false;\" alt=\"".strip_tags(htmlspecialchars($headingDescriptions[$x]))."\" title=\"".strip_tags(htmlspecialchars($headingDescriptions[$x]))."\">" : "";
							$headerHelpLinkPart2 = $headerHelpLinkPart1 ? "</a>" : "";
							$col_two .= "<th><p>$headerHelpLinkPart1<b>$thishead</b>$headerHelpLinkPart2</p></th>\n";
						}
					}
					$col_two .= "</tr>\n";
					$drawnHeadersOnce = true;
				}
				$col_two .= "<tr>\n<td>";
				$col_two .= "</td>\n";
				include_once XOOPS_ROOT_PATH . "/modules/formulize/include/elementdisplay.php";
				foreach($elementsToDraw as $thisele) {
					if($thisele) { 
                        $unsetDisabledFlag = false;
                        if($subform_element_object AND in_array($thisele, explode(',',$subform_element_object->ele_value['disabledelements']))) {
                            $unsetDisabledFlag = !isset($GLOBALS['formulize_forceElementsDisabled']);
                            $GLOBALS['formulize_forceElementsDisabled'] = true;
                        }
						ob_start();
						// critical that we *don't* ask for displayElement to return the element object, since this way the validation logic is passed back through the global space also (ugh).  Otherwise, no validation logic possible for subforms.
						$renderResult = displayElement($deFrid, $thisele, "subformCreateEntry_".$GLOBALS['formulize_globalDefaultBlankCounter']."_".$subformElementId); 
						$col_two_temp = ob_get_contents();
						ob_end_clean();
                        if($unsetDisabledFlag) { unset($GLOBALS['formulize_forceElementsDisabled']); }
						if($col_two_temp OR $renderResult == "rendered" OR $renderResult == "rendered-disabled") { // only draw in a cell if there actually is an element rendered (some elements might be rendered as nothing (such as derived values)
							$col_two .= "<td class='formulize_subform_".$thisele."'>$col_two_temp</td>\n";
						} else {
							$col_two .= "<td>******</td>";
						}
					}
				}
				$col_two .= "</tr>\n";
				
		}
	
	} elseif(count($sub_entries[$subform_id]) > 0) {
		
        if(intval($subform_element_object->ele_value["addButtonLimit"]) AND count($sub_entries[$subform_id]) >= intval($subform_element_object->ele_value["addButtonLimit"])) {
            $hideaddentries = 'hideaddentries';
        }
        
        $sortClause = " sub.entry_id ";
        $joinClause = "";
        if(isset($subform_element_object->ele_value["SortingElement"]) AND $subform_element_object->ele_value["SortingElement"]) {
            $sortElementObject = $element_handler->get($subform_element_object->ele_value["SortingElement"]);
            $sortDirection = $subform_element_object->ele_value["SortingDirection"] == "DESC" ? "DESC" : "ASC";
            $sortTablePrefix = $sortElementObject->isLinked ? 'source' : 'sub';
            // if linked, go join to the source element
            if($sortTablePrefix == 'source') {
                $sortEleValue = $sortElementObject->getVar('ele_value');
                $sortEleValue2Parts = explode("#*=:*", $sortEleValue[2]);
                $sourceFid = $sortEleValue2Parts[0];
                $sourceHandle = $sortEleValue2Parts[1];
                $sourceFormObject = $form_handler->get($sourceFid);
                $joinClause = " LEFT JOIN ".$xoopsDB->prefix("formulize_".$sourceFormObject->getVar('form_handle'))." as source ON sub.`".$sortElementObject->getVar('ele_handle')."` = source.entry_id ";
                $sortClause = " source.`$sourceHandle` ".$sortDirection;
            } else {
                $sortClause = " $sortTablePrefix.`".$sortElementObject->getVar('ele_handle')."` ".$sortDirection;
            }
        } 
		
        if(isset($subform_element_object->ele_value["UserFilterByElement"]) AND $subform_element_object->ele_value["UserFilterByElement"]) {
            $matchingEntryIds = array();
            if(isset($_POST['subformFilterBox_'.$subformInstance]) AND $_POST['subformFilterBox_'.$subformInstance]) {
                $filterElementObject = $element_handler->get($subform_element_object->ele_value["UserFilterByElement"]);
                $matchingEntries = getData('',$subform_id, $filterElementObject->getVar('ele_handle').'/**/'.htmlspecialchars(strip_tags(trim($_POST['subformFilterBox_'.$subformInstance])), ENT_QUOTES));
                foreach($matchingEntries as $matchingEntry) {
                    $matchingEntryIds = array_merge($matchingEntryIds, internalRecordIds($matchingEntry, $subform_id));
                }
                $filterClause = " AND sub.entry_id IN (".implode(",", $matchingEntryIds).")";
            } else {
                $filterClause = " AND false ";
            }
        } else {
            $filterClause = "";
        }
        
		$sformObject = $form_handler->get($subform_id);
		$subEntriesOrderSQL = "SELECT sub.entry_id FROM ".$xoopsDB->prefix("formulize_".$sformObject->getVar('form_handle'))." as sub $joinClause WHERE sub.entry_id IN (".implode(",", $sub_entries[$subform_id]).") $filterClause ORDER BY $sortClause";
		if($subEntriesOrderRes = $xoopsDB->query($subEntriesOrderSQL)) {
			$sub_entries[$subform_id] = array();
			while($subEntriesOrderArray = $xoopsDB->fetchArray($subEntriesOrderRes)) {
				$sub_entries[$subform_id][] = $subEntriesOrderArray['entry_id'];
			}
		}

		$currentSubformInstance = $subformInstance;

		foreach($sub_entries[$subform_id] as $sub_ent) {
            
            // validate that the sub entry has a value for the key field that it needs to (in cases where there is a sub linked to a main and a another sub (ie: it's a sub sub of a sub, and a sub of the main, at the same time, we don't want to draw in entries in the wrong place -- they will be part of the sub_entries array, because they are part of the dataset, but they should not be part of the UI for this subform instance!)
            // $element_to_write is the element in the subform that needs to have a value
            if($element_to_write AND !$subFormKeyElementValue = $data_handler->getElementValueInEntry($sub_ent, $element_to_write)) {
                continue;
            }   
            
			if($sub_ent != "") {
				
				if($rowsOrForms=='row' OR $rowsOrForms =='') {
					
					if(!$drawnHeadersOnce) {
						$col_two .= "<tr><th class='subentry-delete-cell'></th>\n";
                        if(!$renderingSubformUIInModal AND $showViewButtons AND !strstr($_SERVER['PHP_SELF'], "formulize/printview.php")) { $col_two .= "<th class='subentry-view-cell'></th>\n"; }
						foreach($headersToDraw as $i=>$thishead) {
							if($thishead) {
								$headerHelpLinkPart1 = $headingDescriptions[$i] ? "<a href=\"#\" onclick=\"return false;\" alt=\"".$headingDescriptions[$i]."\" title=\"".$headingDescriptions[$i]."\">" : "";
								$headerHelpLinkPart2 = $headerHelpLinkPart1 ? "</a>" : "";
								$col_two .= "<th><p>$headerHelpLinkPart1<b>$thishead</b>$headerHelpLinkPart2</p></th>\n";
							}
						}
						$col_two .= "</tr>\n";
						$drawnHeadersOnce = true;
					}
                    $subElementId = is_object($subform_element_object) ? $subform_element_object->getVar('ele_id') : '';
					$col_two .= "<tr class='row-".$sub_ent."-".$subElementId."'>\n<td class='subentry-delete-cell'>";
					// check to see if we draw a delete box or not
					if ($sub_ent !== "new" and ("hideaddentries" != $hideaddentries)
						and formulizePermHandler::user_can_delete_entry($subform_id, $uid, $sub_ent) AND !strstr($_SERVER['PHP_SELF'], "formulize/printview.php"))
					{
						// note: if the add/delete entry buttons are hidden, then these delete checkboxes are hidden as well
						$need_delete = 1;
						$col_two .= "<input type=checkbox class='delbox' name=delbox$sub_ent value=$sub_ent></input>";
					}
					$col_two .= "</td>\n";
                    $modalParams = $viewType == 'Modal' ? "'$frid', '$fid', '$entry', " : "";
                    if(!$renderingSubformUIInModal AND $showViewButtons AND !strstr($_SERVER['PHP_SELF'], "formulize/printview.php")) { $col_two .= "<td class='subentry-view-cell'><a href='' class='loe-edit-entry' id='view".$sub_ent."' onclick=\"javascript:goSub".$viewType."('$sub_ent', '$subform_id', $modalParams".$subform_element_object->getVar('ele_id').",0);return false;\">&nbsp;</a></td>\n"; }
					include_once XOOPS_ROOT_PATH . "/modules/formulize/include/elementdisplay.php";
					foreach($elementsToDraw as $thisele) {
						if($thisele) { 
                            $unsetDisabledFlag = false;
                            if(in_array($thisele, explode(',',$subform_element_object->ele_value['disabledelements']))) {
                                $unsetDisabledFlag = !isset($GLOBALS['formulize_forceElementsDisabled']);
                                $GLOBALS['formulize_forceElementsDisabled'] = true;
                            }
							ob_start();
							// critical that we *don't* ask for displayElement to return the element object, since this way the validation logic is passed back through the global space also (ugh).  Otherwise, no validation logic possible for subforms.
							$renderResult = displayElement($deFrid, $thisele, $sub_ent); 
							$col_two_temp = trim(ob_get_contents());
							ob_end_clean();
                            if($unsetDisabledFlag) { unset($GLOBALS['formulize_forceElementsDisabled']); }
							if($col_two_temp OR $renderResult == "rendered" OR $renderResult == "rendered-disabled") { // only draw in a cell if there actually is an element rendered (some elements might be rendered as nothing (such as derived values)
                                $textAlign = "";
                                if(is_numeric($col_two_temp)) {
                                    $col_two_temp = formulize_numberFormat($col_two_temp, $thisele);
                                    $textAlign = " right-align-text";
                                }
								$col_two .= "<td class='formulize_subform_".$thisele."$textAlign'>$col_two_temp</td>\n";
							} else {
								$col_two .= "<td>******</td>";
							}
						}
					}
					$col_two .= "</tr>\n";
				} else { // display the full form
					$headerValues = array();
					foreach($elementsToDraw as $thisele) {
						$value = $data_handler->getElementValueInEntry($sub_ent, $thisele);
						$element_object = _getElementObject($thisele);
						$value = prepvalues($value, $element_object->getVar("ele_handle"), $sub_ent);
						if (is_array($value))
							$value = implode(" - ", $value); // may be an array if the element allows multiple selections (checkboxes, multiselect list boxes, etc)
						$headerValues[] = $value;
					}
					$headerToWrite = implode(" &mdash; ", $headerValues);
					if(str_replace(" &mdash; ", "", $headerToWrite) == "") {
						$headerToWrite = _AM_ELE_SUBFORM_NEWENTRY_LABEL;
					}
					
					// check to see if we draw a delete box or not
					$deleteBox = "";
					if ("hideaddentries" != $hideaddentries AND $sub_ent !== "new" and formulizePermHandler::user_can_delete_entry($subform_id, $uid, $sub_ent) AND !strstr($_SERVER['PHP_SELF'], "formulize/printview.php")) {
						$need_delete = 1;
						$deleteBox = "<input type=checkbox class='delbox' name=delbox$sub_ent value=$sub_ent></input>&nbsp;&nbsp;";
					}
					
					if(!strstr($_SERVER['PHP_SELF'], "formulize/printview.php")) {
						$col_two .= "<div class=\"subform-deletebox\">$deleteBox</div><div class=\"subform-entry-container\" id=\"subform-".$subform_id."-"."$sub_ent\">
	<p class=\"subform-header\"><a href=\"#\"><span class=\"accordion-name\">".$headerToWrite."</span></a></p>
	<div class=\"accordion-content content\">";
					}
					ob_start();
					$GLOBALS['formulize_inlineSubformFrid'] = $frid;
                    if ($display_screen = get_display_screen_for_subform($subform_element_object)) {
                        $subScreen_handler = xoops_getmodulehandler('formScreen', 'formulize');
                        $subScreenObject = $subScreen_handler->get($display_screen);
                        $subScreen_handler->render($subScreenObject, $sub_ent, null, true);
                    } else {
                        $renderResult = displayForm($subform_id, $sub_ent, "", "",  "", "", "formElementsOnly");
                    }
					if(!$nestedSubform) {
						unset($GLOBALS['formulize_inlineSubformFrid']);
					}
					$col_two_temp = ob_get_contents();
					ob_end_clean();
					$col_two .= $col_two_temp;
					if(!strstr($_SERVER['PHP_SELF'], "formulize/printview.php")) { 
						$col_two .= "</div>\n</div>\n";
					}
				}
			}
		}

		$subformInstance = $currentSubformInstance; // instance counter might have changed because the form could include other subforms
	}

	if($rowsOrForms=='row' OR $rowsOrForms =='') {
		// complete the table if we're drawing rows
		$col_two .= "</table>";	
	} else {
		if(!strstr($_SERVER['PHP_SELF'], "formulize/printview.php")) {
			$col_two .= "</div>"; // close of the subform-accordion-container
		}
		$col_two .= "\n
<script type=\"text/javascript\">
	jQuery(document).ready(function() {
		jQuery(\"#subform-$subformElementId$subformInstance\").accordion({
            heightStyle: 'content', 
            autoHeight: false, // legacy
			collapsible: true, // sections can be collapsed
			active: ";
			if($_POST['target_sub_instance'] == $subformElementId.$subformInstance AND $_POST['target_sub'] == $subform_id) {
				$col_two .= count($sub_entries[$subform_id])-$_POST['numsubents'];
			} elseif(is_numeric($_POST['subform_entry_'.$subformElementId.$subformInstance.'_active'])) {
				$col_two .= $_POST['subform_entry_'.$subformElementId.$subformInstance.'_active'];
			} else {
				$col_two .= 'false';
			}
			$col_two .= ",
			header: \"> div > p.subform-header\"
		});
		jQuery(\"#subform-$subformElementId$subformInstance\").fadeIn();
	});
</script>";
	} // end of if we're closing the subform inferface where entries are supposed to be collapsable forms

    $deleteButton = "";
	if(((count($sub_entries[$subform_id])>0 AND $sub_entries[$subform_id][0] != "") OR $sub_entry_new OR is_array($sub_entry_written)) AND $need_delete) {
        $deleteButton = "&nbsp;&nbsp;&nbsp;<input type=button name=deletesubs value='" . _formulize_DELETE_CHECKED . "' onclick=\"javascript:sub_del($subform_id, $sub_ent, '$viewType', ".intval($_GET['subformElementId']).", '$fid', '$entry');\">";
        // for now, add clone button when delete button is available...sketchy though, we should make this more controlled
        $deleteButton .= "&nbsp;&nbsp;&nbsp;<input type=button name=clonesubs value='" . _formulize_CLONE_CHECKED . "' onclick=\"javascript:sub_clone($subform_id, $sub_ent, '$viewType', ".intval($_GET['subformElementId']).", '$fid', '$entry');\">";
	}

    // if the 'add x entries button' should be hidden or visible
    if ("hideaddentries" != $hideaddentries) {
        $allowed_to_add_entries = false;
        if ("subform" == $hideaddentries OR 1 == $hideaddentries) {
            // for compatability, accept '1' which is the old value which corresponds to the new use-subform-permissions (saved as "subform")
            // user can add entries if they have permission on the sub form
            $allowed_to_add_entries = $gperm_handler->checkRight("add_own_entry", $subform_id, $groups, $mid);
        } else {
            // user can add entries if they have permission on the main form
            // the user should only be able to add subform entries if they can *edit* the main form entry, since adding a subform entry
            //  is like editing the main form entry. otherwise they could add subform entries on main form entries owned by other users
            $allowed_to_add_entries = formulizePermHandler::user_can_edit_entry($fid, $uid, $entry);
        }
        if ($allowed_to_add_entries AND !strstr($_SERVER['PHP_SELF'], "formulize/printview.php")) {
            if (count($sub_entries[$subform_id]) == 1 AND $sub_entries[$subform_id][0] === "" AND $sub_single) {
                $col_two .= "<div id='subform_button_controls_$subform_id$subformElementId$subformInstance' class='subform_button_controls'><input type=button name=addsub value='". _formulize_ADD_ONE . "' onclick=\"javascript:add_sub('$subform_id', 1, ".$subformElementId.$subformInstance.", '$frid', '$fid', '$entry', '$subformElementId', '$addViewType', ".intval($_GET['subformElementId']).");\"></p>";
            } elseif(!$sub_single) {
                $use_simple_add_one_button = (isset($subform_element_object->ele_value["simple_add_one_button"]) ?
                    1 == $subform_element_object->ele_value["simple_add_one_button"] : false);
                $col_two .= "<div id='subform_button_controls_$subform_id$subformElementId$subformInstance' class='subform_button_controls'><input type=button name=addsub value='".($use_simple_add_one_button ? trans($subform_element_object->ele_value['simple_add_one_button_text']) : _formulize_ADD)."' onclick=\"javascript:add_sub('$subform_id', jQuery('#addsubentries".$subform_id.$subformElementId.$subformInstance."').val(), ".$subformElementId.$subformInstance.", '$frid', '$fid', '$entry', '$subformElementId', '$addViewType', ".intval($_GET['subformElementId']).");\">";
                if ($use_simple_add_one_button) {
                    $col_two .= "<input type=\"hidden\" name=addsubentries$subform_id$subformElementId$subformInstance id=addsubentries$subform_id$subformElementId$subformInstance value=\"1\">";
                } else {
                    $col_two .= "<input type=text name=addsubentries$subform_id$subformElementId$subformInstance id=addsubentries$subform_id$subformElementId$subformInstance value=1 size=2 maxlength=2>";
                    $col_two .= $addEntriesText;
                }
                $col_two .= $deleteButton."</div>";
            }
        }
    }

    $to_return['c1'] = $col_one;
    $to_return['c2'] = $col_two;
    $to_return['single'] = $col_one . $col_two;

    if (is_object($subform_element_object)) {
        global $xoopsUser;
        $show_element_edit_link = (is_object($xoopsUser) and in_array(XOOPS_GROUP_ADMIN, $xoopsUser->getGroups()));
        $edit_link = "";
        if ($show_element_edit_link) {
            $edit_link = "<a class=\"formulize-element-edit-link\" tabindex=\"-1\" href=\"" . XOOPS_URL .
                "/modules/formulize/admin/ui.php?page=element&aid=0&ele_id=" .
                $subform_element_object->getVar("ele_id") . "\" target=\"_blank\">edit element</a>";
        }
        $to_return['single'] = "<div class=\"formulize-subform-".$subform_element_object->getVar("ele_handle")."\">$edit_link $col_one $col_two</div>";
    }

    return $to_return;
}


// add the proxy list to a form
function addOwnershipList($form, $groups, $member_handler, $gperm_handler, $fid, $mid, $entry_id="") {

	global $xoopsDB;
			
			$add_groups = $gperm_handler->getGroupIds("add_own_entry", $fid, $mid);
			// May 5, 2006 -- limit to the user's own groups unless the user has global scope
			if(!$globalscope = $gperm_handler->checkRight("view_globalscope", $fid, $groups, $mid)) {
				$add_groups = array_intersect($add_groups, $groups);
			}
			$all_add_users = array();
			foreach($add_groups as $grp) {
				$add_users = $member_handler->getUsersByGroup($grp);
				$all_add_users = array_merge((array)$add_users, $all_add_users);
				unset($add_users);
			}
		
			$unique_users = array_unique($all_add_users);
            if(in_array(0,$unique_users)) { // if there is a user 0 that has been found, that's an error, cleanup DB and remove errant user id from the array
                $cleanupSQL = "DELETE FROM ".$xoopsDB->prefix('groups_users_link')." WHERE uid=0";
                $xoopsDB->queryF($cleanupSQL);
                $unique_users = array_diff($unique_users, array(0));
            }

			$punames = array();
			foreach($unique_users as $uid) {
				$uqueryforrealnames = "SELECT name, uname FROM " . $xoopsDB->prefix("users") . " WHERE uid=$uid";
				$uresqforrealnames = $xoopsDB->query($uqueryforrealnames);
				$urowqforrealnames = $xoopsDB->fetchRow($uresqforrealnames);
				$punames[] = $urowqforrealnames[0] ? $urowqforrealnames[0] : $urowqforrealnames[1]; // use the uname if there is no full name
			}

			// alphabetize the proxy list added 11/2/04
			array_multisort($punames, $unique_users);

			if($entry_id AND $entry_id != 'new') {
                include_once XOOPS_ROOT_PATH . "/modules/formulize/class/data.php";
                $data_handler = new formulizeDataHandler($fid);
                list($creation_datetime, $mod_datetime, $creation_uid, $mod_uid) = $data_handler->getEntryMeta($entry_id);
                $entryOwner = $creation_uid;
                $member_handler = xoops_gethandler('member');
                if($ownerUserObject = $member_handler->getUser($entryOwner)) {
                    $entryOwnerName = $ownerUserObject->getVar('uname');
                } else {
                    $entryOwnerName = _FORM_ANON_USER;
                }
				$proxylist = new XoopsFormSelect(_AM_SELECT_UPDATE_OWNER, 'updateowner_'.$fid.'_'.$entry_id, 0, 1);
				$proxylist->addOption('nochange', _AM_SELECT_UPDATE_NOCHANGE.$entryOwnerName);
			} else {
				$proxylist = new XoopsFormSelect(_AM_SELECT_PROXY, 'proxyuser', 0, 5, TRUE); // made multi May 3 05
				$proxylist->addOption('noproxy', _formulize_PICKAPROXY);
			}
			
			for($i=0;$i<count($unique_users);$i++)
			{
                if($unique_users[$i]) {
                    $proxylist->addOption($unique_users[$i], $punames[$i]);
                }
			}

			if(!$entry_id) {
				$proxylist->setValue('noproxy');
			} else {
				$proxylist->setValue('nochange');
			}
			$proxylist->setClass("no-print");
			$form->addElement($proxylist);
			return $form;
}


//this function takes a formid and compiles all the elements for that form
//elements_allowed is NOT based off the display values.  It is based off of the elements that are specifically designated for the current displayForm function (used to display parts of forms at once)
// $title is the title of a grid that is being displayed
function compileElements($fid, $form, $element_handler, $prevEntry, $entry, $go_back, $parentLinks, $owner_groups, $groups, $overrideValue="", $elements_allowed="", $profileForm="", $frid="", $mid, $sub_entries, $sub_fids, $member_handler, $gperm_handler, $title, $screen=null, $printViewPages="", $printViewPageTitles="") {
	
	include_once XOOPS_ROOT_PATH.'/modules/formulize/include/elementdisplay.php';
	
	$entryForDEElements = is_numeric($entry) ? $entry : "new"; // if there is no entry, ie: a new entry, then $entry is "" so when writing the entry value into decue_ and other elements that go out to the HTML form, we need to use the keyword "new"
	
	global $xoopsDB, $xoopsUser;

    $elementsAvailableToUser = array();

	// set criteria for matching on display
	// set the basics that everything has to match
	$criteriaBase = new CriteriaCompo();
	$criteriaBase->add(new Criteria('ele_display', 1), 'OR');
	foreach($groups as $thisgroup) {
		$criteriaBase->add(new Criteria('ele_display', '%,'.$thisgroup.',%', 'LIKE'), 'OR');
	}
	if(is_array($elements_allowed) and count($elements_allowed) > 0) {
		// if we're limiting the elements, then add a criteria for that (multiple criteria are joined by AND unless you specify OR manually when adding them (as in the base above))
		$criteria = new CriteriaCompo();
		$criteria->add(new Criteria('ele_id', "(".implode(",",$elements_allowed).")", "IN"));
		$criteria->add($criteriaBase);
	} else {
		$criteria = $criteriaBase; // otherwise, just use the base
	}
	$criteria->setSort('ele_order');
	$criteria->setOrder('ASC');
	$elements =& $element_handler->getObjects($criteria,$fid,true); // true makes the keys of the returned array be the element ids
	$count = 0;
	global $gridCounter;
	$gridCounter = array();
	
	formulize_benchmark("Ready to loop elements.");

	// set the array to be used as the structure of the loop, either the passed in elements in order, or the elements as gathered from the DB
	// ignore passed in element order if there's a screen in effect, since we assume that official element order is authoritative when screens are involved
	// API should still allow arbitrary ordering, so $element_allowed can still be set manually as part of a displayForm call, and the order will be respected then
	if(!is_array($elements_allowed) OR $screen) {
		$element_order_array = $elements;
	} else {
		$element_order_array = $elements_allowed;
	}
	
	foreach($element_order_array as $thisElement) {
		if(is_numeric($thisElement)) { // if we're doing the order based on passed in element ids...
			if(isset($elements[$thisElement])) {
				$i = $elements[$thisElement]; // set the element object for this iteration of the loop
			} else {
				continue; // do not try to render elements that don't exist in the form!! (they might have been deleted from a multipage definition, or who knows what)
			}
			$this_ele_id = $thisElement; // set the element ID number
		} else { // else...we're just looping through the elements directly from the DB
			$i = $thisElement; // set the element object
			$this_ele_id = $i->getVar('ele_id'); // get the element ID number
		}
	
		// check if we're at the start of a page, when doing a printable view of all pages (only situation when printViewPageTitles and printViewPages will be present), and if we are, then put in a break for the page titles
		if($printViewPages) {
			if(!$currentPrintViewPage) {
				$currentPrintViewPage = 1;
			}
			while(!in_array($this_ele_id, $printViewPages[$currentPrintViewPage]) AND $currentPrintViewPage <= count($printViewPages)) {
				$currentPrintViewPage++;
			}
			if($this_ele_id == $printViewPages[$currentPrintViewPage][0]) {
				$form->insertBreak("<div id=\"formulize-printpreview-pagetitle\">" . $printViewPageTitles[$currentPrintViewPage] . "</div>", "head");
			}
		}
	
		// check if this element is included in a grid, and if so, skip it
		if(isset($gridCounter[$this_ele_id])) {
            unset($gridCounter[$this_ele_id]);
			continue;
		}

		$uid = is_object($xoopsUser) ? $xoopsUser->getVar('uid') : 0;
		$owner = getEntryOwner($entry, $fid);
		$ele_type = $i->getVar('ele_type');
		$ele_value = $i->getVar('ele_value');

		
		if($go_back['form']) { // if there's a parent form...
			// check here to see if we need to initialize the value of a linked selectbox when it is the key field for a subform
			// although this is setup as a loop through all found parentLinks, only the last one will be used, since ele_value[2] is overwritten each time.
			// assumption is there will only be one parent link for this form
			for($z=0;$z<count($parentLinks['source']);$z++) {					
				if($this_ele_id == $parentLinks['self'][$z]) { // this is the element
                    $goBackEntries = strstr($go_back['entry'], ',') ? explode(',',$go_back['entry']) : array($go_back['entry']);
                    $lastKey = count($goBackEntries)-1;
					$ele_value[2] = intval($goBackEntries[$lastKey]); // needs to gather the correct value off the stack
				}
			}
		} elseif($overrideValue){ // used to force a default setting in a form element, other than the normal default
			if(!is_array($overrideValue)) { //convert a string to an array so that strings don't screw up logic below (which is designed for arrays)
				$temp = $overrideValue;
				unset($overrideValue);
				$overrideValue[0] = $temp;
			}
			// currently only operative for select boxes
			switch($ele_type) {
				case "select":
					foreach($overrideValue as $ov) {
						if(array_key_exists($ov, $ele_value[2])) {
							$ele_value[2][$ov] = 1;
						}	
					}
					break;
				case "date":
                	// debug
                	//var_dump($overrideValue);
					foreach($overrideValue as $ov) {
						//if(ereg ("([0-9]{4})-([0-9]{2})-([0-9]{2})", $ov, $regs)) {
						if(ereg ("([0-9]{4})-([0-9]{1,2})-([0-9]{1,2})", $ov, $regs)) {
							$ele_value[0] = $ov;
						}
					}
					break;
			}
		}

		if($ele_type != "subform" AND $ele_type != 'grid') { 
			// "" is framework, ie: not applicable
			// $i is element object
			// $entry is entry_id
			// false is "nosave" param...only used to force element to not be picked up by readelements.php after saving
			// $screen is the screen object
			// false means don't print it out to screen, return it here
			$GLOBALS['formulize_sub_fids'] = $sub_fids; // set here so we can pick it up in the render method of elements, if necessary (only necessary for subforms?);
			$deReturnValue = displayElement("", $i, $entry, false, $screen, $prevEntry, false, $profileForm, $groups);
			if(is_array($deReturnValue)) {
				$form_ele = $deReturnValue[0];
				$isDisabled = $deReturnValue[1];
			} else {
				$form_ele = $deReturnValue;
				$isDisabled = false;
			}
            $elementsAvailableToUser[$this_ele_id] = true;
			if(($form_ele == "not_allowed" OR $form_ele == "hidden")) {
				if(isset($GLOBALS['formulize_renderedElementHasConditions']["de_".$fid."_".$entryForDEElements."_".$this_ele_id])) {
					// need to add a flag element to the form, so that when rendered, we'll get a hidden container for the element, in case it is going to appear asynchronously later
					$rowFlag = "{STARTHIDDEN}<<||>>de_".$fid."_".$entryForDEElements."_".$this_ele_id;
					// need to also get the validation code for this element, wrap it in a check for the table row being visible, and assign that to the global array that contains all the validation javascript that we need to add to the form
					// following code follows the pattern set in elementdisplay.php for actually creating rendered element objects
					if($ele_type != "ib") {
						$conditionalValidationRenderer = new formulizeElementRenderer($i);
						if($prevEntry OR $profileForm === "new") {
							$data_handler = new formulizeDataHandler($i->getVar('id_form'));
							$ele_value = loadValue($prevEntry, $i, $ele_value, $data_handler->getEntryOwnerGroups($entry), $groups, $entry, $profileForm); // get the value of this element for this entry as stored in the DB -- and unset any defaults if we are looking at an existing entry
						}
						$conditionalElementForValidiationCode = $conditionalValidationRenderer->constructElement("de_".$fid."_".$entryForDEElements."_".$this_ele_id, $ele_value, $entry, $isDisabled, $screen, true); // last flag is "validation only" so the rendered knows things won't actually be output
                        global $output_datepicker_defaults, $output_timeelement_js;
                        if($output_datepicker_defaults == "de_".$fid."_".$entryForDEElements."_".$this_ele_id) {
                            $output_datepicker_defaults = '';
                        }
                        if($output_timeelement_js == "de_".$fid."_".$entryForDEElements."_".$this_ele_id) {
                            $output_timeelement_js = '';
                        }
						if($js = $conditionalElementForValidiationCode->renderValidationJS()) {
							$GLOBALS['formulize_renderedElementsValidationJS'][$GLOBALS['formulize_thisRendering']][$conditionalElementForValidiationCode->getName()] = "if(window.document.getElementById('formulize-".$conditionalElementForValidiationCode->getName()."').style.display != 'none') {\n".$js."\n}\n";
						}
						unset($conditionalElementForValidiationCode);
						unset($conditionalValidationRenderer);
					}
					$form->addElement($rowFlag);
                    // since it was treated as a conditional element, and the user might interact with it, then we don't consider it a not-available-to-user element
                    unset($elementsAvailableToUser[$this_ele_id]);
				}
				continue;
			}
		}
		
		$req = !$isDisabled ? intval($i->getVar('ele_req')) : 0; 
		$GLOBALS['sub_entries'] = $sub_entries;
		if($ele_type == "subform" ) {
			$thissfid = $ele_value[0];
			if(!$thissfid) { continue; } // can't display non-specified subforms!
			$deReturnValue = displayElement("", $i, $entry, false, $screen, $prevEntry, false, $profileForm, $groups); // do this just to evaluate any conditions...it won't actually render anything, but will return "" for the first key in the array, if the element is allowed
			if(is_array($deReturnValue)) {
				$form_ele = $deReturnValue[0];
				$isDisabled = $deReturnValue[1];
			} else {
				$form_ele = $deReturnValue;
				$isDisabled = false;
			}
			if($passed = security_check($thissfid) AND $form_ele == "") {
				$GLOBALS['sfidsDrawn'][] = $thissfid;
				$customCaption = $i->getVar('ele_caption');
				$customElements = $ele_value[1] ? explode(",", $ele_value[1]) : "";
				if(isset($GLOBALS['formulize_inlineSubformFrid'])) {
					$newLinkResults = checkForLinks($GLOBALS['formulize_inlineSubformFrid'], array($fid), $fid, array($fid=>array($entry)), true); // final true means only include entries from unified display linkages
					$sub_entries = $newLinkResults['sub_entries'];
				}
                // 2 is the number of default blanks, 3 is whether to show the view button or not, 4 is whether to use captions as headings or not, 5 is override owner of entry, $owner is mainform entry owner, 6 is hide the add button, 7 is the conditions settings for the subform element, 8 is the setting for showing just a row or the full form, 9 is text for the add entries button
                $subUICols = drawSubLinks($thissfid, $sub_entries, $uid, $groups, $frid, $mid, $fid, $entry, $customCaption, $customElements, $ele_value[2], $ele_value[3], $ele_value[4], $ele_value[5], $owner, $ele_value[6], $ele_value[7], $this_ele_id, $ele_value[8], $ele_value[9], $i);
				if(isset($subUICols['single'])) {
					$form->insertBreakFormulize($subUICols['single'], "even");
				} else {
					$subLinkUI = new XoopsFormLabel($subUICols['c1'], $subUICols['c2']);
					$form->addElement($subLinkUI);
				}
				unset($subLinkUI);
			}
		} elseif($ele_type == "grid") {

			// we are going to have to store some kind of flag/counter with the id number of the starting element in the table, and the number of times we need to ignore things
			// we need to then listen for this up above and skip those elements as they come up.  This is why grids must come before their elements in the form definition

			include_once XOOPS_ROOT_PATH . "/modules/formulize/include/griddisplay.php";
			list($grid_title, $grid_row_caps, $grid_col_caps, $grid_background, $grid_start, $grid_count) = compileGrid($ele_value, $title, $i);
			$headingAtSide = ($ele_value[5] AND $grid_title) ? true : false; // if there is a value for ele_value[5], then the heading should be at the side, otherwise, grid spans form width as it's own chunk of HTML
			$gridContents = displayGrid($fid, $entry, $grid_row_caps, $grid_col_caps, $grid_title, $grid_background, $grid_start, "", "", true, $screen, $headingAtSide);
			if($headingAtSide) { // grid contents is the two bits for the xoopsformlabel when heading is at side, otherwise, it's just the contents for the break
				$gridElement = new XoopsFormLabel($gridContents[0], $gridContents[1]);
                $helpText = $i->getVar('ele_desc');
                if(trim($helpText)) {
                    $gridElement->setDescription($helpText);
                }
                $form->addElement($gridElement);
                unset($gridElement); // because addElement received values by reference, we need to destroy it here, so if it is recreated in a subsequent iteration, we don't end up overwriting elements we've already assigned. Ack! Ugly!
			} else {
				$form->insertBreakFormulize($gridContents, "head"); // head is the css class of the cell
			}
		} elseif($ele_type == "ib" OR is_array($form_ele)) {
			// if it's a break, handle it differently...$form_ele may be an array if it's a non-interactive element such as a grid
				// final param is used as id name in the table row where this element exists, so we can interact with it for showing and hiding
			$form->insertBreakFormulize("<div class=\"formulize-text-for-display\">" . trans(stripslashes($form_ele[0])) . "</div>", $form_ele[1], 'de_'.$fid.'_'.$entryForDEElements.'_'.$this_ele_id, $i->getVar("ele_handle"));
		} else {
			$form->addElement($form_ele, $req);
		}
		$count++;
		unset($hidden);
		unset($form_ele); // apparently necessary for compatibility with PHP 4.4.0 -- suggested by retspoox, sept 25, 2005
	}

	formulize_benchmark("Done looping elements.");

    // find any hidden elements in the form, that aren't available to the user in this rendering of the form...	
	unset($criteria);
	$notAllowedCriteria = new CriteriaCompo();
	$notAllowedCriteria->add(new Criteria('ele_forcehidden', 1));
    foreach($elementsAvailableToUser as $availElementId=>$boolean) {
        $notAllowedCriteria->add(new Criteria('ele_id', $availElementId, '!='));
    }
	$notAllowedCriteria->setSort('ele_order');
	$notAllowedCriteria->setOrder('ASC');
	$notAllowedElements =& $element_handler->getObjects($notAllowedCriteria,$fid);

	$hiddenElements = generateHiddenElements($notAllowedElements, $entryForDEElements, $screen); // in functions.php, keys in returned array will be the element ids
  
	foreach($hiddenElements as $element_id=>$thisHiddenElement) {
		$form->addElement(new xoopsFormHidden("decue_".$fid."_".$entryForDEElements."_".$element_id, 1));
		if(is_array($thisHiddenElement)) { // could happen for checkboxes
			foreach($thisHiddenElement as $thisIndividualHiddenElement) {
				$form->addElement($thisIndividualHiddenElement);
                unset($thisIndividualHiddenElement);
			}
		} else {
			$form->addElement($thisHiddenElement);
		}
		unset($thisHiddenElement); // some odd reference thing going on here...$thisHiddenElement is being added by reference or something like that, so that when $thisHiddenElement changes in the next run through, every previous element that was created by adding it is updated to point to the next element.  So if you unset at the end of the loop, it forces each element to be added as you would expect.
	}

    
	if($entry AND !is_a($form, 'formulize_elementsOnlyForm')) {
        // two hidden fields encode the main entry id, the first difficult-to-use format is a legacy thing
        // the 'lastentry' format is more sensible, but is only available when there was a real entry, not 'new' (also a legacy convention)
		$form->addElement (new XoopsFormHidden ('entry'.$fid, $entry));
        if(is_numeric($entry)) {
            $form->addElement (new XoopsFormHidden ('lastentry', $entry));
        }
	}
	if($_POST['parent_form']) { // if we just came back from a parent form, then if they click save, we DO NOT want an override condition, even though we are now technically editing an entry that was previously saved when we went to the subform in the first place.  So the override logic looks for this hidden value as an exception.
		$form->addElement (new XoopsFormHidden ('back_from_sub', 1));
	}
	
    
	// add a hidden element to carry all the validation javascript that might be associated with elements rendered with elementdisplay.php...only relevant for elements rendered inside subforms or grids...the validation code comes straight from the element, doesn't have a check around it for the conditional table row id, like the custom form classes at the top of the file use, since those elements won't render as hidden and show/hide in the same way
	if(isset($GLOBALS['formulize_renderedElementsValidationJS'][$GLOBALS['formulize_thisRendering']])) {
		$formulizeHiddenValidation = new XoopsFormHidden('validation', 1);
        global $fullJsCatalogue;
		foreach($GLOBALS['formulize_renderedElementsValidationJS'][$GLOBALS['formulize_thisRendering']] as $thisValidation) { // grab all the validation code we stored in the elementdisplay.php file and attach it to this element
            $catalogueKey = md5(trim($thisValidation));
            if(!isset($fullJsCatalogue[$catalogueKey])) {
                if(count($GLOBALS['formulize_renderedElementsValidationJS'][$GLOBALS['formulize_thisRendering']])> 1) {
                    $fullJsCatalogue[$catalogueKey] = true; // add this to the catalogue of stuff we've handled the validation js for, but only if there is more than one element in this set. If there is only one element, then logging it here and now will prevent it from being handled later. Multiple elements will have their validation codes merged into the single 'validation' element that we're adding at this time, so we log their individual code into the catalogue so we don't mistakenly render multiple copies of the code. But when the single element and this validation element will be identical in their code, then we must not catalogue it until later when it is actually being rendered.
                }
			foreach(explode("\n", $thisValidation) as $thisValidationLine) {
				$formulizeHiddenValidation->customValidationCode[] = $thisValidationLine;
			}
		}
		}
		$form->addElement($formulizeHiddenValidation);
	}

	if(get_class($form) == "formulize_elementsOnlyForm") { // forms of this class are ones that we're rendering just the HTML for the elements, and we need to preserve any validation javascript to stick in the final, parent form when it's finished
		$validationJS = $form->renderValidationJS();
		if(trim($validationJS)!="") {
			$GLOBALS['formulize_elementsOnlyForm_validationCode'][] = $validationJS."\n\n";
		}
	} elseif(isset($GLOBALS['formulize_elementsOnlyForm_validationCode']) AND count($GLOBALS['formulize_elementsOnlyForm_validationCode']) > 0) {
		$elementsonlyvalidation = new XoopsFormHidden('elementsonlyforms', 1);
		$elementsonlyvalidation->customValidationCode = $GLOBALS['formulize_elementsOnlyForm_validationCode'];
		$form->addElement($elementsonlyvalidation);
	}
	
	return $form;

}

// $groups is deprecated and not used in this function any longer
// $owner_groups is used when dealing with a usernames or fullnames selectbox
// $element is the element object representing the element we're loading the previously saved value for
function loadValue($prevEntry, $element, $ele_value, $owner_groups, $groups, $entry_id, $profileForm="") {

	global $myts;
	/*
	 * Hack by F�lix <INBOX Solutions> for sedonde
	 * myts == NULL
	 */
	if(!$myts){
		$myts =& MyTextSanitizer::getInstance();
	}
	/*
	 * Hack by F�lix <INBOX Solutions> for sedonde
	 * myts == NULL
	 */
			$type = $element->getVar('ele_type');
			// going direct from the DB since if multi-language is active, getVar will translate the caption
			//$caption = $element->getVar('ele_caption');
			$ele_id = $element->getVar('ele_id');

			// if we're handling a new profile form, check to see if the user has filled in the form already and use that value if necessary
			// This logic could be of general use in handling posted requests, except for it's inability to handle 'other' boxes.  An update may pay off in terms of speed of reloading the page.
			$value = "";
			if($profileForm === "new") {
				$dataFromUser = "";
				foreach($_POST as $k=>$v) {
					if( preg_match('/de_/', $k)){
						$n = explode("_", $k);
						if($n[3] == $ele_id) { // found the element in $_POST;
							$dataFromUser = prepDataForWrite($element, $v);
							break;
						}
					}
				}
				if($dataFromUser) {
					$value = $dataFromUser;
				}
			}

			// no value detected in form submission of this element...
			if(!$value) {
     				$handle = $element->getVar('ele_handle');
						$key = "";
	     			$keysFound = array_keys($prevEntry['handles'], $handle);
						foreach($keysFound as $thisKeyFound) {
							if("xyz".$prevEntry['handles'][$thisKeyFound] == "xyz".$handle) { // do a comparison with a prefixed string to avoid problems comparing numbers to numbers plus text, ie: "1669" and "1669_copy" since the loose typing in PHP will not interpret those as intended
								$key = $thisKeyFound;
								break;
							}
						}
     				// if the handle was not found in the existing values for this entry, then return the ele_value, unless we're looking at an existing entry, and then we need to clear defaults first
                // unless we're supposed to use the defaults when the element is blank
     				if(!is_numeric($key) AND $key=="") { 
                    if($entry_id AND $element->getVar('ele_use_default_when_blank') == false) {
                        // clear defaults if applicable/necessary...
     						switch($type) {
     							case "text":
     								$ele_value[2] = "";
     								break;
	     						case "textarea":
     								$ele_value[0] = "";
     								break;
     						}
     					} 
	     				return $ele_value; 
                } else {
                    // if we're still here, not returned, and there is a saved value to grab, then grab it 
						if($key !== "") {
							$value = $prevEntry['values'][$key];
						}
						
                    // If the value is blank, and the element is required, or the element has the use-defaults-when-blank option on                    
                    // then do not load in saved value over top of ele_value, just return the default instead
						if(($element->getVar('ele_use_default_when_blank') OR $element->getVar('ele_req')) AND !$value) {
								return $ele_value;
						}
                }	
			}

			// based on element type, swap in saved value from DB over top of default value for this element
			switch ($type)
			{
				case "derived":
					$ele_value[5] = $value;	// there is not a number 5 position in ele_value for derived values...we add the value to print in this position so we don't mess up any other information that might need to be carried around
					break;


                case "text":
                    $ele_value[2] = $value;
                    $ele_value[2] = str_replace("'", "&#039;", $ele_value[2]);
                    break;


                case "textarea":
                case "colorpick":
                    $ele_value[0] = $value;
                    break;


				case "select":
				case "radio":
					// NOTE:  unique delimiter used to identify LINKED select boxes, so they can be handled differently.
					if(is_string($ele_value[2]) and strstr($ele_value[2], "#*=:*"))
                    {
                        // if we've got a linked select box, then do everything differently
						$ele_value[2] .= "#*=:*".$value; // append the selected entry ids to the form and handle info in the element definition
					}
					else
					{
						// put the array into another array (clearing all default values)
						// then we modify our place holder array and then reassign
	
						if ($type != "select")
						{
							$temparray = $ele_value;
						}
						else
						{
							$temparray = $ele_value[2];
						}

						if (is_array($temparray)) {
							$temparraykeys = array_keys($temparray);
                            $temparray = array_fill_keys($temparraykeys, 0); // actually remove the defaults!
						} else {
							$temparraykeys = array();
						}
                        
						if($temparraykeys[0] === "{FULLNAMES}" OR $temparraykeys[0] === "{USERNAMES}") { // ADDED June 18 2005 to handle pulling in usernames for the user's group(s)
							$ele_value[2]['{SELECTEDNAMES}'] = explode("*=+*:", $value);
							if(count($ele_value[2]['{SELECTEDNAMES}']) > 1) { array_shift($ele_value[2]['{SELECTEDNAMES}']); }
							$ele_value[2]['{OWNERGROUPS}'] = $owner_groups;
							break;
						}
	
						// need to turn the prevEntry got from the DB into something the same as what is in the form specification so defaults show up right
						// important: this is safe because $value itself is not being sent to the browser!
						// we're comparing the output of these two lines against what is stored in the form specification, which does not have HTML escaped characters, and has extra slashes.  Assumption is that lack of HTML filtering is okay since only admins and trusted users have access to form creation.  Not good, but acceptable for now.
						$value = $myts->undoHtmlSpecialChars($value);
						if(get_magic_quotes_gpc()) { $value = addslashes($value); } 
	
						$selvalarray = explode("*=+*:", $value);
						$numberOfSelectedValues = strstr($value, "*=+*:") ? count($selvalarray)-1 : 1; // if this is a multiple selection value, then count the array values, minus 1 since there will be one leading separator on the string.  Otherwise, it's a single value element so the number of selections is 1.
						
						$assignedSelectedValues = array();
						foreach($temparraykeys as $k) {
                            
                            // if there's a straight match (not a multiple selection)
							if((string)$k === (string)$value) {
								$temparray[$k] = 1;
								$assignedSelectedValues[$k] = true;
                                
                            // or if there's a match within a multiple selection array) -- TRUE is like ===, matches type and value
							} elseif( is_array($selvalarray) AND in_array((string)$k, $selvalarray, TRUE) ) {
								$temparray[$k] = 1;
								$assignedSelectedValues[$k] = true;
                                
                            // check for a match within an English translated value and assign that, otherwise set to zero
                            // assumption is that development was done first in English and then translated
                            // this safety net will not work if a system is developed first and gets saved data prior to translation in language other than English!!
							} else {
                                foreach($selvalarray as $selvalue) {
                                    if(trim(trans((string)$k, "en")) == trim(trans($selvalue,"en"))) {
                                        $temparray[$k] = 1;
                                        $assignedSelectedValues[$k] = true;
                                        continue 2; // move on to next iteration of outer loop
                                    } 
                                }
                                if($temparray[$k] != 1) {
                                    $temparray[$k] = 0;
                                }
                            }
                            
                        }
						if((!empty($value) OR $value === 0 OR $value === "0") AND count($assignedSelectedValues) < $numberOfSelectedValues) { // if we have not assigned the selected value from the db to one of the options for this element, then lets add it to the array of options, and flag it as out of range.  This is to preserve out of range values in the db that are there from earlier times when the options were different, and also to preserve values that were imported without validation on purpose
							foreach($selvalarray as $selvalue) {
								if(!isset($assignedSelectedValues[$selvalue]) AND (!empty($selvalue) OR $selvalue === 0 OR $selvalue === "0")) {
									$temparray[_formulize_OUTOFRANGE_DATA.$selvalue] = 1;
								}
							}
						}							
						if ($type == "radio" AND $entry_id != "new" AND ($value === "" OR is_null($value)) AND array_search(1, $ele_value)) { // for radio buttons, if we're looking at an entry, and we've got no value to load, but there is a default value for the radio buttons, then use that default value (it's normally impossible to unset the default value of a radio button, so we want to ensure it is used when rendering the element in these conditions)
							$ele_value = $ele_value;
						} elseif ($type != "select")
						{
							$ele_value = $temparray;
						}
						else
						{
							$ele_value[2] = $temparray;
						}
					} // end of IF we have a linked select box
					break;
				case "yn":
					if($value == 1)
					{
						$ele_value = array("_YES"=>1, "_NO"=>0);
					}
					elseif($value == 2)
					{
						$ele_value = array("_YES"=>0, "_NO"=>1);
					}
					else
					{
						$ele_value = array("_YES"=>0, "_NO"=>0);
					}
					break;
				case "date":
					if(!$value AND substr($ele_value[0],0,1) == '{' AND substr($ele_value[0],-1) == '}') {
						$value = $ele_value[0];
					}
					$ele_value[0] = $value;

					break;
				default:
					if(file_exists(XOOPS_ROOT_PATH."/modules/formulize/class/".$type."Element.php")) {
						$customTypeHandler = xoops_getmodulehandler($type."Element", 'formulize');
						return $customTypeHandler->loadValue($value, $ele_value, $element);
					} 
			} // end switch

			/*print_r($ele_value);
			print "<br>"; //debug block
			*/

			return $ele_value;
}



// THIS FUNCTION FORMATS THE DATETIME INFO FOR DISPLAY CLEANLY AT THE TOP OF THE FORM
function formulize_formatDateTime($dt) {
	// assumption is that the server timezone has been set correctly!
	// needs to figure out daylight savings time correctly...ie: is the user's timezone one that has daylight savings, and if so, if they are currently in a different dst condition than they were when the entry was created, add or subtract an hour from the seconds offset, so that the time information is displayed correctly.
	global $xoopsConfig, $xoopsUser;
	$serverTimeZone = $xoopsConfig['server_TZ'];
	$userTimeZone = $xoopsUser ? $xoopsUser->getVar('timezone_offset') : $serverTimeZone;
	$tzDiff = $userTimeZone - $serverTimeZone;
	$tzDiffSeconds = $tzDiff*3600;
	
	if($xoopsConfig['language'] == "french") {
		$return = setlocale("LC_TIME", "fr_FR.UTF8");
	}
	return _formulize_TEMP_AT . " " . strftime(dateFormatToStrftime(_MEDIUMDATESTRING), strtotime($dt)+$tzDiffSeconds); 
}


// write the settings passed to this page from the view entries page, so the view can be restored when they go back
function writeHiddenSettings($settings, $form = null, $entries = array(), $sub_entries = array(), $screen = null) {
    // only write the settings one time (might have multiple forms being rendered)
    static $formulize_settingsWritten = 0;
    if($formulize_settingsWritten) {
        return $form;
    }
    $formulize_settingsWritten = 1;
	//unpack settings
	$sort = $settings['sort'];
	$order = $settings['order'];
	$oldcols = $settings['oldcols'];
	$currentview = $settings['currentview'];
	$global_search = $settings['global_search'];
    $pubfilters = $settings['pubfilters'];
	$searches = array();
	if (!isset($settings['calhidden']) and !is_array($settings['calhidden']))
		$settings['calhidden'] = array();
	foreach($settings as $k=>$v) {
		if(substr($k, 0, 7) == "search_" AND $v != "") {
			$thiscol = substr($k, 7);
			$searches[$thiscol] = $v;
		}
	}
	//calculations:
	$calc_cols = $settings['calc_cols'];
	$calc_calcs = $settings['calc_calcs'];
	$calc_blanks = $settings['calc_blanks'];
	$calc_grouping = $settings['calc_grouping'];

	$hlist = $settings['hlist'];
	$hcalc = $settings['hcalc'];
	$lockcontrols = $settings['lockcontrols'];
	$asearch = $settings['asearch'];
	$lastloaded = $settings['lastloaded'];	

	// used for calendars...
	$calview = $settings['calview'];
	$calfrid = $settings['calfrid'];
	$calfid = $settings['calfid'];
	// plus there's the calhidden key that is handled below
	// plus there's the page number on the LOE screen that is handled below...
	// plus there's the multipage prev and current page

    $entries = is_array($entries) ? $entries : array();
    $sub_entries = is_array($sub_entries) ? $sub_entries : array();
    $allEntries = $entries + $sub_entries;
    
	// write hidden fields
	if($form) { // write as form objects and return form
		$form->addElement (new XoopsFormHidden ('sort', $sort));
		$form->addElement (new XoopsFormHidden ('order', $order));
		$form->addElement (new XoopsFormHidden ('currentview', $currentview));
		$form->addElement (new XoopsFormHidden ('oldcols', $oldcols));
		$form->addElement (new XoopsFormHidden ('global_search', $global_search));
        $form->addElement (new XoopsFormHidden ('pubfilters', implode(",",$pubfilters)));    
		foreach($searches as $key=>$search) {
			$search_key = "search_" . $key;
			$search = str_replace("'", "&#39;", $search);
			$form->addElement (new XoopsFormHidden ($search_key, stripslashes($search)));
		}
		$form->addElement (new XoopsFormHidden ('calc_cols', $calc_cols));
		$form->addElement (new XoopsFormHidden ('calc_calcs', $calc_calcs));
		$form->addElement (new XoopsFormHidden ('calc_blanks', $calc_blanks));
		$form->addElement (new XoopsFormHidden ('calc_grouping', $calc_grouping));
		$form->addElement (new XoopsFormHidden ('hlist', $hlist));
		$form->addElement (new XoopsFormHidden ('hcalc', $hcalc));
		$form->addElement (new XoopsFormHidden ('lockcontrols', $lockcontrols));
		$form->addElement (new XoopsFormHidden ('lastloaded', $lastloaded));
		$asearch = str_replace("'", "&#39;", $asearch);
		$form->addElement (new XoopsFormHidden ('asearch', stripslashes($asearch)));
		$form->addElement (new XoopsFormHidden ('calview', $calview));
		$form->addElement (new XoopsFormHidden ('calfrid', $calfrid));
		$form->addElement (new XoopsFormHidden ('calfid', $calfid));
		foreach($settings['calhidden'] as $chname=>$chvalue) {
			$form->addElement (new XoopsFormHidden ($chname, $chvalue));
		}
		$form->addElement (new XoopsFormHidden ('formulize_LOEPageStart', $_POST['formulize_LOEPageStart']));
		if(isset($settings['formulize_currentPage'])) { // drawing a multipage form...
            $currentPageToSend = $screen ? $settings['formulize_currentPage'].'-'.$screen->getVar('sid') : $settings['formulize_currentPage'];
            $prevPageToSend = $screen ? $settings['formulize_prevPage'].'-'.$settings['formulize_prevScreen'] : $settings['formulize_prevPage'];
			$form->addElement( new XoopsFormHidden ('formulize_currentPage', $currentPageToSend));
			$form->addElement( new XoopsFormHidden ('formulize_prevPage', $prevPageToSend));
			$form->addElement( new XoopsFormHidden ('formulize_doneDest', $settings['formulize_doneDest']));
			$form->addElement( new XoopsFormHidden ('formulize_buttonText', $settings['formulize_buttonText']));
		}
		if($_POST['overridescreen']) {
			$form->addElement( new XoopsFormHidden ('overridescreen', intval($_POST['overridescreen'])));
		}
		if(strlen($_POST['formulize_lockedColumns'])>0) {
			$form->addElement( new XoopsFormHidden ('formulize_lockedColumns', $_POST['formulize_lockedColumns']));
		}
        $form->addElement( new XoopsFormHidden ('formulize_originalVentry', $settings['formulize_originalVentry']));
        foreach($allEntries as $fid=>$fidEntries) {
            foreach($fidEntries as $entry_id) {
                if($entry_id) {
                    $form->addElement(new XoopsFormHidden ('form_'.$fid.'_rendered_entry[]', $entry_id));
                }
            }
        }
        if($screen) {
            $form->addElement(new XoopsFormHidden ('formulize_renderedEntryScreen', $screen->getVar('sid')));
        }
		return $form;
	} else { // write as HTML
		print "<input type=hidden name=sort value='" . $sort . "'>";
		print "<input type=hidden name=order value='" . $order . "'>";
		print "<input type=hidden name=currentview value='" . $currentview . "'>";
		print "<input type=hidden name=oldcols value='" . $oldcols . "'>";
		print "<input type=hidden name=global_search value='" . $global_search . "'>";
        print "<input type=hidden name=pubfilters value='" . implode(",",$pubfilters) . "'>";
		foreach($searches as $key=>$search) {
			$search_key = "search_" . $key;
			$search = str_replace("\"", "&quot;", $search);
			print "<input type=hidden name=$search_key value=\"" . stripslashes($search) . "\">";
		}
		print "<input type=hidden name=calc_cols value='" . $calc_cols . "'>";
		print "<input type=hidden name=calc_calcs value='" . $calc_calcs . "'>";
		print "<input type=hidden name=calc_blanks value='" . $calc_blanks . "'>";
		print "<input type=hidden name=calc_grouping value='" . $calc_grouping . "'>";
		print "<input type=hidden name=hlist value='" . $hlist . "'>";
		print "<input type=hidden name=hcalc value='" . $hcalc . "'>";
		print "<input type=hidden name=lockcontrols value='" . $lockcontrols . "'>";
		print "<input type=hidden name=lastloaded value='" . $lastloaded . "'>";
		$asearch = str_replace("\"", "&quot;", $asearch);
		print "<input type=hidden name=asearch value=\"" . stripslashes($asearch) . "\">";
		print "<input type=hidden name=calview value='" . $calview . "'>";
		print "<input type=hidden name=calfrid value='" . $calfrid . "'>";
		print "<input type=hidden name=calfid value='" . $calfid . "'>";
		foreach($settings['calhidden'] as $chname=>$chvalue) {
			print "<input type=hidden name=$chname value='" . $chvalue . "'>";
		}
		print "<input type=hidden name=formulize_LOEPageStart value='" . $_POST['formulize_LOEPageStart'] . "'>";
		if(isset($settings['formulize_currentPage'])) { // drawing a multipage form...
            $currentPageToSend = $screen ? $settings['formulize_currentPage'].'-'.$screen->getVar('sid') : $settings['formulize_currentPage'];
            $prevPageToSend = $screen ? $settings['formulize_prevPage'].'-'.$settings['formulize_prevScreen'] : $settings['formulize_prevPage'];
			print "<input type=hidden name=formulize_currentPage value='".$currentPageToSend."'>";
			print "<input type=hidden name=formulize_prevPage value='".$prevPageToSend."'>";
			print "<input type=hidden name=formulize_doneDest value='".$settings['formulize_doneDest']."'>";
			print "<input type=hidden name=formulize_buttonText value='".$settings['formulize_buttonText']."'>";
		}
		if($_POST['overridescreen']) {
			print "<input type=hidden name=overridescreen value='".intval($_POST['overridescreen'])."'>";
		}
		if(strlen($_POST['formulize_lockedColumns'])>0) {
			print "<input type=hidden name=formulize_lockedColumns value='".$_POST['formulize_lockedColumns']."'>";
		}
        print "<input type=hidden name=formulize_originalVentry value='".$settings['formulize_originalVentry']."'>";
        foreach($allEntries as $fid=>$fidEntries) {
            foreach($fidEntries as $entry_id) {
                if($entry_id) {
                    print "<input type='hidden' name='form_".$fid."_rendered_entry[]' value='".$entry_id."'>";
                }
            }
        }
        if($screen) {
            print "<input type=hidden name=formulize_renderedEntryScreen value='".$screen->getVar('sid')."'>";
        }
	}
}


// draw in javascript for this form that is relevant to subforms
// $nosave indicates that the user cannot save this entry, so we shouldn't check for formulizechanged
function drawJavascript($nosave=false) {

global $xoopsUser, $xoopsConfig;

static $drawnJavascript = false;
if($drawnJavascript) {
	return;
}

// saving message
print "<div id=savingmessage style=\"display: none; position: absolute; width: 100%; right: 0px; text-align: center; padding-top: 50px; z-index: 100;\">\n";
global $xoopsConfig;
if ( file_exists(XOOPS_ROOT_PATH."/modules/formulize/images/saving-".$xoopsConfig['language'].".gif") ) {
    print "<img src=\"" . XOOPS_URL . "/modules/formulize/images/saving-" . $xoopsConfig['language'] . ".gif\">\n";
} else {
    print "<img src=\"" . XOOPS_URL . "/modules/formulize/images/saving-english.gif\">\n";
}
print "</div>\n";

$uid = $xoopsUser ? $xoopsUser->getVar('uid') : 0;

print "\n<script type='text/javascript'>\n";

print " initialize_formulize_xhr();\n";
print " var formulizechanged=0;\n";
print " var formulize_javascriptFileIncluded = new Array();\n";
print " var formulize_xhr_returned_check_for_unique_value = new Array();\n";

if(isset($GLOBALS['formulize_fckEditors'])) {
	print "function FCKeditor_OnComplete( editorInstance ) { \n";
	print " editorInstance.Events.AttachEvent( 'OnSelectionChange', formulizeFCKChanged ) ;\n";
	print "}\n";
	print "function formulizeFCKChanged( editorInstance ) { \n";
	print "  formulizechanged=1; \n";
	print "}\n";
}

?>

window.onbeforeunload = function (e) {

    if(formulizechanged) {

	var e = e || window.event;

	var confirmationText = "<?php print _formulize_CONFIRMNOSAVE_UNLOAD; ?>"; // message may have single quotes in it!

	// For IE and Firefox prior to version 4
	if (e) {
	    e.returnValue = confirmationText;
	}

	// For Safari
	return confirmationText;
    }
};

jQuery(window).on('unload', function() {
    <?php print formulize_javascriptForRemovingEntryLocks('unload'); ?>
});

<?php
global $codeToIncludejQueryWhenNecessary;
print $codeToIncludejQueryWhenNecessary;
if(isset($_POST['yposition']) AND intval($_POST['yposition'])>0 AND !isset($_POST['formulize_currentPage'])) {
		print "\njQuery(window).load(function () {\n";
		print "\tjQuery(window).scrollTop(".intval($_POST['yposition']).");\n";
		print "});\n";
}
?>

function includeResource(filename, type) {
   if(filename in formulize_javascriptFileIncluded == false) {
     var head = document.getElementsByTagName('head')[0];
     if(type == 'link') {
       var resource = document.createElement("link");
       resource.type = "text/css";
       resource.rel = "stylesheet";
       resource.href = filename;
     } else if(type == 'script') {
       var resource = document.createElement('script');
       resource.type = 'text/javascript';
       resource.src = filename;
     }
     head.appendChild(resource);
     formulize_javascriptFileIncluded[filename] = true;
   }
} 

<?php print checkForChrome(); ?>

function showPop(url) {
	if (window.formulize_popup == null) {
		formulize_popup = window.open(url,'formulize_popup','toolbar=no,scrollbars=yes,resizable=yes,width=800,height=550,screenX=0,screenY=0,top=0,left=0');
      } else {
		if (window.formulize_popup.closed) {
			formulize_popup = window.open(url,'formulize_popup','toolbar=no,scrollbars=yes,resizable=yes,width=800,height=550,screenX=0,screenY=0,top=0,left=0');
            } else {
			window.formulize_popup.location = url;              
		}
	}
	window.formulize_popup.focus();
}

<?php
// check if conditional elements have yet to appear, and return true if so
?>

var needWaitForConditionalAlert = false;

function conditionalCheckIsInProgress() {
    if(typeof conditionalCheckInProgress !== 'undefined' && conditionalCheckInProgress > 0) {
        if(!needWaitForConditionalAlert) {
            needWaitForConditionalAlert = setTimeout(function() {
                if(typeof conditionalCheckInProgress !== 'undefined' && conditionalCheckInProgress > 0) {        
                    alert('Error: something unexpected happened when trying to display conditional elements. Please contact the webmaster for assistance.');
                }
            }, 10000);
        }
        return true;
    } else {
        clearTimeout(needWaitForConditionalAlert);
        needWaitForConditionalAlert = false;
        return false;
    }
}

function validateAndSubmit(leave) {

    if(conditionalCheckIsInProgress()) {
        setTimeout(function() {
            validateAndSubmit(leave);
            }, 1000);
        return false;
    }

    var formulize_numbersonly_found= false;
    jQuery(".numbers-only-textbox").each(function() {
        if(jQuery(this).val().match(/[a-z]/i) !== null) {
            var answer = confirm ("You have entered "+jQuery(this).val()+" in a box that is supposed to have numbers only.  The letters will be removed if you save.  Is this OK?" );
            if (!answer){
                jQuery(this).focus();
                formulize_numbersonly_found = true;
            }
        }
    });

    if (formulize_numbersonly_found){
        return false;
    }

<?php
if(!$nosave) { // need to check for add or update permissions on the current user and this entry before we include this javascript, otherwise they should not be able to save the form
?>

	var validate = xoopsFormValidate_formulize_mainform(leave, window.document.formulize_mainform);
	// this is an optional form validation function which can be provided by a screen template or form text element
	if (window.formulizeExtraFormValidation && typeof(window.formulizeExtraFormValidation) === 'function') {
		validate = window.formulizeExtraFormValidation();
	}
	if(validate) {
		if(typeof savedPage != 'undefined' && savedPage && savedPrevPage) { // set in submitForm and will have values if we're on the second time around of a two step validation, like a uniqueness check with the server
			multipageSetHiddenFields(savedPage, savedPrevPage);
		}
		jQuery(".subform-accordion-container").map(function() {
			subelementid = jQuery(this).attr('subelementid');
			window.document.getElementById('subform_entry_'+subelementid+'_active').value = jQuery(this).accordion( "option", "active" );
		});
		jQuery('#submitx').attr('disabled', 'disabled');
		if(jQuery('.formulize-form-submit-button')) {
			jQuery('.formulize-form-submit-button').attr('disabled', 'disabled');
		}
        if(jQuery('#save_and_leave_button')) {
            jQuery('#save_and_leave_button').attr('disabled', 'disabled');
        }
        jQuery('#yposition').val(jQuery(window).scrollTop());
            showSavingGraphic();
        if (leave=='leave') {
            jQuery('#save_and_leave').val(1);
        }
        window.document.formulize_mainform.submit();
    } else {
        hideSavingGraphic();
    }
<?php
} // end of if not $nosave
?>
}

function showSavingGraphic() {
    window.document.getElementById('formulizeform').style.opacity = 0.5;
    window.document.getElementById('savingmessage').style.opacity = 1;
    window.document.getElementById('savingmessage').style.display = 'block';
    window.scrollTo(0,0);
    formulizechanged = 0; // don't want to trigger the beforeunload warning
}

function hideSavingGraphic() {
    window.document.getElementById('formulizeform').style.opacity = 1;
    window.document.getElementById('savingmessage').style.display = 'none';
}

<?php

print "	function verifyDone() {\n";
//print "		alert(formulizechanged);\n";
if(!$nosave) {
	print "	if(formulizechanged==0) {\n";
}
	print "		removeEntryLocks('submitGoParent');\n"; // true causes the go_parent form to submit
if(!$nosave) {
	print "	} else {\n";
	print "		var answer = confirm (\"" . _formulize_CONFIRMNOSAVE . "\");\n";
	print "		if (answer) {\n";
	print "			formulizechanged = 0;\n"; // don't want to trigger the beforeunload warning
	print "			removeEntryLocks('submitGoParent');\n"; // true causes the go_parent form to submit
	print "		}\n";
	print "	}\n";
}
print "   return false;"; // removeEntryLocks calls the go_parent form for us
print "	}\n";
print " function removeEntryLocks(action) {\n";
global $entriesThatHaveBeenLockedThisPageLoad;
if(count($entriesThatHaveBeenLockedThisPageLoad)>0) {
		print "var killLocks = " . formulize_javascriptForRemovingEntryLocks();
		print "		killLocks.done(function() { \n";
		print "			formulize_javascriptForAfterRemovingLocks(action);\n";
    print "			});\n";
} else {
		print "formulize_javascriptForAfterRemovingLocks(action);\n";
}
print " }\n";
	
?>

function formulize_javascriptForAfterRemovingLocks(action) {
	if(action == 'submitGoParent') {
			window.document.go_parent.submit();
	} else if(action == 'rewritePage') {
		var formAction = jQuery('form[name=formulize_mainform]').attr('action');
		var formData = jQuery('form[name=formulize_mainform]').serialize();
		jQuery.ajax({
			type: "POST",
			url: formAction,
			data: formData,
			success: function(html, x){
				document.open();
				document.write(html);
				document.close();
			}
		});
	}
}

<?php

	
print "function add_sub(sfid, numents, instance_id, frid, fid, mainformentry, subformelement, modal, parent_subformelement) {
    document.formulize_mainform.target_sub.value=sfid;
    document.formulize_mainform.target_sub_frid.value=frid;
    document.formulize_mainform.target_sub_fid.value=fid;
    document.formulize_mainform.target_sub_mainformentry.value=mainformentry;
    document.formulize_mainform.target_sub_subformelement.value=subformelement;
    document.formulize_mainform.target_sub_parent_subformelement.value=parent_subformelement;
    document.formulize_mainform.target_sub_open_modal.value=modal;
    document.formulize_mainform.numsubents.value=numents;
    document.formulize_mainform.target_sub_instance.value=instance_id;
    if(subEntryDialog.dialog('isOpen')) {
        window.document.formulize_mainform.modalscroll.value = subEntryDialog.scrollTop();
        saveSub('reload');
    } else {
        validateAndSubmit();
    }
}\n";

print "	function sub_del(sfid, sub_ent, type, parentSubformElement, fid, entry) {
    var answer = confirm ('" . _formulize_DEL_ENTRIES . "');
    if (answer) {
        document.formulize_mainform.deletesubsflag.value=sfid;
        if(subEntryDialog.dialog('isOpen')) {
            document.formulize_mainform.target_sub.value=sfid;
            document.formulize_mainform.target_sub_fid.value=fid;
            document.formulize_mainform.target_sub_mainformentry.value=entry;
            document.formulize_mainform.target_sub_parent_subformelement.value=parentSubformElement;
            document.formulize_mainform.target_sub_open_modal.value=type;
            window.document.formulize_mainform.modalscroll.value = subEntryDialog.scrollTop();
            saveSub('reload');
        } else {
            validateAndSubmit();
        }
    } else {
        return false;
    }
}\n";

print "	function sub_clone(sfid, sub_ent, type, parentSubformElement, fid, entry) {
    document.formulize_mainform.clonesubsflag.value=sfid;
    if(subEntryDialog.dialog('isOpen')) {
        document.formulize_mainform.target_sub.value=sfid;
        document.formulize_mainform.target_sub_fid.value=fid;
        document.formulize_mainform.target_sub_mainformentry.value=entry;
        document.formulize_mainform.target_sub_parent_subformelement.value=parentSubformElement;
        document.formulize_mainform.target_sub_open_modal.value=type;
        window.document.formulize_mainform.modalscroll.value = subEntryDialog.scrollTop();
        saveSub('reload');
    } else {
        validateAndSubmit();
    }
}\n";


global $xoopsConfig;
if ( file_exists(XOOPS_ROOT_PATH."/modules/formulize/images/working-".$xoopsConfig['language'].".gif") ) {
    $workingMessageGif = "<img src=\"" . XOOPS_URL . "/modules/formulize/images/working-" . $xoopsConfig['language'] . ".gif\">";
    $savingMessageGif = "<img src=\"" . XOOPS_URL . "/modules/formulize/images/saving-" . $xoopsConfig['language'] . ".gif\">";
} else {
    $workingMessageGif = "<img src=\"" . XOOPS_URL . "/modules/formulize/images/working-english.gif\">";
    $savingMessageGif = "<img src=\"" . XOOPS_URL . "/modules/formulize/images/saving-english.gif\">";
}
    
?>

var subEntryDialog;
var savingSubEntry = false;
jQuery(document).ready(function() {
    subEntryDialog = jQuery("#subentry-dialog").dialog({
        autoOpen: false,
        modal: true,
        width: "50%",
        position: { my: "right top", at: "right top", of: window },
        open: function() {
            loadSub(jQuery(this));
            jQuery(this).parent().css('position', 'fixed');
            jQuery(this).parent().css('top', '10px');
            jQuery(this).parent().css('left', (parseInt(jQuery(this).parent().css('left').replace('px', '')) - 10)+'px');
            jQuery(this).css('overflow-y', 'auto !important'); 
            jQuery(this).css('height', (parseInt(jQuery(window).height())-100)+'px');
        }
    });
});

jQuery.ajaxSetup({  
  cache: false  
});  

function loadSub(dialogObject) {
    dialogObject.empty();
    dialogObject.html('<div id="subentry-dialog-content"><center><?php print $workingMessageGif; ?></center></div>');
    dialogObject.load('<?php print XOOPS_URL; ?>/modules/formulize/include/subformdisplay-elementsonly.php?fid='+dialogObject.data('fid')+'&entry_id='+dialogObject.data('next_entry_id')+'&subformElementId='+dialogObject.data('subformElementId'), function() {
        jQuery(".ui-dialog-content").scrollTop(dialogObject.yposition);
        if(typeof setDatePickerMinMaxValues === 'function') { setDatePickerMinMaxValues(); }
    });
}

function redrawSubRow(entry_id,subformElementId) {
    jQuery.get('<?php print XOOPS_URL; ?>/modules/formulize/include/redrawSubformRow.php?entry_id='+entry_id+'&subformElementId='+subformElementId, function(data) {
        jQuery('.row-'+entry_id+'-'+subformElementId).each(function() {
            // remove all but the first two
            var counter = 0;
            var numChildren = jQuery(this).children().length;
            var rowObject = jQuery(this);
            jQuery(this).children().each(function() {
                counter = counter + 1;
                if(counter >= 3) {
                    jQuery(this).remove();
                }
                if(counter == numChildren) {
                    // we've removed the last one, so add in returned data
                    rowObject.html(rowObject.html()+data);
                }
            });
            
        });
    });
}

function goSubModal(ent, fid, frid, mainformFid, mainformEntryId, subformElementId, modalScroll) {
    subEntryDialog.data('entry_id', ent);
    subEntryDialog.data('next_entry_id', ent);
    subEntryDialog.data('fid', fid);
    subEntryDialog.data('frid', frid);
    subEntryDialog.data('mainformFid', mainformFid);
    subEntryDialog.data('mainformEntryId', mainformEntryId);
    subEntryDialog.data('subformElementId', subformElementId);
    subEntryDialog.data('yposition', modalScroll);
    subEntryDialog.dialog('open');
}

function saveSub(reload) {
    if(!savingSubEntry) {
        if(xoopsFormValidate_formulize_modal(document.getElementById('formulize_modal'))) {
            savingSubEntry = true;
            subEntryDialog.children('div').css('opacity', '0.5');
            subEntryDialog.append('<div id=savingmessage style="padding-top: 10px;"><?php print $savingMessageGif; ?></div>');
            jQuery('#formulize_modal input[type="hidden"]').prop('disabled', false);
            var formData = new FormData(jQuery('#formulize_modal')[0]);
            //var formData = subEntryDialog.children('form').serialize();
            jQuery.post({
                url: '<?php print XOOPS_URL; ?>/modules/formulize/include/readelements.php',
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                success: function() {
                    jQuery.post('<?php print XOOPS_URL; ?>/modules/formulize/formulize_xhr_responder.php?op=update_derived_value&uid=<?php global $xoopsUser; print $xoopsUser ? $xoopsUser->getVar('uid') : 0; ?>&fid='+subEntryDialog.data('mainformFid')+'&frid='+subEntryDialog.data('frid')+'&entryId='+subEntryDialog.data('mainformEntryId')+'&returnElements=1', function(data) {
                        savingSubEntry = false;
                        if(reload && reload == 'reload') {
                            jQuery('#formulize_mainform').append(jQuery('#formulize_modal .delbox:checked'));
                            subEntryDialog.dialog('close');
                            validateAndSubmit();
                        } else {
                            if(reload && reload == 'leave') {
                                subEntryDialog.dialog('close');
                            } else {
                                if(reload && reload == 'new') {
                                    subEntryDialog.data('next_entry_id', 'new');
                                }
                                loadSub(subEntryDialog);
                            }
                            redrawSubRow(subEntryDialog.data('entry_id'), subEntryDialog.data('subformElementId'));
                            var elements = JSON.parse(data);
                            for(elementId in elements) {
                                var rowSelector = 'formulize-de_'+subEntryDialog.data('mainformFid')+'_'+subEntryDialog.data('mainformEntryId')+'_'+elementId;
                                // if the element is shown, and there has been a change of value, then update it
                                if(window.document.getElementById(rowSelector) !== null && window.document.getElementById(rowSelector).style.display != 'none'
                                    && jQuery('#'+rowSelector).html() != elements[elementId]) {
                                        jQuery('#'+rowSelector).empty();
                                        jQuery('#'+rowSelector).append(elements[elementId]);
                                }
                            }
                        }
                    });
                }
            });
        }
    }
}


<?php

print "	function goSub(ent, fid, subformElementId) {\n";
print "		document.formulize_mainform.goto_sub.value = ent;\n";
print "		document.formulize_mainform.goto_sfid.value = fid;\n";
print "		document.formulize_mainform.goto_subformElementId.value = subformElementId;\n";
global $formulize_displayingMultipageScreen;
if($formulize_displayingMultipageScreen) {
print "		document.formulize_mainform.formulize_prevPage.value = document.formulize_mainform.formulize_currentPage.value;\n";    
print "		document.formulize_mainform.formulize_currentPage.value = 1;\n";
}
print "		validateAndSubmit();\n";
print "	}\n";
			
//added by Cory Aug 27, 2005 to make forms printable


print "function PrintPop(ele_allowed) {\n";
print "		window.document.printview.elements_allowed.value=ele_allowed;\n"; // nmc 2007.03.24 - added 
print "		window.document.printview.submit();\n";
print "}\n";

//added by Cory Aug 27, 2005 to make forms printable

print "function PrintAllPop() {\n";									// nmc 2007.03.24 - added 
print "		window.document.printview.elements_allowed.value='';\n"; // nmc 2007.03.24 - added 
print "		window.document.printview.submit();\n";					// nmc 2007.03.24 - added 
print "}\n";														// nmc 2007.03.24 - added 

// try and catch changes in a datebox element
print "jQuery(document).ready(function() {
  jQuery(\"img[title='"._CALENDAR."']\").click(function() {
	formulizechanged=1;		
  }); 
});
\n";

drawXhrJavascript();
// if we're not on mobile, do the default date picker stuff
$useragent=$_SERVER['HTTP_USER_AGENT'];
if(!preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i',$useragent)||preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i',substr($useragent,0,4))) {
?>
jQuery(document).ready(function() {
    setDatePickerMinMaxValues();
});

function setDatePickerMinMaxValues() {
	jQuery(".icms-date-box").each(function(){
        date_input = jQuery(this);
        var options = {};
        // copy datepicker_defaults so the original is not modified
        jQuery.extend(options, datepicker_defaults);
        var min_date = date_input.attr('min-date');
        if (min_date && min_date.length > 0) {
            // adjust so that the date does use the current time zone
            min_date = new Date(min_date);
            min_date.setTime(min_date.getTime() + min_date.getTimezoneOffset()*60*1000);
            options.minDate = new Date(min_date);
        }
        var max_date = date_input.attr('max-date');
        if (max_date && max_date.length > 0) {
            // adjust so that the date does use the current time zone
            max_date = new Date(max_date);
            max_date.setTime(max_date.getTime() + max_date.getTimezoneOffset()*60*1000);
            options.maxDate = new Date(max_date);
        }
        if (options.minDate || options.maxDate) {
            date_input.datepicker("destroy");
            date_input.datepicker(options);
        }
    });
}

function check_date_limits(element_id) {
    var date_input = jQuery("#"+element_id);
    var min_date = date_input.attr('min-date');
    var max_date = date_input.attr('max-date');
    var selected_date = new Date(date_input.datepicker('getDate'));
    <?php
        // if the selected_date is not valid then getTime() returns NaN (not-a-number)
        // NaN is NOT equal to NaN, so the comparison ensures the date is valid
    ?>
    if (selected_date.getTime() === selected_date.getTime()) {
        if (min_date && min_date.length > 0) {
            min_date = new Date(min_date);
            <?php
            // adjust the time zone before displaying the date, otherwise it could show the wrong day if
            //  the user and server are in different time zones
             ?>
            min_date.setTime(min_date.getTime() + (min_date.getTimezoneOffset() * 60 * 1000));
            if (selected_date < min_date) {
                // date is too far in the past
                selected_date = null;
                date_input.val('');
                alert("The date you selected is too far in the past.\n\n"+
                    "Please select a date on or after "+min_date.toDateString()+".");
            }
        }
        if (null != selected_date && max_date && max_date.length > 0) {
            max_date = new Date(max_date);
            <?php
            // adjust the time zone before displaying the date, otherwise it could show the wrong day if
            //  the user and server are in different time zones
             ?>
            max_date.setTime(max_date.getTime() + (max_date.getTimezoneOffset() * 60 * 1000));
            if (selected_date > max_date) {
                // date is too far in the future
                date_input.val('');
                alert("The date you selected is too far in the future.\n\n"+
                    "Please select a date on or before "+max_date.toDateString()+".");
            }
        }
    } else {
        // not a valid date
        date_input.val('');
    }
}
<?php
} // end of if we're not on mobile

    if(isset($GLOBALS['formulize_specialValidationLogicHook'])) {
        ?>
var tagBody = '(?:[^"\'>]|"[^"]*"|\'[^\']*\')*';
var tagOrComment = new RegExp(
    '<(?:'
    // Comment body.
    + '!--(?:(?:-*[^->])*--+|-?)'
    // Special "raw text" elements whose content should be elided.
    + '|script\\b' + tagBody + '>[\\s\\S]*?</script\\s*'
    + '|style\\b' + tagBody + '>[\\s\\S]*?</style\\s*'
    // Regular name
    + '|/?[a-z]'
    + tagBody
    + ')>',
    'gi');
function removeTags(html) {
  var oldHtml;
  do {
    oldHtml = html;
    html = html.replace(tagOrComment, '');
  } while (html !== oldHtml);
  return html.replace(/</g, '&lt;');
}
<?php
        $output = "jQuery(document).ready(function() {\n";
        
        foreach($GLOBALS['formulize_specialValidationLogicHook'] as $markupId=>$elementId) {
            $output .= "jQuery('#".$markupId."').change(function() {
                specialValidation".$markupId."(jQuery(this).val());
            });\n";
            // need to generate each ajax call for each element, since the target markupId cannot be passed into the success closure, has to be hard coded
            $output .= "function specialValidation".$markupId."(value) {
    jQuery.ajax({
        url: '".XOOPS_URL."/modules/formulize/formulize_specialValidation.php?markupId=".$markupId."&value='+value,
        success: function(data) {
            data = JSON.parse(removeTags(data));
            jQuery('#va_".trim($markupId,"de_")."').text(data.text);
            jQuery('#va_".trim($markupId,"de_")."').css('color',data.color);
        }
    });
}
";
           $output .= "specialValidation".$markupId."(jQuery('#".$markupId."').val())\n";
        }
        $output .= "});\n";
        print $output;
    }

    print "</script>\n";
    $drawnJavascript = true;
}

// THIS FUNCTION ACTUALLY DRAWS IN THE NECESSARY JAVASCRIPT FOR ALL ELEMENTS FOR WHICH ITS PROPERTIES ARE DEPENDENT ON ANOTHER ELEMENT
// PRIMARILY THIS APPLIES TO CONDITIONAL ELEMENTS, BUT ALSO USED IN ONE-TO-ONE RELATIONSHIPS
// NOTE THAT THROUGHOUT THIS CODE, HANDLE MEANS THE RENDERED MARKUP NAME!!  This should totally be refactored, ala XP
function drawJavascriptForConditionalElements($conditionalElements, $governingElements, $oneToOneElements, $oneToOneMetaData=false) {

    $initCode = "
jQuery(document).ready(function() {

	// preload the current state of the HTML for any conditional elements that are currently displayed, so we can compare against what we get back when their conditions change
	var conditionalElements = new Array('".implode("', '",array_keys($conditionalElements))."');
	";
	$topKey = 0;
	$relevantElementArray = array();
	foreach($governingElements as $thisGoverningElement=>$theseGovernedElements) {
		$initCode .= "governedElements['".$thisGoverningElement."'] = new Array();\n";
		foreach($theseGovernedElements as $innerKey=>$thisGovernedElement) {
			if(!isset($relevantElementArray[$thisGovernedElement])) {
				$initCode .= "relevantElements['".$thisGovernedElement."'] = new Array();\n";
				$initCode .= "oneToOneElements['".$thisGovernedElement."'] = new Array();\n";
				$relevantElementArray[$thisGovernedElement] = true;
			}
			$initCode .= "relevantElements['".$thisGovernedElement."'][$topKey] = '".$thisGoverningElement."';\n";
			$initCode .= "governedElements['".$thisGoverningElement."'][$innerKey] = '".$thisGovernedElement."';\n";
			$initCode .= "oneToOneElements['".$thisGovernedElement."'][$topKey] = ";
			if($oneToOneElements[$thisGoverningElement] == true) {
				$initCode .= "true;\n";
				foreach($oneToOneMetaData[$thisGoverningElement] as $key=>$value) {
						$initCode .= "oneToOneElements['".$thisGovernedElement."']['$key'] = '$value';\n"; 
				}
			} else {
				$initCode .= "false;\n";
			}
		}
		$topKey++;
	}

    foreach(array_keys($conditionalElements) as $ce) {
        $initCode .= "assignConditionalHTML('".$ce."', jQuery('#formulize-".$ce."').html());\n";
	}

    foreach(array_keys($governingElements) as $ge) {
        $initCode .= "jQuery(document).on('change', '[name=\"".$ge."\"]', function() {
    callCheckCondition(jQuery(this).attr('name'));
});\n";
    }

// end the document ready and continue with functions    
    $initCode .= "});\n";
    
    print _drawJavascriptForConditionalElements($initCode);

}

// this function takes the code generated in the drawJavascriptForConditionalElements function and returns with all the boilerplate needed for the conditional elements
// or if the boiler plate has already been rendered, then it just outputs the initialization code
function _drawJavascriptForConditionalElements($initCode) {

global $xoopsUser;
$uid = $xoopsUser ? $xoopsUser->getVar('uid') : 0;

static $codeRendered = false;

if(!$codeRendered) {

    $code = "
<script type='text/javascript'>

// need to be global!
var conditionalHTML = new Array(); 
var governedElements = new Array();
var relevantElements = new Array();
var oneToOneElements = new Array();

var conditionalCheckInProgress = 0;

".$initCode."

function callCheckCondition(name) {
    for(key in governedElements[name]) {
        var handle = governedElements[name][key];
			elementValuesForURL = getRelevantElementValues(relevantElements[handle]);
        var handleParts = handle.split('_');
        if(oneToOneElements[handle]['onetoonefrid'] && handleParts[1] != oneToOneElements[handle]['onetoonefid']) {
				elementValuesForURL = elementValuesForURL + '&onetoonekey=1&onetoonefrid='+oneToOneElements[handle]['onetoonefrid']+'&onetoonefid='+oneToOneElements[handle]['onetoonefid']+'&onetooneentries='+oneToOneElements[handle]['onetooneentries']+'&onetoonefids='+oneToOneElements[handle]['onetoonefids'];			
			}
			checkCondition(handle, conditionalHTML[handle], elementValuesForURL);	
		}
}

function assignConditionalHTML(handle, html) {
	conditionalHTML[handle] = html; 
}

function checkCondition(handle, currentHTML, elementValuesForURL) {
	partsArray = handle.split('_');
    conditionalCheckInProgress = conditionalCheckInProgress + 1;
	jQuery.post(\"".XOOPS_URL."/modules/formulize/formulize_xhr_responder.php?uid=".$uid."&op=get_element_row_html&elementId=\"+partsArray[3]+\"&entryId=\"+partsArray[2]+\"&fid=\"+partsArray[1]+elementValuesForURL, function(data) {
		if(data) {
			try {
				results = JSON.parse(data);
				// data is JSON, so will have extra instructions for us
				data = results.data;
                if(typeof results.newvalues !== 'undefined') {
                    newvalues = results.newvalues;
                    for(key in newvalues) {
                        jQuery(\"[name=\"+newvalues[key].name+\"]\").val(newvalues[key].value);
                    }
                }
		    } catch (e) {
                // do nothing
            }
			// should only empty if there is a change from the current state
            if(data != '{NOCHANGE}' && (currentHTML != data || (window.document.getElementById('formulize-'+handle) !== null && window.document.getElementById('formulize-'+handle).style.display == 'none'))) {
				jQuery('#formulize-'+handle).empty();
				jQuery('#formulize-'+handle).append(data);
				if(typeof setDatePickerMinMaxValues === 'function') { setDatePickerMinMaxValues(); }
                // unless it is a hidden element, show the table row...
                if(parseInt(data.indexOf(\"input type='hidden'\"))!=0) {
                    if(window.document.getElementById('formulize-'+handle) !== null) {
                        window.document.getElementById('formulize-'+handle).style.display = null; // doesn't need real value, just needs to be not set to 'none'
                    }
                    ShowHideTableRow('#formulize-'+handle,false,0,function() {}); // because the newly appended row will have full opacity so immediately make it transparent
                    ShowHideTableRow('#formulize-'+handle,true,1500,function() {});
                    if (typeof window['formulize_initializeAutocomplete'+handle] === 'function') {
                        window['formulize_initializeAutocomplete'+handle]();
                    }
                    if (typeof window['formulize_conditionalElementUpdate'+partsArray[3]] === 'function') {
                        window['formulize_conditionalElementUpdate'+partsArray[3]]();
                    }
                }
				assignConditionalHTML(handle, data);
			}
		} else {
			if( window.document.getElementById('formulize-'+handle) !== null && window.document.getElementById('formulize-'+handle).style.display != 'none') {
				ShowHideTableRow('#formulize-'+handle,false,700,function() {
					jQuery('#formulize-'+handle).empty();
					window.document.getElementById('formulize-'+handle).style.display = 'none';
					assignConditionalHTML(handle, data);
				});
			}
		}
		conditionalCheckInProgress = conditionalCheckInProgress - 1;
	});
}

function getRelevantElementValues(elements) {
	var ret = '';
	for(key in elements) {
		var handle = elements[key];
		if(handle.indexOf('[]')!=-1) { // grab multiple value elements from a different tag
			nameToUse = '[jquerytag='+handle.substring(0, handle.length-2)+']';
		} else {
			nameToUse = '[name='+handle+']';
		}
        if(jQuery('#subentry-dialog '+nameToUse).length > 0) {
            nameToUse = '#subentry-dialog '+nameToUse;
        }
        if(jQuery(nameToUse).length > 0) {
		elementType = jQuery(nameToUse).attr('type');
		if(elementType == 'radio') {
			formulize_selectedItems = jQuery(nameToUse+':checked').val();
		} else if(elementType == 'checkbox') {
			formulize_selectedItems = new Array();
			jQuery(nameToUse).map(function() { // need to check each one individually, because val isn't working right?!
				if(jQuery(this).attr('checked')) {
					foundval = jQuery(this).attr('value');
					formulize_selectedItems.push(foundval);
				} else {
					formulize_selectedItems.push('');		
				}
			});
		} else {
			formulize_selectedItems = jQuery(nameToUse).val();
		}
		if(jQuery.isArray(formulize_selectedItems)) {
			for(key in formulize_selectedItems) {
				ret = ret + '&'+handle+'='+encodeURIComponent(formulize_selectedItems[key]);					
			}
		} else {
			ret = ret + '&'+handle+'='+encodeURIComponent(formulize_selectedItems);				
		}
        }
	}
	return ret;
}


function ShowHideTableRow(rowSelector, show, speed, callback)
{
    var childCellsSelector = jQuery(rowSelector).children();
    var ubound = childCellsSelector.length - 1;
    var lastCallback = null;

    childCellsSelector.each(function(i)
    {
        // Only execute the callback on the last element.
        if (ubound == i)
            lastCallback = callback

        if (show)
        {
            jQuery(this).fadeIn(speed, lastCallback)
        }
        else
        {
            jQuery(this).fadeOut(speed, lastCallback)
        }
    });
}




</script>
";
        $codeRendered = true;
    } else {
        $code = "
<script type='text/javascript'>
$initCode
</script>
";
    }

    return $code;
	
}


function compileGoverningElementsForConditionalElements($conditionalElements, $entries, $sub_entries) {

		// need to setup governing elements array...which is inverse of the conditional elements
		$element_handler = xoops_getmodulehandler('elements','formulize');
		$governingElements = array();
		$GLOBALS['recordedEntries'] = array(); // global array used in the compile functions below, to make sure we only record a given element pair one time
		// so called 'handle' is in this case the rendered markup name?!
		foreach($conditionalElements as $handle=>$theseGoverningElements) {
			foreach($theseGoverningElements as $governingElementKey=>$thisGoverningElement) {
				$elementObject = $element_handler->get($thisGoverningElement);
				if(is_object($elementObject)) {
					if($elementObject->getVar('ele_type') == "derived") {
						unset($conditionalElements[$handle][$governingElementKey]); // derived value elements have no DOM instantiation that we can latch onto, so skip them...we should find a way to update them with the current state of the form maybe??
						continue;
					}
					$governingElements1 = _compileGoverningElements($entries, $elementObject, $handle);
					$governingElements2 = _compileGoverningElements($sub_entries, $elementObject, $handle);
					$governingElements3 = _compileGoverningLinkedSelectBoxSourceConditionElements($handle);
					$governingElements = mergeGoverningElements($governingElements, $governingElements1);
					$governingElements = mergeGoverningElements($governingElements, $governingElements2);
					$governingElements = mergeGoverningElements($governingElements, $governingElements3);
				}
				// must wrap required validation javascript in some check for the pressence of the element??  
			}
		}

		return $governingElements;

}

// this function takes a list of governing elements, and adds them to a master list, cleanly, so keys aren't overwritten
function mergeGoverningElements($masterList, $governingElements) {
		
		foreach($governingElements as $key=>$values) {
				foreach($values as $value) {
						$masterList[$key][] = $value;
				}
		}
		return $masterList;
}

// elementObject is the element that governs whether the handle element shows up
// renderedMarkupName is the de_ name for the handle element, in the current form
// onetoone controls whether governed and governing elements must be in the same entry - if true, then they can (must) be in different entries
function _compileGoverningElements($entries, $elementObject, $renderedMarkupName, $onetoone=false) {
	$type = $elementObject->getVar('ele_type');
	$ele_value = $elementObject->getVar('ele_value');
	if($type == "checkbox" OR ($type == "select" AND $ele_value[1])) {
		$additionalNameParts = "[]"; // set things up with the right [] for multiple value elements
	} else {
		$additionalNameParts = "";
	}
    $renderedNameParts = explode("_", $renderedMarkupName);
    $renderedEntryId = $renderedNameParts[2]; // de_formid_entryid_elementid is the format of the markupname, so we want item 2
	global $recordedEntries;
	$governingElements = array();
	if(isset($entries[$elementObject->getVar('id_form')])) {
		foreach($entries[$elementObject->getVar('id_form')] as $thisEntry) {
			if($thisEntry == "") {
				$thisEntry = "new";
			}
            if(($onetoone OR $thisEntry == $renderedEntryId) AND !isset($recordedEntries[$elementObject->getVar('id_form')][$thisEntry][$elementObject->getVar('ele_id')][$renderedMarkupName])) {
			$governingElements['de_'.$elementObject->getVar('id_form').'_'.$thisEntry.'_'.$elementObject->getVar('ele_id').$additionalNameParts][] = $renderedMarkupName;
				$recordedEntries[$elementObject->getVar('id_form')][$thisEntry][$elementObject->getVar('ele_id')][$renderedMarkupName] = true;
			}
		}
	}
	return $governingElements;
}

function _compileGoverningLinkedSelectBoxSourceConditionElements($handle) {
	// figure out if the $handle is for a lsb
	// if so, check if there are conditions on the lsb
	// check if the terms include any { } elements and grab those
	$handleParts = explode("_",$handle); // de, fid, entry, elementId
	$element_handler = xoops_getmodulehandler('elements','formulize');
	$elementObject = $element_handler->get($handleParts[3]);
	global $recordedEntries;
	$governingElements = array();
	if(is_object($elementObject) AND $elementObject->isLinked) {
		$ele_value = $elementObject->getVar('ele_value');
		$elementConditions = $ele_value[5];
        if (is_array($elementConditions[2])) {
            foreach($elementConditions[2] as $thisTerm) {
                if (substr($thisTerm,0,1) == "{" AND substr($thisTerm, -1) == "}") {
                    // figure out the element, which is presumably in the same form, and assume the same entry
                    $plainTerm = trim($thisTerm,"{}");
                    $curlyBracketElement = $element_handler->get($plainTerm);
                    if(is_object($curlyBracketElement)) {
                    if (!isset($recordedEntries[$curlyBracketElement->getVar('id_form')][$handleParts[2]][$curlyBracketElement->getVar('ele_id')][$handle])) {
                        $governingElements['de_'.$curlyBracketElement->getVar('id_form').'_'.$handleParts[2].'_'.$curlyBracketElement->getVar('ele_id')][] = $handle;
                        $recordedEntries[$curlyBracketElement->getVar('id_form')][$handleParts[2]][$curlyBracketElement->getVar('ele_id')][$handle] = true;
                        }
                    } elseif($plainTerm != 'BLANK' AND $plainTerm != 'USER' AND strtoupper(substr($plainTerm, 0, 5)) != 'TODAY') {
                        print "Error: $plainTerm is used as a condition, but does not resolve to a form element. Has the element been deleted? (or misspelled?)";
                    }
                }
            }
        }
    }
    return $governingElements;
}

// determine which screen to use when displaying a subform
// - if a screen is selected in the admin section, then use that
// - if no screen is selected, use the default screen
// - if the screen selected so far is not a single-page data-entry screen, return null
function get_display_screen_for_subform($subform_element_object) {
    $selected_screen_id = null;

    if ($subform_element_object and is_a($subform_element_object, "formulizeformulize")) {
        $ele_value = $subform_element_object->getVar('ele_value');
        if (isset($ele_value['display_screen'])) {
            // use selected screen
            $selected_screen_id = intval($ele_value['display_screen']);
        } else {
            // use default screen for the form
            $form_handler = xoops_getmodulehandler('forms', 'formulize');
            $formObject = $form_handler->get($ele_value[0]);    // 0 is the form_id
            $selected_screen_id = intval($formObject->getVar('defaultform'));
        }

        if ($selected_screen_id) {
            // a screen is selected -- confirm that it is a single-page or multipage data-entry screen
            global $xoopsDB;
            $screen_type = q("SELECT type FROM ".$xoopsDB->prefix("formulize_screen").
                " WHERE sid=".intval($selected_screen_id)." and fid=".intval($ele_value[0]));
            if (1 != count($screen_type) or !isset($screen_type[0]['type']) or ("form" != $screen_type[0]['type'] AND "multiPage" != $screen_type[0]['type'])) {
                // selected screen is not valid for displaying the subform
                $selected_screen_id = null;
            }
        }
    }

    return $selected_screen_id;
}
