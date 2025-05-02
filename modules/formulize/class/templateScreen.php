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

    function __construct() {
        parent::__construct();
        $this->initVar("custom_code", XOBJ_DTYPE_TXTAREA);
        $this->initVar("savebuttontext", XOBJ_DTYPE_TXTBOX, NULL, false, 255);
        $this->initVar("donebuttontext", XOBJ_DTYPE_TXTBOX, NULL, false, 255);
        $this->initVar("donedest", XOBJ_DTYPE_TXTBOX, NULL, false, 255);
        $this->initVar("template", XOBJ_DTYPE_TXTAREA);
        $this->initVar("viewentryscreen", XOBJ_DTYPE_TXTBOX, NULL, false, 10);
    }
}

class formulizeTemplateScreenHandler extends formulizeScreenHandler {
    var $db;

    const FORMULIZE_CSS_FILE = "/modules/formulize/templates/css/formulize.css";
    const FORMULIZE_JS_FILE = "/modules/formulize/libraries/formulize.js";

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

    function insert($screen) {

        $update = !$screen->getVar('sid') ? false : true;

        if(!$sid = parent::insert($screen)) {
            return false;
        }
        $screen->assignVar('sid', $sid);

        if (!$update) {
            $sql = sprintf("INSERT INTO %s (sid, custom_code, template, donedest, savebuttontext, donebuttontext, viewentryscreen) VALUES (%u, %s, %s, %s, %s, %s, %s)", $this->db->prefix('formulize_screen_template'),
                $screen->getVar('sid'), $this->db->quoteString(formulize_db_escape($screen->getVar('custom_code'))), $this->db->quoteString(formulize_db_escape($screen->getVar('template'))), $this->db->quoteString(formulize_db_escape($screen->getVar('donedest'))), $this->db->quoteString(formulize_db_escape($screen->getVar('savebuttontext'))), $this->db->quoteString(formulize_db_escape($screen->getVar('donebuttontext'))), $this->db->quoteString(formulize_db_escape($screen->getVar('viewentryscreen'))));
        } else {
            $sql = sprintf("UPDATE %s SET custom_code = %s, template = %s, donedest = %s, savebuttontext = %s, donebuttontext = %s, viewentryscreen = %s WHERE sid = %u", $this->db->prefix('formulize_screen_template'),
                $this->db->quoteString(formulize_db_escape($screen->getVar('custom_code'))), $this->db->quoteString(formulize_db_escape($screen->getVar('template'))), $this->db->quoteString(formulize_db_escape($screen->getVar('donedest'))), $this->db->quoteString(formulize_db_escape($screen->getVar('savebuttontext'))), $this->db->quoteString(formulize_db_escape($screen->getVar('donebuttontext'))), $this->db->quoteString(formulize_db_escape($screen->getVar('viewentryscreen'))), $sid);
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


    function render($screen, $entry_id, $settings = "") {

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

        // SOME STANDARDS FOR HOW TO HANDLE 'SAVE' AND 'SAVE AND LEAVE' BUTTONS AND THE DONE DEST NEED TO BE DEVISED FOR TEMPLATE SCREENS!!

        global $xoTheme;
        if($xoTheme) {
            $xoTheme->addStylesheet(self::FORMULIZE_CSS_FILE);
            $xoTheme->addScript(self::FORMULIZE_JS_FILE);
        }

        $custom_code_filename = $this->custom_code_filename($screen);
        $template_filename = $this->template_filename($screen);

        $GLOBALS['formulize_screenCurrentlyRendering'] = $screen;

				print "<div class='formulize-template-screen-contents ck-content'>"; // wrap it in a div so it can be targetted, and include ck-content so right text editor stuff formats as expected

        if (file_exists($custom_code_filename) and file_exists($template_filename)) {
            $vars = $this->run_template_php_code($screen, $custom_code_filename, $entry_id, $settings);
            global $xoopsTpl;
            foreach ($vars as $key => $value) {
                $xoopsTpl->assign($key, $value);
            }

            // if the php code is not calling displayForm of some kind, then include necessary javascript
            $codeContents = file_get_contents($custom_code_filename);
            if(!strstr($codeContents,' displayForm(') AND !strstr($codeContents,' displayFormPages(') AND !strstr($codeContents,'->render(') AND (strstr($codeContents,"\$saveButton") OR strstr($codeContents,"\$doneButton"))) {
                include_once XOOPS_ROOT_PATH.'/modules/formulize/include/formdisplay.php';
                $doneDestination = $screen->getVar('donedest');
                $doneDestination = substr($doneDestination,0,4) == 'http' ? $doneDestination : XOOPS_URL.$doneDestination;
                print "
                    <script>function xoopsFormValidate_formulize_mainform(leave, myform){return true;}</script>
                    <style> #savingmessage { display: none !important; } </style>".
                    drawJavascript().
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
                    $xoopsTpl->display("file:".$template_filename);
                    print "</form>";
                    // pretty hacky! include the js for lists, so that the viewEntryLink etc will work
                    // can mimic add buttons by calling addNew('single') in js or addNew() for multiple entry
                    include_once XOOPS_ROOT_PATH.'/modules/formulize/include/entriesdisplay.php';
                    interfaceJavascript('',$screen->getVar('fid'), null, null, null, null);
                }

            // no viewEntryLink etc in the template, so away we go like normal, just the template
            } else {
                $xoopsTpl->display("file:".$template_filename);
                if($code = updateAlternateURLIdentifierCode($screen, $entry_id)) {
                    print "\n<script>\n$code\n</script>\n";
                }
            }
						print "</div>"; // close template screen div

            // determine proper admin link
            $applications_handler = xoops_getmodulehandler('applications', 'formulize');
						$apps = $applications_handler->getApplicationsByForm($screen->getVar('fid'));
						if(is_array($apps) AND count($apps)>0) {
							$firstAppId = $apps[key($apps)]->getVar('appid');
						} else {
							$firstAppId = 0;
						}
            $url = XOOPS_URL . "/modules/formulize/admin/ui.php?page=screen&sid=".$screen->getVar('sid')."&fid=".$screen->getVar('fid')."&aid=".$firstAppId;
            $xoopsTpl->assign('modifyScreenUrl', $url);
        } else {
            echo "<p>Error: specified screen template does not exist.</p>";
        }
        $GLOBALS['formulize_screenCurrentlyRendering'] = $previouslyRenderingScreen;
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

        // make this a configuration option on the Templates tab!!!
        if (!empty($entry_id)) {
            $templateScreenData = getData($screen->getVar('frid'), $screen->getVar('fid'), $entry_id);
						$formIds = array();
						if($screen->getVar('frid')) {
							$form_relationship_handler = xoops_getmodulehandler('frameworks', 'formulize');
							$formRelationship = $form_relationship_handler->get($screen->getVar('frid'));
							$formIds = $formRelationship->getVar('fids');
						} else {
							$formIds = array($screen->getVar('fid'));
						}
						$form_handler = xoops_getmodulehandler('forms', 'formulize');
						foreach($formIds as $thisFid) {
            	$formObject = $form_handler->get($thisFid);
							$elementTypes = $formObject->getVar('elementTypes');
							$internalRecordIds = internalRecordIds($templateScreenData[0], $thisFid);
							foreach($formObject->getVar('elementHandles') as $i=>$thisHandle) {
								$$thisHandle = display($templateScreenData[0], $thisHandle);
								// if we've got a single value from the mainform, or a one to one form, or a single entry in a subform, format it for display as if in a list
								if(count($internalRecordIds) == 1) {
									$elementHandlerType = $elementTypes[$i]."Element";
									if(!isset($$elementHandlerType) AND file_exists(XOOPS_ROOT_PATH."/modules/formulize/class/".$elementHandlerType.".php")) {
										$$elementHandlerType = xoops_getmodulehandler($elementHandlerType, 'formulize');
									}
									if(!is_array($$thisHandle) AND isset($$elementHandlerType)) {
										$$thisHandle = $$elementHandlerType->formatDataForList($$thisHandle, $thisHandle, $internalRecordIds[0], 0);
									}
								}
							}
						}
            $entry = $templateScreenData[0];
        }

        include_once($code_filename);
        return get_defined_vars();
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

    // returns code.php file
    function getCustomCode($screen, $theme="") {
        static $templates = array();
        if (!isset($templates['custom_code'])) {
            $pathname = $this->custom_code_filename($screen, $theme);
            if (file_exists($pathname)) {
                $templates['custom_code'] = file_get_contents($pathname);
            } else {
                $templates['custom_code'] = $screen->getVar('custom_code');
                if (strlen($templates['custom_code']) > 0) {
                    $this->write_custom_code_to_file(htmlspecialchars_decode($templates['custom_code'], ENT_QUOTES), $screen, $theme);
                }
            }
        }
        return $templates['custom_code'];

    }

    // returns template.html file
    function getTemplateHtml($screen, $theme="") {
        static $templates = array();
        if (!isset($templates['template'])) {
            // there is no template saved in memory, read it from the file;
            $pathname = $this->template_filename($screen, $theme);
            if (file_exists($pathname)) {
                $templates['template'] = file_get_contents($pathname);
            } else {
                $templates['template'] = $screen->getVar('template');
                if (strlen($templates['template']) > 0) {
                    $this->write_template_to_file(htmlspecialchars_decode($templates['template'], ENT_QUOTES), $screen, $theme);
                }
            }
        }
        return $templates['template'];
    }

    // Writes a code.php file in /modules/formulize/templates/screens/default/$sid/code.php
    function write_custom_code_to_file($content, $screen, $theme="") {
        return $this->write_to_file($content, $screen, "/code.php", $theme);
    }

    // Writes a template.html file in /modules/formulize/templates/screens/default/$sid/template.html
    function write_template_to_file($content, $screen, $theme="") {
			if($this->write_to_file($content, $screen, "/template.html", $theme)) {
				// update cached copy
				include XOOPS_ROOT_PATH.'/header.php';
				global $xoopsTpl;
				return $xoopsTpl->touch("file:".$this->template_filename($screen));
			}
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
