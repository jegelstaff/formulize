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

// handles all operations requested through the UI
// included in ui.php
// depends on declarations in ui.php file!!

// $formulizeNeedsDBPatch is set in ui.php (dbversion + structural checks) before this file is included.
$opResults = isset($_uiEnvWarning) ? $_uiEnvWarning : '';
if (!$opResults AND isset($_GET['op'])) {
    ob_start();
    switch($_GET['op']) {
        case "delete":
            deleteForm($_GET['fid']);
            break;
				case "patchDB-only":
            // Escape hatch for when even the DB-only patch run times out: ?f=001 (matching a patch
            // file's numeric prefix) restricts the run to just that file, so each slow patch can be
            // applied in its own request. See xoops_module_update_formulize() for the dbversion implications.
            $patchFileFilter = isset($_GET['f']) ? preg_replace('/[^A-Za-z0-9_]/', '', $_GET['f']) : '';
            // Escape hatch one level further down: for patches that loop over their own list of files
            // (e.g. 002_derived_value_formula_migration) and can't simply be restarted from the top once
            // partially run, ?start=derived_foo.php resumes that inner loop from a specific filename.
            $patchStartFrom = isset($_GET['start']) ? preg_replace('/[^A-Za-z0-9_.\-]/', '', $_GET['start']) : '';
            $patchOnlyFormAction = XOOPS_URL . '/modules/formulize/admin/ui.php?op=patchDB-only'
                . ($patchFileFilter !== '' ? '&f=' . urlencode($patchFileFilter) : '')
                . ($patchStartFrom !== '' ? '&start=' . urlencode($patchStartFrom) : '');
						if (isset($_POST['patchDB-only'])) {
							include_once XOOPS_ROOT_PATH . "/modules/formulize/include/on_update.php";
							$moduleHandler = xoops_gethandler('module');
							$module = $moduleHandler->getByDirname('formulize');
							$prev_dbversion = $module->getVar('dbversion');
							$prev_version = $module->getVar('version');
                xoops_module_update_formulize($module, $prev_version, $prev_dbversion, $patchFileFilter, $patchStartFrom);
                echo $module->messages;
            } else {
                // op=patchDB-only in the URL but no form submission — show the warning without running anything.
                echo '<h1>You are about to update your Formulize database only!</h1>'
                    . '<h2>Warning: this process makes changes to your database and files. Backup your system before proceeding.</h2>'
										. '<p>By updating your database only, you will not update your templates or configuration settings. You will need to do a regular update afterwards to complete the process.</p>';
                if ($patchStartFrom !== '') {
                    echo '<p>Within the selected patch, processing will resume from <strong>' . htmlspecialchars($patchStartFrom) . '</strong> (inclusive) rather than starting over.</p>';
                }
                $setDBVersionLink = '<a href="' . htmlspecialchars(XOOPS_URL . '/modules/formulize/admin/ui.php?op=setDBVersion') . '">Set Database Version</a>';
                if ($patchFileFilter !== '') {
                    echo '<p>This run will be restricted to patch file(s) starting with <strong>' . htmlspecialchars($patchFileFilter) . '</strong>. '
                        . 'The database version will not be advanced by this run. Once you have confirmed every patch file has '
                        . 'completed successfully, use ' . $setDBVersionLink . ' to advance it directly — do not run this '
                        . 'unfiltered, since not every patch is safe to rerun.</p>';
                } else {
                    echo '<p>This will run every patch file in one request. If the server times out partway through, '
                        . 'click one of the individual files below to run just that one, and repeat for each remaining file. '
                        . 'Once you have confirmed every patch file has completed successfully, use ' . $setDBVersionLink . ' '
                        . 'to advance the database version directly — do not run this unfiltered button again once patches '
                        . 'have been run individually, since not every patch is safe to rerun.</p>';
                    $patchFiles = glob(XOOPS_ROOT_PATH . '/modules/formulize/admin/patches/*.php');
                    sort($patchFiles);
                    if ($patchFiles) {
                        echo '<ul>';
                        foreach ($patchFiles as $patchFile) {
                            $name = basename($patchFile, '.php');
                            preg_match('/^(\d+)/', $name, $m);
                            $prefix = isset($m[1]) ? $m[1] : $name;
                            echo '<li><a href="' . htmlspecialchars(XOOPS_URL . '/modules/formulize/admin/ui.php?op=patchDB-only&f=' . urlencode($prefix)) . '">' . htmlspecialchars($name) . '</a></li>';
                        }
                        echo '</ul>';
                    }
                }
                echo '<form action="' . htmlspecialchars($patchOnlyFormAction) . '" method="post">'
                    . '<input type="submit" name="patchDB-only" value="Update Formulize - Database Only'
                    . ($patchFileFilter !== '' ? ' (' . htmlspecialchars($patchFileFilter) . ' only)' : '') . '">'
                    . '</form>';
            }
            break;
        case "setDBVersion":
            // Manual override for the stored dbversion, independent of any patch logic. Needed because
            // some patches (e.g. 002_derived_value_formula_migration) are not safe to simply rerun once
            // partially applied — see that file's own comments — so once an admin has manually confirmed
            // every remaining piece of a patch is actually done, this is how to advance (or, to force a
            // patch to run again for some other reason, roll back) the version directly, bypassing every
            // patch's own gating.
            $moduleHandler = xoops_gethandler('module');
            $module = $moduleHandler->getByDirname('formulize');
            $currentDBVersion = $module->getVar('dbversion');
            $requiredDBVersion = intval($module->getInfo('dbversion'));
            if (isset($_POST['setDBVersion'])) {
                $newDBVersion = intval($_POST['newDBVersion']);
                $xoopsDB->queryF("UPDATE " . $xoopsDB->prefix('modules') . " SET dbversion = " . $newDBVersion . " WHERE dirname = 'formulize'");
                echo '<p>Database version changed from <strong>' . htmlspecialchars($currentDBVersion) . '</strong> to <strong>' . htmlspecialchars($newDBVersion) . '</strong>.</p>';
            } else {
                echo '<h1>Manually set the Formulize database version</h1>'
                    . '<h2>Warning: this directly overwrites the stored database version and runs no patch logic at all. '
                    . 'Only do this once you have manually verified the actual state of the database and files matches the '
                    . 'version you are about to set — setting it too high will permanently skip patches that were never run.</h2>'
                    . '<p>Current stored database version: <strong>' . htmlspecialchars($currentDBVersion) . '</strong><br>'
                    . 'Version required by the code currently installed: <strong>' . htmlspecialchars($requiredDBVersion) . '</strong></p>'
                    . '<form action="' . XOOPS_URL . '/modules/formulize/admin/ui.php?op=setDBVersion" method="post">'
                    . '<input type="number" name="newDBVersion" value="' . htmlspecialchars($currentDBVersion) . '"> '
                    . '<input type="submit" name="setDBVersion" value="Set Database Version">'
                    . '</form>';
            }
            break;
        case "patch40":
        case "patchDB":
            if (isset($_POST['patch40'])) {
                // Load the language strings used by icms_module_update() for template/block status lines.
                icms_loadLanguageFile('system', 'modulesadmin', true);
                icms_loadLanguageFile('system', 'blocksadmin', true);
                // Include the core module-update function. It runs the full update cycle (templates,
                // blocks, configs, onUpdate hook) and returns a <code>-wrapped HTML status string.
                // The echoed output from our patch functions is captured by the ob_start() above;
                // icms_module_update()'s returned msgs string is echoed here so it's captured too.
                include_once ICMS_MODULES_PATH . '/system/admin/modulesadmin/modulesadmin.php';
                echo icms_module_update('formulize');
            } else {
                // op=patchDB in the URL but no form submission — show the warning without running anything.
                echo '<h1>Your Formulize installation needs to be updated!</h1>'
                    . '<h2>Warning: this process makes changes to your database and files. Backup your system before proceeding.</h2>'
                    . '<form action="' . XOOPS_URL . '/modules/formulize/admin/ui.php?op=patchDB" method="post">'
                    . '<input type="submit" name="patch40" value="Update Formulize">'
                    . '</form>';
            }
            break;
    }
    $opResults = ob_get_clean();
}
$xoopsTpl->assign('opResults', $opResults);
$xoopsTpl->assign('showOpClose', (isset($_POST['patch40']) || isset($_POST['patchDB-only']) || isset($_POST['setDBVersion'])) && $opResults !== '');


function deleteForm($fid) {
    global $xoopsDB, $myts, $eh;
    global $xoopsUser, $xoopsModule;

    $gperm_handler = &xoops_gethandler('groupperm');
    $module_id = $xoopsModule->getVar('mid');
    $groups = $xoopsUser ? $xoopsUser->getGroups() : array(0=>XOOPS_GROUP_ANONYMOUS);
    if (!$gperm_handler->checkRight("delete_form", $fid, $groups, $module_id)) {
        return;
    }

    $form_handler = xoops_getmodulehandler('forms', 'formulize');
    $form_handler->dropDataTable($fid);

    $sql = sprintf("DELETE FROM %s WHERE id_form = '%s'", $xoopsDB->prefix("formulize_id"), $fid);
    $xoopsDB->queryF($sql) or $eh->show("error supression 1 dans delform");

    $sql = sprintf("DELETE FROM %s WHERE id_form = '%u'", $xoopsDB->prefix("formulize"), $fid);
    $xoopsDB->queryF($sql) or $eh->show("error supression 2 dans delform");

    $sql = sprintf("DELETE FROM %s WHERE itemname = '%s'", $xoopsDB->prefix("formulize_menu"), $fid);
    $xoopsDB->queryF($sql) or $eh->show("error supression 3 dans delform");

    xoops_notification_deletebyitem ($module_id, "form", $id_form); // added by jwe-10/10/04 to handle removing notifications for a form once it's gone
}
