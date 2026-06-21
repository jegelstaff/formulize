<?php
if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}

// bring in the legacy functions and checks that we rely on
include_once XOOPS_ROOT_PATH . '/modules/formulize/include/common.php';
include_once XOOPS_ROOT_PATH . '/modules/formulize/admin/patches/001_schema_migrations.php';

// Called by icms_module_update() after the module record has been updated in the DB.
// $prev_dbversion is the dbversion that was in the modules table before this update run —
// used to gate one-time migrations that must not repeat on subsequent updates.
function xoops_module_update_formulize($module, $prev_version, $prev_dbversion) {
    global $xoopsDB;

    $requiredDbVersion = intval($module->getInfo('dbversion'));

    // Auto-discover patch files in admin/patches/, e.g. 001_schema_migrations.php, 002_derived....php.
    // Convention: a file named 001_foo.php may define formulize_patch_001_foo($prev, $required).
    // All files are included first, then callable functions are invoked in filename order (the numeric
    // prefix makes that order explicit and stable, because later patches may depend on earlier ones).
    // Each patch function gates itself on the version numbers it receives and returns false to signal
    // failure. On the first failure we abort WITHOUT advancing dbversion, so a failed update is left
    // clearly incomplete and can be corrected and retried cleanly (later patches never run on top of a
    // half-applied earlier one).
    $patchesDir = XOOPS_ROOT_PATH . '/modules/formulize/admin/patches/';
    $patchFiles = glob($patchesDir . '*.php');
    $allSucceeded = true;

    // Buffer all patch output so it can be returned via $module->messages. This ensures the output
    // appears at the correct position in the page on both the core System→Modules→Update path
    // (where icms_module_update() inserts $module->messages into its $msgs array at the right point)
    // and the Formulize admin manual-update path (where op.php captures it via its own ob_start()).
    ob_start();
    if ($patchFiles) {
        sort($patchFiles);
        foreach ($patchFiles as $patchFile) {
            include_once $patchFile;
        }
        foreach ($patchFiles as $patchFile) {
            $funcName = 'formulize_patch_' . basename($patchFile, '.php');
            if (function_exists($funcName)) {
                if ($funcName($prev_dbversion, $requiredDbVersion) === false) {
                    $allSucceeded = false;
                    echo '<p><strong>Update step ' . htmlspecialchars(basename($patchFile))
                        . ' did not complete successfully. The update has been stopped and the database '
                        . 'version has NOT been advanced, so you can correct the problem and run the update '
                        . 'again. Please contact <a href="mailto:info@formulize.org">info@formulize.org</a> '
                        . 'if you need assistance.</strong></p>';
                    break;
                }
            }
        }
    }

    // Advance dbversion in the modules table to what xoops_version.php declares ONLY when every patch
    // succeeded. If anything failed we leave the stored dbversion untouched so the update is not
    // mistakenly considered complete.
    if ($allSucceeded) {
        $xoopsDB->queryF("UPDATE " . $xoopsDB->prefix('modules') . " SET dbversion = " . $requiredDbVersion . " WHERE dirname = 'formulize'");
    }

    // icms_module_update() reads $module->messages after calling this function and adds it to its
    // $msgs array, placing our output at the correct position in the rendered page.
    $module->messages = ob_get_clean();

    // Returning false tells the core module-update flow the script failed (so it reports the failure
    // and rolls the module's dbversion back to its previous value — see modules/system/admin/modulesadmin).
    return $allSucceeded;
}
