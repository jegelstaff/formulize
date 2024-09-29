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
	// For the extra links, the linked element form will be on Many form, the target form will be the One form

	// Set the Primary Relationship as the relationship in effect on all screens that are 'Form Only' right now

	global $xoopsDB, $linkForms;
	$linkForms = array();
	$primaryRelationshipError = false;

	$sql = "INSERT INTO ".$xoopsDB->prefix('formulize_frameworks')." (`frame_id`, `frame_name`) VALUES (-1, 'Primary Relationship');
    UPDATE ".$xoopsDB->prefix('formulize_framework_links')." SET fl_unified_display = 1";
	if(!$res = $xoopsDB->queryF($sql)) {
		$primaryRelationshipError = 'Could not create relationship entry';
	}

	$sql = "SELECT * FROM ".$xoopsDB->prefix('formulize_framework_links');
	if(!$primaryRelationshipError AND !$res = $xoopsDB->query($sql)) {
		$primaryRelationshipError = 'Could not check existing links';
	}

	while(!$primaryRelationshipError AND $row = $xoopsDB->fetchArray($res)) {
		$f1 = $row['fl_form1_id'];
		$f2 = $row['fl_form2_id'];
		$k1 = $row['fl_key1'];
		$k2 = $row['fl_key2'];
		$rel = $row['fl_relationship'];
		$cv = $row['fl_common_value'];
		if($k1 AND $k2) { // 0,0 indicates a "user who made the entries" relationship. Ancient and never used??
			$primaryRelationshipError = insertLinkIntoPrimaryRelationship($cv, $rel, $f1, $f2, $k1, $k2);
		}
	}

	$sql = "SELECT id_form, ele_id, ele_value FROM ".$xoopsDB->prefix('formulize')." WHERE ele_type IN ('select', 'checkbox') AND ele_value LIKE '%#*=:*%'";
	if(!$primaryRelationshipError AND !$res = $xoopsDB->query($sql)) {
		$primaryRelationshipError = 'Could not collate list of existing linked elements';
	}

	while(!$primaryRelationshipError AND $row = $xoopsDB->fetchArray($res)) {
		$f2 = $row['id_form'];
		$k2 = $row['ele_id'];
		$ele_value = unserialize($row['ele_value']);
		$boxproperties = explode("#*=:*", $ele_value[2]);
		$sourceElementHandle = $boxproperties[1];
		$sourceFormLookup = "SELECT id_form, ele_id FROM ".$xoopsDB->prefix('formulize')." WHERE ele_handle = '".formulize_db_escape($sourceElementHandle)."'";
		if(!$sourceFormLookupResult = $xoopsDB->query($sourceFormLookup)) {
			$primaryRelationshipError = "Could not lookup source details of linked form element with this SQL:<br>$sourceFormLookup";
		}
		if(!$primaryRelationshipError) {
			$sourceFormLookupRow = $xoopsDB->fetchArray($sourceFormLookupResult);
			$f1 = $sourceFormLookupRow['id_form'];
			$k1 = $sourceFormLookupRow['ele_id'];
			$rel = 2;
			$cv = 0;
			$primaryRelationshipError = insertLinkIntoPrimaryRelationship($cv, $rel, $f1, $f2, $k1, $k2);
		}
	}

	if(!empty($linkForms)) {
		$linkForms = array_unique($linkForms);
		$sql = "UPDATE ".$xoopsDB->prefix('formulize_screen')." SET `frid` = -1 WHERE `frid` = 0 AND `fid` IN (".implode(', ', $linkForms).")";
		if(!$primaryRelationshipError AND !$res = $xoopsDB->queryF($sql)) {
			$primaryRelationshipError = 'Could not update existing screens to use Primary Relationship';
		}
	}

	return $primaryRelationshipError;
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
 * Inserts a link into the primary relationship, if that link doesn't already exist
 * Maintains a global variable array of the form ids of all links added to the primary relationship this way
 * @param int cv - 1 or 0 depending if the link is based on a common value
 * @param int rel - a number representing the relationship type, 1 for one to one, 2 for one to many, 3 for many to one
 * @param int f1 - the id number of form 1 in the link
 * @param int f2 - the id number of form 2 in the link
 * @param int k1 - the id number of the element in form 1 used in the link
 * @param int k2 - the id number of the element in form 2 used in the link
 * @return boolean|string - False if no error, or a string containing the error text
 */
function insertLinkIntoPrimaryRelationship($cv, $rel, $f1, $f2, $k1, $k2) {
	static $linkPairs = array();
	$primaryRelationshipError = '';
	$mrel = mirrorRelationship($rel);
	if(!isset($linkPairs[$cv][$rel][$k1][$k2]) AND !isset($linkPairs[$cv][$mrel][$k2][$k1])) {
		global $xoopsDB, $linkForms;
		$linkPairs[$cv][$rel][$k1][$k2] = true;
		$linkForms[] = $f1;
		$linkForms[] = $f2;
		$sql = "INSERT INTO ".$xoopsDB->prefix('formulize_framework_links').
			"(`fl_frame_id`,
			`fl_form1_id`,
			`fl_form2_id`,
			`fl_key1`,
			`fl_key2`,
			`fl_relationship`,
			`fl_unified_display`,
			`fl_unified_delete`,
			`fl_common_value`)
			VALUES
			(-1,
			$f1,
			$f2,
			$k1,
			$k2,
			$rel,
			1,
			0,
			$cv)";
		if(!$xoopsDB->queryF($sql)) {
			$primaryRelationshipError = "Could not insert an existing link into the Primary Relationship with this SQL:<br>$sql";
		}
	}
	return $primaryRelationshipError;
}
