<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2006 Freeform Solutions                  ##
###############################################################################
##  This program is free software; you can redistribute it and/or modify     ##
##  it under the terms of the GNU General Public License as published by     ##
##  the Free Software Foundation; either version 2 of the License, or        ##
##  (at your option) any later version.                                      ##
###############################################################################
##  Author of this file: Formulize Incorporated                              ##
##  Project: Formulize                                                       ##
###############################################################################

// Methods on formulizeFormsHandler for creating and maintaining ad hoc table
// forms — forms backed directly by an existing database table (e.g. the system
// users table) rather than a Formulize-managed data table.
//
// An ad hoc table form is created with lockedform = FORMULIZE_LOCKEDFORM_SYSTEM_MANAGED
// and its tableform column set to the target table name. Elements are auto-generated
// from the table's columns (via SHOW COLUMNS) and kept in sync on every page load.

trait formulizeAdHocTableFormTrait {

	// Ensure an ad hoc table form exists for an arbitrary database table.
	// If one already exists (matched by tableform column), returns its id_form.
	// Otherwise, creates a new form record with lockedform=2 (system-managed)
	// and auto-generates element records for each column.
	// $options: 'excludeColumns' => array of column names to skip,
	//           'columnLabels' => array of column_name => friendly label
	function ensureAdHocTableForm($tableName, $formHandle, $formTitle, $options = array()) {
		global $xoopsDB;

		// Check if this table form already exists
		$checkSQL = "SELECT id_form FROM " . $xoopsDB->prefix("formulize_id") . " WHERE tableform = " . $xoopsDB->quoteString($tableName);
		$checkRes = $xoopsDB->query($checkSQL);
		if ($checkRes && $row = $xoopsDB->fetchArray($checkRes)) {
			$fid = intval($row['id_form']);
			// Sync elements in case the table schema changed
			$this->syncAdHocTableFormElements($fid, $tableName, $options);
			return $fid;
		}

		// Create the form record directly (bypassing normal insert which creates data tables and screens we don't need)
		$sql = "INSERT INTO " . $xoopsDB->prefix("formulize_id") . " (`form_title`, `singular`, `plural`, `singleentry`, `tableform`, `lockedform`, `menutext`, `form_handle`, `store_revisions`, `note`, `send_digests`, `pi`, `entries_are_users`, `entries_are_groups`, `parent_perm_fid`) VALUES (" .
			$xoopsDB->quoteString($formTitle) . ", " .
			$xoopsDB->quoteString($formTitle) . ", " .
			$xoopsDB->quoteString($formTitle) . ", " .
			"'off', " .
			$xoopsDB->quoteString($tableName) . ", " .
			FORMULIZE_LOCKEDFORM_SYSTEM_MANAGED . ", " . // system-managed ad hoc table form
			"'', " .
			$xoopsDB->quoteString($formHandle) . ", " .
			"0, '', 0, 0, 0, 0, 0)";

		if (!$xoopsDB->queryF($sql)) {
			return false;
		}

		$fid = $xoopsDB->getInsertId();

		// Create elements for each column, respecting excludeColumns and columnLabels
		if (!$this->createAdHocTableFormElements($tableName, $fid, $options)) {
			// Clean up the form record if element creation failed
			$xoopsDB->queryF("DELETE FROM " . $xoopsDB->prefix("formulize_id") . " WHERE id_form = " . intval($fid));
			return false;
		}

		// Set the headerlist (default visible columns)
		$this->setAdHocFormHeaderlist($fid, $options);

		return $fid;
	}

	// Set the headerlist for an ad hoc form.
	// If $options['defaultColumns'] is provided (array of raw column/handle names), only those
	// elements appear as default columns (in the given order). Otherwise all elements
	// are included. Always overwrites the current headerlist so code-defined defaults win.
	// Resolves canonical handles automatically from columnTypes/extraElements in $options.
	function setAdHocFormHeaderlist($fid, $options = array()) {
		global $xoopsDB;
		$fid = intval($fid);
		$defaultColumns = isset($options['defaultColumns']) ? $options['defaultColumns'] : null;

		if ($defaultColumns) {
			// Build handle→type map so we can compute canonical handles for each defaultColumn entry.
			$handleToType = array();
			foreach (isset($options['columnTypes']) ? $options['columnTypes'] : array() as $col => $type) {
				$handleToType[$col] = $type;
			}
			foreach (isset($options['extraElements']) ? $options['extraElements'] : array() as $extra) {
				if (isset($extra['typeForCaption'])) {
					$handleToType[$extra['handle']] = $extra['typeForCaption'];
				}
			}

			$headerParts = array();
			foreach ($defaultColumns as $handle) {
				$type = isset($handleToType[$handle]) ? $handleToType[$handle] : '';
				$canonicalHandle = $this->_adHocCanonicalHandle($type, $fid, $handle);
				// Accept both canonical and raw handle to handle migration gracefully.
				$safeCanonical = formulize_db_escape($canonicalHandle);
				$safeRaw = formulize_db_escape($handle);
				$inClause = $canonicalHandle !== $handle
					? "IN ('$safeCanonical', '$safeRaw')"
					: "= '$safeCanonical'";
				$sql = "SELECT ele_id FROM " . $xoopsDB->prefix("formulize") .
					" WHERE id_form = $fid AND ele_handle $inClause LIMIT 1";
				$res = $xoopsDB->query($sql);
				if ($res && $row = $xoopsDB->fetchArray($res)) {
					$headerParts[] = intval($row['ele_id']);
				}
			}
		} else {
			$eleResult = $xoopsDB->query(
				"SELECT ele_id FROM " . $xoopsDB->prefix("formulize") .
				" WHERE id_form = $fid ORDER BY ele_order"
			);
			$headerParts = array();
			while ($eleRow = $xoopsDB->fetchArray($eleResult)) {
				$headerParts[] = $eleRow['ele_id'];
			}
		}

		if (count($headerParts) > 0) {
			$headerlist = "*=+*:" . implode("*=+*:", $headerParts);
			$xoopsDB->queryF(
				"UPDATE " . $xoopsDB->prefix("formulize_id") .
				" SET headerlist = " . $xoopsDB->quoteString($headerlist) .
				" WHERE id_form = $fid"
			);
		}
	}

	// Generate the canonical element handle for a userAccount type in an ad hoc form,
	// matching the convention used by createUserAccountElements() and all lookup code:
	// formulize_user_account_{lowercasetype}_{fid}.
	// Returns $fallback unchanged for non-userAccount types.
	function _adHocCanonicalHandle($type, $fid, $fallback) {
		if ($type && strpos($type, 'userAccount') === 0) {
			return 'formulize_user_account_' . strtolower(str_replace('userAccount', '', $type)) . '_' . intval($fid);
		}
		return $fallback;
	}

	// Return the display name for a userAccount element type using the same
	// language constants as createUserAccountElements(), e.g. _formulize_USERACCOUNTUID.
	function _adHocElementName($type, &$nameCache) {
		if (!isset($nameCache[$type])) {
			$constName = "_formulize_" . strtoupper($type);
			$nameCache[$type] = defined($constName) ? constant($constName) : str_replace('userAccount', '', $type);
		}
		return $nameCache[$type];
	}

	// Return the canonical ele_order for an element type based on getUserAccountElementTypes().
	// Non-UA types return null (caller assigns a sequential fallback order).
	function _adHocElementOrder($type) {
		static $positions = null;
		if ($positions === null) {
			$positions = array_flip($this->getUserAccountElementTypes());
		}
		return isset($positions[$type]) ? ($positions[$type] + 1) * 10 : null;
	}

	// Create element records for an ad hoc table form, with support for
	// excluding columns, applying custom labels, and adding virtual extra elements.
	// Captions for typed columns are derived from the element class ->name.
	// ele_order follows the canonical userAccount type order from getUserAccountElementTypes().
	function createAdHocTableFormElements($targetTableName, $fid, $options = array()) {
		$excludeColumns = isset($options['excludeColumns']) ? $options['excludeColumns'] : array();
		$columnLabels   = isset($options['columnLabels'])   ? $options['columnLabels']   : array();
		$columnTypes    = isset($options['columnTypes'])    ? $options['columnTypes']    : array();
		$extraElements  = isset($options['extraElements'])  ? $options['extraElements']  : array();

		$uaTypeCount   = count($this->getUserAccountElementTypes());
		$fallbackOrder = ($uaTypeCount + 1) * 10; // first order slot for non-UA elements
		$nameCache     = array();

		$result = $this->db->queryF("SHOW COLUMNS FROM " . formulize_db_escape($targetTableName));
		$element_handler = xoops_getmodulehandler('elements', 'formulize');
		while ($row = $this->db->fetchRow($result)) {
			$columnName = $row[0];
			if (in_array($columnName, $excludeColumns)) {
				continue;
			}

			$type = isset($columnTypes[$columnName]) ? $columnTypes[$columnName] : 'textarea';
			if (isset($columnTypes[$columnName])) {
				$caption      = $this->_adHocElementName($type, $nameCache);
				$element_order = $this->_adHocElementOrder($type) ?? $fallbackOrder;
				if ($this->_adHocElementOrder($type) === null) { $fallbackOrder += 10; }
			} else {
				$caption      = isset($columnLabels[$columnName]) ? $columnLabels[$columnName] : str_replace("_", " ", $columnName);
				$element_order = $fallbackOrder;
				$fallbackOrder += 10;
			}

			$eleHandle = $this->_adHocCanonicalHandle($type, $fid, $columnName);
			// Store the real DB column name when it differs from the canonical handle so
			// dataExtractionTableForm can access the right column in SELECT * results.
			$eleVal = array(0=>"", 1=>5, 2=>35, 3=>"");
			if ($eleHandle !== $columnName) {
				$eleVal['source_column'] = $columnName;
			}
			$element =& $element_handler->create();
			$element->setVar('ele_caption', $caption);
			$element->setVar('ele_handle', $eleHandle);
			$element->setVar('ele_desc', "");
			$element->setVar('ele_colhead', "");
			$element->setVar('ele_required', 0);
			$element->setVar('ele_order', $element_order);
			$element->setVar('ele_forcehidden', 0);
			$element->setVar('ele_uitext', "");
			$element->setVar('ele_value', $eleVal);
			$element->setVar('id_form', $fid);
			$element->setVar('ele_private', 0);
			$element->setVar('ele_display', 1);
			$element->setVar('ele_disabled', 0);
			$element->setVar('ele_type', $type);
			if (!$element_handler->insert($element, force: true)) {
				return false;
			}
			unset($element);
		}

		// Create virtual elements for fields not backed by a real table column
		// (e.g. data joined from another table and injected post-query).
		// ele_type = 'virtual' tells dataExtractionTableForm to skip these in
		// ORDER BY and WHERE clauses.
		foreach ($extraElements as $extra) {
			if (isset($extra['typeForCaption'])) {
				$caption      = $this->_adHocElementName($extra['typeForCaption'], $nameCache);
				$element_order = $this->_adHocElementOrder($extra['typeForCaption']) ?? $fallbackOrder;
				if ($this->_adHocElementOrder($extra['typeForCaption']) === null) { $fallbackOrder += 10; }
			} else {
				$caption      = isset($extra['caption']) ? $extra['caption'] : str_replace("_", " ", $extra['handle']);
				$element_order = $fallbackOrder;
				$fallbackOrder += 10;
			}

			$element =& $element_handler->create();
			$element->setVar('ele_caption', $caption);
			$element->setVar('ele_handle', $this->_adHocCanonicalHandle(
				isset($extra['typeForCaption']) ? $extra['typeForCaption'] : '', $fid, $extra['handle']));
			$element->setVar('ele_desc', isset($extra['description']) ? $extra['description'] : "");
			$element->setVar('ele_colhead', "");
			$element->setVar('ele_required', 0);
			$element->setVar('ele_order', $element_order);
			$element->setVar('ele_forcehidden', 0);
			$element->setVar('ele_uitext', "");
			$element->setVar('ele_value', array(0=>"", 1=>5, 2=>35, 3=>""));
			$element->setVar('id_form', $fid);
			$element->setVar('ele_private', 0);
			$element->setVar('ele_display', 1);
			$element->setVar('ele_disabled', 0);
			$element->setVar('ele_type', isset($extra['typeForCaption']) ? $extra['typeForCaption'] : 'text');
			if (!$element_handler->insert($element, force: true)) {
				return false;
			}
			unset($element);
		}

		return true;
	}

	// Synchronize ad hoc table form elements with the current table schema.
	// Adds elements for new columns, removes elements for dropped columns.
	// Captions for typed columns come from the element class ->name.
	// ele_order follows the canonical userAccount type order (see getUserAccountElementTypes()).
	function syncAdHocTableFormElements($fid, $tableName, $options = array()) {
		$fid = intval($fid);
		$excludeColumns = isset($options['excludeColumns']) ? $options['excludeColumns'] : array();
		$columnLabels   = isset($options['columnLabels'])   ? $options['columnLabels']   : array();
		$columnTypes    = isset($options['columnTypes'])    ? $options['columnTypes']    : array();
		$extraElements  = isset($options['extraElements'])  ? $options['extraElements']  : array();

		$uaTypeCount   = count($this->getUserAccountElementTypes());
		$fallbackOrder = ($uaTypeCount + 1) * 10;
		$nameCache     = array();

		// Get current table columns
		$colResult = $this->db->queryF("SHOW COLUMNS FROM " . formulize_db_escape($tableName));
		$tableColumns = array();
		while ($row = $this->db->fetchRow($colResult)) {
			$tableColumns[] = $row[0];
		}

		// Index existing elements by handle — include ele_order, ele_value, and ele_desc so drift can be detected
		// and source_column can be kept up to date.
		$eleResult = $this->db->query("SELECT ele_id, ele_caption, ele_handle, ele_type, ele_order, ele_value, ele_desc FROM " . $this->db->prefix("formulize") . " WHERE id_form = " . $fid);
		$existingByHandle = array();
		while ($row = $this->db->fetchArray($eleResult)) {
			$existingEleValue = @unserialize($row['ele_value']);
			$existingByHandle[$row['ele_handle']] = array(
				'ele_id'      => $row['ele_id'],
				'ele_caption' => $row['ele_caption'],
				'ele_type'    => $row['ele_type'],
				'ele_order'   => intval($row['ele_order']),
				'ele_value'   => is_array($existingEleValue) ? $existingEleValue : array(),
				'ele_desc'    => $row['ele_desc'],
			);
		}

		$element_handler = xoops_getmodulehandler('elements', 'formulize');

		$changed = false;
		$validHandles = array();

		// Sync table-column elements
		foreach ($tableColumns as $columnName) {
			if (in_array($columnName, $excludeColumns)) {
				continue;
			}

			$desiredType   = isset($columnTypes[$columnName]) ? $columnTypes[$columnName] : 'textarea';
			$desiredHandle = $this->_adHocCanonicalHandle($desiredType, $fid, $columnName);
			$validHandles[] = $desiredHandle;

			if (isset($columnTypes[$columnName])) {
				$caption      = $this->_adHocElementName($desiredType, $nameCache);
				$desiredOrder = $this->_adHocElementOrder($desiredType) ?? $fallbackOrder;
				if ($this->_adHocElementOrder($desiredType) === null) { $fallbackOrder += 10; }
			} else {
				$caption      = isset($columnLabels[$columnName]) ? $columnLabels[$columnName] : str_replace("_", " ", $columnName);
				$desiredOrder = $fallbackOrder;
				$fallbackOrder += 10;
			}

			// Check for the element by canonical handle first, then by raw column name (migration path).
			$existingKey = isset($existingByHandle[$desiredHandle]) ? $desiredHandle
				: (isset($existingByHandle[$columnName]) ? $columnName : null);

			if ($existingKey !== null) {
				// Element already exists — update handle (rename), caption, type, order, or source_column if any have drifted
				$updates = array();
				if ($existingKey !== $desiredHandle) {
					$updates[] = "ele_handle = " . $this->db->quoteString($desiredHandle);
					// Keep the in-memory index consistent so the deletion pass below does not
					// try to delete the element we just renamed.
					$existingByHandle[$desiredHandle] = $existingByHandle[$existingKey];
					unset($existingByHandle[$existingKey]);
				}
				if ($existingByHandle[$desiredHandle]['ele_caption'] !== $caption) {
					$updates[] = "ele_caption = " . $this->db->quoteString($caption);
				}
				if ($existingByHandle[$desiredHandle]['ele_type'] !== $desiredType) {
					$updates[] = "ele_type = " . $this->db->quoteString($desiredType);
				}
				if ($existingByHandle[$desiredHandle]['ele_order'] !== $desiredOrder) {
					$updates[] = "ele_order = " . intval($desiredOrder);
				}
				// Ensure source_column is stored when the handle differs from the column name.
				if ($desiredHandle !== $columnName) {
					$existingEleVal = $existingByHandle[$desiredHandle]['ele_value'];
					if (!isset($existingEleVal['source_column'])) {
						$existingEleVal['source_column'] = $columnName;
						$updates[] = "ele_value = " . $this->db->quoteString(serialize($existingEleVal));
					}
				}
				if (!empty($updates)) {
					$this->db->queryF("UPDATE " . $this->db->prefix("formulize") . " SET " . implode(', ', $updates) . " WHERE ele_id = " . intval($existingByHandle[$desiredHandle]['ele_id']));
					$changed = true;
				}
				continue;
			}
			// New column — create element with canonical handle
			$newEleVal = array(0=>"", 1=>5, 2=>35, 3=>"");
			if ($desiredHandle !== $columnName) {
				$newEleVal['source_column'] = $columnName;
			}
			$element =& $element_handler->create();
			$element->setVar('ele_caption', $caption);
			$element->setVar('ele_handle', $desiredHandle);
			$element->setVar('ele_desc', "");
			$element->setVar('ele_colhead', "");
			$element->setVar('ele_required', 0);
			$element->setVar('ele_order', $desiredOrder);
			$element->setVar('ele_forcehidden', 0);
			$element->setVar('ele_uitext', "");
			$element->setVar('ele_value', $newEleVal);
			$element->setVar('id_form', $fid);
			$element->setVar('ele_private', 0);
			$element->setVar('ele_display', 1);
			$element->setVar('ele_disabled', 0);
			$element->setVar('ele_type', $desiredType);
			$element_handler->insert($element, force: true);
			unset($element);
			$changed = true;
		}

		// Sync extra (post-query-injected) elements
		foreach ($extraElements as $extra) {
			$desiredType   = isset($extra['typeForCaption']) ? $extra['typeForCaption'] : 'text';
			$desiredHandle = $this->_adHocCanonicalHandle($desiredType, $fid, $extra['handle']);
			$validHandles[] = $desiredHandle;

			if (isset($extra['typeForCaption'])) {
				$caption      = $this->_adHocElementName($extra['typeForCaption'], $nameCache);
				$desiredOrder = $this->_adHocElementOrder($extra['typeForCaption']) ?? $fallbackOrder;
				if ($this->_adHocElementOrder($extra['typeForCaption']) === null) { $fallbackOrder += 10; }
			} else {
				$caption      = isset($extra['caption']) ? $extra['caption'] : str_replace("_", " ", $extra['handle']);
				$desiredOrder = $fallbackOrder;
				$fallbackOrder += 10;
			}

			// Check by canonical handle first, then by original handle (migration path).
			$existingKey = isset($existingByHandle[$desiredHandle]) ? $desiredHandle
				: (isset($existingByHandle[$extra['handle']]) ? $extra['handle'] : null);

			if ($existingKey !== null) {
				$updates = array();
				if ($existingKey !== $desiredHandle) {
					$updates[] = "ele_handle = " . $this->db->quoteString($desiredHandle);
					// Keep in-memory index consistent so deletion pass below doesn't remove a renamed element.
					$existingByHandle[$desiredHandle] = $existingByHandle[$existingKey];
					unset($existingByHandle[$existingKey]);
				}
				if ($existingByHandle[$desiredHandle]['ele_caption'] !== $caption) {
					$updates[] = "ele_caption = " . $this->db->quoteString($caption);
				}
				if ($existingByHandle[$desiredHandle]['ele_type'] !== $desiredType) {
					$updates[] = "ele_type = " . $this->db->quoteString($desiredType);
				}
				if ($existingByHandle[$desiredHandle]['ele_order'] !== $desiredOrder) {
					$updates[] = "ele_order = " . intval($desiredOrder);
				}
				$desiredDesc = isset($extra['description']) ? $extra['description'] : "";
				if ($existingByHandle[$desiredHandle]['ele_desc'] !== $desiredDesc) {
					$updates[] = "ele_desc = " . $this->db->quoteString($desiredDesc);
				}
				if (!empty($updates)) {
					$this->db->queryF("UPDATE " . $this->db->prefix("formulize") . " SET " . implode(', ', $updates) . " WHERE ele_id = " . intval($existingByHandle[$desiredHandle]['ele_id']));
					$changed = true;
				}
				continue;
			}
			$element =& $element_handler->create();
			$element->setVar('ele_caption', $caption);
			$element->setVar('ele_handle', $desiredHandle);
			$element->setVar('ele_desc', isset($extra['description']) ? $extra['description'] : "");
			$element->setVar('ele_colhead', "");
			$element->setVar('ele_required', 0);
			$element->setVar('ele_order', $desiredOrder);
			$element->setVar('ele_forcehidden', 0);
			$element->setVar('ele_uitext', "");
			$element->setVar('ele_value', array(0=>"", 1=>5, 2=>35, 3=>""));
			$element->setVar('id_form', $fid);
			$element->setVar('ele_private', 0);
			$element->setVar('ele_display', 1);
			$element->setVar('ele_disabled', 0);
			$element->setVar('ele_type', $desiredType);
			$element_handler->insert($element, force: true);
			unset($element);
			$changed = true;
		}

		// Remove elements whose handle is no longer wanted
		foreach ($existingByHandle as $handle => $info) {
			if (!in_array($handle, $validHandles)) {
				$this->db->queryF("DELETE FROM " . $this->db->prefix("formulize") . " WHERE ele_id = " . intval($info['ele_id']));
				$changed = true;
			}
		}

		// Always refresh the headerlist so code-defined defaults stay in sync.
		$this->setAdHocFormHeaderlist($fid, $options);

		return true;
	}
}
