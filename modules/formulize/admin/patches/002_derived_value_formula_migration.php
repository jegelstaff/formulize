<?php
if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}

// Migrates derived value formula files from the old "element_handle" double-quote syntax
// to the new $element_handle dollar-sign syntax introduced in Formulize 8.2.
// Only runs when $prev_dbversion < 2 (i.e. has not yet been applied).
//
// IMPORTANT: this migration is a one-time, NON-IDEMPOTENT rewrite of the /code/derived_*.php files.
// Stage 1 renames $variables that collide with element handles to $name_f82; on an already-migrated
// file those $handle references ARE the element references, so re-running would corrupt them. For that
// reason this function ALWAYS returns true (success) and never asks on_update.php to abort/retry it.
// Safety comes from the version gate plus ordering: it only runs when $prev_dbversion < 2, it runs
// AFTER 001_schema_migrations (so an 001 abort prevents it from starting at all), and once it completes
// successfully dbversion advances to 2 and it never runs again. Files that could not be written are
// reported for manual migration rather than triggering a destructive retry.
function formulize_patch_002_derived_value_formula_migration($prev_dbversion, $required_dbversion) {
    if ($prev_dbversion >= 2) {
        return true; // already applied; nothing to do
    }
    global $xoopsDB;

    $derivedCodeDir = XOOPS_ROOT_PATH . '/modules/formulize/code';
    $derivedFiles = glob($derivedCodeDir . '/derived_*.php');
    if (empty($derivedFiles)) {
        return true; // no derived value formula files to migrate
    }

    // Gather all element handles from the DB plus metadata fields that resolve as element references
    $allHandlesSet = array_flip(['uid', 'proxyid', 'creation_date', 'mod_date', 'creator_email', 'owner_groups', 'creation_uid', 'mod_uid', 'creation_datetime', 'mod_datetime']);
    $handlesRes = $xoopsDB->queryF("SELECT DISTINCT ele_handle FROM " . $xoopsDB->prefix('formulize') . " WHERE ele_handle != ''");
    while ($hRow = $xoopsDB->fetchArray($handlesRes)) {
        $allHandlesSet[$hRow['ele_handle']] = true;
    }

    // PHP context variables that are function parameters or the implicit return — must never be renamed or converted
    $reservedVars = array_flip(['value', 'entry', 'form_id', 'entry_id', 'relationship_id']);

    $stage1Changed = [];
    $stage2Changed = [];
    $notWritable = [];
    $element_handler = xoops_getmodulehandler('elements', 'formulize');

    foreach ($derivedFiles as $derivedFilePath) {
        if (!is_readable($derivedFilePath)) {
            continue;
        }
        $content = file_get_contents($derivedFilePath);

        $shortName = basename($derivedFilePath);
        $fileEleHandle = preg_replace('/^derived_(.+)\.php$/', '$1', $shortName);
        if (!($derivedElement = $element_handler->get($fileEleHandle))) {
            continue;
        }

        // Stage 1: rename any $varname that conflicts with an element handle (or metadata field).
        // Only handle conflicts matter: captions/colheads can't form valid PHP variable names
        // anyway (per the convention, only double-quoted strings reference captions/colheads).
        $renamedVars = [];
        $content = preg_replace_callback(
            '/\$([a-zA-Z_][a-zA-Z0-9_]*)/',
            function($m) use ($allHandlesSet, $reservedVars, &$renamedVars) {
                $name = $m[1];
                if (isset($reservedVars[$name]) || !isset($allHandlesSet[$name])) {
                    return $m[0];
                }
                $renamedVars[$name] = true;
                return '$' . $name . '_f82';
            },
            $content
        );
        if (!empty($renamedVars)) {
            $stage1Changed[$shortName] = array_keys($renamedVars);
        }

        // Stage 2: convert ALL double-quoted element references (handle, caption, or colhead) to $handle syntax.
        // Uses formulize_convertCapOrColHeadToHandle with frid=-1 (primary relationship) so it searches
        // all connected forms — handles, captions, and colheads in priority order, matching runtime behavior.
        // Falls back to the global handle set for any element outside the primary relationship.
        $formulaFid = intval($derivedElement->getVar('fid'));
        $convertedHandles = [];
        // NOTE: the character class uses * (not +) so that empty double-quoted strings ("") are matched
        // and consumed as a balanced pair. With +, an empty "" cannot match, so the regex skips its first
        // quote and then mis-pairs its second quote with the opening quote of the NEXT string — shifting
        // every subsequent quote pairing by one and leaving all later "handle" references unconverted.
        $content = preg_replace_callback(
            '/"([^"]*)"/',
            function($m) use ($allHandlesSet, $reservedVars, $formulaFid, &$convertedHandles) {
                $text = $m[1];
                if ($text === '' || isset($reservedVars[$text])) return $m[0];
                list($handle) = formulize_convertCapOrColHeadToHandle(-1, $formulaFid, $text);
                if ($handle !== '{nonefound}') {
                    $convertedHandles[$text] = $handle;
                    return '$' . $handle;
                }
                // Fallback: identifier in a form outside the primary relationship
                if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $text) && isset($allHandlesSet[$text])) {
                    $convertedHandles[$text] = true;
                    return '$' . $text;
                }
                return $m[0];
            },
            $content
        );
        if (!empty($convertedHandles)) {
            $stage2Changed[$shortName] = array_keys($convertedHandles);
        }

        if (!is_writable($derivedFilePath)) {
            $notWritable[] = $shortName;
            continue;
        }
        file_put_contents($derivedFilePath, $content);
    }

    // Report results
    if (!empty($stage1Changed) || !empty($stage2Changed) || !empty($notWritable)) {
        echo '<p><strong>Derived value formula files migrated to Formulize 8.2 $handle syntax:</strong><br>';
        if (!empty($stage1Changed)) {
            $totalVars = array_sum(array_map('count', $stage1Changed));
            $fileText = count($stage1Changed) == 1 ? 'file' : 'files';
            $variableText = $totalVars == 1 ? 'variable' : 'variables';
            echo 'In ' . count($stage1Changed) . ' ' . $fileText . ', ' . $totalVars . ' ' . $variableText . ' renamed to avoid element handle conflicts.<br>';
        }
        if (!empty($stage2Changed)) {
            $totalRefs = array_sum(array_map('count', $stage2Changed));
            $fileText = count($stage2Changed) == 1 ? 'file' : 'files';
            $referenceText = $totalRefs == 1 ? 'reference' : 'references';
            echo 'In ' . count($stage2Changed) . ' ' . $fileText . ', ' . $totalRefs . ' ' . $referenceText . ' converted from "handle" to $handle syntax.<br>';
        }
        if (!empty($notWritable)) {
            echo '<strong>WARNING:</strong> The following file(s) could not be updated (not writable by the web server) and must be manually migrated: ' . implode(', ', $notWritable) . '<br>';
        }
        echo '</p>';
    }

		echo "<script>alert('Derived value changes in 8.2\n\nYou now refer to elements in derived values with $handle syntax instead of \"handle\" syntax.\n\nAlso, derived values now preserve the existing value in the database, if the formula does not explicitly set a value. Previously the existing value would be erased. THIS MIGHT CHANGE HOW YOUR SYSTEM OPERATES. Test your workflows to be sure.');</script>";

    // Always report success — see the note at the top of this function. notWritable files are flagged
    // for manual migration above rather than causing a retry that would corrupt already-migrated files.
    return true;
}
