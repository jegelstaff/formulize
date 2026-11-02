<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2004 Freeform Solutions                  ##
##                Portions copyright (c) 2003 NS Tai (aka tuff)              ##
##                       <http://www.brandycoke.com/>                        ##
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

$modversion = array(
	'name' => _MI_formulize_NAME,
	'version' => "8.30-beta",
	'min_php_version' => "8.1",
	'min_php_version_id' => "80100",
	'description' => _MI_formulize_DESC,
	'author' => "The Formulize Project - formulize.org",
	'license' => "GPL-2.0",
	'image' => "images/formulize.gif",
	'dirname' => "formulize",
	'dbversion' => 25,
	'onUpdate' => "include/on_update.php"
);

$modversion['sqlfile']['mysql'] = "sql/mysql.sql";
$modversion['sqlfile']['pdo.mysql'] = "sql/mysql.sql";

// Tables created by sql file (without prefix!)
// Tables marked LEGACY are no longer created or used. They are listed only so that uninstalling Formulize drops
// them on very old sites that still have them.
$modversion['tables'] = array(
	"formulize",
	"formulize_id",
	"formulize_menu", // LEGACY
	"formulize_menu_links",
	"formulize_menu_permissions",
	"formulize_resource_mapping",
	"formulize_frameworks",
	"formulize_framework_forms", // LEGACY
	"formulize_framework_elements", // LEGACY
	"formulize_framework_links",
	"formulize_menu_cats", // LEGACY
	"formulize_saved_views",
	"group_lists",
	"formulize_other",
	"formulize_notification_conditions",
	"formulize_valid_imports", // LEGACY
	"formulize_screen",
	"formulize_screen_multipage",
	"formulize_screen_listofentries",
	"formulize_screen_template",
  "formulize_screen_calendar",
  "formulize_screen_map",
	"formulize_entry_owner_groups",
	"formulize_application_form_link",
	"formulize_applications",
	"formulize_screen_form",
	"formulize_advanced_calculations",
	"formulize_group_filters",
	"formulize_groupscope_settings",
	"formulize_procedure_logs",
	"formulize_procedure_logs_params",
	"formulize_deletion_logs",
  "formulize_apikeys",
	"formulize_tokens",
  "formulize_digest_data",
  "formulize_passcodes",
  "tfa_codes",
  "formulize_ai_keys"
);

$modversion['formulize_exportable_tables'] = array(
	"formulize",
	"formulize_id",
	"formulize_menu",
	"formulize_menu_links",
	"formulize_menu_permissions",
	"formulize_frameworks",
	"formulize_framework_forms",
	"formulize_framework_elements",
	"formulize_framework_links",
	"formulize_menu_cats",
	"formulize_saved_views",
	"formulize_notification_conditions",
	"formulize_screen",
	"formulize_screen_multipage",
	"formulize_screen_listofentries",
	"formulize_screen_template",
    "formulize_screen_calendar",
    "formulize_screen_map",
    "formulize_entry_owner_groups",
	"formulize_application_form_link",
	"formulize_applications",
	"formulize_screen_form",
	"formulize_advanced_calculations",
	"formulize_group_filters",
	"formulize_groupscope_settings",
    "formulize_apikeys",
    "formulize_tokens",
    "formulize_passcodes"
);

// Everywhere a setting can refer to an element. The usage report, the clean up when an element is deleted, and the
// rewrite when its handle changes all work from this, through formulizeElementReferences
// (class/elementReferences.php), which documents the format. What an element type keeps in its own ele_value is
// declared on its handler, as a static $eleValueReferences.
//
// For each table: its key column, the usage report heading and how a row is named, and each column that can
// refer to an element - the shape of what is stored, what it holds, and what a deletion does with it. 'holds'
// is 'id' where only ids have ever been stored, 'handle' where Formulize saves handles, and 'either' where it
// now saves ids but older settings can still hold handles - a rename writes the element's id there, so the
// setting ends up the way a save would leave it today.
//
// Records are left out even where they mention an element - logs, people's "Other" answers, digest content -
// since they are a history of what happened rather than settings, and so is anything holding arbitrary values,
// like tokens and passcodes.
//
// The tables are listed in the order the usage report reads, screens first.
$modversion['formulize_element_references'] = array(

	'formulize_screen_calendar' => array(
		'key' => 'sid',
		'section' => '_AM_ELE_USAGE_SECTION_CALENDAR_SCREENS',
		'label' => '{title} (screen {sid})',
		'joins' => array(array('table' => 'formulize_screen', 'on' => array('sid' => 'sid'), 'columns' => array('title' => 'title'))),
		'columns' => array(
			'datasets' => array('shape' => 'property', 'class' => 'formulizeCalendarScreenDataset', 'class_file' => 'calendarScreen.php',
				'property' => 'datehandle', 'holds' => 'either', 'on_delete' => 'set', 'set_to' => '',
				'describe' => 'the date the entries are placed on, which the screen cannot be drawn without'),
		),
	),

	'formulize_screen_form' => array(
		'key' => 'sid',
		'section' => '_AM_ELE_USAGE_SECTION_FORM_SCREENS',
		'label' => '{title} (screen {sid})',
		'joins' => array(array('table' => 'formulize_screen', 'on' => array('sid' => 'sid'), 'columns' => array('title' => 'title'))),
		'columns' => array(
			// an empty list means "show every element", so a screen showing only this one is never emptied
			'formelements' => array('shape' => 'list', 'holds' => 'id', 'on_delete' => 'remove_unless_empty',
				'describe' => 'one of the elements chosen to appear',
				'describe_when_emptied' => 'the only element chosen to appear, so the screen will show nothing until another is chosen'),
			'elementdefaults' => array('shape' => 'keys', 'holds' => 'either', 'on_delete' => 'remove',
				'describe' => 'given a default value by the screen'),
		),
	),

	'formulize_screen_listofentries' => array(
		'key' => 'sid',
		'section' => '_AM_ELE_USAGE_SECTION_LIST_SCREENS',
		'label' => '{title} (screen {sid})',
		'joins' => array(array('table' => 'formulize_screen', 'on' => array('sid' => 'sid'), 'columns' => array('title' => 'title'))),
		'columns' => array(
			// each row is a column: the element at 0, ids since formulizeScreen::setVar converts them, handles in
			// rows not saved since. The search the column starts with is at 1, and can carry {handle} and
			// {search_handle} tokens, as a saved view's searches can.
			'advanceview' => array(
				array('shape' => 'rows', 'at' => 0, 'holds' => 'either', 'on_delete' => 'remove',
					'describe' => 'a column'),
				array('shape' => 'path', 'path' => '*.1', 'of' => array('shape' => 'tokens', 'token_prefixes' => array('', 'search_'))),
			),
			'hiddencolumns' => array('shape' => 'list', 'holds' => 'either', 'on_delete' => 'remove',
				'describe' => 'a hidden value column'),
			'decolumns' => array('shape' => 'list', 'holds' => 'either', 'on_delete' => 'remove',
				'describe' => 'an inline editable column'),
			'fundamental_filters' => array('shape' => 'conditions', 'holds' => 'either', 'on_delete' => 'remove',
				'describe' => 'a fundamental filter'),
			// a custom button's effects sit under numeric keys beside its own properties. The effect goes, the
			// button stays - removing someone's button is a bigger decision than removing an effect that cannot run.
			// The value an effect writes is PHP when it mentions $value - see processButtonValue().
			'customactions' => array(
				array('shape' => 'path', 'path' => '*.#.element', 'holds' => 'either', 'on_delete' => 'remove_parent',
					'describe' => 'the effect of a custom button'),
				array('shape' => 'path', 'path' => '*.#.value', 'of' => array('shape' => 'code')),
			),
		),
	),

	'formulize_screen_map' => array(
		'key' => 'sid',
		'section' => '_AM_ELE_USAGE_SECTION_MAP_SCREENS',
		'label' => '{title} (screen {sid})',
		'joins' => array(array('table' => 'formulize_screen', 'on' => array('sid' => 'sid'), 'columns' => array('title' => 'title'))),
		'columns' => array(
			'lat_element' => array('shape' => 'value', 'holds' => 'either', 'on_delete' => 'set', 'set_to' => '',
				'describe' => 'the latitude, which the screen cannot be drawn without'),
			'lng_element' => array('shape' => 'value', 'holds' => 'either', 'on_delete' => 'set', 'set_to' => '',
				'describe' => 'the longitude, which the screen cannot be drawn without'),
			'label_element' => array('shape' => 'value', 'holds' => 'either', 'on_delete' => 'set', 'set_to' => '',
				'describe' => 'the label on each pin'),
			'description_element' => array('shape' => 'value', 'holds' => 'either', 'on_delete' => 'set', 'set_to' => '',
				'describe' => 'the description on each pin'),
			// as a list screen's columns: the element at 0, the search the column starts with at 1
			'columns' => array(
				array('shape' => 'rows', 'at' => 0, 'holds' => 'either', 'on_delete' => 'remove',
					'describe' => 'a column'),
				array('shape' => 'path', 'path' => '*.1', 'of' => array('shape' => 'tokens', 'token_prefixes' => array('', 'search_'))),
			),
			'fundamental_filters' => array('shape' => 'conditions', 'holds' => 'either', 'on_delete' => 'remove',
				'describe' => 'a fundamental filter'),
		),
	),

	'formulize_screen_multipage' => array(
		'key' => 'sid',
		'section' => '_AM_ELE_USAGE_SECTION_FORM_SCREENS',
		'label' => '{title} (screen {sid})',
		'joins' => array(array('table' => 'formulize_screen', 'on' => array('sid' => 'sid'), 'columns' => array('title' => 'title'))),
		'columns' => array(
			// removed from the pages by formulizeMultiPageScreenHandler::removeElementsFromScreens(), which delete()
			// calls, so only described here - including any page it would leave empty. A custom page is stored as
			// array('PHP', code) or array('HTML', markup) instead of a list of elements.
			'pages' => array(
				array('shape' => 'each', 'of' => array('shape' => 'list'), 'holds' => 'id', 'on_delete' => 'elsewhere',
					'describe_pages' => true),
				// only a PHP page's code is run; an HTML page is printed as it is, and its attributes are not references
				array('shape' => 'path', 'path' => '*.1', 'where' => array(0 => 'PHP'), 'of' => array('shape' => 'code')),
			),
			// keyed by page number
			'conditions' => array('shape' => 'each', 'of' => array('shape' => 'conditions'), 'holds' => 'either', 'on_delete' => 'remove',
				'describe' => 'in the conditions that decide whether a page is shown'),
			'elementdefaults' => array('shape' => 'keys', 'holds' => 'either', 'on_delete' => 'remove',
				'describe' => 'given a default value by the screen'),
		),
	),

	'formulize_screen_template' => array(
		'key' => 'templateid',
		'section' => '_AM_ELE_USAGE_SECTION_FORM_SCREENS',
		'label' => '{title} (screen {sid})',
		'joins' => array(array('table' => 'formulize_screen', 'on' => array('sid' => 'sid'), 'columns' => array('title' => 'title'))),
		'columns' => array(
			'custom_code' => array('shape' => 'code'),
			'template' => array('shape' => 'code'),
		),
	),

	'formulize_screen' => array(
		'key' => 'sid',
		'section' => '_AM_ELE_USAGE_SECTION_SCREEN_ADDRESSES',
		'label' => '{title} (screen {sid})',
		'columns' => array(
			'rewriteruleElement' => array('shape' => 'value', 'holds' => 'id', 'on_delete' => 'set', 'set_to' => 0,
				'describe' => 'the address will go back to using the entry id'),
		),
	),

	'formulize' => array(
		'key' => 'ele_id',
		'own_row' => true, // a row is an element, and the element's own row is not a reference to it
		'section' => '_AM_ELE_USAGE_SECTION_OTHER_ELEMENTS',
		'label' => "the {ele_type} element '{ele_handle}' (form {id_form})",
		'columns' => array(
			'ele_caption' => array('shape' => 'tokens'),
			'ele_desc' => array('shape' => 'tokens'),
			'ele_filtersettings' => array('shape' => 'conditions', 'holds' => 'either', 'on_delete' => 'remove',
				'describe' => 'in the conditions that decide whether it is shown'),
			'ele_disabledconditions' => array('shape' => 'conditions', 'holds' => 'either', 'on_delete' => 'remove',
				'describe' => 'in the conditions that decide whether it is read only'),
			'ele_dynamicdefault_conditions' => array('shape' => 'conditions', 'holds' => 'either', 'on_delete' => 'remove',
				'describe' => 'in the conditions on its dynamic default value'),
			// with nothing left to read the default out of, its conditions go too, as the element editor does
			'ele_dynamicdefault_source' => array('shape' => 'value', 'holds' => 'id', 'on_delete' => 'set', 'set_to' => 0,
				'also_set' => array('ele_dynamicdefault_conditions' => 'a:0:{}'),
				'describe' => 'as the source of its dynamic default value'),
			'ele_value' => array('shape' => 'per_type'),
		),
	),

	'formulize_saved_views' => array(
		'key' => 'sv_id',
		'section' => '_AM_ELE_USAGE_SECTION_SAVED_VIEWS',
		'label' => '{sv_name} (view {sv_id})',
		'columns' => array(
			// a column can carry the hidden column prefix, when it was only included for a persistent search on it.
			// The searches line up with the columns by position, so they lose the same positions.
			'sv_oldcols' => array('shape' => 'list', 'delimiter' => ',', 'prefix' => 'formulizeSavedViewsHandler::HIDDEN_COLUMN_PREFIX',
				'holds' => 'either', 'parallel' => array('sv_quicksearches' => 'formulizeSavedViewsHandler::SEARCH_DELIMITER'),
				'on_delete' => 'remove', 'describe' => 'a column'),
			// a view saved from a screen that sorts on several columns holds them all, with their directions in step in sv_order
			'sv_sort' => array('shape' => 'list', 'delimiter' => ',', 'holds' => 'either', 'parallel' => array('sv_order' => ','),
				'on_delete' => 'remove', 'describe' => 'a column it is sorted by'),
			// the calculations name their columns by id, and three settings run parallel to them
			'sv_calc_cols' => array('shape' => 'list', 'delimiter' => 'formulizeSavedViewsHandler::CALCULATION_DELIMITER', 'holds' => 'id',
				'parallel' => array('sv_calc_calcs' => 'formulizeSavedViewsHandler::CALCULATION_DELIMITER',
					'sv_calc_blanks' => 'formulizeSavedViewsHandler::CALCULATION_DELIMITER',
					'sv_calc_grouping' => 'formulizeSavedViewsHandler::CALCULATION_DELIMITER'),
				'on_delete' => 'remove', 'describe' => 'a calculation'),
			// what each calculation is grouped by: per calculated column, per calculation, then the element ids
			'sv_calc_grouping' => array('shape' => 'list', 'delimiter' => array('formulizeSavedViewsHandler::CALCULATION_DELIMITER', ',', '!@^%*'),
				'holds' => 'id', 'on_delete' => 'remove', 'describe' => 'what a calculation is grouped by'),
			// a search can name another column's search box, {search_handle}
			'sv_quicksearches' => array('shape' => 'tokens', 'token_prefixes' => array('', 'search_')),
			// the columns whose search boxes the view puts above the list for people to use
			'sv_pubfilters' => array('shape' => 'list', 'delimiter' => ',', 'holds' => 'either', 'on_delete' => 'remove',
				'describe' => 'a search box it offers'),
		),
	),

	'formulize_group_filters' => array(
		'key' => 'filterid',
		'section' => '_AM_ELE_USAGE_SECTION_GROUP_FILTERS',
		'label' => '{group_name} (group {groupid})',
		'joins' => array(
			array('table' => 'groups', 'on' => array('groupid' => 'groupid'), 'columns' => array('name' => 'group_name')),
			array('table' => 'formulize_id', 'on' => array('fid' => 'id_form'), 'columns' => array('form_title' => 'form_title')),
		),
		'columns' => array(
			'filter' => array('shape' => 'conditions', 'holds' => 'either', 'on_delete' => 'remove',
				'describe' => 'which entries of {form_title} they can see'),
		),
	),

	'formulize_id' => array(
		'key' => 'id_form',
		'section' => '_AM_ELE_USAGE_SECTION_FORM_SETTINGS',
		'label' => '{form_title} (form {id_form})',
		'columns' => array(
			// key 0 holds the conditions that apply generally, every other key is a group id
			'entries_are_users_conditions' => array('shape' => 'each', 'of' => array('shape' => 'conditions'), 'holds' => 'either',
				'on_delete' => 'remove', 'describe' => 'in the conditions that decide whether an entry becomes a user account'),
			'entries_are_users_default_groups_element_links' => array('shape' => 'each', 'of' => array('shape' => 'list'), 'holds' => 'id',
				'on_delete' => 'remove', 'describe' => 'as what connects a group to the entry that decides who is in it'),
			// cleared by formulizeElementsHandler::delete(), and reported separately as is_the_principal_identifier
			'pi' => array('shape' => 'value', 'holds' => 'id', 'on_delete' => 'elsewhere',
				'describe' => 'as its principal identifier'),
			// the columns of the form's default list, with a separator before the first one. Very old forms hold
			// captions here instead of ids, which this does not match.
			'headerlist' => array('shape' => 'list', 'delimiter' => '*=+*:', 'holds' => 'id', 'on_delete' => 'remove',
				'describe' => 'one of the columns of its default list'),
		),
	),

	'formulize_framework_links' => array(
		'key' => 'fl_id',
		'section' => '_AM_ELE_USAGE_SECTION_RELATIONSHIPS',
		'label' => '{frame_name} - joins {form1_title} (form {fl_form1_id}) to {form2_title} (form {fl_form2_id})',
		'joins' => array(
			array('table' => 'formulize_frameworks', 'on' => array('fl_frame_id' => 'frame_id'), 'columns' => array('frame_name' => 'frame_name')),
			array('table' => 'formulize_id', 'on' => array('fl_form1_id' => 'id_form'), 'columns' => array('form_title' => 'form1_title')),
			array('table' => 'formulize_id', 'on' => array('fl_form2_id' => 'id_form'), 'columns' => array('form_title' => 'form2_title')),
		),
		'columns' => array(
			// a key only counts on the side of the link that is the element's own form. Links are removed by
			// formulizeFrameworksHandler, through formulizeElementsHandler::delete().
			'fl_key1' => array('shape' => 'value', 'holds' => 'id', 'when_form_is' => 'fl_form1_id', 'on_delete' => 'elsewhere',
				'describe' => 'what the connection is made on'),
			'fl_key2' => array('shape' => 'value', 'holds' => 'id', 'when_form_is' => 'fl_form2_id', 'on_delete' => 'elsewhere',
				'describe' => 'what the connection is made on'),
		),
	),

	'formulize_notification_conditions' => array(
		'key' => 'not_cons_id',
		'section' => '_AM_ELE_USAGE_SECTION_NOTIFICATIONS',
		'label' => 'a notification on {form_title} (form {not_cons_fid})',
		'joins' => array(array('table' => 'formulize_id', 'on' => array('not_cons_fid' => 'id_form'), 'columns' => array('form_title' => 'form_title'))),
		'columns' => array(
			// Left for a person to change rather than cleaned up: taking away a notification's last condition, or the
			// element that says who receives it, changes who it goes to, which is not a decision a deletion can make.
			// The conditions are three serialized arrays inside the serialized value - see include/setnot.php.
			// Unlike other conditions, a term here is never read for {handle} tokens, so none are looked for.
			'not_cons_con' => array('shape' => 'conditions', 'serialized_parts' => true, 'holds' => 'id', 'token_prefixes' => array(), 'on_delete' => 'report',
				'describe' => 'in the conditions that decide when it is sent, which you will need to change yourself'),
			'not_cons_elementuids' => array('shape' => 'value', 'holds' => 'id', 'on_delete' => 'report',
				'describe' => 'the element that says who receives it, which you will need to change yourself'),
			'not_cons_linkcreator' => array('shape' => 'value', 'holds' => 'id', 'on_delete' => 'report',
				'describe' => 'the element whose linked entry\'s creator receives it, which you will need to change yourself'),
			'not_cons_elementemail' => array('shape' => 'value', 'holds' => 'id', 'on_delete' => 'report',
				'describe' => 'the element holding the address it is sent to, which you will need to change yourself'),
			'not_cons_subject' => array('shape' => 'tags'),
		),
	),

	'formulize_advanced_calculations' => array(
		'key' => 'acid',
		'section' => '_AM_ELE_USAGE_SECTION_FORM_SETTINGS',
		'label' => 'the advanced calculation {name} (calculation {acid})',
		'columns' => array(
			'input' => array('shape' => 'code'),
			'output' => array('shape' => 'code'),
			// each step is a query, which names a column by the bare handle, and PHP around it
			'steps' => array(
				array('shape' => 'path', 'path' => '#.sql', 'of' => array('shape' => 'code', 'sql' => true)),
				array('shape' => 'path', 'path' => '#.preCalculate|calculate|postCalculate', 'of' => array('shape' => 'code')),
			),
			// each filter can be tied to an element, by id, or to none
			'fltr_grps' => array('shape' => 'path', 'path' => '#.form', 'holds' => 'id', 'on_delete' => 'set', 'set_to' => 'none',
				'describe' => 'the element one of its filters is tied to'),
		),
	),
);

// tables that we skip when only dealing with groups in common
// formulize_tokens is presumed to be managed independently in different site instances with different groups
$modversion['formulize_group_tables'] = array(
    "groups",
    "group_lists",
    "formulize_tokens"
);

// fields that indicate a critical group that the record in the table is about
// so we would not sync records related to this group when only syncing groups in common
$modversion['formulize_group_id_fields'] = array(
    "group_permission" => array('groupid'),
    "formulize_entry_owner_groups" => array('groupid'),
    "formulize_groupscope_settings" => array('groupid', 'view_groupid'),
    "formulize_group_filters" => array('groupid'),
    "formulize_notification_conditions" => array('not_cons_groupid'),
    "formulize_menu_permissions" => array('group_id')
);

// fields that are foreign keys to groups, but the record in the table is not about the group
// so we would sync changes in this record, except for the group-related info, when we're only syncing groups in common
// PROBLEM IS THERE ARE DIFFERENT METHODS FOR UNEARTHING THE DAMNED GROUP ID!
// REPURPOSE THE MANAGEPERMISSIONS LOGIC FOR GETTING AT THE VALUES??
$modversion['formulize_group_id_embedded'] = array(
    "formulize"=>array("ele_value","ele_display","ele_disabled"),
    "formulize_screen_listofentries"=>array("customactions"),
    "formulize_saved_views"=>array("sv_pubgroups")
);

/*
 * Table metadata general structure
 *
 * table_name: {
 *      table_fields: {}
 *      table_joins: {
 *          { table_to_join,
 *          fields_table_is_joined_on: {table1field, table2field}
 *          fields_to_return }
 *      }
 * }
 *
 */
$modversion['table_metadata'] = array(
    "formulize" => array(
        "fields" => array("ele_caption", "ele_type"),
        "joins" => array(
            array(
                "join_table" => "formulize_id",
                "join_field" => array("id_form", "id_form"),
                "field" => "form_title"
            )
        )
    ),
    "formulize_id" => array(
        "fields" => array("form_title"),
        "joins" => array()
    ),
    "formulize_menu" => array(),
    "formulize_menu_links" => array(
        "fields" => array("link_text"),
        "joins" => array()
    ),
    "formulize_menu_permissions" => array (
        "fields" => array(),
        "joins" => array(
            array(
                "join_table" => "formulize_menu_links",
                "join_field" => array("menu_id", "menu_id"),
                "field" => "link_text"
            ),
			array(
				"join_table" => "groups",
				"join_field" => array("group_id", "groupid"),
				"field" => "name"
			)
        ),
    ),
    "formulize_frameworks" => array(
        "fields" => array("frame_name"),
        "joins" => array()
    ),
    "formulize_framework_forms" => array(),
    "formulize_framework_elements" => array(),
    "formulize_framework_links" => array(
        "fields" => array(),
        "joins" => array(
            array(
                "join_table" => "formulize_frameworks",
                "join_field" => array("fl_frame_id", "frame_id"),
                "field" => "frame_name"
            ),
            array(
                "join_table" => "formulize_id",
                "join_field" => array("fl_form1_id", "id_form"),
                "field" => "form_title"
            ),
            array(
                "join_table" => "formulize_id",
                "join_field" => array("fl_form2_id", "id_form"),
                "field" => "form_title"
            )
        )
    ),
    "formulize_menu_cats" => array(),
    "formulize_saved_views" => array(
        "fields" => array("sv_name"),
        "joins" => array()
    ),
    "group_lists" => array(
        "fields" => array("gl_name"),
        "joins" => array()
    ),
    "formulize_other" => array(),
    "formulize_notification_conditions" => array(
        "fields" => array(),
        "joins" => array(
            array(
                "join_table" => "formulize_id",
                "join_field" => array("not_cons_fid", "id_form"),
                "field" => "form_title"
            )
        )
    ),
    "formulize_screen" => array(
        "fields" => array("title", "type"),
        "joins" => array(
            array(
                "join_table" => "formulize_id",
                "join_field" => array("fid", "id_form"),
                "field" => "form_title"
            )
        )
    ),
    "formulize_screen_multipage" => array(
        "fields" => array(),
        "joins" => array(
            array(
                "join_table" => "formulize_screen",
                "join_field" => array("sid", "sid"),
                "field" => "title"
            )
        )
    ),
    "formulize_screen_listofentries" => array(
        "fields" => array(),
        "joins" => array(
            array(
                "join_table" => "formulize_screen",
                "join_field" => array("sid", "sid"),
                "field" => "title"
            )
        )
    ),
    "formulize_screen_template" => array(
        "fields" => array(),
        "joins" => array(
            array(
                "join_table" => "formulize_screen",
                "join_field" => array("sid", "sid"),
                "field" => "title"
            )
        )
    ),
		"formulize_screen_map" => array(
        "fields" => array(),
        "joins" => array(
            array(
                "join_table" => "formulize_screen",
                "join_field" => array("sid", "sid"),
                "field" => "title"
            )
        )
    ),
    "formulize_entry_owner_groups" => array(),
    "formulize_application_form_link" => array(
        "fields" => array(),
        "joins" => array(
            array(
                    "join_table" => "formulize_applications",
                    "join_field" => array("appid", "appid"),
                "field" => "name"
                ),
                array(
                    "join_table" => "formulize_id",
                    "join_field" => array("fid", "id_form"),
                    "field" => "form_title"
                )
            )
    ),
    "formulize_applications" => array(
        "fields" => array("name"),
        "joins" => array()
    ),
    "formulize_screen_form" => array(
        "fields" => array(),
        "joins" => array(
            array(
                "join_table" => "formulize_screen",
                "join_field" => array("sid", "sid"),
                "field" => "title"
            )
        )
    ),
    "formulize_screen_calendar" => array(
        "fields" => array(),
        "joins" => array(
            array(
                "join_table" => "formulize_screen",
                "join_field" => array("sid", "sid"),
                "field" => "title"
            )
        )
    ),
    "formulize_advanced_calculations" => array(
        "fields" => array("name"),
        "joins" => array()
    ),
    "formulize_group_filters" => array(
        "fields" => array(),
        "joins" => array(
            array(
                "join_table" => "formulize_id",
                "join_field" => array("fid", "id_form"),
                "field" => "form_title"
            ),
            array(
                "join_table" => "groups",
                "join_field" => array("groupid", "groupid"),
                "field" => "name"
            )
        )
    ),
    "formulize_groupscope_settings" => array(
        "fields" => array(),
        "joins" => array(
            array(
                "join_table" => "formulize_id",
                "join_field" => array("fid", "id_form"),
                "field" => "form_title"
            ),
            array(
                "join_table" => "groups",
                "join_field" => array("groupid", "groupid"),
                "field" => "name"
            )
        )
    ),
    "formulize_procedure_logs" => array(),
    "formulize_procedure_logs_params" => array(),
    "formulize_deletion_logs" => array(),
    "groups" => array(
        "fields" => array("name", "group_type"),
        "joins" => array()
    ),
	"group_permission" => array(
		"fields" => array("gperm_name"),
		"joins" => array(
			array(
				"join_table" => "groups",
				"join_field" => array("gperm_groupid", "groupid"),
				"field" => "name"
			),
			array(
				"join_table" => "formulize_id",
				"join_field" => array("gperm_itemid", "id_form"),
				"field" => "form_title"
			)
		)
	),
    "formulize_entry_owner_groups" => array(
        "fields" => array("entry_id"),
        "joins" => array(
            array(
                "join_table" => "formulize_id",
                "join_field" => array("fid", "id_form"),
                "field" => "form_title"
            ),
            array(
                "join_table" => "groups",
                "join_field" => array("groupid", "groupid"),
                "field" => "name"
            )
        )
	),
    "formulize_apikeys" => array(
        "fields" => array("uid","apikey"),
        "joins" => array()
    ),
    "formulize_tokens" => array(
        "fields" => array("groups","tokenkey"),
        "joins" => array()
    ),
    "formulize_passcodes" => array(
        "fields" => array("passcode", "screen"),
        "joins" => array()
    )
);


// Admin things
$modversion['hasAdmin'] = 1;
$modversion['adminindex'] = "admin/ui.php";
$modversion['adminmenu'] = "admin/menu.php";

// Menu -- content in main menu block
$modversion['hasMain'] = 1;


// Templates

// Need to include templates for any custom element types first
// custom element classes must contain "Element.php" as the final part of the filename
$classFiles = scandir(XOOPS_ROOT_PATH."/modules/formulize/class/");
$customElements = array();
foreach($classFiles as $thisFile) {
	if(substr($thisFile, -11)=="Element.php") {
		$customType = substr($thisFile, 0, strpos($thisFile, "Element.php"));
		$modversion['templates'][] = array('file' => 'admin/element_type_'.$customType.'.html',
    	'description'=>'');
		$modversion['templates'][] = array('file' => 'admin/element_type_'.$customType.'_advanced.html',
    	'description'=>'');
	}
}

$modversion['templates'][] = array(
	'file' => 'formulize_cat.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'formulize_application.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'calendar_month.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'calendar_mini_month.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'calendar_micro_month.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/ui.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/ui-tabs.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/ui-accordion.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/application_settings.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/application_forms.html',
	'description' => '');
$modversion['templates'][] = array(
    'file' => 'admin/application_menu_entries.html',
    'description' => '');
$modversion['templates'][] = array(
    'file' => 'admin/application_code.html',
    'description' => '');
$modversion['templates'][] = array(
    'file' => 'admin/application_menu_entries_sections.html',
    'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/application_screens.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/form_listing.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/form_settings.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/form_permissions.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/form_permissions_entry_group_panel.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/form_permissions_group_panel.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/form_screens.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/add_element_dialog.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/form_elements.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/form_elements_sections.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/form_advanced_calculations.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/application_relationships.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/application_relationships_sections.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/relationship_settings.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/relationship_common_values.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/screen_settings.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/screen_appearance.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/screen_relationships.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/element_names.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/element_options.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/element_display.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/element_advanced.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/element_type_checkbox.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/element_type_colorpick.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/element_type_date.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/element_type_time.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/element_type_derived.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/element_type_grid.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/element_type_captionedContent.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/element_type_fullWidthContent.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/element_type_radio.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/element_type_select.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/element_type_sep.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/element_type_subform.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/element_type_textarea.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/element_type_text.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/element_type_yn.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/element_type_userAccountDefaultValue.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/element_type_userAccountEmail.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/element_type_userAccountFirstName.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/element_type_userAccountLastName.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/element_type_userAccountPhone.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/element_type_userAccountUsername.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/home.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/home_sections.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/screen_list_entries.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/screen_list_custom.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/screen_list_custom_sections.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/screen_form_options.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/screen_list_buttons.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/screen_list_templates.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/screen_list_headings.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/screen_multipage_options.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/screen_multipage_text.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/screen_multipage_pages.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/screen_multipage_pages_sections.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/screen_multipage_pages_settings.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/screen_multipage_templates.html',
	'description' => '');
$modversion['templates'][] = array(
    'file' => 'admin/screen_template_options.html',
    'description' => '');
$modversion['templates'][] = array(
    'file' => 'admin/screen_template_templates.html',
    'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/element_apply_default_to_empty.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/element_recompute_dependent_derived.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/element_optionlist.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/element_linkedoptionlist.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/element_linkedfilter.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/element_linkedsortoptions.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/element_linkedandusernames_scopefilter.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/advanced_calculation_settings.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/advanced_calculation_input_output.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/advanced_calculation_steps.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/advanced_calculation_steps_sections.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/advanced_calculation_fltr_grp.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/advanced_calculation_fltr_grp_sections.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/import_template.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/export_template.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/synchronize.html',
	'description' => '');
	$modversion['templates'][] = array(
		'file' => 'admin/synchronize_sections.html',
		'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/config_sync.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/sync_import.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/sync_import_sections.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/managekeys.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/managetokens.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/logviewer.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/themeeditor.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/looks.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/element_options_delimiter_choice.html',
    'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/screen_map.html',
    'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/screen_map_templates.html',
    'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/screen_calendar_data_sections.html',
    'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/screen_calendar_data.html',
    'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/screen_calendar_templates.html',
    'description' => '');
$modversion['templates'][] = array(
	'file' => 'passcode.html',
    'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/multipage_navigation2-above.html',
    'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/multipage_navigation2-below.html',
    'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/multipage_navigation3-above.html',
    'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/multipage_navigation3-below.html',
    'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/alternate_fields_for_linked_elements.html',
    'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/screen_form_templates.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/screen_form_template_boxes.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'blocks/menu.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'blocks/menu_controller.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/mailusers.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/configsettings.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/configsubject.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/managepermissions.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/variable_template_help.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/relationship_listing.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/relationship_options.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/relationship_create_connection_pair.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/relationship_create_connection_multi.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/relationship_create_connection_common.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/primary_identifier_selection.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/relationship_create_connection_options.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/relationship_connection_options.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/element_autocomplete_allownew.html',
	'description' => '');
$modversion['templates'][] = array(
	'file' => 'admin/element_multiple_onoff.html',
	'description' => '');


//	Module Configs

// Form element settings...
$modversion['config'][1] = array(
	'name' => 't_width',
	'title' => "_MI_formulize_TEXT_WIDTH",
	'description' => '',
	'formtype' => 'textbox',
	'valuetype' => 'int',
	'default' => '30',
);
// $xoopsModuleConfig['t_max']
$modversion['config'][] = array(
	'name' => 't_max',
	'title' => '_MI_formulize_TEXT_MAX',
	'description' => '',
	'formtype' => 'textbox',
	'valuetype' => 'int',
	'default' => '255',
);
// $xoopsModuleConfig['ta_rows']
$modversion['config'][] = array(
	'name' => 'ta_rows',
	'title' => '_MI_formulize_TAREA_ROWS',
	'description' => '',
	'formtype' => 'textbox',
	'valuetype' => 'int',
	'default' => '5',
);
// $xoopsModuleConfig['ta_cols']
$modversion['config'][] = array(
	'name' => 'ta_cols',
	'title' => '_MI_formulize_TAREA_COLS',
	'description' => '',
	'formtype' => 'textbox',
	'valuetype' => 'int',
	'default' => '35',
);

$modversion['config'][] = array(
	'name' =>'number_decimals',
	'title' => '_MI_formulize_NUMBER_DECIMALS',
	'description' => '_MI_formulize_NUMBER_DECIMALS_DESC',
	'formtype' => 'textbox',
	'valuetype' => 'int',
	'default' => 0,
);

$modversion['config'][] = array(
	'name' =>'number_prefix',
	'title' => '_MI_formulize_NUMBER_PREFIX',
	'description' => '_MI_formulize_NUMBER_PREFIX_DESC',
	'formtype' => 'textbox',
	'valuetype' => 'text',
	'default' => "",
);

$modversion['config'][] = array(
	'name' =>'number_suffix',
	'title' => '_MI_formulize_NUMBER_SUFFIX',
	'description' => '_MI_formulize_NUMBER_SUFFIX_DESC',
	'formtype' => 'textbox',
	'valuetype' => 'text',
	'default' => "",
);

$modversion['config'][] = array(
	'name' =>'number_decimalsep',
	'title' => '_MI_formulize_NUMBER_DECIMALSEP',
	'description' => '',
	'formtype' => 'textbox',
	'valuetype' => 'text',
	'default' => ".",
);

$modversion['config'][] = array(
	'name' =>'number_sep',
	'title' => '_MI_formulize_NUMBER_SEP',
	'description' => '',
	'formtype' => 'textbox',
	'valuetype' => 'text',
	'default' => ",",
);

$modversion['config'][] = array(
	'name' => 'time_format',
	'title' => '_MI_formulize_TIME_FORMAT',
	'description' => '_MI_formulize_TIME_FORMAT_DESC',
	'formtype' => 'select',
	'valuetype' => 'text',
	'default' => '12',
	'options' => array(_MI_formulize_TIME_FORMAT_12 => '12', _MI_formulize_TIME_FORMAT_24 => '24'),
);

$modversion['config'][] = array(
	'name' => 'formulizeDefaultEditIconStyle',
	'title' => '_MI_formulize_DEFAULTEDITICONSTYLE',
	'description' => '_MI_formulize_DEFAULTEDITICONSTYLE_DESC',
	'formtype' => 'select',
	'valuetype' => 'int',
	'default' => 1,
	'options' => array(_MI_formulize_DEFAULTEDITICONSTYLE_PEN => 1, _MI_formulize_DEFAULTEDITICONSTYLE_MAGNIFIER => 2),
);

// Date/time display format strings (the _DATESTRING etc. constants that live in each
// language's global.php). Declared here as module prefs so a module update preserves
// them. The default is the constant's current value for the active language (captured
// at install/update time via the defined() guard, which keeps this safe even if
// global.php hasn't been loaded yet in some bootstrap path), so the fields are
// pre-filled with the format actually in use. Saving a non-empty value writes it to
// the generated file global.php includes (see icms_config_Item_Handler::insert);
// clearing a field reverts to the global.php default. Captions/labels are overridden
// in include/configsettings_registry.php, so the titles here are just fallbacks.
$modversion['config'][] = array(
	'name' => 'datestring',
	'title' => 'Date and time format',
	'description' => '',
	'formtype' => 'textbox',
	'valuetype' => 'text',
	'default' => defined('_DATESTRING') ? _DATESTRING : 'D M j, Y - g:ia',
);
$modversion['config'][] = array(
	'name' => 'shortdatestring',
	'title' => 'Short date format',
	'description' => '',
	'formtype' => 'textbox',
	'valuetype' => 'text',
	'default' => defined('_SHORTDATESTRING') ? _SHORTDATESTRING : 'Y-m-d',
);
$modversion['config'][] = array(
	'name' => 'shorttimestring',
	'title' => 'Short time format',
	'description' => '',
	'formtype' => 'textbox',
	'valuetype' => 'text',
	'default' => defined('_SHORTTIMESTRING') ? _SHORTTIMESTRING : 'g:ia',
);

$modversion['config'][] = array(
	'name' => 'delimeter',
	'title' => '_MI_formulize_DELIMETER',
	'description' => '',
	'formtype' => 'select',
	'valuetype' => 'text',
	'default' => 'br',
	'options' => array(_MI_formulize_DELIMETER_BR=>'br', _MI_formulize_DELIMETER_SPACE=>'space'),
);

// export stuff...
$modversion['config'][] = array(
	'name' => 'downloadDefaultToExcel',
	'title' => '_MI_formulize_DOWNLOADDEFAULT',
	'description' => '_MI_formulize_DOWNLOADDEFAULT_DESC',
	'formtype' => 'yesno',
	'valuetype' => 'int',
	'default' => 0,
);
$modversion['config'][] = array(
	'name' => 'exportIntroChar',
	'title' => '_MI_formulize_EXPORTINTROCHAR',
	'description' => '_MI_formulize_EXPORTINTROCHARDESC',
	'formtype' => 'select',
	'valuetype' => 'int',
	'default' => '1',
    'options' => array(
      _MI_formulize_EIC_BASIC=>1,
      _MI_formulize_EIC_ALWAYSAPOS=>2,
      _MI_formulize_EIC_ALWAYSTAB=>3,
      _MI_formulize_EIC_PLAIN=>4
        )
);

// form prefs...
$modversion['config'][] = array(
	'name' =>'show_empty_elements_when_read_only',
	'title' => '_MI_formulize_SHOW_EMPTY_ELEMENTS_WHEN_READ_ONLY',
	'description' => '_MI_formulize_SHOW_EMPTY_ELEMENTS_WHEN_READ_ONLY_DESC',
	'formtype' => 'yesno',
	'valuetype' => 'int',
	'default' => 0,
);
$modversion['config'][] = array(
	'name' => 'formulizeRevisionsForAllForms',
	'title' => '_MI_formulize_REVISIONSFORALLFORMS',
	'description' => '_MI_formulize_REVISIONSFORALLFORMS_DESC',
	'formtype' => 'yesno',
	'valuetype' => 'int',
	'default' => 0,
);

//printable view
// set printable view default to one (1)
// unless it does not exist in the database already, and there are forms in the database already
global $xoopsDB;
$printableViewButtonDefault = 1;
if($xoopsDB) {
	$printableViewConfigExists = q("SELECT 1 FROM ".$xoopsDB->prefix('config')." WHERE conf_name = 'formulizeShowPrintableViewButtons'");
	$formsExist = q("SELECT 1 FROM ".$xoopsDB->prefix('formulize_id')." WHERE id_form > 0");
	if($printableViewConfigExists == false AND $formsExist == true) {
		$printableViewButtonDefault = 0;
	}
}
$modversion['config'][] = array(
	'name' => 'formulizeShowPrintableViewButtons',
	'title' => '_MI_formulize_SHOWPRINTABLEVIEWBUTTONS',
	'description' => '_MI_formulize_SHOWPRINTABLEVIEWBUTTONS_DESC',
	'formtype' => 'yesno',
	'valuetype' => 'int',
	'default' => $printableViewButtonDefault,
);
$modversion['config'][] = array(
	'name' => 'printviewStylesheets',
	'title' => '_MI_formulize_PRINTVIEWSTYLESHEETS',
	'description' => '_MI_formulize_PRINTVIEWSTYLESHEETSDESC',
	'formtype' => 'textbox',
	'valuetype' => 'text',
	'default' => '',
);

// logging...
$modversion['config'][] = array(
	'name' => 'formulizeLoggingOnOff',
	'title' => '_MI_formulize_FORMULIZELOGGINGONOFF',
	'description' => '_MI_formulize_FORMULIZELOGGINGONOFFDESC',
	'formtype' => 'yesno',
	'valuetype' => 'int',
	'default' => 0,
);

$modversion['config'][] = array(
	'name' => 'formulizeLogFileLocation',
	'title' => '_MI_formulize_FORMULIZELOGFILELOCATION',
	'description' => '_MI_formulize_FORMULIZELOGFILELOCATIONDESC',
	'formtype' => 'textbox',
	'valuetype' => 'text',
	'default' => XOOPS_ROOT_PATH.'/logs',
);

$modversion['config'][] = array(
	'name' => 'formulizeLogFileStorageDurationHours',
	'title' => '_MI_formulize_formulizeLogFileStorageDurationHours',
	'description' => '_MI_formulize_formulizeLogFileStorageDurationHoursDESC',
	'formtype' => 'textbox',
	'valuetype' => 'int',
	'default' => '168',
);

// lists
$modversion['config'][] = array(
	'name' => 'LOE_limit',
	'title' => '_MI_formulize_LOE_limit',
	'description' => '_MI_formulize_LOE_limit_DESC',
	'formtype' => 'textbox',
	'valuetype' => 'int',
	'default' => '5000',
);
$modversion['config'][] = array(
	'name' =>'heading_help_link',
	'title' => '_MI_formulize_HEADING_HELP_LINK',
	'description' => '_MI_formulize_HEADING_HELP_LINK_DESC',
	'formtype' => 'yesno',
	'valuetype' => 'int',
	'default' => 1,
);

// two-factor authentication: how long a "remember this device" trust token lasts.
// Read (and clamped to 1-365) by tfa_rememberDeviceDays() in include/2fa/manage.php.
$modversion['config'][] = array(
	'name' => 'tfaRememberDeviceDays',
	'title' => '_MI_formulize_TFA_REMEMBER_DEVICE_DAYS',
	'description' => '_MI_formulize_TFA_REMEMBER_DEVICE_DAYS_DESC',
	'formtype' => 'textbox',
	'valuetype' => 'int',
	'default' => 30,
);

// AI config
$modversion['config'][] = array(
	'name' => 'formulizeAIAssistantEnabled',
	'title' => '_MI_formulize_AIASSISTANTENABLED',
	'description' => '_MI_formulize_AIASSISTANTENABLED_DESC',
	'formtype' => 'yesno',
	'valuetype' => 'int',
	'default' => 0,
);
$modversion['config'][] = array(
	'name' => 'formulizeAIAssistantGroups',
	'title' => '_MI_formulize_AIASSISTANTGROUPS',
	'description' => '_MI_formulize_AIASSISTANTGROUPS_DESC',
	'formtype' => 'group_multi',
	'valuetype' => 'array',
	'default' => array(1),
);
$modversion['config'][] = array(
	'name' => 'formulizeMCPServerEnabled',
	'title' => '_MI_formulize_MCPSERVERENABLED',
	'description' => '_MI_formulize_MCPSERVERENABLED_DESC',
	'formtype' => 'yesno',
	'valuetype' => 'int',
	'default' => 0,
);
$modversion['config'][] = array(
	'name' => 'system_specific_instructions',
	'title' => '_MI_formulize_SYSTEM_SPECIFIC_INSTRUCTIONS',
	'description' => '_MI_formulize_SYSTEM_SPECIFIC_INSTRUCTIONS_DESC',
	'formtype' => 'textsarea',
	'valuetype' => 'text',
	'default' => ''
);

// Administrator-specified AI assistant configuration.
// Each defaults to "user specified", which is the behaviour the assistant had before
// these settings existed: every user picks their own provider, key, model and tools.
// Choosing anything else locks that choice site-wide and removes it from the
// assistant's own settings panel. See modules/formulize/include/aiadminconfig.php.
$modversion['config'][] = array(
	'name' => 'formulizeAIProvider',
	'title' => '_MI_formulize_AIPROVIDER',
	'description' => '_MI_formulize_AIPROVIDER_DESC',
	'formtype' => 'select',
	'valuetype' => 'text',
	'default' => 'userspecified',
	'options' => array(
		_MI_formulize_AIPROVIDER_USERSPECIFIED => 'userspecified',
		_MI_formulize_AIPROVIDER_CLAUDE => 'claude',
		_MI_formulize_AIPROVIDER_GEMINI => 'gemini',
		_MI_formulize_AIPROVIDER_OPENAI => 'openai',
		_MI_formulize_AIPROVIDER_OLLAMA => 'ollama',
	),
);
$modversion['config'][] = array(
	'name' => 'formulizeAIModel',
	'title' => '_MI_formulize_AIMODEL',
	'description' => '_MI_formulize_AIMODEL_DESC',
	'formtype' => 'textbox',
	'valuetype' => 'text',
	'default' => '',
);
// Rendered by a Formulize-specific formtype that never writes the key into the page or
// the config table - it is stored encrypted in formulize_ai_keys under uid -1.
$modversion['config'][] = array(
	'name' => 'formulizeAIApiKey',
	'title' => '_MI_formulize_AIAPIKEY',
	'description' => '_MI_formulize_AIAPIKEY_DESC',
	'formtype' => 'aikey',
	'valuetype' => 'text',
	'default' => '',
);
$modversion['config'][] = array(
	'name' => 'formulizeAIContextLimit',
	'title' => '_MI_formulize_AICONTEXTLIMIT',
	'description' => '_MI_formulize_AICONTEXTLIMIT_DESC',
	'formtype' => 'textbox',
	'valuetype' => 'int',
	'default' => 0,
);
$modversion['config'][] = array(
	'name' => 'formulizeAIOllamaBaseUrl',
	'title' => '_MI_formulize_AIOLLAMABASEURL',
	'description' => '_MI_formulize_AIOLLAMABASEURL_DESC',
	'formtype' => 'textbox',
	'valuetype' => 'text',
	'default' => 'http://localhost:11434',
);
$modversion['config'][] = array(
	'name' => 'formulizeAIToolAccess',
	'title' => '_MI_formulize_AITOOLACCESS',
	'description' => '_MI_formulize_AITOOLACCESS_DESC',
	'formtype' => 'select',
	'valuetype' => 'text',
	'default' => 'userspecified',
	'options' => array(
		_MI_formulize_AITOOLACCESS_USERSPECIFIED => 'userspecified',
		_MI_formulize_AITOOLACCESS_READ => 'read',
		_MI_formulize_AITOOLACCESS_WRITE => 'write',
		_MI_formulize_AITOOLACCESS_MANAGE => 'manage',
		_MI_formulize_AITOOLACCESS_ALL => 'all',
		_MI_formulize_AITOOLACCESS_CUSTOM => 'custom',
	),
);
// Rendered by a Formulize-specific formtype that builds its checkboxes from the live
// MCP tool registry, so the list cannot go stale as tools are added.
$modversion['config'][] = array(
	'name' => 'formulizeAIToolList',
	'title' => '_MI_formulize_AITOOLLIST',
	'description' => '_MI_formulize_AITOOLLIST_DESC',
	'formtype' => 'aitools',
	'valuetype' => 'array',
	'default' => array(),
);
// The page width each type of screen starts with, as type => 'look' or 'full'. Rendered by a
// Formulize-specific formtype that lists the types of screen read from the screen classes, so a new
// type is offered without a new setting. A type with no entry starts with its class's own default
// (see formulize_defaultPageWidthForScreenType).
$modversion['config'][] = array(
	'name' => 'formulizeScreenWidthDefaults',
	'title' => '_MI_formulize_SCREENWIDTHDEFAULTS',
	'description' => '_MI_formulize_SCREENWIDTHDEFAULTS_DESC',
	'formtype' => 'screenwidths',
	'valuetype' => 'array',
	'default' => array(),
);
// Public self-registration: require an invitation token to create an account via signup.php.
$modversion['config'][] = array(
	'name' => 'requireTokenForSignup',
	'title' => '_MI_formulize_REQUIRETOKENFORSIGNUP',
	'description' => '_MI_formulize_REQUIRETOKENFORSIGNUP_DESC',
	'formtype' => 'yesno',
	'valuetype' => 'int',
	'default' => 0,
);
// system config
$modversion['config'][] = array(
	'name' => 'formulizeRewriteRulesEnabled',
	'title' => '_MI_formulize_rewriteRulesEnabled',
	'description' => '_MI_formulize_rewriteRulesEnabledDESC',
	'formtype' => 'yesno',
	'valuetype' => 'int',
	'default' => 0,
);
$modversion['config'][] = array(
	'name' => 'validateCode',
	'title' => '_MI_formulize_VALIDATECODE',
	'description' => '_MI_formulize_VALIDATECODE_DESC',
	'formtype' => 'yesno',
	'valuetype' => 'int',
	'default' => 1,
);
// Fresh installs default to 1 (enforce). Existing installs are provisioned with 0 (report-only) by the
// admin/patches/007 patch, so an upgrade does not suddenly alter how their data displays - see that patch.
$modversion['config'][] = array(
	'name' => 'formulizeEnforceHtmlPurification',
	'title' => '_MI_formulize_ENFORCEHTMLPURIFICATION',
	'description' => '_MI_formulize_ENFORCEHTMLPURIFICATION_DESC',
	'formtype' => 'yesno',
	'valuetype' => 'int',
	'default' => 1,
);
$modversion['config'][] = array(
	'name' => 'notifyByCron',
	'title' => '_MI_formulize_NOTIFYBYCRON',
	'description' => '_MI_formulize_NOTIFYBYCRONDESC',
	'formtype' => 'yesno',
	'valuetype' => 'int',
	'default' => '0',
);
$modversion['config'][] = array(
	'name' => 'formulizeEmbeddingEnabled',
	'title' => '_MI_formulize_EMBEDDINGENABLED',
	'description' => '_MI_formulize_EMBEDDINGENABLED_DESC',
	'formtype' => 'yesno',
	'valuetype' => 'int',
	'default' => 0,
);
$modversion['config'][] = array(
	'name' => 'formulizeFrameAncestors',
	'title' => '_MI_formulize_FRAMEANCESTORS',
	'description' => '_MI_formulize_FRAMEANCESTORS_DESC',
	'formtype' => 'textsarea',
	'valuetype' => 'text',
	'default' => '',
);
$modversion['config'][] = array(
	'name' => 'formulizeEmbedTheme',
	'title' => '_MI_formulize_EMBEDTHEME',
	'description' => '_MI_formulize_EMBEDTHEME_DESC',
	'formtype' => 'embedtheme',
	'valuetype' => 'text',
	'default' => 'formulize_embed',
);
// Installing the site as an app on a phone or computer (a progressive web app). See
// formulize_pwaFeaturesApply() in include/functions.php.
$modversion['config'][] = array(
	'name' => 'formulizePWAEnabled',
	'title' => '_MI_formulize_PWAENABLED',
	'description' => '_MI_formulize_PWAENABLED_DESC',
	'formtype' => 'yesno',
	'valuetype' => 'int',
	'default' => 1,
);
$modversion['config'][] = array(
	'name' => 'formulizePWAShortName',
	'title' => '_MI_formulize_PWASHORTNAME',
	'description' => '_MI_formulize_PWASHORTNAME_DESC',
	'formtype' => 'textbox',
	'valuetype' => 'text',
	'default' => '',
);
$modversion['config'][] = array(
	'name' => 'formulizePublicAPIEnabled',
	'title' => '_MI_formulize_PUBLICAPIENABLED',
	'description' => '_MI_formulize_PUBLICAPIENABLED_DESC',
	'formtype' => 'yesno',
	'valuetype' => 'int',
	'default' => 0,
);
$modversion['config'][] = array(
	'name' => 'formulizePublicAPIAllowedOrigins',
	'title' => '_MI_formulize_PUBLICAPIALLOWEDORIGINS',
	'description' => '_MI_formulize_PUBLICAPIALLOWEDORIGINS_DESC',
	'formtype' => 'textsarea',
	'valuetype' => 'text',
	'default' => '',
);
$modversion['config'][] = array(
	'name' => 'formulizeRemoteFileFetching',
	'title' => '_MI_formulize_REMOTEFILEFETCHING',
	'description' => '_MI_formulize_REMOTEFILEFETCHING_DESC',
	'formtype' => 'yesno',
	'valuetype' => 'int',
	'default' => 1,
);

// The basement...
$modversion['config'][] = array(
	'name' => 'useToken',
	'title' => '_MI_formulize_USETOKEN',
	'description' => '_MI_formulize_USETOKENDESC',
	'formtype' => 'yesno',
	'valuetype' => 'int',
	'default' => 1,
);
$modversion['config'][] = array(
	'name' => 'isSaveLocked',
	'title' => '_MI_formulize_ISSAVELOCKED',
	'description' => '_MI_formulize_ISSAVELOCKEDDESC',
	'formtype' => 'yesno',
	'valuetype' => 'int',
	'default' => 0
);
$modversion['config'][] = array(
	'name' => 'all_done_singles',
	'title' => '_MI_formulize_ALL_DONE_SINGLES',
	'description' => '_MI_formulize_SINGLESDESC',
	'formtype' => 'yesno',
	'valuetype' => 'int',
	'default' => 1,
);
$modversion['config'][] = array(
	'name' => 'useCache',
	'title' => '_MI_formulize_USECACHE',
	'description' => '_MI_formulize_USECACHEDESC',
	'formtype' => 'yesno',
	'valuetype' => 'int',
	'default' => 1,
);
$modversion['config'][] = array(
	'name' => 'logProcedure',
	'title' => '_MI_formulize_LOGPROCEDURE',
	'description' => '_MI_formulize_LOGPROCEDUREDESC',
	'formtype' => 'yesno',
	'valuetype' => 'int',
	'default' => 0,
);
$modversion['config'][] = array(
	'name' => 'debugDerivedValues',
	'title' => '_MI_formulize_DEBUGDERIVEDVALUES',
	'description' => '_MI_formulize_DEBUGDERIVEDVALUESDESC',
	'formtype' => 'yesno',
	'valuetype' => 'int',
	'default' => '0',
);
$modversion['config'][] = array(
	'name' => 'customScope',
	'title' => '_MI_formulize_CUSTOMSCOPE',
	'description' => '_MI_formulize_CUSTOMSCOPEDESC',
	'formtype' => 'textsarea',
	'valuetype' => 'text',
	'default' => '',
);
$modversion['config'][] = array(
	'name' => 'f7MenuTemplate',
	'title' => '_MI_formulize_F7MENUTEMPLATE',
	'description' => '_MI_formulize_F7MENUTEMPLATEDESC',
	'formtype' => 'yesno',
	'valuetype' => 'int',
	'default' => '1',
);
$modversion['config'][] = array(
	'name' => 'useOldCustomButtonEffectWriting',
	'title' => '_MI_formulize_USEOLDCUSTOMBUTTONEFFECTWRITING',
	'description' => '_MI_formulize_USEOLDCUSTOMBUTTONEFFECTWRITINGDESC',
	'formtype' => 'yesno',
	'valuetype' => 'int',
	'default' => '0',
);

// Appearance settings... where the Appearance page in the Formulize admin UI used to
// keep the site's colours, font and logo. They are now kept per theme, in the
// stylesheet generated for each theme, so nothing writes these items any more. They
// are still declared, and still read when a theme has no generated stylesheet yet, so
// that a site upgrading from a version that used them keeps the appearance it already
// had (see formulize_getLegacyAppearanceSettings). Removing the declaration would drop
// the rows on the next module update, and with them a site's settings, so it is left
// in place. The hidden formtype keeps them out of the system preferences form.
$appearanceConfigNames = array('appearance_primary', 'appearance_background', 'appearance_surface',
	'appearance_text', 'appearance_border', 'appearance_success', 'appearance_warning',
	'appearance_danger', 'appearance_info', 'appearance_font', 'appearance_customfont',
	'appearance_logo');
foreach($appearanceConfigNames as $appearanceConfigName) {
	$modversion['config'][] = array(
		'name' => $appearanceConfigName,
		'title' => '_MI_formulize_APPEARANCE_SETTING',
		'description' => '',
		'formtype' => 'hidden',
		'valuetype' => 'text',
		'default' => '',
	);
}

$modversion['blocks'][1] = array(
	'file' => "mymenu.php",
	'name' => _MI_formulizeMENU_BNAME,
	'description' => "",
	'show_func' => "block_formulizeMENU_show",
    'template' => 'menu_controller.html');

// Notifications -- added by jwe 10/10/04, removed for 2.0, reinstated for 2.2 with improved options
$modversion['hasNotification'] = 1;

$modversion['notification'] = array(
	'lookup_file' => 'include/notification.inc.php',
	'lookup_func' => 'form_item_info');

$modversion['notification']['category'][1] = array(
	'name' => 'form',
	'title' => _MI_formulize_NOTIFY_FORM,
	'description' => _MI_formulize_NOTIFY_FORM_DESC,
	'subscribe_from' => 'index.php',
	'item_name' => 'fid',
	'allow_bookmark' => 0,
);
$modversion['notification']['event'][1] = array(
	'name' => 'new_entry',
	'category' => 'form',
	'title' => _MI_formulize_NOTIFY_NEWENTRY,
	'caption' => _MI_formulize_NOTIFY_NEWENTRY_CAP,
	'description' => _MI_formulize_NOTIFY_NEWENTRY_DESC,
	'mail_template' => 'form_newentry',
	'mail_subject' => _MI_formulize_NOTIFY_NEWENTRY_MAILSUB,
);
$modversion['notification']['event'][] = array(
	'name' => 'update_entry',
	'category' => 'form',
	'title' => _MI_formulize_NOTIFY_UPENTRY,
	'caption' => _MI_formulize_NOTIFY_UPENTRY_CAP,
	'description' => _MI_formulize_NOTIFY_UPENTRY_DESC,
	'mail_template' => 'form_upentry',
	'mail_subject' => _MI_formulize_NOTIFY_UPENTRY_MAILSUB,
);
$modversion['notification']['event'][] = array(
	'name' => 'delete_entry',
	'category' => 'form',
	'title' => _MI_formulize_NOTIFY_DELENTRY,
	'caption' => _MI_formulize_NOTIFY_DELENTRY_CAP,
	'description' => _MI_formulize_NOTIFY_DELENTRY_DESC,
	'mail_template' => 'form_delentry',
	'mail_subject' => _MI_formulize_NOTIFY_DELENTRY_MAILSUB,
);

// override mail_template and mail_subject if necessary
if(isset($GLOBALS['formulize_notificationTemplateOverride'])) {
	$modversion['notification']['event'][1]['mail_template'] = $GLOBALS['formulize_notificationTemplateOverride'];
	$modversion['notification']['event'][2]['mail_template'] = $GLOBALS['formulize_notificationTemplateOverride'];
	$modversion['notification']['event'][3]['mail_template'] = $GLOBALS['formulize_notificationTemplateOverride'];
}
if(isset($GLOBALS['formulize_notificationSubjectOverride'])) {
	$modversion['notification']['event'][1]['mail_subject'] = $GLOBALS['formulize_notificationSubjectOverride'];
	$modversion['notification']['event'][2]['mail_subject'] = $GLOBALS['formulize_notificationSubjectOverride'];
	$modversion['notification']['event'][3]['mail_subject'] = $GLOBALS['formulize_notificationSubjectOverride'];
}
