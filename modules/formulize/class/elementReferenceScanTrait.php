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
 * The shared vocabulary for answering "does this setting refer to that element, and what does it look
 * like once the element is gone?"
 *
 * Every class that stores a reference to an element uses this: the screen types, forms, saved views and
 * the elements handler itself. It is a trait rather than methods on a base class because those classes
 * have nothing else in common and no shared ancestor - the same reason
 * modules/formulize/class/adHocTableFormTrait.php is a trait.
 *
 * What belongs here is only what is common to several owners: how a conditions set is shaped, how a flat
 * list is re-indexed, how parallel delimited lists stay in step. What does NOT belong here is any
 * knowledge of a particular table or column - that lives with the class that declares it, which is the
 * whole point of the arrangement.
 *
 * Implementers provide scanForElementReferences($elementObject), returning an array of
 * elementReference() findings. formulizeElementsHandler::findReferencesToElement() discovers them with
 * method_exists(), the same way convertDependenciesForExport() discovers the config-as-code hooks.
 *
 * @package Formulize
 */
trait formulizeElementReferenceScanTrait {

	/**
	 * Does a stored element reference point at this element? A reference can be an id or a handle, because
	 * different settings store different ones and some hold legacy values from before a format changed, so
	 * both are accepted everywhere rather than per setting.
	 * @param mixed $reference The stored reference.
	 * @param int $elementId The element's id.
	 * @param string $handle The element's handle.
	 * @return bool
	 */
	protected function referencePointsAtElement($reference, $elementId, $handle) {
		if(is_array($reference) OR is_object($reference) OR $reference === '' OR $reference === null) {
			return false;
		}
		return (is_numeric($reference) AND intval($reference) === $elementId) OR ((string) $reference === (string) $handle);
	}

	/**
	 * The two things every scan needs from the element it is being asked about, read once so that each
	 * implementation does not repeat the casting.
	 * @param object $elementObject
	 * @return array array($elementId, $handle)
	 */
	protected function elementIdAndHandle($elementObject) {
		return array(intval($elementObject->getVar('ele_id')), $elementObject->getVar('ele_handle'));
	}

	/**
	 * Drop any condition naming this element out of a stored conditions array.
	 *
	 * The current format is four parallel arrays (0=>elements, 1=>operators, 2=>values, 3=>types) whose keys
	 * line up, so a condition is removed from all four at once and the result is re-indexed to keep them
	 * aligned. That alignment is load bearing: buildConditionsFilterSQL() walks $conditions[0] and reads the
	 * other three at the same key.
	 *
	 * Multipage screens can still hold the format that predates that one, where a page's conditions are
	 * array('pagecons'=>..., 'details'=>array('elements'=>..., 'ops'=>..., 'terms'=>...)).
	 * formulizeMultiPageScreen::getConditions() still reads it, so it is still live data and is handled here
	 * rather than being quietly passed over.
	 *
	 * @param mixed $conditions The stored conditions value.
	 * @param int $elementId The element's id.
	 * @param string $handle The element's handle.
	 * @return array|false The new conditions array, or false if nothing referenced the element.
	 */
	protected function removeElementFromConditions($conditions, $elementId, $handle) {
		if(!is_array($conditions)) {
			return false;
		}

		// the format that predates the parallel arrays
		if(!isset($conditions[0]) AND isset($conditions['details']) AND is_array($conditions['details'])
			AND isset($conditions['details']['elements']) AND is_array($conditions['details']['elements'])) {
			$keep = $this->conditionsToKeep($conditions['details']['elements'], $elementId, $handle);
			if($keep === false) {
				return false;
			}
			$new = array('elements'=>array(), 'ops'=>array(), 'terms'=>array());
			foreach($keep as $index) {
				foreach($new as $part => $unused) {
					$new[$part][] = isset($conditions['details'][$part][$index])
						? $conditions['details'][$part][$index]
						: ($part == 'ops' ? '=' : '');
				}
			}
			if(!$new['elements']) {
				return array(); // nothing left, which is how "no conditions" is stored everywhere else
			}
			$conditions['details'] = $new;
			return $conditions;
		}

		if(!isset($conditions[0]) OR !is_array($conditions[0])) {
			return false;
		}
		$keep = $this->conditionsToKeep($conditions[0], $elementId, $handle);
		if($keep === false) {
			return false;
		}
		$new = array(0=>array(), 1=>array(), 2=>array(), 3=>array());
		foreach($keep as $index) {
			for($part = 0; $part <= 3; $part++) {
				// part 1 is the operator, and a blank operator makes broken SQL further down, so a condition
				// that has somehow lost its operator gets the same default that parseSubmittedConditions
				// applies when it cannot make sense of a submitted one
				$new[$part][] = isset($conditions[$part][$index])
					? $conditions[$part][$index]
					: ($part === 1 ? '=' : '');
			}
		}
		// a conditions set with nothing left in it is stored as an empty array, which is what "no conditions"
		// looks like everywhere else - keeping four empty arrays would read as a set that exists but is blank
		return $new[0] ? $new : array();
	}

	/**
	 * Which keys of a conditions set's element list survive the removal of this element?
	 * @return array|false The keys to keep, or false if nothing referenced the element.
	 */
	protected function conditionsToKeep($conditionElements, $elementId, $handle) {
		$keep = array();
		foreach($conditionElements as $index => $reference) {
			if(!$this->referencePointsAtElement($reference, $elementId, $handle)) {
				$keep[] = $index;
			}
		}
		return count($keep) === count($conditionElements) ? false : $keep;
	}

	/**
	 * Drop this element out of a setting that names several elements, re-indexing what is left.
	 * @param mixed $list The stored list, as an array or as a string with a separator.
	 * @param int $elementId The element's id.
	 * @param string $handle The element's handle.
	 * @param string $delimiter What separates the entries when the list is a string.
	 * @return mixed|false The new list in the shape it came in, or false if nothing referenced the element.
	 */
	protected function removeElementFromList($list, $elementId, $handle, $delimiter = ',') {
		// Some settings hold the same list as a real array, others as ids with a separator between them,
		// depending on how the thing that wrote them felt about it - a subform's list of elements to show is
		// either, dependening on whether it came from the admin UI or from an import. Whichever came in is
		// what goes back out, so callers do not have to care and nothing gets rewritten into a shape the
		// reading code has not been shown.
		if(!is_array($list)) {
			if(!is_string($list) OR $list === '') {
				return false;
			}
			$result = $this->removeElementFromDelimitedList($list, $delimiter, $elementId, $handle);
			return $result === false ? false : $result[0];
		}
		$new = array();
		foreach($list as $reference) {
			if(!$this->referencePointsAtElement($reference, $elementId, $handle)) {
				$new[] = $reference;
			}
		}
		return count($new) === count($list) ? false : $new;
	}

	/**
	 * Drop this element out of a stored list of column rows, where each row holds the element it is about in
	 * position 0 and its own settings after that. List screen advanceviews and map screen columns are both
	 * stored this way, and both rely on their rows staying parallel to each other, so a row goes as a whole.
	 * @param mixed $rows The stored rows.
	 * @param int $elementId The element's id.
	 * @param string $handle The element's handle.
	 * @return array|false The new rows, or false if nothing referenced the element.
	 */
	protected function removeElementFromColumnRows($rows, $elementId, $handle) {
		if(!is_array($rows)) {
			return false;
		}
		$new = array();
		foreach($rows as $row) {
			if(is_array($row) AND isset($row[0]) AND $this->referencePointsAtElement($row[0], $elementId, $handle)) {
				continue;
			}
			$new[] = $row;
		}
		return count($new) === count($rows) ? false : $new;
	}

	/**
	 * Clear a setting that names a single element, when that element is the one going.
	 *
	 * 'none' rather than an empty string or a zero, because that is the value the element editor writes when
	 * a person picks nothing - see the 'none' checks in selectElement.php - and getEleValueDependencies()
	 * filters it out for the same reason. Writing anything else would be a value the reading code has never
	 * been shown.
	 *
	 * @param mixed $value The stored reference.
	 * @param int $elementId The element's id.
	 * @param string $handle The element's handle.
	 * @return string|false The new value, or false if this setting did not name the element.
	 */
	protected function clearReferenceToElement($value, $elementId, $handle) {
		return $this->referencePointsAtElement($value, $elementId, $handle) ? 'none' : false;
	}

	/**
	 * Drop this element out of a stored set of defaults, which are keyed by the element they are for.
	 * @param mixed $defaults The stored defaults.
	 * @param int $elementId The element's id.
	 * @param string $handle The element's handle.
	 * @return array|false The new defaults, or false if nothing referenced the element.
	 */
	protected function removeElementFromDefaults($defaults, $elementId, $handle) {
		if(!is_array($defaults)) {
			return false;
		}
		$new = array();
		foreach($defaults as $reference => $value) {
			if(!$this->referencePointsAtElement($reference, $elementId, $handle)) {
				$new[$reference] = $value; // keyed by element, so the keys have to be kept as they are
			}
		}
		return count($new) === count($defaults) ? false : $new;
	}

	/**
	 * Drop this element out of a delimited list, and say which positions went.
	 *
	 * The positions are the point of this: a saved view stores several lists that are parallel to each other
	 * by position and nothing else - the searches line up with the columns, the calculations line up with the
	 * columns they are calculated on. Removing an entry from one list without removing the same position from
	 * the others silently shifts every later entry onto the wrong column.
	 *
	 * @param mixed $value The stored list.
	 * @param string $delimiter What separates the entries.
	 * @param int $elementId The element's id.
	 * @param string $handle The element's handle.
	 * @param string $prefix Optional prefix an entry may carry that is not part of the reference.
	 * @return array|false array($newValue, array of removed positions), or false if nothing referenced it.
	 */
	protected function removeElementFromDelimitedList($value, $delimiter, $elementId, $handle, $prefix = '') {
		$items = explode($delimiter, (string) $value);
		$kept = array();
		$removed = array();
		foreach($items as $index => $item) {
			$bare = ($prefix !== '' AND strpos($item, $prefix) === 0) ? substr($item, strlen($prefix)) : $item;
			if($this->referencePointsAtElement($bare, $elementId, $handle)) {
				$removed[] = $index;
			} else {
				$kept[] = $item;
			}
		}
		return $removed ? array(implode($delimiter, $kept), $removed) : false;
	}

	/**
	 * Drop the given positions out of a delimited list, for the lists that run parallel to one that has just
	 * had entries removed by removeElementFromDelimitedList().
	 * @param mixed $value The stored list.
	 * @param string $delimiter What separates the entries.
	 * @param array $positions The positions to remove.
	 * @return string The new list.
	 */
	protected function removePositionsFromDelimitedList($value, $delimiter, $positions) {
		$items = explode($delimiter, (string) $value);
		foreach($positions as $position) {
			unset($items[$position]);
		}
		return implode($delimiter, $items);
	}

	/**
	 * Name something the way the usage report refers to it: what it is called, and what its id is.
	 * @param string $name The thing's name, which may be blank or carry markup.
	 * @param string $idLabel What its id is called.
	 * @param int $id The id.
	 * @return string
	 */
	protected function describeById($name, $idLabel, $id) {
		$name = trim(strip_tags((string) $name));
		return ($name === '' ? '(untitled)' : $name).' ('.$idLabel.' '.intval($id).')';
	}

	/**
	 * One place an element is referenced from.
	 *
	 * @param string $section The report heading this is grouped under.
	 * @param string $description What a person reads.
	 * @param string $table The unprefixed table holding the reference, or '' when the reference is cleaned
	 *                      up by something other than removeElementReferences() and this is a report entry.
	 * @param string $keyColumn The primary key column of that table.
	 * @param int $keyValue The row's primary key.
	 * @param array $updates column => the exact literal to write. Empty means there is nothing to write.
	 * @return array
	 */
	protected function elementReference($section, $description, $table, $keyColumn, $keyValue, $updates = array()) {
		return array(
			'section' => $section,
			'description' => $description,
			'table' => $table,
			'key_column' => $keyColumn,
			'key_value' => intval($keyValue),
			'updates' => $updates
		);
	}
}
