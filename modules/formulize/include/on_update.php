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
function xoops_module_update_formulize($module, $prev_version, $prev_dbversion, $fileFilter = '', $startFrom = '') {
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
    echo '<p>Previous database version: ' . $prev_dbversion . '<br>'
        . 'Required database version: ' . $requiredDbVersion . '</p>';

    // $fileFilter is an escape hatch for when running every patch in one request times out: it
    // restricts this run to patch file(s) whose name starts with the given string (e.g. "001"), so
    // each slow patch can be run on its own via ?op=patchDB-only&f=001. A filtered run never advances
    // dbversion, since it can't vouch for patches it didn't run — only a full, unfiltered pass does that.
    if ($fileFilter !== '') {
        $patchFiles = array_filter($patchFiles, function ($patchFile) use ($fileFilter) {
            return strpos(basename($patchFile), $fileFilter) === 0;
        });
        echo '<p>Restricting this run to patch file(s) starting with: ' . htmlspecialchars($fileFilter) . '</p>';
        if (!$patchFiles) {
            echo '<p><strong>No patch file matches "' . htmlspecialchars($fileFilter) . '".</strong></p>';
        }
    }
    // $startFrom is a further escape hatch, one level down from $fileFilter: some individual patches
    // (e.g. 002_derived_value_formula_migration) loop over many files of their own and are NOT safe to
    // simply rerun from the top once they've partially completed (see that file's own comments for why).
    // Passing a filename here lets such a patch resume from a specific point instead of the beginning.
    // We only pass it to patches that actually declare a parameter literally named $startFrom (checked
    // via reflection, then passed as a named argument) — never positionally to every patch — so a future
    // patch's own unrelated 3rd parameter can never silently receive this value instead.
    if ($startFrom !== '') {
        echo '<p>Resuming from file: ' . htmlspecialchars($startFrom) . '</p>';
    }
    if ($patchFiles) {
        sort($patchFiles);
        foreach ($patchFiles as $patchFile) {
            include_once $patchFile;
        }
        foreach ($patchFiles as $patchFile) {
            $funcName = 'formulize_patch_' . basename($patchFile, '.php');
            echo '<p>Checking patch file: ' . htmlspecialchars(basename($patchFile)) . '</p>';
            if (function_exists($funcName)) {
                // Every patch is called with ($prev_dbversion, $requiredDbVersion) positionally, so the
                // 3rd parameter specifically (not just a $startFrom named anywhere) must be the one named
                // startFrom for a patch to opt in — matching our calling convention exactly.
                $patchParams = (new ReflectionFunction($funcName))->getParameters();
                $acceptsStartFrom = isset($patchParams[2]) && $patchParams[2]->getName() === 'startFrom';
                if ($startFrom !== '' && $acceptsStartFrom) {
                    $result = $funcName($prev_dbversion, $requiredDbVersion, startFrom: $startFrom);
                } else {
                    $result = $funcName($prev_dbversion, $requiredDbVersion);
                }
                if ($result === false) {
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
    // succeeded AND this was a full, unfiltered run. A filtered single-file run can't vouch for the
    // other patch files, so it must never advance dbversion — otherwise the update would be marked
    // complete while patches other than the filtered one have never actually run.
    if ($allSucceeded && $fileFilter === '') {
        $xoopsDB->queryF("UPDATE " . $xoopsDB->prefix('modules') . " SET dbversion = " . $requiredDbVersion . " WHERE dirname = 'formulize'");
    } elseif ($allSucceeded && $fileFilter !== '' && $patchFiles) {
        // Deliberately NOT suggesting a final unfiltered patchDB-only run here: not every patch is safe
        // to rerun once partially applied (e.g. 002_derived_value_formula_migration — see its own
        // comments), and a plain retry would restart such a patch's own file loop from the top with
        // $prev_dbversion still unchanged, re-corrupting anything already fixed.
        echo '<p>Database version has <strong>not</strong> been advanced, because this was a filtered run. '
            . 'Once you have confirmed every patch file has completed successfully, use '
            . '<a href="' . htmlspecialchars(XOOPS_URL . '/modules/formulize/admin/ui.php?op=setDBVersion') . '">Set Database Version</a> '
            . 'to advance it directly — do not run this unfiltered, since not every patch is safe to rerun.</p>';
    }

    // icms_module_update() reads $module->messages after calling this function and adds it to its
    // $msgs array, placing our output at the correct position in the rendered page.
    $module->messages = ob_get_clean();

    // Returning false tells the core module-update flow the script failed (so it reports the failure
    // and rolls the module's dbversion back to its previous value — see modules/system/admin/modulesadmin).
    return $allSucceeded;
}
