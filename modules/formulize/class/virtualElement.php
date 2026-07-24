<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2006 Freeform Solutions                  ##
###############################################################################
##  This program is free software; you can redistribute it and/or modify     ##
##  it under the terms of the GNU General Public License as published by     ##
##  the Free Software Foundation; either version 2 of the License, or        ##
##  (at your option) any later version.                                       ##
###############################################################################
##  Author of this file: Formulize Incorporated                               ##
##  Project: Formulize                                                        ##
###############################################################################

if (!defined('XOOPS_ROOT_PATH')) {
	exit();
}

require_once XOOPS_ROOT_PATH . "/modules/formulize/class/elements.php";

/**
 * Base class for virtual element types — elements that have no backing database
 * column and are populated post-query by injection functions.
 *
 * Virtual elements are always system-managed; they cannot be created via the admin
 * element picker. Subclass this to define a typed virtual column. Implement
 * buildSearchWhereClause() on the handler to enable search delegation from
 * parseTableFormFilter().
 */
class formulizeVirtualElement extends formulizeElement {

	var $isVirtualElement = true;

	function __construct() {
		parent::__construct();
		$this->name           = "Virtual Element";
		$this->isSystemElement = true;
		$this->hasData        = false;
		$this->needsDataType  = false;
	}

}

/**
 * Base handler for virtual element types.
 *
 * Provides no-op implementations of the standard element handler methods.
 * Subclasses should override buildSearchWhereClause() to support filtering.
 */
class formulizeVirtualElementHandler extends formulizeElementsHandler {

	/**
	 * Registry of link targets for values injected into virtual columns.
	 * [ele_handle][row entry_id] => ordered array of targets, one per injected value (null = no link).
	 * @var array
	 */
	private static $linkTargets = array();

	function create() {
		return new formulizeVirtualElement();
	}

	function prepareDataForDataset($value, $handle, $entry_id) {
		return $value;
	}

	function render($ele_value, $caption, $markupName, $isDisabled, $element, $entry_id, $screen = false, $owner = null) {
		return null;
	}

	/**
	 * Format a virtual element value for list display.
	 *
	 * Injection functions must put PLAIN TEXT in the dataset, never markup - the value is
	 * HTML-escaped here like any other element's data. An injector that wants its values to
	 * be clickable registers link targets with registerLinkTargets(), which composeMarkupForList()
	 * below turns into anchors AFTER the escape. See registerLinkTargets() for why.
	 *
	 * Truncation and auto-linkification are both switched off: injection functions apply their
	 * own display limits before this is called, and the values are already-composed display
	 * strings rather than free text.
	 *
	 * @param mixed $value Pre-formatted plain-text value from the injection function
	 * @param string $handle Element handle
	 * @param int $entry_id Entry ID of the row the value belongs to
	 * @param int $textWidth Column width hint (unused)
	 * @return string Formatted display value
	 */
	function formatDataForList($value, $handle = "", $entry_id = 0, $textWidth = 100) {
		$this->dataIsHtml = false; // plain text value - gets HTML-escaped
		$this->length    = 0;
		$this->clickable = false;
		return parent::formatDataForList($value, $handle, $entry_id, $textWidth);
	}

	/**
	 * Declare where each value in a virtual column's cell should link to.
	 *
	 * WHY THIS EXISTS: injection functions run while the dataset is being assembled, long before
	 * any row is rendered. Markup built there would be escaped by the canonical safety step in
	 * formatDataForList(), which is what it is for - the injected values include user-entered data
	 * (form entry names) and must be escaped. So injectors record link TARGETS here and let
	 * composeMarkupForList() build the anchors, which runs after the escape and is the one place
	 * markup may be added. Building the link at injection time also produced a broken href, since
	 * viewEntryLink() depends on a global that only drawEntries() populates, per row.
	 *
	 * The targets array must line up index-for-index with the array of values injected into the
	 * same cell; use null for a value that should not be a link (an "...and more" summary, say).
	 * A target is an array with 'entry_id', and optionally 'screen_id' to open that entry in a
	 * particular screen rather than the current one. An element may put its own extra keys in
	 * here too, to carry per-value data through to its own composeValueWithTarget() - eagGroupMembers
	 * passes the member count that way. Keys other than entry_id/screen_id are ignored here, and
	 * a target carrying only element-specific data (no entry_id) is simply not linked.
	 *
	 * @param string $handle The virtual element's handle
	 * @param int $rowEntryId The entry id of the ROW the cell belongs to (the dataset's local id)
	 * @param array $targets Ordered targets, parallel to the injected values
	 * @return void
	 */
	static function registerLinkTargets($handle, $rowEntryId, $targets) {
		self::$linkTargets[$handle][$rowEntryId] = array_values($targets);
	}

	/**
	 * Match the value currently being formatted to the target the injector registered for it.
	 *
	 * Correlation is POSITIONAL because nothing else identifies the value: composeMarkupForList()
	 * is handed the value text and the ROW's entry id, never the target entry id, and
	 * getHTMLForList() knows the value's index in the cell but does not pass it on. Values do
	 * arrive one at a time in the order they were injected, so a counter keyed by row tracks which
	 * one we are on. (Keying by the value text instead would be simpler but is not reliable: trans()
	 * rewrites multilingual values before we see them, and two entries in one cell can share a name.)
	 *
	 * The counter is a function-static keyed by entry rather than a property on the handler, because
	 * the handler is a singleton shared by every row on the page. It is private and called from
	 * exactly one place - composeMarkupForList() below - so that subclasses cannot step it twice or
	 * not at all; they override composeValueWithTarget() and are handed the resolved target.
	 *
	 * @param string $handle The element handle
	 * @param int $entry_id The entry id of the row the value belongs to
	 * @return array|null The target for this value, or null if there is none
	 */
	private function nextLinkTarget($handle, $entry_id) {
		if (!isset(self::$linkTargets[$handle][$entry_id])) {
			return null; // this injector registered nothing
		}
		static $valueCounter = array();
		$counterKey = $handle . '|' . $entry_id;
		$index = isset($valueCounter[$counterKey]) ? ++$valueCounter[$counterKey] : ($valueCounter[$counterKey] = 0);
		$target = self::$linkTargets[$handle][$entry_id][$index] ?? null;
		return is_array($target) ? $target : null;
	}

	/**
	 * Resolve this value's target and hand both to composeValueWithTarget().
	 *
	 * Subclasses override composeValueWithTarget(), NOT this, so that the positional counter in
	 * nextLinkTarget() is stepped exactly once per value no matter what a subclass does.
	 *
	 * @param string $value The escaped value - safe to place in HTML as-is, never escape it again
	 * @param string $handle The element handle
	 * @param int $entry_id The entry id of the row this value belongs to
	 * @param string|null $rawValue The value before escaping (unused - the target is registered, not parsed)
	 * @param int $textWidth The list column width (unused - injectors apply their own limits)
	 * @return string The value, linked if a target was registered for it
	 */
	function composeMarkupForList($value, $handle = "", $entry_id = 0, $rawValue = null, $textWidth = 100) {
		return $this->composeValueWithTarget($value, $this->nextLinkTarget($handle, $entry_id));
	}

	/**
	 * Build the final cell markup for one value: wrap it in its entry link, if it has one.
	 *
	 * THE ONE PLACE markup is added to a virtual element's value, and it runs after the canonical
	 * escape in formatDataForList(), which is what makes it safe. A subclass that needs presentation
	 * of its own should override this, call parent::composeValueWithTarget() for the link, and build
	 * around the result - rather than emitting an anchor of its own, so that this stays the only
	 * copy of that logic. eagGroupMembers appends its member count that way.
	 *
	 * @param string $value The escaped value - safe to place in HTML as-is, never escape it again
	 * @param array|null $target The resolved target for this value
	 * @return string The composed markup for this value
	 */
	protected function composeValueWithTarget($value, $target) {
		if (empty($target['entry_id']) OR !function_exists('viewEntryLink')) {
			// Not a link - or we are not being rendered by a list (an export, say), where there is
			// no page for a link to act on. Either way, the plain escaped value.
			return $value;
		}
		// viewEntryLink, NOT a URL of our own: its onclick calls goDetails(), which sets ventry on the
		// list's controls form and re-submits THE CURRENT PAGE, so the entry opens in place and the
		// entry form's close / save-and-close return the user to this list. An absolute link to
		// index.php would open the entry outside this page and strand them with nowhere to return to.
		// This is also why the call belongs here rather than in the injection function: viewEntryLink
		// reads a global that only drawEntries() populates, per row, while it is rendering that row.
		return viewEntryLink($value, intval($target['entry_id']), $target['screen_id'] ?? "");
	}

}
