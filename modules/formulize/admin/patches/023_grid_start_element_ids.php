<?php
if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}

// A grid's starting element, key 4 of its ele_value, is now always the id of an element in the grid's form (issue #643),
// and elementsInGrid() shows nothing for a grid whose starting element it can't find. It used to look a value that
// wasn't a number up as a caption, and start the grid at the top of the form when nothing matched. Some grids hold
// something other than an id from their own form:
//
// - a caption, or nothing, from very old versions. A caption is matched the way elementsInGrid() matched it, so those
//   grids show the same elements as before.
// - a handle, when a configuration import couldn't find the element it named. It's matched as a handle, which is the
//   element the grid was meant to start at.
// - the id of an element in another form, in a form that was cloned, because cloning didn't change the starting
//   element. It's matched by that element's caption in the grid's own form, which is the copy cloning made of it.
//
// A grid that matches none of those started at the top of the form, so it becomes the id of the form's first element,
// which shows the same elements. The grids whose elements change, and the ones that matched nothing, are listed so an
// admin can check them. 'none' is left alone: it means the starting element was deleted, and the grid now shows nothing
// rather than the elements at the top of the form. 'first' is left alone too, because elementsInGrid() still accepts it.
//
// A new site has no grids yet. Running this again changes nothing more, because a grid that starts at an element in
// its own form is left alone.
function formulize_patch_023_grid_start_element_ids($prev_dbversion, $required_dbversion) {
    global $xoopsDB;

    if ($prev_dbversion >= 40) {
        return true; // already applied
    }

    include_once XOOPS_ROOT_PATH . '/modules/formulize/class/elements.php';
    $elementsTable = $xoopsDB->prefix('formulize');

    // the grid element type, and any type that extends it
    if (!$typesResult = $xoopsDB->queryF("SELECT DISTINCT ele_type FROM $elementsTable")) {
        echo '<p>023_grid_start_element_ids: could not read the element types: ' . htmlspecialchars($xoopsDB->error()) . '</p>';
        return false;
    }
    $gridTypes = array();
    while ($typeRow = $xoopsDB->fetchArray($typesResult)) {
        if (elementTypeIsOrExtends($typeRow['ele_type'], 'grid')) {
            $gridTypes[] = $xoopsDB->quoteString($typeRow['ele_type']);
        }
    }
    if (!$gridTypes) {
        echo '<p>There are no grids, so no grid starting elements to convert. result: OK</p>';
        return true;
    }

    if (!$grids = $xoopsDB->queryF("SELECT ele_id, id_form, ele_value FROM $elementsTable WHERE ele_type IN (" . implode(',', $gridTypes) . ")")) {
        echo '<p>023_grid_start_element_ids: could not read the grid elements: ' . htmlspecialchars($xoopsDB->error()) . '</p>';
        return false;
    }

    // run a query that finds one element, and return its id, 0 if there isn't one, or false if the query fails
    $findElementId = function ($sql) use ($xoopsDB) {
        if (!$result = $xoopsDB->queryF($sql)) {
            return false;
        }
        $row = $xoopsDB->fetchArray($result);
        return $row ? intval($row['ele_id']) : 0;
    };

    $converted = 0;
    $changed = array();
    $notFound = array();
    while ($row = $xoopsDB->fetchArray($grids)) {
        $eleValue = @unserialize((string) $row['ele_value']);
        if (!is_array($eleValue)) {
            continue;
        }
        $start = isset($eleValue[4]) ? (string) $eleValue[4] : '';
        if ($start === 'none' OR $start === 'first') {
            continue;
        }
        $gridId = intval($row['ele_id']);
        $fid = intval($row['id_form']);
        $gridName = 'grid ' . $gridId . ' in form ' . $fid;

        // what we'll look for, in order: each is a query, and whether matching it changes the elements the grid shows
        $lookups = array();
        if (is_numeric($start)) {
            $startId = intval($start);
            if ($startId > 0) {
                $inForm = $findElementId("SELECT ele_id FROM $elementsTable WHERE ele_id = $startId AND id_form = $fid");
                if ($inForm === false) {
                    echo '<p>023_grid_start_element_ids: could not read the starting element of ' . $gridName . ': ' . htmlspecialchars($xoopsDB->error()) . '</p>';
                    return false;
                }
                if ($inForm) {
                    continue; // already an element in the grid's form
                }
                // an element in another form, from cloning: the copy of it in this form has its caption, and started
                // out in the same place, which picks between elements with the same caption
                $lookups[] = array("SELECT e.ele_id FROM $elementsTable AS e INNER JOIN $elementsTable AS source ON source.ele_caption = e.ele_caption WHERE source.ele_id = $startId AND e.id_form = $fid ORDER BY (e.ele_order = source.ele_order) DESC, e.ele_id LIMIT 0,1", true);
            }
        } else {
            $quotedStart = $xoopsDB->quoteString($start);
            $lookups[] = array("SELECT ele_id FROM $elementsTable WHERE id_form = $fid AND ele_caption = $quotedStart ORDER BY ele_id LIMIT 0,1", false);
            if ($start !== '') {
                $lookups[] = array("SELECT ele_id FROM $elementsTable WHERE id_form = $fid AND ele_handle = $quotedStart LIMIT 0,1", true);
            }
        }

        $newStart = 0;
        foreach ($lookups as $lookup) {
            list($sql, $changesWhatIsShown) = $lookup;
            $newStart = $findElementId($sql);
            if ($newStart === false) {
                echo '<p>023_grid_start_element_ids: could not look up the starting element of ' . $gridName . ': ' . htmlspecialchars($xoopsDB->error()) . '</p>';
                return false;
            }
            if ($newStart) {
                if ($changesWhatIsShown) {
                    $changed[] = $gridName;
                }
                break;
            }
        }

        // nothing matched, so the grid started at the top of the form, and that's where it stays
        if (!$newStart) {
            $newStart = $findElementId("SELECT ele_id FROM $elementsTable WHERE id_form = $fid AND ele_type NOT IN (" . implode(',', $gridTypes) . ") ORDER BY ele_order LIMIT 0,1");
            if ($newStart === false) {
                echo '<p>023_grid_start_element_ids: could not read the first element of form ' . $fid . ': ' . htmlspecialchars($xoopsDB->error()) . '</p>';
                return false;
            }
            $notFound[] = $gridName;
        }

        $eleValue[4] = $newStart ? $newStart : 'none';
        if (!$xoopsDB->queryF("UPDATE $elementsTable SET ele_value = " . $xoopsDB->quoteString(serialize($eleValue)) . " WHERE ele_id = $gridId")) {
            echo '<p>023_grid_start_element_ids: could not update ' . $gridName . ': ' . htmlspecialchars($xoopsDB->error()) . '</p>';
            return false;
        }
        $converted++;
    }

    echo '<p>Grids now store their starting element by its id (' . $converted . ' grid' . ($converted == 1 ? '' : 's') . ' converted). result: OK</p>';
    if ($changed) {
        echo '<p>These grids now start at the element they were set to start at, instead of at the top of their form. Check that each one shows the right elements: '
            . htmlspecialchars(implode(', ', $changed)) . '.</p>';
    }
    if ($notFound) {
        echo '<p>The starting element of these grids could not be found, so they now start at the first element of their form, which is what they showed before. Check that each one starts where it should: '
            . htmlspecialchars(implode(', ', $notFound)) . '.</p>';
    }
    return true;
}
