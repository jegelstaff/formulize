<?php
###############################################################################
##					     Formulize - ad hoc form creation and reporting							 ##
##                    Copyright (c) The Formulize Project              			 ##
###############################################################################
##  This program is free software; you can redistribute it and/or modify     ##
##  it under the terms of the GNU General Public License as published by     ##
##  the Free Software Foundation; either version 2 of the License, or        ##
##  (at your option) any later version.                                      ##
##                                                                           ##
##  You may not change or alter any portion of this comment or credits       ##
##  of supporting developers from this source code or any supporting         ##
##  source code which is considered copyrighted (c) material of the          ##
##  original comment or credit authors.                                      ##
##                                                                           ##
##  This program is distributed in the hope that it will be useful,          ##
##  but WITHOUT ANY WARRANTY; without even the implied warranty of           ##
##  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            ##
##  GNU General Public License for more details.                             ##
##                                                                           ##
##  You should have received a copy of the GNU General Public License        ##
##  along with this program; if not, write to the Free Software              ##
##  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA ##
###############################################################################
##  Author of this file: The Formulize Project                               ##
##  https://github.com/jegelstaff/formulize/																 ##
###############################################################################

/**
 * Checks if there's a Primary Relationship defined in this Formulize instance
 * @return boolean Returns true or false depending if a Primary Relationship exists or not
 */
function primaryRelationshipExists() {
	global $xoopsDB;
	$result = false;
	$sql = "SELECT * FROM ".$xoopsDB->prefix('formulize_frameworks'). " WHERE frame_id = -1";
	if($res = $xoopsDB->query($sql)) {
		if($xoopsDB->getRowsNum($res) > 0) {
			$result = true;
		}
	}
	return $result;
}

/**
 * Adds a record to the database to represent the primary relationship, populates the relationship with all the link settings necessary based on current configurations
 * @return boolean|string False if there was no error, or a string containing an error statement if there was an error
 */
function createPrimaryRelationship() {
	// Create a relationship with id -1 and call it Primary Relationship
	// Add all existing links from all existing relationships, to this relationship
	// Add extra links to this relationship that represent every linked element connection in the database, which was not in an existing relationship
	// For the extra links, the linked element form will be the Many form, the target form will be the One form
	// DO NOT Set the Primary Relationship as the relationship in effect on all screens that are 'Form Only' right now. See comment below.

	global $xoopsDB, $linkForms;
	$linkForms = array();
	$primaryRelationshipError = '';

	$sql = "UPDATE ".$xoopsDB->prefix('formulize_framework_links')." SET fl_unified_display = 1";
	if(!$res = $xoopsDB->queryF($sql)) {
		$primaryRelationshipError .= '<br>Could not set all links to unified display';
	}

	if(!$primaryRelationshipError) {
		$report = populatePrimaryRelationship(true);
		$primaryRelationshipError .= $report['error'];
	}

	if(!$primaryRelationshipError) {
		$sql = "INSERT INTO ".$xoopsDB->prefix('formulize_frameworks')." (`frame_id`, `frame_name`) VALUES (-1, 'Primary Relationship')";
		if(!$res = $xoopsDB->queryF($sql)) {
			$primaryRelationshipError .= '<br>Could not create relationship entry';
		}
	}
	if($primaryRelationshipError) {
		$sql = "DELETE FROM ".$xoopsDB->prefix('formulize_framework_links')." WHERE fl_frame_id = -1";
		if(!$res = $xoopsDB->queryF($sql)) {
			$primaryRelationshipError .= '<br>Could not cleanup primary relationship links';
		}
		$sql = "DELETE FROM ".$xoopsDB->prefix('formulize_frameworks')." WHERE frame_id = -1";
		if(!$res = $xoopsDB->queryF($sql)) {
			$primaryRelationshipError .= '<br>Could not cleanup primary relationship entry';
		}
	}

	// NOT CURRENTLY CHANGING EXISTING SCREENS TO USE PRIMARY RELATIONSHIP ON UPGRADE... PERHAPS WE SHOULD. PERHAPS IT SHOULD BE AN OPTION.
	// PROBLEM IS THAT FOR SOME SCREENS, THERE WOULD BE PERFORMANCE ISSUES IF A FORM THAT HAS TONS OF LINKS, SUDDENLY HAS WAY MORE DATA INVOLVED IN ITS SCREENS, WHICH WERE PERFECTLY FINE AND SPEEDY UP TILL NOW.
	/*if(!empty($linkForms)) {
		$linkForms = array_unique($linkForms);
		$sql = "UPDATE ".$xoopsDB->prefix('formulize_screen')." SET `frid` = -1 WHERE `frid` = 0 AND `fid` IN (".implode(', ', $linkForms).")";
		if(!$primaryRelationshipError AND !$res = $xoopsDB->queryF($sql)) {
			$primaryRelationshipError = 'Could not update existing screens to use Primary Relationship';
		}
	}*/

	return $primaryRelationshipError;
}

/**
 * Walks every form-to-form connection that ought to be represented in the Primary Relationship, and
 * hands each one to insertLinkIntoPrimaryRelationship.
 *
 * There are two sources of connections:
 * 1. Every link in every other relationship.
 * 2. Every linked element, whose form is thereby connected to the form it draws its values from.
 *
 * Deciding whether a connection is already present, and reconciling the unified delete setting when
 * several links join the same pair of forms, is entirely insertLinkIntoPrimaryRelationship's job -
 * see the long comment in its second branch. Every connection found here is therefore passed to it
 * unconditionally. It is asked beforehand whether the link already exists, but only so that the report
 * can say what was actually new; that answer must never be used to skip the call, or the reconciliation
 * is skipped with it. When calling this to repair an existing Primary Relationship rather than build a
 * new one, call primePrimaryRelationshipLinkPairs() first so that the links already in the Primary
 * Relationship are known to that logic.
 *
 * @param bool $deleteInvalidLinks - when true, a link whose elements no longer exist (or which claims
 *   a connection the elements do not actually have) is deleted from its relationship, which is what we
 *   want while building the Primary Relationship for the first time. Pass false to leave such links
 *   alone and merely report them, which is what we want for a repair run on a live system.
 * @return array - 'error' is a string, empty when everything succeeded, in the same format
 *   createPrimaryRelationship returns. 'added', 'invalid' and 'problems' are arrays of description
 *   strings suitable for reporting to whoever triggered this.
 */
function populatePrimaryRelationship($deleteInvalidLinks) {
	global $xoopsDB;
	$report = array('error' => '', 'added' => array(), 'invalid' => array(), 'problems' => array());

	// SOURCE 1: all the links in all the other relationships.
	$sql = "SELECT * FROM ".$xoopsDB->prefix('formulize_framework_links')." WHERE fl_frame_id != -1";
	if(!$res = $xoopsDB->query($sql)) {
		$report['error'] .= '<br>Could not check existing links';
		return $report;
	}
	// Read every row before touching the table. Writing to a table while a result set from that same
	// table is still being read is what silently truncated this pass on earlier versions: the DELETE
	// below overwrote the result handle being iterated, so the loop ended at the first invalid link and
	// every link after it was never considered.
	$existingLinks = array();
	while($row = $xoopsDB->fetchArray($res)) {
		$existingLinks[] = $row;
	}

	$invalidLinkIds = array();
	foreach($existingLinks as $row) {
		if($report['error']) { break; }
		$f1 = $row['fl_form1_id'];
		$f2 = $row['fl_form2_id'];
		$k1 = $row['fl_key1'];
		$k2 = $row['fl_key2'];
		$rel = $row['fl_relationship'];
		$cv = $row['fl_common_value'];
		$del = $row['fl_unified_delete'];
		$con = $row['fl_one2one_conditional'];
		$book = $row['fl_one2one_bookkeeping'];
		// 0 and 0 would indicate a "user who made the entries" relationship. Primary relationship doesn't support this initially, since it is dynamically responding to links between forms primarily. But could be added. Can also always be manually created by webmaster in old UI if really necessary.
		if(!$k1 OR !$k2) { continue; }
		// validate that the elements are real and are common value or link to each other
		if(elementExists($k1) AND elementExists($k2) AND ($cv == 1 OR elementsAreLinked($k1, $k2))) {
			$description = 'forms '.intval($f1).' and '.intval($f2).', using elements '.intval($k1).' and '.intval($k2).', from relationship '.intval($row['fl_frame_id']);
			$alreadyPresent = linkExistsInPrimaryRelationship($cv, $rel, $k1, $k2);
			try {
				if(!$result = insertLinkIntoPrimaryRelationship($cv, $rel, $f1, $f2, $k1, $k2, $del, $con, $book)) {
					$report['error'] .= "<br>Error inserting existing relationship link into primary relationship";
				} elseif(!$alreadyPresent) {
					$report['added'][] = $description;
				}
			} catch (Throwable $e) {
				// insertLinkIntoPrimaryRelationship throws only after the link itself was written, when it
				// cannot load the element objects to index them. The link is good; the index is missing.
				$report['problems'][] = 'Added the link for '.$description.', but could not create indexes on the elements: '.$e->getMessage();
			}
		} else {
			// linkage is not involving real elements, or they are not linked together properly
			$invalidLinkIds[] = intval($row['fl_id']);
			$report['invalid'][] = 'link '.intval($row['fl_id']).' in relationship '.intval($row['fl_frame_id']).', which joins forms '.intval($f1).' and '.intval($f2).' using elements '.intval($k1).' and '.intval($k2);
		}
	}

	if($deleteInvalidLinks AND !$report['error'] AND $invalidLinkIds) {
		// safe to write now that the result set above has been fully read
		$sql = "DELETE FROM ".$xoopsDB->prefix('formulize_framework_links')." WHERE fl_id IN (".implode(', ', $invalidLinkIds).")";
		if(!$xoopsDB->queryF($sql)) {
			print "<br>Warning: could not delete invalid links from their relationships";
		}
	}

	// SOURCE 2: all the linked elements, which connect their form to the form they draw values from.
	$sql = "SELECT id_form, ele_id, ele_value FROM ".$xoopsDB->prefix('formulize')." WHERE ele_type IN ('select', 'checkbox', 'selectLinked', 'checkboxLinked', 'listboxLinked', 'autocompleteLinked') AND ele_value LIKE '%#*=:*%'";
	if(!$report['error'] AND !$res = $xoopsDB->query($sql)) {
		$report['error'] .= '<br>Could not collate list of existing linked elements';
	}
	// read these up front too, since each one is then looked up against this same table below
	$linkedElements = array();
	while(!$report['error'] AND $row = $xoopsDB->fetchArray($res)) {
		$linkedElements[] = $row;
	}

	foreach($linkedElements as $row) {
		if($report['error']) { break; }
		$f2 = $row['id_form'];
		$k2 = $row['ele_id'];
		$ele_value = @unserialize($row['ele_value']);
		if(!is_array($ele_value) OR !isset($ele_value[2]) OR !is_string($ele_value[2]) OR !strstr($ele_value[2], "#*=:*")) {
			continue; // matched the LIKE on some other part of the serialized value, so there is no link here
		}
		$boxproperties = explode("#*=:*", $ele_value[2]);
		$sourceElementHandle = isset($boxproperties[1]) ? $boxproperties[1] : '';
		if($sourceElementHandle === '') { continue; }
		$sourceFormLookup = "SELECT id_form, ele_id FROM ".$xoopsDB->prefix('formulize')." WHERE ele_handle = '".formulize_db_escape($sourceElementHandle)."'";
		if(!$sourceFormLookupResult = $xoopsDB->query($sourceFormLookup)) {
			$report['error'] .= "<br>Could not lookup source details of linked form element with this SQL:<br>$sourceFormLookup";
			break;
		}
		if(!$sourceFormLookupRow = $xoopsDB->fetchArray($sourceFormLookupResult)) {
			$report['problems'][] = 'Element '.intval($k2).' in form '.intval($f2).' draws its values from "'.$sourceElementHandle.'", which is not an element in any form, so no connection could be made for it.';
			continue;
		}
		$k1 = $sourceFormLookupRow['ele_id'];
		$f1 = $sourceFormLookupRow['id_form'];
		$rel = 2;
		$cv = 0;
		if(elementExists($k1) AND elementExists($k2)) {
			$description = 'forms '.intval($f1).' and '.intval($f2).', using elements '.intval($k1).' and '.intval($k2).', from a linked element';
			$alreadyPresent = linkExistsInPrimaryRelationship($cv, $rel, $k1, $k2);
			try {
				if(!$result = insertLinkIntoPrimaryRelationship($cv, $rel, $f1, $f2, $k1, $k2)) {
					$report['error'] .= "<br>Error inserting non-relationship link into primary relationship";
				} elseif(!$alreadyPresent) {
					$report['added'][] = $description;
				}
			} catch (Throwable $e) {
				$report['problems'][] = 'Added the link for '.$description.', but could not create indexes on the elements: '.$e->getMessage();
			}
		}
	}

	return $report;
}

/**
 * Load the links already in the Primary Relationship into the registry of links that
 * insertLinkIntoPrimaryRelationship uses to avoid making duplicates.
 *
 * Only needed when adding to a Primary Relationship that already exists. When one is being built from
 * scratch the registry starts empty and fills as the links are made, which is exactly right. For a
 * repair run the links already in the database have to be declared up front, otherwise every one of
 * them gets inserted a second time.
 *
 * @return boolean True if the registry was primed, false if the links could not be read
 */
function primePrimaryRelationshipLinkPairs() {
	global $xoopsDB;
	$linkPairs = &_primaryRelationshipLinkPairs();
	$sql = "SELECT `fl_key1`, `fl_key2`, `fl_relationship`, `fl_common_value` FROM ".$xoopsDB->prefix('formulize_framework_links')." WHERE fl_frame_id = -1";
	if(!$res = $xoopsDB->query($sql)) {
		return false;
	}
	while($row = $xoopsDB->fetchArray($res)) {
		$linkPairs[intval($row['fl_common_value'])][intval($row['fl_relationship'])][intval($row['fl_key1'])][intval($row['fl_key2'])] = true;
	}
	return true;
}

/**
 * The registry of links that have been established in the Primary Relationship, held here rather than
 * as a static inside insertLinkIntoPrimaryRelationship so that it can also be primed from the database.
 * @return array Returned by reference, so callers can add to it
 */
function &_primaryRelationshipLinkPairs() {
	static $linkPairs = array();
	return $linkPairs;
}

/**
 * A function to check that the elementId is real, because we don't want to insert old garbage into the Primary Relationship
 */
function elementExists($elementId) {
	static $existingElements = array();
	if(count($existingElements) == 0) {
		global $xoopsDB;
		$sql = "SELECT ele_id FROM ".$xoopsDB->prefix('formulize');
		$res = $xoopsDB->query($sql);
		$existingElements = $xoopsDB->fetchColumn($res, column: 0);
	}
	return in_array($elementId, $existingElements);
}


/**
 * Returns the mirror relationship type: 1 -> 1 || 2 -> 3 || 3 -> 2
 * @param int relationship - The number of the relationship type, 1 for one to one, 2 for one to many, 3 for many to one
 */
function mirrorRelationship($relationship) {
	switch($relationship) {
		case 1:
			return 1;
		case 2:
			return 3;
		case 3:
			return 2;
	}
}

/**
 * Inserts a link into the primary relationship.
 * Maintains a static array of all links added to the primary relationship this way, so that when first creating the Primary Relationship, we don't create duplicates.
 * Maintains a global array of all forms involved in links this page load, in case we want to do something with them later, may be relevant in migrations to F8.
 * Will create duplicates if passed duplicate settings, after the initial creation of the Primary Relationship! So use linkExistsInPrimaryRelationship function to determine if you should call this!
 *
 * Only ever inserts. A link that is already in the Primary Relationship is left exactly as it is,
 * including its unified delete setting, because once a Primary Relationship exists an admin's choices
 * in the relationships interface are the authority on it. We set these values when a link first
 * arrives, and never again.
 *
 * @param int cv - 1 or 0 depending if the link is based on a common value
 * @param int rel - a number representing the relationship type, 1 for one to one, 2 for one to many, 3 for many to one
 * @param int f1 - the id number of form 1 in the link
 * @param int f2 - the id number of form 2 in the link
 * @param int k1 - the id number of the element in form 1 used in the link
 * @param int k2 - the id number of the element in form 2 used in the link
 * @param int del - 1 or 0 indicating if entries should be deleted from one form when deleted from the other. Defaults to 0. This is a request, not the final say: deletion is turned on only if this is 1 AND formPairEnforcesDeletion() agrees that every other connection between these two forms also asks for it.
 * @param int con - 1 or 0 indicating if a one to one connection should trigger conditional behaviour when forms displayed together. Defaults to 1.
 * @param int book - 1 or 0 indicating if a one to one connection should trigger creation of entries in one form when an entry is saved in the other. Defaults to 1.
 * @return boolean|string - Returns boolean true on success, including if the link already exists, or false if insert failed. Prints out error text.
 */
function insertLinkIntoPrimaryRelationship($cv, $rel, $f1, $f2, $k1, $k2, $del=0, $con=1, $book=1) {
	global $xoopsDB, $linkForms;
	// held outside this function so that a repair run can declare the links that are already in the
	// database before calling here - see primePrimaryRelationshipLinkPairs()
	$linkPairs = &_primaryRelationshipLinkPairs();
	$result = true;
	$mrel = mirrorRelationship(intval($rel));
	if(!isset($linkPairs[intval($cv)][intval($rel)][intval($k1)][intval($k2)]) AND !isset($linkPairs[intval($cv)][$mrel][intval($k2)][intval($k1)])) {
		$linkPairs[$cv][$rel][$k1][$k2] = true;
		$linkForms[] = $f1;
		$linkForms[] = $f2;
		// Deleting entries in step with each other is a property of the two forms, not of any one link
		// between them, so the caller's request only stands if every other connection between the forms
		// agrees with it. Because formPairEnforcesDeletion looks at all the links joining the two forms,
		// rather than at this one, every link that goes through this calculation for a given pair of
		// forms during a run gets the same answer.
		//
		// Two links between the same forms CAN still end up differing, because the linked element pass in
		// populatePrimaryRelationship does not go through this calculation at all: it passes no $del, so
		// the condition below is false and the link is created with deletion off. That is deliberate and
		// harmless. Deletion is driven by whichever links have it on - checkForLinks adds
		// "AND fl_unified_delete = 1" when it gathers connected entries - so a link with deletion off
		// simply takes no part, and the configured link still deletes exactly what it was set up to
		// delete. Making the linked element pass agree instead would be the harmful option, since it
		// would extend deletion along a connection nobody configured for it.
		$del = (intval($del) == 1 AND formPairEnforcesDeletion($f1, $f2)) ? 1 : 0;
		$sql = "INSERT INTO ".$xoopsDB->prefix('formulize_framework_links').
			"(`fl_frame_id`,
			`fl_form1_id`,
			`fl_form2_id`,
			`fl_key1`,
			`fl_key2`,
			`fl_relationship`,
			`fl_unified_display`,
			`fl_unified_delete`,
			`fl_common_value`,
			`fl_one2one_conditional`,
			`fl_one2one_bookkeeping`)
			VALUES
			(-1,
			".intval($f1).",
			".intval($f2).",
			".intval($k1).",
			".intval($k2).",
			".intval($rel).",
			1,
			".intval($del).",
			".intval($cv).",
			".intval($con).",
			".intval($book).")";
		if(!$xoopsDB->queryF($sql)) {
			$result = false;
			print "<br>Could not insert a link into the Primary Relationship with this SQL:<br>$sql<br>".$xoopsDB->error();
		} else {
			$e1 = _getElementObject($k1);
			$e2 = _getElementObject($k2);
			if(!is_object($e1) OR !is_object($e2)) {
				throw new Error("Formulize Warning: could not load element objects to create indexes on elements $k1 and/or $k2");
			} else {
				if(strlen($e1->has_index()) == 0){
					$e1->createIndex();
				}
				if(strlen($e2->has_index()) == 0){
					$e2->createIndex();
				}
			}
		}
	}
	return $result;
}

/**
 * Decide whether a link about to be added to the Primary Relationship between two forms should make
 * their entries be deleted in step with each other.
 *
 * Deletion is a property of the two forms, not of a particular pairing of elements, so the answer is
 * worked out by looking at every connection between the forms rather than at the link being made. It
 * is yes only when all of the following hold:
 *
 * 1. Nothing already in the Primary Relationship for these two forms has deletion turned off. Once a
 *    pair of forms is settled in the Primary Relationship, a new link joining them a second way must
 *    not introduce deletion that was not there before, and an admin may have turned it off deliberately.
 * 2. Every link in every other relationship that joins these two forms has deletion turned on.
 * 3. There is at least one such link, since otherwise nothing has asked for deletion at all.
 *
 * Links whose elements no longer exist, or which claim a connection the elements do not actually have,
 * are ignored: they are not connections, so a stale one cannot suppress deletion. "User who made the
 * entries" links, where both keys are 0, are ignored too, because the Primary Relationship has no way
 * to represent that kind of connection and so cannot honour whatever it asks for.
 *
 * Linked elements do not get a vote. A linked element only appears in the links table as a Primary
 * Relationship row, never as a row in another relationship, so it is invisible to the second query
 * below. That is deliberate: such a connection is created with deletion off by default, and a default
 * is not an opinion. Where a linked element pair has also been saved as a real link in some other
 * relationship, that link votes normally.
 *
 * Deliberately not memoised. Each answer is read fresh so it is always authoritative, even if something
 * else in the same request has just changed a link. This costs one or two queries per link inserted.
 *
 * @param int f1 - the id number of one of the forms
 * @param int f2 - the id number of the other form
 * @return boolean True if deletion should be enforced between these two forms, false otherwise
 */
function formPairEnforcesDeletion($f1, $f2) {
	global $xoopsDB;
	$f1 = intval($f1);
	$f2 = intval($f2);
	$formPairClause = "((`fl_form1_id` = $f1 AND `fl_form2_id` = $f2) OR (`fl_form1_id` = $f2 AND `fl_form2_id` = $f1))";

	// 1. anything already in the Primary Relationship for this pair of forms has the final say, if del is 0
	$sql = "SELECT `fl_unified_delete` FROM ".$xoopsDB->prefix('formulize_framework_links')."
		WHERE `fl_frame_id` = -1 AND $formPairClause";
	if(!$res = $xoopsDB->query($sql)) {
		return false; // cannot confirm, so do not turn deletion on
	}
	while($row = $xoopsDB->fetchArray($res)) {
		if(intval($row['fl_unified_delete']) == 0) {
			return false;
		}
	}

	// 2 and 3. every connection between the forms that some other relationship defines must ask for it
	// if del is 0 somewhere, we go with that
	$sql = "SELECT `fl_key1`, `fl_key2`, `fl_common_value`, `fl_unified_delete` FROM ".$xoopsDB->prefix('formulize_framework_links')."
		WHERE `fl_frame_id` != -1 AND $formPairClause";
	if(!$res = $xoopsDB->query($sql)) {
		return false;
	}
	$connections = 0;
	while($row = $xoopsDB->fetchArray($res)) {
		$k1 = $row['fl_key1'];
		$k2 = $row['fl_key2'];
		if(!$k1 OR !$k2) { continue; }
		if(!elementExists($k1) OR !elementExists($k2) OR ($row['fl_common_value'] != 1 AND !elementsAreLinked($k1, $k2))) { continue; }
		if(intval($row['fl_unified_delete']) == 0) {
			return false;
		}
		$connections++;
	}
	return $connections > 0; // if there all connections between forms have del on, then we go with that
}

/**
 * Check whether a link exists in the Primary Relationship
 * @param int cv - 1 or 0 depending if the link is based on a common value
 * @param int rel - a number representing the relationship type, 1 for one to one, 2 for one to many, 3 for many to one
 * @param int k1 - the id number of the element in form 1 used in the link
 * @param int k2 - the id number of the element in form 2 used in the link
 * @return boolean True or False depending if a link with these properties exists in the Primary Relationship or not. Returns false if query fails.
 */
function linkExistsInPrimaryRelationship($cv, $rel, $k1, $k2) {
	global $xoopsDB;
	$result = false;
	$sql = "SELECT `fl_id`
		FROM ".$xoopsDB->prefix('formulize_framework_links')."
		WHERE
			`fl_frame_id` = -1
			AND `fl_common_value` = ".intval($cv)."
			AND ((
				`fl_key1` = ".intval($k1)."
				AND `fl_key2` = ".intval($k2)."
				AND `fl_relationship` = ".intval($rel)."
			) OR (
				`fl_key1` = ".intval($k2)."
				AND `fl_key2` = ".intval($k1)."
				AND `fl_relationship` = ".mirrorRelationship(intval($rel))."
			))";
		if($res = $xoopsDB->query($sql)) {
			$result = $xoopsDB->getRowsNum($res);
		}
		return $result;
}

/**
 * Make a new element in a form, with some association with another element
 * @param string type - Indicator of what kind of element we're making: new-common-parallel, new-common-textbox, new-linked-dropdown, new-linked-autocomplete, new-linked-multiselect-autocomplete, new-linked-checkboxes
 * @param int fid - The form id of the form where the element should be made
 * @param int otherElementId - The element id of an element associated with the new element we're making
 * @return boolean|int - Returns the id number of the element that was made, or false on failure
 */
function makeNewConnectionElement($type, $fid, $otherElementId) {
	global $xoopsDB;
	$element_handler = xoops_getmodulehandler('elements', 'formulize');
	$form_handler = xoops_getmodulehandler('forms', 'formulize');
	$result = false;
	if($otherElement = $element_handler->get($otherElementId) AND $otherForm = $form_handler->get($otherElement->getVar('fid'))) {
		$dataTypeInfo = $otherElement->getDataTypeInformation();
		$form = $form_handler->get($fid);
		$element = $element_handler->create();
		$ele_handle = substr($form::sanitize_handle_name(strtolower($form->getVar('form_handle')."_".$otherElement->getVar('ele_handle'))), 0, 40);
		$firstUniqueCheck = true;
		while (!$uniqueCheck = $form_handler->isElementHandleUnique($ele_handle)) {
			if ($firstUniqueCheck) {
					$ele_handle = $ele_handle . "_".$fid;
					$firstUniqueCheck = false;
			} else {
					$ele_handle = $ele_handle . "_copy";
			}
		}
		// set basics common to every element
    $element->hasData = true;
    $element->isSystemElement = false;
		$element->setVar('id_form', $fid);
		$element->setVar('ele_caption', htmlspecialchars_decode($otherForm->getSingular().' '.htmlspecialchars_decode($otherElement->getVar('ele_caption'))));
		$element->setVar('ele_handle', $ele_handle);
		$element->setVar('ele_order', 1);
		$element->setVar('ele_display', 1);
		// shunt everything else down one
		$sql = "UPDATE ".$xoopsDB->prefix("formulize")." SET ele_order = ele_order + 1 WHERE ele_order >= 1 AND id_form = $fid";
		$res = $xoopsDB->query($sql);
		// set stuff uniquely for the different situations...
		switch($type) {
			case 'new-common-textbox':
				$element->isLinked = false;
				$config_handler = xoops_gethandler('config');
        $formulizeConfig = $config_handler->getConfigsByCat(0, getFormulizeModId());
				$ele_value = array();
				$ele_value[0] = $formulizeConfig['t_width'];
				$ele_value[1] = $formulizeConfig['t_max'];
				$ele_value[3] = ($otherElement->hasNumericDataType() ? 1 : 0);
				$ele_value[5] = ($dataTypeInfo['dataType'] == 'decimal' ? $dataTypeInfo['dataTypeSize'] : (isset($formulizeConfig['number_decimals']) ? $formulizeConfig['number_decimals'] : 0));
				$ele_value[6] = isset($formulizeConfig['number_prefix']) ? $formulizeConfig['number_prefix'] : '';
				$ele_value[7] = isset($formulizeConfig['number_decimalsep']) ? $formulizeConfig['number_decimalsep'] : '.';
				$ele_value[8] = isset($formulizeConfig['number_sep']) ? $formulizeConfig['number_sep'] : ',';
				$ele_value[10] = isset($formulizeConfig['number_suffix']) ? $formulizeConfig['number_suffix'] : '';
				$ele_value[12] = 1; // Default trim option to enabled
				$element->setVar('ele_value', $ele_value); // does not need to be serialized, because element handler insert method applies cleanVars to everything, which will serialize it for us, and we don't want double serialization!
				$element->setVar('ele_type', 'text');
				if($ele_value[3] AND $dataTypeInfo['dataType'] == 'decimal') {
					$fieldDataType = $dataTypeInfo['dataTypeCompleteString'];
				} elseif($ele_value[3]) {
					$fieldDataType = 'int';
				} else {
					$fieldDataType = 'text';
				}
				break;
			case 'new-common-parallel':
				$element->isLinked = $otherElement->isLinked;
				$element->setVar('ele_value', $otherElement->getVar('ele_value')); // does not need to be serialized, because element handler insert method applies cleanVars to everything, which will serialize it for us, and we don't want double serialization!
				$element->setVar('ele_type', $otherElement->getVar('ele_type'));
				$element->setVar('ele_desc', htmlspecialchars_decode($otherElement->getVar('ele_desc')));
				$element->setVar('ele_caption', htmlspecialchars_decode($otherElement->getVar('ele_caption')));
				$element->setVar('ele_colhead', htmlspecialchars_decode($otherElement->getVar('ele_colhead')));
				$element->setVar('ele_required', $otherElement->getVar('ele_required'));
				$element->setVar('ele_uitext', $otherElement->getVar('ele_uitext'));
				$element->setVar('ele_uitextshow', $otherElement->getVar('ele_uitextshow'));
				$element->setVar('ele_delim', $otherElement->getVar('ele_delim'));
				$element->setVar('ele_private', $otherElement->getVar('ele_private'));
				$element->setVar('ele_disabled', $otherElement->getVar('ele_disabled'));
				$element->setVar('ele_display', $otherElement->getVar('ele_display'));
				$element->setVar('ele_encrypt', $otherElement->getVar('ele_encrypt'));
				$element->setVar('ele_filtersettings', $otherElement->getVar('ele_filtersettings'));
				$element->setVar('ele_disabledconditions', $otherElement->getVar('ele_disabledconditions'));
				$element->setVar('ele_use_default_when_blank', $otherElement->getVar('ele_use_default_when_blank'));
				$element->setVar('ele_exportoptions', $otherElement->getVar('ele_exportoptions'));
				$fieldDataType = $dataTypeInfo['dataTypeCompleteString'];
				break;
			case 'new-linked-dropdown':
				$element->isLinked = true;
				$element->setVar('ele_value', array(
					0 => 1,
					1 => 0,
					2 => $otherForm->getVar('fid')."#*=:*".$otherElement->getVar('ele_handle'),
					8 => 0
				));
				$element->setVar('ele_type', 'selectLinked');
				$fieldDataType = 'bigint';
				break;
			case 'new-linked-autocomplete':
				$element->isLinked = true;
				$element->setVar('ele_value', array(
					0 => 1,
					1 => 0,
					2 => $otherForm->getVar('fid')."#*=:*".$otherElement->getVar('ele_handle'),
					8 => 1
				));
				$element->setVar('ele_type', 'autocompleteLinked');
				$fieldDataType = 'bigint';
				break;
			case 'new-linked-multiselect-autocomplete':
				$element->isLinked = true;
				$element->setVar('ele_value', array(
					0 => 1,
					1 => 1,
					2 => $otherForm->getVar('fid')."#*=:*".$otherElement->getVar('ele_handle'),
					8 => 1
				));
				$element->setVar('ele_type', 'autocompleteLinked');
				$fieldDataType = 'text';
				break;
			case 'new-linked-checkboxes':
				$element->isLinked = true;
				$element->setVar('ele_value', array(2 => $otherForm->getVar('fid')."#*=:*".$otherElement->getVar('ele_handle')));
				$element->setVar('ele_type', 'checkboxLinked');
				$fieldDataType = 'text';
				break;
		}
		if($result = $element_handler->insert($element)) { // false on failure, element id on success
			$elementId = $result;
			addElementToMultipageScreens($fid, $elementId, positionAtTopOfPage: 1);
			if($form_handler->insertElementField($element, $fieldDataType)) {
				if($element->createIndex() == false) {
					print "Error: could not create an index in the database for the new element. Please contact info@formulize.org for assistance.";
				}
			} else {
				print "Error: could not create the field in the database for the new element. Please contact info@formulize.org for assistance.";
			}
		} else {
			print "Error: could not save the new element. Please contact info@formulize.org for assistance.";
		}

	}
	return $result;
}

/**
 * Attempt to make a subform interface involving the specified forms, connected by the linking element,
 * and add it to all appropriate screens. Or use an existing element if there is one.
 * Subform interface element will added to screens only if it has not been assigned to screens already.
 * The subform interface will be added to pages of screens that have all the form's elements on them already,
 * and it will be added to new pages on screens where there were no pages that had all the form elements already.
 * @param object|int mainFormObjectOrId - a form object or id representing the main form in the connection
 * @param object|int subformObjectOrId - a form object or id representing the subform in the connection
 * @param int|string|object elementIdentifier - the element id, handle or object of the subform element that connects to the main form
 * @return boolean|int Returns the element id of the subform element created, or an existing subform element found, or false on failure.
 */
function makeSubformInterface($mainFormObjectOrId, $subformObjectOrId, $elementIdentifier) {
	$subformElementId = false;
	$form_handler = xoops_getmodulehandler('forms', 'formulize');
	$mainFormObject = is_a($mainFormObjectOrId, 'formulizeForm') ? $mainFormObjectOrId : $form_handler->get($mainFormObjectOrId);
	$subformObject = is_a($subformObjectOrId, 'formulizeForm') ? $subformObjectOrId : $form_handler->get($subformObjectOrId);
	if($mainFormObject AND $subformObject) {
		if($subformElementId = findOrMakeSubformElement($mainFormObject, $subformObject, $elementIdentifier)) {
			$makeNewPageIfNotAddedToExistingPages = true;
			addElementToMultiPageScreens($mainFormObject->getVar('fid'), $subformElementId, $makeNewPageIfNotAddedToExistingPages);
		} else {
			print "Error: could not create subform element to show entries in ".$subformObject->getVar('title').". Please contact info@formulize.org for assistance.";
		}
	}
	return $subformElementId;
}

/**
 * Create a subform element on the form that is the mainform of a connection. Or find an existing element that serves that purpose if any exists.
 * Existing element is found by convention in the name of the screen handle.
 * Also will make a subform screen for the subform interface to use, if one does not exist already.
 * @param object mainFormObject - the form object of the main form in the connection, where the subform element will be made
 * @param object subformObject - the form object of the subform in the connection, that the subform element will point to
 * @param int|string|object elementIdentifier - the element id, handle or object of the subform element that connects to the main form
 * @return int|boolean return the element id of the element created or found, or false on failure
 */
function findOrMakeSubformElement($mainFormObject, $subformObject, $elementIdentifier) {
	global $xoopsDB;
	$form_handler = xoops_getmodulehandler('forms', 'formulize');
	$element_handler = xoops_getmodulehandler('elements', 'formulize');
	$element = $element_handler->create();
	$ele_handle = substr($mainFormObject::sanitize_handle_name(strtolower($mainFormObject->getVar('form_handle')."_subform_".$subformObject->getVar('title'))), 0, 40);
	if($form_handler->isElementHandleUnique($ele_handle)) {
		$sql = "SELECT max(ele_order) as new_order FROM ".$xoopsDB->prefix("formulize")." WHERE id_form = ".$mainFormObject->getVar('fid');
		$res = $xoopsDB->query($sql);
		$array = $xoopsDB->fetchArray($res);
		$orderChoice = $array['new_order'] + 1;
		$element->setVar('id_form', $mainFormObject->getVar('fid'));
		$element->setVar('ele_handle', $ele_handle);
		$element->setVar('ele_order', $orderChoice);
		$element->setVar('ele_display', 1);
		$element->setVar('ele_caption', $subformObject->getPlural());
		$element->setVar('ele_type', 'subformListings');
		$element->setVar('ele_value', array(
			0 => $subformObject->getVar('fid'),
			1 => $subformObject->getVar('pi'),
			2 => 0,
			3 => 1,
			4 => 0,
			5 => 0,
			6 => 'subform',
			7 => [],
			8 => 'row',
			9 => $subformObject->getPlural(),
			'simple_add_one_button' => 1,
			'simple_add_one_button_text' => _AM_ADD.' '.$subformObject->getSingular(),
			'disabledelements' => [],
			'subform_prepop_element' => 0,
			'enforceFilterChanges' => 1,
			'show_delete_button' => 1,
			'show_clone_button' => 0,
			'display_screen' => findOrMakeSubformScreen($elementIdentifier, $mainFormObject),
			'addButtonLimit' => 0,
			'SortingElement' => 0,
			'SortingDirection' => 'ASC',
			'UserFilterByElement' => 0,
			'FilterByElementStartState' => 1
		));
		return $element_handler->insert($element);
	} else {
		$subformElement = $element_handler->get($ele_handle);
		return $subformElement->getVar('ele_id');
	}
}

/**
 * Find a "subform screen" on the element's form, for displaying entries in subform interfaces
 * Or make one if none found. Do not include the specified element in the new screen, since that would be redundant, as it's the linking element
 * @param int|string|object elementIdentifier - an element id, handle or object, of the linking element in the subform that connects it to the mainform
 * @param int|object mainForm - the form id or object of the main form that is used in this subform context
 * @return int|boolean Returns the screen id of the screen that was found or made, or false on failure
 */
function findOrMakeSubformScreen($elementIdentifier, $mainForm) {
	$subformScreenFound = false;
	$form_handler = xoops_getmodulehandler('forms', 'formulize');
	$mainFormObject = is_int($mainForm) ? $form_handler->get($mainForm) : $mainForm;
	if(is_a($mainFormObject, 'formulizeForm') AND $element = _getElementObject($elementIdentifier)) {
		if($form = $form_handler->get($element->getVar('fid'))) {
			$screen_handler = xoops_getmodulehandler('multiPageScreen', 'formulize');
			$criteria = new Criteria('type','multiPage');
			$screens = $screen_handler->getObjects($criteria, $element->getVar('fid'));
			foreach($screens as $screen) {
				if($screen->getVar('screen_handle') == 'subform_for_form_'.$mainFormObject->getVar('fid')) {
					$subformScreenFound = $screen->getVar('sid');
					break;
				}
			}
			if($subformScreenFound == false) {
				$newScreen = $screen_handler->create();
				$screen_handler->setDefaultFormScreenVars($newScreen, $form);
				$title = sprintf(_AM_FORMULIZE_FORM_SCREEN_TITLE, $form->getSingular()).' - subform of '.$mainFormObject->getPlural();
				$newScreen->setVar('title', $title);
				$newScreen->setVar('screen_handle', $screen_handler->makeHandleUnique('subform_for_form_'.$mainFormObject->getVar('fid'), ""));
				$elements = $form->getVar('elements');
				if(isset($elements[$element->getVar('ele_id')])) {
					unset($elements[$element->getVar('ele_id')]);
				}
				$newScreen->setVar('pages', serialize(array(array_values($elements))));
				$newScreen->setVar('pagetitles', serialize(array(0=>$form->getSingular())));
				$newScreenId = $screen_handler->insert($newScreen);
				if($newScreenId == false) {
					print "Error: could not create subform screen for displaying the subform entries. Please contact info@formulize.org for assistance.";
				}
			}
		}
	}
	return $subformScreenFound ? $subformScreenFound : $newScreenId;
}

/**
 * Add an element to multipage screen pages where all elements in the form are already present
 * Do this for all the screens that a form has
 * But abort if any screen has the element already, since we then leave it up to the webmaster who has already been at work with this element
 * Optionally, create a new page and add the element to the page. Name the new page with the element colhead or caption.
 * @param int fid - the form id number that we're looking for screens in
 * @param int elementIdentifier - the element id number, or handle, or element object that we're adding to the pages
 * @param boolean makeNewPageIfNotAddedToExistingPages - a flag to indicate whether a new page should be added to the screen if the element wasn't added to an existing page
 * @param int positionAtTopOfPage - passing any non null, non false value will position the element at the top of the page. Default is zero, for bottom of the page.
 * @return boolean Return true, or false if one or more additions to pages failed
 */
function addElementToMultiPageScreens($fid, $elementIdentifier, $makeNewPageIfNotAddedToExistingPages = false, $positionAtTopOfPage = 0) {
	$result = true;
	$form_handler = xoops_getmodulehandler('forms', 'formulize');
	if($element = _getElementObject($elementIdentifier) AND $form = $form_handler->get($fid)) {
		if($element->isSystemElement) {
			return true; // ignore system elements
		}
		$elementId = $element->getVar('ele_id');
		$screen_handler = xoops_getmodulehandler('multiPageScreen', 'formulize');
		$criteria_object = new CriteriaCompo(new Criteria('type','multiPage'));
		$screens = $screen_handler->getObjects($criteria_object,intval($fid));
		$candidatePages = array();
		$candidateScreensForNewPages = array();
		foreach($screens as $screen) {
			$sid = $screen->getVar('sid');
			$screenObject = $screen_handler->get($sid); // strangely getting over again, but getObjects returns plain screen objects and we need to get the whole screen with all metadata, so must be getted again :(
			$pages = $screenObject->getVar('pages');
			// find the pages that contain all elements in this form
		  ksort($pages);
			foreach($pages as $i=>$page) {
				// are we dealing with a regular page with element ids?
				// an empty page only counts when it is the screen's one and only page, since that is the brand new state of the default screen on a new form, and the element belongs on it. An empty page alongside other pages is not a page we should be putting elements on.
				$firstItem = empty($page) ? null : $page[array_key_first($page)];
				if(is_numeric($firstItem) OR ($firstItem === null AND count($pages) == 1)) {
					// element is in this page already, stop looking, element has already been managed on screens
					if(in_array($elementId, $page)) {
						return false;
					}
					foreach($form->getVar('elements') as $ele_id) {
						if($ele_id != $elementId AND !in_array($ele_id, $page) AND !in_array($ele_id, $form->getVar('systemElements'))) {
							continue 2; // go to next page
						}
					}
					// page did contain all the elements (except for this one), so this page is a candidate for adding the element to
					$candidatePages[$sid][] = $i;
				}
			}
			if(!isset($candidatePages[$sid])) {
				$candidateScreensForNewPages[$sid] = ($i+1);
			}
		}
		foreach($candidatePages as $sid=>$pageOrdinals) {
			$screenObject = $screen_handler->get($sid); // strangely getting over again, but getObjects returns plain screen objects and we need to get the whole screen with all metadata, so must be getted again :(
			$pages = $screenObject->getVar('pages');
			foreach($pageOrdinals as $i) {
				if($positionAtTopOfPage) {
					array_unshift($pages[$i], $elementId);
				} else {
					$pages[$i][] = $elementId;
				}
			}
			$screenObject->setVar('pages', serialize($pages)); // serialize ourselves, because screen handler insert method does not pass things through cleanVars, which would serialize for us
			$insertResult = $screen_handler->insert($screenObject, force: true);
			if($insertResult == false) {
				print "Error: could not add the new element to the screen \"".$screenObject->getVar('title')."\" (id: $sid). Please contact info@formulize.org for assistance.";
				$result = false;
			}
		}
		if($makeNewPageIfNotAddedToExistingPages AND count($candidateScreensForNewPages)>0) {
			foreach($candidateScreensForNewPages as $sid=>$newPageOrdinal) {
				$screenObject = $screen_handler->get($sid); // strangely getting over again, but getObjects returns plain screen objects and we need to get the whole screen with all metadata, so must be getted again :(
				$pages = $screenObject->getVar('pages');
				$pagetitles = $screenObject->getVar('pagetitles');
				$conditions = $screenObject->getVar('conditions');
				$pages[$newPageOrdinal] = array($elementId);
				$pagetitles[$newPageOrdinal] = $element->getUIName();
				$conditions[$newPageOrdinal] = array();
				$screenObject->setVar('pages', serialize($pages)); // serialize ourselves, because screen handler insert method does not pass things through cleanVars, which would serialize for us
				$screenObject->setVar('pagetitles', serialize($pagetitles));
				$screenObject->setVar('conditions', serialize($conditions));
				$insertResult = $screen_handler->insert($screenObject, force: true);
				if($insertResult == false) {
					throw new Exception("Could not add the new element to the screen \"".$screenObject->getVar('title')."\" (id: $sid). Please contact info@formulize.org for assistance.");
					$result = false;
				}
			}
		}
	} else {
	 throw new Exception("Invalid element or form identifier passed to addElementToMultiPageScreens function.");
	}
	return $result;
}

/**
 * Create a new form with the given name, including setting up default screens, etc, as if the user created it normally themselves
 * @param string name - The name to use for the form
 * @param int|string|object - activeFormIdentifier - the id, handle or object of the form that was active when this creation event was triggered
 * @return int|boolean Returns the new form id, or false if creation failed
 */
function createNewFormWithName($name, $activeFormIdentifier) {
	$result = false;
	$form_handler = xoops_getmodulehandler('forms', 'formulize');
	$activeForm = is_a($activeFormIdentifier, 'formulizeForm') ? $activeFormIdentifier : $form_handler->get($activeFormIdentifier);
	if($name AND $activeForm) {
		global $xoopsDB;
		$formObject = $form_handler->create();
		$handle = formulizeForm::sanitize_handle_name($name);
		if (strlen($handle)) {
    	$uniqueCheckCounter = 0;
			while (!$uniqueCheck = $form_handler->isFormHandleUnique($handle, "")) {
        $handle = str_replace('_'.$uniqueCheckCounter,'',$handle);
        $uniqueCheckCounter++;
        $handle = $handle . "_".$uniqueCheckCounter;
			}
    }
		if(strlen($handle)) {
			$formObject->setVar('form_handle', $handle);
			$formObject->setVar('title', $name);
			$formObject->setVar('single', '');
			if($fid = $form_handler->insert($formObject, force: true)) {

				$formObject->setVar('id_form', $fid);
				$formObject->setVar('fid', $fid);
 				$form_handler->createDataTable($fid);

				// setup default screens...
				$multiPageScreenHandler = xoops_getmodulehandler('multiPageScreen', 'formulize');
  			$defaultFormScreen = $multiPageScreenHandler->create();
  			$multiPageScreenHandler->setDefaultFormScreenVars($defaultFormScreen, $formObject);
  			if(!$defaultFormScreenId = $multiPageScreenHandler->insert($defaultFormScreen, force: true)) {
    			print "Error: could not create default form screen: ".$xoopsDB->error();
  			}
  			$listScreenHandler = xoops_getmodulehandler('listOfEntriesScreen', 'formulize');
    		$screen = $listScreenHandler->create();
    		$listScreenHandler->setDefaultListScreenVars($screen, $defaultFormScreenId, $formObject);
  			if(!$defaultListScreenId = $listScreenHandler->insert($screen, force: true)) {
    			print "Error: could not create default list screen: ".$xoopsDB->error();
  			}
				if($defaultFormScreenId AND $defaultListScreenId) {
					$formObject->setVar('defaultform', $defaultFormScreenId);
					$formObject->setVar('defaultlist', $defaultListScreenId);
					if(!$form_handler->insert($formObject, force: true)) {
						print "Error: could not update form with default screens: ".$xoopsDB->error();
					}
				}

				// assign this form as required to the active form's applications (the form which gave rise to this once)
				$application_handler = xoops_getmodulehandler('applications', 'formulize');
				$apps = $application_handler->getApplicationsByForm($activeForm->getVar('fid'));

				$selectedAppIds = array();
				foreach($apps as $thisAppObject) {
					$selectedAppIds[] = $thisAppObject->getVar('appid');
					$thisAppForms = $thisAppObject->getVar('forms');
					$thisAppForms[] = $fid;
					$thisAppObject->setVar('forms', serialize($thisAppForms));
					if(!$application_handler->insert($thisAppObject, force: true)) {
						print "Error: could not add the form to one of the applications properly: ".$xoopsDB->error();
					}
				}

				// setup the menu links
				$selectedAdminGroupIdsForMenu = array(XOOPS_GROUP_ADMIN);
				$menuitems = "null::" . formulize_db_escape($formObject->getVar('title')) . "::fid=" . formulize_db_escape($fid) . "::::".implode(',',$selectedAdminGroupIdsForMenu)."::null";
				if(!empty($selectedAppIds)) {
					foreach($selectedAppIds as $appid) {
						$application_handler->insertMenuLink(formulize_db_escape($appid), $menuitems, force: true);
					}
				} else {
					$application_handler->insertMenuLink(0, $menuitems, force: true);
				}

				$result = $fid;
			}
		}
	}
	return $result;
}

/**
 * Check the primary relationship and return all forms that are directly connected to this form
 * @param int fid - the id number of the form in question
 * @return array Returns an array of the form ids found, if any. Array can be empty.
 */
function connectedFormsToThisForm($fid) {
	global $xoopsDB;
	$fid = intval($fid);
	$formIds = array();
	$sql = array(
		"SELECT DISTINCT(fl_form1_id)
		FROM ".$xoopsDB->prefix('formulize_framework_links')."
		WHERE fl_frame_id = -1
		AND fl_form2_id = $fid
		AND fl_form1_id != $fid",
		"SELECT DISTINCT(fl_form2_id)
		FROM ".$xoopsDB->prefix('formulize_framework_links')."
		WHERE fl_frame_id = -1
		AND fl_form1_id = $fid
		AND fl_form2_id != $fid"
	);
	foreach($sql as $thisSql) {
		if($res = $xoopsDB->query($thisSql)) {
			$formIds = array_merge($formIds, $xoopsDB->fetchColumn($res, column: 0));
		}
	}
	return $formIds;
}
