<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2011 Freeform Solutions                  ##
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
##  Author of this file: Freeform Solutions                                  ##
##  Project: Formulize                                                       ##
###############################################################################

require_once XOOPS_ROOT_PATH . "/modules/formulize/class/elements.php"; // you need to make sure the base element class has been read in first!
require_once XOOPS_ROOT_PATH . "/modules/formulize/class/userAccountElement.php";

class formulizeUserAccountTimezoneElement extends formulizeUserAccountElement {

    function __construct() {
			parent::__construct();
      $this->name = "User Account Timezone";
			$this->userProperty = "profile:timezone";
		}

}

#[AllowDynamicProperties]
class formulizeUserAccountTimezoneElementHandler extends formulizeUserAccountElementHandler {

	function create() {
		return new formulizeUserAccountTimezoneElement();
	}

	/**
	 * Load the IANA timezone for this user entry.
	 *
	 * Reads the profile:timezone column via the parent. When no timezone has been set
	 * in the profile, falls back to the user's legacy timezone_offset converted to an
	 * IANA identifier.
	 *
	 * @param object    $element  The element object
	 * @param mixed     $value    Ignored; value is read from the user profile
	 * @param int|mixed $entry_id Entry ID
	 * @return string|null IANA timezone string, or null if none found
	 */
	function loadValue($element, $value, $entry_id) {
		$value = parent::loadValue($element, $value, $entry_id);
		if(!$value) {
			// get the uid for the current entry, if there is one
			// get that user's timezone_default, if there is one
			$fid = $element->getVar('fid');
			$form_handler = xoops_getmodulehandler('forms', 'formulize');
			$formObj = $form_handler->get($fid);
			if($entryUserId = $formObj ? $formObj->getSystemUserIdFromEntry($entry_id) : 0) {
				$member_handler = xoops_gethandler('member');
				if($userObject = $member_handler->getUser($entryUserId)) {
					$value = formulize_getIANATimezone($userObject->getVar('timezone_offset'));
				}
			}
		}
		return $value;
	}

	/**
	 * Render the timezone field as a native HTML select populated from the IANA timezone list.
	 *
	 * Defaults to the site's default timezone when no value is set. When disabled, renders
	 * the timezone name as a read-only label.
	 *
	 * @param mixed  $ele_value  Current IANA timezone string
	 * @param string $caption    Field caption
	 * @param string $markupName HTML select name
	 * @param bool   $isDisabled Whether the field is read-only
	 * @param object $element    The element object (unused)
	 * @param mixed  $entry_id   Entry ID (unused)
	 * @param mixed  $screen     Screen object (unused)
	 * @param mixed  $owner      Owner context (unused)
	 * @return XoopsFormLabel
	 */
	function render($ele_value, $caption, $markupName, $isDisabled, $element, $entry_id, $screen, $owner) {
		if(!$ele_value) {
			global $xoopsConfig;
			$ele_value = formulize_getIANATimezone($xoopsConfig['default_TZ']);
		}
		if($isDisabled) {
			return new XoopsFormLabel($caption, $this->makeValueSafeForReadOnlyDisplay($ele_value, $element->getVar('ele_handle'), $entry_id), $markupName);
		}
		$timezones = formulize_getTimezoneList();
		$html = '<select name="'.$markupName.'" id="'.$markupName.'" onchange="javascript:formulizechanged=1;">';
		foreach($timezones as $tz) {
			$selected = ($tz == $ele_value) ? ' selected="selected"' : '';
			$html .= '<option value="'.htmlspecialchars($tz, ENT_QUOTES).'"'.$selected.'>'.htmlspecialchars($tz, ENT_QUOTES).'</option>';
		}
		$html .= '</select>';
		return new XoopsFormLabel($caption, $html, $markupName);
	}

	/**
	 * Build a WHERE clause fragment to search by timezone.
	 *
	 * Delegates to an EXISTS subquery against the profile_profile.timezone column.
	 *
	 * @param string $term       Search term (IANA timezone string)
	 * @param string $operator   SQL operator
	 * @param string $quotes     Quote character(s) for the term
	 * @param string $likebits   LIKE wildcards
	 * @param int    $fid        Form ID (unused)
	 * @param string $tableAlias Alias for the users table in the outer query
	 * @return string SQL WHERE clause fragment
	 */
	function buildSearchWhereClause($term, $operator, $quotes, $likebits, $fid, $tableAlias = 'main') {
		return $this->buildProfileExistsClause('timezone', $term, $operator, $quotes, $likebits, $tableAlias);
	}

}
