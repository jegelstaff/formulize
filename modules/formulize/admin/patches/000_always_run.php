<?php


// Auto-discovery entry point: called by xoops_module_update_formulize() via the patches loop.
// These operations should always run with an update... regardless of dbversion
function formulize_patch_000_always_run($prev_dbversion, $required_dbversion) {
	global $xoopsConfig, $xoopsDB;

	// clear the admin menu cache files, so that any changes to the menu structure or labels will be reflected in the admin interface
	$adminMenuLangs = [ 'english', $xoopsConfig['language'] ];
	$adminMenuLangs = array_unique($adminMenuLangs);
	foreach($adminMenuLangs as $lang) {
		$adminMenuFile = XOOPS_ROOT_PATH.'/cache/adminmenu_'.$lang.'.php';
		if (file_exists($adminMenuFile)) {
			unlink($adminMenuFile);
		}
	}

	// ensure that use_mysession is set to 1 if session_name is set
	$configTable = $xoopsDB->prefix('config');
	$result = $xoopsDB->queryF("SELECT conf_value FROM $configTable WHERE conf_modid = 0 AND conf_name = 'session_name'");
	if (!$result) {
			echo '<p>Error: failed to read session_name: ' . htmlspecialchars($xoopsDB->error()) . ' Please contact <a href=mailto:info@formulize.org>info@formulize.org</a> for assistance.</p>';
			return false;
	}
	$row = $xoopsDB->fetchRow($result);
	if (empty($row[0])) {
			echo '<p>session_name is not set; leaving use_mysession unchanged.</p>';
	} elseif (!$xoopsDB->queryF("UPDATE $configTable SET conf_value = '1' WHERE conf_modid = 0 AND conf_name = 'use_mysession'")) {
			echo '<p>Error: failed to set use_mysession to 1: ' . htmlspecialchars($xoopsDB->error()) . ' Please contact <a href=mailto:info@formulize.org>info@formulize.org</a> for assistance.</p>';
			return false;
	}

	// Rename any element handles containing hyphens before running other schema work.
	// This is idempotent: handles without hyphens are untouched on repeat runs.
	formulize_migrate_hyphenated_handles();

  return true;
}

/**
 * Rename every element handle that contains a hyphen (e.g. "my-handle" → "my_handle").
 *
 * Hyphens are illegal in PHP variable names, so hyphenated handles silently break
 * derived-value formulas, on_before_save, on_after_save, and on_delete code that
 * reference elements as variables. This migration is idempotent: elements whose
 * handles contain no hyphens are left untouched on repeat runs.
 *
 * Collision policy: if "my_handle" already exists in the same form, the new name
 * becomes "my_handle_2", "my_handle_3", etc., until a free slot is found.
 *
 * All structural updates (captions, screen maps, saved views, code files, cache)
 * are delegated to formulizeElementsHandler::renameElementResources(), which is the
 * same path used when an admin renames a handle through the UI.
 *
 * Userland code that cannot be auto-updated (on_before_save / on_after_save /
 * on_delete / advanced calculations) is reported via an alert if it still contains
 * any of the old handle strings.
 */
function formulize_migrate_hyphenated_handles() {
    global $xoopsDB;

    // Find all elements whose handles contain a hyphen
    $res = $xoopsDB->queryF(
        "SELECT ele_id, ele_handle, id_form FROM " . $xoopsDB->prefix('formulize') . " WHERE ele_handle LIKE '%-%'"
    );
    if (!$res || $xoopsDB->getRowsNum($res) == 0) {
        return;
    }

    // Build rename map: ele_id => ['old' => ..., 'new' => ..., 'fid' => ...]
    // Resolve collisions with a numeric suffix so every element gets a unique handle.
    $renameMap = array();
    while ($row = $xoopsDB->fetchArray($res)) {
        $oldHandle = $row['ele_handle'];
        $baseNew   = str_replace('-', '_', $oldHandle);
        $newHandle = $baseNew;
        $suffix    = 2;
        while (true) {
            $checkRes = $xoopsDB->queryF(
                "SELECT ele_id FROM " . $xoopsDB->prefix('formulize')
                . " WHERE ele_handle = " . $xoopsDB->quoteString($newHandle)
                . " AND id_form = "       . intval($row['id_form'])
                . " AND ele_id != "       . intval($row['ele_id'])
            );
            if (!$checkRes || $xoopsDB->getRowsNum($checkRes) == 0) {
                break;
            }
            $newHandle = $baseNew . '_' . $suffix;
            $suffix++;
        }
        $renameMap[intval($row['ele_id'])] = array(
            'old' => $oldHandle,
            'new' => $newHandle,
            'fid' => intval($row['id_form']),
        );
    }

    if (empty($renameMap)) {
        return;
    }

    print "<h3>Renaming element handles containing hyphens:</h3>\n";

    $element_handler = xoops_getmodulehandler('elements', 'formulize');
    $oldHandles      = array();

    foreach ($renameMap as $eleId => $rename) {
        $element = $element_handler->get($eleId);
        if (!$element) {
            print "<p>Error: could not load element ele_id=" . intval($eleId) . " for renaming.</p>";
            continue;
        }
        $element->setVar('ele_handle', $rename['new']);
        if (!$element_handler->insert($element, true)) {
            print "<p>Error renaming ele_id=" . intval($eleId) . ": " . htmlspecialchars($xoopsDB->error()) . "</p>";
            continue;
        }
        print "<p>Renamed: <code>" . htmlspecialchars($rename['old']) . "</code> &rarr; <code>" . htmlspecialchars($rename['new']) . "</code> (form_id=" . $rename['fid'] . ")</p>\n";
        $oldHandles[] = $rename['old'];
        $element_handler->renameElementResources($element, $rename['old']);
    }

    // Alert for userland code that cannot be auto-updated.
    // Scan on_before_save / on_after_save / on_delete and advanced calculations
    // for any surviving occurrences of the old handle strings.
    $alertLines = array();

    $formProcRes = $xoopsDB->queryF(
        "SELECT id_form, on_before_save, on_after_save, on_delete FROM " . $xoopsDB->prefix('formulize_id')
    );
    if ($formProcRes) {
        while ($row = $xoopsDB->fetchArray($formProcRes)) {
            $code = $row['on_before_save'] . "\n" . $row['on_after_save'] . "\n" . $row['on_delete'];
            foreach ($oldHandles as $oldH) {
                if (strpos($code, $oldH) !== false) {
                    $alertLines[] = "Form ID " . intval($row['id_form']) . ": on_before_save / on_after_save / on_delete code references \"" . $oldH . "\"";
                    break;
                }
            }
        }
    }

    $acTableRes = $xoopsDB->queryF("SHOW TABLES LIKE '" . $xoopsDB->prefix('formulize_advanced_calculations') . "'");
    if ($acTableRes && $xoopsDB->getRowsNum($acTableRes) > 0) {
        $acRes = $xoopsDB->queryF(
            "SELECT acid, fid, input, output, steps FROM " . $xoopsDB->prefix('formulize_advanced_calculations')
        );
        if ($acRes) {
            while ($row = $xoopsDB->fetchArray($acRes)) {
                $allCode = $row['input'] . "\n" . $row['output'] . "\n" . $row['steps'];
                foreach ($oldHandles as $oldH) {
                    if (strpos($allCode, $oldH) !== false) {
                        $alertLines[] = "Form ID " . intval($row['fid']) . " (advanced calculation ID " . intval($row['acid']) . "): calculation code references \"" . $oldH . "\"";
                        break;
                    }
                }
            }
        }
    }

    if (!empty($alertLines)) {
        $msg  = "ATTENTION: Element handles containing hyphens were renamed during this update, "
              . "but the following userland code still references the old handle names. "
              . "These references must be updated manually before the affected code will work correctly:\n\n"
              . implode("\n", $alertLines) . "\n\n"
              . "In each case, replace the old handle (e.g. \$my-handle or {my-handle}) "
              . "with the new underscore form (e.g. \$my_handle or {my_handle}).";
        echo '<script>alert(' . json_encode($msg) . ');</script>';
    }
}



