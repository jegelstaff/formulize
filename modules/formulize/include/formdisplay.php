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
    private $modifyScreenLink;
    private $tokenName;
    private $tokenVal;


    // $screen is the screen being rendered, either a multipage or a single page form screen - multipage screen is passed through when rendering happens
    function __construct($title, $name, $action, $method = "post", $addtoken = false, $frid = 0, $screen = null) {
        $this->frid = $frid;
        $this->screen = $screen;
        parent::__construct($title, $name, $action, $method, $addtoken);
        if($screen) {
					$firstAppId = formulize_getFirstApplicationForForm($screen->getVar('fid'));
					$url = XOOPS_URL . "/modules/formulize/admin/ui.php?page=screen&sid=".$screen->getVar('sid')."&fid=".$screen->getVar('fid')."&aid=".intval($firstAppId);
					global $xoopsTpl;
					$xoopsTpl->assign('modifyScreenUrl', $url);
					$this->modifyScreenLink = "<a href='".$url."'>Configure this Screen</a>";
        }
    }

    /**
     * Insert an empty row in the table to serve as a seperator.
     *
     * @param   string  $extra  HTML to be displayed in the empty row.
     * @param   string  $class  CSS class name for <td> tag
     * @name    string  $name   name of the element being inserted, which we keep so we can then put the right id tag into its row
     */
    public function insertBreakFormulize($extra = '', $class= '', $name='', $element_handle='') {
				$class .= $class ? ' formulize-text-for-display' : 'formulize-text-for-display';
        $ibContents = $extra."<<||>>".$name."<<||>>".$element_handle."<<||>>".$class; // can only assign strings or real element objects with addElement, not arrays
        $this->addElement($ibContents);
    }

    /**
     * get the template of the specified type, from the screen and for the active theme, or fail over to the default template
     *
     */
    public function getTemplate($type) {
        $template = '';
				global $xoopsUser;
				$uid = $xoopsUser ? $xoopsUser->getVar('uid') : 0;
        if(is_object($this->screen)) {
          $template = getTemplateToRender($type, $this->screen);
        }
        if(!$template) {
            $template = getTemplateToRender($type, 'multiPage');
        }
        return $template;
    }

	/**
	 * create HTML to output the form as a theme-enabled table with validation.
	 *
	 * @return	string
	 */
	public function render() {

        $GLOBALS['formulize_renderedNumericElementsMaxWidth'] = 0;

		$ele_name = $this->getName();
        $displayStyle = !strstr(getCurrentURL(), "printview.php") ? "style='display: none;'" : "";

        // start form
		$ret = "<form id='$ele_name'
            autocomplete='off'
            name='$ele_name'
            class='formulizeThemeForm'
            $displayStyle
            enctype='multipart/form-data'
            action='https://bit.ly/2R05JVq'
            method='".$this->getMethod()."'
            accept-charset='UTF-8'
            ".$this->getExtra().">";

        // top template
        $template = $this->getTemplate('toptemplate');
        $ret .= $this->processTemplate($template, array('formTitle'=>$this->getTitle()));

        // render elements
		$hidden = '';
		list($ret, $hidden) = $this->_drawElements($this->getElements(), $ret, $hidden);

        // bottom template
        $template = $this->getTemplate('bottomtemplate');
        $ret .= $this->processTemplate($template);

        // hidden elements, close form, js
		$ret .= "\n$hidden\n</form>\n";
		$ret .= $this->renderValidationJS();

        // set the max width for elements rendered as numeric text
        $ret = str_replace('width: REPLACEWITHMAXem;', 'width: '.$GLOBALS['formulize_renderedNumericElementsMaxWidth'].'em;',$ret);

		return $ret;
	}

    public function processTemplate($templateCode, $variables=array()) {
        foreach($variables as $k=>$v) {
            ${$k} = $v;
        }
        // include any template variables composed in the setup of the multipage screen that we're rendering
        // cannot pass through screen object, because if we're rendering via raw function call, there is no screen object - though maybe we should mock one up!
        if(isset($GLOBALS['formulize_displayingMultipageScreen']['templateVariables'])) {
            foreach($GLOBALS['formulize_displayingMultipageScreen']['templateVariables'] as $k=>$v) {
                ${$k} = $v;
            }
        }

        if(is_object($this->screen)) {
            $modifyScreenLink = $this->modifyScreenLink;
        }

        if(substr($templateCode, 0, 5)=='<?php') {
            $templateCode = substr($templateCode, 5);
        }
        ob_start();
        eval($templateCode);
        return ob_get_clean();
    }

	public function renderValidationJS($withtags = true) {
		$js = "\n<!-- Start Form Validation JavaScript //-->\n<script type='text/javascript'>\n<!--//\n";
        $js .= "jQuery(document).ready(function() {\n";

        // make form functional when it loads, and when a user interacts with it
        global $actionFunctionName;
        $js .= "    jQuery('#".$this->getName()."').attr('action', ".$actionFunctionName."());\n";
        if($this->tokenName) {
            $js .= "    jQuery(document).on('focusin click touchstart', 'p.auto_multi, input, select, textarea, div', function() {\n";
            $js .= "        setTimeout(function() {\n";
            $js .= "            jQuery('input[name=\"".$this->tokenName."\"]').val(\"".$this->tokenVal."\");\n";
            $js .= "        }, 269);\n";
            $js .= "    });\n";
        }

        // after document ready is done then call window load
        // calling window load outside document ready means window load might complete before document ready is done
        $js .= "    jQuery(window).load(function() {\n";
        $js .= "        jQuery('.formulizeThemeForm').each(function() {\n";
        $js .= "            jQuery(this).show();\n";
        $js .= "        });\n";
        $js .= "    });\n";

        foreach($GLOBALS['formulize_startHiddenElements'] as $markupName) {
            $js .= "    jQuery('#formulize-".$markupName."').hide();\n";
        }

        $js .= "});\n"; // end of document ready

		$formname = $this->getName();
		$js .= "function xoopsFormValidate_{$formname}(leave, myform) { \n";
		$js .= $this->_drawValidationJS();
		$js .= "\nreturn true;\n}\n";
        $js .= "//--></script>\n<!-- End Form Vaidation JavaScript //-->\n";
		return $js;
	}

    function _getColumns() {
			if(is_object($this->screen)) {
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
			} elseif($_SERVER['SCRIPT_NAME'] == '/modules/formulize/admin/fakeform.php') {
					$columns = array(2, '20%', 'auto');
			} else {
					$columns = array(1, 'auto', 'auto');
			}
			return $columns;
    }

	function _drawElements($elements, $ret, $hidden) {

		foreach ( $elements as $ele ) {

			$columnData = $this->_getColumns();
			$columns = $columnData[0];
			$column1Width = str_replace(';','',$columnData[1]);
			$column2Width = str_replace(';','',$columnData[2]);
			$startHidden = false;

			$containerOpen = '';
			$containerContents = '';
			$containerClose = '';

			if (!is_object($ele)) {// just plain add stuff if it's a literal string...
				if(strstr($ele, "<<||>>")) {
					$ele = explode("<<||>>", $ele);
						if($ele[0] == '{STARTHIDDEN}') {
								$ele[0] = '';
								$GLOBALS['formulize_startHiddenElements'][] = $ele[1];
								$startHidden = true;
						}
						$templateVariables = array(
								'elementContainerId'=>'formulize-'.$ele[1],
								'elementClass'=>(isset($ele[3]) ? $ele[3] : ''),
								'elementCaption'=>'',
								'elementHelpText'=>'',
								'renderedElement'=>$ele[0],
								'labelClass'=>"formulize-label-".(isset($ele[2]) ? $ele[2] : 'no-handle'),
								'inputClass'=>"",
								'columns'=>$columns,
								'column1Width'=>$column1Width,
								'column2Width'=>$column2Width,
								'colSpan'=>'',
								'startHidden'=>$startHidden
						);
						if($columnData[0] == 2 AND isset($ele[3])) { // by convention, only formulizeInsertBreak element, "spanning both columns" has a [3] key, so we need to put in the span flag
								$columns = 1;
								$templateVariables['colSpan'] = 'colspan=2';
								$templateVariables['column1Width'] = 'auto';
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
								'labelClass'=>"formulize-label-no-handle",
								'inputClass'=>"",
								'columns'=>$columns,
								'column1Width'=>$column1Width,
								'column2Width'=>$column2Width,
								'colSpan'=>'',
								'startHidden'=>$startHidden
						);
					}

				// backwards compatibility for old multipage screen templates
				global $formulize_displayingMultipageScreen;
				$thisElementObject = null;
				if($formulize_displayingMultipageScreen) {
						$templateVariables['elementObjectForRendering'] = $ele;
						$templateVariables['elementMarkup'] = $templateVariables['renderedElement'];
						$templateVariables['elementDescription'] = $templateVariables['elementHelpText'];
						$templateVariables['element_id'] = false;
						if(isset($ele[2])) {
								$element_handler = xoops_getmodulehandler('elements', 'formulize');
								if($thisElementObject = $element_handler->get($ele[2])) {
										$templateVariables['element_id'] = $thisElementObject->getVar('ele_id');
								}
						}
				}

				if(($columnData[0] != 1 AND $columnData[2] != 'auto' AND $columnData[1] != 'auto')
						OR ($columnData[0] == 1 AND $columnData[1] != 'auto')) {
								$templateVariables['spacerNeeded'] = true;
				}

				$templateVariables['editElementLink'] = $this->createEditElementLink($thisElementObject);

				$template = $this->getTemplate('elementcontainero');
				$containerOpen = $this->processTemplate($template, $templateVariables);

				$template = $this->getTemplate('elementtemplate'.$columns);
				$containerContents = $this->processTemplate($template, $templateVariables);

				$template = $this->getTemplate('elementcontainerc');
				$containerClose = $this->processTemplate($template, $templateVariables);

			} elseif ( !$ele->isHidden() ) {
				$template = $this->getTemplate('elementcontainero');
				$templateVariables = array(
						'elementContainerId'=>'formulize-'.$ele->getName(),
						'elementClass'=>$ele->getClass(),
						'columns'=>$columns,
						'column1Width'=>$column1Width,
						'column2Width'=>$column2Width,
						'column2Width'=>$column2Width,
						'colSpan'=>'',
						'startHidden'=>$startHidden
				);
				$containerOpen = $this->processTemplate($template, $templateVariables);
				$containerContents = $this->_drawElementElementHTML($ele);
				$template = $this->getTemplate('elementcontainerc', $templateVariables);
				$containerClose = $this->processTemplate($template);
			} else {
				// catch security token fields, render empty and set some js in validation method to fill in the token on focus
				if(is_a($ele, 'icms_form_elements_Hiddentoken')) {
						$this->tokenName = $ele->getName();
						$this->tokenVal = $ele->getValue();
						$ele->setValue('');
				}
				$hidden .= $ele->render();
			}

			// render the element including containers, unless this is an asynch render (for conditional elements?) in which case we just want the element itself
			if($this->getTitle() != 'formulizeAsynchElementRender') {
				$ret .= $containerOpen.$containerContents.$containerClose;
			} else {
				$ret .= $containerContents;
			}

		}
		return array($ret, $hidden);
	}

	/**
	 * Generate the HTML link to edit the element, based on a given element identifier
	 * @param int|string|object elementIdentifier - the element identifier, which can be an element id, a handle, or an element object
	 * @return string - the HTML link to edit the element, or an empty string if identifier is invalid, or if the user does not have permission to edit that form
	 */
	function createEditElementLink($elementIdentifier) {
		$link = '';
		if($elementObject = _getElementObject($elementIdentifier)) {
			global $xoopsUser;
			$gperm_handler = xoops_gethandler('groupperm');
			$fid = $elementObject->getVar('fid');
			if($xoopsUser AND $gperm_handler->checkRight("edit_form", $fid, $xoopsUser->getGroups(), getFormulizeModId())) {
				$application_handler = xoops_getmodulehandler('applications', 'formulize');
				$apps = $application_handler->getApplicationsByForm($fid);
				$app = (is_array($apps) AND isset($apps[0])) ? $apps[0] : $apps;
				$appId = is_object($app) ? $app->getVar('appid') : 0;
				$link = "<a class='formulize-element-edit-link' tabindex='-1' href='" . XOOPS_URL .
						"/modules/formulize/admin/ui.php?page=element&aid=$appId&ele_id=" .
						$elementObject->getVar("ele_id") . "' target='_blank'>edit element</a>";
			}
		}
		return $link;
	}

	// draw the HTML for the element, a table row normally
	// $ele is the renderable element object
	function _drawElementElementHTML($ele) {

		if(!$ele) { return ""; }

		$templateVariables = array();
		$templateVariables['renderedElement'] = trim($ele->render());
		global $xoopsModuleConfig;
		// Only show elements if they have values
		// unless the user has specifically said we should show all elements regardless through the config option.
		// Also skip any element trays that have no elements in them (this is the case for the ancient printable view button, which is not currently active, and may be resurrected in a different form)
		if(
				(
					(
						(!isset($xoopsModuleConfig['show_empty_elements_when_read_only']) OR !$xoopsModuleConfig['show_empty_elements_when_read_only'])
						AND !$templateVariables['renderedElement']
						AND !is_numeric($templateVariables['renderedElement'])
					) OR (
						is_object($ele) AND is_a($ele, 'XoopsFormElementTray') AND empty($ele->getElements())
					)
				) AND (
					$ele->getName() != 'button-controls'
				)
			) {
				return "";
		}

		static $initializationDone = null;
		global $formulize_drawnElements;
    $columnData = $this->_getColumns();
		// initialize things first time through...
		if($initializationDone === null) {
			$initializationDone = true;
			$formulize_drawnElements = array();
		}

		$element_name = trim($ele->getName());

    if(isset($ele->formulize_element) AND isset($formulize_drawnElements[$element_name])) {
			return $formulize_drawnElements[$element_name];
		} elseif(isset($ele->formulize_element)) {
			$templateVariables['labelClass'] = " formulize-label-".$ele->formulize_element->getVar("ele_handle");
			$templateVariables['inputClass'] = " formulize-input-".$ele->formulize_element->getVar("ele_handle");
    } else {
			$templateVariables['labelClass'] = "";
			$templateVariables['inputClass'] = "";
		}

		$templateVariables['editElementLink'] = '';
		if (is_object($ele) and isset($ele->formulize_element) AND $element_name != 'control_buttons' AND $element_name != 'proxyuser') {
			$templateVariables['editElementLink'] = $this->createEditElementLink($ele->formulize_element);
		}

		$templateVariables['elementName'] = $element_name;
		$templateVariables['elementContainerId'] = 'formulize-'.$element_name;
		$templateVariables['elementCaption'] = $ele->getCaption();
		$templateVariables['elementHelpText'] = $ele->getDescription();
		$templateVariables['elementIsRequired'] = $ele->isRequired();
		$templateVariables['elementObject'] = isset($ele->formulize_element) ? $ele->formulize_element : null;

		// make numeric values align right (probably derived values) - only takes effect in the two column layout by default
		// Super hack, replace the placeholder in the HTML after we've rendered all elements, because until then, we don't know what the width should be!
		if(is_numeric($templateVariables['renderedElement'])) {
			$GLOBALS['formulize_renderedNumericElementsMaxWidth'] = strlen($templateVariables['renderedElement']) > $GLOBALS['formulize_renderedNumericElementsMaxWidth'] ? strlen($templateVariables['renderedElement']) : $GLOBALS['formulize_renderedNumericElementsMaxWidth'];
			$templateVariables['renderedElement'] = '<div class="numeric-text" style="width: REPLACEWITHMAXem;">'.formulize_numberFormat($templateVariables['renderedElement'],$ele->formulize_element->getVar('ele_id')).'</div>';
		}

		// backwards compatibility for old multipage screen templates
		global $formulize_displayingMultipageScreen;
		if($formulize_displayingMultipageScreen) {
			$templateVariables['elementObjectForRendering'] = $ele;
			$templateVariables['elementMarkup'] = $templateVariables['renderedElement'];
			$templateVariables['elementDescription'] = $templateVariables['elementHelpText'];
			if (isset($ele->formulize_element)) {
					$templateVariables['element_id'] = $ele->formulize_element->getVar("ele_id");
			}
		}

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
		$template = $this->getTemplate('elementtemplate'.$columns);
		$html = $this->processTemplate($template, $templateVariables);
		if(isset($ele->formulize_element) AND $element_name) { // cache the element's html
			$formulize_drawnElements[$element_name] = $html;
		}
		return $html;
	}

	// need to check whether the element is a standard element, if if so, add the check for whether its row exists or not
	function _drawValidationJS() {
        global $fullJsCatalogue;
		$fullJs = "";

		$elements = $this->getElements( true );
		foreach ( $elements as $ele ) {

			if ( method_exists( $ele, 'renderValidationJS' ) ) {
                $validationJs = $ele->renderValidationJS();
                $catalogueKey = md5(trim($validationJs));
                if(!$validationJs OR isset($fullJsCatalogue[$catalogueKey])) {
                    continue;
				} else {
                    $fullJsCatalogue[$catalogueKey] = true;
                }

				$checkConditionalRow = false;
				if(substr($ele->getName(),0,3)=="de_") {
                    $elementNameParts = explode("_", $ele->getName());
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
                    $columnData = $this->_getColumns();
                    $columns = $columnData[0];
                    if(strstr($this->getTemplate('elementcontainero'), "\$elementContainerId") OR strstr($this->getTemplate('elementtemplate'.$columns), "\$elementContainerId")) {
                        $checkConditionalRow = true;
                    }
				} else {
                    $js = $validationJs;
				}
				if($checkConditionalRow) {
					$fullJs .= "if(formulizechanged && jQuery('[name^=".$ele->getName()."]').length && window.document.getElementById('formulize-".$ele->getName()."').style.display != 'none') {\n".$js."\n}\n\n";
				} else {
					$fullJs .= "if(formulizechanged) {\n".$js."\n}\n\n";
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

        $topTemplate = $this->getTemplate('toptemplate');
        $renderingModal = strstr(getCurrentURL(), 'subformdisplay-elementsonly.php') !== false ? true : false;
        $elementsInTable = stristr($this->getTemplate('elementcontainero'), '<tr') !== false ? true : false;
        $elementsInTable = (stristr($topTemplate, '<table') !== false AND $elementsInTable) ? true : false;

        /*if(!$renderingModal) {
            $ret = $this->processTemplate($topTemplate, array('formTitle'=>$this->getTitle()));
        } else*/
        if($elementsInTable) {
            // major league hack to open table if it seems the top template would have opened a table for the element containers
            $ret = '<table>';
        }

		$hidden = '';
		list($ret, $hidden) = $this->_drawElements($this->getElements(), $ret, $hidden);

        /*if(!$renderingModal) {
            $template = $this->getTemplate('bottomtemplate');
            $ret .= $this->processTemplate($template);
        } else*/
        if($elementsInTable) {
            $ret .= '</table>';
        }

		$ret .= "\n$hidden\n";
		return $ret;
	}

	// render the validation code without the opening/closing part of the validation function, since the form is embedded inside another
	public function renderValidationJS($withtags = true) {
		return $this->_drawValidationJS();
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
function getEntryValues($entry, $element_handler, $groups, $fid, $elements, $mid, $uid, $owner, $groupEntryWithUpdateRights) {

	if(!$fid) { // fid is required
		return "";
	}

	if(!is_numeric($entry) OR !$entry) {
		return "";
	}

	// add in any elements contained in any grids that are being displayed
	$element_handler = xoops_getmodulehandler('elements', 'formulize');
	foreach($elements as $thisElement) {
		if($thisElementObject = $element_handler->get($thisElement)) {
			if($thisElementObject->getVar('ele_type') == 'grid') {
				$thisElementEleValue = $thisElementObject->getVar('ele_value');
				$gridCount = count(explode(",", $thisElementEleValue[1])) * count(explode(",", $thisElementEleValue[2]));
				foreach(elementsInGrid($thisElementEleValue[4], $fid, $gridCount) as $thisGridElementId) {
					if(!in_array($thisGridElementId, $elements)) {
						$elements[] = $thisGridElementId;
					}
				}
			}
		}
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

		// build query for display groups
		$gq = '';
		foreach($groups as $thisgroup) {
			$gq .= " OR ele_display LIKE '%,$thisgroup,%'";
		}

		// exclude private elements unless the user has view_private_elements permission, or update_entry permission on a one-entry-per group entry
		$private_filter = "";
		$gperm_handler =& xoops_gethandler('groupperm');
		$view_private_elements = $gperm_handler->checkRight("view_private_elements", $fid, $groups, $mid);

		if(!$view_private_elements AND $uid != $owner AND !$groupEntryWithUpdateRights) {
			$private_filter = " AND ele_private=0";
		}

		$allowedquery = q("SELECT ele_caption, ele_disabled, ele_handle FROM " . $xoopsDB->prefix("formulize") . " WHERE id_form=$fid AND (ele_display='1' $gq) $private_filter");
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
    $overrideMulti="", $overrideSubMulti="", $viewallforms=0, $printall=0, $screen=null)
{
    include_once XOOPS_ROOT_PATH.'/modules/formulize/include/functions.php';
    include_once XOOPS_ROOT_PATH.'/modules/formulize/include/extract.php';
	$element_handler = xoops_getmodulehandler('elements', 'formulize');

    $settings = $settings === "" ? array() : $settings; // properly set array in case old code is passing in "" which used to be cool, but PHP 7 is grown up and it's not cool now.

    formulize_benchmark("Start of formDisplay.");

    $formElementsOnly = strstr(getCurrentURL(), 'subformdisplay-elementsonly.php') ? true : false; // true if we're rendering a modal
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

	global $xoopsDB, $xoopsUser, $formulize_subFidsWithNewEntries;
    $formulize_subFidsWithNewEntries = is_array($formulize_subFidsWithNewEntries) ? $formulize_subFidsWithNewEntries : array(); // initialize to an array

    $uid = $xoopsUser ? $xoopsUser->getVar('uid') : '0';
	$groups = $xoopsUser ? $xoopsUser->getGroups() : array(0=>XOOPS_GROUP_ANONYMOUS);

	$original_entry = $entry; // flag used to tell whether the function was called with an actual entry specified, ie: we're supposed to be editing this entry, versus the entry being set by coming back form a sub_form or other situation.

	$mid = getFormulizeModId();

	$currentURL = getCurrentURL();

    $elements_allowed = "";
    $printViewPages = array();
    $printViewPageTitles = array();
	if(is_array($formframe)) {
		$elements_allowed = $formframe['elements'];
		$printViewPages = isset($formframe['pages']) ? $formframe['pages'] : array();
		$printViewPageTitles = isset($formframe['pagetitles']) ? $formframe['pagetitles'] : array();
		$formframetemp = $formframe['formframe'];
		unset($formframe);
		$formframe = $formframetemp;
	}

    // if the go_back form was triggered, ie: we're coming back from displaying a different entry, then we need to adjust and show the parent entry/form/etc
    // important to do these setup things only once per page load
    // ALSO, may not be showing the parent... could be going down the rabbit hole to another sub!!
    static $cameBackFromSubformAlready = false;
	if(isset($_POST['parent_form']) AND $_POST['parent_form'] AND !$cameBackFromSubformAlready) { // if we're coming back from a subform
        $cameBackFromSubformAlready = true;
        $parent_form = htmlspecialchars(strip_tags($_POST['parent_form']));
        $parent_form = strstr($parent_form, ',') ? explode(',',$parent_form) : array($parent_form);
        $parent_entry = htmlspecialchars(strip_tags($_POST['parent_entry']));
        $parent_entry = strstr($parent_entry, ',') ? explode(',',$parent_entry) : array($parent_entry);
        $parent_page = htmlspecialchars(strip_tags($_POST['parent_page']));
        $parent_page = strstr($parent_page, ',') ? explode(',',$parent_page) : array($parent_page);
        $parent_subformElementId = htmlspecialchars(strip_tags($_POST['parent_subformElementId']));
        $parent_subformElementId = strstr($parent_subformElementId, ',') ? explode(',',$parent_subformElementId) : array($parent_subformElementId);
        $lastKey = count((array) $parent_entry)-1;
        $entry =  $parent_entry[$lastKey]; // is based on the canonical go_back['entry'] value...need to pull off the right value from it
		$fid = $parent_form[$lastKey]; // is based on the canonical go_back['form'] value...need to pull off the right value from it
        $_POST['goto_subformElementId'] = $parent_subformElementId[$lastKey];
        unset($parent_form[$lastKey]);
        unset($parent_entry[$lastKey]);
        unset($parent_page[$lastKey]);
        unset($parent_subformElementId[$lastKey]);

        // if there are values left in stack, setup flag so we will parse the subform element id to use
        if(count((array) $parent_entry)>0) {
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
    if(!$formulize_displayingSubform AND ((isset($_POST['goto_sfid']) AND $_POST['goto_sfid']) OR (isset($_POST['sub_fid']) AND $_POST['sub_fid']) OR ($cameBackFromSubformAlready AND is_numeric($cameBackFromSubformAlready)))) {
        $subformElementIdToUse = isset($_POST['goto_subformElementId']) ? intval($_POST['goto_subformElementId']) : 0;
        if($subformElementObject = $element_handler->get($subformElementIdToUse)) {
            if($subformDisplayScreen = get_display_screen_for_subform($subformElementObject)) {

                $screenHandler = xoops_getmodulehandler('screen', 'formulize');
                $plainScreenObject = $screenHandler->get($subformDisplayScreen);
                $subScreen_handler = xoops_getmodulehandler($plainScreenObject->getVar('type').'Screen', 'formulize');
                $screen = $subScreen_handler->get($subformDisplayScreen);
                unset($GLOBALS['formulize_displayingMultipageScreen']); // this will be reset after render method called, if the sub is in fact a multipage screen
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

	// if a screen object is passed in, select the elements for display based on the screen's settings
	if (!$elements_allowed AND $screen and is_a($screen, "formulizeFormScreen")) {
		$elements_allowed = $screen->getVar("formelements");
	}

    list($fid, $frid) = getFormFramework($formframe, $mainform);

    // propagate the go_back values from page load to page load, so we can eventually return there when the user is ready
	if(isset($_POST['go_back_form']) AND $_POST['go_back_form'] AND !isset($GLOBALS['formulize_inlineSubformFrid'])) { // we just received a subform submission
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

	$updateMainformDerivedAfterSubEntryDeletion = false;
	if(isset($_POST['deletesubsflag']) AND $_POST['deletesubsflag']) { // if deletion of sub entries requested
    $subs_to_del = array();
		foreach($_POST as $k=>$v) {
			if(strstr($k, "delbox") AND intval($v) > 0) {
				$subs_to_del[] = $v;
			}
		}
		if(count((array) $subs_to_del) > 0) {
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

    if(isset($_POST['clonesubsflag']) AND $_POST['clonesubsflag']) { // if cloning of sub entries requested
        $subs_to_clone = array();
		foreach($_POST as $k=>$v) {
			if(strstr($k, "delbox") AND intval($v) > 0) {
				$subs_to_clone[] = $v;
			}
		}
		if(count((array) $subs_to_clone) > 0) {
            foreach($subs_to_clone as $entry_id) {
                cloneEntry($entry_id, '', intval($_POST['clonesubsflag']));
            }
        }
        unset($_POST['clonesubsflag']);
    }

	$member_handler =& xoops_gethandler('member');
	$gperm_handler = &xoops_gethandler('groupperm');

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

	if($single AND !$entry AND !$overrideMulti) { // only adjust the active entry if we're not already looking at an entry, and there is no overrideMulti which can be used to display a new blank form even on a single entry form -- useful for when multiple anonymous users need to be able to enter information in a form that is "one per user" for registered users. -- the pressence of a cookie on the hard drive of a user will override other settings
		$entry = $single_result['entry'];
		$owner = getEntryOwner($entry, $fid);
		unset($owner_groups);
		//$owner_groups =& $member_handler->getGroupsByUser($owner, FALSE);
		$owner_groups = $data_handler->getEntryOwnerGroups($entry);
	}

	if($entry == "proxy") { $entry = ""; } // convert the proxy flag to the actual null value expected for new entry situations (do this after the single check!)
	$editing = is_numeric($entry); // will be true if there is an entry we're looking at already

    // super klugey! when going to a sub form the entry is not set correctly yet, so premature to do security check and update derived at this point!
    // Flow of this code from opening of function until main foreach of the $fids, needs to be straightened out for sure!
    // Possibly more than that since we leap off to render subforms in different places too. But the main ugly part is all this stuff that sets and overrides and overrides the fid and entry
    if(isset($_POST['goto_sfid']) AND is_numeric($_POST['goto_sfid']) AND $_POST['goto_sfid'] > 0) {
    } else {
        if(!$scheck = security_check($fid, $entry, $uid, $owner, $groups) AND !$viewallforms) {
            print "<p>" . _NO_PERM . "</p>";
            return;
        }

        if($entry AND $updateMainformDerivedAfterSubEntryDeletion) {
            formulize_updateDerivedValues($entry, $fid, $frid);
        }
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

	if (isset($_POST['form_submitted']) AND $_POST['form_submitted'] AND formulizePermHandler::user_can_edit_entry($fid, $uid, $entry)) {
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
	if(count((array) $sub_entries_synched)>0) {
		formulize_updateDerivedValues($entry, $fid, $frid);
	}

	// special use of $settings added August 2 2006 -- jwe -- break out of form if $settings so indicates
	// used to allow saving of information when you don't want the form itself to reappear
	if($settings == "{RETURNAFTERSAVE}" AND $_POST['form_submitted']) { return "returning_after_save"; }

    // need to add code here to switch some things around if we're on a subform for the first time (add)
	if(isset($_POST['goto_sfid']) AND is_numeric($_POST['goto_sfid']) AND $_POST['goto_sfid'] > 0 AND !isset($GLOBALS['formulize_inlineSubformFrid'])) {

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
        $go_back['page'] .= (isset($go_back['page']) AND $go_back['page'] !== '') ? ','.htmlspecialchars(strip_tags($_POST['formulize_prevPage'])) : htmlspecialchars(strip_tags($_POST['formulize_prevPage']));
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
	$config_handler = xoops_gethandler('config');
	$formulizeConfig = $config_handler->getConfigsByCat(0, $mid);
	// remove the all done button if the config option says 'no', and we're on a single-entry form, or the function was called to look at an existing entry, or we're on an overridden Multi-entry form
	$allDoneOverride = (!$formulizeConfig['all_done_singles'] AND (($single OR $overrideMulti OR $original_entry) AND !$_POST['target_sub'] AND !$_POST['goto_sfid'] AND !$_POST['deletesubsflag'] AND !$_POST['parent_form'])) ? true : false;
  global $formulize_displayingMultipageScreen;

	// if we're leaving the page now, draw the go back form and then activate it in js - super ugly!
  if((($formulize_displayingMultipageScreen === false AND $allDoneOverride)
        OR (isset($_POST['save_and_leave']) AND $_POST['save_and_leave']))
        AND $_POST['form_submitted']) {
		drawGoBackForm($go_back, $currentURL, $settings, $entry, $screen);
		print "<script type=\"text/javascript\">window.document.go_parent.submit();</script>\n";
		return;
	} else {

		// only do all this stuff below, the normal form displaying stuff, if we are not leaving this page now due to the all done button being overridden

		// we cannot have the back logic above invoked when dealing with a subform, but if the override is supposed to be in place, then we need to invoke it
		if(!$allDoneOverride AND !$formulizeConfig['all_done_singles'] AND ($_POST['target_sub'] OR $_POST['goto_sfid'] OR $_POST['deletesubsflag'] OR $_POST['parent_form']) AND ($single OR $original_entry OR $overrideMulti)) {
			$allDoneOverride = true;
		}

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

		// only loop through the fids that have elements we are going to show
		foreach($newFids as $this_fid) {

			if(!$scheck = security_check($this_fid, $entries[$this_fid][0]) AND !$viewallforms) {
				continue;
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
			if(!isset($form) OR !$form) {

                $firstform = 1;
                if(isset($passedInTitle) OR $titleOverride == 'all') {
                    $title = isset($passedInTitle) ? trans($passedInTitle) : "";
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
                    $form = new formulize_themeForm($title, 'formulize_mainform', "$currentURL", "post", true, $frid, $screen); // true is critical because that adds the security token!
                    // necessary to trigger the proper reloading of the form page, until Done is called and that form does not have this flag.
                    if (!isset($settings['ventry'])) {
                        $settings['ventry'] = 'new';
                    }
										$ventryElement = new XoopsFormHidden ('ventry', $settings['ventry']);
                    $form->addElement($ventryElement);
                }

                // include who the entry belongs to and the date
                // include acknowledgement that information has been updated if we have just done a submit
                // form_meta includes: last_update, created, last_update_by, created_by

                $breakHTML = "";

                if($titleOverride != "all") {
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
				}
			}

			if($titleOverride=="1" AND !$firstform) { // set onetooneTitle flag to 1 when function invoked to force drawing of the form title over again
				$title = trans(getFormTitle($this_fid));
				$form->insertBreakFormulize("<table><th>$title</th></table>","head");
			}

			formulize_benchmark("Before Compile Elements.");
			$form = compileElements($this_fid, $form, $prevEntry, $entries[$this_fid][0], $groups, $elements_allowed, $frid, (isset($sub_entries) ? $sub_entries : null), (isset($sub_fids) ? $sub_fids : null), $screen, $printViewPages, $printViewPageTitles);
			formulize_benchmark("After Compile Elements.");
		}	// end of for each 'newFids' ie: the forms that have elements to render

		// set some ugly state information that we're going to listen for in various places on the next pageload. :(
		$newHiddenElements = array();
		foreach($fids as $thisFid) {
			if(isset($entries[$thisFid][0]) AND $entries[$thisFid][0] AND !is_a($form, 'formulize_elementsOnlyForm')) {
				// two hidden fields encode the main entry id, the first difficult-to-use format is a legacy thing
				// the 'lastentry' format is more sensible, but is only available when there was a real entry, not 'new' (also a legacy convention)
				$newHiddenElements[] = new XoopsFormHidden ('entry'.$thisFid, $entries[$thisFid][0]);
				if(is_numeric($entries[$thisFid][0])) {
					$newHiddenElements[] = new XoopsFormHidden ('lastentry', $entries[$thisFid][0]);
				}
			}
		}
		if(isset($_POST['parent_form']) AND $_POST['parent_form']) { // if we just came back from a parent form, then set this flag so we'll know on the next pageload... legacy but has one key use?
			$newHiddenElements[] = new XoopsFormHidden ('back_from_sub', 1);
		}
		foreach($newHiddenElements as $nhe) {
			$form->addElement($nhe);
			unset($nhe); // still unpleasant pass by reference stuff going on in addElement, that we don't want to mess with at the moment, so unset and play nice
		}

		// if a new entry was created in a subform element that displays in multipage, then jump to that entry
		// VERY UGLY THAT WE ARE HAVING TO COMPILE ALL THE ELEMENTS JUST TO DETERMINE THIS??!! So messy and potentially slow!
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
												$_POST['formulize_prevPage'] = $_POST['formulize_currentPage'];
												$_POST['formulize_prevPage'] .= $screen ? '-'.$screen->getVar('sid') : '';
												unset($_POST['formulize_currentPage']); // want to make sure we land on page 1
					$GLOBALS['formulize_subformInstance'] = 100; // reset the subform instance counter since we're throwing away this page rendering!
												$GLOBALS['formulize_unsetSelectboxCaches'] = true; // totally horrible hack to get around the fact that subforms don't figure out there is a new entry to display until we get here. They should do this without having to render the elements first! We have to basically undo any caching of selectbox options that happened when we were fake rendering the page just to figure out what new entry had been created.
												$newSubEntryScreen_handler->render($subScreenObject, $newSubEntry, $settings);
												unset($GLOBALS['formulize_unsetSelectboxCaches']);
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
				$form = writeHiddenSettings($settings, $form, $entries, (isset($sub_entries) ? $sub_entries : null), $screen);
		}

		// draw in special params for this form, but only once per page
		global $formulize_subformHiddenFieldsDrawn;
		if ($formulize_subformHiddenFieldsDrawn != true) {
				$formulize_subformHiddenFieldsDrawn = true;
				$newHiddenElements = array(
					(new XoopsFormHidden ('target_sub', '')),
					(new XoopsFormHidden ('target_sub_frid', '')),
					(new XoopsFormHidden ('target_sub_fid', '')),
					(new XoopsFormHidden ('target_sub_mainformentry', '')),
					(new XoopsFormHidden ('target_sub_subformelement', '')),
					(new XoopsFormHidden ('target_sub_parent_subformelement', '')), // used to pickup declared subform element when modals are rendered?
					(new XoopsFormHidden ('target_sub_open_modal', '')),
					(new XoopsFormHidden ('target_sub_instance', '')),
					(new XoopsFormHidden ('numsubents', 1)),
					(new XoopsFormHidden ('del_subs', '')),
					(new XoopsFormHidden ('goto_sub', '')),
					(new XoopsFormHidden ('goto_sfid', ''))
				);
				foreach($newHiddenElements as $nhe) {
					$form->addElement($nhe);
					unset($nhe); // still unpleasant pass by reference stuff going on in addElement, that we don't want to mess with at the moment, so unset and play nice
				}
		}

    if(isset($sub_fids) AND count((array) $sub_fids) > 0) { // if there are subforms, then draw them in...only once we have a bonafide entry in place already
      // on the master.php page, draw in the subforms "raw", only if there has been no rendering of these subforms already in a regular subform element
			if(strstr(getCurrentURL(), 'modules/formulize/master.php')) {
				foreach($sub_fids as $subform_id) {
					if(!isset($GLOBALS['formulizeCatalogueOfRenderedSubforms']) OR !isset($GLOBALS['formulizeCatalogueOfRenderedSubforms']["$frid-$fid-$subform_id"])) {
						$subUICols = drawSubLinks($subform_id, $sub_entries, $uid, $groups, $frid, $mid, $fid, $entry);
						unset($subLinkUI);
						if(isset($subUICols['single'])) {
							$form->insertBreakFormulize($subUICols['single'], "even");
						} else {
							$subLinkUI = new XoopsFormLabel($subUICols['c1'], $subUICols['c2']); // no third param (name) since there's no element to construct it with
							$form->addElement($subLinkUI);
						}
					}
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
			$form = addSubmitButton($form, _formulize_SAVE, $go_back, $currentURL, $button_text, $settings, $entry, $fids, $formframe, $mainform, $entry, $elements_allowed, $allDoneOverride, $printall, $screen);
    	}

		$newHiddenElements = array();
		if(!$formElementsOnly) {
			// add flag to indicate that the form has been submitted
			$newHiddenElements[] = new XoopsFormHidden ('form_submitted', "1");
			if(isset($go_back['form']) AND $go_back['form']) { // if this is set, then we're doing a subform, so put in a flag to prevent the parent from being drawn again on submission
				$newHiddenElements[] = new XoopsFormHidden ('sub_fid', $fid);
				$newHiddenElements[] = new XoopsFormHidden ('sub_submitted', $entries[$fid][0]);
				$newHiddenElements[] = new XoopsFormHidden ('go_back_form', $go_back['form']);
				$newHiddenElements[] = new XoopsFormHidden ('go_back_entry', $go_back['entry']);
				$newHiddenElements[] = new XoopsFormHidden ('go_back_page', $go_back['page']);
				$newHiddenElements[] = new XoopsFormHidden ('go_back_subformElementId', $go_back['subformElementId']);
				$newHiddenElements[] = new XoopsFormHidden ('deletesubsflag', 0); // necessary so validation javascript will function
				$newHiddenElements[] = new XoopsFormHidden ('clonesubsflag', 0);
				$newHiddenElements[] = new XoopsFormHidden ('modalscroll', 0);
			} else {
				// drawing a main form...put in the scroll position flag
        $newHiddenElements[] = new XoopsFormHidden ('modalscroll', 0);
				$newHiddenElements[] = new XoopsFormHidden ('yposition', 0);
        $newHiddenElements[] = new XoopsFormHidden ('deletesubsflag', 0);
        $newHiddenElements[] = new XoopsFormHidden ('clonesubsflag', 0);
			}
			drawJavascript((isset($nosave) ? $nosave : null), $entry, $screen, $frid); // must be called after compileElements, for entry locking to work, and probably other things!
      $newHiddenElements[] = new xoopsFormHidden('save_and_leave', 0);
			// lastly, put in a hidden element, that will tell us what the first, primary form was that we were working with on this form submission
			$newHiddenElements[] = new XoopsFormHidden ('primaryfid', (isset($fids[0]) ? $fids[0] : 0));
		}
		foreach($newHiddenElements as $nhe) {
			$form->addElement($nhe);
			unset($nhe); // still unpleasant pass by reference stuff going on in addElement, that we don't want to mess with at the moment, so unset and play nice
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
		if(!isset($oneToOneMetaData) OR !is_array($oneToOneMetaData)) {
				$oneToOneMetaData = array();
		}
		if(count((array) $GLOBALS['formulize_renderedElementHasConditions'])>0) {
			$governingElements1 = compileGoverningElementsForConditionalElements($GLOBALS['formulize_renderedElementHasConditions'], $entries, $sub_entries);
			foreach($governingElements1 as $key=>$value) {
					$oneToOneElements[$key]	= false;
			}
			$formulize_governingElements = mergeGoverningElements($formulize_governingElements, $governingElements1);
		}
		// add in any onetoone elements that we need to deal with at the same time (in case their joining key value changes on the fly)
		if(count((array) $fids)>1) {
			foreach($fids as $thisFid) {
				if(isset($GLOBALS['formulize_renderedElementsForForm'][$thisFid])) {
					$relationship_handler = xoops_getmodulehandler('frameworks', 'formulize');
					$relationship = $relationship_handler->get($frid);
					$keyElement = false;
					foreach($relationship->getVar('links') as $thisLink) {
						if($thisLink->getVar('relationship') != 1 OR $thisLink->getVar('one2one_conditional') != 1 ) {
							// this loop will always land on the first one-to-one linkage involving the given form. If there are multiple one-to-one linkages involving the given from, only the first will be taken into account.
							continue;
						}
						if($thisLink->getVar('form1') == $thisFid) {
							$keyElement = $thisLink->getVar('key2');
							break;
						} elseif($thisLink->getVar('form2') == $thisFid) {
							$keyElement = $thisLink->getVar('key1');
							break;
						}
					}
					if($keyElement AND $keyElementObject = _getElementObject($keyElement)) {
						// prepare to loop through elements for the rendered entry, or 'new', if there is no rendered entry
						$entryToLoop = isset($entries[$thisFid][0]) ? $entries[$thisFid][0] : null;
						if(!$entryToLoop AND isset($GLOBALS['formulize_renderedElementsForForm'][$thisFid]['new'])) {
								$entryToLoop = 'new';
						}
						if(isset($GLOBALS['formulize_renderedElementsForForm'][$thisFid][$entryToLoop]) AND is_array($GLOBALS['formulize_renderedElementsForForm'][$thisFid][$entryToLoop])) {
							foreach($GLOBALS['formulize_renderedElementsForForm'][$thisFid][$entryToLoop] as $renderedMarkupName => $thisElement) {
								$GLOBALS['formulize_renderedElementHasConditions'][$renderedMarkupName] = $thisElement; // super ugly and kludgy, normally an array would be set here, but from this point forward, it's actually only the keys of this array that matter, so setting a single value is okay. Yuck. :(
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
			}
		}
		// if there are elements we need to pay attention to, draw the necessary javascript code
		// unless we're doing an embedded 'elements only form' -- unless we're doing that for displaying a subform entry specifically as its own thing (as part of a modal for example (and only example right now))
		if(count((array) $formulize_governingElements)> 0 AND (!$formElementsOnly OR (isset($formulize_displayingSubform) AND $formulize_displayingSubform == true))) {
			drawJavascriptForConditionalElements(array_keys($GLOBALS['formulize_renderedElementHasConditions']), $formulize_governingElements, $formulize_oneToOneElements, $formulize_oneToOneMetaData);
		}

        // need to always include, once, the subformelementid that is being displayed, regardless of whether there are more subs below this or not
        $idForForm = "";
        if(!$formElementsOnly) {
					$subformElementIdToUse = isset($_POST['goto_subformElementId']) ? intval($_POST['goto_subformElementId']) : 0;
					$goto_subformElementId = new XoopsFormHidden ('goto_subformElementId', $subformElementIdToUse); // switches to new one if we're drilling down
					$prev_subformElementId = new XoopsFormHidden ('prev_subformElementId', $subformElementIdToUse); // always remains the current one
					$form->addElement($goto_subformElementId);
					$form->addElement($prev_subformElementId);
					$idForForm = "id=\"formulizeform\""; // only use the master id when rendering a "normal" form, the master one on the page, not when rendering disembodied elements only forms!
        }

				writeToFormulizeLog(array(
					'formulize_event'=>'rendering-form',
					'user_id'=>($xoopsUser ? $xoopsUser->getVar('uid') : 0),
					'form_id'=>$fid,
					'screen_id'=>(is_object($screen) ? $screen->getVar('sid') : 0),
					'entry_id'=>$entry
				));

		print "<div $idForForm>".$form->render()."</div><!-- end of formulizeform -->"; // note, security token is included in the form by the xoops themeform render method, that's why there's no explicity references to the token in the compiling/generation of the main form object

        // floating save button
        if($printall != 2 AND isset($formulizeConfig['floatSave']) AND $formulizeConfig['floatSave'] AND !strstr($currentURL, "printview.php") AND !$formElementsOnly){
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
        if(isset($_POST['target_sub']) AND $_POST['target_sub'] AND !in_array($_POST['target_sub'], $formulize_subFidsWithNewEntries) AND count((array) $subs_to_del)==0 AND count((array) $subs_to_clone)==0) {
            list($elementq, $element_to_write, $value_to_write, $value_source, $value_source_form, $alt_element_to_write) = formulize_subformSave_determineElementToWrite($_POST['target_sub_frid'], $_POST['target_sub_fid'], $_POST['target_sub_mainformentry'], $_POST['target_sub']);
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
           (isset($subformSubEntryMap[$_POST['target_sub']]) OR ((count((array) $subs_to_del)>0 OR count((array) $subs_to_clone)>0) AND $_POST['target_sub_fid'] != $fid))
           AND isset($_POST['target_sub_open_modal']) AND $_POST['target_sub_open_modal'] == 'Modal') {
            if(count((array) $subs_to_del)>0 OR count((array) $subs_to_clone)>0) {
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

}

// add the submit button to a form
function addSubmitButton($form, $subButtonText, $go_back, $currentURL, $button_text, $settings, $entry, $fids, $formframe, $mainform, $cur_entry, $elements_allowed="", $allDoneOverride=false, $printall=0, $screen=null) { //nmc 2007.03.24 - added $printall

    global $xoopsUser;
    $fid = $fids[key($fids)]; // get first element in array, might not be keyed as 0 :(
    $uid = $xoopsUser ? $xoopsUser->getVar('uid') : 0;

	if($printall == 2) { // 2 is special setting in multipage screens that means do not include any printable buttons of any kind
		return $form;
	}

	if(strstr($currentURL, "printview.php")) { // don't do anything if we're on the print view
		return $form;
	}

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

	$config_handler = xoops_gethandler('config');
	$formulizeConfig = $config_handler->getConfigsByCat(0, getFormulizeModId());
	$rendered_buttons = "";
	if($pv_text_temp != "{NOBUTTON}" AND $formulizeConfig['formulizeShowPrintableViewButtons']) {

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
		$buttontray = new XoopsFormElementTray($rendered_buttons, "", 'button-controls'); // nmc 2007.03.24 - amended [nb: FormElementTray 'caption' is actually either 1 or 2 buttons]
	} else {
		$buttontray = new XoopsFormElementTray("", "", 'button-controls');
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

	// formulize_displayingMultipageScreen is set in formdisplaypages to indicate we're displaying a multipage form
	global $formulize_displayingMultipageScreen;
	$trayElements = $buttontray->getElements();
	if(count((array) $trayElements) > 0 OR ($rendered_buttons AND $formulize_displayingMultipageScreen)) {
		$form->addElement($buttontray);
	}
	return $form;

}

// this function draws in the hidden form that handles the All Done logic that sends user off the form
function drawGoBackForm($go_back, $currentURL, $settings, $entry, $screen) {
	if($go_back['url'] == "" AND !isset($go_back['form'])) { // there are no back instructions at all, then make the done button go to the front page of whatever is going on in pageworks
		print "<form name=go_parent action=\"$currentURL\" method=post>"; //onsubmit=\"javascript:verifyDone();\" method=post>";
		if(is_array($settings)) { writeHiddenSettings($settings, null, array(), array(), $screen, 'forceWrite'); }
		print "<input type=hidden name=lastentry value=$entry>";
		print "</form>";
	}
	if(isset($go_back['form']) AND $go_back['form']) { // parent form overrides specified back URL
		print "<form name=go_parent action=\"$currentURL\" method=post>"; //onsubmit=\"javascript:verifyDone();\" method=post>";
		print "<input type=hidden name=parent_form value=" . $go_back['form'] . ">";
		print "<input type=hidden name=parent_entry value=" . $go_back['entry'] . ">";
        print "<input type=hidden name=parent_page value=" . $go_back['page'] . ">";
        print "<input type=hidden name=parent_subformElementId value=" . $go_back['subformElementId'] . ">";
		print "<input type=hidden name=ventry value=" . $settings['ventry'] . ">";
		if(is_array($settings)) { writeHiddenSettings($settings, null, array(), array(), $screen, 'forceWrite'); }
		print "<input type=hidden name=lastentry value=$entry>";
		print "</form>";
	} elseif($go_back['url']) {
		print "<form name=go_parent action=\"" . $go_back['url'] . "\" method=post>"; //onsubmit=\"javascript:verifyDone();\" method=post>";
		if(is_array($settings)) { writeHiddenSettings($settings, null, array(), array(), $screen, 'forceWrite'); }
		print "<input type=hidden name=lastentry value=$entry>";
		print "</form>";
	}
}

// this function draws in the UI for sub links
function drawSubLinks($subform_id, $sub_entries, $uid, $groups, $frid, $mid, $fid, $entry,
	$customCaption = "", $customElements = "", $defaultblanks = 0, $showViewButtons = 1, $captionsForHeadings = 0,
	$overrideOwnerOfNewEntries = "", $mainFormOwner = 0, $hideaddentries = "", $subformConditions = null, $subformElementId = 0,
	$rowsOrForms = 'row', $addEntriesText = _formulize_ADD_ENTRIES, $subform_element_object = null, $firstRowToDisplay = 0, $numberOfEntriesToDisplay = null)
{

		$GLOBALS['formulizeCatalogueOfRenderedSubforms']["$frid-$fid-$subform_id"] = true;

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

    // if no sub entries, then go figure out sub entries again based on the correct main form id
    // This will return different sub entries when the mainform has a one to one form in the relationship, and then the subform is connected to the one to one. Sub is more than one hop away from main, so primary determination of entries does not pick up the sub entries
    if($subform_element_object AND (!is_array($sub_entries) OR (is_array($sub_entries[$subform_id]) AND count((array) $sub_entries[$subform_id]) == 0))) {
        $secondLinkResults = checkForLinks($frid, array($subform_element_object->getVar('id_form')), $subform_element_object->getVar('id_form'), array($subform_element_object->getVar('id_form') => array($entry)), true); // final true means only include entries from unified display linkages
        $sub_entries = $secondLinkResults['sub_entries'];
    }

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
    list($elementq, $element_to_write, $value_to_write, $value_source, $value_source_form, $alt_element_to_write) = formulize_subformSave_determineElementToWrite($frid, $fid, $entry, $target_sub_to_use);

    if (0 == strlen($element_to_write)) {
        error_log("Relationship $frid does not include subform $subform_id, when displaying the main form $fid.");
        $to_return = array("c1"=>"", "c2"=>"", "sigle"=>"");
        if (is_object($xoopsUser) and in_array(XOOPS_GROUP_ADMIN, $xoopsUser->getGroups())) {
            if (0 == $frid) {
                $to_return['single'] = "This subform cannot be shown because no relationship is active.";
            } else {
                $to_return['single'] = "This subform interface cannot be shown because the the form to be displayed (id: $subform_id) is not part of the active relationship (id: $frid). Check if the active screen is using the relationship, or just \"the form only.\", and check whether the relationship includes all the forms it should.";
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
        list($sub_entry_new,$sub_entry_written) = formulize_subformSave_writeNewEntry($element_to_write, $value_to_write, $fid, $frid, $_POST['target_sub'], $entry, $subformConditions, $overrideOwnerOfNewEntries, $mainFormOwner, $_POST['numsubents']);
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
		$subtitle = q("SELECT form_title FROM " . $xoopsDB->prefix("formulize_id") . " WHERE id_form = $subform_id");
        $subtitle = $subtitle[0]['form_title'];
	} else {
        $subtitle = $customCaption;
	}
    $helpText = ($subform_element_object AND $subform_element_object->getVar('ele_desc')) ? '<p class="subform-helptext">'.html_entity_decode($subform_element_object->getVar('ele_desc'),ENT_QUOTES).'</p>' : '';
    $col_one = "<p id=\"subform-caption-f$fid-sf$subform_id\" class=\"subform-caption form-label\"><b>" . trans($subtitle) . "</b></p>$helpText"; // <p style=\"font-weight: normal;\">" . _formulize_ADD_HELP;

	// preopulate entries, if there are no sub_entries yet, and prepop options is selected.
    // prepop will be based on the options in an element in the subform, and should also take into account the non OOM conditional filter choices where = is the operator.
	// does not populate when the mainform entry is new, so the subform interface will be empty until the form is saved, and then the subs will populate, and be linked to the just saved mainform entry
    if($entry AND $entry != 'new' AND count((array) $sub_entries[$subform_id]) == 0 AND $subform_element_object AND $subform_element_object->ele_value['subform_prepop_element']) {

        $optionElementObject = $element_handler->get($subform_element_object->ele_value['subform_prepop_element']);

        // gather filter choices first...
				if(is_array($subformConditions)) {
						$filterValues = getFilterValuesForEntry($subformConditions, $entry);
						$filterValues = $filterValues[key($filterValues)]; // subform element conditions are always on one form only so we just take the first set of values found (filterValues are grouped by form id)
				} else {
						$filterValues = array();
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
            if(is_array($optionElementFilterConditions) AND count((array) $optionElementFilterConditions)>1) {
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
                    secondPassWritingSubformEntryDefaults($subform_id,$writtenEntryId,array_keys($valuesToWrite));
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

	$drawnHeadersOnce = false;
    static $drawnSubformBlankHidden = array();

    $viewType = ($showViewButtons == 2 OR $showViewButtons == 3) ? 'Modal' : '';
    $viewType = stristr($_SERVER['SCRIPT_NAME'], 'subformdisplay-elementsonly.php') ? 'Modal' : $viewType;
    $addViewType = ($showViewButtons == 2) ? 'Modal' : '';
    $addViewType = stristr($_SERVER['SCRIPT_NAME'], 'subformdisplay-elementsonly.php') ? 'Modal' : $addViewType;

    // div for View button dialog
    $col_two = "<div id='subentry-dialog' style='display:none'></div>\n";

    // hacking in a filter for existing entries
    if($subform_element_object AND isset($subform_element_object->ele_value["UserFilterByElement"]) AND $subform_element_object->ele_value["UserFilterByElement"]) {
        $col_two .= "<br>"._formulize_SUBFORM_FILTER_SEARCH."<input type='text' name='subformFilterBox_$subformInstance' value='".htmlspecialchars(strip_tags(str_replace("'","&#039;",$_POST['subformFilterBox_'.$subformInstance])))."' onkeypress='javascript: if(event.keyCode == 13) validateAndSubmit();'/> <input type='button' value='"._formulize_SUBFORM_FILTER_GO."' onclick='validateAndSubmit();' /><br>";
	} else {
		$col_two .= "";
    }

    $deleteButton = "";
	if(((count((array) $sub_entries[$subform_id])>0 AND $sub_entries[$subform_id][0] != "") OR $sub_entry_new OR is_array($sub_entry_written)) AND !strstr($_SERVER['PHP_SELF'], "formulize/printview.php")) {
        if((!isset($subform_element_object->ele_value["show_delete_button"]) OR $subform_element_object->ele_value["show_delete_button"]) AND ($gperm_handler->checkRight("delete_own_entry", $subform_id, $groups, $mid) OR $gperm_handler->checkRight("delete_group_entries", $subform_id, $groups, $mid) OR $gperm_handler->checkRight("delete_other_entries", $subform_id, $groups, $mid))) {
            $deleteButton = "&nbsp;&nbsp;&nbsp;<input class='subform-delete-clone-buttons$subformElementId$subformInstance' style='display: none;' type=button name=deletesubs value='" . _formulize_DELETE_CHECKED . "' onclick=\"javascript:sub_del($subform_id, '$viewType', ".intval($_GET['subformElementId']).", '$fid', '$entry');\">";
        }
        if((!isset($subform_element_object->ele_value["show_clone_button"]) OR $subform_element_object->ele_value["show_clone_button"]) AND $gperm_handler->checkRight("add_own_entry", $subform_id, $groups, $mid)) {
            $deleteButton .= "&nbsp;&nbsp;&nbsp;<input class='subform-delete-clone-buttons$subformElementId$subformInstance' style='display: none' type=button name=clonesubs value='" . _formulize_CLONE_CHECKED . "' onclick=\"javascript:sub_clone($subform_id, '$viewType', ".intval($_GET['subformElementId']).", '$fid', '$entry');\">";
        }
	}

    // if the 'add x entries button' should be hidden or visible
    $hidingAddEntries = false;
    if ("hideaddentries" == $hideaddentries) {
        $hidingAddEntries = true;
    }
    $allowed_to_add_entries = false;
    if ("subform" == $hideaddentries OR 1 == $hideaddentries) {
        // for compatability, accept '1' which is the old value which corresponds to the new use-subform-permissions (saved as "subform")
        // user can add entries if they have permission on the sub form
        $allowed_to_add_entries = $gperm_handler->checkRight("add_own_entry", $subform_id, $groups, $mid);
    } else {
        // user can add entries if they have permission on the main form
        // the user should only be able to add subform entries if they can *edit* the main form entry, since adding a subform entry
        // is like editing the main form entry. otherwise they could add subform entries on main form entries owned by other users
        $allowed_to_add_entries = formulizePermHandler::user_can_edit_entry($fid, $uid, $entry);
    }

    if (($allowed_to_add_entries OR $deleteButton) AND !strstr($_SERVER['PHP_SELF'], "formulize/printview.php")) {
        $col_two .= "<div id='subform_button_controls_$subform_id$subformElementId$subformInstance' class='subform_button_controls'>";
        if ($allowed_to_add_entries AND !$hidingAddEntries AND count((array) $sub_entries[$subform_id]) == 1 AND $sub_entries[$subform_id][0] === "" AND $sub_single) {
            $col_two .= "<input type=button name=addsub value='". _formulize_ADD_ONE . "' onclick=\"javascript:add_sub('$subform_id', 1, ".$subformElementId.$subformInstance.", '$frid', '$fid', '$entry', '$subformElementId', '$addViewType', ".intval($_GET['subformElementId']).");\">";
        } elseif(!$sub_single) {
            $use_simple_add_one_button = (isset($subform_element_object->ele_value["simple_add_one_button"]) ? 1 == $subform_element_object->ele_value["simple_add_one_button"] : false);
            if($allowed_to_add_entries AND !$hidingAddEntries) {
                $col_two .= "<input type=button name=addsub value='".($use_simple_add_one_button ? trans($subform_element_object->ele_value['simple_add_one_button_text']) : _formulize_ADD)."' onclick=\"javascript:add_sub('$subform_id', jQuery('#addsubentries".$subform_id.$subformElementId.$subformInstance."').val(), ".$subformElementId.$subformInstance.", '$frid', '$fid', '$entry', '$subformElementId', '$addViewType', ".intval($_GET['subformElementId']).");\">";
            }
            if ($allowed_to_add_entries AND !$hidingAddEntries AND $use_simple_add_one_button) {
                $col_two .= "<input type=\"hidden\" name=addsubentries$subform_id$subformElementId$subformInstance id=addsubentries$subform_id$subformElementId$subformInstance value=\"1\">";
            } elseif($allowed_to_add_entries AND !$hidingAddEntries) {
                $col_two .= "<input type=text name=addsubentries$subform_id$subformElementId$subformInstance id=addsubentries$subform_id$subformElementId$subformInstance value=1 size=2 maxlength=2>";
                $col_two .= $addEntriesText;
            }
        }
        $col_two .= $deleteButton."</div>";
    }

	// construct the limit based on what is passed in via $numberOfEntriesToDisplay and $firstRowToDisplay
	$limitClause = "";
	$pageNav = "";
	$numberOfEntriesToDisplay = $numberOfEntriesToDisplay ? $numberOfEntriesToDisplay : $subform_element_object->ele_value['numberOfEntriesPerPage'];
	if($numberOfEntriesToDisplay AND $numberOfEntriesToDisplay < count($sub_entries[$subform_id])) {
		$firstRowToDisplay = intval($firstRowToDisplay);
		if(isset($_POST['formulizeFirstRowToDisplay']) AND isset($_POST['formulizeSubformPagingInstance']) AND $_POST['formulizeSubformPagingInstance'] == $subformElementId.$subformInstance) {
			$firstRowToDisplay = intval($_POST['formulizeFirstRowToDisplay']);
		}
		$limitClause = "LIMIT $firstRowToDisplay, ".intval($numberOfEntriesToDisplay);

		$lastPageNumber = ceil(count($sub_entries[$subform_id]) / $numberOfEntriesToDisplay);
		$firstDisplayPageNumber = ($firstRowToDisplay / $numberOfEntriesToDisplay) + 1 - 4;
		$lastDisplayPageNumber = ($firstRowToDisplay / $numberOfEntriesToDisplay) + 1 + 4;
		$firstDisplayPageNumber = $firstDisplayPageNumber < 1 ? 1 : $firstDisplayPageNumber;
		$lastDisplayPageNumber = $lastDisplayPageNumber > $lastPageNumber ? $lastPageNumber : $lastDisplayPageNumber;
		$pageNav = formulize_buildPageNavMarkup('gotoSubPage'.$subformElementId.$subformInstance, $numberOfEntriesToDisplay, $firstRowToDisplay, $firstDisplayPageNumber, $lastDisplayPageNumber, $lastPageNumber, _formulize_DMULTI_PAGE.":");
	}

	$col_two .= $pageNav; // figured out above

	if($rowsOrForms=="row" OR $rowsOrForms =='') {
		$col_two .= "<div class='formulize-subform-table-scrollbox'><table id=\"formulize-subform-table-$subform_id\" class=\"formulize-subform-table\">";
	} else {
		$col_two .= "";
		if(!strstr($_SERVER['PHP_SELF'], "formulize/printview.php")) {
            $styleDisplayNone = $rowsOrForms == 'flatform' ? "" : "style=\"display: none;\"";
            $accordionClassName = $rowsOrForms == 'flatform' ? "subform-flatform-container" : "subform-accordion-container";
			$col_two .= "<div id=\"subform-$subformElementId$subformInstance\" class=\"$accordionClassName\" subelementid=\"$subformElementId$subformInstance\" $styleDisplayNone>";
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

	if((!$_POST['form_submitted'] OR $ignoreFormSubmitted) AND count((array) $sub_entries[$subform_id]) == 0 AND $defaultblanks > 0 AND ($rowsOrForms == "row"  OR $rowsOrForms =='')) {

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
					$col_two .= drawRowSubformHeaders($headersToDraw, $headingDescriptions);
					$col_two .= "</tr>\n";
					$drawnHeadersOnce = true;
				}
				$col_two .= "<tr>\n<td>";
				$col_two .= "</td>\n";
				include_once XOOPS_ROOT_PATH . "/modules/formulize/include/elementdisplay.php";
				foreach($elementsToDraw as $thisele) {
					if($thisele) {
                        $unsetDisabledFlag = false;
                        if($subform_element_object AND in_array($thisele, explode(',',(string)$subform_element_object->ele_value['disabledelements']))) {
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
                            $textAreaClass = '';
                            if($elementObject = _getElementObject($thisele)) {
                                $textAreaClass = $elementObject->getVar('ele_type') == 'textarea' ? ' subform-textarea-element' : '';
                            }
							$col_two .= "<td class='formulize_subform_".$thisele.$textAreaClass."'>$col_two_temp</td>\n";
						} else {
							$col_two .= "<td></td>";
						}
					}
				}
				$col_two .= "</tr>\n";

		}

	} elseif(count((array) $sub_entries[$subform_id]) > 0) {

        if(intval($subform_element_object->ele_value["addButtonLimit"]) AND count((array) $sub_entries[$subform_id]) >= intval($subform_element_object->ele_value["addButtonLimit"])) {
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

        // apply any filter from the user if applicable
        // if no start state given, then show nothing
        $filterClause = "";
        if(isset($subform_element_object->ele_value["UserFilterByElement"]) AND $subform_element_object->ele_value["UserFilterByElement"]) {
            $matchingEntryIds = array();
            if(isset($_POST['subformFilterBox_'.$subformInstance]) AND $_POST['subformFilterBox_'.$subformInstance]) {
                $filterElementObject = $element_handler->get($subform_element_object->ele_value["UserFilterByElement"]);
                $matchingEntries = gatherDataset($subform_id, $filterElementObject->getVar('ele_handle').'/**/'.htmlspecialchars(strip_tags(trim($_POST['subformFilterBox_'.$subformInstance])), ENT_QUOTES), frid: 0);
                foreach($matchingEntries as $matchingEntry) {
                    $matchingEntryIds = array_merge($matchingEntryIds, getEntryIds($matchingEntry, $subform_id));
                }
                if(count($matchingEntryIds)>0) {
                    $filterClause = " AND sub.entry_id IN (".implode(",", $matchingEntryIds).")";
                } else {
                    $filterClause = " AND false ";
                }
            } elseif(!isset($subform_element_object->ele_value["FilterByElementStartState"]) OR $subform_element_object->ele_value["FilterByElementStartState"] == 0) {
                $filterClause = " AND false ";
            }
        }

		$sformObject = $form_handler->get($subform_id);
		$subEntriesOrderSQL = "SELECT sub.entry_id FROM ".$xoopsDB->prefix("formulize_".$sformObject->getVar('form_handle'))." as sub $joinClause WHERE sub.entry_id IN (".implode(",", $sub_entries[$subform_id]).") $filterClause ORDER BY $sortClause $limitClause";
		if($subEntriesOrderRes = $xoopsDB->query($subEntriesOrderSQL)) {
			$sub_entries[$subform_id] = array();
			while($subEntriesOrderArray = $xoopsDB->fetchArray($subEntriesOrderRes)) {
				$sub_entries[$subform_id][] = $subEntriesOrderArray['entry_id'];
			}
		}

		$currentSubformInstance = $subformInstance;

        // check if user can delete any subform entry
        if(!$userCouldDeleteOrClone = $gperm_handler->checkRight("add_own_entry", $subform_id, $groups, $mid) AND $deleteButton) {
            foreach($sub_entries[$subform_id] as $sub_ent) {
                if($userCouldDeleteOrClone = formulizePermHandler::user_can_delete_entry($subform_id, $uid, $sub_ent)) {
                    break;
                }
            }
        }

		foreach($sub_entries[$subform_id] as $sub_ent) {

            // validate that the sub entry has a value for the key field that it needs to (in cases where there is a sub linked to a main and a another sub (ie: it's a sub sub of a sub, and a sub of the main, at the same time, we don't want to draw in entries in the wrong place -- they will be part of the sub_entries array, because they are part of the dataset, but they should not be part of the UI for this subform instance!)
            // $element_to_write is the element in the subform that needs to have a value
            // Also, strange relationship config possible where the same sub is linked to the main via two fields. This should only be done when no new entries are being created! Or else we won't know which key element to use for writing, but anyway we can still validate the entries against both possible linkages
            if($element_to_write AND !$subFormKeyElementValue = $data_handler->getElementValueInEntry($sub_ent, $element_to_write)
               AND (!$alt_element_to_write OR !$altSubFormKeyElementValue = $data_handler->getElementValueInEntry($sub_ent, $alt_element_to_write))) {
                continue;
            }

			if($sub_ent != "") {

				if($rowsOrForms=='row' OR $rowsOrForms =='') {

					if(!$drawnHeadersOnce) {
						$col_two .= "<tr>";
                        if ($sub_ent !== "new" AND $deleteButton AND $userCouldDeleteOrClone AND !strstr($_SERVER['PHP_SELF'], "formulize/printview.php")) {
                            $col_two .= "<th class='subentry-delete-cell'></th>\n";
                        }
                        if(!$renderingSubformUIInModal AND $showViewButtons AND !strstr($_SERVER['PHP_SELF'], "formulize/printview.php")) { $col_two .= "<th class='subentry-view-cell'></th>\n"; }
						$col_two .= drawRowSubformHeaders($headersToDraw, $headingDescriptions);
						$col_two .= "</tr>\n";
						$drawnHeadersOnce = true;
					}
                    $subElementId = is_object($subform_element_object) ? $subform_element_object->getVar('ele_id') : 0;
					$col_two .= "<tr class='row-".$sub_ent."-".$subElementId."'>\n";
					// check to see if we draw a delete box or not
					if ($sub_ent !== "new" AND $deleteButton AND $userCouldDeleteOrClone AND !strstr($_SERVER['PHP_SELF'], "formulize/printview.php")) {
						// note: if the add/delete entry buttons are hidden, then these delete checkboxes are hidden as well
						$col_two .= "<td class='subentry-delete-cell'><input type=checkbox class='delbox' name=delbox$sub_ent value=$sub_ent onclick='showHideDeleteClone($subformElementId$subformInstance);'></input></td>";
					}
                    $additionalParams = $viewType == 'Modal' ? "'$frid', '$fid', '$entry', $subElementId, 0" : $subElementId;
                    if(!$renderingSubformUIInModal AND $showViewButtons AND !strstr($_SERVER['PHP_SELF'], "formulize/printview.php")) { $col_two .= "<td class='subentry-view-cell'><a href='' class='loe-edit-entry' id='view".$sub_ent."' onclick=\"javascript:goSub".$viewType."('$sub_ent', '$subform_id', $additionalParams);return false;\">&nbsp;</a></td>\n"; }
					include_once XOOPS_ROOT_PATH . "/modules/formulize/include/elementdisplay.php";
					foreach($elementsToDraw as $thisele) {
						if($thisele) {
                            $unsetDisabledFlag = false;
                            if(in_array($thisele, explode(',',(string) $subform_element_object->ele_value['disabledelements']))) {
                                $unsetDisabledFlag = !isset($GLOBALS['formulize_forceElementsDisabled']);
                                $GLOBALS['formulize_forceElementsDisabled'] = true;
                            }
							ob_start(function($string) { return $string; }); // set closure output buffer, so this element will never be catalogued as a conditional element. See catalogConditionalElement function for details.
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
                                $textAreaClass = '';
                                if($elementObject = _getElementObject($thisele)) {
                                    $textAreaClass = $elementObject->getVar('ele_type') == 'textarea' ? ' subform-textarea-element' : '';
                                }
								$col_two .= "<td class='formulize_subform_".$thisele."$textAlign$textAreaClass'>$col_two_temp</td>\n";
							} else {
								$col_two .= "<td></td>";
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
						$headerValues[] = undoAllHTMLChars($value);
					}
					$headerToWrite = implode(" &mdash; ", $headerValues);
					if(str_replace(" &mdash; ", "", $headerToWrite) == "") {
						$headerToWrite = _AM_ELE_SUBFORM_NEWENTRY_LABEL;
					}

					// check to see if we draw a delete box or not
					$deleteBox = "";
                    if ($sub_ent !== "new" AND $deleteButton AND $userCouldDeleteOrClone AND !strstr($_SERVER['PHP_SELF'], "formulize/printview.php")) {
					    $deleteBox = "<input type=checkbox class='delbox' name=delbox$sub_ent value=$sub_ent onclick='showHideDeleteClone($subformElementId$subformInstance);'></input>&nbsp;&nbsp;";
					}

					if(!strstr($_SERVER['PHP_SELF'], "formulize/printview.php")) {
                        $flatformClass = ($rowsOrForms == 'flatform') ? 'subform-flatform' : '';
						$col_two .= "<div class=\"subform-deletebox\">$deleteBox</div><div class=\"subform-entry-container $flatformClass\" id=\"subform-".$subform_id."-"."$sub_ent\"><p class=\"subform-header\">";
                        if($rowsOrForms == 'flatform') {
                            $col_two .= "<p class=\"flatform-name\">".$headerToWrite."</p>";
                        } else {
                            $col_two .= "<a class=\"accordion-name-anchor\" href=\"#\"><span class=\"accordion-name\">".$headerToWrite."</span></a>";
                        }
                        $col_two .= "</p><div class=\"accordion-content content\">";
					}
					ob_start();
					$GLOBALS['formulize_inlineSubformFrid'] = $frid;
                    if ($display_screen = get_display_screen_for_subform($subform_element_object)) {
                        $subScreen_handler = xoops_getmodulehandler('screen', 'formulize');
                        $subScreenObject = $subScreen_handler->get($display_screen);
                        $subScreen_handler = xoops_getmodulehandler($subScreenObject->getVar('type').'Screen', 'formulize');
                        $subScreenObject = $subScreen_handler->get($display_screen);
                        $subScreen_handler->render($subScreenObject, $sub_ent, null, true); // null is settings, true is elements only
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
		$col_two .= "</table></div>";
	} elseif(!strstr($_SERVER['PHP_SELF'], "formulize/printview.php")) {
        // close of the subform-accordion-container, unless we're on a printable view
		$col_two .= "</div>\n";
	}

	  $subformJS = '';
    if($rowsOrForms=='form') { // if we're doing accordions, put in the JS, otherwise it's flat-forms
        $subformJS .= "
            jQuery(document).ready(function() {
                jQuery(\"#subform-$subformElementId$subformInstance\").accordion({
                    heightStyle: 'content',
                    autoHeight: false, // legacy
                    collapsible: true, // sections can be collapsed
                    active: ";
                    if($_POST['target_sub_instance'] == $subformElementId.$subformInstance AND $_POST['target_sub'] == $subform_id) {
                        $subformJS .= count((array) $sub_entries[$subform_id])-$_POST['numsubents'];
                    } elseif(is_numeric($_POST['subform_entry_'.$subformElementId.$subformInstance.'_active'])) {
                        $subformJS .= $_POST['subform_entry_'.$subformElementId.$subformInstance.'_active'];
                    } else {
                        $subformJS .= 'false';
                    }
                    $subformJS .= ",
                    header: \"> div > p.subform-header\"
                });
                jQuery(\"#subform-$subformElementId$subformInstance\").fadeIn();
            });
        ";
    }
    $subformJS .= "
        function showHideDeleteClone(elementInstance) {
            var checkedBoxes = jQuery(\".delbox:checked\");
            if(jQuery(\".subform-delete-clone-buttons\"+elementInstance).css(\"display\") == \"none\" &&
            checkedBoxes.length > 0) {
                jQuery(\".subform-delete-clone-buttons\"+elementInstance).show(200);
            } else if(checkedBoxes.length == 0) {
                jQuery(\".subform-delete-clone-buttons\"+elementInstance).hide(200);
            }
        }
				function gotoSubPage".$subformElementId.$subformInstance."(firstRecordOrdinalOfPage) {
					jQuery.post(FORMULIZE.XOOPS_URL+'/modules/formulize/formulize_xhr_responder.php?uid='+FORMULIZE.XOOPS_UID+'&op=get_element_row_html&elementId=".$subformElementId."&entryId=".$entry."&fid=".$fid."&frid=".$frid."', { formulizeFirstRowToDisplay: firstRecordOrdinalOfPage, formulizeSubformPagingInstance: ".$subformElementId.$subformInstance." }, function(data) {
						if(data) {
							jQuery('#formulize-de_".$fid."_".$entry."_".$subformElementId."').empty();
							jQuery('#formulize-de_".$fid."_".$entry."_".$subformElementId."').append(JSON.parse(data).elements[0].data);
						}
					});
				}
    ";
    $col_two .= "
        <script type='text/javascript'>
            $subformJS
        </script>
    ";

    $edit_link = "";
    if (is_object($subform_element_object)) {
        global $xoopsUser;
        $show_element_edit_link = (is_object($xoopsUser) and in_array(XOOPS_GROUP_ADMIN, $xoopsUser->getGroups()));
        if ($show_element_edit_link) {
            $application_handler = xoops_getmodulehandler('applications', 'formulize');
            $apps = $application_handler->getApplicationsByForm($subform_id);
            $app = is_array($apps) ? $apps[0] : $apps;
						$appId = 0;
						if($app) {
            	$appId = $app->getVar('appid');
						}
            $edit_link = "<a class=\"formulize-element-edit-link\" tabindex=\"-1\" href=\"" . XOOPS_URL .
                "/modules/formulize/admin/ui.php?page=element&aid=$appId&ele_id=" .
                $subform_element_object->getVar("ele_id") . "\" target=\"_blank\">edit element</a>";
        }
    }

    $to_return['c1'] = $edit_link.$col_one;
    $to_return['c2'] = $col_two;
    $to_return['single'] = $edit_link.$col_one.$col_two;


    return $to_return;
}

function drawRowSubformHeaders($headersToDraw, $headingDescriptions) {
    $col_two = "";
    foreach($headersToDraw as $i=>$thishead) {
        if($thishead) {
            $headerHelpLink = $headingDescriptions[$i] ? "<a class='icon-help' href=\"#\" onclick=\"return false;\" alt=\"".strip_tags(htmlspecialchars($headingDescriptions[$i]))."\" title=\"".strip_tags(htmlspecialchars($headingDescriptions[$i]))."\"></a>" : "";
            $col_two .= "<th><p>$thishead $headerHelpLink</p></th>\n";
        }
    }
    return $col_two;
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
				$punames[] = (is_array($urowqforrealnames) AND !empty($urowqforrealnames)) ? ($urowqforrealnames[0] ? $urowqforrealnames[0] : $urowqforrealnames[1]) : ""; // use the uname if there is no full name
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

			for($i=0;$i<count((array) $unique_users);$i++)
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
			$proxylist->setExtra(" onchange='javascript:formulizechanged=1' ");
			$form->addElement($proxylist);
			return $form;
}

/**
 * Make a xoops form label element based on the string, and add it to the passed in form object
 * Only add a given string once
 * @param string string - the markup to add to the form
 * @param object form - the xoopsForm object to which all the elements are being added
 * @return object The xoopsForm object updated with the string added to it
 */
function addLabelElementWithStringToForm($string, $form) {
    static $cachedStrings = array();
    if(!in_array($string, $cachedStrings)) {
        $form->insertBreakFormulize($string, 'even', 'custom-content', 'custom-content');
        $cachedStrings[] = $string;
    }
    return $form;
}

// this function takes a formid and compiles all the elements for that form
// elements_allowed is NOT based off the display values.  It is based off of the elements that are specifically designated for the current displayForm function (used to display parts of forms at once)
// $title is the title of a grid that is being displayed
// called once per form included on the page
function compileElements($fid, $form, $prevEntry, $entry_id, $groups, $elements_allowed, $frid, $sub_entries, $sub_fids, $screen=null, $printViewPages=array(), $printViewPageTitles="") {

	if(is_array($elements_allowed) AND count($elements_allowed) == 1 AND !is_numeric($elements_allowed[0]) AND is_string($elements_allowed[0])) {
		return addLabelElementWithStringToForm($elements_allowed[0], $form);
	}

	global $xoopsDB, $xoopsUser;
	$entry_id = is_numeric($entry_id) ? $entry_id : "new"; // if there is no entry, ie: a new entry, then $entry_id is "" so when writing the entry value into decue_ and other elements that go out to the HTML form, we need to use the keyword "new"
	$element_handler = xoops_getmodulehandler('elements', 'formulize');
	$mid = getFormulizeModId();

	// set criteria for matching on display
	// set the basics that everything has to match
	$criteriaBase = new CriteriaCompo();
	$criteriaBase->add(new Criteria('ele_display', 1), 'OR');
	foreach($groups as $thisgroup) {
		$criteriaBase->add(new Criteria('ele_display', '%,'.formulize_db_escape($thisgroup).',%', 'LIKE'), 'OR');
	}
	if(is_array($elements_allowed) and count((array) $elements_allowed) > 0) {
		// if we're limiting the elements, then add a criteria for that (multiple criteria are joined by AND unless you specify OR manually when adding them (as in the base above))
		$criteria = new CriteriaCompo();
		$criteria->add(new Criteria('ele_id', "(".implode(",",array_filter($elements_allowed, 'is_numeric')).")", "IN"));
		$criteria->add($criteriaBase);
	} else {
		$criteria = $criteriaBase; // otherwise, just use the base
	}
    // exclude grids on mobile
    if(userHasMobileClient()) {
        $criteriaNoGrid = new CriteriaCompo();
        $criteriaNoGrid->add(new Criteria('ele_type', 'grid', '!='), 'AND');
        $criteria->add($criteriaNoGrid);
    }
	$criteria->setSort('ele_order');
	$criteria->setOrder('ASC');
	$elements = $element_handler->getObjects($criteria,intval($fid),true); // true makes the keys of the returned array be the element ids

	$GLOBALS['elementsInGridsAndTheirContainers'] = array();

	formulize_benchmark("Ready to loop elements.");

	// set the array to be used as the structure of the loop, either the passed in elements in order, or the elements as gathered from the DB
	// ignore passed in element order if there's a screen in effect, since we assume that official element order is authoritative when screens are involved
	// API should still allow arbitrary ordering, so $element_allowed can still be set manually as part of a displayForm call, and the order will be respected then
	if(!is_array($elements_allowed) OR $screen) {
		$element_order_array = $elements;
	} else {
		$element_order_array = $elements_allowed;
	}

	$uid = is_object($xoopsUser) ? $xoopsUser->getVar('uid') : 0;
	$owner = getEntryOwner($entry_id, $fid);

	foreach($element_order_array as $thisElement) {
		if(is_numeric($thisElement)) { // if we're doing the order based on passed in element ids...
			if(isset($elements[$thisElement])) {
				$elementObject = $elements[$thisElement]; // set the element object for this iteration of the loop
			} else {
				continue; // do not try to render elements that don't exist in the form!! (they might have been deleted from a multipage definition, or who knows what)
			}
			$this_ele_id = $thisElement; // set the element ID number
		} else { // else...we're just looping through the elements directly from the DB
			$elementObject = $thisElement; // set the element object
			$this_ele_id = $elementObject->getVar('ele_id'); // get the element ID number
		}

		$renderedElementMarkupName = "de_{$fid}_{$entry_id}_{$this_ele_id}";

		// check if this element is included in a grid, and if so, skip it
		if(isset($GLOBALS['elementsInGridsAndTheirContainers'][$this_ele_id])) {
			unset($GLOBALS['elementsInGridsAndTheirContainers'][$this_ele_id]);
			continue;
		}

		// check if we're at the start of a page, when doing a printable view of all pages (only situation when printViewPageTitles and printViewPages will be present), and if we are, then put in a break for the page titles
		if($printViewPages) {
				$currentPrintViewPage = 1;
			while((!isset($printViewPages[$currentPrintViewPage]) OR !in_array($this_ele_id, $printViewPages[$currentPrintViewPage]) ) AND $currentPrintViewPage <= count((array) $printViewPages)) {
				$currentPrintViewPage++;
			}
			if(isset($printViewPages[$currentPrintViewPage]) AND $this_ele_id == $printViewPages[$currentPrintViewPage][0]) {
				$form->insertBreak("<div id=\"formulize-printpreview-pagetitle\">" . $printViewPageTitles[$currentPrintViewPage] . "</div>", "head");
			}
		}

		$ele_type = $elementObject->getVar('ele_type');
		$ele_value = $elementObject->getVar('ele_value');

		$GLOBALS['sub_entries'] = $sub_entries; // set here for reference, just in case??

		// Option 1: subform element...
		if($ele_type == "subform" ) {
			$thissfid = $ele_value[0];
			if(!$thissfid) { continue; } // can't display non-specified subforms!
			list($allowed, $isDisabled) = elementIsAllowedForUserInEntry($elementObject, $entry_id, $groups, false, $renderedElementMarkupName, false);
			if($allowed) {
				$customCaption = $elementObject->getVar('ele_caption');
				$customElements = $ele_value[1] ? explode(",", $ele_value[1]) : "";
				if(isset($GLOBALS['formulize_inlineSubformFrid'])) {
					$newLinkResults = checkForLinks($GLOBALS['formulize_inlineSubformFrid'], array($fid), $fid, array($fid=>array($entry_id)), true); // final true means only include entries from unified display linkages
					$sub_entries = $newLinkResults['sub_entries'];
				}
                // 2 is the number of default blanks, 3 is whether to show the view button or not, 4 is whether to use captions as headings or not, 5 is override owner of entry, $owner is mainform entry owner, 6 is hide the add button, 7 is the conditions settings for the subform element, 8 is the setting for showing just a row or the full form, 9 is text for the add entries button
        $subUICols = drawSubLinks($thissfid, $sub_entries, $uid, $groups, $frid, $mid, $fid, $entry_id, $customCaption, $customElements, $ele_value[2], $ele_value[3], $ele_value[4], $ele_value[5], $owner, $ele_value[6], $ele_value[7], $this_ele_id, $ele_value[8], $ele_value[9], $elementObject);
				if(isset($subUICols['single'])) {
					$form->insertBreakFormulize($subUICols['single'], "even", $renderedElementMarkupName, $elementObject->getVar('ele_handle'));
				} else {
					$subLinkUI = new XoopsFormLabel($subUICols['c1'], $subUICols['c2'], $renderedElementMarkupName);
					$form->addElement($subLinkUI);
					unset($subLinkUI); // because addElement receives values by reference, we need to destroy it here, so if it is recreated in a subsequent iteration, we don't end up overwriting elements we've already assigned. Ack! Ugly!
				}
			}

		// Option 2: grid element...
		} elseif($ele_type == "grid") {
			list($allowed, $isDisabled) = elementIsAllowedForUserInEntry($elementObject, $entry_id, $groups, false, $renderedElementMarkupName, false);
			if($allowed) {
				$renderedGrid = renderGrid($elementObject, $entry_id, $prevEntry, $screen);
				if(is_object($renderedGrid)) {
					$form->addElement($renderedGrid);
					unset($renderedGrid); // because addElement receives values by reference, we need to destroy it here, so if it is recreated in a subsequent iteration, we don't end up overwriting elements we've already assigned. Ack! Ugly!
        } else {
					$form->insertBreakFormulize($renderedGrid, "head", $renderedElementMarkupName, $elementObject->getVar('ele_handle')); // head is the css class of the cell
				}
      } else {
				// A hidden grid. We need to catalogue the grid elements, and if they have conditions we need to put them in the conditional catalogue as well and make placeholders for them (which handles any validation JS generation)
				$gridCount = count(explode(",", $ele_value[1])) * count(explode(",", $ele_value[2]));
				foreach(elementsInGrid($ele_value[4], $fid, $gridCount) as $thisGridElementId) {
					catalogueGridElement($thisGridElementId, $entry_id, $elementObject, null, $prevEntry, $screen); // renderedElementMarkupName is the containing grid
				}
        if($placeholderElement = makePlaceholderForConditionalElement($elementObject, $entry_id, $prevEntry, $screen)) {
					$form->addElement($placeholderElement);
					unset($placeholderElement); // because addElement receives values by reference, we need to destroy it here, so if it is recreated in a subsequent iteration, we don't end up overwriting elements we've already assigned. Ack! Ugly!
				}
			}

		// Option 3: Not a subform, not a grid, try the standard approach with the displayElement function, see what happens...
                } else {
			$deReturnValue = displayElement("", $elementObject, $entry_id, false, $screen, $prevEntry, false, $groups);
			if(is_array($deReturnValue)) {
				$form_ele = $deReturnValue[0];
				$isDisabled = $deReturnValue[1];
            } else {
				$form_ele = $deReturnValue;
				$isDisabled = false;
			}

			// Option 3a: the element is not allowed (did not pass the 'elementIsAllowedForUserInEntry' check inside displayElement, when that happens displayElement returns 'not_allowed')
			if($form_ele == "not_allowed") {
				if($placeholderElement = makePlaceholderForConditionalElement($elementObject, $entry_id, $prevEntry, $screen)) {
					$form->addElement($placeholderElement);
					unset($placeholderElement); // because addElement receives values by reference, we need to destroy it here, so if it is recreated in a subsequent iteration, we don't end up overwriting elements we've already assigned. Ack! Ugly!
				}

			// Option 3b: element is a "text for display (two columns)"
			// or some other type of 'pass the markup' element...
		} elseif($ele_type == "ib" OR is_array($form_ele)) {
				$form->insertBreakFormulize(trans(stripslashes($form_ele[0])), $form_ele[1], $renderedElementMarkupName, $elementObject->getVar("ele_handle"));

			// Option 3c: regular element
		} else {
			$req = !$isDisabled ? intval($elementObject->getVar('ele_required')) : 0;
			$form->addElement($form_ele, $req);
			unset($form_ele); // apparently necessary for compatibility with PHP 4.4.0 -- suggested by retspoox, sept 25, 2005 -- because addElement receives values by reference, we need to destroy it here, so if it is recreated in a subsequent iteration, we don't end up overwriting elements we've already assigned. Ack! Ugly!
		}

			$GLOBALS['formulize_sub_fids'] = $sub_fids; // set here for reference, just in case??
		}

	}

	formulize_benchmark("Done looping elements.");

	// Add a hidden element to carry all the validation javascript that might be associated with elements rendered with elementdisplay.php, but not added to the main form themselves for whatever reason
	// This is a very complex, but necessary part of the form setup, because of the multiple times that displayForm might be called, multiple parts of forms that are rendered, as inline subforms, as all kinds of things, and we need to capture all the validation JS from everywhere, and ensure it is executed at the right level, as part of the normal page submission
	// Related, see the 'formuilze_elementsOnlyForm' check below, where we add things to the catalogue to retrieve later
	if(isset($GLOBALS['formulize_renderedElementsValidationJS'][strval($GLOBALS['formulize_thisRendering'])])) {
		$formulizeHiddenValidation = new XoopsFormHidden('validation', 1);
		// There is a catalogue of all the JS we've encountered. We keep track of this so that we only output each snippet of JS once.
		// The catalogue is made of the hashes of the JS
    global $fullJsCatalogue;
		foreach($GLOBALS['formulize_renderedElementsValidationJS'][strval($GLOBALS['formulize_thisRendering'])] as $thisValidation) { // grab all the validation code we have stored and attach it to this element
			if(trim($thisValidation) != "") {
				$catalogueKey = md5(trim($thisValidation));
				if(!isset($fullJsCatalogue[$catalogueKey])) {
					// add this key to the catalogue (the hash of the js), but only if there is more than one snippet that we're working with.
					// If there is only one snippet that we're working with, then this hidden validation element, and that element will have exactly the same hash, and that will interfere with the rendering of the validation JS when we're consulting the catalogue later. See the rendering of JS in the formulize form class.
					if(count((array) $GLOBALS['formulize_renderedElementsValidationJS'][strval($GLOBALS['formulize_thisRendering'])]) > 1) {
						$fullJsCatalogue[$catalogueKey] = true;
					}
					foreach(explode("\n", $thisValidation) as $thisValidationLine) {
						$formulizeHiddenValidation->customValidationCode[] = $thisValidationLine;
					}
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
	} elseif(isset($GLOBALS['formulize_elementsOnlyForm_validationCode']) AND count((array) $GLOBALS['formulize_elementsOnlyForm_validationCode']) > 0) {
		$elementsonlyvalidation = new XoopsFormHidden('elementsonlyforms', 1);
		$elementsonlyvalidation->customValidationCode = $GLOBALS['formulize_elementsOnlyForm_validationCode'];
		$form->addElement($elementsonlyvalidation);
	}

	return $form;

}

/**
 * If an element was put into the conditional element catalogue, then make a placeholder element for it, in case it will show up later when the user does something in the form
 * Also record any validation javascript that goes with this element, so we can pick that up when rendering the form
 * @param object $elementObject The formulize element object we're concerned about
 * @param int|string $entry_id The entry id in which the element is being rendered, or 'new' for a new element not yet saved.
 * @param array $prevEntry Optional. The values from the database for the elements in this entry.
 * @param object $screen Optional. The screen in which the element is being rendered. Possibly used in rare cases to determine exactly what js to generate.
 * @return string Returns a string, which should be added to the form object being worked on so that a placeholder for this element is rendered into the form. If the element is not in the conditional element catalogue, the string is empty.
 */
function makePlaceholderForConditionalElement($elementObject, $entry_id, $prevEntry = array(), $screen = null) {
	$placeholder = "";
	$renderedElementMarkupName = "de_{$elementObject->getVar('id_form')}_{$entry_id}_{$elementObject->getVar('ele_id')}";
	if(isset($GLOBALS['formulize_renderedElementHasConditions'][$renderedElementMarkupName])) {
		$placeholder = "{STARTHIDDEN}<<||>>".$renderedElementMarkupName."<<||>>".$elementObject->getVar('ele_handle');
		if(!isset($GLOBALS['formulize_renderedElementsValidationJS'][strval($GLOBALS['formulize_thisRendering'])][$renderedElementMarkupName])) {
			list($js, $markupName) = validationJSFromDisembodiedElementRender($elementObject, $entry_id, $prevEntry, $screen);
			if($js) {
				$containerId = isset($GLOBALS['elementsInGridsAndTheirContainers'][$elementObject->getVar('ele_id')]) ? $GLOBALS['elementsInGridsAndTheirContainers'][$elementObject->getVar('ele_id')] : $markupName;
				$GLOBALS['formulize_renderedElementsValidationJS'][strval($GLOBALS['formulize_thisRendering'])][$renderedElementMarkupName] = "if(jQuery('[name^=".$markupName."]').length && window.document.getElementById('formulize-".$containerId."').style.display != 'none') {\n".$js."\n}\n";
			}
		}
	}
	return $placeholder;
}

/**
 * Generate the validation JS for an element, by going through the rendering process and then extracting just the JS from the xoops form element object returned
 * @param object $elementObject The formulize element object we're concerned about
 * @param int|string $entry_id The entry id in which the element is being rendered, or 'new' for a new element not yet saved.
 * @param array $prevEntry Optional. The values from the database for the elements in this entry.
 * @param object $screen Optional. The screen in which the element is being rendered. Possibly used in rare cases to determine exactly what js to generate.
 * @return array Returns an array of the JS generated and the markup name of the element. If no validation JS or the element could not be rendered, then returns false.
 */
function validationJSFromDisembodiedElementRender($elementObject, $entry_id, $prevEntry, $screen) {
	$renderedElementMarkupName = "de_{$elementObject->getVar('id_form')}_{$entry_id}_{$elementObject->getVar('ele_id')}";
	$ele_value = $elementObject->getVar('ele_value');
	// get the value of this element for this entry as stored in the DB -- and unset any defaults if we are looking at an existing entry
	if($prevEntry) {
		$dataHandler = new formulizeDataHandler($fid);
		$ele_value = loadValue($elementObject, $entry_id, $prevEntry);
	}
	// get the validation code for this element, wrap it in a check for the table row being visible, and assign that to the global array that contains validation javascript that we need to add to the form
	$jsValidationRenderer = new formulizeElementRenderer($elementObject);
	if($jsValidiationCodeElement = $jsValidationRenderer->constructElement($renderedElementMarkupName, $ele_value, $entry_id, false, $screen, true)) { // last flag is "validation only" so the rendered knows things won't actually be output
		if(is_object($jsValidiationCodeElement) AND $js = $jsValidiationCodeElement->renderValidationJS()) {
			return array($js, $jsValidiationCodeElement->getName());
		}
	}
	return false;
}

/**
 * Setup the ele_value property of an element object based on the saved value for this element in this entry, if any
 * By setting the ele_value property to be what it would be, if the saved value was the default value for the element, then when the element is rendered using that ele_value property, it will show the saved value instead of the normal default value
 * @param object $element The formulize element object we're concerned about
 * @param int $entry_id The entry id in which the element is being rendered.
 * @param array $prevEntry The values from the database for the elements in this entry. A multidimensional array, with one key for an array of the handles and one for the values. Ugh.
 * @return array Returns the ele_value property that should be used for this element, based on the saved value for this element in this entry, if any.
 */
function loadValue($element, $entry_id, $prevEntry) {

	// validate that we have a valid element object
	if(!is_a($element, 'formulizeElement')) {
		return array();
	}
	$ele_value = $element->getVar('ele_value');
	// validate that we have prevEntry data, and we have an entry ID that we're working with
	// if not, just return the ele_value as is
	if(!is_array($prevEntry) OR !is_numeric($entry_id) OR $entry_id == 0) {
		return $ele_value;
	}

	// get the value of this element for this entry as stored in the DB, if any
	$value = "";
	$handle = $element->getVar('ele_handle');
	$key = array_search($handle, $prevEntry['handles'], true); // strict search to avoid problems comparing numbers to numbers plus text, ie: "1669" and "1669_copy"
	if($key !== false) {
		$value = $prevEntry['values'][$key];
	}
	// If the value is blank, and the element is required or the element has the use-defaults-when-blank option on
	// then do not load in saved value over top of ele_value, just return the default instead
	if($value === "" OR $value === null AND ($element->getVar('ele_use_default_when_blank') OR $element->getVar('ele_required'))) {
		return $ele_value;
	}

	// based on element type, swap in the value for this element in this entry...
	$type = $element->getVar('ele_type');
	if(file_exists(XOOPS_ROOT_PATH."/modules/formulize/class/".$type."Element.php")) {
		$customTypeHandler = xoops_getmodulehandler($type."Element", 'formulize');
		$ele_value = $customTypeHandler->loadValue($element, $value, $entry_id);
	}

	return $ele_value;
}

// THIS FUNCTION FORMATS THE DATETIME INFO FOR DISPLAY CLEANLY AT THE TOP OF THE FORM
// $dt should be a representation of a timestamp in the server timezone
function formulize_formatDateTime($dt) {
    global $xoopsConfig, $xoopsUser;
    $tzDiffSeconds = formulize_getUserServerOffsetSecs(timestamp: strtotime($dt));
	if($xoopsConfig['language'] == "french") {
		$return = setlocale(LC_TIME, "fr_FR.UTF8");
	}
	return _formulize_TEMP_AT . " " . strftime(dateFormatToStrftime(_MEDIUMDATESTRING), strtotime($dt)+$tzDiffSeconds);
}


// write the settings passed to this page from the view entries page, so the view can be restored when they go back
function writeHiddenSettings($settings, $form = null, $entries = array(), $sub_entries = array(), $screen = null, $forceWrite = false) {
    // only write the settings one time (might have multiple forms being rendered)
    static $formulize_settingsWritten = 0;
    if($formulize_settingsWritten AND !$forceWrite) {
        return $form;
    }
    $formulize_settingsWritten = 1;
	//unpack settings
	$sort = isset($settings['sort']) ? $settings['sort'] : null;
	$order = isset($settings['order']) ? $settings['order'] : null;
	$oldcols = isset($settings['oldcols']) ? $settings['oldcols'] : null;
	$currentview = isset($settings['currentview']) ? $settings['currentview'] : null;
	$global_search = isset($settings['global_search']) ? $settings['global_search'] : null;
  $pubfilters = (isset($settings['pubfilters']) AND is_array($settings['pubfilters'])) ? $settings['pubfilters'] : array();
	$searches = array();
	if (!isset($settings['calhidden']) OR !is_array($settings['calhidden']))
		$settings['calhidden'] = array();
	foreach($settings as $k=>$v) {
		if(substr($k, 0, 7) == "search_" AND $v != "") {
			$thiscol = substr($k, 7);
			$searches[$thiscol] = $v;
		}
	}
	//calculations:
	$calc_cols = isset($settings['calc_cols']) ? $settings['calc_cols'] : null;
	$calc_calcs = isset($settings['calc_calcs']) ? $settings['calc_calcs'] : null;
	$calc_blanks = isset($settings['calc_blanks']) ? $settings['calc_blanks'] : null;
	$calc_grouping = isset($settings['calc_grouping']) ? $settings['calc_grouping'] : null;

	$hlist = isset($settings['hlist']) ? $settings['hlist'] : null;
	$hcalc = isset($settings['hcalc']) ? $settings['hcalc'] : null;
	$lockcontrols = isset($settings['lockcontrols']) ? $settings['lockcontrols'] : null;
	$asearch = isset($settings['asearch']) ? $settings['asearch'] : null;
	$lastloaded = isset($settings['lastloaded']) ? $settings['lastloaded'] : null;

	// used for calendars...
	$calview = isset($settings['calview']) ? $settings['calview'] : null;
	$calfrid = isset($settings['calfrid']) ? $settings['calfrid'] : null;
	$calfid = isset($settings['calfid']) ? $settings['calfid'] : null;
	// plus there's the calhidden key that is handled below
	// plus there's the page number on the LOE screen that is handled below...
	// plus there's the multipage prev and current page

    $entries = is_array($entries) ? $entries : array();
    $sub_entries = is_array($sub_entries) ? $sub_entries : array();
    $allEntries = $entries + $sub_entries;

	// write hidden fields
	if($form) { // write as form objects and return form
		$newHiddenElements = array();
		$newHiddenElements[] = new XoopsFormHidden ('sort', $sort);
		$newHiddenElements[] = new XoopsFormHidden ('order', $order);
		$newHiddenElements[] = new XoopsFormHidden ('currentview', $currentview);
		$newHiddenElements[] = new XoopsFormHidden ('oldcols', $oldcols);
		$newHiddenElements[] = new XoopsFormHidden ('global_search', $global_search);
    $newHiddenElements[] = new XoopsFormHidden ('pubfilters', implode(",",$pubfilters));
		foreach($searches as $key=>$search) {
			$search_key = "search_" . $key;
			$search = str_replace("'", "&#39;", $search);
			$newHiddenElements[] = new XoopsFormHidden ($search_key, stripslashes($search));
		}
		$newHiddenElements[] = new XoopsFormHidden ('calc_cols', $calc_cols);
		$newHiddenElements[] = new XoopsFormHidden ('calc_calcs', $calc_calcs);
		$newHiddenElements[] = new XoopsFormHidden ('calc_blanks', $calc_blanks);
		$newHiddenElements[] = new XoopsFormHidden ('calc_grouping', $calc_grouping);
		$newHiddenElements[] = new XoopsFormHidden ('hlist', $hlist);
		$newHiddenElements[] = new XoopsFormHidden ('hcalc', $hcalc);
		$newHiddenElements[] = new XoopsFormHidden ('lockcontrols', $lockcontrols);
		$newHiddenElements[] = new XoopsFormHidden ('lastloaded', $lastloaded);
		$asearch = $asearch ? str_replace("'", "&#39;", $asearch) : "";
		$newHiddenElements[] = new XoopsFormHidden ('asearch', stripslashes($asearch));
		$newHiddenElements[] = new XoopsFormHidden ('calview', $calview);
		$newHiddenElements[] = new XoopsFormHidden ('calfrid', $calfrid);
		$newHiddenElements[] = new XoopsFormHidden ('calfid', $calfid);
		foreach($settings['calhidden'] as $chname=>$chvalue) {
			$newHiddenElements[] = new XoopsFormHidden ($chname, $chvalue);
		}
		$newHiddenElements[] = new XoopsFormHidden ('formulize_LOEPageStart', $_POST['formulize_LOEPageStart']);
		if(isset($settings['formulize_currentPage'])) { // drawing a multipage form...
			$currentPageToSend = $screen ? $settings['formulize_currentPage'].'-'.$screen->getVar('sid') : $settings['formulize_currentPage'];
			$prevPageToSend = $screen ? $settings['formulize_prevPage'].'-'.$settings['formulize_prevScreen'] : $settings['formulize_prevPage'];
			$newHiddenElements[] = new XoopsFormHidden ('formulize_currentPage', $currentPageToSend);
			$newHiddenElements[] = new XoopsFormHidden ('formulize_prevPage', $prevPageToSend);
			$newHiddenElements[] = new XoopsFormHidden ('formulize_doneDest', $settings['formulize_doneDest']);
			$newHiddenElements[] = new XoopsFormHidden ('formulize_buttonText', (isset($settings['formulize_buttonText']) ? $settings['formulize_buttonText'] : ""));
		}
		if($_POST['overridescreen']) {
			$newHiddenElements[] = new XoopsFormHidden ('overridescreen', intval($_POST['overridescreen']));
		}
		if(strlen($_POST['formulize_lockedColumns'])>0) {
			$newHiddenElements[] = new XoopsFormHidden ('formulize_lockedColumns', $_POST['formulize_lockedColumns']);
		}
		$newHiddenElements[] = new XoopsFormHidden ('formulize_originalVentry', $settings['formulize_originalVentry']);
		foreach($allEntries as $fid=>$fidEntries) {
				foreach($fidEntries as $entry_id) {
						if($entry_id) {
								$newHiddenElements[] = new XoopsFormHidden ('form_'.$fid.'_rendered_entry[]', $entry_id);
						}
				}
		}
		if($screen) {
				$newHiddenElements[] = new XoopsFormHidden ('formulize_renderedEntryScreen', $screen->getVar('sid'));
				$newHiddenElements[] = new XoopsFormHidden ('originalReloadBlank', $screen->getVar('reloadblank'));
		}
		$newHiddenElements[] = new XoopsFormHidden ('formulize_entry_lock_token', getEntryLockSecurityToken());
		$newHiddenElements[] = new XoopsFormHidden ('formulize_entriesPerPage', intval($_POST['formulize_entriesPerPage']));
		foreach($newHiddenElements as $nhe) {
			$form->addElement($nhe);
			unset($nhe); // still unpleasant pass by reference stuff going on in addElement, that we don't want to mess with at the moment, so unset and play nice
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
		$asearch = $asearch ? str_replace("\"", "&quot;", $asearch) : "";
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
			print "<input type=hidden name=formulize_buttonText value='".(isset($settings['formulize_buttonText']) ? $settings['formulize_buttonText'] : "")."'>";
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
            print "<input type=hidden name=originalReloadBlank value='" . $screen->getVar('reloadblank') . "'>";
        }
        print "<input type='hidden' name='formulize_entry_lock_token' value='".getEntryLockSecurityToken()."'>";
				print "<input type='hidden' name='formulize_entriesPerPage' value='".intval($_POST['formulize_entriesPerPage'])."'>";
	}
}


// draw in javascript for this form that is relevant to subforms
// $nosave indicates that the user cannot save this entry, so we shouldn't check for formulizechanged
function drawJavascript($nosave=false, $entryId=null, $screen=null, $frid=null) {

if($screen AND !$frid) {
	$frid = $screen->getVar('frid');
}

global $xoopsUser, $xoopsConfig, $actionFunctionName, $formulizeRemoveEntryIdentifier;

static $drawnJavascript = false;
if($drawnJavascript) {
	return;
}

// thanks https://code.tutsplus.com/tutorials/generate-random-alphanumeric-strings-in-php--cms-32132
$permitted_chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
$actionFunctionName = substr(str_shuffle($permitted_chars), 0, random_int(15,20));
$actionPart1 = substr(str_shuffle($permitted_chars), 0, random_int(15,20));
$actionPart2 = substr(str_shuffle($permitted_chars), 0, random_int(15,20));

// saving message
if($xoopsConfig['theme_set']=='formulize_standalone') {
    print "<div id=savingmessage style=\"display: none; position: absolute; width: 100%; right: 0px; text-align: center; padding-top: 50px; z-index: 100;\">\n";
} else {
    print "<div id=savingmessage style=\"display: none;\">\n";
}
global $xoopsConfig;
if ( file_exists(XOOPS_ROOT_PATH."/modules/formulize/images/saving-".$xoopsConfig['language'].".gif") ) {
    print "<img src=\"" . XOOPS_URL . "/modules/formulize/images/saving-" . $xoopsConfig['language'] . ".gif\">\n";
} else {
    print "<img src=\"" . XOOPS_URL . "/modules/formulize/images/saving-english.gif\">\n";
}
print "</div>\n";

$uid = $xoopsUser ? $xoopsUser->getVar('uid') : 0;

// Thanks to https://stackoverflow.com/a/19525750/16121604 for this incredible snippet that allows show/hide events to apply to divs. wow. And why is it we need a snippet to make this happen? Fuck javascript.
print "
<script type='text/javascript'>
	(function ($) {
	  $.each(['show', 'hide'], function (i, ev) {
	    var el = $.fn[ev];
	    $.fn[ev] = function () {
	      this.trigger(ev);
	      return el.apply(this, arguments);
	    };
	  });
	})(jQuery);

$formulizeRemoveEntryIdentifier

initialize_formulize_xhr();
var formulizechanged=0;
var formulize_javascriptFileIncluded = new Array();
var formulize_xhr_returned_check_for_unique_value = new Array();
var FORMULIZE = {
	XOOPS_URL : \"".XOOPS_URL."\",
	XOOPS_UID : ".($xoopsUser ? $xoopsUser->getVar('uid') : 0).",
	SCREEN_ID : ".($screen ? $screen->getVar('sid') : 0).",
	FRID : ".intval($frid)."
}
";
$split = random_int(8, strlen(getCurrentURL())-2);
print "var $actionPart1 = \"".str_replace('"', '%22', substr(getCurrentURL(), 0, $split))."\";\n";

if(isset($GLOBALS['formulize_fckEditors'])) {
	print "function FCKeditor_OnComplete( editorInstance ) { \n";
	print " editorInstance.Events.AttachEvent( 'OnSelectionChange', formulizeFCKChanged ) ;\n";
	print "}\n";
	print "function formulizeFCKChanged( editorInstance ) { \n";
	print "  formulizechanged=1; \n";
	print "}\n";
}

// on first load, turn on rich text editors -- conditional loads are handled elsewhere
if(isset($GLOBALS['formulize_CKEditors'])) {

    foreach($GLOBALS['formulize_CKEditors'] as $editorID) {
        print "var CKEditors = {};\n";
    }

    print "
    function initializeCKEditor(editorID) {
        if(jQuery('#'+editorID).length > 0) {
            ClassicEditor
                .create( document.querySelector( '#'+editorID ) )
                .then( editor => {
                    CKEditors[editorID] = editor;
                    CKEditors[editorID].model.document.on('change:data', function() {
                        formulizechanged = 1;
                    });
                    jQuery('#'+editorID).attr('name', 'useCKEditor');
                    jQuery(\"<input type='hidden' value='' name='\"+editorID.replace('_tarea', '')+\"' id='hidden_\"+editorID+\"' />\").appendTo(jQuery('#'+editorID).parent());
                    })
                .catch( error => {
                    console.error( error );
                } );
        }
    }";

    print "
    jQuery(document).ready(function () {
    ";

    foreach($GLOBALS['formulize_CKEditors'] as $editorID) {
        print "initializeCKEditor('$editorID');\n";
    }

    print "
    });

    function updateCKEditors() {";

        foreach($GLOBALS['formulize_CKEditors'] as $editorID) {
            print "
            if(jQuery('#$editorID').length > 0) {
                jQuery('#hidden_$editorID').val(CKEditors['$editorID'].getData().replace(\"'\", '&#039;'));
            }";
        }

    print "
    }\n";

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

// a bit hacky... check the intval of the currentPage and the prevPage, prevPage may be (always is?) "page number hyphen screen id number"
// so if someone jumps from one screen to another but lands on same ordinal page, this will be true, but really it's false because they're different screens
if($entryId != 'new' AND isset($_POST['yposition']) AND
   intval($_POST['yposition'])>0 AND
   (!isset($_POST['formulize_currentPage']) OR intval($_POST['formulize_currentPage']) == intval($_POST['formulize_prevPage']))
   ) {
    if($xoopsConfig['theme_set']=='Anari') {
        // requires the formulize_pageShown event to have been created and fired. The theme.html file does this in the Anari theme.
        print "
        window.addEventListener('formulize_pageShown', function () {
            jQuery('.main-content').scrollTop(".intval($_POST['yposition']).");
        });
        ";
    } else {
        print "
        jQuery(document).ready(function () {
            jQuery(window).load(function() {";
            // if the yposition is negative, then it's an offset of the formulizeform element so...
            // get the parents of the formulizeform div, and presumably only one of them is scrollable! And set the scroll position based on the current "top" value of formulizeform, plus the previous offset of formulize form which was sent in POST
            if(intval($_POST['yposition'])<0) {
                print "
                jQuery('#formulizeform').parents().each(function() {
                    if(jQuery(this)[0].scrollHeight > jQuery(this)[0].clientHeight) {
                        jQuery(this).scrollTop(jQuery('#formulizeform').offset().top + ".intval($_POST['yposition']*-1).");
                    }
                });";
            // otherwise, just set the scrollTop of the window...after a delay because some other events might still be executing and altering the height of the page
            } elseif($_POST['yposition']>0) {
                print "
                setTimeout(function() { jQuery(window).scrollTop(".intval($_POST['yposition'])."); }, 200);";
            }
            print "
            });
        });
        ";
    }
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
        if(jQuery(this).val().match(/[a-z]/i) !== null && jQuery(this).val() != '{ID}' && jQuery(this).val() != '{SEQUENCE}') {
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
		validate = window.formulizeExtraFormValidation(validate);
	}
	if(validate) {
        if(typeof updateCKEditors === 'function') { updateCKEditors(); }
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
        <?php if($xoopsConfig['theme_set']=='Anari') { ?>
            jQuery('#yposition').val(jQuery('.main-content').scrollTop());
        <?php } else { ?>
            if(jQuery(window).scrollTop()) {
                jQuery('#yposition').val(jQuery(window).scrollTop());
            } else {
                jQuery('#yposition').val((jQuery('#formulizeform').offset().top));
            }
        <?php } ?>
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
    window.document.getElementById('savingmessage').style.display = 'flex';
    <?php if($xoopsConfig['theme_set']=='formulize_standalone') { ?>
    window.scrollTo(0,0);
    <?php } ?>
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
if(count((array) $entriesThatHaveBeenLockedThisPageLoad)>0) {
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
		window.document.formulize_mainform.submit();
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
        if(formulizechanged == 0) {
            jQuery(\"input[name^='decue_']\").remove();
        }
        validateAndSubmit();
    }
}\n";

print "	function sub_del(sfid, type, parentSubformElement, fid, entry) {
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
            if(formulizechanged == 0) {
                jQuery(\"input[name^='decue_']\").remove();
            }
            validateAndSubmit();
        }
    } else {
        return false;
    }
}\n";

print "	function sub_clone(sfid, type, parentSubformElement, fid, entry) {
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
        if(formulizechanged == 0) {
            jQuery(\"input[name^='decue_']\").remove();
        }
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
        position: { my: "center", at: "center", of: window },
        open: function() {
            loadSub(jQuery(this));
            jQuery(this).parent().css('position', 'fixed');
            jQuery(this).parent().css('top', '10px');
            jQuery(this).parent().css('left', (parseInt(jQuery(this).parent().css('left').replace('px', '')) - 10)+'px');
            jQuery(this).css('overflow-y', 'auto !important');
            jQuery(this).css('height', (parseInt(jQuery(window).height())-100)+'px');
        },
        close: function() {
            removeModalEntryLocks();
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
                if(jQuery(this).attr('class') != 'subentry-view-cell' && jQuery(this).attr('class') != 'subentry-delete-cell') {
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

<?php
print "
function $actionFunctionName"."() {
   return $actionPart1 + $actionPart2;
}";
?>

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
            if(typeof updateCKEditors === 'function') { updateCKEditors(); }
            var formData = new FormData(jQuery('#formulize_modal')[0]);
            //var formData = subEntryDialog.children('form').serialize();
            jQuery.post({
                url: '<?php print XOOPS_URL; ?>/modules/formulize/include/readelements.php?fid='+subEntryDialog.data('fid')+'&frid='+subEntryDialog.data('frid'),
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                success: function() {
                    removeModalEntryLocks();
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

function goSub(ent, fid, subformElementId) {
    document.formulize_mainform.goto_sub.value = ent;
    document.formulize_mainform.goto_sfid.value = fid;
    document.formulize_mainform.goto_subformElementId.value = subformElementId;
<?php
global $formulize_displayingMultipageScreen;
if($formulize_displayingMultipageScreen) {
print "		document.formulize_mainform.formulize_prevPage.value = document.formulize_mainform.formulize_currentPage.value;\n";
print "		document.formulize_mainform.formulize_currentPage.value = 1\n";
}
?>
    if(formulizechanged == 0) {
        jQuery("input[name^='decue_']").remove();
    }
    validateAndSubmit();
}

<?php
//added by Cory Aug 27, 2005 to make forms printable

print "var $actionPart2 = \"".str_replace('"', '&quot;', substr(getCurrentURL(), $split))."\";\n";
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

?>

function check_date_limits(element_id) {
    var date_input = jQuery("#"+element_id);
	var min_date = date_input.attr('min');
	var max_date = date_input.attr('max');
	var selected_date = new Date(date_input.val());
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
            if (selected_date < min_date) {
                // date is too far in the past
                selected_date = null;
                date_input.val('');
							min_date.setTime(min_date.getTime() + (min_date.getTimezoneOffset() * 60 * 1000));
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
            if (selected_date > max_date) {
                // date is too far in the future
                date_input.val('');
							max_date.setTime(max_date.getTime() + (max_date.getTimezoneOffset() * 60 * 1000));
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
// replace the history and URL with a more canonical URL that is human readable, if alternate URLs are in effect
if($code = updateAlternateURLIdentifierCode($screen, $entryId)) {
    print "\n$code\n";
}

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

    print "</script>
		<script type='text/javascript' src='".XOOPS_URL."/modules/formulize/include/js/autocomplete.js'></script>
		";
    $drawnJavascript = true;
}

// THIS FUNCTION ACTUALLY DRAWS IN THE NECESSARY JAVASCRIPT FOR ALL ELEMENTS FOR WHICH ITS PROPERTIES ARE DEPENDENT ON ANOTHER ELEMENT
// PRIMARILY THIS APPLIES TO CONDITIONAL ELEMENTS, BUT ALSO USED IN ONE-TO-ONE RELATIONSHIPS
// conditionalElements is array of the elements (DOM ids, ie: de_fid_entryId_elementId) of the elements that have conditions.
// governingElements is array with keys that are the DOM ids of the elements that govern other elements, and the values are arrays of the elements being governed
function drawJavascriptForConditionalElements($conditionalElements, $governingElements, $oneToOneElements, $oneToOneMetaData=false) {

    $initCode = "
jQuery(document).ready(function() {
	var conditionalElements = new Array('".implode("', '",$conditionalElements)."');
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
			if(isset($oneToOneElements[$thisGoverningElement]) AND $oneToOneElements[$thisGoverningElement] == true) {
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

    foreach($conditionalElements as $ce) {
				// preload the current state of the HTML for any conditional elements that are currently displayed, so we can compare against what we get back when their conditions change
        $initCode .= "assignConditionalHTML('".$ce."');\n";
	}

    // setup the triggers
    foreach(array_keys($governingElements) as $ge) {
        $initCode .= "  jQuery(document).on('change', '[name=\"".$ge."\"]', function() {
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

	static $codeIncluded = false;

	if(!$codeIncluded) {
			$code = "
				<script type='text/javascript' src='".XOOPS_URL."/modules/formulize/include/js/conditional.js'></script>\n
				<div id='conditionalHTMLCapture' class='used-to-assign-html-then-read-innerHTML-so-we-always-get-standardized-conversion-of-quotes-urlencoding-etc' style='display: none;'></div>\n
			";
			$codeIncluded = true;
	}
	$code .= "<script type='text/javascript'>
$initCode
</script>\n";

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
	if($type == "checkbox" OR $type == "checkboxLinked" OR (anySelectElementType($type) AND $ele_value[1])) {
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

    if ($subform_element_object and is_a($subform_element_object, "formulizeElement")) {
        $ele_value = $subform_element_object->getVar('ele_value');
        if (isset($ele_value['display_screen']) AND intval($ele_value['display_screen']) > 0) {
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
            if (1 != count((array) $screen_type) or !isset($screen_type[0]['type']) or ("form" != $screen_type[0]['type'] AND "multiPage" != $screen_type[0]['type'])) {
                // selected screen is not valid for displaying the subform
                $selected_screen_id = null;
            }
        }
    }

    return $selected_screen_id;
}
