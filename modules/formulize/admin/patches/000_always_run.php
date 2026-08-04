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

	// Clear the generated wrappers for form custom code, so they are rebuilt from whatever source is on
	// disk now. The wrappers are content-addressed (see formulizeForm::procedure_cache_filename), so a
	// stale one cannot be used in the ordinary course of things - this is here for the update itself, to
	// sweep away the accumulated files of every earlier version in one go, including any left by the fixed
	// -name scheme that came before. Deleting is always safe: a missing wrapper is regenerated on demand.
	foreach((array) glob(ICMS_CACHE_PATH.'/form_*_on_before_save*.php') as $cacheFile) { @unlink($cacheFile); }
	foreach((array) glob(ICMS_CACHE_PATH.'/form_*_on_after_save*.php') as $cacheFile) { @unlink($cacheFile); }
	foreach((array) glob(ICMS_CACHE_PATH.'/form_*_on_delete*.php') as $cacheFile) { @unlink($cacheFile); }
	foreach((array) glob(ICMS_CACHE_PATH.'/form_*_custom_edit_check*.php') as $cacheFile) { @unlink($cacheFile); }

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

	// Add to the Primary Relationship any connection between forms that is missing from it.
	// Runs on every update, because a Primary Relationship can be left incomplete by an earlier
	// version and there is otherwise nothing that would ever notice.
	if (!formulize_repair_primary_relationship()) {
		return false;
	}

  return true;
}

/**
 * Add to the Primary Relationship every connection between forms that is missing from it.
 *
 * The Primary Relationship is built once, by createPrimaryRelationship(), when a site is upgraded to
 * the version that introduced it. Nothing has ever revisited that result afterwards, so a site whose
 * Primary Relationship came out incomplete stays that way forever, and the symptoms are indirect and
 * hard to attribute: relationships that quietly resolve against the wrong form, derived value formulas
 * that cannot see elements in their own form, screens that cannot reach data they should be able to.
 *
 * It could come out incomplete for more than one reason. Versions before the fix in
 * populatePrimaryRelationship deleted an invalid link while still reading the list of links, which
 * overwrote the result handle being read and abandoned the rest of the list, so a single stale link
 * early in the table stopped every later link from being considered. The set of element types that
 * count as linked elements has also grown since, so a site built before that grew is missing whatever
 * the newer types would have contributed.
 *
 * Rather than try to detect any particular cause, this simply asks for the whole Primary Relationship
 * to be worked out again and adds whatever is not already there. It is safe to run on every update:
 * populatePrimaryRelationship is idempotent once the existing links have been declared to it via
 * primePrimaryRelationshipLinkPairs(), and on a site with a complete Primary Relationship it adds
 * nothing and prints nothing.
 *
 * Two deliberate differences from building a Primary Relationship from scratch:
 * - Invalid links found along the way are reported, not deleted. Deleting them is reasonable while
 *   setting up the Primary Relationship in the first place; quietly deleting relationship links on a
 *   live system during a routine update is not.
 * - A connection that is already present keeps its current unified delete setting unless one of the
 *   links feeding it says that setting should be off, in which case it is turned off. So a repair can
 *   turn cascading deletion off, matching the rule that it only applies when every link connecting the
 *   two forms asks for it, but it will never turn cascading deletion on.
 *
 * @return boolean False only if the Primary Relationship could not be read or rebuilt, which should
 *   stop the update; true otherwise, including when there was nothing to do.
 */
function formulize_repair_primary_relationship() {
	global $linkForms;

	// No Primary Relationship yet means there is nothing to repair. On a site old enough not to have
	// one, 001_schema_migrations creates it from scratch, and it runs after this patch.
	if (!primaryRelationshipExists()) {
		return true;
	}

	// insertLinkIntoPrimaryRelationship() collects the forms it touches in this global
	if (!isset($linkForms) OR !is_array($linkForms)) {
		$linkForms = array();
	}

	// Declare the links that already exist, so they are not inserted a second time
	if (!primePrimaryRelationshipLinkPairs()) {
		echo '<p>Error: could not read the existing Primary Relationship links. Please contact <a href=mailto:info@formulize.org>info@formulize.org</a> for assistance.</p>';
		return false;
	}

	$report = populatePrimaryRelationship(false);

	if ($report['error']) {
		echo '<p>Error: could not check the Primary Relationship for missing connections: ' . $report['error']
			. '<br>Please contact <a href=mailto:info@formulize.org>info@formulize.org</a> for assistance.</p>';
		return false;
	}

	if ($report['added']) {
		echo '<h3>Connections added to the Primary Relationship:</h3>';
		echo '<p>Your Primary Relationship was missing ' . count($report['added'])
			. (count($report['added']) == 1 ? ' connection' : ' connections')
			. ' that should have been in it. ' . (count($report['added']) == 1 ? 'It has' : 'They have')
			. ' been added. Forms connected this way can now find each other in derived value formulas,'
			. ' screens, and anywhere else the Primary Relationship is used.</p><ul>';
		foreach ($report['added'] as $line) {
			echo '<li>' . htmlspecialchars($line) . '</li>';
		}
		echo '</ul>';
	}

	if ($report['invalid']) {
		echo '<h3>Invalid relationship links found:</h3>';
		echo '<p>These links refer to elements that no longer exist, or claim a connection that the'
			. ' elements do not actually have. They have been left alone, and are not part of the Primary'
			. ' Relationship. You can delete them in the relationships area of the admin interface.</p><ul>';
		foreach ($report['invalid'] as $line) {
			echo '<li>' . htmlspecialchars($line) . '</li>';
		}
		echo '</ul>';
	}

	if ($report['problems']) {
		echo '<h3>Problems encountered while checking the Primary Relationship:</h3><ul>';
		foreach ($report['problems'] as $line) {
			echo '<li>' . htmlspecialchars($line) . '</li>';
		}
		echo '</ul>';
	}

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



