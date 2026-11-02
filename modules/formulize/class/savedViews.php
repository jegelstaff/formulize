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
 * Right now this holds the format constants, which the declaration of where elements are referenced names
 * for saved views (see $modversion['formulize_element_references']). The load and save logic in
 * entriesdisplay.php (see saveReport() and loadReport() there) is the natural next thing to move in, so
 * that there is one place that knows how a saved view is put together.
 *
 * @package Formulize
 */
#[AllowDynamicProperties]
class formulizeSavedViewsHandler {

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
		if(!$row) {
			return false;
		}
		foreach(self::ELEMENT_SETTINGS as $column) {
			if(isset($row[$column])) {
				$row[$column] = $this->columnsAs($row[$column], 'handle');
			}
		}
		return $row;
	}

	/**
	 * The settings of a view that name elements: the columns, the columns it is sorted by, and the columns
	 * whose search boxes it offers. They are stored as element ids, so that renaming an element cannot leave a
	 * view pointing at a handle that no longer exists, and handed out as handles, which is what the list works
	 * in. A view saved before they were stored as ids holds handles, which pass through either way.
	 */
	const ELEMENT_SETTINGS = array('sv_oldcols', 'sv_sort', 'sv_pubfilters');

	/**
	 * Turn the elements named in a comma separated list of columns into ids or into handles. Entries that are
	 * not elements - the metadata columns such as creation_uid, or an element that no longer exists - are
	 * left as they are, and so is the hidden column prefix.
	 * @param string $columns The columns.
	 * @param string $as 'id' or 'handle'.
	 * @return string
	 */
	private function columnsAs($columns, $as) {
		if((string) $columns === '') {
			return $columns;
		}
		$element_handler = xoops_getmodulehandler('elements', 'formulize');
		$entries = explode(',', (string) $columns);
		foreach($entries as $index => $entry) {
			$prefix = strpos($entry, self::HIDDEN_COLUMN_PREFIX) === 0 ? self::HIDDEN_COLUMN_PREFIX : '';
			$reference = substr($entry, strlen($prefix));
			if($reference === '' OR ($as == 'id') == is_numeric($reference)) {
				continue; // already in the form asked for
			}
			if($elementObject = $element_handler->get($reference)) {
				$entries[$index] = $prefix.($as == 'id' ? $elementObject->getVar('ele_id') : $elementObject->getVar('ele_handle'));
			}
		}
		return implode(',', $entries);
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
		foreach(self::ELEMENT_SETTINGS as $column) {
			if(isset($values[$column])) {
				$values[$column] = $this->columnsAs($values[$column], 'id');
			}
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
	 * Delete every saved view belonging to a form, ie: when the form itself is deleted. A view of a form that no
	 * longer exists cannot be loaded, so there is nothing to keep it for.
	 *
	 * The form is identified the way getFormFramework() does it: a view saved with a relationship stores the
	 * relationship id in sv_formframe and the form in sv_mainform; without a relationship, sv_mainform is blank
	 * and sv_formframe is the form. So sv_formframe only means this form when sv_mainform is blank; otherwise it
	 * is a relationship id, which could equal the form id by chance. The same rule is applied here in SQL, rather
	 * than by loading every view in the system to pass through getFormFramework().
	 *
	 * @param int $fid The form whose views should be deleted
	 * @return bool
	 */
	public function deleteAllForForm($fid) {
		$fid = intval($fid);
		if(!$fid) {
			return false;
		}
		$table = $this->db->prefix('formulize_saved_views');
		return (bool) $this->db->query("DELETE FROM $table WHERE sv_mainform = $fid OR ((sv_mainform IS NULL OR sv_mainform = 0) AND sv_formframe = $fid)");
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

}
