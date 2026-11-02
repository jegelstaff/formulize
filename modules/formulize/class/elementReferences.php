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
 * Everywhere one element is referred to, worked out from a single declaration of where references can be.
 *
 * The declaration is $modversion['formulize_element_references'] in xoops_version.php: for each table, the
 * columns that can refer to an element, the shape of what is stored in each, and whether it holds an id, a
 * handle or a {handle} token. What an element type keeps in its own ele_value is declared on that type's
 * handler, as a static $eleValueReferences in the same format, because element types are separate classes
 * that can be added without touching anything else.
 *
 * Everything that needs to know where an element is used reads it from here: the usage report, the clean up
 * when an element is deleted, the rewrite when its handle changes, and the update patch looking for handles
 * that were never brought up to date. They cannot disagree about where a reference is, because none of them
 * knows on its own.
 *
 * Each shape is understood by one method below, which finds a reference at the positions that shape has,
 * and only there. A handle that happens to be written somewhere else in the same setting - the value a
 * condition compares against, a search term - is not a reference to the element and is never changed.
 *
 * @package Formulize
 */
class formulizeElementReferences {

	private $db;
	private $elementId;
	private $handle;
	private $formId;

	/**
	 * @param int $elementId The element's id.
	 * @param string $handle The handle to look for, exactly as references to it are written. Usually the
	 *   element's handle, but a rename looks for the handle it had before, and the update patch for old
	 *   spellings of it.
	 * @param int $formId The element's form.
	 */
	public function __construct($elementId, $handle, $formId) {
		global $xoopsDB;
		$this->db = $xoopsDB;
		$this->elementId = intval($elementId);
		$this->handle = (string) $handle;
		$this->formId = intval($formId);
		include_once XOOPS_ROOT_PATH.'/modules/formulize/class/savedViews.php'; // the declaration names its delimiters
	}

	/**
	 * @param object $elementObject The element.
	 * @param string|null $handle The handle to look for, if not the element's own - see the constructor.
	 * @return formulizeElementReferences
	 */
	public static function forElement($elementObject, $handle = null) {
		return new self($elementObject->getVar('ele_id'), $handle === null ? $elementObject->getVar('ele_handle') : $handle, $elementObject->getVar('fid'));
	}

	/**
	 * The declaration of every table that can refer to an element, from xoops_version.php.
	 * @return array table => its declaration
	 */
	public static function declaration() {
		static $declaration = null;
		if($declaration === null) {
			$module_handler = xoops_gethandler('module');
			$formulizeModule = $module_handler->getByDirname('formulize');
			$declared = $formulizeModule ? $formulizeModule->getInfo('formulize_element_references') : false;
			$declaration = is_array($declared) ? $declared : array();
		}
		return $declaration;
	}

	/**
	 * What an element type declares about the references in its ele_value.
	 * @param string $type The element type.
	 * @return array ele_value key => its declaration
	 */
	public static function eleValueDeclaration($type) {
		static $declarations = array();
		if(!isset($declarations[$type])) {
			$declarations[$type] = array();
			$file = XOOPS_ROOT_PATH.'/modules/formulize/class/'.basename((string) $type).'Element.php';
			if(file_exists($file)) {
				require_once $file;
				$class = 'formulize'.ucfirst($type).'ElementHandler';
				if(class_exists($class, false) AND property_exists($class, 'eleValueReferences') AND is_array($class::$eleValueReferences)) {
					$declarations[$type] = $class::$eleValueReferences;
				}
			}
		}
		return $declarations[$type];
	}

	/**
	 * Every setting that refers to the element, for the usage report and for the clean up when it is deleted.
	 *
	 * Each one says where it is, what a person reads about it, and what deleting the element writes there.
	 * A reference that a deletion leaves for a person, or that other code cleans up, has no updates.
	 *
	 * @return array Each with section, description, table, key_column, key_value and updates.
	 */
	public function references() {
		$references = array();
		foreach(self::declaration() as $table => $tableSpec) {
			foreach($this->candidateRows($table, $tableSpec) as $row) {
				if($this->isTheElementItself($tableSpec, $row)) {
					continue;
				}
				$working = $row; // later columns see what earlier ones changed - parallel lists, coupled resets
				$updates = array();
				$phrases = array();
				foreach($tableSpec['columns'] as $column => $spec) {
					if(!array_key_exists($column, $working) OR !$this->appliesToRow($spec, $row)) {
						continue;
					}
					$result = $this->walkColumn($spec, $working[$column], 'remove', null, $row);
					if(!$result['found']) {
						continue;
					}
					foreach($result['phrases'] as $phrase) {
						$phrases[] = $this->fillPlaceholders($phrase, $row);
					}
					if(!$result['changed']) {
						continue;
					}
					$updates[$column] = $working[$column] = $result['stored'];
					foreach($this->option($spec, 'parallel', array()) as $parallelColumn => $delimiter) {
						if(array_key_exists($parallelColumn, $working)) {
							$updates[$parallelColumn] = $working[$parallelColumn] =
								self::removePositions($working[$parallelColumn], self::resolve($delimiter), $result['positions']);
						}
					}
					foreach($this->option($spec, 'also_set', array()) as $alsoColumn => $alsoValue) {
						$updates[$alsoColumn] = $working[$alsoColumn] = $alsoValue;
					}
				}
				if($phrases) {
					$references[] = array(
						'section' => self::resolve($tableSpec['section']),
						'description' => $this->label($tableSpec, $row).' - '.implode(', ', array_unique($phrases)),
						'table' => $table,
						'key_column' => $tableSpec['key'],
						'key_value' => intval($row[$tableSpec['key']]),
						'updates' => $updates
					);
				}
			}
		}
		return $references;
	}

	/**
	 * Where a person has written the element's handle into text or code - a {handle} token, a notification's
	 * {ELEMENT_HANDLE} tag, a $handle variable in code kept in the database. These are not cleaned up when
	 * the element is deleted, because removing a name from someone's text or code changes what it means.
	 * They are reported instead.
	 * @return array Each with table, key_value, description and row.
	 */
	public function mentions() {
		$mentions = array();
		foreach(self::declaration() as $table => $tableSpec) {
			foreach($this->candidateRows($table, $tableSpec) as $row) {
				if($this->isTheElementItself($tableSpec, $row)) {
					continue;
				}
				foreach($tableSpec['columns'] as $column => $spec) {
					if(array_key_exists($column, $row) AND $this->walkColumn($spec, $row[$column], 'mention', null, $row)['found']) {
						$mentions[] = array(
							'table' => $table,
							'key_value' => intval($row[$tableSpec['key']]),
							'description' => $this->label($tableSpec, $row).' refers to this element by name in its settings',
							'row' => $row
						);
						break; // once per row is enough for a person to go and look
					}
				}
			}
		}
		return $mentions;
	}

	/**
	 * Rewrite every reference to the old handle so it names the new one: handles at the positions declared
	 * to hold them, {handle} tokens, and notification tags. Ids are left as they are, because a rename does
	 * not change an id. Code is left as it is, because it is a person's to change - see mentions().
	 * @param string $newHandle The handle as it is now.
	 * @return void
	 */
	public function rename($newHandle) {
		$newHandle = (string) $newHandle;
		if($newHandle === '' OR $newHandle === $this->handle) {
			return;
		}
		foreach(self::declaration() as $table => $tableSpec) {
			foreach($this->candidateRows($table, $tableSpec) as $row) {
				$sets = array();
				foreach($tableSpec['columns'] as $column => $spec) {
					if(!array_key_exists($column, $row)) {
						continue;
					}
					$result = $this->walkColumn($spec, $row[$column], 'rename', $newHandle, $row);
					if($result['changed']) {
						$sets[] = "`$column` = ".$this->db->quoteString((string) $result['stored']);
					}
				}
				if($sets) {
					// queryF, because a rename can be made from a GET request - an update patch run from a link
					$this->db->queryF("UPDATE ".$this->db->prefix($table)." SET ".implode(', ', $sets)
						." WHERE `".$tableSpec['key']."` = ".intval($row[$tableSpec['key']]));
				}
			}
		}
	}

	/**
	 * The rows of a table that could refer to the element: a coarse filter in SQL, on the handle or the id
	 * appearing anywhere in a declared column. The collation ignores case and the underscores in a handle
	 * are wildcards, so this reads more rows than it needs to. Which of them really refer to the element is
	 * decided column by column, at the declared positions.
	 * @param string $table The unprefixed table name.
	 * @param array $tableSpec Its declaration.
	 * @return array The rows, with any columns the label needs from other tables.
	 */
	private function candidateRows($table, $tableSpec) {
		$selects = array('t.*');
		$joins = '';
		foreach($this->option($tableSpec, 'joins', array()) as $index => $join) {
			$alias = 'j'.intval($index);
			$on = array();
			foreach($join['on'] as $localColumn => $joinedColumn) {
				$on[] = "$alias.`$joinedColumn` = t.`$localColumn`";
			}
			$joins .= " LEFT JOIN ".$this->db->prefix($join['table'])." $alias ON ".implode(' AND ', $on);
			foreach($join['columns'] as $joinedColumn => $as) {
				$selects[] = "$alias.`$joinedColumn` AS `$as`";
			}
		}
		$likes = array();
		$terms = array_unique(array($this->handle, strtoupper($this->handle), (string) $this->elementId));
		foreach(array_keys($tableSpec['columns']) as $column) {
			foreach($terms as $term) {
				$likes[] = "t.`$column` LIKE ".$this->db->quoteString('%'.$term.'%');
			}
		}
		$rows = array();
		if($likes AND $result = $this->db->query("SELECT ".implode(', ', $selects)." FROM ".$this->db->prefix($table)." t $joins WHERE ".implode(' OR ', $likes))) {
			while($row = $this->db->fetchArray($result)) {
				$rows[] = $row;
			}
		}
		return $rows;
	}

	/**
	 * Is this row the element itself? Its own settings are not a reference to it, and go when it does. A
	 * rename still rewrites them, because its own caption can carry its own {handle} token.
	 */
	private function isTheElementItself($tableSpec, $row) {
		return $this->option($tableSpec, 'own_row', false) AND intval($row[$tableSpec['key']]) === $this->elementId;
	}

	/**
	 * Some references only count when another column of the row says they are about the element's form, as
	 * a relationship's keys do.
	 */
	private function appliesToRow($spec, $row) {
		if($formColumn = $this->option($spec, 'when_form_is', false)) {
			return intval($row[$formColumn]) === $this->formId;
		}
		return true;
	}

	/**
	 * Read one stored column, walk it, and say what to write back.
	 * @param array $spec The column's declaration.
	 * @param mixed $stored What the column holds.
	 * @param string $mode 'remove' for a deletion, 'rename', or 'mention'.
	 * @param string|null $newHandle For a rename.
	 * @param array $row The whole row, which per-type ele_value declarations need for ele_type.
	 * @return array found, changed, stored (the new column value), phrases, positions.
	 */
	private function walkColumn($spec, $stored, $mode, $newHandle, $row) {
		$none = array('found' => false, 'changed' => false, 'stored' => $stored, 'phrases' => array(), 'positions' => array());
		if($stored === null OR $stored === '') {
			return $none;
		}
		$serialized = self::storedSerialized($spec);
		if($serialized) {
			if($classFile = $this->option($spec, 'class_file', false)) {
				include_once XOOPS_ROOT_PATH.'/modules/formulize/class/'.basename($classFile);
				$value = @unserialize((string) $stored);
			} else {
				$value = @unserialize((string) $stored, array('allowed_classes' => false));
			}
			if($value === false) {
				return $spec['shape'] == 'property' ? $this->unreadableObjects($spec, $stored, $mode) : $none;
			}
		} else {
			$value = $stored;
		}
		$result = $this->walk($spec, $value, $mode, $newHandle, $row);
		$result['stored'] = $result['changed'] ? ($serialized ? serialize($result['value']) : (string) $result['value']) : $stored;
		return $result;
	}

	/**
	 * Which shapes are stored as a serialized PHP value, rather than as text.
	 */
	private static function storedSerialized($spec) {
		switch($spec['shape']) {
			case 'rows': case 'conditions': case 'keys': case 'each': case 'path': case 'property': case 'per_type':
				return true;
			case 'list':
				return !isset($spec['delimiter']);
		}
		return false;
	}

	/**
	 * Walk one value according to its shape. Each shape knows where in the value a reference can be.
	 * @return array found, changed, value, phrases, positions.
	 */
	private function walk($spec, $value, $mode, $newHandle, $row) {
		switch($spec['shape']) {
			case 'value': $result = $this->walkValue($spec, $value, $mode, $newHandle); break;
			case 'list': $result = $this->walkList($spec, $value, $mode, $newHandle); break;
			case 'rows': $result = $this->walkRows($spec, $value, $mode, $newHandle); break;
			case 'conditions': $result = $this->walkConditions($spec, $value, $mode, $newHandle); break;
			case 'keys': $result = $this->walkKeys($spec, $value, $mode, $newHandle); break;
			case 'each': $result = $this->walkEach($spec, $value, $mode, $newHandle, $row); break;
			case 'path': $result = $this->walkPath($spec, $value, $mode, $newHandle); break;
			case 'property': $result = $this->walkProperty($spec, $value, $mode, $newHandle); break;
			case 'linked_source': $result = $this->walkLinkedSource($spec, $value, $mode, $newHandle); break;
			case 'tokens': $result = $this->walkTokens($spec, $value, $mode, $newHandle); break;
			case 'tags': $result = $this->walkTags($spec, $value, $mode, $newHandle); break;
			case 'code': $result = $this->walkCode($spec, $value, $mode); break;
			case 'per_type': $result = $this->walkPerType($spec, $value, $mode, $newHandle, $row); break;
			default: $result = self::unchanged($value);
		}
		$result += array('phrases' => array(), 'positions' => array());
		if($mode == 'remove' AND $result['found']) {
			// a reference a deletion leaves for a person, or that other code cleans up, is described and not written.
			// Only where that is declared: per_type has no deletion behaviour of its own, its keys each have theirs.
			if(in_array($this->option($spec, 'on_delete', null), array('report', 'elsewhere'), true)) {
				$result['changed'] = false;
				$result['value'] = $value;
			}
			if(!$result['phrases'] AND isset($spec['describe'])) {
				$result['phrases'] = array($spec['describe']);
			}
		}
		return $result;
	}

	/**
	 * The whole value is the reference.
	 */
	private function walkValue($spec, $value, $mode, $newHandle) {
		$holds = $this->option($spec, 'holds', 'either');
		if($mode == 'remove' AND $this->matches($value, $holds)) {
			return self::changedTo($this->option($spec, 'set_to', ''));
		}
		if($mode == 'rename' AND $this->renamable($value, $holds)) {
			return self::changedTo($newHandle);
		}
		return self::unchanged($value);
	}

	/**
	 * Each entry of a list is a reference. The list is an array, or text with a delimiter between the entries,
	 * or text with several levels of delimiter when 'delimiter' is a list of them. Whichever it came in as is
	 * what goes back out. Entries can carry a prefix that is not part of the reference.
	 */
	private function walkList($spec, $value, $mode, $newHandle) {
		$delimiters = (array) $this->option($spec, 'delimiter', ',');
		$delimiter = self::resolve(array_shift($delimiters));
		if(!is_array($value) AND $delimiters) {
			// one level of the list, each part of which is a list at the next level
			$parts = explode($delimiter, (string) $value);
			$found = false;
			$changed = false;
			$inner = array_merge($spec, array('delimiter' => $delimiters));
			foreach($parts as $index => $part) {
				$result = $this->walkList($inner, $part, $mode, $newHandle);
				$found = $found || $result['found'];
				if($result['changed']) {
					$parts[$index] = $result['value'];
					$changed = true;
				}
			}
			return array('found' => $found, 'changed' => $changed, 'value' => implode($delimiter, $parts));
		}
		if(is_array($value)) {
			$items = $value;
		} elseif(is_string($value) OR is_numeric($value)) {
			$items = explode($delimiter, (string) $value);
		} else {
			return self::unchanged($value);
		}
		$holds = $this->option($spec, 'holds', 'either');
		$prefix = self::resolve($this->option($spec, 'prefix', ''));
		$kept = array();
		$positions = array();
		$changed = false;
		foreach($items as $index => $item) {
			$bare = ($prefix !== '' AND is_string($item) AND strpos($item, $prefix) === 0) ? substr($item, strlen($prefix)) : $item;
			if($mode == 'remove' AND $this->matches($bare, $holds)) {
				$positions[] = $index;
				continue;
			}
			if($mode == 'rename' AND $this->renamable($bare, $holds)) {
				$item = ($bare === $item ? '' : $prefix).$newHandle;
				$changed = true;
			}
			$kept[$index] = $item;
		}
		if($mode == 'remove') {
			if(!$positions) {
				return self::unchanged($value);
			}
			if(!$kept AND $this->option($spec, 'on_delete', 'remove') == 'remove_unless_empty') {
				// an empty list means something else - for a form screen, show every element - so it is not emptied
				return array('found' => true, 'changed' => false, 'value' => $value,
					'phrases' => array($this->option($spec, 'describe_when_emptied', $this->option($spec, 'describe', ''))));
			}
			$kept = array_values($kept);
			return array('found' => true, 'changed' => true, 'value' => is_array($value) ? $kept : implode($delimiter, $kept), 'positions' => $positions);
		}
		if($changed) {
			return self::changedTo(is_array($value) ? $kept : implode($delimiter, $kept));
		}
		return self::unchanged($value);
	}

	/**
	 * Each row of a list of rows refers to an element at one position, and its other positions are that
	 * element's settings - so a row goes as a whole.
	 */
	private function walkRows($spec, $value, $mode, $newHandle) {
		if(!is_array($value)) {
			return self::unchanged($value);
		}
		$at = $this->option($spec, 'at', 0);
		$holds = $this->option($spec, 'holds', 'either');
		$new = array();
		$found = false;
		$changed = false;
		foreach($value as $index => $row) {
			if(is_array($row) AND isset($row[$at])) {
				if($mode == 'remove' AND $this->matches($row[$at], $holds)) {
					$found = $changed = true;
					continue;
				}
				if($mode == 'rename' AND $this->renamable($row[$at], $holds)) {
					$row[$at] = $newHandle;
					$changed = true;
				}
			}
			$new[$index] = $row;
		}
		if($mode == 'remove') {
			$new = array_values($new);
		}
		return array('found' => $found, 'changed' => $changed, 'value' => $changed ? $new : $value);
	}

	/**
	 * A conditions set. The current format is four parallel arrays - 0 the elements, 1 the operators, 2 the
	 * terms, 3 the types - whose keys line up, and buildConditionsFilterSQL() walks 0 and reads the others at
	 * the same key, so a condition is removed from all four at once and the result re-indexed. Multipage
	 * screens can still hold the format before that one, array('pagecons'=>..., 'details'=>array('elements',
	 * 'ops', 'terms')), which formulizeMultiPageScreen::getConditions() still reads.
	 *
	 * The element is only ever at position 0. The terms are what a condition compares against, and a term
	 * that happens to be the handle is a value, not a reference - but a term can hold a {handle} token, or a
	 * {search_handle} token naming a list's search box for that element, and those are renamed and mentioned.
	 *
	 * Notification conditions store the first three arrays serialized one by one inside the serialized
	 * value ('serialized_parts').
	 */
	private function walkConditions($spec, $value, $mode, $newHandle) {
		if(!is_array($value)) {
			return self::unchanged($value);
		}
		$serializedParts = $this->option($spec, 'serialized_parts', false);
		if($serializedParts) {
			$parts = array();
			for($part = 0; $part <= 2; $part++) {
				$parts[$part] = isset($value[$part]) ? @unserialize((string) $value[$part], array('allowed_classes' => false)) : array();
				if(!is_array($parts[$part])) {
					return self::unchanged($value);
				}
			}
			$result = $this->walkConditionParts(array('elements' => 0, 'ops' => 1, 'terms' => 2, 'types' => null), $parts, $spec, $mode, $newHandle);
			if($result['changed']) {
				$result['value'] = array(serialize($result['value'][0]), serialize($result['value'][1]), serialize($result['value'][2]));
			}
			return $result;
		}
		if(!isset($value[0]) AND isset($value['details']) AND is_array($value['details'])) {
			$result = $this->walkConditionParts(array('elements' => 'elements', 'ops' => 'ops', 'terms' => 'terms', 'types' => null), $value['details'], $spec, $mode, $newHandle);
			if($result['changed']) {
				// nothing left is stored as an empty array, the way "no conditions" is stored everywhere else
				$result['value'] = ($mode == 'remove' AND !$result['value']['elements']) ? array() : array_merge($value, array('details' => $result['value']));
			}
			return $result;
		}
		$result = $this->walkConditionParts(array('elements' => 0, 'ops' => 1, 'terms' => 2, 'types' => 3), $value, $spec, $mode, $newHandle);
		if($result['changed'] AND $mode == 'remove' AND !$result['value'][0]) {
			$result['value'] = array(); // nothing left, which is how "no conditions" is stored everywhere else
		}
		return $result;
	}

	/**
	 * The work on a conditions set once its parts have been named, whichever format it came in.
	 * @param array $keys Which key of $parts holds the elements, the operators, the terms and the types.
	 */
	private function walkConditionParts($keys, $parts, $spec, $mode, $newHandle) {
		$elementsKey = $keys['elements'];
		if(!isset($parts[$elementsKey]) OR !is_array($parts[$elementsKey])) {
			return self::unchanged($parts);
		}
		$holds = $this->option($spec, 'holds', 'either');
		$termsKey = $keys['terms'];
		$tokenPrefixes = $this->option($spec, 'token_prefixes', array('', 'search_'));

		if($mode == 'mention') {
			foreach((array) (isset($parts[$termsKey]) ? $parts[$termsKey] : array()) as $term) {
				if(is_string($term) AND $this->hasToken($term, $tokenPrefixes)) {
					return array('found' => true, 'changed' => false, 'value' => $parts);
				}
			}
			return self::unchanged($parts);
		}

		if($mode == 'rename') {
			$changed = false;
			foreach($parts[$elementsKey] as $index => $reference) {
				if($this->renamable($reference, $holds)) {
					$parts[$elementsKey][$index] = $newHandle;
					$changed = true;
				}
			}
			if(isset($parts[$termsKey]) AND is_array($parts[$termsKey])) {
				foreach($parts[$termsKey] as $index => $term) {
					if(is_string($term) AND ($renamed = $this->renameTokens($term, $newHandle, $tokenPrefixes)) !== $term) {
						$parts[$termsKey][$index] = $renamed;
						$changed = true;
					}
				}
			}
			return array('found' => $changed, 'changed' => $changed, 'value' => $parts);
		}

		$keep = array();
		foreach($parts[$elementsKey] as $index => $reference) {
			if(!$this->matches($reference, $holds)) {
				$keep[] = $index;
			}
		}
		if(count($keep) === count($parts[$elementsKey])) {
			return self::unchanged($parts);
		}
		$new = array();
		foreach($keys as $name => $key) {
			if($key === null) {
				continue;
			}
			$new[$key] = array();
			foreach($keep as $index) {
				// a condition that has somehow lost its operator gets the same default that
				// parseSubmittedConditions applies, because a blank operator makes broken SQL further down
				$new[$key][] = isset($parts[$key][$index]) ? $parts[$key][$index] : ($name == 'ops' ? '=' : '');
			}
		}
		foreach($parts as $key => $part) {
			if(!isset($new[$key])) {
				$new[$key] = $part; // anything else beside the parts, such as pagecons, is kept as it is
			}
		}
		return self::changedTo($new);
	}

	/**
	 * The keys of an array are the references, as in a screen's defaults for its elements.
	 */
	private function walkKeys($spec, $value, $mode, $newHandle) {
		if(!is_array($value)) {
			return self::unchanged($value);
		}
		$holds = $this->option($spec, 'holds', 'either');
		$new = array();
		$changed = false;
		foreach($value as $key => $item) {
			if($mode == 'remove' AND $this->matches($key, $holds)) {
				$changed = true;
				continue;
			}
			if($mode == 'rename' AND $this->renamable($key, $holds)) {
				$key = $newHandle;
				$changed = true;
			}
			$new[$key] = $item;
		}
		return array('found' => $changed, 'changed' => $changed, 'value' => $changed ? $new : $value);
	}

	/**
	 * The same declaration applies to every value of an array whose keys are something else - page numbers,
	 * group ids - so the keys are kept as they are.
	 */
	private function walkEach($spec, $value, $mode, $newHandle, $row) {
		if(!is_array($value)) {
			return self::unchanged($value);
		}
		$inner = $spec['of'] + array_diff_key($spec, array_flip(array('shape', 'of', 'describe_pages')));
		$found = false;
		$changed = false;
		$pagesShared = array();
		$pagesEmptied = array();
		foreach($value as $key => $item) {
			$result = $this->walk($inner, $item, $mode, $newHandle, $row);
			if(!$result['found']) {
				continue;
			}
			$found = true;
			if($result['changed']) {
				$value[$key] = $result['value'];
				$changed = true;
			}
			if($mode == 'remove' AND $this->option($spec, 'describe_pages', false)) {
				// asked as if the page lost the element now, whatever does the removing
				$removed = $this->walk(array_merge($inner, array('on_delete' => 'remove')), $item, 'remove', null, $row);
				if(count((array) $removed['value'])) {
					$pagesShared[] = intval($key) + 1;
				} else {
					$pagesEmptied[] = intval($key) + 1;
				}
			}
		}
		$result = array('found' => $found, 'changed' => $changed, 'value' => $value);
		if($pagesShared OR $pagesEmptied) {
			// a page left with nothing on it stops appearing in the form even though it is still on the screen,
			// so that is said separately - see the comment in formulizeElementsHandler::delete()
			$result['phrases'] = array();
			if($pagesShared) {
				$result['phrases'][] = (count($pagesShared) > 1 ? 'on pages ' : 'on page ').implode(', ', $pagesShared);
			}
			if($pagesEmptied) {
				$many = count($pagesEmptied) > 1;
				$result['phrases'][] = 'the only element on '.($many ? 'pages ' : 'page ').implode(', ', $pagesEmptied)
					.', so '.($many ? 'those pages will be empty' : 'that page will be empty')
					.' and will stop appearing in the form until something is put on '.($many ? 'them' : 'it');
			}
		}
		return $result;
	}

	/**
	 * The reference is at a path inside nested arrays: segments separated by dots, where * is any key, # is
	 * any numeric key, and the last segment can name several keys with |. A deletion can remove the array the
	 * reference sits in rather than the reference alone ('remove_parent'), as a custom button's effect goes as
	 * a whole. With 'braces', the stored value can be the handle wrapped in {}, to tell it apart from a
	 * literal value that looks like one.
	 */
	private function walkPath($spec, $value, $mode, $newHandle) {
		$result = $this->walkPathLevel($spec, $value, explode('.', $spec['path']), $mode, $newHandle);
		return array('found' => $result['found'], 'changed' => $result['changed'], 'value' => $result['value']);
	}

	private function walkPathLevel($spec, $node, $segments, $mode, $newHandle) {
		$outcome = array('found' => false, 'changed' => false, 'value' => $node, 'remove_me' => false);
		if(!is_array($node)) {
			return $outcome;
		}
		$segment = array_shift($segments);
		$holds = $this->option($spec, 'holds', 'either');
		$braces = $this->option($spec, 'braces', false);
		foreach($node as $key => $child) {
			if($segment === '#' AND !is_numeric($key)) {
				continue;
			}
			if($segment !== '*' AND $segment !== '#' AND !in_array((string) $key, explode('|', $segment), true)) {
				continue;
			}
			if($segments) {
				$result = $this->walkPathLevel($spec, $child, $segments, $mode, $newHandle);
				$outcome['found'] = $outcome['found'] || $result['found'];
				if($result['remove_me']) {
					unset($node[$key]);
					$outcome['changed'] = true;
				} elseif($result['changed']) {
					$node[$key] = $result['value'];
					$outcome['changed'] = true;
				}
				continue;
			}
			$bare = ($braces AND is_string($child) AND substr($child, 0, 1) == '{' AND substr($child, -1) == '}') ? substr($child, 1, -1) : $child;
			if($mode == 'remove' AND $this->matches($bare, $holds)) {
				$outcome['found'] = true;
				if($this->option($spec, 'on_delete', 'remove') == 'remove_parent') {
					$outcome['remove_me'] = true; // the array this sits in goes, one level up
					return $outcome;
				}
				$node[$key] = $this->option($spec, 'set_to', '');
				$outcome['changed'] = true;
			} elseif($mode == 'rename' AND $this->renamable($bare, $holds)) {
				$node[$key] = $bare === $child ? $newHandle : '{'.$newHandle.'}';
				$outcome['found'] = $outcome['changed'] = true;
			}
		}
		$outcome['value'] = $node;
		return $outcome;
	}

	/**
	 * A property of each object in a list of objects, as a calendar screen's datasets are stored.
	 */
	private function walkProperty($spec, $value, $mode, $newHandle) {
		if(!is_array($value) OR !$value) {
			return self::unchanged($value);
		}
		foreach($value as $object) {
			if(!($object instanceof $spec['class'])) {
				return $this->unreadableObjects($spec, serialize($value), $mode);
			}
		}
		$holds = $this->option($spec, 'holds', 'either');
		$property = $spec['property'];
		$found = false;
		foreach($value as $object) {
			$reference = $object->getVar($property);
			if($mode == 'remove' AND $this->matches($reference, $holds)) {
				$object->setVar($property, $this->option($spec, 'set_to', ''));
				$found = true;
			} elseif($mode == 'rename' AND $this->renamable($reference, $holds)) {
				$object->setVar($property, $newHandle);
				$found = true;
			}
		}
		return array('found' => $found, 'changed' => $found, 'value' => $value);
	}

	/**
	 * Objects that did not come back as the class they were stored as cannot be changed safely. If the
	 * handle appears anywhere in what is stored, a person is told to look, which may say yes when the answer
	 * is no, but the alternative is staying silent about a screen that may be about to break.
	 */
	private function unreadableObjects($spec, $stored, $mode) {
		$found = ($mode == 'remove' AND strpos((string) $stored, $this->handle) !== false);
		return array('found' => $found, 'changed' => false, 'stored' => $stored, 'value' => $stored, 'positions' => array(),
			'phrases' => $found ? array($this->option($spec, 'describe', '').' (this could not be read properly, so check it and change it yourself)') : array());
	}

	/**
	 * The source of a linked list, "fid#*=:*handle": the handle after the separator is the reference.
	 */
	private function walkLinkedSource($spec, $value, $mode, $newHandle) {
		if(!is_string($value)) {
			return self::unchanged($value);
		}
		$parts = explode('#*=:*', $value);
		if(count($parts) != 2) {
			return self::unchanged($value);
		}
		$holds = $this->option($spec, 'holds', 'handle');
		if($mode == 'remove' AND $this->matches(trim($parts[1]), $holds)) {
			return array('found' => true, 'changed' => false, 'value' => $value);
		}
		if($mode == 'rename' AND $this->renamable(trim($parts[1]), $holds)) {
			return self::changedTo($parts[0].'#*=:*'.$newHandle);
		}
		return self::unchanged($value);
	}

	/**
	 * {handle} tokens in text a person wrote, which Formulize swaps for the element's value.
	 */
	private function walkTokens($spec, $value, $mode, $newHandle) {
		if(!is_string($value)) {
			return self::unchanged($value);
		}
		$prefixes = $this->option($spec, 'token_prefixes', array(''));
		if($mode == 'mention' AND $this->hasToken($value, $prefixes)) {
			return array('found' => true, 'changed' => false, 'value' => $value);
		}
		if($mode == 'rename' AND ($renamed = $this->renameTokens($value, $newHandle, $prefixes)) !== $value) {
			return self::changedTo($renamed);
		}
		return self::unchanged($value);
	}

	/**
	 * A notification's {ELEMENT_HANDLE} tags, which name the handle in capitals. Both the forms with and
	 * without the underscore are accepted when a notification is sent, and the revision tag besides - see
	 * formulize_processNotification().
	 */
	private function walkTags($spec, $value, $mode, $newHandle) {
		if(!is_string($value)) {
			return self::unchanged($value);
		}
		$tags = self::notificationTags($this->handle);
		if($mode == 'mention' AND str_replace($tags, '', $value) !== $value) {
			return array('found' => true, 'changed' => false, 'value' => $value);
		}
		if($mode == 'rename' AND ($renamed = str_replace($tags, self::notificationTags($newHandle), $value)) !== $value) {
			return self::changedTo($renamed);
		}
		return self::unchanged($value);
	}

	private static function notificationTags($handle) {
		$upper = strtoupper($handle);
		return array('{REVISION_ELEMENT_'.$upper.'}', '{ELEMENT_'.$upper.'}', '{ELEMENT'.$upper.'}');
	}

	/**
	 * Code a person wrote and keeps in the database. Only ever mentioned, never changed.
	 */
	private function walkCode($spec, $value, $mode) {
		$found = ($mode == 'mention' AND is_string($value) AND preg_match(formulizeElementsHandler::handleReferencePattern($this->handle), $value));
		return array('found' => (bool) $found, 'changed' => false, 'value' => $value);
	}

	/**
	 * An element's ele_value, which means whatever the element's own type says it means - see
	 * eleValueDeclaration().
	 */
	private function walkPerType($spec, $value, $mode, $newHandle, $row) {
		if(!is_array($value) OR !isset($row['ele_type'])) {
			return self::unchanged($value);
		}
		$found = false;
		$changed = false;
		$phrases = array();
		foreach(self::eleValueDeclaration($row['ele_type']) as $key => $keySpec) {
			if(!array_key_exists($key, $value)) {
				continue;
			}
			$result = $this->walk($keySpec, $value[$key], $mode, $newHandle, $row);
			if($result['found']) {
				$found = true;
				$phrases = array_merge($phrases, $result['phrases']);
			}
			if($result['changed']) {
				$value[$key] = $result['value'];
				$changed = true;
			}
		}
		return array('found' => $found, 'changed' => $changed, 'value' => $value, 'phrases' => $phrases);
	}

	/**
	 * Does a stored reference point at this element, given what the setting holds? A setting that holds ids
	 * matches only the id and one that holds handles only the handle. 'either' accepts both, for settings that
	 * still hold handles written before they stored ids.
	 */
	private function matches($reference, $holds) {
		if(is_array($reference) OR is_object($reference) OR $reference === '' OR $reference === null) {
			return false;
		}
		$isId = (is_numeric($reference) AND intval($reference) === $this->elementId);
		$isHandle = ((string) $reference === $this->handle);
		switch($holds) {
			case 'id': return $isId;
			case 'handle': return $isHandle;
		}
		return $isId OR $isHandle;
	}

	/**
	 * Should a rename rewrite this stored reference? Only where it is the old handle. Where a setting can hold
	 * either, a number is taken to be an id even when the old handle is that number - legacy handles equal
	 * their element's id - because turning a reference by id into one by handle would be a change of meaning.
	 */
	private function renamable($reference, $holds) {
		if(!is_string($reference) AND !is_numeric($reference)) {
			return false;
		}
		if((string) $reference !== $this->handle) {
			return false;
		}
		return $holds == 'handle' OR ($holds == 'either' AND !is_numeric($reference));
	}

	private function hasToken($text, $prefixes) {
		foreach($prefixes as $prefix) {
			if(strpos($text, '{'.$prefix.$this->handle.'}') !== false) {
				return true;
			}
		}
		return false;
	}

	private function renameTokens($text, $newHandle, $prefixes) {
		foreach($prefixes as $prefix) {
			$text = str_replace('{'.$prefix.$this->handle.'}', '{'.$prefix.$newHandle.'}', $text);
		}
		return $text;
	}

	/**
	 * Drop the given positions from a delimited list that runs parallel to another one, so the two stay in
	 * step: a saved view's searches line up with its columns, its calculation settings with the columns
	 * calculated on, by position and nothing else.
	 */
	private static function removePositions($value, $delimiter, $positions) {
		$items = explode($delimiter, (string) $value);
		foreach($positions as $position) {
			unset($items[$position]);
		}
		return implode($delimiter, $items);
	}

	/**
	 * How the usage report names a row, from the declaration's label: {column} is replaced with that column
	 * of the row, or of a joined table. A blank name reads as (untitled).
	 */
	private function label($tableSpec, $row) {
		return $this->fillPlaceholders($this->option($tableSpec, 'label', '{'.$tableSpec['key'].'}'), $row);
	}

	private function fillPlaceholders($text, $row) {
		return preg_replace_callback('/\{([a-zA-Z0-9_]+)\}/', function($match) use ($row) {
			if(!array_key_exists($match[1], $row)) {
				return $match[0];
			}
			$value = trim(strip_tags((string) $row[$match[1]]));
			return $value === '' ? '(untitled)' : $value;
		}, (string) $text);
	}

	/**
	 * A delimiter, prefix or section heading can be given as the name of a constant - formulizeSavedViewsHandler::SEARCH_DELIMITER,
	 * _AM_ELE_USAGE_SECTION_SAVED_VIEWS - so the value is defined once, where the code that writes it is.
	 */
	private static function resolve($value) {
		return (is_string($value) AND preg_match('/^[A-Za-z_][A-Za-z0-9_]*(::[A-Za-z_][A-Za-z0-9_]*)?$/', $value) AND defined($value)) ? constant($value) : $value;
	}

	private function option($spec, $name, $default) {
		return array_key_exists($name, $spec) ? $spec[$name] : $default;
	}

	private static function unchanged($value) {
		return array('found' => false, 'changed' => false, 'value' => $value);
	}

	private static function changedTo($value) {
		return array('found' => true, 'changed' => true, 'value' => $value);
	}
}
