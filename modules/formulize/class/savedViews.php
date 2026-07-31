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
