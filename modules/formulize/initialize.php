<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2004 Freeform Solutions                  ##
##                Portions copyright (c) 2003 NS Tai (aka tuff)              ##
##                       <http://www.brandycoke.com/>                        ##
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
##  Author of this file: Freeform Solutions and NS Tai (aka tuff) and others ##
##  URL: http://www.brandycoke.com/                                          ##
##  Project: Formulize                                                       ##
###############################################################################



if (file_exists(XOOPS_ROOT_PATH.'/class/mail/phpmailer/class.phpmailer.php'))
    include_once XOOPS_ROOT_PATH.'/class/mail/phpmailer/class.phpmailer.php';

$GLOBALS['formulize_asynchronousFormDataInDatabaseReadyFormat'] = array();
$GLOBALS['formulize_asynchronousFormDataInAPIFormat'] = array();

$GLOBALS['formulize_subformInstance'] = 100;

$GLOBALS['formulize_displayingMultipageScreen'] = false; // later, will be set to the screen id if we're displaying a multipage screen, or just true if we're displaying a multipage form without a screen specified

global $xoopsDB, $myts, $xoopsUser, $xoopsModule, $xoopsTpl, $xoopsConfig, $renderedFormulizeScreen, $formulizeCanonicalURI;

$thisRendering = microtime(true); // setup a flag that is common to this instance of rendering a formulize page
if(!isset($prevRendering)) {
    $prevRendering = array();
}
$prevRendering[$thisRendering] = isset($GLOBALS['formulize_thisRendering']) ? $GLOBALS['formulize_thisRendering'] : "";
$GLOBALS['formulize_thisRendering'] = $thisRendering;

$fid = ((isset( $_GET['fid'])) AND is_numeric( $_GET['fid'])) ? intval( $_GET['fid']) : "" ;
$fid = ((isset($_POST['fid'])) AND is_numeric($_POST['fid'])) ? intval($_POST['fid']) : $fid ;

$frid = ((isset( $_GET['frid'])) AND is_numeric( $_GET['frid'])) ? intval( $_GET['frid']) : "" ;
$frid = ((isset($_POST['frid'])) AND is_numeric($_POST['frid'])) ? intval($_POST['frid']) : $frid ;

// gather $_GET['sid'] (screen)
if(isset($formulize_screen_id) AND is_numeric($formulize_screen_id)) {
  $sid = $formulize_screen_id;
} elseif(isset($_GET['sid']) AND is_numeric($_GET['sid'])) {
    $sid = $_GET['sid'];
} else {
    $sid="";
}

// get the global or group permission
$groups = $xoopsUser ? $xoopsUser->getGroups() : array(0=>XOOPS_GROUP_ANONYMOUS);
$uid = $xoopsUser ? $xoopsUser->getVar('uid') : 0;
$mid = getFormulizeModId();
$gperm_handler = &xoops_gethandler('groupperm');
$view_globalscope = $gperm_handler->checkRight("view_globalscope", $fid, $groups, $mid);
$view_groupscope = $gperm_handler->checkRight("view_groupscope", $fid, $groups, $mid);

$config_handler =& xoops_gethandler('config');
$formulizeConfig =& $config_handler->getConfigsByCat(0, $mid);

// query added Oct 2013
// get the default menu link for the current user, and set the fid or sid based on it

if( !$fid AND !$sid) {
	include_once XOOPS_ROOT_PATH."/modules/formulize/class/applications.php";
	$includeMenuURLs = true;
	list($fid,$sid,$defaultMenuLinkUrl) = formulizeApplicationMenuLinksHandler::getDefaultScreenForUser();
	if($defaultMenuLinkUrl) {
		header('Location: '.$defaultMenuLinkUrl);
		exit();
	}
}

$screen_handler =& xoops_getmodulehandler('screen', 'formulize');
if($sid) {
    $thisscreen1 = $screen_handler->get($sid); // first get basic screen object to determine type
    $fid = is_object($thisscreen1) ? $thisscreen1->getVar('fid') : 0;
}

// set the flag to force derived value updates, if it is in the URL
if(isset($_GET['forceDerivedValueUpdate'])) {
    $GLOBALS['formulize_forceDerivedValueUpdate'] = true;
}

// query modified to include singleentry - July 28, 2005 -- part of switch to new intnerface
$sql=sprintf("SELECT singleentry,desc_form FROM ".$xoopsDB->prefix("formulize_id")." WHERE id_form='$fid'");
$res = $xoopsDB->query ( $sql ) or die('SQL Error !<br />'.$sql.'<br />'.$xoopsDB->error());
//global $nb_fichier;

if ( $res ) {
  while ( $row = $xoopsDB->fetchArray ( $res ) ) {
    $singleentry = $row['singleentry'];
    $desc_form = $row['desc_form'];
  }
}

$myts =& MyTextSanitizer::getInstance();
$title = $myts->displayTarea($desc_form);

$currentURL = getCurrentURL();
if($fid AND !$view_form = $gperm_handler->checkRight("view_form", $fid, $groups, $mid)) {
    if(strstr($currentURL, "/modules/formulize/") OR $formulizeCanonicalURI) { // if it's a formulize page reload to login screen (check URL and check if there was a valid Formulize clean URL)
        $nopermission = $xoopsUser ? "op=nopermission&" : ""; // no permission flag will bump the user to the All Applications page since they don't have perm for this page. If no user, they will be prompted for login.
        redirect_header(XOOPS_URL . "/user.php?".$nopermission."xoops_redirect=".urlencode($currentURL), 3, _formulize_NO_PERMISSION, false);
    } else { // if formulize is just being included elsewhere, then simply show error and end script
        global $user;
        if(isset($GLOBALS['formulizeHostSystemUserId']) AND is_object($user) AND is_array($user->roles) AND !$xoopsUser) {
            // Drupal user is not logged in
            $slashPosition = strpos($currentURL,"/",10);
            $afterSlashLocation = substr($currentURL,$slashPosition+1);
            redirect_header("/user?destination=".$afterSlashLocation, 0, _formulize_NO_PERMISSION);
        } else {
            print "<p>"._formulize_NO_PERMISSION."</p>\n";
        }
        return;
    }
}

// IF A SCREEN IS REQUESTED, GET DETAILS FOR THAT SCREEN AND CALL THE NECESSARY DISPLAY FUNCTION
$rendered = false;
$screen = false;
if($sid) {
    if(is_object($thisscreen1)) {
        unset($screen_handler); // reset handler to that type of screen
        $screen_handler =& xoops_getmodulehandler($thisscreen1->getVar('type').'Screen', 'formulize');
        $screen = $screen_handler->get($sid); // get the full screen object

        if(isset($_POST['ventry']) AND $_POST['ventry'] AND $screen->getVar('type') == 'listOfEntries' AND $screen->getVar("viewentryscreen") != "none" AND $screen->getVar("viewentryscreen") AND !strstr($screen->getVar("viewentryscreen"), "p")) { // if the user is viewing an entry off a list, then check what screen gets used to display entries instead, since that's what we're doing (but only if there is a screen specified, and it's not a pageworks page)
            // do all this to set the Frid properly. That's it. Otherwise, no change. Frid affects behaviour in readelements.php
            $base_screen_handler = xoops_getmodulehandler('screen', 'formulize');
            $viewEntryScreenObject = $base_screen_handler->get(intval($screen->getVar('viewentryscreen')));
            $frid = $viewEntryScreenObject->getVar('frid');
        } else {
            $frid = $screen->getVar('frid'); // set these here just in case it's needed in readelements.php
        }
    }
}

// gather $_GET['ve'] (viewentry), or prefer a global value that has been set
if(isset($formulize_entry_id) AND is_numeric($formulize_entry_id)) {
  $entry = $formulize_entry_id;
} elseif(isset($_GET['ve']) AND is_numeric($_GET['ve'])) {
    $entry = $_GET['ve'];
} else {
    $entry = "";
}

$formulize_screen_loadview = (!isset($formulize_screen_loadview) OR !is_numeric($formulize_screen_loadview)) ? (isset($_GET['loadview']) ? intval($_GET['loadview']) : 0) : $formulize_screen_loadview;
$loadThisView = (isset($formulize_screen_loadview) AND is_numeric($formulize_screen_loadview)) ? $formulize_screen_loadview : "";
if (!$loadThisView) {
    // a 0 could possibly screw things up, so change to ""
    $loadThisView = "";
}

if ($screen) {

		$renderedFormulizeScreen = $screen;
    // this will only be included once, but we need to do it after the fid and frid for the current page load have been determined!!
    include_once XOOPS_ROOT_PATH . "/modules/formulize/include/readelements.php";


    // validate any passcode for anon users that has been saved in session, or require one from users first before anything else
    if($uid == 0 AND $screen->getVar('anonNeedsPasscode')) {
        $screenAllowedForUser = false;
        $passCodeHandler = xoops_getmodulehandler('passcode', 'formulize');
        $passCode = isset($_SESSION['formulize_passCode_'.$screen->getVar('sid')]) ? $_SESSION['formulize_passCode_'.$screen->getVar('sid')] : '';
        // if no passcode in session and if we have not received a user's submitted passcode...
        if (!$passCode AND (!isset($_POST['passcode']) OR isset($_SESSION['formulize_passcodeFailed']))) {
            unset($_SESSION['formulize_passcodeFailed']);
            $xoopsTpl->display("db:passcode.html");
            return;
        // if no passcode but we have received a user's submitted passcode then process it...
        } elseif(!$passCode) {
            $passCode = $_POST['passcode'];
        }
        // if a passcode has been determined, validate it...
        if($passCode) {
            $passCode_handler = xoops_getmodulehandler('passcode', 'formulize');
            $screenAllowedForUser = $passCode_handler->validatePasscode($passCode, $screen->getVar('sid'));
        }
    } else {
        // group based permission for other non-anon users will go here
        $screenAllowedForUser = true;
    }

    if($screenAllowedForUser) {

			writeToFormulizeLog(array(
				'formulize_event' => 'attempting-screen-rendering',
				'user_id' => ($xoopsUser ? $xoopsUser->getVar('uid') : 0),
				'form_id' => $screen->getVar('fid'),
				'screen_id' => $screen->getVar('sid')
			));

			if($screen->getVar('type') == "listOfEntries" AND ((isset($_GET['iform']) AND $_GET['iform'] == "e") OR isset($_GET['showform']))) { // form itself specifically requested, so force it to load here instead of a list
					if($screen->getVar('frid')) {
							include_once XOOPS_ROOT_PATH . "/modules/formulize/include/formdisplay.php";
							displayForm($screen->getVar('frid'), "", $screen->getVar('fid'), "", "{NOBUTTON}");
					} else {
							include_once XOOPS_ROOT_PATH . "/modules/formulize/include/formdisplay.php";
							displayForm($screen->getVar('fid'), "", "", "", "{NOBUTTON}");
					}
			} elseif($screen->getVar('type') == 'calendar') {
					$screen_handler->render($screen);
			} else {
					$screen_handler->render($screen, $entry, $loadThisView);
			}
    } else {
        $_SESSION['formulize_passcodeFailed'] = true;
        print "<p>"._formulize_NO_PERM."</p>";
    }
    $rendered = true;
}

// IF NO SCREEN IS REQUESTED (or none rendered successfully, ie: a bad screen id was passed), THEN USE THE DEFAULT DISPLAY LOGIC TO DETERMINE WHAT TO SHOW THE USER

// Only allowed for logged in users! Too many security holes if we allow anons all the different ways of getting to forms. They must go through a screen.

// new logic to handle invoking new interface (2005)
// 1. determine if the form is a single or multi
// 1.5 if multi->displayEntries, if single...
// 2. if single, determine if the user has group or global scope
// 2.5 if yes->displayEntries, if no...
// 3 displayForm

if (!$rendered AND $uid) {
    if (isset($fid) AND is_numeric($fid) AND $fid) {

			writeToFormulizeLog(array(
				'formulize_event' => 'attempting-raw-rendering',
				'user_id' => ($xoopsUser ? $xoopsUser->getVar('uid') : 0),
				'form_id' => intval($fid)
			));

        $form_handler = xoops_getmodulehandler('forms', 'formulize');
        $formObject = $form_handler->get($fid);
        $defaultFormScreen = $formObject->getVar('defaultform');
        $defaultListScreen = $formObject->getVar('defaultlist');
        if (((!$singleentry AND $xoopsUser) OR $view_globalscope OR ($view_groupscope AND $singleentry != "group")) AND !$entry AND (!isset($_GET['iform']) OR $_GET['iform'] != "e") AND !isset($_GET['showform'])) { // if it's multientry and there's a xoopsUser, or the user has globalscope, or the user has groupscope and it's not a one-per-group form, and after all that, no entry has been requested, then show the list (note that anonymous users default to the form view...to provide them lists of their own entries....well you can't, but groupscope and globalscope will show them all entries by anons or by everyone) ..... unless there is an override in the URL that is meant to force the form itself to display .... iform is "interactive form", devised by Feratech.
            if ($defaultListScreen AND !$formulize_masterUIOverride) {
                $basescreenObject = $screen_handler->get($defaultListScreen);
                $finalscreen_handler = xoops_getmodulehandler($basescreenObject->getVar('type').'Screen', 'formulize');
                $finalscreenObject = $finalscreen_handler->get($defaultListScreen);
                $frid = $finalscreenObject->getVar('frid');
                // this will only be included once, but we need to do it after the fid and frid for the current page load have been determined!!
                include_once XOOPS_ROOT_PATH . "/modules/formulize/include/readelements.php";
                $renderedFormulizeScreen = $finalscreenObject;
                $finalscreen_handler->render($finalscreenObject, $entry, $loadThisView);
						// no screen to show, go with the basics...
            } else {
								if (isset($frid) AND is_numeric($frid) AND $frid) {
										// this will only be included once, but we need to do it after the fid and frid for the current page load have been determined!!
										include_once XOOPS_ROOT_PATH . "/modules/formulize/include/readelements.php";
										displayEntries($frid, $fid);
								} else {
                		// this will only be included once, but we need to do it after the fid and frid for the current page load have been determined!!
                		include_once XOOPS_ROOT_PATH . "/modules/formulize/include/readelements.php";
									  displayEntries($fid);
								}
            }
        } else {
            // otherwise, show the form
            if ($defaultFormScreen AND !$formulize_masterUIOverride) {
                $basescreenObject = $screen_handler->get($defaultFormScreen);
                $finalscreen_handler = xoops_getmodulehandler($basescreenObject->getVar('type').'Screen', 'formulize');
                $finalscreenObject = $finalscreen_handler->get($defaultFormScreen);
                $frid = $finalscreenObject->getVar('frid');
                // this will only be included once, but we need to do it after the fid and frid for the current page load have been determined!!
                include_once XOOPS_ROOT_PATH . "/modules/formulize/include/readelements.php";
                $renderedFormulizeScreen = $finalscreenObject;
                $finalscreen_handler->render($finalscreenObject, $entry);
						// no screen to show, go with the basics...
            } else {
								if (isset($frid) AND is_numeric($frid) AND $frid) {
										// this will only be included once, but we need to do it after the fid and frid for the current page load have been determined!!
										include_once XOOPS_ROOT_PATH . "/modules/formulize/include/readelements.php";
										displayForm($frid, $entry, $fid, "", "{NOBUTTON}");
								} else {
										// this will only be included once, but we need to do it after the fid and frid for the current page load have been determined!!
										include_once XOOPS_ROOT_PATH . "/modules/formulize/include/readelements.php";
										displayForm($fid, $entry, "", "", "{NOBUTTON}"); // if it's a single and they don't have group or global scope, OR if an entry was specified in particular
								}
						}
        }
    } else {
        // if no form is specified, then show the General Forms category
        // this will only be included once, but we need to do it after the fid and frid for the current page load have been determined!!
        include_once XOOPS_ROOT_PATH . "/modules/formulize/include/readelements.php";
        // if it's a formulize page, reload to login screen
        if (strstr($currentURL, "/modules/formulize/")) {
            header("Location: " . XOOPS_URL . "/modules/formulize/application.php?id=all");
        } else {
            print "<p>Formulize could not display a screen for you.  Are you sure the specified screen exists?</p>";
        }
    }
} elseif(!$rendered AND !$xoopsUser) {
	// boot the user to the homepage. Anons will be able to login there.
	header("location: ".XOOPS_URL);
	exit();
}

// renderedFormulizeScreen is a global, and might be altered by entriesdisplay.php if it sends the user off to a different screen (like a form screen instead of the list)
if ($renderedFormulizeScreen AND is_object($xoopsTpl)) {
    $xoopsTpl->assign('xoops_pagetitle', $renderedFormulizeScreen->getVar('title'));
    $xoopsTpl->assign('icms_pagetitle', $renderedFormulizeScreen->getVar('title'));
    $xoopsTpl->assign('formulize_screen_id', $renderedFormulizeScreen->getVar('sid'));
} elseif (is_object($xoopsTpl))  {
    $xoopsTpl->assign('xoops_pagetitle', $title);
    $xoopsTpl->assign('icms_pagetitle', $title);
}
if(is_object($xoopsTpl)) {
	$xoopsTpl->assign('formulize_customCodeForApplications', (isset($GLOBALS['formulize_customCodeForApplications']) ? $formulize_customCodeForApplications : ''));
}
// go back to the previous rendering flag, in case this operation was nested inside something else
$GLOBALS['formulize_thisRendering'] = $prevRendering[$thisRendering];

writeToFormulizeLog(array(
	'formulize_event' => 'completed-page-rendering',
	'user_id' => ($xoopsUser ? $xoopsUser->getVar('uid') : 0),
	'form_id' => ($rendered ? $screen->getVar('fid') : intval($fid)),
	'screen_id' => ($rendered ? $screen->getVar('sid') : '')
));

