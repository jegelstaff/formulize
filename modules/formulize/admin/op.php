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
$xoopsTpl->assign('showOpClose', isset($_POST['patch40']) && $opResults !== '');


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
