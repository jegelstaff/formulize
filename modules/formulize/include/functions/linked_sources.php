<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                Copyright (c) 2026 Formulize Incorporated                  ##
###############################################################################
##  Released under the GNU General Public License. See license elsewhere in  ##
##  this distribution for details.                                           ##
###############################################################################

// The source of a linked element: which form and which element its options come from. It is kept in the
// element's ele_value[2] as "fid#*=:*element", and these two functions are the only things that read or
// write that string. Everything else asks them.
//
// The element half is stored as the element's id, so that renaming the source element cannot leave the link
// pointing at a handle that no longer exists. Sources written before that hold the handle, which still reads
// correctly. Whatever is stored, the element comes back as its handle, because that is what everything that
// uses a source works in - the queries it builds name the source element's field by its handle.
//
// Part of functions.php, which requires this file at its end. It is a file of its own only to keep
// functions.php to a size that can be worked with: the functions are global, and are available wherever
// functions.php is.

if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}

/**
 * Split a linked element's source into its parts, with the source element as its handle.
 *
 * Given the source itself, returns what explode("#*=:*", $source) would - the form id, then the element, then
 * anything after - except that the element is always the handle, whether the source stores its id or, from
 * before, its handle. Some code adds a third part while it works, the entry ids chosen in the element, and that
 * comes back as it is.
 *
 * Given the linked element instead - the object, its id or its handle - returns the form id and handle of the
 * source stored in it, through getSourceFormAndElementForLinkedElement(). That is the stored source, so it never
 * has a third part: code holding an ele_value with entry ids added must pass that string, not the element.
 *
 * Nothing to split - an element that is not linked, or an empty value - comes back as array(''), which is what
 * explode() gives for an empty string.
 *
 * @param int|string|object $source The source, as it is kept in ele_value[2], or the linked element, as an object, an id or a handle.
 * @return array The parts. [0] the form id, [1] the source element's handle, [2] onwards anything after.
 * @throws Exception If $source names an element that does not exist, or is an object that is not an element.
 */
function formulize_linkedSourceParts($source) {
	if(!is_string($source) OR !strstr($source, "#*=:*")) {
		// not the source itself, so it is the element, or nothing
		if($source === null OR $source === false OR $source === '' OR is_array($source)) {
			return array('');
		}
		if(!$elementObject = _getElementObject($source)) {
			throw new Exception("formulize_linkedSourceParts() cannot find the element ".(is_object($source) ? "it was given, which is a ".get_class($source) : "with id or handle '".htmlspecialchars((string) $source)."'").".");
		}
		return getSourceFormAndElementForLinkedElement($elementObject) ?: array('');
	}
	$parts = explode("#*=:*", $source);
	if (count($parts) >= 2 AND is_numeric(trim($parts[1]))) {
		$element_handler = xoops_getmodulehandler('elements', 'formulize');
		if ($elementObject = $element_handler->get(intval(trim($parts[1])))) {
			$parts[1] = $elementObject->getVar('ele_handle');
		}
	}
	return $parts;
}

/**
 * The source of a linked element, as it is kept in ele_value[2]: "fid#*=:*element id".
 *
 * @param int $formId The form the options come from.
 * @param mixed $element The source element, as an object, an id or a handle.
 * @return string The source. If the element cannot be found, the form id with what was given, so nothing is lost.
 */
function formulize_linkedSourceString($formId, $element) {
	$elementObject = is_object($element) ? $element : xoops_getmodulehandler('elements', 'formulize')->get($element);
	return intval($formId)."#*=:*".($elementObject ? intval($elementObject->getVar('ele_id')) : $element);
}
