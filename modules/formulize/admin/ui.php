<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2010 Freeform Solutions                  ##
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
##  URL: http://www.formulize.org                           ##
##  Project: Formulize                                                       ##
###############################################################################

// initialize the ImpressCMS admin page template
include_once("admin_header.php");
xoops_cp_header();
define('_FORMULIZE_UI_PHP_INCLUDED', 1);

global $xoopsTpl, $xoopsDB, $xoopsUser;

// include necessary Formulize files/functions
include_once XOOPS_ROOT_PATH . "/modules/formulize/include/common.php";
include_once XOOPS_ROOT_PATH . '/modules/formulize/include/on_update.php';


// If saveLock is turned on, exit
/*if(saveLock) {
		exit();
}*/

if (!isset($xoopsTpl)) {
    global $xoopsOption, $xoopsConfig, $xoopsModule;

    $xoopsOption['theme_use_smarty'] = 1;

    // include Smarty template engine and initialize it
    require_once XOOPS_ROOT_PATH . '/class/template.php';
    require_once XOOPS_ROOT_PATH . '/class/theme.php';
    require_once XOOPS_ROOT_PATH . '/class/theme_blocks.php';

    if ( @$xoopsOption['template_main'] ) {
        if ( false === strpos( $xoopsOption['template_main'], ':' ) ) {
            $xoopsOption['template_main'] = 'db:' . $xoopsOption['template_main'];
        }
    }
    $xoopsThemeFactory = new xos_opal_ThemeFactory();
    $xoopsThemeFactory->allowedThemes = $xoopsConfig['theme_set_allowed'];
    $xoopsThemeFactory->defaultTheme = $xoopsConfig['theme_set'];

    $xoTheme =& $xoopsThemeFactory->createInstance(array(
        'contentTemplate' => @$xoopsOption['template_main'],
    ));
    $xoopsTpl =& $xoTheme->template;
}

/**
 * DO BASIC CHECKS TO DETERMINE IF THE ENVIRONMENT IS HEALTHY AND WHETHER AN UPDATE IS REQUIRED
 * Depends on some functions included through on_update.php, e.g. primaryRelationshipExists() and need81ElementTypeConversion()
 */

// Environment checks — run FIRST, on every admin page load, as a fix-first step.
// The tokens folder is fundamental to Formulize working at all, so we attempt to create/repair it
// before anything else. If it can't be fixed, the warning becomes the opResults panel and op.php
// will refuse to run any op (including the DB patch) until the environment is healthy.
ob_start();
if (!file_exists(XOOPS_ROOT_PATH . '/tokens')) {
    if (mkdir(XOOPS_ROOT_PATH . '/tokens', 0755) === false) {
        print '<h1>Your system is missing the ' . XOOPS_ROOT_PATH . '/tokens folder.</h1>'
            . '<p>Formulize will not work until this folder exists <b>and</b> is writable by the web server.</p>';
    } else {
        file_put_contents(XOOPS_ROOT_PATH . '/tokens/index.html', '<script>history.go(-1);</script>');
        file_put_contents(XOOPS_ROOT_PATH . '/tokens/.gitignore', '*');
    }
}
if (file_exists(XOOPS_ROOT_PATH . '/tokens') && !is_writable(XOOPS_ROOT_PATH . '/tokens')) {
    if (chmod(XOOPS_ROOT_PATH . '/tokens', 0755) === false) {
        print '<h1>The ' . XOOPS_ROOT_PATH . '/tokens folder is not writable by the web server.</h1>'
            . '<p>Formulize will not work until this folder is writable by the web server.</p>';
    }
}
$_uiEnvWarning = ob_get_clean();

$module_handler = xoops_gethandler('module');
$formulizeModule = $module_handler->getByDirname('formulize');
// patch needed if dbversion is out of date, or if the primary relationship doesn't exist, or there are pre-81 element types, or there is code that needs conversion to the new storage model
$formulizeNeedsDBPatch = (
	($formulizeModule->getDBVersion() < intval($formulizeModule->getInfo('dbversion')))
  OR !primaryRelationshipExists()
  OR need81ElementTypeConversion()
  OR (file_exists(XOOPS_ROOT_PATH . '/modules/formulize/custom_code') ? true : codeInNeedOfConversion())
);

// If an update is needed and no other op is in progress, route through the patchDB op
// so op.php shows the warning panel. The warning HTML lives in op.php in one place.
if ($formulizeNeedsDBPatch) {
    $_GET['op'] = 'patchDB';
}

/**
 * END OF BASIC CHECKS
 */

// handle any operations requested as part of this page load
// sets up a template variable with the results of the op, called opResults
include_once "op.php";

/**
 * Get the home tabs configuration
 * @param string $activePage The currently active page/tab identifier
 * @return array Array of tab configurations
 */
function getHomeTabs($activePage = 'home') {
    $tabs = array();

    // Apps is a plain page tab: the forms dashboard (admin/home.php), unchanged.
    $tabs[] = array(
        'name' => 'Apps',
        'url' => 'ui.php?page=home',
        'template' => 'db:admin/home.html',
        'active' => ($activePage == 'home')
    );

    // Subject tabs (Users / Settings) are declared as data in
    // include/configsettings_registry.php. Each is rendered by the generic handler
    // admin/configsubject.php into the shared wrapper admin/configsubject.html,
    // which draws the subject's secondary navigation and its active sub-view. To
    // add/move/rename a subject, its sub-views, or which preferences appear, edit
    // the registry file only.
    foreach(formulize_configSettingsRegistry() as $slug => $subject) {
        $tabs[] = array(
            'name' => $subject['name'],
            'url' => 'ui.php?page=' . $slug,
            'template' => 'db:admin/configsubject.html',
            'active' => ($activePage == $slug)
        );
    }

    // Standalone tool tabs (each its own controller + template, unchanged).
    $tabs[] = array(
        'name' => 'Log Viewer',
        'url' => 'ui.php?page=logviewer',
        'template' => 'db:admin/logviewer.html',
        'active' => ($activePage == 'logviewer')
    );

    $tabs[] = array(
        'name' => 'Import/Export',
        'url' => 'ui.php?page=config-sync',
        'template' => 'db:admin/config_sync.html',
        'active' => ($activePage == 'config-sync')
    );

    $tabs[] = array(
        'name' => 'Synchronize',
        'url' => 'ui.php?page=synchronize',
        'template' => 'db:admin/synchronize.html',
        'active' => ($activePage == 'synchronize')
    );

    // ui-tabs.html expects 1-based, contiguous keys (it computes tabselected = key - 1)
    return array_combine(range(1, count($tabs)), array_values($tabs));
}

// make the primary relationship if it doesn't exist already
if(primaryRelationshipExists() === false AND !$formulizeNeedsDBPatch) {
	if($error = createPrimaryRelationship()) {
		print "<p>$error</p>";
	}
}

// switch the theme for the screen if that's requested
if(isset($_POST['themeswitch']) AND $_POST['themeswitch'] AND isset($_GET['sid'])) {
    $screen_handler = xoops_getmodulehandler('screen', 'formulize');
    $screen = $screen_handler->get($_GET['sid']);
    $screen->setVar('theme', $_POST['themeswitch']);
    $screen_handler->insert($screen);
}

// create a set of templates for the screen if that's what the user requested
if(isset($_POST['seedtemplates']) AND $_POST['seedtemplates'] AND isset($_GET['sid'])) {
    $screen_handler = xoops_getmodulehandler('screen', 'formulize');
    $screen = $screen_handler->get($_GET['sid']);
    $themeDefaultPath = XOOPS_ROOT_PATH."/modules/formulize/templates/screens/".$screen->getVar('theme')."/default/".$screen->getVar('type')."/";
    if(!file_exists($themeDefaultPath)) {
        $themeDefaultPath = str_replace($screen->getVar('theme'), '', $themeDefaultPath);
    }
    if(!file_exists($themeDefaultPath)) {
        exit('Error: could not locate a valid default template path for "'.$screen->getVar('type').'" screens.');
    }
    recurse_copy($themeDefaultPath, XOOPS_ROOT_PATH."/modules/formulize/templates/screens/".$screen->getVar('theme')."/".$screen->getVar('sid')."/");
}


// create the contents that we want to display for the currently selected page
// the included php files create the values for $adminPage that are used for this page

$adminPage = array();
$adminPage['show_user_view'] = ''; // will be set for screens when preparing their admin page, so user can jump to the actual screen to see it in action

// include the active page file based on the 'page' parameter in the URL
$requestedPage = isset($_GET['page']) ? $_GET['page'] : "home";
if(formulize_isConfigSubject($requestedPage)) {
	// registry-declared subject tab (Apps/Users/Site): rendered by the shared
	// subject handler, which draws the secondary nav and the active sub-view
	$active_page = "configsubject.php";
} else {
	$candidate = str_replace("-", "_", $requestedPage).'.php';
	if(file_exists(XOOPS_ROOT_PATH."/modules/formulize/admin/".$candidate)) {
		// a standalone tool page (Copy Perms, Import/Export, Synchronize, Log Viewer)
		$active_page = $candidate;
	} else {
		// unknown page: fall back to the Apps dashboard
		$active_page = "home.php";
	}
}
include $active_page;

$adminPage['logo'] = "/modules/formulize/images/formulize-logo.png";

// assign the default selected tab, if any:
if (isset($_GET['tab']) AND (!isset($_POST['tabs_selected']) OR $_POST['tabs_selected'] === "")) {
    foreach($adminPage['tabs'] as $selected=>$tabData) {
        if (strtolower($tabData['name']) == $_GET['tab']) {
            $adminPage['tabselected'] = $selected-1;
            break;
        }
    }
} elseif (isset($_POST['tabs_selected']) and $_POST['tabs_selected'] !== "") {
    $adminPage['tabselected']  = intval($_POST['tabs_selected']);
}

// make isSaveLocked preference available to template
$adminPage['isSaveLocked'] = sendSaveLockPrefToTemplate();

// retrieve the xoops_version info
$metadata = $formulizeModule->getInfo();
$config_handler = xoops_gethandler('config');
$formulizeConfig = $config_handler->getConfigsByCat(0, getFormulizeModId());
$xoopsTpl->assign('formulizeConfig', $formulizeConfig);

// assign the contents to the template and display
$adminPage['formulizeModId'] = getFormulizeModId();
$xoopsTpl->assign('version', $metadata['version']);
$xoopsTpl->assign('adminPage', $adminPage);
if (isset($breadcrumbtrail))
    $xoopsTpl->assign('breadcrumbtrail', $breadcrumbtrail);
$xoopsTpl->assign('scrollx', (isset($_POST['scrollx']) ? intval($_POST['scrollx']) : (isset($_GET['scrollx']) ? intval($_GET['scrollx']) : 0)));
$accordion_active = (isset($_POST['accordion_active']) AND $_POST['accordion_active'] !== "" AND $_POST['accordion_active'] !== "false") ? intval($_POST['accordion_active']) : "false";
$xoopsTpl->assign('accordion_active', $accordion_active);

// if we detect we're in the test environment, disable floating save button because it Selenium on Sauce cannot handle it obscuring clickable elements
// send snippet that will cause javascript evaluation to always fail
if(SDATA_DB_PREFIX == 'selenium') {
    $xoopsTpl->assign('allowFloatingSave', ' && 1==2');
} else {
    $xoopsTpl->assign('allowFloatingSave', '');
}

$xoopsTpl->assign('XOOPS_URL', XOOPS_URL);
$xoopsTpl->assign('UID', $xoopsUser->getVar('uid'));
$xoopsTpl->display("db:admin/ui.html");

xoops_cp_footer();
