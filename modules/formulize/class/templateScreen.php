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

	// see formulizeScreen::screenTypeName
	public static function screenTypeName() {
		return defined('_AM_SCREEN_SETTINGS_KIND_TEMPLATE') ? _AM_SCREEN_SETTINGS_KIND_TEMPLATE : 'Template'; // not every language has it
	}

	// the template is the whole of the screen, so introductory text would go in it, not above it
	public static function screenTypeHasIntroductoryText() {
		return false;
	}


    function __construct() {
        parent::__construct();
        $this->initVar("savebuttontext", XOBJ_DTYPE_TXTBOX, NULL, false, 255);
        $this->initVar("donebuttontext", XOBJ_DTYPE_TXTBOX, NULL, false, 255);
        $this->initVar("donedest", XOBJ_DTYPE_TXTBOX, NULL, false, 255);
        $this->initVar("viewentryscreen", XOBJ_DTYPE_TXTBOX, NULL, false, 10);
    }
}

#[AllowDynamicProperties]
class formulizeTemplateScreenHandler extends formulizeScreenHandler {
    var $db;

    const FORMULIZE_CSS_FILE = "/modules/formulize/templates/css/formulize.css";
    const FORMULIZE_JS_FILE = "/modules/formulize/libraries/formulize.js";

    // Names an element's handle never becomes a variable under in a template screen's code, because the code
    // that runs the template still needs them after the handles are made into variables - see
    // run_template_php_code(). An element with one of these handles has no variable of its own there.
    const RESERVED_TEMPLATE_VARIABLES = array(
        'code_filename',                                          // include target below (the LFI guard)
        'templateScreenData', 'form_handler',                    // read after the loop / next outer pass
        'elementTypes', 'internalRecordIds', 'i', 'thisHandle',  // loop-carried, read after the assignment
        'preparedForTemplate', 'preparedWasFormatted', 'entry_id', // consumed by the purify pass below
        'formulize_reservedTemplateVars',                        // so a handle cannot disable this guard
    );

    /**
     * The forms whose elements a template screen's code gets a $handle variable for: the screen's form, or every
     * form of its relationship when the screen has one that includes its form.
     * @param object $screen The template screen.
     * @return array The form ids.
     */
    public static function formsWithHandleVariables($screen) {
        $formIds = array();
        if($screen->getVar('frid')) {
            $form_relationship_handler = xoops_getmodulehandler('frameworks', 'formulize');
            if($formRelationship = $form_relationship_handler->get($screen->getVar('frid'))) {
                $formIds = (array) $formRelationship->getVar('fids');
            }
        }
        if(!$screen->getVar('frid') OR !in_array($screen->getVar('fid'), $formIds)) {
            $formIds = array($screen->getVar('fid'));
        }
        return $formIds;
    }

    function __construct(&$db) {
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

    /**
     * Set the default values on a new template screen. The settings with fixed values come from the site's theme's
     * screen-defaults.json (see formulizeScreenHandler::getThemeScreenDefaults). The ones here are the words, which
     * are in the site's language, and what comes from the form.
     *
     * @param object $screen the new screen
     * @param object $formObject the form the screen is for
     * @return void
     */
    public function setDefaultScreenVars($screen, $formObject) {
        global $xoopsConfig;
        $this->applyDefaultScreenVars($screen, array(
            'fid' => $formObject->getVar('fid'),
            'frid' => -1,
            'type' => 'template',
            'theme' => $xoopsConfig['theme_set'],
            'savebuttontext' => _formulize_SAVE,
            'donebuttontext' => _formulize_SAVE_AND_LEAVE,
        ));
    }

    function insert($screen, $force=false) {

        $update = !$screen->getVar('sid') ? false : true;

        if(!$sid = parent::insert($screen)) {
            return false;
        }
        $screen->assignVar('sid', $sid);

        if (!$update) {
            $sql = sprintf("INSERT INTO %s (sid, donedest, savebuttontext, donebuttontext, viewentryscreen) VALUES (%u, %s, %s, %s, %s)", $this->db->prefix('formulize_screen_template'),
                $screen->getVar('sid'), $this->db->quoteString($screen->getVar('donedest')), $this->db->quoteString($screen->getVar('savebuttontext')), $this->db->quoteString($screen->getVar('donebuttontext')), $this->db->quoteString($screen->getVar('viewentryscreen')));
        } else {
            $sql = sprintf("UPDATE %s SET donedest = %s, savebuttontext = %s, donebuttontext = %s, viewentryscreen = %s WHERE sid = %u", $this->db->prefix('formulize_screen_template'),
                $this->db->quoteString($screen->getVar('donedest')), $this->db->quoteString($screen->getVar('savebuttontext')), $this->db->quoteString($screen->getVar('donebuttontext')), $this->db->quoteString($screen->getVar('viewentryscreen')), $sid);
        }
        $result = $this->db->query($sql);
        if (!$result) {
            print "Error: could not save template screen properly: ".$this->db->error()." for query: $sql";
            return false;
        }

        $success1 = true;
        if(isset($_POST['screens-custom_code'])) {
            $success1 = $this->write_custom_code_to_file(trim($_POST['screens-custom_code']), $screen, $screen->getVar('theme'));
        }
        $success2 = true;
        if(isset($_POST['screens-template'])) {
            $success2 = $this->write_template_to_file(trim($_POST['screens-template']), $screen, $screen->getVar('theme'));
        }
        if (!$success1 || !$success2) {
            return false;
        }

        return $sid;
    }


    function get($sid) {
        $sid = intval($sid);
        if ($screen = $this->takeTypedScreenHandoff($sid)) {
            return $screen;
        }
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


    function render($screen, $entry_id, $settings = array()) {

        if(!security_check($screen->getVar('fid'), $entry_id)) {
					if(!$done_dest = $screen->getVar('donedest')) {
						$done_dest = determineDoneDestinationFromURL($screen);
					}
					$done_dest = stripEntryFromDoneDestination($done_dest);
					$done_dest = substr($done_dest,0,4) == "http" ? $done_dest : "http://".$done_dest;
					icms::$logger->disableLogger();
					while(ob_get_level()) {
							ob_end_clean();
					}
					print "<script>window.location = \"$done_dest\";</script>";
					exit();
        }

        $previouslyRenderingScreen = (isset($GLOBALS['formulize_screenCurrentlyRendering']) AND $GLOBALS['formulize_screenCurrentlyRendering']) ? $GLOBALS['formulize_screenCurrentlyRendering'] : null;

        // SOME STANDARDS FOR HOW TO HANDLE 'SAVE' AND 'SAVE AND CLOSE' BUTTONS AND THE DONE DEST NEED TO BE DEVISED FOR TEMPLATE SCREENS!!

        global $xoTheme;
        if($xoTheme) {
            $cssVersion = formulize_get_file_version(self::FORMULIZE_CSS_FILE);
            $jsVersion = formulize_get_file_version(self::FORMULIZE_JS_FILE);
            formulize_uiAddToTheme();
            $xoTheme->addStylesheet(self::FORMULIZE_CSS_FILE . "?v=" . $cssVersion);
            $xoTheme->addScript(self::FORMULIZE_JS_FILE . "?v=" . $jsVersion);
        }

        $custom_code_filename = $this->custom_code_filename($screen);
        $template_filename = $this->template_filename($screen);

        $GLOBALS['formulize_screenCurrentlyRendering'] = $screen;

				print "<div class='formulize-template-screen-contents ck-content'>"; // wrap it in a div so it can be targetted, and include ck-content so right text editor stuff formats as expected

        // the screen is its code and its template. It can do without either one - code that draws everything itself,
        // or a template that needs no code - but not both
        $hasCode = file_exists($custom_code_filename);
        $hasTemplate = file_exists($template_filename);
        if ($hasCode OR $hasTemplate) {
            $vars = $this->run_template_php_code($screen, $custom_code_filename, $entry_id, $settings);
            global $xoopsTpl;
            foreach ($vars as $key => $value) {
                $xoopsTpl->assign($key, $value);
            }

            // what the code and the template use decides what the page needs for them to work
            $codeContents = ($hasCode ? file_get_contents($custom_code_filename) : '')."\n".($hasTemplate ? file_get_contents($template_filename) : '');

            // if the php code is not calling displayForm of some kind, then include necessary javascript
            if(!strstr($codeContents,' displayForm(') AND !strstr($codeContents,' displayFormPages(') AND !strstr($codeContents,'->render(') AND (strstr($codeContents,"\$saveButton") OR strstr($codeContents,"\$doneButton"))) {
                include_once XOOPS_ROOT_PATH.'/modules/formulize/include/formdisplay.php';
                $doneDestination = $screen->getVar('donedest');
                $doneDestination = substr($doneDestination,0,4) == 'http' ? $doneDestination : XOOPS_URL.$doneDestination;
                print "
                    <script>function xoopsFormValidate_formulize_mainform(leave, myform){return true;}</script>
                    <style> #savingmessage { display: none !important; } </style>".
                    drawJavascript(entryId: $entry_id, screen: $screen).
                    "<div id='formulizeform' style='display: none;'><form id='formulize_mainform' name='formulize_mainform' action='$doneDestination' method='post'>".
                    writeHiddenSettings($settings, null, array($screen->getVar('fid')=>array($entry_id)), array(), $screen).
                    "</form></div>
                ";
            }
            // if the designer is sending the user into an entry, then we need various apparatus in the page to make this work
            if(strstr($codeContents,"viewEntryLink(") OR strstr($codeContents,"viewEntryButton(") OR strstr($codeContents,"formulize_buildDateRangeFilter") OR strstr($codeContents,"formulize_buildFilter")) {

                // handle a click for an entry... hand off to the screen
                if(isset($_POST['ventry']) AND $_POST['ventry']) {

									// figure out the viewentryscreen
									$viewentryscreen = (isset($_POST['overridescreen']) AND is_numeric($_POST['overridescreen'])) ? $_POST['overridescreen'] : $screen->getVar('viewentryscreen');
									if($viewentryscreen == "none") {
											$form_handler = xoops_getmodulehandler('forms', 'formulize');
											$formObj = $form_handler->get($screen->getVar('fid'));
											$viewentryscreen = $formObj->getVar('defaultform');
									}
									if(!$viewentryscreen OR !is_numeric($viewentryscreen)) {
											exit("Error: could not determine the screen to use for displaying the form. Check if there is a default screen set for the form '".$formObj->getVar('title'));
									}

									$screenHandler = xoops_getmodulehandler('multiPageScreen', 'formulize');
									$screenObject = $screenHandler->get($viewentryscreen);
									if($_POST['ventry'] == 'single') { // new entry, so entry is blank/new
											$_POST['ventry'] = '';
											$screenObject->setVar('reloadblank', 0); // reload the entry they save
									}
									$screenHandler->render($screenObject, $_POST['ventry'], array());

                // otherwise, wrap the template screen in the necessary apparatus
                } else {
                    // setup the a basic form, with required hidden elements so that viewEntryLink etc will work
                    // have to include loadreport because the list of entries screen js which we reuse here, requires that in the dom :(
                    print "<form name='controls' method='post'>
                        <input type='hidden' name='ventry' value=''>
                        <input type='hidden' name='overridescreen' value=''>
                        <input type='hidden' name='loadreport' value=''>
						<input type='hidden' name='formulize_scrollx' value=''>
						<input type='hidden' name='formulize_scrolly' value=''>";
                    if ($hasTemplate) {
                        $xoopsTpl->display("file:".$template_filename);
                    }
                    print "</form>";
                    // pretty hacky! include the js for lists, so that the viewEntryLink etc will work
                    // can mimic add buttons by calling addNew('single') in js or addNew() for multiple entry
                    include_once XOOPS_ROOT_PATH.'/modules/formulize/include/entriesdisplay.php';
                    interfaceJavascript('',$screen->getVar('fid'), null, null, null, null, $screen);
                }

            // no viewEntryLink etc in the template, so away we go like normal, just the template
            } else {
                if ($hasTemplate) {
                    $xoopsTpl->display("file:".$template_filename);
                }
                if($code = updateAlternateURLIdentifierCode($screen, $entry_id)) {
                    print "\n<script>\n$code\n</script>\n";
                }
            }

        } else {
            echo "<p>Error: specified screen template does not exist.</p>";
        }

				print "</div>"; // close template screen div

        $GLOBALS['formulize_screenCurrentlyRendering'] = $previouslyRenderingScreen;

				setTitleOfPageInTemplate($entry_id, (is_object($screen) ? $screen : null));

    }


    function run_template_php_code($screen, $code_filename, $entry_id, $settings) {

        $saveButton = '<input type="button" class="formButton" name="submitx" id="submitx" onclick="javascript:validateAndSubmit();" value="'.htmlspecialchars(strip_tags($screen->getVar('savebuttontext'))).'">';
        $doneButton = '<input type="button" class="formButton" name="submit_save_and_leave" id="submit_save_and_leave" value="'.htmlspecialchars(strip_tags($screen->getVar('donebuttontext'))).'" onclick="javascript:validateAndSubmit(\'leave\');">';
        $doneDest = $screen->getVar('donedest');
        if(!$doneDest) {
            $doneDest = getCurrentURL();
        } elseif(!strstr($doneDest, XOOPS_URL)) {
            $doneDest = XOOPS_URL."$doneDest";
        }

        // Element-handle values are exposed to the admin-authored template code as $<handle> variables and
        // ALSO reach the Smarty template layer via get_defined_vars() below. Two ideas are kept separate:
        //  1. The PHP code sees the values we prepared: the list-formatted (already-safe) value for single
        //     scalars, and the original for arrays / multi-record values. We do NOT mangle what it sees.
        //  2. Whatever a value holds after the code runs is what reaches the template. For a value the
        //     code did NOT change that is still our raw prepared data, and a raw array / multi-record value
        //     is an unescaped HTML sink there. So after the code runs we purify the Smarty-bound COPY of
        //     each unchanged value that formatDataForList did not already make safe. A value the code DID
        //     change is its own output and is left as-is - the admin owns it.
        // Plus two reserved-name protections while building the $<handle> variables:
        //  - entry data must never clobber a local this method still relies on. The list below is only the
        //    names that are READ after a $$thisHandle assignment could overwrite them - either later in the
        //    handle loop (loop-carried state) or after it, through to the return. The critical one is
        //    $code_filename, the include target below: clobbering it would turn user data into a control
        //    path. Deliberately NOT listed: locals used only BEFORE the loop ($screen, $settings,
        //    $saveButton, ...) - a handle cannot reach back and affect them - and locals that are
        //    reassigned before their next read ($formObject, $thisFid, $entry, $snapshotEntry, $vars,
        //    $wasFormatted, $elementHandlerType), which self-heal. Add a name here only if you add a read
        //    of another local after the point the handle loop could overwrite it.
        //  - separately, the element-handler lookup is guarded with is_object() so a handle colliding with
        //    a "<type>Element" variable cannot turn a later method call into a fatal.
        $formulize_reservedTemplateVars = self::RESERVED_TEMPLATE_VARIABLES;
        $preparedForTemplate = array();  // handle => value we prepared (to detect code changes afterwards)
        $preparedWasFormatted = array(); // handle => true if formatDataForList already made it safe

        // make this a configuration option on the Templates tab!!!
        if (!empty($entry_id)) {
            $templateScreenData = gatherDataset($screen->getVar('fid'), filter: $entry_id, frid: $screen->getVar('frid'));
						$formIds = self::formsWithHandleVariables($screen);
						$form_handler = xoops_getmodulehandler('forms', 'formulize');
						foreach($formIds as $thisFid) {
            	$formObject = $form_handler->get($thisFid);
							$elementTypes = $formObject->getVar('elementTypes');
							$internalRecordIds = getEntryIds($templateScreenData[0], $thisFid);
							foreach($formObject->getVar('elementHandles') as $i=>$thisHandle) {
								// never let entry data overwrite this method's own control-flow locals (see note above)
								if(in_array($thisHandle, $formulize_reservedTemplateVars, true)) {
									continue;
								}
								$$thisHandle = getValue($templateScreenData[0], $thisHandle);
								$wasFormatted = false;
								// if we've got a single value from the mainform, or a one to one form, or a single entry in a subform, format it for display as if in a list
								if(count($internalRecordIds) == 1) {
									$elementHandlerType = $elementTypes[$i]."Element";
									if(!isset($$elementHandlerType) AND file_exists(XOOPS_ROOT_PATH."/modules/formulize/class/".$elementHandlerType.".php")) {
										$$elementHandlerType = xoops_getmodulehandler($elementHandlerType, 'formulize');
									}
									if(!is_array($$thisHandle) AND isset($$elementHandlerType) AND is_object($$elementHandlerType)) {
										$$thisHandle = $$elementHandlerType->formatDataForList($$thisHandle, $thisHandle, $internalRecordIds[0], 0);
										$wasFormatted = true;
									}
								}
								$preparedForTemplate[$thisHandle] = $$thisHandle;
								$preparedWasFormatted[$thisHandle] = $wasFormatted;
							}
						}
            $entry = $templateScreenData[0];
            $snapshotEntry = $entry;
        }

        if (file_exists($code_filename)) {
            include_once($code_filename);
        }

        $vars = get_defined_vars();

        // Purify the Smarty-bound copies of prepared values the template code did not change and that
        // formatDataForList did not already make safe (the raw arrays / multi-record values, plus any
        // single value with no element handler to format it). A value the code reassigned is its own
        // output and is left alone.
        foreach($preparedForTemplate as $purifyHandle => $purifyValue) {
            if(empty($preparedWasFormatted[$purifyHandle])
                AND array_key_exists($purifyHandle, $vars)
                AND $vars[$purifyHandle] === $purifyValue) {
                $vars[$purifyHandle] = formulize_purifyHtmlValueDeep($purifyValue, $purifyHandle, $entry_id);
            }
        }
        // $entry and $templateScreenData expose the same raw entry data to the template by another door
        // (eg: {$entry.handle}), bypassing the per-handle treatment above - so purify their copies too,
        // unless the template code replaced them.
        if(isset($snapshotEntry) AND array_key_exists('entry', $vars) AND $vars['entry'] === $snapshotEntry) {
            $vars['entry'] = formulize_purifyHtmlValueDeep($vars['entry'], '', $entry_id);
        }
        if(isset($templateScreenData) AND array_key_exists('templateScreenData', $vars) AND $vars['templateScreenData'] === $templateScreenData) {
            $vars['templateScreenData'] = formulize_purifyHtmlValueDeep($vars['templateScreenData'], '', $entry_id);
        }

        // keep this method's bookkeeping locals out of the template namespace
        unset($vars['formulize_reservedTemplateVars'], $vars['preparedForTemplate'], $vars['preparedWasFormatted'],
            $vars['wasFormatted'], $vars['snapshotEntry']);

        return $vars;
    }

    // Returns a cache folder name of shape "{ROOT_PATH}/modules/formulize/templates/screens/default/{$sid}/
    function cache_folder_name($screen, $theme="") {
        if(!$theme) {
            global $xoopsConfig;
            $theme = $xoopsConfig['theme_set'];
        }
        return XOOPS_ROOT_PATH."/modules/formulize/templates/screens/".$theme."/".$screen->getVar('sid')."/";
    }

    // Returns a custom code filename of shape "{ROOT_PATH}/modules/formulize/templates/screens/default/{$sid}/code.php"
    function custom_code_filename($screen, $theme="") {
        return $this->cache_folder_name($screen, $theme) . "code.php";
    }

    // Returns a template filename of shape "{ROOT_PATH}/modules/formulize/templates/screens/default/{$sid}/template.html"
    function template_filename($screen, $theme="") {
        return $this->cache_folder_name($screen, $theme) ."template.html";
    }

    // returns the code.php file, which is the screen's code itself - there is no other copy
    function getCustomCode($screen, $theme="") {
        static $templates = array();
        if (!isset($templates['custom_code'])) {
            $pathname = $this->custom_code_filename($screen, $theme);
            $templates['custom_code'] = file_exists($pathname) ? file_get_contents($pathname) : '';
        }
        return $templates['custom_code'];
    }

    // returns the template.html file, which is the screen's template itself - there is no other copy
    function getTemplateHtml($screen, $theme="") {
        static $templates = array();
        if (!isset($templates['template'])) {
            $pathname = $this->template_filename($screen, $theme);
            $templates['template'] = file_exists($pathname) ? file_get_contents($pathname) : '';
        }
        return $templates['template'];
    }

    /**
     * Give a template screen copies of its code and template from one theme, in a theme where it has neither yet.
     * A template screen has no default templates to fall back on, so without either one it cannot be drawn in that
     * theme. The files are copied as they are.
     *
     * @param object $screen The template screen.
     * @param string $fromTheme The theme to copy them from.
     * @param string $toTheme The theme to copy them to.
     * @return bool Whether everything there was to copy was copied.
     */
    function copyTemplatesToTheme($screen, $fromTheme, $toTheme) {
        if(file_exists($this->custom_code_filename($screen, $toTheme)) OR file_exists($this->template_filename($screen, $toTheme))) {
            return true;
        }
        $copied = true;
        if(file_exists($codeFile = $this->custom_code_filename($screen, $fromTheme))) {
            $copied = $this->write_custom_code_to_file(file_get_contents($codeFile), $screen, $toTheme);
        }
        if(file_exists($templateFile = $this->template_filename($screen, $fromTheme))) {
            $copied = ($this->write_template_to_file(file_get_contents($templateFile), $screen, $toTheme) AND $copied);
        }
        return $copied;
    }

    // Writes a code.php file in /modules/formulize/templates/screens/default/$sid/code.php
    // Code that is empty, or only the opening PHP tag the code box starts with, is no code, so it gets no file
    function write_custom_code_to_file($content, $screen, $theme="") {
        if (formulize_screenTemplateIsEmpty($content)) {
            return $this->remove_file($screen, "code.php", $theme);
        }
        return $this->write_to_file($content, $screen, "/code.php", $theme);
    }

    // Writes a template.html file in /modules/formulize/templates/screens/default/$sid/template.html
    // A template with nothing in it is no template, so it gets no file
    function write_template_to_file($content, $screen, $theme="") {
			if (trim($content) === '') {
				return $this->remove_file($screen, "template.html", $theme);
			}
			if($result = $this->write_to_file($content, $screen, "/template.html", $theme)) {
				$result = self::recompileTemplate($this->template_filename($screen, $theme));
			}
			return $result;
    }

    /**
     * Make Smarty compile a template screen's template again after its file has changed - when the admin page saves
     * it, and when renaming an element rewrites the handles in it. Smarty only notices a changed file by itself when
     * theme_fromfile is on, so otherwise the screen keeps showing the compiled copy of what the file used to say.
     *
     * Every compiled copy of the file is removed, not only the one touch() replaces: the copies are kept per theme set,
     * and when the admin theme differs from the site theme, webmasters and everyone else are shown different ones. A
     * copy that is gone is compiled again the next time the screen is shown. A new template engine does the touch,
     * rather than the page's own, so this works where no page is being drawn - a save, an MCP request, an update.
     *
     * @param string $filename The template's path.
     * @return bool Whether the template compiled.
     */
    public static function recompileTemplate($filename) {
        $tpl = new icms_view_Tpl();
        $resource = "file:".$filename;
        if(!$tpl->use_sub_dirs) {
            // compiled copies are named <compile id>^<the name Smarty gives the file>.php, one per compile id
            foreach((array) glob($tpl->compile_dir."/*".basename($tpl->_get_auto_filename($tpl->compile_dir, $resource)).".php") as $compiledCopy) {
                @unlink($compiledCopy);
            }
        }
        return $tpl->touch($resource) ? true : false;
    }

    // Removes one of a template screen's files, and the screen's folder once nothing is left in it
    function remove_file($screen, $name, $theme="") {
        $foldername = $this->cache_folder_name($screen, $theme);
        if (file_exists($foldername.$name) AND !unlink($foldername.$name)) {
            error_log("ERROR: Could not remove template screen file: ".$foldername.$name);
            return false;
        }
        formulize_removeScreenTemplateFolderIfEmpty($foldername);
        return true;
    }

    function write_to_file($content, $screen, $name, $theme="") {
        $foldername = $this->cache_folder_name($screen, $theme);

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
