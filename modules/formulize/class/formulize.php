<?php

###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) Formulize Project                        ##
###############################################################################
##                    XOOPS - PHP Content Management System                  ##
##                       Copyright (c) 2000 XOOPS.org                        ##
##                          <http://www.xoops.org/>                          ##
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
##  Author of this file: Formulize Project                                   ##
##  Project: Formulize                                                       ##
###############################################################################

include_once XOOPS_ROOT_PATH.'/modules/formulize/include/common.php';

#[AllowDynamicProperties]
class FormulizeObject extends XoopsObject {

	static function sanitize_handle_name($handle_name) {
		// strip non-alphanumeric characters from handles
		return preg_replace("/[^a-zA-Z0-9_-]+/", "", str_replace(" ", "_", $handle_name));
	}

	/**
	 * Keep a list of the field names that are serialized arrays in the DB
	 * Eventually this is redundant because we will convert them to JSON, or a more normalized structure
	 * @return array The list of fields names that are serialized arrays
	 */
	static function serializedDBFields() {
		return [
			'formulize' => [
				'ele_value',
				'ele_uitext',
				'ele_filtersettings',
				'ele_disabledconditions',
				'ele_exportoptions',
			],
			'formulize_notification_conditions' => [
				'not_cons_con'
			],
			'formulize_screen_form' => [
				'formelements',
				'elementdefaults',
			],
			'formulize_screen_multipage' => [
				'buttontext',
				'pages',
				'pagetitles',
				'conditions',
				'elementdefaults'
			],
			'formulize_screen_listofentries' => [
				'limitviews',
				'defaultview',
				'advanceview',
				'hiddencolumns',
				'decolumns',
				'customactions',
				'fundamental_filters'
			],
			'formulize_screen_calendar' => [
				'datasets'
			],
			'formulize_group_filters' => [
				'filter'
			],
			'formulize_advanced_calculations' => [
				'steps',
  			'steptitles',
  			'fltr_grps',
  			'fltr_grptitles'
			],
			'formulize_digest_data' => [
				'extra_tags'
			],
			'formulize_screen_calendar' => [
				'datasets'
			]
		];
	}

}
