<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2007 Freeform Solutions                  ##
###############################################################################
##  This program is free software; you can redistribute it and/or modify     ##
##  it under the terms of the GNU General Public License as published by     ##
##  the Free Software Foundation; either version 2 of the License, or        ##
##  (at your option) any later version.                                      ##
##                                                                           ##
##  This program is distributed in the hope that it will be useful,          ##
##  but WITHOUT ANY WARRANTY; without even the implied warranty of           ##
##  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            ##
##  GNU General Public License for more details.                             ##
###############################################################################
##  Project: Formulize                                                       ##
###############################################################################

if (!defined("XOOPS_ROOT_PATH")) {
    die("XOOPS root path not defined");
}

require_once XOOPS_ROOT_PATH.'/modules/formulize/class/elementReferenceScanTrait.php';

/**
 * Saved views - the columns, searches, calculations and sort order a person saved off a list screen.
 *
 * This class exists to give that storage format an owner. A saved view is several lists tied to each other
 * by position and nothing else: sv_oldcols is comma separated, sv_quicksearches holds one entry per column
 * joined by a delimiter that appears nowhere else in the system, and sv_calc_cols runs parallel to three
 * more lists describing what is calculated on each column. Knowledge of that arrangement was previously
 * split between include/entriesdisplay.php, which builds and reads it, and the element deletion cleanup,
 * which rewrites it - and the two fell out of step, which is how searches ended up being applied to the
 * wrong columns after a column was removed.
 *
 * Right now this holds the format constants and the reference scan. The load and save logic in
 * entriesdisplay.php (see saveReport() and loadReport() there) is the natural next thing to move in, so
 * that there is one place that knows how a saved view is put together.
 *
 * @package Formulize
 */
#[AllowDynamicProperties]
class formulizeSavedViewsHandler {

	use formulizeElementReferenceScanTrait;

	// One search per column, in the same order as the columns. Chosen to be something that will not occur
	// in a search term. See where $qsearches is assembled in include/entriesdisplay.php.
	const SEARCH_DELIMITER = '&*=%4#';

	// Separates the calculation columns, and equally the three lists that run parallel to them:
	// sv_calc_calcs, sv_calc_blanks and sv_calc_grouping.
	const CALCULATION_DELIMITER = '/';

	// A column carried in the view only so that a persistent search on it survives, without the column
	// itself being shown to anyone.
	const HIDDEN_COLUMN_PREFIX = 'hiddencolumn_';

	var $db;

	function __construct(&$db) {
		$this->db =& $db;
	}

	function &getInstance(&$db) {
		static $instance;
		if (!isset($instance)) {
			$instance = new formulizeSavedViewsHandler($db);
		}
		return $instance;
	}

	/**
	 * Read one saved view.
	 *
	 * By id, or by name within a form. A name is only unique within the form (and relationship) the view was
	 * saved against, so a form has to be given to look one up that way. Asking by name alone is refused
	 * rather than answered: names repeat across forms, so the answer would be whichever view happened to be
	 * created first anywhere in the system, and loading its columns and searches into a list belonging to a
	 * different form is not a useful thing to do quietly.
	 *
	 * @param int|string $idOrName The sv_id, or the sv_name.
	 * @param int $fid The form the view belongs to. Required when looking up by name.
	 * @param int $frid The relationship the view belongs to, if there is one.
	 * @return array|false The row, or false if there is no such view.
	 * @throws Exception if asked for a name with no form, or if the query fails.
	 */
	public function get($idOrName, $fid = 0, $frid = 0) {
		$table = $this->db->prefix('formulize_saved_views');
		if(is_numeric($idOrName)) {
			$sql = "SELECT * FROM $table WHERE sv_id = ".intval($idOrName);
		} elseif($fid OR $frid) {
			// a view records the form and relationship that were in effect when it was saved, and when there
			// is no relationship the mainform is stored blank rather than as the form's own id
			$formframe = $frid ? intval($frid) : intval($fid);
			$mainform = $frid ? intval($fid) : "''";
			$sql = "SELECT * FROM $table WHERE sv_name = ".$this->db->quoteString($idOrName)."
				AND sv_formframe = $formframe AND sv_mainform = $mainform ORDER BY sv_id";
		} else {
			throw new Exception("A saved view can only be looked up by name within a form. No form was given for: '".strip_tags(htmlspecialchars($idOrName))."'");
		}
		if(!$result = $this->db->query($sql)) {
			throw new Exception("Could not load the specified saved view: '".strip_tags(htmlspecialchars($idOrName))."'");
		}
		$row = $this->db->fetchArray($result);
		return $row ? $row : false;
	}

	/**
	 * Write a saved view, and say which one it was.
	 *
	 * Takes the columns to write rather than a fixed argument list, so that a column added to the table
	 * later is a change to the caller assembling the values and to nothing here.
	 *
	 * @param array $values Column name => value. Values are written as given, so anything that has to be a
	 *                      number should already be one.
	 * @param int $svId The view to update, or 0 to create a new one.
	 * @return int|false The view's id, or false if it could not be written.
	 */
	public function save($values, $svId = 0) {
		if(!is_array($values) OR !$values) {
			return false;
		}
		$sets = array();
		foreach($values as $column => $value) {
			$sets[] = "`$column` = ".$this->db->quoteString((string) $value);
		}
		$table = $this->db->prefix('formulize_saved_views');
		if($svId = intval($svId)) {
			return $this->db->query("UPDATE $table SET ".implode(', ', $sets)." WHERE sv_id = $svId") ? $svId : false;
		}
		if(!$this->db->query("INSERT INTO $table SET ".implode(', ', $sets))) {
			return false;
		}
		return intval($this->db->getInsertId());
	}

	/**
	 * Delete a saved view.
	 * @param int $svId
	 * @return bool
	 */
	public function delete($svId) {
		$table = $this->db->prefix('formulize_saved_views');
		return (bool) $this->db->query("DELETE FROM $table WHERE sv_id = ".intval($svId));
	}

	/**
	 * Who owns a saved view? Cached, because whether a person may change or delete a view is asked about the
	 * same view several times over a single page load.
	 * @param int $svId
	 * @return int|false The owner's uid, or false if there is no such view.
	 */
	public function getOwner($svId) {
		static $owners = array();
		$svId = intval($svId);
		if(!isset($owners[$svId])) {
			$table = $this->db->prefix('formulize_saved_views');
			$owners[$svId] = false;
			if($result = $this->db->query("SELECT sv_owner_uid FROM $table WHERE sv_id = $svId")) {
				$row = $this->db->fetchArray($result);
				$owners[$svId] = ($row AND intval($row['sv_owner_uid']) > 0) ? intval($row['sv_owner_uid']) : false;
			}
		}
		return array_key_exists($svId, $owners) ? $owners[$svId] : false;
	}

	/**
	 * Where does a saved view refer to an element, and what does the view look like once the element is
	 * gone? The columns someone chose, the searches on them, the calculations, and the sort column.
	 *
	 * The parallel lists are the whole reason this needs care: removing a column without removing the same
	 * position from the searches shifts every later search onto the wrong column, which shows up as a list
	 * quietly filtering on something nobody asked it to.
	 *
	 * Called by formulizeElementsHandler::findReferencesToElement().
	 *
	 * @param object $elementObject The element being asked about.
	 * @return array The references found.
	 */
	public function scanForElementReferences($elementObject) {
		list($elementId, $handle) = $this->elementIdAndHandle($elementObject);
		$references = array();
		// the LIKE clauses are a coarse filter only. An underscore is a single character wildcard in LIKE and
		// handles are full of them, so this deliberately catches more rows than it needs to and the precise
		// test happens below.
		$sql = "SELECT sv_id, sv_name, sv_oldcols, sv_quicksearches, sv_sort, sv_calc_cols, sv_calc_calcs,
			sv_calc_blanks, sv_calc_grouping FROM ".$this->db->prefix('formulize_saved_views')."
			WHERE sv_oldcols LIKE ".$this->db->quoteString('%'.$handle.'%')."
			OR sv_calc_cols LIKE ".$this->db->quoteString('%'.intval($elementId).'%')."
			OR sv_sort = ".$this->db->quoteString($handle);
		if(!$result = $this->db->query($sql)) {
			return $references;
		}
		while($row = $this->db->fetchArray($result)) {
			$updates = array();
			$usedAs = array();

			// the columns of the view, one of which can carry a hiddencolumn_ prefix when it was included for
			// the sake of a persistent search without being shown. The searches are stored one per column in
			// the same order, so the positions that come out of the columns come out of the searches too.
			if($columns = $this->removeElementFromDelimitedList($row['sv_oldcols'], ',', $elementId, $handle, self::HIDDEN_COLUMN_PREFIX)) {
				list($newColumns, $removedPositions) = $columns;
				$updates['sv_oldcols'] = $newColumns;
				$updates['sv_quicksearches'] = $this->removePositionsFromDelimitedList($row['sv_quicksearches'], self::SEARCH_DELIMITER, $removedPositions);
				$usedAs[] = 'a column';
			}

			// the calculations, which name their column by element id, and which run parallel to the three
			// settings saying what is calculated, how blanks are treated and how results are grouped
			if($calculations = $this->removeElementFromDelimitedList($row['sv_calc_cols'], self::CALCULATION_DELIMITER, $elementId, $handle)) {
				list($newCalcCols, $removedPositions) = $calculations;
				$updates['sv_calc_cols'] = $newCalcCols;
				foreach(array('sv_calc_calcs', 'sv_calc_blanks', 'sv_calc_grouping') as $parallelSetting) {
					$updates[$parallelSetting] = $this->removePositionsFromDelimitedList($row[$parallelSetting], self::CALCULATION_DELIMITER, $removedPositions);
				}
				$usedAs[] = 'a calculation';
			}

			if((string) $row['sv_sort'] === (string) $handle) {
				$updates['sv_sort'] = ''; // the list falls back to its default order
				$usedAs[] = 'the column it is sorted by';
			}

			if($usedAs) {
				$references[] = $this->elementReference(
					_AM_ELE_USAGE_SECTION_SAVED_VIEWS,
					$this->describeById($row['sv_name'], 'view', $row['sv_id']).' - '.implode(', ', $usedAs),
					'formulize_saved_views', 'sv_id', $row['sv_id'], $updates
				);
			}
		}
		return $references;
	}
}
